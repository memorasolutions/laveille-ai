<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Admin CRUD Academy
 * Couvre : CRUD cours, gating 403, validation, structure chapitres/leçons, instructeurs.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Sauter si le module n'est pas activé (ex. CI sans Academy)
    if (! \Nwidart\Modules\Facades\Module::find('Academy')?->isEnabled()) {
        test()->markTestSkipped('Module Academy désactivé — tests skipped.');
    }

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Admin avec permission academy.manage
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    // super_admin reçoit academy.manage via AcademyPermissionsSeeder (run dans RefreshDatabase + seeder)
    // Si seeder non lancé automatiquement, donner la permission manuellement :
    if (! $this->admin->can('academy.manage')) {
        $this->admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    // Utilisateur sans permission
    $this->user = User::factory()->create();
});

// ── 1. Index accessible à l'admin ─────────────────────────────────────────────

test('admin avec academy.manage voit la liste des cours', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.academy.courses.index'))
        ->assertOk()
        ->assertSee('Académie');
});

// ── 2. Gating 403 pour non-admin ──────────────────────────────────────────────

test('utilisateur sans permission reçoit 403 sur la liste', function (): void {
    $this->actingAs($this->user)
        ->get(route('admin.academy.courses.index'))
        ->assertForbidden();
});

test('utilisateur sans permission reçoit 403 sur la création', function (): void {
    $this->actingAs($this->user)
        ->post(route('admin.academy.courses.store'), ['title' => 'Test'])
        ->assertForbidden();
});

// ── 3. Création d'un cours valide ──────────────────────────────────────────────

test('admin peut créer un cours gratuit', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.academy.courses.store'), [
            'title'       => 'Introduction à l\'IA',
            'language'    => 'fr-CA',
            'level'       => 'intro',
            'visibility'  => 'public',
            'access_type' => 'free',
            'currency'    => 'CAD',
        ])
        ->assertRedirect(route('admin.academy.courses.index'))
        ->assertSessionHas('success');

    expect(Course::where('title', 'Introduction à l\'IA')->exists())->toBeTrue();
});

// ── 4. Validation : cours payant sans prix → erreur ──────────────────────────

test('la création d\'un cours payant sans prix échoue', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.academy.courses.store'), [
            'title'       => 'Cours payant',
            'language'    => 'fr-CA',
            'level'       => 'inter',
            'visibility'  => 'public',
            'access_type' => 'paid_one_time',
            'price_cents' => 0,
            'currency'    => 'CAD',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('price_cents');
});

// ── 5. Validation : titre manquant → erreur ────────────────────────────────────

test('la création sans titre échoue avec erreur de validation', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.academy.courses.store'), [
            'title'       => '',
            'language'    => 'fr-CA',
            'level'       => 'intro',
            'visibility'  => 'public',
            'access_type' => 'free',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('title');
});

// ── 6. Mise à jour (edit + update) ────────────────────────────────────────────

test('admin peut modifier un cours', function (): void {
    $course = Course::create([
        'slug'        => 'test-cours',
        'title'       => 'Cours Test',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'draft',
        'currency'    => 'CAD',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.academy.courses.update', $course), [
            'title'       => 'Cours Test Modifié',
            'language'    => 'fr-CA',
            'level'       => 'inter',
            'visibility'  => 'public',
            'access_type' => 'free',
            'status'      => 'draft',
            'currency'    => 'CAD',
        ])
        ->assertRedirect(route('admin.academy.courses.index'))
        ->assertSessionHas('success');

    expect($course->fresh()->title)->toBe('Cours Test Modifié');
});

// ── 7. Publier / dépublier ────────────────────────────────────────────────────

test('admin peut publier un cours en brouillon', function (): void {
    $course = Course::create([
        'slug'        => 'cours-draft',
        'title'       => 'Cours Draft',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'draft',
        'currency'    => 'CAD',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.academy.courses.toggle-status', $course))
        ->assertRedirect(route('admin.academy.courses.index'))
        ->assertSessionHas('success');

    expect($course->fresh()->status)->toBe('published');
});

// ── 8. Ajouter un chapitre ────────────────────────────────────────────────────

test('admin peut ajouter un chapitre à un cours', function (): void {
    $course = Course::create([
        'slug'        => 'cours-chapitres',
        'title'       => 'Cours Chapitres',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'draft',
        'currency'    => 'CAD',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.academy.chapters.store', $course), [
            'title' => 'Chapitre 1 : Les bases',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Chapter::where('course_id', $course->id)->where('title', 'Chapitre 1 : Les bases')->exists())->toBeTrue();
});

// ── 9. Ajouter une leçon ──────────────────────────────────────────────────────

test('admin peut ajouter une leçon à un chapitre', function (): void {
    $course  = Course::create([
        'slug' => 'cours-lecons', 'title' => 'Cours Leçons',
        'language' => 'fr-CA', 'level' => 'intro',
        'visibility' => 'public', 'access_type' => 'free',
        'status' => 'draft', 'currency' => 'CAD',
    ]);
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch1', 'position' => 1]);

    $this->actingAs($this->admin)
        ->post(route('admin.academy.lessons.store', $chapter), [
            'title' => 'Leçon 1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Lesson::where('chapter_id', $chapter->id)->where('title', 'Leçon 1')->exists())->toBeTrue();
});

// ── 10. Ajouter un LessonItem (vidéo) ────────────────────────────────────────

test('admin peut ajouter un élément vidéo à une leçon', function (): void {
    $course  = Course::create([
        'slug' => 'cours-items', 'title' => 'Cours Items',
        'language' => 'fr-CA', 'level' => 'intro',
        'visibility' => 'public', 'access_type' => 'free',
        'status' => 'draft', 'currency' => 'CAD',
    ]);
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch1', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'L1', 'slug' => 'l1', 'position' => 1]);

    $this->actingAs($this->admin)
        ->post(route('admin.academy.lesson-items.store', $lesson), [
            'type'       => 'video',
            'title'      => 'Intro vidéo',
            'player_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $item = LessonItem::where('lesson_id', $lesson->id)->first();
    expect($item)->not->toBeNull();
    expect($item->type)->toBe('video');
    expect($item->payload['player_url'])->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ');
});

// ── 11. Assigner un instructeur ───────────────────────────────────────────────

test('admin peut assigner un instructeur à un cours', function (): void {
    $course     = Course::create([
        'slug' => 'cours-roles', 'title' => 'Cours Rôles',
        'language' => 'fr-CA', 'level' => 'intro',
        'visibility' => 'public', 'access_type' => 'free',
        'status' => 'draft', 'currency' => 'CAD',
    ]);
    $instructor = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.academy.course-roles.store', $course), [
            'user_id' => $instructor->id,
            'role'    => 'instructor',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(CourseRole::where('course_id', $course->id)->where('user_id', $instructor->id)->exists())->toBeTrue();
});

// ── 12. Non-régression : routes publiques Academy non cassées ─────────────────

test('la route publique academy.index répond sans erreur 500', function (): void {
    // Mode « en construction » par défaut → un guest reçoit la page 503 (gate volontaire),
    // jamais une 500 (erreur applicative). On vérifie l'absence d'erreur serveur réelle.
    $response = $this->get(route('academy.index'));
    expect($response->status())->not->toBe(500);
    expect(in_array($response->status(), [200, 503], true))->toBeTrue();
});
