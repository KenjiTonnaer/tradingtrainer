<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paper_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 20);           // 'AAPL', 'BTC', etc.
            $table->string('type', 4);              // 'buy' of 'sell'
            $table->string('asset_type', 10)->default('stock'); // 'stock' of 'crypto'
            $table->decimal('quantity', 15, 6);     // aantal aandelen/coins (crypto heeft decimalen)
            $table->decimal('price_per_unit', 15, 4); // prijs op moment van trade
            $table->decimal('total_value', 15, 2);  // quantity * price_per_unit
            $table->decimal('wallet_balance_after', 15, 2); // wallet saldo na deze trade
            $table->string('status', 10)->default('filled'); // 'filled', 'cancelled'
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'symbol']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_trades');
    }
};