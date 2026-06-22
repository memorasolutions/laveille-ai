<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Catégories de la banque de questions réutilisable (QB1, socle DONNÉES).
 * Arbre owner-scoped : chaque formateur possède SES catégories (owner_id),
 * organisables en sous-catégories (parent_id self). Migration ADDITIVE
 * (guard hasTable) ; INERTE tant que QB2 (UI + câblage quiz) n'est pas livré.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_question_categories')) {
            return;
        }

        Schema::create('academy_question_categories', function (Blueprint $table): void {
            $table->id();
            // Formateur propriétaire (null = orphelin conservé si l'utilisateur est supprimé).
            $table->unsignedBigInteger('owner_id')->nullable();
            // Parent self (null = racine).
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('parent_id')
                ->references('id')
                ->on('academy_question_categories')
                ->nullOnDelete();

            $table->index(['owner_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_question_categories');
    }
};
