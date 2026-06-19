<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests M7 — Recherche Scout, export CSV, déclencheurs Newsletter
 *
 * Stratégie : tests STRUCTURELS (ReflectionClass + analyse du code source),
 * identique aux tests M3/M4/M5/M6 — pas de RefreshDatabase (incompatible
 * avec JSON_UNQUOTE SQLite dans ce projet).
 *
 * Groupes :
 *   1. Course Searchable — trait + toSearchableArray + shouldBeSearchable
 *   2. CourseReindexCommand — structure
 *   3. AcademyController — méthode index enrichie (q, fallback)
 *   4. ExportController — structure + permission
 *   5. Events M7 — classes typées
 *   6. AcademyNewsletterTriggerListener — défensif no-op
 *   7. Routes M7 déclarées
 *
 * Garde-fou M8 : si le module Academy est désactivé dans modules_statuses.json,
 * tous les tests de ce fichier sont marqués SKIPPED (suite toujours verte).
 */

declare(strict_types=1);

uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// Garde-fou M8 : passer tous les tests si le module Academy est désactivé.
beforeEach(fn () => test()->skipIfAcademyDisabled());

use Illuminate\Support\Facades\Route;
use Modules\Academy\Console\CourseReindexCommand;
use Modules\Academy\Events\AcademyCertificateIssued;
use Modules\Academy\Events\AcademyEnrollmentCreated;
use Modules\Academy\Events\AcademyItemCompleted;
use Modules\Academy\Http\Controllers\AcademyController;
use Modules\Academy\Http\Controllers\ExportController;
use Modules\Academy\Listeners\AcademyNewsletterTriggerListener;
use Modules\Academy\Models\Concerns\CourseSearchable;
use Modules\Academy\Models\Course;

// ══ Groupe 1 : Course Searchable ══════════════════════════════════════════

test('Course utilise le trait CourseSearchable', function () {
    $traits = class_uses_recursive(Course::class);
    expect($traits)->toContain(CourseSearchable::class);
});

test('Course possède la méthode toSearchableArray', function () {
    expect(method_exists(Course::class, 'toSearchableArray'))->toBeTrue();
});

test('toSearchableArray contient les champs requis', function () {
    $ref    = new ReflectionClass(Course::class);
    $method = $ref->getMethod('toSearchableArray');
    expect($method->isPublic())->toBeTrue();

    // Vérification statique : lire le code source du trait
    $traitSource = file_get_contents(
        module_path('Academy', 'app/Models/Concerns/CourseSearchable.php')
    );
    expect($traitSource)->toContain("'id'");
    expect($traitSource)->toContain("'title'");
    expect($traitSource)->toContain("'summary'");
    expect($traitSource)->toContain("'level'");
    expect($traitSource)->toContain("'slug'");
    expect($traitSource)->toContain("'indexed_at'");
});

test('shouldBeSearchable existe et retourne bool', function () {
    $ref    = new ReflectionClass(Course::class);
    $method = $ref->getMethod('shouldBeSearchable');
    expect($method->isPublic())->toBeTrue();
    expect((string) $method->getReturnType())->toBe('bool');
});

test('shouldBeSearchable conditionne status=published ET visibility=public', function () {
    $source = file_get_contents(module_path('Academy', 'app/Models/Concerns/CourseSearchable.php'));
    expect($source)->toContain("status === 'published'");
    expect($source)->toContain("visibility === 'public'");
});

test('searchableAs retourne academy_courses', function () {
    $source = file_get_contents(module_path('Academy', 'app/Models/Concerns/CourseSearchable.php'));
    expect($source)->toContain("'academy_courses'");
});

test('Course utilise Laravel Scout Searchable', function () {
    $traits = class_uses_recursive(Course::class);
    expect($traits)->toContain(\Laravel\Scout\Searchable::class);
});

// ══ Groupe 2 : CourseReindexCommand ═══════════════════════════════════════

test('CourseReindexCommand existe', function () {
    expect(class_exists(CourseReindexCommand::class))->toBeTrue();
});

test('CourseReindexCommand signature est academy:reindex', function () {
    $cmd = new CourseReindexCommand();
    expect($cmd->getName())->toBe('academy:reindex');
});

test('CourseReindexCommand est défensif (source contient class_exists Scout)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Console/CourseReindexCommand.php')
    );
    expect($source)->toContain('class_exists');
    expect($source)->toContain('Scout');
});

// ══ Groupe 3 : AcademyController — recherche ══════════════════════════════

test('AcademyController::index accepte le paramètre q', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Http/Controllers/AcademyController.php')
    );
    expect($source)->toContain("input('q'");
    expect($source)->toContain('currentSearch');
});

test('AcademyController a un fallback SQL LIKE', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Http/Controllers/AcademyController.php')
    );
    expect($source)->toContain('LIKE');
    expect($source)->toContain('buildFallbackQuery');
});

test('AcademyController enveloppe Scout dans try/catch', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Http/Controllers/AcademyController.php')
    );
    expect($source)->toContain('try {');
    expect($source)->toContain('catch (Throwable');
});

// ══ Groupe 4 : ExportController ═══════════════════════════════════════════

