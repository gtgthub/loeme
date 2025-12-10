<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/profile', [ProfileController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/api/orders/{symbol}', [OrderController::class, 'index'])->middleware(['auth', 'verified'])->name('orders.index');
Route::post('/api/orders', [OrderController::class, 'store'])->middleware(['auth', 'verified'])->name('orders.store');
Route::post('/api/orders/{id}/cancel', [OrderController::class, 'cancel'])->middleware(['auth', 'verified'])->name('orders.cancel');
Route::post('/api/orders/match', [OrderController::class, 'match'])->middleware(['auth', 'verified'])->name('orders.match'); // Internal matching or job-based match trigger	Matches new orders with the first valid counter order

require __DIR__.'/auth.php';