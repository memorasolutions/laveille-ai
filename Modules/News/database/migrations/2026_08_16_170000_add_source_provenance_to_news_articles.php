<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complément de conservation (design doc "Actus - composition manuelle assistée", 2026-08-15,
 * section 5.2) : date de capture et empreinte SHA-256 du texte source collé, calculées par
 * Modules\News\Http\Controllers\Admin\NewsCompositionController::update() à chaque collage ou
 * modification RÉELLE du texte source (hash différent de celui déjà en base - jamais sur un
 * texte vide ou inchangé).
 *
 * Ces deux colonnes SURVIVENT à la suppression du texte source intégral (même règle que les
 * paires de la fiche de preuve éditoriale, migration 2026_08_16_160000) : avec les extraits
 * invoqués dans 'editorial_proof_pairs', elles constituent la preuve durable qui rend le texte
 * intégral supprimable sans perte. Colonnes internes, jamais lues par un chemin public - même
 * garde-fou que 'internal_source_text' et 'editorial_proof_pairs'.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'source_captured_at')) {
                $table->timestamp('source_captured_at')->nullable()->after('editorial_proof_pairs');
            }
            if (! Schema::hasColumn('news_articles', 'source_content_hash')) {
                $table->string('source_content_hash', 64)->nullable()->after('source_captured_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'source_content_hash')) {
                $table->dropColumn('source_content_hash');
            }
            if (Schema::hasColumn('news_articles', 'source_captured_at')) {
                $table->dropColumn('source_captured_at');
            }
        });
    }
};
