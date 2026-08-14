<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voir Modules/Tools/database/migrations/2026_08_14_090000_... (même
 * décision, même justification) - second compteur "propre" à côté de
 * views_count, filtré/dédupliqué via Modules\Core\Services\ViewCounterService,
 * jamais de réinitialisation de l'historique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('author_posts', function (Blueprint $table) {
            $table->unsignedInteger('views_count_verified')->default(0)->after('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('author_posts', function (Blueprint $table) {
            $table->dropColumn('views_count_verified');
        });
    }
};
