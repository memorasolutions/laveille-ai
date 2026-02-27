<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Tag;

uses(RefreshDatabase::class);

// --- Modèle Tag ---

it('crée un tag avec slug auto-généré', function () {
    $tag = Tag::create(['name' => 'Laravel PHP']);

    expect($tag->slug)->toBe('laravel-php');
    expect($tag->color)->toBe('#6366f1');
});

it('a une relation many-to-many avec les articles', function () {
    $tag = Tag::create(['name' => 'Test']);
    $user = User::factory()->create();
    $article = Article::create([
        'title' => 'Article test',
        'slug' => 'article-test',
        'content' => '<p>Contenu.</p>',
        'status' => 'published',
        'published_at' => now(),
        'user_id' => $user->id,
    ]);

    $tag->articles()->attach($article);

    expect($tag->articles)->toHaveCount(1);
    expect($article->tagsRelation)->toHaveCount(1);
});

// --- Admin CRUD ---

it('admin peut voir la liste des tags', function () {
    Tag::create(['name' => 'PHP']);
    Tag::create(['name' => 'Laravel']);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.blog.tags.index'))
        ->assertOk()
        ->assertSee('PHP')
        ->assertSee('Laravel');
});

it('admin peut créer un tag', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.blog.tags.store'), [
            'name' => 'Nouveau tag',
            'description' => 'Description test',
            'color' => '#ff5733',
        ])
        ->assertRedirect(route('admin.blog.tags.index'));

    expect(Tag::where('name', 'Nouveau tag')->exists())->toBeTrue();
    expect(Tag::where('name', 'Nouveau tag')->first()->color)->toBe('#ff5733');
});

it('admin peut modifier un tag', function () {
    $tag = Tag::create(['name' => 'Ancien nom']);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->put(route('admin.blog.tags.update', $tag), [
            'name' => 'Nouveau nom',
        ])
        ->assertRedirect(route('admin.blog.tags.index'));

    expect($tag->fresh()->name)->toBe('Nouveau nom');
});

it('admin peut supprimer un tag', function () {
    $tag = Tag::create(['name' => 'À supprimer']);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->delete(route('admin.blog.tags.destroy', $tag))
        ->assertRedirect(route('admin.blog.tags.index'));

    expect(Tag::withTrashed()->find($tag->id)->trashed())->toBeTrue();
});

it('la validation rejette un nom de tag dupliqué', function () {
    Tag::create(['name' => 'Existant']);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.blog.tags.store'), ['name' => 'Existant'])
        ->assertSessionHasErrors('name');
});

// --- Route publique ---

it('la page /blog/tag/{slug} affiche les articles du tag', function () {
    $tag = Tag::create(['name' => 'Laravel']);
    $user = User::factory()->create();
    $article = Article::create([
        'title' => 'Article Laravel',
        'slug' => 'article-laravel',
        'content' => '<p>Contenu Laravel.</p>',
        'status' => 'published',
        'published_at' => now(),
        'user_id' => $user->id,
    ]);
    $tag->articles()->attach($article);

    $this->get(route('blog.tag', $tag))
        ->assertOk()
        ->assertSee('Article Laravel')
        ->assertSee('Laravel');
});

it('la page tag contient du JSON-LD CollectionPage', function () {
    $tag = Tag::create(['name' => 'SEO Test']);

    $this->get(route('blog.tag', $tag))
        ->assertOk()
        ->assertSee('"@type": "CollectionPage"', false)
        ->assertSee('"@type": "BreadcrumbList"', false);
});

it('la page tag affiche un message si aucun article', function () {
    $tag = Tag::create(['name' => 'Vide']);

    $this->get(route('blog.tag', $tag))
        ->assertOk()
        ->assertSee('Aucun article avec ce tag');
});

it('le getRouteKeyName du tag est slug', function () {
    $tag = new Tag();
    expect($tag->getRouteKeyName())->toBe('slug');
});
