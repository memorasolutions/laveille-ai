<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des demandes de cours personnalisé (vitrine /cours).
 *
 * Un visiteur public demande une formation sur mesure ; la demande est persistée ici
 * et le fondateur est notifié par courriel. Les soumissions marquées « spam » restent
 * en base (quarantaine consultable) plutôt que rejetées en silence - même doctrine que
 * contact_messages. Migration réversible : down() supprime la table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_requests', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('courriel', 190);
            $table->string('organisation', 190)->nullable();
            $table->string('sujet', 190);
            $table->unsignedSmallInteger('heures')->nullable();
            $table->string('niveau', 20)->nullable();
            $table->unsignedSmallInteger('participants')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->string('spam_reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_requests');
    }
};
