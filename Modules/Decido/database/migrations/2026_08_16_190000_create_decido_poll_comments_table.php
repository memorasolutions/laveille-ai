<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LOT 2 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 5) : commentaire libre facultatif,
 * UN par participant et par sondage (jamais un par créneau - ce serait 14 champs à remplir et du
 * bruit). Table dédiée plutôt qu'une colonne sur decido_poll_votes : un votant en mode
 * yes_no_maybe a une ligne PollVote PAR OPTION (voir decido_poll_votes), un votant qui a décliné
 * (decido_poll_declines) n'a AUCUNE ligne PollVote - une colonne sur PollVote n'aurait donc pas pu
 * représenter le commentaire d'un votant décliné, et l'aurait dupliqué N fois pour un votant
 * yes_no_maybe à N créneaux. Même contrainte unique (poll_id, voter_token) que
 * decido_poll_declines : un seul commentaire par (sondage, votant), mis à jour par
 * updateOrCreate() à chaque soumission (vote OU déclin, voir PublicPollController).
 *
 * 'comment' en varchar(280) (pas text) : la borne de longueur applicative (max:280, voir la
 * validation du contrôleur) est ainsi imposée aussi au niveau schéma - défense en profondeur si
 * jamais une ligne était un jour insérée hors du contrôleur (script, tinker).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decido_poll_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('decido_polls')->cascadeOnDelete();
            $table->uuid('voter_token');
            $table->string('voter_pseudonym', 100);
            $table->string('comment', 280);
            $table->timestamps();

            $table->unique(['poll_id', 'voter_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decido_poll_comments');
    }
};
