<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module « signature éditoriale » (signal humain E-E-A-T vérifiable, design doc
 * SPEC-SIGNAL-HUMAIN, décision club des sages 5 oracles notée 93/100, 2026-08-20). Deux colonnes
 * additives et réversibles, non destructives :
 *
 * - 'reviewed_at' (timestamp, nullable) : date RÉELLE de la relecture éditoriale, jamais dérivée
 *   ni fabriquée - posée uniquement par Modules\News\Console\NewsApplyCommand (la seule porte
 *   d'écriture bornée) quand la charge utile applique une vraie relecture substantielle.
 * - 'reviewed_by' (string, nullable) : libellé de qui a relu. Vide par défaut : la fiche publique
 *   retombe alors sur le libellé applicatif « La rédaction de laveille.ai » (voir
 *   NewsArticle::reviewerLabel()), jamais une valeur écrite en dur ici.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('retired_at');
            }
            if (! Schema::hasColumn('news_articles', 'reviewed_by')) {
                $table->string('reviewed_by')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'reviewed_by')) {
                $table->dropColumn('reviewed_by');
            }
            if (Schema::hasColumn('news_articles', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
        });
    }
};
