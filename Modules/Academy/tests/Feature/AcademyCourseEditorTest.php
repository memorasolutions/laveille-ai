<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Éditeur de cours front-end (CourseEditor, PHASE 3 / FE-3).
 *
 * Prouve que CHAQUE mutation est gardée par une autorisation SERVEUR (OWASP A01) :
 *  - admin                       → peut éditer/publier/structurer N'IMPORTE quel cours ;
 *  - formateur owner du cours A   → édite A, mais NE PEUT PAS ouvrir/éditer le cours B
 *    d'un autre formateur (ANTI-ESCALADE) ;
 *  - étudiant / user sans rôle    → interdit (entrée et mutations) ;
 *  - ANTI-IDOR : ajouter une leçon à un chapitre d'un AUTRE cours → refusé ;
 *  - suppression de cours par un non-owner → refusée.
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

    $this->courseA = makeEditorCourse('cours-a', 'Cours A');
    $this->courseB = makeEditorCourse('cours-b', 'Cours B');
});

/** Helper : crée un cours gratuit en brouillon minimal. */
function makeEditorCourse(string $slug, string $title): Course
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

/** Helper : ajoute un chapitre à un cours. */
function addEditorChapter(Course $course, string $title = 'Chapitre 1'): Chapter
{
    return Chapter::create([
        'course_id' => $course->id,
        'title'     => $title,
        'position'  => (int) Chapter::where('course_id', $course->id)->max('position') + 1,
    ]);
}

/** Helper : ajoute une leçon à un chapitre. */
function addEditorLesson(Chapter $chapter, string $title = 'Leçon'): Lesson
{
    $position = (int) Lesson::where('chapter_id', $chapter->id)->max('position') + 1;

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => $title,
        'slug'       => Illuminate\Support\Str::slug($title).'-'.$chapter->id.'-'.$position,
        'position'   => $position,
    ]);
}

/** Helper : ajoute un item à une leçon. */
function addEditorItem(Lesson $lesson, string $title = 'Élément'): LessonItem
{
    $position = (int) LessonItem::where('lesson_id', $lesson->id)->max('position') + 1;

    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'document',
        'title'       => $title,
        'position'    => $position,
        'payload'     => [],
        'is_required' => false,
    ]);
}

/** Helper : admin academy.manage. */
function makeEditorAdmin(): User
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
function makeEditorOwner(Course $course): User
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
// 1. ADMIN - peut tout faire sur N'IMPORTE quel cours
// ─────────────────────────────────────────────────────────────────────────────

test('admin peut éditer les métadonnées de n\'importe quel cours', function (): void {
    Livewire::actingAs(makeEditorAdmin())
        ->test(CourseEditor::class, ['course' => $this->courseB])
        ->set('title', 'Cours B révisé')
        ->set('summary', 'Nouveau résumé')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->courseB->fresh()->title)->toBe('Cours B révisé');
});

test('admin peut publier puis dépublier un cours', function (): void {
    $component = Livewire::actingAs(makeEditorAdmin())
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('togglePublish');

    expect($this->courseA->fresh()->status)->toBe('published');
    expect($this->courseA->fresh()->published_at)->not->toBeNull();

    $component->call('togglePublish');
    expect($this->courseA->fresh()->status)->toBe('draft');
});

