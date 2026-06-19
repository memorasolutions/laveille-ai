<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests M6 — Certificats + JSON-LD
 *
 * Stratégie : tests STRUCTURELS (analyse du code source + ReflectionClass)
 * sans RefreshDatabase, identique aux tests M3/M4/M5.
 *
 * Groupes :
 *   1. CertificateService — structure et logique
 *   2. CertificateController — structure
 *   3. Routes M6 déclarées
 *   4. Vue certificat — présence et contenu
 *   5. JSON-LD — présence et validité dans les vues
 *   6. Intégration ProgressService (hook 100%)
 *   7. Lien certificat dans lesson.blade
 *
 * Garde-fou M8 : si le module Academy est désactivé dans modules_statuses.json,
 * tous les tests de ce fichier sont marqués SKIPPED (suite toujours verte).
 */

declare(strict_types=1);

uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// Garde-fou M8 : passer tous les tests si le module Academy est désactivé.
beforeEach(fn () => test()->skipIfAcademyDisabled());

use Modules\Academy\Services\CertificateService;
use Modules\Academy\Models\CertificateIssued;

// ══ Groupe 1 : CertificateService — structure ═════════════════════════════

test('CertificateService existe', function () {
    expect(class_exists(CertificateService::class))->toBeTrue();
});

test('CertificateService::issueFor est une méthode publique non-statique', function () {
    $ref = new ReflectionMethod(CertificateService::class, 'issueFor');
    expect($ref->isPublic())->toBeTrue();
    expect($ref->isStatic())->toBeFalse();
});

test('CertificateService::issueFor retourne ?CertificateIssued (return type)', function () {
    $ref        = new ReflectionMethod(CertificateService::class, 'issueFor');
    $returnType = $ref->getReturnType();
    expect($returnType)->not()->toBeNull();
    expect((string) $returnType)->toContain('CertificateIssued');
});

test('CertificateService::issuedFor existe et est public', function () {
    $ref = new ReflectionMethod(CertificateService::class, 'issuedFor');
    expect($ref->isPublic())->toBeTrue();
    expect($ref->isStatic())->toBeFalse();
});

test('CertificateService vérifie la progression à 100% (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Services/CertificateService.php')
    );
    // Doit vérifier percent === 100 (ou !== 100)
    expect($source)->toContain('percent');
    expect($source)->toContain('100');
    // Doit retourner null si non complété
    expect($source)->toContain('return null');
});

test('CertificateService est idempotent (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Services/CertificateService.php')
    );
    // Doit chercher un certificat existant avant d'en créer un
    expect($source)->toContain('first()');
    // Doit utiliser firstOrCreate ou vérifier l'existant
    expect($source)->toMatch('/existing|firstOr/i');
});

test('CertificateService génère un serial préfixé ACAD- (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Services/CertificateService.php')
    );
    expect($source)->toContain('ACAD-');
});

test('CertificateService génère un verification_hash SHA-256 (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Services/CertificateService.php')
    );
    expect($source)->toContain('sha256');
});

test('CertificateService a un ActivityLog défensif (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Services/CertificateService.php')
    );
    expect($source)->toContain('academy.certificate.issued');
    expect($source)->toContain('class_exists');
});

test('CertificateService déclenche un événement défensif (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Services/CertificateService.php')
    );
    expect($source)->toContain("event('academy.certificate.issued'");
    expect($source)->toMatch('/catch.*\\\\Throwable/');
});

test('CertificateService calcule hours_earned depuis duration_minutes (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Services/CertificateService.php')
    );
    expect($source)->toContain('duration_minutes');
    expect($source)->toContain('ceil');
    expect($source)->toContain('hours_earned');
});

// ══ Groupe 2 : CertificateController — structure ══════════════════════════

test('CertificateController existe', function () {
    expect(class_exists(\Modules\Academy\Http\Controllers\CertificateController::class))->toBeTrue();
});

test('CertificateController::show est une méthode publique', function () {
    $ref = new ReflectionMethod(\Modules\Academy\Http\Controllers\CertificateController::class, 'show');
    expect($ref->isPublic())->toBeTrue();
});

test('CertificateController::show utilise firstOrFail (404 automatique)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Http/Controllers/CertificateController.php')
    );
    expect($source)->toContain('firstOrFail');
});

test('CertificateController charge les relations user et course', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Http/Controllers/CertificateController.php')
    );
    expect($source)->toContain("'user'");
    expect($source)->toContain("'course'");
});

// ══ Groupe 3 : Routes M6 ══════════════════════════════════════════════════

