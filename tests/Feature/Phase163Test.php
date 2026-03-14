<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\AI\Livewire\AiArticleGenerator;
use Modules\AI\Services\AiService;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

beforeEach(function () {
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->admin = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $this->admin->assignRole($role);
});

// --- Component Registration ---

it('registers ai-article-generator Livewire component', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(AiArticleGenerator::class)
        ->assertStatus(200);
});

// --- Visibility based on setting ---

it('renders nothing when AI is disabled', function () {
    Setting::set('ai.chatbot_enabled', '0');

    Livewire::test(AiArticleGenerator::class)
        ->assertDontSee("Générer avec l'IA");
});

it('renders button when AI is enabled', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(AiArticleGenerator::class)
        ->assertSee("Générer avec l'IA");
});

// --- Modal open/close ---

it('opens modal via openModal', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(AiArticleGenerator::class)
        ->assertSet('showModal', false)
        ->call('openModal')
        ->assertSet('showModal', true);
});

it('closes modal and resets all state', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(AiArticleGenerator::class)
        ->set('showModal', true)
        ->set('topic', 'Test Topic')
        ->set('tone', 'casual')
        ->set('length', 'long')
        ->set('locale', 'en')
        ->set('generatedContent', ['title' => 'Test'])
        ->set('isGenerating', true)
        ->set('error', 'Some error')
        ->call('closeModal')
        ->assertSet('showModal', false)
        ->assertSet('topic', '')
        ->assertSet('tone', 'professional')
        ->assertSet('length', 'medium')
        ->assertSet('locale', 'fr')
        ->assertSet('generatedContent', [])
        ->assertSet('isGenerating', false)
        ->assertSet('error', '');
});

// --- Validation ---

it('validates empty topic on generate', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(AiArticleGenerator::class)
        ->set('topic', '')
        ->call('generate')
        ->assertHasErrors(['topic' => 'required']);
});

it('validates topic max length', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(AiArticleGenerator::class)
        ->set('topic', str_repeat('a', 201))
        ->call('generate')
        ->assertHasErrors(['topic' => 'max']);
});

// --- Generate article ---

it('generates article successfully with Http fake', function () {
    Setting::set('ai.chatbot_enabled', '1');
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.content_model', 'test-model');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'title' => 'Generated Title',
                    'content' => '<p>Generated content</p>',
                    'excerpt' => 'Generated excerpt',
                    'meta_description' => 'Generated meta',
                    'tags' => ['laravel', 'php'],
                ])]],
            ],
        ]),
    ]);

    $component = Livewire::test(AiArticleGenerator::class)
        ->set('topic', 'Laravel best practices')
        ->call('generate');

    expect($component->get('isGenerating'))->toBeFalse()
        ->and($component->get('error'))->toBe('')
        ->and($component->get('generatedContent.title'))->toBe('Generated Title')
        ->and($component->get('generatedContent.content'))->toBe('<p>Generated content</p>')
        ->and($component->get('generatedContent.tags'))->toBe(['laravel', 'php']);
});

it('handles API error gracefully', function () {
    Setting::set('ai.chatbot_enabled', '1');

    $this->mock(AiService::class, function ($mock) {
        $mock->shouldReceive('generateArticle')
            ->once()
            ->andThrow(new \RuntimeException('API failure'));
    });

    $component = Livewire::test(AiArticleGenerator::class)
        ->set('topic', 'Test topic')
        ->call('generate');

    expect($component->get('isGenerating'))->toBeFalse()
        ->and($component->get('error'))->not->toBeEmpty();
});

it('handles empty API response with default values', function () {
    Setting::set('ai.chatbot_enabled', '1');
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.content_model', 'test-model');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => '']],
            ],
        ]),
    ]);

    $component = Livewire::test(AiArticleGenerator::class)
        ->set('topic', 'Empty test')
        ->call('generate');

    // generateArticle returns default array when response is empty
    expect($component->get('generatedContent.title'))->toBe('Empty test')
        ->and($component->get('isGenerating'))->toBeFalse();
});

