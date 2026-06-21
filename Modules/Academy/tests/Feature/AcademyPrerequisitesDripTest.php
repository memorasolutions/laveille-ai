<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Prérequis de cours + libération progressive (drip) — Phase C / C4.
 *
 * Prouve le GATING SERVEUR (jamais de confiance au client) :
 *  - PRÉREQUIS : sans le cours prérequis complété → inscription bloquée + contenu
 *    absent du DOM + panneau « Prérequis à compléter » ; prérequis complété → accès ;
 *    auto-référence impossible ; édition gâtée manageStructure ; anti-IDOR.
 *  - DRIP : leçon drip_days=7, inscrit il y a 3 jours → verrouillée (contenu hors DOM
 *    + « disponible le … ») ; inscrit il y a 10 jours → accessible ; gérant/preview →
 *    jamais verrouillé ; édition gâtée manageStructure ; anti-IDOR.
 *  - Migrations additives (colonnes/table présentes).
 *
 * Fichier AUTONOME : helpers locaux préfixés C4 (aucune collision inter-fichiers).
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Progress;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers locaux (préfixe C4)
// ─────────────────────────────────────────────────────────────────────────────

/** Cours publié+public gratuit (par défaut) avec un chapitre + une leçon contenant un item doc. */
function c4Course(string $slug, string $title, array $overrides = []): Course
{
    $course = Course::create(array_merge([
        'slug'        => $slug,
        'title'       => $title,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'published_at' => now(),
        'currency'    => 'CAD',
    ], $overrides));

    return $course;
}

/** Ajoute un chapitre + une leçon (drip optionnel) + un item document à un cours, retourne la leçon. */
function c4Lesson(Course $course, ?int $dripDays = null, string $secret = 'CONTENU-SECRET-DOC'): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre 1',
        'position'  => 1,
    ]);

    $lesson = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon 1',
        'slug'       => 'lecon-1-'.$course->id,
        'position'   => 1,
        'drip_days'  => $dripDays,
    ]);

    LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => 'document',
        'title'     => 'Document',
        'position'  => 1,
        'payload'   => ['rich_text' => $secret],
        'is_required' => true,
    ]);

    return $lesson;
}

/** Admin academy.manage. */
function c4Admin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    if (! $admin->can('academy.manage')) {
        $admin->givePermissionTo(
            Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    return $admin;
}

/** Formateur owner d'un cours donné. */
function c4Owner(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $user->id, 'role' => 'owner']);

    return $user;
}

/** Étudiant (sans rôle de cours). */
function c4Student(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

/** Inscrit un étudiant à un cours avec une date d'inscription donnée. */
function c4Enroll(User $user, Course $course, ?\Illuminate\Support\Carbon $enrolledAt = null): Enrollment
{
    return Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'free',
        'enrolled_at' => $enrolledAt ?? now(),
    ]);
}

/** Marque un cours complété (100 %) pour un user, via une ligne de progression. */
function c4CompleteCourse(User $user, Course $course): void
{
    Progress::updateOrCreate(
        ['user_id' => $user->id, 'course_id' => $course->id],
        ['percent' => 100, 'required_total' => 1, 'required_completed' => 1, 'last_activity_at' => now()]
    );
}

// ═════════════════════════════════════════════════════════════════════════════
// A. MIGRATIONS ADDITIVES
// ═════════════════════════════════════════════════════════════════════════════

test('migrations additives : table prérequis + colonne drip_days présentes', function (): void {
    expect(Schema::hasTable('academy_course_prerequisites'))->toBeTrue();
    expect(Schema::hasColumn('lessons', 'drip_days'))->toBeTrue();
});

// ═════════════════════════════════════════════════════════════════════════════
// B. PRÉREQUIS
// ═════════════════════════════════════════════════════════════════════════════

test('prérequis NON complété : inscription bloquée + contenu absent + panneau prérequis', function (): void {
    $prereq = c4Course('c4-prereq', 'Cours prérequis');
    $course = c4Course('c4-cible', 'Cours cible');
    $course->prerequisites()->attach($prereq->id);

    $lesson  = c4Lesson($course);
    $student = c4Student();
    c4Enroll($student, $course); // inscrit, mais prérequis non complété

    // Inscription bloquée côté serveur.
    $this->actingAs($student)
        ->post(route('academy.courses.enroll', $course))
        ->assertSessionHas('error');

    // Fiche du cours : panneau prérequis + lien vers le cours prérequis.
    $this->actingAs($student)
        ->get(route('academy.courses.show', $course->slug))
        ->assertOk()
        ->assertSee('Prérequis à compléter', false)
        ->assertSee($prereq->title, false);

    // Lecteur de leçon : contenu verrouillé JAMAIS injecté dans le DOM.
    $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course->slug, $lesson]))
        ->assertOk()
        ->assertDontSee('CONTENU-SECRET-DOC')
        ->assertSee('Prérequis à compléter', false);
});

