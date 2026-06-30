<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Badges / jalons d'engagement (Phase E / E1).
 *
 * Prouve que :
 *  - l'attribution est SERVEUR (calcul depuis Progress/Completion/CertificateIssued) ;
 *  - elle est IDEMPOTENTE (ré-évaluer ne crée jamais de doublon) ;
 *  - les seuils sont respectés (10 leçons décerne, 9 non) ;
 *  - un certificat émis décerne le badge « Diplômé » ;
 *  - un utilisateur ne voit QUE ses propres badges (scoping anti-IDOR) ;
 *  - les migrations sont additives (down = drop).
 *
 * Autonome : helpers préfixés e1, aucune redéclaration d'une fonction d'un autre
 * fichier de test.
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Academy\Models\Badge;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Progress;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Models\UserBadge;
use Modules\Academy\Services\BadgeService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    // Catalogue de badges identique au seeder (idempotent), sans dépendre du CI.
    $this->seed(\Modules\Academy\Database\Seeders\AcademyBadgesSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers e1 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/** Crée un cours gratuit publié minimal. */
function e1Course(string $slug = 'e1-cours'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'E1 Cours',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/** Enregistre une progression directe (source de vérité serveur). */
function e1Progress(User $user, Course $course, int $percent): Progress
{
    return Progress::create([
        'user_id'            => $user->id,
        'course_id'          => $course->id,
        'percent'            => $percent,
        'required_total'     => 1,
        'required_completed' => $percent >= 100 ? 1 : 0,
        'last_activity_at'   => now(),
    ]);
}

/** Crée N complétions de leçons réelles pour un utilisateur dans un cours. */
function e1CompleteLessons(User $user, Course $course, int $count): void
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre',
        'position'  => 1,
    ]);

    for ($i = 1; $i <= $count; $i++) {
        $lesson = Lesson::create([
            'chapter_id' => $chapter->id,
            'title'      => "Leçon $i",
            'slug'       => "e1-lecon-$i-{$course->id}",
            'position'   => $i,
        ]);

        $item = LessonItem::create([
            'lesson_id'   => $lesson->id,
            'type'        => 'document',
            'title'       => "Item $i",
            'position'    => 1,
            'is_required' => true,
        ]);

        Completion::create([
            'user_id'        => $user->id,
            'course_id'      => $course->id,
            'lesson_item_id' => $item->id,
            'status'         => 'completed',
            'completed_at'   => now(),
        ]);
    }
}

