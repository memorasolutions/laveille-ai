<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Console\MakeCrudCommand;
use Modules\Core\Services\CrudService;

uses(RefreshDatabase::class);

// Nettoyage des fichiers générés par make:crud après chaque test
afterEach(function () {
    $patterns = [
        base_path('Modules/Core/app/Models/TestModel.php'),
        base_path('Modules/Core/app/Models/DupeModel.php'),
        base_path('Modules/Core/app/Policies/TestModelPolicy.php'),
        base_path('Modules/Core/app/Policies/DupeModelPolicy.php'),
        base_path('Modules/Core/app/Http/Controllers/TestModelController.php'),
        base_path('Modules/Core/app/Http/Controllers/DupeModelController.php'),
        base_path('Modules/Core/app/Services/TestModelCrudService.php'),
        base_path('Modules/Core/app/Services/DupeModelCrudService.php'),
        base_path('Modules/Core/tests/Feature/TestModelCrudTest.php'),
        base_path('Modules/Core/tests/Feature/DupeModelCrudTest.php'),
    ];
    foreach ($patterns as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }
    // Supprimer migrations générées
    foreach (glob(base_path('Modules/Core/database/migrations/*test_models*')) ?: [] as $f) {
        unlink($f);
    }
    foreach (glob(base_path('Modules/Core/database/migrations/*dupe_models*')) ?: [] as $f) {
        unlink($f);
    }
    // Supprimer dossiers de vues générées
    $viewDirs = [
        base_path('Modules/Core/resources/views/themes/backend/test-models'),
        base_path('Modules/Core/resources/views/themes/backend/dupe-models'),
    ];
    foreach ($viewDirs as $dir) {
        if (is_dir($dir)) {
            array_map('unlink', glob("{$dir}/*") ?: []);
            rmdir($dir);
        }
    }
});

test('CrudService class exists', function () {
    expect(class_exists(CrudService::class))->toBeTrue();
});

test('CrudService can be instantiated with model class', function () {
    $service = new CrudService(User::class);

    expect($service)->toBeInstanceOf(CrudService::class);
});

test('CrudService create works', function () {
    $service = new CrudService(User::class);

    $user = $service->create([
        'name' => 'Test User',
        'email' => 'crud@test.com',
        'password' => bcrypt('password'),
    ]);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Test User');
});

test('CrudService find works', function () {
    $service = new CrudService(User::class);
    $user = User::factory()->create();

    $found = $service->find($user->id);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($user->id);
});

test('CrudService update works', function () {
    $service = new CrudService(User::class);
    $user = User::factory()->create();

    $updated = $service->update($user->id, ['name' => 'Updated']);

    expect($updated->name)->toBe('Updated');
});

test('CrudService delete works', function () {
    $service = new CrudService(User::class);
    $user = User::factory()->create();

    $result = $service->delete($user->id);

    expect($result)->toBeTrue();
    expect(User::find($user->id))->toBeNull();
});

test('CrudService all with filters', function () {
    $service = new CrudService(User::class);
    User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);

    $filtered = $service->all(['name' => 'Alice']);

    expect($filtered)->toHaveCount(1);
    expect($filtered->first()->name)->toBe('Alice');
});

test('CrudService paginate works', function () {
    $service = new CrudService(User::class);
    User::factory()->count(5)->create();

    $paginated = $service->paginate(2);

    expect($paginated)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
    expect($paginated->perPage())->toBe(2);
    expect($paginated->total())->toBe(5);
});

test('CrudService count works', function () {
    $service = new CrudService(User::class);
    User::factory()->count(3)->create();

    expect($service->count())->toBe(3);
});

test('MakeCrudCommand is registered', function () {
    expect(class_exists(MakeCrudCommand::class))->toBeTrue();
});

test('make:crud command is available', function () {
    $this->artisan('make:crud', ['module' => 'Core', 'model' => 'TestModel'])
        ->assertSuccessful();

    $path = base_path('Modules/Core/app/Services/TestModelCrudService.php');
    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toContain('TestModelCrudService')
        ->toContain('extends CrudService');

    unlink($path);
});

test('make:crud prevents duplicate', function () {
    $path = base_path('Modules/Core/app/Services/DupeModelCrudService.php');

    $this->artisan('make:crud', ['module' => 'Core', 'model' => 'DupeModel'])
        ->assertSuccessful();

    $this->artisan('make:crud', ['module' => 'Core', 'model' => 'DupeModel'])
        ->assertFailed();

    unlink($path);
});
