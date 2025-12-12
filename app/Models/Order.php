<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    const STATUS_OPEN = 1;
    const STATUS_FILLED = 2;
    const STATUS_CANCELLED = 3;

    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'remaining_amount',
        'status',
        'locked_usd',
    ];

    protected $casts = [
        'price' => 'decimal:18',
        'amount' => 'decimal:18',
        'remaining_amount' => 'decimal:18',
        'locked_usd' => 'decimal:18',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // OPTIONAL relations (if trades table is used)

    public function buyTrades()
    {
        return $this->hasMany(Trade::class, 'buy_order_id');
    }

    public function sellTrades()
    {
        return $this->hasMany(Trade::class, 'sell_order_id');
    }

    /**
     * Attempt to match this order with existing opposite orders
     * Rules:
     * 1. Full match only - no partial fills
     * 2. New BUY matches with first SELL where sell.price <= buy.price
     * 3. New SELL matches with first BUY where buy.price >= sell.price
     * 4. Charge 1.5% commission deducted from seller side
     * 
     * @return bool True if matched, false otherwise
     */
    public function match()
    {
        // Only match open orders
        if ($this->status !== self::STATUS_OPEN) {
            return false;
        }

        $matchingOrder = null;
        
        if ($this->side === 'buy') {
            // New BUY: Find first SELL where sell.price <= buy.price and amounts match
            $matchingOrder = self::where('symbol', $this->symbol)
                ->where('side', 'sell')
                ->where('status', self::STATUS_OPEN)
                ->where('price', '<=', $this->price)
                ->where('remaining_amount', $this->remaining_amount) // Full match only
                ->orderBy('price', 'asc') // Get lowest sell price first
                ->orderBy('created_at', 'asc') // FIFO for same price
                ->first();
        } else {
            // New SELL: Find first BUY where buy.price >= sell.price and amounts match
            $matchingOrder = self::where('symbol', $this->symbol)
                ->where('side', 'buy')
                ->where('status', self::STATUS_OPEN)
                ->where('price', '>=', $this->price)
                ->where('remaining_amount', $this->remaining_amount) // Full match only
                ->orderBy('price', 'desc') // Get highest buy price first
                ->orderBy('created_at', 'asc') // FIFO for same price
                ->first();
        }

        // No match found
        if (!$matchingOrder) {
            return false;
        }

        // Execute the trade
        return $this->executeTrade($matchingOrder);
    }

    /**
     * Execute a trade between this order and the matching order
     * 
     * @param Order $matchingOrder
     * @return bool
     */
    private function executeTrade(Order $matchingOrder)
    {
        DB::beginTransaction();
        
        try {
            // Determine buyer and seller
            $buyOrder = $this->side === 'buy' ? $this : $matchingOrder;
            $sellOrder = $this->side === 'sell' ? $this : $matchingOrder;
            
            $buyer = $buyOrder->user;
            $seller = $sellOrder->user;
            
            // Trade executes at the price of the resting order (the one that was placed first)
            $executionPrice = $matchingOrder->price;
            $amount = $this->remaining_amount; // Full match only
            
            // Calculate trade volume and commission (1.5% from seller)
            $tradeVolume = round($executionPrice * $amount, 3);
            $commission = round($tradeVolume * 0.015, 3); // 1.5%
            $sellerProceeds = round($tradeVolume - $commission, 3);
            
            // Extract base symbol (e.g., BTC from BTC-USD)
            $symbolParts = explode('-', $this->symbol);
            $baseSymbol = $symbolParts[0];
            
            // Update seller: Give USD (minus commission), unlock and remove asset
            $sellerAsset = $seller->assets()->where('symbol', $baseSymbol)->first();
            if (!$sellerAsset || $sellerAsset->locked_amount < $amount) {
                throw new \Exception('Seller asset not found or insufficient locked amount');
            }
            
            // Unlock and remove the asset from seller
            $sellerAsset->locked_amount = round($sellerAsset->locked_amount - $amount, 3);
            $sellerAsset->amount = round($sellerAsset->amount - $amount, 3);
            $sellerAsset->save();
            
            // Give seller the proceeds (minus commission)
            $seller->balance = round($seller->balance + $sellerProceeds, 3);
            $seller->save();
            
            // Update buyer: Give asset
            $buyerAsset = $buyer->assets()->firstOrCreate(
                ['symbol' => $baseSymbol],
                ['amount' => 0, 'locked_amount' => 0]
            );
            $buyerAsset->amount = round($buyerAsset->amount + $amount, 3);
            $buyerAsset->save();
            
            // Refund buyer if trade executed at lower price than locked
            // Buyer locked USD based on their buy price, but trade executes at seller's price
            $buyerLockedAmount = round($buyOrder->price * $amount, 3);
            $actualCost = $tradeVolume; // Executed at seller's price
            $refund = round($buyerLockedAmount - $actualCost, 3);
            if ($refund > 0) {
                $buyer->balance = round($buyer->balance + $refund, 3);
                $buyer->save();
            }
            
            // Mark both orders as filled
            $buyOrder->status = self::STATUS_FILLED;
            $buyOrder->remaining_amount = 0;
            $buyOrder->locked_usd = 0;
            $buyOrder->save();
            
            $sellOrder->status = self::STATUS_FILLED;
            $sellOrder->remaining_amount = 0;
            $sellOrder->locked_usd = 0;
            $sellOrder->save();
            
            // Create trade record
            $trade = Trade::create([
                'buy_order_id' => $buyOrder->id,
                'sell_order_id' => $sellOrder->id,
                'symbol' => $this->symbol,
                'price' => $executionPrice,
                'amount' => $amount,
                'commission' => $commission,
            ]);
            
            DB::commit();
            
            // Broadcast trade event to both parties
            event(new \App\Events\OrderMatched($trade, $buyOrder->user_id));
            event(new \App\Events\OrderMatched($trade, $sellOrder->user_id));
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Trade execution failed: ' . $e->getMessage());
            return false;
        }
    }
}
