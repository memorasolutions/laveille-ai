<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Proxy vidéo SIGNÉ (« protéger l'accès, pas l'iframe »). Le lien ScreenPal
 * réel n'apparaît JAMAIS dans le HTML envoyé au navigateur élève : la leçon
 * pointe son iframe vers CETTE route (signée, expiration 4 h), qui RE-VÉRIFIE
 * intégralement l'autorisation d'accès à l'item (LessonAccessService, la même
 * logique que LessonController et H5pPlayerController — DRY) puis redirige
 * (302) vers le lien ScreenPal réel. La validité temporelle/signature de
 * l'URL (middleware `signed`) protège contre le rejeu au-delà de 4 h, mais ne
 * remplace jamais la re-vérification d'autorisation : une signature valide
 * copiée-collée par un tiers non autorisé échoue toujours (403).
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Services\LessonAccessService;

class VideoRedirectController extends Controller
{
    /**
     * GET /academie/courses/{course:slug}/lessons/{lesson}/items/{itemId}/video-redirect
     *
     * Sécurité :
     * - middleware `signed` (déclaré sur la route) : rejette toute URL sans
     *   signature valide ou expirée (URL::temporarySignedRoute, 4 h).
     * - re-résolution serveur de l'item (anti-IDOR) : il doit appartenir à
     *   cette leçon, qui doit appartenir à ce cours.
     * - autorisation re-vérifiée intégralement (LessonAccessService) : la
     *   signature valide ne suffit jamais seule, l'inscription/le rôle sont
     *   re-contrôlés à CHAQUE requête.
     */
    public function __invoke(Request $request, Course $course, Lesson $lesson, int $itemId): RedirectResponse
    {
        $item = LessonAccessService::resolveItem($course, $lesson, $itemId, 'video');
        if ($item === null) {
            abort(404);
        }

        if (! LessonAccessService::canAccessItem(auth()->user(), $course, $item)) {
            abort(403);
        }

        $playerUrl = $item->payload['player_url'] ?? ($item->payload['embed'] ?? null);
        if (empty($playerUrl)) {
            abort(404);
        }

        return redirect()->away($playerUrl);
    }
}
