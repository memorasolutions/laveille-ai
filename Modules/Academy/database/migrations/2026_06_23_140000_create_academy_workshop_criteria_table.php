<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F21 - ATELIER (Workshop) : GRILLE de critères d'évaluation par les pairs (type Moodle
 * « Workshop »). Le gérant définit la grille ; chaque pair note chaque critère (0..max_score)
 * pour les travaux qui lui sont attribués. Table ADDITIVE et idempotente (guard
 * Schema::hasTable) : aucune autre table n'est touchée, aucune dépendance ; les types
 * d'items existants restent inchangés.
 *
 *   - lesson_item_id : item « workshop » porteur (FK cascade).
 *   - label          : libellé du critère affiché à l'évaluateur.
 *   - description    : précision facultative (consigne de notation).
 *   - max_score      : note maximale du critère (entier, défaut 10).
 *   - weight         : pondération du critère dans la note du travail (défaut 1).
 *   - position       : ordre d'affichage / de notation (0, 1, 2…).
 *   - SoftDeletes    : un critère retiré de la grille est conservé (les notes déjà
 *                      saisies restent rattachées) ; exclu des listes par défaut.
 *   - Index (lesson_item_id, position) : lecture ordonnée rapide de la grille.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_workshop_criteria')) {
            return;
        }

        Schema::create('academy_workshop_criteria', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_item_id');
            $table->string('label', 200);
            $table->text('description')->nullable();
            $table->unsignedInteger('max_score')->default(10);
            $table->decimal('weight', 8, 2)->default(1);
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
        Schema::dropIfExists('academy_workshop_criteria');
    }
};
