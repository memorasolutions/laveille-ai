<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Modules\Blog\Models\Article;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(fn () => App::setLocale('fr_CA'));

it('search matches term in title', function () {
    Article::factory()->create([
        'title' => ['fr_CA' => 'Guide complet Laravel'],
        'content' => ['fr_CA' => 'x'],
        'excerpt' => ['fr_CA' => 'y'],
    ]);

    expect(Article::searchText('Laravel')->count())->toBe(1);
});

it('search returns nothing for unrelated term', function () {
    Article::factory()->create([
        'title' => ['fr_CA' => 'Guide complet Laravel'],
        'content' => ['fr_CA' => 'x'],
        'excerpt' => ['fr_CA' => 'y'],
    ]);

    expect(Article::searchText('zzzznomatch')->count())->toBe(0);
});

it('search ignores empty term', function () {
    Article::factory()->create();
    Article::factory()->create();

    expect(Article::searchText('')->count())->toBe(Article::count());
});

it('search escapes percent so it is not match-all', function () {
    Article::factory()->create([
        'title' => ['fr_CA' => 'Hello World'],
        'content' => ['fr_CA' => 'x'],
        'excerpt' => ['fr_CA' => 'y'],
    ]);

    expect(Article::searchText('%')->count())->toBe(0);
});
