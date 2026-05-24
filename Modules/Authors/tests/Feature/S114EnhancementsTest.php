<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Authors\Mail\TipReceivedNotificationMail;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorPushSubscription;
use Modules\Authors\Services\WebPushService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS114(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's114-'.strtolower(Str::random(6)),
        'display_name' => 'S114 Test',
        'tier' => 'free',
    ]);
}

it('TipReceivedNotificationMail builds with correct subject and amount', function () {
    $author = makeAuthorS114();
    $mail = new TipReceivedNotificationMail($author, 500, 'cad', 'tipper@example.com');

    $envelope = $mail->envelope();
    expect($envelope->subject)->toContain('5,00')->toContain('CAD');
});

it('WebPushService subscribe creates new push subscription with valid keys', function () {
    $author = makeAuthorS114();
    $service = new WebPushService();

    $subscription = $service->subscribe($author, [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        'keys' => [
            'p256dh' => 'public-key-base64',
            'auth' => 'auth-token-base64',
        ],
    ]);

    expect($subscription)->toBeInstanceOf(AuthorPushSubscription::class);
    expect($subscription->endpoint)->toBe('https://fcm.googleapis.com/fcm/send/abc123');
    expect($subscription->author_profile_id)->toBe($author->id);
});

it('WebPushService subscribe throws on missing endpoint', function () {
    $author = makeAuthorS114();
    $service = new WebPushService();

    expect(fn () => $service->subscribe($author, ['keys' => ['p256dh' => 'x', 'auth' => 'y']]))
        ->toThrow(InvalidArgumentException::class);
});

it('WebPushService subscribe throws on missing keys', function () {
    $author = makeAuthorS114();
    $service = new WebPushService();

    expect(fn () => $service->subscribe($author, ['endpoint' => 'https://example.com', 'keys' => []]))
        ->toThrow(InvalidArgumentException::class);
});

it('WebPushService unsubscribe deletes subscription by endpoint', function () {
    $author = makeAuthorS114();
    AuthorPushSubscription::create([
        'author_profile_id' => $author->id,
        'endpoint' => 'https://test.endpoint.com/abc',
        'public_key' => 'x',
        'auth_token' => 'y',
    ]);

    $service = new WebPushService();
    expect($service->unsubscribe('https://test.endpoint.com/abc'))->toBeTrue();
    expect($service->unsubscribe('https://nope.com'))->toBeFalse();
});

it('WebPushService isEnabled false without VAPID keys', function () {
    config(['services.webpush.vapid_public_key' => null]);
    $service = new WebPushService();
    expect($service->isEnabled())->toBeFalse();
});

it('AuthorDashboard switchTab accepts affiliates + historique tabs', function () {
    $component = \Livewire\Livewire::test(\Modules\Authors\Livewire\AuthorDashboard::class)
        ->call('switchTab', 'affiliates')
        ->assertSet('activeTab', 'affiliates')
        ->call('switchTab', 'historique')
        ->assertSet('activeTab', 'historique')
        ->call('switchTab', 'invalid-tab')
        ->assertSet('activeTab', 'historique');
});

it('AuthorsReportCommand runs without error and shows authors count', function () {
    $author = makeAuthorS114();
    AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'test-report',
        'title' => 'Test',
        'body_markdown' => 'X',
        'body_html' => '<p>X</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);

    $this->artisan('authors:report')
        ->expectsOutputToContain('📊 Rapport Authors Memora')
        ->expectsOutputToContain('Total auteurs actifs')
        ->assertSuccessful();
});

it('Tip button on post.blade.php shown when cashier configured', function () {
    config(['cashier.secret' => 'sk_test_xxx']);
    $author = makeAuthorS114();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'tip-post',
        'title' => 'Tip Post',
        'body_markdown' => 'X',
        'body_html' => '<p>X</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('lv-tip-btn-post', false);
});

it('Tip received webhook handler queues TipReceivedNotificationMail', function () {
    Mail::fake();
    $author = makeAuthorS114();

    $controller = new \Modules\Authors\Http\Controllers\StripeWebhookController();
    $reflection = new \ReflectionClass($controller);
    $method = $reflection->getMethod('handleCheckoutSessionCompleted');
    $method->setAccessible(true);

    $payload = [
        'data' => [
            'object' => [
                'metadata' => [
                    'tip_type' => 'one-time',
                    'author_profile_id' => (string) $author->id,
                    'author_slug' => $author->slug,
                ],
                'amount_total' => 500,
                'currency' => 'cad',
                'customer_details' => ['email' => 'tipper@example.com'],
            ],
        ],
    ];

    $method->invoke($controller, $payload);

    Mail::assertQueued(TipReceivedNotificationMail::class);
});
