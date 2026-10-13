<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Services\MoodleCatalogService;
use Nwidart\Modules\Facades\Module;

uses(Tests\TestCase::class);

// Permet le test isolé avant la régénération de l'autoload au déploiement.
require_once __DIR__.'/../../app/Services/MoodleCatalogService.php';

beforeEach(function () {
    config([
        'cache.default' => 'array',
        'catalog.moodle_ws_url' => 'https://moodle.example.test/webservice/rest/server.php',
        'catalog.moodle_ws_token' => 'catalog-test-token',
        'catalog.moodle_base_url' => 'https://moodle.example.test',
        'catalog.cache_ttl' => 1800,
        'catalog.http_timeout' => 15,
    ]);
    Cache::forget('catalog.courses');
    Http::preventStrayRequests();
    Storage::fake('public');
    $this->module = Mockery::mock(\Nwidart\Modules\Module::class);
    $this->module->shouldReceive('isEnabled')->andReturn(true)->byDefault();
    Module::shouldReceive('find')->with('Catalog')->andReturn($this->module);
    $this->categories = [
        ['id' => 10, 'name' => 'Productivité', 'visible' => 1],
        ['id' => 20, 'name' => 'Cybersécurité', 'visible' => 1],
    ];
    $this->courses = [
        ['id' => 1, 'categoryid' => 10, 'visible' => 1, 'fullname' => 'Site'],
        ['id' => 2, 'categoryid' => 10, 'visible' => 1, 'fullname' => 'Formation publique', 'customfields' => [['shortname' => 'prix', 'value' => '49 $']], 'summary' => '<p>Présentation</p>', 'overviewfiles' => [['fileurl' => 'https://moodle.example.test/webservice/pluginfile.php/2/image.png?download=1']]],
        ['id' => 3, 'categoryid' => 20, 'visible' => 0, 'fullname' => 'Formation masquée'],
    ];
    $this->image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
    $this->fakeCatalogue = function () {
        Http::fake(fn (Request $request) => str_contains($request->url(), 'pluginfile.php')
            ? Http::response($this->image, 200, ['Content-Type' => 'image/png'])
            : Http::response(match ($request['wsfunction']) {
                'core_course_get_categories' => $this->categories,
                'core_course_get_courses_by_field' => ['courses' => $this->courses, 'warnings' => []],
                default => throw new RuntimeException('Fonction Moodle inattendue.'),
            }));
    };
});

it('exclut le cours site et les cours masqués', function () {
    ($this->fakeCatalogue)();

    expect(config('catalog.moodle_ws_token'))->toBe('catalog-test-token');
    expect(Module::find('Catalog')->isEnabled())->toBeTrue();
    $groups = app(MoodleCatalogService::class)->getCatalogue();

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['category'])->toBe('Productivité')
        ->and(array_column($groups[0]['courses'], 'id'))->toBe([2])
        ->and($groups[0]['courses'][0]['price'])->toBe('49 $')
        ->and($groups[0]['courses'][0]['course_url'])->toBe('https://moodle.example.test/course/view.php?id=2');
    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request) => ($request['moodlewsrestformat'] ?? null) === 'json'
        && $request['wstoken'] === 'catalog-test-token'
        && $request['wsfunction'] === 'core_course_get_courses_by_field');
});

it('regroupe dynamiquement plusieurs catégories et conserve le cache', function () {
    $this->courses[] = ['id' => 4, 'category' => 20, 'visible' => 1, 'fullname' => 'Autre formation'];
    ($this->fakeCatalogue)();
    $service = app(MoodleCatalogService::class);

    $groups = $service->getCatalogue();

    expect(array_column($groups, 'category'))->toBe(['Productivité', 'Cybersécurité'])
        ->and(array_column($groups[0]['courses'], 'id'))->toBe([2])
        ->and(array_column($groups[1]['courses'], 'id'))->toBe([4])
        ->and($groups[1]['courses'][0]['price'])->toBeNull()
        ->and($service->getCatalogue())->toBe($groups);
    Http::assertSentCount(3);
});

it('ignore les catégories masquées', function () {
    $this->categories[0]['visible'] = 0;
    ($this->fakeCatalogue)();

    expect(app(MoodleCatalogService::class)->getCatalogue())->toBe([]);
});

