<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paper_trades', function (Blueprint $table) {
            $table->uuid('order_key')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->json('quote')->nullable();
            $table->decimal('realized_pnl', 15, 2)->nullable();
            $table->unique(['user_id', 'order_key']);
        });
    }

    public function down(): void
    {
        Schema::table('paper_trades', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'order_key']);
            $table->dropColumn(['order_key', 'currency', 'quote', 'realized_pnl']);
        });
    }
};
