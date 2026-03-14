<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Modules\ABTest\Models\ABParticipation;
use Modules\ABTest\Models\Experiment;
use Modules\ABTest\Services\ABTestService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates an experiment via factory', function () {
    $experiment = Experiment::factory()->create([
        'name' => 'Button Color Test',
        'variants' => ['control', 'variant_a'],
        'status' => 'draft',
    ]);

    expect($experiment)->toBeInstanceOf(Experiment::class)
        ->and($experiment->name)->toBe('Button Color Test')
        ->and($experiment->variants)->toBe(['control', 'variant_a'])
        ->and($experiment->status)->toBe('draft');

    $this->assertDatabaseHas('ab_experiments', ['name' => 'Button Color Test']);
});

it('starts an experiment', function () {
    $experiment = Experiment::factory()->create(['status' => 'draft']);

    $experiment->start();

    expect($experiment->fresh()->status)->toBe('running')
        ->and($experiment->fresh()->started_at)->not->toBeNull();
});

it('completes an experiment with winner', function () {
    $experiment = Experiment::factory()->running()->create();

    $experiment->complete('variant_a');

    $fresh = $experiment->fresh();
    expect($fresh->status)->toBe('completed')
        ->and($fresh->winner)->toBe('variant_a')
        ->and($fresh->ended_at)->not->toBeNull();
});

it('assigns a variant to a user', function () {
    $service = new ABTestService;
    $experiment = Experiment::factory()->running()->create([
        'variants' => ['control', 'variant_a'],
    ]);

    $variant = $service->assignVariant($experiment, userId: 42);

    expect($variant)->toBeIn(['control', 'variant_a']);

    $this->assertDatabaseHas('ab_participations', [
        'experiment_id' => $experiment->id,
        'user_id' => 42,
    ]);
});

it('returns same variant for same user', function () {
    $service = new ABTestService;
    $experiment = Experiment::factory()->running()->create([
        'variants' => ['control', 'variant_a'],
    ]);

    $first = $service->assignVariant($experiment, userId: 99);
    $second = $service->assignVariant($experiment, userId: 99);

    expect($first)->toBe($second);

    expect(ABParticipation::where('experiment_id', $experiment->id)
        ->where('user_id', 99)
        ->count())->toBe(1);
});

it('assigns a variant via session id when no user id', function () {
    $service = new ABTestService;
    $experiment = Experiment::factory()->running()->create([
        'variants' => ['control', 'variant_a'],
    ]);
    $sessionId = 'test-session-abc123';

    $variant = $service->assignVariant($experiment, sessionId: $sessionId);

    expect($variant)->toBeIn(['control', 'variant_a']);

    $this->assertDatabaseHas('ab_participations', [
        'experiment_id' => $experiment->id,
        'session_id' => $sessionId,
    ]);
});

it('converts a participation', function () {
    $service = new ABTestService;
    $experiment = Experiment::factory()->running()->create();

    ABParticipation::factory()->create([
        'experiment_id' => $experiment->id,
        'user_id' => 55,
        'session_id' => null,
        'variant' => 'control',
        'converted_at' => null,
    ]);

    $result = $service->convert($experiment, userId: 55);

    expect($result)->toBeTrue();

    $participation = ABParticipation::where('experiment_id', $experiment->id)
        ->where('user_id', 55)
        ->first();

    expect($participation->converted_at)->not->toBeNull();
});

it('returns false for already converted participation', function () {
    $service = new ABTestService;
    $experiment = Experiment::factory()->running()->create();

    ABParticipation::factory()->converted()->create([
        'experiment_id' => $experiment->id,
        'user_id' => 77,
        'session_id' => null,
        'variant' => 'variant_a',
    ]);

    $result = $service->convert($experiment, userId: 77);

    expect($result)->toBeFalse();
});

it('gets correct results with conversions', function () {
    $service = new ABTestService;
    $experiment = Experiment::factory()->running()->create([
        'variants' => ['control', 'variant_a'],
    ]);

    // 3 participations in control, 2 converted
    ABParticipation::factory()->count(2)->converted()->create([
        'experiment_id' => $experiment->id,
        'session_id' => null,
        'variant' => 'control',
    ]);
    ABParticipation::factory()->create([
        'experiment_id' => $experiment->id,
        'session_id' => null,
        'variant' => 'control',
        'converted_at' => null,
    ]);

    // 2 participations in variant_a, 1 converted
    ABParticipation::factory()->converted()->create([
        'experiment_id' => $experiment->id,
        'session_id' => null,
        'variant' => 'variant_a',
    ]);
    ABParticipation::factory()->create([
        'experiment_id' => $experiment->id,
        'session_id' => null,
        'variant' => 'variant_a',
        'converted_at' => null,
    ]);

    $results = $service->getResults($experiment);

    expect($results)->toHaveKeys(['control', 'variant_a'])
        ->and($results['control']['participants'])->toBe(3)
        ->and($results['control']['conversions'])->toBe(2)
        ->and($results['control']['rate'])->toBe(round(2 / 3, 4))
        ->and($results['variant_a']['participants'])->toBe(2)
        ->and($results['variant_a']['conversions'])->toBe(1)
        ->and($results['variant_a']['rate'])->toBe(0.5);
});

it('scope running returns only running experiments', function () {
    Experiment::factory()->running()->count(2)->create();
    Experiment::factory()->create(['status' => 'draft']);
    Experiment::factory()->completed()->create();

    $running = Experiment::running()->get();

    expect($running)->toHaveCount(2);
    $running->each(fn ($e) => expect($e->status)->toBe('running'));
});

it('scope completed returns only completed experiments', function () {
    Experiment::factory()->completed()->count(3)->create();
    Experiment::factory()->running()->create();
    Experiment::factory()->create(['status' => 'draft']);

    $completed = Experiment::completed()->get();

    expect($completed)->toHaveCount(3);
    $completed->each(fn ($e) => expect($e->status)->toBe('completed'));
});

it('isRunning returns true only for running experiments', function () {
    $running = Experiment::factory()->running()->create();
    $draft = Experiment::factory()->create(['status' => 'draft']);
    $completed = Experiment::factory()->completed()->create();

    expect($running->isRunning())->toBeTrue()
        ->and($draft->isRunning())->toBeFalse()
        ->and($completed->isRunning())->toBeFalse();
});

it('getResults returns zero rate when no participants', function () {
    $experiment = Experiment::factory()->running()->create([
        'variants' => ['control', 'variant_a'],
    ]);

    $results = $experiment->getResults();

    expect($results['control']['participants'])->toBe(0)
        ->and($results['control']['conversions'])->toBe(0)
        ->and($results['control']['rate'])->toBe(0.0)
        ->and($results['variant_a']['participants'])->toBe(0)
        ->and($results['variant_a']['rate'])->toBe(0.0);
});
