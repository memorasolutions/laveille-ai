<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\CommentsTable;
use Modules\Blog\Models\Article;
// #177 : les commentaires affichés par le backoffice proviennent désormais du modèle
// polymorphique Community\Comment (table community_comments), pas de Blog\Comment.
use Modules\Community\Models\Comment;
// La route admin.blog.comments.approve opère encore sur le modèle legacy Blog\Comment
// (state machine), distinct du modèle Community affiché dans le tableau.
use Modules\Blog\Models\Comment as BlogComment;
use Spatie\Permission\Models\Role;

/**
 * Crée un commentaire polymorphique rattaché à un article (modèle Community).
 */
function makeArticleComment(Article $article, array $attributes = []): Comment
{
    return Comment::create(array_merge([
        'commentable_type' => Article::class,
        'commentable_id' => $article->id,
        'guest_name' => 'Testeur',
        'content' => 'Contenu de commentaire',
        'status' => 'approved',
    ], $attributes));
}

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
    makeArticleComment($article, ['content' => 'Super commentaire test']);

    $this->actingAs($this->admin)->get('/admin/blog/comments')
        ->assertSee('Super commentaire test');
});

it('le filtre search retourne le bon commentaire', function () {
    $article = Article::factory()->create();
    makeArticleComment($article, ['content' => 'Commentaire rouge unique']);
    makeArticleComment($article, ['content' => 'Commentaire bleu unique']);

    Livewire::actingAs($this->admin)->test(CommentsTable::class)
        ->set('search', 'rouge')
        ->assertSee('Commentaire rouge unique')
        ->assertDontSee('Commentaire bleu unique');
});

it('le filtre filterStatus fonctionne', function () {
    $article = Article::factory()->create();
    makeArticleComment($article, ['content' => 'Approuve visible', 'status' => 'approved']);
    makeArticleComment($article, ['content' => 'Brouillon masque', 'status' => 'pending']);

    Livewire::actingAs($this->admin)->test(CommentsTable::class)
        ->set('filterStatus', 'approved')
        ->assertSee('Approuve visible')
        ->assertDontSee('Brouillon masque');
});

it('la page affiche le total commentaires', function () {
    $article = Article::factory()->create();
    makeArticleComment($article);

    $this->actingAs($this->admin)->get('/admin/blog/comments')
        ->assertSee('commentaire');
});

it('approve action fonctionne via route', function () {
    // La route legacy d'approbation opère sur Blog\Comment (state machine), pas Community\Comment.
    $article = Article::factory()->create();
    $comment = BlogComment::factory()->create(['article_id' => $article->id, 'status' => 'pending']);

    $this->actingAs($this->admin)
        ->get(route('admin.blog.comments.approve', $comment))
        ->assertRedirect();

    expect((string) $comment->fresh()->status)->toBe('approved');
});
