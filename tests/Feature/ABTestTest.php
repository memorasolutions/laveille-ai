<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ABTest\Models\ABParticipation;
use Modules\ABTest\Models\Experiment;
use Modules\ABTest\Services\ABTestService;

uses(RefreshDatabase::class);

test('experiment model can be created', function () {
    $experiment = Experiment::factory()->create();

    $this->assertDatabaseHas('ab_experiments', [
        'id' => $experiment->id,
        'status' => 'draft',
    ]);

    expect($experiment->variants)->toBeArray()
        ->and($experiment->variants)->toContain('control');
});

test('experiment can start', function () {
    $experiment = Experiment::factory()->create();

    $experiment->start();

    expect($experiment->status)->toBe('running')
        ->and($experiment->started_at)->not->toBeNull();
});

test('experiment can complete', function () {
    $experiment = Experiment::factory()->running()->create();

    $experiment->complete('variant_a');

    expect($experiment->status)->toBe('completed')
        ->and($experiment->winner)->toBe('variant_a')
        ->and($experiment->ended_at)->not->toBeNull();
});

test('experiment scopes work', function () {
    Experiment::factory()->create();
    Experiment::factory()->running()->create();
    Experiment::factory()->completed()->create();

    expect(Experiment::running()->count())->toBe(1)
        ->and(Experiment::completed()->count())->toBe(1);
});

test('experiment getResults returns correct data', function () {
    $experiment = Experiment::factory()->running()->create();

    ABParticipation::create(['experiment_id' => $experiment->id, 'variant' => 'control', 'user_id' => 1]);
    ABParticipation::create(['experiment_id' => $experiment->id, 'variant' => 'control', 'user_id' => 2, 'converted_at' => now()]);
    ABParticipation::create(['experiment_id' => $experiment->id, 'variant' => 'variant_a', 'user_id' => 3]);

    $results = $experiment->getResults();

    expect($results['control']['participants'])->toBe(2)
        ->and($results['control']['conversions'])->toBe(1)
        ->and($results['control']['rate'])->toBe(0.5)
        ->and($results['variant_a']['participants'])->toBe(1)
        ->and($results['variant_a']['conversions'])->toBe(0);
});

test('service assigns variant consistently', function () {
    $service = app(ABTestService::class);
    $experiment = Experiment::factory()->running()->create();

    $variant1 = $service->assignVariant($experiment, 1);
    $variant2 = $service->assignVariant($experiment, 1);

    expect($variant1)->toBe($variant2);
    expect(ABParticipation::count())->toBe(1);
});

test('service assigns both variants over multiple users', function () {
    $service = app(ABTestService::class);
    $experiment = Experiment::factory()->running()->create(['variants' => ['control', 'variant_a']]);

    $variants = [];
    for ($i = 1; $i <= 30; $i++) {
        $variants[] = $service->assignVariant($experiment, $i);
    }

    expect(array_unique($variants))->toContain('control')
        ->and(array_unique($variants))->toContain('variant_a');
});

test('service converts participation', function () {
    $service = app(ABTestService::class);
    $experiment = Experiment::factory()->running()->create();

    $service->assignVariant($experiment, 1);
    $result = $service->convert($experiment, 1);

    expect($result)->toBeTrue();

    $participation = ABParticipation::where('experiment_id', $experiment->id)
        ->where('user_id', 1)
        ->first();

    expect($participation->converted_at)->not->toBeNull();
});

test('service convert returns false if already converted', function () {
    $service = app(ABTestService::class);
    $experiment = Experiment::factory()->running()->create();

    $service->assignVariant($experiment, 1);

    expect($service->convert($experiment, 1))->toBeTrue()
        ->and($service->convert($experiment, 1))->toBeFalse();
});

test('admin can view experiments index', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get(route('admin.experiments.index'))
        ->assertOk();
});

test('admin can create experiment', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->post(route('admin.experiments.store'), [
            'name' => 'Homepage CTA Test',
            'variants' => ['control', 'variant_a', 'variant_b'],
        ])
        ->assertRedirect();

    $experiment = Experiment::where('name', 'Homepage CTA Test')->first();

    expect($experiment)->not->toBeNull()
        ->and($experiment->variants)->toBe(['control', 'variant_a', 'variant_b'])
        ->and($experiment->slug)->toBe('homepage-cta-test');
});

test('admin can delete experiment', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $experiment = Experiment::factory()->create();

    $this->actingAs($user)
        ->delete(route('admin.experiments.destroy', $experiment))
        ->assertRedirect();

    $this->assertDatabaseMissing('ab_experiments', ['id' => $experiment->id]);
});
