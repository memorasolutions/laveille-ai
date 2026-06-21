<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Annonces aux inscrits d'un cours (D3, pilotage).
 *
 * Prouve, côté SERVEUR (OWASP A01) :
 *  - un gérant publie une annonce → l'inscrit ACTIF la voit dans son espace ;
 *  - un BROUILLON (published_at null) n'est JAMAIS visible d'un étudiant ;
 *  - un étudiant NON inscrit ne voit aucune annonce ;
 *  - un non-gérant ne peut ni créer ni supprimer (403) ;
 *  - ANTI-IDOR : agir sur (ou voir) l'annonce d'un AUTRE cours est refusé ;
 *  - le corps markdown contenant <script> est NEUTRALISÉ (anti-XSS).
 *
 * Helpers PRÉFIXÉS « d3 » pour éviter toute redéclaration avec les autres suites.
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseRoster;
use Modules\Academy\Livewire\Dashboard;
use Modules\Academy\Models\Announcement;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->courseA = d3MakeCourse('annonce-a', 'Cours A');
    $this->courseB = d3MakeCourse('annonce-b', 'Cours B');
});

/** Helper : crée un cours gratuit publié minimal. */
function d3MakeCourse(string $slug, string $title): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => $title,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/** Helper : owner (formateur) d'un cours donné. */
function d3MakeOwner(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create([
        'course_id' => $course->id,
        'user_id'   => $user->id,
        'role'      => 'owner',
    ]);

    return $user;
}

/** Helper : étudiant inscrit ACTIF à un cours donné. */
function d3MakeEnrolledStudent(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('student');
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);

    return $user;
}

// ─────────────────────────────────────────────────────────────────────────────
// Gérant publie / brouillon
// ─────────────────────────────────────────────────────────────────────────────

it('publie une annonce qu\'un inscrit actif voit', function (): void {
    $owner   = d3MakeOwner($this->courseA);
    $student = d3MakeEnrolledStudent($this->courseA);

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('announcementTitle', 'Nouvelle leçon')
        ->set('announcementBody', 'Le module 2 est disponible.')
        ->call('saveAnnouncement', true);

    $announcement = Announcement::where('course_id', $this->courseA->id)->first();
    expect($announcement)->not->toBeNull();
    expect($announcement->published_at)->not->toBeNull();
    expect($announcement->author_id)->toBe($owner->id);

    // L'inscrit actif la voit dans son espace.
    Livewire::actingAs($student)
        ->test(Dashboard::class)
        ->assertSee('Nouvelle leçon')
        ->assertSee('Le module 2 est disponible.');
});

it('garde un brouillon invisible des étudiants', function (): void {
    $owner   = d3MakeOwner($this->courseA);
    $student = d3MakeEnrolledStudent($this->courseA);

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('announcementTitle', 'Brouillon secret')
        ->set('announcementBody', 'Pas encore prêt.')
        ->call('saveAnnouncement', false);

    $announcement = Announcement::where('course_id', $this->courseA->id)->first();
    expect($announcement->published_at)->toBeNull();

    Livewire::actingAs($student)
        ->test(Dashboard::class)
        ->assertDontSee('Brouillon secret')
        ->assertDontSee('Pas encore prêt.');
});

// ─────────────────────────────────────────────────────────────────────────────
// Sécurité : visibilité étudiant scopée aux inscriptions
// ─────────────────────────────────────────────────────────────────────────────

it('cache les annonces aux étudiants non inscrits', function (): void {
    $owner = d3MakeOwner($this->courseA);

    Announcement::create([
        'course_id'    => $this->courseA->id,
        'author_id'    => $owner->id,
        'title'        => 'Annonce du cours A',
        'body'         => 'Message public.',
        'published_at' => now(),
    ]);

    // Étudiant inscrit au cours B uniquement → ne voit pas l'annonce du cours A.
    $studentB = d3MakeEnrolledStudent($this->courseB);

    Livewire::actingAs($studentB)
        ->test(Dashboard::class)
        ->assertDontSee('Annonce du cours A')
        ->assertDontSee('Message public.');
});

it('interdit la création / suppression à un non-gérant (403)', function (): void {
    $student = d3MakeEnrolledStudent($this->courseA);

    // Un simple inscrit ne peut même pas monter le composant de gestion.
    Livewire::actingAs($student)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->assertForbidden();
});

it('empêche un gérant d\'agir sur l\'annonce d\'un autre cours (anti-IDOR)', function (): void {
    $ownerA = d3MakeOwner($this->courseA);

    // Une annonce appartenant au cours B.
    $foreign = Announcement::create([
        'course_id'    => $this->courseB->id,
        'author_id'    => null,
        'title'        => 'Annonce du cours B',
        'body'         => 'Étrangère.',
        'published_at' => now(),
    ]);

    // Le gérant du cours A monte SON roster, mais cible l'annonce du cours B :
    // re-résolue scopée course_id = A → ModelNotFound, rien supprimé.
    expect(function () use ($ownerA, $foreign): void {
        Livewire::actingAs($ownerA)
            ->test(CourseRoster::class, ['course' => $this->courseA])
            ->call('deleteAnnouncement', $foreign->id);
    })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Announcement::find($foreign->id))->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// Anti-XSS : le corps markdown contenant <script> est neutralisé
// ─────────────────────────────────────────────────────────────────────────────

it('neutralise le HTML brut du corps (anti-XSS)', function (): void {
    $owner = d3MakeOwner($this->courseA);

    $announcement = Announcement::create([
        'course_id'    => $this->courseA->id,
        'author_id'    => $owner->id,
        'title'        => 'Test XSS',
        'body'         => "Bonjour <script>alert('xss')</script> **gras**",
        'published_at' => now(),
    ]);

    $html = $announcement->renderedBody();

    // anti-XSS : la balise <script> est retirée (html_input=strip) → ne peut pas
    // s'exécuter. Le texte intérieur survit en simple texte inoffensif, mais
    // jamais comme balise script active.
    expect($html)->not->toContain('<script>');
    expect($html)->not->toContain('</script>');
    // La syntaxe markdown, elle, reste interprétée (rendu sûr).
    expect($html)->toContain('<strong>gras</strong>');
});

// ─────────────────────────────────────────────────────────────────────────────
// Migration additive (la table existe après migration)
// ─────────────────────────────────────────────────────────────────────────────

it('a créé la table additive academy_announcements', function (): void {
    expect(\Illuminate\Support\Facades\Schema::hasTable('academy_announcements'))->toBeTrue();
});
