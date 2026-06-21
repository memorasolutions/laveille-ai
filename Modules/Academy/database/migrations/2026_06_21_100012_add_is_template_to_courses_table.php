<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Drapeau « modèle réutilisable » sur les cours (Phase C / C3 - duplication +
 * modèles). Un cours marqué is_template = true est listé dans la section
 * « Modèles » du tableau de bord et peut être dupliqué via « Utiliser ce modèle ».
 * Migration ADDITIVE et idempotente (guard Schema::hasColumn).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses') || Schema::hasColumn('courses', 'is_template')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table): void {
            $table->boolean('is_template')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'is_template')) {
            Schema::table('courses', function (Blueprint $table): void {
                $table->dropColumn('is_template');
            });
        }
    }
};
