<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Helpers de version applicatifs (DRY) — utilisés dans footer admin/frontend
 * et tout endroit nécessitant la version courante.
 *
 * lv_version()         -> "v1.0.0 · e200011b"
 * lv_version(false)    -> "v1.0.0"
 * lv_git_sha()         -> "e200011b" ou null
 * lv_semver()          -> "1.0.0"
 */

use Illuminate\Support\Facades\Cache;

if (! function_exists('lv_semver')) {
    function lv_semver(): string
    {
        return (string) config('version.semver', '1.0.0');
    }
}

if (! function_exists('lv_git_sha')) {
    /**
     * Lit le SHA court du commit courant depuis .git/HEAD (sans exec).
     * Cache 15min pour éviter I/O à chaque request.
     */
    function lv_git_sha(): ?string
    {
        return Cache::remember('lv.git_sha', now()->addMinutes(15), function (): ?string {
            $headFile = base_path('.git/HEAD');
            if (! is_file($headFile)) {
                return null;
            }

            $head = trim((string) @file_get_contents($headFile));
            if ($head === '') {
                return null;
            }

            if (str_starts_with($head, 'ref: ')) {
                $refPath = base_path('.git/' . substr($head, 5));
                if (! is_file($refPath)) {
                    return null;
                }
                $sha = trim((string) @file_get_contents($refPath));
            } else {
                $sha = $head;
            }

            return $sha !== '' ? substr($sha, 0, 8) : null;
        });
    }
}

if (! function_exists('lv_version')) {
    /**
     * Retourne la version courante au format "v1.0.0 · e200011b" (ou "v1.0.0" si $withSha=false).
     */
    function lv_version(bool $withSha = true): string
    {
        $base = 'v' . lv_semver();
        if (! $withSha) {
            return $base;
        }

        $sha = lv_git_sha();

        return $sha ? "{$base} · {$sha}" : $base;
    }
}
