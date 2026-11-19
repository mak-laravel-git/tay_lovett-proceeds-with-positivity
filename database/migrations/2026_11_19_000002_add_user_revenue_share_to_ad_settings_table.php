<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Option B sandbox toggle: when enabled, verified provider publisher revenue can credit a user's
 * pending wallet cents at floor(1/3). Default off — never invent live cash payouts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_settings', function (Blueprint $table) {
            $table->boolean('user_revenue_share_enabled')->default(false)->after('gate_quote_generation');
        });
    }

    public function down(): void
    {
        Schema::table('ad_settings', function (Blueprint $table) {
            $table->dropColumn('user_revenue_share_enabled');
        });
    }
};
