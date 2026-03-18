<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Ecommerce\Database\Seeders\EcommerceEmailTemplateSeeder;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Ecommerce\Notifications\AbandonedCartNotification;
use Modules\Ecommerce\Notifications\OrderConfirmationNotification;
use Modules\Ecommerce\Notifications\OrderRefundedNotification;
use Modules\Ecommerce\Notifications\OrderShippedNotification;
use Modules\Notifications\Models\EmailTemplate;
use Modules\Notifications\Services\EmailTemplateService;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createEmailTestOrder(array $attributes = []): Order
{
    $user = User::factory()->create();

    return Order::create(array_merge([
        'user_id' => $user->id,
        'order_number' => 'CMD-TEST-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        'status' => 'pending',
        'subtotal' => 99.99,
        'total' => 114.97,
        'shipping_cost' => 0,
        'tax_amount' => 14.98,
        'discount_amount' => 0,
    ], $attributes));
}

function createEmailTestCartWithItems(): array
{
    $user = User::factory()->create();
    $product = Product::create([
        'name' => 'T-shirt test',
        'slug' => 'tshirt-test-'.uniqid(),
        'price' => 29.99,
        'is_active' => true,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'TST-'.uniqid(),
        'price' => 29.99,
        'stock' => 10,
        'is_active' => true,
    ]);
    $cart = Cart::create(['user_id' => $user->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    return [$user, $cart->fresh(['items.variant.product'])];
}

test('seeder creates 4 ecommerce email templates', function () {
    (new EcommerceEmailTemplateSeeder)->run();

    expect(EmailTemplate::where('module', 'ecommerce')->count())->toBe(4);
});

test('seeder is idempotent', function () {
    (new EcommerceEmailTemplateSeeder)->run();
    (new EcommerceEmailTemplateSeeder)->run();

    expect(EmailTemplate::where('module', 'ecommerce')->count())->toBe(4);
});

test('order confirmation uses template when available', function () {
    (new EcommerceEmailTemplateSeeder)->run();

    $user = User::factory()->create();
    $order = createEmailTestOrder(['user_id' => $user->id, 'order_number' => 'CMD-TMPL-001']);

    $notification = new OrderConfirmationNotification($order);
    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class);
    expect($mail->subject)->toContain('CMD-TMPL-001');
    expect($mail->viewData['content'])->toContain('CMD-TMPL-001');
});

test('order confirmation falls back to MailMessage without template', function () {
    $user = User::factory()->create();
    $order = createEmailTestOrder(['user_id' => $user->id, 'order_number' => 'CMD-FALL-001']);

    $notification = new OrderConfirmationNotification($order);
    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class);
    expect($mail->subject)->toContain('CMD-FALL-001');
    expect($mail->greeting)->toContain($user->name);
});

test('order shipped notification includes tracking in template', function () {
    (new EcommerceEmailTemplateSeeder)->run();

    $user = User::factory()->create();
    $order = createEmailTestOrder(['user_id' => $user->id, 'tracking_number' => 'TRACK-ABC123']);

    $notification = new OrderShippedNotification($order);
    $mail = $notification->toMail($user);

    expect($mail->subject)->toContain($order->order_number);
    expect($mail->viewData['content'])->toContain('TRACK-ABC123');
});

test('order refunded notification includes amount', function () {
    (new EcommerceEmailTemplateSeeder)->run();

    $user = User::factory()->create();
    $order = createEmailTestOrder(['user_id' => $user->id, 'order_number' => 'CMD-REF-001']);

    $notification = new OrderRefundedNotification($order, 49.99);
    $mail = $notification->toMail($user);

    expect($mail->subject)->toContain('CMD-REF-001');
    expect($mail->viewData['content'])->toContain('49.99');
});

test('abandoned cart notification uses template', function () {
    (new EcommerceEmailTemplateSeeder)->run();

    [$user, $cart] = createEmailTestCartWithItems();

    $notification = new AbandonedCartNotification($cart, 1);
    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class);
    expect($mail->subject)->not()->toBeEmpty();
});

test('all 4 templates render without leftover placeholders', function () {
    (new EcommerceEmailTemplateSeeder)->run();

    $service = app(EmailTemplateService::class);

    $dummyData = [
        'user' => ['name' => 'Jean Dupont', 'email' => 'jean@test.com'],
        'app' => ['name' => 'TestApp', 'url' => 'https://test.com'],
        'order' => ['number' => 'CMD-001', 'total' => '99.99', 'date' => '18/03/2026', 'url' => 'https://test.com/orders/1', 'tracking' => 'TRACK123'],
        'refund' => ['amount' => '49.99'],
        'currency' => 'CAD',
        'cart' => ['items' => 'T-shirt, Jeans', 'item_count' => '2', 'total' => '109.98', 'url' => 'https://test.com/cart'],
    ];

    $slugs = [
        'ecommerce_order_confirmation',
        'ecommerce_order_shipped',
        'ecommerce_order_refunded',
        'ecommerce_abandoned_cart',
    ];

    foreach ($slugs as $slug) {
        $rendered = $service->render($slug, $dummyData);
        expect($rendered)->not()->toBeNull("Template {$slug} not found");
        expect($rendered['body_html'])->not()->toContain('{{');
        expect($rendered['subject'])->not()->toContain('{{');
    }
});
