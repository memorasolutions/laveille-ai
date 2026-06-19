<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Academy\Models\CertificateIssued;

class CertificateController extends Controller
{
    /**
     * Affiche la page publique d'un certificat, vérifiable via son slug unique.
     * Retourne 404 si le slug est inconnu (via firstOrFail).
     */
    public function show(string $public_url_slug): \Illuminate\View\View
    {
        // Garde-fou : firstOrFail → ModelNotFoundException → 404.
        // Le try-catch explicite protège contre toute exception inattendue
        // (ex. mauvais nom de table, migration absente) qui produirait un 500.
        try {
            $certificate = CertificateIssued::where('public_url_slug', $public_url_slug)
                ->with(['user', 'course'])
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return view('academy::public.certificate', compact('certificate'));
    }
}
