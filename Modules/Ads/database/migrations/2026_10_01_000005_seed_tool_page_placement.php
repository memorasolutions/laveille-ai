<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * Emplacement AdSense « tool-page » : pages d'outils interactifs (/outils/...), rendu APRÈS la
 * sortie de l'outil, jamais dans les contrôles. Réutilise l'unité display responsive existante
 * (slot 4523648998, aussi utilisée par article-bottom) : aucune dépendance au tableau de bord Google.
 *
 * Idempotente : firstOrNew par clé, une ligne existante conserve son nom et son ad_code.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Ads\Models\AdPlacement;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ads_placements')) {
            return;
        }

        $ad = AdPlacement::firstOrNew(['key' => 'tool-page']);

        if (! $ad->exists) {
            $ad->name = 'Page d\'outil';
            $ad->description = 'AdSense display responsive, après la sortie de l\'outil (jamais dans les contrôles).';
            $ad->ad_code = null; // AdSense pur.
        }

        $ad->ad_slot = '4523648998';
        $ad->ad_format = 'auto';
        $ad->min_height = 280;
        $ad->lazy = true;
        $ad->is_external = false;
        $ad->is_active = true;
        $ad->save();
    }

    public function down(): void
    {
        // Aucune suppression de ligne : l'emplacement est simplement désactivé.
        $ad = AdPlacement::where('key', 'tool-page')->first();
        if ($ad) {
            $ad->is_active = false;
            $ad->save();
        }
    }
};
