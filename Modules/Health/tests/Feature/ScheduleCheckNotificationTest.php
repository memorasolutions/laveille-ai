<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Health\Notifications\CheckFailedNotification;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Result;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('affiche une marche à suivre sur un échec du contrôle Schedule', function () {
    // Incident reel du 2026-08-02 10h41-10h42 UTC : le courriel recu ne contenait AUCUNE
    // marche a suivre (contrairement a OPcache), juste "Failed" - corrige ici.
    $check = ScheduleCheck::new();
    $result = Result::make()->failed('The schedule did not run yet.');
    $result->check = $check;

    $courriel = implode("\n", (new CheckFailedNotification([$result]))->toMail()->introLines);

    expect($courriel)
        ->toContain('Marche à suivre')
        ->toContain('surcharge PONCTUELLE')
        ->toContain('reprendra de lui-même');
});

it('n ajoute PAS la marche à suivre Schedule quand le contrôle va bien', function () {
    $check = ScheduleCheck::new();
    $result = Result::make()->ok();
    $result->check = $check;

    $courriel = implode("\n", (new CheckFailedNotification([$result]))->toMail()->introLines);

    expect($courriel)->not->toContain('Marche à suivre');
});
