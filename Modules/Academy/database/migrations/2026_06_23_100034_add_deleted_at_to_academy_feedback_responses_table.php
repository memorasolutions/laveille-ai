<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FEEDBACK - SoftDeletes (audit trail, C3) sur les réponses. Colonne ADDITIVE et
 * idempotente (guard hasColumn) : une réponse supprimée est conservée (deleted_at)
 * plutôt que perdue, et reste exclue des agrégats par le scope par défaut Eloquent.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_feedback_responses')) {
            return;
        }
        if (Schema::hasColumn('academy_feedback_responses', 'deleted_at')) {
            return;
        }

        Schema::table('academy_feedback_responses', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academy_feedback_responses')) {
            return;
        }
        if (! Schema::hasColumn('academy_feedback_responses', 'deleted_at')) {
            return;
        }

        Schema::table('academy_feedback_responses', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
