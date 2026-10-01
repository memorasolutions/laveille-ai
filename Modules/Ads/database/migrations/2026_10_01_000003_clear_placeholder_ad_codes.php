<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * Retire un placeholder de développement laissé comme ad_code DIRECT sur un ou plusieurs
 * emplacements AdSense (typiquement « Emplacement publicitaire - haut d'article »).
 *
 * Défaut mesuré le 2026-10-01 (capture du fondateur, connecté en admin) : l'emplacement
 * article-top affichait ce placeholder au lieu d'une vraie annonce. Cause confirmée par la
 * logique de AdsRenderer::render() :
 *   - un emplacement qui porte À LA FOIS un ad_code direct ET un identifiant AdSense alterne ;
 *   - un MEMBRE (donc un admin) reçoit toujours la pub DIRECTE -> le placeholder, en permanence ;
 *   - un VISITEUR anonyme reçoit l'alternance -> le placeholder un jour sur deux, au lieu d'AdSense.
 *
 * En retirant ce placeholder (ad_code = null), ces emplacements redeviennent AdSense pur :
 * l'admin ne voit plus rien (member -> null), le visiteur voit TOUJOURS AdSense. On ne touche
 * JAMAIS un ad_code direct légitime (ex. encart livre <x-fronttheme::book-promo />), filtré par
 * la signature texte « Emplacement publicitaire », propre au placeholder.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ads_placements')) {
            return;
        }

        DB::table('ads_placements')
            ->where('ad_code', 'like', '%Emplacement publicitaire%')
            ->update(['ad_code' => null]);
    }

    public function down(): void
    {
        // Irréversible par conception : le placeholder retiré était un résidu de développement,
        // jamais une donnée à conserver. Aucune restauration.
    }
};
