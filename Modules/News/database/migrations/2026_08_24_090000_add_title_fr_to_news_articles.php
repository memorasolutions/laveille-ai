<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traduction des titres précalculée hors du chemin de l'écran de composition (demande du
 * propriétaire, 2026-08-24) : l'écran /admin/news/composition bornait ses candidats à 200 lignes
 * pour ne jamais dépasser le budget de 8 secondes de la traduction synchrone en lot
 * (Modules\Core\Services\TranslationService::translateBatch) - 452 actualités du jour restaient
 * ainsi invisibles. Deux colonnes additives et réversibles :
 *
 * - 'title_fr' (string, nullable, même longueur que 'title') : traduction française du titre,
 *   calculée par la commande planifiée Modules\News\Console\TranslateTitlesCommand
 *   (`news:translate-titles`), jamais par l'écran lui-même.
 * - 'title_fr_at' (timestamp, nullable) : date RÉELLE à laquelle la traduction a été écrite,
 *   jamais fabriquée - sert de simple marqueur de fraîcheur, aucune logique n'en dépend pour
 *   l'instant.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'title_fr')) {
                $table->string('title_fr')->nullable()->after('title');
            }
            if (! Schema::hasColumn('news_articles', 'title_fr_at')) {
                $table->timestamp('title_fr_at')->nullable()->after('title_fr');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'title_fr_at')) {
                $table->dropColumn('title_fr_at');
            }
            if (Schema::hasColumn('news_articles', 'title_fr')) {
                $table->dropColumn('title_fr');
            }
        });
    }
};
