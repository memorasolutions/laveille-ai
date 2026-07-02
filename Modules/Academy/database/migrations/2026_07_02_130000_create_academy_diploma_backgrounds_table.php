<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Phase 3 du système de diplomation moderne — bibliothèque d'images d'ARRIÈRE-PLAN
 * réutilisables pour un DiplomaTemplate (formateur owner-scopé via created_by,
 * même pattern que academy_diploma_templates). AUCUNE colonne de chemin de fichier
 * ici : l'image elle-même est gérée par Spatie MediaLibrary (collection « background »
 * sur le modèle DiplomaBackground, même pipeline que Course::cover / LessonItem::poster
 * — un seul système de stockage média, jamais un deuxième). Migration ADDITIVE
 * (guard hasTable) ; INERTE tant que academy.diploma_editor_enabled n'est pas activé.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_diploma_backgrounds')) {
            return;
        }

        Schema::create('academy_diploma_backgrounds', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // Formateur/organisation propriétaire (null = orphelin conservé si
            // l'utilisateur est supprimé — jamais de perte silencieuse d'arrière-plan).
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_diploma_backgrounds');
    }
};
