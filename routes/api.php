<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MarketDataController;

Route::get('markets/{symbol}/prices', [MarketController::class, 'prices']);

// Secure market data proxy (no client secrets)
Route::middleware(['auth'])->group(function () {
	Route::get('/markets/{symbol}/bars/latest', [MarketDataController::class, 'latestBar']);
});
