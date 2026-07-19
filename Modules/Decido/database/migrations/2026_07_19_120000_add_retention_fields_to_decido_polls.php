<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Politique de rétention complète (2026-07-19, recherche pp_search + validation Codex/Gemini,
 * approuvée par l'utilisateur) : jusqu'ici expires_at n'était écrit qu'à la clôture manuelle
 * (PollManageController::close()) - un sondage ouvert ou en brouillon n'avait AUCUNE date
 * d'expiration, contournant silencieusement decido:purge-expired (round 5, skill /100) tant
 * qu'il n'était jamais clôturé. Cette migration :
 *
 * 1) Ajoute expiry_warned_at (idempotence de l'avertissement courriel unique à J-14, cf.
 *    Modules\Decido\Console\WarnExpiringPollsCommand) et extension_count (plafond dur de 2
 *    prolongations, cf. PollManageController::extend()).
 * 2) Rétro-remplit expires_at pour tout sondage EXISTANT ouvert/brouillon qui n'en a pas encore
 *    (le calcul exact est encapsulé dans Modules\Decido\Services\PollExpirationService, mais
 *    dupliqué ici en SQL pur - pas de dépendance sur les modèles Eloquent/config applicative
 *    dans une migration, pattern déjà utilisé par Modules\Privacy\Console\PurgeExpiredDataCommand
 *    et cohérent avec le reste du projet).
 *
 * IMPORTANT (choix documenté) : les sondages DÉJÀ clôturés avec un expires_at déjà fixé (ancienne
 * règle : 6 mois post-clôture) ne sont PAS retouchés ici - on ne raccourcit jamais une échéance
 * déjà promise à un utilisateur. Seules les FUTURES clôtures utilisent la nouvelle règle de 30
 * jours (PollManageController::close()). Ce filtre est appliqué par whereIn('status', ['open',
 * 'draft']) ci-dessous : un sondage 'closed' n'est jamais touché par ce backfill, qu'il ait ou
 * non un expires_at déjà défini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            if (! Schema::hasColumn('decido_polls', 'expiry_warned_at')) {
                $table->timestamp('expiry_warned_at')->nullable()->after('expires_at');
            }
            if (! Schema::hasColumn('decido_polls', 'extension_count')) {
                $table->unsignedTinyInteger('extension_count')->default(0)->after('expiry_warned_at');
            }
        });

        $dateMonths = (int) config('decido.expiration_months_date_type', 2);
        $fallbackMonths = (int) config('decido.expiration_months_classic_or_draft', 3);

        // 1) Sondages de type "date", ouverts/brouillons, sans expires_at : dernière date
        //    candidate (MAX(ends_at), à défaut MAX(starts_at) des créneaux générés) + N mois.
        $datePolls = DB::table('decido_polls')
            ->whereNull('expires_at')
            ->whereIn('status', ['open', 'draft'])
            ->where('type', 'date')
            ->get(['id']);

        foreach ($datePolls as $row) {
            $lastEndsAt = DB::table('decido_poll_options')->where('poll_id', $row->id)->max('ends_at');
            $lastSlot = $lastEndsAt ?: DB::table('decido_poll_options')->where('poll_id', $row->id)->max('starts_at');

            if ($lastSlot) {
                DB::table('decido_polls')->where('id', $row->id)->update([
                    'expires_at' => \Carbon\Carbon::parse($lastSlot)->addMonths($dateMonths),
                ]);
            }
        }

        // 2) Tout le reste sans expires_at (classique, ou "date" sans créneau généré - brouillon
        //    vide) : date de création + N mois. Réinterroge whereNull('expires_at') pour ne
        //    reprendre QUE ce que le passage 1 n'a pas couvert.
        DB::table('decido_polls')
            ->whereNull('expires_at')
            ->whereIn('status', ['open', 'draft'])
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function ($row) use ($fallbackMonths) {
                DB::table('decido_polls')->where('id', $row->id)->update([
                    'expires_at' => \Carbon\Carbon::parse($row->created_at)->addMonths($fallbackMonths),
                ]);
            });
    }

    public function down(): void
    {
        // Réversibilité de schéma complète (colonnes retirées) ; le backfill de expires_at n'est
        // volontairement PAS annulé (aucune trace de la valeur NULL d'origine n'est conservée -
        // pattern standard pour une migration de backfill de données, cf. discussion Laravel).
        // Sans conséquence pratique : la valeur précédente était NULL (aucune expiration), donc ne
        // pas la restaurer ne fait que laisser une date d'expiration calculée en base, ce qui est
        // strictement moins destructeur qu'un retour à NULL (qui désactiverait silencieusement la
        // purge pour ces sondages).
        Schema::table('decido_polls', function (Blueprint $table) {
            if (Schema::hasColumn('decido_polls', 'extension_count')) {
                $table->dropColumn('extension_count');
            }
            if (Schema::hasColumn('decido_polls', 'expiry_warned_at')) {
                $table->dropColumn('expiry_warned_at');
            }
        });
    }
};
