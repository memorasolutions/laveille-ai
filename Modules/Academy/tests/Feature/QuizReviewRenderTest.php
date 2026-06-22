<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — V1-a COUCHE révision : affichage des rétroactions après soumission.
 *
 * Prouve que :
 *  - après soumission, le lecteur affiche la RÉVISION (feedback global + général +
 *    spécifique au choix) pour l'inscrit, SCOPÉE à SA tentative ;
 *  - un AUTRE utilisateur ne voit jamais la tentative d'autrui (pas d'IDOR) ;
 *  - anti-XSS : un feedback contenant <script> est neutralisé (jamais exécuté).
 *
 * Autonome : helpers préfixés v1ar. SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers v1ar
// ─────────────────────────────────────────────────────────────────────────────

function v1arSetup(array $itemPayload, array $questionPayload, ?string $explanation = null): array
{
    $course = Course::create([
        'slug'        => 'cours-rev-'.uniqid(),
        'title'       => 'Cours révision',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);

    $owner = User::factory()->create();
    $owner->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $owner->id, 'role' => 'owner']);

    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'L', 'slug' => 'l-'.$chapter->id, 'position' => 1]);

    $cat = QuestionCategory::create(['owner_id' => $owner->id, 'parent_id' => null, 'name' => 'Cat', 'position' => 0]);

    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'mcq',
        'prompt'      => 'Énoncé de la question',
        'payload'     => $questionPayload,
        'explanation' => $explanation,
        'difficulty'  => 'facile',
        'is_active'   => true,
    ]);

    $item = LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => 'quiz',
        'title'     => 'Quiz',
        'position'  => 1,
        'payload'   => array_merge(['question_bank' => ['category_id' => $cat->id, 'draw_count' => 1]], $itemPayload),
    ]);

    return [$course, $lesson, $item];
}

function v1arStudentEnrolled(Course $course): User
{
    $student = User::factory()->create();
    $student->assignRole('student');
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $student->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);

    return $student;
}

function v1arStartUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/start";
}

function v1arSubmitUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/submit";
}

function v1arLessonUrl(Course $course, Lesson $lesson): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}";
}

// ─────────────────────────────────────────────────────────────────────────────
// Révision affichée : 3 couches pour l'inscrit
// ─────────────────────────────────────────────────────────────────────────────

test('la révision affiche feedback global, général et spécifique pour l’inscrit', function (): void {
    [$course, $lesson, $item] = v1arSetup(
        itemPayload: [
            'passing_score'    => 60,
            'overall_feedback' => [['min_percent' => 0, 'message' => 'Reprends les bases ici']],
        ],
        questionPayload: [
            'choices'         => ['Bonne', 'Mauvaise'],
            'correct'         => 0,
            'choice_feedback' => [0 => 'Bien joué', 1 => 'Choix piège attention'],
        ],
        explanation: 'Voici le rappel general important',
    );

    $student = v1arStudentEnrolled($course);

    $this->actingAs($student)->post(v1arStartUrl($course, $lesson, $item));
    // Choisit le mauvais choix (index 1) → 0 % → borne 0 % applicable.
    $this->actingAs($student)->post(v1arSubmitUrl($course, $lesson, $item), ['answers' => ['0' => 1]]);

    $this->actingAs($student)
        ->get(v1arLessonUrl($course, $lesson))
        ->assertOk()
        ->assertSee('Révision de vos réponses')
        ->assertSee('Reprends les bases ici')          // couche 3 : global
        ->assertSee('Choix piège attention')           // couche 1 : choix sélectionné (index 1)
        ->assertSee('Voici le rappel general important') // couche 2 : feedback général
        ->assertDontSee('Bien joué');                  // feedback du choix NON sélectionné
});

test('un autre utilisateur ne voit pas la tentative d’autrui', function (): void {
    [$course, $lesson, $item] = v1arSetup(
        itemPayload: ['passing_score' => 60],
        questionPayload: [
            'choices'         => ['Bonne', 'Mauvaise'],
            'correct'         => 0,
            'choice_feedback' => [1 => 'Feedback prive de la victime'],
        ],
    );

    $victim = v1arStudentEnrolled($course);
    $this->actingAs($victim)->post(v1arStartUrl($course, $lesson, $item));
    $this->actingAs($victim)->post(v1arSubmitUrl($course, $lesson, $item), ['answers' => ['0' => 1]]);

    // Un AUTRE inscrit, qui n'a fait aucune tentative, ne voit aucune révision.
    $other = v1arStudentEnrolled($course);
    $this->actingAs($other)
        ->get(v1arLessonUrl($course, $lesson))
        ->assertOk()
        ->assertDontSee('Révision de vos réponses')
        ->assertDontSee('Feedback prive de la victime');
});

test('anti-XSS : un feedback contenant <script> est neutralisé', function (): void {
    [$course, $lesson, $item] = v1arSetup(
        itemPayload: [
            'passing_score'    => 60,
            'overall_feedback' => [['min_percent' => 0, 'message' => 'Global <script>alert(1)</script> ok']],
        ],
        questionPayload: [
            'choices'         => ['Bonne', 'Mauvaise'],
            'correct'         => 0,
            'choice_feedback' => [1 => 'Choix <script>alert(2)</script> piege'],
        ],
        explanation: 'General <script>alert(3)</script> note',
    );

    $student = v1arStudentEnrolled($course);
    $this->actingAs($student)->post(v1arStartUrl($course, $lesson, $item));
    $this->actingAs($student)->post(v1arSubmitUrl($course, $lesson, $item), ['answers' => ['0' => 1]]);

    $html = $this->actingAs($student)
        ->get(v1arLessonUrl($course, $lesson))
        ->assertOk()
        ->getContent();

    // Le HTML rendu de la révision ne contient AUCUNE balise <script> injectée
    // (Str::markdown html_input=strip la retire). Le texte voisin reste affiché.
    expect($html)->not->toContain('<script>alert(1)</script>');
    expect($html)->not->toContain('<script>alert(2)</script>');
    expect($html)->not->toContain('<script>alert(3)</script>');
});
