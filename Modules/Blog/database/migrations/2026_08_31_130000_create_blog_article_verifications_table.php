<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module « vérification » étendu au blogue (2026-08-31, demande fondateur : « aussi avoir des
 * tags qui disent si on contredit une nouvelle qui circule sur internet »).
 *
 * Crée `blog_article_verifications` : une LISTE de vérifications attachées à un article de fond,
 * jamais un verdict global sur l'article entier. Décision de structure du panel (unanime,
 * jamais rouverte) : une actualité n'examine qu'UNE affirmation, mais un article de fond peut en
 * examiner PLUSIEURS parmi d'autres sujets - un verdict global écraserait des conclusions
 * hétérogènes et pourrait faire croire que tout l'article est faux alors qu'une seule de ses
 * sections traite d'une affirmation contestée.
 *
 * DRY strict : le vocabulaire des verdicts (libellé, teinte, phrase explicative, note ClaimReview)
 * reste défini À UN SEUL ENDROIT, `Modules\News\Models\NewsArticle::FACT_CHECK_VERDICTS`. Cette
 * table ne stocke QUE la clé du verdict (colonne `verdict`), jamais une copie du libellé - ajouter
 * un verdict au vocabulaire ne touche jamais cette table.
 *
 * Chaque entrée porte :
 *   article_id      - l'article de blog concerné (clé étrangère, cascade à la suppression : un
 *                      article supprimé emporte ses vérifications, jamais l'inverse).
 *   claim           - l'affirmation normalisée, telle qu'examinée, en une phrase.
 *   verdict         - une des cinq clés de FACT_CHECK_VERDICTS, ou NULL si la vérification est
 *                      « non concluante » (voir inconclusive_at plus bas - orthogonal, jamais un
 *                      sixième verdict, même statut que côté actualités).
 *   motif           - l'explication propre à CE cas précis (distincte de la phrase générique du
 *                      verdict, qui décrit la catégorie, pas le cas).
 *   sources         - tableau JSON d'URL des sources PROBANTES qui soutiennent le verdict (notre
 *                      preuve) - pluriel et distinct de source_url ci-dessous.
 *   source_url      - l'URL où l'affirmation circule (l'origine traçable, « quand elle existe » -
 *                      nullable), même sémantique que fact_check_source côté actualités.
 *   inconclusive_at - statut « vérification non concluante », mutuellement exclusif avec verdict,
 *                      même mécanisme orthogonal que `news_articles.fact_check_inconclusive_at`.
 *   verified_at     - date de vérification.
 *   position        - ordre d'affichage dans la liste, quand un article porte plusieurs entrées.
 *
 * Suppression douce (deleted_at) : zéro suppression de données, cohérent avec le modèle voisin
 * `Faq` (blog_faqs, même mécanisme).
 *
 * Réversible : down() supprime la table sans toucher à `articles`.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blog_article_verifications')) {
            Schema::create('blog_article_verifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('article_id');
                $table->text('claim');
                $table->string('verdict', 40)->nullable();
                $table->text('motif')->nullable();
                $table->json('sources')->nullable();
                $table->string('source_url', 2048)->nullable();
                $table->timestamp('inconclusive_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
                $table->index(['article_id', 'position']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_article_verifications');
    }
};