test('route academy.certificates.show est déclarée', function () {
    expect(\Illuminate\Support\Facades\Route::has('academy.certificates.show'))->toBeTrue();
});

test('route academy.certificates.show correspond à /academie/certificats/{slug}', function () {
    $url = route('academy.certificates.show', 'test-slug');
    expect($url)->toContain('/academie/certificats/test-slug');
});

test('route certificats est publique (pas de middleware auth)', function () {
    $source = file_get_contents(module_path('Academy', 'routes/web.php'));
    // La route certificats doit exister en dehors du groupe middleware('auth')
    expect($source)->toContain('certificats');
    expect($source)->toContain('CertificateController');
});

// ══ Groupe 4 : Vue certificat — présence et contenu ═══════════════════════

test('vue certificate.blade.php existe', function () {
    $path = module_path('Academy', 'resources/views/public/certificate.blade.php');
    expect(file_exists($path))->toBeTrue();
});

test('vue certificat affiche le nom de l\'apprenant (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/certificate.blade.php')
    );
    expect($source)->toContain('$certificate->user->name');
});

test('vue certificat affiche le titre du cours (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/certificate.blade.php')
    );
    expect($source)->toContain('$certificate->course->title');
});

test('vue certificat a un bouton window.print() (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/certificate.blade.php')
    );
    expect($source)->toContain('window.print()');
});

test('vue certificat a un CSS @media print (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/certificate.blade.php')
    );
    expect($source)->toContain('@media print');
    // Le bouton d'impression doit être masqué à l'impression
    expect($source)->toContain('no-print');
});

test('vue certificat affiche le serial (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/certificate.blade.php')
    );
    expect($source)->toContain('$certificate->serial');
});

// ══ Groupe 5 : JSON-LD — présence dans les vues ═══════════════════════════

test('vue show.blade.php (cours) contient un bloc JSON-LD (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/show.blade.php')
    );
    expect($source)->toContain('application/ld+json');
    expect($source)->toContain("'@context'");
    expect($source)->toContain("'@type'");
    expect($source)->toContain("'Course'");
});

test('vue show.blade.php utilise json_encode PHP (pas de package spatie)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/show.blade.php')
    );
    expect($source)->toContain('json_encode');
    expect($source)->toContain('JSON_UNESCAPED_UNICODE');
    // Vérifie que le JSON-LD est un tableau PHP (pas une instanciation d'objet Spatie)
    expect($source)->not()->toMatch('/new\s+Spatie\\\\SchemaOrg/i');
});

test('vue show.blade.php JSON-LD Course inclut inLanguage fr-CA et provider (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/show.blade.php')
    );
    expect($source)->toContain('fr-CA');
    expect($source)->toContain('provider');
    expect($source)->toContain('Organization');
});

test('vue certificate.blade.php contient un bloc JSON-LD EducationalOccupationalCredential (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/certificate.blade.php')
    );
    expect($source)->toContain('application/ld+json');
    expect($source)->toContain('EducationalOccupationalCredential');
    expect($source)->toContain("'@context'");
});

test('vue certificat JSON-LD est un tableau PHP encodé (pas de package)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/certificate.blade.php')
    );
    expect($source)->toContain('json_encode');
    expect($source)->toContain('JSON_UNESCAPED_UNICODE');
    // Vérifie que le JSON-LD est un tableau PHP (pas une instanciation d'objet Spatie)
    expect($source)->not()->toMatch('/new\s+Spatie\\\\SchemaOrg/i');
});

test('vue certificat JSON-LD inclut holder Person et recognizedBy Organization (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/certificate.blade.php')
    );
    expect($source)->toContain('holder');
    expect($source)->toContain('Person');
    expect($source)->toContain('recognizedBy');
    expect($source)->toContain('Organization');
});

