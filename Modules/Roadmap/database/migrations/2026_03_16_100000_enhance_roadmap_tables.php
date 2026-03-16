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
        Schema::create('roadmap_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('color', 7)->default('#6366f1');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('idea_comments', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('is_official');
        });

        Schema::table('ideas', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('roadmap_categories')->nullOnDelete()->after('category');
        });

        Schema::table('boards', function (Blueprint $table) {
            $table->boolean('hide_votes_before_voting')->default(false)->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('ideas', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::table('idea_comments', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });

        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn('hide_votes_before_voting');
        });

        Schema::dropIfExists('roadmap_categories');
    }
};
