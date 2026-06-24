<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F19 - WIKI (remédiation audit B2) : promeut l'index (lesson_item_id, slug) en contrainte
 * UNIQUE. WikiService::uniqueSlug garantit déjà l'unicité côté applicatif, mais sans filet BD
 * deux requêtes concurrentes pouvaient insérer le même slug et rendre une page inaccessible.
 *
 * Migration corrective ADDITIVE et idempotente : sûre que la table ait été créée avec l'index
 * simple (déploiement initial) OU déjà avec l'unique (installation neuve après le correctif).
 * On NE modifie jamais la migration d'origine déjà déployée (elle ne se rejouerait pas).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_wiki_pages')) {
            return;
        }

        // Retrait de l'index simple s'il existe (nom conventionnel Laravel).
        Schema::table('academy_wiki_pages', function (Blueprint $table): void {
            try {
                $table->dropIndex('academy_wiki_pages_lesson_item_id_slug_index');
            } catch (\Throwable $e) {
                // Index absent (installation neuve déjà en unique) : on continue.
            }
        });

        // Ajout de la contrainte unique si elle n'est pas déjà présente.
        Schema::table('academy_wiki_pages', function (Blueprint $table): void {
            try {
                $table->unique(['lesson_item_id', 'slug']);
            } catch (\Throwable $e) {
                // Contrainte déjà présente : rien à faire.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academy_wiki_pages')) {
            return;
        }

        Schema::table('academy_wiki_pages', function (Blueprint $table): void {
            try {
                $table->dropUnique('academy_wiki_pages_lesson_item_id_slug_unique');
            } catch (\Throwable $e) {
                // Contrainte absente : on continue.
            }

            try {
                $table->index(['lesson_item_id', 'slug']);
            } catch (\Throwable $e) {
                // Index déjà présent : rien à faire.
            }
        });
    }
};
