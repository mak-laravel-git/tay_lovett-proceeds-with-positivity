<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('provider_key');
            $table->date('report_date');
            $table->string('timezone');
            // null while the provider has not reported (or reporting is unavailable).
            $table->bigInteger('provider_revenue_micros')->nullable();
            $table->bigInteger('local_revenue_micros')->default(0);
            $table->bigInteger('difference_micros')->nullable();
            $table->unsignedInteger('local_transaction_count')->default(0);
            $table->string('status');
            $table->json('country_breakdown')->nullable();
            $table->json('flag_counts')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->unsignedSmallInteger('rechecked_count')->default(0);
            $table->timestamps();
            $table->unique(['provider_key', 'report_date']);
            $table->index(['status', 'report_date']);
        });

        Schema::create('ad_reconciliation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_reconciliation_id')->constrained()->cascadeOnDelete();
            $table->string('external_user_id');
            $table->bigInteger('provider_revenue_micros')->default(0);
            $table->bigInteger('local_revenue_micros')->default(0);
            $table->bigInteger('difference_micros')->default(0);
            $table->string('flag');
            $table->timestamps();
            $table->unique(['ad_reconciliation_id', 'external_user_id'], 'recon_lines_unique');
        });

        // Settled / paid publisher revenue. No provider API reports this, so an administrator records it by hand.
        Schema::create('ad_provider_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('provider_key');
            $table->date('period_start');
            $table->date('period_end');
            $table->bigInteger('amount_micros');
            $table->string('reference')->nullable();
            $table->date('received_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['provider_key', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_provider_payouts');
        Schema::dropIfExists('ad_reconciliation_lines');
        Schema::dropIfExists('ad_reconciliations');
    }
};
