<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Incident 2026-08-13 : views_count s'incrémente sans filtre robots ni
 * déduplication depuis l'origine (rapport vues/clics réels mesuré de 8 à
 * 487x selon la fiche). Décision : ne JAMAIS réinitialiser/supprimer
 * l'historique - ajout d'un second compteur "propre", filtré/dédupliqué
 * dès sa création, qui repart de zéro pour permettre la comparaison et
 * une future bascule des critères de tri/popularité. Voir
 * Modules\Core\Services\ViewCounterService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->unsignedInteger('views_count_verified')->default(0)->after('views_count');
            $table->index('views_count_verified');
        });
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropIndex(['views_count_verified']);
            $table->dropColumn('views_count_verified');
        });
    }
};
