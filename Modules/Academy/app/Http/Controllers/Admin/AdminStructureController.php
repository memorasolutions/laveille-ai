<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Illuminate\View\View;
use Modules\Academy\Models\LessonItem;

class AdminStructureController extends Controller
{
    // ── Chapitres ──────────────────────────────────────────────────────────────

    public function addChapter(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
        ]);

        $position = (int) Chapter::where('course_id', $course->id)->max('position') + 1;

        Chapter::create([
            'course_id' => $course->id,
            'title'     => $request->title,
            'position'  => $position,
            'summary'   => $request->summary,
        ]);

        return back()->with('success', 'Chapitre ajouté avec succès.');
    }

    public function updateChapter(Request $request, Chapter $chapter): RedirectResponse
    {
        $request->validate([
            'title'    => 'required|string|max:200',
            'summary'  => 'nullable|string|max:1000',
            'position' => 'nullable|integer|min:1',
        ]);

        $chapter->update($request->only(['title', 'summary', 'position']));

        return back()->with('success', 'Chapitre mis à jour.');
    }

    public function deleteChapter(Chapter $chapter): RedirectResponse
    {
        $chapter->delete();

        return back()->with('success', 'Chapitre supprimé.');
    }

    // ── Leçons ─────────────────────────────────────────────────────────────────

    public function addLesson(Request $request, Chapter $chapter): RedirectResponse
    {
        $request->validate([
            'title'             => 'required|string|max:255',
            'summary'           => 'nullable|string|max:1000',
            'estimated_minutes' => 'nullable|integer|min:1',
        ]);

        $position = (int) Lesson::where('chapter_id', $chapter->id)->max('position') + 1;

        Lesson::create([
            'chapter_id'        => $chapter->id,
            'title'             => $request->title,
            'slug'              => Str::slug($request->title),
            'position'          => $position,
            'summary'           => $request->summary,
            'estimated_minutes' => $request->estimated_minutes,
        ]);

        return back()->with('success', 'Leçon ajoutée avec succès.');
    }

    public function editLesson(Lesson $lesson): View
    {
        $lesson->load(['lessonItems', 'chapter.course']);

        return view('academy::admin.lessons.edit', compact('lesson'));
    }

    public function updateLesson(Request $request, Lesson $lesson): RedirectResponse
    {
        $request->validate([
            'title'             => 'required|string|max:255',
            'slug'              => 'required|string|max:255',
            'summary'           => 'nullable|string|max:1000',
            'estimated_minutes' => 'nullable|integer|min:1',
            'position'          => 'required|integer|min:1',
        ]);

        $lesson->update($request->only(['title', 'slug', 'summary', 'estimated_minutes', 'position']));

        return redirect()->route('admin.academy.lessons.edit', $lesson)
            ->with('success', 'Leçon mise à jour.');
    }

    public function deleteLesson(Lesson $lesson): RedirectResponse
    {
        $courseId = $lesson->chapter->course_id;
        $lesson->delete();

        return redirect()->route('admin.academy.courses.edit', $courseId)
            ->with('success', 'Leçon supprimée.');
    }

    // ── Éléments de leçon ──────────────────────────────────────────────────────

    public function addItem(Request $request, Lesson $lesson): RedirectResponse
    {
        $request->validate([
            'type'        => 'required|in:video,quiz,doc',
            'title'       => 'required|string|max:255',
            'position'    => 'nullable|integer|min:1',
            'is_required' => 'nullable|boolean',
        ]);

        $payload = $this->buildPayload($request);

        $position = $request->filled('position')
            ? (int) $request->position
            : (int) LessonItem::where('lesson_id', $lesson->id)->max('position') + 1;

        LessonItem::create([
            'lesson_id'         => $lesson->id,
            'type'              => $request->type,
            'title'             => $request->title,
            'position'          => $position,
            'payload'           => $payload,
            'estimated_minutes' => $request->estimated_minutes,
            'is_required'       => $request->boolean('is_required'),
            'external_ref'      => $request->external_ref,
        ]);

        return back()->with('success', 'Élément ajouté avec succès.');
    }

    public function updateItem(Request $request, LessonItem $item): RedirectResponse
    {
        $request->validate([
            'type'        => 'required|in:video,quiz,doc',
            'title'       => 'required|string|max:255',
            'position'    => 'required|integer|min:1',
            'is_required' => 'nullable|boolean',
        ]);

        $payload = $this->buildPayload($request);

        $item->update([
            'type'              => $request->type,
            'title'             => $request->title,
            'position'          => (int) $request->position,
            'payload'           => $payload,
            'estimated_minutes' => $request->estimated_minutes,
            'is_required'       => $request->boolean('is_required'),
            'external_ref'      => $request->external_ref,
        ]);

        return back()->with('success', 'Élément mis à jour.');
    }

    public function deleteItem(LessonItem $item): RedirectResponse
    {
        $item->delete();

        return back()->with('success', 'Élément supprimé.');
    }

    // ── Privé ──────────────────────────────────────────────────────────────────

    private function buildPayload(Request $request): array
    {
        return match ($request->type) {
            'video' => (function () use ($request): array {
                $request->validate([
                    'player_url'       => 'required|url',
                    'duration_seconds' => 'nullable|integer|min:1',
                ]);

                return [
                    'player_url'       => $request->player_url,
                    'duration_seconds' => $request->duration_seconds ? (int) $request->duration_seconds : null,
                ];
            })(),
            'quiz' => (function () use ($request): array {
                $request->validate([
                    'qt_bank_key'   => 'required|string',
                    'passing_score' => 'required|integer|between:0,100',
                ]);

                return [
                    'qt_bank_key'   => $request->qt_bank_key,
                    'passing_score' => (int) $request->passing_score,
                ];
            })(),
            'doc' => [
                'content' => $request->content ?? '',
            ],
            default => [],
        };
    }
}
