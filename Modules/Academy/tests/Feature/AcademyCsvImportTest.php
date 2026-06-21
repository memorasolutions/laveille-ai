<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Import CSV d'inscriptions en masse (CourseRoster, PHASE C / C1).
 *
 * Prouve :
 *  - owner/admin importe un CSV : les comptes EXISTANTS non inscrits sont inscrits,
 *    les courriels SANS compte sont rapportés SANS qu'aucun compte ne soit créé
 *    (conformité LCAP/Loi 25), les courriels invalides sont ignorés ;
 *  - réimport idempotent : aucune nouvelle inscription, les déjà-inscrits comptés ;
 *  - SÉCURITÉ (OWASP A01) : un non-gestionnaire (étudiant) NE PEUT PAS importer
 *    (403), et un owner du cours A ne peut pas importer sur le cours B (anti-escalade).
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseRoster;
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

    $this->courseA = makeCsvCourse('csv-a', 'Cours A');
    $this->courseB = makeCsvCourse('csv-b', 'Cours B');
});

/** Helper : crée un cours gratuit en brouillon minimal. */
function makeCsvCourse(string $slug, string $title): Course
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

/** Helper : admin academy.manage (super_admin). */
function makeCsvAdmin(): User
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

/** Helper : formateur avec un rôle de cours donné (owner par défaut). */
function makeCsvRole(Course $course, string $role = 'owner'): User
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

/** Helper : un étudiant (compte existant, sans rôle de cours). */
function makeCsvStudent(?string $email = null): User
{
    $student = User::factory()->create($email ? ['email' => $email] : []);
    $student->assignRole('student');

    return $student;
}

