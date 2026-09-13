<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Health\Checks\OpenRouterCreditCheck;
use Modules\Health\Checks\ProductHuntApiCheck;
use Modules\Health\Notifications\CheckFailedNotification;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Result;

uses(Tests\TestCase::class, RefreshDatabase::class);

function courrielDe(array $resultats): string
{
    return implode("\n", (new CheckFailedNotification($resultats))->toMail()->introLines);
}

// Le defaut signale par le fondateur le 2026-09-13, sur le premier courriel reellement envoye :
// un jeton d'API tiers refuse n'empeche AUCUN visiteur d'utiliser le site.
it("ne promet aucune panne visiteur quand seule une chaîne de fond est à l'arrêt", function () {
    $resultat = Result::make()->meta(['cause' => 'jeton'])->failed('Jeton ProductHunt invalide (HTTP 401).');
    $resultat->check = ProductHuntApiCheck::new();

    $courriel = courrielDe([$resultat]);

    expect($courriel)->toContain('Le site reste accessible');
    expect($courriel)->toContain("chaîne de fond");
    expect($courriel)->not->toContain('indisponibilité');
    expect($courriel)->not->toContain('ralentissements');
});

// Le defaut preexistait ici, avant meme le controle ProductHunt.
it('applique la même retenue au crédit OpenRouter, où le défaut existait déjà', function () {
    $resultat = Result::make()->meta(['restant' => 3.0])->failed('Crédit OpenRouter presque épuisé.');
    $resultat->check = OpenRouterCreditCheck::new();

    expect(courrielDe([$resultat]))
        ->toContain('Le site reste accessible')
        ->not->toContain('indisponibilité');
});

// LA contre-epreuve qui compte : il ne s'agit pas d'adoucir les VRAIES alertes.
it('garde intacte la phrase des pannes qui touchent vraiment le visiteur', function () {
    $resultat = Result::make()->failed('La connexion à la base de données a échoué.');
    $resultat->check = DatabaseCheck::new();

    expect(courrielDe([$resultat]))
        ->toContain('Les visiteurs peuvent subir des ralentissements, des erreurs ou une indisponibilité')
        ->not->toContain('Le site reste accessible');
});

// Quand les deux familles tombent ensemble, le visiteur passe en premier.
it('fait primer la panne visiteur quand les deux familles échouent dans le même courriel', function () {
    $fond = Result::make()->meta(['cause' => 'jeton'])->failed('Jeton ProductHunt invalide.');
    $fond->check = ProductHuntApiCheck::new();

    $visiteur = Result::make()->failed('La connexion à la base de données a échoué.');
    $visiteur->check = DatabaseCheck::new();

    expect(courrielDe([$fond, $visiteur]))
        ->toContain('Les visiteurs peuvent subir des ralentissements')
        ->not->toContain('Le site reste accessible');
});

// La metadonnee `cause` sert a aiguiller la marche a suivre, elle n'apprend rien au lecteur et
// redisait le resume deux lignes plus haut.
it("n'affiche plus la métadonnée interne « cause » dans le courriel", function () {
    $resultat = Result::make()->meta(['cause' => 'jeton'])->failed('Jeton ProductHunt invalide.');
    $resultat->check = ProductHuntApiCheck::new();

    $courriel = courrielDe([$resultat]);

    expect($courriel)->not->toContain('cause : jeton');
    // mais la marche à suivre, elle, s'aiguille toujours dessus
    expect($courriel)->toContain('api.producthunt.com/v2/oauth/applications');
});

// Un controle de chaine de fond au VERT ne doit pas faire basculer la phrase d'un courriel
// qui porte par ailleurs une vraie panne d'infrastructure.
it('ignore les contrôles au vert dans le choix de la phrase', function () {
    $vert = Result::make()->ok('API ProductHunt fonctionnelle.');
    $vert->check = ProductHuntApiCheck::new();

    $visiteur = Result::make()->failed('La connexion à la base de données a échoué.');
    $visiteur->check = DatabaseCheck::new();

    expect(courrielDe([$vert, $visiteur]))
        ->toContain('Les visiteurs peuvent subir des ralentissements');
});
