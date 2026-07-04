<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Import d'une sauvegarde MOODLE (.mbz, ZIP standard) vers un cours Academy.
 * Parité Moodle « restauration », PÉRIMÈTRE MVP volontairement borné (voir
 * config('academy.moodle_import') pour le détail documenté) :
 *
 *   - lecture DIRECTE des entrées du zip (AUCUNE extraction massive sur disque,
 *     contrairement à ScormPackageService/H5pPackageService - seul le fichier
 *     joint d'une activité « resource », s'il est retrouvé, est copié dans la
 *     collection média « attachments » du nouvel item) ;
 *   - un cours Moodle -> un NOUVEAU Course Academy (TOUJOURS brouillon, gratuit,
 *     propriétaire = importateur, JAMAIS publié automatiquement) ;
 *   - UN SEUL chapitre regroupe l'import ; chaque SECTION Moodle devient une
 *     Lesson Academy (parité avec la hiérarchie Course > Chapter > Lesson > Item) ;
 *   - SEULES les activités « page », « resource » et « label » deviennent des
 *     items Academy (type « document », payload['rich_text']) ; toute autre
 *     activité (quiz, assign, forum, scorm, h5pactivity, url, workshop, etc.)
 *     est IGNORÉE mais TOUJOURS comptée par type et rapportée à l'appelant
 *     (jamais de perte silencieuse - voir règle produit).
 *
 * FORMAT .mbz : Moodle ne publie AUCUN schéma XML officiel (XSD) pour ses
 * sauvegardes (vérifié par recherche - le format est calqué sur le schéma de
 * base de données, stable dans l'esprit mais non contractuel). Ce service lit
 * le point d'entrée AUTORITATIF du manifeste : moodle_backup.xml/information/
 * contents/{sections,activities}, qui liste déjà titres + répertoires + types
 * sans avoir à reparser chaque sections/section_N.xml. Chaque activité retenue
 * (page/resource/label) est ensuite lue depuis son propre
 * activities/<type>_<id>/<type>.xml (structure confirmée : <activity><id>...
 * <modulename>...<page|resource|label>...</page|resource|label></activity>).
 *
 * SÉCURITÉ (contenu ZIP + XML tous deux NON FIABLES, viennent d'un tiers) :
 *  - taille (compressée) bornée AVANT toute ouverture ;
 *  - le fichier doit s'ouvrir comme un ZIP valide (jamais de confiance au mime) ;
 *  - nombre d'entrées borné (anti zip-bomb,« max_entries ») ;
 *  - PARSING XML SANS RÉSOLUTION D'ENTITÉS EXTERNES (anti-XXE, voir
 *    Concerns\SafeXmlParsing partagé avec ScormPackageService) ;
 *  - total d'octets DÉCOMPRESSÉS lus pendant tout le parsing borné (« max_read_kb »,
 *    anti zip-bomb même SANS extraction massive : une archive peut contenir un
 *    XML minuscule compressé qui explose en plusieurs Go une fois décompressé) ;
 *  - ANTI ZIP-SLIP : réutilise ZipEntrySafety (PARTAGÉ avec H5P/SCORM) pour
 *    valider tout chemin lu dans le zip (y compris les chemins ISSUS du XML
 *    NON FIABLE : « directory » d'une section/activité, chemin de fichier
 *    « files/<hash> ») - jamais de confiance aveugle au manifeste ;
 *  - le fichier joint d'une activité « resource » n'est rapatrié QUE s'il est une
 *    entrée RÉELLE du zip, sous la taille max configurée, ET d'une extension
 *    dans la liste blanche déjà acceptée par LessonItem (pdf/doc/docx/jpg/png/webp) -
 *    sinon l'item « resource » est quand même créé, SANS pièce jointe (jamais
 *    bloquant, jamais d'exécution de code) ;
 *  - le cours créé est TOUJOURS un brouillon (status=draft, access_type=free) :
 *    aucune donnée importée n'est jamais publiée automatiquement ;
 *  - le service NE FAIT PAS d'autorisation (séparation des responsabilités,
 *    même convention que CourseBackupService) : l'appelant DOIT avoir autorisé
 *    'create' sur Course AVANT d'appeler import().
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academy\Exceptions\InvalidCourseBackupException;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\Concerns\SafeXmlParsing;
use Modules\Academy\Services\Concerns\ZipEntrySafety;
use ZipArchive;

