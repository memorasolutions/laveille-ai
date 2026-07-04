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

    // Uniformisation de mise en page avec les 2 autres outils "outils gratuits"
    // (generateur-mots-passe/tirage-presentations) : col-lg-8 (pas col-lg-9),
    // pas d'icône emoji dans le H1 (uniquement $tool->name, comme les 2 autres).
    // Note : le fond #f8f9fa "chunké" (.mv-panel) a été essayé puis retiré (#705,
    // effet "pains empilés" signalé par l'utilisateur) — pas d'assertion dessus.
    $response->assertSee('col-lg-8', escape: false);
    $response->assertDontSee('col-lg-9', escape: false);
    $response->assertDontSee('⏱️ Minuteur visuel', escape: false);

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

    // Légende du feu de circulation — 3 lignes dynamiques (seuils dérivés de totalSeconds)
    // + rappel que le chiffre au centre reste le temps exact restant.
    $response->assertSee('class="mv-traffic-legend"', escape: false);
    $response->assertSee('class="mv-traffic-legend__line"', escape: false);
    $response->assertSee('class="mv-traffic-legend__note"', escape: false);
    $response->assertSee('Plus de la moitié du temps', escape: false);
    $response->assertSee('trafficGreenThreshold', escape: false);
    $response->assertSee('trafficYellowThreshold', escape: false);
    $response->assertSee('trafficTotalFormatted', escape: false);

    // Sablier réaliste — dégradé de sable, texture de grain (feTurbulence) et verre dégradé.
    $response->assertSee('id="mvSandGradient"', escape: false);
    $response->assertSee('id="mvGlassGradient"', escape: false);
    $response->assertSee('id="mvSandGrain"', escape: false);
    $response->assertSee('feTurbulence', escape: false);
    $response->assertSee('class="mv-hourglass-sand-surface"', escape: false);

    // Checkbox Réglages visibles (convention charte display:inline-block !important,
    // cf. tirage-presentations/generateur-mots-passe) + suffixe "s" du seuil d'alerte.
    $response->assertSee('display:inline-block !important; width:20px; height:20px', escape: false);
    $response->assertSee('id="mvWarningThreshold"', escape: false);

    // Grains qui tombent visiblement à travers le goulot du sablier pendant le décompte.
    $response->assertSee('class="mv-sand-stream"', escape: false);
    $response->assertSee('mv-sand-grain-particle-1', escape: false);
    $response->assertSee('mv-sand-grain-particle-2', escape: false);
    $response->assertSee('mv-sand-grain-particle-3', escape: false);

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
