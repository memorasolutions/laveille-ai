<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Libération progressive par leçon (Phase C / C4 - « drip »). Une leçon avec
 * drip_days = N n'est accessible qu'à partir de enrolled_at + N jours. null (ou 0)
 * = disponible immédiatement. Migration ADDITIVE et idempotente (guard hasColumn).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lessons') || Schema::hasColumn('lessons', 'drip_days')) {
            return;
        }

        Schema::table('lessons', function (Blueprint $table): void {
            $table->unsignedInteger('drip_days')->nullable()->default(null)->after('estimated_minutes');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('lessons') && Schema::hasColumn('lessons', 'drip_days')) {
            Schema::table('lessons', function (Blueprint $table): void {
                $table->dropColumn('drip_days');
            });
        }
    }
};
