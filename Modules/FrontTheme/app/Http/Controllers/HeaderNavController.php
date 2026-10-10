<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * GET /api/header-nav - navigation de l'entête, publique, en lecture seule (ticket #3013).
 *
 * Sérialise l'arbre de HeaderNavService, qui est AUSSI ce dont se rend l'entête de laveille.ai :
 * une seule définition, deux sorties. Le contrat JSON est figé (consommateur : l'académie Moodle).
 *
 * L'ETag dérive de `version` (empreinte du contenu du menu), jamais de `generated_at` : sans quoi
 * il changerait à chaque requête et le 304 ne se produirait jamais.
 */

namespace Modules\FrontTheme\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\FrontTheme\Services\HeaderNavService;

class HeaderNavController
{
    private const CACHE_CONTROL = 'public, max-age=300';

    public function __invoke(Request $request, HeaderNavService $service): JsonResponse|Response
    {
        $payload = $service->toApi();
        $etag = '"'.$payload['version'].'"';

        if ($this->etagMatches($request->header('If-None-Match'), $etag)) {
            return response('', 304)->withHeaders(['ETag' => $etag, 'Cache-Control' => self::CACHE_CONTROL]);
        }

        return response()
            ->json($payload, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->withHeaders(['ETag' => $etag, 'Cache-Control' => self::CACHE_CONTROL]);
    }

    /** Compare l'en-tête If-None-Match (liste possible, préfixe faible W/ toléré) à l'ETag courant. */
    private function etagMatches(?string $header, string $etag): bool
    {
        if ($header === null || $header === '') {
            return false;
        }
        if (trim($header) === '*') {
            return true;
        }
        foreach (explode(',', $header) as $candidate) {
            if (preg_replace('#^\s*W/#', '', trim($candidate)) === $etag) {
                return true;
            }
        }

        return false;
    }
}
