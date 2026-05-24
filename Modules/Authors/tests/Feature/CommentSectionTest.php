<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Livewire\CommentSection;
use Modules\Authors\Mail\CommentReplyNotificationMail;
use Modules\Authors\Models\AuthorComment;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorProfileC(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 'commenter-'.strtolower(Str::random(6)),
        'display_name' => 'Test Author',
        'tier' => 'free',
    ]);
}

function makeFakeCommentable(AuthorProfile $author): \stdClass
{
    return (object) ['type' => AuthorProfile::class, 'id' => $author->id, 'authorProfileId' => $author->id];
}

it('auth user posts comment auto-published when spam score low', function () {
    Mail::fake();
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->set('body', 'Super article merci !')
        ->set('consent', true)
        ->call('submit');

    $this->assertDatabaseHas('author_comments', [
        'author_profile_id' => $c->authorProfileId,
        'body' => 'Super article merci !',
        'user_id' => $user->id,
    ]);

    $comment = AuthorComment::latest('id')->first();
    expect($comment->approved_at)->not->toBeNull();
});

it('guest posts comment pending moderation', function () {
    Mail::fake();
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->set('body', 'Cool!')
        ->set('guestName', 'Bob')
        ->set('guestEmail', 'bob@example.com')
        ->set('consent', true)
        ->call('submit');

    $comment = AuthorComment::latest()->first();
    expect($comment->approved_at)->toBeNull();
    expect($comment->author_name)->toBe('Bob');
});

it('reply nested level 1 succeeds', function () {
    Mail::fake();
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);
    $parent = AuthorComment::create([
        'author_profile_id' => $c->authorProfileId,
        'commentable_type' => $c->type,
        'commentable_id' => $c->id,
        'author_name' => 'Parent',
        'author_email' => 'parent@example.com',
        'body' => 'Parent comment body',
        'approved_at' => now(),
    ]);

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->call('startReply', $parent->id)
        ->set('body', 'Child reply')
        ->set('guestName', 'Child')
        ->set('guestEmail', 'child@example.com')
        ->set('consent', true)
        ->call('submit');

    $child = AuthorComment::where('parent_id', $parent->id)->first();
    expect($child)->not->toBeNull();
});

it('reactions toggle add then remove for authenticated user', function () {
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);
    $user = User::factory()->create();
    $this->actingAs($user);
    $comment = AuthorComment::create([
        'author_profile_id' => $c->authorProfileId,
        'commentable_type' => $c->type,
        'commentable_id' => $c->id,
        'author_name' => 'X',
        'body' => 'Test comment body',
        'approved_at' => now(),
    ]);

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->call('toggleReaction', $comment->id, '👍');

    $comment->refresh();
    expect($comment->reactions['👍'])->toContain($user->id);

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->call('toggleReaction', $comment->id, '👍');

    $comment->refresh();
    expect(isset($comment->reactions['👍']))->toBeFalse();
});

it('reactions ignored when emoji not in ALLOWED_REACTIONS', function () {
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);
    $user = User::factory()->create();
    $this->actingAs($user);
    $comment = AuthorComment::create([
        'author_profile_id' => $c->authorProfileId,
        'commentable_type' => $c->type,
        'commentable_id' => $c->id,
        'author_name' => 'X',
        'body' => 'Test',
        'approved_at' => now(),
    ]);

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->call('toggleReaction', $comment->id, '🚀');

    $comment->refresh();
    expect($comment->reactions ?? [])->toBe([]);
});

it('honeypot website filled silently rejects', function () {
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->set('body', 'spammy body')
        ->set('guestName', 'Spammer')
        ->set('guestEmail', 's@s.com')
        ->set('consent', true)
        ->set('website', 'http://spam.com')
        ->call('submit');

    expect(AuthorComment::count())->toBe(0);
});

it('rejects comment without body', function () {
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->set('consent', true)
        ->set('guestName', 'N')
        ->set('guestEmail', 'n@n.com')
        ->call('submit')
        ->assertHasErrors(['body' => 'required']);
});

it('rejects comment with body too short', function () {
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->set('body', 'ok')
        ->set('guestName', 'A')
        ->set('guestEmail', 'a@a.com')
        ->set('consent', true)
        ->call('submit')
        ->assertHasErrors(['body' => 'min']);
});

it('rejects without consent (Loi 25)', function () {
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->set('body', 'Hello world ok')
        ->set('guestName', 'N')
        ->set('guestEmail', 'n@n.com')
        ->call('submit')
        ->assertHasErrors(['consent' => 'accepted']);
});

it('flags as spam if spam score >= 70', function () {
    Mail::fake();
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);
    $body = 'viagra https://a.com https://b.com https://c.com https://d.com https://e.com https://f.com https://g.com casino';

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->set('body', $body)
        ->set('guestName', 'Spammy')
        ->set('guestEmail', 'spam@example.com')
        ->set('consent', true)
        ->call('submit');

    $comment = AuthorComment::latest()->first();
    expect($comment->spam_score)->toBeGreaterThanOrEqual(70);
    expect($comment->flagged_at)->not->toBeNull();
});

it('queues reply notification email to parent author', function () {
    Mail::fake();
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);
    $parent = AuthorComment::create([
        'author_profile_id' => $c->authorProfileId,
        'commentable_type' => $c->type,
        'commentable_id' => $c->id,
        'author_name' => 'Parent',
        'author_email' => 'parent@example.com',
        'body' => 'Parent comment',
        'approved_at' => now(),
    ]);

    Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ])
        ->call('startReply', $parent->id)
        ->set('body', 'Reply to parent here')
        ->set('guestName', 'Replier')
        ->set('guestEmail', 'replier@example.com')
        ->set('consent', true)
        ->call('submit');

    Mail::assertQueued(CommentReplyNotificationMail::class, fn ($mail) => $mail->parent->id === $parent->id);
});

it('paginates 20 top-level comments', function () {
    $author = makeAuthorProfileC();
    $c = makeFakeCommentable($author);

    for ($i = 0; $i < 25; $i++) {
        AuthorComment::create([
            'author_profile_id' => $c->authorProfileId,
            'commentable_type' => $c->type,
            'commentable_id' => $c->id,
            'author_name' => 'User'.$i,
            'body' => 'Comment body '.$i,
            'approved_at' => now(),
            'parent_id' => null,
        ]);
    }

    $component = Livewire::test(CommentSection::class, [
        'commentableType' => $c->type,
        'commentableId' => $c->id,
        'authorProfileId' => $c->authorProfileId,
    ]);

    $comments = $component->viewData('comments');
    expect($comments->count())->toBe(20);
    expect($comments->total())->toBe(25);
});
