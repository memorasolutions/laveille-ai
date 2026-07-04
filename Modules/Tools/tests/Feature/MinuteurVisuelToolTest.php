<?php

declare(strict_types=1);

use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class);

beforeEach(function () {
    Tool::firstOrCreate(['slug' => 'minuteur-visuel'], [
        'name' => 'Minuteur visuel',
        'description' => 'Minuteur visuel gratuit avec 5 styles animés, présélections rapides et alertes sonores.',
        'icon' => '⏱️',
        'sort_order' => 14,
        'is_active' => true,
        'is_under_construction' => true,
        'category' => 'productivite',
    ]);
});

it('renders minuteur-visuel tool page with required DOM markers', function () {
    Tool::where('slug', 'minuteur-visuel')->update(['is_under_construction' => false]);

    $response = $this->get('/outils/minuteur-visuel');

    $response->assertStatus(200);
    $response->assertSee('Minuteur visuel', escape: false);

    // 5 styles visuels dans un radiogroup.
    $response->assertSee('role="radiogroup"', escape: false);
    $response->assertSee('Disque', escape: false);
    $response->assertSee('Sablier', escape: false);
    $response->assertSee('Anneau', escape: false);
    $response->assertSee('Chiffres', escape: false);
    $response->assertSee('Feu de circulation', escape: false);

    // Présélections nommées (5/10/15/25/45 + Pomodoro).
    $response->assertSee('5 min', escape: false);
    $response->assertSee('10 min', escape: false);
    $response->assertSee('15 min', escape: false);
    $response->assertSee('25 min', escape: false);
    $response->assertSee('45 min', escape: false);
    $response->assertSee('Pomodoro 25 min', escape: false);
    $response->assertSee('Pause 5 min', escape: false);

    // Durée personnalisée — saisie exacte en minutes, hors présélections.
    $response->assertSee('id="mvCustomMinutes"', escape: false);
    $response->assertSee('Définir', escape: false);

    // Annonces ARIA sobres.
    $response->assertSee('aria-live="polite"', escape: false);

    // JSON-LD WebApplication (via tool-geo, pas SoftwareApplication).
    $response->assertSee('WebApplication', escape: false);

    // Fichiers statiques référencés.
    $response->assertSee('assets/tools/minuteur-visuel/minuteur-visuel-core.js', escape: false);
    $response->assertSee('assets/tools/minuteur-visuel/minuteur-visuel.css', escape: false);
});

it('exposes the curated 5-tone color palette in the DOM', function () {
    Tool::where('slug', 'minuteur-visuel')->update(['is_under_construction' => false]);

    $response = $this->get('/outils/minuteur-visuel');

    $response->assertStatus(200);
    $response->assertSee('#991B1B', escape: false);
    $response->assertSee('#064E5A', escape: false);
    $response->assertSee('#9A2A06', escape: false);
    $response->assertSee('#6B21A8', escape: false);
    $response->assertSee('#1E40AF', escape: false);
});

it('accepts share query params without breaking server render', function () {
    Tool::where('slug', 'minuteur-visuel')->update(['is_under_construction' => false]);

    $response = $this->get('/outils/minuteur-visuel?minutes=15&style=ring');

    $response->assertStatus(200);
    $response->assertSee('Minuteur visuel', escape: false);
});

it('respects is_under_construction flag for non-admin user', function () {
    Tool::where('slug', 'minuteur-visuel')->update(['is_under_construction' => true]);

    $response = $this->get('/outils/minuteur-visuel');

    $response->assertStatus(200);
    $response->assertSee('En construction', escape: false);
});

it('serves minuteur-visuel in tools public index when active', function () {
    Tool::where('slug', 'minuteur-visuel')->update(['is_under_construction' => false, 'is_active' => true]);

    $response = $this->get('/outils');

    $response->assertStatus(200);
    $response->assertSee('Minuteur visuel', escape: false);
});