it('retourne un tableau vide sans jeton même avec un cache rempli', function () {
    Http::fake();
    Cache::put('catalog.courses', [['category' => 'Ancienne', 'courses' => []]], 1800);
    config(['catalog.moodle_ws_token' => '']);

    expect(app(MoodleCatalogService::class)->getCatalogue())->toBe([]);
    Http::assertNothingSent();
});

it('retourne un tableau vide quand le module est désactivé', function () {
    Http::fake();
    $this->module->shouldReceive('isEnabled')->andReturn(false);
    Cache::put('catalog.courses', [['category' => 'Ancienne', 'courses' => []]], 1800);

    expect(app(MoodleCatalogService::class)->getCatalogue())->toBe([]);
    Http::assertNothingSent();
});

it('absorbe les erreurs HTTP sur chacun des deux appels', function (string $function) {
    Log::spy();
    Http::fake(fn (Request $request) => $request['wsfunction'] === $function
        ? Http::response('catalog-test-token', 503)
        : Http::response($this->categories));

    expect(app(MoodleCatalogService::class)->getCatalogue())->toBe([]);
    Log::shouldHaveReceived('warning')->once()->with('Lecture du catalogue Moodle impossible.');
})->with(['core_course_get_categories', 'core_course_get_courses_by_field']);

it('absorbe les exceptions réseau sans journaliser leur contenu', function () {
    Log::spy();
    Http::fake(fn () => throw new ConnectionException('URL avec catalog-test-token'));

    expect(app(MoodleCatalogService::class)->getCatalogue())->toBe([]);
    Log::shouldHaveReceived('warning')->once()->with('Lecture du catalogue Moodle impossible.');
});

it('absorbe les erreurs Moodle renvoyées avec un statut HTTP 200', function () {
    Http::fake(fn () => Http::response(['exception' => 'moodle_exception', 'errorcode' => 'invalidtoken']));

    expect(app(MoodleCatalogService::class)->getCatalogue())->toBe([]);
});

it('rejette une réponse de cours sans tableau courses', function (array $data) {
    Log::spy();
    Http::fake(fn (Request $request) => Http::response(match ($request['wsfunction']) {
        'core_course_get_categories' => $this->categories,
        'core_course_get_courses_by_field' => $data,
        default => throw new RuntimeException('Fonction Moodle inattendue.'),
    }));

    expect(app(MoodleCatalogService::class)->getCatalogue())->toBe([]);
    Log::shouldHaveReceived('warning')->once()->with('Lecture du catalogue Moodle impossible.');
})->with([
    'liste plate' => [[['id' => 2, 'categoryid' => 10, 'visible' => 1]]],
    'champ absent' => [['warnings' => []]],
    'champ invalide' => [['courses' => 'invalide', 'warnings' => []]],
]);

it('proxifie une image une seule fois et ne retourne aucun jeton', function () {
    $image = $this->image;
    $this->courses[1]['overviewfiles'] = [['fileurl' => 'https://moodle.example.test/webservice/pluginfile.php/2/image.png?download=1']];
    Http::fake(fn (Request $request) => str_contains($request->url(), 'pluginfile.php')
        ? Http::response($image, 200, ['Content-Type' => 'image/png'])
        : Http::response(match ($request['wsfunction']) {
            'core_course_get_categories' => $this->categories,
            'core_course_get_courses_by_field' => ['courses' => $this->courses, 'warnings' => []],
            default => throw new RuntimeException('Fonction Moodle inattendue.'),
        }));
    $service = app(MoodleCatalogService::class);

    $groups = $service->getCatalogue();
    Cache::forget('catalog.courses');

    expect($groups[0]['courses'][0]['image_local'])->toBe(asset('storage/catalog/course-2.png'))
        ->and(json_encode($groups))->not->toContain('catalog-test-token')
        ->and($service->getCatalogue())->toBe($groups);
    Storage::disk('public')->assertExists('catalog/course-2.png');
    expect(Storage::disk('public')->get('catalog/course-2.png'))->toBe($image);
    Http::assertSentCount(5);
    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'pluginfile.php')
        && $request['token'] === 'catalog-test-token' && $request['download'] == 1);
});

