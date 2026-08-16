<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fiche de preuve editoriale (Phase B, design doc "Actus - composition manuelle assistee",
 * 2026-08-15, section 7) : chaque passage risque du resume publie peut etre appuye par une
 * paire {phrase publiee, extrait exact du texte source, decision fait/analyse}. Colonne JSON
 * interne, jamais lue par un chemin public (JSON-LD, RSS, sitemap, vue publique) - seul le
 * back-office (Modules\News\Http\Controllers\Admin\NewsCompositionController) y touche, meme
 * regle que 'internal_source_text' (migration 2026_08_16_150800).
 *
 * Ces paires SURVIVENT a la suppression du texte source integral (decision 5.2 du design doc) :
 * ce sont les extraits INVOQUES qui font foi, pas le texte complet - c'est ce qui rend le texte
 * integral supprimable sans perdre la preuve editoriale.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'editorial_proof_pairs')) {
                $table->json('editorial_proof_pairs')->nullable()->after('internal_source_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'editorial_proof_pairs')) {
                $table->dropColumn('editorial_proof_pairs');
            }
        });
    }
};
