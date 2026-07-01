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
use Modules\Academy\Models\DiplomaTemplate;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\DiplomaRenderService;

class CertificateController extends Controller
{
    /** Couleur d'accent de charte par défaut (cohérente avec la vue HTML). */
    private const DEFAULT_ACCENT = '#064E5A';

    public function __construct(
        private readonly DiplomaRenderService $diplomaRenderService,
    ) {}

    /**
     * Affiche la page publique d'un certificat, vérifiable via son slug unique.
     * Retourne 404 si le slug est inconnu (via firstOrFail).
     *
     * Phase 2 - diplomation moderne : si le cours a un gabarit de diplôme associé
     * ET que le drapeau academy.diploma_editor_enabled est actif, le rendu est
     * délégué à DiplomaRenderService (HTML autonome). Sinon (cas par défaut, la
     * quasi-totalité des cours), comportement STRICTEMENT identique à avant.
     */
    public function show(string $public_url_slug): \Illuminate\View\View|Response
    {
        $certificate = $this->resolveCertificate($public_url_slug);
        $template    = $this->resolveDiplomaTemplate($certificate);

        if ($template !== null) {
            return response($this->diplomaRenderService->renderHtml($template, $certificate))
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return view('academy::public.certificate', [
            'certificate' => $certificate,
        ]);
    }

    /**
     * D11 - Télécharge le certificat en PDF (parité Moodle).
     *
     * Le PDF est rendu par dompdf (déjà installé : barryvdh/laravel-dompdf) à partir
     * d'un gabarit autonome. Garde-fou de portabilité : si dompdf est absent, on
     * retombe sur la page HTML imprimable plutôt que de produire une 500.
     *
     * Phase 2 - diplomation moderne : même bascule que show(), déléguée à
     * DiplomaRenderService::renderPdf() quand un gabarit est associé et actif.
     */
    public function download(string $public_url_slug): Response|\Illuminate\Http\RedirectResponse
    {
        $certificate = $this->resolveCertificate($public_url_slug);

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return redirect()->route('academy.certificates.show', $public_url_slug);
        }

        $template = $this->resolveDiplomaTemplate($certificate);

        if ($template !== null) {
            return $this->diplomaRenderService->renderPdf($template, $certificate);
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

    /**
     * Phase 2 - résout le gabarit de diplôme du cours du certificat, uniquement si
     * le drapeau est actif ET qu'un gabarit est réellement associé. Retourne null
     * dans TOUS les autres cas (drapeau OFF, cours sans gabarit, gabarit supprimé
     * malgré le nullOnDelete) : c'est CE null qui garantit le rendu par défaut
     * inchangé, jamais d'exception propagée vers l'appelant.
     */
    private function resolveDiplomaTemplate(CertificateIssued $certificate): ?DiplomaTemplate
    {
        if (! (bool) config('academy.diploma_editor_enabled', false)) {
            return null;
        }

        $templateId = $certificate->course?->diploma_template_id;

        return $templateId !== null ? DiplomaTemplate::find($templateId) : null;
    }
}
