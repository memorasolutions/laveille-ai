<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Mode kiosque — journal des incidents (« violations ») consignés pendant une
 * tentative de quiz surveillée : sortie de plein écran, changement d'onglet,
 * outils de développement suspectés, sortie volontaire du mode kiosque.
 * PUREMENT DÉCLARATIF : aucune invalidation automatique de tentative, juste
 * un journal consultable par le formateur propriétaire du cours (transparence
 * envers l'apprenant — voir Services\KioskViolationService).
 * Migration ADDITIVE (guard hasTable), down() = drop strict.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_kiosk_violations')) {
            return;
        }

        Schema::create('academy_kiosk_violations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quiz_attempt_id');
            $table->unsignedBigInteger('user_id');
            // Dénormalisé pour permettre un reporting par item sans jointure
            // supplémentaire (la tentative peut être supprimée sans perdre le contexte).
            $table->unsignedBigInteger('lesson_item_id');
            // Liste blanche applicative (voir KioskViolationService::TYPES) : jamais une
            // valeur libre du client — validée serveur avant insertion.
            $table->string('type', 40);
            $table->timestamp('occurred_at');
            // Détails techniques optionnels (ex. dimensions fenêtre pour devtools_suspected).
            // Jamais de PII : uniquement des métadonnées techniques de diagnostic.
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('quiz_attempt_id')
                ->references('id')
                ->on('academy_quiz_attempts')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // Lecture fréquente : incidents d'une tentative (formateur), ordonnés par temps.
            $table->index(['quiz_attempt_id', 'occurred_at']);
            // Lecture fréquente : incidents d'un item toutes tentatives confondues (audit).
            $table->index(['lesson_item_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_kiosk_violations');
    }
};
