<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('static_pages')) {
            Schema::table('static_pages', function (Blueprint $table) {
                $table->dateTime('published_at')->nullable()->after('status')->index();
                $table->dateTime('expired_at')->nullable()->after('published_at')->index();
            });
        }

        if (Schema::hasTable('faqs')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->dateTime('published_at')->nullable()->after('is_published')->index();
                $table->dateTime('expired_at')->nullable()->after('published_at')->index();
            });
        }

        // articles est créé par le module Blog (désactivable) — garde pour SQLite/tests
        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dateTime('expired_at')->nullable()->after('published_at')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('static_pages')) {
            Schema::table('static_pages', function (Blueprint $table) {
                $table->dropColumn(['published_at', 'expired_at']);
            });
        }

        if (Schema::hasTable('faqs')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->dropColumn(['published_at', 'expired_at']);
            });
        }

        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn(['expired_at']);
            });
        }
    }
};
