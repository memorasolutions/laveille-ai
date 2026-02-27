<?php

declare(strict_types=1);

test('telescope gate uses permission-based access', function () {
    $content = file_get_contents(base_path('Modules/Core/app/Providers/TelescopeServiceProvider.php'));
    expect($content)->toContain("can('view_telescope')");
});

test('horizon gate uses permission-based access', function () {
    $content = file_get_contents(base_path('Modules/Core/app/Providers/HorizonServiceProvider.php'));
    expect($content)->toContain("can('view_horizon')");
});

test('gitattributes has export-ignore rules', function () {
    $content = file_get_contents(base_path('.gitattributes'));
    expect($content)->toContain('export-ignore')
        ->toContain('docker');
});

test('makefile exists with targets', function () {
    expect(file_exists(base_path('Makefile')))->toBeTrue();
    $content = file_get_contents(base_path('Makefile'));
    expect($content)->toContain('test')
        ->toContain('lint')
        ->toContain('deploy')
        ->toContain('docker-up');
});

test('supervisor config exists', function () {
    expect(file_exists(base_path('docker/supervisor/laravel-worker.conf')))->toBeTrue();
});

test('locale is configured as french', function () {
    $env = file_get_contents(base_path('.env.example'));
    expect($env)->toContain('APP_LOCALE=fr');
});

test('french translations exist', function () {
    expect(file_exists(base_path('lang/fr.json')))->toBeTrue();
});

test('ide helper package is installed', function () {
    expect(class_exists(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class))->toBeTrue();
});

test('ide helper files are gitignored', function () {
    $content = file_get_contents(base_path('.gitignore'));
    expect($content)->toContain('_ide_helper.php');
});
