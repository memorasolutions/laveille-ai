<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Livewire\AuthorEditor;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorPostRevision;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorProfileE(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 'editor-'.strtolower(Str::random(6)),
        'display_name' => 'Editor Test',
        'tier' => 'free',
    ]);
}

it('autoSave creates draft post on first save', function () {
    $author = makeAuthorProfileE();
    $this->actingAs($author->user);

    Livewire::test(AuthorEditor::class, ['authorProfile' => $author])
        ->set('title', 'Mon premier article')
        ->set('body_markdown', 'Ceci est le contenu...')
        ->call('autoSave');

    $post = AuthorPost::where('author_profile_id', $author->id)->first();
    expect($post)->not->toBeNull();
    expect($post->status)->toBe('draft');
    expect($post->slug)->toBe('mon-premier-article');
});

it('publish creates published post with revision snapshot', function () {
    $author = makeAuthorProfileE();
    $this->actingAs($author->user);

    Livewire::test(AuthorEditor::class, ['authorProfile' => $author])
        ->set('title', 'Article publié')
        ->set('body_markdown', 'Contenu de l article ici avec assez de mots pour passer')
        ->call('publish');

    $post = AuthorPost::first();
    expect($post->status)->toBe('published');
    expect($post->published_at)->not->toBeNull();
    expect($post->slug)->toBe('article-publie');
    expect(AuthorPostRevision::where('author_post_id', $post->id)->count())->toBeGreaterThanOrEqual(1);
});

it('publish rejects without title', function () {
    $author = makeAuthorProfileE();
    $this->actingAs($author->user);

    Livewire::test(AuthorEditor::class, ['authorProfile' => $author])
        ->set('body_markdown', 'Contenu suffisamment long ici pour passer')
        ->call('publish')
        ->assertHasErrors(['title' => 'required']);
});

it('publish rejects with body too short', function () {
    $author = makeAuthorProfileE();
    $this->actingAs($author->user);

    Livewire::test(AuthorEditor::class, ['authorProfile' => $author])
        ->set('title', 'Titre valide')
        ->set('body_markdown', 'court')
        ->call('publish')
        ->assertHasErrors(['body_markdown']);
});

it('slug auto-generated with collision suffix', function () {
    $author = makeAuthorProfileE();
    $this->actingAs($author->user);

    AuthorPost::create([
        'author_profile_id' => $author->id,
        'title' => 'Test',
        'slug' => 'test',
        'body_markdown' => 'X',
        'body_html' => '<p>X</p>',
        'status' => 'draft',
        'visibility' => 'public',
    ]);

    Livewire::test(AuthorEditor::class, ['authorProfile' => $author])
        ->set('title', 'Test')
        ->set('body_markdown', 'Contenu suffisamment long pour passer validation')
        ->call('publish');

    $newest = AuthorPost::orderByDesc('id')->first();
    expect($newest->slug)->toBe('test-2');
});

it('reading_time calculated from body markdown', function () {
    $author = makeAuthorProfileE();
    $this->actingAs($author->user);

    $body = str_repeat('Lorem ipsum dolor sit amet ', 50);

    Livewire::test(AuthorEditor::class, ['authorProfile' => $author])
        ->set('title', 'Reading time test')
        ->set('body_markdown', $body)
        ->call('publish');

    $post = AuthorPost::first();
    expect($post->reading_time_minutes)->toBeGreaterThanOrEqual(1);
    expect($post->reading_time_minutes)->toBeLessThanOrEqual(3);
});

it('tags saved as JSON array', function () {
    $author = makeAuthorProfileE();
    $this->actingAs($author->user);

    Livewire::test(AuthorEditor::class, ['authorProfile' => $author])
        ->set('title', 'Tags test')
        ->set('body_markdown', 'Contenu avec un peu de longueur pour passer la validation min 20 chars')
        ->set('tags', ['ia', 'prompt'])
        ->call('publish');

    $post = AuthorPost::first();
    expect($post->tags)->toBe(['ia', 'prompt']);
});

it('public route /@slug/{post_slug} renders published post with 200', function () {
    $author = makeAuthorProfileE();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'title' => 'Public article',
        'slug' => 'public-article',
        'body_markdown' => 'Body',
        'body_html' => '<p>Body</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
        'excerpt' => 'Short excerpt',
    ]);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('Public article');
    $response->assertSee('<p>Body</p>', false);

    $post->refresh();
    expect($post->views_count)->toBe(1);
});
