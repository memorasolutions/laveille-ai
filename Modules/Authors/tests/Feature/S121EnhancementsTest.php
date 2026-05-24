<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Livewire\CommentModerationQueue;
use Modules\Authors\Mail\SubscriberDigestMail;
use Modules\Authors\Models\AuthorComment;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;
use Modules\Authors\Services\OgImageService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS121(): AuthorProfile
{
    $user = User::factory()->create(['email' => 'a-'.Str::random(4).'@example.com']);

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's121-'.strtolower(Str::random(6)),
        'display_name' => 'S121 Test',
        'tier' => 'free',
    ]);
}

function makeS121Post(AuthorProfile $author, array $overrides = []): AuthorPost
{
    return AuthorPost::create(array_merge([
        'author_profile_id' => $author->id,
        'slug' => 'post-'.strtolower(Str::random(6)),
        'title' => 'Post '.Str::random(4),
        'body_markdown' => 'Lorem ipsum dolor sit amet consectetur.',
        'body_html' => '<p>Lorem ipsum dolor sit amet consectetur.</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
        'tags' => [],
        'reading_time' => 1,
        'views_count' => 0,
    ], $overrides));
}

it('OgImageService is enabled when GD present', function () {
    $service = app(OgImageService::class);
    expect($service->isEnabled())->toBe(function_exists('imagecreatetruecolor'));
});

it('OgImageService generates a PNG file', function () {
    $service = app(OgImageService::class);
    if (! $service->isEnabled()) {
        $this->markTestSkipped('GD not available');
    }
    $author = makeAuthorS121();
    $post = makeS121Post($author, ['title' => 'Mon super article sur l\'IA']);

    $path = $service->generate($post, $author);

    expect($path)->not->toBeNull()
        ->and(is_file($path))->toBeTrue()
        ->and(getimagesize($path)[0])->toBe(1200)
        ->and(getimagesize($path)[1])->toBe(630);
});

it('OgImageService url returns asset path with hash', function () {
    $service = app(OgImageService::class);
    $author = makeAuthorS121();
    $post = makeS121Post($author);

    expect($service->url($post, $author))->toContain('og-images/')
        ->and($service->url($post, $author))->toEndWith('.png');
});

it('OG image route returns PNG image', function () {
    $service = app(OgImageService::class);
    if (! $service->isEnabled()) {
        $this->markTestSkipped('GD not available');
    }
    $author = makeAuthorS121();
    $post = makeS121Post($author);

    $response = $this->get('/og-image/'.$author->slug.'/'.$post->slug.'.png');
    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('image/png');
});

it('Post page og:image meta uses dynamic route', function () {
    $author = makeAuthorS121();
    $post = makeS121Post($author);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('/og-image/'.$author->slug.'/'.$post->slug.'.png', false);
    $response->assertSee('og:image:width', false);
});

it('SubscriberDigestMail builds with correct subject', function () {
    $author = makeAuthorS121();
    $sub = AuthorSubscriber::create([
        'author_profile_id' => $author->id,
        'email' => 'sub@example.com',
        'confirmation_token' => 'tok-'.Str::random(40),
        'confirmed_at' => now()->subWeek(),
    ]);
    $posts = collect([makeS121Post($author)]);

    $mail = new SubscriberDigestMail($sub, $author, $posts);
    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('nouveau')
        ->and($envelope->subject)->toContain($author->display_name);
});

it('SendSubscriberDigestCommand queues mail + updates last_digest_at when posts since last', function () {
    Mail::fake();
    $author = makeAuthorS121();
    $sub = AuthorSubscriber::create([
        'author_profile_id' => $author->id,
        'email' => 'newsub@example.com',
        'confirmation_token' => 'tok-'.Str::random(40),
        'confirmed_at' => now()->subWeek(),
        'last_digest_at' => now()->subWeek(),
    ]);
    makeS121Post($author, ['published_at' => now()->subDay()]);

    $this->artisan('authors:subscriber-digest')->assertSuccessful();

    Mail::assertQueued(SubscriberDigestMail::class);
    expect($sub->fresh()->last_digest_at->isToday())->toBeTrue();
});

it('SendSubscriberDigestCommand skips subscriber with no new posts', function () {
    Mail::fake();
    $author = makeAuthorS121();
    AuthorSubscriber::create([
        'author_profile_id' => $author->id,
        'email' => 'nonew@example.com',
        'confirmation_token' => 'tok-'.Str::random(40),
        'confirmed_at' => now()->subWeek(),
        'last_digest_at' => now()->subMinute(),
    ]);
    makeS121Post($author, ['published_at' => now()->subWeek()]);

    $this->artisan('authors:subscriber-digest')->assertSuccessful();

    Mail::assertNotQueued(SubscriberDigestMail::class);
});

it('CommentModerationQueue renders with status counts', function () {
    $author = makeAuthorS121();
    $post = makeS121Post($author);

    AuthorComment::create([
        'author_profile_id' => $author->id,
        'commentable_type' => AuthorPost::class,
        'commentable_id' => $post->id,
        'author_name' => 'Bob',
        'body' => 'Premier commentaire en attente',
        'spam_score' => 0,
    ]);

    $component = Livewire::test(CommentModerationQueue::class);
    $component->assertSet('status', 'pending');
    expect($component->viewData('counts')['pending'])->toBe(1);
});

it('CommentModerationQueue approve action sets approved_at', function () {
    $author = makeAuthorS121();
    $post = makeS121Post($author);
    $comment = AuthorComment::create([
        'author_profile_id' => $author->id,
        'commentable_type' => AuthorPost::class,
        'commentable_id' => $post->id,
        'author_name' => 'Alice',
        'body' => 'Commentaire à approuver',
        'spam_score' => 0,
    ]);

    Livewire::test(CommentModerationQueue::class)
        ->call('approve', $comment->id);

    expect($comment->fresh()->approved_at)->not->toBeNull();
});

it('CommentModerationQueue markSpam flags comment', function () {
    $author = makeAuthorS121();
    $post = makeS121Post($author);
    $comment = AuthorComment::create([
        'author_profile_id' => $author->id,
        'commentable_type' => AuthorPost::class,
        'commentable_id' => $post->id,
        'author_name' => 'Spammer',
        'body' => 'Buy now cheap stuff',
        'spam_score' => 0,
    ]);

    Livewire::test(CommentModerationQueue::class)
        ->call('markSpam', $comment->id);

    $fresh = $comment->fresh();
    expect($fresh->flagged_at)->not->toBeNull()
        ->and((int) $fresh->spam_score)->toBe(100);
});

it('Comment moderation backoffice route requires authentication', function () {
    $response = $this->get('/backoffice/authors/comments');
    expect($response->status())->toBeIn([302, 401, 403]);
});
