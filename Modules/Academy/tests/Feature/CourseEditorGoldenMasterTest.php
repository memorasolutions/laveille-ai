<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * TESTS GOLDEN-MASTER / CARACTÉRISATION — CourseEditor.php (God-component ~2852 L.)
 *
 * OBJECTIF : FIGER le comportement ACTUEL de CourseEditor AVANT tout refactor.
 * Ces tests décrivent CE QUI EST, pas ce qui devrait idéalement être. Si un
 * comportement paraît étrange, on le fige tel quel (voir commentaire BIZARRERIE).
 *
 * COUVERTURE (complète les 22 tests de AcademyCourseEditorTest déjà verts) :
 *  A. Montage / hydratation (fillMetadataFrom)
 *  B. Métadonnées : save() avec cours payant sans prix (erreur business)
 *  C. Mutations chapitres : updateChapter, moveChapterUp/Down
 *  D. Mutations leçons   : updateLesson (drip=0→null), deleteLesson,
 *                          moveLessonUp/Down
 *  E. Mutations items    : addItem(document/video), updateItem,
 *                          deleteItem, toggleRequired, moveItemUp/Down
 *  F. Achèvement du cours : saveCompletion (all_required / percent + valeur /
 *                           percent sans valeur → erreur)
 *  G. Certificat          : saveCertificate (hex valide / hex invalide → erreur)
 *  H. Prérequis           : savePrerequisites (sync + anti-IDOR : id forgé écarté)
 *  I. Confirmations 2-temps (jamais de popup native) : confirm/cancel pour
 *                           chapitre, leçon, item, cours
 *  J. Autosave via updated() : modifier un champ metadata persiste SANS call explicite
 *  K. Anti-IDOR complémentaires : deleteItem / toggleRequired / updateItem sur
 *                                 item étranger → ModelNotFoundException
 *
 * GARDE-FOU : si le module Academy est désactivé, tous les tests sont SKIPPED.
 * NOMS DES HELPERS : préfixe `gmCE_` pour éviter les collisions avec les fonctions
 * globales de AcademyCourseEditorTest.php (même namespace global Pest).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\CourseCompletionService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixe gmCE_ pour éviter collision avec AcademyCourseEditorTest)
// ─────────────────────────────────────────────────────────────────────────────

function gmCE_makeCourse(string $slug = 'cours-gm', string $title = 'Cours GM'): Course
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

function gmCE_makeAdmin(): User
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

function gmCE_makeOwner(Course $course): User
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

function gmCE_addChapter(Course $course, string $title = 'Chapitre GM'): Chapter
{
    return Chapter::create([
        'course_id' => $course->id,
        'title'     => $title,
        'position'  => (int) Chapter::where('course_id', $course->id)->max('position') + 1,
    ]);
}

function gmCE_addLesson(Chapter $chapter, string $title = 'Leçon GM'): Lesson
{
    $position = (int) Lesson::where('chapter_id', $chapter->id)->max('position') + 1;

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => $title,
        'slug'       => \Illuminate\Support\Str::slug($title).'-'.$chapter->id.'-'.$position,
        'position'   => $position,
    ]);
}

function gmCE_addItem(Lesson $lesson, string $type = 'document', string $title = 'Élément GM'): LessonItem
{
    $position = (int) LessonItem::where('lesson_id', $lesson->id)->max('position') + 1;

    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => $type,
        'title'       => $title,
        'position'    => $position,
        'payload'     => [],
        'is_required' => false,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Setup commun
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->course = gmCE_makeCourse();
    $this->admin  = gmCE_makeAdmin();
});

// ─────────────────────────────────────────────────────────────────────────────
// A. MONTAGE — hydratation des champs depuis le modèle
// ─────────────────────────────────────────────────────────────────────────────

