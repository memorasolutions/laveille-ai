<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trouve pendant la simulation E2E complete du site (2026-08-03, run SIM_laveille0803) :
 * la fiche outil Decido affirmait « sans compte requis » alors que la creation d'un
 * sondage exige une session authentifiee (Modules/Decido/routes/web.php, middleware
 * 'auth' sur /decido/creer* et /decido) - seul le VOTE est reellement anonyme. Un
 * visiteur anonyme qui clique « Utiliser » est redirige vers /login sans explication,
 * contredisant la promesse affichee sur /outils. Corrige la copie pour refleter le
 * comportement reel (auth requise pour creer, aucune pour voter) plutot que de changer
 * un comportement d'authentification deja stabilise sur 27+ rounds adversariaux.
 */
return new class extends Migration
{
    private const OLD = 'Organisez un sondage collectif gratuit pour choisir une date ou une option en groupe, sans compte requis. Export CSV/ICS, lien court et code QR inclus.';

    private const NEW = 'Organisez un sondage collectif gratuit pour choisir une date ou une option en groupe. Voter ne demande aucun compte ; créer un sondage nécessite un compte gratuit (gestion et rappels inclus). Export CSV/ICS, lien court et code QR inclus.';

    public function up(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        DB::table('tools')
            ->where('slug', 'decido')
            ->where('description', self::OLD)
            ->update(['description' => self::NEW, 'updated_at' => now()]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        DB::table('tools')
            ->where('slug', 'decido')
            ->where('description', self::NEW)
            ->update(['description' => self::OLD, 'updated_at' => now()]);
    }
};
