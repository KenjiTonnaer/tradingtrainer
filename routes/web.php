<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MarketDataController;
use App\Http\Controllers\TradeController;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    Route::get('/trades',          [TradeController::class, 'index'])->name('trades.index');
    Route::get('/portfolio',       [TradeController::class, 'portfolio'])->name('portfolio.index');
    Route::post('/trades/buy',     [TradeController::class, 'buy'])->name('trades.buy');
    Route::post('/trades/sell',    [TradeController::class, 'sell'])->name('trades.sell');
});

// Markets / Trading page with real-time charts (Finnhub API + WebSocket)
// Accessible from sidebar: Dashboard > Trading
// Default symbol: AAPL (can switch between multiple stocks)
Route::middleware(['auth'])->group(function () {
    Route::get('/markets/{symbol}', [MarketController::class, 'show'])->name('markets.show');
    // Secure latest bar proxy (uses server-side Alpaca keys)
    Route::get('/markets/{symbol}/bars/latest', [MarketDataController::class, 'latestBar'])->name('markets.bars.latest');
});
