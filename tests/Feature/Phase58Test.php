<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Blog\Livewire\BlogList;
use Modules\Blog\Models\Article;

uses(RefreshDatabase::class);

it('composant BlogList se monte correctement', function () {
    Livewire::test(BlogList::class)
        ->assertSet('perPage', 9)
        ->assertSet('hasMore', false);
});

it('BlogList affiche les articles publiés', function () {
    Article::factory()->published()->count(3)->create();
    Article::factory()->draft()->count(2)->create();

    Livewire::test(BlogList::class)
        ->assertViewHas('articles', fn ($articles) => $articles->count() === 3);
});

it('loadMore augmente le nombre d articles affichés', function () {
    Article::factory()->published()->count(12)->create();

    Livewire::test(BlogList::class)
        ->assertSet('perPage', 9)
        ->call('loadMore')
        ->assertSet('perPage', 18);
});

it('hasMore est vrai quand il y a plus d articles', function () {
    Article::factory()->published()->count(15)->create();

    $component = Livewire::test(BlogList::class);
    expect($component->get('hasMore'))->toBeTrue();
});

it('filtre par catégorie fonctionne', function () {
    Article::factory()->published()->create(['category' => 'Tech']);
    Article::factory()->published()->create(['category' => 'Design']);

    Livewire::test(BlogList::class, ['category' => 'Tech'])
        ->assertViewHas('articles', fn ($articles) => $articles->count() === 1 && $articles->first()->category === 'Tech');
});

it('page blog publique se charge correctement', function () {
    $this->get(route('blog.index'))
        ->assertOk();
});
