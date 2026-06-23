<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F16 - LECTEUR H5P (page chargée DANS L'IFRAME SANDBOX du lecteur de leçon).
 *
 * Cette page rend le contenu H5P via h5p-standalone (CDN jsdelivr) en pointant
 * sur le dossier extrait (disque public). Elle est servie SUR NOTRE DOMAINE pour
 * que le sandbox `allow-same-origin` autorise le fetch du content.json, mais elle
 * porte sa PROPRE CSP (jsdelivr autorisé pour les scripts/styles du player) :
 * le reste du site garde sa CSP stricte.
 *
 * SÉCURITÉ (anti-IDOR + gating) :
 *  - l'item est RE-RÉSOLU côté serveur et vérifié appartenir à la leçon/au cours ;
 *  - l'accès exige une inscription active OU un gérant en prévisualisation OU un
 *    item marqué « preview » : on ne sert JAMAIS le contenu à un non-autorisé ;
 *  - aucune URL de contenu n'est exposée hors de cette page gatée.
 *
 * NOTE (dette, hors périmètre) : la complétion xAPI « completed » émise par H5P
 * n'est pas (encore) capturée ; l'achèvement se fait par critère « view » (V2-c)
 * dans le LessonController. À brancher plus tard via postMessage + xAPI listener.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\H5pPackageService;

class H5pPlayerController extends Controller
{
    public function play(Request $request, Course $course, Lesson $lesson, int $itemId): \Illuminate\Http\Response
    {
        // 1. RE-RÉSOLUTION SERVEUR de l'item (anti-IDOR) : il doit appartenir à
        //    cette leçon, qui appartient à ce cours (via le chapitre).
        $lesson->loadMissing('chapter');
        if ((int) $lesson->chapter?->course_id !== $course->id) {
            abort(404);
        }

        $item = LessonItem::where('id', $itemId)
            ->where('lesson_id', $lesson->id)
            ->where('type', 'h5p')
            ->first();

        if ($item === null) {
            abort(404);
        }

        // 2. Gérant de CE cours en prévisualisation (admin OU owner/instructor/editor).
        $isManager = auth()->check() && auth()->user()->can('update', $course);

        // 3. Accès : item « preview » ouvert, OU gérant, OU inscription active réelle.
        //    Un cours non publié reste réservé au gérant (preview).
        $itemPreview = (bool) ($item->payload['preview'] ?? false);
        $isEnrolled  = auth()->check() && Enrollment::where('user_id', auth()->id())
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        if (! $itemPreview && ! $isManager && ! $isEnrolled) {
            abort(403);
        }

        if (! $isManager && ($course->status !== 'published' || $course->visibility !== 'public') && ! $itemPreview) {
            abort(404);
        }

        // 4. V5-d : restrictions d'accès par item (anti-contournement). Jamais
        //    imposées au gérant en prévisualisation.
        if (! $isManager && auth()->check() && class_exists(\Modules\Academy\Services\AccessRestrictionService::class)) {
            $restriction = \Modules\Academy\Services\AccessRestrictionService::evaluate(
                auth()->user(),
                $item,
                $course,
            );
            if (! $restriction['allowed']) {
                abort(403);
            }
        }

        // 5. URL publique du dossier extrait (validée hors périmètre = null).
        $contentUrl = (new H5pPackageService())->publicUrl($item->payload['h5p_path'] ?? null);
        if ($contentUrl === null) {
            abort(404);
        }

        $cdnBase = rtrim((string) config('academy.h5p.cdn_base'), '/');
        $cdnHost = (string) config('academy.h5p.cdn_host', 'cdn.jsdelivr.net');

        $html = view('academy::public.h5p-player', [
            'contentUrl' => $contentUrl,
            'cdnBase'    => $cdnBase,
            'title'      => $item->title,
        ])->render();

        // 6. CSP DÉDIÉE à la page player : jsdelivr autorisé pour le player
        //    uniquement (scripts/styles/fonts), contenu servi en same-origin.
        //    frame-ancestors borne l'inclusion à notre propre domaine.
        $siteHost = (string) config('academy.site_host', 'laveille.ai');
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://{$cdnHost}",
            "style-src 'self' 'unsafe-inline' https://{$cdnHost}",
            "img-src 'self' data: blob: https:",
            "media-src 'self' data: blob:",
            "font-src 'self' data: https://{$cdnHost}",
            "connect-src 'self'",
            "frame-ancestors 'self' https://{$siteHost} https://*.{$siteHost}",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        return response($html, 200)
            ->header('Content-Security-Policy', $csp)
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
