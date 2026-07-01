<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * TUTEUR IA — Grant d'accès PAR APPRENANT ET PAR COURS (fenêtre + quota).
 *
 * CRITIQUE (zéro effet rétroactif surprise) : « access_expires_at » est CALCULÉ ET
 * FIGÉ au moment du calcul du grant (à l'inscription, voir
 * TutorAccessService::calculateGrantFor), JAMAIS recalculé dynamiquement à chaque
 * lecture. Si le formateur resserre/élargit la politique du cours après coup, cela
 * ne s'applique qu'aux NOUVELLES inscriptions (pas de recalcul rétroactif silencieux).
 *
 * « questions_used_current_period » / « period_reset_at » portent le quota mensuel
 * (filet de sécurité coût, indépendant de la fenêtre) — remis à zéro automatiquement
 * quand « period_reset_at » est dépassé (voir TutorAccessService::incrementUsage).
 *
 * unique(user_id, course_id) : un seul grant par apprenant et par cours (idempotence
 * du calcul — un second appel à l'inscription ne recrée jamais le grant existant).
 *
 * Migration ADDITIVE, gardée par Schema::hasTable (portabilité, ré-exécution sûre).
 * down() = inverse exact (drop de la table).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_ai_tutor_grants')) {
            return;
        }

        Schema::create('academy_ai_tutor_grants', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');

            // Fenêtre d'accès — figée au calcul, jamais recalculée dynamiquement.
            $table->timestamp('access_starts_at');
            $table->timestamp('access_expires_at')->nullable(); // NULL = illimité dans le temps

            // Quota mensuel — filet de sécurité coût, indépendant de la fenêtre.
            $table->unsignedInteger('questions_used_current_period')->default(0);
            $table->timestamp('period_reset_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'course_id'], 'academy_ai_tutor_grants_user_course_unique');
            $table->index(['access_expires_at'], 'academy_ai_tutor_grants_expires_idx');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_ai_tutor_grants');
    }
};
