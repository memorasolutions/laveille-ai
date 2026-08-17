<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Récupération automatique Markdown + Publier-et-purger (design doc "Actus - composition
 * manuelle assistée" 2026-08-15, révision 2026-08-17). Deux colonnes additives et réversibles :
 *
 * - 'source_acquisition' (JSON, nullable) : trace de la récupération automatique du texte source
 *   par Modules\News\Services\SourceMarkdownFetcher (méthode http/puppeteer, URL finale, statut
 *   HTTP, nombre de mots, date de capture, empreinte SHA-256 du Markdown BRUT figée au moment de
 *   la récupération - elle prouve toute retouche ultérieure du texte source puisque
 *   'source_content_hash', lui, se recalcule à chaque modification). Même garde-fou que
 *   'internal_source_text'/'editorial_proof_pairs'/'source_content_hash' : jamais lue par un
 *   chemin public, back-office uniquement.
 *
 * - 'published_at' (timestamp, nullable) : AUCUNE colonne équivalente n'existait avant cette
 *   migration - vérifié dans le modèle (Modules\News\Models\NewsArticle) et dans toutes les
 *   migrations du module avant de l'ajouter, plutôt que d'inventer un nom en devinant.
 *   Volontairement DISTINCTE de 'pub_date' (date de publication ORIGINALE chez l'éditeur source,
 *   utilisée par l'index d'accueil et le tri chronologique - jamais écrasée) : 'published_at'
 *   porte le moment où LA VEILLE elle-même a publié la fiche via le bouton Publier-et-purger de
 *   l'écran de composition (Modules\News\Http\Controllers\Admin\NewsCompositionController::
 *   publish()), seul point d'écriture.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'source_acquisition')) {
                $table->json('source_acquisition')->nullable()->after('source_content_hash');
            }
            if (! Schema::hasColumn('news_articles', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('source_acquisition');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'published_at')) {
                $table->dropColumn('published_at');
            }
            if (Schema::hasColumn('news_articles', 'source_acquisition')) {
                $table->dropColumn('source_acquisition');
            }
        });
    }
};
