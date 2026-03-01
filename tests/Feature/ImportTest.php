<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Import\Services\ImportResult;
use Modules\Import\Services\ImportService;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('redirects guest to login', function () {
    $this->get(route('admin.import.index'))
        ->assertRedirect(route('login'));
});

it('forbids editor from accessing import', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)
        ->get(route('admin.import.index'))
        ->assertForbidden();
});

it('allows admin to view import page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.import.index'))
        ->assertOk()
        ->assertSee('Import de données');
});

it('shows model type options', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.import.index'))
        ->assertSee('Articles')
        ->assertSee('Pages')
        ->assertSee('Utilisateurs');
});

it('validates file upload on preview', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.import.preview'), [])
        ->assertSessionHasErrors(['file', 'model_type']);
});

it('previews a CSV file', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $csvContent = "title,content,excerpt\nArticle 1,Contenu 1,Résumé 1\nArticle 2,Contenu 2,Résumé 2";
    $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

    $this->actingAs($admin)
        ->post(route('admin.import.preview'), [
            'file' => $file,
            'model_type' => 'article',
        ])
        ->assertOk()
        ->assertSee('Prévisualisation')
        ->assertSee('Article 1')
        ->assertSee('Contenu 1');
});

it('downloads a CSV template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.import.template', 'article'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

it('redirects for invalid template type', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.import.template', 'invalid'))
        ->assertRedirect(route('admin.import.index'));
});

it('creates ImportResult with correct defaults', function () {
    $result = new ImportResult();

    expect($result->total)->toBe(0)
        ->and($result->imported)->toBe(0)
        ->and($result->skipped)->toBe(0)
        ->and($result->errors)->toBeEmpty()
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->getSuccessRate())->toBe(0.0);
});

it('calculates ImportResult success rate', function () {
    $result = new ImportResult();
    $result->total = 10;
    $result->imported = 8;
    $result->skipped = 2;
    $result->errors = [3 => 'erreur', 7 => 'erreur'];

    expect($result->getSuccessRate())->toBe(80.0)
        ->and($result->isSuccess())->toBeFalse()
        ->and($result->toArray())->toHaveKeys(['total', 'imported', 'skipped', 'errors', 'success_rate', 'is_success']);
});

it('resolves available fields for each model type', function () {
    expect(ImportService::FIELD_MAPS['article'])->toContain('title', 'content')
        ->and(ImportService::FIELD_MAPS['page'])->toContain('title', 'content')
        ->and(ImportService::FIELD_MAPS['user'])->toContain('name', 'email', 'password');
});

it('previews file with correct structure', function () {
    $service = new ImportService();

    $csvContent = "title,content\nTest,Contenu test\nTest2,Contenu 2";
    $path = \Illuminate\Support\Facades\Storage::disk('local')->path('imports/test_preview.csv');

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }
    file_put_contents($path, $csvContent);

    $preview = $service->preview($path, 'csv', 5);

    expect($preview)->toHaveKeys(['headers', 'rows', 'total_previewed'])
        ->and($preview['headers'])->toBe(['title', 'content'])
        ->and($preview['rows'])->toHaveCount(2)
        ->and($preview['rows'][0])->toBe(['Test', 'Contenu test']);

    unlink($path);
});

it('imports articles from CSV', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);

    $csvContent = "title,content,excerpt\nArticle Import,Contenu importé,Résumé\nArticle 2,Contenu 2,Résumé 2";
    $path = \Illuminate\Support\Facades\Storage::disk('local')->path('imports/test_import.csv');

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }
    file_put_contents($path, $csvContent);

    $service = new ImportService();
    $result = $service->import($path, 'csv', 'article', [
        0 => 'title',
        1 => 'content',
        2 => 'excerpt',
    ]);

    expect($result->total)->toBe(2)
        ->and($result->imported)->toBe(2)
        ->and($result->skipped)->toBe(0);

    $this->assertDatabaseHas('articles', ['title->fr' => 'Article Import']);
    $this->assertDatabaseHas('articles', ['title->fr' => 'Article 2']);

    @unlink($path);
});

it('skips invalid rows and collects errors', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);

    $service = new ImportService();
    $result = $service->import(
        \Illuminate\Support\Facades\Storage::disk('local')->path('imports/nonexistent.csv'),
        'csv',
        'article',
        [0 => 'title'],
    );

    expect($result->total)->toBe(0);
});
