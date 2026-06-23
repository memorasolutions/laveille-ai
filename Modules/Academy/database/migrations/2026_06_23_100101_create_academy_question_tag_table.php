<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F17 - Pivot question <-> tag. Table ADDITIVE. Unicite (question_id, tag_id) pour
 * empecher tout doublon d'attachement. Pas de FK inter-module dure (portabilite) ;
 * le rattachement reste integre au niveau applicatif (sync owner-scope dans le
 * gestionnaire de banque). Guard hasTable ; down dropIfExists.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_question_tag')) {
            return;
        }

        Schema::create('academy_question_tag', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('question_id')->index();
            $table->unsignedBigInteger('tag_id')->index();
            $table->timestamps();

            $table->unique(['question_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_question_tag');
    }
};
