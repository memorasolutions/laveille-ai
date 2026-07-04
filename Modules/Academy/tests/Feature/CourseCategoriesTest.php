<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Catégories de cours (Vague 4, parité Moodle).
 *
 * Couvre :
 *  1. Drapeau OFF : route/composant 404, filtre absent de la liste publique.
 *  2. CRUD via CourseCategoryManager Livewire : réservé academy.manage.
 *  3. IDOR/permission : un formateur (sans academy.manage) ne peut pas gérer
 *     la taxonomie (403), mais PEUT classer SON cours dans une catégorie
 *     existante via CourseEditor.
 *  4. Filtre par catégorie sur la liste publique des cours.
 *  5. Suppression d'une catégorie : les cours redeviennent sans catégorie
 *     (FK nullOnDelete), jamais supprimés.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseCategoryManager;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseCategory;
use Modules\Academy\Models\CourseRole;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (noms distincts pour éviter les collisions avec les autres fichiers)
// ─────────────────────────────────────────────────────────────────────────────

function makeCatCourse(string $suffix = ''): Course
{
    return Course::create([
        'slug'        => 'cat-cours-' . $suffix . '-' . uniqid(),
        'title'       => 'Cours catégorie ' . $suffix,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function makeCatAdmin(): User
{
    $u = User::factory()->create();
    $u->assignRole('admin');
    return $u;
}

function makeCatInstructor(): User
{
    $u = User::factory()->create();
    $u->assignRole('instructor');
    return $u;
}

function makeCatOwner(Course $course): User
{
    $u = makeCatInstructor();
    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'owner']);
    return $u;
}

function makeCategory(string $name = 'Développement web'): CourseCategory
{
    return CourseCategory::create([
        'name'     => $name,
        'slug'     => \Illuminate\Support\Str::slug($name) . '-' . uniqid(),
        'position' => 0,
    ]);
}

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// 1. Drapeau OFF (défaut) : 404 + rien d'affiché
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau OFF (defaut) : la gestion des categories est 404', function (): void {
    config()->set('academy.course_categories_enabled', false);

    $admin = makeCatAdmin();

    Livewire::actingAs($admin)
        ->test(CourseCategoryManager::class)
        ->assertStatus(404);
});

test('drapeau OFF : aucun filtre categorie sur la liste publique des cours', function (): void {
    config()->set('academy.course_categories_enabled', false);
    makeCatCourse('off');

    $response = $this->get(route('academy.index'));

    $response->assertOk();
    $response->assertDontSee('academy-category-filter', false);
});

