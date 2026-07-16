<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed de l'entrée annuaire pour Décido (module dédié /decido, pas une vue
 * générique tools::public.tools.decido) - #1092. Pattern calqué sur
 * 2026_07_04_120000_seed_minuteur_visuel_tool_entry.php (updateOrInsert, down()
 * réversible).
 *
 * is_under_construction=true : le carton apparaît sur /outils (badge « En
 * construction ») mais le clic pointe vers /decido, déjà gaté par
 * Modules\Decido\Http\Middleware\DecidoUnderConstruction (superadmin-only,
 * 503 sinon) - double gate volontaire, cohérent avec le pattern Anonymiseur/
 * Minuteur visuel avant leur mise en ligne publique. Passage à false = décision
 * explicite séparée de l'utilisateur (GO de mise en ligne publique).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        $data = [
            'name' => 'Décido',
            'description' => 'Organisez un sondage collectif gratuit pour choisir une date ou une option en groupe, sans compte requis. Export CSV/ICS, lien court et code QR inclus.',
            'icon' => '🗳️',
            'category' => 'communication',
            'is_active' => true,
            'sort_order' => 15,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('tools', 'is_under_construction')) {
            $data['is_under_construction'] = true;
        }

        DB::table('tools')->updateOrInsert(['slug' => 'decido'], $data);
    }

    public function down(): void
    {
        if (Schema::hasTable('tools')) {
            DB::table('tools')->where('slug', 'decido')->delete();
        }
    }
};