/** Helper : un fichier CSV téléversé à partir d'un contenu texte. */
function makeCsvUpload(string $content, string $name = 'inscriptions.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. IMPORT NOMINAL - 2 existants inscrits, 1 inconnu rapporté, 0 compte créé
// ─────────────────────────────────────────────────────────────────────────────

test('owner importe un CSV : 2 existants inscrits, 1 inconnu rapporté, AUCUN compte créé', function (): void {
    $owner = makeCsvRole($this->courseA, 'owner');

    $s1 = makeCsvStudent('alice@exemple.ca');
    $s2 = makeCsvStudent('bob@exemple.ca');

    $usersBefore = User::count();

    $csv = makeCsvUpload("email,role\nalice@exemple.ca,student\nbob@exemple.ca,student\ninconnu@exemple.ca,student\n");

    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('csvFile', $csv)
        ->call('importCsv')
        ->assertHasNoErrors();

    // Inscriptions réelles des deux comptes existants.
    expect(Enrollment::where('course_id', $this->courseA->id)->where('user_id', $s1->id)->where('status', 'active')->exists())->toBeTrue();
    expect(Enrollment::where('course_id', $this->courseA->id)->where('user_id', $s2->id)->where('status', 'active')->exists())->toBeTrue();
    expect(Enrollment::where('course_id', $this->courseA->id)->count())->toBe(2);

    // AUCUN compte créé pour le courriel inconnu (conformité LCAP/Loi 25).
    expect(User::count())->toBe($usersBefore);
    expect(User::where('email', 'inconnu@exemple.ca')->exists())->toBeFalse();

    // Le rapport reflète exactement l'import.
    $report = $component->get('importReport');
    expect($report['enrolled'])->toBe(2);
    expect($report['already'])->toBe(0);
    expect($report['invalid'])->toBe(0);
    expect($report['unknown_emails'])->toBe(['inconnu@exemple.ca']);
});

test('import sans en-tête fonctionne (1re ligne = donnée)', function (): void {
    $owner = makeCsvRole($this->courseA, 'owner');
    $s1    = makeCsvStudent('sansentete@exemple.ca');

    $csv = makeCsvUpload("sansentete@exemple.ca\n");

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('csvFile', $csv)
        ->call('importCsv')
        ->assertHasNoErrors();

    expect(Enrollment::where('course_id', $this->courseA->id)->where('user_id', $s1->id)->where('status', 'active')->exists())->toBeTrue();
});

test('import robuste : BOM, séparateur point-virgule, espaces et casse mixte', function (): void {
    $owner = makeCsvRole($this->courseA, 'owner');
    $s1    = makeCsvStudent('charlie@exemple.ca');

    // BOM UTF-8 + en-tête « courriel » + séparateur ';' + espaces + MAJUSCULES.
    $content = "\xEF\xBB\xBFcourriel;role\n  CHARLIE@Exemple.CA ;student\n";
    $csv     = makeCsvUpload($content);

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('csvFile', $csv)
        ->call('importCsv')
        ->assertHasNoErrors();

    expect(Enrollment::where('course_id', $this->courseA->id)->where('user_id', $s1->id)->where('status', 'active')->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. IDEMPOTENCE - réimport ne crée aucune nouvelle inscription
// ─────────────────────────────────────────────────────────────────────────────

test('réimport idempotent : 0 nouvelle inscription, déjà-inscrits comptés', function (): void {
    $owner = makeCsvRole($this->courseA, 'owner');
    makeCsvStudent('alice@exemple.ca');
    makeCsvStudent('bob@exemple.ca');

    $csv = "email\nalice@exemple.ca\nbob@exemple.ca\n";

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('csvFile', makeCsvUpload($csv))
        ->call('importCsv')
        ->assertHasNoErrors();

    expect(Enrollment::where('course_id', $this->courseA->id)->count())->toBe(2);

    // Réimport du MÊME fichier.
    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('csvFile', makeCsvUpload($csv))
        ->call('importCsv')
        ->assertHasNoErrors();

    // Aucune nouvelle ligne.
    expect(Enrollment::where('course_id', $this->courseA->id)->count())->toBe(2);

    $report = $component->get('importReport');
    expect($report['enrolled'])->toBe(0);
    expect($report['already'])->toBe(2);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. COURRIEL INVALIDE - ignoré et compté
// ─────────────────────────────────────────────────────────────────────────────

test('courriel invalide ignoré et compté, rien inscrit pour cette ligne', function (): void {
    $owner = makeCsvRole($this->courseA, 'owner');
    $s1    = makeCsvStudent('valide@exemple.ca');

    $csv = makeCsvUpload("email\nvalide@exemple.ca\npas-un-courriel\n@invalide\n");

    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('csvFile', $csv)
        ->call('importCsv')
        ->assertHasNoErrors();

    expect(Enrollment::where('course_id', $this->courseA->id)->count())->toBe(1);
    expect(Enrollment::where('course_id', $this->courseA->id)->where('user_id', $s1->id)->exists())->toBeTrue();

    $report = $component->get('importReport');
    expect($report['enrolled'])->toBe(1);
    expect($report['invalid'])->toBe(2);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. SÉCURITÉ (OWASP A01) - non-gestionnaire interdit, anti-escalade
// ─────────────────────────────────────────────────────────────────────────────

test('SÉCURITÉ : un étudiant ne peut PAS importer (403, rien écrit)', function (): void {
    $student = makeCsvStudent();
    makeCsvStudent('cible@exemple.ca');

    Livewire::actingAs($student)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->assertForbidden();

    // Aucune inscription possible.
    expect(Enrollment::where('course_id', $this->courseA->id)->count())->toBe(0);
});

test('ANTI-ESCALADE : owner de A ne peut pas importer sur B même en forgeant le courseId', function (): void {
    $owner = makeCsvRole($this->courseA, 'owner');
    makeCsvStudent('cible@exemple.ca');

    $csv = makeCsvUpload("email\ncible@exemple.ca\n");

    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA]);

    // Forge le courseId vers B : importCsv() re-résout B et authorize() → 403.
    $component->set('courseId', $this->courseB->id)
        ->set('csvFile', $csv)
        ->call('importCsv')
        ->assertForbidden();

    expect(Enrollment::where('course_id', $this->courseB->id)->count())->toBe(0);
});

test('admin peut importer sur n\'importe quel cours', function (): void {
    makeCsvStudent('dave@exemple.ca');

    $csv = makeCsvUpload("email\ndave@exemple.ca\n");

    Livewire::actingAs(makeCsvAdmin())
        ->test(CourseRoster::class, ['course' => $this->courseB])
        ->set('csvFile', $csv)
        ->call('importCsv')
        ->assertHasNoErrors();

    expect(Enrollment::where('course_id', $this->courseB->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. VALIDATION FICHIER - mime et taille côté serveur
// ─────────────────────────────────────────────────────────────────────────────

test('fichier non-CSV rejeté côté serveur (mimes)', function (): void {
    $owner = makeCsvRole($this->courseA, 'owner');

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('csvFile', UploadedFile::fake()->create('virus.php', 10, 'application/x-php'))
        ->call('importCsv')
        ->assertHasErrors(['csvFile']);

    expect(Enrollment::where('course_id', $this->courseA->id)->count())->toBe(0);
});

test('CSV trop lourd (> 2 Mo) rejeté côté serveur', function (): void {
    $owner = makeCsvRole($this->courseA, 'owner');

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('csvFile', UploadedFile::fake()->create('gros.csv', 3000, 'text/csv'))
        ->call('importCsv')
        ->assertHasErrors(['csvFile']);

    expect(Enrollment::where('course_id', $this->courseA->id)->count())->toBe(0);
});
