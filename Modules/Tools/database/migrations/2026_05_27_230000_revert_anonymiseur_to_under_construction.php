<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * #313 — Sprint S129 : remettre l'anonymiseur en admin-only le temps que
 * Stéphane valide visuellement le MVP avant la publication grand public.
 * Décision user 2026-05-27 (verbatim) : "Il n'est pas écrit en construction.
 * Et seulement moi le voit tant que je ne donne pas le go public".
 * La migration `2026_05_27_220000_publish_anonymiseur_tool` (P1.7) avait
 * flippé is_under_construction=false trop tôt — on revient à true ici.
 * Idempotent (hasColumn check + simple UPDATE WHERE slug).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tools', 'is_under_construction')) {
            return;
        }
        DB::table('tools')->where('slug', 'anonymiseur')->update(['is_under_construction' => true]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tools', 'is_under_construction')) {
            return;
        }
        DB::table('tools')->where('slug', 'anonymiseur')->update(['is_under_construction' => false]);
    }
};
