@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <balance-card
            title="Total Balance"
            :balance="{{ $stats['total_balance'] }}"
            :decimals="2"
            currency="USD"
            icon="wallet"
            subtitle="Available balance"
        ></balance-card>

        <balance-card
            title="Active Orders"
            :balance="{{ $stats['active_orders'] }}"
            :decimals="0"
            currency=""
            icon="orders"
            color="default"
            subtitle="Open orders"
        ></balance-card>

        <balance-card
            title="Total Trades"
            :balance="{{ $stats['total_trades'] }}"
            :decimals="0"
            currency=""
            icon="chart"
            color="default"
            subtitle="Completed trades"
        ></balance-card>
    </div>

    <!-- Assets and Recent Orders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- My Assets -->
        <asset-list
            :assets="{{ json_encode($assets) }}"
            title="My Assets"
            :loading="false"
            :show-view-all="true"
            :initial-visible-count="5"
        ></asset-list>

        <!-- Recent Orders -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Orders</h3>
                    <a href="{{ route('orders.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                        View All →
                    </a>
                </div>
            </div>

            @if($recentOrders->isEmpty())
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p>No recent orders</p>
                    <a href="{{ route('trading', ['symbol' => 'BTC-USD']) }}" class="mt-4 inline-block px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium">
                        Start Trading
                    </a>
                </div>
            @else
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($recentOrders as $order)
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $order->symbol }}</span>
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded {{ $order->side === 'buy' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                            {{ strtoupper($order->side) }}
                                        </span>
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded
                                            @if($order->status === 1) bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                            @elseif($order->status === 2) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                            @endif">
                                            @if($order->status === 1) Open
                                            @elseif($order->status === 2) Filled
                                            @else Cancelled
                                            @endif
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ number_format($order->amount, 8) }} @ {{ number_format($order->price, 8) }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $order->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('trading', ['symbol' => 'BTC-USD']) }}" class="flex items-center space-x-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <div>
                    <div class="font-semibold text-gray-900 dark:text-white">Trade BTC/USD</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Start trading Bitcoin</div>
                </div>
            </a>

            <a href="{{ route('orders.index') }}" class="flex items-center space-x-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <div>
                    <div class="font-semibold text-gray-900 dark:text-white">View Orders</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Manage your orders</div>
                </div>
            </a>

            <a href="{{ route('profile') }}" class="flex items-center space-x-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <div>
                    <div class="font-semibold text-gray-900 dark:text-white">My Profile</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">View account details</div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
