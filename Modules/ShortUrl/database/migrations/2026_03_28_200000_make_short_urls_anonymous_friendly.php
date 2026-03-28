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
        Schema::table('short_urls', function (Blueprint $table): void {
            // Clé étrangère user_id : suppression, modification nullable, recréation avec nullOnDelete()
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Métadonnées Open Graph pour preview social personnalisé
            $table->string('og_title', 255)->nullable()->after('tags');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image', 500)->nullable()->after('og_description');

            // Marqueur anonyme
            $table->boolean('is_anonymous')->default(false)->after('is_active');
            $table->index('is_anonymous');
        });
    }

    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table): void {
            $table->dropIndex(['is_anonymous']);
            $table->dropColumn(['og_image', 'og_description', 'og_title', 'is_anonymous']);

            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
