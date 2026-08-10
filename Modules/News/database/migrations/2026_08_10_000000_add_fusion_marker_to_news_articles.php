<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actus 2.0 : marqueur additif « fiche comparative ». Aucune suppression, aucune donnée
 * modifiée pour les articles existants (défaut false). Tout le reste du modèle de données
 * (is_potential_duplicate_of, dedup_score, dedup_reason, seo_status, news_dedup_log) réutilise
 * des colonnes/table déjà en place depuis 2026-04-28 et 2026-06-07 (voir design doc section 4).
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'is_comparative_digest')) {
                $table->boolean('is_comparative_digest')->default(false)->after('feed_type');
                $table->index('is_comparative_digest');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'is_comparative_digest')) {
                $table->dropIndex(['is_comparative_digest']);
                $table->dropColumn('is_comparative_digest');
            }
        });
    }
};
