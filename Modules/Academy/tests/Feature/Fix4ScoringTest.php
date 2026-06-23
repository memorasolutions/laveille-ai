<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — CORRECTIF F4 / C1 : crédit partiel NON arrondi à l'entier par question.
 *
 * BUG corrigé : QuizService::score() accumulait `points_earned += (int) round($fraction
 * * $points)` pour les types à crédit partiel. Une question à 1 point avec fraction 0,5
 * donnait alors round(0,5) = 1 point → 100 % au lieu de 50 % (incohérence « 0/1 bonnes
 * réponses » mais « 1/1 points / 100 % », et un passing_score franchi à tort).
 *
 * Ce fichier prouve, sur les 4 types à crédit partiel (qcm multi, ordonnancement, cloze,
 * glisser-texte) :
 *  - 1 point, fraction 0,5 → points_earned == 0,5 et percent == 50 (PLUS 100 %) ;
 *  - 8 points, 2/4 → points_earned == 4 et percent == 50 (inchangé : valeur entière) ;
 *  - les types BINAIRES (qcm simple, vrai/faux, court, numérique) restent inchangés ;
 *  - le badge « sans faute » (correct/total) reste exact ;
 *  - INTÉGRATION : un quiz lié-banque (ddwtos 1 pt) à 50 % avec passing_score 60 ÉCHOUE
 *    désormais (avant : 100 %, réussite à tort).
 *
 * Autonome : helpers préfixés fix4 (aucune redéclaration). SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\QuestionBankManager;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Services\QuizService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers fix4 (préfixés, autonomes — pas de collision avec f1/f2/f4/v1e/qb2q)
// ─────────────────────────────────────────────────────────────────────────────

/** QCM MULTI : 2 bonnes (index 0 et 1) parmi 4 choix. */
function fix4MultiItem(int $points): array
{
    return [
        'type'     => 'qcm',
        'multiple' => true,
        'question' => 'Réponses multiples',
        'choices'  => ['Choix 0', 'Choix 1', 'Choix 2', 'Choix 3'],
        'correct'  => [0, 1],
        'points'   => $points,
    ];
}

/** ORDONNANCEMENT : N éléments, positions absolues correctes = $answer. */
function fix4OrderItem(array $answer, int $points): array
{
    return [
        'type'     => 'ordonnancement',
        'question' => 'Mettez dans le bon ordre',
        'elements' => array_map(fn (int $k): string => "Élément $k", range(0, count($answer) - 1)),
        'answer'   => $answer,
        'points'   => $points,
    ];
}

/** CLOZE : 2 trous « short » (chat / souris). */
function fix4ClozeItem(int $points): array
{
    return [
        'type'     => 'cloze',
        'question' => 'Complétez',
        'segments' => [
            ['type' => 'text',  'value' => 'Le '],
            ['type' => 'blank', 'index' => 0, 'kind' => 'short'],
            ['type' => 'text',  'value' => ' mange la '],
            ['type' => 'blank', 'index' => 1, 'kind' => 'short'],
            ['type' => 'text',  'value' => '.'],
        ],
        'blanks' => [
            0 => ['kind' => 'short', 'accepted' => ['chat']],
            1 => ['kind' => 'short', 'accepted' => ['souris']],
        ],
        'points' => $points,
    ];
}

/** GLISSER-TEXTE (ddwtos) : 2 trous, pool de 4 mots. trou 0 → index 1, trou 1 → index 2. */
function fix4DdwtosItem(int $points): array
{
    return [
        'type'     => 'glisser-texte',
        'question' => 'Complétez',
        'segments' => [
            ['type' => 'text',  'value' => 'Le '],
            ['type' => 'blank', 'index' => 0],
            ['type' => 'text',  'value' => ' mange la '],
            ['type' => 'blank', 'index' => 1],
            ['type' => 'text',  'value' => '.'],
        ],
        'options' => ['fromage', 'chat', 'souris', 'chien'],
        'answers' => [0 => 1, 1 => 2],
        'points'  => $points,
    ];
}

