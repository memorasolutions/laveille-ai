<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - F22-b GRAPHE DE COMPÉTENCES (relations pondérées, CompetencyGraphService).
 *
 * Prouve que :
 *  - drapeau OFF (défaut) = comportement neutre partout : masteryFor=0.0,
 *    isUnlocked=true (jamais verrouillé), graphFor=graphe vide ;
 *  - masteryFor calcule correctement à partir de statements xAPI de test connus
 *    (complétion + score de quiz, moyenne des deux signaux) ;
 *  - isUnlocked refuse si un prérequis n'est pas atteint à son seuil, accepte
 *    si tous le sont ;
 *  - l'auto-référence est impossible (contrainte applicative + DB) ;
 *  - graphFor retourne la bonne structure (nodes/edges) pour un cours de test.
 *
 * Autonome : helpers préfixés « cg ». Skippé si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Competency;
use Modules\Academy\Models\CompetencyLink;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\XapiStatement;
use Modules\Academy\Services\CompetencyGraphService;
use Modules\Academy\Services\XapiRecorderService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    config()->set('academy.competency_graph_enabled', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers cg (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function cgCourse(string $slug = 'cg-cours'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'CG Cours',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function cgStudent(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

/** @return array<int, LessonItem> */
function cgItems(Course $course, int $count, string $type = 'document'): array
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chap', 'position' => 1]);
    $items   = [];
    for ($i = 1; $i <= $count; $i++) {
        $lesson  = Lesson::create([
            'chapter_id' => $chapter->id,
            'title'      => "Leçon $i",
            'slug'       => "cg-l-$i-{$course->id}-{$type}-" . uniqid(),
            'position'   => $i,
        ]);
        $items[] = LessonItem::create([
            'lesson_id'   => $lesson->id,
            'type'        => $type,
            'title'       => "Item $i",
            'position'    => 1,
            'is_required' => true,
            'payload'     => $type === 'quiz' ? ['questions' => [['q' => 'x']]] : null,
        ]);
    }

    return $items;
}

function cgCompetency(string $name = 'Compétence'): Competency
{
    return Competency::create([
        'name'      => $name,
        'slug'      => Competency::slugify($name) . '-' . uniqid(),
        'is_active' => true,
    ]);
}

function cgStatementCompleted(User $user, LessonItem $item): XapiStatement
{
    return XapiStatement::create([
        'user_id'     => $user->id,
        'verb'        => XapiRecorderService::VERB_COMPLETED,
        'object_type' => XapiRecorderService::OBJECT_LESSON,
        'object_id'   => $item->id,
        'result'      => null,
        'context'     => null,
        'raw_payload' => ['test' => true],
        'occurred_at' => now(),
    ]);
}

function cgStatementQuiz(User $user, LessonItem $item, float $percent): XapiStatement
{
    return XapiStatement::create([
        'user_id'     => $user->id,
        'verb'        => XapiRecorderService::VERB_ATTEMPTED,
        'object_type' => XapiRecorderService::OBJECT_QUIZ,
        'object_id'   => $item->id,
        'result'      => ['percent' => $percent, 'passed' => $percent >= 70],
        'context'     => null,
        'raw_payload' => ['test' => true],
        'occurred_at' => now(),
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Drapeau OFF = comportement neutre partout (rétrocompat stricte)
// ─────────────────────────────────────────────────────────────────────────────

it('drapeau OFF (défaut) : masteryFor, isUnlocked et graphFor restent neutres', function (): void {
    config()->set('academy.competency_graph_enabled', false);

    $course  = cgCourse();
    $student = cgStudent();
    [$item]  = cgItems($course, 1);

    $prerequisite = cgCompetency('Prérequis');
    $dependent    = cgCompetency('Dépendante');
    CompetencyLink::create(['competency_id' => $dependent->id, 'lesson_item_id' => $item->id]);
    CompetencyLink::create(['competency_id' => $prerequisite->id, 'lesson_item_id' => $item->id]);

    $dependent->requiresCompetencies()->attach($prerequisite->id, ['mastery_threshold' => 0.70, 'weight' => 1.0]);

    // Même avec un statement xAPI existant et un prérequis NON atteint,
    // drapeau OFF = comportement neutre (aucune exception, retour neutre).
    cgStatementCompleted($student, $item);

    $service = app(CompetencyGraphService::class);

    expect($service->isEnabled())->toBeFalse()
        ->and($service->masteryFor($student, $dependent))->toBe(0.0)
        ->and($service->isUnlocked($student, $dependent))->toBeTrue()
        ->and($service->graphFor($course))->toBe(['nodes' => [], 'edges' => []]);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. masteryFor : calcul à partir de statements xAPI connus
// ─────────────────────────────────────────────────────────────────────────────

it('masteryFor calcule 0.0 pour une compétence sans aucun statement (drapeau ON)', function (): void {
    config()->set('academy.competency_graph_enabled', true);

    $course     = cgCourse();
    $student    = cgStudent();
    [$item]     = cgItems($course, 1);
    $competency = cgCompetency();
    CompetencyLink::create(['competency_id' => $competency->id, 'lesson_item_id' => $item->id]);

    expect(app(CompetencyGraphService::class)->masteryFor($student, $competency))->toBe(0.0);
});

it('masteryFor calcule le taux de complétion pur quand seuls des items non notés sont liés', function (): void {
    config()->set('academy.competency_graph_enabled', true);

    $course     = cgCourse();
    $student    = cgStudent();
    $items      = cgItems($course, 2, 'document');
    $competency = cgCompetency();
    foreach ($items as $item) {
        CompetencyLink::create(['competency_id' => $competency->id, 'lesson_item_id' => $item->id]);
    }

    // 1 item complété sur 2 → 0.5.
    cgStatementCompleted($student, $items[0]);

    expect(app(CompetencyGraphService::class)->masteryFor($student, $competency))->toBe(0.5);

    // Les 2 items complétés → 1.0.
    cgStatementCompleted($student, $items[1]);

    expect(app(CompetencyGraphService::class)->masteryFor($student, $competency))->toBe(1.0);
});

it('masteryFor calcule le score moyen de quiz (dernière tentative par item)', function (): void {
    config()->set('academy.competency_graph_enabled', true);

    $course     = cgCourse();
    $student    = cgStudent();
    [$quiz]     = cgItems($course, 1, 'quiz');
    $competency = cgCompetency();
    CompetencyLink::create(['competency_id' => $competency->id, 'lesson_item_id' => $quiz->id]);

    // Première tentative faible, puis une meilleure : seule la DERNIÈRE compte.
    cgStatementQuiz($student, $quiz, 40.0);
    cgStatementQuiz($student, $quiz, 80.0);

    expect(app(CompetencyGraphService::class)->masteryFor($student, $competency))->toBe(0.8);
});

it('masteryFor moyenne complétion et score de quiz quand les deux signaux existent', function (): void {
    config()->set('academy.competency_graph_enabled', true);

    $course     = cgCourse();
    $student    = cgStudent();
    [$doc]      = cgItems($course, 1, 'document');
    [$quiz]     = cgItems($course, 1, 'quiz');
    $competency = cgCompetency();
    CompetencyLink::create(['competency_id' => $competency->id, 'lesson_item_id' => $doc->id]);
    CompetencyLink::create(['competency_id' => $competency->id, 'lesson_item_id' => $quiz->id]);

    // Complétion : 1/1 item non noté = 1.0. Quiz : 60 % = 0.6. Moyenne = 0.8.
    cgStatementCompleted($student, $doc);
    cgStatementQuiz($student, $quiz, 60.0);

    expect(app(CompetencyGraphService::class)->masteryFor($student, $competency))->toBe(0.8);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. isUnlocked : ET logique strict sur les prérequis
// ─────────────────────────────────────────────────────────────────────────────

it('isUnlocked refuse si un prérequis n’est pas atteint au seuil requis', function (): void {
    config()->set('academy.competency_graph_enabled', true);

    $course       = cgCourse();
    $student      = cgStudent();
    [$quiz]       = cgItems($course, 1, 'quiz');
    $prerequisite = cgCompetency('Prérequis');
    $dependent    = cgCompetency('Dépendante');
    CompetencyLink::create(['competency_id' => $prerequisite->id, 'lesson_item_id' => $quiz->id]);

    $dependent->requiresCompetencies()->attach($prerequisite->id, ['mastery_threshold' => 0.70, 'weight' => 1.0]);

    // Score 50 % < seuil 70 % → verrouillée.
    cgStatementQuiz($student, $quiz, 50.0);

    expect(app(CompetencyGraphService::class)->isUnlocked($student, $dependent))->toBeFalse();
});

it('isUnlocked accepte quand TOUS les prérequis sont atteints à leur seuil', function (): void {
    config()->set('academy.competency_graph_enabled', true);

    $course        = cgCourse();
    $student       = cgStudent();
    [$quizA, $quizB] = cgItems($course, 2, 'quiz');

    $prereqA   = cgCompetency('Prérequis A');
    $prereqB   = cgCompetency('Prérequis B');
    $dependent = cgCompetency('Dépendante');
    CompetencyLink::create(['competency_id' => $prereqA->id, 'lesson_item_id' => $quizA->id]);
    CompetencyLink::create(['competency_id' => $prereqB->id, 'lesson_item_id' => $quizB->id]);

    $dependent->requiresCompetencies()->attach($prereqA->id, ['mastery_threshold' => 0.70, 'weight' => 1.0]);
    $dependent->requiresCompetencies()->attach($prereqB->id, ['mastery_threshold' => 0.50, 'weight' => 1.0]);

    // A : 80 % >= 70 %. B : 60 % >= 50 %. Les deux atteints → déverrouillée.
    cgStatementQuiz($student, $quizA, 80.0);
    cgStatementQuiz($student, $quizB, 60.0);

    expect(app(CompetencyGraphService::class)->isUnlocked($student, $dependent))->toBeTrue();
});

it('isUnlocked reste TOUJOURS vrai pour une compétence sans aucun prérequis déclaré', function (): void {
    config()->set('academy.competency_graph_enabled', true);

    $student    = cgStudent();
    $competency = cgCompetency();

    expect(app(CompetencyGraphService::class)->isUnlocked($student, $competency))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Anti-auto-référence
// ─────────────────────────────────────────────────────────────────────────────

it('interdit une relation de compétence en auto-référence (contrainte DB)', function (): void {
    $competency = cgCompetency('Seule');

    $insert = fn () => \Illuminate\Support\Facades\DB::table('academy_competency_relations')->insert([
        'competency_id'          => $competency->id,
        'requires_competency_id' => $competency->id,
        'mastery_threshold'      => 0.70,
        'weight'                 => 1.0,
        'created_at'             => now(),
        'updated_at'             => now(),
    ]);

    expect($insert)->toThrow(\Illuminate\Database\QueryException::class, 'CHECK constraint failed');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. graphFor : structure nodes/edges pour un cours
// ─────────────────────────────────────────────────────────────────────────────

it('graphFor retourne les nœuds et arêtes du graphe pour un cours donné', function (): void {
    config()->set('academy.competency_graph_enabled', true);

    $course = cgCourse();
    $items  = cgItems($course, 2);

    $prerequisite = cgCompetency('Bases');
    $dependent    = cgCompetency('Avancé');
    CompetencyLink::create(['competency_id' => $prerequisite->id, 'lesson_item_id' => $items[0]->id]);
    CompetencyLink::create(['competency_id' => $dependent->id, 'lesson_item_id' => $items[1]->id]);

    $dependent->requiresCompetencies()->attach($prerequisite->id, ['mastery_threshold' => 0.70, 'weight' => 1.0]);

    $graph = app(CompetencyGraphService::class)->graphFor($course);

    expect($graph['nodes'])->toHaveCount(2)
        ->and(collect($graph['nodes'])->pluck('name')->all())->toContain('Bases', 'Avancé')
        ->and($graph['edges'])->toHaveCount(1)
        ->and($graph['edges'][0]['from'])->toBe($prerequisite->id)
        ->and($graph['edges'][0]['to'])->toBe($dependent->id)
        ->and($graph['edges'][0]['mastery_threshold'])->toBe(0.70);
});

it('graphFor retourne un graphe vide pour un cours sans compétence liée', function (): void {
    config()->set('academy.competency_graph_enabled', true);

    $course = cgCourse('cg-vide');

    expect(app(CompetencyGraphService::class)->graphFor($course))->toBe(['nodes' => [], 'edges' => []]);
});
