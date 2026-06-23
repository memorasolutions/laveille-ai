<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F17 - TAGS de la banque de questions. Table ADDITIVE, owner-scopée : chaque
 * formateur a SES propres etiquettes (owner_id), creees a la volee. Unicite du
 * slug PAR proprietaire (un meme libelle peut exister chez deux formateurs sans
 * collision). Guard hasTable ; down dropIfExists. Aucune question existante n'est
 * touchee (retrocompat stricte : une question sans tag fonctionne exactement comme
 * avant).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_question_tags')) {
            return;
        }

        Schema::create('academy_question_tags', function (Blueprint $table): void {
            $table->id();
            // Propriétaire (formateur). Pas de FK inter-module (portabilité, cf. QuizAttempt).
            $table->unsignedBigInteger('owner_id')->index();
            $table->string('name', 80);
            $table->string('slug', 100);
            $table->timestamps();

            // Unicité du slug par propriétaire (anti-doublon owner-scope).
            $table->unique(['owner_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_question_tags');
    }
};
