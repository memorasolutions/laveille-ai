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

test('PricingStatsCommand source uses Tool::pricingDistribution', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('Tool::pricingDistribution()');
});

test('PricingStatsCommand source uses autoFlagged scope', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('->autoFlagged()');
});

test('PricingStatsCommand source uses userSubmitted scope', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('->userSubmitted()');
});

test('PricingStatsCommand source uses notArchived scope', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('->notArchived()');
});

test('PricingStatsCommand registered via artisan list', function () {
    $output = \Illuminate\Support\Facades\Artisan::all();
    expect(array_key_exists('directory:pricing-stats', $output))->toBeTrue();
});
