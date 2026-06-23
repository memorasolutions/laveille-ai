<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - AUDIT F6, lots C1/C2/C3.
 *
 *  C1 : en mode IMMÉDIAT / ADAPTATIF, une question VERROUILLÉE respecte les
 *       review_options du formateur (parité avec la révision différée) :
 *         - show_right_answer = false → la bonne réponse N'EST PAS exposée
 *           (qcm/vraifaux « Bonne réponse : … » ET type à crédit partiel cloze) ;
 *         - show_right_answer = true  → elle l'est.
 *  C2 : l'alerte de réessai (focus programmatique) a un focus VISIBLE (WCAG 2.4.7) :
 *       plus d'outline:none seul, présence d'un style d'outline visible.
 *  C3 : dans l'éditeur, les champs adaptatifs sont conditionnés au mode adaptatif
 *       (wrapper x-show lié au select de comportement).
 *
 * Autonome : helpers préfixés fix6. SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
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
// Helpers fix6 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function fix6Course(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours F6',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function fix6Lesson(Course $course): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-'.$chapter->id,
        'position'   => 1,
    ]);
}

function fix6Owner(Course $course): User
{
    $owner = User::factory()->create();
    $owner->assignRole('instructor');
    CourseRole::create([
        'course_id' => $course->id,
        'user_id'   => $owner->id,
        'role'      => 'owner',
    ]);

    return $owner;
}

function fix6Category(User $owner): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Banque F6',
        'position'  => 0,
    ]);
}

/** Une affirmation vraie → question vraifaux (bonne réponse = index 0 « Vrai »). */
function fix6FillTrueFalse(QuestionCategory $cat, int $n = 1): void
{
    for ($i = 0; $i < $n; $i++) {
        Question::create([
            'category_id' => $cat->id,
            'owner_id'    => $cat->owner_id,
            'type'        => 'truefalse',
            'prompt'      => "Affirmation #$i (vraie)",
            'payload'     => ['answer' => true],
            'difficulty'  => 'facile',
            'is_active'   => true,
        ]);
    }
}

/** Cloze à UN trou court : « La capitale est [[1]]. », accepté = « Lutece » (mot témoin). */
function fix6FillCloze(QuestionCategory $cat): void
{
    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $cat->owner_id,
        'type'        => 'cloze',
        'prompt'      => 'Complétez',
        'payload'     => [
            'text'   => 'La capitale est [[1]].',
            'blanks' => [
                ['kind' => 'short', 'accepted' => ['Lutece']],
            ],
        ],
        'difficulty'  => 'facile',
        'is_active'   => true,
    ]);
}

function fix6QuizItem(Lesson $lesson, array $payload): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz',
        'position'    => 1,
        'payload'     => $payload,
        'is_required' => false,
    ]);
}

function fix6Enroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function fix6Student(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

function fix6StartUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/start";
}

function fix6VerifyUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/verify";
}

function fix6ShowHtml(Course $c, Lesson $l, User $student): string
{
    return test()->actingAs($student)
        ->get(route('academy.lessons.show', [$c, $l]))
        ->getContent();
}

// ─────────────────────────────────────────────────────────────────────────────
// C1 - qcm/vraifaux : « Bonne réponse : … » verrouillée gâtée par show_right_answer
// ─────────────────────────────────────────────────────────────────────────────

test('C1 immédiat qcm : show_right_answer=false masque la bonne réponse en verrouillé', function (): void {
    $course = fix6Course('c1-tf-off');
    $lesson = fix6Lesson($course);
    $cat    = fix6Category(fix6Owner($course));
    fix6FillTrueFalse($cat);

    $item = fix6QuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 1],
        'question_behaviour' => 'immediate',
        'review_options'     => ['show_right_answer' => false],
    ]);

    $student = fix6Student();
    fix6Enroll($course, $student);
    $this->actingAs($student)->post(fix6StartUrl($course, $lesson, $item));

    // Réponse FAUSSE (index 1 = Faux) → verrouillée, incorrecte.
    $this->actingAs($student)->post(fix6VerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => 1]);

    $html = fix6ShowHtml($course, $lesson, $student);
    expect($html)->toContain('✗ À revoir');         // show_correctness défaut = true
    expect($html)->toContain('Votre réponse');       // sa réponse reste visible
    expect($html)->not->toContain('Bonne réponse :'); // mais PAS la bonne réponse
});

test('C1 immédiat qcm : show_right_answer=true expose la bonne réponse en verrouillé', function (): void {
    $course = fix6Course('c1-tf-on');
    $lesson = fix6Lesson($course);
    $cat    = fix6Category(fix6Owner($course));
    fix6FillTrueFalse($cat);

    $item = fix6QuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 1],
        'question_behaviour' => 'immediate',
        'review_options'     => ['show_right_answer' => true],
    ]);

    $student = fix6Student();
    fix6Enroll($course, $student);
    $this->actingAs($student)->post(fix6StartUrl($course, $lesson, $item));
    $this->actingAs($student)->post(fix6VerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => 1]);

    $html = fix6ShowHtml($course, $lesson, $student);
    expect($html)->toContain('Bonne réponse :');
});

// ─────────────────────────────────────────────────────────────────────────────
// C1 - type à CRÉDIT PARTIEL (cloze) : la bonne réponse par trou suit show_right_answer
// ─────────────────────────────────────────────────────────────────────────────

