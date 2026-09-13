<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: ticket #2524 (étape 2) - colonne is_approved sur le pivot news_article_term, DÉFAUT
 *         VRAI. C'est l'administrateur qui DÉSAPPROUVE ce qui est faux : un défaut FAUX
 *         condamnerait les quelque 2940 liaisons déjà posées en production (source 'auto', voir
 *         NewsToolSyncAction) à ne jamais s'afficher avant une revue humaine complète de
 *         toutes, alors qu'un contrôle qualité sur 14 liaisons tirées au hasard les a toutes
 *         confirmées justes. Doctrine « désapprouver, JAMAIS supprimer » : une liaison
 *         désapprouvée RESTE en base - si elle était supprimée, le détecteur automatique la
 *         recréerait au passage suivant et la décision humaine serait perdue. Index composite
 *         avec term_id : c'est exactement la paire filtrée par la section publique « Dans
 *         l'actualité » de la fiche de glossaire (Modules\Dictionary\Models\Term::
 *         approvedNewsArticles(), Modules/Dictionary/resources/views/public/show.blade.php).
 * MCP: SELF (<5 lignes)
 * RAISON: ticket #2524 - section visible « Dans l'actualité », partie 1/4.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_article_term')) {
            return;
        }

        Schema::table('news_article_term', function (Blueprint $table) {
            if (! Schema::hasColumn('news_article_term', 'is_approved')) {
                $table->boolean('is_approved')->default(true)->after('source');
                $table->index(['term_id', 'is_approved']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('news_article_term')) {
            return;
        }

        Schema::table('news_article_term', function (Blueprint $table) {
            if (Schema::hasColumn('news_article_term', 'is_approved')) {
                $table->dropIndex(['term_id', 'is_approved']);
                $table->dropColumn('is_approved');
            }
        });
    }
};
