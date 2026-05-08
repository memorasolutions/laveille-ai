<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

use Illuminate\Support\Collection;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ToolComparisonService;

it('returns criteria schema with 6 sections', function () {
    $svc = new ToolComparisonService();
    $schema = $svc->getCriteriaSchema();

    expect($schema)->toBeArray()
        ->and(array_keys($schema))->toEqual([
            'identite', 'tarification', 'capacites', 'integrations', 'confidentialite', 'editorial',
        ]);

    foreach ($schema as $section) {
        expect($section)->toHaveKeys(['label', 'icon', 'criteria']);
        expect($section['criteria'])->not->toBeEmpty();
    }
});

it('validates and dedup IDs limited to MAX_TOOLS', function () {
    $svc = new ToolComparisonService();

    expect($svc->validateIds('1,2,3,4,5,6,7'))->toEqual([1, 2, 3, 4]);
    expect($svc->validateIds([1, 2, 2, 3]))->toEqual([1, 2, 3]);
    expect($svc->validateIds('1,abc,2,-3,0,4'))->toEqual([1, 2, 4]);
    expect($svc->validateIds(''))->toEqual([]);
    expect($svc->validateIds([]))->toEqual([]);
});

it('formatValue handles null/empty as Non renseigné', function () {
    $svc = new ToolComparisonService();

    expect($svc->formatValue(null, 'text'))->toBe(__('Non renseigné'));
    expect($svc->formatValue('', 'text'))->toBe(__('Non renseigné'));
    expect($svc->formatValue([], 'list'))->toBe(__('Non renseigné'));
});

it('formatValue formats bool', function () {
    $svc = new ToolComparisonService();
    expect($svc->formatValue(true, 'bool'))->toBe(__('Oui'));
    expect($svc->formatValue(false, 'bool'))->toBe(__('Non'));
});

it('formatValue formats pricing labels', function () {
    $svc = new ToolComparisonService();
    expect($svc->formatValue('free', 'pricing'))->toBe(__('🆓 Gratuit'));
    expect($svc->formatValue('freemium', 'pricing'))->toBe(__('💎 Freemium'));
    expect($svc->formatValue('paid', 'pricing'))->toBe(__('💰 Payant'));
});

it('formatValue formats opt_out states', function () {
    $svc = new ToolComparisonService();
    expect($svc->formatValue('yes', 'opt_out'))->toBe(__('✅ Oui'));
    expect($svc->formatValue('no', 'opt_out'))->toBe(__('❌ Non'));
    expect($svc->formatValue('unknown', 'opt_out'))->toBe(__('❔ Inconnu'));
});

it('formatValue formats list as comma separated', function () {
    $svc = new ToolComparisonService();
    expect($svc->formatValue(['text', 'image', 'code'], 'list'))->toBe('text, image, code');
});

it('computeDiff returns neutral for all tools when ≤1 has data', function () {
    $svc = new ToolComparisonService();
    $tools = collect([
        new Tool(['name' => 'A', 'is_multimodal' => null]),
        new Tool(['name' => 'B', 'is_multimodal' => null]),
    ]);
    $tools[0]->id = 1;
    $tools[1]->id = 2;

    $diff = $svc->computeDiff($tools, ['accessor' => 'is_multimodal', 'type' => 'bool', 'better' => 'true', 'label' => 'M']);
    expect($diff)->toEqual([1 => 'neutral', 2 => 'neutral']);
});

it('computeDiff bool: true=best when better=true', function () {
    $svc = new ToolComparisonService();
    $tools = collect([
        tap(new Tool(['is_multimodal' => true]), fn ($t) => $t->id = 1),
        tap(new Tool(['is_multimodal' => false]), fn ($t) => $t->id = 2),
    ]);

    $diff = $svc->computeDiff($tools, ['accessor' => 'is_multimodal', 'type' => 'bool', 'better' => 'true', 'label' => 'M']);
    expect($diff[1])->toBe('best');
    expect($diff[2])->toBe('worst');
});

it('computeDiff pricing: free beats paid', function () {
    $svc = new ToolComparisonService();
    $tools = collect([
        tap(new Tool(['pricing' => 'free']), fn ($t) => $t->id = 1),
        tap(new Tool(['pricing' => 'paid']), fn ($t) => $t->id = 2),
    ]);

    $diff = $svc->computeDiff($tools, ['accessor' => 'pricing', 'type' => 'pricing', 'better' => 'free', 'label' => 'P']);
    expect($diff[1])->toBe('best');
    expect($diff[2])->toBe('worst');
});

it('computeDiff lifecycle: active=best, deprecated=worst', function () {
    $svc = new ToolComparisonService();
    $tools = collect([
        tap(new Tool(['lifecycle_status' => 'active']), fn ($t) => $t->id = 1),
        tap(new Tool(['lifecycle_status' => 'deprecated']), fn ($t) => $t->id = 2),
    ]);

    $diff = $svc->computeDiff($tools, ['accessor' => 'lifecycle_status', 'type' => 'lifecycle', 'better' => 'active', 'label' => 'L']);
    expect($diff[1])->toBe('best');
    expect($diff[2])->toBe('worst');
});

it('computeDiff year: newer=best by default', function () {
    $svc = new ToolComparisonService();
    $tools = collect([
        tap(new Tool(['launch_year' => 2024]), fn ($t) => $t->id = 1),
        tap(new Tool(['launch_year' => 2020]), fn ($t) => $t->id = 2),
    ]);

    $diff = $svc->computeDiff($tools, ['accessor' => 'launch_year', 'type' => 'year', 'better' => 'newer', 'label' => 'Y']);
    expect($diff[1])->toBe('best');
    expect($diff[2])->toBe('worst');
});

it('MAX_TOOLS constant equals 4', function () {
    expect(ToolComparisonService::MAX_TOOLS)->toBe(4);
});
