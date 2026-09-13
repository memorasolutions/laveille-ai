<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2528 - le champ « Ordre d'affichage » (colonne dictionary_terms.sort_order) a été
 * retiré des formulaires d'administration des termes : la valeur était enregistrée mais
 * n'était lue par AUCUN tri de termes (glossaire public et liste admin trient par nom ;
 * seules les catégories utilisent sort_order). Ce test prouve trois choses : le champ n'est
 * plus affiché, la création/modification fonctionne toujours sans lui, et un appel qui
 * enverrait quand même le paramètre (validation restée tolérante) ne casse rien.
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Dictionary\Models\Term;
use Database\Seeders\RolesAndPermissionsSeeder;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

// ── Helper local (préfixé Tso pour éviter tout conflit inter-fichiers) ─────────────────────

function tsoTerm(array $overrides = []): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $slug = 'terme-sort-order-'.uniqid();

    return Term::create(array_merge([
        'name' => [$locale => 'Terme sort_order '.uniqid(), 'fr' => 'Terme sort_order'],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Définition de test pour le retrait du champ sort_order.', 'fr' => 'Définition de test.'],
        'type' => 'ai_term',
        'is_published' => true,
    ], $overrides));
}

// ── 1) Le champ n'est plus affiché ──────────────────────────────────────────────────────────

test('la page de création répond 200 et n\'affiche plus le champ sort_order', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dictionary.create'))
        ->assertOk()
        ->assertDontSee('name="sort_order"', false);
});

test('la page de modification répond 200 et n\'affiche plus le champ sort_order', function () {
    $term = tsoTerm();

    $this->actingAs($this->admin)
        ->get(route('admin.dictionary.edit', $term))
        ->assertOk()
        ->assertDontSee('name="sort_order"', false);
});

// ── 2) Créer/modifier un terme SANS ce paramètre fonctionne toujours ───────────────────────

test('créer un terme sans le paramètre sort_order fonctionne toujours', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.dictionary.store'), [
            'name' => 'Terme creation sans sort order',
            'definition' => 'Définition sans le paramètre sort_order.',
            'type' => 'ai_term',
            'is_published' => '1',
            'dictionary_category_id' => '', // toujours envoyé par le <select> du vrai formulaire ("-- Aucune catégorie --")
        ])
        ->assertRedirect(route('admin.dictionary.index'));

    $created = Term::query()->where('name->fr', 'Terme creation sans sort order')->first();
    expect($created)->not->toBeNull();
    expect($created->sort_order)->toBe(0); // valeur par défaut du contrôleur, jamais lue par un tri
});

test('modifier un terme sans le paramètre sort_order fonctionne toujours', function () {
    $term = tsoTerm();

    $this->actingAs($this->admin)
        ->put(route('admin.dictionary.update', $term), [
            'name' => 'Terme sort_order modifié',
            'definition' => 'Définition modifiée sans le paramètre sort_order.',
            'type' => 'ai_term',
            'is_published' => '1',
            'dictionary_category_id' => '', // toujours envoyé par le <select> du vrai formulaire ("-- Aucune catégorie --")
        ])
        ->assertRedirect(route('admin.dictionary.index'));

    expect($term->fresh()->getTranslation('name', 'fr'))->toBe('Terme sort_order modifié');
});

// ── 3) Envoyer quand même le paramètre ne provoque AUCUNE erreur (validation tolérante) ────

test('envoyer quand même sort_order à la création ne provoque aucune erreur', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.dictionary.store'), [
            'name' => 'Terme avec sort order fantome',
            'definition' => 'Un appel externe pourrait encore envoyer ce paramètre.',
            'type' => 'ai_term',
            'is_published' => '1',
            'dictionary_category_id' => '', // toujours envoyé par le <select> du vrai formulaire ("-- Aucune catégorie --")
            'sort_order' => 42,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.dictionary.index'));

    $created = Term::query()->where('name->fr', 'Terme avec sort order fantome')->first();
    expect($created)->not->toBeNull();
});

test('envoyer quand même sort_order à la modification ne provoque aucune erreur', function () {
    $term = tsoTerm();

    $this->actingAs($this->admin)
        ->put(route('admin.dictionary.update', $term), [
            'name' => 'Terme modifie avec sort order fantome',
            'definition' => 'Un appel externe pourrait encore envoyer ce paramètre.',
            'type' => 'ai_term',
            'is_published' => '1',
            'dictionary_category_id' => '', // toujours envoyé par le <select> du vrai formulaire ("-- Aucune catégorie --")
            'sort_order' => 7,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.dictionary.index'));
});