test('admin peut ajouter un chapitre puis une leçon', function (): void {
    $component = Livewire::actingAs(makeEditorAdmin())
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->set('newChapterTitle', 'Introduction')
        ->call('addChapter')
        ->assertHasNoErrors();

    $chapter = Chapter::where('course_id', $this->courseA->id)->first();
    expect($chapter)->not->toBeNull();

    $component
        ->set("newLesson.{$chapter->id}.title", 'Première leçon')
        ->call('addLesson', $chapter->id)
        ->assertHasNoErrors();

    expect(Lesson::where('chapter_id', $chapter->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. FORMATEUR - édite SON cours A, JAMAIS le cours B d'un autre (ANTI-ESCALADE)
// ─────────────────────────────────────────────────────────────────────────────

test('formateur owner de A peut éditer les métadonnées et la structure de A', function (): void {
    $owner = makeEditorOwner($this->courseA);

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->set('title', 'Cours A par son owner')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->courseA->fresh()->title)->toBe('Cours A par son owner');

    $component->set('newChapterTitle', 'Chapitre owner')->call('addChapter')->assertHasNoErrors();
    expect(Chapter::where('course_id', $this->courseA->id)->count())->toBe(1);
});

test('ANTI-ESCALADE : formateur owner de A ne peut PAS ouvrir l\'éditeur du cours B', function (): void {
    $owner = makeEditorOwner($this->courseA);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseB])
        ->assertForbidden();
});

test('ANTI-ESCALADE : formateur de A ne peut pas muter B même en forgeant le courseId', function (): void {
    $owner = makeEditorOwner($this->courseA);

    // L'éditeur est ouvert légitimement sur A, puis on tente de pointer vers B.
    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA]);

    // Forge l'identifiant côté navigateur vers le cours B (non autorisé) puis
    // tente une mutation. save() re-résout B et appelle authorize('update', B)
    // → Livewire rend un 403 (l'autorisation serveur bloque l'écriture).
    $component->set('courseId', $this->courseB->id)
        ->set('title', 'Pirate')
        ->call('save')
        ->assertForbidden();

    // Le cours B n'a pas changé.
    expect($this->courseB->fresh()->title)->toBe('Cours B');
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. ÉTUDIANT / USER SANS RÔLE - interdit
// ─────────────────────────────────────────────────────────────────────────────

test('étudiant ne peut pas ouvrir l\'éditeur', function (): void {
    $student = User::factory()->create();
    $student->assignRole('student');

    Livewire::actingAs($student)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->assertForbidden();
});

test('utilisateur sans aucun rôle ne peut pas ouvrir l\'éditeur', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. ANTI-IDOR - ajouter une leçon à un chapitre d'un AUTRE cours est refusé
// ─────────────────────────────────────────────────────────────────────────────

test('ANTI-IDOR : ajouter une leçon à un chapitre n\'appartenant pas au cours courant est refusé', function (): void {
    $owner = makeEditorOwner($this->courseA);

    // Un chapitre qui appartient au cours B (étranger à l'éditeur ouvert sur A).
    $foreignChapter = addEditorChapter($this->courseB, 'Chapitre étranger');

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->set("newLesson.{$foreignChapter->id}.title", 'Leçon injectée');

    // resolveChapterFor() ne trouve pas le chapitre dans le cours A → 404 ModelNotFound.
    expect(fn () => $component->call('addLesson', $foreignChapter->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Lesson::where('chapter_id', $foreignChapter->id)->count())->toBe(0);
});

test('ANTI-IDOR : supprimer un chapitre d\'un autre cours est refusé', function (): void {
    $owner = makeEditorOwner($this->courseA);
    $foreignChapter = addEditorChapter($this->courseB, 'Chapitre étranger');

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA]);

    expect(fn () => $component->call('deleteChapter', $foreignChapter->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Chapter::find($foreignChapter->id))->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. SUPPRESSION DU COURS - réservée admin/owner
// ─────────────────────────────────────────────────────────────────────────────

test('un formateur editor (non owner) ne peut PAS supprimer le cours', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('instructor');
    CourseRole::create([
        'course_id' => $this->courseA->id,
        'user_id'   => $editor->id,
        'role'      => 'editor',
    ]);

    // L'editor a bien le droit d'ENTRER (update) mais PAS de supprimer (delete).
    Livewire::actingAs($editor)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('deleteCourse')
        ->assertForbidden();

    expect(Course::find($this->courseA->id))->not->toBeNull();
});

test('le propriétaire peut supprimer son cours', function (): void {
    $owner = makeEditorOwner($this->courseA);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('deleteCourse')
        ->assertRedirect(route('academy.dashboard'));

    expect(Course::find($this->courseA->id))->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. GLISSER-DÉPOSER (B1) - reorderChapters / reorderLessons / reorderItems
//    Persistance des positions + anti-IDOR + autorisation serveur.
// ─────────────────────────────────────────────────────────────────────────────

test('reorderChapters persiste le nouvel ordre des chapitres', function (): void {
    $c1 = addEditorChapter($this->courseA, 'Chapitre 1'); // position 1
    $c2 = addEditorChapter($this->courseA, 'Chapitre 2'); // position 2
    $c3 = addEditorChapter($this->courseA, 'Chapitre 3'); // position 3

    Livewire::actingAs(makeEditorAdmin())
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('reorderChapters', [$c3->id, $c1->id, $c2->id])
        ->assertHasNoErrors();

    expect($c3->fresh()->position)->toBe(1);
    expect($c1->fresh()->position)->toBe(2);
    expect($c2->fresh()->position)->toBe(3);
});

test('reorderLessons persiste le nouvel ordre des leçons d\'un chapitre', function (): void {
    $chapter = addEditorChapter($this->courseA);
    $l1 = addEditorLesson($chapter, 'Leçon 1');
    $l2 = addEditorLesson($chapter, 'Leçon 2');
    $l3 = addEditorLesson($chapter, 'Leçon 3');

    Livewire::actingAs(makeEditorAdmin())
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('reorderLessons', $chapter->id, [$l2->id, $l3->id, $l1->id])
        ->assertHasNoErrors();

    expect($l2->fresh()->position)->toBe(1);
    expect($l3->fresh()->position)->toBe(2);
    expect($l1->fresh()->position)->toBe(3);
});

test('reorderItems persiste le nouvel ordre des éléments d\'une leçon', function (): void {
    $chapter = addEditorChapter($this->courseA);
    $lesson  = addEditorLesson($chapter);
    $i1 = addEditorItem($lesson, 'Élément 1');
    $i2 = addEditorItem($lesson, 'Élément 2');
    $i3 = addEditorItem($lesson, 'Élément 3');

    Livewire::actingAs(makeEditorAdmin())
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('reorderItems', $lesson->id, [$i3->id, $i2->id, $i1->id])
        ->assertHasNoErrors();

    expect($i3->fresh()->position)->toBe(1);
    expect($i2->fresh()->position)->toBe(2);
    expect($i1->fresh()->position)->toBe(3);
});

// ── ANTI-IDOR : un id étranger dans la liste → AUCUNE écriture ────────────────

test('ANTI-IDOR : reorderChapters avec un chapitre d\'un AUTRE cours est rejeté sans rien écrire', function (): void {
    $a1 = addEditorChapter($this->courseA, 'A1'); // position 1
    $a2 = addEditorChapter($this->courseA, 'A2'); // position 2
    $foreign = addEditorChapter($this->courseB, 'B étranger');

    Livewire::actingAs(makeEditorAdmin())
        ->test(CourseEditor::class, ['course' => $this->courseA])
        // On injecte un id étranger : l'ensemble ne correspond plus aux chapitres de A.
        ->call('reorderChapters', [$a2->id, $foreign->id])
        ->assertHasNoErrors();

    // Positions inchangées : la garde a tout rejeté.
    expect($a1->fresh()->position)->toBe(1);
    expect($a2->fresh()->position)->toBe(2);
    expect($foreign->fresh()->position)->toBe(1); // 1re du cours B, intacte.
});

test('ANTI-IDOR : reorderLessons avec une leçon d\'un autre chapitre est rejeté sans rien écrire', function (): void {
    $chapter      = addEditorChapter($this->courseA, 'Chapitre A');
    $otherChapter = addEditorChapter($this->courseA, 'Autre chapitre A');
    $l1 = addEditorLesson($chapter, 'L1');
    $l2 = addEditorLesson($chapter, 'L2');
    $foreignLesson = addEditorLesson($otherChapter, 'Leçon étrangère');

    Livewire::actingAs(makeEditorAdmin())
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('reorderLessons', $chapter->id, [$l2->id, $foreignLesson->id])
        ->assertHasNoErrors();

    expect($l1->fresh()->position)->toBe(1);
    expect($l2->fresh()->position)->toBe(2);
    expect($foreignLesson->fresh()->position)->toBe(1);
});

test('ANTI-IDOR : reorderLessons ciblant un chapitre d\'un AUTRE cours lève ModelNotFound', function (): void {
    $owner          = makeEditorOwner($this->courseA);
    $foreignChapter = addEditorChapter($this->courseB, 'Chapitre du cours B');
    $foreignLesson  = addEditorLesson($foreignChapter, 'Leçon du cours B');

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA]);

    expect(fn () => $component->call('reorderLessons', $foreignChapter->id, [$foreignLesson->id]))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($foreignLesson->fresh()->position)->toBe(1);
});

test('ANTI-IDOR : reorderItems avec un item d\'une autre leçon est rejeté sans rien écrire', function (): void {
    $chapter     = addEditorChapter($this->courseA);
    $lesson      = addEditorLesson($chapter, 'Leçon cible');
    $otherLesson = addEditorLesson($chapter, 'Autre leçon');
    $i1 = addEditorItem($lesson, 'I1');
    $i2 = addEditorItem($lesson, 'I2');
    $foreignItem = addEditorItem($otherLesson, 'Item étranger');

    Livewire::actingAs(makeEditorAdmin())
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->call('reorderItems', $lesson->id, [$i2->id, $foreignItem->id])
        ->assertHasNoErrors();

    expect($i1->fresh()->position)->toBe(1);
    expect($i2->fresh()->position)->toBe(2);
    expect($foreignItem->fresh()->position)->toBe(1);
});

test('ANTI-IDOR : reorderChapters rejette une liste incomplète (sous-ensemble) sans rien écrire', function (): void {
    $a1 = addEditorChapter($this->courseA, 'A1');
    $a2 = addEditorChapter($this->courseA, 'A2');
    $a3 = addEditorChapter($this->courseA, 'A3');

    Livewire::actingAs(makeEditorAdmin())
        ->test(CourseEditor::class, ['course' => $this->courseA])
        // Liste partielle (a3 manquant) : cardinalité différente → rejet total.
        ->call('reorderChapters', [$a2->id, $a1->id])
        ->assertHasNoErrors();

    expect($a1->fresh()->position)->toBe(1);
    expect($a2->fresh()->position)->toBe(2);
    expect($a3->fresh()->position)->toBe(3);
});

// ── AUTORISATION : non-gestionnaire / formateur d'un autre cours refusés ──────

test('un étudiant ne peut PAS réordonner (entrée éditeur refusée)', function (): void {
    $chapter = addEditorChapter($this->courseA);
    $student = User::factory()->create();
    $student->assignRole('student');

    Livewire::actingAs($student)
        ->test(CourseEditor::class, ['course' => $this->courseA])
        ->assertForbidden();
});

test('ANTI-ESCALADE : formateur de A ne peut pas réordonner les chapitres de B en forgeant le courseId', function (): void {
    $owner = makeEditorOwner($this->courseA);
    $b1 = addEditorChapter($this->courseB, 'B1');
    $b2 = addEditorChapter($this->courseB, 'B2');

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->courseA]);

    // On pointe l'éditeur vers B (non autorisé) puis on tente de réordonner.
    $component->set('courseId', $this->courseB->id)
        ->call('reorderChapters', [$b2->id, $b1->id])
        ->assertForbidden();

    // Aucune écriture sur le cours B.
    expect($b1->fresh()->position)->toBe(1);
    expect($b2->fresh()->position)->toBe(2);
});
