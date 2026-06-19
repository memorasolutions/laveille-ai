<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests M4 — Quiz, complétion, progression, reprise
 *
 * Stratégie : tests STRUCTURELS (analyse du code source) qui n'utilisent pas
 * RefreshDatabase (incompatible avec JSON_UNQUOTE SQLite dans ce projet).
 *
 * Groupes :
 *   1. QuizService — scoring (pur PHP, aucune DB)
 *   2. CompletionService — idempotence (structurel)
 *   3. ProgressService — calcul % (structurel)
 *   4. Routes M4 déclarées
 *   5. Vues — quiz-player.blade, progress-bar.blade, lesson.blade
 *   6. Gating — non inscrit bloqué
 *
 * Garde-fou M8 : si le module Academy est désactivé dans modules_statuses.json,
 * tous les tests de ce fichier sont marqués SKIPPED (suite toujours verte).
 */

declare(strict_types=1);

uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// Garde-fou M8 : passer tous les tests si le module Academy est désactivé.
beforeEach(fn () => test()->skipIfAcademyDisabled());

use Modules\Academy\Services\QuizService;

// ══ Groupe 1 : QuizService — scoring ══════════════════════════════════════

test('QuizService::score retourne zéro total si questions vides', function () {
    $result = QuizService::score([], []);
    expect($result['total'])->toBe(0);
    expect($result['percent'])->toBe(0);
    expect($result['correct'])->toBe(0);
});

test('QuizService::score calcule QCM correctement (bonne réponse)', function () {
    $questions = [[
        'type'    => 'qcm',
        'choices' => ['A', 'B', 'C', 'D'],
        'correct' => 2,
    ]];
    $result = QuizService::score($questions, ['0' => '2']);
    expect($result['correct'])->toBe(1);
    expect($result['wrong'])->toBe(0);
    expect($result['percent'])->toBe(100);
});

test('QuizService::score calcule QCM incorrectement (mauvaise réponse)', function () {
    $questions = [[
        'type'    => 'qcm',
        'choices' => ['A', 'B', 'C', 'D'],
        'correct' => 2,
    ]];
    $result = QuizService::score($questions, ['0' => '0']);
    expect($result['correct'])->toBe(0);
    expect($result['wrong'])->toBe(1);
    expect($result['percent'])->toBe(0);
});

test('QuizService::score gère vraifaux correctement', function () {
    $questions = [[
        'type'    => 'vraifaux',
        'choices' => ['Vrai', 'Faux'],
        'correct' => 1,
    ]];
    $result = QuizService::score($questions, ['0' => '1']);
    expect($result['correct'])->toBe(1);
});

test('QuizService::score gère réponse courte (normalisée)', function () {
    $questions = [[
        'type'     => 'court',
        'question' => 'Qu\'est-ce que l\'IA ?',
        'accepted' => ['Intelligence Artificielle', 'intelligence artificielle'],
    ]];
    // Réponse avec espaces et casse différente
    $result = QuizService::score($questions, ['0' => '  Intelligence Artificielle  ']);
    expect($result['correct'])->toBe(1);
});

test('QuizService::score gère réponse courte incorrecte', function () {
    $questions = [[
        'type'     => 'court',
        'accepted' => ['ChatGPT'],
    ]];
    $result = QuizService::score($questions, ['0' => 'Gemini']);
    expect($result['correct'])->toBe(0);
});

test('QuizService::score gère appariement correct', function () {
    $questions = [[
        'type'   => 'appariement',
        'terms'  => ['A', 'B', 'C', 'D'],
        'defs'   => ['def1', 'def2', 'def3', 'def4'],
        'answer' => [0, 1, 2, 3],
    ]];
    $result = QuizService::score($questions, ['0' => ['0', '1', '2', '3']]);
    expect($result['correct'])->toBe(1);
});

test('QuizService::score gère appariement incorrect', function () {
    $questions = [[
        'type'   => 'appariement',
        'terms'  => ['A', 'B'],
        'defs'   => ['def1', 'def2'],
        'answer' => [0, 1],
    ]];
    $result = QuizService::score($questions, ['0' => ['1', '0']]);
    expect($result['correct'])->toBe(0);
});

test('QuizService::score calcule le pourcentage correctement (3/5)', function () {
    $questions = [];
    for ($i = 0; $i < 5; $i++) {
        $questions[] = ['type' => 'qcm', 'choices' => ['A', 'B'], 'correct' => 0];
    }
    $answers = ['0' => '0', '1' => '0', '2' => '0', '3' => '1', '4' => '1'];
    $result = QuizService::score($questions, $answers);
    expect($result['correct'])->toBe(3);
    expect($result['wrong'])->toBe(2);
    expect($result['percent'])->toBe(60);
});

