<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Authors\Mail\WebmentionReceivedNotificationMail;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorWebmention;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS117(): AuthorProfile
{
    $user = User::factory()->create(['email' => 'author-'.Str::random(4).'@example.com']);

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's117-'.strtolower(Str::random(6)),
        'display_name' => 'S117 Test',
        'tier' => 'free',
    ]);
}

it('AuthorWebmentionObserver dispatches notif on verified_at transition NULL→datetime', function () {
    Mail::fake();
    $author = makeAuthorS117();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'wm-notif-test',
        'title' => 'Test',
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
        'verified_at' => null,
    ]);
    Mail::assertNothingQueued();
    $wm->update(['verified_at' => now()]);
    Mail::assertQueued(WebmentionReceivedNotificationMail::class);
});

it('AuthorWebmentionObserver does NOT dispatch on unrelated update', function () {
    Mail::fake();
    $author = makeAuthorS117();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'wm-no-notif',
        'title' => 'X',
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
        'verified_at' => now(),
    ]);
    Mail::assertNothingQueued();
    $wm->update(['source_excerpt' => 'New excerpt']);
    Mail::assertNothingQueued();
});

it('AuthorWebmentionObserver does NOT dispatch when verified_at unset', function () {
    Mail::fake();
    $author = makeAuthorS117();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'wm-unverify',
        'title' => 'X',
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
        'verified_at' => now(),
    ]);
    $wm->update(['verified_at' => null]);
    Mail::assertNothingQueued();
});

it('authors:webmention-send CLI fails on unknown post-id', function () {
    $this->artisan('authors:webmention-send', ['post-id' => 99999])
        ->expectsOutputToContain('not found')
        ->assertFailed();
});

it('authors:webmention-send CLI warns on post without external links', function () {
    $author = makeAuthorS117();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'no-links',
        'title' => 'X',
        'body_markdown' => 'X',
        'body_html' => '<p>No external links here</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);
    $this->artisan('authors:webmention-send', ['post-id' => $post->id])
        ->expectsOutputToContain('No external URLs')
        ->assertSuccessful();
});

it('mini-site shows tip button with Pourboires region when cashier configured', function () {
    config(['cashier.secret' => 'sk_test_xxx']);
    $author = makeAuthorS117();
    $response = $this->get('/@'.$author->slug);
    $response->assertSee('lv-tip-btn', false);
    $response->assertSee('Pourboires', false);
});

it('mini-site renders status 200 (no regression on search button absent)', function () {
    $author = makeAuthorS117();
    $response = $this->get('/@'.$author->slug);
    $response->assertStatus(200);
});

it('AuthorsWebmentionSendCommand processes post with external URL', function () {
    \Illuminate\Support\Facades\Http::fake([
        'external.com/*' => \Illuminate\Support\Facades\Http::response('', 200, [
            'Link' => '<https://external.com/webmention>; rel="webmention"',
        ]),
    ]);
    $author = makeAuthorS117();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'with-links',
        'title' => 'X',
        'body_markdown' => 'X',
        'body_html' => '<p><a href="https://external.com/post">link</a></p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);
    $this->artisan('authors:webmention-send', ['post-id' => $post->id])
        ->expectsOutputToContain('Sending webmentions')
        ->assertSuccessful();
});
