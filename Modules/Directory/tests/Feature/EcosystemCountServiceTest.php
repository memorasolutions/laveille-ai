<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests EcosystemCountService::counts() — comptage agrégé (1 seule requête GROUP BY, pas de
 * N+1) des outils publiés par ecosystem_tag pour les badges/filtres du frontend, avec cache
 * (Cache::rememberForever) invalidé automatiquement par ToolObserver sur create/update/delete.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\EcosystemCountService;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

function makeCountTestTool(string $slug, string $status = 'published', ?string $ecosystemTag = 'openai'): Tool
{
    $tool = new Tool();
    $tool->url = 'https://chatgpt.com';
    $tool->pricing = 'free';
    $tool->status = $status;
    $tool->is_featured = false;
    $tool->ecosystem_tag = $ecosystemTag;
    $tool->setTranslation('name', 'fr_CA', ucfirst($slug));
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Outil de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Test.');
    $tool->save();

    return $tool;
}

test('compte uniquement les outils publies et tagges, group by ecosystem_tag', function () {
    makeCountTestTool('openai-1', 'published', 'openai');
    makeCountTestTool('openai-2', 'published', 'openai');
    makeCountTestTool('openai-pending', 'pending', 'openai'); // pas publié -> exclu
    makeCountTestTool('sans-tag', 'published', null); // pas de tag -> exclu

    $counts = (new EcosystemCountService())->counts();

    expect($counts)->toBe(['openai' => 2]);
});

test('le resultat est mis en cache sous la cle CACHE_KEY', function () {
    makeCountTestTool('openai-cache', 'published', 'openai');

    expect(Cache::has(EcosystemCountService::CACHE_KEY))->toBeFalse();

    (new EcosystemCountService())->counts();

    expect(Cache::has(EcosystemCountService::CACHE_KEY))->toBeTrue();
});

test('le cache est invalide automatiquement a la creation d\'un nouvel outil taggé', function () {
    makeCountTestTool('openai-before', 'published', 'openai');
    $before = (new EcosystemCountService())->counts();
    expect($before)->toBe(['openai' => 1]);

    // La création doit invalider le cache via ToolObserver::created() — sinon ce 2e appel
    // renverrait encore la valeur périmée mise en cache par l'appel précédent.
    makeCountTestTool('openai-after', 'published', 'openai');
    $after = (new EcosystemCountService())->counts();

    expect($after)->toBe(['openai' => 2]);
});

test('le cache est invalide automatiquement quand ecosystem_tag change sur un outil existant', function () {
    $tool = makeCountTestTool('changeant', 'published', 'openai');
    expect((new EcosystemCountService())->counts())->toBe(['openai' => 1]);

    $tool->ecosystem_tag = 'google';
    $tool->save();

    expect((new EcosystemCountService())->counts())->toBe(['google' => 1]);
});

test('le cache est invalide automatiquement a la suppression d\'un outil tagué', function () {
    $tool = makeCountTestTool('a-supprimer', 'published', 'openai');
    expect((new EcosystemCountService())->counts())->toBe(['openai' => 1]);

    $tool->delete();

    expect((new EcosystemCountService())->counts())->toBe([]);
});
