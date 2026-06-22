<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Éditeur de la BANQUE DE QUESTIONS (QuestionBankManager, QB2).
 *
 * Prouve que CHAQUE mutation est gardée par une autorisation SERVEUR OWNER-SCOPED
 * (OWASP A01) :
 *  - un formateur crée catégories + questions des 4 types (validation par type) ;
 *  - il ne voit/édite QUE les siennes (catégorie/question d'un AUTRE owner →
 *    ModelNotFound, anti-IDOR) ;
 *  - étudiant / sans-rôle → 403 sur la route ET au mount du composant ;
 *  - suppression d'une catégorie BLOQUÉE si elle contient questions ou sous-cat. ;
 *  - parent d'un AUTRE owner refusé à la création.
 *
 * Autonome : helpers préfixés qb2 (aucune redéclaration d'une fonction d'un autre
 * fichier de test). SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Modules\Academy\Livewire\QuestionBankManager;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers qb2 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function qb2Instructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function qb2Student(): User
{
    $user = User::factory()->create();
    $user->assignRole('student');

    return $user;
}

function qb2Category(User $owner, ?QuestionCategory $parent = null, string $name = 'Catégorie'): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => $parent?->id,
        'name'      => $name,
        'position'  => 0,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Accès / autorisation d'entrée
// ─────────────────────────────────────────────────────────────────────────────

test('un formateur peut ouvrir la banque', function (): void {
    Livewire::actingAs(qb2Instructor())
        ->test(QuestionBankManager::class)
        ->assertOk();
});

test('un étudiant reçoit 403 au mount du composant', function (): void {
    Livewire::actingAs(qb2Student())
        ->test(QuestionBankManager::class)
        ->assertStatus(403);
});

test('un étudiant reçoit 403 sur la route de la banque', function (): void {
    $this->actingAs(qb2Student())
        ->get('/academie/banque')
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// CRUD catégories (owner-scoped)
// ─────────────────────────────────────────────────────────────────────────────

test('un formateur crée une catégorie (owner_id forcé = lui)', function (): void {
    $instructor = qb2Instructor();

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->set('newCategoryName', 'Bases de l’IA')
        ->call('createCategory')
        ->assertHasNoErrors();

    $cat = QuestionCategory::where('name', 'Bases de l’IA')->first();
    expect($cat)->not->toBeNull();
    expect((int) $cat->owner_id)->toBe($instructor->id);
    expect($cat->parent_id)->toBeNull();
});

test('le parent d’un AUTRE owner est refusé (anti-IDOR)', function (): void {
    $mine    = qb2Instructor();
    $other   = qb2Instructor();
    $foreign = qb2Category($other, null, 'Parent étranger');

    $component = Livewire::actingAs($mine)
        ->test(QuestionBankManager::class)
        ->set('newCategoryName', 'Ma sous-catégorie')
        ->set('newCategoryParentId', $foreign->id);

    // resolveCategory scopé owner → la catégorie parente étrangère est introuvable.
    expect(fn () => $component->call('createCategory'))
        ->toThrow(ModelNotFoundException::class);

    expect(QuestionCategory::where('name', 'Ma sous-catégorie')->exists())->toBeFalse();
});

test('suppression bloquée si la catégorie contient des questions', function (): void {
    $instructor = qb2Instructor();
    $cat        = qb2Category($instructor);

    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $instructor->id,
        'type'        => 'truefalse',
        'prompt'      => 'Vrai ?',
        'payload'     => ['answer' => true],
        'difficulty'  => 'facile',
        'is_active'   => true,
    ]);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('deleteCategory', $cat->id);

    expect(QuestionCategory::find($cat->id))->not->toBeNull();
});

test('suppression bloquée si la catégorie contient des sous-catégories', function (): void {
    $instructor = qb2Instructor();
    $parent     = qb2Category($instructor, null, 'Parent');
    qb2Category($instructor, $parent, 'Enfant');

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('deleteCategory', $parent->id);

    expect(QuestionCategory::find($parent->id))->not->toBeNull();
});

test('une catégorie vide est supprimable', function (): void {
    $instructor = qb2Instructor();
    $cat        = qb2Category($instructor, null, 'Vide');

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('deleteCategory', $cat->id);

    expect(QuestionCategory::find($cat->id))->toBeNull();
});

