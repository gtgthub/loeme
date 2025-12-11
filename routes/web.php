<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TradingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController;

// Welcome page
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Trading
    Route::get('/trading/{symbol?}', [TradingController::class, 'index'])->name('trading');
    Route::post('/trading/orders', [TradingController::class, 'store'])->name('trading.orders.store');
    
    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::get('/orders/overview', [OrderController::class, 'overview'])->name('orders.overview');
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/match', [OrderController::class, 'match'])->name('orders.match');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
});

// API Routes (for AJAX calls from Vue components)
Route::prefix('api')->middleware('auth')->group(function () {
    // Profile & Balance
    Route::get('/profile', [ProfileController::class, 'apiProfile']);
    
    // Orders
    Route::get('/orders', [OrderController::class, 'apiIndex']);
    Route::post('/order', [OrderController::class, 'store']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    
    // Orderbook
    Route::get('/orderbook', [OrderController::class, 'orderbook']);
    
    // Trades
    Route::get('/trades/{symbol}', function($symbol) {
        $trades = \App\Models\Trade::where('symbol', $symbol)
            ->latest('created_at')
            ->take(50)
            ->get();
        return response()->json(['trades' => $trades]);
    });
    
    // Assets (legacy)
    Route::get('/assets', function() {
        return response()->json(['assets' => auth()->user()->assets]);
    });
});