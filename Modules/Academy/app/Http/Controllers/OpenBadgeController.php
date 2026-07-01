<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\UserBadge;
use Modules\Academy\Services\OpenBadgeService;

/**
 * Endpoints publics de vérification OpenBadge 3.0.
 *
 * Tous gâtés par config('academy.open_badges_enabled') → 404 si désactivé.
 * Aucun middleware auth : les assertions et pages de vérification sont publiques
 * par nature (vérifiables par des tiers comme LinkedIn, Credly, etc.).
 *
 * // ACTION: ajout D09 — contrôleur OpenBadge (page vérification + assertions JSON-LD)
 * // SELF: nouveau fichier < 5 lignes correctives après génération MCP
 * // RAISON: endpoints publics requis par le standard OB 3.0 (id = URL stable)
 */
class OpenBadgeController extends Controller
{
    public function __construct(
        private readonly OpenBadgeService $service,
    ) {}

    /**
     * Page HTML publique de vérification d'un badge de certificat.
     * URL stable : /academie/badges/{serial}
     */
    public function show(string $serial): View
    {
        if (! $this->service->isEnabled()) {
            abort(404);
        }

        /** @var CertificateIssued $cert */
        $cert = CertificateIssued::with(['user', 'course'])
            ->where('serial', $serial)
            ->firstOrFail();

        $assertionUrl = route('academy.badges.assertion', ['serial' => $cert->serial]);

        $linkedInUrl = 'https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME'
            . '&name=' . urlencode((string) $cert->course->title)
            . '&organizationName=' . urlencode((string) config('app.name'))
            . ($cert->issued_at ? '&issueYear=' . $cert->issued_at->year : '')
            . ($cert->issued_at ? '&issueMonth=' . $cert->issued_at->month : '')
            . '&certUrl=' . urlencode($assertionUrl)
            . '&certId=' . urlencode($cert->serial);

        return view('academy::public.badge-verify', [
            'certificate'  => $cert,
            'linkedInUrl'  => $linkedInUrl,
            'assertionUrl' => $assertionUrl,
        ]);
    }

    /**
     * Assertion JSON-LD OpenBadge 3.0 pour un certificat.
     * Content-Type: application/ld+json (requis par le standard).
     * URL stable : /academie/badges/{serial}/assertion
     */
    public function assertion(string $serial): JsonResponse
    {
        if (! $this->service->isEnabled()) {
            abort(404);
        }

        /** @var CertificateIssued $cert */
        $cert = CertificateIssued::with(['user', 'course'])
            ->where('serial', $serial)
            ->firstOrFail();

        $assertion = $this->service->assertionForCertificate($cert);

        return response()
            ->json($assertion, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/ld+json');
    }

    /**
     * Assertion JSON-LD OpenBadge 3.0 pour un badge utilisateur.
     * URL stable : /academie/badges/user-badge/{id}/assertion
     */
    public function userBadgeAssertion(int $id): JsonResponse
    {
        if (! $this->service->isEnabled()) {
            abort(404);
        }

        /** @var UserBadge $ub */
        $ub = UserBadge::with(['user', 'badge'])
            ->findOrFail($id);

        $assertion = $this->service->assertionForUserBadge($ub);

        return response()
            ->json($assertion, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/ld+json');
    }
}
