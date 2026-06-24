<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F19 - WIKI : PAGES collaboratives attachées à un item de leçon « wiki » (type Moodle
 * « Wiki »). Table ADDITIVE et idempotente (guards) : aucune autre table n'est touchée,
 * aucune dépendance. Les types d'items existants restent inchangés.
 *
 *   - lesson_item_id : item « wiki » porteur (FK cascade).
 *   - title / slug   : titre lisible + identifiant d'URL stable (unique par wiki, géré
 *                      au service car les pages supprimées (soft) conservent leur slug).
 *   - body           : contenu markdown (rendu strippé, anti-XSS, au lecteur).
 *   - created_by NULLABLE (nullOnDelete) : auteur d'origine ; null si compte supprimé
 *     (la page est conservée, affichée « (inconnu) »).
 *   - edited_by NULLABLE (nullOnDelete) : dernier éditeur (= auteur de l'état COURANT).
 *     Sert à attribuer la révision snapshotée à son vrai auteur (cf. WikiService).
 *   - revision       : numéro de version COURANTE (commence à 1, incrémenté à l'édition).
 *   - is_home        : page « accueil » par défaut du wiki (une seule, la 1re créée).
 *   - is_locked      : page verrouillée (lecture seule ; plus d'édition sauf gérant).
 *   - SoftDeletes    : une page supprimée est conservée (audit/modération), exclue par défaut.
 *   - Index (lesson_item_id, is_home) : résolution rapide de la page d'accueil.
 *   - Index (lesson_item_id, slug)    : résolution rapide d'une page par slug.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_wiki_pages')) {
            return;
        }

        Schema::create('academy_wiki_pages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_item_id');
            $table->string('title', 200);
            $table->string('slug', 220);
            $table->text('body')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->boolean('is_home')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lesson_item_id')
                ->references('id')
                ->on('lesson_items')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('edited_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['lesson_item_id', 'is_home']);
            $table->index(['lesson_item_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_wiki_pages');
    }
};
