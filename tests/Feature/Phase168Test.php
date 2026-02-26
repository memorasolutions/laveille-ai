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

// --- AiService::generateSummary ---

test('generateSummary returns stripped content if short enough', function () {
    $service = app(AiService::class);
    $result = $service->generateSummary('<p>Short text here.</p>', 'fr', 160);

    expect($result)->toBe('Short text here.');
});

test('generateSummary calls AI for long content', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'AI generated summary']]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $service = app(AiService::class);
    $longContent = str_repeat('This is long content for summary. ', 50);
    $result = $service->generateSummary($longContent, 'fr', 160);

    expect($result)->toBe('AI generated summary');
    Http::assertSentCount(1);
});

test('generateSummary returns fallback on empty AI response', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => []]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $service = app(AiService::class);
    $longContent = str_repeat('Long content text. ', 50);
    $result = $service->generateSummary($longContent, 'fr', 160);

    expect(mb_strlen($result))->toBeLessThanOrEqual(160);
    expect($result)->toBe(mb_substr(strip_tags($longContent), 0, 160));
});

test('generateSummary respects maxLength parameter', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => str_repeat('A', 300)]]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $service = app(AiService::class);
    $longContent = str_repeat('Word. ', 100);
    $result = $service->generateSummary($longContent, 'fr', 50);

    expect(mb_strlen($result))->toBeLessThanOrEqual(50);
});

test('generateSummary handles empty content', function () {
    $service = app(AiService::class);
    $result = $service->generateSummary('', 'fr', 160);

    expect($result)->toBe('');
});

test('generateSummary strips HTML tags before checking length', function () {
    $service = app(AiService::class);
    $htmlContent = '<p><strong>Hello</strong> <em>world</em></p>';
    $result = $service->generateSummary($htmlContent, 'fr', 160);

    expect($result)->toBe('Hello world');
});

// --- Observer auto-summary ---

test('Observer generates excerpt on published article with auto_summary enabled', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'Auto generated excerpt']]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.auto_summary', true);

    $article = Article::factory()->published()->create(['excerpt' => null]);
    $article->refresh();

    $excerpt = $article->getTranslation('excerpt', 'fr', false);
    expect($excerpt)->not->toBeEmpty();
});

test('Observer skips excerpt if auto_summary setting disabled', function () {
    Setting::set('ai.auto_summary', false);

    $article = Article::factory()->published()->create(['excerpt' => null]);
    $article->refresh();

    $excerpt = $article->getTranslation('excerpt', 'fr', false);
    expect(empty($excerpt))->toBeTrue();
});

test('Observer skips excerpt if excerpt already exists', function () {
    Setting::set('ai.auto_summary', true);

    $article = Article::factory()->published()->create(['excerpt' => 'Existing excerpt']);
    $article->refresh();

    expect($article->getTranslation('excerpt', 'fr', false))->toBe('Existing excerpt');
});

test('Observer skips excerpt if article not published', function () {
    Setting::set('ai.auto_summary', true);

    $article = Article::factory()->draft()->create(['excerpt' => null]);
    $article->refresh();

    $excerpt = $article->getTranslation('excerpt', 'fr', false);
    expect(empty($excerpt))->toBeTrue();
});

// --- Admin route ---

test('Admin can regenerate summary via route', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'Regenerated summary']]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $article = Article::factory()->published()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.blog.articles.regenerate-summary', $article))
        ->assertOk()
        ->assertJson(['success' => true]);
});

test('Admin route returns JSON with summary field', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'Summary for JSON']]],
        ]),
    ]);
    Setting::set('ai.openrouter_api_key', 'test-key');

    $article = Article::factory()->published()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('admin.blog.articles.regenerate-summary', $article));

    $response->assertJsonStructure(['success', 'message', 'summary']);
    expect($response->json('summary'))->toBe('Summary for JSON');
});

test('Unauthenticated user cannot access regenerate-summary route', function () {
    $article = Article::factory()->published()->create();

    $this->post(route('admin.blog.articles.regenerate-summary', $article))
        ->assertRedirect('/login');
});

// --- Translations ---

test('translation key Résumé généré avec succès exists in en', function () {
    app()->setLocale('en');
    expect(__('Résumé généré avec succès'))->toBe('Summary generated successfully');
});

test('translation key Générer le résumé exists in en', function () {
    app()->setLocale('en');
    expect(__('Générer le résumé'))->toBe('Generate summary');
});

test('translation key Résumé automatique exists in en', function () {
    app()->setLocale('en');
    expect(__('Résumé automatique'))->toBe('Auto-summary');
});