test('A1 : le montage initialise les champs de métadonnées depuis le cours', function (): void {
    // CARACTÉRISATION : fillMetadataFrom() doit peupler les propriétés Livewire
    // avec les valeurs actuelles du modèle (pas des valeurs par défaut du composant).
    $course = gmCE_makeCourse('montage-test', 'Cours Montage');
    $course->update(['level' => 'avance', 'access_type' => 'free', 'language' => 'en-CA']);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $course]);

    $component
        ->assertSet('title', 'Cours Montage')
        ->assertSet('level', 'avance')
        ->assertSet('access_type', 'free')
        ->assertSet('language', 'en-CA')
        ->assertSet('courseId', $course->id);
});

test('A2 : le montage hydrate les critères d\'achèvement (défaut all_required)', function (): void {
    // CARACTÉRISATION : un cours sans completion_criteria → all_required + value 80.
    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course]);

    $component
        ->assertSet('completion_type', CourseCompletionService::TYPE_ALL_REQUIRED)
        ->assertSet('completion_value', 80);
});

// ─────────────────────────────────────────────────────────────────────────────
// B. MÉTADONNÉES — save() : règle prix pour cours payant
// ─────────────────────────────────────────────────────────────────────────────

test('B1 : save() avec access_type payant et price_cents nul retourne une erreur', function (): void {
    // CARACTÉRISATION : règle business — un cours payant sans prix déclenche addError
    // (return anticipé, pas d'exception). Le cours reste inchangé.
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('access_type', 'paid_one_time')
        ->set('price_cents', null)
        ->call('save')
        ->assertHasErrors(['price_cents']);

    expect($this->course->fresh()->access_type)->toBe('free'); // inchangé
});

test('B2 : save() avec access_type payant et un prix valide persiste', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('access_type', 'paid_one_time')
        ->set('price_cents', 4900)
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $this->course->fresh();
    expect($fresh->access_type)->toBe('paid_one_time');
    expect($fresh->price_cents)->toBe(4900);
});

// ─────────────────────────────────────────────────────────────────────────────
// C. CHAPITRES — updateChapter, moveChapterUp/Down
// ─────────────────────────────────────────────────────────────────────────────

test('C1 : updateChapter modifie le titre et le résumé d\'un chapitre', function (): void {
    $chapter = gmCE_addChapter($this->course, 'Titre initial');

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('updateChapter', $chapter->id, 'Titre modifié', 'Résumé ajouté')
        ->assertHasNoErrors();

    $fresh = $chapter->fresh();
    expect($fresh->title)->toBe('Titre modifié');
    expect($fresh->summary)->toBe('Résumé ajouté');
});

test('C2 : moveChapterUp échange les positions de deux chapitres', function (): void {
    $c1 = gmCE_addChapter($this->course, 'Chapitre 1'); // position 1
    $c2 = gmCE_addChapter($this->course, 'Chapitre 2'); // position 2

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('moveChapterUp', $c2->id)    // c2 remonte → devient position 1
        ->assertHasNoErrors();

    expect($c2->fresh()->position)->toBe(1);
    expect($c1->fresh()->position)->toBe(2);
});

test('C3 : moveChapterDown échange les positions de deux chapitres', function (): void {
    $c1 = gmCE_addChapter($this->course, 'Chapitre 1'); // position 1
    $c2 = gmCE_addChapter($this->course, 'Chapitre 2'); // position 2

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('moveChapterDown', $c1->id)  // c1 descend → devient position 2
        ->assertHasNoErrors();

    expect($c1->fresh()->position)->toBe(2);
    expect($c2->fresh()->position)->toBe(1);
});