test('prérequis complété : accès au contenu OK', function (): void {
    $prereq = c4Course('c4-prereq2', 'Cours prérequis');
    $course = c4Course('c4-cible2', 'Cours cible');
    $course->prerequisites()->attach($prereq->id);

    $lesson  = c4Lesson($course);
    $student = c4Student();
    c4CompleteCourse($student, $prereq); // prérequis satisfait
    c4Enroll($student, $course);

    // Le contenu est désormais rendu (item document).
    $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course->slug, $lesson]))
        ->assertOk()
        ->assertSee('CONTENU-SECRET-DOC')
        ->assertDontSee('Prérequis à compléter', false);
});

test('prérequis : helper prerequisitesUnmetFor reflète l\'état de complétion', function (): void {
    $prereq = c4Course('c4-prereq3', 'Prérequis');
    $course = c4Course('c4-cible3', 'Cible');
    $course->prerequisites()->attach($prereq->id);
    $student = c4Student();

    expect($course->prerequisitesUnmetFor($student)->pluck('id')->all())->toBe([$prereq->id]);

    c4CompleteCourse($student, $prereq);
    expect($course->fresh()->prerequisitesUnmetFor($student)->isEmpty())->toBeTrue();
});

test('auto-référence interdite : un cours ne peut pas être son propre prérequis', function (): void {
    $course = c4Course('c4-self', 'Cours');
    $owner  = c4Owner($course);

    // L'id du cours lui-même n'est PAS dans availableCourses() → écarté par savePrerequisites().
    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set('prerequisiteIds', [$course->id])
        ->call('savePrerequisites')
        ->assertHasNoErrors();

    expect($course->fresh()->prerequisites()->count())->toBe(0);
});

test('édition des prérequis gâtée : gérant peut, étudiant → 403 (mount)', function (): void {
    $prereq = c4Course('c4-p4', 'Prérequis');
    $course = c4Course('c4-c4', 'Cible');
    $admin  = c4Admin(); // admin voit TOUS les cours → peut poser n'importe quel prérequis

    Livewire::actingAs($admin)
        ->test(CourseEditor::class, ['course' => $course])
        ->set('prerequisiteIds', [$prereq->id])
        ->call('savePrerequisites')
        ->assertHasNoErrors();

    expect($course->fresh()->prerequisites()->pluck('courses.id')->all())->toBe([$prereq->id]);

    // Un étudiant ne peut même pas ouvrir l'éditeur.
    Livewire::actingAs(c4Student())
        ->test(CourseEditor::class, ['course' => $course])
        ->assertForbidden();
});

test('un formateur peut poser un prérequis parmi SES cours', function (): void {
    $course = c4Course('c4-fo-cible', 'Cible');
    $prereq = c4Course('c4-fo-prereq', 'Prérequis');

    // Le formateur est owner des DEUX cours → le prérequis est dans availableCourses().
    $owner = c4Owner($course);
    CourseRole::create(['course_id' => $prereq->id, 'user_id' => $owner->id, 'role' => 'owner']);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set('prerequisiteIds', [$prereq->id])
        ->call('savePrerequisites')
        ->assertHasNoErrors();

    expect($course->fresh()->prerequisites()->pluck('courses.id')->all())->toBe([$prereq->id]);
});

test('ANTI-IDOR prérequis : un id de cours non visible par le formateur est ignoré', function (): void {
    $courseA = c4Course('c4-idor-a', 'Cours A');
    $courseB = c4Course('c4-idor-b', 'Cours B (étranger)'); // owner différent
    $ownerA  = c4Owner($courseA);
    c4Owner($courseB); // B appartient à un AUTRE formateur

    // ownerA tente de mettre B en prérequis alors qu'il ne le voit pas → écarté serveur.
    Livewire::actingAs($ownerA)
        ->test(CourseEditor::class, ['course' => $courseA])
        ->set('prerequisiteIds', [$courseB->id])
        ->call('savePrerequisites')
        ->assertHasNoErrors();

    expect($courseA->fresh()->prerequisites()->count())->toBe(0);
});

// ═════════════════════════════════════════════════════════════════════════════
// C. DRIP (libération progressive)
// ═════════════════════════════════════════════════════════════════════════════

test('drip : leçon drip_days=7, inscrit il y a 3 jours → verrouillée (contenu absent + date)', function (): void {
    $course  = c4Course('c4-drip-locked', 'Cours drip');
    $lesson  = c4Lesson($course, dripDays: 7);
    $student = c4Student();
    c4Enroll($student, $course, now()->subDays(3));

    $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course->slug, $lesson]))
        ->assertOk()
        ->assertDontSee('CONTENU-SECRET-DOC')
        ->assertSee('Disponible le');
});

