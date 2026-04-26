<?php
declare(strict_types=1);

use Modules\Directory\Models\Tool;

test('Tool has scopeNotArchived method', function () {
    expect(method_exists(Tool::class, 'scopeNotArchived'))->toBeTrue();
});

test('scopeNotArchived produces SQL excluding archived', function () {
    $sql = Tool::query()->notArchived()->toSql();
    expect($sql)->toContain('lifecycle_status');
    expect($sql)->toContain('!=');
});

test('scopeNotArchived can chain with published', function () {
    $sql = Tool::published()->notArchived()->toSql();
    expect($sql)->toContain("status");
    expect($sql)->toContain('lifecycle_status');
});

test('scopeNotArchived bindings include archived value', function () {
    $bindings = Tool::query()->notArchived()->getBindings();
    expect($bindings)->toContain('archived');
});

test('RefreshPricingCommand source uses notArchived scope', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('->notArchived()');
});