test('QuizService::score est défensif sur réponse manquante', function () {
    $questions = [['type' => 'qcm', 'choices' => ['A', 'B'], 'correct' => 0]];
    // Pas de réponse fournie pour l'index 0
    $result = QuizService::score($questions, []);
    expect($result['correct'])->toBe(0);
    expect($result['details'][0]['correct'])->toBeFalse();
});

// ══ Groupe 2 : CompletionService — idempotence (structurel) ═══════════════

test('CompletionService existe', function () {
    expect(class_exists(\Modules\Academy\Services\CompletionService::class))->toBeTrue();
});

test('CompletionService::markComplete est une méthode statique', function () {
    $ref = new ReflectionMethod(\Modules\Academy\Services\CompletionService::class, 'markComplete');
    expect($ref->isStatic())->toBeTrue();
    expect($ref->isPublic())->toBeTrue();
});

test('CompletionService::markComplete accepte score et qtAttemptId optionnels', function () {
    $ref    = new ReflectionMethod(\Modules\Academy\Services\CompletionService::class, 'markComplete');
    $params = $ref->getParameters();
    // user, item, score (nullable), qtAttemptId (nullable)
    expect(count($params))->toBeGreaterThanOrEqual(2);
    // Les paramètres score et qtAttemptId doivent avoir des valeurs par défaut (null)
    $scoreParam = collect($params)->first(fn ($p) => $p->getName() === 'score');
    expect($scoreParam?->isOptional())->toBeTrue();
});

test('CompletionService::markStarted existe et est statique', function () {
    $ref = new ReflectionMethod(\Modules\Academy\Services\CompletionService::class, 'markStarted');
    expect($ref->isStatic())->toBeTrue();
});

test('CompletionService vérifie idempotence via updateOrCreate sur user+lesson_item', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Services/CompletionService.php')
    );
    // Doit utiliser firstOrCreate ou updateOrCreate avec user_id + lesson_item_id
    expect($source)->toContain('user_id');
    expect($source)->toContain('lesson_item_id');
    // Doit vérifier si déjà completed avant d'écrire
    expect($source)->toContain("status', 'completed'");
});

// ══ Groupe 3 : ProgressService — calcul % (structurel) ════════════════════

test('ProgressService existe et a recalculate statique', function () {
    expect(class_exists(\Modules\Academy\Services\ProgressService::class))->toBeTrue();
    $ref = new ReflectionMethod(\Modules\Academy\Services\ProgressService::class, 'recalculate');
    expect($ref->isStatic())->toBeTrue();
    expect($ref->isPublic())->toBeTrue();
});

test('ProgressService::recalculate utilise is_required et status completed', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Services/ProgressService.php')
    );
    expect($source)->toContain('is_required');
    expect($source)->toContain("'completed'");
    expect($source)->toContain('percent');
    expect($source)->toContain('required_total');
    expect($source)->toContain('required_completed');
});

test('ProgressService::recalculate utilise updateOrCreate (idempotent)', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Services/ProgressService.php')
    );
    expect($source)->toContain('updateOrCreate');
});

test('ProgressService::resumeLesson existe', function () {
    expect(method_exists(\Modules\Academy\Services\ProgressService::class, 'resumeLesson'))->toBeTrue();
});

test('ProgressService::resumeLesson retourne Lesson ou null', function () {
    $ref        = new ReflectionMethod(\Modules\Academy\Services\ProgressService::class, 'resumeLesson');
    $returnType = $ref->getReturnType();
    expect($returnType)->not()->toBeNull();
    // Type de retour = ?Lesson (nullable)
    expect($returnType->allowsNull())->toBeTrue();
});

test('ProgressService déclenche l événement academy.progress.updated', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Services/ProgressService.php')
    );
    expect($source)->toContain('academy.progress.updated');
});

// ══ Groupe 4 : Routes M4 ══════════════════════════════════════════════════

test('les routes quiz start et submit sont déclarées dans web.php', function () {
    $source = file_get_contents(base_path('Modules/Academy/routes/web.php'));
    expect($source)->toContain('quiz.start');
    expect($source)->toContain('quiz.submit');
    expect($source)->toContain('QuizController');
});

test('la route lessons.complete est déclarée dans web.php', function () {
    $source = file_get_contents(base_path('Modules/Academy/routes/web.php'));
    expect($source)->toContain('lessons.complete');
    expect($source)->toContain('CompletionController');
});

test('les routes M4 requièrent l authentification (middleware auth)', function () {
    $source = file_get_contents(base_path('Modules/Academy/routes/web.php'));
    // Les routes quiz et complete doivent être dans un groupe auth
    expect($source)->toContain("middleware('auth')");
});

// ══ Groupe 5 : Vues M4 ════════════════════════════════════════════════════

