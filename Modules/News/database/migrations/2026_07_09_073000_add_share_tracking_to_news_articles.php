<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'linkedin_shared_at')) {
                // Pas de ->after('seo_status') : cette colonne (migration distincte) n'est pas
                // garantie déjà appliquée dans tous les environnements au moment où cette
                // migration tourne — on ajoute simplement en fin de table, sans dépendance d'ordre.
                $table->timestamp('linkedin_shared_at')->nullable();
            }
            if (! Schema::hasColumn('news_articles', 'facebook_shared_at')) {
                $table->timestamp('facebook_shared_at')->nullable()->after('linkedin_shared_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'linkedin_shared_at')) {
                $table->dropColumn('linkedin_shared_at');
            }
            if (Schema::hasColumn('news_articles', 'facebook_shared_at')) {
                $table->dropColumn('facebook_shared_at');
            }
        });
    }
};
