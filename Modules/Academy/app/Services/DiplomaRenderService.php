<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\DiplomaTemplate;
use Modules\Core\Services\QrCodeService;
use RuntimeException;

/**
 * Rendu HTML/CSS et PDF d'un diplôme (Phase 1 — système de diplomation moderne).
 *
 * Le RENDU final reste en HTML/CSS (jamais canvas) : Konva.js ne sert QUE à
 * l'éditeur (DiplomaTemplateEditor). Ce service traduit les positions en %
 * du `layout_config` en HTML positionné, interpole les variables des 4
 * familles ({système|pédagogique|organisationnel|custom}), puis délègue la
 * conversion PDF au MÊME pipeline que CertificateController (dompdf,
 * barryvdh/laravel-dompdf — aucun 2e pipeline). Le QR encode la MÊME URL de
 * vérification publique que le certificat existant (academy.certificates.show)
 * et réutilise le générateur QR déjà en prod (Modules\Core\Services\QrCodeService).
 */
final class DiplomaRenderService
{
    public function __construct(
        private readonly QrCodeService $qrCodeService,
    ) {}

    /**
     * Taxonomie des 4 familles de variables pour l'éditeur (dropdown d'insertion).
     *
     * @return array<string, array{label: string, variables: array<string, string>}>
     */
    public static function variableCatalog(): array
    {
        return [
            'system' => [
                'label'     => 'Système',
                'variables' => [
                    'learner_name'       => "Nom de l'apprenant",
                    'issue_date'         => "Date d'émission",
                    'certificate_number' => 'Numéro de certificat',
                ],
            ],
            'pedagogical' => [
                'label'     => 'Pédagogique',
                'variables' => [
                    'course_title'    => 'Titre du cours',
                    'duration_hours'  => 'Durée (heures)',
                    'final_score'     => 'Score final',
                ],
            ],
            'organizational' => [
                'label'     => 'Organisationnel',
                'variables' => [
                    'organization_name' => "Nom de l'organisme",
                    'signature_name'    => 'Nom du signataire',
                ],
            ],
            'custom' => [
                // Texte libre tapé directement par le formateur dans l'élément :
                // aucune clé prédéfinie, l'élément peut néanmoins mélanger du
                // texte libre et des {famille.cle} des 3 autres familles.
                'label'     => 'Personnalisé',
                'variables' => [],
            ],
        ];
    }

    /**
     * Valeurs RÉELLES résolues à partir d'un certificat émis (user + course).
     * Défensif : jamais d'exception si une relation n'est pas chargée/absente.
     *
     * @return array<string, string>
     */
    public function resolveVariables(CertificateIssued $certificate): array
    {
        $user   = $certificate->user;
        $course = $certificate->course;

        $hoursEarned   = $certificate->hours_earned;
        $durationHours = $hoursEarned !== null
            ? ($hoursEarned === 1 ? '1 heure' : "{$hoursEarned} heures")
            : '';

        $finalScore = $certificate->final_score !== null
            ? "{$certificate->final_score} %"
            : '';

        return [
            'system.learner_name'       => $user?->name ?? '',
            'system.issue_date'         => $certificate->issued_at
                ? $certificate->issued_at->locale('fr_CA')->translatedFormat('j F Y')
                : '—',
            'system.certificate_number' => $certificate->serial ?? '',

            'pedagogical.course_title'   => $course?->title ?? '',
            'pedagogical.duration_hours' => $durationHours,
            'pedagogical.final_score'    => $finalScore,

            'organizational.organization_name' => (string) config('app.name', ''),
            'organizational.signature_name'    => $course?->certificate_signature_name ?? '',
        ];
    }

    /**
     * Valeurs d'EXEMPLE pour l'aperçu en direct de l'éditeur (jamais une
     * vraie donnée d'apprenant).
     *
     * @return array<string, string>
     */
    public function sampleVariables(): array
    {
        return [
            'system.learner_name'       => 'Jean Tremblay',
            'system.issue_date'         => '1 juillet 2026',
            'system.certificate_number' => 'ACAD-EXEMPLE01',

            'pedagogical.course_title'   => "Introduction à l'intelligence artificielle",
            'pedagogical.duration_hours' => '8 heures',
            'pedagogical.final_score'    => '92 %',

            'organizational.organization_name' => (string) config('app.name', ''),
            'organizational.signature_name'    => 'Stéphane Lapointe',
        ];
    }

    /** URL publique de vérification — LA MÊME que la page certificat existante. */
    public function verificationUrl(CertificateIssued $certificate): string
    {
        return route('academy.certificates.show', $certificate->public_url_slug);
    }