test('drip : leçon drip_days=7, inscrit il y a 10 jours → accessible', function (): void {
    $course  = c4Course('c4-drip-open', 'Cours drip');
    $lesson  = c4Lesson($course, dripDays: 7);
    $student = c4Student();
    c4Enroll($student, $course, now()->subDays(10));

    $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course->slug, $lesson]))
        ->assertOk()
        ->assertSee('CONTENU-SECRET-DOC');
});

test('drip : gérant en prévisualisation → jamais verrouillé (voit tout)', function (): void {
    $course = c4Course('c4-drip-preview', 'Cours drip', ['status' => 'draft', 'published_at' => null]);
    $lesson = c4Lesson($course, dripDays: 30);
    $owner  = c4Owner($course);

    // Aucune inscription du gérant : il prévisualise via ?preview=1, drip ignoré.
    $this->actingAs($owner)
        ->get(route('academy.lessons.show', [$course->slug, $lesson]).'?preview=1')
        ->assertOk()
        ->assertSee('CONTENU-SECRET-DOC')
        ->assertDontSee('Disponible le');
});

test('drip : helper isDripLockedFor — futur verrouillé, passé ouvert, sans drip jamais', function (): void {
    $course = c4Course('c4-drip-helper', 'Cours');
    $lesson = c4Lesson($course, dripDays: 7);

    expect($lesson->isDripLockedFor(now()->subDays(3)))->toBeTrue();   // 3 < 7 → verrouillé
    expect($lesson->isDripLockedFor(now()->subDays(10)))->toBeFalse(); // 10 >= 7 → ouvert

    $immediate = c4Lesson(c4Course('c4-drip-none', 'Sans drip'), dripDays: null);
    expect($immediate->isDripLockedFor(now()))->toBeFalse();           // pas de drip
});

test('drip : édition gâtée — owner règle drip_days, étudiant → 403', function (): void {
    $course = c4Course('c4-drip-edit', 'Cours');
    $lesson = c4Lesson($course, dripDays: null);
    $owner  = c4Owner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateLesson', $lesson->id, 'Leçon 1', null, null, 14)
        ->assertHasNoErrors();

    expect($lesson->fresh()->drip_days)->toBe(14);

    // 0 = normalisé en null (immédiat).
    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateLesson', $lesson->id, 'Leçon 1', null, null, 0)
        ->assertHasNoErrors();

    expect($lesson->fresh()->drip_days)->toBeNull();

    // Étudiant ne peut pas ouvrir l'éditeur.
    Livewire::actingAs(c4Student())
        ->test(CourseEditor::class, ['course' => $course])
        ->assertForbidden();
});

test('drip : validation max 365 jours rejetée', function (): void {
    $course = c4Course('c4-drip-val', 'Cours');
    $lesson = c4Lesson($course, dripDays: null);
    $owner  = c4Owner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateLesson', $lesson->id, 'Leçon 1', null, null, 999)
        ->assertHasErrors();

    expect($lesson->fresh()->drip_days)->toBeNull();
});

test('ANTI-IDOR drip : régler le drip d\'une leçon d\'un AUTRE cours échoue, rien écrit', function (): void {
    $courseA = c4Course('c4-drip-idor-a', 'Cours A');
    $courseB = c4Course('c4-drip-idor-b', 'Cours B');
    $lessonB = c4Lesson($courseB, dripDays: null);
    $ownerA  = c4Owner($courseA);

    // ownerA ouvre l'éditeur sur A mais cible une leçon de B (id forgé) → ModelNotFound.
    expect(fn () => Livewire::actingAs($ownerA)
        ->test(CourseEditor::class, ['course' => $courseA])
        ->call('updateLesson', $lessonB->id, 'Hack', null, null, 5))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($lessonB->fresh()->drip_days)->toBeNull();
});

test('drip : complétion bloquée tant que la leçon est verrouillée', function (): void {
    $course  = c4Course('c4-drip-complete', 'Cours');
    $lesson  = c4Lesson($course, dripDays: 7);
    $item    = $lesson->lessonItems()->first();
    $student = c4Student();
    c4Enroll($student, $course, now()->subDays(2)); // verrouillé (2 < 7)

    $this->actingAs($student)
        ->post(route('academy.lessons.complete', [$course->slug, $lesson, $item->id]))
        ->assertForbidden();

    expect(Completion::where('user_id', $student->id)->where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('RÉGRESSION : vider le champ drip (chaîne vide du DOM) ne plante pas et met drip_days à null', function (): void {
    // Le formulaire envoie $event.target.drip_days.value = chaîne ('' si vidé). updateLesson
    // doit normaliser '' → null SANS TypeError (strict_types sur un ?int) → pas de 500.
    $course = c4Course('c4-drip-clear', 'Cours drip clear');
    $lesson = c4Lesson($course, dripDays: 7);
    $owner  = c4Owner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateLesson', $lesson->id, $lesson->title, null, '', '') // estimated_minutes='' et drip_days=''
        ->assertHasNoErrors()
        ->assertOk();

    expect($lesson->fresh()->drip_days)->toBeNull();
});
