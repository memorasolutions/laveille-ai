<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - rendu et assainissement du moteur PHP SignatureRenderer (jumeau de
 * signature-render.js, voir docblocks respectifs).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Signature\Services\SignatureRenderer;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

test('le rendu échappe les champs texte contre une tentative d\'injection HTML', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => '<script>alert(1)</script>',
        'last_name' => 'Tremblay"><img src=x onerror=alert(2)>',
        'email' => 'test@example.com',
    ], 'minimal');

    // La propriété de sécurité n'est PAS "la sous-chaîne n'apparaît jamais" (le texte inerte
    // "onerror=alert(2)" peut légitimement survivre, ÉCHAPPÉ) mais "aucune balise active n'est
    // jamais interprétable" : <script> et <img ...onerror=...> doivent apparaître UNIQUEMENT sous
    // forme échappée (&lt;...&gt;), jamais comme une vraie balise HTML brute.
    expect($html)->not->toContain('<script>')
        ->and($html)->not->toContain('<img src=x')
        ->and($html)->toContain('&lt;script&gt;')
        ->and($html)->toContain('&lt;img src=x onerror=alert(2)&gt;');
});

test('un schéma javascript: dans une URL est rejeté, jamais rendu comme href', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie',
        'last_name' => 'Tremblay',
        'email' => 'marie@example.com',
        'website' => 'javascript:alert(1)',
        'cta_text' => 'Cliquez',
        'cta_url' => 'javascript:alert(2)',
    ], 'minimal');

    expect($html)->not->toContain('javascript:');
});

test('les 4 gabarits produisent une structure de tables sans flex/grid/position', function (string $template): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'job_title' => 'Directrice', 'organization' => 'Acme inc.',
    ], $template, [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
    ]);

    expect($html)->toContain('<table')
        ->and($html)->not->toContain('display:flex')
        ->and($html)->not->toContain('display: flex')
        ->and($html)->not->toContain('grid-template')
        ->and($html)->not->toContain('position:absolute')
        ->and($html)->not->toContain('position: absolute');
})->with(['minimal', 'professionnel', 'portrait', 'compact']);

test('chaque balise img générée porte des attributs width/height numériques codés en dur', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'professionnel', [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
    ]);

    expect($html)->toMatch('/<img[^>]*width="\d+"[^>]*height="\d+"/');
});

test('une image sans dimensions connues n\'est jamais rendue (jamais un <img> sans width/height)', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'professionnel', [
        'logo' => ['url' => 'https://laveille.ai/logo.png'],
    ]);

    expect($html)->not->toContain('<img');
});

test('un gabarit inconnu retombe sur minimal plutôt que de planter', function (): void {
    $html = SignatureRenderer::render(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com'], 'inexistant');

    expect($html)->toContain('A B');
});
