<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('replay_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol');
            $table->string('source');
            $table->date('market_date')->nullable();
            $table->json('candles');
            $table->unsignedInteger('cursor')->default(12);
            $table->decimal('cash', 15, 2)->default(10000);
            $table->decimal('quantity', 15, 6)->default(0);
            $table->decimal('stop_loss', 15, 4)->nullable();
            $table->decimal('take_profit', 15, 4)->nullable();
            $table->decimal('peak_equity', 15, 2)->default(10000);
            $table->decimal('max_drawdown', 8, 4)->default(0);
            $table->json('orders');
            $table->json('curve');
            $table->boolean('completed')->default(false);
            $table->text('evaluation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replay_sessions');
    }
};
