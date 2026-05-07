<?php

declare(strict_types=1);

/**
 * Tests reflexifs Subscriber state (isConfirmed, isActive, scopeActive).
 *
 * Pattern Mockery::mock(Model::class)->makePartial() validé S82 (#221+#223).
 * Zéro DB, 100% logique pure model.
 *
 * @author  MEMORA solutions <info@memora.ca>
 * @project la-veille-de-stef-v2
 * @session S83 #225 Pest étendus
 */

use Modules\Newsletter\Models\Subscriber;

it('isConfirmed returns true when confirmed_at is set', function () {
    $sub = Mockery::mock(Subscriber::class)->makePartial();
    $sub->setRawAttributes([
        'confirmed_at' => '2026-04-30 12:00:00',
        'unsubscribed_at' => null,
    ]);

    expect($sub->isConfirmed())->toBeTrue();
});

it('isConfirmed returns false when confirmed_at is null', function () {
    $sub = Mockery::mock(Subscriber::class)->makePartial();
    $sub->setRawAttributes([
        'confirmed_at' => null,
        'unsubscribed_at' => null,
    ]);

    expect($sub->isConfirmed())->toBeFalse();
});

it('isActive returns true when confirmed AND not unsubscribed', function () {
    $sub = Mockery::mock(Subscriber::class)->makePartial();
    $sub->setRawAttributes([
        'confirmed_at' => '2026-04-30 12:00:00',
        'unsubscribed_at' => null,
    ]);

    expect($sub->isActive())->toBeTrue();
});

it('isActive returns false when not confirmed', function () {
    $sub = Mockery::mock(Subscriber::class)->makePartial();
    $sub->setRawAttributes([
        'confirmed_at' => null,
        'unsubscribed_at' => null,
    ]);

    expect($sub->isActive())->toBeFalse();
});

it('isActive returns false when unsubscribed even if confirmed', function () {
    $sub = Mockery::mock(Subscriber::class)->makePartial();
    $sub->setRawAttributes([
        'confirmed_at' => '2026-01-01 10:00:00',
        'unsubscribed_at' => '2026-04-15 08:30:00',
    ]);

    expect($sub->isActive())->toBeFalse();
});
