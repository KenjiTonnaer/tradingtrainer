<?php

namespace App\Http\Controllers;

use App\Services\MarketData;

class MarketController extends Controller
{
    public function show(string $symbol, MarketData $market)
    {
        return view('markets.show', ['symbol' => $market->symbol($symbol)]);
    }
}
