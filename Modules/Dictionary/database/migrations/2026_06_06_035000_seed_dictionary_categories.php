<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed des 6 catégories stables du glossaire (dictionary_categories).
 *
 * Contexte : ces catégories n'ont jamais été créées par une migration versionnée — elles
 * n'existaient qu'en prod (seedées manuellement à l'origine, cf. commentaire de
 * 2026_06_06_040000_add_glossary_terms_ml_batch1.php : « FK vers dictionary_categories
 * qui n'existent que sur MySQL (seedées en prod) »). Des dizaines de migrations de batch
 * de termes (2026_06_06 → 2026_07_07) référencent ces IDs en dur (dictionary_category_id
 * = 1 à 6), noms confirmés par leurs docblocks respectifs :
 *   1 « Intelligence artificielle » (IA), 2 « Concepts fondamentaux », 3 « Acronymes et sigles »,
 *   4 « Sécurité et éthique », 5 « Outils et techniques », 6 « Données et traitement ».
 *
 * Sans cette migration, toute base fraîche (ou restaurée après un migrate:fresh) casse sur
 * la première migration de batch de termes (violation de clé étrangère dictionary_category_id).
 *
 * IDs explicites (1-6) obligatoires pour rester compatible avec ces références en dur.
 * Idempotent (skip si l'ID existe déjà) — sûr à rejouer. RÉVERSIBLE (down() ne supprime que
 * les lignes dont le nom correspond encore à celui inséré ici, pour ne jamais écraser une
 * catégorie renommée depuis via l'admin).
 */
return new class extends Migration
{
    private function categories(): array
    {
        return [
            ['id' => 1, 'name' => 'Intelligence artificielle', 'slug' => 'intelligence-artificielle', 'description' => "Termes généraux liés à l'intelligence artificielle.", 'icon' => '🤖', 'sort_order' => 1],
            ['id' => 2, 'name' => 'Concepts fondamentaux', 'slug' => 'concepts-fondamentaux', 'description' => "Notions de base de l'apprentissage automatique et du deep learning.", 'icon' => '📚', 'sort_order' => 2],
            ['id' => 3, 'name' => 'Acronymes et sigles', 'slug' => 'acronymes-et-sigles', 'description' => "Acronymes et sigles courants du domaine de l'IA.", 'icon' => '🔤', 'sort_order' => 3],
            ['id' => 4, 'name' => 'Sécurité et éthique', 'slug' => 'securite-et-ethique', 'description' => "Sécurité, alignement et enjeux éthiques de l'IA.", 'icon' => '🛡️', 'sort_order' => 4],
            ['id' => 5, 'name' => 'Outils et techniques', 'slug' => 'outils-et-techniques', 'description' => "Outils, logiciels et techniques liés à l'IA.", 'icon' => '🛠️', 'sort_order' => 5],
            ['id' => 6, 'name' => 'Données et traitement', 'slug' => 'donnees-et-traitement', 'description' => "Données, métriques et traitement de l'information.", 'icon' => '📊', 'sort_order' => 6],
        ];
    }

    public function up(): void
    {
        if (! Schema::hasTable('dictionary_categories')) {
            echo "[seed-categories] table absente — ignoré\n";

            return;
        }

        foreach ($this->categories() as $cat) {
            if (DB::table('dictionary_categories')->where('id', $cat['id'])->exists()) {
                echo "[seed-categories] id={$cat['id']} déjà présent, skip\n";

                continue;
            }

            DB::table('dictionary_categories')->insert([
                'id' => $cat['id'],
                'name' => json_encode(['fr_CA' => $cat['name'], 'fr' => $cat['name']]),
                'slug' => json_encode(['fr_CA' => $cat['slug'], 'fr' => $cat['slug']]),
                'description' => json_encode(['fr_CA' => $cat['description'], 'fr' => $cat['description']]),
                'icon' => $cat['icon'],
                'color' => null,
                'sort_order' => $cat['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            echo "[seed-categories] inséré : id={$cat['id']} {$cat['name']}\n";
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('dictionary_categories')) {
            return;
        }

        foreach ($this->categories() as $cat) {
            DB::table('dictionary_categories')
                ->where('id', $cat['id'])
                ->where('name', json_encode(['fr_CA' => $cat['name'], 'fr' => $cat['name']]))
                ->delete();
        }
    }
};
