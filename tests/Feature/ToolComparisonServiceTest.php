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

    expect($svc->validateIds('1,2,3,4,5,6,7,8,9'))->toEqual([1, 2, 3, 4, 5, 6]);
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

it('MAX_TOOLS constant equals 6', function () {
    expect(ToolComparisonService::MAX_TOOLS)->toBe(6);
});

// ─────────── S88 bonifs ───────────

it('compare-bar component renders animations + selection mode CSS', function () {
    $rendered = view('directory::components.compare-bar')->render();

    expect($rendered)->toContain('lvChipSlideIn');
    expect($rendered)->toContain('lvBounce');
    expect($rendered)->toContain('lv-selection-mode');
    expect($rendered)->toContain('selectionMode');
    expect($rendered)->toContain('toggleMode');
    expect($rendered)->toContain('thumbs');
    expect($rendered)->toContain('prefers-reduced-motion');
});

it('compare-toggle icon variant uses 32x32 circle (post S89.5 refonte)', function () {
    $tool = tap(new \Modules\Directory\Models\Tool(['name' => 'Test', 'url' => 'https://example.com']), fn ($t) => $t->id = 999);
    $rendered = view('directory::components.compare-toggle', ['tool' => $tool, 'variant' => 'icon'])->render();

    expect($rendered)->toContain('width: 32px');
    expect($rendered)->toContain('height: 32px');
    expect($rendered)->toContain('data-cmp-card-id');
    expect($rendered)->toContain('bounce');
});

it('compare-toggle pill variant also 44px min-height', function () {
    $tool = tap(new \Modules\Directory\Models\Tool(['name' => 'Test', 'url' => 'https://example.com']), fn ($t) => $t->id = 999);
    $rendered = view('directory::components.compare-toggle', ['tool' => $tool, 'variant' => 'pill'])->render();

    expect($rendered)->toContain('min-height: 44px');
});

// ─────────── S89.5 refonte selection state ───────────

it('compare-toggle icon variant uses circle 32x32 absolute corner', function () {
    $tool = tap(new \Modules\Directory\Models\Tool(['name' => 'T', 'url' => 'https://t.com']), fn ($t) => $t->id = 1);
    $rendered = view('directory::components.compare-toggle', ['tool' => $tool, 'variant' => 'icon'])->render();

    expect($rendered)->toContain('lv-cmp-toggle--icon');
    expect($rendered)->toContain('width: 32px');
    expect($rendered)->toContain('height: 32px');
    expect($rendered)->toContain('border-radius: 50%');
    expect($rendered)->toContain('position: absolute');
});

it('compare-toggle icon variant has no square unicode checkbox', function () {
    $tool = tap(new \Modules\Directory\Models\Tool(['name' => 'T']), fn ($t) => $t->id = 1);
    $rendered = view('directory::components.compare-toggle', ['tool' => $tool, 'variant' => 'icon'])->render();

    expect($rendered)->not->toContain('☐');
});

it('rt-card is-selected has overlay with pointer-events none', function () {
    $tool = tap(new \Modules\Directory\Models\Tool(['name' => 'T', 'url' => 'https://t.com']), fn ($t) => $t->id = 1);
    $rendered = view('directory::components.compare-toggle', ['tool' => $tool, 'variant' => 'icon'])->render();

    expect($rendered)->toContain('.rt-card.is-selected');
    expect($rendered)->toContain('pointer-events: none');
    expect($rendered)->toContain('::before');
});

it('compare-bar opens at count=1 and shows adaptive label', function () {
    $rendered = view('directory::components.compare-bar')->render();

    expect($rendered)->toContain("'is-open': \$store.compare.count >= 1");
    expect($rendered)->toContain('Sélectionnez au moins 2 outils');
    expect($rendered)->toContain('canCompare');
});

// ─────────── Versioning SemVer ───────────

it('lv_semver returns SemVer string from config', function () {
    expect(lv_semver())->toMatch('/^\d+\.\d+\.\d+$/');
});

it('lv_version returns vX.Y.Z prefixed', function () {
    expect(lv_version(false))->toStartWith('v');
    expect(lv_version(false))->toMatch('/^v\d+\.\d+\.\d+$/');
});

it('lv_version with sha contains separator', function () {
    $v = lv_version(true);
    expect($v)->toStartWith('v');
    if (lv_git_sha()) {
        expect($v)->toContain('·');
        expect($v)->toMatch('/v\d+\.\d+\.\d+ · [a-f0-9]{8}/');
    }
});

// ─────────── S89 refonte ───────────

it('MAX_TOOLS bumped to 6 for slider support', function () {
    expect(ToolComparisonService::MAX_TOOLS)->toBe(6);
});

