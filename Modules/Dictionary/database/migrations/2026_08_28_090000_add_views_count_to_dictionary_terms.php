<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le glossaire appelle ViewCounterService::record() depuis toujours (voir
 * PublicDictionaryController::show()), mais la colonne n'a jamais existé sur
 * dictionary_terms - le service reste un no-op silencieux (Schema::hasColumn
 * gardé côté service, garde-fou zéro casse). 502 fiches, aucune ne remonte
 * quelle est lue ; cette donnée sert déjà à cibler l'enrichissement plutôt
 * que de le faire au hasard (même logique que l'annuaire, clicks_count).
 *
 * Le tri anti-robot (config('view_counter.bot_patterns')) et la déduplication
 * courte fenêtre vivent déjà dans le service depuis l'incident 2026-08-13
 * (compteur des actualités : 1,1M cumulé contre 666 vues réelles) - cette
 * migration n'ajoute donc AUCUNE logique, seulement la colonne qui manquait.
 *
 * Pas de views_count_verified ici (contrairement à Tools/Authors/News, voir
 * leurs migrations du 2026-08-14) : ce compteur "propre" n'a de sens QUE
 * pour isoler un historique déjà pollué d'un nouveau départ filtré. Sur ce
 * module, views_count est filtré/dédupliqué dès sa toute première écriture -
 * il n'y a donc aucun historique à assainir, et un jumeau repartirait de
 * zéro en parfait lock-step avec lui, pour toujours (même incrément, même
 * garde, même appel, dans la même méthode). Un doublon sans risque réel de
 * divergence est justement ce que la règle DRY du projet interdit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dictionary_terms', function (Blueprint $table) {
            $table->unsignedInteger('views_count')->default(0)->after('sort_order');
            $table->index('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('dictionary_terms', function (Blueprint $table) {
            $table->dropIndex(['views_count']);
            $table->dropColumn('views_count');
        });
    }
};
