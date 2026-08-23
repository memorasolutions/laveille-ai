<?php

declare(strict_types=1);

/**
 * Le délai d'EnrichToolJob doit TOUJOURS couvrir le pire cas de la cascade OpenRouter.
 *
 * Ce test existe à cause d'une panne réelle. Le 2026-08-23 à 10h50 heure du Québec, une alerte
 * « EnrichToolJob has been attempted too many times » est arrivée. Sa trace ne montrait que la
 * mécanique de la file d'attente : aucune exception applicative, aucun indice de la cause. La
 * cause était une contradiction arithmétique entre deux nombres logés dans deux fichiers
 * différents - le job s'accordait 180 secondes, la cascade pouvait en demander environ 1 080
 * (3 modèles × 3 tentatives × 60 s de délai HTTP, et `tools:enrich-pending` enchaîne DEUX
 * cascades par outil). Le job se faisait donc tuer par son propre délai, deux fois, puis marquer
 * en échec sans avoir jamais produit d'erreur.
 *
 * Deux nombres qui doivent rester cohérents mais vivent séparément finissent toujours par
 * diverger. Le délai se CALCULE désormais depuis le budget, et ce test échoue si la relation
 * se brise - y compris si quelqu'un rallonge le budget sans y penser.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Jobs\EnrichToolJob;
use Modules\Directory\Services\OpenRouterService;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('accorde au job au moins le budget de toutes ses cascades', function () {
    $job = new EnrichToolJob(1);

    $pireCas = OpenRouterService::budgetSecondes() * EnrichToolJob::CASCADES_PAR_OUTIL;

    expect($job->timeout)->toBeGreaterThanOrEqual($pireCas);
});

it('suit le budget quand celui-ci change, sans qu\'on touche au job', function () {
    config()->set('directory.openrouter_cascade_budget_seconds', 300);

    $job = new EnrichToolJob(1);

    expect($job->timeout)->toBe(300 * EnrichToolJob::CASCADES_PAR_OUTIL + EnrichToolJob::MARGE_SECONDES)
        ->and($job->timeout)->toBeGreaterThanOrEqual(300 * EnrichToolJob::CASCADES_PAR_OUTIL);
});

it('garde une marge pour le travail qui n\'est pas un appel réseau', function () {
    // Requêtes en base, écritures, journalisation : le job ne fait pas QUE des appels HTTP.
    $job = new EnrichToolJob(1);

    expect($job->timeout - OpenRouterService::budgetSecondes() * EnrichToolJob::CASCADES_PAR_OUTIL)
        ->toBe(EnrichToolJob::MARGE_SECONDES);
});

it('refuse un budget absurdement court, quoi que dise la configuration', function () {
    // Un budget à zéro ou négatif rendrait toute cascade impossible avant même le premier appel.
    config()->set('directory.openrouter_cascade_budget_seconds', 0);

    expect(OpenRouterService::budgetSecondes())->toBeGreaterThanOrEqual(15);
});
