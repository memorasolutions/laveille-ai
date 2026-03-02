<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('static_pages', function (Blueprint $table) {
            $table->dateTime('published_at')->nullable()->after('status')->index();
            $table->dateTime('expired_at')->nullable()->after('published_at')->index();
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dateTime('published_at')->nullable()->after('is_published')->index();
            $table->dateTime('expired_at')->nullable()->after('published_at')->index();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dateTime('expired_at')->nullable()->after('published_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('static_pages', function (Blueprint $table) {
            $table->dropColumn(['published_at', 'expired_at']);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn(['published_at', 'expired_at']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['expired_at']);
        });
    }
};
