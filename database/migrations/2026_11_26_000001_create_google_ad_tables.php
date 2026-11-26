<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive only: two new tables.
     */
    public function up(): void
    {
        // Single-row settings for Google ads. Both switches start off, so nothing loads until an
        // administrator has entered the account details.
        Schema::create('google_ad_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('rewarded_enabled')->default(false);
            $table->boolean('display_enabled')->default(false);
            $table->string('adsense_client_id')->nullable();
            $table->string('gam_network_code')->nullable();
            // Serve Google's sample units instead of the configured ones.
            $table->boolean('test_mode')->default(false);
            $table->text('ads_txt')->nullable();
            $table->timestamps();
        });

        // One row per configured ad unit: rewarded units used by the workflow and display units on pages.
        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('kind');
            $table->string('network');
            // Where a display unit is mounted. Null for rewarded units.
            $table->string('location')->nullable();
            $table->string('ad_unit_path')->nullable();
            $table->string('adsense_slot_id')->nullable();
            $table->json('sizes')->nullable();
            $table->string('devices')->default('all');
            $table->unsignedSmallInteger('min_height')->default(0);
            // For in-list locations: show after every N items.
            $table->unsignedSmallInteger('every_n')->nullable();
            $table->unsignedSmallInteger('load_timeout_seconds')->default(10);
            $table->unsignedSmallInteger('min_watch_seconds')->default(5);
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(10);
            $table->timestamps();
            $table->index(['kind', 'is_enabled', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations: only the tables created above.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_placements');
        Schema::dropIfExists('google_ad_settings');
    }
};
