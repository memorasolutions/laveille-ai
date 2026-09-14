<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demande du fondateur (2026-09-14) : le choix « aucune ne me convient » devient un REGLAGE que
 * l'organisateur active ou non, plutot qu'un bouton impose a tous les sondages.
 *
 * DEFAUT A `true`, ET C'EST LA DECISION QUI COMPTE : tous les sondages existants gardent la
 * possibilite que leurs participants voyaient hier. Un sondage EN COURS ne doit pas perdre une
 * option en cours de vote - ce serait changer les regles pendant la partie, et rendre
 * incomprehensibles les declins deja enregistres.
 *
 * Ce reglage gouverne ce qu'on PROPOSE, jamais ce qu'on a deja RECU : un declin enregistre reste
 * visible dans les resultats meme si l'organisateur decoche ensuite (voir le commentaire de
 * PollManageController::updateAllowDecline).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            if (! Schema::hasColumn('decido_polls', 'allow_decline')) {
                $table->boolean('allow_decline')->default(true)->after('activity_notifications_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            if (Schema::hasColumn('decido_polls', 'allow_decline')) {
                $table->dropColumn('allow_decline');
            }
        });
    }
};
