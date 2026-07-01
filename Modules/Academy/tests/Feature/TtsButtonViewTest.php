<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Narration TTS (Web Speech API native, accessibilité).
 *
 * Prouve le GATING du bouton « Écouter cette leçon » sur la page de leçon (vue classique) :
 *  - academy.tts_enabled = false (défaut) → bouton ABSENT du DOM rendu ;
 *  - academy.tts_enabled = true            → bouton présent (aucun appel réseau/tiers,
 *    Web Speech API 100% navigateur, aucune régression du reste de la page).
 *
 * Note : la route academy.lessons.show exige `auth` (fix B01 - contenu jamais public aux
 * guests) ; on authentifie donc un utilisateur lambda pour atteindre la page.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

/** Helper : cours publié + public avec un chapitre + une leçon (gratuit). */
function makeTtsLesson(string $slug): Lesson
{
    $course = Course::create([
        'slug'          => $slug,
        'title'         => 'Cours '.$slug,
        'language'      => 'fr-CA',
        'level'         => 'intro',
        'visibility'    => 'public',
        'access_type'   => 'free',
        'status'        => 'published',
        'currency'      => 'CAD',
        'published_at'  => now(),
    ]);

    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre 1',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon narrée',
        'slug'       => 'lecon-narree-'.$course->id,
        'summary'    => 'Résumé de la leçon pour la narration.',
        'position'   => 1,
    ]);
}

test('drapeau academy.tts_enabled à false (défaut) → bouton TTS absent du DOM', function (): void {
    config()->set('academy.tts_enabled', false);

    $lesson = makeTtsLesson('tts-off');
    $course = $lesson->chapter->course;
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get(route('academy.lessons.show', ['course' => $course->slug, 'lesson' => $lesson]))
        ->assertOk()
        ->assertDontSee('Écouter cette leçon', false)
        ->assertDontSee('speechSynthesis', false);
});

test('drapeau academy.tts_enabled à true → bouton TTS présent dans le DOM', function (): void {
    config()->set('academy.tts_enabled', true);

    $lesson = makeTtsLesson('tts-on');
    $course = $lesson->chapter->course;
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get(route('academy.lessons.show', ['course' => $course->slug, 'lesson' => $lesson]))
        ->assertOk()
        ->assertSee('Écouter cette leçon', false)
        ->assertSee('speechSynthesis', false);
});
