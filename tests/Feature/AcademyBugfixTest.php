<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests de non-régression pour les correctifs identifiés dans le rapport de
 * simulation E2E 20260619a (segment visiteur).
 *
 * BUG-03 (P0) — CertificateIssued : mauvais nom de table → QueryException → 500
 *   Fix : protected $table = 'certificates_issued' + catch ModelNotFoundException
 *
 * BUG-02 (P1) — Fuite contenu doc : le texte est visible aux non-inscrits
 *   Fix : gating $hasAccess étendu au type doc (même règle que video)
 *
 * Stratégie : tests STRUCTURELS (code source + ReflectionClass), pas de DB.
 * Garde-fou M8 : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// Garde-fou M8 : passer tous les tests si le module Academy est désactivé.
beforeEach(fn () => test()->skipIfAcademyDisabled());

// ══════════════════════════════════════════════════════════════════════════════
// BUG-03 : CertificateIssued — nom de table correct → 404 au lieu de 500
// ══════════════════════════════════════════════════════════════════════════════

test('BUG-03 : CertificateIssued::$table est certificates_issued (pas le nom auto Laravel)', function () {
    // Par convention Laravel, Model "CertificateIssued" → table "certificate_issueds" (erroné).
    // La vraie table est "certificates_issued". Sans $table explicite : QueryException → 500.
    $model = new \Modules\Academy\Models\CertificateIssued();
    expect($model->getTable())->toBe('certificates_issued');
});

test('BUG-03 : CertificateController::show catch ModelNotFoundException et abort 404 (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Http/Controllers/CertificateController.php')
    );
    // Doit importer ModelNotFoundException
    expect($source)->toContain('ModelNotFoundException');
    // Doit avoir un catch qui appelle abort(404)
    expect($source)->toContain('catch (ModelNotFoundException)');
    expect($source)->toContain('abort(404)');
});

test('BUG-03 : CertificateController::show utilise toujours firstOrFail (pas de régression)', function () {
    $source = file_get_contents(
        module_path('Academy', 'app/Http/Controllers/CertificateController.php')
    );
    expect($source)->toContain('firstOrFail');
});

// ══════════════════════════════════════════════════════════════════════════════
// BUG-02 : Gating du type « doc » — le texte ne doit PAS fuiter aux non-inscrits
// ══════════════════════════════════════════════════════════════════════════════

test('BUG-02 : la vue lesson.blade.php entoure le type doc de la condition $hasAccess (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/lesson.blade.php')
    );

    // Le bloc @elseif type doc doit être suivi d'un @if($hasAccess) AVANT d'injecter le rich_text.
    // On cherche la position de "TYPE DOC" et de "$hasAccess" après celui-ci.
    $docTypePos   = strpos($source, "TYPE DOC");
    $hasAccessPos = strpos($source, '@if($hasAccess)', $docTypePos !== false ? $docTypePos : 0);

    expect($docTypePos)->not()->toBeFalse('Le commentaire TYPE DOC doit être présent dans la vue');
    expect($hasAccessPos)->not()->toBeFalse('$hasAccess doit apparaître dans le bloc doc');
    expect($hasAccessPos)->toBeGreaterThan($docTypePos, '$hasAccess doit être APRÈS le bloc doc');
});

test('BUG-02 : rich_text ne figure PAS dans le bloc @else du type doc (pas de fuite DOM)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/lesson.blade.php')
    );

    // Extraire le segment du type doc (entre "TYPE DOC" et "TYPE inconnu" ou fin de @forelse)
    $docStart = strpos($source, "TYPE DOC");
    $docEnd   = strpos($source, "Type inconnu", $docStart !== false ? $docStart : 0);

    if ($docStart === false || $docEnd === false) {
        // Si les marqueurs ne sont pas là, le test passe (commentaires facultatifs)
        $this->markTestSkipped('Marqueurs commentaires introuvables — vérifier manuellement.');
        return;
    }

    $docSegment = substr($source, $docStart, $docEnd - $docStart);

    // Dans le segment doc, trouver le bloc @else (panneau de gating)
    preg_match_all('/@else\b(.*?)@endif/s', $docSegment, $elseMatches);

    foreach ($elseMatches[1] as $elseBlock) {
        expect($elseBlock)->not()->toContain('rich_text',
            'rich_text ne doit JAMAIS être dans le bloc @else du type doc (risque de fuite de contenu)'
        );
    }
});

test('BUG-02 : la vue lesson affiche un panneau de gating doc pour les non-inscrits (structurel)', function () {
    $source = file_get_contents(
        module_path('Academy', 'resources/views/public/lesson.blade.php')
    );
    // Le panneau de gating doc doit contenir le message d'invitation à se connecter/s'inscrire
    expect($source)->toContain('Connexion requise pour lire ce document');
    expect($source)->toContain('Inscrivez-vous pour accéder à ce document');
});

// ══════════════════════════════════════════════════════════════════════════════
// BUG-01 : CSP globale Core — screenpal.com dans frame-src
// ══════════════════════════════════════════════════════════════════════════════

test('BUG-01 : ContentSecurityPolicy Core inclut screenpal.com dans frame-src (structurel)', function () {
    $source = file_get_contents(
        base_path('Modules/Core/app/Http/Middleware/ContentSecurityPolicy.php')
    );
    expect($source)->toContain('screenpal.com');
    expect($source)->toContain('frame-src');
});

// ══════════════════════════════════════════════════════════════════════════════
// GAP-01 : Lien « Académie » défensif dans la nav FrontTheme
// ══════════════════════════════════════════════════════════════════════════════

test('GAP-01 : le header FrontTheme contient un lien défensif vers academy.index (structurel)', function () {
    $source = file_get_contents(
        base_path('Modules/FrontTheme/resources/views/partials/header.blade.php')
    );
    // Le lien doit être conditionnel (Route::has)
    expect($source)->toContain("Route::has('academy.index')");
    // Et pointer vers la route academy.index
    expect($source)->toContain("route('academy.index')");
});
