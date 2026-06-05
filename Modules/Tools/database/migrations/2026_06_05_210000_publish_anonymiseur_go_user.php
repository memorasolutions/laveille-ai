<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Publication publique de l'anonymiseur de texte — GO explicite user 2026-06-05
 * (« publie l'outil ») après la refonte UX/UI complète v1.65.43→55 :
 * éditeur riche, format préservé, surlignage des 2 colonnes, rapport de
 * restauration structuré, tooltip valeur anonyme, certification E2E PASS.
 * Le flag avait été remis à true par un one-off CI ; on le repasse à false ici.
 * Idempotent (hasColumn check + UPDATE WHERE slug). Réversible via down().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tools', 'is_under_construction')) {
            return;
        }
        DB::table('tools')->where('slug', 'anonymiseur')->update([
            'is_under_construction' => false,
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tools', 'is_under_construction')) {
            return;
        }
        DB::table('tools')->where('slug', 'anonymiseur')->update(['is_under_construction' => true]);
    }
};
