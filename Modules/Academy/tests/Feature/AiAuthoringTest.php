<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests ciblés de l'Authoring IA (LMS 2026).
 * Jamais d'appel réseau réel : AiService est mocké. Couvre :
 *   (a) drapeau OFF → generateOutline retourne [] sans appeler AiService ;
 *   (b) generateOutline retourne une structure valide depuis JSON mocké ;
 *   (c) InsertOutlineDraftAction crée chapitres/leçons en brouillon (summary marqué) ;
 *   (d) utilisateur sans rôle CourseRole = AuthorizationException sur generateOutline().
 */

declare(strict_types=1);

namespace Modules\Academy\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\Academy\Actions\InsertOutlineDraftAction;
use Modules\Academy\Livewire\AiAuthoringModal;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Services\AcademyAuthoringAiService;
use Modules\AI\Services\AiService;
use Tests\TestCase;

class AiAuthoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Fixtures
    // -------------------------------------------------------------------------

    private function makeCourse(string $slug = 'cours-authoring'): Course
    {
        return Course::create([
            'slug'        => $slug,
            'title'       => 'Cours Authoring IA',
            'language'    => 'fr-CA',
            'level'       => 'intro',
            'visibility'  => 'public',
            'access_type' => 'free',
            'status'      => 'draft',
            'currency'    => 'CAD',
        ]);
    }

    private function makeOwner(Course $course): User
    {
        $user = User::factory()->create();
        $user->assignRole('instructor');
        CourseRole::create([
            'course_id' => $course->id,
            'user_id'   => $user->id,
            'role'      => 'owner',
        ]);

        return $user;
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_drapeau_off_outline_retourne_tableau_vide(): void
    {
        config(['academy.ai_authoring_enabled' => false]);

        $service = new AcademyAuthoringAiService();
        $result  = $service->generateOutline('Un cours sur l\'IA');

        $this->assertSame([], $result);
    }

    public function test_generate_outline_depuis_json_ia_mocke(): void
    {
        config(['academy.ai_authoring_enabled' => true]);

        $mockJson = '{"chapters":[{"title":"Introduction à l\'IA","lessons":[{"title":"Définitions clés","objective":"Comprendre les concepts fondamentaux"}]}]}';

        $this->mock(AiService::class, function ($mock) use ($mockJson): void {
            $mock->shouldReceive('chatWithHistory')
                ->once()
                ->andReturn($mockJson);
        });

        $service = new AcademyAuthoringAiService();
        $result  = $service->generateOutline('Introduction à l\'intelligence artificielle');

        $this->assertArrayHasKey('chapters', $result);
        $this->assertNotEmpty($result['chapters']);
        $this->assertSame('Introduction à l\'IA', $result['chapters'][0]['title']);
        $this->assertArrayHasKey('lessons', $result['chapters'][0]);
        $this->assertSame('Définitions clés', $result['chapters'][0]['lessons'][0]['title']);
        $this->assertSame('Comprendre les concepts fondamentaux', $result['chapters'][0]['lessons'][0]['objective']);
    }

    public function test_insert_outline_cree_chapitres_en_brouillon(): void
    {
        config(['academy.ai_authoring_enabled' => true]);

        $course = $this->makeCourse('cours-brouillon');
        $owner  = $this->makeOwner($course);

        $outline = [
            'chapters' => [
                [
                    'title'   => 'Module 1 : Fondements',
                    'lessons' => [
                        ['title' => 'Leçon 1 – Introduction', 'objective' => 'Saisir les bases du sujet'],
                        ['title' => 'Leçon 2 – Concepts',     'objective' => 'Identifier les concepts clés'],
                    ],
                ],
            ],
        ];

        $action = app(InsertOutlineDraftAction::class);
        $result = $action($course, $outline, $owner);

        $this->assertSame(1, $result['chapters_created']);
        $this->assertSame(2, $result['lessons_created']);

        $chapter = Chapter::where('course_id', $course->id)->where('title', 'Module 1 : Fondements')->first();
        $this->assertNotNull($chapter);
        $this->assertStringContainsString('généré par IA', (string) $chapter->summary);

        $lesson = Lesson::where('chapter_id', $chapter->id)->where('title', 'Leçon 1 – Introduction')->first();
        $this->assertNotNull($lesson);
        $this->assertSame('Saisir les bases du sujet', $lesson->summary);
    }

    public function test_non_staff_generer_outline_refuse(): void
    {
        config(['academy.ai_authoring_enabled' => true]);

        $course   = $this->makeCourse('cours-gate');
        $intruder = User::factory()->create();

        // Un utilisateur sans CourseRole est refusé (403) dès le montage du
        // composant (authorize('update', $course) dans mount()). Aucun appel IA.
        $this->mock(AiService::class)->shouldNotReceive('chatWithHistory');

        Livewire::actingAs($intruder)
            ->test(AiAuthoringModal::class, ['course' => $course])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // (e) Disjoncteur DoS financier : rate-limit 20/heure sur generateOutline()
    // -------------------------------------------------------------------------

    public function test_rate_limit_generate_outline_bloque_apres_20_par_heure(): void
    {
        config(['academy.ai_authoring_enabled' => true]);

        $course = $this->makeCourse('cours-rate-limit-outline');
        $owner  = $this->makeOwner($course);

        // Pré-remplit le disjoncteur à sa limite (20/heure) sans jamais appeler l'IA.
        $key = 'ai-authoring-outline:' . $owner->id;
        for ($i = 0; $i < 20; $i++) {
            RateLimiter::hit($key, 3600);
        }

        // La limite étant atteinte, AUCUN appel IA ne doit être effectué (21e tentative bloquée).
        $this->mock(AiService::class)->shouldNotReceive('chatWithHistory');

        $component = Livewire::actingAs($owner)
            ->test(AiAuthoringModal::class, ['course' => $course])
            ->set('outlinePrompt', 'Un cours sur l\'IA')
            ->call('generateOutline')
            ->assertSet('generatedOutline', []);

        $this->assertStringContainsString('20/heure', $component->get('errorMessage'));
    }
}
