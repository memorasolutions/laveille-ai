<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F20 - BASE DE DONNÉES collaborative : ENTRÉES (fiches) soumises par les inscrits selon
 * le schéma de l'activité « database » (type Moodle « Database »). Une entrée porte
 * plusieurs valeurs (une par champ, table academy_database_values). Table ADDITIVE et
 * idempotente (guard Schema::hasTable).
 *
 *   - lesson_item_id : item « database » porteur (FK cascade).
 *   - user_id NULLABLE (nullOnDelete) : auteur de la fiche ; null si compte supprimé
 *     (la fiche est conservée, affichée « (inconnu) »).
 *   - is_approved    : la fiche est-elle visible de tous ? Défaut true. Quand l'activité
 *     exige une modération (require_approval), une fiche d'étudiant naît à false : visible
 *     de son seul auteur + des gérants, jusqu'à approbation.
 *   - SoftDeletes    : une fiche supprimée (par l'auteur ou un gérant) est conservée
 *     (audit/modération), exclue par défaut.
 *   - Index (lesson_item_id, is_approved) : liste filtrée rapide (approuvées / en attente).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_database_entries')) {
            return;
        }

        Schema::create('academy_database_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_item_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_approved')->default(true);
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

            $table->index(['lesson_item_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_database_entries');
    }
};
