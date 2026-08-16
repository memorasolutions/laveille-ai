<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LOT 1 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 3) : « aucune date ne me
 * convient ». Table dédiée plutôt qu'un "no" sur chaque option, pour deux raisons :
 *
 * 1) Les modes single_choice/approval n'ont AUCUNE valeur "no" (seulement "selected") -
 *    "no sur chaque option" n'est même pas représentable pour eux sans changer leur modèle de
 *    vote. Une table séparée fonctionne identiquement pour les 3 modes de vote.
 * 2) Un refus explicite reste distinct d'une réponse "non" créneau par créneau (qui, elle,
 *    continue d'alimenter les statistiques oui/peut-être/non par créneau sans être polluée par
 *    un état global) - plus honnête que de forcer un "no" partout pour simuler un refus global.
 *
 * Mutuellement exclusif avec decido_poll_votes pour un même (poll_id, voter_token) :
 * PublicPollController::vote() supprime le refus existant si le votant revote normalement, et
 * PublicPollController::decline() supprime les votes existants si le votant déclare finalement
 * qu'aucune date ne lui convient - jamais les deux à la fois pour le même votant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decido_poll_declines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('decido_polls')->cascadeOnDelete();
            $table->uuid('voter_token');
            $table->string('voter_pseudonym', 100);
            $table->timestamps();

            $table->unique(['poll_id', 'voter_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decido_poll_declines');
    }
};
