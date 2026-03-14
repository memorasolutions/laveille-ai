<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);
beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create(); // password par défaut = 'password'
});

it('delete account section exists in profile', function () {
    $this->actingAs($this->user)->get(route('user.profile'))->assertSee('Supprimer');
});

it('delete account requires authentication', function () {
    $this->delete(route('user.account.delete'))->assertRedirect();
});

it('delete account requires password', function () {
    $this->actingAs($this->user)->delete(route('user.account.delete'), [])->assertSessionHasErrors('password');
});

it('delete account fails with wrong password', function () {
    $this->actingAs($this->user)->delete(route('user.account.delete'), ['password' => 'wrong'])->assertSessionHasErrors('password');
});

it('delete account succeeds with correct password', function () {
    $this->actingAs($this->user)->delete(route('user.account.delete'), ['password' => 'password'])->assertRedirect('/');
});

it('user is removed from database after deletion', function () {
    $id = $this->user->id;
    $this->actingAs($this->user)->delete(route('user.account.delete'), ['password' => 'password']);
    $this->assertDatabaseMissing('users', ['id' => $id]);
});

it('user tokens are deleted with account', function () {
    $this->user->createToken('my-token');
    $uid = $this->user->id;
    $this->actingAs($this->user)->delete(route('user.account.delete'), ['password' => 'password']);
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $uid]);
});

it('user is logged out after account deletion', function () {
    $this->actingAs($this->user)->delete(route('user.account.delete'), ['password' => 'password']);
    $this->assertGuest();
});

it('export data requires authentication', function () {
    $this->get(route('user.export-data'))->assertRedirect();
});

it('export data returns 200', function () {
    $this->actingAs($this->user)->get(route('user.export-data'))->assertOk();
});

it('export data has content disposition header', function () {
    $this->actingAs($this->user)->get(route('user.export-data'))->assertHeader('Content-Disposition');
});

it('export data section exists in profile', function () {
    $this->actingAs($this->user)->get(route('user.profile'))->assertSee('Exporter');
});
