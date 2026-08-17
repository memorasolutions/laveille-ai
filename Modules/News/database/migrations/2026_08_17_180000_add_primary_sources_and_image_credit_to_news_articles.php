<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bonification panel 2026-08-17 (soir) - décision du propriétaire, panel de 5 IA (design doc
 * "Actus - composition manuelle assistée" 2026-08-15, section "Bonification panel 2026-08-17
 * (soir)") : les fiches doivent CITER l'original (sources primaires visibles) et porter une PHOTO
 * créditée plutôt qu'une illustration. Deux colonnes additives et réversibles :
 *
 * - 'primary_sources' (JSON, nullable) : tableau de {label, url, note?} - les sources primaires
 *   citées par la fiche, fournies par l'agent (seul écrivain : Modules\News\Console\
 *   NewsApplyCommand, mode --payload). Contrairement à 'internal_source_text' et
 *   'editorial_proof_pairs', ce champ N'EST PAS interne : il est affiché tel quel sur la fiche
 *   publique (Modules\News\resources\views\public\show.blade.php, section « Sources » en fin de
 *   fiche) - voir la migration 2026_08_16_160000 pour le garde-fou inverse (interne uniquement).
 *
 * - 'image_credit' (string, nullable) : crédit de la photo affichée (ex. « Photo : Untel,
 *   Unsplash »), affiché discrètement sous l'image principale de la fiche publique.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'primary_sources')) {
                $table->json('primary_sources')->nullable()->after('published_at');
            }
            if (! Schema::hasColumn('news_articles', 'image_credit')) {
                $table->string('image_credit')->nullable()->after('primary_sources');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'image_credit')) {
                $table->dropColumn('image_credit');
            }
            if (Schema::hasColumn('news_articles', 'primary_sources')) {
                $table->dropColumn('primary_sources');
            }
        });
    }
};
