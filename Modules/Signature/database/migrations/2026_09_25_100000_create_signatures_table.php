<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * .devis/outil_signature_html/PLAN-OUTIL-SIGNATURE.md, section 11.2. Colonnes calquées sur le
 * patron Modules\Decido\Models\Poll (admin_token_hash, expiry_warned_at) là où le plan le demande
 * explicitement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('admin_token_hash', 255)->unique();
            $table->string('template', 20)->default('minimal');
            $table->json('content');
            $table->unsignedInteger('schema_version')->default(1);
            $table->timestamp('last_owner_activity_at')->nullable();
            $table->timestamp('expiry_warned_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->string('status', 20)->default('active');
            // Date du DERNIER chargement d'image, au jour près, section 6.7 - jamais d'IP ni
            // d'agent utilisateur (voir Modules\Signature\Http\Controllers\SignatureAssetController).
            $table->date('last_image_loaded_on')->nullable();
            // Courriel de rappel opt-in (section 6.7) - distinct de tout compte, jamais le lien
            // secret dedans, supprimé avec le reste du contenu à la purge (voir purge ci-dessous).
            $table->string('reminder_email')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            // created_at porte un DEFAULT CURRENT_TIMESTAMP explicite (correctif B1) : c'est l'ANCRE
            // de repli de Signature::isEligibleForPurge() quand last_owner_activity_at est NULL -
            // une ligne insérée hors Eloquent (donc sans l'horodatage automatique du modèle) ne doit
            // jamais se retrouver sans ancre connue, ce qui forcerait la commande de purge à
            // l'ignorer par prudence au lieu de pouvoir évaluer son ancienneté réelle.
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index('user_id');
            $table->index(['status', 'last_owner_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
