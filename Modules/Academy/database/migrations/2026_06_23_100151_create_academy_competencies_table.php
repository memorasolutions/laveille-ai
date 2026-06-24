<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F22 - COMPÉTENCES / RÉSULTATS (« competencies / outcomes », parité Moodle). Le
 * RÉFÉRENTIEL de compétences appartient à UN propriétaire (owner_id, formateur) ;
 * l'admin (academy.manage) gère tout (autorisation SERVEUR côté composant).
 *
 * Chaque compétence peut référencer une ÉCHELLE F14 (scale_id, academy_scales) qui
 * sert à exprimer le NIVEAU d'acquisition ; sans échelle, le barème est binaire par
 * défaut (« Non atteint » / « Atteint »). « pass_threshold » (optionnel, 1..100) est
 * le seuil de note appliqué aux items NOTÉS liés (cf. CompetencyService) : null =
 * l'acquisition repose UNIQUEMENT sur l'achèvement (V2-c), strictement rétrocompatible.
 *
 * Migration ADDITIVE guardée (hasTable). Table NOUVELLE : aucune donnée existante
 * touchée. RÉTROCOMPAT : aucune compétence créée = comportement actuel inchangé.
 * down() = drop de la seule table nouvelle.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_competencies')) {
            return;
        }

        Schema::create('academy_competencies', function (Blueprint $table): void {
            $table->id();
            // owner_id nullable : null = compétence « système » partagée (admin). Un
            // formateur ne voit/édite que SES compétences (scope owned, anti-IDOR).
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('name', 160);
            $table->string('slug', 180)->nullable();
            $table->text('description')->nullable();
            // Échelle F14 réutilisée pour le NIVEAU d'acquisition (nullOnDelete : si
            // l'échelle est supprimée, la compétence retombe sur le barème binaire).
            $table->unsignedBigInteger('scale_id')->nullable();
            // Seuil de note (1..100) appliqué aux items NOTÉS liés. NULL = acquisition
            // par achèvement seul (rétrocompat). Voir CompetencyService::acquisitionState.
            $table->unsignedTinyInteger('pass_threshold')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('scale_id')->references('id')->on('academy_scales')->nullOnDelete();

            $table->index('owner_id');
            // Unicité du slug par propriétaire (anti-doublon owner-scope).
            $table->unique(['owner_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_competencies');
    }
};
