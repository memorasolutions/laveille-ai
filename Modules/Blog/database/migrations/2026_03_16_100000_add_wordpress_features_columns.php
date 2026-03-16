<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('status');
            $table->string('content_password')->nullable()->after('is_featured');
            $table->string('format')->default('standard')->after('content_password');
        });

        Schema::table('static_pages', function (Blueprint $table) {
            $table->string('content_password')->nullable()->after('status');
        });

        $now = now();
        DB::table('settings')->insertOrIgnore([
            ['key' => 'posts_per_page', 'group' => 'blog', 'type' => 'number', 'value' => '10', 'description' => 'Number of posts per page', 'is_public' => false, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_category_id', 'group' => 'blog', 'type' => 'number', 'value' => null, 'description' => 'Default category for new posts', 'is_public' => false, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'comments_require_approval', 'group' => 'blog', 'type' => 'boolean', 'value' => 'true', 'description' => 'Comments must be approved before display', 'is_public' => false, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'comments_nesting_depth', 'group' => 'blog', 'type' => 'number', 'value' => '3', 'description' => 'Maximum comment nesting depth', 'is_public' => false, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'excerpt_length', 'group' => 'blog', 'type' => 'number', 'value' => '55', 'description' => 'Default excerpt length in words', 'is_public' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['format', 'content_password', 'is_featured']);
        });

        Schema::table('static_pages', function (Blueprint $table) {
            $table->dropColumn('content_password');
        });

        DB::table('settings')->whereIn('key', [
            'posts_per_page', 'default_category_id', 'comments_require_approval',
            'comments_nesting_depth', 'excerpt_length',
        ])->delete();
    }
};
