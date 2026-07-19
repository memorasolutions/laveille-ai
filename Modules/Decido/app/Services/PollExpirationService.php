<?php

declare(strict_types=1);

namespace Modules\Decido\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Modules\Decido\Enums\PollType;
use Modules\Decido\Models\Poll;

/**
 * Calcul centralisé (DRY - un seul endroit, jamais dupliqué) de la date d'expiration d'un
 * sondage Decido, réutilisé par : PollManageController::store() (création), ::close()
 * (clôture manuelle), ::extend() (prolongation), et la migration de backfill
 * 2026_07_19_120000_add_retention_fields_to_decido_polls (dupliquée en SQL pur là-bas
 * seulement, une migration ne devant pas dépendre du code applicatif - voir son docblock).
 *
 * Politique (2026-07-19, recherche pp_search + validation Codex/Gemini, approuvée utilisateur) :
 *   - Sondage "date"      : dernière date candidate proposée + N mois (config
 *                           decido.expiration_months_date_type).
 *   - Sondage "classique"
 *     ou brouillon         : date de création + N mois (config
 *                           decido.expiration_months_classic_or_draft).
 *   - Clôture manuelle     : clôture + N jours (config decido.expiration_days_after_close).
 *   - Prolongation         : expires_at courant + N mois (config decido.extension_months),
 *                           plafonnée à decido.max_extensions.
 */
class PollExpirationService
{
    /**
     * Calcule l'expiration initiale d'un sondage (à sa création, ou lors d'un recalcul suite à
     * une modification des dates candidates). Pour le type "date", relit les options déjà
     * persistées en base (poll->options()) - la méthode doit donc être appelée APRÈS que les
     * PollOption ont été créées dans la même transaction.
     */
    public static function forNewOrUpdatedPoll(Poll $poll): CarbonInterface
    {
        if ($poll->type === PollType::DATE) {
            $lastSlot = $poll->options()->max('ends_at') ?? $poll->options()->max('starts_at');

            if ($lastSlot) {
                return Carbon::parse($lastSlot)->addMonths((int) config('decido.expiration_months_date_type', 2));
            }

            // Sondage "date" sans aucun créneau généré (brouillon vide, ex. rollback partiel
            // évité par DB::transaction mais couvert ici en défense) : retombe sur la même règle
            // que "classique ou brouillon" ci-dessous.
        }

        $reference = $poll->created_at ?? now();

        return Carbon::parse($reference)->addMonths((int) config('decido.expiration_months_classic_or_draft', 3));
    }

    /**
     * Expiration appliquée à la clôture manuelle (PollManageController::close()).
     */
    public static function forClosure(): CarbonInterface
    {
        return now()->addDays((int) config('decido.expiration_days_after_close', 30));
    }

    /**
     * Expiration appliquée à une prolongation ("Prolonger de 3 mois") : ajoutée à l'expires_at
     * COURANT du sondage (pas à now()), pour ne jamais raccourcir une échéance déjà annoncée au
     * créateur par le courriel d'avertissement.
     */
    public static function forExtension(Poll $poll): CarbonInterface
    {
        $reference = $poll->expires_at ?? now();

        return Carbon::parse($reference)->addMonths((int) config('decido.extension_months', 3));
    }
}
