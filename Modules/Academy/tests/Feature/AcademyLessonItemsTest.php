<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Items de leçon dans l'éditeur front-end (CourseEditor, FE-3b).
 *
 * Prouve que CHAQUE mutation d'item est gardée par une autorisation SERVEUR
 * (OWASP A01) ET vérifie l'appartenance de l'item à CE cours (anti-IDOR) :
 *  - formateur owner du cours A : add/update/delete/move/toggleRequired sur SES items ;
 *  - ANTI-IDOR : agir sur un item/leçon d'un AUTRE cours (B) → ModelNotFound, rien écrit ;
 *  - ANTI-ESCALADE : formateur d'un autre cours → 403, aucune écriture ;
 *  - étudiant / user sans rôle → interdit ;
 *  - validation du `type` : un type hors liste blanche → refusé.
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->courseA = makeItemsCourse('cours-a', 'Cours A');
    $this->courseB = makeItemsCourse('cours-b', 'Cours B');

    // Une leçon dans chaque cours.
    $this->lessonA = makeItemsLesson($this->courseA);
    $this->lessonB = makeItemsLesson($this->courseB);
});

/** Helper : crée un cours gratuit en brouillon minimal. */
function makeItemsCourse(string $slug, string $title): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => $title,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'draft',
        'currency'    => 'CAD',
    ]);
}

/** Helper : crée un chapitre + une leçon dans un cours et retourne la leçon. */
function makeItemsLesson(Course $course): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-'.$chapter->id,
        'position'   => 1,
    ]);
}

/** Helper : crée un item dans une leçon donnée. */
function makeItem(Lesson $lesson, string $type = 'video', int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => $type,
        'title'       => 'Élément '.$position,
        'position'    => $position,
        'payload'     => [],
        'is_required' => false,
    ]);
}

/** Helper : admin academy.manage. */
function makeItemsAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    if (! $admin->can('academy.manage')) {
        $admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    return $admin;
}

/** Helper : formateur owner d'un cours donné. */
function makeItemsOwner(Course $course): User
{
    $owner = User::factory()->create();
    $owner->assignRole('instructor');
    CourseRole::create([
        'course_id' => $course->id,
        'user_id'   => $owner->id,
        'role'      => 'owner',
    ]);

    return $owner;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. FORMATEUR OWNER - cycle complet sur SA leçon
// ─────────────────────────────────────────────────────────────────────────────

test('formateur owner peut ajouter une vidéo (ScreenPal) à sa leçon', function (): void {
    $owner = makeItemsOwner($this->courseA);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->set("newItem.{$this->lessonA->id}.title", 'Vidéo intro')
        ->set("newItem.{$this->lessonA->id}.external_ref", 'screenpal-abc123')
        ->call('addItem', $this->lessonA->id, 'video')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $this->lessonA->id)->first();
    expect($item)->not->toBeNull();
    expect($item->type)->toBe('video');
    expect($item->payload['embed'] ?? null)->toBe('screenpal-abc123');
    expect($item->external_ref)->toBe('screenpal-abc123');
});

test('formateur owner peut ajouter un document avec texte riche', function (): void {
    $owner = makeItemsOwner($this->courseA);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->set("newItem.{$this->lessonA->id}.title", 'Notes de cours')
        ->set("newItem.{$this->lessonA->id}.rich_text", '# Titre\nContenu du document.')
        ->call('addItem', $this->lessonA->id, 'document')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $this->lessonA->id)->where('type', 'document')->first();
    expect($item)->not->toBeNull();
    expect($item->payload['rich_text'] ?? null)->toContain('Contenu du document');
});

test('formateur owner peut ajouter un quiz lié à une banque QT', function (): void {
    $owner = makeItemsOwner($this->courseA);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->set("newItem.{$this->lessonA->id}.title", 'Quiz du module')
        ->set("newItem.{$this->lessonA->id}.qt_bank_key", 'qt.module.1')
        ->call('addItem', $this->lessonA->id, 'quiz')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $this->lessonA->id)->where('type', 'quiz')->first();
    expect($item)->not->toBeNull();
    expect($item->payload['qt_bank_key'] ?? null)->toBe('qt.module.1');
});

test('formateur owner peut mettre à jour un item de sa leçon', function (): void {
    $owner = makeItemsOwner($this->courseA);
    $item  = makeItem($this->lessonA, 'video');

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('updateItem', $item->id, 'video', 'Titre révisé', 12, 'screenpal-xyz', null, null)
        ->assertHasNoErrors();

    $item->refresh();
    expect($item->title)->toBe('Titre révisé');
    expect($item->estimated_minutes)->toBe(12);
    expect($item->payload['embed'] ?? null)->toBe('screenpal-xyz');
});

test('formateur owner peut supprimer un item de sa leçon', function (): void {
    $owner = makeItemsOwner($this->courseA);
    $item  = makeItem($this->lessonA, 'document');

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('deleteItem', $item->id)
        ->assertHasNoErrors();

    expect(LessonItem::find($item->id))->toBeNull();
});

test('formateur owner peut basculer le caractère obligatoire d\'un item', function (): void {
    $owner = makeItemsOwner($this->courseA);
    $item  = makeItem($this->lessonA, 'quiz');
    expect($item->is_required)->toBeFalse();

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('toggleRequired', $item->id);

    expect($item->fresh()->is_required)->toBeTrue();

    $component->call('toggleRequired', $item->id);
    expect($item->fresh()->is_required)->toBeFalse();
});

