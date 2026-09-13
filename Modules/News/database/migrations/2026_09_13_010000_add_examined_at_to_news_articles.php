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
 * ACTION : ticket #2525 (2026-09-13) - deux colonnes horodatées, NULLABLES, qui distinguent
 *          « la fiche a-t-elle été EXAMINÉE par le détecteur automatique ? » de « la fiche a-t-
 *          elle une liaison ? ». Les deux commandes de rattrapage (news:backfill-auto-terms /
 *          news:backfill-auto-tools) sélectionnaient jusqu'ici leur lot via
 *          whereDoesntHave('terms'|'tools') - une fiche sans AUCUNE mention réelle (absence
 *          normale) n'obtient jamais de liaison, reste donc éternellement dans ce périmètre et
 *          se fait retraiter à chaque exécution (mesuré en production : 400 fiches traitées par
 *          lot, mais seulement 303 puis 249 puis 189 puis 149 réellement évacuées sur cinq lots
 *          consécutifs).
 *
 *          NULL = « jamais examinée ». Colonnes ADDITIVES : aucune ligne existante n'est
 *          modifiée par cette migration (contrairement à content_updated_at, il n'existe ici
 *          aucune valeur de départ honnête à reconstituer - une fiche déjà liée par le passé n'a
 *          pas forcément été EXAMINÉE par ce mécanisme précis, seul un vrai passage du détecteur
 *          peut le prouver). Indexées : Modules\News\Console\Concerns\
 *          SelectsArticlesPendingAutoDetection filtre et trie CHAQUE lot sur ces colonnes.
 * MCP: SELF (<5 lignes)
 * RAISON: ticket #2525 - le critère de sélection doit être « pas encore examinée », jamais
 *         « sans liaison ».
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_articles')) {
            return;
        }

        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'terms_examined_at')) {
                $table->timestamp('terms_examined_at')->nullable()->after('content_updated_at')->index();
            }
            if (! Schema::hasColumn('news_articles', 'tools_examined_at')) {
                $table->timestamp('tools_examined_at')->nullable()->after('terms_examined_at')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('news_articles')) {
            return;
        }

        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'tools_examined_at')) {
                $table->dropColumn('tools_examined_at');
            }
            if (Schema::hasColumn('news_articles', 'terms_examined_at')) {
                $table->dropColumn('terms_examined_at');
            }
        });
    }
};
