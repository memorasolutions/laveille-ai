<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * #237 P27 — Pest tests anti-régression Blade @context corruption JSON-LD.
 *
 * Cause racine : Laravel 11 a introduit la directive Blade @context qui intercepte
 * toute occurrence de `@context` dans le source .blade.php, MÊME à l'intérieur
 * d'une string PHP passée à json_encode() via {!! !!}. La compilation Blade
 * remplace alors `@context` par du PHP qui appelle context()->has(...) → JSON
 * corrompu rendu côté client → Google rejette le schema → perte rich snippets.
 *
 * Fix : pré-encoder via `@php { ... json_encode([...]) ... } @endphp` puis
 * `{!! $var !!}` (Blade ne compile pas @context dans un bloc @php).
 *
 * Ces tests vérifient :
 *   1. Aucun fichier .blade.php n'a `@context` brut hors @php / @verbatim / @@ escape
 *   2. Le helper lv_jsonld_author_stephane() retourne un Person Schema.org valide
 *   3. La compilation Blade des fichiers critiques ne produit pas de corruption
 *      (signature `$__contextArgs` absente)
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

uses(Tests\TestCase::class);

/**
 * Liste des fichiers .blade.php qui DOIVENT être protégés contre la corruption
 * Blade @context (contiennent du JSON-LD inline).
 */
function lv_jsonld_blade_files(): array
{
    return [
        base_path('Modules/Core/resources/views/partials/glossary-jsonld.blade.php'),
        base_path('Modules/Tools/resources/views/public/tools/crossword/index.blade.php'),
        base_path('Modules/Tools/resources/views/public/tools/brain-dump.blade.php'),
        base_path('Modules/Core/resources/views/components/smart-share.blade.php'),
        base_path('Modules/FrontTheme/resources/views/blog/show.blade.php'),
        base_path('Modules/FrontTheme/resources/views/components/book-promo.blade.php'),
        base_path('Modules/Shop/resources/views/public/show.blade.php'),
    ];
}

test('helper lv_jsonld_author_stephane returns valid Schema.org Person', function () {
    expect(function_exists('lv_jsonld_author_stephane'))->toBeTrue();

    $author = lv_jsonld_author_stephane();

    expect($author)->toBeArray()
        ->and($author['@type'])->toBe('Person')
        ->and($author['name'])->toBe('Stéphane Lapointe')
        ->and($author)->toHaveKey('@id')
        ->and($author['@id'])->toContain('/auteur/stephane-lapointe')
        ->and($author)->toHaveKey('url')
        ->and($author)->toHaveKey('sameAs')
        ->and($author['sameAs'])->toContain('https://www.linkedin.com/in/lapointestephane')
        ->and($author)->toHaveKey('knowsAbout')
        ->and($author['knowsAbout'])->toContain('Intelligence artificielle')
        ->and($author['knowsAbout'])->toContain('Loi 25 (Québec)');
});

test('helper lv_jsonld_publisher returns valid Schema.org Organization', function () {
    expect(function_exists('lv_jsonld_publisher'))->toBeTrue();

    $publisher = lv_jsonld_publisher();

    expect($publisher)->toBeArray()
        ->and($publisher['@type'])->toBe('Organization')
        ->and($publisher)->toHaveKey('@id')
        ->and($publisher['@id'])->toContain('#organization')
        ->and($publisher)->toHaveKey('name')
        ->and($publisher)->toHaveKey('logo');
});

test('all critical JSON-LD blade files compile without @context corruption', function () {
    $compiler = app('blade.compiler');
    $corruptionSignature = '$__contextArgs';

    foreach (lv_jsonld_blade_files() as $path) {
        expect(file_exists($path))->toBeTrue("Fichier introuvable : {$path}");

        $source = file_get_contents($path);
        $compiled = $compiler->compileString($source);

        expect($compiled)->not->toContain(
            $corruptionSignature,
            "Corruption Blade @context détectée dans : {$path}"
        );
    }
});