    /**
     * Document HTML autonome représentant le diplôme (dimensions letter paysage).
     */
    public function renderHtml(DiplomaTemplate $template, CertificateIssued $certificate, bool $sample = false): string
    {
        $variables = $sample ? $this->sampleVariables() : $this->resolveVariables($certificate);

        $elementsHtml = '';
        foreach ($template->elements() as $element) {
            $elementsHtml .= $this->renderElement(is_array($element) ? $element : [], $variables, $certificate, $sample);
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Diplôme</title>
    <style>
        @page { margin: 0; size: letter landscape; }
        body {
            margin: 0;
            padding: 0;
            width: 27.94cm;
            height: 21.59cm;
            font-family: Arial, sans-serif;
        }
        .diploma-container { position: relative; width: 100%; height: 100%; }
        .diploma-container div, .diploma-container img { box-sizing: border-box; }
    </style>
</head>
<body>
    <div class="diploma-container">{$elementsHtml}</div>
</body>
</html>
HTML;
    }

    /**
     * Réponse HTTP PDF du diplôme — RÉUTILISE dompdf (même pipeline que
     * CertificateController::download(), aucun 2e moteur PDF).
     */
    public function renderPdf(DiplomaTemplate $template, CertificateIssued $certificate): Response
    {
        if (! class_exists(Pdf::class)) {
            throw new RuntimeException('dompdf indisponible.');
        }

        $filename = 'diplome-' . ($certificate->serial ?? 'sans-numero') . '.pdf';

        return Pdf::loadHTML($this->renderHtml($template, $certificate))
            ->setPaper('letter', 'landscape')
            ->download($filename);
    }

    /**
     * Rend un élément unique du canevas en HTML positionné (% → style inline).
     * Chaque valeur numérique/couleur/mot-clé est bornée (défense en profondeur :
     * layout_config est une colonne JSON éditée par un formateur authentifié,
     * mais reste un contenu persisté — jamais injecté tel quel dans du HTML).
     *
     * @param array<string, mixed> $element
     * @param array<string, string> $variables
     */
    private function renderElement(array $element, array $variables, CertificateIssued $certificate, bool $sample): string
    {
        $x      = $this->toPercent($element['x'] ?? 0);
        $y      = $this->toPercent($element['y'] ?? 0);
        $width  = $this->toPercent($element['width'] ?? 20);
        $height = $this->toPercent($element['height'] ?? 10);

        $style = "position:absolute; left:{$x}%; top:{$y}%; width:{$width}%; height:{$height}%;";

        if (isset($element['font_size'])) {
            $fontSize = max(6, min(120, (int) $element['font_size']));
            $style .= " font-size:{$fontSize}px;";
        }
        if (isset($element['font_weight'])) {
            $style .= ' font-weight:' . $this->sanitizeKeyword((string) $element['font_weight'], '400') . ';';
        }
        if (isset($element['color'])) {
            $style .= ' color:' . $this->sanitizeHexColor((string) $element['color']) . ';';
        }
        if (isset($element['align'])) {
            $align = (string) $element['align'];
            $style .= ' text-align:' . (in_array($align, ['left', 'center', 'right'], true) ? $align : 'left') . ';';
        }

        $kind = (string) ($element['kind'] ?? 'text');

        return match ($kind) {
            'qr'    => $this->renderQrElement($style, $certificate, $sample),
            'image' => $this->renderImageElement($style, $element, $variables),
            default => '<div style="' . $style . '">' . $this->interpolate((string) ($element['content'] ?? ''), $variables) . '</div>',
        };
    }

    private function renderQrElement(string $style, CertificateIssued $certificate, bool $sample): string
    {
        // Jamais de QR généré en mode aperçu (données d'exemple : l'URL de
        // vérification doit toujours pointer vers un VRAI certificat).
        $qrSrc = $sample ? '' : $this->qrDataUri($certificate);

        if ($qrSrc === '') {
            return '<div style="' . $style . ' background:#F3F4F6; display:flex; align-items:center; justify-content:center; font-size:10px; color:#6B7280;">QR</div>';
        }

        return '<img src="' . $qrSrc . '" alt="QR de vérification" style="' . $style . ' object-fit:contain;" />';
    }

    /**
     * @param array<string, mixed>  $element
     * @param array<string, string> $variables
     */
    private function renderImageElement(string $style, array $element, array $variables): string
    {
        $variableKey = (string) ($element['variable'] ?? '');
        $src         = $variableKey !== '' ? ($variables[$variableKey] ?? '') : '';

        if ($src === '' || ! str_starts_with($src, 'http')) {
            return '';
        }

        return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" style="' . $style . ' object-fit:contain;" />';
    }

    /**
     * Interpole chaque {famille.cle} présent dans $variables. Le contenu est
     * échappé AVANT interpolation (anti-XSS) ; un token absent de la carte
     * est laissé tel quel (jamais d'erreur pour une variable inconnue).
     *
     * @param array<string, string> $variables
     */
    private function interpolate(string $content, array $variables): string
    {
        $escaped = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        foreach ($variables as $key => $value) {
            $token = '{' . $key . '}';
            if (str_contains($escaped, $token)) {
                $escaped = str_replace($token, htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $escaped);
            }
        }

        return $escaped;
    }

    /** QR encodé en data URI — défensif : jamais d'exception propagée. */
    private function qrDataUri(CertificateIssued $certificate): string
    {
        try {
            $png = $this->qrCodeService->generate($this->verificationUrl($certificate), ['size' => 300]);

            return 'data:image/png;base64,' . base64_encode($png);
        } catch (\Throwable) {
            return '';
        }
    }

    private function toPercent(mixed $value): float
    {
        return round(max(0.0, min(100.0, (float) $value)), 2);
    }

    private function sanitizeHexColor(string $value): string
    {
        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1 ? $value : '#1A1D23';
    }

    private function sanitizeKeyword(string $value, string $default): string
    {
        return preg_match('/^[a-zA-Z0-9]+$/', $value) === 1 ? $value : $default;
    }
}
