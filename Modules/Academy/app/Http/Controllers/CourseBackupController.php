<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F15 - Téléchargement de la SAUVEGARDE (.json) d'un cours.
 *
 * SÉCURITÉ (OWASP A01) : le cours est re-résolu côté serveur (binding par slug) puis
 * AUTORISÉ (manageStructure : admin OU owner/instructor/editor de CE cours). Exporter
 * un cours qu'on ne gère pas = 403 (anti-IDOR). Aucune donnée personnelle d'étudiant
 * n'est incluse (le service n'exporte qu'une structure). Réponse en pièce jointe.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Academy\Models\Course;
use Modules\Academy\Services\CourseBackupService;

class CourseBackupController extends Controller
{
    public function export(Course $course, CourseBackupService $service): JsonResponse
    {
        // Autorisation SERVEUR (anti-IDOR) : Gate::authorize lève une 403 si refusé.
        Gate::authorize('manageStructure', $course);

        $data     = $service->export($course);
        $filename = 'academie-cours-'.$course->slug.'-'.now()->timezone('America/Toronto')->format('Y-m-d').'.json';

        return response()
            ->json($data, 200, [
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/json; charset=utf-8');
    }
}
