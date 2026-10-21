<?php

namespace App\Http\Controllers;

use App\Services\MarketData;
use Illuminate\Http\JsonResponse;

class MarketDataController extends Controller
{
    public function latestBar(string $symbol, MarketData $market): JsonResponse
    {
        return response()->json($market->quote($symbol));
    }
}
