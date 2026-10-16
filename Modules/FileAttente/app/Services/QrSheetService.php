<?php

declare(strict_types=1);

namespace Modules\FileAttente\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Core\Services\QrCodeService;
use Modules\FileAttente\Models\Board;

/**
 * PDF PRIVÉ des cartes QR d'un tableau (remis par l'animateur, montre les vrais noms).
 * Chaque QR encode l'URL d'affichage du scan : {app.url}/f/{jeton}.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class QrSheetService
{
    public const PER_PAGE = 12; // 3 colonnes x 4 lignes

    public function __construct(
        private readonly BoardService $boards,
        private readonly QrCodeService $qr,
    ) {}

    public function scanUrl(string $plainToken): string
    {
        return rtrim((string) config('app.url'), '/').'/f/'.$plainToken;
    }

    /** @return string binaire PDF */
    public function render(Board $board): string
    {
        $cards = [];
        foreach ($board->participants()->orderBy('joined_seq')->get() as $p) {
            $png = $this->qr->generate($this->scanUrl($this->boards->plainToken($p)), [
                'size' => 300,
                'margin' => 2,
                'foreground' => '#1A1D23',
                'ecc' => 'M',
            ]);
            $cards[] = [
                'name' => $p->full_name,
                'code' => __('fileattente::ui.pdf.number', ['n' => $p->joined_seq]),
                'qr' => 'data:image/png;base64,'.base64_encode($png),
            ];
        }

        return Pdf::loadView('fileattente::pdf.feuille-qr', [
            'title' => $board->title ?: __('fileattente::ui.app_title'),
            'pages' => array_chunk($cards, self::PER_PAGE),
        ])->setPaper('A4')->output();
    }
}
