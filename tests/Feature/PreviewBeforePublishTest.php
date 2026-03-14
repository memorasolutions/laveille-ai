<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;

uses(RefreshDatabase::class);

function previewAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

// ── Article Preview ──

test('guest est redirige vers login sur preview article', function () {
    $article = Article::factory()->create();

    $response = $this->get(route('admin.blog.articles.preview', $article));

    $response->assertRedirect(route('login'));
});
