<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Health\Checks\OpenRouterCreditCheck;
use Modules\Health\Notifications\CheckFailedNotification;
use Spatie\Health\Checks\Result;

uses(Tests\TestCase::class, RefreshDatabase::class);

function courrielOpenRouter(Result $resultat): string
{
    $resultat->check = OpenRouterCreditCheck::new();

    return implode("\n", (new CheckFailedNotification([$resultat]))->toMail()->introLines);
}

// Ce test verifie AUSSI, implicitement, que le libelle derive du nom de classe vaut bien
// « openroutercredit » : si Spatie changeait sa facon de le deriver, la branche ne serait plus
// atteinte et la marche a suivre disparaitrait du courriel en silence.
it('affiche la marche a suivre « recharger » quand le solde a ete mesure', function () {
    $resultat = Result::make()
        ->meta(['restant' => 12.5, 'total' => 500.0, 'consomme' => 487.5, 'jours_estimes' => 2.1])
        ->failed('Crédit OpenRouter presque épuisé : 12,50 $ restants.');

    expect(courrielOpenRouter($resultat))
        ->toContain('Marche à suivre')
        ->toContain('openrouter.ai/credits')
        ->toContain('SANS erreur visible')
        ->toContain('Crédit OpenRouter restant (US$)');
});

// Le piege corrige pour OPcache le 2026-08-01 : conseiller une recharge alors que la mesure a
// simplement echoue envoie chercher un probleme qui n'existe pas.
it('affiche une marche a suivre DIFFERENTE quand la mesure a echoue', function () {
    $resultat = Result::make()
        ->meta(['statut' => 401])
        ->warning("Crédit OpenRouter non mesurable : la clé d'API est refusée (HTTP 401).");

    $courriel = courrielOpenRouter($resultat);

    expect($courriel)
        ->toContain('la mesure a échoué')
        ->toContain('OPENROUTER_API_KEY')
        ->and($courriel)->not->toContain('Recharger le compte');
});

it('n ajoute aucune marche a suivre quand le solde va bien', function () {
    $resultat = Result::make()->meta(['restant' => 400.0])->ok();

    expect(courrielOpenRouter($resultat))->not->toContain('Marche à suivre');
});
