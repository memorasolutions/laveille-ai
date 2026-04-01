<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('news_articles', 'short_url_id')) return;
        Schema::table('news_articles', function (Blueprint $table) {
            $table->unsignedBigInteger('short_url_id')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropColumn('short_url_id');
        });
    }
};
