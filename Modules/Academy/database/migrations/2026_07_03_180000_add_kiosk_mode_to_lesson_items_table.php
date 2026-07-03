<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Mode kiosque (verrouillage anti-triche des évaluations surveillées — parité
 * Moodle « Safe Exam Browser »). Ajoute la colonne `kiosk_mode` (booléen,
 * défaut false) sur `lesson_items` : quand true SUR un item de type quiz ET
 * que le drapeau global `academy.kiosk_mode_enabled` est actif, le lecteur de
 * quiz applique le mode plein écran + détection d'incidents (voir
 * Services\KioskViolationService, quiz-player.blade.php).
 * Migration ADDITIVE (guard hasColumn), down() = drop strict de la colonne.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('lesson_items', 'kiosk_mode')) {
            return;
        }

        Schema::table('lesson_items', function (Blueprint $table): void {
            $table->boolean('kiosk_mode')->default(false)->after('is_required');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('lesson_items', 'kiosk_mode')) {
            return;
        }

        Schema::table('lesson_items', function (Blueprint $table): void {
            $table->dropColumn('kiosk_mode');
        });
    }
};
