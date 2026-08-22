<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\Http\Middleware\HoneypotProtection;
use Modules\Core\Support\Honeypot;

uses(Tests\TestCase::class);

it('détecte un robot quand le champ canonique est rempli', function () {
    $request = Request::create('/', 'POST', [Honeypot::FIELD => 'http://pourriel.example']);

    expect(Honeypot::isBot($request))->toBeTrue();
});

it('détecte encore un robot sur l\'ancien nom de champ', function () {
    // Des pages déjà servies et mises en cache chez des visiteurs émettent encore
    // « website ». Cesser de le lire désactiverait leur protection jusqu'à l'expiration
    // de leur cache, sans que personne ne s'en aperçoive.
    $request = Request::create('/', 'POST', ['website' => 'http://pourriel.example']);

    expect(Honeypot::isBot($request))->toBeTrue();
});

it('laisse passer une requête propre', function () {
    $request = Request::create('/', 'POST', ['email' => 'personne@exemple.ca']);

    expect(Honeypot::isBot($request))->toBeFalse();
});

it('laisse passer un champ leurre présent mais vide', function () {
    // C'est le cas NORMAL : tout formulaire honnête soumet le leurre vide.
    $request = Request::create('/', 'POST', [Honeypot::FIELD => '']);

    expect(Honeypot::isBot($request))->toBeFalse();
});

it('ne confond jamais website_url avec un leurre', function () {
    // NON-RÉGRESSION CRITIQUE. « website_url » est un VRAI champ métier du module
    // Acronyms (site web officiel d'un acronyme). Le traiter comme un leurre rejetterait
    // des soumissions parfaitement légitimes et casserait ce module.
    $request = Request::create('/', 'POST', ['website_url' => 'https://exemple.qc.ca']);

    expect(Honeypot::isBot($request))->toBeFalse();
});

it('classe le champ canonique avant les anciens', function () {
    $fields = Honeypot::fields();

    expect($fields[0])->toBe(Honeypot::FIELD)
        ->and($fields)->toContain('website');
});

it('rend les attributs attendus du champ leurre', function () {
    $attributs = Honeypot::attributesString();

    expect($attributs)->toContain('name="'.Honeypot::FIELD.'"')
        ->and($attributs)->toContain('tabindex="-1"')
        ->and($attributs)->toContain('aria-hidden="true"')
        ->and($attributs)->toContain('autocomplete="off"');
});

it('n\'utilise jamais display none ni visibility hidden', function () {
    // ACCESSIBILITÉ. Deux raisons de refuser ces deux règles : certains robots les
    // détectent et évitent alors le champ, ce qui rend le piège inutile ; et un champ
    // ainsi masqué peut malgré tout être exposé par certaines technologies d'assistance.
    // Le champ est donc sorti de l'écran, jamais masqué.
    $attributs = Honeypot::attributesString();

    expect($attributs)->not->toContain('display:none')
        ->and($attributs)->not->toContain('visibility:hidden');
});

it('le middleware transmet une requête propre au maillon suivant', function () {
    $middleware = new HoneypotProtection;
    $request = Request::create('/', 'POST', ['email' => 'personne@exemple.ca']);

    $reponse = $middleware->handle($request, fn () => new Response('suite'));

    expect($reponse->getContent())->toBe('suite');
});

it('le middleware rejette silencieusement un leurre rempli en JSON', function () {
    // Rejet SILENCIEUX assumé : le robot reçoit un succès et persiste dans une stratégie
    // qui ne produit rien. Une erreur lui apprendrait qu'il a été repéré.
    $middleware = new HoneypotProtection;
    $request = Request::create('/', 'POST', [Honeypot::FIELD => 'pourriel']);
    $request->headers->set('Accept', 'application/json');

    $reponse = $middleware->handle($request, fn () => new Response('ne doit jamais être atteint'));

    expect($reponse->getStatusCode())->toBe(200)
        ->and($reponse->getContent())->not->toBe('ne doit jamais être atteint');
});

it('le composant blade se rend réellement sans erreur', function () {
    // RENDU RÉEL, et non simple vérification de syntaxe. Une variable Blade réservée
    // écrasée (comme $attributes) ne se voit qu'au rendu : php -l et view:cache la
    // laissent passer. Ce test est là pour ça.
    $this->blade('<x-core::honeypot />')
        ->assertSee(Honeypot::FIELD, false)
        ->assertDontSee('display:none', false);
});
