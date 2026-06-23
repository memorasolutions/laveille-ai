<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V5-c - Préférences de notification courriel par utilisateur et par type.
 * Migration ADDITIVE : une ligne = un opt-in/opt-out explicite pour un type.
 * En l'absence de ligne, le défaut de config (academy.notifications.defaults)
 * s'applique - aucune donnée existante n'est touchée (rétrocompat absolue).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_notification_preferences')) {
            return;
        }

        Schema::create('academy_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            // Une seule préférence par (utilisateur, type) : upsert idempotent.
            $table->unique(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_notification_preferences');
    }
};
