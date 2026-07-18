<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * P0 (2026-07-19, signalé par l'utilisateur en production - 500 sur /admin/directory) :
 * app.locale = 'fr_CA' mais le champ Translatable 'slug' de Tool n'est souvent renseigné que
 * sous la clé 'fr'. config/translatable.php n'étant pas publié, aucun repli automatique
 * n'existe côté spatie/laravel-translatable - $tool->slug pour la locale courante renvoyait ''
 * et route('directory.show', '') levait UrlGenerationException. Corrigé dans
 * Tool::getPublicUrl() (repli manuel locale courante -> 'fr' -> 1re traduction disponible),
 * réutilisé par admin/index.blade.php au lieu de dupliquer route('directory.show', $tool->slug).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

test('Tool::getPublicUrl() ne plante pas quand le slug n\'existe que pour une autre locale', function () {
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr', 'Outil Test');
    $tool->setTranslation('slug', 'fr', 'outil-test-fallback');
    $tool->save();

    expect($tool->getTranslation('slug', 'fr_CA', false))->toBe('');
    expect($tool->getPublicUrl())->toContain('outil-test-fallback');
});

test('admin directory index does not 500 when a tool has no fr_CA slug translation', function () {
    config(['app.locale' => 'fr_CA']);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $tool = new Tool();
    $tool->setTranslation('name', 'fr', 'Outil Test QA P0');
    $tool->setTranslation('slug', 'fr', 'outil-test-qa-p0');
    $tool->setTranslation('description', 'fr', 'Test.');
    $tool->setTranslation('short_description', 'fr', 'Test.');
    $tool->url = 'https://example.com';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();

    $response = $this->actingAs($admin)->get(route('admin.directory.index'));

    $response->assertStatus(200);
    $response->assertSee('outil-test-qa-p0', false);
});
