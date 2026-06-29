<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\RolesTable;
use Modules\Backoffice\Livewire\SubscribersTable;
use Modules\Backoffice\Livewire\UsersTable;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);
});

test('UsersTable : loadMore augmente perPage d\'un pas', function () {
    $component = Livewire::test(UsersTable::class);

    expect($component->get('perPage'))->toBe(25);

    $component->call('loadMore');

    expect($component->get('perPage'))->toBe(50);
});

test('UsersTable : resetInfiniteScroll remet perPage à 25', function () {
    $component = Livewire::test(UsersTable::class);
    $component->call('loadMore');
    $component->call('loadMore');

    expect($component->get('perPage'))->toBe(75);

    $component->call('resetInfiniteScroll');

    expect($component->get('perPage'))->toBe(25);
});

test('SubscribersTable : loadMore augmente perPage et le rendu réussit', function () {
    $component = Livewire::test(SubscribersTable::class);

    expect($component->get('perPage'))->toBe(25);

    $component->call('loadMore')->assertOk();

    expect($component->get('perPage'))->toBe(50);
});

test('RolesTable : loadMore augmente perPage et le rendu réussit', function () {
    $component = Livewire::test(RolesTable::class);

    expect($component->get('perPage'))->toBe(25);

    $component->call('loadMore')->assertOk();

    expect($component->get('perPage'))->toBe(50);
});

test('UsersTable : mise à jour de la recherche remet perPage à 25', function () {
    $component = Livewire::test(UsersTable::class);
    $component->call('loadMore');
    $component->call('loadMore');

    expect($component->get('perPage'))->toBe(75);

    $component->set('search', 'test');

    expect($component->get('perPage'))->toBe(25);
});
