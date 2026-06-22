<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V1-d — LIMITE DE TEMPS : colonne additive `timed_out` sur l'historique des
 * tentatives. Posée à true par le serveur lorsque la soumission arrive APRÈS la
 * limite de temps (now - started_at > time_limit_minutes + tolérance). Le serveur
 * ne fait JAMAIS confiance au client pour le temps : il re-calcule à partir de
 * started_at (posé serveur en session) et de la limite du payload.
 *
 * ADDITIVE + GUARDÉE : défaut false → toutes les lignes existantes restent
 * « dans les temps » (rétrocompat stricte). Aucun item sans limite n'est affecté.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_quiz_attempts')) {
            return;
        }

        if (Schema::hasColumn('academy_quiz_attempts', 'timed_out')) {
            return;
        }

        Schema::table('academy_quiz_attempts', function (Blueprint $table): void {
            $table->boolean('timed_out')->default(false)->after('passed');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academy_quiz_attempts')) {
            return;
        }

        if (! Schema::hasColumn('academy_quiz_attempts', 'timed_out')) {
            return;
        }

        Schema::table('academy_quiz_attempts', function (Blueprint $table): void {
            $table->dropColumn('timed_out');
        });
    }
};
