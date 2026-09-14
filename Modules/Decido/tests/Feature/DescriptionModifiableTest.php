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
 * Defaut signale par le fondateur le 2026-09-14 : « quand on ouvre le lien admin, on ne peut pas
 * entrer ou changer la description (voir pour aussi le sondage de date) ».
 *
 * Diagnostic : ce n'etait PAS un champ qui n'enregistrait pas - la fonctionnalite n'existait pas.
 * Le lien de gestion permettait de fermer, prolonger, supprimer, exporter, changer l'echeance et
 * le nombre d'attendus, mais AUCUNE route ne touchait `description` apres la creation. Rien
 * n'etait donc perdu en silence ; rien n'etait rattrapable non plus.
 */
function decidoSondageAvecOption(string $type, ?string $description = null): Poll
{
    $poll = new Poll;
    $poll->title = 'Sondage de test';
    $poll->description = $description;
    $poll->type = $type;
    $poll->vote_mode = 'single_choice';
    $poll->timezone = 'America/Toronto';
    $poll->status = 'open';
    $poll->creator_id = User::factory()->create()->id;
    $poll->admin_token_hash = hash('sha256', 'jeton-description');
    $poll->save();

    PollOption::factory()->create(['poll_id' => $poll->id]);

    return $poll;
}

function lienDeGestion(Poll $poll): string
{
    return route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'jeton-description']);
}

it('affiche le champ description sur la page de gestion, pour les DEUX types de sondage', function (string $type) {
    $poll = decidoSondageAvecOption($type);

    $this->get(lienDeGestion($poll))
        ->assertStatus(200)
        ->assertSee('Description du sondage')
        ->assertSee('name="description"', false);
})->with(['date', 'classic']);

// LE test du defaut : entrer une description sur un sondage qui n'en avait pas.
it('permet de SAISIR une description absente, depuis le lien admin', function (string $type) {
    $poll = decidoSondageAvecOption($type);
    expect($poll->description)->toBeNull();

    $this->post(route('decido.description', [
        'poll' => $poll->public_id,
        'adminToken' => 'jeton-description',
    ]), ['description' => 'On choisit le local après le vote.'])->assertRedirect();

    expect($poll->fresh()->description)->toBe('On choisit le local après le vote.');
})->with(['date', 'classic']);

it('permet de CHANGER une description existante', function () {
    $poll = decidoSondageAvecOption('classic', 'Texte initial');

    $this->post(route('decido.description', [
        'poll' => $poll->public_id,
        'adminToken' => 'jeton-description',
    ]), ['description' => 'Texte corrigé'])->assertRedirect();

    expect($poll->fresh()->description)->toBe('Texte corrigé');
});

// Ce que le correctif vaut vraiment : le votant voit le texte corrige.
it('montre la description corrigée aux votants', function () {
    $poll = decidoSondageAvecOption('classic', 'Ancien texte fautif');

    $this->post(route('decido.description', [
        'poll' => $poll->public_id,
        'adminToken' => 'jeton-description',
    ]), ['description' => 'Texte corrigé et visible'])->assertRedirect();

    $this->get(route('decido.vote.show', ['slug' => $poll->share_slug]))
        ->assertStatus(200)
        ->assertSee('Texte corrigé et visible')
        ->assertDontSee('Ancien texte fautif');
});

// Vider le champ doit rendre NULL, pas une chaine vide : les vues testent @if($poll->description),
// et une chaine vide y passerait pour une valeur presente (un paragraphe vide s'afficherait).
it('remet la description à NULL quand on vide le champ, jamais une chaîne vide', function () {
    $poll = decidoSondageAvecOption('classic', 'À effacer');

    $this->post(route('decido.description', [
        'poll' => $poll->public_id,
        'adminToken' => 'jeton-description',
    ]), ['description' => '   '])->assertRedirect();

    expect($poll->fresh()->description)->toBeNull();
});

// La borne est REPRISE de store() : la depasser y provoquait un 500 brut (SQLSTATE 22001).
// Une validation plus permissive ici rouvrirait le meme defaut par une autre porte.
it('refuse proprement une description de plus de 5000 caractères, sans 500', function () {
    $poll = decidoSondageAvecOption('classic');

    $this->post(route('decido.description', [
        'poll' => $poll->public_id,
        'adminToken' => 'jeton-description',
    ]), ['description' => str_repeat('a', 5001)])->assertSessionHasErrors('description');

    expect($poll->fresh()->description)->toBeNull();
});

// Meme garde que les autres ecritures de gestion : le jeton admin n'est pas decoratif.
it('refuse la modification avec un mauvais jeton admin', function () {
    $poll = decidoSondageAvecOption('classic', 'Intouchable');

    $this->post(route('decido.description', [
        'poll' => $poll->public_id,
        'adminToken' => 'mauvais-jeton',
    ]), ['description' => 'Tentative'])->assertStatus(403);

    expect($poll->fresh()->description)->toBe('Intouchable');
});
