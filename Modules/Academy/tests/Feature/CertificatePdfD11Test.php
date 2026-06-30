<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * D11 - Téléchargement PDF du certificat (parité Moodle). Prouve :
 *  - la route renvoie bien un fichier PDF en pièce jointe (Content-Type +
 *    Content-Disposition: attachment au nom du certificat) ;
 *  - un slug inconnu retourne 404 (jamais de 500) ;
 *  - la personnalisation du cours (couleur/titre) est utilisée sans casser le rendu.
 *
 * Garde-fou : SKIPPED si le module Academy ou dompdf est absent.
 * Helpers préfixés « d11 » pour éviter toute redéclaration avec les autres suites.
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

    if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
        $this->markTestSkipped('dompdf (barryvdh/laravel-dompdf) absent.');
    }

    config()->set('academy.under_construction', false);
});

function d11Course(string $slug = 'cours-d11', string $title = 'Cours D11'): Course
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

function d11Certificate(User $user, Course $course): CertificateIssued
{
    return CertificateIssued::create([
        'user_id'           => $user->id,
        'course_id'         => $course->id,
        'serial'            => 'D11-' . uniqid(),
        'verification_hash' => hash('sha256', uniqid('', true)),
        'public_url_slug'   => 'd11-cert-' . uniqid(),
        'issued_at'         => now(),
        'hours_earned'      => 3,
        'final_score'       => 95,
    ]);
}

test('le téléchargement renvoie un PDF en pièce jointe', function (): void {
    $course = d11Course();
    $user   = User::factory()->create(['name' => 'Alice Apprenante']);
    $cert   = d11Certificate($user, $course);

    $response = $this->get(route('academy.certificates.download', $cert->public_url_slug));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))
        ->toContain('attachment')
        ->toContain('certificat-' . $cert->serial . '.pdf');
});

test('un slug inconnu retourne 404 (jamais de 500)', function (): void {
    $this->get(route('academy.certificates.download', 'slug-inexistant-xyz'))
        ->assertNotFound();
});

test('un cours personnalisé produit le PDF sans erreur', function (): void {
    $course = d11Course();
    $course->update([
        'certificate_title'          => 'Diplôme spécial D11',
        'certificate_accent_color'   => '#AA1133',
        'certificate_signature_name' => 'Stéphane Lapointe',
    ]);

    $user = User::factory()->create(['name' => 'Bob Apprenant']);
    $cert = d11Certificate($user, $course);

    $this->get(route('academy.certificates.download', $cert->public_url_slug))
        ->assertOk();
});
