@extends('layouts.app')

@section('title', 'Profile')
@section('page-title', 'My Profile')

@section('content')
<div class="space-y-6">
    <!-- Profile Information -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Profile Information</h3>
        
        <div class="space-y-4">
            <div class="flex items-center space-x-4">
                <div class="w-20 h-20 rounded-full bg-blue-500 flex items-center justify-center text-white text-3xl font-bold">
                    {{ strtoupper(substr($user->name ?? $user->email, 0, 1)) }}
                </div>
                <div>
                    <h4 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $user->name ?? 'No name set' }}</h4>
                    <p class="text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                    <div class="text-gray-900 dark:text-white">{{ $user->name ?? 'Not set' }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                    <div class="text-gray-900 dark:text-white">{{ $user->email }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Balance</label>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($user->balance, 2) }} USD</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Member Since</label>
                    <div class="text-gray-900 dark:text-white">{{ $user->created_at->format('F j, Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Stats -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Account Statistics</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Orders</div>
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $user->orders()->count() }}</div>
            </div>

            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Completed Trades</div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $user->trades()->count() }}</div>
            </div>

            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Active Assets</div>
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $user->assets()->where('amount', '>', 0)->count() }}</div>
            </div>
        </div>
    </div>

    <!-- Assets -->
    <asset-list
        :assets="{{ json_encode($user->assets) }}"
        title="My Assets"
        :loading="false"
        :show-view-all="false"
    ></asset-list>
</div>
@endsection
