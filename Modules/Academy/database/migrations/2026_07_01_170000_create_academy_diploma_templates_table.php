<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Gabarits de diplômes personnalisables par les formateurs (éditeur Konva.js,
 * Phase 1 du système de diplomation moderne). Chaque gabarit est owner-scopé
 * via created_by (nullable pour ne jamais perdre un gabarit si l'utilisateur
 * est supprimé, même pattern que academy_question_categories.owner_id).
 * Migration ADDITIVE (guard hasTable) ; INERTE tant que le drapeau
 * academy.diploma_editor_enabled n'est pas activé.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_diploma_templates')) {
            return;
        }

        Schema::create('academy_diploma_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // Éléments du canevas (positions en % — logo, nom, titre cours, date,
            // signature, QR, texte custom), voir DiplomaTemplate::elements().
            $table->json('layout_config');
            $table->boolean('is_default')->default(false);
            // Formateur/organisation propriétaire (null = orphelin conservé si
            // l'utilisateur est supprimé — jamais de perte silencieuse de gabarit).
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['created_by', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_diploma_templates');
    }
};
