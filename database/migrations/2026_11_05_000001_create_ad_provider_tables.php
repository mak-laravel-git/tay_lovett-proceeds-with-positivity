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
        Schema::create('ad_providers', function (Blueprint $table) {
            $table->id();
            // Matches the key of an adapter registered in code (App\Services\Ads\AdProviderRegistry).
            $table->string('key')->unique();
            $table->string('label');
            $table->boolean('is_enabled')->default(false);
            // Lower number is called first in the fallback chain.
            $table->unsignedInteger('priority')->default(100);
            $table->string('health')->default('unknown');
            // Encrypted credentials (api token, signing secret, reporting token). Never sent to the frontend.
            $table->text('settings')->nullable();
            // Non-secret tuning (timeouts, cache seconds ...).
            $table->json('config')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->string('last_error_category')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['is_enabled', 'priority']);
        });

        Schema::create('ad_provider_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ad_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_settings', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_timezone')->default('America/New_York');
            // Informational USD value of one point, used only for the "estimated remaining revenue" figure.
            $table->unsignedBigInteger('point_usd_value_micros')->nullable();
            // Per-row tolerance when comparing provider-reported and locally recorded revenue ($0.01).
            $table->unsignedInteger('reconciliation_tolerance_micros')->default(10000);
            $table->unsignedSmallInteger('reconciliation_recheck_days')->default(3);
            $table->unsignedInteger('offers_cache_seconds')->default(120);
            $table->timestamps();
        });

        Schema::create('ad_reward_rules', function (Blueprint $table) {
            $table->id();
            // null = global rule for every provider; otherwise overrides the global rule for that provider only.
            $table->string('provider_key')->nullable();
            $table->string('activity_type')->default('survey');
            $table->unsignedInteger('points');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['provider_key', 'activity_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_reward_rules');
        Schema::dropIfExists('ad_settings');
        Schema::dropIfExists('ad_provider_audits');
        Schema::dropIfExists('ad_providers');
    }
};