function fix4Score(array $item, mixed $given): array
{
    return QuizService::score([$item], ['0' => $given]);
}

function fix4Instructor(): User
{
    $u = User::factory()->create();
    $u->assignRole('instructor');

    return $u;
}

function fix4Category(User $owner): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Banque fix4',
        'position'  => 0,
    ]);
}

// ═════════════════════════════════════════════════════════════════════════════
// C1 — LE test clé : 1 point, fraction 0,5 → 0,5 point / 50 % (PLUS 100 %)
//      Couvre les 4 types à crédit partiel.
// ═════════════════════════════════════════════════════════════════════════════

test('C1 qcm multi : 1 point, 1 bonne sur 2 → 0,5 point / 50 % (pas 100 %)', function (): void {
    $r = fix4Score(fix4MultiItem(1), [0]); // 1 bonne cochée sur 2 → fraction 0,5

    expect($r['points_possible'])->toBe(1);
    expect($r['points_earned'])->toBe(0.5);
    expect($r['percent'])->toBe(50);
    expect($r['correct'])->toBe(0); // pas « sans faute »
    expect($r['total'])->toBe(1);
});

test('C1 ordonnancement : 1 point, 1 position sur 2 → 0,5 point / 50 %', function (): void {
    // answer [0,1] ; donné [0,0] → seule la position 0 est bien placée (1/2).
    $r = fix4Score(fix4OrderItem([0, 1], 1), [0, 0]);

    expect($r['points_possible'])->toBe(1);
    expect($r['points_earned'])->toBe(0.5);
    expect($r['percent'])->toBe(50);
    expect($r['correct'])->toBe(0);
});

test('C1 cloze : 1 point, 1 trou sur 2 → 0,5 point / 50 %', function (): void {
    // trou 0 correct (« chat »), trou 1 faux.
    $r = fix4Score(fix4ClozeItem(1), [0 => 'chat', 1 => 'erreur']);

    expect($r['points_possible'])->toBe(1);
    expect($r['points_earned'])->toBe(0.5);
    expect($r['percent'])->toBe(50);
    expect($r['correct'])->toBe(0);
});

test('C1 glisser-texte : 1 point, 1 trou sur 2 → 0,5 point / 50 %', function (): void {
    // trou 0 correct (index 1 = « chat »), trou 1 faux (index 1 ≠ 2).
    $r = fix4Score(fix4DdwtosItem(1), [0 => 1, 1 => 1]);

    expect($r['points_possible'])->toBe(1);
    expect($r['points_earned'])->toBe(0.5);
    expect($r['percent'])->toBe(50);
    expect($r['correct'])->toBe(0);
});

// ═════════════════════════════════════════════════════════════════════════════
// Rétrocompat : 8 points, 2/4 → 4 points / 50 % (valeur ENTIÈRE inchangée, reste int)
// ═════════════════════════════════════════════════════════════════════════════

test('rétrocompat : 8 points, 2/4 bien placés → 4 points / 50 % (int inchangé)', function (): void {
    // answer [0,1,2,3] ; donné [0,1,3,2] → positions 0 et 1 correctes (2/4).
    $r = fix4Score(fix4OrderItem([0, 1, 2, 3], 8), [0, 1, 3, 2]);

    expect($r['points_possible'])->toBe(8);
    expect($r['points_earned'])->toBe(4);   // 0,5 * 8 = 4 (entier → reste int)
    expect($r['points_earned'])->toBeInt(); // pas de 4.0 fragile en strict
    expect($r['percent'])->toBe(50);
    expect($r['correct'])->toBe(0);
});

