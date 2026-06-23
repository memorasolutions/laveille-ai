<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F23 - Téléchargement CSV du rapport de PARTICIPATION d'un cours. LECTURE SEULE.
 * Le cours est re-résolu serveur (binding par slug) puis autorisé
 * (manageEnrollments = admin OU owner/instructor) AVANT toute génération
 * (anti-IDOR). Le contenu est produit par CourseReportService (DRY) : BOM UTF-8 +
 * séparateur « ; » (robuste Excel FR). Aucune mutation.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Academy\Models\Course;
use Modules\Academy\Services\CourseReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseReportController extends Controller
{
    public function participationCsv(Course $course, CourseReportService $reports): StreamedResponse
    {
        // Autorisation SERVEUR : même gate que l'écran de rapports (pilotage du cours).
        $this->authorize('manageEnrollments', $course);

        $csv = $reports->participationCsv($course);
        $filename = 'participation-' . $course->slug . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }
}
