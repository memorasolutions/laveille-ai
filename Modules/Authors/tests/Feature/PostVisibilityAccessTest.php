<?php

declare(strict_types=1);

/**
 * Ticket #2445 - un article « abonnés » ou « premium » renvoyait 404 à TOUT LE MONDE,
 * y compris à son propre auteur, parce que les portes publiques appelaient scopePublic().
 *
 * Ce que ces tests verrouillent : la PAGE existe pour tous, c'est le CORPS qui est protégé,
 * et la seule règle qui en décide est AuthorPost::isReadableBy().
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthor2445(): AuthorProfile
{
    $user = User::factory()->create(['email' => 'auteur-'.Str::random(6).'@example.com']);

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 'a2445-'.strtolower(Str::random(6)),
        'display_name' => 'Auteur 2445',
        'tier' => 'free',
    ]);
}

function makePost2445(AuthorProfile $author, string $visibility): AuthorPost
{
    return AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'p2445-'.strtolower(Str::random(6)),
        'title' => 'Titre de test 2445',
        'excerpt' => 'Extrait toujours visible.',
        'body_markdown' => 'Corps protégé.',
        'body_html' => '<p>CORPS-SECRET-2445</p>',
        'status' => AuthorPost::STATUS_PUBLISHED,
        'visibility' => $visibility,
        'published_at' => now()->subDay(),
    ]);
}

it('sert la page d un article abonnes au lieu de renvoyer 404', function () {
    $author = makeAuthor2445();
    $post = makePost2445($author, AuthorPost::VISIBILITY_SUBSCRIBERS);

    $this->get('/@'.$author->slug.'/'.$post->slug)
        ->assertOk()
        ->assertSee('Titre de test 2445')
        ->assertDontSee('CORPS-SECRET-2445', false);
});

it('sert la page d un article premium au lieu de renvoyer 404, sans son corps', function () {
    $author = makeAuthor2445();
    $post = makePost2445($author, AuthorPost::VISIBILITY_PREMIUM);

    $this->get('/@'.$author->slug.'/'.$post->slug)
        ->assertOk()
        ->assertDontSee('CORPS-SECRET-2445', false);
});

it('laisse l auteur lire son propre article restreint', function () {
    $author = makeAuthor2445();
    $post = makePost2445($author, AuthorPost::VISIBILITY_SUBSCRIBERS);

    $this->actingAs($author->user)
        ->get('/@'.$author->slug.'/'.$post->slug)
        ->assertOk()
        ->assertSee('CORPS-SECRET-2445', false);
});

it('laisse un abonne CONFIRME lire un article abonnes', function () {
    $author = makeAuthor2445();
    $post = makePost2445($author, AuthorPost::VISIBILITY_SUBSCRIBERS);
    $lecteur = User::factory()->create(['email' => 'lecteur-'.Str::random(6).'@example.com']);

    AuthorSubscriber::create([
        'author_profile_id' => $author->id,
        'email' => $lecteur->email,
        'confirmed_at' => now(),
    ]);

    expect($post->fresh()->isReadableBy($lecteur))->toBeTrue();
});

it('refuse un abonne DESABONNE', function () {
    $author = makeAuthor2445();
    $post = makePost2445($author, AuthorPost::VISIBILITY_SUBSCRIBERS);
    $lecteur = User::factory()->create(['email' => 'ex-'.Str::random(6).'@example.com']);

    AuthorSubscriber::create([
        'author_profile_id' => $author->id,
        'email' => $lecteur->email,
        'confirmed_at' => now()->subMonth(),
        'unsubscribed_at' => now(),
    ]);

    expect($post->fresh()->isReadableBy($lecteur))->toBeFalse();
});

it('refuse premium meme a un abonne confirme, car aucun paiement n existe', function () {
    $author = makeAuthor2445();
    $post = makePost2445($author, AuthorPost::VISIBILITY_PREMIUM);
    $lecteur = User::factory()->create(['email' => 'prem-'.Str::random(6).'@example.com']);

    AuthorSubscriber::create([
        'author_profile_id' => $author->id,
        'email' => $lecteur->email,
        'confirmed_at' => now(),
    ]);

    expect($post->fresh()->isReadableBy($lecteur))->toBeFalse();
});

it('listable retient les trois visibilites, public n en retient qu une', function () {
    $author = makeAuthor2445();
    makePost2445($author, AuthorPost::VISIBILITY_PUBLIC);
    makePost2445($author, AuthorPost::VISIBILITY_SUBSCRIBERS);
    makePost2445($author, AuthorPost::VISIBILITY_PREMIUM);

    expect(AuthorPost::published()->listable()->count())->toBe(3)
        ->and(AuthorPost::published()->public()->count())->toBe(1);
});
