<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Additive uniquement : ajoute first_name/last_name (nullable) à côté de la
     * colonne name existante, sans y toucher. Objectif : offrir des variables
     * distinctes ("Bonjour {prénom}") sans casser les 47 vues qui lisent déjà
     * $user->name. La colonne name reste la source de vérité affichée partout
     * et est synchronisée (trim("{first_name} {last_name}")) côté application
     * à chaque écriture qui fournit les deux nouveaux champs.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
