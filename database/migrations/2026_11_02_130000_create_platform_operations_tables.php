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
        Schema::create('ai_provider_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('driver');
            $table->string('label');
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_enabled')->default(true);
            $table->string('health')->default('unknown');
            $table->text('payload');
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_error_category')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['driver', 'is_enabled', 'priority']);
        });

        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('driver');
            $table->string('name');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('structured_output_tested')->default(false);
            $table->unsignedInteger('max_request_cents')->nullable();
            $table->timestamps();
            $table->unique(['driver', 'name']);
        });

        Schema::create('ai_route_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provider_credential_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_model_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->timestamps();
        });

        Schema::create('ai_credential_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_provider_credential_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('error_category')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('routing_mode')->default('priority');
            $table->unsignedInteger('timeout_seconds')->default(20);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->unsignedInteger('daily_budget_cents')->default(0);
            $table->boolean('generation_enabled')->default(false);
            $table->unsignedInteger('default_cooldown_seconds')->default(60);
            $table->timestamps();
        });

        Schema::create('ai_budget_counters', function (Blueprint $table) {
            $table->id();
            $table->date('usage_date')->unique();
            $table->unsignedInteger('reserved_cents')->default(0);
            $table->unsignedInteger('spent_cents')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_budget_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_budget_counter_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key')->unique();
            $table->unsignedInteger('amount_cents');
            $table->string('state')->default('reserved');
            $table->timestamps();
        });

        Schema::create('ai_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->string('driver');
            $table->string('model');
            $table->string('credential_label');
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('reserved_cents')->default(0);
            $table->unsignedInteger('spent_cents')->default(0);
            $table->string('error_category')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('state');
            $table->string('provider')->default('demo');
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('pending_cents')->default(0);
            $table->integer('available_cents')->default(0);
            $table->integer('reserved_cents')->default(0);
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entry_type');
            $table->integer('amount_cents');
            $table->string('idempotency_key')->unique();
            $table->string('description');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('status')->default('pending')->index();
            $table->string('destination_label');
            $table->string('external_reference')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('ad_sessions');
        Schema::dropIfExists('ai_generation_logs');
        Schema::dropIfExists('ai_budget_reservations');
        Schema::dropIfExists('ai_budget_counters');
        Schema::dropIfExists('ai_settings');
        Schema::dropIfExists('ai_credential_audits');
        Schema::dropIfExists('ai_route_steps');
        Schema::dropIfExists('ai_models');
        Schema::dropIfExists('ai_provider_credentials');
    }
};
