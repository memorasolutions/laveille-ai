<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OpcacheStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $configuredToken = (string) config('health.opcache.token', '');
        // L'en-tête est privilégié : contrairement au paramètre d'URL, il n'atterrit
        // pas dans les journaux d'accès du serveur web ni du réseau de diffusion.
        $requestToken = (string) ($request->header('X-Sante-Jeton') ?: $request->query('token', ''));

        abort_unless(
            (bool) config('health.opcache.enabled', false)
            && $configuredToken !== ''
            && hash_equals($configuredToken, $requestToken),
            404
        );

        if (! function_exists('opcache_get_status')) {
            return response()->json(['error' => 'La fonction opcache_get_status() n’est pas disponible.'], 503);
        }

        $status = $this->readOpcacheStatus();

        if ($status === false) {
            return response()->json(['error' => 'OPcache n’est pas actif pour le SAPI PHP courant.'], 503);
        }

        return response()->json([
            'sapi' => PHP_SAPI,
            'memory_usage' => $this->only($status['memory_usage'] ?? [], [
                'used_memory', 'free_memory', 'wasted_memory', 'current_wasted_percentage',
            ]),
            'opcache_statistics' => $this->only($status['opcache_statistics'] ?? [], [
                'num_cached_scripts', 'num_cached_keys', 'max_cached_keys', 'hits', 'misses',
                'oom_restarts', 'hash_restarts', 'manual_restarts',
            ]),
            'interned_strings_usage' => $this->only($status['interned_strings_usage'] ?? [], [
                'buffer_size', 'used_memory', 'free_memory', 'number_of_strings',
            ]),
            'cache_full' => (bool) ($status['cache_full'] ?? false),
        ]);
    }

    /** @return array<string, mixed>|false */
    public function readOpcacheStatus(): array|false
    {
        return opcache_get_status(false);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function only(array $values, array $keys): array
    {
        return array_intersect_key($values, array_flip($keys));
    }
}
