<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Auth\Livewire\Register;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('registration is refused without the age attestation checkbox', function () {
    Livewire::test(Register::class)
        ->set('first_name', 'Jean')
        ->set('last_name', 'Tremblay')
        ->set('email', 'jean.tremblay@example.com')
        ->set('password', 'MotDePasse123!')
        ->set('password_confirmation', 'MotDePasse123!')
        ->set('age_attested', false)
        ->call('register')
        ->assertHasErrors(['age_attested']);

    $this->assertDatabaseMissing('users', ['email' => 'jean.tremblay@example.com']);
});

test('registration succeeds when the age attestation checkbox is checked', function () {
    Livewire::test(Register::class)
        ->set('first_name', 'Marie')
        ->set('last_name', 'Gagnon')
        ->set('email', 'marie.gagnon@example.com')
        ->set('password', 'MotDePasse123!')
        ->set('password_confirmation', 'MotDePasse123!')
        ->set('age_attested', true)
        ->call('register')
        ->assertHasNoErrors(['age_attested']);

    $this->assertDatabaseHas('users', ['email' => 'marie.gagnon@example.com']);
});
