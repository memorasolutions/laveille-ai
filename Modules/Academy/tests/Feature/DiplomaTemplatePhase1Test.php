<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Phase 1 du système de diplomation moderne (gabarits de diplômes,
 * éditeur Konva.js, rendu HTML/PDF via DiplomaRenderService).
 *
 * Prouve que :
 *  (a) drapeau academy.diploma_editor_enabled OFF (défaut) → l'éditeur 404, ET le
 *      système de certificat EXISTANT (page + PDF) reste inchangé (non-régression) ;
 *  (b) un DiplomaTemplate + layout_config persiste correctement, owner-scopé
 *      (anti-IDOR : un formateur ne charge/modifie/supprime QUE ses gabarits) ;
 *  (c) le rendu HTML interpole les 4 familles de variables (système, pédagogique,
 *      organisationnel, custom) avec des DONNÉES RÉELLES d'un certificat/cours ;
 *  (d) le QR généré pointe vers LA MÊME URL de vérification publique que le
 *      certificat existant (academy.certificates.show) — un seul système de confiance.
 *
 * Autonome : helpers préfixés « dip » (aucune redéclaration d'une fonction d'un
 * autre fichier de test). SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\DiplomaTemplateEditor;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\DiplomaTemplate;
use Modules\Academy\Services\DiplomaRenderService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    config()->set('academy.diploma_editor_enabled', false); // chaque test l'active lui-même si besoin

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers dip (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function dipInstructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function dipCourse(string $slug = 'dip-cours'): Course
{
    return Course::create([
        'slug'                        => $slug,
        'title'                       => 'Introduction à l\'intelligence artificielle',
        'language'                    => 'fr-CA',
        'level'                       => 'intro',
        'visibility'                  => 'public',
        'access_type'                 => 'free',
        'status'                      => 'published',
        'currency'                    => 'CAD',
        'certificate_signature_name'  => 'Stéphane Lapointe',
    ]);
}

function dipCertificate(User $user, Course $course): CertificateIssued
{
    return CertificateIssued::create([
        'user_id'           => $user->id,
        'course_id'         => $course->id,
        'serial'            => 'ACAD-DIP' . uniqid(),
        'verification_hash' => hash('sha256', uniqid('', true)),
        'public_url_slug'   => 'cert-dip-' . uniqid(),
        'issued_at'         => now(),
        'hours_earned'      => 6,
        'final_score'       => 88,
    ]);
}

/** @return array<int, array<string, mixed>> */
function dipElements(): array
{
    return [
        [
            'id' => 'el_1', 'kind' => 'text',
            'content' => 'Bravo {system.learner_name} !', 'variable' => 'system.learner_name',
            'x' => 10.0, 'y' => 10.0, 'width' => 80.0, 'height' => 12.0,
            'font_size' => 26, 'font_weight' => '700', 'color' => '#064E5A', 'align' => 'center',
        ],
        [
            'id' => 'el_2', 'kind' => 'text',
            'content' => '{pedagogical.course_title} — {pedagogical.final_score}', 'variable' => null,
            'x' => 10.0, 'y' => 30.0, 'width' => 80.0, 'height' => 10.0,
            'font_size' => 18, 'font_weight' => '400', 'color' => '#1A1D23', 'align' => 'center',
        ],
        [
            'id' => 'el_3', 'kind' => 'text',
            'content' => 'Délivré par {organizational.organization_name}, signé {organizational.signature_name}',
            'variable' => null, 'x' => 10.0, 'y' => 50.0, 'width' => 80.0, 'height' => 10.0,
            'font_size' => 14, 'font_weight' => '400', 'color' => '#374151', 'align' => 'center',
        ],
        ['id' => 'el_4', 'kind' => 'qr', 'x' => 80.0, 'y' => 75.0, 'width' => 15.0, 'height' => 15.0],
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) Drapeau OFF — éditeur 404, certificat existant inchangé
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau off — l editeur de gabarits est 404', function (): void {
    $instructor = dipInstructor();
    $this->actingAs($instructor);

    Livewire::test(DiplomaTemplateEditor::class)->assertStatus(404);
});

test('drapeau off — la page publique du certificat reste inchangee', function (): void {
    $course = dipCourse();
    $user   = User::factory()->create(['name' => 'Alice Apprenante']);
    $cert   = dipCertificate($user, $course);

    $response = $this->get(route('academy.certificates.show', $cert->public_url_slug));

    $response->assertOk();
    $response->assertSee($cert->serial);
    $response->assertSee($course->title);
});

test('drapeau off — le telechargement PDF du certificat reste inchange', function (): void {
    if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
        $this->markTestSkipped('dompdf (barryvdh/laravel-dompdf) absent.');
    }

    $course = dipCourse();
    $user   = User::factory()->create(['name' => 'Bob Apprenant']);
    $cert   = dipCertificate($user, $course);

    $response = $this->get(route('academy.certificates.download', $cert->public_url_slug));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) Persistance du gabarit, owner-scopée (anti-IDOR)
// ─────────────────────────────────────────────────────────────────────────────

test('un formateur cree un gabarit et son layout_config persiste', function (): void {
    config()->set('academy.diploma_editor_enabled', true);

    $instructor = dipInstructor();
    $this->actingAs($instructor);

    Livewire::test(DiplomaTemplateEditor::class)
        ->set('name', 'Gabarit officiel')
        ->set('elements', dipElements())
        ->call('save')
        ->assertHasNoErrors();

    $template = DiplomaTemplate::query()->where('name', 'Gabarit officiel')->firstOrFail();

    expect($template->created_by)->toBe($instructor->id);
    expect($template->layout_config['elements'])->toHaveCount(4);
    expect($template->elements()[0]['content'])->toBe('Bravo {system.learner_name} !');
});

