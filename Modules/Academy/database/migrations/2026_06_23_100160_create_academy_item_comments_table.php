<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F18 - COMMENTAIRES sur un item de leçon (parité Moodle « comments »). Table
 * ADDITIVE et idempotente (guard) : aucune autre table n'est touchée, aucune
 * dépendance. Un item sans commentaire reste inchangé (rétrocompat).
 *
 *   - lesson_item_id : item commenté (FK cascade).
 *   - user_id NULLABLE (nullOnDelete) : auteur ; null si le compte est supprimé
 *     (affiché « (inconnu) » dans la vue).
 *   - SoftDeletes : un commentaire supprimé est conservé (audit/modération), exclu
 *     par défaut.
 *   - Index (lesson_item_id) : chargement des commentaires d'un item performant.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_item_comments')) {
            return;
        }

        Schema::create('academy_item_comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_item_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lesson_item_id')
                ->references('id')
                ->on('lesson_items')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('lesson_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_item_comments');
    }
};
