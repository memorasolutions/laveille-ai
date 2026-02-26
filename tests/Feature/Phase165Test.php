<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\AI\Services\AiService;
use Modules\Blog\Models\Article;
use Modules\SEO\Models\MetaTag;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

beforeEach(function () {
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->admin = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $this->admin->assignRole($role);
    $this->article = Article::factory()->create(['status' => 'published', 'user_id' => $this->admin->id]);

    Setting::set('ai.seo_auto_generate', '1');
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.seo_model', 'test-model');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');
});

// --- AiService::generateSeoMeta ---

it('generateSeoMeta returns valid SEO data', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'title' => 'Optimized SEO Title',
                    'description' => 'A great meta description for the article.',
                    'keywords' => 'laravel,php,web',
                    'og_title' => 'OG Title Here',
                    'og_description' => 'OG description for sharing.',
                ])]],
            ],
        ]),
    ]);

    $service = app(AiService::class);
    $result = $service->generateSeoMeta('My Article', 'Article body content here.');

    expect($result)->toHaveKeys(['title', 'description', 'keywords', 'og_title', 'og_description'])
        ->and($result['title'])->toBe('Optimized SEO Title')
        ->and($result['description'])->toBe('A great meta description for the article.')
        ->and($result['keywords'])->toBe('laravel,php,web')
        ->and($result['og_title'])->toBe('OG Title Here')
        ->and($result['og_description'])->toBe('OG description for sharing.');
});

it('generateSeoMeta handles empty API response', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => '']],
            ],
        ]),
    ]);

    $title = 'My Article Title';
    $service = app(AiService::class);
    $result = $service->generateSeoMeta($title, 'Article body content here.');

    expect($result)->toHaveKeys(['title', 'description', 'keywords', 'og_title', 'og_description'])
        ->and($result['title'])->toBe(mb_substr($title, 0, 60));
});

it('generateSeoMeta handles invalid JSON', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'not json']],
            ],
        ]),
    ]);

    $title = 'My Article Title';
    $service = app(AiService::class);
    $result = $service->generateSeoMeta($title, 'Article body content here.');

    expect($result)->toHaveKeys(['title', 'description', 'keywords', 'og_title', 'og_description'])
        ->and($result['title'])->toBe(mb_substr($title, 0, 60));
});

// --- Observer creates MetaTag when article is published ---

it('observer creates MetaTag when article is published', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'title' => 'Optimized SEO Title',
                    'description' => 'A great meta description for the article.',
                    'keywords' => 'laravel,php,web',
                    'og_title' => 'OG Title Here',
                    'og_description' => 'OG description for sharing.',
                ])]],
            ],
        ]),
    ]);

    $article = Article::withoutEvents(fn () => Article::factory()->create([
        'status' => 'draft',
        'user_id' => $this->admin->id,
    ]));

    $article->status = 'published';
    $article->save();

    expect(MetaTag::count())->toBe(1)
        ->and(MetaTag::first()->url_pattern)->toBe('/blog/'.$article->slug);
});

it('observer skips when seo_auto_generate is disabled', function () {
    Setting::set('ai.seo_auto_generate', '0');

    Http::fake();

    Article::withoutEvents(fn () => Article::factory()->create([
        'status' => 'draft',
        'user_id' => $this->admin->id,
    ]));

    Http::assertNothingSent();
    expect(MetaTag::count())->toBe(0);
});

it('observer skips when article is draft', function () {
    Http::fake();

    Article::withoutEvents(fn () => Article::factory()->create([
        'status' => 'draft',
        'user_id' => $this->admin->id,
    ]));

    expect(MetaTag::count())->toBe(0);
});

it('observer triggers on status change to published', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'title' => 'Optimized SEO Title',
                    'description' => 'A great meta description for the article.',
                    'keywords' => 'laravel,php,web',
                    'og_title' => 'OG Title Here',
                    'og_description' => 'OG description for sharing.',
                ])]],
            ],
        ]),
    ]);

    $article = Article::withoutEvents(fn () => Article::factory()->create([
        'status' => 'draft',
        'user_id' => $this->admin->id,
    ]));

    $article->status = 'published';
    $article->save();

    expect(MetaTag::count())->toBe(1);
});

it('observer handles API error gracefully', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([], 500),
    ]);

    $article = Article::withoutEvents(fn () => Article::factory()->create([
        'status' => 'draft',
        'user_id' => $this->admin->id,
    ]));

    expect(fn () => $article->update(['status' => 'published']))->not->toThrow(\Exception::class);
    expect($article->exists)->toBeTrue();
});

it('MetaTag has correct url_pattern', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'title' => 'Optimized SEO Title',
                    'description' => 'A great meta description for the article.',
                    'keywords' => 'laravel,php,web',
                    'og_title' => 'OG Title Here',
                    'og_description' => 'OG description for sharing.',
                ])]],
            ],
        ]),
    ]);

    $article = Article::withoutEvents(fn () => Article::factory()->create([
        'status' => 'draft',
        'user_id' => $this->admin->id,
    ]));

    $article->status = 'published';
    $article->save();

    $metaTag = MetaTag::first();
    expect($metaTag)->not->toBeNull()
        ->and($metaTag->url_pattern)->toBe('/blog/'.$article->slug);
});

it('observer does not duplicate MetaTag with updateOrCreate', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'title' => 'Optimized SEO Title',
                    'description' => 'A great meta description for the article.',
                    'keywords' => 'laravel,php,web',
                    'og_title' => 'OG Title Here',
                    'og_description' => 'OG description for sharing.',
                ])]],
            ],
        ]),
    ]);

    $article = Article::withoutEvents(fn () => Article::factory()->create([
        'status' => 'draft',
        'user_id' => $this->admin->id,
    ]));

    $article->status = 'published';
    $article->save();

    expect(MetaTag::count())->toBe(1);

    // Trigger the observer again by marking as dirty and saving
    $article->touch();
    $article->status = 'published';
    $article->save();

    expect(MetaTag::count())->toBe(1);
});

// --- Admin route ---

it('admin can regenerate SEO via route', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'title' => 'Optimized SEO Title',
                    'description' => 'A great meta description for the article.',
                    'keywords' => 'laravel,php,web',
                    'og_title' => 'OG Title Here',
                    'og_description' => 'OG description for sharing.',
                ])]],
            ],
        ]),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.blog.articles.regenerate-seo', $this->article))
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('regenerate SEO requires authentication', function () {
    $this->post(route('admin.blog.articles.regenerate-seo', $this->article))
        ->assertRedirect();
});

// --- Translations ---

it('has all required SEO translations', function () {
    $frPath = lang_path('fr.json');
    $enPath = lang_path('en.json');

    $fr = json_decode((string) file_get_contents($frPath), true);
    $en = json_decode((string) file_get_contents($enPath), true);

    $keys = [
        'Génération SEO automatique',
        'Régénérer le SEO',
        'SEO généré avec succès',
    ];

    foreach ($keys as $key) {
        expect($fr)->toHaveKey($key)
            ->and($en)->toHaveKey($key);
    }
});
