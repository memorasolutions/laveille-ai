<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\TranslationsManager;
use Modules\Translation\Services\TranslationService;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');

    // Backup original files
    $this->originalFr = File::exists(lang_path('fr.json')) ? File::get(lang_path('fr.json')) : null;
    $this->originalEn = File::exists(lang_path('en.json')) ? File::get(lang_path('en.json')) : null;

    // Write test translation files
    File::put(lang_path('fr.json'), json_encode([
        'Hello' => 'Bonjour',
        'Goodbye' => 'Au revoir',
        'Welcome' => 'Bienvenue',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");

    File::put(lang_path('en.json'), json_encode([
        'Hello' => 'Hello',
        'Goodbye' => 'Goodbye',
        'Welcome' => '',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
});

afterEach(function () {
    // Restore original files
    if ($this->originalFr !== null) {
        File::put(lang_path('fr.json'), $this->originalFr);
    }
    if ($this->originalEn !== null) {
        File::put(lang_path('en.json'), $this->originalEn);
    }

    // Clean up test locale
    if (File::exists(lang_path('es.json'))) {
        File::delete(lang_path('es.json'));
    }
});

// --- TranslationService Tests ---

test('translation service can get locales', function () {
    $service = app(TranslationService::class);
    $locales = $service->getLocales();

    expect($locales)->toContain('fr')->toContain('en');
});

test('translation service can get translations', function () {
    $service = app(TranslationService::class);
    $translations = $service->getTranslations('fr');

    expect($translations)->toHaveKey('Hello', 'Bonjour');
});

test('translation service can set translation', function () {
    $service = app(TranslationService::class);
    $service->setTranslation('fr', 'Hello', 'Salut');

    $translations = $service->getTranslations('fr');
    expect($translations['Hello'])->toBe('Salut');
});

test('translation service can add key to all locales', function () {
    $service = app(TranslationService::class);
    $service->addKey('New key', ['fr' => 'Nouveau', 'en' => 'New']);

    expect($service->getTranslations('fr'))->toHaveKey('New key', 'Nouveau');
    expect($service->getTranslations('en'))->toHaveKey('New key', 'New');
});

test('translation service can delete key from all locales', function () {
    $service = app(TranslationService::class);
    $service->deleteKey('Hello');

    expect($service->getTranslations('fr'))->not->toHaveKey('Hello');
    expect($service->getTranslations('en'))->not->toHaveKey('Hello');
});

test('translation service can get translation count', function () {
    $service = app(TranslationService::class);
    $count = $service->getTranslationCount('en');

    expect($count)->toBe(['total' => 3, 'translated' => 2]);
});

test('translation service can add locale', function () {
    $service = app(TranslationService::class);
    $service->addLocale('es');

    expect(File::exists(lang_path('es.json')))->toBeTrue();
});

test('translation service can remove locale', function () {
    $service = app(TranslationService::class);
    $service->addLocale('es');
    $service->removeLocale('es');

    expect(File::exists(lang_path('es.json')))->toBeFalse();
});

test('translation service cannot remove protected locale', function () {
    $service = app(TranslationService::class);
    $service->removeLocale('fr');
})->throws(InvalidArgumentException::class);

test('translation service can import translations', function () {
    $service = app(TranslationService::class);
    $service->importFromArray('fr', ['NewKey' => 'Nouvelle valeur']);

    $translations = $service->getTranslations('fr');
    expect($translations)->toHaveKey('NewKey', 'Nouvelle valeur');
    expect($translations)->toHaveKey('Hello', 'Bonjour');
});

// --- Route Tests ---

test('admin can access translations page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.translations.index'))
        ->assertOk()
        ->assertSee('Traductions');
});

test('guest cannot access translations page', function () {
    $this->get(route('admin.translations.index'))
        ->assertRedirect();
});

test('admin can export translations', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.translations.export', 'en'))
        ->assertOk()
        ->assertHeader('content-type', 'application/json');
});

// --- Livewire Tests ---

test('livewire translations manager renders', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->assertOk()
        ->assertSee('Traductions');
});

test('livewire can search translations', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->set('search', 'Hello')
        ->assertSee('Hello')
        ->assertDontSee('Au revoir');
});

test('livewire can filter untranslated only', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->set('showUntranslatedOnly', true)
        ->assertSee('Bienvenue')
        ->assertDontSee('Goodbye');
});

test('livewire can update translation', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->call('updateTranslation', 'Hello', 'Hola');

    $service = app(TranslationService::class);
    expect($service->getTranslations('en')['Hello'])->toBe('Hola');
});

test('livewire can add key', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->set('newKey', 'Test Key')
        ->set('newSourceValue', 'Clé test')
        ->set('newTargetValue', 'Test Key EN')
        ->call('addKey');

    $service = app(TranslationService::class);
    expect($service->getTranslations('fr'))->toHaveKey('Test Key', 'Clé test');
    expect($service->getTranslations('en'))->toHaveKey('Test Key', 'Test Key EN');
});

test('livewire can delete key', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->call('deleteKey', 'Hello');

    $service = app(TranslationService::class);
    expect($service->getTranslations('fr'))->not->toHaveKey('Hello');
    expect($service->getTranslations('en'))->not->toHaveKey('Hello');
});

test('livewire can add locale', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->set('newLocale', 'es')
        ->call('addLocale');

    expect(File::exists(lang_path('es.json')))->toBeTrue();
});
