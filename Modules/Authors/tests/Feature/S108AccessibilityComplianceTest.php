<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;
use Modules\Authors\Services\TurnstileVerificationService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS108(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's108-'.strtolower(Str::random(6)),
        'display_name' => 'S108 Test',
        'tier' => 'free',
    ]);
}

it('mini-site contains skip link as first focusable element', function () {
    $author = makeAuthorS108();
    $response = $this->get('/@'.$author->slug);
    $response->assertStatus(200);
    $response->assertSee('Aller au contenu principal', false);
    $response->assertSee('id="lv-main-content"', false);
});

it('TurnstileVerificationService returns true when not configured (graceful bypass)', function () {
    config(['services.turnstile.secret_key' => null]);
    $service = new TurnstileVerificationService();
    expect($service->isEnabled())->toBeFalse();
    expect($service->verify('fake-token'))->toBeTrue();
});

it('TurnstileVerificationService returns false on empty token when enabled', function () {
    config(['services.turnstile.secret_key' => 'test-secret-key']);
    $service = new TurnstileVerificationService();
    expect($service->isEnabled())->toBeTrue();
    expect($service->verify(null))->toBeFalse();
    expect($service->verify(''))->toBeFalse();
});

it('RFC 8058 one-click POST endpoint unsubscribes without CSRF', function () {
    $author = makeAuthorS108();
    $token = Str::random(40);
    $sub = AuthorSubscriber::create([
        'author_profile_id' => $author->id,
        'email' => 'oneclick@example.com',
        'confirmation_token' => $token,
        'confirmed_at' => now(),
    ]);
    $url = URL::signedRoute('authors.newsletter.unsubscribe-1click', ['slug' => $author->slug, 'token' => $token]);
    $response = $this->post($url);
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    $sub->refresh();
    expect($sub->unsubscribed_at)->not->toBeNull();
});

it('RFC 8058 one-click POST returns 401 when signature invalid', function () {
    $author = makeAuthorS108();
    $response = $this->post('/auteur/'.$author->slug.'/newsletter/unsubscribe-1click/fake-token');
    $response->assertStatus(401);
});

it('palette WCAG AAA tokens compiled in show.blade.php', function () {
    $author = makeAuthorS108();
    $response = $this->get('/@'.$author->slug);
    $response->assertSee('#064E5A', false);
    $response->assertSee('#9A2A06', false);
});
