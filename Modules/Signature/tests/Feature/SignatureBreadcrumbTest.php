<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Critère d'acceptation n°13 du plan (section 9.5, sur chacune des pages de l'outil) : (1) fil
 * d'Ariane visuel avec au moins un élément cliquable avant le dernier, (2) JSON-LD BreadcrumbList
 * valide, (3) le dernier élément correspond à la page courante et n'est pas un lien.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->superadmin = User::factory()->create(['email' => config('app.superadmin_email')]);
    $this->superadmin->assignRole('super_admin');
    Tool::updateOrCreate(['slug' => 'signature-courriel'], [
        'name' => 'Signature de courriel', 'description' => 'x',
        'is_active' => true, 'is_under_construction' => false,
    ]);
});

dataset('pages_avec_fil_ariane', function () {
    return [
        'assistant' => ['signature.assistant', []],
        'guide gmail' => ['signature.guide.gmail', []],
        'guide outlook web' => ['signature.guide.outlook-web', []],
    ];
});

test('chaque page de l\'outil porte un fil d\'Ariane visuel avec un élément cliquable avant le dernier', function (string $route, array $params): void {
    $html = $this->actingAs($this->superadmin)->get(route($route, $params))->getContent();

    expect($html)->toContain('wpo-breadcumb-wrap')
        ->and($html)->toContain('href="'.route('tools.index').'"');
})->with('pages_avec_fil_ariane');

test('chaque page de l\'outil émet un JSON-LD BreadcrumbList valide', function (string $route, array $params): void {
    $html = $this->actingAs($this->superadmin)->get(route($route, $params))->getContent();

    // JsonLdService::render() peut formatter avec ou sans espace après ":" selon les options
    // json_encode utilisées - on ne suppose jamais l'espacement exact ici.
    expect($html)->toContain('"BreadcrumbList"');

    // Plusieurs blocs JSON-LD peuvent coexister sur la page (ex. SoftwareApplication) - on isole
    // TOUS les scripts ld+json puis on retient celui qui contient BreadcrumbList, jamais une
    // capture regex naïve qui s'arrêterait à la première accolade fermante rencontrée.
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $scriptMatches);
    $breadcrumbJson = null;
    foreach ($scriptMatches[1] as $candidate) {
        $decoded = json_decode($candidate, true);
        if (is_array($decoded) && ($decoded['@type'] ?? null) === 'BreadcrumbList') {
            $breadcrumbJson = $decoded;
            break;
        }
    }

    $json = $breadcrumbJson;
    expect($json)->not->toBeNull()
        ->and($json['@type'])->toBe('BreadcrumbList')
        ->and($json['itemListElement'])->toBeArray()
        ->and(count($json['itemListElement']))->toBeGreaterThanOrEqual(2);
})->with('pages_avec_fil_ariane');

test('le dernier élément du fil d\'Ariane correspond à la page courante et n\'est PAS un lien cliquable', function (): void {
    $html = $this->actingAs($this->superadmin)->get(route('signature.guide.gmail'))->getContent();

    // La partial rend le dernier élément en <span>, jamais en <a> (Modules/FrontTheme/resources/
    // views/partials/breadcrumb.blade.php) - on vérifie l'absence d'un <a> autour du libellé exact
    // de la page courante.
    expect($html)->toContain('<span>Guide Gmail</span>')
        ->and($html)->not->toContain('<a href="'.route('signature.guide.gmail').'">Guide Gmail</a>');
});

test('le fil à 3 niveaux garde "Signature de courriel" cliquable comme élément intermédiaire', function (): void {
    $html = $this->actingAs($this->superadmin)->get(route('signature.guide.gmail'))->getContent();

    expect($html)->toContain('<a href="'.route('signature.assistant').'">Signature de courriel</a>');
});
