<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F19 - WIKI : RÉVISIONS (historique) d'une page de wiki. Une ligne = l'état PRÉCÉDENT
 * d'une page, snapshoté juste AVANT une édition (même principe que QuestionVersion F17 :
 * on conserve l'état remplacé). Table ADDITIVE et idempotente (guards).
 *
 *   - wiki_page_id : page concernée (FK cascade).
 *   - user_id NULLABLE (nullOnDelete) : auteur du contenu snapshoté (pas l'éditeur qui
 *     remplace) ; null si compte supprimé (affiché « (inconnu) »).
 *   - title / body : copie du contenu à l'instant du snapshot (lecture seule).
 *   - revision     : numéro de version snapshotée (1, 2, 3…).
 *   - snapshot_at  : horodatage du snapshot.
 *   - Index (wiki_page_id, revision) : historique trié performant.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_wiki_revisions')) {
            return;
        }

        Schema::create('academy_wiki_revisions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('wiki_page_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title', 200);
            $table->text('body')->nullable();
            $table->unsignedInteger('revision');
            $table->timestamp('snapshot_at')->nullable();
            $table->timestamps();

            $table->foreign('wiki_page_id')
                ->references('id')
                ->on('academy_wiki_pages')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['wiki_page_id', 'revision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_wiki_revisions');
    }
};
