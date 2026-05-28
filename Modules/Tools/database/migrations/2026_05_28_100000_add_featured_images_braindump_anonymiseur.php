<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * #313 — Ajoute les hero images (og:image + card index) aux outils
 * brain-dump et anonymiseur qui n'en avaient pas (générées via Gemini).
 * Idempotent : UPDATE WHERE slug + hasColumn check.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tools', 'featured_image')) {
            return;
        }
        DB::table('tools')->where('slug', 'brain-dump')->update(['featured_image' => 'images/tools/brain-dump.jpg']);
        DB::table('tools')->where('slug', 'anonymiseur')->update(['featured_image' => 'images/tools/anonymiseur.jpg']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tools', 'featured_image')) {
            return;
        }
        DB::table('tools')->whereIn('slug', ['brain-dump', 'anonymiseur'])->update(['featured_image' => null]);
    }
};