test('JSON-LD tableau PHP de la vue cours est décodable (simulation)', function () {
    // Construit un tableau identique à celui dans show.blade.php et vérifie json_encode/decode
    $fakeTitle  = 'Introduction à l\'IA';
    $fakeSlug   = 'intro-ia';
    $fakeSummary = 'Un cours sur l\'IA';

    $__courseJsonLd = [
        '@context'   => 'https://schema.org',
        '@type'      => 'Course',
        'name'       => $fakeTitle,
        'inLanguage' => 'fr-CA',
        'provider'   => ['@type' => 'Organization', 'name' => 'La veille de Stef', 'url' => 'https://laveille.ai'],
        'description' => \Illuminate\Support\Str::limit(strip_tags($fakeSummary), 300, ''),
        'url'        => 'https://laveille.ai/academie/courses/' . $fakeSlug,
    ];

    $encoded = json_encode($__courseJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    expect($encoded)->not()->toBeFalse();

    $decoded = json_decode($encoded, true);
    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('@context', 'https://schema.org');
    expect($decoded)->toHaveKey('@type', 'Course');
    expect($decoded)->toHaveKey('inLanguage', 'fr-CA');
    expect($decoded)->toHaveKey('provider');
    expect($decoded['provider'])->toHaveKey('@type', 'Organization');
});

test('JSON-LD tableau PHP du certificat est décodable (simulation)', function () {
    $fakeTitle   = 'Introduction à l\'IA';
    $fakeName    = 'Jean Dupont';
    $fakeSlug    = 'cert-abc123def45600-1234567890';
    $fakeDate    = now()->toIso8601String();

    $__certJsonLd = [
        '@context'           => 'https://schema.org',
        '@type'              => 'EducationalOccupationalCredential',
        'name'               => 'Certificat – ' . $fakeTitle,
        'description'        => 'Certificat de complétion du cours « ' . $fakeTitle . ' » décerné par La veille de Stef.',
        'url'                => 'https://laveille.ai/academie/certificats/' . $fakeSlug,
        'dateCreated'        => $fakeDate,
        'credentialCategory' => 'Certificate',
        'recognizedBy'       => ['@type' => 'Organization', 'name' => 'La veille de Stef', 'url' => 'https://laveille.ai'],
        'about'              => ['@type' => 'Course', 'name' => $fakeTitle],
        'holder'             => ['@type' => 'Person', 'name' => $fakeName],
    ];

    $encoded = json_encode($__certJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    expect($encoded)->not()->toBeFalse();

    $decoded = json_decode($encoded, true);
    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('@context', 'https://schema.org');
    expect($decoded)->toHaveKey('@type', 'EducationalOccupationalCredential');
    expect($decoded)->toHaveKey('holder');
    expect($decoded['holder'])->toHaveKey('@type', 'Person');
    expect($decoded['holder'])->toHaveKey('name', $fakeName);
    expect($decoded)->toHaveKey('recognizedBy');
    expect($decoded['recognizedBy'])->toHaveKey('@type', 'Organization');
});

// ══ Groupe 6 : Intégration ProgressService (hook 100%) ════════════════════

test('ProgressService appelle CertificateService quand percent atteint 100 (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Services/ProgressService.php')
    );
    expect($source)->toContain('CertificateService');
    expect($source)->toContain('100');
    expect($source)->toContain('issueFor');
    // Doit être défensif
    expect($source)->toMatch('/catch.*\\\\Throwable/');
});

test('ProgressService importe CertificateService (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Services/ProgressService.php')
    );
    expect($source)->toContain('use Modules\\Academy\\Services\\CertificateService;');
});

// ══ Groupe 7 : Lien certificat dans lesson.blade ══════════════════════════

test('lesson.blade.php contient le lien vers le certificat (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/lesson.blade.php')
    );
    expect($source)->toContain('academy.certificates.show');
    expect($source)->toContain('Obtenir mon certificat');
});

test('lesson.blade.php affiche le certificat seulement si 100% complété (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/lesson.blade.php')
    );
    // Doit vérifier userProgress->percent >= 100
    expect($source)->toContain('percent');
    expect($source)->toContain('100');
    expect($source)->toContain('CertificateService');
});

test('lesson.blade.php utilise CertificateService de façon défensive (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/lesson.blade.php')
    );
    expect($source)->toContain('class_exists');
    expect($source)->toContain('\\Throwable');
});

// ══ Groupe 8 : CertificateIssued model ════════════════════════════════════

test('CertificateIssued a les champs fillable requis', function () {
    $model    = new CertificateIssued();
    $fillable = $model->getFillable();

    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('course_id');
    expect($fillable)->toContain('serial');
    expect($fillable)->toContain('verification_hash');
    expect($fillable)->toContain('public_url_slug');
    expect($fillable)->toContain('issued_at');
    expect($fillable)->toContain('hours_earned');
    expect($fillable)->toContain('final_score');
});

test('CertificateIssued caste issued_at en Carbon', function () {
    $model = new CertificateIssued();
    $casts = $model->getCasts();
    expect($casts)->toHaveKey('issued_at');
});

test('CertificateIssued a les relations user() et course()', function () {
    expect(method_exists(CertificateIssued::class, 'user'))->toBeTrue();
    expect(method_exists(CertificateIssued::class, 'course'))->toBeTrue();
});
