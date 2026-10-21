<?php

use App\Http\Controllers\MarketController;
use App\Http\Controllers\MarketDataController;
use App\Http\Controllers\TradeController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('dashboard', \App\Http\Controllers\DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/account/state', function (\Illuminate\Http\Request $request) {
        return response()->json(['cash' => (float) ($request->user()->wallet()->value('balance') ?? 0), 'positions' => $request->user()->paperPositions()->get(),
            'protection_active' => (int) \Illuminate\Support\Facades\Cache::get('trading:protection:last_run', 0) >= now()->subMinutes(2)->timestamp]);
    });
    Route::get('/journal', [\App\Http\Controllers\JournalController::class, 'index'])->name('journal.index');
    Route::patch('/journal/{trade}', [\App\Http\Controllers\JournalController::class, 'update'])->name('journal.update');
    Route::get('/replay', [\App\Http\Controllers\ReplayController::class, 'index'])->name('replay.index');
    Route::post('/replay', [\App\Http\Controllers\ReplayController::class, 'store'])->middleware('throttle:20,1')->name('replay.store');
    Route::get('/replay/{replay}', [\App\Http\Controllers\ReplayController::class, 'show']);
    Route::patch('/replay/{replay}', [\App\Http\Controllers\ReplayController::class, 'update']);
    Route::post('/account/snapshot', function (\Illuminate\Http\Request $request, \App\Services\PortfolioValue $value) {
        return response()->json($value->snapshot($request->user()));
    })->middleware('throttle:10,1');
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

    Route::get('/trades', [TradeController::class, 'index'])->name('trades.index');
    Route::get('/portfolio', [TradeController::class, 'portfolio'])->name('portfolio.index');
    Route::patch('/portfolio/{position}/protection', [TradeController::class, 'protection'])->name('positions.protection');
    Route::post('/trades/buy', [TradeController::class, 'buy'])->name('trades.buy');
    Route::post('/trades/sell', [TradeController::class, 'sell'])->name('trades.sell');
});

// Authenticated, server-side market data. Provider credentials never reach the client.
Route::middleware(['auth'])->group(function () {
    Route::get('/markets/{symbol}', [MarketController::class, 'show'])->name('markets.show');
    // Secure latest bar proxy (uses server-side Alpaca keys)
    Route::get('/markets/{symbol}/bars/latest', [MarketDataController::class, 'latestBar'])->name('markets.bars.latest');
});