it('utilise le repli pour une image indisponible ou une réponse non image', function (int $status) {
    $this->courses[1]['overviewfiles'] = [['fileurl' => 'https://moodle.example.test/image.png']];
    Http::fake(fn (Request $request) => str_contains($request->url(), 'image.png')
        ? Http::response('catalog-test-token', $status)
        : Http::response(match ($request['wsfunction']) {
            'core_course_get_categories' => $this->categories,
            'core_course_get_courses_by_field' => ['courses' => $this->courses, 'warnings' => []],
            default => throw new RuntimeException('Fonction Moodle inattendue.'),
        }));

    $groups = app(MoodleCatalogService::class)->getCatalogue();

    expect($groups)->toHaveCount(1)->and($groups[0]['courses'][0]['image_local'])->toBeNull();
    Storage::disk('public')->assertMissing('catalog/course-2.png');
})->with([200, 404]);

it('ne transmet pas le jeton à un hôte tiers pour une image', function () {
    $this->courses[1]['overviewfiles'] = [['fileurl' => 'https://other.example.test/image.png']];
    ($this->fakeCatalogue)();

    $groups = app(MoodleCatalogService::class)->getCatalogue();

    expect($groups[0]['courses'][0]['image_local'])->toBeNull();
    Http::assertSentCount(2);
});

it('rend une grille unique avec filtres, prix et repli sans JavaScript', function () {
    $disk = Storage::disk('public');
    $disk->put('catalog-test-views/layouts/master.blade.php', "@stack('styles') @yield('breadcrumb') @yield('content')");
    $disk->put('catalog-test-views/partials/breadcrumb.blade.php', '{{ $breadcrumbTitle }}');
    view()->prependNamespace('fronttheme', $disk->path('catalog-test-views'));
    view()->addNamespace('catalog', __DIR__.'/../../resources/views');
    $this->courses[1]['summary'] = '<a href="https://moodle.example.test/?token=catalog-test-token">Présentation</a>';
    ($this->fakeCatalogue)();
    $service = app(MoodleCatalogService::class);

    $html = view('catalog::public.index', ['categories' => $service->getCatalogue()])->render();
    expect($html)->toContain('Formation publique', 'Présentation', 'Voir la formation')
        ->not->toContain('catalog-test-token', '<h2>Productivité</h2>', 'Formation masquée');

    Cache::forget('catalog.courses');
    $this->courses[] = ['id' => 4, 'categoryid' => 20, 'visible' => 1, 'fullname' => 'Autre formation'];
    $html = view('catalog::public.index', ['categories' => $service->getCatalogue()])->render();
    expect($html)->toContain('49 $', 'Gratuit')->not->toContain('<h2>Productivité</h2>', '<h2>Cybersécurité</h2>');

    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath = new DOMXPath($document);
    $grid = '//div[contains(concat(" ", normalize-space(@class), " "), " grid ")]';
    $filters = '//div[@class="catalog-filters"]';
    expect($xpath->query($grid)->length)->toBe(1)
        ->and($xpath->query($grid.'/article')->length)->toBe(2)
        ->and($xpath->query('//article')->length)->toBe(2)
        ->and($xpath->query('//article[@hidden or @x-cloak or @style]')->length)->toBe(0)
        ->and($xpath->query($filters.'[@x-cloak]')->length)->toBe(1)
        ->and($xpath->query($filters.'/button')->length)->toBe(3)
        ->and($xpath->query($filters.'/button[@aria-pressed="true"]')->length)->toBe(1);
    foreach (['all' => 'Toutes', 'Productivité' => 'Productivité', 'Cybersécurité' => 'Cybersécurité'] as $filter => $label) {
        $button = $xpath->query($filters.'/button[@type="button" and @data-filter="'.$filter.'"]');
        expect($button->length)->toBe(1)
            ->and(trim($button->item(0)->textContent))->toBe($label)
            ->and($button->item(0)->getAttribute('aria-pressed'))->toBe($filter === 'all' ? 'true' : 'false');
    }
    foreach ([2 => 'Formation publique', 4 => 'Autre formation'] as $id => $fullname) {
        $url = 'https://moodle.example.test/course/view.php?id='.$id;
        $card = '//article[.//a[@href="'.$url.'"]]';
        $thumbnail = $card.'/a[@class="catalog-thumb-link" and @href="'.$url.'"]';
        expect($xpath->query($card)->length)->toBe(1)
            ->and($xpath->query($card)->item(0)->getAttribute('data-category'))->toBe($id === 2 ? 'Productivité' : 'Cybersécurité')
            ->and($xpath->query($card.'//span[@class="catalog-price"]')->item(0)->textContent)->toBe($id === 2 ? '49 $' : 'Gratuit')
            ->and($xpath->query($thumbnail)->length)->toBe(1)
            ->and($xpath->query($thumbnail)->item(0)->getAttribute('aria-label'))->toBe('Voir la formation : '.$fullname)
            ->and($xpath->query($thumbnail.'[@aria-hidden="true" or @tabindex="-1"]')->length)->toBe(0)
            ->and($xpath->query($card.'/div/a[@href="'.$url.'" and normalize-space(.)="Voir la formation"]')->length)->toBe(1)
            ->and($xpath->query($thumbnail.'//a')->length)->toBe(0);
        if ($id === 2) {
            expect($xpath->query($thumbnail.'/img[@src="'.asset('storage/catalog/course-2.png').'"]')->length)->toBe(1);
        } else {
            expect($xpath->query($thumbnail.'/div[@class="catalog-placeholder"]/svg[@aria-hidden="true"]')->length)->toBe(1);
        }
    }

    config(['catalog.moodle_ws_token' => '']);
    $html = view('catalog::public.index', ['categories' => $service->getCatalogue()])->render();
    expect($html)->toContain('Les formations seront bientôt disponibles.')->not->toContain('catalog-test-token');
});

