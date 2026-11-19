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
        // One row per "show me an ad/survey" request, with the chain of providers that were tried.
        Schema::create('ad_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type')->default('survey');
            $table->string('resolved_provider_key')->nullable();
            $table->string('outcome')->default('failed');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->unsignedSmallInteger('offer_count')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['outcome', 'created_at']);
        });

        // One row per call made to a provider inside a request (fetch offers, reporting, connection test ...).
        Schema::create('ad_provider_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('provider_key');
            $table->string('operation');
            $table->string('outcome');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error_category')->nullable();
            // Position in the fallback chain, starting at 1.
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();
            $table->index(['provider_key', 'created_at']);
            $table->index(['operation', 'outcome']);
        });

        // The opaque identifier a provider knows a user by. Never an email address or a name.
        Schema::create('provider_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider_key');
            $table->string('external_user_id');
            $table->timestamps();
            $table->unique(['provider_key', 'user_id']);
            $table->unique(['provider_key', 'external_user_id']);
        });

        // One independent transaction record per survey attempt (or per unmatched provider payout).
        Schema::create('ad_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // Nullable so a signed postback for an unknown user can still be stored as its own transaction.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ad_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->default('app');
            $table->string('provider_key');
            $table->string('provider_user_id')->nullable();
            $table->string('activity_type')->default('survey');
            // The survey / campaign identifier chosen by the user.
            $table->string('offer_identifier')->nullable();
            // Advertised survey details captured at start, for display only.
            $table->unsignedSmallInteger('offer_length_minutes')->nullable();
            $table->string('offer_category')->nullable();
            // The provider's unique reward id (cpid). Unique per provider: the duplicate-callback guard.
            $table->string('provider_transaction_id')->nullable();
            $table->string('status')->default('started');
            $table->string('reward_status')->default('pending');
            $table->string('reward_note')->nullable();
            $table->timestamp('survey_loaded_at')->nullable();
            $table->timestamp('survey_started_at')->nullable();
            $table->timestamp('survey_returned_at')->nullable();
            // The moment our server accepted a verified postback (the provider sends no timestamp of its own).
            $table->timestamp('provider_confirmed_at')->nullable();
            $table->timestamp('reward_credited_at')->nullable();
            // Redirect status code reported on return. Informational only; never approves anything.
            $table->string('provider_status')->nullable();
            $table->unsignedTinyInteger('survey_outcome')->nullable();
            $table->unsignedTinyInteger('payout_type')->nullable();
            // What the provider says it pays the user for this event, in its own integer unit.
            $table->bigInteger('provider_payout_amount')->nullable();
            $table->string('payout_currency')->nullable();
            // Publisher revenue in millionths of a dollar. Never a float.
            $table->bigInteger('publisher_revenue_micros')->default(0);
            $table->string('currency', 8)->default('USD');
            $table->unsignedInteger('awarded_points')->default(0);
            $table->string('callback_verification_status')->default('none');
            $table->json('callback_payload')->nullable();
            $table->string('country', 8)->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->unique(['provider_key', 'provider_transaction_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['provider_key', 'status']);
            $table->index('provider_confirmed_at');
            $table->index('offer_identifier');
            $table->index(['status', 'reward_status']);
        });

        // Every inbound callback, including duplicates and invalid ones, for audit.
        Schema::create('provider_callbacks', function (Blueprint $table) {
            $table->id();
            $table->string('provider_key');
            $table->foreignUlid('activity_id')->nullable()->constrained('ad_activities')->nullOnDelete();
            $table->string('provider_transaction_id')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->string('result');
            $table->string('note')->nullable();
            $table->bigInteger('revenue_micros')->default(0);
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('received_at');
            $table->timestamps();
            $table->index(['provider_key', 'received_at']);
            $table->index('result');
            $table->index('provider_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_callbacks');
        Schema::dropIfExists('ad_activities');
        Schema::dropIfExists('provider_identities');
        Schema::dropIfExists('ad_provider_calls');
        Schema::dropIfExists('ad_requests');
    }
};
