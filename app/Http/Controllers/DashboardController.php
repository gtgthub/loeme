<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the dashboard
     */
    public function index()
    {
        $user = auth()->user();
        
        // Get user's assets
        $assets = $user->assets;
        
        // Get recent orders
        $recentOrders = $user->orders()
            ->latest()
            ->take(10)
            ->get();
        
        // Calculate stats
        $stats = [
            'total_balance' => $user->balance,
            'active_orders' => $user->orders()
                ->where('status', Order::STATUS_OPEN)
                ->count(),
            'total_trades' => $user->trades_count,
        ];
        
        return view('dashboard.index', compact('user', 'assets', 'recentOrders', 'stats'));
    }
}
