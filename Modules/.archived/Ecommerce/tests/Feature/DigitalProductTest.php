<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Ecommerce\Models\DigitalAsset;
use Modules\Ecommerce\Models\DigitalAssetDownload;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Ecommerce\Services\DigitalDownloadService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'view_ecommerce']);
    $role = Role::firstOrCreate(['name' => 'super_admin']);
    $role->syncPermissions(Permission::all());

    $this->user = User::create(['name' => 'Buyer', 'email' => 'buyer@test.com', 'password' => bcrypt('password')]);
    $this->otherUser = User::create(['name' => 'Other', 'email' => 'other@test.com', 'password' => bcrypt('password')]);

    $this->product = Product::create(['name' => 'Ebook', 'slug' => 'ebook-test', 'price' => 29.99]);
    $this->variant = ProductVariant::create(['product_id' => $this->product->id, 'sku' => 'EB-001', 'price' => 29.99, 'stock' => 999, 'is_active' => true]);
});

// --- Model ---

test('digital asset can be created', function () {
    $asset = DigitalAsset::create([
        'product_id' => $this->product->id,
        'file_path' => 'digital/ebook.pdf',
        'original_filename' => 'guide-complet.pdf',
        'file_size' => 1048576,
        'mime_type' => 'application/pdf',
        'download_limit' => 3,
    ]);

    expect($asset->exists)->toBeTrue()
        ->and($asset->is_active)->toBeTrue()
        ->and($asset->download_limit)->toBe(3);
});

test('digital asset belongs to product', function () {
    $asset = DigitalAsset::create([
        'product_id' => $this->product->id,
        'file_path' => 'digital/test.pdf',
        'original_filename' => 'test.pdf',
    ]);

    expect($this->product->digitalAssets)->toHaveCount(1)
        ->and($this->product->digitalAssets->first()->id)->toBe($asset->id);
});

test('active scope filters inactive assets', function () {
    DigitalAsset::create(['product_id' => $this->product->id, 'file_path' => 'a.pdf', 'original_filename' => 'a.pdf', 'is_active' => true]);
    DigitalAsset::create(['product_id' => $this->product->id, 'file_path' => 'b.pdf', 'original_filename' => 'b.pdf', 'is_active' => false]);

    expect(DigitalAsset::active()->count())->toBe(1);
});

// --- Service: canDownload ---

test('canDownload returns true for valid paid order', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'DL-001', 'status' => 'paid',
        'subtotal' => 30, 'total' => 30, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);
    $asset = DigitalAsset::create(['product_id' => $this->product->id, 'file_path' => 'dl.pdf', 'original_filename' => 'dl.pdf']);

    $service = new DigitalDownloadService;
    expect($service->canDownload($asset, $order, $this->user))->toBeTrue();
});

test('canDownload returns false for inactive asset', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'DL-002', 'status' => 'paid',
        'subtotal' => 30, 'total' => 30, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);
    $asset = DigitalAsset::create(['product_id' => $this->product->id, 'file_path' => 'dl.pdf', 'original_filename' => 'dl.pdf', 'is_active' => false]);

    $service = new DigitalDownloadService;
    expect($service->canDownload($asset, $order, $this->user))->toBeFalse();
});

test('canDownload returns false for wrong user', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'DL-003', 'status' => 'paid',
        'subtotal' => 30, 'total' => 30, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);
    $asset = DigitalAsset::create(['product_id' => $this->product->id, 'file_path' => 'dl.pdf', 'original_filename' => 'dl.pdf']);

    $service = new DigitalDownloadService;
    expect($service->canDownload($asset, $order, $this->otherUser))->toBeFalse();
});

test('canDownload returns false for unpaid order', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'DL-004', 'status' => 'pending',
        'subtotal' => 30, 'total' => 30, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);
    $asset = DigitalAsset::create(['product_id' => $this->product->id, 'file_path' => 'dl.pdf', 'original_filename' => 'dl.pdf']);

    $service = new DigitalDownloadService;
    expect($service->canDownload($asset, $order, $this->user))->toBeFalse();
});

test('canDownload returns false when download limit reached', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'DL-005', 'status' => 'paid',
        'subtotal' => 30, 'total' => 30, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);
    $asset = DigitalAsset::create(['product_id' => $this->product->id, 'file_path' => 'dl.pdf', 'original_filename' => 'dl.pdf', 'download_limit' => 1]);

    // Simulate 1 download already done
    DigitalAssetDownload::create([
        'digital_asset_id' => $asset->id, 'order_id' => $order->id,
        'user_id' => $this->user->id, 'downloaded_at' => now(),
    ]);

    $service = new DigitalDownloadService;
    expect($service->canDownload($asset, $order, $this->user))->toBeFalse();
});

test('canDownload allows unlimited when download_limit is null', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'DL-006', 'status' => 'paid',
        'subtotal' => 30, 'total' => 30, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);
    $asset = DigitalAsset::create(['product_id' => $this->product->id, 'file_path' => 'dl.pdf', 'original_filename' => 'dl.pdf', 'download_limit' => null]);

    // 10 downloads already
    for ($i = 0; $i < 10; $i++) {
        DigitalAssetDownload::create([
            'digital_asset_id' => $asset->id, 'order_id' => $order->id,
            'user_id' => $this->user->id, 'downloaded_at' => now(),
        ]);
    }

    $service = new DigitalDownloadService;
    expect($service->canDownload($asset, $order, $this->user))->toBeTrue();
});

// --- API ---

test('API download links returns 403 for non-owner', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'DL-007', 'status' => 'paid',
        'subtotal' => 30, 'total' => 30, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);

    $this->actingAs($this->otherUser)
        ->getJson("/api/ecommerce/orders/{$order->id}/downloads")
        ->assertForbidden();
});
