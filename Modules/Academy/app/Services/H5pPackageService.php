<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F16 - CONTENU INTERACTIF H5P (parité Moodle « H5P »).
 *
 * SOURCE UNIQUE (DRY) de la VALIDATION + EXTRACTION SÉCURISÉE d'un paquet .h5p
 * (qui est un fichier ZIP). Un paquet H5P contient du JavaScript tiers : ce
 * service ne fait que poser les fichiers statiques sur un disque PUBLIC NON
 * exécutable (le contenu n'est JAMAIS exécuté côté serveur), le rendu se fait
 * ensuite dans un iframe SANDBOX via le player h5p-standalone (CDN jsdelivr).
 *
 * DÉFENSES (sécurité d'abord) :
 *  - taille bornée (<= 30 Mo, configurable « academy.h5p.max_kb ») ;
 *  - ANTI ZIP-BOMB : nombre d'entrées borné (« max_entries ») ET taille TOTALE
 *    décompressée bornée (« max_extract_kb ») → un petit zip ne peut pas saturer
 *    le disque ; dépassement => nettoyage du dossier partiel + rejet propre ;
 *  - le fichier doit s'ouvrir comme un ZIP valide (on ne se fie pas au mime) ;
 *  - le ZIP doit contenir « h5p.json » ET « content/content.json » (sinon rejet
 *    propre, jamais de 500) ;
 *  - ANTI ZIP-SLIP : toute entrée dont le chemin remonte (« .. ») ou est absolu
 *    est rejetée → aucun fichier ne peut être écrit hors du dossier cible ;
 *  - liste NOIRE d'extensions exécutables (php, phtml, phar, …) : ces fichiers
 *    ne sont jamais extraits (défense en profondeur, même si le disque public
 *    ne sert pas de PHP) ;
 *  - dossier cible non devinable (UUID) → un item H5P d'un autre formateur n'est
 *    pas accessible par énumération.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

final class H5pPackageService
{
    /** Taille maximale par DÉFAUT d'un paquet .h5p compressé (30 Mo). Surchargée par config. */
    public const MAX_BYTES = 30 * 1024 * 1024;

    /** Nombre d'entrées zip par DÉFAUT (anti zip-bomb). Surchargé par config. */
    public const MAX_ENTRIES = 5000;

    /** Taille DÉCOMPRESSÉE totale par DÉFAUT (200 Mo, anti zip-bomb). Surchargée par config. */
    public const MAX_EXTRACT_BYTES = 200 * 1024 * 1024;

    /** Disque public (servi via storage:link). Le contenu y est statique, jamais exécuté. */
    public const DISK = 'public';

    /** Dossier racine des paquets extraits sur le disque public. */
    public const BASE_DIR = 'academy-h5p';

    /** Fichiers obligatoires d'un paquet H5P valide (parité Moodle / h5p.org). */
    public const REQUIRED_ENTRIES = ['h5p.json', 'content/content.json'];

    /**
     * Extensions exécutables JAMAIS extraites (défense en profondeur anti-RCE).
     * Un paquet H5P légitime ne contient que js/css/json/images/audio/vidéo/fonts.
     */
    private const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phps', 'phar',
        'pht', 'shtml', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'exe', 'htaccess',
    ];

    /**
     * Exception de validation (message FR sûr à afficher). Ne fuite jamais de
     * chemin serveur ; aucun 500 (le contrôleur/Livewire la transforme en erreur
     * de champ).
     */
    public static function reject(string $message): \RuntimeException
    {
        return new \RuntimeException($message);
    }

    /**
     * Valide puis extrait un paquet .h5p vers BASE_DIR/<uuid>/ sur le disque public.
     *
     * @return array{path: string, title: string} chemin relatif du dossier extrait
     *                                             (ex. « academy-h5p/<uuid> ») + titre.
     *
     * @throws \RuntimeException si le paquet est invalide (taille, zip, structure, slip).
     */
    public function extract(UploadedFile $file): array
    {
        // 1. Taille (COMPRESSÉE) bornée AVANT toute ouverture. Lue depuis la config
        //    (« academy.h5p.max_kb », défaut 30 Mo) pour cohérence avec l'upload.
        $maxBytes = $this->maxBytes();
        if ($file->getSize() > $maxBytes) {
            throw self::reject('Le paquet H5P dépasse la taille maximale de '.intdiv($maxBytes, 1024 * 1024).' Mo.');
        }

        $realPath = $file->getRealPath();
        if ($realPath === false || ! is_file($realPath)) {
            throw self::reject('Fichier H5P illisible.');
        }

        // 2. Doit s'ouvrir comme un ZIP valide (on NE se fie PAS au mime déclaré).
        $zip = new ZipArchive();
        if ($zip->open($realPath) !== true) {
            throw self::reject('Le fichier n\'est pas un paquet H5P (.h5p) valide.');
        }

        try {
            // 3. ANTI ZIP-BOMB (1/2) : nombre d'entrées borné AVANT la boucle. Un zip
            //    avec un nombre démesuré d'entrées est rejeté proprement (jamais de 500).
            if ($zip->numFiles > $this->maxEntries()) {
                throw self::reject('Paquet H5P rejeté : trop de fichiers ('.$zip->numFiles.').');
            }

            // 4. Structure obligatoire : h5p.json + content/content.json.
            foreach (self::REQUIRED_ENTRIES as $required) {
                if ($zip->locateName($required) === false) {
                    throw self::reject('Paquet H5P invalide : « '.$required.' » est manquant.');
                }
            }

            // 5. Titre (best-effort) depuis h5p.json.
            $title = $this->readTitle($zip);

            // 6. Extraction entrée par entrée (anti zip-slip + liste noire + anti zip-bomb).
            $uuid    = (string) Str::uuid();
            $relRoot = self::BASE_DIR.'/'.$uuid;
            $disk    = Storage::disk(self::DISK);

            // ANTI ZIP-BOMB (2/2) : accumulateur de la taille TOTALE décompressée.
            $maxExtractBytes = $this->maxExtractBytes();
            $extractedBytes  = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = (string) $zip->getNameIndex($i);

                // Dossiers : ignorés (créés implicitement par put()).
                if ($entryName === '' || str_ends_with($entryName, '/')) {
                    continue;
                }

                // ANTI ZIP-SLIP : aucun chemin absolu, aucune remontée « .. ».
                $normalized = str_replace('\\', '/', $entryName);
                if (str_starts_with($normalized, '/')
                    || str_contains($normalized, '../')
                    || str_contains($normalized, '..\\')
                    || in_array($normalized, ['..'], true)
                ) {
                    throw self::reject('Paquet H5P rejeté : chemin de fichier non sûr détecté.');
                }

                // Métadonnées macOS / fichiers cachés : ignorés.
                if (str_starts_with($normalized, '__MACOSX/') || str_contains($normalized, '/.')) {
                    continue;
                }

                // Liste NOIRE d'extensions exécutables : jamais extraites.
                $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
                if ($ext !== '' && in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
                    continue;
                }

                $contents = $zip->getFromIndex($i);
                if ($contents === false) {
                    continue; // entrée illisible : on l'ignore plutôt que d'échouer
                }

                // ANTI ZIP-BOMB : on somme la taille RÉELLE décompressée. Au dépassement
                // du seuil, on nettoie le dossier déjà extrait et on rejette proprement
                // (jamais de 500, aucun fichier orphelin laissé sur le disque).
                $extractedBytes += strlen($contents);
                if ($extractedBytes > $maxExtractBytes) {
                    $disk->deleteDirectory($relRoot);
                    throw self::reject('Paquet H5P rejeté : le contenu décompressé dépasse la taille autorisée.');
                }

                // Écriture via le disque (compatible Storage::fake en test ;
                // le disque public ne sert jamais de PHP).
                $disk->put($relRoot.'/'.$normalized, $contents);
            }

            // 7. Garde-fou final : les fichiers obligatoires ont bien été posés.
            if (! $disk->exists($relRoot.'/h5p.json')
                || ! $disk->exists($relRoot.'/content/content.json')) {
                // Nettoyage si l'extraction a échoué à mi-chemin.
                $disk->deleteDirectory($relRoot);
                throw self::reject('Extraction du paquet H5P incomplète.');
            }

            return ['path' => $relRoot, 'title' => $title];
        } finally {
            $zip->close();
        }
    }

    /**
     * Supprime le dossier extrait d'un paquet H5P. ANTI-TRAVERSAL : on refuse tout
     * chemin qui ne commence pas par BASE_DIR/ ou qui contient « .. ».
     */
    public function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        $normalized = trim(str_replace('\\', '/', $relativePath), '/');

        if (! str_starts_with($normalized, self::BASE_DIR.'/')
            || str_contains($normalized, '..')) {
            return; // chemin hors périmètre : on ne touche à rien
        }

        Storage::disk(self::DISK)->deleteDirectory($normalized);
    }

    /**
     * URL publique du dossier extrait, consommée par le player h5p-standalone
     * (h5pJsonPath). null si le chemin est hors périmètre.
     */
    public function publicUrl(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        $normalized = trim(str_replace('\\', '/', $relativePath), '/');
        if (! str_starts_with($normalized, self::BASE_DIR.'/') || str_contains($normalized, '..')) {
            return null;
        }

        return rtrim(Storage::disk(self::DISK)->url($normalized), '/');
    }

    /**
     * Taille maximale du fichier COMPRESSÉ (octets). Lue depuis la config
     * (« academy.h5p.max_kb », en Ko) ; repli sur la constante par défaut.
     */
    private function maxBytes(): int
    {
        $kb = (int) config('academy.h5p.max_kb', intdiv(self::MAX_BYTES, 1024));

        return $kb > 0 ? $kb * 1024 : self::MAX_BYTES;
    }

    /**
     * Nombre maximal d'entrées du zip (anti zip-bomb). Lu depuis la config
     * (« academy.h5p.max_entries ») ; repli sur la constante par défaut.
     */
    private function maxEntries(): int
    {
        $n = (int) config('academy.h5p.max_entries', self::MAX_ENTRIES);

        return $n > 0 ? $n : self::MAX_ENTRIES;
    }

    /**
     * Taille maximale TOTALE décompressée (octets). Lue depuis la config
     * (« academy.h5p.max_extract_kb », en Ko) ; repli sur la constante par défaut.
     */
    private function maxExtractBytes(): int
    {
        $kb = (int) config('academy.h5p.max_extract_kb', intdiv(self::MAX_EXTRACT_BYTES, 1024));

        return $kb > 0 ? $kb * 1024 : self::MAX_EXTRACT_BYTES;
    }

    /** Lit le titre depuis h5p.json (best-effort, jamais d'échec). */
    private function readTitle(ZipArchive $zip): string
    {
        $raw = $zip->getFromName('h5p.json');
        if (is_string($raw)) {
            $meta = json_decode($raw, true);
            if (is_array($meta) && is_string($meta['title'] ?? null) && trim($meta['title']) !== '') {
                return trim((string) $meta['title']);
            }
        }

        return 'Contenu interactif H5P';
    }
}