it('formate le prix affiché et conserve le prix brut sauf les valeurs vides', function (?string $value, ?string $expected, ?string $display) {
    $this->courses[1]['customfields'] = [
        ['shortname' => 'autre', 'value' => 'Ignoré'],
        ['shortname' => 'prix', 'value' => $value],
    ];
    ($this->fakeCatalogue)();

    $groups = app(MoodleCatalogService::class)->getCatalogue();
    expect($groups[0]['courses'][0]['price'])->toBe($expected)
        ->and($groups[0]['courses'][0]['price_display'])->toBe($display ?? __('Gratuit'));

    $disk = Storage::disk('public');
    $disk->put('catalog-test-views/layouts/master.blade.php', "@stack('styles') @yield('content')");
    $disk->put('catalog-test-views/partials/breadcrumb.blade.php', '{{ $breadcrumbTitle }}');
    view()->prependNamespace('fronttheme', $disk->path('catalog-test-views'));
    view()->addNamespace('catalog', __DIR__.'/../../resources/views');
    $html = view('catalog::public.index', ['categories' => $groups])->render();

    expect($html)->toContain('<span class="catalog-price">'.e($display ?? __('Gratuit')).'</span>');
})->with([
    'nul' => [null, null, null],
    'vide' => ['', null, null],
    'espaces' => ['   ', null, null],
    'zéro' => ['0', '0', "0\u{00A0}$"],
    'entier' => ['199', '199', "199\u{00A0}$"],
    'décimales explicites' => ['199.00', '199.00', "199,00\u{00A0}$"],
    'milliers' => ['1500', '1500', "1\u{00A0}500\u{00A0}$"],
    'fraction' => ['199.95', '199.95', "199,95\u{00A0}$"],
    'virgule' => ['199,95', '199,95', "199,95\u{00A0}$"],
    'nombre avec espaces' => [' 199 ', ' 199 ', "199\u{00A0}$"],
    'sur demande' => ['sur demande', 'sur demande', 'sur demande'],
    'texte exact' => [' 49 $ & taxes <incluses> ', ' 49 $ & taxes <incluses> ', '49 $ & taxes <incluses>'],
]);

it('isole les observateurs HTTP puis restaure leur état après une exception', function () {
    $hub = \Sentry\SentrySdk::getCurrentHub();
    $recording = \Laravel\Telescope\Telescope::$shouldRecord;
    $observed = null;
    Http::fake(function () use ($hub, &$observed) {
        $observed = [
            \Laravel\Telescope\Telescope::isRecording(),
            \Sentry\SentrySdk::getCurrentHub() === $hub,
            \Sentry\SentrySdk::getCurrentHub()->getClient(),
        ];
        throw new ConnectionException('catalog-test-token');
    });

    expect(app(MoodleCatalogService::class)->getCatalogue())->toBe([])
        ->and($observed)->toBe([false, false, null])
        ->and(\Sentry\SentrySdk::getCurrentHub())->toBe($hub)
        ->and(\Laravel\Telescope\Telescope::$shouldRecord)->toBe($recording);
    Http::assertSentCount(0);
});
