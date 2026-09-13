<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Health\Checks\ProductHuntApiCheck;
use Modules\Health\Notifications\CheckFailedNotification;
use Spatie\Health\Checks\Result;

uses(Tests\TestCase::class, RefreshDatabase::class);

function courrielProductHunt(Result $resultat): string
{
    $resultat->check = ProductHuntApiCheck::new();

    return implode("\n", (new CheckFailedNotification([$resultat]))->toMail()->introLines);
}

// Ce test garde AUSSI le piege deja documente dans CheckFailedNotification : la branche compare
// la CLASSE, jamais le libelle. Spatie derive « ProductHuntApiCheck » en « Product Hunt Api » -
// une comparaison de chaine ferait disparaitre la marche a suivre du courriel, sans erreur.
it('affiche la marche à suivre « créer un jeton » quand le jeton est en cause', function () {
    $resultat = Result::make()
        ->meta(['cause' => 'jeton'])
        ->failed('Jeton ProductHunt invalide (HTTP 401).');

    expect(courrielProductHunt($resultat))
        ->toContain('Marche à suivre')
        ->toContain('api.producthunt.com/v2/oauth/applications')
        ->toContain('1Password')
        ->toContain("n'expire pas de lui-même");
});

// Meme piege que celui corrige pour OPcache le 2026-08-01, puis pour OpenRouter : envoyer
// remplacer un jeton alors que c'est le RESEAU qui a lache fait chercher un probleme inexistant.
it('affiche une marche à suivre DIFFÉRENTE quand le contact a échoué', function () {
    $resultat = Result::make()
        ->meta(['cause' => 'transport'])
        ->failed('Échec persistant de contact avec ProductHunt (HTTP 502), 3 essais consécutifs.');

    $courriel = courrielProductHunt($resultat);

    expect($courriel)
        ->toContain('Ne rien remplacer')
        ->and($courriel)->not->toContain('Créer une application');
});

it("n'ajoute aucune marche à suivre quand l'API va bien", function () {
    $resultat = Result::make()->meta(['cause' => 'api'])->ok();

    expect(courrielProductHunt($resultat))->not->toContain('Marche à suivre');
});
