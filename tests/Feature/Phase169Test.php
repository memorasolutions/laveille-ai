<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\AI\Services\AiService;
use Modules\Blog\Models\Article;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

// --- AiService::analyzeContent ---

test('analyzeContent returns valid analysis with AI response', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'score' => 85,
                'readability' => 'Good',
                'seo_tips' => ['Add keywords'],
                'structure_tips' => ['Add headings'],
                'improvements' => ['Add images'],
            ])]]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $result = app(AiService::class)->analyzeContent('Test Title', 'Test content');

    expect($result)
        ->toHaveKeys(['score', 'readability', 'seo_tips', 'structure_tips', 'improvements'])
        ->and($result['score'])->toBe(85)
        ->and($result['readability'])->toBe('Good')
        ->and($result['seo_tips'])->toBe(['Add keywords'])
        ->and($result['structure_tips'])->toBe(['Add headings'])
        ->and($result['improvements'])->toBe(['Add images']);
});

test('analyzeContent returns default on empty AI response', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => []]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $result = app(AiService::class)->analyzeContent('Test', 'Content');

    expect($result['score'])->toBe(50);
    expect($result['readability'])->toBe('Unable to analyze');
    expect($result['seo_tips'])->toBeEmpty();
});

test('analyzeContent returns default on invalid JSON', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'not valid json at all']]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $result = app(AiService::class)->analyzeContent('Test', 'Content');

    expect($result['score'])->toBe(50);
    expect($result['readability'])->toBe('Unable to analyze');
});

test('analyzeContent returns default when no API key configured', function () {
    Setting::set('ai.openrouter_api_key', '');

    $result = app(AiService::class)->analyzeContent('Test', 'Content');

    expect($result['score'])->toBe(50);
    expect($result['readability'])->toBe('Unable to analyze');
});

test('analyzeContent strips HTML before analyzing', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'score' => 90,
                'readability' => 'Excellent',
                'seo_tips' => [],
                'structure_tips' => [],
                'improvements' => [],
            ])]]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $result = app(AiService::class)->analyzeContent(
        '<h1>Title</h1>',
        '<p>Content with <strong>HTML</strong></p>'
    );

    expect($result['score'])->toBe(90);
    Http::assertSentCount(1);
});

test('analyzeContent score is clamped 0-100', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'score' => 150,
                'readability' => 'Test',
                'seo_tips' => [],
                'structure_tips' => [],
                'improvements' => [],
            ])]]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $result = app(AiService::class)->analyzeContent('Test', 'Content');

    expect($result['score'])->toBe(100);
});

test('analyzeContent handles empty content gracefully', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'score' => 10,
                'readability' => 'Poor',
                'seo_tips' => ['Add content'],
                'structure_tips' => [],
                'improvements' => [],
            ])]]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $result = app(AiService::class)->analyzeContent('', '');

    expect($result)->toBeArray();
    expect($result['score'])->toBeInt();
});

// --- Admin route ---

test('Admin can analyze article via route', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'score' => 85,
                'readability' => 'Good',
                'seo_tips' => ['Tip 1'],
                'structure_tips' => ['Tip 2'],
                'improvements' => ['Tip 3'],
            ])]]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $article = Article::factory()->published()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.blog.articles.analyze', $article))
        ->assertOk()
        ->assertJson(['success' => true]);
});

test('Admin route returns JSON with analysis field', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'score' => 75,
                'readability' => 'Decent',
                'seo_tips' => ['Add meta'],
                'structure_tips' => [],
                'improvements' => [],
            ])]]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $article = Article::factory()->published()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('admin.blog.articles.analyze', $article));

    $response->assertJsonStructure([
        'success',
        'analysis' => ['score', 'readability', 'seo_tips', 'structure_tips', 'improvements'],
    ]);
});

test('Unauthenticated user cannot access analyze route', function () {
    $article = Article::factory()->published()->create();

    $this->post(route('admin.blog.articles.analyze', $article))
        ->assertRedirect('/login');
});

test('Non-admin user cannot access analyze route', function () {
    $article = Article::factory()->published()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.blog.articles.analyze', $article))
        ->assertForbidden();
});

// --- Translations ---

test('translation key Analyse du contenu exists in en', function () {
    app()->setLocale('en');
    expect(__('Analyse du contenu'))->toBe('Content analysis');
});

test('translation key Analyser le contenu exists in en', function () {
    app()->setLocale('en');
    expect(__('Analyser le contenu'))->toBe('Analyze content');
});

test('translation key Analyse générée avec succès exists in en', function () {
    app()->setLocale('en');
    expect(__('Analyse générée avec succès'))->toBe('Analysis generated successfully');
});
