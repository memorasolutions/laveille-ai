<?php

declare(strict_types=1);

/**
 * GET /api/header-nav (ticket #3013) : contrat JSON figé, ETag, 304, throttle, source unique.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Modules\FrontTheme\Services\HeaderNavService;

uses(Tests\TestCase::class);

beforeEach(function () {
    foreach (['directory_tools_count', 'dictionary_terms_count', 'acronyms_count'] as $cle) {
        cache()->put($cle, 7, 60);
    }
});

it('répond 200 avec la forme du contrat figé', function () {
    $reponse = $this->getJson('/api/header-nav');

    $reponse->assertOk()->assertJsonStructure(['version', 'generated_at', 'menu', 'footer' => ['legal']]);

    $json = $reponse->json();
    expect(\Carbon\Carbon::parse($json['generated_at'])->isValid())->toBeTrue();
    expect($json['generated_at'])->toMatch('/[+-]\d{2}:\d{2}$|Z$/');

    $labels = array_column($json['menu'], 'label');
    expect($labels)->toContain('Accueil', 'Outils', 'Apprendre');
    expect($labels)->not->toContain('Formations');
});

it("n'expose un cta que sous Outils, et des children que sous les panneaux", function () {
    $menu = collect($this->getJson('/api/header-nav')->json('menu'));

    $outils = $menu->firstWhere('label', 'Outils');
    expect($outils['cta'])->toBe(['label' => 'Voir tous les outils gratuits', 'url' => route('tools.index')]);
    expect(array_column($outils['children'], 'group'))->toBe(['Productivité', 'Création', 'Détente', 'Pratique']);

    foreach ($menu->where('label', '!=', 'Outils') as $noeud) {
        expect($noeud)->not->toHaveKey('cta');
    }

    $accueil = $menu->firstWhere('label', 'Accueil');
    expect($accueil)->not->toHaveKey('children');
    expect(array_keys($accueil))->toBe(['label', 'url']);
});

it("ne laisse sortir aucune clé de présentation interne", function () {
    $brut = $this->get('/api/header-nav')->getContent();

    foreach (['"icon"', '"mobile"', '"sidebar"', '"tail"', '"layout"', '"margin_last"'] as $cle) {
        expect($brut)->not->toContain($cle);
    }

    $menu = json_decode($brut, true)['menu'];
    foreach ($menu as $noeud) {
        foreach ($noeud['children'] ?? [] as $groupe) {
            foreach ($groupe['items'] as $item) {
                expect(array_keys($item))->toBe(['label', 'url', 'subtitle']);
            }
        }
    }
});

it('pose ETag et Cache-Control public, puis répond 304 sur If-None-Match', function () {
    $premiere = $this->getJson('/api/header-nav');
    $etag = $premiere->headers->get('ETag');

    expect($etag)->not->toBeNull();
    expect($premiere->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=300');

    $this->getJson('/api/header-nav', ['If-None-Match' => $etag])
        ->assertStatus(304)
        ->assertHeader('ETag', $etag);

    // Préfixe faible toléré (certains intermédiaires réécrivent l'ETag en W/"...").
    $this->getJson('/api/header-nav', ['If-None-Match' => 'W/'.$etag])->assertStatus(304);

    $this->getJson('/api/header-nav', ['If-None-Match' => '"autre"'])->assertOk();
});

it("garde un ETag stable d'une requête à l'autre (generated_at n'y entre pas)", function () {
    $a = $this->getJson('/api/header-nav')->headers->get('ETag');
    $b = $this->getJson('/api/header-nav')->headers->get('ETag');

    expect($a)->toBe($b);
});

it("change de version quand le contenu change (un compteur de l'annuaire)", function () {
    $avant = $this->getJson('/api/header-nav')->json('version');
    cache()->put('directory_tools_count', 999, 60);
    $apres = $this->getJson('/api/header-nav')->json('version');

    expect($apres)->not->toBe($avant);
});

it('est limitée par un throttle', function () {
    $route = app('router')->getRoutes()->getByName('api.header-nav');

    expect($route->gatherMiddleware())->toContain('throttle:60,1');

    // Et le plafond mord pour de vrai : la 61e requête de la minute reçoit 429.
    $dernier = null;
    for ($i = 0; $i < 61; $i++) {
        $dernier = $this->getJson('/api/header-nav');
    }
    $dernier->assertStatus(429);
});

it("sort de la même source que l'entête : les libellés rendus sont ceux de l'API", function () {
    $api = collect($this->getJson('/api/header-nav')->json('menu'))
        ->flatMap(fn ($n) => collect($n['children'] ?? [])->flatMap(fn ($g) => array_column($g['items'], 'label')));

    $html = view('fronttheme::partials.nav.items', ['nav' => app(HeaderNavService::class)->tree()])->render();

    foreach ($api as $label) {
        expect($html)->toContain(e($label));
    }
});
