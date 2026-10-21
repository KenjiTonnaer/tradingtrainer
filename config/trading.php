<?php

return [
    'starting_balance' => 10000,
    'mode' => env('TRADING_DATA_MODE', 'auto'),
    'stocks' => ['AAPL', 'MSFT', 'NVDA', 'TSLA', 'GOOGL', 'AMZN', 'META', 'AMD', 'NFLX', 'DIS'],
    'crypto' => ['BTC', 'ETH', 'SOL', 'DOGE', 'XRP', 'ADA', 'LINK', 'DOT', 'LTC', 'AVAX'],
];
