<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paper_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 20);
            $table->string('asset_type', 10)->default('stock');
            $table->decimal('quantity', 15, 6);          // totaal aantal dat je bezit
            $table->decimal('avg_buy_price', 15, 4);     // gemiddelde aankoopprijs
            $table->decimal('total_invested', 15, 2);    // totaal bedrag geïnvesteerd
            $table->timestamps();

            $table->unique(['user_id', 'symbol']);        // één positie per symbol per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_positions');
    }
};