test('un formateur ne peut pas supprimer la catégorie d’un autre (anti-IDOR)', function (): void {
    $mine    = qb2Instructor();
    $other   = qb2Instructor();
    $foreign = qb2Category($other, null, 'Étrangère');

    $component = Livewire::actingAs($mine)->test(QuestionBankManager::class);

    expect(fn () => $component->call('deleteCategory', $foreign->id))
        ->toThrow(ModelNotFoundException::class);

    expect(QuestionCategory::find($foreign->id))->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// CRUD questions — les 4 types + validation par type
// ─────────────────────────────────────────────────────────────────────────────

test('crée une question mcq valide', function (): void {
    $instructor = qb2Instructor();
    $cat        = qb2Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'mcq')
        ->set('qPrompt', 'Capitale du Québec ?')
        ->set('qChoices', ['Montréal', 'Québec', 'Laval'])
        ->set('qCorrect', 1)
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->where('type', 'mcq')->first();
    expect($q)->not->toBeNull();
    expect((int) $q->owner_id)->toBe($instructor->id);
    expect($q->payload['choices'])->toBe(['Montréal', 'Québec', 'Laval']);
    expect($q->payload['correct'])->toBe(1);
});

test('refuse une question mcq avec moins de 2 choix', function (): void {
    $instructor = qb2Instructor();
    $cat        = qb2Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'mcq')
        ->set('qPrompt', 'Incomplet')
        ->set('qChoices', ['Seul', ''])
        ->set('qCorrect', 0)
        ->call('saveQuestion')
        ->assertHasErrors('qChoices');

    expect(Question::where('category_id', $cat->id)->exists())->toBeFalse();
});

test('crée une question truefalse valide', function (): void {
    $instructor = qb2Instructor();
    $cat        = qb2Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'truefalse')
        ->set('qPrompt', 'Le Saint-Laurent est un fleuve.')
        ->set('qAnswerTrue', true)
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->where('type', 'truefalse')->first();
    expect($q)->not->toBeNull();
    expect($q->payload['answer'])->toBeTrue();
});

test('crée une question short valide', function (): void {
    $instructor = qb2Instructor();
    $cat        = qb2Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'short')
        ->set('qPrompt', 'Consigne donnée à une IA ?')
        ->set('qAccepted', ['prompt', 'invite'])
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->where('type', 'short')->first();
    expect($q)->not->toBeNull();
    expect($q->payload['accepted'])->toBe(['prompt', 'invite']);
});

test('refuse une question short sans réponse acceptée', function (): void {
    $instructor = qb2Instructor();
    $cat        = qb2Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'short')
        ->set('qPrompt', 'Sans réponse')
        ->set('qAccepted', ['', '  '])
        ->call('saveQuestion')
        ->assertHasErrors('qAccepted');

    expect(Question::where('category_id', $cat->id)->exists())->toBeFalse();
});

test('crée une question matching valide', function (): void {
    $instructor = qb2Instructor();
    $cat        = qb2Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'matching')
        ->set('qPrompt', 'Associe les termes.')
        ->set('qPairs', [
            ['term' => 'IA', 'def' => 'Intelligence artificielle.'],
            ['term' => 'API', 'def' => 'Interface de programmation.'],
        ])
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->where('type', 'matching')->first();
    expect($q)->not->toBeNull();
    expect($q->payload['pairs'])->toHaveCount(2);
});

test('refuse une question matching avec moins de 2 paires complètes', function (): void {
    $instructor = qb2Instructor();
    $cat        = qb2Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'matching')
        ->set('qPrompt', 'Incomplet')
        ->set('qPairs', [
            ['term' => 'IA', 'def' => 'Intelligence artificielle.'],
            ['term' => '', 'def' => ''],
        ])
        ->call('saveQuestion')
        ->assertHasErrors('qPairs');

    expect(Question::where('category_id', $cat->id)->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// Anti-IDOR sur l'édition/suppression de questions
// ─────────────────────────────────────────────────────────────────────────────

test('un formateur ne peut pas éditer la question d’un autre (anti-IDOR)', function (): void {
    $mine  = qb2Instructor();
    $other = qb2Instructor();
    $cat   = qb2Category($other, null, 'Catégorie de l’autre');

    $foreign = Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $other->id,
        'type'        => 'truefalse',
        'prompt'      => 'Protégée',
        'payload'     => ['answer' => true],
        'difficulty'  => 'facile',
        'is_active'   => true,
    ]);

    $component = Livewire::actingAs($mine)->test(QuestionBankManager::class);

    expect(fn () => $component->call('editQuestion', $foreign->id))
        ->toThrow(ModelNotFoundException::class);
});

test('un formateur ne peut pas supprimer la question d’un autre (anti-IDOR)', function (): void {
    $mine  = qb2Instructor();
    $other = qb2Instructor();
    $cat   = qb2Category($other, null, 'Catégorie de l’autre');

    $foreign = Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $other->id,
        'type'        => 'mcq',
        'prompt'      => 'Protégée',
        'payload'     => ['choices' => ['A', 'B'], 'correct' => 0],
        'difficulty'  => 'facile',
        'is_active'   => true,
    ]);

    $component = Livewire::actingAs($mine)->test(QuestionBankManager::class);

    expect(fn () => $component->call('deleteQuestion', $foreign->id))
        ->toThrow(ModelNotFoundException::class);

    expect(Question::find($foreign->id))->not->toBeNull();
});

test('la liste des catégories ne montre que les miennes', function (): void {
    $mine  = qb2Instructor();
    $other = qb2Instructor();

    qb2Category($mine, null, 'À moi 1');
    qb2Category($mine, null, 'À moi 2');
    qb2Category($other, null, 'À l’autre');

    Livewire::actingAs($mine)
        ->test(QuestionBankManager::class)
        ->assertSee('À moi 1')
        ->assertSee('À moi 2')
        ->assertDontSee('À l’autre');
});
