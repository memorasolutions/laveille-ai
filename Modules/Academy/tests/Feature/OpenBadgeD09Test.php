<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Open Badges 3.0 vérifiables (dette D09).
 *
 * Couvre :
 *  - drapeau off → 404 (pas d'exposition des endpoints)
 *  - assertion JSON-LD conforme OB 3.0 (contextes 1EdTech + W3C VC, type, issuer, subject)
 *  - pseudonymat de l'apprenant (jamais d'email en clair dans l'assertion publique)
 *  - serial invalide → 404
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 * Helpers préfixés « d09 » pour éviter les conflits avec les autres suites.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Course;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers d09 (préfixés, autonomes, sans conflit avec les autres fichiers)
// ─────────────────────────────────────────────────────────────────────────────

/** Crée un cours publié minimal pour les tests D09. */
function d09Course(string $slug = 'd09-cours'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'D09 Cours',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/** Crée un utilisateur avec un email connu pour les tests de pseudonymat. */
function d09User(): User
{
    return User::factory()->create(['email' => 'd09learner@example.com']);
}

/** Émet un certificat de test pour user + course. */
function d09Cert(User $user, Course $course): CertificateIssued
{
    return CertificateIssued::create([
        'user_id'           => $user->id,
        'course_id'         => $course->id,
        'serial'            => 'ACAD-D09TEST001',
        'verification_hash' => hash('sha256', 'd09test'),
        'public_url_slug'   => 'cert-d09test-' . time(),
        'issued_at'         => now(),
        'hours_earned'      => 2,
        'final_score'       => 95,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Tests
// ─────────────────────────────────────────────────────────────────────────────

it('flag off retourne 404 pour l assertion', function (): void {
    config()->set('academy.open_badges_enabled', false);

    $user   = d09User();
    $course = d09Course();
    $cert   = d09Cert($user, $course);

    $this->get(route('academy.badges.assertion', ['serial' => $cert->serial]))
        ->assertStatus(404);
});

it('l assertion JSON-LD est conforme OB 3 0', function (): void {
    config()->set('academy.open_badges_enabled', true);

    $user   = d09User();
    $course = d09Course();
    $cert   = d09Cert($user, $course);

    $response = $this->get(route('academy.badges.assertion', ['serial' => $cert->serial]));
    $response->assertStatus(200);

    $json = $response->json();

    // Contextes 1EdTech + W3C VC Data Model
    expect($json['@context'])->toContain('https://www.w3.org/2018/credentials/v1');
    expect($json['@context'])->toContain('https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json');

    // Type OpenBadgeCredential obligatoire
    expect($json['type'])->toContain('OpenBadgeCredential');

    // Issuer et credentialSubject présents
    expect($json)->toHaveKey('issuer');
    expect($json)->toHaveKey('credentialSubject');
});

it('l identifiant apprenant est pseudonyme', function (): void {
    config()->set('academy.open_badges_enabled', true);

    $user   = d09User(); // email : d09learner@example.com
    $course = d09Course();
    $cert   = d09Cert($user, $course);

    $response = $this->get(route('academy.badges.assertion', ['serial' => $cert->serial]));
    $response->assertStatus(200);

    $json      = $response->json();
    $subjectId = $json['credentialSubject']['id'] ?? '';

    // L'email ne doit JAMAIS apparaître en clair (Loi 25 QC / RGPD)
    expect($subjectId)->not->toContain('d09learner@example.com');

    // L'identifiant pseudonyme doit utiliser le préfixe URN Memora
    expect($subjectId)->toContain('urn:memora:learner:');
});

it('serial inconnu retourne 404', function (): void {
    config()->set('academy.open_badges_enabled', true);

    $this->get(route('academy.badges.assertion', ['serial' => 'ACAD-INEXISTANT999']))
        ->assertStatus(404);
});
