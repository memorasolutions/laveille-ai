<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - ProgressService::continueUrlFor() (bouton « Continuer le cours »,
 * academy::public.show). Prouve, de façon AUTONOME (helpers préfixés v6cu) :
 *
 *  - aucune leçon complétée → URL de la TOUTE PREMIÈRE leçon (= resumeLesson) ;
 *  - la première leçon complétée, pas la seconde → URL de la 2e leçon ;
 *  - toutes les leçons complétées → cours complété (all_required) → URL du
 *    certificat émis (recalculate() l'émet automatiquement, DRY, cf. CourseCompletionTest) ;
 *  - jamais de lien mort (href="#") : une URL valide est TOUJOURS retournée.
 *
 * SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ProgressService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

/** Cours gratuit publié minimal (défaut all_required, comme show.blade.php en prod). */
function v6cuCourse(): Course
{
    return Course::create([
        'slug'        => 'v6cu-cours',
        'title'       => 'V6cu Cours',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/**
 * Crée $count leçons ORDONNÉES (1 chapitre), chacune avec un item requis unique.
 * Retourne [Lesson[], LessonItem[]] dans l'ordre de position.
 *
 * @return array{0: array<int, Lesson>, 1: array<int, LessonItem>}
 */
function v6cuLessons(Course $course, int $count): array
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre',
        'position'  => 1,
    ]);

    $lessons = [];
    $items   = [];

    for ($i = 1; $i <= $count; $i++) {
        $lesson = Lesson::create([
            'chapter_id' => $chapter->id,
            'title'      => "Leçon $i",
            'slug'       => "v6cu-lecon-$i",
            'position'   => $i,
        ]);

        $items[] = LessonItem::create([
            'lesson_id'   => $lesson->id,
            'type'        => 'document',
            'title'       => "Item $i",
            'position'    => 1,
            'is_required' => true,
        ]);

        $lessons[] = $lesson;
    }

    return [$lessons, $items];
}

test('aucune leçon complétée : continueUrlFor renvoie la toute première leçon', function (): void {
    $course             = v6cuCourse();
    [$lessons, $items]  = v6cuLessons($course, 3);
    $user               = User::factory()->create();

    $url = ProgressService::continueUrlFor($user, $course);

    expect($url)->toBe(route('academy.lessons.show', [$course, $lessons[0]]));
});

test('1re leçon complétée, pas la 2e : continueUrlFor renvoie la 2e leçon', function (): void {
    $course             = v6cuCourse();
    [$lessons, $items]  = v6cuLessons($course, 3);
    $user               = User::factory()->create();

    Completion::create([
        'user_id'        => $user->id,
        'course_id'      => $course->id,
        'lesson_item_id' => $items[0]->id,
        'status'         => 'completed',
        'completed_at'   => now(),
    ]);
    ProgressService::recalculate($user, $course);

    $url = ProgressService::continueUrlFor($user, $course);

    expect($url)->toBe(route('academy.lessons.show', [$course, $lessons[1]]));
});

test('toutes les leçons complétées : continueUrlFor renvoie le certificat émis', function (): void {
    $course             = v6cuCourse();
    [$lessons, $items]  = v6cuLessons($course, 2);
    $user               = User::factory()->create();

    foreach ($items as $item) {
        Completion::create([
            'user_id'        => $user->id,
            'course_id'      => $course->id,
            'lesson_item_id' => $item->id,
            'status'         => 'completed',
            'completed_at'   => now(),
        ]);
    }
    ProgressService::recalculate($user, $course);

    $certificate = CertificateIssued::where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->first();

    expect($certificate)->not->toBeNull();

    $url = ProgressService::continueUrlFor($user, $course);

    expect($url)->toBe(route('academy.certificates.show', $certificate->public_url_slug));
});
