<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Import SCORM - SOURCE UNIQUE (DRY) de la VALIDATION + EXTRACTION SÉCURISÉE
 * d'un paquet SCORM (.zip contenant un « imsmanifest.xml » à la racine), et de
 * la RÉSOLUTION du point de lancement (launch URL) du SCO principal.
 *
 * PÉRIMÈTRE MVP (documenté, voir config('academy.scorm') pour le détail) :
 *  - SINGLE-SCO uniquement : on retient le PREMIER <item>/<resource> de la
 *    PREMIÈRE <organization> du manifeste. Un paquet multi-SCO n'est PAS géré ;
 *  - SCORM 1.2 pris en charge intégralement, SCORM 2004 en mode basique (pas
 *    de moteur de séquencement IMS SS).
 *
 * SÉCURITÉ (contenu ZIP + XML tous deux NON FIABLES, viennent d'un tiers) :
 *  - taille bornée (compressé ET décompressé, anti zip-bomb, cf. H5pPackageService) ;
 *  - le fichier doit s'ouvrir comme un ZIP valide (jamais de confiance au mime) ;
 *  - « imsmanifest.xml » obligatoire À LA RACINE (sinon rejet propre) ;
 *  - PARSING XML SANS RÉSOLUTION D'ENTITÉS EXTERNES (anti-XXE) : chargement via
 *    LIBXML_NONET SANS LIBXML_DTDLOAD/LIBXML_NOENT - aucune requête réseau ni
 *    inclusion de fichier externe ne peut être déclenchée par un manifeste
 *    malveillant (payload XXE classique lecture de fichier local / SSRF) ;
 *  - le « href » de lancement lu dans le XML est lui-même validé (zip-slip,
 *    schéma d'URL absolu refusé) puis vérifié comme ENTRÉE RÉELLE du zip avant
 *    tout usage - jamais de confiance aveugle au contenu du manifeste ;
 *  - ANTI ZIP-SLIP + liste noire d'extensions exécutables : réutilise le trait
 *    ZipEntrySafety, PARTAGÉ avec H5pPackageService (DRY, même défense) ;
 *  - extraction sur le disque PRIVÉ « local » (storage/app/private) : AUCUN
 *    fichier n'est servi directement par le serveur web, tout accès passe par
 *    ScormAssetController qui re-vérifie l'inscription au cours à CHAQUE requête.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Academy\Services\Concerns\SafeXmlParsing;
use Modules\Academy\Services\Concerns\ZipEntrySafety;
use ZipArchive;

final class ScormPackageService
{
    use SafeXmlParsing;  // parseXmlSafely() anti-XXE, DRY avec MoodleBackupImportService
    use ZipEntrySafety;

    /** Taille maximale par DÉFAUT d'un paquet SCORM compressé (200 Mo). Surchargée par config. */
    public const MAX_BYTES = 200 * 1024 * 1024;

    /** Nombre d'entrées zip par DÉFAUT (anti zip-bomb). Surchargé par config. */
    public const MAX_ENTRIES = 10000;

    /** Taille DÉCOMPRESSÉE totale par DÉFAUT (500 Mo, anti zip-bomb). Surchargée par config. */
    public const MAX_EXTRACT_BYTES = 500 * 1024 * 1024;

    /** Disque PRIVÉ (jamais servi directement) : tout accès passe par ScormAssetController. */
    public const DISK = 'local';

    /** Dossier racine des paquets extraits sur le disque privé. */
    public const BASE_DIR = 'academy-scorm';

    /** Fichier manifeste obligatoire, À LA RACINE du paquet (spec SCORM). */
    public const MANIFEST_ENTRY = 'imsmanifest.xml';

    /**
     * Exception de validation (message FR sûr à afficher). Ne fuite jamais de
     * chemin serveur ni de détail XML ; aucun 500 (le contrôleur/Livewire la
     * transforme en erreur de champ).
     */
    public static function reject(string $message): \RuntimeException
    {
        return new \RuntimeException($message);
    }

    /**
     * Valide, extrait puis résout le point de lancement d'un paquet SCORM vers
     * BASE_DIR/<uuid>/ sur le disque privé.
     *
     * @return array{path: string, title: string, version: string, launch_url: string}
     *
     * @throws \RuntimeException si le paquet est invalide (taille, zip, manifeste,
     *                            launch introuvable, chemin non sûr).
     */
    public function extract(UploadedFile $file): array
    {
        // 1. Taille (COMPRESSÉE) bornée AVANT toute ouverture.
        $maxBytes = $this->maxBytes();
        if ($file->getSize() > $maxBytes) {
            throw self::reject('Le paquet SCORM dépasse la taille maximale de '.intdiv($maxBytes, 1024 * 1024).' Mo.');
        }

        $realPath = $file->getRealPath();
        if ($realPath === false || ! is_file($realPath)) {
            throw self::reject('Fichier SCORM illisible.');
        }

        // 2. Doit s'ouvrir comme un ZIP valide (on NE se fie PAS au mime déclaré).
        $zip = new ZipArchive();
        if ($zip->open($realPath) !== true) {
            throw self::reject('Le fichier n\'est pas un paquet SCORM (.zip) valide.');
        }

        try {
            // 3. ANTI ZIP-BOMB (1/2) : nombre d'entrées borné AVANT la boucle.
            if ($zip->numFiles > $this->maxEntries()) {
                throw self::reject('Paquet SCORM rejeté : trop de fichiers ('.$zip->numFiles.').');
            }

            // 4. Manifeste obligatoire À LA RACINE.
            if ($zip->locateName(self::MANIFEST_ENTRY) === false) {
                throw self::reject('Paquet SCORM invalide : « imsmanifest.xml » est manquant à la racine.');
            }

            $manifestXml = $zip->getFromName(self::MANIFEST_ENTRY);
            if (! is_string($manifestXml) || trim($manifestXml) === '') {
                throw self::reject('Paquet SCORM invalide : « imsmanifest.xml » est illisible ou vide.');
            }

            $manifest = $this->parseManifest($manifestXml);

            $version   = $this->detectVersion($manifest);
            $title     = $this->readTitle($manifest);
            $launchUrl = $this->resolveLaunchUrl($manifest);

            if ($launchUrl === null) {
                throw self::reject('Paquet SCORM invalide : impossible de déterminer le point de lancement (SCO) du manifeste.');
            }

            // Le href de lancement (lu dans un XML NON FIABLE) doit lui-même être sûr :
            // pas de zip-slip, pas de schéma d'URL absolu (http://, data:, //hôte).
            $normalizedLaunch = $this->normalizeZipEntryName(ltrim($launchUrl, './'));
            if ($this->isUnsafeZipEntryPath($normalizedLaunch)
                || preg_match('#^[a-z][a-z0-9+.-]*://#i', $launchUrl) === 1
                || str_starts_with($launchUrl, '//')
            ) {
                throw self::reject('Paquet SCORM rejeté : point de lancement non sûr dans le manifeste.');
            }

            // Le fichier de lancement doit être une ENTRÉE RÉELLE du zip (jamais de
            // confiance aveugle au manifeste : un href pointant vers un fichier
            // inexistant est un manifeste invalide, pas un lecteur qui plante).
            if ($zip->locateName($normalizedLaunch) === false) {
                throw self::reject('Paquet SCORM invalide : le fichier de lancement référencé par le manifeste est introuvable dans l\'archive.');
            }

            // 5. Extraction entrée par entrée (anti zip-slip + liste noire + anti zip-bomb).
            $uuid    = (string) Str::uuid();
            $relRoot = self::BASE_DIR.'/'.$uuid;
            $disk    = Storage::disk(self::DISK);

            $maxExtractBytes = $this->maxExtractBytes();
            $extractedBytes  = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = (string) $zip->getNameIndex($i);

                if ($entryName === '' || str_ends_with($entryName, '/')) {
                    continue;
                }

                $normalized = $this->normalizeZipEntryName($entryName);

                if ($this->isUnsafeZipEntryPath($normalized)) {
                    throw self::reject('Paquet SCORM rejeté : chemin de fichier non sûr détecté.');
                }

                if ($this->isIgnorableZipEntry($normalized)) {
                    continue;
                }

                if ($this->isBlockedZipExtension($normalized)) {
                    continue;
                }

                $contents = $zip->getFromIndex($i);
                if ($contents === false) {
                    continue;
                }

                $extractedBytes += strlen($contents);
                if ($extractedBytes > $maxExtractBytes) {
                    $disk->deleteDirectory($relRoot);
                    throw self::reject('Paquet SCORM rejeté : le contenu décompressé dépasse la taille autorisée.');
                }

                $disk->put($relRoot.'/'.$normalized, $contents);
            }

            // 6. Garde-fou final : le manifeste ET le fichier de lancement ont bien
            // été posés (le fichier de lancement pourrait avoir été filtré par la
            // liste noire d'extensions, ex. un launch en .php - cas invalide).
            if (! $disk->exists($relRoot.'/'.self::MANIFEST_ENTRY)
                || ! $disk->exists($relRoot.'/'.$normalizedLaunch)) {
                $disk->deleteDirectory($relRoot);
                throw self::reject('Extraction du paquet SCORM incomplète (fichier de lancement filtré ou manquant).');
            }

            return [
                'path'       => $relRoot,
                'title'      => $title,
                'version'    => $version,
                'launch_url' => $normalizedLaunch,
            ];
        } finally {
            $zip->close();
        }
    }

    /**
     * Supprime le dossier extrait d'un paquet SCORM. ANTI-TRAVERSAL : on refuse
     * tout chemin qui ne commence pas par BASE_DIR/ ou qui contient « .. ».
     */
    public function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        $normalized = trim(str_replace('\\', '/', $relativePath), '/');

        if (! str_starts_with($normalized, self::BASE_DIR.'/') || str_contains($normalized, '..')) {
            return;
        }

        Storage::disk(self::DISK)->deleteDirectory($normalized);
    }

    /**
     * Résout un chemin d'ASSET demandé (ex. depuis l'iframe du lecteur) contre
     * le dossier extrait d'un item, en le validant intégralement (ANTI-IDOR +
     * anti-traversal) : retourne le chemin RELATIF AU DISQUE (utilisable avec
     * Storage::disk(self::DISK)) si l'asset existe réellement, sinon null.
     *
     * Utilisé par ScormAssetController - jamais de confiance dans le chemin
     * fourni par le client au-delà de cette validation stricte.
     */
    public function resolveAssetPath(?string $packageRelDir, string $requestedPath): ?string
    {
        if ($packageRelDir === null || $packageRelDir === '') {
            return null;
        }

        $baseNormalized = trim(str_replace('\\', '/', $packageRelDir), '/');
        if (! str_starts_with($baseNormalized, self::BASE_DIR.'/') || str_contains($baseNormalized, '..')) {
            return null;
        }

        $requestedNormalized = $this->normalizeZipEntryName(ltrim($requestedPath, '/'));
        if ($requestedNormalized === '' || $this->isUnsafeZipEntryPath($requestedNormalized)) {
            return null;
        }

        $full = $baseNormalized.'/'.$requestedNormalized;

        return Storage::disk(self::DISK)->exists($full) ? $full : null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MANIFESTE - parsing XXE-SAFE + résolution single-SCO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Parse le manifeste XML de façon SÛRE (anti-XXE, voir Concerns\SafeXmlParsing
     * partagé avec MoodleBackupImportService) et lève un message métier clair si
     * le manifeste n'est pas un XML valide.
     */
    private function parseManifest(string $xml): \SimpleXMLElement
    {
        $doc = $this->parseXmlSafely($xml);
        if ($doc === null) {
            throw self::reject('Paquet SCORM invalide : « imsmanifest.xml » n\'est pas un XML valide.');
        }

        return $doc;
    }

    /**
     * Détecte la version SCORM (« 1.2 » ou « 2004 ») à partir des espaces de noms
     * XML déclarés sur l'élément racine et/ou de <metadata><schemaversion>.
     * Repli « inconnu » si aucun indice fiable (le paquet est quand même accepté :
     * on tente le lancement en mode SCORM 1.2 par défaut côté runtime).
     */
    private function detectVersion(\SimpleXMLElement $manifest): string
    {
        $namespaces = $manifest->getDocNamespaces(true);
        $joined     = strtolower(implode(' ', $namespaces));

        if (str_contains($joined, 'adlcp_v1p3') || str_contains($joined, '2004')) {
            return '2004';
        }
        if (str_contains($joined, 'adlcp_rootv1p2')) {
            return '1.2';
        }

        $schemaVersion = strtolower((string) ($manifest->metadata->schemaversion ?? ''));
        if (str_contains($schemaVersion, '2004')) {
            return '2004';
        }
        if (str_contains($schemaVersion, '1.2')) {
            return '1.2';
        }

        return 'inconnu';
    }

    /** Lit le titre de la première organisation (best-effort, jamais d'échec). */
    private function readTitle(\SimpleXMLElement $manifest): string
    {
        $organizations = $manifest->organizations ?? null;
        if ($organizations !== null) {
            foreach ($organizations->organization as $organization) {
                $title = trim((string) ($organization->title ?? ''));
                if ($title !== '') {
                    return $title;
                }
            }
        }

        return 'Contenu SCORM importé';
    }

    /**
     * Résout le point de lancement SINGLE-SCO : premier <item> (avec
     * identifierref) de la PREMIÈRE organisation, puis la <resource>
     * correspondante et son attribut « href ». Repli : si aucune organisation
     * n'est structurée ainsi, on prend la PREMIÈRE resource avec un href
     * (paquet minimal). null si rien d'exploitable (manifeste invalide).
     */
    private function resolveLaunchUrl(\SimpleXMLElement $manifest): ?string
    {
        $resourcesByIdentifier = [];
        if (isset($manifest->resources)) {
            foreach ($manifest->resources->resource as $resource) {
                $identifier = (string) ($resource['identifier'] ?? '');
                $href       = (string) ($resource['href'] ?? '');
                if ($identifier !== '') {
                    $resourcesByIdentifier[$identifier] = $href;
                }
            }
        }

        if (isset($manifest->organizations)) {
            foreach ($manifest->organizations->organization as $organization) {
                foreach ($organization->item as $item) {
                    $ref = (string) ($item['identifierref'] ?? '');
                    if ($ref !== '' && isset($resourcesByIdentifier[$ref]) && $resourcesByIdentifier[$ref] !== '') {
                        return $resourcesByIdentifier[$ref];
                    }
                }
            }
        }

        // Repli : première resource avec un href non vide (manifeste minimal
        // sans structure organizations/item exploitable).
        foreach ($resourcesByIdentifier as $href) {
            if ($href !== '') {
                return $href;
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BORNES DE SÉCURITÉ (config, mêmes noms que H5pPackageService)
    // ─────────────────────────────────────────────────────────────────────────

    private function maxBytes(): int
    {
        $kb = (int) config('academy.scorm.max_kb', intdiv(self::MAX_BYTES, 1024));

        return $kb > 0 ? $kb * 1024 : self::MAX_BYTES;
    }

    private function maxEntries(): int
    {
        $n = (int) config('academy.scorm.max_entries', self::MAX_ENTRIES);

        return $n > 0 ? $n : self::MAX_ENTRIES;
    }

    private function maxExtractBytes(): int
    {
        $kb = (int) config('academy.scorm.max_extract_kb', intdiv(self::MAX_EXTRACT_BYTES, 1024));

        return $kb > 0 ? $kb * 1024 : self::MAX_EXTRACT_BYTES;
    }
}