test('formateur owner peut réordonner les items (monter / descendre)', function (): void {
    $owner = makeItemsOwner($this->courseA);
    $first  = makeItem($this->lessonA, 'video', 1);
    $second = makeItem($this->lessonA, 'document', 2);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('moveItemUp', $second->id);

    expect($first->fresh()->position)->toBe(2);
    expect($second->fresh()->position)->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. ANTI-IDOR - agir sur un item/leçon d'un AUTRE cours est refusé
// ─────────────────────────────────────────────────────────────────────────────

test('ANTI-IDOR : ajouter un item à une leçon d\'un autre cours est refusé', function (): void {
    $owner = makeItemsOwner($this->courseA);

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->set("newItem.{$this->lessonB->id}.title", 'Item injecté');

    // resolveLessonFor() ne trouve pas la leçon de B dans le cours A → ModelNotFound.
    expect(fn () => $component->call('addItem', $this->lessonB->id, 'video'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(LessonItem::where('lesson_id', $this->lessonB->id)->count())->toBe(0);
});

test('ANTI-IDOR : mettre à jour un item d\'un autre cours est refusé, rien écrit', function (): void {
    $owner       = makeItemsOwner($this->courseA);
    $foreignItem = makeItem($this->lessonB, 'video');

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA]);

    expect(fn () => $component->call('updateItem', $foreignItem->id, 'video', 'Piraté', null, 'hack', null, null))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($foreignItem->fresh()->title)->toBe($foreignItem->title);
    expect($foreignItem->fresh()->title)->not->toBe('Piraté');
});

test('ANTI-IDOR : supprimer un item d\'un autre cours est refusé, l\'item reste', function (): void {
    $owner       = makeItemsOwner($this->courseA);
    $foreignItem = makeItem($this->lessonB, 'quiz');

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA]);

    expect(fn () => $component->call('deleteItem', $foreignItem->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(LessonItem::find($foreignItem->id))->not->toBeNull();
});

test('ANTI-IDOR : toggleRequired sur un item d\'un autre cours est refusé', function (): void {
    $owner       = makeItemsOwner($this->courseA);
    $foreignItem = makeItem($this->lessonB, 'video');

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA]);

    expect(fn () => $component->call('toggleRequired', $foreignItem->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($foreignItem->fresh()->is_required)->toBeFalse();
});

test('ANTI-IDOR : réordonner un item d\'un autre cours est refusé', function (): void {
    $owner       = makeItemsOwner($this->courseA);
    $foreignItem = makeItem($this->lessonB, 'video');

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA]);

    expect(fn () => $component->call('moveItemUp', $foreignItem->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. ANTI-ESCALADE - formateur d'un autre cours → 403, aucune écriture
// ─────────────────────────────────────────────────────────────────────────────

test('ANTI-ESCALADE : formateur de A ne peut pas muter un item de B en forgeant le courseId', function (): void {
    $owner       = makeItemsOwner($this->courseA);
    $foreignItem = makeItem($this->lessonB, 'video');

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA]);

    // On forge l'identifiant du cours côté navigateur vers B (non autorisé).
    // addItem re-résout B puis authorize('manageStructure', B) → 403.
    $component->set('courseId', $this->courseB->id)
        ->set("newItem.{$this->lessonB->id}.title", 'Pirate')
        ->call('addItem', $this->lessonB->id, 'video')
        ->assertForbidden();

    expect(LessonItem::where('lesson_id', $this->lessonB->id)->where('title', 'Pirate')->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. ÉTUDIANT / USER SANS RÔLE - interdit
// ─────────────────────────────────────────────────────────────────────────────

test('étudiant ne peut pas ouvrir l\'éditeur (donc pas gérer les items)', function (): void {
    $student = User::factory()->create();
    $student->assignRole('student');

    Livewire::actingAs($student)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->assertForbidden();
});

test('utilisateur sans aucun rôle ne peut pas gérer les items', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. VALIDATION DU TYPE - liste blanche
// ─────────────────────────────────────────────────────────────────────────────

test('un type d\'item hors liste blanche est refusé, rien écrit', function (): void {
    $owner = makeItemsOwner($this->courseA);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->set("newItem.{$this->lessonA->id}.title", 'Type bidon')
        ->call('addItem', $this->lessonA->id, 'malware')
        ->assertHasErrors('type');

    expect(LessonItem::where('lesson_id', $this->lessonA->id)->count())->toBe(0);
});

test('le titre est obligatoire pour ajouter un item', function (): void {
    $owner = makeItemsOwner($this->courseA);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('addItem', $this->lessonA->id, 'video')
        ->assertHasErrors('title');

    expect(LessonItem::where('lesson_id', $this->lessonA->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. ADMIN - peut gérer les items de n'importe quel cours
// ─────────────────────────────────────────────────────────────────────────────

test('admin peut ajouter un item à n\'importe quel cours', function (): void {
    Livewire::actingAs(makeItemsAdmin())
        ->test(CourseEditor::class, ['course' => $this->courseB])
        ->set("newItem.{$this->lessonB->id}.title", 'Item admin')
        ->call('addItem', $this->lessonB->id, 'document')
        ->assertHasNoErrors();

    expect(LessonItem::where('lesson_id', $this->lessonB->id)->count())->toBe(1);
});
