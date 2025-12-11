@extends('layouts.app')

@section('title', 'Trading')
@section('page-title', 'Trading - ' . $symbol)

@section('content')
<div class="space-y-6">
    <!-- Symbol Selector -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $symbol }}</h2>
            <div class="flex space-x-2">
                <a href="{{ route('trading', ['symbol' => 'BTC-USD']) }}" class="px-4 py-2 rounded-lg font-medium {{ $symbol === 'BTC-USD' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    BTC/USD
                </a>
                <a href="{{ route('trading', ['symbol' => 'ETH-USD']) }}" class="px-4 py-2 rounded-lg font-medium {{ $symbol === 'ETH-USD' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    ETH/USD
                </a>
                <a href="{{ route('trading', ['symbol' => 'USDT-USD']) }}" class="px-4 py-2 rounded-lg font-medium {{ $symbol === 'USDT-USD' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    USDT/USD
                </a>
            </div>
        </div>
    </div>

    <!-- Trading Interface -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Form -->
        <div class="lg:col-span-1">
            <order-form
                symbol="{{ $symbol }}"
                :user-assets="{{ json_encode($userAssets) }}"
                initial-side="buy"
            ></order-form>
        </div>

        <!-- Order Book -->
        <div class="lg:col-span-1">
            <order-book
                :orders="{{ json_encode($openOrders) }}"
                symbol="{{ $symbol }}"
                :auto-refresh="true"
                :refresh-interval="5000"
            ></order-book>
        </div>

        <!-- Trade History -->
        <div class="lg:col-span-1">
            <trade-history
                :trades="{{ json_encode($recentTrades) }}"
                symbol="{{ $symbol }}"
                :auto-refresh="true"
                :refresh-interval="5000"
                :visible-count="20"
            ></trade-history>
        </div>
    </div>

    <!-- Additional Info -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Market Information</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <div class="text-gray-600 dark:text-gray-400 mb-1">24h High</div>
                <div class="font-semibold text-gray-900 dark:text-white">-</div>
            </div>
            <div>
                <div class="text-gray-600 dark:text-gray-400 mb-1">24h Low</div>
                <div class="font-semibold text-gray-900 dark:text-white">-</div>
            </div>
            <div>
                <div class="text-gray-600 dark:text-gray-400 mb-1">24h Volume</div>
                <div class="font-semibold text-gray-900 dark:text-white">-</div>
            </div>
            <div>
                <div class="text-gray-600 dark:text-gray-400 mb-1">24h Change</div>
                <div class="font-semibold text-gray-900 dark:text-white">-</div>
            </div>
        </div>
    </div>
</div>
@endsection
