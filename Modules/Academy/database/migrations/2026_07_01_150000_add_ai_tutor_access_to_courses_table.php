<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * TUTEUR IA — Fenêtre d'accès + quota (recommandation de veille juillet 2026).
 * Colonnes ADDITIVES sur « courses », choisies par le formateur À LA CRÉATION du
 * cours et MODIFIABLES en tout temps (voir HandlesCourseSettings::saveAiTutorAccess) :
 *
 *   - ai_tutor_window_type          : none|relative_to_enrollment|relative_to_course_launch|fixed_date
 *                                     (défaut « none » = accès illimité, comportement actuel inchangé) ;
 *   - ai_tutor_window_days          : nombre de jours de fenêtre (utilisé si relative_*) ;
 *   - ai_tutor_fixed_expiry_at      : date d'expiration fixe (utilisée si fixed_date) ;
 *   - ai_tutor_monthly_quota        : quota mensuel de questions (filet de sécurité coût,
 *                                     indépendant de la fenêtre — NULL = illimité) ;
 *   - ai_tutor_reminder_days_before : jours avant expiration pour le 1er rappel (défaut 7,
 *                                     un 2e rappel fixe est TOUJOURS envoyé à J-1).
 *
 * IMPORTANT (zéro effet rétroactif surprise) : cette config du COURS ne s'applique
 * QU'AUX NOUVEAUX GRANTS calculés (voir TutorAccessService::calculateGrantFor). La
 * modifier n'affecte JAMAIS un grant déjà figé pour un apprenant déjà inscrit.
 *
 * Guards hasTable/hasColumn (idempotent). down() = inverse exact (dropColumn).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table): void {
            if (! Schema::hasColumn('courses', 'ai_tutor_window_type')) {
                $table->string('ai_tutor_window_type', 32)->default('none')->after('certificate_accent_color');
            }
            if (! Schema::hasColumn('courses', 'ai_tutor_window_days')) {
                $table->unsignedInteger('ai_tutor_window_days')->nullable()->after('ai_tutor_window_type');
            }
            if (! Schema::hasColumn('courses', 'ai_tutor_fixed_expiry_at')) {
                $table->timestamp('ai_tutor_fixed_expiry_at')->nullable()->after('ai_tutor_window_days');
            }
            if (! Schema::hasColumn('courses', 'ai_tutor_monthly_quota')) {
                $table->unsignedInteger('ai_tutor_monthly_quota')->nullable()->after('ai_tutor_fixed_expiry_at');
            }
            if (! Schema::hasColumn('courses', 'ai_tutor_reminder_days_before')) {
                $table->unsignedInteger('ai_tutor_reminder_days_before')->nullable()->default(7)->after('ai_tutor_monthly_quota');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table): void {
            foreach ([
                'ai_tutor_reminder_days_before',
                'ai_tutor_monthly_quota',
                'ai_tutor_fixed_expiry_at',
                'ai_tutor_window_days',
                'ai_tutor_window_type',
            ] as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
