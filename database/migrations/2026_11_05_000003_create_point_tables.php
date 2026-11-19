<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Points are deliberately separate from the cents-based wallet: they are a reward unit, not money.
     */
    public function up(): void
    {
        Schema::create('point_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('lifetime_earned')->default(0);
            $table->timestamps();
        });

        // Immutable: rows are only ever inserted. The unique key is what makes a reward impossible to repeat.
        Schema::create('point_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('activity_id')->nullable()->constrained('ad_activities')->nullOnDelete();
            $table->string('entry_type');
            $table->integer('points');
            $table->string('idempotency_key')->unique();
            $table->string('description');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_ledger_entries');
        Schema::dropIfExists('point_balances');
    }
};
