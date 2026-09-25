<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed de l'entrée annuaire pour le générateur de signature HTML (module dédié
 * /outils/signature-courriel, pas une vue générique tools::public.tools.*) -
 * .devis/outil_signature_html/PLAN-OUTIL-SIGNATURE.md, section 11.1. Pattern calqué sur
 * 2026_07_16_120000_seed_decido_tool_entry.php (updateOrInsert, down() réversible).
 *
 * is_under_construction=true, construction_mode par défaut ('construction', posé par
 * 2026_07_26_160000_add_construction_mode_to_tools.php) : le visiteur anonyme reçoit la page
 * 200 + noindex tools::public.under-construction (JAMAIS indexée), seul le superadmin (le
 * fondateur) voit l'outil réel - Modules\Tools\Models\Tool::isAccessibleTo(), déjà en place,
 * jamais réinventé (brief du 2026-09-25). Passage à false = décision explicite séparée
 * (GO de mise en ligne publique), comme pour Décido.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        $data = [
            'name' => 'Signature de courriel',
            'description' => "Créez une signature de courriel professionnelle gratuite, sans compte obligatoire\u{00A0}: 4 gabarits, liens sociaux, logo et portrait, copie en un clic.",
            'icon' => '✍️',
            'category' => 'communication',
            'is_active' => true,
            'sort_order' => 16,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('tools', 'is_under_construction')) {
            $data['is_under_construction'] = true;
        }

        DB::table('tools')->updateOrInsert(['slug' => 'signature-courriel'], $data);
    }

    public function down(): void
    {
        if (Schema::hasTable('tools')) {
            DB::table('tools')->where('slug', 'signature-courriel')->delete();
        }
    }
};
