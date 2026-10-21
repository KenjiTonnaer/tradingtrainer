<?php

// Independent PHP worker used only by TradeConcurrencyTest's disposable SQLite DB.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (! $app->environment('testing') || ! str_contains(basename($argv[1]), 'tradingtrainer-concurrency-')) {
    throw new RuntimeException('A disposable test database is required.');
}
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $argv[1], 'cache.default' => 'array', 'trading.mode' => 'simulated']);
Illuminate\Support\Facades\DB::purge();
try {
    app(App\Services\ExecuteTrade::class)->execute(App\Models\User::findOrFail($argv[2]), 'buy', ['symbol' => 'AAPL', 'quantity' => 1, 'order_key' => $argv[3]]);
    echo 'filled';
} catch (Illuminate\Validation\ValidationException $e) {
    echo 'rejected';
}
