<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Insère un plan IA en chapitres/leçons BROUILLON dans un cours
 * SELF: < 5 lignes de logique propre (idempotence + positions)
 * RAISON: Séparer la persistance de la génération IA ; le formateur confirme avant l'écriture.
 */

declare(strict_types=1);

namespace Modules\Academy\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;

class InsertOutlineDraftAction
{
    /**
     * Insère le plan généré comme chapitres/leçons brouillon (non publiés).
     * Idempotence : un chapitre existant de même titre est ignoré (pas de doublon).
     * Les chapitres/leçons insérés portent la mention "(généré par IA – à réviser)"
     * dans leur résumé pour traçabilité visible dans l'éditeur.
     *
     * @param  array<string, mixed>  $outline  ['chapters' => [...]]
     * @return array{chapters_created: int, lessons_created: int}
     */
    public function __invoke(Course $course, array $outline, User $user): array
    {
        $empty = ['chapters_created' => 0, 'lessons_created' => 0];

        if (! $course->hasRole($user, ['owner', 'instructor'])) {
            return $empty;
        }

        $chapters = $outline['chapters'] ?? null;

        if (! is_array($chapters) || empty($chapters)) {
            return $empty;
        }

        try {
            return DB::transaction(function () use ($course, $chapters): array {
                $chaptersCreated = 0;
                $lessonsCreated  = 0;

                $existingTitles = Chapter::where('course_id', $course->id)
                    ->pluck('title')
                    ->map(fn (string $t) => mb_strtolower(trim($t)))
                    ->all();

                $maxChapterPos = (int) (Chapter::where('course_id', $course->id)->max('position') ?? 0);

                foreach ($chapters as $chapterData) {
                    $chapterTitle = trim((string) ($chapterData['title'] ?? ''));

                    if ($chapterTitle === '' || in_array(mb_strtolower($chapterTitle), $existingTitles, true)) {
                        continue;
                    }

                    $lessons = is_array($chapterData['lessons'] ?? null) ? $chapterData['lessons'] : [];

                    if (empty($lessons)) {
                        continue;
                    }

                    $firstObjective = '';
                    foreach ($lessons as $l) {
                        $obj = trim((string) ($l['objective'] ?? ''));
                        if ($obj !== '') {
                            $firstObjective = $obj;
                            break;
                        }
                    }

                    $summary  = $firstObjective !== '' ? $firstObjective . ' ' : '';
                    $summary .= '(généré par IA – à réviser)';

                    $chapter = Chapter::create([
                        'course_id' => $course->id,
                        'title'     => $chapterTitle,
                        'position'  => ++$maxChapterPos,
                        'summary'   => $summary,
                    ]);

                    $chaptersCreated++;
                    $existingTitles[] = mb_strtolower($chapterTitle);

                    $maxLessonPos = 0;

                    foreach ($lessons as $lessonData) {
                        $lessonTitle = trim((string) ($lessonData['title'] ?? ''));

                        if ($lessonTitle === '') {
                            continue;
                        }

                        Lesson::create([
                            'chapter_id'         => $chapter->id,
                            'title'              => $lessonTitle,
                            'slug'               => Str::slug($lessonTitle) . '_gen_' . uniqid(),
                            'position'           => ++$maxLessonPos,
                            'summary'            => trim((string) ($lessonData['objective'] ?? '')),
                            'estimated_minutes'  => null,
                            'drip_days'          => null,
                        ]);

                        $lessonsCreated++;
                    }
                }

                return ['chapters_created' => $chaptersCreated, 'lessons_created' => $lessonsCreated];
            });
        } catch (\Throwable) {
            return $empty;
        }
    }
}