// --- Apply fields ---

it('applyField dispatches ai-article-fill event', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(AiArticleGenerator::class)
        ->set('generatedContent', ['title' => 'My Title', 'content' => '<p>Body</p>'])
        ->call('applyField', 'title')
        ->assertDispatched('ai-article-fill', field: 'title', value: 'My Title');
});

it('applyAll dispatches ai-article-fill-all event', function () {
    Setting::set('ai.chatbot_enabled', '1');

    $data = [
        'title' => 'Title',
        'content' => '<p>Content</p>',
        'excerpt' => 'Excerpt',
        'meta_description' => 'Meta',
        'tags' => ['tag1'],
    ];

    Livewire::test(AiArticleGenerator::class)
        ->set('generatedContent', $data)
        ->call('applyAll')
        ->assertDispatched('ai-article-fill-all', data: $data);
});

it('applyField with invalid field does not dispatch', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(AiArticleGenerator::class)
        ->set('generatedContent', ['title' => 'Test'])
        ->call('applyField', 'nonexistent')
        ->assertNotDispatched('ai-article-fill');
});

// --- Default values ---

it('has correct default property values', function () {
    Setting::set('ai.chatbot_enabled', '1');

    $component = Livewire::test(AiArticleGenerator::class);

    expect($component->get('tone'))->toBe('professional')
        ->and($component->get('length'))->toBe('medium')
        ->and($component->get('locale'))->toBe('fr')
        ->and($component->get('showModal'))->toBeFalse()
        ->and($component->get('generatedContent'))->toBe([])
        ->and($component->get('isGenerating'))->toBeFalse()
        ->and($component->get('error'))->toBe('');
});

// --- AiService::generateArticle ---

it('AiService generateArticle returns correct structure', function () {
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.content_model', 'test-model');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'title' => 'Service Title',
                    'content' => '<p>Service content</p>',
                    'excerpt' => 'Service excerpt',
                    'meta_description' => 'Service meta',
                    'tags' => ['service', 'test'],
                ])]],
            ],
        ]),
    ]);

    $service = app(AiService::class);
    $result = $service->generateArticle('Test topic');

    expect($result)->toHaveKeys(['title', 'content', 'excerpt', 'meta_description', 'tags'])
        ->and($result['title'])->toBe('Service Title')
        ->and($result['tags'])->toBe(['service', 'test']);
});

it('AiService generateArticle handles invalid JSON with defaults', function () {
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.content_model', 'test-model');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'This is not JSON at all']],
            ],
        ]),
    ]);

    $service = app(AiService::class);
    $result = $service->generateArticle('Fallback topic');

    expect($result['title'])->toBe('Fallback topic')
        ->and($result['tags'])->toBe([]);
});

// --- Layout inclusion ---

it('includes ai-article-generator in blog create view', function () {
    $path = module_path('Blog', 'resources/views/themes/backend/admin/articles/create.blade.php');
    if (! file_exists($path)) {
        $path = module_path('Blog', 'resources/views/admin/articles/create.blade.php');
    }
    $content = file_get_contents($path);

    expect($content)->toContain("@livewire('ai-article-generator')");
});

// --- Translations ---

it('has all required article generator translations', function () {
    $frPath = lang_path('fr.json');
    $enPath = lang_path('en.json');

    $fr = json_decode(file_get_contents($frPath), true);
    $en = json_decode(file_get_contents($enPath), true);

    $keys = [
        "Générer avec l'IA",
        "Génération d'article IA",
        "Sujet de l'article",
        'Ton',
        'Longueur',
        'Langue',
        'Professionnel',
        'Décontracté',
        'Créatif',
        'Court (~500 mots)',
        'Moyen (~1000 mots)',
        'Long (~2000 mots)',
        'Générer',
        'Génération en cours...',
        'Résultats générés',
        'Appliquer',
        'Appliquer tout',
    ];

    foreach ($keys as $key) {
        expect($fr)->toHaveKey($key)
            ->and($en)->toHaveKey($key);
    }
});
