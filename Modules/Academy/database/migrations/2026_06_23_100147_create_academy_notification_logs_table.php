<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V5-c - Journal anti-doublon des notifications courriel (idempotence).
 * Sert surtout aux rappels d'échéance planifiés : la commande quotidienne ne
 * re-rappelle jamais la même échéance au même utilisateur (clé de dédoublonnage
 * unique). Migration ADDITIVE - aucune donnée existante touchée.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_notification_logs')) {
            return;
        }

        Schema::create('academy_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            // Clé métier d'unicité (ex. « due:asgn-12:20260625 ») : empêche le
            // double envoi de la MÊME notification au MÊME utilisateur.
            $table->string('dedup_key', 191);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type', 'dedup_key'], 'academy_notif_log_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_notification_logs');
    }
};
