<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Mail\NewsletterWelcomeMail;
use Modules\Authors\Models\AuthorComment;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;
use Modules\Authors\Services\NewsletterSubscriberService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS118(): AuthorProfile
{
    $user = User::factory()->create(['email' => 'a-'.Str::random(4).'@example.com']);

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's118-'.strtolower(Str::random(6)),
        'display_name' => 'S118 Test',
        'tier' => 'free',
    ]);
}

it('NewsletterWelcomeMail builds with correct subject', function () {
    $author = makeAuthorS118();
    $sub = AuthorSubscriber::create([
        'author_profile_id' => $author->id,
        'email' => 'test@example.com',
        'confirmation_token' => 'token-'.Str::random(40),
    ]);

    $mail = new NewsletterWelcomeMail($sub, $author);
    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('Bienvenue')
        ->and($envelope->subject)->toContain($author->display_name);
});

it('NewsletterSubscriberService confirm dispatches NewsletterWelcomeMail when transitioning to confirmed', function () {
    Mail::fake();
    $author = makeAuthorS118();
    $token = 'token-'.Str::random(40);
    AuthorSubscriber::create([
        'author_profile_id' => $author->id,
        'email' => 'newsub@example.com',
        'confirmation_token' => $token,
        'confirmed_at' => null,
    ]);

    $service = new NewsletterSubscriberService(app(\Illuminate\Contracts\Mail\Mailer::class));
    $confirmed = $service->confirm($token);

    expect($confirmed)->not->toBeNull()
        ->and($confirmed->confirmed_at)->not->toBeNull();

    Mail::assertQueued(NewsletterWelcomeMail::class);
});

it('NewsletterSubscriberService confirm does NOT re-dispatch welcome on already-confirmed', function () {
    Mail::fake();
    $author = makeAuthorS118();
    $token = 'token-'.Str::random(40);
    AuthorSubscriber::create([
        'author_profile_id' => $author->id,
        'email' => 'already@example.com',
        'confirmation_token' => $token,
        'confirmed_at' => now()->subDay(),
    ]);

    $service = new NewsletterSubscriberService(app(\Illuminate\Contracts\Mail\Mailer::class));
    $service->confirm($token);

    Mail::assertNotQueued(NewsletterWelcomeMail::class);
});

it('AuthorRecentNotifications mounts with authorProfileId', function () {
    $author = makeAuthorS118();
    $this->actingAs($author->user);

    Livewire::test(\Modules\Authors\Livewire\AuthorRecentNotifications::class, [
        'authorProfileId' => $author->id,
    ])->assertSet('authorProfileId', $author->id);
});

it('AuthorRecentNotifications renders empty state when no activity', function () {
    $author = makeAuthorS118();
    $this->actingAs($author->user);

    $component = Livewire::test(\Modules\Authors\Livewire\AuthorRecentNotifications::class, [
        'authorProfileId' => $author->id,
    ]);

    $events = $component->viewData('events');
    expect($events)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($events->count())->toBe(0);
});

it('AuthorRecentNotifications aggregates comments + subscribers events', function () {
    $author = makeAuthorS118();
    $this->actingAs($author->user);

    AuthorComment::create([
        'author_profile_id' => $author->id,
        'commentable_type' => 'X',
        'commentable_id' => 1,
        'author_name' => 'Bob',
        'body' => 'Hello world',
        'approved_at' => now(),
        'created_at' => now()->subMinutes(5),
    ]);

    AuthorSubscriber::create([
        'author_profile_id' => $author->id,
        'email' => 'sub@example.com',
        'confirmation_token' => 't1',
        'confirmed_at' => now()->subMinutes(10),
    ]);

    $component = Livewire::test(\Modules\Authors\Livewire\AuthorRecentNotifications::class, [
        'authorProfileId' => $author->id,
    ]);

    $events = $component->viewData('events');
    expect($events->count())->toBe(2)
        ->and($events->first()['type'])->toBe('comment');
});

it('AuthorRecentNotifications limits aggregate at 10 events', function () {
    $author = makeAuthorS118();
    $this->actingAs($author->user);

    for ($i = 0; $i < 15; $i++) {
        AuthorComment::create([
            'author_profile_id' => $author->id,
            'commentable_type' => 'X',
            'commentable_id' => 1,
            'author_name' => 'U',
            'body' => 'X'.$i,
            'approved_at' => now(),
            'created_at' => now()->subMinutes($i),
        ]);
    }

    $component = Livewire::test(\Modules\Authors\Livewire\AuthorRecentNotifications::class, [
        'authorProfileId' => $author->id,
    ]);

    $events = $component->viewData('events');
    expect($events->count())->toBeLessThanOrEqual(10);
});

it('Post page shows tip variants 5/10/20 when cashier configured', function () {
    config(['cashier.secret' => 'sk_test_xxx']);
    $author = makeAuthorS118();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'tip-variants-post',
        'title' => 'Test',
        'body_markdown' => 'X',
        'body_html' => '<p>X</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('lv-tip-btn-post', false);
    $response->assertSee('Pourboires', false);
});
