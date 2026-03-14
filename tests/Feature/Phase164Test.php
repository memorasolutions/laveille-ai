<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\AI\Services\AiService;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

beforeEach(function () {
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->admin = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $this->admin->assignRole($role);
    $this->article = Article::factory()->create(['status' => 'published', 'user_id' => $this->admin->id]);

    Setting::set('ai.auto_moderation_enabled', '1');
    Setting::set('ai.moderation_threshold', '0.7');
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.moderation_model', 'test-model');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');
});

// --- AiService::moderateContent ---

it('moderateContent returns approve verdict', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'verdict' => 'approve',
                    'confidence' => 0.95,
                    'reason' => 'Clean content',
                    'categories' => [],
                ])]],
            ],
        ]),
    ]);

    $service = app(AiService::class);
    $result = $service->moderateContent('Great article, thanks!');

    expect($result['verdict'])->toBe('approve')
        ->and($result['confidence'])->toBe(0.95)
        ->and($result['categories'])->toBe([]);
});

it('moderateContent returns spam verdict', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'verdict' => 'spam',
                    'confidence' => 0.98,
                    'reason' => 'Promotional content',
                    'categories' => ['spam', 'self-promotion'],
                ])]],
            ],
        ]),
    ]);

    $service = app(AiService::class);
    $result = $service->moderateContent('Buy cheap pills at example.com!');

    expect($result['verdict'])->toBe('spam')
        ->and($result['confidence'])->toBe(0.98)
        ->and($result['categories'])->toContain('spam');
});

it('moderateContent returns flag verdict', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'verdict' => 'flag',
                    'confidence' => 0.82,
                    'reason' => 'Potentially offensive',
                    'categories' => ['toxicity'],
                ])]],
            ],
        ]),
    ]);

    $service = app(AiService::class);
    $result = $service->moderateContent('Borderline content here');

    expect($result['verdict'])->toBe('flag')
        ->and($result['confidence'])->toBe(0.82);
});

it('moderateContent handles invalid JSON with default', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'This is not JSON']],
            ],
        ]),
    ]);

    $service = app(AiService::class);
    $result = $service->moderateContent('Some content');

    expect($result['verdict'])->toBe('flag')
        ->and($result['confidence'])->toBe(0.1);
});

it('moderateContent handles empty API response', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => '']],
            ],
        ]),
    ]);

    $service = app(AiService::class);
    $result = $service->moderateContent('Some content');

    expect($result['verdict'])->toBe('flag')
        ->and($result['confidence'])->toBe(0.1);
});

// --- Observer auto-moderation ---

it('auto-approves comment when AI says approve with high confidence', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'verdict' => 'approve',
                    'confidence' => 0.9,
                    'reason' => 'Clean',
                    'categories' => [],
                ])]],
            ],
        ]),
    ]);

    $comment = Comment::create([
        'article_id' => $this->article->id,
        'user_id' => $this->admin->id,
        'content' => 'Great article!',
        'status' => 'pending',
    ]);

    $comment->refresh();
    expect($comment->status->getValue())->toBe('approved');
});

it('marks comment as spam when AI says spam with high confidence', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'verdict' => 'spam',
                    'confidence' => 0.95,
                    'reason' => 'Spam detected',
                    'categories' => ['spam'],
                ])]],
            ],
        ]),
    ]);

    $comment = Comment::create([
        'article_id' => $this->article->id,
        'user_id' => $this->admin->id,
        'content' => 'Buy cheap pills!',
        'status' => 'pending',
    ]);

    $comment->refresh();
    expect($comment->status->getValue())->toBe('spam');
});

it('keeps comment pending when AI says flag', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'verdict' => 'flag',
                    'confidence' => 0.85,
                    'reason' => 'Needs review',
                    'categories' => ['toxicity'],
                ])]],
            ],
        ]),
    ]);

    $comment = Comment::create([
        'article_id' => $this->article->id,
        'user_id' => $this->admin->id,
        'content' => 'Flagged content',
        'status' => 'pending',
    ]);

    $comment->refresh();
    expect($comment->status->getValue())->toBe('pending');
});

it('keeps comment pending when confidence below threshold', function () {
    Setting::set('ai.moderation_threshold', '0.9');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'verdict' => 'approve',
                    'confidence' => 0.8,
                    'reason' => 'Low confidence',
                    'categories' => [],
                ])]],
            ],
        ]),
    ]);

    $comment = Comment::create([
        'article_id' => $this->article->id,
        'user_id' => $this->admin->id,
        'content' => 'Low confidence comment',
        'status' => 'pending',
    ]);

    $comment->refresh();
    expect($comment->status->getValue())->toBe('pending');
});

it('skips moderation when auto_moderation_enabled is off', function () {
    Setting::set('ai.auto_moderation_enabled', '0');

    Http::fake();

    $comment = Comment::create([
        'article_id' => $this->article->id,
        'user_id' => $this->admin->id,
        'content' => 'Should not be moderated',
        'status' => 'pending',
    ]);

    $comment->refresh();
    expect($comment->status->getValue())->toBe('pending');
    Http::assertNothingSent();
});

it('moderates guest comments same as authenticated', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'verdict' => 'approve',
                    'confidence' => 0.92,
                    'reason' => 'Clean',
                    'categories' => [],
                ])]],
            ],
        ]),
    ]);

    $comment = Comment::create([
        'article_id' => $this->article->id,
        'user_id' => null,
        'guest_name' => 'Guest User',
        'guest_email' => 'guest@example.com',
        'content' => 'Nice post!',
        'status' => 'pending',
    ]);

    $comment->refresh();
    expect($comment->status->getValue())->toBe('approved');
});

it('always creates comment even when marked spam', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'verdict' => 'spam',
                    'confidence' => 1.0,
                    'reason' => 'Definitely spam',
                    'categories' => ['spam'],
                ])]],
            ],
        ]),
    ]);

    $comment = Comment::create([
        'article_id' => $this->article->id,
        'user_id' => $this->admin->id,
        'content' => 'Spammy content',
        'status' => 'pending',
    ]);

    expect(Comment::count())->toBe(1)
        ->and($comment->exists)->toBeTrue();
});

it('moderation threshold setting affects decisions', function () {
    Setting::set('ai.moderation_threshold', '0.5');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'verdict' => 'approve',
                    'confidence' => 0.6,
                    'reason' => 'Borderline',
                    'categories' => [],
                ])]],
            ],
        ]),
    ]);

    $comment = Comment::create([
        'article_id' => $this->article->id,
        'user_id' => $this->admin->id,
        'content' => 'Borderline comment',
        'status' => 'pending',
    ]);

    $comment->refresh();
    expect($comment->status->getValue())->toBe('approved');
});

// --- Observer registration ---

it('registers CommentModerationObserver in AiServiceProvider', function () {
    $observers = Comment::getEventDispatcher()->getListeners('eloquent.created: '.Comment::class);

    expect($observers)->not->toBeEmpty();
});

// --- Translations ---

it('has all required moderation translations', function () {
    $frPath = lang_path('fr.json');
    $enPath = lang_path('en.json');

    $fr = json_decode(file_get_contents($frPath), true);
    $en = json_decode(file_get_contents($enPath), true);

    $keys = [
        'Modération automatique',
        "Commentaire approuvé automatiquement par l'IA.",
        "Commentaire marqué comme spam par l'IA.",
        'Commentaire signalé pour révision manuelle.',
        'Seuil de confiance',
    ];

    foreach ($keys as $key) {
        expect($fr)->toHaveKey($key)
            ->and($en)->toHaveKey($key);
    }
});
