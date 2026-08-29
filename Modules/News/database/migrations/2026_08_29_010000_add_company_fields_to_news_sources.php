<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rattache une source d'actualités à sa compagnie d'IA et la marque OFFICIELLE (2026-08-29,
 * demande du fondateur : filtrer les actualités par compagnie dans l'écran de composition, et
 * faire entrer les flux officiels de ces compagnies dans le pipeline de collecte).
 *
 * `news_sources` n'avait jusqu'ici aucun moyen de porter cette information (colonnes existantes :
 * id, name, url, category, language, active, last_fetched_at) - c'était le seul vrai blocage,
 * pas l'écran lui-même (voir Modules\News\Http\Controllers\Admin\NewsCompositionController et
 * public/assets/admin/news-article-picker.js, qui savent déjà filtrer côté client dès que le
 * champ existe dans la charge utile JSON).
 *
 * Deux colonnes simples plutôt qu'une table de compagnies séparée : `category` (sur cette même
 * table) et `category_tag` (sur news_articles, voir add_structured_fields_to_news_articles)
 * suivent déjà ce patron de taxonomie légère dans ce projet - chaîne libre, nullable, indexée,
 * sans logique qui présuppose un jeu de valeurs fermé. Rien ici n'exige l'intégrité référentielle
 * ni les attributs propres (logo, description, page de présentation) qu'appellerait une vraie
 * table de compagnies ; la liste peut s'enrichir plus tard par simple ajout de lignes, sans
 * migration de structure supplémentaire.
 *
 * - 'is_official' (booléen, défaut false) : vrai seulement pour un flux publié PAR la compagnie
 *   elle-même (blog officiel, page de recherche) - jamais pour un média tiers qui EN PARLE.
 * - 'company' (chaîne, nullable, indexée) : nom normalisé de la compagnie ('OpenAI', 'Google
 *   DeepMind'...) - vide pour toute source qui ne représente aucune compagnie unique (média
 *   généraliste, agrégateur).
 *
 * Additif uniquement : aucune colonne existante modifiée, aucune ligne touchée. down() retire
 * exactement ce qu'up() a ajouté - zéro perte pour les données déjà présentes. Gardée idempotente
 * (Schema::hasColumn) comme add_title_fr_to_news_articles : rejouable sans casse si elle a déjà
 * tourné en partie.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_sources', function (Blueprint $table) {
            if (! Schema::hasColumn('news_sources', 'is_official')) {
                $table->boolean('is_official')->default(false)->after('active');
            }

            // L'index est posé DANS LE MÊME bloc que la création de la colonne : ainsi, un
            // deuxième passage (colonne déjà présente) ne retente jamais de recréer l'index et
            // n'a besoin d'aucune vérification d'existence d'index séparée (Schema ne l'expose
            // pas simplement dans ce projet).
            if (! Schema::hasColumn('news_sources', 'company')) {
                $table->string('company')->nullable()->after('is_official');
                $table->index('company');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_sources', function (Blueprint $table) {
            if (Schema::hasColumn('news_sources', 'company')) {
                $table->dropIndex(['company']);
                $table->dropColumn('company');
            }
            if (Schema::hasColumn('news_sources', 'is_official')) {
                $table->dropColumn('is_official');
            }
        });
    }
};
