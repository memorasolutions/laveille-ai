<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Consumer LTI 1.3 minimal (Academy branche des outils EXTERNES, jamais
 * l'inverse) : lance un outil pédagogique tiers depuis un item de leçon
 * « lti_tool », puis reçoit son jeton d'identité (id_token) au retour.
 *
 * SÉCURITÉ (anti-IDOR + gating, même pattern que VideoRedirectController /
 * H5pPlayerController — DRY via LessonAccessService) :
 *  - drapeau academy.lti_enabled vérifié en tête de CHAQUE méthode ;
 *  - l'item est RE-RÉSOLU côté serveur (appartenance leçon/cours) ;
 *  - l'accès est RE-VÉRIFIÉ intégralement (LessonAccessService::canAccessItem) ;
 *  - le callback n'expose JAMAIS de détail technique à l'utilisateur : toute
 *    validation échouée (état, signature, émetteur, deployment_id...) se
 *    traduit par un message générique, le détail va au seul journal serveur.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Academy\Exceptions\LtiLaunchException;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LtiToolRegistration;
use Modules\Academy\Services\LessonAccessService;
use Modules\Academy\Services\LtiLaunchService;

class LtiLaunchController extends Controller
{
    public function __construct(private readonly LtiLaunchService $lti) {}

    /**
     * GET /academie/courses/{course:slug}/lessons/{lesson}/lti/{item}/launch
     *
     * Amorce le flux OIDC : re-résout et ré-autorise l'item côté serveur,
     * résout l'outil externe rattaché, puis délègue au service la redirection
     * vers l'« auth_login_url » de l'outil.
     */
    public function launch(Course $course, Lesson $lesson, int $item, Request $request): RedirectResponse
    {
        abort_unless((bool) config('academy.lti_enabled', false), 404);

        $resolvedItem = LessonAccessService::resolveItem($course, $lesson, $item, 'lti_tool');
        if ($resolvedItem === null) {
            abort(404);
        }

        if (! LessonAccessService::canAccessItem(auth()->user(), $course, $resolvedItem)) {
            abort(403);
        }

        $toolId = $resolvedItem->payload['lti_tool_registration_id'] ?? null;
        $tool   = $toolId !== null ? LtiToolRegistration::find($toolId) : null;

        if ($tool === null) {
            abort(404);
        }

        if (! $tool->is_active) {
            abort(403);
        }

        return $this->lti->initiateLogin($tool, $request->user(), $resolvedItem);
    }

    /**
     * GET|POST /academie/lti/callback
     *
     * Point de retour OIDC (form_post) : valide le jeton d'identité de l'outil
     * externe. Aucune authentification `auth` requise ici (l'appelant est
     * l'outil externe, pas l'apprenant) — la sécurité vient entièrement du
     * state/nonce à usage unique et de la validation du jeton (voir service).
     */
    public function callback(Request $request): \Illuminate\Contracts\View\View
    {
        abort_unless((bool) config('academy.lti_enabled', false), 404);

        try {
            $result = $this->lti->handleCallback($request);

            return view('academy::public.lti.tool-frame', [
                'tool'  => $result['tool'],
                'title' => $result['tool']->name,
            ]);
        } catch (LtiLaunchException $e) {
            Log::warning('lti_launch_failed', [
                'raison' => $e->getMessage(),
            ]);

            return view('academy::public.lti.error');
        } catch (\Throwable $e) {
            Log::error('lti_launch_failed_unexpected', [
                'exception' => $e::class,
                'message'   => $e->getMessage(),
            ]);

            return view('academy::public.lti.error');
        }
    }
}
