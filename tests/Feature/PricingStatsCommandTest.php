<?php
declare(strict_types=1);

use Modules\Directory\Console\PricingStatsCommand;

test('PricingStatsCommand class exists', function () {
    expect(class_exists(PricingStatsCommand::class))->toBeTrue();
});

test('PricingStatsCommand signature is directory:pricing-stats', function () {
    $command = app(PricingStatsCommand::class);
    expect($command->getName())->toBe('directory:pricing-stats');
});

test('PricingStatsCommand handle returns int', function () {
    $reflection = new ReflectionMethod(PricingStatsCommand::class, 'handle');
    expect($reflection->getReturnType()?->getName())->toBe('int');
});

test('PricingStatsCommand source uses Tool::healthMetrics distribution', function () {
    // Refactor : la distribution provient désormais de Tool::healthMetrics()['distribution'].
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('Tool::healthMetrics()');
    expect($source)->toContain("['distribution']");
});

test('PricingStatsCommand source uses autoFlagged scope', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('->autoFlagged()');
});

test('PricingStatsCommand source uses userSubmitted scope', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('->userSubmitted()');
});

test('PricingStatsCommand source surfaces drift and never-checked metrics', function () {
    // Refactor : drift/never-checked proviennent de Tool::healthMetrics() (clés drift_90 / never_checked).
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain("['drift_90']");
    expect($source)->toContain("['never_checked']");
});

test('PricingStatsCommand registered via artisan list', function () {
    $output = \Illuminate\Support\Facades\Artisan::all();
    expect(array_key_exists('directory:pricing-stats', $output))->toBeTrue();
});
