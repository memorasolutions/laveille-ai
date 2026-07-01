<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests du FEEDBACK IA sur réponses ouvertes (correction assistée LMS 2026).
 * Principe : jamais d'appel réseau réel (AiService mocké). Couvre :
 *   (a) drapeau OFF → aucune action IA, aucune écriture ;
 *   (b) le service construit un prompt contenant la rubrique + le texte de la remise ;
 *   (c) la note IA est SUGGÉRÉE (colonne ai_*), jamais la note officielle sans le
 *       formateur (score/feedback restent intacts tant qu'il n'a pas enregistré) ;
 *   (d) gating : un non-staff est refusé (403).
 */

declare(strict_types=1);

namespace Modules\Academy\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseAssignments;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\RubricCriterion;
use Modules\Academy\Models\RubricLevel;
use Modules\Academy\Models\Submission;
use Modules\Academy\Services\AcademyFeedbackAiService;
use Modules\AI\Services\AiService;
use Tests\TestCase;

class AcademyAiFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Rôles/permissions requis par la CoursePolicy (instructor/student/staff).
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Fixtures
    // -------------------------------------------------------------------------

    private function makeCourse(string $slug = 'cours-fb'): Course
    {
        return Course::create([
            'slug'        => $slug,
            'title'       => 'Cours feedback',
            'language'    => 'fr-CA',
            'level'       => 'intro',
            'visibility'  => 'public',
            'access_type' => 'free',
            'status'      => 'published',
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

    private function makeStudent(Course $course): User
    {
        $user = User::factory()->create();
        $user->assignRole('student');
        Enrollment::create([
            'course_id'   => $course->id,
            'user_id'     => $user->id,
            'status'      => 'active',
            'source'      => 'admin',
            'enrolled_at' => now(),
        ]);

        return $user;
    }

    private function makeAssignment(Course $course, array $attrs = []): Assignment
    {
        return Assignment::create(array_merge([
            'course_id'    => $course->id,
            'title'        => 'Étude de cas',
            'instructions' => 'Analysez la situation et proposez une recommandation.',
            'max_points'   => 100,
            'is_published' => true,
            'position'     => 1,
        ], $attrs));
    }

    private function makeSubmission(Assignment $assignment, User $student, string $body): Submission
    {
        return Submission::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
            'body'          => $body,
            'submitted_at'  => now(),
        ]);
    }

    private function makeRubric(Assignment $assignment): void
    {
        $criterion = RubricCriterion::create([
            'assignment_id' => $assignment->id,
            'description'   => 'Clarté de l\'argumentation',
            'position'      => 1,
        ]);
        RubricLevel::create(['criterion_id' => $criterion->id, 'description' => 'Excellent', 'points' => 10, 'position' => 1]);
        RubricLevel::create(['criterion_id' => $criterion->id, 'description' => 'À améliorer', 'points' => 5, 'position' => 2]);
    }

    // -------------------------------------------------------------------------
    // (a) Drapeau OFF → aucune action IA, aucune écriture
    // -------------------------------------------------------------------------

    public function test_drapeau_off_aucune_action_ni_ecriture(): void
    {
        config(['academy.ai_feedback_enabled' => false]);

        $course     = $this->makeCourse();
        $owner      = $this->makeOwner($course);
        $student    = $this->makeStudent($course);
        $assignment = $this->makeAssignment($course);
        $submission = $this->makeSubmission($assignment, $student, 'Ma réponse rédigée.');

        // Aucun appel IA ne doit être effectué quand le drapeau est OFF.
        $this->mock(AiService::class)
            ->shouldNotReceive('chatWithHistory');

        Livewire::actingAs($owner)
            ->test(CourseAssignments::class, ['course' => $course])
            ->call('proposeAiFeedback', $submission->id)
            ->assertSet('aiFeedbackSubmission', null);

        $submission->refresh();
        $this->assertNull($submission->ai_feedback);
        $this->assertNull($submission->ai_suggested_score);
        $this->assertNull($submission->ai_generated_at);
    }

    // -------------------------------------------------------------------------
    // (b) Le service construit un prompt avec la rubrique + le texte de la remise
    // -------------------------------------------------------------------------

    public function test_service_construit_prompt_avec_rubrique_et_remise(): void
    {
        $course     = $this->makeCourse('cours-prompt');
        $student    = $this->makeStudent($course);
        $assignment = $this->makeAssignment($course);
        $this->makeRubric($assignment);

        $body     = 'Voici mon analyse détaillée du cas soumis.';
        $service  = new AcademyFeedbackAiService();
        $messages = $service->buildMessages($assignment->fresh(), $body, 100);

        $system = $messages[0]['content'] ?? '';
        $user   = end($messages);

        // Rubrique présente
        $this->assertStringContainsString('Clarté de l\'argumentation', $system);
        $this->assertStringContainsString('RUBRIQUE', $system);
        // Barème sur max_points
        $this->assertStringContainsString('100', $system);
        // Format de sortie strict pour extraire la note
        $this->assertStringContainsString('NOTE_SUGGEREE', $system);
        // Le texte de la remise est le message user
        $this->assertSame('user', $user['role']);
        $this->assertStringContainsString($body, $user['content']);
    }

    // -------------------------------------------------------------------------
    // (c) La note IA est SUGGÉRÉE, jamais écrite comme note officielle
    // -------------------------------------------------------------------------

    public function test_note_ia_est_suggeree_jamais_officielle(): void
    {
        config(['academy.ai_feedback_enabled' => true]);

        $course     = $this->makeCourse('cours-suggere');
        $owner      = $this->makeOwner($course);
        $student    = $this->makeStudent($course);
        $assignment = $this->makeAssignment($course);
        $submission = $this->makeSubmission($assignment, $student, 'Réponse de l\'apprenant à évaluer.');

        // Mock : l'IA renvoie une note suggérée + un feedback structuré.
        $this->mock(AiService::class)
            ->shouldReceive('chatWithHistory')
            ->once()
            ->andReturn("NOTE_SUGGEREE: 82\n---\n**Clarté** : « bonne structure ». Piste : approfondir la conclusion.");

        Livewire::actingAs($owner)
            ->test(CourseAssignments::class, ['course' => $course])
            ->call('proposeAiFeedback', $submission->id)
            ->assertSet('aiFeedbackSubmission', $submission->id)
            ->assertSet('aiSuggestedScoreDraft', '82');

        $submission->refresh();

        // Le brouillon IA est persisté dans les colonnes DISTINCTES.
        $this->assertNotNull($submission->ai_generated_at);
        $this->assertSame('82.00', (string) $submission->ai_suggested_score);
        $this->assertStringContainsString('Clarté', (string) $submission->ai_feedback);

        // La note et le feedback OFFICIELS restent INTACTS (le formateur n'a rien validé).
        $this->assertNull($submission->score);
        $this->assertNull($submission->feedback);
        $this->assertNull($submission->graded_at);
        $this->assertFalse($submission->isGraded());
    }

    // -------------------------------------------------------------------------
    // (d) Gating : un non-staff est refusé
    // -------------------------------------------------------------------------

    public function test_gating_non_staff_refuse(): void
    {
        config(['academy.ai_feedback_enabled' => true]);

        $course     = $this->makeCourse('cours-gate');
        $student    = $this->makeStudent($course);
        $assignment = $this->makeAssignment($course);
        $submission = $this->makeSubmission($assignment, $student, 'Ma réponse.');

        // Aucun appel IA ne doit passer : le non-staff est refusé (403) dès le
        // montage du composant de correction (manageStructure), donc bien avant
        // toute possibilité d'action IA. Défense serveur, jamais le navigateur.
        $this->mock(AiService::class)
            ->shouldNotReceive('chatWithHistory');

        Livewire::actingAs($student)
            ->test(CourseAssignments::class, ['course' => $course])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // (e) Disjoncteur DoS financier : rate-limit 20/heure sur proposeAiFeedback()
    // -------------------------------------------------------------------------

    public function test_rate_limit_propose_ai_feedback_bloque_apres_20_par_heure(): void
    {
        config(['academy.ai_feedback_enabled' => true]);

        $course     = $this->makeCourse('cours-fb-rate-limit');
        $owner      = $this->makeOwner($course);
        $student    = $this->makeStudent($course);
        $assignment = $this->makeAssignment($course);
        $submission = $this->makeSubmission($assignment, $student, 'Réponse de l\'apprenant à évaluer.');

        // Pré-remplit le disjoncteur à sa limite (20/heure) sans jamais appeler l'IA.
        $key = 'ai-feedback:' . $owner->id;
        for ($i = 0; $i < 20; $i++) {
            RateLimiter::hit($key, 3600);
        }

        // La limite étant atteinte, AUCUN appel IA ne doit être effectué (21e tentative bloquée).
        $this->mock(AiService::class)->shouldNotReceive('chatWithHistory');

        $component = Livewire::actingAs($owner)
            ->test(CourseAssignments::class, ['course' => $course])
            ->call('proposeAiFeedback', $submission->id)
            ->assertSet('aiFeedbackSubmission', null);

        $this->assertStringContainsString('20/heure', $component->get('aiFeedbackError'));

        $submission->refresh();
        $this->assertNull($submission->ai_feedback);
    }
}
