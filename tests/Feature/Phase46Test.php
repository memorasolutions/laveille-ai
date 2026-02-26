<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Blog\Livewire\BlogSearch;
use Modules\Blog\Models\Article;

uses(RefreshDatabase::class);

it('la page blog retourne 200', function () {
    $this->get('/blog')->assertStatus(200);
});

it('recherche retourne des articles pertinents', function () {
    Article::factory()->create([
        'title' => 'Laravel Guide',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Livewire::test(BlogSearch::class)
        ->set('search', 'Laravel')
        ->assertSee('Laravel Guide');
});

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
