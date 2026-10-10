<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * Emplacement AdSense « news-infeed » : unité manuelle dans la grille de /actualites, après la 3e carte.
 * Réutilise l'unité display responsive existante (slot 4523648998, comme tool-page) : aucune dépendance
 * au tableau de bord Google. Désactivable : is_active = 0.
 *
 * Idempotente (firstOrNew). Réversible : down() supprime uniquement la ligne créée ici.
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

        $ad = AdPlacement::firstOrNew(['key' => 'news-infeed']);

        if (! $ad->exists) {
            $ad->name = 'Actualités - dans la grille';
            $ad->description = 'AdSense display responsive, après la 3e carte de /actualites.';
            $ad->ad_code = null;
        }

        $ad->ad_slot = '4523648998';
        $ad->ad_format = 'auto';
        $ad->min_height = 280;
        $ad->lazy = true;
        $ad->is_external = true;
        $ad->is_active = true;
        $ad->save();
    }

    public function down(): void
    {
        if (Schema::hasTable('ads_placements')) {
            AdPlacement::where('key', 'news-infeed')->delete();
        }
    }
};
