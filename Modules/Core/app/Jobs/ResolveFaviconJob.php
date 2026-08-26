<?php

declare(strict_types=1);

namespace Modules\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Services\FaviconResolverService;

/**
 * Résout le favicon d'un domaine EN ARRIERE-PLAN.
 *
 * Raison d'etre, mesuree le 2026-08-26 : la resolution interroge jusqu'a 3 fournisseurs
 * externes avec 3 secondes de delai chacun, soit 9 secondes par domaine. Appelee depuis une
 * vue, elle bloquait le rendu : une premiere visite de fiche d'outil coutait 4,4 a 10,6 s
 * contre 0,5 s ensuite. Le rendu lit desormais le cache seulement (resolveCached) et confie
 * le travail reseau a ce job.
 *
 * File dediee `favicons` : elle doit etre consommee par un worker, sinon les jobs
 * s'accumulent sans jamais s'executer et aucun favicon nouveau n'apparait.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class ResolveFaviconJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    // Large marge sur les 9 s du pire cas, sans immobiliser le worker si un fournisseur pend.
    public int $timeout = 30;

    public function __construct(public string $domain, public int $size = 64)
    {
        $this->onQueue('favicons');
    }

    public function handle(): void
    {
        // Ici l'appel bloquant est legitime : on est hors du cycle de rendu.
        FaviconResolverService::resolve($this->domain, $this->size);
    }
}
