@extends('layouts.app')

@section('title', 'Orders & Wallet Overview')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Debug Info -->
    <div id="vue-status" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
        ⏳ Initializing page...
    </div>
    
    <!-- Breadcrumb -->
    <nav class="mb-6">
        <ol class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
            <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 dark:text-white font-medium">Orders & Wallet</li>
        </ol>
    </nav>

    <!-- Loading State -->
    <div id="loading-placeholder" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-1/4 mb-4"></div>
            <div class="grid grid-cols-3 gap-4">
                <div class="h-24 bg-gray-200 rounded"></div>
                <div class="h-24 bg-gray-200 rounded"></div>
                <div class="h-24 bg-gray-200 rounded"></div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
            <div class="space-y-3">
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
            </div>
        </div>
    </div>

    <!-- Orders Overview Component -->
    <div id="overview-container" style="display: none;">
        <orders-overview
            pusher-key="{{ config('broadcasting.connections.pusher.key') }}"
            pusher-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}"
            :auto-refresh="true"
            @price-selected="handlePriceSelected"
        ></orders-overview>
    </div>

    <!-- Quick Action Button -->
    <div class="fixed bottom-6 right-6 z-40">
        <a 
            href="{{ route('orders.create') }}"
            class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="font-semibold">Place New Order</span>
        </a>
    </div>
</div>

<script>
// Debug: Log when script runs
console.log('✓ Overview page script loaded');

document.addEventListener('DOMContentLoaded', function() {
    console.log('✓ DOM loaded');
    
    // Update status
    const status = document.getElementById('vue-status');
    if (status) {
        status.textContent = '⏳ Waiting for Vue...';
    }
    
    // Check Vue after a delay
    setTimeout(() => {
        const vueElement = document.querySelector('[data-vue-app]');
        const isVueMounted = vueElement && vueElement.__vue__;
        
        if (status) {
            if (isVueMounted) {
                status.className = 'mb-4 p-3 bg-green-50 border border-green-200 rounded text-sm text-green-800';
                status.textContent = '✓ Vue mounted! Loading overview...';
                
                // Show overview, hide placeholder
                const placeholder = document.getElementById('loading-placeholder');
                const container = document.getElementById('overview-container');
                if (placeholder) placeholder.style.display = 'none';
                if (container) container.style.display = 'block';
                
                console.log('✓ Overview displayed');
            } else {
                status.className = 'mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-800';
                status.innerHTML = '❌ Vue failed to mount. Check console (F12) for errors. <a href="/test-vue" class="underline">Run diagnostics</a>';
                console.error('❌ Vue not mounted');
            }
        }
    }, 1500);
    
    function handlePriceSelected(price) {
        console.log('Price selected from orderbook:', price);
        // Could redirect to order form with pre-filled price
        // window.location.href = '{{ route("orders.create") }}?price=' + price;
    }
});
</script>

<!-- Add user ID meta tag for Pusher private channels -->
<meta name="user-id" content="{{ auth()->id() }}">
@endsection
