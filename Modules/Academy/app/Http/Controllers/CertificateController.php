<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Academy\Models\CertificateIssued;

class CertificateController extends Controller
{
    /**
     * Affiche la page publique d'un certificat, vérifiable via son slug unique.
     * Retourne 404 si le slug est inconnu (via firstOrFail).
     */
    public function show(string $public_url_slug): \Illuminate\View\View
    {
        $certificate = CertificateIssued::where('public_url_slug', $public_url_slug)
            ->with(['user', 'course'])
            ->firstOrFail();

        return view('academy::public.certificate', compact('certificate'));
    }
}
