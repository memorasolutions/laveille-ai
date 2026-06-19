<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests M3 — Lecteur de leçon + protection vidéo (jalon sécurité)
 *
 * Stratégie : tests structurels (analyse du code source) qui n'utilisent pas
 * RefreshDatabase (incompatible avec JSON_UNQUOTE SQLite dans ce projet).
 * Ces tests vérifient que le code implémente correctement le gating.
 *
 * Pour des tests HTTP end-to-end, utiliser la suite Browser/Playwright.
 *
 * Garde-fou M8 : si le module Academy est désactivé dans modules_statuses.json,
 * tous les tests de ce fichier sont marqués SKIPPED (suite toujours verte).
 */

declare(strict_types=1);

uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// Garde-fou M8 : passer tous les tests si le module Academy est désactivé.
beforeEach(fn () => test()->skipIfAcademyDisabled());

// ══ Groupe 1 : LessonController — structure et sécurité ═══════════════════

test('LessonController existe dans le module Academy', function () {
    expect(class_exists(\Modules\Academy\Http\Controllers\LessonController::class))->toBeTrue();
});

test('LessonController possède la méthode show', function () {
    expect(method_exists(\Modules\Academy\Http\Controllers\LessonController::class, 'show'))->toBeTrue();
});

test('LessonController::show retourne une View (type-hint correct)', function () {
    $reflection = new ReflectionMethod(\Modules\Academy\Http\Controllers\LessonController::class, 'show');
    $returnType = $reflection->getReturnType()?->getName();
    // \Illuminate\View\View ou \Illuminate\Contracts\View\View
    expect($returnType)->toContain('View');
});

test('LessonController vérifie que le cours est publié (abort 404 si draft)', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Http/Controllers/LessonController.php')
    );
    // Doit tester status === 'published' et visibility === 'public'
    expect($source)->toContain("status !== 'published'");
    expect($source)->toContain("visibility !== 'public'");
    expect($source)->toContain('abort(404)');
});

test('LessonController vérifie que la leçon appartient au cours (abort 404 si non)', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Http/Controllers/LessonController.php')
    );
    // Doit valider l'appartenance de la leçon au cours
    expect($source)->toContain('belongsToCourse');
    expect($source)->toContain('abort(404)');
});

test('LessonController vérifie l inscription active (status active)', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Http/Controllers/LessonController.php')
    );
    // Doit interroger Enrollment avec status = active
    expect($source)->toContain("'status', 'active'");
    expect($source)->toContain('isEnrolled');
});

// ══ Groupe 2 : Vue lesson.blade — gating vidéo (pas de fuite URL) ═══════════

test('la vue lesson.blade.php existe', function () {
    $path = base_path('Modules/Academy/resources/views/public/lesson.blade.php');
    expect(file_exists($path))->toBeTrue();
});

test('la vue lesson vérifie $hasAccess AVANT d injecter player_url dans le DOM', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/public/lesson.blade.php')
    );

    // La condition $hasAccess doit entourer le rendu de l'iframe
    expect($source)->toContain('$hasAccess');

    // L'URL player_url est dans le composant video-player (conditionnel)
    // Vérifier que l'accès conditionnel précède le composant
    $hasAccessPos   = strpos($source, '$hasAccess');
    $playerUrlPos   = strpos($source, 'player_url');
    expect($hasAccessPos)->toBeLessThan($playerUrlPos,
        'La condition $hasAccess doit apparaître AVANT la référence à player_url'
    );
});

test('la vue lesson affiche un CTA login quand l accès est refusé (pas d iframe)', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/public/lesson.blade.php')
    );
    // Le panneau de gating doit proposer la connexion
    expect($source)->toContain('Connexion requise');
    expect($source)->toContain('academy-gated-panel');
});

test('la vue lesson n inclut PAS de player_url hors condition $hasAccess', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/public/lesson.blade.php')
    );

    // Trouver le bloc @if($hasAccess ...) ... @else ...
    // Compter les occurrences de player_url : toutes doivent être dans le bloc conditionnel
    preg_match_all('/\$item->payload\[.player_url.\]/', $source, $matches);
    $totalOccurrences = count($matches[0]);

    // Toutes les références player_url doivent être dans un bloc conditionnel avec hasAccess
    // Vérification : player_url ne doit PAS apparaître dans le bloc @else (panneau de gating)
    // Pattern : extraire ce qui est après le dernier @else et avant @endif
    $elseBlocks = [];
    preg_match_all('/@else\b(.*?)@endif/s', $source, $elseMatches);
    foreach ($elseMatches[1] as $elseBlock) {
        if (str_contains($elseBlock, 'player_url')) {
            $elseBlocks[] = $elseBlock;
        }
    }

    expect(count($elseBlocks))->toBe(0,
        'player_url ne doit JAMAIS apparaître dans un bloc @else (risque de fuite DOM)'
    );
});

