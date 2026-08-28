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
 * Incident 2026-08-13 (mesuré par recoupement GA4, propriété 500300528, sur janvier 2026 à
 * aujourd'hui) : `clicks_count` compte les robots depuis l'origine, sans tri ni déduplication -
 * jusqu'à 652 fois le trafic humain réel selon la fiche (ex. FLUX : 1 957 affichés contre 3
 * vues réelles). L'annuaire était le SEUL module resté sur `$tool->increment('clicks_count')`
 * brut ; Tools, Authors, News et Dictionary sont déjà passés par
 * Modules\Core\Services\ViewCounterService (tri anti-robot + déduplication courte fenêtre).
 *
 * Second compteur "propre" ajouté à côté de `clicks_count`, jamais de réinitialisation ni de
 * suppression de l'historique (donnée existante, même fausse - voir Modules/Tools/database/
 * migrations/2026_08_14_090000_... pour la même décision sur `tools`).
 *
 * Contrairement au glossaire (Modules/Dictionary/database/migrations/2026_08_28_090000_...,
 * qui a délibérément renoncé à ce jumeau), la colonne jumelle est ici justifiée : sur le
 * glossaire, `views_count` est filtré/dédupliqué dès sa toute première écriture, donc un
 * jumeau y resterait identique pour toujours (aucun risque réel de divergence - DRY interdit
 * ce doublon). Ici `clicks_count` porte au contraire un historique DÉJÀ pollué par des années
 * de comptage brut, qu'on ne peut pas assainir rétroactivement (on ne sait pas, pour une ligne
 * déjà incrémentée, si elle vient d'un robot ou d'un humain). Une colonne qui repart de zéro,
 * filtrée dès sa création, est donc le seul moyen d'obtenir un jour un chiffre honnête, sans
 * détruire ni falsifier `clicks_count`.
 *
 * Nom retenu : `clicks_count_verified` (et non `views_count_verified`) - Modules\Core\Services\
 * ViewCounterService::record() calcule le nom de la colonne jumelle en ajoutant
 * config('view_counter.verified_suffix') au nom de la colonne HISTORIQUE passée en argument ;
 * ici cet argument est 'clicks_count' (voir PublicDirectoryController::show()), pas
 * 'views_count' comme dans les 3 autres modules.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('directory_tools', 'clicks_count_verified')) {
            return;
        }

        Schema::table('directory_tools', function (Blueprint $table) {
            $table->unsignedBigInteger('clicks_count_verified')->default(0)->after('clicks_count');
            $table->index('clicks_count_verified');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('directory_tools', 'clicks_count_verified')) {
            return;
        }

        // Même garde que Modules/Dictionary/database/migrations/2026_08_28_090000_... : un
        // rollback naïf effacerait des vues réellement comptées, ce qui est une suppression de
        // données interdite sans exception sur ce projet. On ne retire la colonne QUE si elle
        // est encore vide (aucune perte possible) ; sinon on s'arrête en le disant plutôt que
        // de détruire en silence ce qu'on est incapable de reconstituer.
        $cumul = (int) DB::table('directory_tools')->sum('clicks_count_verified');

        if ($cumul > 0) {
            throw new RuntimeException(
                'Rollback refusé : la colonne clicks_count_verified de directory_tools porte '
                .$cumul.' vues cumulées. Les retirer serait une perte de données. '
                .'Sauvegarder la colonne avant, puis retirer la garde de ce down() '
                .'si le retrait est vraiment voulu.'
            );
        }

        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropIndex(['clicks_count_verified']);
            $table->dropColumn('clicks_count_verified');
        });
    }
};
