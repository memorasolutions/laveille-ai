<?php
declare(strict_types=1);

use Modules\Directory\Console\RefreshPricingCommand;
use Modules\Directory\Models\ToolPricingReport;
use Modules\Directory\Services\OpenRouterService;
use Modules\Directory\Support\PricingCategories;

test('OpenRouterService class exists', function () {
    expect(class_exists(OpenRouterService::class))->toBeTrue();
});

test('OpenRouterService classifyPricing method exists', function () {
    expect(method_exists(OpenRouterService::class, 'classifyPricing'))->toBeTrue();
});

test('RefreshPricingCommand class exists', function () {
    expect(class_exists(RefreshPricingCommand::class))->toBeTrue();
});

test('RefreshPricingCommand has reset-suspects option', function () {
    $command = app(RefreshPricingCommand::class);
    $hasOption = false;
    foreach ($command->getDefinition()->getOptions() as $option) {
        if ($option->getName() === 'reset-suspects') {
            $hasOption = true;
            break;
        }
    }
    expect($hasOption)->toBeTrue();
});

test('RefreshPricingCommand has batch option', function () {
    $command = app(RefreshPricingCommand::class);
    $hasOption = false;
    foreach ($command->getDefinition()->getOptions() as $option) {
        if ($option->getName() === 'batch') {
            $hasOption = true;
            break;
        }
    }
    expect($hasOption)->toBeTrue();
});

test('PricingCategories FREE_TRIAL constant equals free_trial', function () {
    expect(PricingCategories::FREE_TRIAL)->toBe('free_trial');
});

test('PricingCategories values contains free_trial', function () {
    expect(PricingCategories::values())->toContain('free_trial');
});

test('PricingCategories labels contains key free_trial', function () {
    expect(array_key_exists('free_trial', PricingCategories::labels()))->toBeTrue();
});

test('ToolPricingReport scopeForTool method exists', function () {
    expect(method_exists(ToolPricingReport::class, 'scopeForTool'))->toBeTrue();
});

test('RefreshPricingCommand uses HasKillSwitch trait', function () {
    $traits = class_uses(RefreshPricingCommand::class);
    expect(in_array(\App\Console\Concerns\HasKillSwitch::class, $traits))->toBeTrue();
});

test('RefreshPricingCommand signature contains directory:refresh-pricing', function () {
    $command = app(RefreshPricingCommand::class);
    expect($command->getName())->toBe('directory:refresh-pricing');
});

test('OpenRouterService classifyPricing accepts 1 parameter string', function () {
    $reflection = new ReflectionClass(OpenRouterService::class);
    $method = $reflection->getMethod('classifyPricing');
    $params = $method->getParameters();
    expect(count($params))->toBe(1);
    expect($params[0]->getType()->getName())->toBe('string');
});
