<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\ArticlesTable;
use Modules\Backoffice\Livewire\CommentsTable;
use Modules\Backoffice\Livewire\UsersTable;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

test('users table has bulk action properties', function () {
    Livewire::test(UsersTable::class)
        ->assertSet('selected', [])
        ->assertSet('selectAll', false)
        ->assertSet('bulkAction', '');
});

test('users bulk select all selects page ids', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    User::factory()->count(3)->create();

    Livewire::actingAs($admin)->test(UsersTable::class)
        ->set('selectAll', true)
        ->assertNotSet('selected', []);
});

test('users bulk activate works', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $users = User::factory()->count(2)->create(['is_active' => false]);
    $ids = $users->pluck('id')->map(fn ($id) => (int) $id)->toArray();

    Livewire::actingAs($admin)->test(UsersTable::class)
        ->set('selected', $ids)
        ->set('bulkAction', 'activate')
        ->call('executeBulkAction');

    foreach ($users as $user) {
        expect($user->fresh()->is_active)->toBeTrue();
    }
});

test('users bulk deactivate works', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $users = User::factory()->count(2)->create(['is_active' => true]);
    $ids = $users->pluck('id')->map(fn ($id) => (int) $id)->toArray();

    Livewire::actingAs($admin)->test(UsersTable::class)
        ->set('selected', $ids)
        ->set('bulkAction', 'deactivate')
        ->call('executeBulkAction');

    foreach ($users as $user) {
        expect($user->fresh()->is_active)->toBeFalse();
    }
});

test('users bulk delete works', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $users = User::factory()->count(2)->create();
    $ids = $users->pluck('id')->map(fn ($id) => (int) $id)->toArray();

    Livewire::actingAs($admin)->test(UsersTable::class)
        ->set('selected', $ids)
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction');

    foreach ($ids as $id) {
        expect(User::find($id))->toBeNull();
    }
});

test('articles table renders with bulk actions', function () {
    Livewire::test(ArticlesTable::class)
        ->assertSet('selected', [])
        ->assertSet('bulkAction', '')
        ->assertOk();
});

test('articles bulk delete works', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id]);

    Livewire::test(ArticlesTable::class)
        ->set('selected', [(int) $article->id])
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction');

    expect(Article::find($article->id))->toBeNull();
});

test('comments table renders with bulk actions', function () {
    Livewire::test(CommentsTable::class)
        ->assertSet('selected', [])
        ->assertSet('bulkAction', '')
        ->assertOk();
});

test('comments bulk delete works', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id]);
    $comment = Comment::factory()->create(['article_id' => $article->id, 'user_id' => $user->id]);

    Livewire::test(CommentsTable::class)
        ->set('selected', [(int) $comment->id])
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction');

    expect(Comment::withTrashed()->find($comment->id))->toBeNull();
});

test('bulk action without selection does not execute', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $usersBefore = User::count();

    Livewire::actingAs($admin)->test(UsersTable::class)
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction')
        ->assertSet('selected', []);

    expect(User::count())->toBe($usersBefore);
});

test('reset bulk selection clears state', function () {
    Livewire::test(UsersTable::class)
        ->set('selected', [1, 2])
        ->set('selectAll', true)
        ->set('bulkAction', 'delete')
        ->call('resetBulkSelection')
        ->assertSet('selected', [])
        ->assertSet('selectAll', false)
        ->assertSet('bulkAction', '');
});
