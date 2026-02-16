<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FrontTheme\Http\Middleware\ThemeMiddleware;
use Modules\FrontTheme\Services\ThemeService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('theme service exists', function () {
    expect(class_exists(ThemeService::class))->toBeTrue();
});

test('theme middleware exists', function () {
    expect(class_exists(ThemeMiddleware::class))->toBeTrue();
});

test('theme service get available themes returns array', function () {
    $service = new ThemeService;
    $themes = $service->getAvailableThemes();

    expect($themes)->toBeArray();
});

test('laravel themer package is available', function () {
    expect(class_exists(\Qirolab\Theme\Theme::class))->toBeTrue();
});
