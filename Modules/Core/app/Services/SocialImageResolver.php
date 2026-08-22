<?php

declare(strict_types=1);

namespace Modules\Core\Services;

/**
 * Résout une image « partageable » pour og:image / aperçus de réseaux sociaux - jamais en WebP
 * ni en AVIF.
 *
 * Pourquoi ce service existe (audit 2026-08-22) : ni Facebook ni LinkedIn ne documentent le
 * WebP comme format supporté pour l'aperçu de partage (seul X le supporte à la source) ;
 * l'AVIF n'est supporté nulle part. Une og:image en WebP/AVIF ne déclenche AUCUNE erreur -
 * l'aperçu est simplement VIDE côté réseau social, ce qui rend le défaut indétectable sans
 * vérification manuelle poste par poste.
 *
 * Avant ce correctif, la même chaîne de repli (essayer le .jpg de même nom, sinon le .png,
 * sinon une image par défaut) existait déjà, dupliquée, dans Dictionary et News - et laissait
 * passer les images externes et certains résidus locaux sans .jpg jumeau. Ce service centralise
 * la règle à UN seul endroit, appelée par le Glossaire, les Actualités, le Blogue et les
 * Outils plutôt que recopiée dans chaque vue (DRY - CLAUDE.md règle 11).
 */
final class SocialImageResolver
{
    /**
     * Visuel de repli du site, servi quand aucune image fiable n'est disponible - garantit un
     * aperçu jamais vide plutôt qu'un défaut silencieux.
     */
    private const DEFAULT_FALLBACK = 'images/og-image.png';

    /**
     * Extensions qu'aucun grand réseau social ne garantit d'afficher en aperçu de partage.
     */
    private const UNSAFE_EXTENSIONS = ['webp', 'avif'];

    /**
     * Retourne un chemin/URL d'image sûr pour og:image - jamais en WebP ni en AVIF.
     *
     * Règles, dans l'ordre :
     * - chemin vide ou nul -> $fallback ;
     * - extension WebP/AVIF sur un fichier LOCAL -> essaie le .jpg de même nom (via
     *   file_exists() sur public_path()), puis le .png, sinon $fallback (jamais le fichier
     *   WebP/AVIF lui-même) ;
     * - extension WebP/AVIF sur une URL EXTERNE (http/https) -> $fallback directement : on ne
     *   peut ni la convertir, ni garantir son format réel côté serveur distant ;
     * - toute autre extension (locale ou externe) -> inchangée.
     *
     * @param  string|null $path     chemin relatif à public_path(), ou URL absolue
     * @param  string|null $fallback repli à servir si aucune image sûre n'est trouvée (défaut :
     *                                images/og-image.png)
     */
    public static function shareable(?string $path, ?string $fallback = null): ?string
    {
        $fallback ??= self::DEFAULT_FALLBACK;

        if ($path === null || trim($path) === '') {
            return $fallback;
        }

        // Ignore une éventuelle chaîne de requête (?w=1200...) pour détecter l'extension réelle
        // d'une URL externe, sans jamais modifier le chemin retourné lui-même.
        $pathWithoutQuery = strtok($path, '?');
        $pathWithoutQuery = $pathWithoutQuery === false ? $path : $pathWithoutQuery;
        $extension = strtolower((string) pathinfo($pathWithoutQuery, PATHINFO_EXTENSION));

        if (! in_array($extension, self::UNSAFE_EXTENSIONS, true)) {
            return $path;
        }

        if (self::isExternalUrl($path)) {
            return $fallback;
        }

        $jpgPath = self::withExtension($path, 'jpg');
        if (self::existsPublicly($jpgPath)) {
            return $jpgPath;
        }

        $pngPath = self::withExtension($path, 'png');
        if (self::existsPublicly($pngPath)) {
            return $pngPath;
        }

        return $fallback;
    }

    private static function isExternalUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    private static function withExtension(string $path, string $extension): string
    {
        return preg_replace('/\.[^.]*$/', '.' . $extension, $path) ?? $path;
    }

    private static function existsPublicly(string $path): bool
    {
        return file_exists(public_path(ltrim($path, '/')));
    }
}
