<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests `php artisan directory:backfill-ecosystem-tags` — pipeline de peuplement de
 * directory_tools.ecosystem_tag. Couvre le mode --dry-run (aucune écriture), l'écriture réelle,
 * et la règle non négociable : ne JAMAIS écraser un ecosystem_tag déjà rempli manuellement.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

function makeEcosystemTestTool(string $slug, string $url, ?string $ecosystemTag = null): Tool
{
    $tool = new Tool();
    $tool->url = $url;
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->is_featured = false;
    $tool->ecosystem_tag = $ecosystemTag;
    $tool->setTranslation('name', 'fr_CA', ucfirst($slug));
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Outil de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Test.');
    $tool->save();

    return $tool;
}

test('le dry-run detecte les tags sans rien ecrire en base', function () {
    $tool = makeEcosystemTestTool('chatgpt-dry', 'https://chatgpt.com');

    $this->artisan('directory:backfill-ecosystem-tags', ['--dry-run' => true])
        ->assertExitCode(0);

    expect($tool->fresh()->ecosystem_tag)->toBeNull();
});

test('sans --dry-run, ecrit le tag detecte sur les outils sans ecosystem_tag', function () {
    $tool = makeEcosystemTestTool('claude-write', 'https://claude.ai');

    $this->artisan('directory:backfill-ecosystem-tags')->assertExitCode(0);

    expect($tool->fresh()->ecosystem_tag)->toBe('anthropic');
});

test('n\'ecrase JAMAIS un ecosystem_tag deja rempli manuellement', function () {
    $tool = makeEcosystemTestTool('manual-tag', 'https://openai.com', 'mon-tag-manuel-a-moi');

    $this->artisan('directory:backfill-ecosystem-tags')->assertExitCode(0);

    expect($tool->fresh()->ecosystem_tag)->toBe('mon-tag-manuel-a-moi');
});

test('laisse null les outils dont le domaine n\'est pas reconnu (jamais de devinette)', function () {
    $tool = makeEcosystemTestTool('inconnu', 'https://un-domaine-jamais-vu-xyz789.example');

    $this->artisan('directory:backfill-ecosystem-tags')->assertExitCode(0);

    expect($tool->fresh()->ecosystem_tag)->toBeNull();
});

test('n\'evite pas le faux positif meme lors du backfill reel (fakeopenai.com)', function () {
    $tool = makeEcosystemTestTool('fake-openai', 'https://fakeopenai.com');

    $this->artisan('directory:backfill-ecosystem-tags')->assertExitCode(0);

    expect($tool->fresh()->ecosystem_tag)->toBeNull();
});
