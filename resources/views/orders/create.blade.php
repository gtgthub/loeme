@extends('layouts.app')

@section('title', 'Place Limit Order')

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
            <li class="text-gray-900 dark:text-white font-medium">Place Order</li>
        </ol>
    </nav>

    <div class="max-w-2xl mx-auto">
        <!-- Page Title -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Place Limit Order</h1>
            <p class="text-gray-600 dark:text-gray-400">Create a new buy or sell order for BTC or ETH</p>
        </div>

        <!-- Loading State -->
        <div id="loading-placeholder" class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
                <div class="h-10 bg-gray-200 rounded mb-4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
                <div class="h-10 bg-gray-200 rounded mb-4"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
            </div>
        </div>

        <!-- Limit Order Form Component -->
        <div id="order-form-container" style="display: none;">
            <limit-order-form 
                :balances='@json($balances ?? [])'
                @order-placed="handleOrderPlaced"
            ></limit-order-form>
        </div>

        <!-- Quick Links -->
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-300">Quick Links</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <a href="{{ route('orders.overview') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            View All Orders
                        </a>
                        <span class="text-gray-400">•</span>
                        <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            Dashboard
                        </a>
                        <span class="text-gray-400">•</span>
                        <a href="{{ route('profile') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            View Wallet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Debug: Log when script runs
console.log('✓ Page script loaded');

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
                status.textContent = '✓ Vue mounted! Loading form...';
                
                // Show form, hide placeholder
                const placeholder = document.getElementById('loading-placeholder');
                const container = document.getElementById('order-form-container');
                if (placeholder) placeholder.style.display = 'none';
                if (container) container.style.display = 'block';
                
                console.log('✓ Form displayed');
            } else {
                status.className = 'mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-800';
                status.innerHTML = '❌ Vue failed to mount. Check console (F12) for errors. <a href="/test-vue" class="underline">Run diagnostics</a>';
                console.error('❌ Vue not mounted');
            }
        }
    }, 1500);
    
    function handleOrderPlaced(order) {
        console.log('Order placed:', order);
        // Optionally redirect to orders page after a delay
        setTimeout(() => {
            window.location.href = '{{ route("orders.overview") }}';
        }, 2000);
    }
});
</script>
@endsection
