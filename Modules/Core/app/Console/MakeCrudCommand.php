<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {module : Nom du module} {model : Nom du modèle} {--fields= : Champs CSV ex: name:string,price:decimal} {--with-api : Générer aussi ApiController et Resource} {--force : Écraser les fichiers existants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère un CRUD complet (Model, Migration, Policy, Controller, Views, Tests)';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Force overwriting of existing files.
     */
    protected bool $force = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $moduleName = $this->argument('module');
        $modelName = $this->argument('model');
        $fields = $this->option('fields') ?? '';
        $withApi = $this->option('with-api');
        $this->force = $this->option('force');

        $module = Str::studly($moduleName);
        $model = Str::studly($modelName);
        $servicePath = "Modules/{$module}/app/Services/{$model}CrudService.php";

        if ($this->files->exists($servicePath) && ! $this->force) {
            $this->error("Le CrudService pour {$model} existe déjà. Utilisez --force pour écraser.");

            return static::FAILURE;
        }

        $parsedFields = $this->parseFields($fields);

        $this->info("Génération du CRUD pour le modèle {$modelName} dans le module {$moduleName}...");

        $this->generateModel($moduleName, $modelName, $parsedFields);
        $this->generateMigration($moduleName, $modelName, $parsedFields);
        $this->generatePolicy($moduleName, $modelName);
        $this->generateCrudService($moduleName, $modelName);
        $this->generateController($moduleName, $modelName, $parsedFields);
        $this->generateFactory($moduleName, $modelName, $parsedFields);
        $this->generateViews($moduleName, $modelName, $parsedFields);
        $this->generateTests($moduleName, $modelName, $parsedFields);

        if ($withApi) {
            $this->info('Génération des fichiers API...');
            $this->generateApiController($moduleName, $modelName);
            $this->generateApiResource($moduleName, $modelName);
        }

        $this->showInstructions($moduleName, $modelName, $withApi);

        return static::SUCCESS;
    }

    /**
     * Parse the fields string.
     */
    protected function parseFields(string $fields): array
    {
        if (empty($fields)) {
            return [];
        }

        $result = [];
        $pairs = explode(',', $fields);

        foreach ($pairs as $pair) {
            $parts = explode(':', trim($pair));
            if (count($parts) === 2) {
                $result[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $result;
    }

    /**
     * Get the table name from the model name.
     */
    protected function getTableName(string $model): string
    {
        return Str::plural(Str::snake($model));
    }

    /**
     * Get the plural kebab-case version of the model name.
     */
    protected function getModelPluralSlug(string $model): string
    {
        return Str::plural(Str::kebab(class_basename($model)));
    }

    /**
     * Get the plural camelCase version of the model name.
     */
    protected function getModelPluralVar(string $model): string
    {
        return Str::camel(Str::plural(class_basename($model)));
    }

    /**
     * Write content to a file, handling existing files and the --force option.
     */
    protected function writeFile(string $path, string $content): bool
    {
        if ($this->files->exists($path) && ! $this->force) {
            $this->warn("  ⚠ Existe déjà: {$path} (--force pour écraser)");

            return false;
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $content);
        $this->line("  ✓ {$path}");

        return true;
    }

    protected function generateModel(string $moduleName, string $modelName, array $fields): void
    {
        $module = Str::studly($moduleName);
        $model = Str::studly($modelName);
        $path = "Modules/{$module}/app/Models/{$model}.php";

        $fillable = collect(array_keys($fields))
            ->map(fn ($field) => "'{$field}'")
            ->implode(', ');

        $casts = collect($fields)
            ->mapWithKeys(function ($type, $name) {
                $castType = match ($type) {
                    'boolean' => "'{$name}' => 'boolean'",
                    'decimal' => "'{$name}' => 'float'",
                    'date' => "'{$name}' => 'date'",
                    'datetime' => "'{$name}' => 'datetime'",
                    'json' => "'{$name}' => 'array'",
                    default => null,
                };

                return $castType ? [$name => $castType] : [];
            })
            ->filter()
            ->implode(",\n        ");

        $stub = <<<PHP
<?php
declare(strict_types=1);

namespace Modules\{$module}\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class {$model} extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [{$fillable}];

    protected function casts(): array
    {
        return [
            {$casts}
        ];
    }

    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return \Modules\{$module}\Database\Factories\{$model}Factory::new();
    }
}
PHP;
        $this->writeFile($path, $stub);
    }

    protected function generateMigration(string $moduleName, string $modelName, array $fields): void
    {
        $module = Str::studly($moduleName);
        $tableName = $this->getTableName($modelName);
        $timestamp = Carbon::now()->format('Y_m_d_His');
        $path = "Modules/{$module}/database/migrations/{$timestamp}_create_{$tableName}_table.php";

        $schemaFields = collect($fields)->map(function ($type, $name) {
            return match ($type) {
                'string' => "\$table->string('{$name}');",
                'text' => "\$table->text('{$name}');",
                'integer' => "\$table->integer('{$name}');",
                'decimal' => "\$table->decimal('{$name}', 10, 2);",
                'boolean' => "\$table->boolean('{$name}')->default(false);",
                'date' => "\$table->date('{$name}')->nullable();",
                'datetime' => "\$table->dateTime('{$name}')->nullable();",
                'json' => "\$table->json('{$name}')->nullable();",
                default => "\$table->string('{$name}');",
            };
        })->implode("\n            ");

        $stub = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            {$schemaFields}
            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
        $this->writeFile($path, $stub);
    }

    protected function generatePolicy(string $moduleName, string $modelName): void
    {
        $module = Str::studly($moduleName);
        $model = Str::studly($modelName);
        $path = "Modules/{$module}/app/Policies/{$model}Policy.php";
        $modelArg = '$'.lcfirst($model);

        $stub = <<<PHP
<?php

namespace Modules\{$module}\Policies;

use App\Models\User;
use Modules\{$module}\Models\{$model};
use Illuminate\Auth\Access\HandlesAuthorization;

class {$model}Policy
{
    use HandlesAuthorization;

    public function viewAny(User \$user): bool
    {
        return true; // Customize this
    }

    public function view(User \$user, {$model} {$modelArg}): bool
    {
        return true; // Customize this
    }

    public function create(User \$user): bool
    {
        return true; // Customize this
    }

    public function update(User \$user, {$model} {$modelArg}): bool
    {
        return true; // Customize this
    }

    public function delete(User \$user, {$model} {$modelArg}): bool
    {
        return true; // Customize this
    }
}
PHP;
        $this->writeFile($path, $stub);
    }

    protected function generateCrudService(string $moduleName, string $modelName): void
    {
        $module = Str::studly($moduleName);
        $model = Str::studly($modelName);
        $path = "Modules/{$module}/app/Services/{$model}CrudService.php";

        $stub = <<<PHP
<?php

namespace Modules\{$module}\Services;

use Modules\Core\Services\CrudService;
use Modules\{$module}\Models\{$model};

class {$model}CrudService extends CrudService
{
    protected string \$modelClass = {$model}::class;
}
PHP;
        $this->writeFile($path, $stub);
    }

    protected function generateController(string $moduleName, string $modelName, array $fields): void
    {
        $module = Str::studly($moduleName);
        $model = Str::studly($modelName);
        $moduleL = strtolower($module);
        $plural = $this->getModelPluralSlug($model);
        $pluralVar = $this->getModelPluralVar($model);
        $var = lcfirst($model);
        $path = "Modules/{$module}/app/Http/Controllers/{$model}Controller.php";

        $validation = collect($fields)->map(function ($type, $name) {
            $rules = match ($type) {
                'string' => "'required|string|max:255'",
                'text' => "'required|string'",
                'integer', 'decimal' => "'required|numeric'",
                'boolean' => "'nullable|boolean'",
                'date', 'datetime' => "'nullable|date'",
                'json' => "'nullable'",
                default => "'required|string'",
            };

            return "'{$name}' => [{$rules}]";
        })->implode(",\n            ");

        $stub = <<<PHP
<?php

namespace Modules\{$module}\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\{$module}\Services\{$model}CrudService;

class {$model}Controller extends Controller
{
    public function __construct(protected {$model}CrudService \$service)
    {
    }

    public function index(): View
    {
        return view('{$moduleL}::{$plural}.index', [
            '{$pluralVar}' => \$this->service->paginate(15)
        ]);
    }

    public function create(): View
    {
        return view('{$moduleL}::{$plural}.create');
    }

    public function store(Request \$request): RedirectResponse
    {
        \$validatedData = \$request->validate([
            {$validation}
        ]);
        \$this->service->create(\$validatedData);
        return redirect()->route('{$moduleL}.{$plural}.index')->with('success', '{$model} créé avec succès.');
    }

    public function show(int \$id): View
    {
        return view('{$moduleL}::{$plural}.show', [
            '{$var}' => \$this->service->findOrFail(\$id)
        ]);
    }

    public function edit(int \$id): View
    {
        return view('{$moduleL}::{$plural}.edit', [
            '{$var}' => \$this->service->findOrFail(\$id)
        ]);
    }

    public function update(Request \$request, int \$id): RedirectResponse
    {
        \$validatedData = \$request->validate([
            {$validation}
        ]);
        \$this->service->update(\$id, \$validatedData);
        return redirect()->route('{$moduleL}.{$plural}.index')->with('success', '{$model} mis à jour avec succès.');
    }

    public function destroy(int \$id): RedirectResponse
    {
        \$this->service->delete(\$id);
        return redirect()->route('{$moduleL}.{$plural}.index')->with('success', '{$model} supprimé avec succès.');
    }
}
PHP;
        $this->writeFile($path, $stub);
    }

    protected function generateViews(string $moduleName, string $modelName, array $fields): void
    {
        $module = Str::studly($moduleName);
        $model = Str::studly($modelName);
        $moduleL = strtolower($module);
        $plural = $this->getModelPluralSlug($model);
        $pluralVar = $this->getModelPluralVar($model);
        $var = lcfirst($model);
        $modelLabel = Str::headline($model);
        $pluralLabel = Str::headline(Str::plural($model));

        $viewPath = "Modules/{$module}/resources/views/{$plural}";

        // Index View
        $indexHeaders = collect(array_keys($fields))->map(fn ($f) => '<th>'.Str::headline($f).'</th>')->implode("\n                            ");
        $indexCells = collect(array_keys($fields))->map(fn ($f) => "<td>{{ \$item->{$f} }}</td>")->implode("\n                            ");
        $colspan = count($fields) + 2;

        $indexStub = <<<BLADE
@extends('backoffice::layouts.admin', ['title' => '{$pluralLabel}', 'subtitle' => 'Liste'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('{$pluralLabel}') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="list" class="icon-md text-primary"></i>{{ __('{$pluralLabel}') }}</h4>
    <a href="{{ route('{$moduleL}.{$plural}.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
        <i data-lucide="plus"></i> {{ __('Ajouter') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        {$indexHeaders}
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(\${$pluralVar} as \$item)
                    <tr>
                        <td>{{ \$item->id }}</td>
                        {$indexCells}
                        <td class="text-end">
                            <a href="{{ route('{$moduleL}.{$plural}.edit', \$item->id) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                <i data-lucide="pencil" class="icon-sm"></i> {{ __('Modifier') }}
                            </a>
                            <form action="{{ route('{$moduleL}.{$plural}.destroy', \$item->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" onclick="return confirm('Confirmer la suppression ?')">
                                    <i data-lucide="trash-2" class="icon-sm"></i> {{ __('Supprimer') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{$colspan}" class="text-center text-muted py-4">{{ __('Aucun élément trouvé.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ \${$pluralVar}->links() }}
        </div>
    </div>
</div>

@endsection
BLADE;
        $this->writeFile("{$viewPath}/index.blade.php", $indexStub);

        // Form fields partial
        $formFields = collect($fields)->map(function ($type, $name) use ($var) {
            $label = Str::headline($name);
            $inputName = $name;
            $required = in_array($type, ['string', 'text', 'integer', 'decimal']) ? " <span class=\"text-danger\">*</span>" : '';
            $oldValue = "old('{$inputName}', isset(\${$var}) ? \${$var}->{$inputName} : '')";

            $input = match ($type) {
                'text' => "<textarea class=\"form-control @error('{$inputName}') is-invalid @enderror\" id=\"{$inputName}\" name=\"{$inputName}\" rows=\"3\">{{ {$oldValue} }}</textarea>",
                'boolean' => "<div class=\"form-check form-switch\">\n                <input type=\"hidden\" name=\"{$inputName}\" value=\"0\">\n                <input type=\"checkbox\" class=\"form-check-input\" id=\"{$inputName}\" name=\"{$inputName}\" value=\"1\" {{ old('{$inputName}', isset(\${$var}) ? \${$var}->{$inputName} : 0) ? 'checked' : '' }}>\n            </div>",
                'date' => "<input type=\"date\" class=\"form-control @error('{$inputName}') is-invalid @enderror\" id=\"{$inputName}\" name=\"{$inputName}\" value=\"{{ {$oldValue} }}\">",
                'datetime' => "<input type=\"datetime-local\" class=\"form-control @error('{$inputName}') is-invalid @enderror\" id=\"{$inputName}\" name=\"{$inputName}\" value=\"{{ {$oldValue} }}\">",
                'integer' => "<input type=\"number\" class=\"form-control @error('{$inputName}') is-invalid @enderror\" id=\"{$inputName}\" name=\"{$inputName}\" value=\"{{ {$oldValue} }}\">",
                'decimal' => "<input type=\"number\" step=\"0.01\" class=\"form-control @error('{$inputName}') is-invalid @enderror\" id=\"{$inputName}\" name=\"{$inputName}\" value=\"{{ {$oldValue} }}\">",
                default => "<input type=\"text\" class=\"form-control @error('{$inputName}') is-invalid @enderror\" id=\"{$inputName}\" name=\"{$inputName}\" value=\"{{ {$oldValue} }}\">"
            };

            return <<<BLADE
        <div class="col-md-6 mb-3">
            <label for="{$inputName}" class="form-label fw-medium">{$label}{$required}</label>
            {$input}
            @error('{$inputName}')
                <div class="invalid-feedback d-block">{{ \$message }}</div>
            @enderror
        </div>
BLADE;
        })->implode("\n");

        $fieldsContent = "<div class=\"row\">\n{$formFields}\n</div>";
        $this->writeFile("{$viewPath}/_fields.blade.php", $fieldsContent);

        // Create View
        $createStub = <<<BLADE
@extends('backoffice::layouts.admin', ['title' => '{$pluralLabel}', 'subtitle' => 'Ajouter'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('{$moduleL}.{$plural}.index') }}">{{ __('{$pluralLabel}') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Ajouter') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="plus-circle" class="icon-md text-primary"></i>{{ __('Ajouter') }}</h4>
    <a href="{{ route('{$moduleL}.{$plural}.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
        <i data-lucide="arrow-left"></i> {{ __('Retour') }}
    </a>
</div>

<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <h5 class="fw-semibold mb-0">{{ __('Informations') }}</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('{$moduleL}.{$plural}.store') }}" method="POST">
            @csrf
            @include('{$moduleL}::{$plural}._fields')
            <div class="d-flex align-items-center gap-3 mt-3">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="save" class="icon-sm"></i> {{ __('Enregistrer') }}
                </button>
                <a href="{{ route('{$moduleL}.{$plural}.index') }}" class="btn btn-light d-inline-flex align-items-center gap-2">
                    <i data-lucide="x" class="icon-sm"></i> {{ __('Annuler') }}
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
BLADE;
        $this->writeFile("{$viewPath}/create.blade.php", $createStub);

        // Edit View
        $editStub = <<<BLADE
@extends('backoffice::layouts.admin', ['title' => '{$pluralLabel}', 'subtitle' => 'Modifier'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('{$moduleL}.{$plural}.index') }}">{{ __('{$pluralLabel}') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Modifier') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="pencil" class="icon-md text-primary"></i>{{ __('Modifier') }} #{{ \${$var}->id }}</h4>
    <a href="{{ route('{$moduleL}.{$plural}.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
        <i data-lucide="arrow-left"></i> {{ __('Retour') }}
    </a>
</div>

<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <h5 class="fw-semibold mb-0">{{ __('Informations') }}</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('{$moduleL}.{$plural}.update', \${$var}->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('{$moduleL}::{$plural}._fields', ['{$var}' => \${$var}])
            <div class="d-flex align-items-center gap-3 mt-3">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="save" class="icon-sm"></i> {{ __('Enregistrer') }}
                </button>
                <a href="{{ route('{$moduleL}.{$plural}.index') }}" class="btn btn-light d-inline-flex align-items-center gap-2">
                    <i data-lucide="x" class="icon-sm"></i> {{ __('Annuler') }}
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
BLADE;
        $this->writeFile("{$viewPath}/edit.blade.php", $editStub);
    }

    protected function generateFactory(string $moduleName, string $modelName, array $fields): void
    {
        $module = Str::studly($moduleName);
        $model = Str::studly($modelName);

        $path = "Modules/{$module}/database/factories/{$model}Factory.php";

        $stub = <<<'PHP'
<?php

namespace Modules\ModuleName\Database\Factories;

use Modules\ModuleName\Models\ModelName;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModelNameFactory extends Factory
{
    protected $model = ModelName::class;

    public function definition(): array
    {
        return [
FIELDS_PLACEHOLDER
        ];
    }
}
PHP;

        $fieldsContent = '';
        foreach ($fields as $field => $type) {
            $fakeData = match ($type) {
                'string' => 'fake()->words(3, true)',
                'text' => 'fake()->paragraph()',
                'integer' => 'fake()->numberBetween(1, 100)',
                'decimal' => 'fake()->randomFloat(2, 1, 1000)',
                'boolean' => 'fake()->boolean()',
                'date' => 'now()->toDateString()',
                'datetime' => 'now()->toDateTimeString()',
                'json' => "['key' => 'value']",
                default => 'fake()->word()',
            };
            $fieldsContent .= "            '{$field}' => {$fakeData},\n";
        }

        $stub = str_replace('ModuleName', $module, $stub);
        $stub = str_replace('ModelName', $model, $stub);
        $stub = str_replace('FIELDS_PLACEHOLDER', rtrim($fieldsContent), $stub);

        $this->writeFile($path, $stub);
    }

    protected function generateTests(string $moduleName, string $modelName, array $fields): void
    {
        $module = Str::studly($moduleName);
        $model = Str::studly($modelName);
        $moduleL = strtolower($module);
        $plural = $this->getModelPluralSlug($model);
        $path = "Modules/{$module}/tests/Feature/{$model}CrudTest.php";

        $factoryData = collect($fields)->map(function ($type, $name) {
            $value = match ($type) {
                'string' => 'fake()->words(3, true)',
                'text' => 'fake()->paragraph()',
                'integer' => 'fake()->numberBetween(1, 100)',
                'decimal' => 'fake()->randomFloat(2, 1, 1000)',
                'boolean' => 'fake()->boolean()',
                'date' => 'now()->toDateString()',
                'datetime' => 'now()->toDateTimeString()',
                'json' => "['key' => 'value']",
                default => 'fake()->word()',
            };

            return "'{$name}' => {$value}";
        })->implode(",\n        ");

        $stub = <<<PHP
<?php

namespace Modules\{$module}\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\{$module}\Models\{$model};

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \$this->user = User::factory()->create();
    \$this->actingAs(\$this->user);
});

test('can list {$plural}', function () {
    {$model}::factory()->count(3)->create();
    \$response = \$this->get(route('{$moduleL}.{$plural}.index'));
    \$response->assertStatus(200);
    \$response->assertViewIs('{$moduleL}::{$plural}.index');
});

test('can show create form for {$model}', function () {
    \$response = \$this->get(route('{$moduleL}.{$plural}.create'));
    \$response->assertStatus(200);
    \$response->assertViewIs('{$moduleL}::{$plural}.create');
});

test('can store a new {$model}', function () {
    \$data = [
        {$factoryData}
    ];

    \$response = \$this->post(route('{$moduleL}.{$plural}.store'), \$data);

    \$response->assertRedirect(route('{$moduleL}.{$plural}.index'));
    \$this->assertDatabaseHas('{$this->getTableName($model)}', \$data);
});

test('can delete a {$model}', function () {
    \${$modelName} = {$model}::factory()->create();

    \$response = \$this->delete(route('{$moduleL}.{$plural}.destroy', \${$modelName}->id));

    \$response->assertRedirect(route('{$moduleL}.{$plural}.index'));
    \$this->assertSoftDeleted('{$this->getTableName($model)}', ['id' => \${$modelName}->id]);
});
PHP;
        $this->writeFile($path, $stub);
    }

    protected function generateApiController(string $moduleName, string $modelName): void
    {
        $module = Str::studly($moduleName);
        $model = Str::studly($modelName);
        $var = lcfirst($model);
        $path = "Modules/{$module}/app/Http/Controllers/Api/{$model}Controller.php";

        $stub = str_replace(
            ['{{MODULE}}', '{{MODEL}}', '{{VAR}}'],
            [$module, $model, $var],
            <<<'PHP'
<?php

namespace Modules\{{MODULE}}\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\{{MODULE}}\Models\{{MODEL}};
use Modules\{{MODULE}}\Http\Resources\{{MODEL}}Resource;

class {{MODEL}}Controller extends Controller
{
    public function index(): JsonResponse
    {
        return {{MODEL}}Resource::collection({{MODEL}}::paginate(15))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = {{MODEL}}::create($request->all());
        return (new {{MODEL}}Resource($data))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        ${{VAR}} = {{MODEL}}::findOrFail($id);
        return (new {{MODEL}}Resource(${{VAR}}))->response();
    }

    public function update(Request $request, int $id): JsonResponse
    {
        ${{VAR}} = {{MODEL}}::findOrFail($id);
        ${{VAR}}->update($request->all());
        return (new {{MODEL}}Resource(${{VAR}}))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        {{MODEL}}::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
PHP
        );
        $this->writeFile($path, $stub);
    }

    protected function generateApiResource(string $moduleName, string $modelName): void
    {
        $module = Str::studly($moduleName);
        $model = Str::studly($modelName);
        $path = "Modules/{$module}/app/Http/Resources/{$model}Resource.php";

        $stub = str_replace(
            ['{{MODULE}}', '{{MODEL}}'],
            [$module, $model],
            <<<'PHP'
<?php

namespace Modules\{{MODULE}}\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class {{MODEL}}Resource extends JsonResource
{
    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
PHP
        );
        $this->writeFile($path, $stub);
    }

    protected function showInstructions(string $moduleName, string $modelName, bool $withApi): void
    {
        $module = Str::studly($moduleName);
        $model = Str::studly($modelName);
        $plural = $this->getModelPluralSlug($model);

        $this->info("\nInstructions de configuration :");
        $this->line("1. Ajoutez la route web dans `Modules/{$module}/routes/web.php`:");
        $this->warn("   Route::resource('{$plural}', \Modules\{$module}\Http\Controllers\{$model}Controller::class)->middleware('auth');");

        if ($withApi) {
            $this->line("\n2. Ajoutez la route API dans `Modules/{$module}/routes/api.php`:");
            $this->warn("   Route::apiResource('{$plural}', \Modules\{$module}\Http\Controllers\Api\{$model}Controller::class);");
        }

        $this->line("\n3. Exécutez la migration:");
        $this->warn("   php artisan module:migrate {$module}");

        $this->info("\nCRUD pour {$model} généré avec succès !");
    }
}
