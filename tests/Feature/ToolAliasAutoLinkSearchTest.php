<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Services\GlossaryLinkifier;
use Modules\Directory\Models\Tool;

// === Tests de structure (sans base de données) ===

test('GlossaryLinkifier charge la colonne aliases dans la requête Tools', function () {
    $source = file_get_contents(base_path('Modules/Core/app/Services/GlossaryLinkifier.php'));
    expect($source)->toContain("'id', 'name', 'slug', 'short_description', 'aliases'");
});

test('GlossaryLinkifier boucle sur les aliases de chaque outil', function () {
    $source = file_get_contents(base_path('Modules/Core/app/Services/GlossaryLinkifier.php'));
    expect($source)->toContain('$tool->aliases')
        ->and($source)->toContain("'type'           => 'tool_alias'");
});

test('GlossaryLinkifier respecte TOOL_NEVER_AUTO pour les aliases', function () {
    $source = file_get_contents(base_path('Modules/Core/app/Services/GlossaryLinkifier.php'));
    // Le bloc alias doit aussi vérifier TOOL_NEVER_AUTO
    expect(substr_count($source, 'TOOL_NEVER_AUTO'))->toBeGreaterThanOrEqual(2);
});

test('GlossaryLinkifier vérifie la longueur minimale pour les aliases', function () {
    $source = file_get_contents(base_path('Modules/Core/app/Services/GlossaryLinkifier.php'));
    // MIN_LENGTH doit être utilisé dans le bloc aliases
    expect(substr_count($source, 'self::MIN_LENGTH'))->toBeGreaterThanOrEqual(2);
});

test('Tool::searchableFields inclut la colonne aliases', function () {
    expect(Tool::searchableFields())->toContain('aliases');
});

test('Tool::searchableFields conserve les champs existants', function () {
    $fields = Tool::searchableFields();
    expect($fields)
        ->toContain('name')
        ->toContain('short_description')
        ->toContain('description')
        ->toContain('aliases');
});

// === Tests de comportement avec base de données ===

uses(RefreshDatabase::class);

/**
 * Crée un Tool publié en passant les champs translatables comme tableaux PHP
 * (Spatie HasTranslations appelle alors setTranslations(), non setTranslation()).
 */
function createPublishedTool(string $nameKey, string $slugKey, array $aliases = [], string $url = 'https://example.com'): Tool
{
    $locales = ['fr_CA', 'fr', 'en', 'en_CA'];
    $nameArr = array_fill_keys($locales, $nameKey);
    $slugArr = array_fill_keys($locales, $slugKey);
    $descArr = ['fr_CA' => 'Description test'];

    $tool = new Tool();
    $tool->setTranslations('name', $nameArr);
    $tool->setTranslations('slug', $slugArr);
    $tool->setTranslations('short_description', $descArr);
    $tool->setTranslations('description', ['fr_CA' => '']);
    $tool->status = 'published';
    $tool->url = $url;
    $tool->aliases = $aliases;
    $tool->save();

    return $tool;
}

test('GlossaryLinkifier génère une entrée pour chaque alias non vide d\'un outil publié', function () {
    GlossaryLinkifier::flushCache();

    createPublishedTool('z.ai', 'z-ai', ['GLM', 'GLM-5.1', 'ChatGLM'], 'https://z.ai');

    $terms = GlossaryLinkifier::loadTerms('fr_CA');

    // L'outil lui-même doit être présent
    $toolEntry = collect($terms)->firstWhere('name', 'z.ai');
    expect($toolEntry)->not->toBeNull()
        ->and($toolEntry['type'])->toBe('tool')
        ->and($toolEntry['url'])->toBe('/annuaire/z-ai');

    // Chaque alias doit générer une entrée distincte de type tool_alias
    $aliasGlm = collect($terms)->firstWhere('name', 'GLM');
    expect($aliasGlm)->not->toBeNull()
        ->and($aliasGlm['type'])->toBe('tool_alias')
        ->and($aliasGlm['url'])->toBe('/annuaire/z-ai');

    $aliasGlm51 = collect($terms)->firstWhere('name', 'GLM-5.1');
    expect($aliasGlm51)->not->toBeNull()
        ->and($aliasGlm51['url'])->toBe('/annuaire/z-ai');

    $aliasChatglm = collect($terms)->firstWhere('name', 'ChatGLM');
    expect($aliasChatglm)->not->toBeNull()
        ->and($aliasChatglm['url'])->toBe('/annuaire/z-ai');
});

test('GlossaryLinkifier ignore les aliases trop courts (< MIN_LENGTH)', function () {
    GlossaryLinkifier::flushCache();

    createPublishedTool('TestToolCourt', 'test-tool-court', ['AB', 'TT', 'GPT-Test-Long']);

    $terms = GlossaryLinkifier::loadTerms('fr_CA');

    // AB et TT ont 2 chars < MIN_LENGTH (3) → ignorés
    expect(collect($terms)->firstWhere('name', 'AB'))->toBeNull();
    expect(collect($terms)->firstWhere('name', 'TT'))->toBeNull();
    // GPT-Test-Long >= 3 chars → présent
    expect(collect($terms)->firstWhere('name', 'GPT-Test-Long'))->not->toBeNull();
});

test('la recherche annuaire trouve un outil par son alias via LIKE', function () {
    GlossaryLinkifier::flushCache();

    createPublishedTool('z.ai Recherche', 'z-ai-search', ['ChatGLM-Search', 'ZhipuAI'], 'https://z.ai');

    // Requête LIKE sur aliases doit retrouver le record
    $found = Tool::published()
        ->where(function ($q) {
            $q->orWhere('aliases', 'LIKE', '%ChatGLM-Search%');
        })
        ->first();

    expect($found)->not->toBeNull()
        ->and($found->getTranslation('slug', 'fr_CA', false))->toBe('z-ai-search');
});
