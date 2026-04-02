<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('news_articles', 'resolved_url')) {
            Schema::table('news_articles', function (Blueprint $table) {
                $table->string('resolved_url', 500)->nullable()->after('url');
            });
        }
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropColumn('resolved_url');
        });
    }
};
