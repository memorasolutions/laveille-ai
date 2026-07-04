<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Règles de sécurité PARTAGÉES pour l'extraction d'une archive ZIP téléversée
 * (paquets H5P et SCORM) : liste NOIRE d'extensions exécutables (défense en
 * profondeur anti-RCE) + détection d'un chemin d'entrée UNSAFE (zip-slip :
 * chemin absolu ou remontée « .. ») + filtrage des artefacts macOS/fichiers
 * cachés. DRY : SOURCE UNIQUE de ces règles, partagée par H5pPackageService
 * et ScormPackageService (même menace « archive tierce non fiable », même
 * défense). Extrait sans changement de comportement depuis H5pPackageService
 * (F16) au moment d'ajouter l'import SCORM.
 */

declare(strict_types=1);

namespace Modules\Academy\Services\Concerns;

trait ZipEntrySafety
{
    /**
     * Extensions exécutables JAMAIS extraites (défense en profondeur anti-RCE).
     * Un paquet H5P/SCORM légitime ne contient que js/css/json/html/xml/images/
     * audio/vidéo/fonts.
     */
    private const ZIP_BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phps', 'phar',
        'pht', 'shtml', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'exe', 'htaccess',
    ];

    /** Normalise un nom d'entrée zip (slashes uniformes Windows/Unix). */
    private function normalizeZipEntryName(string $entryName): string
    {
        return str_replace('\\', '/', $entryName);
    }

    /**
     * Vrai si le chemin d'entrée est un ZIP-SLIP (chemin absolu ou remontée
     * « .. ») : ne doit JAMAIS être extrait, quel que soit l'appelant.
     */
    private function isUnsafeZipEntryPath(string $normalized): bool
    {
        return str_starts_with($normalized, '/')
            || str_contains($normalized, '../')
            || str_contains($normalized, '..\\')
            || $normalized === '..';
    }

    /** Vrai si l'entrée est un artefact macOS / fichier caché à ignorer silencieusement. */
    private function isIgnorableZipEntry(string $normalized): bool
    {
        return str_starts_with($normalized, '__MACOSX/') || str_contains($normalized, '/.');
    }

    /** Vrai si l'extension de l'entrée est dans la liste NOIRE des exécutables. */
    private function isBlockedZipExtension(string $normalized): bool
    {
        $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));

        return $ext !== '' && in_array($ext, self::ZIP_BLOCKED_EXTENSIONS, true);
    }
}
