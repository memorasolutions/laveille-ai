<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module « vérification » (2026-08-21, demande fondateur : « quand on démonte une information
 * fausse, il devrait y avoir une indication dans ce sens »).
 *
 * Trois colonnes ADDITIVES, toutes nullables : une fiche d'actualité ordinaire n'est pas
 * concernée et reste strictement inchangée. Le module ne s'active que sur les fiches qui
 * vérifient réellement une affirmation circulant ailleurs.
 *
 *   fact_check_verdict  - étiquette normalisée du verdict (voir NewsArticle::FACT_CHECK_VERDICTS,
 *                         source unique du vocabulaire : le libellé public, la couleur du badge
 *                         et la note ClaimReview en découlent, jamais écrits en dur ailleurs).
 *   fact_check_claim    - l'affirmation examinée, telle qu'elle circule, en une phrase. C'est le
 *                         claimReviewed du balisage Schema.org ClaimReview.
 *   fact_check_source   - d'où provient l'affirmation (URL du message ou de la page d'origine).
 *                         Schema.org exige que la déclaration soit attribuée à une source TIERCE,
 *                         distincte du site qui la vérifie.
 *
 * Réversible : down() retire les trois colonnes sans toucher au reste de la table.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'fact_check_verdict')) {
                $table->string('fact_check_verdict', 40)->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('news_articles', 'fact_check_claim')) {
                $table->text('fact_check_claim')->nullable()->after('fact_check_verdict');
            }
            if (! Schema::hasColumn('news_articles', 'fact_check_source')) {
                $table->string('fact_check_source', 2048)->nullable()->after('fact_check_claim');
            }
        });

        // Index : la page d'index des vérifications filtre uniquement sur la présence d'un verdict.
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'fact_check_verdict')) {
                $table->index('fact_check_verdict', 'news_articles_fact_check_verdict_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'fact_check_verdict')) {
                $table->dropIndex('news_articles_fact_check_verdict_index');
            }
            foreach (['fact_check_source', 'fact_check_claim', 'fact_check_verdict'] as $column) {
                if (Schema::hasColumn('news_articles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
