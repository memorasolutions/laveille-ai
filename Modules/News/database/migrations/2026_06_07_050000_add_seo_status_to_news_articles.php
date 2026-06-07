<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'seo_status')) {
                $table->string('seo_status', 10)
                    ->default('index')
                    ->after('views_count');
                $table->index('seo_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'seo_status')) {
                $table->dropIndex(['seo_status']);
                $table->dropColumn('seo_status');
            }
        });
    }
};
