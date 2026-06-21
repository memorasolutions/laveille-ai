<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Duplication de cours + modèles (Phase C / C3).
 *
 * Prouve que :
 *  - duplication d'un cours (2 chapitres / N leçons / items) → cours NEUF en
 *    'draft', slug DIFFÉRENT, MÊME structure (compte chapitres/leçons/items + payload),
 *    owner = duplicateur, SOURCE inchangé, enrollments / course_roles NON copiés ;
 *  - SÉCURITÉ (OWASP A01) : un user sans droit sur le source NE PEUT PAS dupliquer (403) ;
 *  - le slug du cours dupliqué est UNIQUE (pas de collision si on duplique 2×) ;
 *  - is_template : toggle dans l'éditeur + « utiliser ce modèle » duplique.
 *
 * Fichier AUTONOME : helpers locaux préfixés Dup (aucune dépendance ni collision
 * avec les autres fichiers de tests Academy). Garde-fou : module désactivé → SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Livewire\Dashboard;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\CourseDuplicator;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

/** Helper local : cours gratuit en brouillon (préfixe Dup, sans collision inter-fichiers). */
function makeDupCourse(string $slug, string $title): Course
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

/** Helper local : formateur avec un rôle de cours donné (owner par défaut). */
function makeDupRole(Course $course, string $role = 'owner'): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create([
        'course_id' => $course->id,
        'user_id'   => $user->id,
        'role'      => $role,
    ]);

    return $user;
}

/** Helper local : admin academy.manage. */
function makeDupAdmin(): User
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

/**
 * Helper local : peuple un cours avec 2 chapitres, des leçons et des items variés
 * (vidéo + document + quiz, avec payloads). Retourne les compteurs attendus.
 *
 * @return array{chapters:int, lessons:int, items:int}
 */
function seedDupStructure(Course $course): array
{
    $c1 = Chapter::create(['course_id' => $course->id, 'title' => 'Chapitre 1', 'position' => 1, 'summary' => 'Intro']);
    $c2 = Chapter::create(['course_id' => $course->id, 'title' => 'Chapitre 2', 'position' => 2]);

    $l1 = Lesson::create(['chapter_id' => $c1->id, 'title' => 'Leçon 1', 'slug' => 'lecon-1', 'position' => 1, 'estimated_minutes' => 10]);
    $l2 = Lesson::create(['chapter_id' => $c1->id, 'title' => 'Leçon 2', 'slug' => 'lecon-2', 'position' => 2]);
    $l3 = Lesson::create(['chapter_id' => $c2->id, 'title' => 'Leçon 3', 'slug' => 'lecon-3', 'position' => 1]);

    LessonItem::create([
        'lesson_id' => $l1->id, 'type' => 'video', 'title' => 'Vidéo intro', 'position' => 1,
        'payload' => ['player_url' => 'https://share.screenpal.com/player/abc', 'duration_seconds' => 600],
        'is_required' => true, 'estimated_minutes' => 10,
    ]);
    LessonItem::create([
        'lesson_id' => $l1->id, 'type' => 'document', 'title' => 'Notes', 'position' => 2,
        'payload' => ['rich_text' => '# Titre\n\nContenu markdown.'],
    ]);
    LessonItem::create([
        'lesson_id' => $l2->id, 'type' => 'quiz', 'title' => 'Quiz', 'position' => 1,
        'payload' => ['qt_bank_key' => 'qt.module1', 'passing_score' => 70, 'attempts_allowed' => 3],
    ]);
    LessonItem::create([
        'lesson_id' => $l3->id, 'type' => 'document', 'title' => 'Conclusion', 'position' => 1,
        'payload' => ['rich_text' => 'Fin.'], 'external_ref' => 'ref-xyz',
    ]);

    return ['chapters' => 2, 'lessons' => 3, 'items' => 4];
}

/** Compte récursif des items d'un cours (toutes leçons confondues). */
function dupItemCount(Course $course): int
{
    return LessonItem::whereHas('lesson.chapter', fn ($q) => $q->where('course_id', $course->id))->count();
}

