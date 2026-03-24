<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Acronyms\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\MetaScraperService;
use Throwable;

class ScrapeMetaController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['url' => 'required|url|max:2048']);

        $key = 'scrape_meta:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['error' => __('Trop de requêtes. Réessayez dans quelques secondes.')], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            $data = MetaScraperService::scrape($request->input('url'));

            return response()->json($data);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            return response()->json(['error' => __('Erreur lors du scraping.')], 500);
        }
    }
}
