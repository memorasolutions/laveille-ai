<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Ecommerce\Services\ProductImportExportService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'view_products']);
    Permission::firstOrCreate(['name' => 'view_ecommerce']);
    $role = Role::firstOrCreate(['name' => 'super_admin']);
    $role->syncPermissions(Permission::all());

    $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
    $this->admin->assignRole('super_admin');
});

// --- Service: Export ---

test('export generates CSV with products and variants', function () {
    $product = Product::create(['name' => 'Widget', 'slug' => 'widget', 'price' => 29.99, 'is_active' => true]);
    ProductVariant::create(['product_id' => $product->id, 'sku' => 'WDG-001', 'price' => 29.99, 'stock' => 10, 'is_active' => true]);

    $service = new ProductImportExportService;
    $csv = $service->exportProducts();

    expect($csv)->toContain('name,slug,price,is_active,sku,variant_price,stock,weight')
        ->and($csv)->toContain('Widget')
        ->and($csv)->toContain('WDG-001');
});

test('export handles product without variants', function () {
    Product::create(['name' => 'Solo', 'slug' => 'solo', 'price' => 10, 'is_active' => true]);

    $service = new ProductImportExportService;
    $csv = $service->exportProducts();

    expect($csv)->toContain('Solo')
        ->and($csv)->toContain('solo');
});

// --- Service: Import ---

test('import creates new product from CSV', function () {
    $csv = "name,slug,price,is_active,sku,variant_price,stock,weight\nNew Product,new-product,49.99,1,NP-001,49.99,20,1.5";

    $service = new ProductImportExportService;
    $result = $service->importProducts($csv);

    expect($result['created'])->toBe(1)
        ->and($result['updated'])->toBe(0)
        ->and($result['errors'])->toBeEmpty();

    expect(Product::where('slug', 'new-product')->exists())->toBeTrue();
    expect(ProductVariant::where('sku', 'NP-001')->exists())->toBeTrue();
});

test('import updates existing product', function () {
    Product::create(['name' => 'Old', 'slug' => 'existing', 'price' => 10]);

    $csv = "name,slug,price,is_active,sku,variant_price,stock,weight\nUpdated,existing,25,1,,,, ";

    $service = new ProductImportExportService;
    $result = $service->importProducts($csv);

    expect($result['updated'])->toBe(1)
        ->and(Product::where('slug', 'existing')->first()->name)->toBe('Updated');
});

test('import reports errors for invalid rows', function () {
    $csv = "name,slug,price,is_active,sku,variant_price,stock,weight\n,,invalid,1,,,,";

    $service = new ProductImportExportService;
    $result = $service->importProducts($csv);

    expect($result['errors'])->not->toBeEmpty();
});

// --- Admin routes ---

test('admin can access import export page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.ecommerce.import-export.index'))
        ->assertOk()
        ->assertSee('Exporter');
});

test('admin can export CSV', function () {
    Product::create(['name' => 'Test', 'slug' => 'test-export', 'price' => 10]);

    $this->actingAs($this->admin)
        ->get(route('admin.ecommerce.import-export.export'))
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('admin can import CSV file', function () {
    $csv = "name,slug,price,is_active,sku,variant_price,stock,weight\nImported,imported-product,19.99,1,IMP-001,19.99,5,";

    $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

    $this->actingAs($this->admin)
        ->post(route('admin.ecommerce.import-export.import'), ['file' => $file])
        ->assertRedirect(route('admin.ecommerce.import-export.index'));

    expect(Product::where('slug', 'imported-product')->exists())->toBeTrue();
});
