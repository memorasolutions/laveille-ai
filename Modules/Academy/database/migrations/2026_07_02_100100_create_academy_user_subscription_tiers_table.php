<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Assignation d'un palier d'abonnement (academy_subscription_tiers) à un
 * utilisateur, historisée (jamais écrasée : une nouvelle ligne par changement
 * de palier, l'ancienne repasse `is_active=false`). Voir Services\TierGateService
 * qui résout le palier COURANT d'un utilisateur (assignation active la plus
 * récente ; à défaut, le palier marqué `is_default`).
 *
 * Aucune écriture Stripe : cette table est purement locale (assignation MANUELLE
 * par l'admin ou future automatisation via webhook Cashier — non câblée ici).
 *
 * Migration ADDITIVE guardée (hasTable), down() = drop strict.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_user_subscription_tiers')) {
            return;
        }

        Schema::create('academy_user_subscription_tiers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subscription_tier_id');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('subscription_tier_id')
                ->references('id')
                ->on('academy_subscription_tiers')
                ->cascadeOnDelete();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_user_subscription_tiers');
    }
};
