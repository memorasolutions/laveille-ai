<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // PUBLICATION de Décido (sortie de construction) — go explicite de l'utilisateur le
    // 2026-07-18, après 27 rounds de revue adversariale (skill /100) + simulation E2E complète
    // (#1134-1139). Retire le badge "Bientôt" sur /outils. Le gate DecidoUnderConstruction
    // (config('decido.under_construction'), variable DECIDO_UNDER_CONSTRUCTION) est levé
    // séparément côté .env — cette migration ne couvre que l'entrée annuaire.
    public function up(): void
    {
        if (Schema::hasTable('tools') && Schema::hasColumn('tools', 'is_under_construction')) {
            DB::table('tools')->where('slug', 'decido')->update([
                'is_under_construction' => false,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tools') && Schema::hasColumn('tools', 'is_under_construction')) {
            DB::table('tools')->where('slug', 'decido')->update([
                'is_under_construction' => true,
                'updated_at' => now(),
            ]);
        }
    }
};
