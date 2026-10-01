<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * Articles de blogue ET fiches d'actualité = AdSense PUR.
 *
 * Décision du fondateur (2026-10-01) : plus d'alternance avec une pub directe maison (encart
 * livre) dans la zone article. Les trois emplacements article-top, article-inline et
 * article-bottom passent à ad_code = NULL : un visiteur voit toujours AdSense, un membre ne voit
 * plus rien (AdsRenderer::render() renvoie null pour un membre sur un emplacement AdSense seul).
 *
 * Trace de rollback : les anciennes valeurs sont journalisées (canal « composition ») AVANT d'être
 * retirées. NE TOUCHE QUE ces trois clés.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const KEYS = ['article-top', 'article-inline', 'article-bottom'];

    // Seule pub directe documentée sur cette zone (migration 2026_05_18_120000) : encart livre.
    private const BOOK_PROMO = '<x-fronttheme::book-promo variant="inline" />';

    public function up(): void
    {
        if (! Schema::hasTable('ads_placements')) {
            return;
        }

        $rows = DB::table('ads_placements')->whereIn('key', self::KEYS)->get(['key', 'ad_code']);

        foreach ($rows as $row) {
            // Trace de rollback : valeur exacte avant retrait.
            try {
                Log::channel('composition')->info('ads: articles_adsense_pur - ancien ad_code', [
                    'key' => $row->key,
                    'ad_code' => $row->ad_code,
                ]);
            } catch (\Throwable) {
                // Un journal indisponible ne doit jamais bloquer le déploiement.
            }
        }

        DB::table('ads_placements')->whereIn('key', self::KEYS)->update(['ad_code' => null]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('ads_placements')) {
            return;
        }

        // Restaure l'encart livre UNIQUEMENT sur article-inline (seul emplacement documenté comme
        // alternant) et seulement s'il est vide : jamais d'écrasement d'une valeur posée depuis.
        DB::table('ads_placements')
            ->where('key', 'article-inline')
            ->where(function ($q) {
                $q->whereNull('ad_code')->orWhere('ad_code', '');
            })
            ->update(['ad_code' => self::BOOK_PROMO]);
    }
};
