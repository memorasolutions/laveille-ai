<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // PUBLICATION du Minuteur visuel (sortie de construction) — go explicite de l'utilisateur
    // le 2026-07-06, après régression complète du module Tools (33 tests verts).
    public function up(): void
    {
        if (Schema::hasTable('tools') && Schema::hasColumn('tools', 'is_under_construction')) {
            DB::table('tools')->where('slug', 'minuteur-visuel')->update([
                'is_under_construction' => false,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tools') && Schema::hasColumn('tools', 'is_under_construction')) {
            DB::table('tools')->where('slug', 'minuteur-visuel')->update([
                'is_under_construction' => true,
                'updated_at' => now(),
            ]);
        }
    }
};
