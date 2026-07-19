<?php

declare(strict_types=1);

namespace Modules\Decido\Console;

use Illuminate\Console\Command;
use Modules\Decido\Models\Poll;

/**
 * Purge tout sondage Decido (peu importe le statut - ouvert, brouillon ou clôturé) dont
 * expires_at est dépassé.
 *
 * Élargissement de périmètre (2026-07-19, recherche pp_search + validation Codex/Gemini,
 * approuvée utilisateur) : le champ expires_at n'était écrit qu'à la clôture
 * (PollManageController::close()) et cette commande ne purgeait donc QUE les sondages
 * 'closed' - politique de rétention morte pour tout sondage jamais clôturé, trouvée par une
 * passe adversariale indépendante (skill /100, round 5). Modules\Decido\Services\PollExpirationService
 * calcule désormais expires_at dès la CRÉATION du sondage (ouvert ou brouillon), quel que soit
 * son statut final - retirer le filtre status='closed' ici était donc la contrepartie
 * nécessaire pour que la nouvelle politique de rétention s'applique réellement. Pattern calqué
 * sur Modules\ShortUrl\Console\CleanupExpiredCommand.
 */
class PurgeExpiredPollsCommand extends Command
{
    protected $signature = 'decido:purge-expired';

    protected $description = 'Supprime les sondages Decido (tout statut) dont la date d\'expiration est depassee';

    public function handle(): int
    {
        // Round 7 (skill /100) : chargeait tous les sondages expirés en memoire (->get()) puis
        // emettait une requete DELETE individuelle par sondage en boucle - defaut de conception
        // qui empire lineairement avec le volume. Remplace par un DELETE en masse : aucun hook
        // Eloquent deleting/deleted n'est enregistre sur Poll (verifie), et les cascades vers
        // options/votes sont au niveau contrainte FK DB (cascadeOnDelete), pas au niveau Eloquent
        // - donc un DELETE en masse se comporte de facon strictement identique et reste sur.
        //
        // baseConditions() = une closure plutot qu'un seul $query réutilisé (round 23, skill /100) :
        // interroger deux fois le MEME objet Builder (compter les short_url_id, PUIS supprimer)
        // risquerait de faire fuiter un ->whereNotNull('short_url_id') ajouté pour la première
        // requête dans la seconde, excluant à tort du DELETE les sondages expirés SANS lien court.
        // Reconstruire la requête à l'identique à chaque appel élimine ce risque par construction.
        // Round 27 (skill /100, période close au round 27 - cf. mémoire projet) filtrait sur
        // status='closed' en plus de expires_at : retiré le 2026-07-19 (élargissement de
        // périmètre, voir docblock de la classe) - désormais TOUT sondage expiré est purgé,
        // quel que soit son statut ('open', 'draft' ou 'closed').
        $baseConditions = fn () => Poll::whereNotNull('expires_at')
            ->where('expires_at', '<', now());

        $count = $baseConditions()->count();

        // Round 23 (skill /100) : decido_polls.short_url_id n'a AUCUNE contrainte de clé étrangère
        // (migration add_short_url_id_to_decido_polls : unsignedBigInteger nullable, ni constrained()
        // ni cascadeOnDelete()) - un sondage supprimé ici laissait systématiquement survivre en base
        // le ShortUrl associé (créé par Poll::claimShortUrl()), qui continue de rediriger (301,
        // redirect_type actif, is_active jamais désactivé) vers l'URL du sondage désormais supprimée
        // - un lien mort, potentiellement partagé publiquement (c'est tout l'objet d'un lien court),
        // qui pointe indéfiniment vers une page 404 au lieu d'un message clair. Les ShortUrl
        // concernés sont d'abord identifiés puis soft-supprimés (SoftDeletes du modèle ShortUrl) :
        // ShortUrlService::resolve() applique le scope global "non supprimé" d'Eloquent et cesse donc
        // de les résoudre, ShortUrlRedirectController affichant alors la page /lien-expire dédiée
        // (reason=notfound) au lieu d'un 404 brut sur l'URL cible.
        $shortUrlIds = $baseConditions()->whereNotNull('short_url_id')->pluck('short_url_id');

        $baseConditions()->delete();

        if ($shortUrlIds->isNotEmpty() && \Modules\Core\Services\ModuleChecker::isAvailable('ShortUrl')) {
            \Modules\ShortUrl\Models\ShortUrl::whereIn('id', $shortUrlIds)->delete();
        }

        $this->info("Sondages Decido expires supprimes : {$count}.");

        return self::SUCCESS;
    }
}