/** Émet un certificat brut pour un utilisateur sur un cours. */
function e1Certificate(User $user, Course $course): CertificateIssued
{
    return CertificateIssued::create([
        'user_id'           => $user->id,
        'course_id'         => $course->id,
        'serial'            => 'E1-'.uniqid(),
        'verification_hash' => hash('sha256', uniqid('', true)),
        'public_url_slug'   => 'e1-cert-'.uniqid(),
        'issued_at'         => now(),
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Premier cours complété → badge first_course_completed + IDEMPOTENCE
// ─────────────────────────────────────────────────────────────────────────────

test('complétion du 1er cours décerne le badge first_course_completed (idempotent)', function (): void {
    $user   = User::factory()->create();
    $course = e1Course();
    e1Progress($user, $course, 100);

    $service = new BadgeService();

    $awarded = $service->evaluateForUser($user, $course);

    $keys = $awarded->pluck('key')->all();
    expect($keys)->toContain('first_course_completed');

    // Idempotence : ré-évaluer ne crée PAS de doublon ni de nouvelle attribution.
    $again = $service->evaluateForUser($user, $course);
    expect($again->pluck('key')->all())->not->toContain('first_course_completed');

    $badge = Badge::where('key', 'first_course_completed')->first();
    expect(UserBadge::where('badge_id', $badge->id)->where('user_id', $user->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Seuil lessons_completed : décerné à 10, pas à 9
// ─────────────────────────────────────────────────────────────────────────────

test('lessons_completed décerné à 10 leçons mais PAS à 9', function (): void {
    $service = new BadgeService();

    // 9 leçons → pas de « Persévérant ».
    $user9   = User::factory()->create();
    $course9 = e1Course('e1-cours-9');
    e1CompleteLessons($user9, $course9, 9);

    $awarded9 = $service->evaluateForUser($user9, $course9);
    expect($awarded9->pluck('key')->all())->not->toContain('perseverant');

    $perseverant = Badge::where('key', 'perseverant')->first();
    expect(UserBadge::where('badge_id', $perseverant->id)->where('user_id', $user9->id)->exists())->toBeFalse();

    // 10 leçons → « Persévérant » décerné.
    $user10   = User::factory()->create();
    $course10 = e1Course('e1-cours-10');
    e1CompleteLessons($user10, $course10, 10);

    $awarded10 = $service->evaluateForUser($user10, $course10);
    expect($awarded10->pluck('key')->all())->toContain('perseverant');
    expect(UserBadge::where('badge_id', $perseverant->id)->where('user_id', $user10->id)->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. first_certificate : décerné quand un CertificateIssued existe
// ─────────────────────────────────────────────────────────────────────────────

test('first_certificate décerné quand un certificat existe', function (): void {
    $user   = User::factory()->create();
    $course = e1Course();
    e1Certificate($user, $course);

    $awarded = (new BadgeService())->evaluateForUser($user, $course);

    expect($awarded->pluck('key')->all())->toContain('diplome');

    // Sans certificat, un autre utilisateur ne l'obtient pas.
    $other = User::factory()->create();
    $awardedOther = (new BadgeService())->evaluateForUser($other, $course);
    expect($awardedOther->pluck('key')->all())->not->toContain('diplome');
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Scoping : un utilisateur ne voit QUE ses propres badges
// ─────────────────────────────────────────────────────────────────────────────

test('un utilisateur ne voit que ses propres badges (scoping anti-IDOR)', function (): void {
    $service = new BadgeService();

    $alice  = User::factory()->create();
    $bob    = User::factory()->create();
    $course = e1Course();

    e1Progress($alice, $course, 100);
    $service->evaluateForUser($alice, $course);

    // Bob n'a rien gagné.
    expect($service->earnedFor($alice)->isNotEmpty())->toBeTrue();
    expect($service->earnedFor($bob)->isEmpty())->toBeTrue();

    // Les badges d'Alice appartiennent TOUS à Alice.
    $service->earnedFor($alice)->each(function (UserBadge $ub) use ($alice): void {
        expect($ub->user_id)->toBe($alice->id);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. perfect_quiz : décerné sur une complétion à score 100
// ─────────────────────────────────────────────────────────────────────────────

test('perfect_quiz décerné quand une complétion a un score de 100', function (): void {
    $user   = User::factory()->create();
    $course = e1Course();

    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'position' => 1]);
    $lesson  = Lesson::create([
        'chapter_id' => $chapter->id, 'title' => 'L', 'slug' => 'e1-quiz-'.$course->id, 'position' => 1,
    ]);
    // Quiz à 3 questions. QuizAttempt = source de vérité pour hasPerfectQuiz (fix B03).
    // Payload['questions'] conservé pour la rétrocompat d'autres parties du code.
    $item = LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz',
        'position'    => 1,
        'is_required' => true,
        'payload'     => ['questions' => [['q' => 'a'], ['q' => 'b'], ['q' => 'c']]],
    ]);
    Completion::create([
        'user_id'        => $user->id,
        'course_id'      => $course->id,
        'lesson_item_id' => $item->id,
        'status'         => 'completed',
        'score'          => 3,
        'completed_at'   => now(),
    ]);
    // QuizAttempt requis pour hasPerfectQuiz après fix B03.
    QuizAttempt::create([
        'user_id'        => $user->id,
        'lesson_item_id' => $item->id,
        'course_id'      => $course->id,
        'score'          => 3,
        'max_score'      => 3,
        'percent'        => 100,
        'passed'         => true,
        'answers'        => [],
        'submitted_at'   => now(),
    ]);

    $awarded = (new BadgeService())->evaluateForUser($user, $course);
    expect($awarded->pluck('key')->all())->toContain('sans_faute');

    // Contre-épreuve : 2/3 ne décerne PAS le badge.
    $other  = User::factory()->create();
    Completion::create([
        'user_id'        => $other->id,
        'course_id'      => $course->id,
        'lesson_item_id' => $item->id,
        'status'         => 'completed',
        'score'          => 2,
        'completed_at'   => now(),
    ]);
    QuizAttempt::create([
        'user_id'        => $other->id,
        'lesson_item_id' => $item->id,
        'course_id'      => $course->id,
        'score'          => 2,
        'max_score'      => 3,
        'percent'        => 67,
        'passed'         => false,
        'answers'        => [],
        'submitted_at'   => now(),
    ]);
    $awardedOther = (new BadgeService())->evaluateForUser($other, $course);
    expect($awardedOther->pluck('key')->all())->not->toContain('sans_faute');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5b. B03 — perfect_quiz via QuizAttempt (chemin réel de production)
//
//     Reproduit le bug B03 : un quiz créé via l'admin N'A PAS de
//     payload['questions'] (les questions viennent de QuizService/QuestionBank,
//     stockées dans QuizAttempt). Avec l'ANCIEN code, $total=0 → badge jamais
//     décerné. Après le fix (hasPerfectQuiz lit QuizAttempt), le badge est attribué.
//
//     Ce test ÉCHOUAIT avant le fix (sans_faute absent) et PASSE après.
// ─────────────────────────────────────────────────────────────────────────────

test('B03 régression — perfect_quiz attribué via QuizAttempt (sans payload questions)', function (): void {
    $user   = User::factory()->create();
    $course = e1Course('e1-b03');

    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch B03', 'position' => 1]);
    $lesson  = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'L B03',
        'slug'       => 'e1-b03-quiz-' . $course->id,
        'position'   => 1,
    ]);

    // Quiz sans payload['questions'] = chemin réel (QuizService/QuestionBank stocke
    // les questions dans QuizAttempt.questions_snapshot, pas dans item.payload).
    $item = LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz B03',
        'position'    => 1,
        'is_required' => true,
        'payload'     => [],   // PAS de 'questions' = cas de production réel
    ]);

    // Complétion avec score parfait.
    Completion::create([
        'user_id'        => $user->id,
        'course_id'      => $course->id,
        'lesson_item_id' => $item->id,
        'status'         => 'completed',
        'score'          => 5,
        'completed_at'   => now(),
    ]);

    // QuizAttempt réussie à 100 % (source de vérité réelle).
    QuizAttempt::create([
        'user_id'        => $user->id,
        'lesson_item_id' => $item->id,
        'course_id'      => $course->id,
        'score'          => 5,
        'max_score'      => 5,
        'percent'        => 100,
        'passed'         => true,
        'answers'        => [],
        'submitted_at'   => now(),
    ]);

    $awarded = (new BadgeService())->evaluateForUser($user, $course);
    expect($awarded->pluck('key')->all())->toContain('sans_faute');

    // Contre-épreuve : tentative non parfaite (4/5) → badge absent.
    $other = User::factory()->create();
    QuizAttempt::create([
        'user_id'        => $other->id,
        'lesson_item_id' => $item->id,
        'course_id'      => $course->id,
        'score'          => 4,
        'max_score'      => 5,
        'percent'        => 80,
        'passed'         => true,   // réussi mais pas parfait
        'answers'        => [],
        'submitted_at'   => now(),
    ]);

    $awardedOther = (new BadgeService())->evaluateForUser($other, $course);
    expect($awardedOther->pluck('key')->all())->not->toContain('sans_faute');
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. Migrations additives (tables présentes, structure attendue)
// ─────────────────────────────────────────────────────────────────────────────

test('migrations additives : tables badges et pivot présentes', function (): void {
    expect(Schema::hasTable('academy_badges'))->toBeTrue();
    expect(Schema::hasTable('academy_user_badges'))->toBeTrue();

    expect(Schema::hasColumns('academy_badges', [
        'key', 'name', 'description', 'icon', 'criteria_type', 'criteria_value', 'is_active',
    ]))->toBeTrue();

    expect(Schema::hasColumns('academy_user_badges', [
        'badge_id', 'user_id', 'course_id', 'awarded_at',
    ]))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. Seeder idempotent : relancer ne crée pas de doublon
// ─────────────────────────────────────────────────────────────────────────────

test('le seeder de badges est idempotent', function (): void {
    $before = Badge::count();

    $this->seed(\Modules\Academy\Database\Seeders\AcademyBadgesSeeder::class);

    expect(Badge::count())->toBe($before);
});
