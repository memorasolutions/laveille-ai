<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Modules\Translation\Services\TranslationService;

uses(Tests\TestCase::class);

test('translation service is registered as singleton', function () {
    $service1 = app(TranslationService::class);
    $service2 = app(TranslationService::class);

    expect($service1)->toBeInstanceOf(TranslationService::class);
    expect($service1)->toBe($service2);
});

test('translation service returns available locales', function () {
    $service = app(TranslationService::class);
    $locales = $service->getLocales();

    expect($locales)->toBeArray();
    expect($locales)->toContain('fr');
    expect($locales)->toContain('en');
});

test('translation service reads translations', function () {
    $service = app(TranslationService::class);
    $translations = $service->getTranslations('fr');

    expect($translations)->toBeArray();
    expect($translations)->not->toBeEmpty();
});

test('translation service can set and read translation', function () {
    $service = app(TranslationService::class);

    $service->setTranslation('fr', '_test_key_', 'Valeur de test');
    $translations = $service->getTranslations('fr');

    expect($translations)->toHaveKey('_test_key_', 'Valeur de test');

    // Cleanup
    $service->deleteTranslation('fr', '_test_key_');
    $after = $service->getTranslations('fr');
    expect($after)->not->toHaveKey('_test_key_');
});

test('translation service can import from array', function () {
    $service = app(TranslationService::class);

    $service->importFromArray('fr', [
        '_import_test_1_' => 'Import 1',
        '_import_test_2_' => 'Import 2',
    ]);

    $translations = $service->getTranslations('fr');
    expect($translations)->toHaveKey('_import_test_1_', 'Import 1');
    expect($translations)->toHaveKey('_import_test_2_', 'Import 2');

    // Cleanup
    $service->deleteTranslation('fr', '_import_test_1_');
    $service->deleteTranslation('fr', '_import_test_2_');
});

test('spatie translatable package is available', function () {
    expect(trait_exists(\Spatie\Translatable\HasTranslations::class))->toBeTrue();
});

test('translation service can delete translation', function () {
    $service = app(TranslationService::class);
    $service->setTranslation('fr', '_delete_test_', 'À supprimer');
    $service->deleteTranslation('fr', '_delete_test_');

    $translations = $service->getTranslations('fr');
    expect($translations)->not->toHaveKey('_delete_test_');
});
