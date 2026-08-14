<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voir Modules/Tools/database/migrations/2026_08_14_090000_... (même
 * décision, même justification). C'est ce compteur (views_count) qui a
 * servi de critère à l'élagage SEO daté du 2026-08-13 (désindexation de
 * fiches réellement cliquées) - traitement désactivé par défaut
 * (config('news.seo_prune.enabled') = false) mais colonne intacte.
 * Second compteur "propre" filtré/dédupliqué via
 * Modules\Core\Services\ViewCounterService, jamais de réinitialisation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->unsignedInteger('views_count_verified')->default(0)->after('views_count');
            $table->index('views_count_verified');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropIndex(['views_count_verified']);
            $table->dropColumn('views_count_verified');
        });
    }
};
