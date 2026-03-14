<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('user dashboard loads', function () {
    $this->get(route('user.dashboard'))
        ->assertStatus(200)
        ->assertSee('Bonjour');
});

it('dashboard shows articles count stat', function () {
    Article::factory()->count(3)->create(['user_id' => $this->user->id]);

    $this->get(route('user.dashboard'))
        ->assertStatus(200)
        ->assertSee('3');
});

it('dashboard shows published and draft counts', function () {
    Article::factory()->count(2)->create(['user_id' => $this->user->id, 'status' => 'published']);
    Article::factory()->create(['user_id' => $this->user->id, 'status' => 'draft']);

    $response = $this->get(route('user.dashboard'))->assertStatus(200);
    $response->assertSee('Publiés');
    $response->assertSee('Brouillons');
});

it('dashboard shows Free plan badge when no subscription', function () {
    $this->get(route('user.dashboard'))
        ->assertStatus(200)
        ->assertSee('Plan Free');
});

it('dashboard shows recent articles', function () {
    $article = Article::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Mon super article',
    ]);

    $this->get(route('user.dashboard'))
        ->assertStatus(200)
        ->assertSee('Mon super article');
});

it('dashboard shows comments count for user articles', function () {
    $article = Article::factory()->create(['user_id' => $this->user->id]);
    Comment::factory()->count(4)->create(['article_id' => $article->id]);

    $this->get(route('user.dashboard'))
        ->assertStatus(200)
        ->assertSee('Commentaires reçus');
});

it('unauthenticated user is redirected from dashboard', function () {
    auth()->logout();
    $this->get(route('user.dashboard'))
        ->assertRedirect(route('login'));
});