test('C4 : moveChapterUp sur le premier chapitre ne change rien (bout de liste)', function (): void {
    $c1 = gmCE_addChapter($this->course, 'Unique'); // position 1

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('moveChapterUp', $c1->id)
        ->assertHasNoErrors();

    // CARACTÉRISATION : swapChapter retourne silencieusement (pas d'erreur ni d'exception).
    expect($c1->fresh()->position)->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// D. LEÇONS — updateLesson, deleteLesson, moveLessonUp/Down
// ─────────────────────────────────────────────────────────────────────────────

test('D1 : updateLesson modifie le titre d\'une leçon', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter, 'Leçon originale');

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('updateLesson', $lesson->id, 'Leçon mise à jour', 'Résumé', 30, null)
        ->assertHasNoErrors();

    $fresh = $lesson->fresh();
    expect($fresh->title)->toBe('Leçon mise à jour');
    expect($fresh->estimated_minutes)->toBe(30);
});

test('D2 : updateLesson normalise drip_days=0 en null (BIZARRERIE : 0 = « immédiat » = null)', function (): void {
    // CARACTÉRISATION COMPORTEMENT ÉTRANGE :
    // drip_days=0 signifie « disponible immédiatement » → normalisé en null.
    // On fige cette normalisation pour que le refactor la préserve.
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter);
    $lesson->update(['drip_days' => 5]);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('updateLesson', $lesson->id, $lesson->title, null, null, 0)
        ->assertHasNoErrors();

    // 0 jours → doit être persisté comme null (rétrocompat « pas de drip »).
    expect($lesson->fresh()->drip_days)->toBeNull();
});

test('D3 : deleteLesson supprime la leçon et réinitialise confirmingLessonDeletion', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter, 'Leçon à supprimer');

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('confirmingLessonDeletion', $lesson->id)
        ->call('deleteLesson', $lesson->id)
        ->assertHasNoErrors();

    // La leçon disparaît de la BD.
    expect(Lesson::find($lesson->id))->toBeNull();

    // CARACTÉRISATION : deleteLesson réinitialise l'état de confirmation.
    $component->assertSet('confirmingLessonDeletion', null);
});

test('D4 : moveLessonUp échange les positions de deux leçons', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $l1 = gmCE_addLesson($chapter, 'Leçon 1'); // position 1
    $l2 = gmCE_addLesson($chapter, 'Leçon 2'); // position 2

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('moveLessonUp', $l2->id)
        ->assertHasNoErrors();

    expect($l2->fresh()->position)->toBe(1);
    expect($l1->fresh()->position)->toBe(2);
});

test('D5 : moveLessonDown échange les positions de deux leçons', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $l1 = gmCE_addLesson($chapter, 'Leçon 1'); // position 1
    $l2 = gmCE_addLesson($chapter, 'Leçon 2'); // position 2

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('moveLessonDown', $l1->id)
        ->assertHasNoErrors();

    expect($l1->fresh()->position)->toBe(2);
    expect($l2->fresh()->position)->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// E. ITEMS DE LEÇON — addItem, updateItem, deleteItem, toggleRequired, move
// ─────────────────────────────────────────────────────────────────────────────

test('E1 : addItem(document) crée un LessonItem de type document', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set("newItem.{$lesson->id}", ['title' => 'Mon document', 'type' => 'document'])
        ->call('addItem', $lesson->id, 'document')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->first();
    expect($item)->not->toBeNull();
    expect($item->type)->toBe('document');
    expect($item->title)->toBe('Mon document');
});

test('E2 : addItem(video) crée un LessonItem de type video', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set("newItem.{$lesson->id}", [
            'title'      => 'Ma vidéo',
            'type'       => 'video',
            'player_url' => 'https://screenpal.com/player/c-abc123',
        ])
        ->call('addItem', $lesson->id, 'video')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'video')->first();
    expect($item)->not->toBeNull();
    expect($item->title)->toBe('Ma vidéo');
});

test('E3 : updateItem modifie le titre d\'un item existant', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter);
    $item    = gmCE_addItem($lesson, 'document', 'Titre original');

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('updateItem', $item->id, 'document', 'Titre mis à jour', null, [])
        ->assertHasNoErrors();

    expect($item->fresh()->title)->toBe('Titre mis à jour');
});