test('glossary-jsonld partial compiled output preserves @context literal', function () {
    $path = base_path('Modules/Core/resources/views/partials/glossary-jsonld.blade.php');
    $source = file_get_contents($path);
    $compiled = app('blade.compiler')->compileString($source);

    // Après compilation, le JSON-LD doit toujours contenir la string littérale @context
    // (préservée car le @php block est respecté par Blade).
    expect($compiled)->toContain("'@context'")
        ->and($compiled)->toContain('schema.org')
        ->and($compiled)->not->toContain('$__contextArgs');
});

test('blog/show.blade.php no longer uses chr(64) workaround', function () {
    $path = base_path('Modules/FrontTheme/resources/views/blog/show.blade.php');
    $source = file_get_contents($path);

    // Le hack chr(64).'context' devait être remplacé par '@context' propre dans @php block.
    expect($source)->not->toContain("chr(64).'context'")
        ->and($source)->not->toContain('chr(64).\'context\'');
});

test('JsonLdService Article method uses harmonised author helper when available', function () {
    $path = base_path('Modules/SEO/app/Services/JsonLdService.php');
    $source = file_get_contents($path);

    expect($source)->toContain('lv_jsonld_author_stephane');
});

test('rendered JSON-LD via Blade is valid JSON with @context preserved', function () {
    // Simulation : compile une mini-vue qui mimique le pattern fixé.
    $template = <<<'BLADE'
@php
    $jsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => 'Test',
    ], JSON_UNESCAPED_UNICODE);
@endphp
<script type="application/ld+json">{!! $jsonLd !!}</script>
BLADE;

    $compiled = Blade::compileString($template);
    $tmp = tempnam(sys_get_temp_dir(), 'blade_test_').'.php';
    file_put_contents($tmp, $compiled);

    ob_start();
    require $tmp;
    $output = ob_get_clean();
    @unlink($tmp);

    // Extract JSON-LD
    preg_match('/<script type="application\/ld\+json">(.+?)<\/script>/s', $output, $m);
    $jsonStr = trim($m[1] ?? '');

    $decoded = json_decode($jsonStr, true);
    expect($decoded)->not->toBeNull('JSON-LD doit être décodable')
        ->and($decoded['@context'])->toBe('https://schema.org')
        ->and($decoded['@type'])->toBe('Person')
        ->and($decoded['name'])->toBe('Test');
});

test('composer autoload registers app/Helpers/jsonld.php', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);
    $files = $composer['autoload']['files'] ?? [];

    expect($files)->toContain('app/Helpers/jsonld.php');
});

test('no Blade comment in critical files contains @directive that would leak as PHP', function () {
    // Cause racine S95 P27 itération 2 : Blade comment {{-- ... @php ... --}} compile vers
    // {{-- ... <?php ... --}} dans le source, le `<?php` étant alors interprété comme
    // ouverture PHP réelle qui contamine toute la suite (les @endpush/@push suivants
    // sont avalés). Symptôme : "Cannot end a section without first starting one".
    //
    // Règle : aucun @directive dans un commentaire Blade. Utiliser texte simple
    // (« bloc PHP » au lieu de « @php block », « context » au lieu de « @context »).
    $forbiddenInComments = ['@php', '@endphp', '@context', '@if', '@endif', '@foreach', '@endforeach', '@push', '@endpush', '@section', '@endsection'];

    foreach (lv_jsonld_blade_files() as $path) {
        $source = file_get_contents($path);
        // Extract all Blade comments
        preg_match_all('/\{\{--.*?--\}\}/s', $source, $matches);
        foreach ($matches[0] as $comment) {
            foreach ($forbiddenInComments as $directive) {
                expect($comment)->not->toContain(
                    $directive,
                    "Blade comment contient {$directive} dans {$path} — risque de fuite PHP au compile.\nComment : " . $comment
                );
            }
        }
    }
});