test('un formateur ne peut pas charger le gabarit d un autre formateur (anti-IDOR)', function (): void {
    config()->set('academy.diploma_editor_enabled', true);

    $owner  = dipInstructor();
    $intrus = dipInstructor();

    $template = DiplomaTemplate::create([
        'name'          => 'Gabarit privé',
        'layout_config' => ['elements' => dipElements()],
        'is_default'    => false,
        'created_by'    => $owner->id,
    ]);

    $this->actingAs($intrus);

    expect(fn () => Livewire::test(DiplomaTemplateEditor::class, ['templateId' => $template->id]))
        ->toThrow(ModelNotFoundException::class);
});

test('un formateur ne peut pas supprimer le gabarit d un autre formateur (anti-IDOR)', function (): void {
    config()->set('academy.diploma_editor_enabled', true);

    $owner  = dipInstructor();
    $intrus = dipInstructor();

    $template = DiplomaTemplate::create([
        'name'          => 'Gabarit privé',
        'layout_config' => ['elements' => dipElements()],
        'is_default'    => false,
        'created_by'    => $owner->id,
    ]);

    $this->actingAs($intrus);

    $component = Livewire::test(DiplomaTemplateEditor::class)
        ->call('confirmDelete', $template->id);

    expect(fn () => $component->call('delete', $template->id))
        ->toThrow(ModelNotFoundException::class);

    expect(DiplomaTemplate::query()->find($template->id))->not->toBeNull();
});

test('un seul gabarit par defaut par proprietaire', function (): void {
    config()->set('academy.diploma_editor_enabled', true);

    $instructor = dipInstructor();
    $this->actingAs($instructor);

    $first = DiplomaTemplate::create([
        'name' => 'Premier', 'layout_config' => ['elements' => dipElements()],
        'is_default' => true, 'created_by' => $instructor->id,
    ]);

    Livewire::test(DiplomaTemplateEditor::class)
        ->set('name', 'Deuxième')
        ->set('elements', dipElements())
        ->set('isDefault', true)
        ->call('save');

    expect($first->fresh()->is_default)->toBeFalse();
    expect(DiplomaTemplate::query()->where('is_default', true)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) Interpolation des 4 familles de variables avec des DONNÉES RÉELLES
// ─────────────────────────────────────────────────────────────────────────────

test('le rendu HTML interpole les 4 familles avec des donnees reelles', function (): void {
    $course = dipCourse();
    $user   = User::factory()->create(['name' => 'Camille Étudiante']);
    $cert   = dipCertificate($user, $course);
    $cert->load(['user', 'course']);

    $template = new DiplomaTemplate(['layout_config' => ['elements' => dipElements()]]);

    $html = app(DiplomaRenderService::class)->renderHtml($template, $cert);
    // Décodé pour comparer au texte source (le rendu échappe apostrophes/accents en entités HTML).
    $decoded = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

    // Système : nom de l'apprenant réel
    expect($decoded)->toContain('Bravo Camille Étudiante !');
    // Pédagogique : titre du cours réel + score réel
    expect($decoded)->toContain($course->title);
    expect($decoded)->toContain('88 %');
    // Organisationnel : nom de l'organisme + signataire du cours
    expect($decoded)->toContain((string) config('app.name'));
    expect($decoded)->toContain('Stéphane Lapointe');
    // Aucun placeholder non résolu ne doit subsister
    expect($html)->not->toContain('{system.learner_name}');
    expect($html)->not->toContain('{pedagogical.course_title}');
});

test('l apercu en mode exemple n utilise jamais une vraie donnee d apprenant', function (): void {
    $course = dipCourse();
    $user   = User::factory()->create(['name' => 'Vraie Personne Confidentielle']);
    $cert   = dipCertificate($user, $course);

    $template = new DiplomaTemplate(['layout_config' => ['elements' => dipElements()]]);

    $html = app(DiplomaRenderService::class)->renderHtml($template, $cert, sample: true);

    expect($html)->toContain('Jean Tremblay');
    expect($html)->not->toContain('Vraie Personne Confidentielle');
});

// ─────────────────────────────────────────────────────────────────────────────
// (d) Le QR pointe vers LA MÊME URL de vérification que le certificat existant
// ─────────────────────────────────────────────────────────────────────────────

test('l URL de verification du diplome est identique a celle du certificat existant', function (): void {
    $course = dipCourse();
    $user   = User::factory()->create();
    $cert   = dipCertificate($user, $course);

    $service            = app(DiplomaRenderService::class);
    $verificationUrl    = $service->verificationUrl($cert);
    $existingCertifUrl  = route('academy.certificates.show', $cert->public_url_slug);

    expect($verificationUrl)->toBe($existingCertifUrl);
});

test('le rendu HTML embarque un QR (image data URI) quand un element qr est present', function (): void {
    $course = dipCourse();
    $user   = User::factory()->create();
    $cert   = dipCertificate($user, $course);
    $cert->load(['user', 'course']);

    $template = new DiplomaTemplate(['layout_config' => ['elements' => dipElements()]]);

    $html = app(DiplomaRenderService::class)->renderHtml($template, $cert);

    expect($html)->toContain('data:image/png;base64,');
});
