<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->string('seo_title', 512)->nullable()->change();
            $table->string('meta_description', 512)->nullable()->change();
            $table->string('image_url', 2048)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->string('seo_title', 255)->nullable()->change();
            $table->string('meta_description', 255)->nullable()->change();
            $table->string('image_url', 255)->nullable()->change();
        });
    }
};
