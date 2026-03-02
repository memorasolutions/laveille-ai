<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Blog\Models\Article;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('account deletion requires password', function () {
    $user = User::factory()->create(['password' => 'Password1!']);

    $this->actingAs($user)
        ->delete(route('user.account.delete'))
        ->assertSessionHasErrors('password');
});

it('account deletion with wrong password fails', function () {
    $user = User::factory()->create(['password' => 'Password1!']);

    $this->actingAs($user)
        ->delete(route('user.account.delete'), ['password' => 'WrongPass9!'])
        ->assertSessionHasErrors('password');
});

it('account deletion anonymizes blog comments', function () {
    $user = User::factory()->create(['password' => 'Password1!']);
    $article = Article::factory()->create(['user_id' => $user->id]);

    $commentId = DB::table('blog_comments')->insertGetId([
        'article_id' => $article->id,
        'user_id' => $user->id,
        'guest_name' => $user->name,
        'guest_email' => $user->email,
        'content' => 'Un commentaire test.',
        'status' => 'approved',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete(route('user.account.delete'), ['password' => 'Password1!']);

    // Comment should survive (article cascade-deletes, so re-check with fresh article)
    // Actually article also cascade-deletes on user delete, so comment cascade-deletes via article
    // The anonymization happens BEFORE the user delete, so let's verify it worked
    // Note: since articles cascade on user delete, and comments cascade on article delete,
    // the comment will be gone. Let's test with a comment on ANOTHER user's article instead.
})->skip('Comments cascade-delete via article->user FK chain');

it('account deletion anonymizes comments on other articles', function () {
    $author = User::factory()->create();
    $commenter = User::factory()->create(['password' => 'Password1!']);
    $article = Article::factory()->create(['user_id' => $author->id]);

    DB::table('blog_comments')->insert([
        'article_id' => $article->id,
        'user_id' => $commenter->id,
        'guest_name' => $commenter->name,
        'guest_email' => $commenter->email,
        'content' => 'Commentaire sur article autre.',
        'status' => 'approved',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($commenter)
        ->delete(route('user.account.delete'), ['password' => 'Password1!']);

    $comment = DB::table('blog_comments')->where('article_id', $article->id)->first();

    expect($comment)->not->toBeNull()
        ->and($comment->user_id)->toBeNull()
        ->and($comment->guest_name)->toBe('Utilisateur supprimé')
        ->and($comment->guest_email)->toBeNull();
});

it('account deletion clears sessions', function () {
    $user = User::factory()->create(['password' => 'Password1!']);

    DB::table('sessions')->insert([
        'id' => 'session-to-delete',
        'user_id' => $user->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'Test',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->actingAs($user)
        ->delete(route('user.account.delete'), ['password' => 'Password1!']);

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});

it('account deletion clears login attempts', function () {
    $user = User::factory()->create(['password' => 'Password1!']);

    DB::table('login_attempts')->insert([
        'user_id' => $user->id,
        'email' => $user->email,
        'ip_address' => '127.0.0.1',
        'status' => 'success',
        'logged_in_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete(route('user.account.delete'), ['password' => 'Password1!']);

    expect(DB::table('login_attempts')->where('user_id', $user->id)->count())->toBe(0);
});

it('account deletion removes user from database', function () {
    $user = User::factory()->create(['password' => 'Password1!']);
    $userId = $user->id;

    $this->actingAs($user)
        ->delete(route('user.account.delete'), ['password' => 'Password1!']);

    $this->assertDatabaseMissing('users', ['id' => $userId]);
});

it('guest cannot delete account', function () {
    $this->delete(route('user.account.delete'))->assertRedirect();
});
