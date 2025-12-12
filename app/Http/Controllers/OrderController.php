<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class OrderController extends Controller
{
    /**
     * Get the user's orders
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $orders = auth()->user()->orders()->latest()->get();
        
        // If request wants JSON (API call), return JSON
        if ($request->wantsJson()) {
            return response()->json([
                'orders' => $orders,
            ], 200);
        }
        
        // Otherwise return view
        return view('orders.index', compact('orders'));
    }

    /**
     * Show the create order form
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $user = auth()->user();
        
        // Get balances in format expected by component with rounding to 3 decimal places
        $balances = [];
        $balances['USD'] = [
            'symbol' => 'USD',
            'amount' => round($user->balance, 3),
            'locked_amount' => 0,
            'available' => round($user->balance, 3)
        ];
        
        $assets = $user->assets;
        foreach ($assets as $asset) {
            $balances[$asset->symbol] = [
                'symbol' => $asset->symbol,
                'amount' => round($asset->amount, 3),
                'locked_amount' => round($asset->locked_amount ?? 0, 3),
                'available' => round($asset->amount - ($asset->locked_amount ?? 0), 3)
            ];
        }
        
        return view('orders.create', compact('balances'));
    }

    /**
     * Show the orders overview page
     *
     * @return \Illuminate\View\View
     */
    public function overview()
    {
        return view('orders.overview');
    }

    /**
     * Create a new order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string',
            'side' => 'required|in:buy,sell',
            'price' => 'required|numeric|min:0.00000001',
            'amount' => 'required|numeric|min:0.00000001',
            'type' => 'sometimes|in:limit,market',
        ]);

        $user = auth()->user();
        $data = $request->all();
        $data['type'] = $data['type'] ?? 'limit';

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
            // Extract base symbol (e.g., BTC from BTC-USD)
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
            $asset->locked_amount = round(($asset->locked_amount ?? 0) + $amount, 3);
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

    /**
     * Cancel an order
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function cancel(Request $request, $id)
    {
        $order = auth()->user()->orders()->find($id);
        if (!$order) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }
            return redirect()->back()->with('error', 'Order not found');
        }
        
        // Check if order can be cancelled
        if ($order->status !== Order::STATUS_OPEN) {
            $statusLabel = $order->status === Order::STATUS_FILLED ? 'filled' : 'already cancelled';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot cancel order that is {$statusLabel}",
                ], 400);
            }
            return redirect()->back()->with('error', "Cannot cancel order that is {$statusLabel}");
        }
        
        DB::beginTransaction();
        
        try {
            $user = $order->user;
            
            if ($order->side === 'buy') {
                // Return locked USD to user's balance
                $user->balance = round($user->balance + $order->locked_usd, 3);
                $user->save();
            } else {
                // Unlock the asset
                $symbolParts = explode('-', $order->symbol);
                $baseSymbol = $symbolParts[0];
                $asset = $user->assets()->where('symbol', $baseSymbol)->first();
                
                if ($asset) {
                    $asset->locked_amount = round($asset->locked_amount - $order->remaining_amount, 3);
                    $asset->save();
                }
            }
            
            // Mark order as cancelled
            $order->status = Order::STATUS_CANCELLED;
            $order->locked_usd = 0;
            $order->save();
            
            DB::commit();
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'order' => $order,
                    'message' => 'Order cancelled and funds released successfully',
                ], 200);
            }
            
            return redirect()->back()->with('success', 'Order cancelled and funds released successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order cancellation failed: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to cancel order',
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to cancel order');
        }
    }

    /**
     * Match an order
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function match()
    {
        $orders = auth()->user()->orders()->where('status', Order::STATUS_OPEN)->get();
        foreach ($orders as $order) {
            $order->match();
        }
        return response()->json([
            'message' => 'Orders matched',
        ], 200);
    }

    /**
     * API endpoint for getting user's orders with filters
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiIndex(Request $request)
    {
        $query = auth()->user()->orders();
        
        // Apply filters
        if ($request->has('symbol') && $request->symbol) {
            $query->where('symbol', $request->symbol);
        }
        
        if ($request->has('side') && $request->side) {
            $query->where('side', $request->side);
        }
        
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        $orders = $query->latest()->get();
        
        // Add status labels for frontend with rounding to 3 decimal places
        $orders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'symbol' => $order->symbol,
                'side' => $order->side,
                'price' => round($order->price, 3),
                'amount' => round($order->amount, 3),
                'remaining_amount' => round($order->remaining_amount, 3),
                'filled_amount' => round($order->amount - $order->remaining_amount, 3),
                'status' => $order->status,
                'status_label' => $this->getStatusLabel($order->status),
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ];
        });
        
        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * Get orderbook for a symbol
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderbook(Request $request)
    {
        $symbol = $request->get('symbol', 'BTCUSD');
        
        // Normalize symbol format to match what's stored in DB
        // Frontend sends BTCUSD (no hyphen), backend stores BTC-USD (with hyphen)
        // Convert BTCUSD to BTC-USD
        if (strpos($symbol, '-') === false) {
            // Add hyphen before USD/USDT
            $symbol = preg_replace('/(BTC|ETH|USDT)(USD)$/', '$1-$2', $symbol);
        }
        
        // Get all open orders for this symbol
        $orders = Order::where('symbol', $symbol)
            ->where('status', Order::STATUS_OPEN)
            ->orderBy('price', 'desc')
            ->get();
        
        // Separate buy and sell orders
        $bids = $orders->filter(function ($order) {
            return $order->side === 'buy';
        })->values();
        
        $asks = $orders->filter(function ($order) {
            return $order->side === 'sell';
        })->values();
        
        // Calculate cumulative totals with rounding to 3 decimal places
        $cumulativeBid = 0;
        $bidsWithTotal = $bids->map(function ($order) use (&$cumulativeBid) {
            $cumulativeBid += $order->remaining_amount;
            return [
                'price' => round($order->price, 3),
                'amount' => round($order->remaining_amount, 3),
                'total' => round($cumulativeBid, 3),
            ];
        });
        
        $cumulativeAsk = 0;
        $asksWithTotal = $asks->sortBy('price')->map(function ($order) use (&$cumulativeAsk) {
            $cumulativeAsk += $order->remaining_amount;
            return [
                'price' => round($order->price, 3),
                'amount' => round($order->remaining_amount, 3),
                'total' => round($cumulativeAsk, 3),
            ];
        })->values();
        
        return response()->json([
            'success' => true,
            'symbol' => $symbol,
            'bids' => $bidsWithTotal,
            'asks' => $asksWithTotal,
        ]);
    }

    /**
     * Get status label from status code
     *
     * @param int $status
     * @return string
     */
    private function getStatusLabel($status)
    {
        switch ($status) {
            case Order::STATUS_OPEN:
                return 'Open';
            case Order::STATUS_FILLED:
                return 'Filled';
            case Order::STATUS_CANCELLED:
                return 'Cancelled';
            default:
                return 'Unknown';
        }
    }
}
