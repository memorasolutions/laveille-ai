<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 136 (2026-07-30) : le site annonçait aux moteurs une page que personne ne peut utiliser.
//
// Un outil gaté répond 200 OK avec une page placeholder. Deux problèmes se cumulaient :
//   1. La vue placeholder du module Tools ne posait jamais @section('page_noindex', true), donc le
//      layout retombait sur « index, follow ». Les deux AUTRES modules qui gatent un outil (Decido
//      et Books) posent déjà cette section exactement pour ce cas : le patron du projet existait,
//      ce module l'avait simplement manqué.
//   2. Le sitemap listait l'outil avec priorité 0.8 et fréquence hebdomadaire - la même qu'un outil
//      pleinement fonctionnel - parce que scopeActive() ne filtre QUE is_active.
//
// En local, le défaut était masqué par APP_NOINDEX (noindex global de développement) : il ne se
// serait manifesté qu'en production.
//
// Conséquence : budget de crawl gaspillé chaque semaine, et surtout un visiteur qui arrive depuis
// un résultat de recherche sur une page qui ne peut rien lui offrir.
//
// Note sur le choix du correctif : le filtre du sitemap est posé AU POINT D'APPEL, pas dans
// scopeActive(). Ce scope est partagé avec la liste /outils, qui doit justement continuer
// d'afficher les outils gatés (avec leur badge, cf. round 135) aux superadmins. Le modifier aurait
// corrigé le sitemap en cassant l'affichage.

it('marks the gated tool page as noindex (round 136)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/under-construction.blade.php'));

    expect($blade)->toContain("@section('page_noindex', true)");
});

it('aligns with the pattern already used by the other gated modules (round 136)', function () {
    foreach (['Decido', 'Books', 'Tools'] as $module) {
        $path = base_path("Modules/{$module}/resources/views/public/under-construction.blade.php");
        expect(file_exists($path))->toBeTrue("Vue placeholder absente pour {$module}");
        expect(file_get_contents($path))->toContain("@section('page_noindex', true)");
    }
});

it('keeps gated tools out of the sitemap (round 136)', function () {
    $controller = file_get_contents(base_path('Modules/SEO/app/Http/Controllers/SitemapController.php'));

    expect($controller)->toContain("Tool::active()->where('is_under_construction', false)->ordered()");
});

it('leaves scopeActive untouched so the tools list still shows gated tools (round 136)', function () {
    $model = file_get_contents(base_path('Modules/Tools/app/Models/Tool.php'));

    // Le scope partagé ne doit PAS avoir été durci : la liste /outils s'en sert pour montrer les
    // outils gatés aux superadmins, avec le badge du round 135.
    expect($model)->toContain("return \$query->where('is_active', true);");
    expect($model)->not->toContain("->where('is_active', true)->where('is_under_construction', false)");
});

it('really excludes the gated tool from the generated sitemap (round 136, real output)', function () {
    Tool::firstOrCreate(['slug' => 'outil-gate-test'], [
        'name' => 'Outil gaté de test',
        'description' => 'Test',
        'icon' => '🔒',
        'is_active' => true,
        'is_under_construction' => true,
        'construction_mode' => 'revision',
        'category' => 'productivite',
    ]);

    Tool::firstOrCreate(['slug' => 'outil-public-test'], [
        'name' => 'Outil public de test',
        'description' => 'Test',
        'icon' => '🔓',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    // Preuve par le contenu réellement généré, pas par la présence d'une chaîne dans le code.
    expect($xml)->toContain('/outils/outil-public-test');
    expect($xml)->not->toContain('/outils/outil-gate-test');
});
