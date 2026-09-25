<?php

declare(strict_types=1);

namespace Modules\Signature\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Signature\Http\Controllers\Concerns\ResolvesSignatureByToken;
use Modules\Signature\Services\SignatureRenderer;

/**
 * Export « Outlook classique » (fichier .htm téléchargeable, section 9 et 11.3) : route SERVEUR
 * de repli, toujours disponible même si le rendu client (renderSignature en JavaScript,
 * section 4) échoue - utilise le moteur PHP jumeau Modules\Signature\Services\SignatureRenderer
 * (voir son docblock pour l'écart assumé au plan).
 */
class SignatureExportController extends Controller
{
    use ResolvesSignatureByToken;

    public function show(string $token): Response
    {
        $signature = $this->findByToken($token);
        $signature->load('images');

        // toAssetPayload() donne width/height (dérivée réelle) ET display_width/display_height
        // (taille voulue à l'affichage) - le rendu HTML doit utiliser la taille D'AFFICHAGE comme
        // attributs width/height de la balise <img> (section 5 du plan), jamais la résolution
        // brute de la dérivée sur-échantillonnée.
        $images = [];
        foreach ($signature->imagesPayload() as $role => $asset) {
            $images[$role] = [
                'url' => $asset['url'],
                'width' => $asset['display_width'],
                'height' => $asset['display_height'],
            ];
        }

        $html = SignatureRenderer::render($signature->content ?? [], $signature->template, $images);
        $document = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Signature</title></head><body>'.$html.'</body></html>';

        return response($document, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="signature-outlook.htm"');
    }
}
