<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Progress;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV des données Academy (inscriptions, complétions, progression).
 * Gâté par la permission `academy.reports.view` (spatie/laravel-permission).
 * Utilise fputcsv + cursor() pour économiser la mémoire.
 * UTF-8 BOM inclus pour la compatibilité Excel/LibreOffice.
 */
class ExportController extends Controller
{
    public function exportEnrollments(Request $request): StreamedResponse
    {
        if ($request->user()->cannot('academy.reports.view')) {
            abort(403);
        }

        $courseId = (int) $request->input('course_id', 0);
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $filename = 'academy-enrollments-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($courseId, $dateFrom, $dateTo) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8

            fputcsv($output, ['ID', 'Utilisateur', 'Courriel', 'Cours', 'Statut', 'Source', 'Inscrit le']);

            Enrollment::with(['user', 'course'])
                ->when($courseId > 0, fn ($q) => $q->where('course_id', $courseId))
                ->when($dateFrom, fn ($q) => $q->where('enrolled_at', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->where('enrolled_at', '<=', $dateTo . ' 23:59:59'))
                ->orderBy('enrolled_at', 'desc')
                ->cursor()
                ->each(function ($enrollment) use ($output) {
                    fputcsv($output, [
                        $enrollment->id,
                        $enrollment->user?->name ?? '',
                        $enrollment->user?->email ?? '',
                        $enrollment->course?->title ?? '',
                        $enrollment->status,
                        $enrollment->source,
                        $enrollment->enrolled_at?->format('Y-m-d H:i:s') ?? '',
                    ]);
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportCompletions(Request $request): StreamedResponse
    {
        if ($request->user()->cannot('academy.reports.view')) {
            abort(403);
        }

        $courseId = (int) $request->input('course_id', 0);
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $filename = 'academy-completions-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($courseId, $dateFrom, $dateTo) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8

            fputcsv($output, ['ID', 'Utilisateur', 'Courriel', 'Cours', 'Item', 'Statut', 'Score', 'Complété le']);

            Completion::with(['user', 'course', 'lessonItem'])
                ->when($courseId > 0, fn ($q) => $q->where('course_id', $courseId))
                ->when($dateFrom, fn ($q) => $q->where('completed_at', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
                ->orderBy('completed_at', 'desc')
                ->cursor()
                ->each(function ($completion) use ($output) {
                    fputcsv($output, [
                        $completion->id,
                        $completion->user?->name ?? '',
                        $completion->user?->email ?? '',
                        $completion->course?->title ?? '',
                        $completion->lessonItem?->title ?? '',
                        $completion->status,
                        $completion->score ?? '',
                        $completion->completed_at?->format('Y-m-d H:i:s') ?? '',
                    ]);
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportProgress(Request $request): StreamedResponse
    {
        if ($request->user()->cannot('academy.reports.view')) {
            abort(403);
        }

        $courseId = (int) $request->input('course_id', 0);

        $filename = 'academy-progress-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($courseId) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8

            fputcsv($output, ['ID', 'Utilisateur', 'Courriel', 'Cours', 'Pourcentage', 'Dernière activité']);

            Progress::with(['user', 'course'])
                ->when($courseId > 0, fn ($q) => $q->where('course_id', $courseId))
                ->orderBy('last_activity_at', 'desc')
                ->cursor()
                ->each(function ($progress) use ($output) {
                    fputcsv($output, [
                        $progress->id,
                        $progress->user?->name ?? '',
                        $progress->user?->email ?? '',
                        $progress->course?->title ?? '',
                        $progress->percent,
                        $progress->last_activity_at?->format('Y-m-d H:i:s') ?? '',
                    ]);
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
