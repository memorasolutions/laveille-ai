<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Phase 2 du système de diplomation moderne : CÂBLAGE réel dans
 * l'émission des certificats (CertificateController), au-delà de l'éditeur/service
 * de rendu déjà couverts par DiplomaTemplatePhase1Test. Ce fichier ne redouble PAS
 * ces tests ni ceux d'AcademyCertificateCustomTest / CertificatePdfD11Test (rendu
 * par défaut / personnalisation champs texte) — il prouve UNIQUEMENT :
 *
 *  (a) NON-RÉGRESSION : cours SANS diploma_template_id → rendu par défaut inchangé,
 *      drapeau OFF puis ON (le drapeau seul ne change rien sans gabarit associé) ;
 *  (b) CHEMIN ACTIF : cours AVEC diploma_template_id + drapeau ON → le rendu passe
 *      réellement par DiplomaRenderService (HTML custom + PDF) ;
 *  (c) REPLI DE SÉCURITÉ : même cours, drapeau désactivé après coup → repli
 *      immédiat sur le rendu par défaut (la donnée reste, seul l'effet est coupé) ;
 *  (d) ANTI-IDOR : le sélecteur de gabarit dans CourseEditor n'expose et n'accepte
 *      QUE les gabarits du formateur propriétaire (created_by = auth id).
 *
 * Garde-fou : SKIPPED si le module Academy ou dompdf est absent (tous les
 * scénarios ci-dessous vérifient aussi le PDF). Helpers préfixés « dip2 » pour
 * éviter toute redéclaration avec DiplomaTemplatePhase1Test/AcademyCertificateCustomTest/CertificatePdfD11Test.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\DiplomaTemplate;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
        $this->markTestSkipped('dompdf (barryvdh/laravel-dompdf) absent.');
    }

    config()->set('academy.under_construction', false);
    config()->set('academy.diploma_editor_enabled', false); // chaque test l'active lui-même si besoin

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers dip2 (préfixés, autonomes — aucune factory Course/CertificateIssued/
// DiplomaTemplate n'existe dans ce projet, ::create() direct comme les autres suites)
// ─────────────────────────────────────────────────────────────────────────────

function dip2Course(string $slug, string $title = 'Cours Phase 2'): Course
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

function dip2Certificate(User $user, Course $course): CertificateIssued
{
    return CertificateIssued::create([
        'user_id'           => $user->id,
        'course_id'         => $course->id,
        'serial'            => 'DIP2-' . uniqid(),
        'verification_hash' => hash('sha256', uniqid('', true)),
        'public_url_slug'   => 'dip2-cert-' . uniqid(),
        'issued_at'         => now(),
        'hours_earned'      => 4,
        'final_score'       => 90,
    ]);
}

/** Formateur propriétaire d'un cours (relation RÉELLE : Course::courseRoles(), pas roles()). */
function dip2Owner(Course $course): User
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

/** @return array<int, array<string, mixed>> */
function dip2Elements(): array
{
    return [
        [
            'id' => 'el_1', 'kind' => 'text',
            'content' => '{system.learner_name}', 'variable' => 'system.learner_name',
            'x' => 10.0, 'y' => 10.0, 'width' => 80.0, 'height' => 12.0,
        ],
    ];
}

function dip2Template(User $owner, string $name = 'Gabarit Phase 2'): DiplomaTemplate
{
    return DiplomaTemplate::create([
        'name'          => $name,
        'layout_config' => ['elements' => dip2Elements()],
        'is_default'    => false,
        'created_by'    => $owner->id,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) NON-RÉGRESSION — cours SANS diploma_template_id
// ─────────────────────────────────────────────────────────────────────────────

test('cours sans diploma_template_id — rendu par défaut inchangé, drapeau OFF puis ON', function (): void {
    $course  = dip2Course('dip2-cours-sans-gabarit');
    $learner = User::factory()->create(['name' => 'Alice Apprenante']);
    $cert    = dip2Certificate($learner, $course);

    foreach ([false, true] as $flag) {
        config()->set('academy.diploma_editor_enabled', $flag);

        $html = $this->get(route('academy.certificates.show', $cert->public_url_slug));
        $html->assertOk();
        $html->assertSee('Certificat de complétion');
        $html->assertSee('#064E5A');
        $html->assertSee('Alice Apprenante');

        $pdf = $this->get(route('academy.certificates.download', $cert->public_url_slug));
        $pdf->assertOk();
        expect($pdf->headers->get('content-type'))->toContain('application/pdf');
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) CHEMIN ACTIF — cours AVEC diploma_template_id + drapeau ON
// ─────────────────────────────────────────────────────────────────────────────

test('cours avec diploma_template_id + drapeau ON — rendu custom HTML et PDF', function (): void {
    $course  = dip2Course('dip2-cours-avec-gabarit');
    $owner   = dip2Owner($course);
    $template = dip2Template($owner);
    $course->update(['diploma_template_id' => $template->id]);

    $learner = User::factory()->create(['name' => 'Bob Apprenant']);
    $cert    = dip2Certificate($learner, $course);

    config()->set('academy.diploma_editor_enabled', true);

    $html = $this->get(route('academy.certificates.show', $cert->public_url_slug));
    $html->assertOk();
    $html->assertDontSee('Certificat de complétion');
    $html->assertSee('Bob Apprenant');
    $html->assertSee('diploma-container');

    $pdf = $this->get(route('academy.certificates.download', $cert->public_url_slug));
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) REPLI DE SÉCURITÉ — diploma_template_id renseigné mais drapeau OFF
// ─────────────────────────────────────────────────────────────────────────────

test('cours avec diploma_template_id mais drapeau OFF — repli sur le rendu par défaut', function (): void {
    $course   = dip2Course('dip2-cours-gabarit-drapeau-off');
    $owner    = dip2Owner($course);
    $template = dip2Template($owner);
    $course->update(['diploma_template_id' => $template->id]);

    $learner = User::factory()->create(['name' => 'Carole Apprenante']);
    $cert    = dip2Certificate($learner, $course);

    config()->set('academy.diploma_editor_enabled', false);

    $html = $this->get(route('academy.certificates.show', $cert->public_url_slug));
    $html->assertOk();
    $html->assertSee('Certificat de complétion');
    $html->assertSee('#064E5A');
    $html->assertSee('Carole Apprenante');
});

// ─────────────────────────────────────────────────────────────────────────────
// (d) ANTI-IDOR — sélecteur owner-scopé dans CourseEditor
// ─────────────────────────────────────────────────────────────────────────────

test('le sélecteur de gabarit n\'expose et n\'accepte que les gabarits du formateur propriétaire', function (): void {
    $course1 = dip2Course('dip2-cours-1');
    $owner1  = dip2Owner($course1);
    $template1 = dip2Template($owner1, 'Gabarit du formateur 1');

    $course2 = dip2Course('dip2-cours-2');
    $owner2  = dip2Owner($course2);
    $template2 = dip2Template($owner2, 'Gabarit du formateur 2');

    config()->set('academy.diploma_editor_enabled', true);

    $component = Livewire::actingAs($owner1)->test(CourseEditor::class, ['course' => $course1]);

    // Le formateur 1 ne voit QUE son propre gabarit dans le sélecteur.
    $templates = $component->instance()->myDiplomaTemplates();
    expect($templates)->toHaveCount(1);
    expect($templates->first()->id)->toBe($template1->id);

    // Tentative de forcer un gabarit d'un AUTRE formateur (id forgé) → no-op silencieux.
    $component->set('diploma_template_id', $template2->id)
        ->call('saveCertificate')
        ->assertHasNoErrors();

    expect($course1->fresh()->diploma_template_id)->toBeNull();
});
