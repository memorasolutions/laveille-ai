<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('GET /privacy returns 200 and contains Politique de confidentialité', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertSee('Politique de confidentialité', false);
});

test('GET /terms returns 200 and contains Conditions d\'utilisation', function () {
    $this->get('/terms')
        ->assertOk()
        ->assertSee('Conditions', false);
});

test('GET /legal returns 200 and contains Mentions légales', function () {
    $this->get('/legal')
        ->assertOk()
        ->assertSee('Mentions', false);
});

test('Privacy page mentions PIPEDA', function () {
    $this->get('/privacy')->assertSee('PIPEDA', false);
});

test('Privacy page mentions Loi 25', function () {
    $this->get('/privacy')->assertSee('Loi 25', false);
});

test('Privacy page mentions RGPD', function () {
    $this->get('/privacy')->assertSee('RGPD', false);
});

test('Privacy page mentions CCPA', function () {
    $this->get('/privacy')->assertSee('CCPA', false);
});

test('Terms page links to privacy route', function () {
    $this->get('/terms')->assertSee('/privacy', false);
});

test('DetectPrivacyJurisdiction: fr-CA maps to canada_quebec', function () {
    $this->get('/', ['Accept-Language' => 'fr-CA,fr;q=0.9'])->assertOk();
    expect(session('privacy_jurisdiction'))->toBe('canada_quebec');
});

test('DetectPrivacyJurisdiction: en-US maps to ccpa', function () {
    $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])->assertOk();
    expect(session('privacy_jurisdiction'))->toBe('ccpa');
});

test('DetectPrivacyJurisdiction: de-DE maps to gdpr', function () {
    $this->get('/', ['Accept-Language' => 'de-DE,de;q=0.9'])->assertOk();
    expect(session('privacy_jurisdiction'))->toBe('gdpr');
});

test('DetectPrivacyJurisdiction: ja-JP maps to pipeda default', function () {
    $this->get('/', ['Accept-Language' => 'ja-JP,ja;q=0.9'])->assertOk();
    expect(session('privacy_jurisdiction'))->toBe('pipeda');
});

test('Cookie consent banner visible on homepage', function () {
    $this->get('/')->assertOk()->assertSee('Tout accepter', false);
});

test('Cookie consent has Personnaliser button', function () {
    $this->get('/')->assertOk()->assertSee('Personnaliser', false);
});
