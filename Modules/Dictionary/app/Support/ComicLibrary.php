<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Dictionary\Support;

/**
 * Bibliothèque de planches de BD pédagogiques (standard réutilisable « visionneur de BD »).
 *
 * Convention zéro-code : déposer les fichiers web + un manifest.json dans
 * public/bd/{term-slug}/ et l'indicateur + le visionneur apparaissent
 * automatiquement sur la grille et la fiche du glossaire.
 *
 * Manifest attendu (public/bd/{slug}/manifest.json) :
 *   { "term_slug", "title", "alt", "planches": [ { "avif", "webp", "jpg",
 *     "avif_1024", "webp_1024", "thumb", "width", "height" }, ... ] }
 */
final class ComicLibrary
{
    /** Cache mémoire par requête (slug => array|null). */
    private static array $cache = [];

    /**
     * Indique si un terme possède une BD (présence du manifest = convention).
     */
    public static function hasComic(string $slug): bool
    {
        return self::manifestPath($slug) !== null;
    }

    /**
     * Renvoie le manifest décodé enrichi des URL absolues résolues, ou null.
     *
     * Structure renvoyée :
     *   [ 'term_slug', 'title', 'alt', 'download_url', 'planches' => [ [
     *       'avif','webp','jpg','avif_1024','webp_1024','thumb' (=> URL),
     *       'width','height' ], ... ] ]
     */
    public static function forSlug(string $slug): ?array
    {
        if (array_key_exists($slug, self::$cache)) {
            return self::$cache[$slug];
        }

        $path = self::manifestPath($slug);
        if ($path === null) {
            return self::$cache[$slug] = null;
        }

        $raw = @file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;

        if (! is_array($data) || empty($data['planches']) || ! is_array($data['planches'])) {
            return self::$cache[$slug] = null;
        }

        $base = 'bd/'.$slug.'/';
        $fileKeys = ['avif', 'webp', 'jpg', 'avif_1024', 'webp_1024', 'thumb'];

        $planches = [];
        foreach ($data['planches'] as $planche) {
            if (! is_array($planche)) {
                continue;
            }
            $resolved = $planche;
            foreach ($fileKeys as $key) {
                if (! empty($planche[$key]) && is_string($planche[$key])) {
                    $resolved[$key] = asset($base.$planche[$key]);
                } else {
                    $resolved[$key] = null;
                }
            }
            $resolved['width'] = isset($planche['width']) ? (int) $planche['width'] : null;
            $resolved['height'] = isset($planche['height']) ? (int) $planche['height'] : null;
            $planches[] = $resolved;
        }

        if ($planches === []) {
            return self::$cache[$slug] = null;
        }

        $comic = [
            'term_slug' => (string) ($data['term_slug'] ?? $slug),
            'title' => (string) ($data['title'] ?? ''),
            'alt' => (string) ($data['alt'] ?? ($data['title'] ?? '')),
            // Lien « télécharger » = version pleine résolution du 1er panneau (jpg de préférence).
            'download_url' => $planches[0]['jpg'] ?? $planches[0]['webp'] ?? $planches[0]['avif'] ?? null,
            'planches' => $planches,
        ];

        return self::$cache[$slug] = $comic;
    }

    /**
     * Chemin absolu du manifest si présent et lisible, sinon null.
     */
    private static function manifestPath(string $slug): ?string
    {
        // Garde-fou anti-traversal : on ne garde que des slugs sûrs.
        if ($slug === '' || ! preg_match('/^[a-z0-9\-]+$/i', $slug)) {
            return null;
        }

        $path = public_path('bd/'.$slug.'/manifest.json');

        return is_file($path) ? $path : null;
    }
}
