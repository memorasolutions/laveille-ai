<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page commentaires retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/blog/comments')->assertStatus(200);
});

it('les invites sont rediriges vers login', function () {
    $this->get('/admin/blog/comments')->assertRedirect('/login');
});

it('les non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/blog/comments')->assertStatus(403);
});

it('la page affiche le bouton reinitialiser', function () {
    $this->actingAs($this->admin)->get('/admin/blog/comments')
        ->assertSee('Réinitialiser');
});

it('la page affiche aucun commentaire quand vide', function () {
    Comment::query()->forceDelete();

    $this->actingAs($this->admin)->get('/admin/blog/comments')
        ->assertSee('Aucun commentaire');
});

it('un commentaire cree apparait dans la liste', function () {
    $article = Article::factory()->create();
    Comment::factory()->create(['article_id' => $article->id, 'content' => 'Super commentaire test']);

    $this->actingAs($this->admin)->get('/admin/blog/comments')
        ->assertSee('Super commentaire test');
});

it('le filtre search retourne le bon commentaire', function () {
    $article = Article::factory()->create();
    Comment::factory()->create(['article_id' => $article->id, 'content' => 'Commentaire rouge unique']);
    Comment::factory()->create(['article_id' => $article->id, 'content' => 'Commentaire bleu unique']);

    $this->actingAs($this->admin)->get('/admin/blog/comments?search=rouge')
        ->assertSee('Commentaire rouge unique')
        ->assertDontSee('Commentaire bleu unique');
});

it('le filtre filterStatus fonctionne', function () {
    $article = Article::factory()->create();
    Comment::factory()->create(['article_id' => $article->id, 'content' => 'Approuve visible', 'status' => 'approved']);
    Comment::factory()->create(['article_id' => $article->id, 'content' => 'Brouillon masque', 'status' => 'pending']);

    $this->actingAs($this->admin)->get('/admin/blog/comments?filterStatus=approved')
        ->assertSee('Approuve visible')
        ->assertDontSee('Brouillon masque');
});

it('la page affiche le total commentaires', function () {
    $article = Article::factory()->create();
    Comment::factory()->create(['article_id' => $article->id]);

    $this->actingAs($this->admin)->get('/admin/blog/comments')
        ->assertSee('commentaire');
});

it('approve action fonctionne via route', function () {
    $article = Article::factory()->create();
    $comment = Comment::factory()->create(['article_id' => $article->id, 'status' => 'pending']);

    $this->actingAs($this->admin)
        ->get(route('admin.blog.comments.approve', $comment))
        ->assertRedirect();

    expect((string) $comment->fresh()->status)->toBe('approved');
});