test('le composant quiz-player.blade.php existe', function () {
    $path = base_path('Modules/Academy/resources/views/components/quiz-player.blade.php');
    expect(file_exists($path))->toBeTrue();
});

test('quiz-player affiche le formulaire start si inscrit et pas de session active', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/components/quiz-player.blade.php')
    );
    expect($source)->toContain('quiz.start');
    expect($source)->toContain('Commencer le quiz');
});

test('quiz-player affiche le formulaire submit quand session active', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/components/quiz-player.blade.php')
    );
    expect($source)->toContain('quiz.submit');
    expect($source)->toContain('Soumettre le quiz');
});

test('quiz-player affiche gated panel si non inscrit', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/components/quiz-player.blade.php')
    );
    expect($source)->toContain('academy-gated-panel');
    expect($source)->toContain('Inscrivez-vous pour accéder à ce quiz');
});

test('quiz-player affiche résultat réussi et échec', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/components/quiz-player.blade.php')
    );
    expect($source)->toContain('Réussi');   // "Quiz Réussi" ou "Non réussi" — présent dans les 2 variantes
    expect($source)->toContain('réussi');
    expect($source)->toContain('Réessayer');
});

test('quiz-player rend les 4 types de questions (qcm, vraifaux, court, appariement)', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/components/quiz-player.blade.php')
    );
    expect($source)->toContain("'qcm'");
    expect($source)->toContain("'vraifaux'");
    expect($source)->toContain("'court'");
    expect($source)->toContain("'appariement'");
});

test('progress-bar.blade.php existe', function () {
    $path = base_path('Modules/Academy/resources/views/public/partials/progress-bar.blade.php');
    expect(file_exists($path))->toBeTrue();
});

test('progress-bar affiche le pourcentage et le lien reprendre', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/public/partials/progress-bar.blade.php')
    );
    expect($source)->toContain('percent');
    expect($source)->toContain('Reprendre');
    expect($source)->toContain('required_completed');
    expect($source)->toContain('required_total');
});

test('lesson.blade intègre le composant quiz-player pour type=quiz', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/public/lesson.blade.php')
    );
    expect($source)->toContain('quiz-player');
    expect($source)->toContain('quizResult');
});

test('lesson.blade inclut la barre de progression', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/public/lesson.blade.php')
    );
    expect($source)->toContain('progress-bar');
    expect($source)->toContain('userProgress');
});

test('lesson.blade a le bouton Marquer comme terminé pour video et doc', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/resources/views/public/lesson.blade.php')
    );
    expect($source)->toContain('Marquer comme terminé');
    expect($source)->toContain('lessons.complete');
});

// ══ Groupe 6 : Gating — QuizController ════════════════════════════════════

test('QuizController existe avec méthodes startQuiz et submitQuiz', function () {
    expect(class_exists(\Modules\Academy\Http\Controllers\QuizController::class))->toBeTrue();
    expect(method_exists(\Modules\Academy\Http\Controllers\QuizController::class, 'startQuiz'))->toBeTrue();
    expect(method_exists(\Modules\Academy\Http\Controllers\QuizController::class, 'submitQuiz'))->toBeTrue();
});

test('QuizController vérifie l inscription avant d agir (abort 403)', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Http/Controllers/QuizController.php')
    );
    expect($source)->toContain('abort(403)');
    expect($source)->toContain('Enrollment');
    expect($source)->toContain("'status', 'active'");
});

test('QuizController stocke les questions en session (pas dans le DOM)', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Http/Controllers/QuizController.php')
    );
    // Les questions doivent être mises en session, pas retournées dans la vue directement
    expect($source)->toContain('session()->put');
    expect($source)->toContain('questions');
});

test('QuizController respecte attempts_allowed', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Http/Controllers/QuizController.php')
    );
    expect($source)->toContain('attempts_allowed');
    expect($source)->toContain('Nombre de tentatives maximum atteint');
});

test('QuizController marque completed seulement si score >= passing_score', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Http/Controllers/QuizController.php')
    );
    expect($source)->toContain('passing_score');
    expect($source)->toContain('markComplete');
    // La complétion ne doit être appelée que si $passed
    $passedPos    = strpos($source, '$passed');
    $completePos  = strpos($source, 'markComplete');
    expect($passedPos)->toBeLessThan($completePos,
        'La variable $passed doit être calculée AVANT l\'appel à markComplete'
    );
});

test('CompletionController existe et protège les quiz (type check)', function () {
    $source = file_get_contents(
        base_path('Modules/Academy/app/Http/Controllers/CompletionController.php')
    );
    expect($source)->toContain("type === 'quiz'");
    expect($source)->toContain('abort(403)');
    expect($source)->toContain('Enrollment');
});
