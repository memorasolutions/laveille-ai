<?php

declare(strict_types=1);

it('Tool model utilise Cache::remember dans healthMetrics', function () {
    $content = file_get_contents(__DIR__.'/../../Modules/Directory/app/Models/Tool.php');
    expect($content)->toContain('Cache::remember')
        ->and($content)->toContain('HEALTH_METRICS_CACHE_KEY');
});

it('Tool model declare HEALTH_METRICS_TTL = 300', function () {
    $content = file_get_contents(__DIR__.'/../../Modules/Directory/app/Models/Tool.php');
    expect($content)->toContain('HEALTH_METRICS_TTL = 300');
});

it('Tool model expose flushHealthMetricsCache helper', function () {
    $content = file_get_contents(__DIR__.'/../../Modules/Directory/app/Models/Tool.php');
    expect($content)->toContain('flushHealthMetricsCache(): void')
        ->and($content)->toContain('Cache::forget');
});

it('Tool model importe facade Cache', function () {
    $content = file_get_contents(__DIR__.'/../../Modules/Directory/app/Models/Tool.php');
    expect($content)->toContain('use Illuminate\Support\Facades\Cache');
});

it('RefreshPricingCommand invalide cache quand modifications faites', function () {
    $content = file_get_contents(__DIR__.'/../../Modules/Directory/app/Console/RefreshPricingCommand.php');
    expect($content)->toContain('Tool::flushHealthMetricsCache()')
        ->and($content)->toContain('!$dryRun && ($modified > 0 || $lowConfidence > 0)');
});
