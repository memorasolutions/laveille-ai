<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Création de cours front-end (CourseCreate, PHASE 5 / FE-5).
 *
 * Prouve que la sécurité est SERVEUR (OWASP A01) et que le flux complet tient :
 *  - admin (academy.manage)   → peut créer un cours ;
 *  - formateur (instructor)   → peut créer ; il en devient OWNER (course_roles) ;
 *    il peut ensuite ÉDITER son cours (manageStructure) et il apparaît dans SON
 *    dashboard « Mes cours », PAS dans celui d'un autre formateur (anti-fuite) ;
 *  - étudiant / user sans rôle → création interdite (403) ;
 *  - validation : titre requis ; slug auto-unique généré (jamais de collision).
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseCreate;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Livewire\Dashboard;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

/** Helper : admin academy.manage. */
function makeCreateAdmin(): User
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

/** Helper : formateur (rôle instructor). */
function makeCreateInstructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. FORMATEUR - crée un cours et en devient OWNER (flux complet)
// ─────────────────────────────────────────────────────────────────────────────

test('formateur peut créer un cours et en devient owner', function (): void {
    $instructor = makeCreateInstructor();

    Livewire::actingAs($instructor)
        ->test(CourseCreate::class)
        ->set('title', 'Mon premier cours IA')
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirect(route('academy.courses.manage', 'mon-premier-cours-ia'));

    $course = Course::where('slug', 'mon-premier-cours-ia')->first();
    expect($course)->not->toBeNull();
    expect($course->status)->toBe('draft');
    expect($course->created_by)->toBe($instructor->id);

    // Le créateur figure dans course_roles comme OWNER de SON cours.
    expect(CourseRole::where('course_id', $course->id)
        ->where('user_id', $instructor->id)
        ->where('role', 'owner')
        ->exists())->toBeTrue();
});

test('FLUX : le formateur créateur peut ensuite éditer SON cours (manageStructure)', function (): void {
    $instructor = makeCreateInstructor();

    Livewire::actingAs($instructor)
        ->test(CourseCreate::class)
        ->set('title', 'Cours à éditer')
        ->call('create')
        ->assertHasNoErrors();

    $course = Course::where('slug', 'cours-a-editer')->firstOrFail();

    // L'owner ouvre l'éditeur (authorize('update')) et ajoute un chapitre
    // (authorize('manageStructure')) sans aucune erreur ni 403.
    Livewire::actingAs($instructor)
        ->test(CourseEditor::class, ['course' => $course])
        ->set('newChapterTitle', 'Introduction')
        ->call('addChapter')
        ->assertHasNoErrors();

    expect(Chapter::where('course_id', $course->id)->count())->toBe(1);
});

test('FLUX : le cours créé apparaît dans le dashboard de son créateur, PAS dans celui d\'un autre formateur', function (): void {
    $creator = makeCreateInstructor();
    $other   = makeCreateInstructor();

    Livewire::actingAs($creator)
        ->test(CourseCreate::class)
        ->set('title', 'Cours du créateur')
        ->call('create')
        ->assertHasNoErrors();

    // Le créateur voit son cours dans « Mes cours ».
    Livewire::actingAs($creator)
        ->test(Dashboard::class)
        ->assertSee('Cours du créateur');

    // L'autre formateur ne le voit PAS (course_roles scopé à son user_id).
    Livewire::actingAs($other)
        ->test(Dashboard::class)
        ->assertDontSee('Cours du créateur');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. ADMIN - peut créer
// ─────────────────────────────────────────────────────────────────────────────

test('admin peut créer un cours', function (): void {
    $admin = makeCreateAdmin();

    Livewire::actingAs($admin)
        ->test(CourseCreate::class)
        ->set('title', 'Cours admin')
        ->call('create')
        ->assertHasNoErrors();

    $course = Course::where('slug', 'cours-admin')->first();
    expect($course)->not->toBeNull();
    expect($course->status)->toBe('draft');
    expect($course->created_by)->toBe($admin->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. ÉTUDIANT / USER SANS RÔLE - création interdite (403)
// ─────────────────────────────────────────────────────────────────────────────

test('étudiant ne peut pas ouvrir le formulaire de création', function (): void {
    $student = User::factory()->create();
    $student->assignRole('student');

    Livewire::actingAs($student)
        ->test(CourseCreate::class)
        ->assertForbidden();
});

test('utilisateur sans aucun rôle ne peut pas créer (même en forçant create())', function (): void {
    $user = User::factory()->create();

    // Le montage lui-même est interdit (authorize au mount).
    Livewire::actingAs($user)
        ->test(CourseCreate::class)
        ->assertForbidden();

    // Aucun cours n'a pu être créé.
    expect(Course::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. VALIDATION - titre requis, slug auto-unique
// ─────────────────────────────────────────────────────────────────────────────

test('le titre est requis', function (): void {
    Livewire::actingAs(makeCreateInstructor())
        ->test(CourseCreate::class)
        ->set('title', '')
        ->call('create')
        ->assertHasErrors(['title' => 'required']);

    expect(Course::count())->toBe(0);
});

test('le slug est généré et rendu unique en cas de titre identique', function (): void {
    $instructor = makeCreateInstructor();

    Livewire::actingAs($instructor)
        ->test(CourseCreate::class)
        ->set('title', 'Atelier IA')
        ->call('create')
        ->assertHasNoErrors();

    Livewire::actingAs($instructor)
        ->test(CourseCreate::class)
        ->set('title', 'Atelier IA')
        ->call('create')
        ->assertHasNoErrors();

    expect(Course::where('slug', 'atelier-ia')->exists())->toBeTrue();
    expect(Course::where('slug', 'atelier-ia-2')->exists())->toBeTrue();
    expect(Course::count())->toBe(2);
});
