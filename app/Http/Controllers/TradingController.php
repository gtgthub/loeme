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
        
        // Get user's assets
        $userAssets = $user->assets;
        
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

        // Check if user has sufficient balance
        $total = $data['price'] * $data['amount'];
        
        if ($data['side'] === 'buy') {
            if ($user->balance < $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance'
                ], 400);
            }
        } else {
            // For sell orders, check if user has the asset
            $asset = $user->assets()->where('symbol', explode('-', $data['symbol'])[0])->first();
            if (!$asset || ($asset->amount - $asset->locked_amount) < $data['amount']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient assets'
                ], 400);
            }
        }

        // Create the order
        $order = $user->orders()->create([
            'symbol' => $data['symbol'],
            'side' => $data['side'],
            'price' => $data['price'],
            'amount' => $data['amount'],
            'remaining_amount' => $data['amount'],
            'status' => Order::STATUS_OPEN,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully',
            'order' => $order
        ], 201);
    }
}
