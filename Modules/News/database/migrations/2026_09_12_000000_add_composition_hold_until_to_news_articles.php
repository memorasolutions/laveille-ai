<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION : colonne `composition_hold_until` (RÉTENTION de composition, mesuré le 2026-09-12 -
 *          `news:prune-drafts` a supprimé les 17 fiches d'un lot éditorial en attente de
 *          composition, faute de tout mécanisme distinguant un brouillon orphelin d'un brouillon
 *          RETENU pour un travail en cours). Nullable = comportement actuel de la purge
 *          INCHANGÉ pour toute fiche existante (aucune n'est retenue tant que la colonne n'est
 *          pas explicitement posée par `news:hold` ou `news:apply --payload`).
 *
 *          Indexée : `PruneDraftsCommand::collectCandidates()` la lit à CHAQUE ligne du flux
 *          (~662 articles/jour) dans la requête de sélection elle-même (jamais un filtre après
 *          coup - contrairement au critère composé de `structured_summary`, en JSON, qui ne peut
 *          être testé qu'en PHP après hydratation).
 * MCP: SELF (<5 lignes)
 * RAISON: mécanisme de rétention imposé - une fiche sur laquelle un travail éditorial est en
 *         cours ne doit jamais concurrencer la fenêtre des N plus récents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'composition_hold_until')) {
                $table->timestamp('composition_hold_until')->nullable()->after('reviewed_by')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'composition_hold_until')) {
                $table->dropColumn('composition_hold_until');
            }
        });
    }
};
