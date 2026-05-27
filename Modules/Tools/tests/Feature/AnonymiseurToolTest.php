<?php

declare(strict_types=1);

use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class);

beforeEach(function () {
    Tool::firstOrCreate(['slug' => 'anonymiseur'], [
        'name' => 'Anonymiseur de texte',
        'description' => 'Anonymisez vos textes avant de les envoyer à une IA. 100 % local.',
        'icon' => '🕵️',
        'sort_order' => 11,
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'securite',
    ]);
});

it('renders anonymiseur tool page with required DOM markers', function () {
    Tool::where('slug', 'anonymiseur')->update(['is_under_construction' => false]);

    $response = $this->get('/outils/anonymiseur');

    $response->assertStatus(200);
    $response->assertSee('Anonymiseur de texte', escape: false);
    $response->assertSee('id="sourceText"', escape: false);
    $response->assertSee('id="btnDetect"', escape: false);
    $response->assertSee('id="infoBanner"', escape: false);
    $response->assertSee('id="ruleModal"', escape: false);
    $response->assertSee('id="toastContainer"', escape: false);
    $response->assertSee('assets/tools/anonymiseur/app.js', escape: false);
    $response->assertSee('assets/tools/anonymiseur/styles.css', escape: false);
    $response->assertSee('assets/tools/anonymiseur/enhancements.js', escape: false);
    $response->assertSee('SoftwareApplication', escape: false);
});

it('respects is_under_construction flag for non-admin user', function () {
    Tool::where('slug', 'anonymiseur')->update(['is_under_construction' => true]);

    $response = $this->get('/outils/anonymiseur');

    $response->assertStatus(200);
    $response->assertSee('En construction');
});

it('serves anonymiseur in tools public index when active', function () {
    Tool::where('slug', 'anonymiseur')->update(['is_under_construction' => false, 'is_active' => true]);

    $response = $this->get('/outils');

    $response->assertStatus(200);
    $response->assertSee('Anonymiseur', escape: false);
});
