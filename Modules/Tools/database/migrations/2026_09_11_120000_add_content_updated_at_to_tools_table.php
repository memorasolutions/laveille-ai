<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Introduit la date de modification ÉDITORIALE (`content_updated_at`), distincte de
 * `updated_at` - voir Modules\Core\Traits\TracksEditorialModification et
 * docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md (MESURE B). `updated_at` est réécrit
 * par une simple consultation (Modules\Core\Services\ViewCounterService::record()) depuis
 * toujours, et publié à tort comme `lastmod` d'un outil dans le sitemap général.
 *
 * Colonne ADDITIVE, nullable : aucune ligne existante n'est modifiée avant le backfill explicite
 * ci-dessous, aucune colonne existante n'est touchée.
 *
 * Valeur de départ HONNÊTE pour l'existant : la date de CRÉATION pour toutes les lignes, sans
 * exception. `Modules\Tools\Models\Tool` ne journalise aucune édition (aucun trait Activitylog) -
 * il n'existe donc AUCUNE trace d'édition antérieure exploitable pour ce module, et fabriquer une
 * date plus récente que created_at serait exactement le mensonge qu'on corrige.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            if (! Schema::hasColumn('tools', 'content_updated_at')) {
                $table->timestamp('content_updated_at')->nullable()->after('updated_at');
            }
        });

        DB::table('tools')->update(['content_updated_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Donnée entièrement DÉRIVÉE (= created_at, jamais une saisie irremplaçable) : un
        // rollback qui retire la colonne ne perd aucune information qu'on ne pourrait pas
        // recalculer en la relançant.
        Schema::table('tools', function (Blueprint $table) {
            if (Schema::hasColumn('tools', 'content_updated_at')) {
                $table->dropColumn('content_updated_at');
            }
        });
    }
};
