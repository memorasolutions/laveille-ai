<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\SaaS\Http\Controllers\StripeWebhookController;
use Modules\SaaS\Notifications\PaymentFailedNotification;
use Modules\SaaS\Notifications\PaymentSucceededNotification;
use Modules\SaaS\Notifications\SubscriptionCancelledNotification;
use Modules\SaaS\Notifications\TrialEndingNotification;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('sends payment succeeded notification on invoice success webhook', function () {
    Notification::fake();

    $user = User::factory()->create(['stripe_id' => 'cus_test_success']);

    $payload = [
        'data' => [
            'object' => [
                'id' => 'in_test_123',
                'customer' => 'cus_test_success',
                'amount_paid' => 2999,
            ],
        ],
    ];

    $controller = new StripeWebhookController;
    $method = new ReflectionMethod($controller, 'handleInvoicePaymentSucceeded');
    $method->invoke($controller, $payload);

    Notification::assertSentTo($user, PaymentSucceededNotification::class);
});

it('sends payment failed notification on invoice failure webhook', function () {
    Notification::fake();

    $user = User::factory()->create(['stripe_id' => 'cus_test_fail']);

    $payload = [
        'data' => [
            'object' => [
                'id' => 'in_fail_456',
                'customer' => 'cus_test_fail',
            ],
        ],
    ];

    $controller = new StripeWebhookController;
    $method = new ReflectionMethod($controller, 'handleInvoicePaymentFailed');
    $method->invoke($controller, $payload);

    Notification::assertSentTo($user, PaymentFailedNotification::class);
});

it('sends subscription cancelled notification on deletion webhook', function () {
    Notification::fake();

    $user = User::factory()->create(['stripe_id' => 'cus_test_cancel']);

    $payload = [
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [
                'id' => 'sub_cancel_789',
                'customer' => 'cus_test_cancel',
                'current_period_end' => now()->addDays(30)->timestamp,
                'items' => ['data' => [['price' => ['id' => 'price_test']]]],
            ],
        ],
    ];

    $controller = new StripeWebhookController;
    $method = new ReflectionMethod($controller, 'handleCustomerSubscriptionDeleted');
    $method->invoke($controller, $payload);

    Notification::assertSentTo($user, SubscriptionCancelledNotification::class);
});

it('trial ending notification contains correct subject and link', function () {
    $notification = new TrialEndingNotification('2026-03-15');

    $mail = $notification->toMail(User::factory()->make());

    expect($mail->subject)->toBe('Fin de la période d\'essai')
        ->and($mail->actionUrl)->toContain('/pricing');
});

it('payment failed notification contains invoice id', function () {
    $notification = new PaymentFailedNotification('in_abc_123');

    $mail = $notification->toMail(User::factory()->make());

    expect($mail->subject)->toBe('Échec de paiement');

    $body = implode(' ', $mail->introLines);
    expect($body)->toContain('in_abc_123');
});

it('payment succeeded notification contains amount when provided', function () {
    $notification = new PaymentSucceededNotification('in_xyz_456', 4999);

    $mail = $notification->toMail(User::factory()->make());

    expect($mail->subject)->toBe('Paiement confirmé');

    $body = implode(' ', $mail->introLines);
    expect($body)->toContain('49.99')
        ->and($body)->toContain('in_xyz_456');
});

it('payment succeeded notification handles zero amount gracefully', function () {
    $notification = new PaymentSucceededNotification('in_zero_789', 0);

    $mail = $notification->toMail(User::factory()->make());

    $body = implode(' ', $mail->introLines);
    expect($body)->toContain('in_zero_789')
        ->and($body)->not->toContain('0.00');
});

it('does not send notification when stripe customer not found', function () {
    Notification::fake();

    $payload = [
        'data' => [
            'object' => [
                'id' => 'in_orphan',
                'customer' => 'cus_nonexistent',
                'amount_paid' => 1000,
            ],
        ],
    ];

    $controller = new StripeWebhookController;
    $method = new ReflectionMethod($controller, 'handleInvoicePaymentSucceeded');
    $method->invoke($controller, $payload);

    Notification::assertNothingSent();
});
