<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Paliers d'abonnement Academy (freemium/pro/organisation — LMS 2026).
 * Une ligne = un palier configurable par l'ADMIN (nom, prix affiché, période,
 * fonctionnalités débloquées, nombre de sièges pour un palier organisation).
 *
 * IMPORTANT (zéro appel Stripe) : `price_cents` et `stripe_price_id` sont des
 * données NEUTRES et MODIFIABLES en base par l'administrateur. Aucun produit ni
 * prix Stripe réel n'est créé par le code : `stripe_price_id` reste NULL tant que
 * l'admin n'a pas collé l'identifiant d'un vrai prix Stripe créé manuellement
 * dans le Dashboard Stripe (hors de ce code). Voir Services\TierGateService.
 *
 * `features` (json) : liste de clés de fonctionnalités Academy débloquées à ce
 * palier (ex. ["academy_gamification", "academy_open_badges"]) — comparées aux
 * drapeaux `academy.*_enabled` existants, jamais un système parallèle.
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
        if (Schema::hasTable('academy_subscription_tiers')) {
            return;
        }

        Schema::create('academy_subscription_tiers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Prix NEUTRE affiché, en cents (ex. 2999 = 29,99 $) — configurable admin,
            // jamais un montant Stripe réel généré par le code.
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('billing_period', 20)->default('monthly'); // monthly|yearly
            // Clés de fonctionnalités Academy débloquées à ce palier.
            $table->json('features')->nullable();
            // Nombre de sièges max (paliers organisation) — null = illimité/non applicable.
            $table->unsignedInteger('max_seats')->nullable();
            // À REMPLIR PLUS TARD par l'admin quand un vrai produit Stripe existe.
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_subscription_tiers');
    }
};