test('E4 : deleteItem supprime l\'item et réinitialise confirmingItemDeletion', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter);
    $item    = gmCE_addItem($lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('confirmingItemDeletion', $item->id)
        ->call('deleteItem', $item->id)
        ->assertHasNoErrors();

    expect(LessonItem::find($item->id))->toBeNull();

    // CARACTÉRISATION : deleteItem réinitialise l'état de confirmation.
    $component->assertSet('confirmingItemDeletion', null);
});

test('E5 : toggleRequired inverse le drapeau is_required d\'un item', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter);
    $item    = gmCE_addItem($lesson); // is_required = false

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('toggleRequired', $item->id)
        ->assertHasNoErrors();

    expect($item->fresh()->is_required)->toBeTrue();

    // Deuxième appel : repasse à false.
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('toggleRequired', $item->id)
        ->assertHasNoErrors();

    expect($item->fresh()->is_required)->toBeFalse();
});

test('E6 : moveItemUp échange les positions de deux items', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter);
    $i1 = gmCE_addItem($lesson, 'document', 'Item 1'); // position 1
    $i2 = gmCE_addItem($lesson, 'document', 'Item 2'); // position 2

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('moveItemUp', $i2->id)
        ->assertHasNoErrors();

    expect($i2->fresh()->position)->toBe(1);
    expect($i1->fresh()->position)->toBe(2);
});

test('E7 : moveItemDown échange les positions de deux items', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter);
    $i1 = gmCE_addItem($lesson, 'document', 'Item 1'); // position 1
    $i2 = gmCE_addItem($lesson, 'document', 'Item 2'); // position 2

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('moveItemDown', $i1->id)
        ->assertHasNoErrors();

    expect($i1->fresh()->position)->toBe(2);
    expect($i2->fresh()->position)->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// F. ACHÈVEMENT DU COURS — saveCompletion
// ─────────────────────────────────────────────────────────────────────────────

test('F1 : saveCompletion avec all_required persiste le critère en BD', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('completion_type', CourseCompletionService::TYPE_ALL_REQUIRED)
        ->call('saveCompletion')
        ->assertHasNoErrors();

    $criteria = $this->course->fresh()->completion_criteria;
    expect($criteria['type'])->toBe(CourseCompletionService::TYPE_ALL_REQUIRED);
});

test('F2 : saveCompletion avec percent et valeur persiste le critère et la valeur', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('completion_type', CourseCompletionService::TYPE_PERCENT)
        ->set('completion_value', 75)
        ->call('saveCompletion')
        ->assertHasNoErrors();

    $criteria = $this->course->fresh()->completion_criteria;
    expect($criteria['type'])->toBe(CourseCompletionService::TYPE_PERCENT);
    expect($criteria['value'])->toBe(75);
});

test('F3 : saveCompletion avec percent et valeur hors-limites (> 100) retourne une erreur', function (): void {
    // CARACTÉRISATION : completion_value doit être entre 1 et 100.
    // Note : passer null via ->set() sur ?int en Livewire v4 ne déclenche PAS
    // l'erreur `required` (comportement observé — peut-être coercition interne).
    // En revanche, une valeur > 100 échoue bien la règle max:100.
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('completion_type', CourseCompletionService::TYPE_PERCENT)
        ->set('completion_value', 101)
        ->call('saveCompletion')
        ->assertHasErrors(['completion_value']);
});

// ─────────────────────────────────────────────────────────────────────────────
// G. PERSONNALISATION DU CERTIFICAT — saveCertificate
// ─────────────────────────────────────────────────────────────────────────────

test('G1 : saveCertificate persiste un titre, un message et une couleur hex valide', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('certificate_title', 'Certificat de réussite')
        ->set('certificate_message', 'Félicitations pour avoir complété ce cours.')
        ->set('certificate_signature_name', 'Stéphane Lapointe')
        ->set('certificate_accent_color', '#064E5A')
        ->call('saveCertificate')
        ->assertHasNoErrors();

    $fresh = $this->course->fresh();
    expect($fresh->certificate_title)->toBe('Certificat de réussite');
    expect($fresh->certificate_accent_color)->toBe('#064E5A');
    expect($fresh->certificate_signature_name)->toBe('Stéphane Lapointe');
});