// ══ Groupe 3 : Composant video-player — filigrane et sécurité ═══════════════

test('le composant video-player.blade.php existe', function () {
    $path = base_path('Modules/Academy/resources/views/components/video-player.blade.php');
    expect(file_exists($path))->toBeTrue();
});

test('le composant video-player contient un overlay filigrane aria-hidden', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/components/video-player.blade.php')
    );
    expect($source)->toContain('aria-hidden="true"');
    expect($source)->toContain('academy-watermark');
});

test('le composant video-player a pointer-events:none sur l overlay', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/components/video-player.blade.php')
    );
    expect($source)->toContain('pointer-events: none');
});

test('le composant video-player affiche le nom ou email de l utilisateur dans le filigrane', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/components/video-player.blade.php')
    );
    // Le nom/email est utilisé pour construire le filigrane
    expect($source)->toContain('auth()->user()');
    expect($source)->toContain('name');
});

test('le composant video-player utilise sandbox sur l iframe', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/components/video-player.blade.php')
    );
    expect($source)->toContain('sandbox=');
});

test('le composant video-player a un attribut title pour l accessibilité', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/components/video-player.blade.php')
    );
    expect($source)->toContain('title=');
});

// ══ Groupe 4 : Middleware CSP Academy ════════════════════════════════════════

test('le middleware AcademyCsp existe', function () {
    expect(class_exists(\Modules\Academy\Http\Middleware\AcademyCsp::class))->toBeTrue();
});

test('le middleware AcademyCsp possède la méthode handle', function () {
    expect(method_exists(\Modules\Academy\Http\Middleware\AcademyCsp::class, 'handle'))->toBeTrue();
});

test('le middleware AcademyCsp émet frame-ancestors dans la CSP', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Http/Middleware/AcademyCsp.php')
    );
    expect($source)->toContain('frame-ancestors');
    expect($source)->toContain('Content-Security-Policy');
});

test('le middleware AcademyCsp émet frame-src avec l hôte vidéo configuré', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Http/Middleware/AcademyCsp.php')
    );
    expect($source)->toContain('frame-src');
    // L'hôte vient de la config (pas hardcodé)
    expect($source)->toContain("config('academy.video_embed_host'");
});

test('le middleware AcademyCsp est distinct du middleware CSP global Core', function () {
    // AcademyCsp et Core/ContentSecurityPolicy sont deux classes différentes
    expect(\Modules\Academy\Http\Middleware\AcademyCsp::class)
        ->not->toBe(\Modules\Core\Http\Middleware\ContentSecurityPolicy::class);
});

// ══ Groupe 5 : Routes et configuration ════════════════════════════════════════

test('la route academy.lessons.show est déclarée dans web.php', function () {
    $source = file_get_contents(base_path('Modules/Academy/routes/web.php'));
    // Le nom complet est academy.lessons.show via le préfixe name('academy.') + ->name('lessons.show')
    expect($source)->toContain("->name('lessons.show')");
    expect($source)->toContain('LessonController');
});

test('le middleware AcademyCsp est appliqué au groupe de routes Academy', function () {
    $source = file_get_contents(base_path('Modules/Academy/routes/web.php'));
    expect($source)->toContain('AcademyCsp');
});

test('la config academy a la clé video_embed_host', function () {
    $config = require base_path('Modules/Academy/config/config.php');
    expect($config)->toHaveKey('video_embed_host');
    expect($config['video_embed_host'])->toContain('screenpal.com');
});

test('la config academy a la clé site_host', function () {
    $config = require base_path('Modules/Academy/config/config.php');
    expect($config)->toHaveKey('site_host');
});

// ══ Groupe 6 : AcademyServiceProvider — composants anonymes enregistrés ══════

test('AcademyServiceProvider enregistre les composants Blade anonymes', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Providers/AcademyServiceProvider.php')
    );
    expect($source)->toContain('anonymousComponentPath');
    // Le chemin inclut /components pour cibler le dossier des composants anonymes
    expect($source)->toContain('/components');
});
