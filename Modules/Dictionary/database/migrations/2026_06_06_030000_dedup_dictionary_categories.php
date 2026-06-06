<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dédoublonnage des catégories du glossaire (dictionary_categories).
 *
 * Contexte : la table contenait des lignes dupliquées (mêmes catégories ré-insérées),
 * provoquant un <select> de filtre avec chaque catégorie en triple. Le rendu/filtrage
 * (par slug) fonctionnait, mais le menu était sale.
 *
 * Stratégie SÛRE et RÉVERSIBLE :
 *  - groupe par `name` BRUT (JSON translatable) → ne fusionne QUE les doublons strictement
 *    identiques (jamais de sur-fusion de catégories réellement distinctes) ;
 *  - canonique = ligne avec icône non-nulle d'abord, sinon plus petit id ;
 *  - RÉASSIGNE d'abord dictionary_terms.dictionary_category_id des doublons → canonique
 *    (la FK est nullOnDelete : supprimer sans réassigner mettrait les termes à NULL) ;
 *  - puis supprime les lignes doublons ;
 *  - tables de sauvegarde créées AVANT toute écriture → down() restaure intégralement.
 *
 * Zéro perte de termes (le total dictionary_terms est inchangé ; seules des lignes
 * catégories redondantes disparaissent). Rollback : `migrate:rollback` (down) OU restauration
 * manuelle depuis dict_categories_dedup_bak / dict_terms_catmap_dedup_bak.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dictionary_categories') || ! Schema::hasTable('dictionary_terms')) {
            echo "[dedup] tables absentes — ignoré\n";
            return;
        }

        // 1) Sauvegardes (idempotentes) AVANT toute modification
        if (! Schema::hasTable('dict_categories_dedup_bak')) {
            DB::statement('CREATE TABLE dict_categories_dedup_bak AS SELECT * FROM dictionary_categories');
        }
        if (! Schema::hasTable('dict_terms_catmap_dedup_bak')) {
            DB::statement('CREATE TABLE dict_terms_catmap_dedup_bak AS SELECT id AS term_id, dictionary_category_id FROM dictionary_terms');
        }

        $cats = DB::table('dictionary_categories')->get();
        $termsBefore = DB::table('dictionary_terms')->count();
        echo "[dedup] AVANT : {$cats->count()} catégories, {$termsBefore} termes\n";

        // 2) Groupement par name brut
        $groups = [];
        foreach ($cats as $c) {
            $groups[(string) $c->name][] = $c;
        }

        $mergedGroups = 0;
        $deletedRows = 0;

        foreach ($groups as $rows) {
            if (count($rows) <= 1) {
                continue;
            }
            // canonique : icône présente d'abord, puis plus petit id
            usort($rows, function ($a, $b) {
                $ai = (isset($a->icon) && $a->icon !== null && $a->icon !== '') ? 0 : 1;
                $bi = (isset($b->icon) && $b->icon !== null && $b->icon !== '') ? 0 : 1;
                return $ai !== $bi ? ($ai <=> $bi) : ($a->id <=> $b->id);
            });
            $canonical = $rows[0];
            $dupIds = array_map(static fn ($r) => $r->id, array_slice($rows, 1));

            // 3) Réassigner les termes des doublons vers la canonique (AVANT suppression)
            DB::table('dictionary_terms')
                ->whereIn('dictionary_category_id', $dupIds)
                ->update(['dictionary_category_id' => $canonical->id]);

            // 4) Supprimer les lignes doublons
            DB::table('dictionary_categories')->whereIn('id', $dupIds)->delete();

            $mergedGroups++;
            $deletedRows += count($dupIds);
            echo "[dedup] canonique #{$canonical->id} ← supprimées : ".implode(',', $dupIds)."\n";
        }

        $catsAfter = DB::table('dictionary_categories')->count();
        $termsAfter = DB::table('dictionary_terms')->count();
        echo "[dedup] APRÈS : {$catsAfter} catégories ({$mergedGroups} groupes fusionnés, {$deletedRows} lignes supprimées), {$termsAfter} termes\n";
        echo "[dedup] termes inchangés : ".($termsBefore === $termsAfter ? 'OUI' : 'NON (!!)')."\n";
    }

    public function down(): void
    {
        // Restaure la catégorie d'origine de chaque terme
        if (Schema::hasTable('dict_terms_catmap_dedup_bak')) {
            DB::statement(
                'UPDATE dictionary_terms t '
                .'JOIN dict_terms_catmap_dedup_bak b ON b.term_id = t.id '
                .'SET t.dictionary_category_id = b.dictionary_category_id'
            );
        }
        // Ré-insère les catégories supprimées
        if (Schema::hasTable('dict_categories_dedup_bak')) {
            $existing = DB::table('dictionary_categories')->pluck('id')->all();
            foreach (DB::table('dict_categories_dedup_bak')->get() as $row) {
                if (! in_array($row->id, $existing, true)) {
                    DB::table('dictionary_categories')->insert((array) $row);
                }
            }
        }
    }
};
