<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TradingController extends Controller
{
    /**
     * Show the trading page
     */
    public function index($symbol = 'BTC-USD')
    {
        $user = auth()->user();
        
        // Get open orders for this symbol
        $openOrders = Order::where('symbol', $symbol)
            ->where('status', Order::STATUS_OPEN)
            ->orderBy('price', 'desc')
            ->get();
        
        // Get recent trades for this symbol
        $recentTrades = Trade::where('symbol', $symbol)
            ->latest('created_at')
            ->take(50)
            ->get();
        
        // Get user's assets and include USD balance
        $userAssets = $user->assets->toArray();
        
        // Calculate locked USD from open buy orders
        $lockedUsd = $user->orders()
            ->where('status', Order::STATUS_OPEN)
            ->where('side', 'buy')
            ->sum('locked_usd');
        
        // Add USD as a virtual asset
        array_unshift($userAssets, [
            'symbol' => 'USD',
            'amount' => $user->balance,
            'locked_amount' => $lockedUsd,
        ]);
        
        return view('trading.index', compact('symbol', 'openOrders', 'recentTrades', 'userAssets'));
    }

    /**
     * Place a new order
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|string',
            'side' => 'required|in:buy,sell',
            'price' => 'required|numeric|min:0.00000001',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $data = $validator->validated();

        // Round values to 3 decimal places
        $price = round($data['price'], 3);
        $amount = round($data['amount'], 3);
        $total = round($price * $amount, 3);
        
        if ($data['side'] === 'buy') {
            // Check if user has sufficient balance
            if ($user->balance < $total) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient USD balance. To BUY, you need \${$total} USD but you only have \${$user->balance} USD."
                ], 400);
            }
            
            // Deduct balance from user
            $user->balance = round($user->balance - $total, 3);
            $user->save();
            
            // Create the order with locked USD
            $order = $user->orders()->create([
                'symbol' => $data['symbol'],
                'side' => $data['side'],
                'price' => $price,
                'amount' => $amount,
                'remaining_amount' => $amount,
                'status' => Order::STATUS_OPEN,
                'locked_usd' => $total,
            ]);
        } else {
            // For sell orders, check if user has the asset
            $symbolParts = explode('-', $data['symbol']);
            $baseSymbol = $symbolParts[0];
            $asset = $user->assets()->where('symbol', $baseSymbol)->first();
            if (!$asset || ($asset->amount - ($asset->locked_amount ?? 0)) < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient {$baseSymbol} balance. To SELL {$baseSymbol}, you need to own {$baseSymbol} assets."
                ], 400);
            }
            
            // Lock the asset
            $asset->locked_amount = round($asset->locked_amount + $amount, 3);
            $asset->save();
            
            // Create the order with locked USD value
            $order = $user->orders()->create([
                'symbol' => $data['symbol'],
                'side' => $data['side'],
                'price' => $price,
                'amount' => $amount,
                'remaining_amount' => $amount,
                'status' => Order::STATUS_OPEN,
                'locked_usd' => $total,
            ]);
        }

        // Attempt to match the order immediately
        $matched = $order->match();

        return response()->json([
            'success' => true,
            'message' => $matched ? 'Order matched and filled successfully' : 'Order placed successfully',
            'order' => $order->fresh(), // Refresh to get updated status
            'matched' => $matched
        ], 201);
    }
}