test('drapeau ON : la page admin/categories se rend correctement pour un admin', function (): void {
    config()->set('academy.course_categories_enabled', true);
    $admin    = makeCatAdmin();
    $category = makeCategory('Rendu visuel');

    $response = $this->actingAs($admin)->get(route('academy.admin.categories'));

    $response->assertOk();
    $response->assertSee('Catégories de cours', false);
    $response->assertSee('Rendu visuel', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. CRUD via CourseCategoryManager - reserve academy.manage
// ─────────────────────────────────────────────────────────────────────────────

test('admin (academy.manage) peut creer une categorie', function (): void {
    config()->set('academy.course_categories_enabled', true);
    $admin = makeCatAdmin();

    Livewire::actingAs($admin)
        ->test(CourseCategoryManager::class)
        ->call('create')
        ->set('name', 'Marketing numérique')
        ->set('color', '#064E5A')
        ->set('icon', '📈')
        ->call('save')
        ->assertHasNoErrors();

    expect(CourseCategory::where('name', 'Marketing numérique')->exists())->toBeTrue();
});

test('admin peut renommer une categorie existante', function (): void {
    config()->set('academy.course_categories_enabled', true);
    $admin    = makeCatAdmin();
    $category = makeCategory('Ancien nom');

    Livewire::actingAs($admin)
        ->test(CourseCategoryManager::class)
        ->call('edit', $category->id)
        ->set('name', 'Nouveau nom')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->fresh()->name)->toBe('Nouveau nom');
});

test('validation : nom vide est rejete', function (): void {
    config()->set('academy.course_categories_enabled', true);
    $admin = makeCatAdmin();

    Livewire::actingAs($admin)
        ->test(CourseCategoryManager::class)
        ->call('create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('validation : couleur non hexadecimale est rejetee', function (): void {
    config()->set('academy.course_categories_enabled', true);
    $admin = makeCatAdmin();

    Livewire::actingAs($admin)
        ->test(CourseCategoryManager::class)
        ->call('create')
        ->set('name', 'Test couleur')
        ->set('color', 'pas-une-couleur')
        ->call('save')
        ->assertHasErrors(['color']);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. IDOR/permission : un formateur SANS academy.manage ne gere pas la taxonomie
// ─────────────────────────────────────────────────────────────────────────────

test('formateur SANS academy.manage ne peut pas ouvrir la gestion des categories (403)', function (): void {
    config()->set('academy.course_categories_enabled', true);
    $instructor = makeCatInstructor();

    Livewire::actingAs($instructor)
        ->test(CourseCategoryManager::class)
        ->assertForbidden();
});

test('formateur SANS academy.manage ne peut pas supprimer une categorie (403)', function (): void {
    config()->set('academy.course_categories_enabled', true);
    $instructor = makeCatInstructor();
    $category   = makeCategory('Protégée');

    Livewire::actingAs($instructor)
        ->test(CourseCategoryManager::class)
        ->assertForbidden();

    expect(CourseCategory::where('id', $category->id)->exists())->toBeTrue();
});

test('formateur PEUT classer SON cours dans une categorie existante via CourseEditor', function (): void {
    config()->set('academy.course_categories_enabled', true);
    $course   = makeCatCourse('editor');
    $owner    = makeCatOwner($course);
    $category = makeCategory('Langues');

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set('category_id', $category->id)
        ->assertHasNoErrors();

    expect($course->fresh()->category_id)->toBe($category->id);
});

test('CourseEditor : selectionner "aucune categorie" (chaine vide) remet category_id a null', function (): void {
    config()->set('academy.course_categories_enabled', true);
    $course   = makeCatCourse('editor-null');
    $owner    = makeCatOwner($course);
    $category = makeCategory('À retirer');
    $course->update(['category_id' => $category->id]);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set('category_id', '')
        ->assertHasNoErrors();

    expect($course->fresh()->category_id)->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Filtre par categorie sur la liste publique
// ─────────────────────────────────────────────────────────────────────────────

test('filtre par categorie sur la liste publique ne montre que les cours classes', function (): void {
    config()->set('academy.course_categories_enabled', true);

    $catA = makeCategory('Catégorie A');
    $catB = makeCategory('Catégorie B');

    $courseA = makeCatCourse('filtre-a');
    $courseA->update(['category_id' => $catA->id]);

    $courseB = makeCatCourse('filtre-b');
    $courseB->update(['category_id' => $catB->id]);

    $response = $this->get(route('academy.index', ['category' => $catA->id]));

    $response->assertOk();
    $response->assertSee($courseA->title, false);
    $response->assertDontSee($courseB->title, false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Suppression : les cours redeviennent sans categorie (nullOnDelete)
// ─────────────────────────────────────────────────────────────────────────────

test('suppression d\'une categorie ne supprime pas les cours qui y etaient classes', function (): void {
    config()->set('academy.course_categories_enabled', true);
    $admin    = makeCatAdmin();
    $category = makeCategory('À supprimer');
    $course   = makeCatCourse('surv');
    $course->update(['category_id' => $category->id]);

    Livewire::actingAs($admin)
        ->test(CourseCategoryManager::class)
        ->call('confirmDelete', $category->id)
        ->call('remove', $category->id)
        ->assertHasNoErrors();

    expect(CourseCategory::where('id', $category->id)->exists())->toBeFalse();
    expect(Course::where('id', $course->id)->exists())->toBeTrue();
    expect($course->fresh()->category_id)->toBeNull();
});