test('rétrocompat : tout-correct → points pleins + badge sans-faute (4 types)', function (): void {
    $multi = fix4Score(fix4MultiItem(1), [0, 1]);
    expect($multi['points_earned'])->toBe(1);
    expect($multi['percent'])->toBe(100);
    expect($multi['correct'])->toBe(1);

    $order = fix4Score(fix4OrderItem([0, 1], 1), [0, 1]);
    expect($order['points_earned'])->toBe(1);
    expect($order['percent'])->toBe(100);
    expect($order['correct'])->toBe(1);

    $cloze = fix4Score(fix4ClozeItem(1), [0 => 'chat', 1 => 'souris']);
    expect($cloze['points_earned'])->toBe(1);
    expect($cloze['percent'])->toBe(100);

    $ddw = fix4Score(fix4DdwtosItem(1), [0 => 1, 1 => 2]);
    expect($ddw['points_earned'])->toBe(1);
    expect($ddw['percent'])->toBe(100);
});

// ═════════════════════════════════════════════════════════════════════════════
// Binaires INCHANGÉS (qcm simple, vrai/faux, court, numérique)
// ═════════════════════════════════════════════════════════════════════════════

test('binaire : qcm simple, vrai/faux, court, numérique restent en tout-ou-rien', function (): void {
    $qcm = [
        'type' => 'qcm', 'question' => 'Q', 'choices' => ['Bon', 'Mauvais'], 'correct' => 0, 'points' => 1,
    ];
    expect(fix4Score($qcm, 0)['points_earned'])->toBe(1);
    expect(fix4Score($qcm, 0)['percent'])->toBe(100);
    expect(fix4Score($qcm, 1)['points_earned'])->toBe(0);
    expect(fix4Score($qcm, 1)['percent'])->toBe(0);

    $vf = ['type' => 'vraifaux', 'question' => 'VF', 'correct' => 1, 'points' => 3];
    expect(fix4Score($vf, 1)['points_earned'])->toBe(3);
    expect(fix4Score($vf, 0)['points_earned'])->toBe(0);

    $court = ['type' => 'court', 'question' => 'C', 'accepted' => ['oui'], 'points' => 2];
    expect(fix4Score($court, 'OUI')['points_earned'])->toBe(2); // normalisation casse
    expect(fix4Score($court, 'non')['points_earned'])->toBe(0);

    $num = ['type' => 'numerique', 'question' => 'N', 'correct' => 10.0, 'tolerance' => 0.5, 'points' => 4];
    expect(fix4Score($num, '10,2')['points_earned'])->toBe(4); // dans la tolérance
    expect(fix4Score($num, '12')['points_earned'])->toBe(0);
});

// ═════════════════════════════════════════════════════════════════════════════
// C1 INTÉGRATION — passing_score : un 50 % (ddwtos 1 pt) N'EST PLUS une réussite
// ═════════════════════════════════════════════════════════════════════════════

test('C1 intégration : ddwtos 1 pt à 50 % avec passing_score 60 → ÉCHEC (avant : 100 %)', function (): void {
    $course  = Course::create([
        'slug' => 'fix4-passing', 'title' => 'Cours fix4', 'language' => 'fr-CA',
        'level' => 'intro', 'visibility' => 'public', 'access_type' => 'free',
        'status' => 'published', 'currency' => 'CAD',
    ]);
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'Leçon', 'slug' => 'lecon-'.$chapter->id, 'position' => 1]);

    $owner = fix4Instructor();
    CourseRole::create(['course_id' => $course->id, 'user_id' => $owner->id, 'role' => 'owner']);
    $cat = QuestionCategory::create(['owner_id' => $owner->id, 'parent_id' => null, 'name' => 'Banque', 'position' => 0]);

    // UNE question ddwtos valant 1 point, 2 trous (mots tous distincts).
    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'ddwtos',
        'prompt'      => 'Le [[1]] mange la [[2]].',
        'payload'     => [
            'text'    => 'Le [[1]] mange la [[2]].',
            'words'   => ['chat', 'souris', 'chien', 'fromage'],
            'answers' => [0 => 0, 1 => 1],
        ],
        'difficulty'  => 'moyen',
        'points'      => 1,
        'is_active'   => true,
    ]);

    $item = LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz',
        'position'    => 1,
        'payload'     => ['question_bank' => ['category_id' => $cat->id, 'draw_count' => 1], 'passing_score' => 60],
        'is_required' => false,
    ]);

    $student = User::factory()->create();
    $student->assignRole('student');
    Enrollment::create([
        'course_id' => $course->id, 'user_id' => $student->id, 'status' => 'active',
        'source' => 'admin', 'enrolled_at' => now(),
    ]);

    $start  = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/start";
    $submit = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/submit";

    $this->actingAs($student)->post($start)->assertRedirect();

    $round = session("academy.quiz.{$item->id}")['questions'] ?? [];
    expect($round)->toHaveCount(1);
    $q = $round[0];

    // 50 % : trou 0 correct, trou 1 faux. answers = indices corrects dans le pool mélangé ;
    // pour le trou 1 on choisit l'index correct du trou 0 (mots distincts → forcément ≠).
    $given = [0 => $q['answers'][0], 1 => $q['answers'][0]];

    $this->actingAs($student)->post($submit, ['answers' => ['0' => $given]])->assertRedirect();

    $result = session('academy.quiz_result');
    expect($result)->not->toBeNull();
    expect($result['percent'])->toBe(50);
    expect($result['points_earned'])->toBe(0.5);
    expect($result['passed'])->toBeFalse(); // 50 < 60 : la correction empêche la réussite à tort
});

