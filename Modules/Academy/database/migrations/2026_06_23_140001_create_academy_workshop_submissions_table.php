<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F21 - ATELIER (Workshop) : TRAVAUX remis par les inscrits (1 par étudiant) en phase de
 * soumission (type Moodle « Workshop »). Table ADDITIVE et idempotente (guard
 * Schema::hasTable).
 *
 *   - lesson_item_id : item « workshop » porteur (FK cascade).
 *   - user_id NULLABLE (nullOnDelete) : auteur du travail ; null si compte supprimé
 *     (le travail est conservé, affiché « (inconnu) »).
 *   - title          : titre court du travail.
 *   - body           : texte du travail (pièce jointe = HORS périmètre, dette assumée).
 *   - status         : « submitted » (remis). Réservé pour des états futurs.
 *   - SoftDeletes    : un travail supprimé est conservé (audit), exclu par défaut.
 *   - Index (lesson_item_id, user_id) : recherche rapide du travail d'un étudiant.
 *     L'unicité « 1 travail par étudiant » est garantie au service (firstOrNew) plutôt
 *     que par une contrainte unique, pour rester compatible avec les SoftDeletes.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_workshop_submissions')) {
            return;
        }

        Schema::create('academy_workshop_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_item_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->string('status', 20)->default('submitted');
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

            $table->index(['lesson_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_workshop_submissions');
    }
};