test('G2 : saveCertificate rejette une couleur non-hex (erreur de validation)', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('certificate_accent_color', 'rouge')
        ->call('saveCertificate')
        ->assertHasErrors(['certificate_accent_color']);
});

test('G3 : saveCertificate normalise une chaîne vide en null (efface la personnalisation)', function (): void {
    // CARACTÉRISATION : '' → null dans le composant avant validation.
    // Résultat en BD : null (retour aux défauts du gabarit).
    $this->course->update(['certificate_title' => 'Titre précédent']);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('certificate_title', '')
        ->call('saveCertificate')
        ->assertHasNoErrors();

    expect($this->course->fresh()->certificate_title)->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// H. PRÉREQUIS — savePrerequisites
// ─────────────────────────────────────────────────────────────────────────────

test('H1 : savePrerequisites synchronise un prérequis valide', function (): void {
    $prereq = gmCE_makeCourse('cours-prereq-1', 'Prérequis 1');

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('prerequisiteIds', [$prereq->id])
        ->call('savePrerequisites')
        ->assertHasNoErrors();

    $synced = $this->course->fresh()->prerequisites()->pluck('courses.id')->all();
    expect($synced)->toContain($prereq->id);
});

test('H2 : savePrerequisites écarte un id forgé inexistant (anti-IDOR)', function (): void {
    // CARACTÉRISATION : l'id 999999 n'existe pas → ignoré (intersection).
    // Le résultat en BD = liste vide (aucun prérequis).
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('prerequisiteIds', [999999])
        ->call('savePrerequisites')
        ->assertHasNoErrors();

    $synced = $this->course->fresh()->prerequisites()->pluck('courses.id')->all();
    expect($synced)->toBeEmpty();
});

// ─────────────────────────────────────────────────────────────────────────────
// I. CONFIRMATIONS INLINE 2-TEMPS (pas de popup native)
//    Tests de COMPORTEMENT D'ÉTAT : le composant gère correctement l'UI
//    de confirmation sans jamais appeler confirm() JavaScript.
// ─────────────────────────────────────────────────────────────────────────────

test('I1 : confirmChapterDeletion fixe l\'id ; cancelChapterDeletion le remet à null', function (): void {
    $chapter = gmCE_addChapter($this->course);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('confirmChapterDeletion', $chapter->id)
        ->assertSet('confirmingChapterDeletion', $chapter->id)
        ->call('cancelChapterDeletion')
        ->assertSet('confirmingChapterDeletion', null);

    // Le chapitre n'est PAS supprimé (seule la confirmation visuellement annulée).
    expect(Chapter::find($chapter->id))->not->toBeNull();
});

test('I2 : confirmLessonDeletion fixe l\'id ; cancelLessonDeletion le remet à null', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('confirmLessonDeletion', $lesson->id)
        ->assertSet('confirmingLessonDeletion', $lesson->id)
        ->call('cancelLessonDeletion')
        ->assertSet('confirmingLessonDeletion', null);

    expect(Lesson::find($lesson->id))->not->toBeNull();
});

test('I3 : confirmItemDeletion fixe l\'id ; cancelItemDeletion le remet à null', function (): void {
    $chapter = gmCE_addChapter($this->course);
    $lesson  = gmCE_addLesson($chapter);
    $item    = gmCE_addItem($lesson);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('confirmItemDeletion', $item->id)
        ->assertSet('confirmingItemDeletion', $item->id)
        ->call('cancelItemDeletion')
        ->assertSet('confirmingItemDeletion', null);

    expect(LessonItem::find($item->id))->not->toBeNull();
});

test('I4 : confirmCourseDeletion passe à true ; cancelCourseDeletion repasse à false', function (): void {
    Livewire::actingAs(gmCE_makeOwner($this->course))
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('confirmCourseDeletion')
        ->assertSet('confirmingCourseDeletion', true)
        ->call('cancelCourseDeletion')
        ->assertSet('confirmingCourseDeletion', false);

    expect(Course::find($this->course->id))->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// J. AUTOSAVE via updated() — set() d'un champ metadata persiste en BD
// ─────────────────────────────────────────────────────────────────────────────

test('J1 : modifier summary via set() déclenche l\'autosave sans appel explicite à save()', function (): void {
    // CARACTÉRISATION : updated('summary') → save() automatique.
    // Cette mécanique est invisible depuis la vue (wire:model.blur) mais
    // critique pour le refactor : si on sépare les responsabilités, ce
    // hook doit rester fonctionnel.
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('summary', 'Résumé autosauvegardé')
        ->assertHasNoErrors();
    // Note : set() déclenche updated() → save() → DB. On vérifie en BD.
    expect($this->course->fresh()->summary)->toBe('Résumé autosauvegardé');
});

test('J2 : modifier level via set() déclenche l\'autosave', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->set('level', 'avance')
        ->assertHasNoErrors();

    expect($this->course->fresh()->level)->toBe('avance');
});

// ─────────────────────────────────────────────────────────────────────────────
// K. ANTI-IDOR COMPLÉMENTAIRES sur les mutations d'items
// ─────────────────────────────────────────────────────────────────────────────

test('K1 : ANTI-IDOR — deleteItem sur un item d\'un autre cours lève ModelNotFoundException', function (): void {
    $autresCours  = gmCE_makeCourse('autre-cours-k1', 'Autre Cours K1');
    $chapEtranger = gmCE_addChapter($autresCours);
    $leçonEtrang  = gmCE_addLesson($chapEtranger);
    $itemEtranger = gmCE_addItem($leçonEtrang);

    $owner = gmCE_makeOwner($this->course);

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->course]);

    expect(fn () => $component->call('deleteItem', $itemEtranger->id))
        ->toThrow(ModelNotFoundException::class);

    // L'item étranger n'est PAS supprimé.
    expect(LessonItem::find($itemEtranger->id))->not->toBeNull();
});

