<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * Les emplacements de contenu « article-top » et « article-inline » étaient des unités In-Article
 * (« fluid »). Mesuré au navigateur le 2026-10-01 : Google rend un cadre d'annonce de 280 px pour
 * une unité In-Article, mais y sert parfois une création image plus courte, alignée en haut, ce qui
 * laisse un grand BLANC sous l'annonce. L'unité display responsive « auto » (celle de article-bottom)
 * remplit au contraire tout son cadre (création + bandeau natif), sans blanc - vérifié par capture
 * sur la même page. On bascule donc ces deux emplacements en « auto », hauteur inchangée (280 px).
 *
 * Réversible : down() restaure le format « fluid ». Idempotente : simple mise à jour par clé.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Ads\Models\AdPlacement;

return new class extends Migration
{
    private const KEYS = ['article-top', 'article-inline'];

    public function up(): void
    {
        if (! Schema::hasTable('ads_placements')) {
            return;
        }

        AdPlacement::whereIn('key', self::KEYS)->update([
            'ad_format' => 'auto',
            'min_height' => 280,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('ads_placements')) {
            return;
        }

        AdPlacement::whereIn('key', self::KEYS)->update([
            'ad_format' => 'fluid',
            'min_height' => 280,
        ]);
    }
};
