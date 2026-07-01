<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests ciblés de la Traduction IA (LMS 2026).
 * Jamais d'appel réseau réel : AiService est mocké. Couvre :
 *   (a) drapeau OFF → translateField retourne '' sans appeler AiService ;
 *   (b) translateField retourne le texte traduit depuis une réponse IA mockée ;
 *   (c) utilisateur sans rôle CourseRole = AuthorizationException sur le composant Livewire.
 */

declare(strict_types=1);

namespace Modules\Academy\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\Academy\Livewire\TranslateFieldModal;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Services\AcademyTranslationAiService;
use Modules\AI\Services\AiService;
use Tests\TestCase;

class AiTranslationTest extends TestCase
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

    private function makeCourse(string $slug = 'cours-traduction'): Course
    {
        return Course::create([
            'slug'        => $slug,
            'title'       => 'Cours Traduction IA',
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

    public function test_drapeau_off_translate_field_retourne_vide(): void
    {
        config(['academy.ai_translation_enabled' => false]);

        $this->mock(AiService::class)->shouldNotReceive('chatWithHistory');

        $service = new AcademyTranslationAiService();
        $result  = $service->translateField('Bonjour le monde', 'fr', 'en');

        $this->assertSame('', $result);
    }

    public function test_translate_field_depuis_texte_ia_mocke(): void
    {
        config(['academy.ai_translation_enabled' => true]);

        $this->mock(AiService::class, function ($mock): void {
            $mock->shouldReceive('chatWithHistory')
                ->once()
                ->andReturn('Hello world');
        });

        $service = new AcademyTranslationAiService();
        $result  = $service->translateField('Bonjour le monde', 'fr', 'en');

        $this->assertSame('Hello world', $result);
    }

    public function test_translate_field_texte_vide_retourne_vide_sans_appel_ia(): void
    {
        config(['academy.ai_translation_enabled' => true]);

        $this->mock(AiService::class)->shouldNotReceive('chatWithHistory');

        $service = new AcademyTranslationAiService();
        $result  = $service->translateField('   ', 'fr', 'en');

        $this->assertSame('', $result);
    }

    public function test_non_staff_traduire_refuse(): void
    {
        config(['academy.ai_translation_enabled' => true]);

        $course   = $this->makeCourse('cours-traduction-gate');
        $intruder = User::factory()->create();

        // Un utilisateur sans CourseRole est refusé (403) dès le montage du
        // composant (authorize('manageStructure', $course) dans mount()). Aucun appel IA.
        $this->mock(AiService::class)->shouldNotReceive('chatWithHistory');

        Livewire::actingAs($intruder)
            ->test(TranslateFieldModal::class, ['course' => $course])
            ->assertForbidden();
    }

    public function test_owner_peut_ouvrir_et_traduire(): void
    {
        config(['academy.ai_translation_enabled' => true]);

        $course = $this->makeCourse('cours-traduction-owner');
        $owner  = $this->makeOwner($course);

        $this->mock(AiService::class, function ($mock): void {
            $mock->shouldReceive('chatWithHistory')
                ->once()
                ->andReturn('Hello world');
        });

        Livewire::actingAs($owner)
            ->test(TranslateFieldModal::class, ['course' => $course])
            ->set('sourceText', 'Bonjour le monde')
            ->set('sourceLocale', 'fr')
            ->set('targetLocale', 'en')
            ->call('translate')
            ->assertSet('translatedText', 'Hello world')
            ->call('confirmTranslation')
            ->assertSet('isConfirmed', true);
    }

    // -------------------------------------------------------------------------
    // Disjoncteur DoS financier : rate-limit 20/heure sur translate()
    // -------------------------------------------------------------------------

    public function test_rate_limit_translate_bloque_apres_20_par_heure(): void
    {
        config(['academy.ai_translation_enabled' => true]);

        $course = $this->makeCourse('cours-traduction-rate-limit');
        $owner  = $this->makeOwner($course);

        // Pré-remplit le disjoncteur à sa limite (20/heure) sans jamais appeler l'IA.
        $key = 'ai-translate:' . $owner->id;
        for ($i = 0; $i < 20; $i++) {
            RateLimiter::hit($key, 3600);
        }

        // La limite étant atteinte, AUCUN appel IA ne doit être effectué (21e tentative bloquée).
        $this->mock(AiService::class)->shouldNotReceive('chatWithHistory');

        $component = Livewire::actingAs($owner)
            ->test(TranslateFieldModal::class, ['course' => $course])
            ->set('sourceText', 'Bonjour le monde')
            ->set('sourceLocale', 'fr')
            ->set('targetLocale', 'en')
            ->call('translate')
            ->assertSet('translatedText', '');

        $this->assertStringContainsString('20/heure', $component->get('errorMessage'));
    }
}
