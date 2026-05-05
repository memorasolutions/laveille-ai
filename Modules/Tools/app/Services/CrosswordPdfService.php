<?php

declare(strict_types=1);

namespace Modules\Tools\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

final class CrosswordPdfService
{
    /**
     * 2026-05-05 #102 : taille cellule auto-scale priorité largeur maximale.
     * A4 portrait = 595×842pt. Surface utile ~527×758pt après margin 1.5cm×1.2cm.
     * Header 80pt + h1 30pt + footer 30pt = 140pt fixes → 618pt max pour grille+indices.
     * Stratégie : maximiser largeur (520pt) ; hauteur étendue à 560pt — si grille très haute,
     * les indices passent automatiquement page 2 (DomPDF page-break-before:auto).
     * Clamp [10pt, 32pt] pour garder lisibilité.
     */
    private function calculateCellSize(?array $grid): int
    {
        $cols = max(1, (int) ($grid['cols'] ?? 10));
        $rows = max(1, (int) ($grid['rows'] ?? 10));
        $maxWidth = 520;
        $maxHeight = 560;
        $cellByWidth = (int) floor($maxWidth / $cols);
        $cellByHeight = (int) floor($maxHeight / $rows);
        $cell = min($cellByWidth, $cellByHeight);

        return max(10, min(32, $cell));
    }

    public function renderBlank(array $generated, ?string $title = null, ?string $playUrl = null, string $inactiveStyle = 'black'): string
    {
        $pdf = Pdf::loadView('tools::public.tools.crossword.pdf-blank', [
            'grid' => $generated['grid'] ?? null,
            'words' => $generated['words'] ?? [],
            'title' => $title ?: 'Mots croisés',
            'playUrl' => $playUrl,
            'generatedAt' => now()->format('Y-m-d'),
            'inactiveStyle' => $inactiveStyle,
            'cellSize' => $this->calculateCellSize($generated['grid'] ?? null),
        ])->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    public function renderSolution(array $generated, ?string $title = null, ?string $playUrl = null, string $inactiveStyle = 'black'): string
    {
        $pdf = Pdf::loadView('tools::public.tools.crossword.pdf-solution', [
            'grid' => $generated['grid'] ?? null,
            'words' => $generated['words'] ?? [],
            'title' => $title ?: 'Mots croisés - Corrigé',
            'playUrl' => $playUrl,
            'generatedAt' => now()->format('Y-m-d'),
            'inactiveStyle' => $inactiveStyle,
            'cellSize' => $this->calculateCellSize($generated['grid'] ?? null),
        ])->setPaper('A4', 'portrait');

        return $pdf->output();
    }
}
