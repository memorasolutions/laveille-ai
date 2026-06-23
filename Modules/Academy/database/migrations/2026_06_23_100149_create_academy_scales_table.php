<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F14 - ÉCHELLES PERSONNALISÉES (scales, parité Moodle). Une échelle appartient à
 * UN propriétaire (owner_id, formateur) ; l'admin gère tout (autorisation SERVEUR
 * côté composant). « items » = liste ORDONNÉE [{label, value}] (du plus faible au
 * plus fort). Migration ADDITIVE guardée (hasTable). Table NOUVELLE : aucune
 * donnée existante touchée ; down() = drop de la seule table nouvelle.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_scales')) {
            return;
        }

        Schema::create('academy_scales', function (Blueprint $table): void {
            $table->id();
            // owner_id nullable : null = échelle « système » partagée (admin). Un
            // formateur ne voit/édite que SES échelles (scope owned, anti-IDOR).
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            // Liste ordonnée des niveaux : [{label: string, value: float}]. JSON
            // pour rester simple et portable (pas de table d'items dédiée).
            $table->json('items')->nullable();
            $table->timestamps();

            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_scales');
    }
};
