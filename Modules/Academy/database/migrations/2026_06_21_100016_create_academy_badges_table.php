<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Catalogue des badges / jalons d'engagement de l'Académie (Phase E / E1).
 * Un badge = une règle d'attribution (criteria_type + criteria_value) évaluée
 * SERVEUR depuis les données réelles de l'utilisateur. Migration ADDITIVE
 * (guard hasTable) ; aucune donnée n'est insérée ici (voir AcademyBadgesSeeder).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_badges')) {
            return;
        }

        Schema::create('academy_badges', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            // Icône courte (emoji ou sigle) affichée dans l'espace étudiant.
            $table->string('icon', 16)->nullable();
            // Type de critère : aucune contrainte enum SQL (portable SQLite/MySQL),
            // validé côté code via BadgeService. Valeurs :
            //   first_course_completed | course_completed | lessons_completed
            //   first_certificate | perfect_quiz
            $table->string('criteria_type');
            // Seuil numérique (ex. nb de leçons pour lessons_completed). Null = sans seuil.
            $table->unsignedInteger('criteria_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'criteria_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_badges');
    }
};
