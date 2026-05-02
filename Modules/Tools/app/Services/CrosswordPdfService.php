<?php

declare(strict_types=1);

namespace Modules\Tools\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

final class CrosswordPdfService
{
    public function renderBlank(array $generated, ?string $title = null, ?string $playUrl = null, string $inactiveStyle = 'black'): string
    {
        $pdf = Pdf::loadView('tools::public.tools.crossword.pdf-blank', [
            'grid' => $generated['grid'] ?? null,
            'words' => $generated['words'] ?? [],
            'title' => $title ?: 'Mots croisés',
            'playUrl' => $playUrl,
            'generatedAt' => now()->format('Y-m-d'),
            'inactiveStyle' => $inactiveStyle,
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
        ])->setPaper('A4', 'portrait');

        return $pdf->output();
    }
}
