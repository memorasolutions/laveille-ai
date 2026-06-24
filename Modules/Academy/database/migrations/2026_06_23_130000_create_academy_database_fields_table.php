<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F20 - BASE DE DONNÉES collaborative : SCHÉMA des champs d'une activité « database »
 * (type Moodle « Database »). Le gérant définit un schéma de champs ; les inscrits
 * soumettent ensuite des fiches (entrées) selon ce schéma. Table ADDITIVE et idempotente
 * (guard Schema::hasTable) : aucune autre table n'est touchée, aucune dépendance ; les
 * types d'items existants restent inchangés.
 *
 *   - lesson_item_id : item « database » porteur (FK cascade).
 *   - label          : libellé lisible affiché à l'étudiant.
 *   - name           : clé technique (slug) du champ, unique par activité (gérée au service).
 *   - type           : type simple du champ (text / textarea / number / url / select).
 *   - options        : JSON des choix possibles (uniquement pour le type « select »).
 *   - required       : la valeur du champ est-elle obligatoire à la soumission ?
 *   - position       : ordre d'affichage / de saisie (0, 1, 2…).
 *   - SoftDeletes    : un champ retiré du schéma est conservé (les valeurs déjà saisies
 *                      restent rattachées) ; il est exclu des listes par défaut.
 *   - Index (lesson_item_id, position) : lecture ordonnée rapide du schéma.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_database_fields')) {
            return;
        }

        Schema::create('academy_database_fields', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_item_id');
            $table->string('label', 200);
            $table->string('name', 120);
            $table->string('type', 20)->default('text');
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lesson_item_id')
                ->references('id')
                ->on('lesson_items')
                ->onDelete('cascade');

            $table->index(['lesson_item_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_database_fields');
    }
};
