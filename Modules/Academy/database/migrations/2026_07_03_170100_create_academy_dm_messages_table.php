<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Messagerie directe (DM) — messages d'une conversation. `read_at` porte
 * l'accusé de lecture (NULL = non lu par le destinataire). Aucune suppression
 * physique par l'utilisateur : softDeletes() uniquement (pattern déjà utilisé
 * ailleurs dans le module, ex. Course), une conversation purgée par un admin
 * reste régie par la cascade FK sur academy_dm_conversations.
 *
 * Migration ADDITIVE (guard hasTable), down() = drop strict.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_dm_messages')) {
            return;
        }

        Schema::create('academy_dm_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sender_id');
            // Destinataire dénormalisé (conversation = 2 participants) : évite un
            // calcul « l'autre participant » à chaque lecture et simplifie les
            // requêtes « messages non lus adressés à moi ».
            $table->unsignedBigInteger('recipient_id');
            // Texte brut UNIQUEMENT (jamais de HTML — échappé à l'affichage, voir
            // DirectMessageThread). Longueur plafonnée en validation applicative.
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('academy_dm_conversations')
                ->cascadeOnDelete();

            $table->foreign('sender_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('recipient_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // Fil d'une conversation par ordre chronologique.
            $table->index(['conversation_id', 'created_at']);
            // Compteur « non lus » d'un destinataire (badge cloche).
            $table->index(['recipient_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_dm_messages');
    }
};
