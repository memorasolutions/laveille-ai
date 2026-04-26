<?php
declare(strict_types=1);

use Modules\Directory\Console\RefreshPricingCommand;

test('RefreshPricingCommand has confidence-threshold option', function () {
    $command = app(RefreshPricingCommand::class);
    $hasOption = false;
    foreach ($command->getDefinition()->getOptions() as $option) {
        if ($option->getName() === 'confidence-threshold') {
            $hasOption = true;
            break;
        }
    }
    expect($hasOption)->toBeTrue();
});

test('RefreshPricingCommand confidence-threshold default is 0.6', function () {
    $command = app(RefreshPricingCommand::class);
    $option = $command->getDefinition()->getOption('confidence-threshold');
    expect($option->getDefault())->toBe('0.6');
});

test('RefreshPricingCommand signature contains confidence-threshold', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('--confidence-threshold=0.6');
});

test('RefreshPricingCommand source uses confidenceThreshold variable', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('$confidenceThreshold');
});

test('RefreshPricingCommand comparison uses variable not hardcoded 0.6', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('$confidence < $confidenceThreshold');
    expect($source)->not->toContain('$confidence < 0.6');
});

test('RefreshPricingCommand reads option as float', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain("(float) \$this->option('confidence-threshold')");
});
