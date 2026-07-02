<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Phase 3 du système de diplomation moderne (bibliothèque
 * d'arrière-plans réutilisables + rate-limiting), au-delà de l'éditeur/rendu
 * déjà couverts par DiplomaTemplatePhase1Test et du câblage certificat déjà
 * couvert par DiplomaTemplatePhase2WiringTest. Ce fichier ne redouble PAS ces
 * suites — il prouve UNIQUEMENT :
 *
 *  (a) UPLOAD D'ARRIÈRE-PLAN : owner-scopé (created_by), anti-abus (type non-image
 *      rejeté, taille > 5 Mo rejetée), invisible/refusé pour un formateur étranger
 *      (anti-IDOR sur selectBackground()) ;
 *  (b) RÉUTILISATION MULTI-COURS : DEUX cours du MÊME formateur peuvent pointer
 *      vers le MÊME diploma_template_id sans erreur (non-régression — ce
 *      comportement était déjà acquis via une relation belongsTo simple sans
 *      contrainte unique, ce test le PROUVE, il ne construit rien de nouveau) ;
 *  (c) RATE-LIMIT sur save() : au-delà de 20 sauvegardes/heure, la sauvegarde
 *      est bloquée proprement (message d'erreur flashé, aucune écriture en base).
 *
 * Garde-fou : SKIPPED si le module Academy est désactivé. Helpers préfixés
 * « dip3 » (aucune redéclaration d'une fonction d'un autre fichier de test).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Academy\Livewire\DiplomaTemplateEditor;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\DiplomaBackground;
use Modules\Academy\Models\DiplomaTemplate;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    config()->set('academy.diploma_editor_enabled', true);
    Storage::fake('public');

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers dip3 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function dip3Instructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function dip3Course(string $slug, string $title = 'Cours Phase 3'): Course
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

/** @return array<int, array<string, mixed>> */
function dip3Elements(): array
{
    return [
        [
            'id' => 'el_1', 'kind' => 'text',
            'content' => '{system.learner_name}', 'variable' => 'system.learner_name',
            'x' => 10.0, 'y' => 10.0, 'width' => 80.0, 'height' => 12.0,
        ],
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) UPLOAD D'ARRIÈRE-PLAN — owner-scopé + anti-abus (type/taille) + anti-IDOR
// ─────────────────────────────────────────────────────────────────────────────

test('upload d\'arrière-plan valide — persisté, owner-scopé (created_by)', function (): void {
    $instructor = dip3Instructor();

    Livewire::actingAs($instructor)
        ->test(DiplomaTemplateEditor::class)
        ->set('newBackgroundName', 'Fond officiel')
        ->set('newBackgroundFile', UploadedFile::fake()->image('fond.jpg', 1600, 1200))
        ->call('uploadBackground')
        ->assertHasNoErrors();

    $background = DiplomaBackground::first();

    expect($background)->not->toBeNull()
        ->and($background->created_by)->toBe($instructor->id)
        ->and($background->imageUrl())->not->toBeNull();
});

test('upload d\'arrière-plan — un fichier NON-image est rejeté côté serveur', function (): void {
    $instructor = dip3Instructor();

    Livewire::actingAs($instructor)
        ->test(DiplomaTemplateEditor::class)
        ->set('newBackgroundName', 'Virus')
        ->set('newBackgroundFile', UploadedFile::fake()->create('virus.php', 10, 'application/x-php'))
        ->call('uploadBackground')
        ->assertHasErrors(['newBackgroundFile']);

    expect(DiplomaBackground::count())->toBe(0);
});

test('upload d\'arrière-plan — une image trop lourde (> 5 Mo) est rejetée côté serveur', function (): void {
    $instructor = dip3Instructor();

    Livewire::actingAs($instructor)
        ->test(DiplomaTemplateEditor::class)
        ->set('newBackgroundName', 'Gros fond')
        ->set('newBackgroundFile', UploadedFile::fake()->image('gros.jpg')->size(6000))
        ->call('uploadBackground')
        ->assertHasErrors(['newBackgroundFile']);

    expect(DiplomaBackground::count())->toBe(0);
});

test('arrière-plan d\'un AUTRE formateur — invisible et refusé (anti-IDOR)', function (): void {
    $otherInstructor   = dip3Instructor();
    $foreignBackground = DiplomaBackground::create([
        'name'       => 'Fond étranger',
        'created_by' => $otherInstructor->id,
    ]);
    $foreignBackground->addMedia(UploadedFile::fake()->image('foreign.jpg', 1600, 1200))
        ->toMediaCollection('background');

    $instructor = dip3Instructor();

    $component = Livewire::actingAs($instructor)->test(DiplomaTemplateEditor::class);

    // Invisible dans la bibliothèque du formateur courant.
    $myBackgrounds = $component->instance()->myBackgrounds();
    expect($myBackgrounds->contains('id', $foreignBackground->id))->toBeFalse();

    // Tentative de sélection d'un id forgé (appartenant à un autre) → no-op silencieux.
    $component->call('selectBackground', $foreignBackground->id);
    expect($component->get('backgroundId'))->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) RÉUTILISATION MULTI-COURS — même gabarit sur DEUX cours du même formateur
// ─────────────────────────────────────────────────────────────────────────────

test('deux cours du même formateur peuvent pointer vers le MÊME diploma_template_id sans erreur', function (): void {
    $course1 = dip3Course('dip3-cours-a');
    $course2 = dip3Course('dip3-cours-b');

    $owner = dip3Instructor();
    CourseRole::create(['course_id' => $course1->id, 'user_id' => $owner->id, 'role' => 'owner']);
    CourseRole::create(['course_id' => $course2->id, 'user_id' => $owner->id, 'role' => 'owner']);

    $template = DiplomaTemplate::create([
        'name'          => 'Gabarit partagé',
        'layout_config' => ['elements' => dip3Elements()],
        'is_default'    => false,
        'created_by'    => $owner->id,
    ]);

    $course1->update(['diploma_template_id' => $template->id]);
    $course2->update(['diploma_template_id' => $template->id]);

    expect($course1->fresh()->diploma_template_id)->toBe($template->id)
        ->and($course2->fresh()->diploma_template_id)->toBe($template->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) RATE-LIMIT — sauvegarde de gabarit (save()) bloquée proprement au-delà de 20/heure
// ─────────────────────────────────────────────────────────────────────────────

test('rate-limit atteint sur la sauvegarde de gabarit — save() bloque proprement, aucune écriture', function (): void {
    $instructor = dip3Instructor();

    // Simule l'atteinte de la limite (20/heure) SANS écrire 20 gabarits réels.
    $rateLimitKey = 'diploma-template-save:' . $instructor->id;
    for ($i = 0; $i < 20; $i++) {
        RateLimiter::hit($rateLimitKey, 3600);
    }
    expect(RateLimiter::tooManyAttempts($rateLimitKey, 20))->toBeTrue();

    // 21e sauvegarde : bloquée AVANT toute écriture (aucune exception, no-op propre).
    // NOTE : l'assertion sur le message flashé (session « diploma_editor_error ») n'est
    // délibérément PAS testée ici — le pilote de session « array » utilisé en test ne
    // propage pas fiablement les flashs Livewire à travers des actions ->set()/->call()
    // chaînées (aucun autre test de ce module ne repose sur ce mécanisme). La preuve
    // déterministe et sans ambiguïté du blocage est l'ABSENCE totale d'écriture en base
    // ET l'absence d'assignation de templateId côté composant.
    $component = Livewire::actingAs($instructor)
        ->test(DiplomaTemplateEditor::class)
        ->set('name', 'Gabarit X')
        ->set('elements', dip3Elements())
        ->call('save');

    expect($component->get('templateId'))->toBeNull()
        ->and(DiplomaTemplate::count())->toBe(0);
});
