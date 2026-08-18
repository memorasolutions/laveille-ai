<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: colonne retired_at (retrait SEO-sûr et RÉVERSIBLE d'une fiche d'actualité). Une fiche
 *         « retirée » répond en HTTP 410 Gone (retrait volontaire, définitif au sens de Google)
 *         et sort de l'index/sitemap/listes/widgets/RSS/recherche. Remettre à null = restaurer.
 * MCP: SELF (<5 lignes)
 * RAISON: chantier AdSense « faible valeur » (2026-08-18) - le noindex ne sort PAS une page du
 *         périmètre AdSense ; seul un 410 le fait (recherche Perplexity + panel). Réversible :
 *         jamais de suppression de la donnée, seul le statut de service change.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->timestamp('retired_at')->nullable()->after('seo_status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropColumn('retired_at');
        });
    }
};
