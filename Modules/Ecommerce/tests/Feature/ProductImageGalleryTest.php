<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Ecommerce\Models\Product;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'view_products']);
    Permission::firstOrCreate(['name' => 'create_products']);
    Permission::firstOrCreate(['name' => 'update_products']);
    Permission::firstOrCreate(['name' => 'view_ecommerce']);

    $role = Role::firstOrCreate(['name' => 'super_admin']);
    $role->syncPermissions(Permission::all());

    $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
    $this->admin->assignRole('super_admin');

    $this->product = Product::create(['name' => 'Test Product', 'slug' => 'test-product', 'price' => 99.99]);
});

test('product has media collections defined', function () {
    $collections = $this->product->getRegisteredMediaCollections()->pluck('name')->toArray();

    expect($collections)->toContain('gallery')
        ->and($collections)->toContain('featured_image');
});

test('admin can upload featured image on store', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)->post(route('admin.ecommerce.products.store'), [
        'name' => 'New Product',
        'slug' => 'new-product',
        'price' => 50.00,
        'featured_image' => UploadedFile::fake()->image('hero.jpg', 800, 600),
    ]);

    $product = Product::where('slug', 'new-product')->first();
    expect($product)->not->toBeNull()
        ->and($product->getFirstMedia('featured_image'))->not->toBeNull();
});

test('admin can upload gallery images on store', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)->post(route('admin.ecommerce.products.store'), [
        'name' => 'Gallery Product',
        'slug' => 'gallery-product',
        'price' => 70.00,
        'gallery' => [
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.jpg'),
        ],
    ]);

    $product = Product::where('slug', 'gallery-product')->first();
    expect($product->getMedia('gallery'))->toHaveCount(2);
});

test('admin can remove gallery image on update', function () {
    Storage::fake('public');

    $media = $this->product->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('gallery');

    $this->actingAs($this->admin)->put(route('admin.ecommerce.products.update', $this->product), [
        'name' => $this->product->name,
        'slug' => $this->product->slug,
        'price' => $this->product->price,
        'remove_gallery' => [$media->id],
    ]);

    $this->product->refresh();
    expect($this->product->getMedia('gallery'))->toHaveCount(0);
});

test('featured_image collection is single file', function () {
    Storage::fake('public');

    $this->product->addMedia(UploadedFile::fake()->image('first.jpg'))->toMediaCollection('featured_image');
    $this->product->addMedia(UploadedFile::fake()->image('second.jpg'))->toMediaCollection('featured_image');

    expect($this->product->getMedia('featured_image'))->toHaveCount(1);
});

test('API product show includes media', function () {
    Storage::fake('public');

    $this->product->addMedia(UploadedFile::fake()->image('api.jpg'))->toMediaCollection('gallery');

    $this->getJson('/api/ecommerce/products/'.$this->product->slug)
        ->assertOk()
        ->assertJsonStructure(['data' => ['media']]);
});
