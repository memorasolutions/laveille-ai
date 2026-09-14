<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Decido\Models\Poll;
use Modules\Decido\Models\PollOption;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

/**
 * Demande du fondateur (2026-09-14) : le choix « aucune ne me convient » devient un REGLAGE que
 * l'organisateur active ou non, plutot qu'un bouton impose a tous les sondages.
 *
 * Les trois decisions que ces tests verrouillent, et qui ne se devinent pas en lisant le code :
 *  1. le defaut est ACTIVE, y compris pour les sondages crees AVANT la migration ;
 *  2. un declin DEJA recu reste visible apres decochage (on gouverne ce qu'on propose, pas ce
 *     qu'on a recu) ;
 *  3. le refus vit COTE SERVEUR : cacher le bouton ne suffit pas, la route est publique.
 */
function decidoSondageReglage(bool $allowDecline = true): Poll
{
    $poll = new Poll;
    $poll->title = 'Sondage de test';
    $poll->type = 'classic';
    $poll->vote_mode = 'single_choice';
    $poll->timezone = 'America/Toronto';
    $poll->status = 'open';
    $poll->allow_decline = $allowDecline;
    $poll->creator_id = User::factory()->create()->id;
    $poll->admin_token_hash = hash('sha256', 'jeton-reglage');
    $poll->save();

    PollOption::factory()->create(['poll_id' => $poll->id]);

    return $poll;
}

it('propose le choix par défaut, sans rien configurer', function () {
    $poll = decidoSondageReglage();

    expect($poll->allow_decline)->toBeTrue();

    $this->get(route('decido.vote.show', ['slug' => $poll->share_slug]))
        ->assertStatus(200)
        ->assertSee('ne me convient');
});

// Un sondage cree AVANT la migration ne doit pas perdre une possibilite que ses participants
// voyaient hier : ce serait changer les regles en cours de vote.
it('garde le choix actif sur un sondage créé avant la migration (colonne au défaut)', function () {
    $poll = new Poll;
    $poll->title = 'Sondage ancien';
    $poll->type = 'classic';
    $poll->vote_mode = 'single_choice';
    $poll->timezone = 'America/Toronto';
    $poll->status = 'open';
    $poll->creator_id = User::factory()->create()->id;
    $poll->admin_token_hash = hash('sha256', 'jeton-reglage');
    $poll->save(); // jamais d'affectation explicite de allow_decline : on lit le DEFAUT de la colonne

    PollOption::factory()->create(['poll_id' => $poll->id]);

    expect($poll->fresh()->allow_decline)->toBeTrue();
});

it('cache le bouton quand le réglage est décoché', function () {
    $poll = decidoSondageReglage(false);

    $this->get(route('decido.vote.show', ['slug' => $poll->share_slug]))
        ->assertStatus(200)
        ->assertDontSee('ne me convient');
});

// LE test qui compte : cacher n'est pas interdire. La route est publique.
it('REFUSE un déclin envoyé directement quand le réglage est décoché', function () {
    $poll = decidoSondageReglage(false);

    $this->post(route('decido.vote.decline', ['slug' => $poll->share_slug]), [
        'voter_pseudonym' => 'Contournement',
    ])->assertStatus(403);

    expect($poll->declines()->count())->toBe(0);
});

it('accepte le déclin quand le réglage est activé', function () {
    $poll = decidoSondageReglage(true);

    $this->post(route('decido.vote.decline', ['slug' => $poll->share_slug]), [
        'voter_pseudonym' => 'Participant',
    ])->assertRedirect();

    expect($poll->declines()->count())->toBe(1);
});

it("permet à l'organisateur de décocher le réglage depuis le lien admin", function () {
    $poll = decidoSondageReglage(true);

    $this->post(route('decido.allow-decline', [
        'poll' => $poll->public_id,
        'adminToken' => 'jeton-reglage',
    ]), ['allow_decline' => '0'])->assertRedirect();

    expect($poll->fresh()->allow_decline)->toBeFalse();
});

it('permet de le recocher ensuite', function () {
    $poll = decidoSondageReglage(false);

    $this->post(route('decido.allow-decline', [
        'poll' => $poll->public_id,
        'adminToken' => 'jeton-reglage',
    ]), ['allow_decline' => '1'])->assertRedirect();

    expect($poll->fresh()->allow_decline)->toBeTrue();
});

// Le reglage gouverne ce qu'on PROPOSE, jamais ce qu'on a deja RECU. Masquer une reponse
// reellement donnee serait une perte de donnee, et fausserait le total des participants.
it('garde visibles les déclins DÉJÀ reçus après décochage', function () {
    $poll = decidoSondageReglage(true);

    $this->post(route('decido.vote.decline', ['slug' => $poll->share_slug]), [
        'voter_pseudonym' => 'Déjà répondu',
    ])->assertRedirect();

    $this->post(route('decido.allow-decline', [
        'poll' => $poll->public_id,
        'adminToken' => 'jeton-reglage',
    ]), ['allow_decline' => '0'])->assertRedirect();

    expect($poll->fresh()->allow_decline)->toBeFalse();
    expect($poll->declines()->count())->toBe(1);

    $this->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-reglage']))
        ->assertStatus(200)
        ->assertSee('Déjà répondu');
});

it('refuse de changer le réglage avec un mauvais jeton admin', function () {
    $poll = decidoSondageReglage(true);

    $this->post(route('decido.allow-decline', [
        'poll' => $poll->public_id,
        'adminToken' => 'mauvais-jeton',
    ]), ['allow_decline' => '0'])->assertStatus(403);

    expect($poll->fresh()->allow_decline)->toBeTrue();
});

it('affiche la case sur la page de gestion', function () {
    $poll = decidoSondageReglage(true);

    $this->get(route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-reglage']))
        ->assertStatus(200)
        ->assertSee('name="allow_decline"', false);
});
