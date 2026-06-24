<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F20 - BASE DE DONNÉES collaborative : VALEURS d'une entrée (une valeur par champ du
 * schéma). Table ADDITIVE et idempotente (guard Schema::hasTable).
 *
 *   - database_entry_id : entrée (fiche) porteuse (FK cascade).
 *   - database_field_id : champ du schéma concerné (FK cascade : un champ retiré du
 *     schéma est soft-supprimé côté champ ; ses valeurs cascadent si l'entrée part).
 *   - value             : valeur saisie (texte brut borné ; rendu strippé/échappé selon
 *     le type au moment de l'affichage, anti-XSS).
 *   - Index (database_entry_id)  : lecture groupée des valeurs d'une entrée (anti N+1).
 *   - Index (database_field_id)  : agrégations éventuelles par champ.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_database_values')) {
            return;
        }

        Schema::create('academy_database_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('database_entry_id');
            $table->unsignedBigInteger('database_field_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('database_entry_id')
                ->references('id')
                ->on('academy_database_entries')
                ->onDelete('cascade');

            $table->foreign('database_field_id')
                ->references('id')
                ->on('academy_database_fields')
                ->onDelete('cascade');

            $table->index(['database_entry_id']);
            $table->index(['database_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_database_values');
    }
};
