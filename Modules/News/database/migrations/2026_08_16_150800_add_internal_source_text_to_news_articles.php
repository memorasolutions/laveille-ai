<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Écran de composition manuelle (Phase A, design doc "Actus - composition manuelle assistée",
 * 2026-08-15, section 5.2) : emplacement DISTINCT et explicite pour le texte source collé par
 * l'admin lors de la composition d'une fiche. N'A RIEN À VOIR avec l'ancienne colonne
 * news_articles.description (purgée, design doc "Actus - zéro copie du texte source",
 * 2026-08-13) : cette dernière ne doit plus jamais recevoir de texte source, et cette nouvelle
 * colonne ne doit JAMAIS être lue par un chemin public (JSON-LD, RSS, sitemap, vue publique) -
 * seul le back-office (Modules\News\Http\Controllers\Admin\NewsCompositionController) y touche.
 *
 * Conservé en base, jamais exposé côté public, supprimable à tout moment par l'admin (bouton
 * dédié → NewsCompositionController::destroySourceText()). Politique de rétention complète
 * (extraits invoqués, empreinte, date de capture, journal de suppression) : Phase C, hors
 * périmètre de cette migration.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'internal_source_text')) {
                $table->longText('internal_source_text')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'internal_source_text')) {
                $table->dropColumn('internal_source_text');
            }
        });
    }
};
