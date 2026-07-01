<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest/PHPUnit du tuteur IA Academy (TutorChat + AcademyTutorService).
 * Principe : jamais d'appel réseau réel (AiService mocké), SQLite safe.
 */

declare(strict_types=1);

namespace Modules\Academy\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\TutorChat;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\AcademyTutorService;
use Modules\AI\Services\AiService;
use Tests\TestCase;

class AcademyTutorTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers de fixture (inline — aucune factory externe requise)
    // -------------------------------------------------------------------------

    private function makeCourse(string $slug = 'cours-test'): Course
    {
        $course = new Course([
            'slug'        => $slug,
            'title'       => 'Cours test',
            'language'    => 'fr',
            'level'       => 'beginner',
            'visibility'  => 'public',
            'access_type' => 'free',
            'status'      => 'published',
        ]);
        $course->save();

        return $course;
    }

    private function makeChapter(Course $course): Chapter
    {
        $chapter = new Chapter([
            'course_id' => $course->id,
            'title'     => 'Chapitre test',
            'position'  => 1,
        ]);
        $chapter->save();

        return $chapter;
    }

    private function makeLesson(Chapter $chapter, string $slug = 'lecon-test'): Lesson
    {
        $lesson = new Lesson([
            'chapter_id' => $chapter->id,
            'title'      => 'Leçon test',
            'slug'       => $slug,
            'position'   => 1,
            'summary'    => 'Résumé test de la leçon',
        ]);
        $lesson->save();

        return $lesson;
    }

    private function makeLessonItem(Lesson $lesson, string $richText = 'Contenu important de la leçon'): LessonItem
    {
        $item = new LessonItem([
            'lesson_id' => $lesson->id,
            'type'      => 'doc',
            'title'     => 'Item test',
            'position'  => 1,
            'payload'   => ['rich_text' => $richText],
        ]);
        $item->save();

        return $item;
    }

    private function enroll(User $user, Course $course): void
    {
        Enrollment::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'status'      => 'active',
            'source'      => 'manual',
            'enrolled_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Test 1 : gating — non-inscrit refusé à chaque action
    // -------------------------------------------------------------------------

    public function test_gating_refuse_non_inscrit(): void
    {
        $user    = User::factory()->create();
        $course  = $this->makeCourse();
        $chapter = $this->makeChapter($course);
        $lesson  = $this->makeLesson($chapter);

        Livewire::actingAs($user)
            ->test(TutorChat::class, ['lesson' => $lesson, 'course' => $course])
            ->call('sendQuestion')
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Test 2 : le service construit un contexte contenant le texte de la leçon
    // -------------------------------------------------------------------------

    public function test_service_construit_contexte_avec_contenu_lecon(): void
    {
        $course  = $this->makeCourse();
        $chapter = $this->makeChapter($course);
        $lesson  = $this->makeLesson($chapter);
        $this->makeLessonItem($lesson, 'Contenu important de la leçon');

        $service = new AcademyTutorService();
        $context = $service->buildContext($lesson, $course);

        $this->assertStringContainsString('Contenu important de la leçon', $context);
        $this->assertStringContainsString('Leçon test', $context);
        $this->assertStringContainsString('Résumé test de la leçon', $context);
    }

    // -------------------------------------------------------------------------
    // Test 3 : le service tronque le contexte à MAX_CONTEXT_CHARS
    // -------------------------------------------------------------------------

    public function test_service_tronque_le_contexte(): void
    {
        $course  = $this->makeCourse('cours-long');
        $chapter = $this->makeChapter($course);
        $lesson  = $this->makeLesson($chapter, 'lecon-longue');
        $this->makeLessonItem($lesson, str_repeat('A', 10000));

        $service = new AcademyTutorService();
        $context = $service->buildContext($lesson, $course);

        $this->assertLessThanOrEqual(AcademyTutorService::MAX_CONTEXT_CHARS, strlen($context));
    }

    // -------------------------------------------------------------------------
    // Test 4 : le service construit un prompt contenant les informations clés
    // -------------------------------------------------------------------------

    public function test_service_construit_prompt_strict(): void
    {
        $service  = new AcademyTutorService();
        $messages = $service->buildMessages(
            context:     'Contenu de la leçon',
            lessonTitle: 'Ma leçon',
            courseTitle: 'Mon cours',
            question:    'Question test',
            history:     []
        );

        $systemContent = $messages[0]['content'] ?? '';

        $this->assertStringContainsString('Mon cours', $systemContent);
        $this->assertStringContainsString('Ma leçon', $systemContent);
        $this->assertStringContainsString('Contenu de la leçon', $systemContent);
        $this->assertStringContainsString('UNIQUEMENT', $systemContent);

        // La question est le dernier message
        $lastMessage = end($messages);
        $this->assertSame('user', $lastMessage['role']);
        $this->assertSame('Question test', $lastMessage['content']);
    }

    // -------------------------------------------------------------------------
    // Test 5 : question envoyée avec un inscrit actif — réponse IA mockée
    // -------------------------------------------------------------------------

    public function test_question_envoyee_avec_inscrit(): void
    {
        $user    = User::factory()->create();
        $course  = $this->makeCourse('cours-inscrit');
        $chapter = $this->makeChapter($course);
        $lesson  = $this->makeLesson($chapter, 'lecon-inscrit');
        $this->makeLessonItem($lesson);
        $this->enroll($user, $course);

        // ACTION: Mock AiService — jamais d'appel réseau dans les tests
        $mockAi = $this->mock(AiService::class);
        $mockAi->shouldReceive('chatWithHistory')
            ->once()
            ->andReturn('Réponse test du tuteur IA');

        $component = Livewire::actingAs($user)
            ->test(TutorChat::class, ['lesson' => $lesson, 'course' => $course])
            ->set('question', 'Explique-moi la leçon svp')
            ->call('sendQuestion');

        // Vérifie que l'historique contient les deux rôles
        $messages = $component->get('messages');

        $this->assertCount(2, $messages);
        $this->assertSame('user', $messages[0]['role']);
        $this->assertSame('Explique-moi la leçon svp', $messages[0]['content']);
        $this->assertSame('assistant', $messages[1]['role']);
        $this->assertSame('Réponse test du tuteur IA', $messages[1]['content']);
        $this->assertFalse($messages[1]['error']);
    }

    // -------------------------------------------------------------------------
    // Test 6 : clearHistory vide les messages
    // -------------------------------------------------------------------------

    public function test_clear_history_vide_les_messages(): void
    {
        $user    = User::factory()->create();
        $course  = $this->makeCourse('cours-clear');
        $chapter = $this->makeChapter($course);
        $lesson  = $this->makeLesson($chapter, 'lecon-clear');
        $this->enroll($user, $course);

        Livewire::actingAs($user)
            ->test(TutorChat::class, ['lesson' => $lesson, 'course' => $course])
            ->call('clearHistory')
            ->assertSet('messages', []);
    }
}
