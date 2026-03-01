<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WelcomeNewsletterNotification;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('subscriber model exists and can be created', function () {
    $subscriber = Subscriber::create([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    expect($subscriber->email)->toBe('test@example.com');
    expect($subscriber->token)->toHaveLength(64);
    expect($subscriber->isConfirmed())->toBeFalse();
    expect($subscriber->isActive())->toBeFalse();
});

it('subscribe route sends notification and creates subscriber', function () {
    Notification::fake();

    $this->post('/newsletter/subscribe', ['email' => 'new@example.com', 'name' => 'New User'])
        ->assertRedirect();

    $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'new@example.com']);
    Notification::assertSentOnDemand(WelcomeNewsletterNotification::class);
});

it('subscribe with existing email does not duplicate', function () {
    Notification::fake();

    Subscriber::create(['email' => 'exists@example.com']);

    $this->post('/newsletter/subscribe', ['email' => 'exists@example.com']);

    expect(Subscriber::where('email', 'exists@example.com')->count())->toBe(1);
});

it('confirmation link confirms subscriber', function () {
    $subscriber = Subscriber::create(['email' => 'confirm@example.com']);

    $this->get(route('newsletter.confirm', $subscriber->token))
        ->assertRedirect('/');

    expect($subscriber->fresh()->isConfirmed())->toBeTrue();
});

it('unsubscribe link unsubscribes subscriber', function () {
    $subscriber = Subscriber::create([
        'email' => 'unsub@example.com',
        'confirmed_at' => now(),
    ]);

    $this->get(route('newsletter.unsubscribe', $subscriber->token))
        ->assertRedirect('/');

    expect($subscriber->fresh()->isActive())->toBeFalse();
    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});

it('active scope returns only confirmed and not unsubscribed', function () {
    Subscriber::create(['email' => 'pending@example.com']);
    Subscriber::create(['email' => 'active@example.com', 'confirmed_at' => now()]);
    Subscriber::create(['email' => 'unsub@example.com', 'confirmed_at' => now(), 'unsubscribed_at' => now()]);

    expect(Subscriber::active()->count())->toBe(1);
});

it('admin newsletter page loads for admin user', function () {
    $user = \App\Models\User::factory()->create();
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']);
    $user->assignRole('admin');

    $this->actingAs($user)->get('/admin/newsletter')
        ->assertStatus(200)
        ->assertSee('Liste des abonnés');
});

it('subscribe validates email format', function () {
    $this->post('/newsletter/subscribe', ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});
