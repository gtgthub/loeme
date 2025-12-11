<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        
        // Get balances in format expected by component
        $balances = [];
        $balances['USD'] = [
            'symbol' => 'USD',
            'amount' => $user->balance,
            'locked_amount' => 0,
            'available' => $user->balance
        ];
        
        $assets = $user->assets;
        foreach ($assets as $asset) {
            $balances[$asset->symbol] = [
                'symbol' => $asset->symbol,
                'amount' => $asset->amount,
                'locked_amount' => $asset->locked_amount ?? 0,
                'available' => $asset->amount - ($asset->locked_amount ?? 0)
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

        // Check if user has sufficient balance
        $total = $data['price'] * $data['amount'];
        
        if ($data['side'] === 'buy') {
            if ($user->balance < $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient USD balance'
                ], 400);
            }
        } else {
            // For sell orders, check if user has the asset
            // Extract base symbol (e.g., BTC from BTCUSD)
            $baseSymbol = preg_replace('/USD$/', '', $data['symbol']);
            $asset = $user->assets()->where('symbol', $baseSymbol)->first();
            
            if (!$asset || ($asset->amount - ($asset->locked_amount ?? 0)) < $data['amount']) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient {$baseSymbol} balance"
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
        
        $order->status = Order::STATUS_CANCELLED;
        $order->save();
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'order' => $order,
            ], 200);
        }
        
        return redirect()->back()->with('success', 'Order cancelled successfully');
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
        
        // Add status labels for frontend
        $orders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'symbol' => $order->symbol,
                'side' => $order->side,
                'price' => $order->price,
                'amount' => $order->amount,
                'remaining_amount' => $order->remaining_amount,
                'filled_amount' => $order->amount - $order->remaining_amount,
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
        
        // Calculate cumulative totals
        $cumulativeBid = 0;
        $bidsWithTotal = $bids->map(function ($order) use (&$cumulativeBid) {
            $cumulativeBid += $order->remaining_amount;
            return [
                'price' => $order->price,
                'amount' => $order->remaining_amount,
                'total' => $cumulativeBid,
            ];
        });
        
        $cumulativeAsk = 0;
        $asksWithTotal = $asks->sortBy('price')->map(function ($order) use (&$cumulativeAsk) {
            $cumulativeAsk += $order->remaining_amount;
            return [
                'price' => $order->price,
                'amount' => $order->remaining_amount,
                'total' => $cumulativeAsk,
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
