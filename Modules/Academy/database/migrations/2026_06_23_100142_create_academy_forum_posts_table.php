<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FORUM - RÉPONSES (messages) à un sujet de discussion (item « forum »). Table
 * ADDITIVE et idempotente (guards) : aucune autre table n'est touchée, aucune
 * dépendance.
 *
 *   - topic_id : sujet parent (FK cascade).
 *   - user_id NULLABLE (nullOnDelete) : auteur ; null si le compte est supprimé.
 *   - SoftDeletes : une réponse supprimée est conservée (audit/modération), exclue
 *     par défaut.
 *   - Index (topic_id) : chargement des réponses d'un sujet performant.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_forum_posts')) {
            return;
        }

        Schema::create('academy_forum_posts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('topic_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('topic_id')
                ->references('id')
                ->on('academy_forum_topics')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('topic_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_forum_posts');
    }
};
