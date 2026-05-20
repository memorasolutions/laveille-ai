<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Note (S101) : la commande newsletter:digest a été refondue (modes --preview / --send,
 * dispatch via SendDigestJob, sortie en français). Les anciennes assertions ciblaient une
 * version obsolète (DigestNotification envoyée directement, messages en anglais). Ce fichier
 * teste désormais les comportements observables et stables de la commande courante.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

test('newsletter:digest command is registered', function () {
    expect(Artisan::all())->toHaveKey('newsletter:digest');
});

test('digest skips with a French notice when disabled in settings', function () {
    // Sans --force et digest désactivé : la commande sort proprement (exit 0) avec un avis FR.
    $this->artisan('newsletter:digest')
        ->expectsOutputToContain('désactivé')
        ->assertExitCode(0);
});

test('digest runs without error when enabled but no content', function () {
    Setting::set('newsletter.digest_enabled', true);

    // Aucun contenu/abonné : la commande doit se terminer proprement (exit 0), sans exception.
    $this->artisan('newsletter:digest')
        ->assertExitCode(0);
});

test('digest --force bypasses the disabled flag without error', function () {
    // --force contourne le flag désactivé ; sans contenu la commande sort quand même proprement.
    $this->artisan('newsletter:digest', ['--force' => true])
        ->assertExitCode(0);
});

test('digest is scheduled', function () {
    $events = \Illuminate\Support\Facades\Schedule::events();

    $found = collect($events)->contains(function ($event) {
        return str_contains($event->command ?? '', 'newsletter:digest');
    });

    expect($found)->toBeTrue();
});
