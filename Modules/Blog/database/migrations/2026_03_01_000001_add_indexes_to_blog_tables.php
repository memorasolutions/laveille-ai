<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
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
        Schema::table('blog_comments', function (Blueprint $table) {
            $table->index('status');
            $table->index(['article_id', 'status']);
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('blog_comments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['article_id', 'status']);
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
    }
};
