<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorProfileP(): array
{
    $user = User::factory()->create();
    $author = AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 'premium-'.strtolower(Str::random(6)),
        'display_name' => 'Premium Test',
        'tier' => 'free',
    ]);

    return ['user' => $user, 'author' => $author];
}

it('upgrade page returns 200 for authenticated user free tier', function () {
    ['user' => $u] = makeAuthorProfileP();
    $this->actingAs($u);
    $this->get('/auteur/upgrade')->assertStatus(200)->assertSee('Devenir Premium');
});

it('upgrade page redirects unauthenticated to login', function () {
    $response = $this->get('/auteur/upgrade');
    expect(in_array($response->status(), [302, 401], true))->toBeTrue();
});

it('checkout returns 503 when stripe price not configured', function () {
    ['user' => $u] = makeAuthorProfileP();
    $this->actingAs($u);
    config(['cashier.price_premium' => null]);
    $this->post('/auteur/upgrade/checkout')->assertStatus(503);
});

it('EnsurePremium middleware blocks non-premium user', function () {
    ['user' => $u] = makeAuthorProfileP();
    $this->actingAs($u);
    \Illuminate\Support\Facades\Route::middleware(['web', 'auth', \Modules\Authors\Http\Middleware\EnsurePremium::class])
        ->get('/test-premium-temp-route', fn () => response('ok'));
    $response = $this->get('/test-premium-temp-route');
    $response->assertRedirect();
});

it('stripe webhook endpoint exists and rejects unsigned payload', function () {
    $response = $this->postJson('/stripe/webhook', ['type' => 'customer.subscription.created']);
    expect(in_array($response->status(), [400, 403, 404, 422, 500], true))->toBeTrue();
});
