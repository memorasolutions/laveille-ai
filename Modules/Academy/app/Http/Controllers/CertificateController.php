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
use Illuminate\Http\Response;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\LessonItem;

class CertificateController extends Controller
{
    /** Couleur d'accent de charte par défaut (cohérente avec la vue HTML). */
    private const DEFAULT_ACCENT = '#064E5A';

    /**
     * Affiche la page publique d'un certificat, vérifiable via son slug unique.
     * Retourne 404 si le slug est inconnu (via firstOrFail).
     */
    public function show(string $public_url_slug): \Illuminate\View\View
    {
        return view('academy::public.certificate', [
            'certificate' => $this->resolveCertificate($public_url_slug),
        ]);
    }

    /**
     * D11 - Télécharge le certificat en PDF (parité Moodle).
     *
     * Le PDF est rendu par dompdf (déjà installé : barryvdh/laravel-dompdf) à partir
     * d'un gabarit autonome. Garde-fou de portabilité : si dompdf est absent, on
     * retombe sur la page HTML imprimable plutôt que de produire une 500.
     */
    public function download(string $public_url_slug): Response|\Illuminate\Http\RedirectResponse
    {
        $certificate = $this->resolveCertificate($public_url_slug);

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return redirect()->route('academy.certificates.show', $public_url_slug);
        }

        $course = $certificate->course;

        // Mêmes règles de validation/repli que la vue HTML (anti-XSS, rétrocompat).
        $accent = is_string($course->certificate_accent_color ?? null)
            && preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $course->certificate_accent_color) === 1
                ? $course->certificate_accent_color
                : self::DEFAULT_ACCENT;

        $data = [
            'certificate'     => $certificate,
            'accent'          => $accent,
            'certTitle'       => $course->certificate_title ?: 'Certificat de complétion',
            'certSignature'   => $course->certificate_signature_name ?: null,
            'certMessageHtml' => filled($course->certificate_message)
                ? LessonItem::renderRichText($course->certificate_message)
                : null,
        ];

        $filename = 'certificat-' . $certificate->serial . '.pdf';

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('academy::public.certificate-pdf', $data)
            ->setPaper('letter', 'landscape')
            ->download($filename);
    }

    /**
     * Résout un certificat par son slug public (404 si inconnu, jamais de 500).
     */
    private function resolveCertificate(string $public_url_slug): CertificateIssued
    {
        try {
            return CertificateIssued::where('public_url_slug', $public_url_slug)
                ->with(['user', 'course'])
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            abort(404);
        }
    }
}
