<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Import SCORM - table d'inscription (« registration » au sens SCORM) qui porte
 * l'état d'exécution CMI PAR UTILISATEUR pour un LessonItem de type « scorm ».
 * Équivalent Moodle « scorm_scoes_track », simplifié au périmètre MVP (une seule
 * ligne par (user, lesson_item) - single-SCO uniquement, voir config('academy.scorm')).
 *
 * « cmi_data » : dump JSON complet des paires clé/valeur CMI reçues du SCO
 * (utile au diagnostic + reprise), SANS remplacer le système de progression
 * existant : lesson_status/score_raw sont EXTRAITS et bridgés vers
 * Completion/ProgressService via CompletionService::markComplete (DRY, source
 * unique de la progression du cours).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_scorm_registrations')) {
            return;
        }

        Schema::create('academy_scorm_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('lesson_item_id');

            // Statut brut cmi.core.lesson_status (1.2) ou dérivé de
            // cmi.completion_status/success_status (2004). Valeurs possibles :
            // passed / completed / failed / incomplete / browsed / not attempted.
            $table->string('lesson_status')->nullable();

            // cmi.core.score.raw (1.2) ou cmi.score.raw (2004), borné 0..100 côté service.
            $table->unsignedTinyInteger('score_raw')->nullable();

            // Reprise (dette assumée : pas de moteur de séquencement 2004, simple mémo).
            $table->string('lesson_location', 255)->nullable();
            $table->text('suspend_data')->nullable();

            // Dump complet des clés CMI reçues (diagnostic, extensibilité future).
            $table->json('cmi_data')->nullable();

            $table->timestamp('last_committed_at')->nullable();
            $table->timestamps();

            // Une seule inscription par (utilisateur, item) - idempotent (updateOrCreate).
            $table->unique(['user_id', 'lesson_item_id'], 'academy_scorm_reg_unique');

            $table->foreign('lesson_item_id')
                ->references('id')
                ->on('lesson_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_scorm_registrations');
    }
};
