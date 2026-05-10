<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S90 #43 — Alertes catégorie utilisateur (best practice 2026 retention).
 *
 * Permet à un utilisateur authentifié de suivre les nouveautés d'une ou
 * plusieurs catégories du répertoire. Cron weekly digest enverra un email
 * récapitulatif des outils ajoutés/modifiés (phase 2 quand audience >= 50).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_category_subscriptions')) {
            return;
        }

        Schema::create('user_category_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('directory_category_id')->constrained('directory_categories')->cascadeOnDelete();
            $table->string('frequency', 16)->default('weekly'); // weekly|never|daily (futur)
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'directory_category_id'], 'ucs_user_cat_unique');
            $table->index(['frequency', 'last_notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_category_subscriptions');
    }
};
