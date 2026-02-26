<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\AI\Services\AiService;
use Modules\Blog\Models\Article;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->admin = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $this->admin->assignRole($role);
    $this->article = Article::factory()->create(['status' => 'published', 'user_id' => $this->admin->id]);
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.default_model', 'test-model');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');
});

it('translateContent returns translated text', function (): void {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Translated text content']],
            ],
        ]),
    ]);

    $service = app(AiService::class);
    $result = $service->translateContent('Bonjour le monde', 'fr', 'en');

    expect($result)->toBe('Translated text content');
});

it('translateContent returns original when API fails', function (): void {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => '']],
            ],
        ]),
    ]);

    $service = app(AiService::class);
    $result = $service->translateContent('Bonjour le monde', 'fr', 'en');

    expect($result)->toBe('Bonjour le monde');
});

it('translateContent returns empty string for empty input', function (): void {
    $service = app(AiService::class);
    $result = $service->translateContent('', 'fr', 'en');

    expect($result)->toBe('');
});

it('translateContent sends correct system prompt', function (): void {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Translated text content']],
            ],
        ]),
    ]);

    $service = app(AiService::class);
    $service->translateContent('Bonjour le monde', 'fr', 'en');

    Http::assertSent(function ($request): bool {
        $body = json_decode($request->body(), true);
        $messages = $body['messages'] ?? [];
        $allContent = collect($messages)->pluck('content')->implode(' ');

        return str_contains(strtolower($allContent), 'translat')
            && (str_contains($allContent, 'fr') || str_contains($allContent, 'en'));
    });
});

it('admin can translate article via route', function (): void {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Translated text content']],
            ],
        ]),
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.blog.articles.translate', $this->article), [
            'target_locale' => 'en',
        ]);

    $response->assertOk()
        ->assertJson(['success' => true]);
});

it('translate route requires authentication', function (): void {
    $response = $this->post(route('admin.blog.articles.translate', $this->article), [
        'target_locale' => 'en',
    ]);

    $response->assertRedirect();
});

it('translate route validates target_locale', function (): void {
    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.blog.articles.translate', $this->article), [
            'target_locale' => 'de',
        ]);

    $response->assertStatus(422);
});

it('translate route sets translations on article', function (): void {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'My English Title']],
            ],
        ]),
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.blog.articles.translate', $this->article), [
            'target_locale' => 'en',
        ]);

    $this->article->refresh();

    expect($this->article->getTranslation('title', 'en'))->not->toBeEmpty();
});

it('translate route generates slug for target locale', function (): void {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'My English Title']],
            ],
        ]),
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.blog.articles.translate', $this->article), [
            'target_locale' => 'en',
        ]);

    $this->article->refresh();

    $slug = $this->article->getTranslation('slug', 'en');
    expect($slug)->toMatch('/^[a-z0-9\-]+$/');
});

it('translate route handles API error gracefully', function (): void {
    Http::fake([
        'openrouter.ai/*' => Http::response([], 500),
    ]);

    $originalTitle = $this->article->getTranslation('title', 'fr');

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.blog.articles.translate', $this->article), [
            'target_locale' => 'en',
        ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $this->article->refresh();
    expect($this->article->getTranslation('title', 'en'))->toBe($originalTitle);
});

it('translate preserves source locale content', function (): void {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Translated text content']],
            ],
        ]),
    ]);

    $originalTitle = $this->article->getTranslation('title', 'fr');

    $this->actingAs($this->admin)
        ->postJson(route('admin.blog.articles.translate', $this->article), [
            'target_locale' => 'en',
        ]);

    $this->article->refresh();

    expect($this->article->getTranslation('title', 'fr'))->toBe($originalTitle);
});

it('AiService has translateContent method', function (): void {
    expect(method_exists(AiService::class, 'translateContent'))->toBeTrue();
});

it('has all required translation translations', function (): void {
    $frPath = base_path('lang/fr.json');
    $enPath = base_path('lang/en.json');

    expect(file_exists($frPath))->toBeTrue();
    expect(file_exists($enPath))->toBeTrue();

    $fr = json_decode(file_get_contents($frPath), true);
    $en = json_decode(file_get_contents($enPath), true);

    $requiredKeys = [
        'Article traduit avec succès',
        'Traduire en anglais',
        'Traduire en français',
        'Traduction automatique',
        'Échec de la traduction.',
    ];

    foreach ($requiredKeys as $key) {
        expect($fr)->toHaveKey($key);
        expect($en)->toHaveKey($key);
    }
});
