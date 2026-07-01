<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Présence aux séances en direct : une ligne par (séance, utilisateur), posée
 * la première fois que l'apprenant clique « Rejoindre ». L'unicité
 * (live_session_id, user_id) rend l'enregistrement IDEMPOTENT (un second clic
 * ne crée jamais de doublon).
 *
 * Migration ADDITIVE, gardée par Schema::hasTable. down() = inverse exact.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_live_session_attendance')) {
            return;
        }

        Schema::create('academy_live_session_attendance', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('live_session_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->timestamp('joined_at')->nullable();

            $table->timestamps();

            // Idempotence : au plus une présence par (séance, utilisateur).
            $table->unique(['live_session_id', 'user_id'], 'academy_live_attendance_unique');

            $table->foreign('live_session_id')
                ->references('id')
                ->on('academy_live_sessions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_live_session_attendance');
    }
};
