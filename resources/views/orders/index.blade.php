@extends('layouts.app')

@section('title', 'My Orders')
@section('page-title', 'My Orders')

@section('content')
<div class="space-y-6">
    <!-- Orders List -->
    <order-list
        :orders="{{ json_encode($orders) }}"
        :loading="false"
    ></order-list>

    @if($orders->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-12 text-center">
            <svg class="w-20 h-20 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No orders yet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Start trading to see your orders here</p>
            <a href="{{ route('trading', ['symbol' => 'BTC-USD']) }}" class="inline-block px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors">
                Start Trading
            </a>
        </div>
    @endif
</div>
@endsection
