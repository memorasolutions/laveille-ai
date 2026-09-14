<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Decido\Mail\PollActivityDigestMail;
use Modules\Decido\Models\Poll;
use Modules\Decido\Models\PollOption;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

/**
 * Defaut signale par le fondateur le 2026-09-14 : un sondage CLASSIQUE affichait « Aucune de ces
 * DATES ne me convient », alors que ses options ne sont pas des dates.
 *
 * Ce qui rend ce test utile, c'est son ETENDUE : le libelle vivait a QUATRE endroits, et l'ecran
 * signale n'en etait qu'un. Les trois autres n'ont ete trouves que par un grep du module entier -
 * le bouton du votant, le message au votant qui revient, le resume de l'organisateur, et le
 * courriel d'activite. Corriger l'endroit signale seul aurait laisse trois mensonges en place.
 */
function decidoSondage(string $type): Poll
{
    $poll = new Poll;
    $poll->title = 'Sondage de test';
    $poll->type = $type;
    $poll->vote_mode = 'single_choice';
    $poll->timezone = 'America/Toronto';
    $poll->status = 'open';
    $poll->creator_id = User::factory()->create()->id;
    $poll->admin_token_hash = hash('sha256', 'jeton-admin-de-test');
    $poll->save();

    PollOption::factory()->create(['poll_id' => $poll->id]);

    return $poll;
}

it('propose « aucune de ces dates » sur un sondage de DATE', function () {
    $poll = decidoSondage('date');

    $this->get(route('decido.vote.show', ['slug' => $poll->share_slug]))
        ->assertStatus(200)
        ->assertSee('Aucune de ces dates ne me convient');
});

// LE test du defaut signale.
it('propose « aucune de ces réponses » sur un sondage CLASSIQUE, jamais « dates »', function () {
    $poll = decidoSondage('classic');

    $reponse = $this->get(route('decido.vote.show', ['slug' => $poll->share_slug]))->assertStatus(200);

    $reponse->assertSee('Aucune de ces réponses ne me convient');
    $reponse->assertDontSee('Aucune de ces dates ne me convient');
});

// Deuxieme endroit, invisible depuis l'ecran signale : le votant qui revient apres avoir decline.
it('dit « aucune réponse ne te convenait » au votant qui revient sur un sondage classique', function () {
    $poll = decidoSondage('classic');

    // Le message « tu as deja indique... » ne s'affiche qu'au votant RECONNU, par son cookie.
    // Sans le porter explicitement sur les deux requetes, la page ne le montre pas et le test
    // passerait au vert sur un simple assertDontSee - un faux vert qui ne prouverait rien.
    $cookieName = 'decido_voter_'.$poll->public_id;
    $voterToken = (string) Str::uuid();

    $this->withCookie($cookieName, $voterToken)
        ->post(route('decido.vote.decline', ['slug' => $poll->share_slug]), [
            'voter_pseudonym' => 'Testeur',
        ])->assertRedirect();

    $reponse = $this->withCookie($cookieName, $voterToken)
        ->get(route('decido.vote.show', ['slug' => $poll->share_slug]))
        ->assertStatus(200);

    $reponse->assertSee('aucune réponse ne te convenait', false);
    $reponse->assertDontSee('aucune date ne te convenait', false);
});

// Troisieme endroit : le resume que lit l'ORGANISATEUR, pas le votant.
it("ne parle pas de « date » à l'organisateur d'un sondage classique", function () {
    $poll = decidoSondage('classic');

    $this->post(route('decido.vote.decline', ['slug' => $poll->share_slug]), [
        'voter_pseudonym' => 'Testeur',
    ])->assertRedirect();

    $reponse = $this->get(route('decido.manage', [
        'poll' => $poll->public_id,
        'adminToken' => 'jeton-admin-de-test',
    ]))->assertStatus(200);

    // On cherche SANS l'apostrophe : Blade echappe « ' » en « &#039; » dans la sortie HTML,
    // donc chercher la chaine brute ne trouverait jamais rien (piege verifie ici meme).
    $reponse->assertSee('aucune réponse ne leur convenait', false);
    $reponse->assertDontSee('aucune date ne leur convenait', false);
});

// Quatrieme endroit : le COURRIEL, que le gabarit ne pouvait meme pas conditionner faute d'avoir
// le type - il a fallu le lui passer.
it('adapte aussi le courriel de résumé au type de sondage', function () {
    $classique = decidoSondage('classic');
    $rendu = (new PollActivityDigestMail($classique, 0, 2, 0))->build()->render();

    expect($rendu)->toContain("aucune réponse ne leur convenait");
    expect($rendu)->not->toContain("aucune date ne leur convenait");

    $dates = decidoSondage('date');
    $renduDates = (new PollActivityDigestMail($dates, 0, 2, 0))->build()->render();

    expect($renduDates)->toContain("aucune date ne leur convenait");
});
