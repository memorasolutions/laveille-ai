<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Authors\Mail\NewsletterConfirmationMail;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorProfile(): AuthorProfile
{
    $user = User::factory()->create();
    $slug = 'test-author-'.strtolower(Str::random(6));

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => $slug,
        'display_name' => 'Test Author',
        'tier' => 'free',
    ]);
}

it('subscribes a new email and queues confirmation mail', function () {
    Mail::fake();
    $profile = makeAuthorProfile();
    $email = 'subscribe-'.Str::random(4).'@example.com';

    $response = $this->postJson("/auteur/{$profile->slug}/newsletter/subscribe", [
        'email' => $email,
        'consent' => '1',
    ]);

    $response->assertStatus(200)->assertJson(['ok' => true]);
    $this->assertDatabaseHas('author_subscribers', [
        'email' => $email,
        'author_profile_id' => $profile->id,
        'confirmed_at' => null,
    ]);

    $sub = AuthorSubscriber::where('email', $email)->first();
    expect($sub->confirmation_token)->not->toBeNull();

    Mail::assertQueued(NewsletterConfirmationMail::class);
});

it('rejects honeypot website filled silently', function () {
    Mail::fake();
    $profile = makeAuthorProfile();

    $response = $this->postJson("/auteur/{$profile->slug}/newsletter/subscribe", [
        'email' => 'spam@example.com',
        'consent' => '1',
        'website' => 'http://spambot.example',
    ]);

    $response->assertStatus(200)->assertJson(['ok' => true]);
    $this->assertDatabaseMissing('author_subscribers', ['email' => 'spam@example.com']);
    Mail::assertNothingQueued();
});

it('idempotent: re-subscribe unconfirmed same email regenerates token without duplicate row', function () {
    Mail::fake();
    $profile = makeAuthorProfile();
    $email = 'repeat-'.Str::random(4).'@example.com';

    $this->postJson("/auteur/{$profile->slug}/newsletter/subscribe", ['email' => $email, 'consent' => '1'])->assertOk();
    $this->postJson("/auteur/{$profile->slug}/newsletter/subscribe", ['email' => $email, 'consent' => '1'])->assertOk();

    expect(AuthorSubscriber::where('author_profile_id', $profile->id)->count())->toBe(1);
    Mail::assertQueued(NewsletterConfirmationMail::class, 2);
});

it('confirms subscription via signed URL', function () {
    $profile = makeAuthorProfile();
    $token = Str::random(40);
    $subscriber = AuthorSubscriber::create([
        'author_profile_id' => $profile->id,
        'email' => 'confirm@example.com',
        'confirmation_token' => $token,
        'confirmed_at' => null,
    ]);

    $url = URL::signedRoute('authors.newsletter.confirm', [
        'slug' => $profile->slug,
        'token' => $token,
    ]);

    $this->get($url)->assertStatus(200);

    $subscriber->refresh();
    expect($subscriber->confirmed_at)->not->toBeNull();
});

it('unsubscribes via signed URL', function () {
    $profile = makeAuthorProfile();
    $token = Str::random(40);
    $subscriber = AuthorSubscriber::create([
        'author_profile_id' => $profile->id,
        'email' => 'unsubscribe@example.com',
        'confirmation_token' => $token,
        'confirmed_at' => now(),
    ]);

    $url = URL::signedRoute('authors.newsletter.unsubscribe', [
        'slug' => $profile->slug,
        'token' => $token,
    ]);

    $this->get($url)->assertStatus(200);

    $subscriber->refresh();
    expect($subscriber->unsubscribed_at)->not->toBeNull();
});

it('rejects subscribe without consent', function () {
    $profile = makeAuthorProfile();

    $this->postJson("/auteur/{$profile->slug}/newsletter/subscribe", [
        'email' => 'no-consent@example.com',
    ])->assertStatus(422);
});

it('throttles subscribe to 5 per minute', function () {
    Mail::fake();
    $profile = makeAuthorProfile();

    for ($i = 0; $i < 5; $i++) {
        $this->postJson("/auteur/{$profile->slug}/newsletter/subscribe", [
            'email' => "throttle{$i}@example.com",
            'consent' => '1',
        ])->assertStatus(200);
    }

    $this->postJson("/auteur/{$profile->slug}/newsletter/subscribe", [
        'email' => 'throttle6@example.com',
        'consent' => '1',
    ])->assertStatus(429);
});

it('rejects subscribe with invalid email', function () {
    $profile = makeAuthorProfile();

    $this->postJson("/auteur/{$profile->slug}/newsletter/subscribe", [
        'email' => 'not-an-email',
        'consent' => '1',
    ])->assertStatus(422);
});
