<?php

use App\Support\QuoteSlug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public URLs address a quote by slug so the database id is never exposed.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->string('slug', 120)->nullable()->after('id');
        });

        // Give every existing quote a slug before the column becomes required.
        $taken = [];

        DB::table('quotes')->orderBy('id')->chunkById(200, function ($quotes) use (&$taken): void {
            foreach ($quotes as $quote) {
                $slug = QuoteSlug::generate(
                    (string) $quote->text,
                    fn (string $candidate): bool => isset($taken[$candidate]),
                );

                $taken[$slug] = true;

                DB::table('quotes')->where('id', $quote->id)->update(['slug' => $slug]);
            }
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->string('slug', 120)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