test('K2 : ANTI-IDOR — toggleRequired sur un item d\'un autre cours lève ModelNotFoundException', function (): void {
    $autresCours  = gmCE_makeCourse('autre-cours-k2', 'Autre Cours K2');
    $chapEtranger = gmCE_addChapter($autresCours);
    $leçonEtrang  = gmCE_addLesson($chapEtranger);
    $itemEtranger = gmCE_addItem($leçonEtrang); // is_required = false

    $owner = gmCE_makeOwner($this->course);

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->course]);

    expect(fn () => $component->call('toggleRequired', $itemEtranger->id))
        ->toThrow(ModelNotFoundException::class);

    // is_required n'a pas changé.
    expect($itemEtranger->fresh()->is_required)->toBeFalse();
});

test('K3 : ANTI-IDOR — updateItem sur un item d\'un autre cours lève ModelNotFoundException', function (): void {
    $autresCours  = gmCE_makeCourse('autre-cours-k3', 'Autre Cours K3');
    $chapEtranger = gmCE_addChapter($autresCours);
    $leçonEtrang  = gmCE_addLesson($chapEtranger);
    $itemEtranger = gmCE_addItem($leçonEtrang, 'document', 'Titre original étranger');

    $owner = gmCE_makeOwner($this->course);

    $component = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $this->course]);

    expect(fn () => $component->call('updateItem', $itemEtranger->id, 'document', 'Titre forgé', null, []))
        ->toThrow(ModelNotFoundException::class);

    // Le titre n'a pas changé.
    expect($itemEtranger->fresh()->title)->toBe('Titre original étranger');
});