test('C1 immédiat cloze : show_right_answer=false masque la bonne réponse des trous', function (): void {
    $course = fix6Course('c1-cloze-off');
    $lesson = fix6Lesson($course);
    $cat    = fix6Category(fix6Owner($course));
    fix6FillCloze($cat);

    $item = fix6QuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 1],
        'question_behaviour' => 'immediate',
        'review_options'     => ['show_right_answer' => false],
    ]);

    $student = fix6Student();
    fix6Enroll($course, $student);
    $this->actingAs($student)->post(fix6StartUrl($course, $lesson, $item));

    // Trou rempli FAUX → verrouillé, incorrect ; le mot témoin « Lutece » ne doit pas fuir.
    $this->actingAs($student)->post(fix6VerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => [0 => 'Montreal']]);

    $html = fix6ShowHtml($course, $lesson, $student);
    expect($html)->toContain('Trou 1');     // révision par trou rendue
    expect($html)->not->toContain('Lutece'); // bonne réponse NON exposée
});

test('C1 immédiat cloze : show_right_answer=true expose la bonne réponse des trous', function (): void {
    $course = fix6Course('c1-cloze-on');
    $lesson = fix6Lesson($course);
    $cat    = fix6Category(fix6Owner($course));
    fix6FillCloze($cat);

    $item = fix6QuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 1],
        'question_behaviour' => 'immediate',
        'review_options'     => ['show_right_answer' => true],
    ]);

    $student = fix6Student();
    fix6Enroll($course, $student);
    $this->actingAs($student)->post(fix6StartUrl($course, $lesson, $item));
    $this->actingAs($student)->post(fix6VerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => [0 => 'Montreal']]);

    $html = fix6ShowHtml($course, $lesson, $student);
    expect($html)->toContain('Lutece');
});

// ─────────────────────────────────────────────────────────────────────────────
// C1 - rétrocompat : SANS review_options, le verrouillé expose tout (défaut true)
// ─────────────────────────────────────────────────────────────────────────────

test('C1 rétrocompat : sans review_options, la bonne réponse reste exposée (défaut)', function (): void {
    $course = fix6Course('c1-default');
    $lesson = fix6Lesson($course);
    $cat    = fix6Category(fix6Owner($course));
    fix6FillTrueFalse($cat);

    $item = fix6QuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 1],
        'question_behaviour' => 'immediate',
    ]);

    $student = fix6Student();
    fix6Enroll($course, $student);
    $this->actingAs($student)->post(fix6StartUrl($course, $lesson, $item));
    $this->actingAs($student)->post(fix6VerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => 1]);

    $html = fix6ShowHtml($course, $lesson, $student);
    expect($html)->toContain('Bonne réponse :');
});

// ─────────────────────────────────────────────────────────────────────────────
// C2 - WCAG 2.4.7 : focus visible sur l'alerte de réessai (adaptatif)
// ─────────────────────────────────────────────────────────────────────────────

test('C2 : l\'alerte de réessai a un focus visible (pas outline:none seul)', function (): void {
    $course = fix6Course('c2-retry');
    $lesson = fix6Lesson($course);
    $cat    = fix6Category(fix6Owner($course));
    fix6FillTrueFalse($cat);

    $item = fix6QuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 1],
        'question_behaviour' => 'adaptive', // réessai possible (max 3 par défaut)
    ]);

    $student = fix6Student();
    fix6Enroll($course, $student);
    $this->actingAs($student)->post(fix6StartUrl($course, $lesson, $item));

    // 1 essai RATÉ, non verrouillé → l'alerte « Réessayez » s'affiche au rechargement.
    $this->actingAs($student)->post(fix6VerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => 1]);

    $html = fix6ShowHtml($course, $lesson, $student);
    expect($html)->toContain('Réessayez');
    expect($html)->toContain('academy-quiz-retry-alert');     // classe focusable
    expect($html)->toContain('outline: 2px solid #991B1B');    // style de focus visible
    // L'ancien anti-pattern (outline:none inline sur l'alerte) est retiré.
    expect($html)->not->toContain('border: 1px solid #FCA5A5; outline: none');
});

// ─────────────────────────────────────────────────────────────────────────────
// C3 - éditeur : champs adaptatifs conditionnés au mode adaptatif (x-show)
// ─────────────────────────────────────────────────────────────────────────────

test('C3 : l\'éditeur conditionne les champs adaptatifs au mode (x-show + x-model)', function (): void {
    $course = fix6Course('c3-editor');
    $lesson = fix6Lesson($course);
    $owner  = fix6Owner($course);
    fix6QuizItem($lesson, ['passing_score' => 60]); // quiz sans comportement = différé

    $html = Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->html();

    // Le select de comportement pilote un scope Alpine partagé…
    expect($html)->toContain('x-model="behaviour"');
    // …et les champs adaptatifs ne s'affichent qu'en mode adaptatif.
    expect($html)->toContain("x-show=\"behaviour === 'adaptive'\"");
    // Les champs eux-mêmes existent toujours (build inoffensif s'ils sont envoyés).
    expect($html)->toContain('name="adaptive_penalty"');
    expect($html)->toContain('name="adaptive_max_tries"');
});
