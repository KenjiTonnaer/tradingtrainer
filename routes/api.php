<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MarketDataController;
use App\Http\Controllers\PiecesController;

Route::get('markets/{symbol}/prices', [MarketController::class, 'prices']);

// Secure market data proxy (no client secrets)
Route::middleware(['auth'])->group(function () {
	Route::get('/markets/{symbol}/bars/latest', [MarketDataController::class, 'latestBar']);
});

// Pieces OS proxy — authenticated so only logged-in users can reach local Pieces
Route::middleware(['auth'])->prefix('pieces')->group(function () {
    Route::get('/ping', [PiecesController::class, 'ping']);
    Route::post('/chat', [PiecesController::class, 'chat']);
});