/** Compte récursif des leçons d'un cours. */
function dupLessonCount(Course $course): int
{
    return Lesson::whereHas('chapter', fn ($q) => $q->where('course_id', $course->id))->count();
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. DUPLICATION PROFONDE - structure copiée, source intact, neuf en draft
// ─────────────────────────────────────────────────────────────────────────────

test('le service duplique en profondeur : structure identique, cours neuf en draft, owner = duplicateur', function (): void {
    $source   = makeDupCourse('dup-source', 'Cours original');
    $expected = seedDupStructure($source);
    $owner    = makeDupRole($source, 'owner');

    // Bruit à NE PAS copier : une inscription + un rôle supplémentaire sur le source.
    $student = User::factory()->create();
    Enrollment::create([
        'course_id' => $source->id, 'user_id' => $student->id, 'status' => 'active',
        'source' => 'admin', 'enrolled_at' => now(),
    ]);

    $duplicator = app(CourseDuplicator::class);
    $copy       = $duplicator->duplicate($source->fresh(), $owner);

    // Cours NEUF distinct, brouillon, slug différent.
    expect($copy->id)->not->toBe($source->id);
    expect($copy->status)->toBe('draft');
    expect($copy->slug)->not->toBe($source->slug);
    expect($copy->title)->toContain('(copie)');
    expect((int) $copy->created_by)->toBe($owner->id);

    // MÊME structure (compteurs identiques).
    expect(Chapter::where('course_id', $copy->id)->count())->toBe($expected['chapters']);
    expect(dupLessonCount($copy))->toBe($expected['lessons']);
    expect(dupItemCount($copy))->toBe($expected['items']);

    // Payload copié (vidéo).
    $videoCopy = LessonItem::whereHas('lesson.chapter', fn ($q) => $q->where('course_id', $copy->id))
        ->where('type', 'video')->first();
    expect($videoCopy->payload['player_url'])->toBe('https://share.screenpal.com/player/abc');
    expect($videoCopy->is_required)->toBeTrue();

    // Owner attribué sur la copie (un seul rôle owner = le duplicateur).
    expect(CourseRole::where('course_id', $copy->id)->where('role', 'owner')->where('user_id', $owner->id)->exists())->toBeTrue();
    expect(CourseRole::where('course_id', $copy->id)->count())->toBe(1);

    // Source INCHANGÉ : structure intacte, toujours ses rôles + son inscription.
    expect(Chapter::where('course_id', $source->id)->count())->toBe($expected['chapters']);
    expect(dupItemCount($source->fresh()))->toBe($expected['items']);
    expect(CourseRole::where('course_id', $source->id)->where('user_id', $owner->id)->exists())->toBeTrue();

    // Enrollments NON copiés sur la copie.
    expect(Enrollment::where('course_id', $copy->id)->count())->toBe(0);
    // L'inscription du source est intacte.
    expect(Enrollment::where('course_id', $source->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. SÉCURITÉ - un user sans droit ne peut pas dupliquer (403)
// ─────────────────────────────────────────────────────────────────────────────

test('un user sans rôle sur le source ne peut PAS dupliquer → forbidden, rien créé', function (): void {
    $source = makeDupCourse('dup-secured', 'Cours protégé');
    seedDupStructure($source);
    makeDupRole($source, 'owner'); // owner existant (un autre que l'attaquant)

    // Attaquant : un formateur (peut créer un cours) mais SANS aucun rôle sur ce source.
    $attacker = User::factory()->create();
    $attacker->assignRole('instructor');

    $before = Course::count();

    Livewire::actingAs($attacker)
        ->test(Dashboard::class)
        ->call('duplicate', $source->id)
        ->assertForbidden();

    expect(Course::count())->toBe($before); // aucune copie créée
});

test('un étudiant (ni rôle de cours ni create) ne peut pas dupliquer → forbidden', function (): void {
    $source = makeDupCourse('dup-student', 'Cours');
    seedDupStructure($source);

    $student = User::factory()->create();
    $student->assignRole('student');

    $before = Course::count();

    Livewire::actingAs($student)
        ->test(Dashboard::class)
        ->call('duplicate', $source->id)
        ->assertForbidden();

    expect(Course::count())->toBe($before);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. SLUG UNIQUE - dupliquer 2× ne crée pas de collision
// ─────────────────────────────────────────────────────────────────────────────

test('dupliquer deux fois le même cours produit deux slugs uniques', function (): void {
    $source = makeDupCourse('dup-twice', 'Cours à dupliquer');
    seedDupStructure($source);
    $owner = makeDupRole($source, 'owner');

    $duplicator = app(CourseDuplicator::class);
    $copy1      = $duplicator->duplicate($source->fresh(), $owner);
    $copy2      = $duplicator->duplicate($source->fresh(), $owner);

    expect($copy1->slug)->not->toBe($copy2->slug);
    expect($copy1->slug)->not->toBe($source->slug);
    expect($copy2->slug)->not->toBe($source->slug);

    // Unicité globale des 3 slugs.
    expect(Course::whereIn('slug', [$source->slug, $copy1->slug, $copy2->slug])->count())->toBe(3);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. MODÈLES - toggle is_template (éditeur) + « utiliser ce modèle » duplique
// ─────────────────────────────────────────────────────────────────────────────

test('un owner peut marquer son cours comme modèle via l\'éditeur', function (): void {
    $course = makeDupCourse('dup-tmpl', 'Cours modèle');
    $owner  = makeDupRole($course, 'owner');

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set('is_template', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($course->fresh()->is_template)->toBeTrue();
});

test('« utiliser ce modèle » duplique le cours (même service) et redirige vers l\'éditeur', function (): void {
    $template = makeDupCourse('dup-model', 'Modèle de cours');
    $template->update(['is_template' => true]);
    $expected = seedDupStructure($template);
    $owner    = makeDupRole($template, 'owner');

    $component = Livewire::actingAs($owner)->test(Dashboard::class);

    // Le modèle apparaît dans la section « Modèles ».
    expect($component->instance()->managedTemplates()->pluck('id')->all())->toContain($template->id);

    $before = Course::count();

    $component->call('duplicate', $template->id)
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Course::count())->toBe($before + 1);

    $copy = Course::where('id', '!=', $template->id)->latest('id')->first();
    expect($copy->status)->toBe('draft');
    expect($copy->is_template)->toBeFalse(); // la copie n'est PAS un modèle
    expect(dupItemCount($copy))->toBe($expected['items']);
});
