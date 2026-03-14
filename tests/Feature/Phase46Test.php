<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Blog\Livewire\BlogSearch;
use Modules\Blog\Models\Article;

uses(RefreshDatabase::class);

it('recherche vide ne retourne aucun résultat', function () {
    Article::factory()->create([
        'title' => 'Mon Article Test',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Livewire::test(BlogSearch::class)
        ->set('search', '')
        ->assertDontSee('Mon Article Test');
});

it('recherche sans résultat affiche message', function () {
    Livewire::test(BlogSearch::class)
        ->set('search', 'xyz_inexistant_abc')
        ->assertSee('Aucun résultat');
});
