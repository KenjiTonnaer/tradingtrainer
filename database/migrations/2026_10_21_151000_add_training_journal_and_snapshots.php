<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paper_trades', function (Blueprint $table) {
            $table->string('strategy', 80)->nullable();
            $table->text('entry_reason')->nullable();
            $table->text('evaluation')->nullable();
            $table->string('emotion', 30)->nullable();
            $table->unsignedTinyInteger('discipline')->nullable();
            $table->decimal('stop_loss', 15, 4)->nullable();
            $table->decimal('take_profit', 15, 4)->nullable();
            $table->decimal('planned_risk', 15, 2)->nullable();
        });
        Schema::table('paper_positions', function (Blueprint $table) {
            $table->decimal('stop_loss', 15, 4)->nullable();
            $table->decimal('take_profit', 15, 4)->nullable();
            $table->string('strategy', 80)->nullable();
        });
        Schema::create('portfolio_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('equity', 15, 2);
            $table->decimal('cash', 15, 2);
            $table->string('data_status');
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_snapshots');
        Schema::table('paper_positions', fn (Blueprint $t) => $t->dropColumn(['stop_loss', 'take_profit', 'strategy']));
        Schema::table('paper_trades', fn (Blueprint $t) => $t->dropColumn(['strategy', 'entry_reason', 'evaluation', 'emotion', 'discipline', 'stop_loss', 'take_profit', 'planned_risk']));
    }
};