test('ExportController existe', function () {
    expect(class_exists(ExportController::class))->toBeTrue();
});

test('ExportController a les 3 méthodes export', function () {
    $ref = new ReflectionClass(ExportController::class);
    expect($ref->hasMethod('exportEnrollments'))->toBeTrue();
    expect($ref->hasMethod('exportCompletions'))->toBeTrue();
    expect($ref->hasMethod('exportProgress'))->toBeTrue();
});

test('ExportController vérifie la permission academy.reports.view', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Http/Controllers/ExportController.php')
    );
    expect($source)->toContain("academy.reports.view");
    expect($source)->toContain('abort(403)');
});

test('ExportController utilise fputcsv et BOM UTF-8', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Http/Controllers/ExportController.php')
    );
    expect($source)->toContain('fputcsv');
    expect($source)->toContain('\xEF\xBB\xBF');
    expect($source)->toContain('streamDownload');
    expect($source)->toContain('cursor()');
});

test('ExportController progress utilise le champ percent (pas percentage)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Http/Controllers/ExportController.php')
    );
    expect($source)->toContain('$progress->percent');
    expect($source)->not->toContain('$progress->percentage');
});

// ══ Groupe 5 : Events M7 ══════════════════════════════════════════════════

test('AcademyEnrollmentCreated existe', function () {
    expect(class_exists(AcademyEnrollmentCreated::class))->toBeTrue();
});

test('AcademyEnrollmentCreated a les propriétés user/course/enrollment', function () {
    $ref = new ReflectionClass(AcademyEnrollmentCreated::class);
    expect($ref->hasProperty('user'))->toBeTrue();
    expect($ref->hasProperty('course'))->toBeTrue();
    expect($ref->hasProperty('enrollment'))->toBeTrue();
});

test('AcademyItemCompleted existe', function () {
    expect(class_exists(AcademyItemCompleted::class))->toBeTrue();
});

test('AcademyItemCompleted a les propriétés user/course/completion', function () {
    $ref = new ReflectionClass(AcademyItemCompleted::class);
    expect($ref->hasProperty('user'))->toBeTrue();
    expect($ref->hasProperty('course'))->toBeTrue();
    expect($ref->hasProperty('completion'))->toBeTrue();
});

test('AcademyCertificateIssued existe', function () {
    expect(class_exists(AcademyCertificateIssued::class))->toBeTrue();
});

test('AcademyCertificateIssued a les propriétés user/course/certificate', function () {
    $ref = new ReflectionClass(AcademyCertificateIssued::class);
    expect($ref->hasProperty('user'))->toBeTrue();
    expect($ref->hasProperty('course'))->toBeTrue();
    expect($ref->hasProperty('certificate'))->toBeTrue();
});

test('les events utilisent Dispatchable et SerializesModels', function () {
    foreach ([AcademyEnrollmentCreated::class, AcademyItemCompleted::class, AcademyCertificateIssued::class] as $eventClass) {
        // Vérification via code source (plus fiable que class_uses_recursive sur les traits internes Laravel)
        $basename = (new ReflectionClass($eventClass))->getFileName();
        $source   = file_get_contents($basename);
        expect(str_contains($source, 'Dispatchable'))->toBeTrue();
        expect(str_contains($source, 'SerializesModels'))->toBeTrue();
    }
});

// ══ Groupe 6 : AcademyNewsletterTriggerListener — défensif ════════════════

test('AcademyNewsletterTriggerListener existe', function () {
    expect(class_exists(AcademyNewsletterTriggerListener::class))->toBeTrue();
});

test('AcademyNewsletterTriggerListener a les 3 méthodes handle', function () {
    $ref = new ReflectionClass(AcademyNewsletterTriggerListener::class);
    expect($ref->hasMethod('handleEnrollment'))->toBeTrue();
    expect($ref->hasMethod('handleCompletion'))->toBeTrue();
    expect($ref->hasMethod('handleCertificate'))->toBeTrue();
});

test('AcademyNewsletterTriggerListener est défensif (module_enabled + class_exists)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Listeners/AcademyNewsletterTriggerListener.php')
    );
    expect($source)->toContain('module_enabled');
    expect($source)->toContain('class_exists');
    expect($source)->toContain('catch (Throwable');
    expect($source)->toContain('Log::error');
});

test('AcademyNewsletterTriggerListener utilise les bons trigger_types', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Listeners/AcademyNewsletterTriggerListener.php')
    );
    expect($source)->toContain('academy_enrolled');
    expect($source)->toContain('academy_item_completed');
    expect($source)->toContain('academy_certificate_issued');
});

// ══ Groupe 7 : Routes M7 ══════════════════════════════════════════════════

test('routes admin export enrollments est déclarée', function () {
    expect(Route::has('academy.admin.export.enrollments'))->toBeTrue();
});

test('routes admin export completions est déclarée', function () {
    expect(Route::has('academy.admin.export.completions'))->toBeTrue();
});

test('routes admin export progress est déclarée', function () {
    expect(Route::has('academy.admin.export.progress'))->toBeTrue();
});

test('vue /academie contient un formulaire de recherche', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/index.blade.php')
    );
    expect($source)->toContain('name="q"');
    expect($source)->toContain('currentSearch');
});
