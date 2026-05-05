<?php

declare(strict_types=1);

namespace Modules\Tools\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

final class CrosswordPdfService
{
    /**
     * 2026-05-05 #100 : taille cellule auto-scale pour tenir sur 1 page A4 portrait.
     * A4 portrait = 595×842pt. Margin 1.5cm×1.2cm = ~42×34pt → surface utile ~527×758pt.
     * Réserve : header 80pt + h1 30pt + clues 280pt + footer 30pt = 420pt → grille max ~330pt hauteur.
     * Largeur grille max ~500pt.
     * Clamp [10pt, 28pt] pour garder lisibilité même grilles 25×25.
     */
    private function calculateCellSize(?array $grid): int
    {
        $cols = max(1, (int) ($grid['cols'] ?? 10));
        $rows = max(1, (int) ($grid['rows'] ?? 10));
        $maxWidth = 500;
        $maxHeight = 330;
        $cellByWidth = (int) floor($maxWidth / $cols);
        $cellByHeight = (int) floor($maxHeight / $rows);
        $cell = min($cellByWidth, $cellByHeight);

        return max(10, min(28, $cell));
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
