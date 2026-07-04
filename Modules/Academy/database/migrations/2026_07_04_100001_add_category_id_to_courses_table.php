<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Rattache un cours à 0 ou 1 catégorie (academy_course_categories.id). Nullable :
 * un cours sans catégorie reste valide (rétrocompatibilité totale, aucun cours
 * existant n'est affecté). Suppression d'une catégorie -> nullOnDelete (les
 * cours redeviennent « sans catégorie », jamais supprimés en cascade).
 *
 * Migration ADDITIVE, gardée par Schema::hasColumn. down() = inverse exact.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('courses', 'category_id')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table): void {
            $table->unsignedBigInteger('category_id')->nullable()->after('level');
            $table->foreign('category_id')
                ->references('id')
                ->on('academy_course_categories')
                ->nullOnDelete();
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('courses', 'category_id')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
