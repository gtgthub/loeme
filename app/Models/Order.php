<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'price' => 'decimal:18',
        'amount' => 'decimal:18',
        'remaining_amount' => 'decimal:18',
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

    public function match()
    {
        // Your matching logic here...
        
        // When a match is found and trade is created
        $trade = Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'symbol' => $this->symbol,
            'price' => $matchPrice,
            'amount' => $matchAmount,
        ]);
        
        // Broadcast to both users
        event(new OrderMatched($trade, $buyOrder->user_id));
        event(new OrderMatched($trade, $sellOrder->user_id));
    }
}
