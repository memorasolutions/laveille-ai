<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Import SCORM - LECTEUR (page chargée dans l'iframe SANDBOX du lecteur de
 * leçon). Cette page définit le PONT JS-PHP minimal (window.API SCORM 1.2 /
 * window.API_1484_11 SCORM 2004 basique) puis charge le SCO dans une iframe
 * IMBRIQUÉE pointant vers ScormAssetController (disque privé, jamais public).
 *
 * SÉCURITÉ (anti-IDOR + gating, même pattern que H5pPlayerController/
 * VideoRedirectController - DRY via LessonAccessService) :
 *  - drapeau academy.scorm_enabled vérifié en tête (404 si désactivé) ;
 *  - l'item est RE-RÉSOLU côté serveur (appartenance leçon/cours) ;
 *  - l'accès est RE-VÉRIFIÉ intégralement (LessonAccessService::canAccessItem) ;
 *  - AUCUNE URL de contenu n'est exposée hors de cette page gatée.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Services\LessonAccessService;

class ScormPlayerController extends Controller
{
    public function play(Request $request, Course $course, Lesson $lesson, int $itemId): \Illuminate\Http\Response
    {
        abort_unless((bool) config('academy.scorm_enabled', false), 404);

        $item = LessonAccessService::resolveItem($course, $lesson, $itemId, 'scorm');
        if ($item === null) {
            abort(404);
        }

        if (! LessonAccessService::canAccessItem($request->user(), $course, $item)) {
            abort(403);
        }

        $scormPath = $item->payload['scorm_path'] ?? null;
        $launchUrl = $item->payload['scorm_launch_url'] ?? null;

        if (empty($scormPath) || empty($launchUrl)) {
            abort(404);
        }

        // Nonce CSP à usage unique pour le pont API SCORM (script inline minimal,
        // aucun 'unsafe-inline' générique - plus strict que le player H5P).
        $nonce = base64_encode(random_bytes(16));

        $launchSrc = route('academy.scorm.asset', [
            'course'   => $course,
            'lesson'   => $lesson,
            'itemId'   => $item->id,
            'path'     => $launchUrl,
        ]);

        $commitUrl = route('academy.scorm.commit', [$course, $lesson, $item->id]);

        $html = view('academy::public.scorm-player', [
            'title'      => $item->title ?? ($item->payload['scorm_title'] ?? 'Contenu SCORM'),
            'launchSrc'  => $launchSrc,
            'commitUrl'  => $commitUrl,
            'nonce'      => $nonce,
        ])->render();

        // CSP DÉDIÉE, PLUS STRICTE que H5P : tout le contenu (SCO + assets) est
        // servi EN SAME-ORIGIN via ScormAssetController, aucun CDN externe requis.
        // Le script du pont API est le SEUL script inline, autorisé par nonce.
        $siteHost = (string) config('academy.site_host');
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "media-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-src 'self'",
            "frame-ancestors 'self' https://{$siteHost} https://*.{$siteHost}",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        return response($html, 200)
            ->header('Content-Security-Policy', $csp)
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
