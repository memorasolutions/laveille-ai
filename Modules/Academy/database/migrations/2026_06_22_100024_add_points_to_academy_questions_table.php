<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V1-c - POINTAGE / BARÈME. Colonne ADDITIVE « points » (pondération explicite par
 * question de banque). default 1 → comportement identique à aujourd'hui pour toute
 * question existante (rétrocompat stricte). Guard hasColumn ; down dropColumn.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_questions')) {
            return;
        }

        if (Schema::hasColumn('academy_questions', 'points')) {
            return;
        }

        Schema::table('academy_questions', function (Blueprint $table): void {
            // Pondération de la question (1..100). Défaut 1 = barème neutre (rétrocompat).
            $table->unsignedSmallInteger('points')->default(1)->after('difficulty');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academy_questions')) {
            return;
        }

        if (! Schema::hasColumn('academy_questions', 'points')) {
            return;
        }

        Schema::table('academy_questions', function (Blueprint $table): void {
            $table->dropColumn('points');
        });
    }
};
