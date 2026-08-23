<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Health\Checks\OpenRouterCreditCheck;
use Spatie\Health\Enums\Status;

uses(Tests\TestCase::class);

beforeEach(function () {
    config()->set('directory.openrouter_api_key', 'cle-de-test');
    config()->set('health.openrouter', [
        'enabled' => true,
        'timeout' => 5,
        'poll_seconds' => 1800,
        'warn_after_consecutive_failures' => 3,
        'warn_remaining_usd' => 50,
        'fail_remaining_usd' => 15,
        'warn_remaining_days' => 10,
        'fail_remaining_days' => 3,
        'connection_failures_cache_key' => 'tests:or:echecs',
        'measurement_cache_key' => 'tests:or:mesure',
    ]);

    Cache::forget('tests:or:echecs');
    Cache::forget('tests:or:mesure');
});

function openrouterCreditsFake(float $total, float $consomme): void
{
    Http::fake([
        'openrouter.ai/api/v1/credits' => Http::response([
            'data' => ['total_credits' => $total, 'total_usage' => $consomme],
        ], 200),
    ]);
}

// LE test le plus important du fichier : un message de notification non vide, MEME au vert,
// part en courriel a chaque passage du controle de sante - donc chaque minute. C'est la
// difference entre un garde-fou et un harcelement qu'on finit par filtrer.
it('reste totalement silencieux quand le solde est confortable', function () {
    openrouterCreditsFake(500, 100);

    $resultat = OpenRouterCreditCheck::new()->run();

    expect($resultat->status->equals(Status::ok()))->toBeTrue()
        ->and($resultat->getNotificationMessage())->toBe('')
        ->and($resultat->shortSummary)->toContain('400,00 $');
});

it('avertit quand le solde passe sous le seuil bas', function () {
    openrouterCreditsFake(500, 460);

    $resultat = OpenRouterCreditCheck::new()->run();

    expect($resultat->status->equals(Status::warning()))->toBeTrue()
        ->and($resultat->getNotificationMessage())->toContain('40,00 $');
});

it('echoue quand le solde est presque epuise', function () {
    openrouterCreditsFake(500, 490);

    $resultat = OpenRouterCreditCheck::new()->run();

    expect($resultat->status->equals(Status::failed()))->toBeTrue()
        ->and($resultat->getNotificationMessage())->toContain('10,00 $');
});

it('avertit quand aucune cle n\'est configuree', function () {
    config()->set('directory.openrouter_api_key', null);

    $resultat = OpenRouterCreditCheck::new()->run();

    expect($resultat->status->equals(Status::warning()))->toBeTrue()
        ->and($resultat->getNotificationMessage())->toContain("clé d'API");
});

it('avertit immediatement quand la cle est refusee, sans attendre de repetition', function () {
    Http::fake(['openrouter.ai/*' => Http::response(['error' => 'no'], 401)]);

    $resultat = OpenRouterCreditCheck::new()->run();

    expect($resultat->status->equals(Status::warning()))->toBeTrue()
        ->and($resultat->getNotificationMessage())->toContain('401');
});

it('ne dit rien sur un echec de connexion isole', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $resultat = OpenRouterCreditCheck::new()->run();

    expect($resultat->status->equals(Status::ok()))->toBeTrue()
        ->and($resultat->getNotificationMessage())->toBe('');
});

it('avertit apres trois echecs de connexion consecutifs', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    OpenRouterCreditCheck::new()->run();
    OpenRouterCreditCheck::new()->run();
    $troisieme = OpenRouterCreditCheck::new()->run();

    expect($troisieme->status->equals(Status::warning()))->toBeTrue()
        ->and($troisieme->getNotificationMessage())->toContain('3 fois');
});

// Le controle tourne chaque minute : sans etranglement, ce serait 1440 appels par jour a
// OpenRouter pour un solde qui bouge de quelques dollars, et autant d'occasions d'echec.
it('n\'interroge pas l\'API quand la derniere mesure est encore fraiche', function () {
    openrouterCreditsFake(500, 100);
    OpenRouterCreditCheck::new()->run();

    // La preuve doit DISCRIMINER. Un 500 ne conviendrait pas : le premier echec transitoire
    // renvoie lui aussi ok() en silence, donc le test passerait meme si le reseau etait
    // appele. On reponds donc un solde CRITIQUE : s'il etait lu, le verdict basculerait en
    // echec. Rester au vert avec l'ancien montant ne s'explique que par le cache.
    // (Http::assertSentCount ne sert a rien ici : un second Http::fake() remet a zero les
    // requetes enregistrees - verifie le 2026-08-23, l'assertion voyait 0 au lieu de 1.)
    openrouterCreditsFake(500, 495);
    $second = OpenRouterCreditCheck::new()->run();

    expect($second->status->equals(Status::ok()))->toBeTrue()
        ->and($second->getNotificationMessage())->toBe('')
        ->and($second->shortSummary)->toContain('400,00 $');
});

it('estime l\'autonomie et echoue quand elle tombe sous trois jours', function () {
    // 120 $ il y a deux heures, 80 $ maintenant : 40 $ brules en 2 h, soit 480 $ par jour.
    // Le solde de 80 $ reste au-dessus des DEUX seuils en dollars ; seule l'autonomie alerte.
    Cache::forever('tests:or:mesure', [
        't' => time() - 7200,
        'restant' => 120.0,
        'total' => 500.0,
        'consomme' => 380.0,
        'jours' => null,
    ]);
    openrouterCreditsFake(500, 420);

    $resultat = OpenRouterCreditCheck::new()->run();

    expect($resultat->status->equals(Status::failed()))->toBeTrue()
        ->and($resultat->getNotificationMessage())->toContain("jours d'autonomie");
});

it('ignore l\'autonomie quand le solde remonte apres une recharge', function () {
    Cache::forever('tests:or:mesure', [
        't' => time() - 7200,
        'restant' => 20.0,
        'total' => 500.0,
        'consomme' => 480.0,
        'jours' => null,
    ]);
    openrouterCreditsFake(1000, 480);

    $resultat = OpenRouterCreditCheck::new()->run();

    expect($resultat->status->equals(Status::ok()))->toBeTrue()
        ->and($resultat->getNotificationMessage())->toBe('');
});

it('ignore une entree de cache d\'un format inconnu plutot que de casser', function () {
    Cache::forever('tests:or:mesure', 'une-vieille-valeur-de-format-different');
    openrouterCreditsFake(500, 100);

    $resultat = OpenRouterCreditCheck::new()->run();

    expect($resultat->status->equals(Status::ok()))->toBeTrue();
});
