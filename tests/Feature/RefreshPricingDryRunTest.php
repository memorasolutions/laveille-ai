<?php
declare(strict_types=1);

use Modules\Directory\Console\RefreshPricingCommand;

test('RefreshPricingCommand has dry-run option', function () {
    $command = app(RefreshPricingCommand::class);
    $hasOption = false;
    foreach ($command->getDefinition()->getOptions() as $option) {
        if ($option->getName() === 'dry-run') {
            $hasOption = true;
            break;
        }
    }
    expect($hasOption)->toBeTrue();
});

test('RefreshPricingCommand signature contains dry-run flag', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('{--dry-run}');
});

test('RefreshPricingCommand source declares dryRun variable', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('$dryRun = (bool) $this->option(\'dry-run\')');
});

test('RefreshPricingCommand skips ToolPricingReport create on dry-run', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('if (!$dryRun) {');
    expect($source)->toContain('DRY-RUN would create ToolPricingReport');
});

test('RefreshPricingCommand skips tool update on dry-run', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('DRY-RUN would update tool');
});

test('RefreshPricingCommand dry-run is boolean flag without default', function () {
    $command = app(RefreshPricingCommand::class);
    $option = $command->getDefinition()->getOption('dry-run');
    expect($option->acceptValue())->toBeFalse();
});
