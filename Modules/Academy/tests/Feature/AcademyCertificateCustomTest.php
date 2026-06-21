<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Personnalisation du certificat AU NIVEAU DU COURS (Phase E / E3).
 *
 * Couvre :
 *  - rendu : un cours personnalisé (titre/message/signature/couleur) → la page
 *    publique du certificat émis affiche ces valeurs ;
 *  - rétrocompatibilité : un cours SANS personnalisation → rendu par défaut
 *    inchangé (un certificat existant n'est pas cassé) ;
 *  - sécurité OWASP A01 : édition gâtée manageStructure (non-gérant → 403),
 *    anti-XSS (titre/message avec <script> neutralisé au rendu), couleur invalide
 *    rejetée, un gérant ne personnalise que SES cours.
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 * Helpers préfixés « e3 » pour éviter toute redéclaration avec les autres suites.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\CertificateIssued;
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

/** Helper : crée un cours gratuit publié minimal. */
function e3Course(string $slug = 'cours-e3', string $title = 'Cours E3'): Course
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

/** Helper : admin academy.manage. */
function e3Admin(): User
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
function e3Owner(Course $course): User
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

/** Helper : émet un certificat brut pour un user sur un cours. */
function e3Certificate(User $user, Course $course): CertificateIssued
{
    return CertificateIssued::create([
        'user_id'           => $user->id,
        'course_id'         => $course->id,
        'serial'            => 'E3-'.uniqid(),
        'verification_hash' => hash('sha256', uniqid('', true)),
        'public_url_slug'   => 'e3-cert-'.uniqid(),
        'issued_at'         => now(),
        'hours_earned'      => 2,
        'final_score'       => 100,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. RENDU - cours personnalisé → la page publique affiche les valeurs du cours
// ─────────────────────────────────────────────────────────────────────────────

test('un cours personnalisé affiche titre, message, signature et couleur sur le certificat public', function (): void {
    $course = e3Course();
    $course->update([
        'certificate_title'          => 'Diplôme spécial E3',
        'certificate_message'        => 'Mention très honorable pour ce parcours.',
        'certificate_signature_name' => 'Stéphane Lapointe, La veille',
        'certificate_accent_color'   => '#AA1133',
    ]);

    $user = User::factory()->create(['name' => 'Alice Apprenante']);
    $cert = e3Certificate($user, $course);

    $this->get(route('academy.certificates.show', $cert->public_url_slug))
        ->assertOk()
        ->assertSee('Diplôme spécial E3')
        ->assertSee('Mention très honorable pour ce parcours.')
        ->assertSee('Stéphane Lapointe, La veille')
        // La couleur d'accent est injectée comme variable CSS / bordure de carte.
        ->assertSee('#AA1133')
        ->assertSee('Alice Apprenante');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. RÉTROCOMPATIBILITÉ - cours sans personnalisation → rendu par défaut intact
// ─────────────────────────────────────────────────────────────────────────────

test('un cours SANS personnalisation rend le certificat par défaut (rétrocompat)', function (): void {
    $course = e3Course('cours-defaut', 'Cours par défaut');

    // Aucun champ de personnalisation renseigné (null partout).
    expect($course->certificate_title)->toBeNull();
    expect($course->certificate_message)->toBeNull();
    expect($course->certificate_signature_name)->toBeNull();
    expect($course->certificate_accent_color)->toBeNull();

    $user = User::factory()->create(['name' => 'Bob Apprenant']);
    $cert = e3Certificate($user, $course);

    $this->get(route('academy.certificates.show', $cert->public_url_slug))
        ->assertOk()
        // Titre par défaut du gabarit.
        ->assertSee('Certificat de complétion')
        // Couleur de charte par défaut.
        ->assertSee('#064E5A')
        ->assertSee('Bob Apprenant')
        ->assertSee('Cours par défaut');
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. SÉCURITÉ - édition gâtée manageStructure
// ─────────────────────────────────────────────────────────────────────────────

test('un non-gérant ne peut pas ouvrir l\'éditeur (403)', function (): void {
    $course = e3Course();
    $intrus = User::factory()->create(); // aucun rôle/ownership

    Livewire::actingAs($intrus)
        ->test(CourseEditor::class, ['course' => $course])
        ->assertForbidden();
});

test('un formateur ne personnalise QUE le certificat de SON cours', function (): void {
    $courseA = e3Course('cours-a', 'Cours A');
    $courseB = e3Course('cours-b', 'Cours B');

    $ownerA = e3Owner($courseA);

    // Le owner de A ne peut pas ouvrir l'éditeur de B (anti-escalade).
    Livewire::actingAs($ownerA)
        ->test(CourseEditor::class, ['course' => $courseB])
        ->assertForbidden();

    // Sur SON cours, il personnalise sans erreur.
    Livewire::actingAs($ownerA)
        ->test(CourseEditor::class, ['course' => $courseA])
        ->set('certificate_title', 'Certificat du cours A')
        ->call('saveCertificate')
        ->assertHasNoErrors();

    expect($courseA->fresh()->certificate_title)->toBe('Certificat du cours A');
    // Le cours B n'a JAMAIS été touché.
    expect($courseB->fresh()->certificate_title)->toBeNull();
});

test('admin peut personnaliser le certificat de n\'importe quel cours', function (): void {
    $course = e3Course();

    Livewire::actingAs(e3Admin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set('certificate_signature_name', 'La veille')
        ->set('certificate_accent_color', '#064E5A')
        ->call('saveCertificate')
        ->assertHasNoErrors();

    expect($course->fresh()->certificate_signature_name)->toBe('La veille');
    expect($course->fresh()->certificate_accent_color)->toBe('#064E5A');
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. SÉCURITÉ - anti-XSS au rendu (titre/message)
// ─────────────────────────────────────────────────────────────────────────────

test('un titre/message contenant du script est neutralisé au rendu (anti-XSS)', function (): void {
    $course = e3Course();
    $course->update([
        'certificate_title'   => '<script>alert(1)</script>Titre',
        'certificate_message' => 'Bravo <script>alert(2)</script> et bien joué.',
    ]);

    $user = User::factory()->create(['name' => 'Carol']);
    $cert = e3Certificate($user, $course);

    $html = $this->get(route('academy.certificates.show', $cert->public_url_slug))
        ->assertOk()
        ->getContent();

    // Aucune balise <script> active injectée par les champs personnalisés.
    expect($html)->not->toContain('<script>alert(1)</script>');
    expect($html)->not->toContain('<script>alert(2)</script>');
    // Le texte légitime reste visible (échappé / nettoyé).
    expect($html)->toContain('Titre');
    expect($html)->toContain('Bravo');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. SÉCURITÉ - couleur invalide rejetée
// ─────────────────────────────────────────────────────────────────────────────

test('une couleur d\'accent invalide est rejetée (validation serveur)', function (): void {
    $course = e3Course();

    Livewire::actingAs(e3Admin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set('certificate_accent_color', 'rouge; background:url(x)')
        ->call('saveCertificate')
        ->assertHasErrors('certificate_accent_color');

    // Rien n'a été persisté pour la couleur.
    expect($course->fresh()->certificate_accent_color)->toBeNull();
});

test('un champ vidé efface la personnalisation (retour au défaut)', function (): void {
    $course = e3Course();
    $course->update(['certificate_title' => 'Ancien titre']);

    Livewire::actingAs(e3Admin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set('certificate_title', '')
        ->call('saveCertificate')
        ->assertHasNoErrors();

    expect($course->fresh()->certificate_title)->toBeNull();
});