it('MISMATCH_THRESHOLD_PCT exposed as 50.0', function () {
    expect(ToolComparisonService::MISMATCH_THRESHOLD_PCT)->toBe(50.0);
});

it('validateIds caps at 6 tools', function () {
    $svc = new ToolComparisonService();
    expect($svc->validateIds('1,2,3,4,5,6,7,8'))->toEqual([1, 2, 3, 4, 5, 6]);
});

it('computeMismatch returns full overlap when tools share categories', function () {
    $svc = new ToolComparisonService();

    $cat = tap(new \Modules\Directory\Models\Category(['name' => 'LLM']), fn ($c) => $c->id = 100);
    $tools = collect([
        tap(new \Modules\Directory\Models\Tool(['name' => 'A']), function ($t) use ($cat) {
            $t->id = 1;
            $t->setRelation('categories', collect([$cat]));
        }),
        tap(new \Modules\Directory\Models\Tool(['name' => 'B']), function ($t) use ($cat) {
            $t->id = 2;
            $t->setRelation('categories', collect([$cat]));
        }),
    ]);

    $result = $svc->computeMismatch($tools);
    expect($result['overlap_pct'])->toBe(100.0);
    expect($result['is_mismatch'])->toBeFalse();
    expect($result['dominant_tool_ids'])->toEqual([1, 2]);
});

it('computeMismatch detects mismatch when tools have no shared categories', function () {
    $svc = new ToolComparisonService();

    $catLlm = tap(new \Modules\Directory\Models\Category(['name' => 'LLM']), fn ($c) => $c->id = 100);
    $catImg = tap(new \Modules\Directory\Models\Category(['name' => 'Image']), fn ($c) => $c->id = 200);
    $catAudio = tap(new \Modules\Directory\Models\Category(['name' => 'Audio']), fn ($c) => $c->id = 300);

    $tools = collect([
        tap(new \Modules\Directory\Models\Tool(['name' => 'LLM tool']), function ($t) use ($catLlm) {
            $t->id = 1;
            $t->setRelation('categories', collect([$catLlm]));
        }),
        tap(new \Modules\Directory\Models\Tool(['name' => 'Img tool']), function ($t) use ($catImg) {
            $t->id = 2;
            $t->setRelation('categories', collect([$catImg]));
        }),
        tap(new \Modules\Directory\Models\Tool(['name' => 'Audio tool']), function ($t) use ($catAudio) {
            $t->id = 3;
            $t->setRelation('categories', collect([$catAudio]));
        }),
    ]);

    $result = $svc->computeMismatch($tools);
    expect($result['overlap_pct'])->toBeLessThan(50.0);
    expect($result['is_mismatch'])->toBeTrue();
    expect($result['shared_categories']->count())->toBe(0);
});

it('classifyCriteria flags criteria as common or specific based on 50pct threshold', function () {
    $svc = new ToolComparisonService();

    // Tool A : has underlying_model, no opt_out_training
    // Tool B : has both
    // Tool C : has neither
    $tools = collect([
        tap(new \Modules\Directory\Models\Tool(['underlying_model' => 'GPT-4o', 'opt_out_training' => null]), fn ($t) => $t->id = 1),
        tap(new \Modules\Directory\Models\Tool(['underlying_model' => 'Claude', 'opt_out_training' => 'yes']), fn ($t) => $t->id = 2),
        tap(new \Modules\Directory\Models\Tool(['underlying_model' => null, 'opt_out_training' => null]), fn ($t) => $t->id = 3),
    ]);

    $schema = [
        'capacites' => [
            'label' => 'Cap', 'icon' => '🤖',
            'criteria' => [
                'underlying_model' => ['label' => 'Modèle', 'accessor' => 'underlying_model', 'type' => 'text', 'better' => 'neutral'],
                'opt_out_training' => ['label' => 'Opt-out', 'accessor' => 'opt_out_training', 'type' => 'opt_out', 'better' => 'yes'],
            ],
        ],
    ];

    $result = $svc->classifyCriteria($tools, $schema);
    // 2/3 outils ont underlying_model -> common (66%)
    expect($result['capacites']['underlying_model'])->toBe('common');
    // 1/3 outil a opt_out_training -> specific (33%)
    expect($result['capacites']['opt_out_training'])->toBe('specific');
});

it('computeMismatch returns 100pct when only one tool', function () {
    $svc = new ToolComparisonService();
    $tools = collect([
        tap(new \Modules\Directory\Models\Tool(['name' => 'Solo']), function ($t) {
            $t->id = 1;
            $t->setRelation('categories', collect());
        }),
    ]);

    $result = $svc->computeMismatch($tools);
    expect($result['overlap_pct'])->toBe(100.0);
    expect($result['is_mismatch'])->toBeFalse();
});
