<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive only: new tables and two new nullable columns. No existing column or row is removed.
     */
    public function up(): void
    {
        // Single-row settings for the quote workflow: which steps run and what happens when one cannot.
        Schema::create('quote_workflow_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);

            $table->boolean('ad1_enabled')->default(true);
            $table->string('ad1_on_unavailable')->default('continue');
            $table->string('ad1_on_error')->default('block');
            $table->string('ad1_on_dismissed')->default('block');
            $table->foreignId('ad1_placement_id')->nullable()->constrained('ad_placements')->nullOnDelete();

            $table->boolean('survey_enabled')->default(true);
            $table->string('survey_on_no_surveys')->default('block');
            $table->string('survey_on_api_error')->default('block');
            $table->string('survey_on_disqualified')->default('block');
            $table->json('survey_provider_keys')->nullable();
            $table->boolean('award_survey_points')->default(false);

            $table->boolean('ad2_enabled')->default(true);
            $table->string('ad2_on_unavailable')->default('block');
            $table->string('ad2_on_error')->default('block');
            $table->string('ad2_on_dismissed')->default('block');
            $table->foreignId('ad2_placement_id')->nullable()->constrained('ad_placements')->nullOnDelete();

            $table->string('publish_mode')->default('auto');
            // How many "continue anyway" fallbacks one user may use per day. 0 means no limit.
            $table->unsignedSmallInteger('max_fallback_skips_per_day')->default(3);
            $table->text('disclosure_text')->nullable();
            $table->unsignedSmallInteger('expires_after_hours')->default(24);
            $table->unsignedSmallInteger('log_retention_days')->default(90);
            $table->timestamps();
        });

        // One run of the workflow for one user.
        Schema::create('quote_workflows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->string('state');
            // The rules in force when the run started, so a settings change never breaks a run midway.
            $table->json('settings_snapshot');
            $table->string('ad1_result')->nullable();
            $table->string('survey_result')->nullable();
            $table->string('ad2_result')->nullable();
            $table->foreignUlid('survey_activity_id')->nullable()->constrained('ad_activities')->nullOnDelete();
            // Equals user_id while the run is active and null afterwards: one active run per user.
            $table->unsignedBigInteger('active_user_id')->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['state', 'created_at']);
        });

        // One attempt to show one rewarded ad. The browser reports events; the server enforces their order.
        Schema::create('rewarded_ad_attempts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('quote_workflow_id')->constrained('quote_workflows')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('step');
            $table->foreignId('ad_placement_id')->nullable()->constrained('ad_placements')->nullOnDelete();
            $table->string('ad_unit_path')->nullable();
            $table->string('status');
            $table->unsignedSmallInteger('min_watch_seconds')->default(0);
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('shown_at')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('error_detail')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        // Append-only activity log: workflow, ad, survey, generation, publishing and admin changes.
        Schema::create('workflow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('quote_workflow_id')->nullable()->constrained('quote_workflows')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category');
            $table->string('event');
            $table->string('level')->default('info');
            $table->json('context')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['category', 'created_at']);
            $table->index(['level', 'created_at']);
            $table->index(['user_id', 'event', 'created_at']);
        });

        Schema::table('ad_activities', function (Blueprint $table) {
            // Set when the survey was started from a workflow run; it decides whether points are awarded.
            $table->ulid('quote_workflow_id')->nullable()->after('purpose')->index();
        });

        Schema::table('ad_provider_calls', function (Blueprint $table) {
            // A shortened copy of the provider's answer with secrets removed, for troubleshooting.
            $table->text('response_excerpt')->nullable()->after('error_category');
        });

        // "A quote needs a survey" now lives with the other workflow rules. Carry the current choice over. The
        // old ad_settings.gate_quote_generation column is left in place (unused) so nothing is lost.
        $gate = DB::table('ad_settings')->value('gate_quote_generation');

        if ($gate !== null) {
            DB::table('quote_workflow_settings')->insert([
                'survey_enabled' => (bool) $gate,
                'survey_provider_keys' => json_encode(['tapresearch', 'theoremreach']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migration: only what was added above.
     */
    public function down(): void
    {
        Schema::table('ad_provider_calls', function (Blueprint $table) {
            $table->dropColumn('response_excerpt');
        });

        Schema::table('ad_activities', function (Blueprint $table) {
            $table->dropIndex(['quote_workflow_id']);
            $table->dropColumn('quote_workflow_id');
        });

        Schema::dropIfExists('workflow_events');
        Schema::dropIfExists('rewarded_ad_attempts');
        Schema::dropIfExists('quote_workflows');
        Schema::dropIfExists('quote_workflow_settings');
    }
};
