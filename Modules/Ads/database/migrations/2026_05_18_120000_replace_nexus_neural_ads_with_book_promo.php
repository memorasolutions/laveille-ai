<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai — #230 remplace pub Nexus Neural par livre auteur
 *
 * Met à jour les 2 placements publicitaires (id=1 nexus-neural + id=2 article-inline)
 * pour utiliser le composant Blade `<x-fronttheme::book-promo variant="inline" />`
 * (livre « L'IA sans se faire poursuivre — Édition 2026 » de Stéphane Lapointe).
 *
 * Compatible avec la nouvelle logique `AdsRenderer::render()` qui détecte les
 * composants Blade dans `ad_code` et les compile au runtime via `Blade::render()`.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $newCode = '<x-fronttheme::book-promo variant="inline" />';

        // id=1 : ex-clé "nexus-neural" -> renommée "book-author"
        $rowsKey1 = DB::table('ads_placements')->where('key', 'nexus-neural')->count();
        if ($rowsKey1 > 0) {
            DB::table('ads_placements')
                ->where('key', 'nexus-neural')
                ->update([
                    'key' => 'book-author',
                    'name' => "Promo livre auteur (L'IA sans se faire poursuivre)",
                    'description' => "Encart livre Stéphane Lapointe — Loi 25 / RGPD / AI Act pour PME francophones",
                    'ad_code' => $newCode,
                    'updated_at' => now(),
                ]);
        }

        // id=2 : clé "article-inline" (auto-injection blog) — même composant
        $rowsKey2 = DB::table('ads_placements')->where('key', 'article-inline')->count();
        if ($rowsKey2 > 0) {
            DB::table('ads_placements')
                ->where('key', 'article-inline')
                ->update([
                    'name' => 'Promo livre auteur (auto-injecté articles)',
                    'description' => 'Encart livre Stéphane Lapointe inséré automatiquement après le 3e paragraphe des articles',
                    'ad_code' => $newCode,
                    'updated_at' => now(),
                ]);
        }

        // Vider le cache pour que les nouveaux contenus soient servis immédiatement
        if (class_exists(\Modules\Ads\Services\AdsRenderer::class)) {
            app(\Modules\Ads\Services\AdsRenderer::class)->clearCache();
        }
    }

    public function down(): void
    {
        // Rollback : on ne restaure pas l'ancien HTML Nexus Neural (volontairement retiré).
        // Si besoin de rollback, désactiver les ads (is_active=0) plutôt que restaurer.
        DB::table('ads_placements')
            ->whereIn('key', ['book-author', 'article-inline'])
            ->update(['is_active' => 0, 'updated_at' => now()]);
    }
};