// ═════════════════════════════════════════════════════════════════════════════
// C2 — pool ddwtos avec un libellé dupliqué → rejeté
// ═════════════════════════════════════════════════════════════════════════════

test('C2 : un pool ddwtos avec un libellé dupliqué est rejeté', function (): void {
    $instructor = fix4Instructor();
    $cat        = fix4Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ddwtos')
        ->set('qPrompt', 'Doublon dans le pool')
        ->set('qDdwtosText', 'Le [[1]] mange la [[2]].')
        ->set('qDdwtosWords', ['chat', 'souris', 'chat', 'chien']) // « chat » en double
        ->set('qDdwtosAnswers', [0 => 0, 1 => 1])
        ->call('saveQuestion')
        ->assertHasErrors('qDdwtosWords');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('C2 : un pool ddwtos aux libellés tous distincts est accepté', function (): void {
    $instructor = fix4Instructor();
    $cat        = fix4Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ddwtos')
        ->set('qPrompt', 'Pool distinct')
        ->set('qDdwtosText', 'Le [[1]] mange la [[2]].')
        ->set('qDdwtosWords', ['chat', 'souris', 'chien', 'fromage'])
        ->set('qDdwtosAnswers', [0 => 0, 1 => 1])
        ->call('saveQuestion')
        ->assertHasNoErrors();

    expect(Question::where('category_id', $cat->id)->count())->toBe(1);
});

// ═════════════════════════════════════════════════════════════════════════════
// C3 — zéro tiret cadratin (U+2014) dans le composant quiz-player
// ═════════════════════════════════════════════════════════════════════════════

test('C3 : la vue quiz-player ne contient aucun tiret cadratin (—)', function (): void {
    $path = base_path('Modules/Academy/resources/views/components/quiz-player.blade.php');
    $src  = file_get_contents($path);

    expect($src)->not->toBeFalse();
    expect(mb_substr_count($src, "\u{2014}"))->toBe(0);
});

// ═════════════════════════════════════════════════════════════════════════════
// C4 — aria-required sur le select de désignation ddwtos dans l'éditeur
// ═════════════════════════════════════════════════════════════════════════════

test('C4 : le select de désignation ddwtos porte aria-required dans l’éditeur', function (): void {
    $instructor = fix4Instructor();
    $cat        = fix4Category($instructor);

    $html = Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ddwtos')
        ->set('qDdwtosText', 'Le [[1]] dort.')
        ->set('qDdwtosWords', ['chat', 'chien'])
        ->html();

    // Le <select id="qDdwtosAnswers-0"> doit exister ET porter aria-required="true".
    expect($html)->toContain('id="qDdwtosAnswers-0"');
    expect($html)->toMatch('/qDdwtosAnswers-0[\s\S]*?aria-required="true"/');
});