final class MoodleBackupImportService
{
    use SafeXmlParsing;
    use ZipEntrySafety;

    /** Taille maximale par DÉFAUT du fichier .mbz compressé (200 Mo). Surchargée par config. */
    public const MAX_BYTES = 200 * 1024 * 1024;

    /** Nombre d'entrées zip par DÉFAUT (anti zip-bomb). Surchargé par config. */
    public const MAX_ENTRIES = 20000;

    /** Total d'octets DÉCOMPRESSÉS lus par DÉFAUT pendant tout le parsing (300 Mo). */
    public const MAX_READ_BYTES = 300 * 1024 * 1024;

    /** Taille max par DÉFAUT du fichier joint d'une activité « resource » (20 Mo). */
    public const MAX_ATTACHMENT_BYTES = 20 * 1024 * 1024;

    /** Fichier manifeste obligatoire, À LA RACINE du paquet (point d'entrée Moodle). */
    public const MANIFEST_ENTRY = 'moodle_backup.xml';

    /** Types d'activité Moodle SIMPLES pris en charge (parité « document » Academy). */
    private const SUPPORTED_ACTIVITY_TYPES = ['page', 'resource', 'label'];

    /** Extensions de pièce jointe acceptées (même liste blanche que HandlesItemMedia). */
    private const ATTACHMENT_MIME_BY_EXTENSION = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];

    /** Bornes de taille XML PAR ENTRÉE (généreuses - défense en profondeur, pas une limite fonctionnelle). */
    private const MAX_MANIFEST_ENTRY_BYTES  = 5 * 1024 * 1024;
    private const MAX_ACTIVITY_ENTRY_BYTES  = 2 * 1024 * 1024;
    private const MAX_FILES_INDEX_BYTES     = 10 * 1024 * 1024;

    /**
     * Exception de validation (message FR sûr à afficher). Jamais de 500, jamais
     * de détail serveur/XML fuité.
     */
    public static function reject(string $message): InvalidCourseBackupException
    {
        return new InvalidCourseBackupException($message);
    }

    /**
     * Importe un fichier .mbz vers un NOUVEAU cours Academy (brouillon, owner =
     * $owner). Retourne un résumé structuré, jamais silencieux sur ce qui a été
     * ignoré.
     *
     * @return array{
     *   course: Course,
     *   sections_imported: int,
     *   items_imported: int,
     *   items_ignored: array<string, int>,
     * }
     *
     * @throws InvalidCourseBackupException si le fichier n'est pas une sauvegarde
     *                                       Moodle valide/reconnue.
     */
    public function import(UploadedFile $file, User $owner): array
    {
        // 1. Taille (COMPRESSÉE) bornée AVANT toute ouverture.
        $maxBytes = $this->maxBytes();
        if ($file->getSize() > $maxBytes) {
            throw self::reject('Le fichier .mbz dépasse la taille maximale de '.intdiv($maxBytes, 1024 * 1024).' Mo.');
        }

        $realPath = $file->getRealPath();
        if ($realPath === false || ! is_file($realPath)) {
            throw self::reject('Fichier .mbz illisible.');
        }

        // 2. Doit s'ouvrir comme un ZIP valide (on NE se fie PAS au mime déclaré).
        $zip = new ZipArchive();
        if ($zip->open($realPath) !== true) {
            throw self::reject('Le fichier n\'est pas une sauvegarde Moodle (.mbz) valide : ce n\'est pas une archive ZIP.');
        }

        try {
            // 3. ANTI ZIP-BOMB (1/2) : nombre d'entrées borné AVANT toute lecture.
            if ($zip->numFiles > $this->maxEntries()) {
                throw self::reject('Sauvegarde Moodle rejetée : trop de fichiers dans l\'archive ('.$zip->numFiles.').');
            }

            $totalRead = 0;
            $maxRead   = $this->maxReadBytes();

            // 4. Manifeste obligatoire À LA RACINE.
            $manifestXml = $this->readRequiredEntry($zip, self::MANIFEST_ENTRY, $totalRead, $maxRead, self::MAX_MANIFEST_ENTRY_BYTES);
            $manifest    = $this->parseXmlSafely($manifestXml);
            if ($manifest === null) {
                throw self::reject('Sauvegarde Moodle invalide : « moodle_backup.xml » n\'est pas un XML valide.');
            }

            // 5. Format Moodle RECONNU : élément racine + noeud <information> attendus.
            if ($manifest->getName() !== 'moodle_backup' || ! isset($manifest->information)) {
                throw self::reject('Ce fichier ne ressemble pas à une sauvegarde Moodle (.mbz) reconnue.');
            }

            $information = $manifest->information;
            $courseTitle = $this->extractCourseTitle($information);

            $sections   = $this->extractSections($information);
            $activities = $this->extractActivitiesBySection($information);

            return DB::transaction(function () use ($zip, $owner, $courseTitle, $sections, $activities, &$totalRead, $maxRead): array {
                $course = $this->createCourse($courseTitle, $owner);

                CourseRole::create([
                    'course_id' => $course->id,
                    'user_id'   => $owner->id,
                    'role'      => 'owner',
                ]);

                $chapter = Chapter::create([
                    'course_id' => $course->id,
                    'title'     => mb_substr($courseTitle, 0, 255),
                    'position'  => 1,
                    'summary'   => 'Importé automatiquement d\'une sauvegarde Moodle (.mbz).',
                ]);

                $itemsImported = 0;
                $itemsIgnored  = [];
                $fileIndex     = null; // résolu paresseusement, une seule fois (files.xml)

                foreach ($sections as $position => $section) {
                    $lesson = Lesson::create([
                        'chapter_id' => $chapter->id,
                        'title'      => mb_substr($section['title'], 0, 255),
                        'slug'       => $this->uniqueLessonSlug($section['title']),
                        'position'   => $position + 1,
                    ]);

                    $sectionActivities = $activities[$section['sectionid']] ?? [];

                    foreach ($sectionActivities as $itemPosition => $activity) {
                        $modulename = $activity['modulename'];

                        if (! in_array($modulename, self::SUPPORTED_ACTIVITY_TYPES, true)) {
                            $itemsIgnored[$modulename] = ($itemsIgnored[$modulename] ?? 0) + 1;

                            continue;
                        }

                        try {
                            $created = $this->importActivity(
                                $zip,
                                $lesson,
                                $activity,
                                $itemPosition + 1,
                                $totalRead,
                                $maxRead,
                                $fileIndex
                            );
                        } catch (\Throwable) {
                            // Une activité individuelle corrompue/illisible ne fait jamais
                            // échouer tout l'import : elle est comptée « ignorée », jamais perdue en silence.
                            $created = false;
                        }

                        if ($created) {
                            $itemsImported++;
                        } else {
                            $itemsIgnored[$modulename] = ($itemsIgnored[$modulename] ?? 0) + 1;
                        }
                    }
                }

                return [
                    'course'            => $course->refresh(),
                    'sections_imported' => count($sections),
                    'items_imported'    => $itemsImported,
                    'items_ignored'     => $itemsIgnored,
                ];
            });
        } finally {
            $zip->close();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MANIFESTE - lecture du point d'entrée AUTORITATIF (information/contents)
    // ─────────────────────────────────────────────────────────────────────────

    /** Titre du cours source : original_course_fullname, sinon contents/course/title, sinon repli générique. */
    private function extractCourseTitle(\SimpleXMLElement $information): string
    {
        $title = trim((string) ($information->original_course_fullname ?? ''));
        if ($title !== '') {
            return $title;
        }

        $title = trim((string) ($information->contents->course->title ?? ''));
        if ($title !== '') {
            return $title;
        }

        return 'Cours importé de Moodle';
    }

    /**
     * Liste ORDONNÉE des sections (information/contents/sections/section), telles
     * qu'énumérées dans le manifeste (= ordre du cours source).
     *
     * @return array<int, array{sectionid: int, title: string}>
     */
    private function extractSections(\SimpleXMLElement $information): array
    {
        $sections = [];
        $index    = 1;

        foreach ($information->contents->sections->section ?? [] as $section) {
            $sectionId = (int) ($section->sectionid ?? $section->id ?? 0);
            if ($sectionId <= 0) {
                continue;
            }

            $title = trim((string) ($section->title ?? $section->name ?? ''));

            $sections[] = [
                'sectionid' => $sectionId,
                'title'     => $title !== '' ? $title : 'Section '.$index,
            ];
            $index++;
        }

        return $sections;
    }

    /**
     * Activités (information/contents/activities/activity) GROUPÉES par sectionid,
     * dans l'ORDRE d'apparition du manifeste (= ordre du cours source).
     *
     * @return array<int, array<int, array{moduleid: int, modulename: string, title: string, directory: string}>>
     */
    private function extractActivitiesBySection(\SimpleXMLElement $information): array
    {
        $bySection = [];

        foreach ($information->contents->activities->activity ?? [] as $activity) {
            $sectionId  = (int) ($activity->sectionid ?? 0);
            $modulename = strtolower(trim((string) ($activity->modulename ?? '')));
            if ($sectionId <= 0 || $modulename === '') {
                continue;
            }

            $bySection[$sectionId][] = [
                'moduleid'   => (int) ($activity->moduleid ?? $activity->id ?? 0),
                'modulename' => $modulename,
                'title'      => trim((string) ($activity->title ?? $activity->name ?? '')),
                'directory'  => trim((string) ($activity->directory ?? '')),
            ];
        }

        return $bySection;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACTIVITÉ SIMPLE (page / resource / label) -> LessonItem type « document »
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  array{moduleid: int, modulename: string, title: string, directory: string}  $activity
     * @param  \SimpleXMLElement|null  $fileIndex  passé par référence : résolu paresseusement (files.xml)
     */
    private function importActivity(
        ZipArchive $zip,
        Lesson $lesson,
        array $activity,
        int $position,
        int &$totalRead,
        int $maxRead,
        ?\SimpleXMLElement &$fileIndex
    ): bool {
        $directory = $activity['directory'];
        if ($directory === '' || $this->isUnsafeZipEntryPath($this->normalizeZipEntryName($directory))) {
            return false;
        }

        $modulename = $activity['modulename'];
        $entryPath  = rtrim($directory, '/').'/'.$modulename.'.xml';

        $xml = $this->readOptionalEntry($zip, $entryPath, $totalRead, $maxRead, self::MAX_ACTIVITY_ENTRY_BYTES);
        if ($xml === null) {
            return false;
        }

        $doc = $this->parseXmlSafely($xml);
        if ($doc === null) {
            return false;
        }

        // Structure confirmée : <activity><modulename>...</modulename><page>...</page></activity>.
        // Repli défensif : certains exports pourraient poser le type directement en racine.
        $node = isset($doc->{$modulename}) ? $doc->{$modulename} : $doc;

        $name = trim((string) ($node->name ?? $activity['title'] ?? ''));
        if ($name === '') {
            $name = ucfirst($modulename).' importé';
        }

        $body = match ($modulename) {
            'page'  => (string) ($node->content ?? $node->intro ?? ''),
            'label' => (string) ($node->intro ?? ''),
            'resource' => (string) ($node->intro ?? ''),
            default => '',
        };

        $richText = $this->htmlToPlainText($body);

        $item = LessonItem::create([
            'lesson_id'   => $lesson->id,
            'type'        => 'document',
            'title'       => mb_substr($name, 0, 255),
            'position'    => $position,
            'payload'     => ['rich_text' => $richText],
            'is_required' => false,
            'external_ref' => 'moodle:'.$modulename.':'.$activity['moduleid'],
        ]);

        if ($modulename === 'resource') {
            // Best-effort, JAMAIS bloquant : un fichier introuvable/trop gros/type non
            // supporté laisse simplement l'item SANS pièce jointe (le texte reste importé).
            try {
                $this->tryAttachResourceFile($zip, $item, $directory, $totalRead, $maxRead, $fileIndex);
            } catch (\Throwable) {
                // silencieux : la pièce jointe est un bonus, pas le cœur de l'import.
            }
        }

        return true;
    }

    /**
     * Convertit un contenu HTML Moodle (intro/content) en TEXTE BRUT sûr, en
     * préservant grossièrement la mise en paragraphe. Le rendu « document »
     * (LessonItem::renderRichText) applique de toute façon html_input=strip -
     * cette conversion évite juste que tout le balisage brut soit visible tel quel.
     */
    private function htmlToPlainText(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $normalized = preg_replace('#<(br|/p|/div|/li|/h[1-6])\s*/?>#i', "\n", $html);
        $text       = strip_tags($normalized ?? $html);
        $text       = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text       = preg_replace("/\n{3,}/", "\n\n", trim($text));

        return $text ?? '';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PIÈCE JOINTE « resource » (best-effort) - inforef.xml -> files.xml -> files/
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tente de retrouver ET rapatrier le fichier joint d'une activité « resource »
     * via son inforef.xml (id de fichier référencé) puis files.xml (métadonnées :
     * hash de contenu, nom, mimetype). Attache dans la collection Spatie
     * « attachments » de l'item SI l'extension est dans la liste blanche ET la
     * taille sous le plafond configuré. Ne lève JAMAIS : un échec quelconque
     * laisse simplement l'item sans pièce jointe.
     */
    private function tryAttachResourceFile(
        ZipArchive $zip,
        LessonItem $item,
        string $activityDirectory,
        int &$totalRead,
        int $maxRead,
        ?\SimpleXMLElement &$fileIndex
    ): void {
        $inforefXml = $this->readOptionalEntry(
            $zip,
            rtrim($activityDirectory, '/').'/inforef.xml',
            $totalRead,
            $maxRead,
            self::MAX_ACTIVITY_ENTRY_BYTES
        );
        if ($inforefXml === null) {
            return;
        }

        $inforef = $this->parseXmlSafely($inforefXml);
        if ($inforef === null || ! isset($inforef->fileref)) {
            return;
        }

        $fileId = null;
        foreach ($inforef->fileref->file ?? [] as $fileRef) {
            $id = (int) ($fileRef->id ?? $fileRef['id'] ?? 0);
            if ($id > 0) {
                $fileId = $id;

                break;
            }
        }
        if ($fileId === null) {
            return;
        }

        // files.xml (racine du zip) résolu paresseusement UNE SEULE FOIS pour tout l'import.
        if ($fileIndex === null) {
            $filesXml = $this->readOptionalEntry($zip, 'files.xml', $totalRead, $maxRead, self::MAX_FILES_INDEX_BYTES);
            if ($filesXml === null) {
                return;
            }

            $parsed = $this->parseXmlSafely($filesXml);
            if ($parsed === null) {
                return;
            }

            $fileIndex = $parsed;
        }

        $fileNode = null;
        foreach ($fileIndex->file ?? [] as $candidate) {
            $id = (int) ($candidate->id ?? $candidate['id'] ?? 0);
            if ($id === $fileId) {
                $fileNode = $candidate;

                break;
            }
        }
        if ($fileNode === null) {
            return;
        }

        $hash     = trim((string) ($fileNode->contenthash ?? ''));
        $filename = trim((string) ($fileNode->filename ?? ''));
        if ($hash === '' || $filename === '' || ! preg_match('/^[a-f0-9]{6,64}$/i', $hash)) {
            return;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (! isset(self::ATTACHMENT_MIME_BY_EXTENSION[$extension])) {
            return; // extension non prise en charge par la collection « attachments » -> pas bloquant.
        }

        $maxAttachment = $this->maxAttachmentBytes();

        // Deux dispositions Moodle usuelles : files/<2 premiers car. du hash>/<hash>, ou files/<hash>.
        $candidatePaths = ['files/'.substr($hash, 0, 2).'/'.$hash, 'files/'.$hash];

        foreach ($candidatePaths as $path) {
            $contents = $this->readOptionalEntry($zip, $path, $totalRead, $maxRead, $maxAttachment);
            if ($contents === null) {
                continue;
            }

            $this->attachContentsToItem($item, $contents, $filename, $extension);

            return;
        }
    }

    /** Écrit les octets récupérés dans un fichier temporaire puis les attache via Spatie. */
    private function attachContentsToItem(LessonItem $item, string $contents, string $displayName, string $extension): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'moodle_import_');
        if ($tmpPath === false) {
            return;
        }

        try {
            file_put_contents($tmpPath, $contents);

            $media = $item->addMedia($tmpPath)
                ->usingFileName(Str::uuid().'.'.$extension)
                ->preservingOriginal()
                ->toMediaCollection('attachments');

            $payload                = $item->payload ?? [];
            $attachments            = $payload['attachments'] ?? [];
            $attachments[]          = [
                'name'     => mb_substr($displayName, 0, 255),
                'url'      => $media->getUrl(),
                'media_id' => $media->id,
            ];
            $payload['attachments'] = array_values($attachments);
            $item->forceFill(['payload' => $payload])->save();
        } finally {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COURS - création (TOUJOURS brouillon/gratuit) + slug/lesson-slug UNIQUES
    // ─────────────────────────────────────────────────────────────────────────

    private function createCourse(string $title, User $owner): Course
    {
        $title = trim($title) ?: 'Cours importé de Moodle';

        return Course::create([
            'slug'        => $this->uniqueCourseSlug($title),
            'title'       => mb_substr($title, 0, 255),
            'language'    => 'fr-CA',
            'level'       => 'intro',
            'visibility'  => 'private',
            // Un cours importé est TOUJOURS gratuit + brouillon (jamais publié automatiquement).
            'access_type' => 'free',
            'status'      => 'draft',
            'is_template' => false,
            'currency'    => 'CAD',
            'created_by'  => $owner->id,
            'updated_by'  => $owner->id,
        ]);
    }

    /** Slug de cours UNIQUE (même règle que CourseBackupService/CourseDuplicator/CourseCreate). */
    private function uniqueCourseSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'cours';
        $slug = $base;
        $i    = 2;

        while (Course::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /** Slug de leçon UNIQUE (les slugs de leçons ne sont pas contraints unique en base). */
    private function uniqueLessonSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'lecon';
        $slug = $base;
        $i    = 2;

        while (Lesson::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LECTURE ZIP BORNÉE (anti zip-slip + anti zip-bomb CUMULATIF)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Lit une entrée OBLIGATOIRE du zip (rejette proprement si absente/trop
     * grosse/plafond cumulé dépassé).
     */
    private function readRequiredEntry(ZipArchive $zip, string $entryName, int &$totalRead, int $maxTotal, int $maxSingle): string
    {
        $contents = $this->readOptionalEntry($zip, $entryName, $totalRead, $maxTotal, $maxSingle, $rejectedTooBig);
        if ($contents === null) {
            if ($rejectedTooBig) {
                throw self::reject('Sauvegarde Moodle rejetée : « '.$entryName.' » dépasse la taille autorisée.');
            }

            throw self::reject('Ce fichier ne semble pas être une sauvegarde Moodle (.mbz) : « '.$entryName.' » est absent.');
        }

        return $contents;
    }

    /**
     * Lit une entrée OPTIONNELLE du zip : retourne null (jamais d'exception) si
     * l'entrée est absente, non sûre (zip-slip), ou dépasse le plafond PAR ENTRÉE.
     * Incrémente le compteur CUMULÉ et lève UNIQUEMENT si le plafond TOTAL (anti
     * zip-bomb) est dépassé - un fichier trop gros individuellement est juste ignoré.
     */
    private function readOptionalEntry(
        ZipArchive $zip,
        string $entryName,
        int &$totalRead,
        int $maxTotal,
        int $maxSingle,
        ?bool &$rejectedTooBig = false
    ): ?string {
        $rejectedTooBig = false;

        $normalized = $this->normalizeZipEntryName($entryName);
        if ($normalized === '' || $this->isUnsafeZipEntryPath($normalized)) {
            return null;
        }

        $index = $zip->locateName($normalized);
        if ($index === false) {
            return null;
        }

        $stat = $zip->statIndex($index);
        $size = is_array($stat) ? (int) ($stat['size'] ?? 0) : 0;

        if ($size > $maxSingle) {
            $rejectedTooBig = true;

            return null;
        }

        $totalRead += $size;
        if ($totalRead > $maxTotal) {
            throw self::reject('Sauvegarde Moodle rejetée : le contenu décompressé dépasse la taille autorisée.');
        }

        $contents = $zip->getFromIndex($index);

        return is_string($contents) ? $contents : null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BORNES DE SÉCURITÉ (config)
    // ─────────────────────────────────────────────────────────────────────────

    private function maxBytes(): int
    {
        $kb = (int) config('academy.moodle_import.max_kb', intdiv(self::MAX_BYTES, 1024));

        return $kb > 0 ? $kb * 1024 : self::MAX_BYTES;
    }

    private function maxEntries(): int
    {
        $n = (int) config('academy.moodle_import.max_entries', self::MAX_ENTRIES);

        return $n > 0 ? $n : self::MAX_ENTRIES;
    }

    private function maxReadBytes(): int
    {
        $kb = (int) config('academy.moodle_import.max_read_kb', intdiv(self::MAX_READ_BYTES, 1024));

        return $kb > 0 ? $kb * 1024 : self::MAX_READ_BYTES;
    }

    private function maxAttachmentBytes(): int
    {
        $kb = (int) config('academy.moodle_import.max_attachment_kb', intdiv(self::MAX_ATTACHMENT_BYTES, 1024));

        return $kb > 0 ? $kb * 1024 : self::MAX_ATTACHMENT_BYTES;
    }
}
