<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F17 - VERSIONS (historique leger) d'une question de banque. A chaque edition, on
 * archive d'ABORD l'etat PRECEDENT (numero de version incremente) avant d'ecrire la
 * nouvelle valeur. Table ADDITIVE, owner-scopee. Aucun « epinglage » par un quiz
 * (hors perimetre F17) : c'est un journal de consultation/restauration uniquement.
 * Guard hasTable ; down dropIfExists.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_question_versions')) {
            return;
        }

        Schema::create('academy_question_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('question_id')->index();
            // Proprietaire recopie depuis la question (scope owner direct, anti-IDOR).
            $table->unsignedBigInteger('owner_id')->index();
            // Numero de version croissant par question (1, 2, 3...).
            $table->unsignedInteger('version')->default(1);
            $table->text('prompt');
            $table->json('payload')->nullable();
            $table->text('explanation')->nullable();
            $table->string('type', 32);
            $table->timestamp('snapshot_at')->nullable();
            $table->timestamps();

            $table->unique(['question_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_question_versions');
    }
};
