<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Authors\Mail\AuthorWeeklyDigestMail;
use Modules\Authors\Mail\WebmentionReceivedNotificationMail;
use Modules\Authors\Models\AuthorComment;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorWebmention;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS116(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's116-'.strtolower(Str::random(6)),
        'display_name' => 'S116 Test',
        'tier' => 'free',
    ]);
}

it('EnsureSuperAdmin middleware blocks unauthenticated', function () {
    $path = '/test-su-admin-blocked-'.uniqid();
    \Illuminate\Support\Facades\Route::get($path, fn () => 'ok')
        ->middleware(['web', \Modules\Authors\Http\Middleware\EnsureSuperAdmin::class]);

    $response = $this->get($path);
    expect(in_array($response->status(), [302, 401, 403], true))->toBeTrue();
});

it('EnsureSuperAdmin middleware blocks non-admin user', function () {
    // Force testing env to disable local id=1 fallback bypass
    app()->detectEnvironment(fn () => 'production');

    $path = '/test-su-admin-nonadmin-'.uniqid();
    User::factory()->create();
    User::factory()->create();
    $user = User::factory()->create();
    $this->actingAs($user);

    \Illuminate\Support\Facades\Route::get($path, fn () => 'ok-super-admin')
        ->middleware(['web', \Modules\Authors\Http\Middleware\EnsureSuperAdmin::class]);

    $response = $this->get($path);
    expect($response->status())->toBe(403);
});

it('WebmentionReceivedNotificationMail builds with correct subject', function () {
    $author = makeAuthorS116();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'wm-mail',
        'title' => 'Test article notif',
        'body_markdown' => 'X',
        'body_html' => '<p>X</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);
    $wm = AuthorWebmention::create([
        'author_post_id' => $post->id,
        'target_url' => 'x',
        'source_url' => 'y',
        'received_at' => now(),
    ]);

    $mail = new WebmentionReceivedNotificationMail($wm, $post, $author);
    $envelope = $mail->envelope();

    expect($envelope->subject)
        ->toContain('Test article notif')
        ->toContain('mention');
});

it('AuthorWeeklyDigestMail builds with stats array', function () {
    $author = makeAuthorS116();
    $stats = [
        'posts_published' => 2,
        'comments_received' => 5,
        'subscribers_gained' => 3,
        'webmentions_verified' => 1,
        'tips_count' => 0,
        'affiliate_clicks' => 10,
    ];

    $mail = new AuthorWeeklyDigestMail($author, $stats, now()->subDays(7), now());

    expect($mail->stats['posts_published'])->toBe(2)
        ->and($mail->author->id)->toBe($author->id);
});

it('AuthorsWeeklyDigestCommand sends digest only when activity > 0', function () {
    Mail::fake();
    $author = makeAuthorS116();

    for ($i = 0; $i < 5; $i++) {
        AuthorComment::create([
            'author_profile_id' => $author->id,
            'commentable_type' => 'X',
            'commentable_id' => 1,
            'author_name' => 'User'.$i,
            'body' => 'Comment '.$i,
            'approved_at' => now(),
            'created_at' => now()->subDays(2),
        ]);
    }

    $this->artisan('authors:digest')
        ->expectsOutputToContain('Digest envoyé')
        ->assertSuccessful();

    Mail::assertQueued(AuthorWeeklyDigestMail::class);
});

it('AuthorsWeeklyDigestCommand skips author with zero activity', function () {
    Mail::fake();
    makeAuthorS116();

    $this->artisan('authors:digest')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('AuthorsWeeklyDigestCommand --author-id filters to single author', function () {
    Mail::fake();
    $a1 = makeAuthorS116();
    makeAuthorS116();

    AuthorComment::create([
        'author_profile_id' => $a1->id,
        'commentable_type' => 'X',
        'commentable_id' => 1,
        'author_name' => 'X',
        'body' => 'Test comment',
        'approved_at' => now(),
        'created_at' => now()->subDays(1),
    ]);

    $this->artisan('authors:digest', ['--author-id' => $a1->id])->assertSuccessful();

    Mail::assertQueued(AuthorWeeklyDigestMail::class, 1);
});

it('mini-site shows tip button gated cashier.secret', function () {
    config(['cashier.secret' => 'sk_test_xxx']);
    $author = makeAuthorS116();

    $response = $this->get('/@'.$author->slug);

    $response->assertSee('lv-tip-btn', false);
});
