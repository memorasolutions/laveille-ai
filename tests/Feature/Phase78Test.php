<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\MediaTable;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

it('admin media index returns 200', function () {
    $this->get(route('admin.media.index'))->assertStatus(200);
});

it('non-admin gets 403 on media index', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.media.index'))
        ->assertStatus(403);
});

it('unauthenticated redirects from media index', function () {
    $this->post('/logout');
    $this->get(route('admin.media.index'))->assertRedirect();
});

it('media table livewire component mounts', function () {
    Livewire::test(MediaTable::class)
        ->assertStatus(200);
});

it('media table has default search empty', function () {
    Livewire::test(MediaTable::class)
        ->assertSet('search', '');
});

it('media table filter type defaults empty', function () {
    Livewire::test(MediaTable::class)
        ->assertSet('filterType', '');
});

it('media table reset filters clears search and type', function () {
    Livewire::test(MediaTable::class)
        ->set('search', 'test')
        ->set('filterType', 'image')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('filterType', '');
});
