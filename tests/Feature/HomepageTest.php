<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Pages\Models\StaticPage;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

it('affiche la landing page par défaut', function () {
    $this->get('/')->assertOk()->assertSee('Laravel');
});

it('affiche la landing quand homepage.type est landing', function () {
    Setting::set('homepage.type', 'landing', 'string', 'homepage');

    $this->get('/')->assertOk();
});

it('affiche une page statique quand configurée', function () {
    $page = StaticPage::create([
        'title' => 'Bienvenue sur notre site',
        'slug' => 'bienvenue',
        'content' => '<p>Contenu de la page d\'accueil personnalisée.</p>',
        'status' => 'published',
    ]);

    Setting::set('homepage.type', 'page', 'string', 'homepage');
    Setting::set('homepage.page_id', (string) $page->id, 'string', 'homepage');

    $response = $this->get('/');
    $response->assertOk();
    $response->assertSee('Bienvenue sur notre site');
});

it('fallback sur la landing si la page statique n\'existe pas', function () {
    Setting::set('homepage.type', 'page', 'string', 'homepage');
    Setting::set('homepage.page_id', '99999', 'string', 'homepage');

    $this->get('/')->assertOk();
});

it('fallback sur la landing si la page est en brouillon', function () {
    $page = StaticPage::create([
        'title' => 'Brouillon',
        'slug' => 'brouillon',
        'content' => '<p>Non publiée.</p>',
        'status' => 'draft',
    ]);

    Setting::set('homepage.type', 'page', 'string', 'homepage');
    Setting::set('homepage.page_id', (string) $page->id, 'string', 'homepage');

    $response = $this->get('/');
    $response->assertOk();
    $response->assertDontSee('Brouillon');
});

it('fallback sur la landing si page_id est vide', function () {
    Setting::set('homepage.type', 'page', 'string', 'homepage');
    Setting::set('homepage.page_id', '', 'string', 'homepage');

    $this->get('/')->assertOk();
});

it('les settings homepage existent après seeding', function () {
    $this->artisan('db:seed', ['--class' => 'Modules\\Settings\\Database\\Seeders\\SettingsDatabaseSeeder']);

    expect(Setting::where('key', 'homepage.type')->exists())->toBeTrue();
    expect(Setting::where('key', 'homepage.page_id')->exists())->toBeTrue();
    expect(Setting::get('homepage.type'))->toBe('landing');
});
