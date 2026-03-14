<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SEO\Services\JsonLdService;

uses(RefreshDatabase::class);

test('json-ld service escapes html tags in output', function () {
    $article = (object) [
        'title' => '</script><script>alert(1)</script>',
        'published_at' => now(),
        'updated_at' => now(),
        'cover_image' => null,
        'user' => null,
        'meta_description' => 'test desc',
        'excerpt' => null,
        'slug' => 'test',
    ];

    $output = JsonLdService::render(JsonLdService::article($article));

    $closingScriptTag = '</script>';
    $positions = [];
    $offset = 0;

    while (($pos = strpos($output, $closingScriptTag, $offset)) !== false) {
        $positions[] = $pos;
        $offset = $pos + strlen($closingScriptTag);
    }

    expect($positions)->toHaveCount(1);
});

test('json-ld service produces valid json', function () {
    $article = (object) [
        'title' => 'Test Article with "quotes" & special <chars>',
        'published_at' => now(),
        'updated_at' => now(),
        'cover_image' => null,
        'user' => null,
        'meta_description' => 'test desc',
        'excerpt' => null,
        'slug' => 'test',
    ];

    $output = JsonLdService::render(JsonLdService::article($article));

    preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $output, $matches);
    $jsonString = $matches[1] ?? '';

    $decoded = json_decode($jsonString);
    expect($decoded)->not->toBeNull()
        ->and($decoded->headline)->toContain('Test Article');
});

test('crud command stub uses validated not all', function () {
    $filePath = base_path('Modules/Core/app/Console/MakeCrudCommand.php');
    $fileContent = file_get_contents($filePath);

    expect($fileContent)->not->toContain('$request->all()')
        ->and($fileContent)->toContain('$request->validated()');
});
