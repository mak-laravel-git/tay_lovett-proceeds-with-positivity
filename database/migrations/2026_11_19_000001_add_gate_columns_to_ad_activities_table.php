<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A completed survey can unlock one action (today: one generated quote). `purpose` says what the survey
     * was started for, `consumed_at` marks the unlock as used and `consumed_quote_id` records what it paid for.
     */
    public function up(): void
    {
        Schema::table('ad_activities', function (Blueprint $table) {
            $table->string('purpose')->nullable()->after('activity_type');
            $table->timestamp('consumed_at')->nullable()->after('reward_credited_at');
            $table->foreignId('consumed_quote_id')->nullable()->after('consumed_at')->constrained('quotes')->nullOnDelete();
            $table->index(['user_id', 'purpose', 'status', 'consumed_at'], 'ad_activities_gate_lookup');
        });

        Schema::table('ad_settings', function (Blueprint $table) {
            // Kill switch: when off, quote generation no longer needs a survey.
            $table->boolean('gate_quote_generation')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('ad_settings', function (Blueprint $table) {
            $table->dropColumn('gate_quote_generation');
        });

        Schema::table('ad_activities', function (Blueprint $table) {
            $table->dropIndex('ad_activities_gate_lookup');
            $table->dropConstrainedForeignId('consumed_quote_id');
            $table->dropColumn(['purpose', 'consumed_at']);
        });
    }
};
