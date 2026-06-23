<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F15 - SAUVEGARDE / RESTAURATION / IMPORT de cours (parité Moodle backup/restore).
 *
 * SOURCE UNIQUE (DRY) de la sérialisation PORTABLE d'un cours et de sa restauration.
 * Un « backup » Academy est une STRUCTURE pédagogique, JAMAIS des données d'élèves :
 *
 *   EXPORTÉ  : métadonnées du cours (réglages, grade_letter_scheme, completion_criteria),
 *              arbre chapitres > leçons > items (payload COMPLET), devoirs + grilles
 *              (rubric), catégories/items de notes (carnet), et - optionnellement - le
 *              CONTENU de la banque de questions référencée par les items quiz.
 *
 *   EXCLU    : inscriptions, rôles de cours, complétions, progression, tentatives de
 *              quiz, soumissions, certificats, cohortes, annonces, forum, rétroaction,
 *              badges - bref TOUTE donnée personnelle d'étudiant. C'est à la fois plus
 *              sûr (Loi 25 : aucune fuite de renseignement personnel) et plus propre
 *              (on restaure un MODÈLE de cours neuf, pas un historique).
 *
 * RÉFÉRENCES INTERNES (le point délicat de la restauration) : certaines données
 * pointent vers d'autres entités du MÊME cours par identifiant de base de données :
 *   - payload['access_restrictions']['conditions'][].item_id  -> un autre LessonItem
 *   - payload['question_bank']['category_id']                 -> une QuestionCategory
 *   - course.completion_criteria['items'][]                   -> des LessonItem
 *   - grade_items.item_id (+ item_type)                       -> LessonItem ou Assignment
 *   - grade_items.grade_category_id                           -> GradeCategory
 *   - assignments.lesson_id                                   -> Lesson
 * À l'export, ces références gardent leur identifiant LOCAL d'origine (clé `_ref` =
 * id source). À l'import, on crée d'abord toutes les entités en mémorisant une table
 * de correspondance `id source -> nouvel id`, puis on REMAPPE chaque référence vers le
 * NOUVEL identifiant. Une référence orpheline (cible absente) est retirée proprement.
 *
 * SÉCURITÉ : le service ne fait PAS d'autorisation (séparation des responsabilités).
 * L'appelant (contrôleur d'export / composant d'import) DOIT autoriser côté serveur
 * (manageStructure pour exporter, create pour importer) AVANT d'appeler ces méthodes.
 * L'import VALIDE STRICTEMENT le JSON (version, types, listes blanches, bornes) :
 * un fichier malformé ou hostile lève InvalidCourseBackupException (jamais un 500 brut,
 * aucune exécution de code, aucune injection - le JSON ne porte que des données).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academy\Exceptions\InvalidCourseBackupException;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\GradeCategory;
use Modules\Academy\Models\GradeItem;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Models\RubricCriterion;
use Modules\Academy\Models\RubricLevel;

class CourseBackupService
{
    /** Version du format de sauvegarde (SemVer du schéma JSON, pas de l'app). */
    public const FORMAT_VERSION = '1.0';

    /** Versions de format que l'import sait restaurer (rétrocompatibilité). */
    public const SUPPORTED_FORMATS = ['1.0'];

    /** Niveaux de cours autorisés (liste blanche). */
    private const LEVELS = ['intro', 'inter', 'avance'];

    /** Visibilités autorisées (liste blanche). */
    private const VISIBILITIES = ['public', 'unlisted', 'private'];

    /** Types d'items de grade autorisés (liste blanche). */
    private const GRADE_ITEM_TYPES = ['quiz', 'assignment'];

    /** Types de questions de banque autorisés (liste blanche). */
    private const QUESTION_TYPES = ['mcq', 'truefalse', 'short', 'matching'];

    // ─────────────────────────────────────────────────────────────────────────────
    // EXPORT
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Sérialise un cours en structure PORTABLE et versionnée (prête au json_encode).
     * Les identifiants de base d'origine sont conservés comme clés locales (`_ref`) ;
     * les données personnelles d'étudiants ne sont JAMAIS incluses.
     *
     * @return array<string, mixed>
     */
    public function export(Course $course): array
    {
        $course->loadMissing(['chapters.lessons.lessonItems']);

        return [
            'format_version'  => self::FORMAT_VERSION,
            'exported_at'     => now()->timezone('America/Toronto')->toIso8601String(),
            'academy_version' => (string) config('academy.version', ''),
            'app_name'        => (string) config('app.name', ''),
            'course'          => $this->exportCourseMeta($course),
            'chapters'        => $this->exportChapters($course),
            'assignments'     => $this->exportAssignments($course),
            'grade_categories' => $this->exportGradeCategories($course),
            'grade_items'     => $this->exportGradeItems($course),
            'question_bank'   => $this->exportQuestionBank($course),
        ];
    }

    /**
     * Métadonnées du cours (réglages STRUCTURELS uniquement). On EXCLUT les champs
     * propres à l'installation source (stripe_price_id, image_media_id) et les
     * références à d'autres entités locales non portables (faq_dictionary_ids,
     * tools_collection_id) : un import les laisse vides plutôt que de pointer à faux.
     *
     * @return array<string, mixed>
     */
    private function exportCourseMeta(Course $course): array
    {
        return [
            'title'                      => (string) $course->title,
            'subtitle'                   => $course->subtitle,
            'summary'                    => $course->summary,
            'description'                => $course->description,
            'language'                   => $course->language,
            'level'                      => $course->level,
            'duration_minutes'           => $course->duration_minutes,
            'visibility'                 => $course->visibility,
            'currency'                   => $course->currency,
            'seo_jsonld'                 => $course->seo_jsonld,
            'certificate_title'          => $course->certificate_title,
            'certificate_message'        => $course->certificate_message,
            'certificate_signature_name' => $course->certificate_signature_name,
            'certificate_accent_color'   => $course->certificate_accent_color,
            'grade_letter_scheme'        => $course->grade_letter_scheme,
            'completion_criteria'        => $course->completion_criteria,
        ];
    }

    /**
     * Arbre chapitres > leçons > items. Chaque nœud garde son id d'origine en `_ref`
     * (clé locale stable servant au remappage des références internes à l'import).
     *
     * @return array<int, array<string, mixed>>
     */
    private function exportChapters(Course $course): array
    {
        // Utilise les relations déjà chargées par loadMissing() en début d'export :
        // zéro requête N+1, tri sur la collection en mémoire (même résultat qu'orderBy).
        return $course->chapters
            ->sortBy('position')
            ->values()
            ->map(function (Chapter $chapter): array {
                return [
                    '_ref'     => (int) $chapter->id,
                    'title'    => $chapter->title,
                    'position' => (int) $chapter->position,
                    'summary'  => $chapter->summary,
                    'lessons'  => $chapter->lessons
                        ->sortBy('position')
                        ->values()
                        ->map(fn (Lesson $lesson): array => [
                            '_ref'              => (int) $lesson->id,
                            'title'             => $lesson->title,
                            'slug'              => $lesson->slug,
                            'position'          => (int) $lesson->position,
                            'summary'           => $lesson->summary,
                            'estimated_minutes' => $lesson->estimated_minutes,
                            'drip_days'         => $lesson->drip_days,
                            'items'             => $lesson->lessonItems
                                ->sortBy('position')
                                ->values()
                                ->map(fn (LessonItem $item): array => [
                                    '_ref'              => (int) $item->id,
                                    'type'              => $item->type,
                                    'title'             => $item->title,
                                    'position'          => (int) $item->position,
                                    'payload'           => $item->payload,
                                    'estimated_minutes' => $item->estimated_minutes,
                                    'is_required'       => (bool) $item->is_required,
                                    'external_ref'      => $item->external_ref,
                                ])->all(),
                        ])->all(),
                ];
            })->all();
    }

    /**
     * Devoirs du cours + leur grille d'évaluation (critères et niveaux). lesson_ref
     * porte l'id local de la leçon rattachée (remappé à l'import), null sinon.
     *
     * @return array<int, array<string, mixed>>
     */
    private function exportAssignments(Course $course): array
    {
        return Assignment::where('course_id', $course->id)
            ->orderBy('position')
            ->get()
            ->map(function (Assignment $assignment): array {
                return [
                    '_ref'         => (int) $assignment->id,
                    'lesson_ref'   => $assignment->lesson_id !== null ? (int) $assignment->lesson_id : null,
                    'title'        => $assignment->title,
                    'instructions' => $assignment->instructions,
                    'max_points'   => (int) $assignment->max_points,
                    'due_at'       => $assignment->due_at?->toIso8601String(),
                    'is_published' => (bool) $assignment->is_published,
                    'position'     => (int) $assignment->position,
                    'rubric_criteria' => RubricCriterion::where('assignment_id', $assignment->id)
                        ->orderBy('position')->orderBy('id')
                        ->get()
                        ->map(fn (RubricCriterion $criterion): array => [
                            'description' => $criterion->description,
                            'position'    => (int) $criterion->position,
                            'levels'      => RubricLevel::where('criterion_id', $criterion->id)
                                ->orderBy('position')->orderBy('id')
                                ->get()
                                ->map(fn (RubricLevel $level): array => [
                                    'description' => $level->description,
                                    'points'      => (int) $level->points,
                                    'position'    => (int) $level->position,
                                ])->all(),
                        ])->all(),
                ];
            })->all();
    }

    /**
     * Catégories du carnet de notes (gradebook) du cours.
     *
     * @return array<int, array<string, mixed>>
     */
    private function exportGradeCategories(Course $course): array
    {
        return GradeCategory::where('course_id', $course->id)
            ->orderBy('position')
            ->get()
            ->map(fn (GradeCategory $category): array => [
                '_ref'     => (int) $category->id,
                'name'     => $category->name,
                'weight'   => (float) $category->weight,
                'position' => (int) $category->position,
            ])->all();
    }

    /**
     * Items du carnet de notes. item_ref = id local de l'entité notée (LessonItem si
     * item_type='quiz', Assignment si 'assignment'). grade_category_ref = id local de
     * la catégorie (null si non classé). Les deux sont remappés à l'import.
     *
     * @return array<int, array<string, mixed>>
     */
    private function exportGradeItems(Course $course): array
    {
        return GradeItem::where('course_id', $course->id)
            ->orderBy('position')
            ->get()
            ->map(fn (GradeItem $gi): array => [
                'item_type'          => (string) $gi->item_type,
                'item_ref'           => (int) $gi->item_id,
                'grade_category_ref' => $gi->grade_category_id !== null ? (int) $gi->grade_category_id : null,
                'weight'             => (float) $gi->weight,
                'position'           => (int) $gi->position,
            ])->all();
    }

    /**
     * Contenu de la banque de questions RÉFÉRENCÉE par les items quiz du cours
     * (payload['question_bank']['category_id']). On exporte l'arbre rooté sur chaque
     * catégorie référencée (catégorie + sa descendance) + les questions de ces
     * catégories, pour que l'import soit AUTONOME (le nouveau propriétaire reçoit une
     * copie de la banque). Si aucun item ne référence de banque, retourne des listes
     * vides (cas le plus courant : les quiz utilisent qt_bank_key, non concerné).
     *
     * @return array{categories: array<int, array<string, mixed>>, questions: array<int, array<string, mixed>>}
     */
    private function exportQuestionBank(Course $course): array
    {
        $categoryIds = $this->referencedBankCategoryIds($course);

        if ($categoryIds === []) {
            return ['categories' => [], 'questions' => []];
        }

        // Étendre à toute la descendance (parité Moodle : un parent tire ses sous-cat.).
        $allIds = [];
        foreach (QuestionCategory::whereIn('id', $categoryIds)->get() as $cat) {
            $allIds[] = (int) $cat->id;
            if (method_exists($cat, 'descendantIds')) {
                foreach ($cat->descendantIds() as $descId) {
                    $allIds[] = (int) $descId;
                }
            }
        }
        $allIds = array_values(array_unique($allIds));

        $categories = QuestionCategory::whereIn('id', $allIds)
            ->orderBy('id')
            ->get()
            ->map(fn (QuestionCategory $cat): array => [
                '_ref'       => (int) $cat->id,
                // parent_ref n'est conservé que si le parent fait partie de l'export
                // (sinon la catégorie devient une racine pour le nouveau propriétaire).
                'parent_ref' => ($cat->parent_id !== null && in_array((int) $cat->parent_id, $allIds, true))
                    ? (int) $cat->parent_id
                    : null,
                'name'       => $cat->name,
                'slug'       => $cat->slug,
                'position'   => (int) $cat->position,
            ])->all();

        $questions = Question::whereIn('category_id', $allIds)
            ->orderBy('id')
            ->get()
            ->map(fn (Question $q): array => [
                'category_ref' => (int) $q->category_id,
                'type'         => $q->type,
                'prompt'       => $q->prompt,
                'payload'      => $q->payload,
                'explanation'  => $q->explanation,
                'difficulty'   => $q->difficulty,
                'points'       => (int) $q->points,
                'is_active'    => (bool) $q->is_active,
            ])->all();

        return ['categories' => $categories, 'questions' => $questions];
    }

    /**
     * IDs des catégories de banque référencées par au moins un item quiz du cours.
     *
     * @return array<int, int>
     */
    private function referencedBankCategoryIds(Course $course): array
    {
        $ids = [];

        foreach ($course->chapters as $chapter) {
            foreach ($chapter->lessons as $lesson) {
                foreach ($lesson->lessonItems as $item) {
                    $catId = (int) (($item->payload['question_bank']['category_id'] ?? 0));
                    if ($catId > 0) {
                        $ids[] = $catId;
                    }
                }
            }
        }

        return array_values(array_unique($ids));
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // IMPORT / RESTAURATION
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Restaure une structure de cours (issue de export()) en créant un NOUVEAU cours
     * possédé par $owner. Idempotent au sens où un ré-import crée un AUTRE cours (slug
     * unique) sans jamais écraser un cours existant. Tout dans UNE transaction.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidCourseBackupException si le format est inconnu ou les données invalides
     */
    public function import(array $data, User $owner): Course
    {
        self::assertValidEnvelope($data);

        return DB::transaction(function () use ($data, $owner): Course {
            $course = $this->createCourse($data['course'] ?? [], $owner);

            CourseRole::create([
                'course_id' => $course->id,
                'user_id'   => $owner->id,
                'role'      => 'owner',
            ]);

            // Banque de questions d'abord : son id-map sert au remappage des payloads.
            $bankCategoryMap = $this->importQuestionBank($data['question_bank'] ?? [], $owner);

            // Arbre : items créés avec payload BRUT (remappé après, une fois la map prête).
            [$lessonMap, $itemMap] = $this->importStructure($data['chapters'] ?? [], $course);

            // Devoirs + grilles (remap lesson_ref).
            $assignmentMap = $this->importAssignments($data['assignments'] ?? [], $course, $lessonMap);

            // Carnet de notes.
            $gradeCategoryMap = $this->importGradeCategories($data['grade_categories'] ?? [], $course);
            $this->importGradeItems($data['grade_items'] ?? [], $course, $itemMap, $assignmentMap, $gradeCategoryMap);

            // Remappage final des références internes des payloads + du critère cours.
            $this->remapItemPayloads($itemMap, $bankCategoryMap);
            $this->remapCourseCompletionCriteria($course, $itemMap);

            return $course->refresh();
        });
    }

    /**
     * Valide rapidement un JSON décodé : structure d'enveloppe + version supportée.
     * Échoue PROPREMENT (exception métier) plutôt que de laisser planter plus loin.
     * Exposée en public static pour que CourseImport puisse la réutiliser DÈS l'aperçu
     * (validation à l'upload, sans attendre le clic « Importer »).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidCourseBackupException
     */
    public static function assertValidEnvelope(array $data): void
    {
        $version = (string) ($data['format_version'] ?? '');
        if (! in_array($version, self::SUPPORTED_FORMATS, true)) {
            throw new InvalidCourseBackupException(
                'Format de sauvegarde non pris en charge (version « '.($version ?: 'inconnue').' »).'
            );
        }

        if (! isset($data['course']) || ! is_array($data['course'])) {
            throw new InvalidCourseBackupException('Fichier invalide : métadonnées du cours absentes.');
        }

        $title = trim((string) ($data['course']['title'] ?? ''));
        if ($title === '') {
            throw new InvalidCourseBackupException('Fichier invalide : le cours n\'a pas de titre.');
        }

        // Les sections de structure, si présentes, DOIVENT être des listes.
        foreach (['chapters', 'assignments', 'grade_categories', 'grade_items'] as $key) {
            if (isset($data[$key]) && ! is_array($data[$key])) {
                throw new InvalidCourseBackupException('Fichier invalide : section « '.$key.' » corrompue.');
            }
        }
    }

    /**
     * Crée le cours-cible : métadonnées assainies (listes blanches + bornes), TOUJOURS
     * en brouillon, gratuit, owner = importateur. Les champs propres à l'installation
     * source (Stripe, médias, références locales) sont volontairement laissés vides.
     *
     * @param  array<string, mixed>  $meta
     */
    private function createCourse(array $meta, User $owner): Course
    {
        $title = trim((string) ($meta['title'] ?? 'Cours importé')) ?: 'Cours importé';

        $level = (string) ($meta['level'] ?? 'intro');
        if (! in_array($level, self::LEVELS, true)) {
            $level = 'intro';
        }

        $visibility = (string) ($meta['visibility'] ?? 'private');
        if (! in_array($visibility, self::VISIBILITIES, true)) {
            $visibility = 'private';
        }

        $language = (string) ($meta['language'] ?? 'fr-CA');
        if ($language === '' || mb_strlen($language) > 10) {
            $language = 'fr-CA';
        }

        return Course::create([
            'slug'                       => $this->uniqueCourseSlug($title),
            'title'                      => mb_substr($title, 0, 255),
            'subtitle'                   => $this->cleanNullableString($meta['subtitle'] ?? null, 255),
            'summary'                    => $this->cleanNullableString($meta['summary'] ?? null, 2000),
            // Borne anti-DoS : longText ≤ 200 000 caractères.
            'description'                => $this->cleanNullableString($meta['description'] ?? null, 200000),
            'language'                   => $language,
            'level'                      => $level,
            'duration_minutes'           => $this->cleanNullableInt($meta['duration_minutes'] ?? null, 0, 100000),
            'visibility'                 => $visibility,
            // Un cours importé est TOUJOURS gratuit + brouillon (réglages Stripe non portables).
            'access_type'                => 'free',
            'price_cents'                => null,
            'currency'                   => $this->cleanNullableString($meta['currency'] ?? 'CAD', 8) ?? 'CAD',
            'stripe_price_id'            => null,
            'status'                     => 'draft',
            'is_template'                => false,
            'published_at'               => null,
            'seo_jsonld'                 => is_array($meta['seo_jsonld'] ?? null) ? $meta['seo_jsonld'] : null,
            // Références locales NON portables : laissées vides à l'import (anti-faux-lien).
            'faq_dictionary_ids'         => null,
            'tools_collection_id'        => null,
            'certificate_title'          => $this->cleanNullableString($meta['certificate_title'] ?? null, 255),
            // Borne anti-DoS : text ≤ 65 535 caractères.
            'certificate_message'        => $this->cleanNullableString($meta['certificate_message'] ?? null, 65535),
            'certificate_signature_name' => $this->cleanNullableString($meta['certificate_signature_name'] ?? null, 255),
            // Couleur CSS validée : #RGB, #RRGGBB, #RGBA, #RRGGBBAA uniquement (anti-injection style).
            'certificate_accent_color'   => $this->cleanCertificateColor($meta['certificate_accent_color'] ?? null),
            'grade_letter_scheme'        => is_array($meta['grade_letter_scheme'] ?? null) ? $meta['grade_letter_scheme'] : null,
            // completion_criteria recréé tel quel ; ses items[] sont remappés en fin d'import.
            'completion_criteria'        => is_array($meta['completion_criteria'] ?? null) ? $meta['completion_criteria'] : null,
            'created_by'                 => $owner->id,
            'updated_by'                 => $owner->id,
        ]);
    }

    /**
     * Recrée l'arbre chapitres > leçons > items avec payload BRUT (le remappage des
     * références internes des payloads a lieu APRÈS, une fois la table item connue).
     *
     * @param  array<int, mixed>  $chapters
     * @return array{0: array<int, int>, 1: array<int, int>}  [lessonMap, itemMap] (id source -> nouvel id)
     */
    private function importStructure(array $chapters, Course $course): array
    {
        $lessonMap = [];
        $itemMap   = [];

        foreach ($chapters as $chapterData) {
            if (! is_array($chapterData)) {
                continue;
            }

            $newChapter = Chapter::create([
                'course_id' => $course->id,
                'title'     => mb_substr(trim((string) ($chapterData['title'] ?? 'Chapitre')) ?: 'Chapitre', 0, 255),
                'position'  => (int) ($chapterData['position'] ?? 0),
                'summary'   => $this->cleanNullableString($chapterData['summary'] ?? null, 2000),
            ]);

            foreach ((array) ($chapterData['lessons'] ?? []) as $lessonData) {
                if (! is_array($lessonData)) {
                    continue;
                }

                $lessonTitle = mb_substr(trim((string) ($lessonData['title'] ?? 'Leçon')) ?: 'Leçon', 0, 255);

                $newLesson = Lesson::create([
                    'chapter_id'        => $newChapter->id,
                    'title'             => $lessonTitle,
                    'slug'              => $this->uniqueLessonSlug((string) ($lessonData['slug'] ?? '') ?: $lessonTitle),
                    'position'          => (int) ($lessonData['position'] ?? 0),
                    'summary'           => $this->cleanNullableString($lessonData['summary'] ?? null, 2000),
                    'estimated_minutes' => $this->cleanNullableInt($lessonData['estimated_minutes'] ?? null, 0, 100000),
                    'drip_days'         => $this->cleanNullableInt($lessonData['drip_days'] ?? null, 0, 3650),
                ]);

                $oldLessonRef = (int) ($lessonData['_ref'] ?? 0);
                if ($oldLessonRef > 0) {
                    $lessonMap[$oldLessonRef] = (int) $newLesson->id;
                }

                foreach ((array) ($lessonData['items'] ?? []) as $itemData) {
                    if (! is_array($itemData)) {
                        continue;
                    }

                    $payload = $itemData['payload'] ?? null;
                    if (! is_array($payload)) {
                        $payload = null;
                    }

                    $newItem = LessonItem::create([
                        'lesson_id'         => $newLesson->id,
                        'type'              => mb_substr((string) ($itemData['type'] ?? 'document'), 0, 40),
                        'title'             => mb_substr(trim((string) ($itemData['title'] ?? '')) ?: 'Élément', 0, 255),
                        'position'          => (int) ($itemData['position'] ?? 0),
                        'payload'           => $payload,
                        'estimated_minutes' => $this->cleanNullableInt($itemData['estimated_minutes'] ?? null, 0, 100000),
                        'is_required'       => (bool) ($itemData['is_required'] ?? false),
                        'external_ref'      => $this->cleanNullableString($itemData['external_ref'] ?? null, 255),
                    ]);

                    $oldItemRef = (int) ($itemData['_ref'] ?? 0);
                    if ($oldItemRef > 0) {
                        $itemMap[$oldItemRef] = (int) $newItem->id;
                    }
                }
            }
        }

        return [$lessonMap, $itemMap];
    }

    /**
     * Recrée les devoirs + grilles. lesson_ref est remappé vers la nouvelle leçon
     * (null si la leçon n'existe pas dans l'import = devoir rattaché au cours entier).
     *
     * @param  array<int, mixed>  $assignments
     * @param  array<int, int>    $lessonMap
     * @return array<int, int>  assignment id source -> nouvel id
     */
    private function importAssignments(array $assignments, Course $course, array $lessonMap): array
    {
        $assignmentMap = [];

        foreach ($assignments as $data) {
            if (! is_array($data)) {
                continue;
            }

            $lessonRef = $data['lesson_ref'] ?? null;
            $newLessonId = ($lessonRef !== null && isset($lessonMap[(int) $lessonRef]))
                ? $lessonMap[(int) $lessonRef]
                : null;

            $newAssignment = Assignment::create([
                'course_id'    => $course->id,
                'lesson_id'    => $newLessonId,
                'title'        => mb_substr(trim((string) ($data['title'] ?? 'Devoir')) ?: 'Devoir', 0, 255),
                // Borne anti-DoS : text ≤ 65 535 caractères.
                'instructions' => $this->cleanNullableString($data['instructions'] ?? null, 65535),
                'max_points'   => $this->cleanNullableInt($data['max_points'] ?? 100, 0, 100000) ?? 100,
                'due_at'       => $this->cleanNullableDate($data['due_at'] ?? null),
                'is_published' => (bool) ($data['is_published'] ?? false),
                'position'     => (int) ($data['position'] ?? 0),
            ]);

            $oldRef = (int) ($data['_ref'] ?? 0);
            if ($oldRef > 0) {
                $assignmentMap[$oldRef] = (int) $newAssignment->id;
            }

            foreach ((array) ($data['rubric_criteria'] ?? []) as $critData) {
                if (! is_array($critData)) {
                    continue;
                }

                $criterion = RubricCriterion::create([
                    'assignment_id' => $newAssignment->id,
                    'description'   => mb_substr(trim((string) ($critData['description'] ?? 'Critère')) ?: 'Critère', 0, 255),
                    'position'      => (int) ($critData['position'] ?? 0),
                ]);

                foreach ((array) ($critData['levels'] ?? []) as $levelData) {
                    if (! is_array($levelData)) {
                        continue;
                    }

                    RubricLevel::create([
                        'criterion_id' => $criterion->id,
                        'description'  => mb_substr(trim((string) ($levelData['description'] ?? '')) ?: 'Niveau', 0, 255),
                        'points'       => $this->cleanNullableInt($levelData['points'] ?? 0, 0, 100000) ?? 0,
                        'position'     => (int) ($levelData['position'] ?? 0),
                    ]);
                }
            }
        }

        return $assignmentMap;
    }

    /**
     * Recrée les catégories du carnet de notes.
     *
     * @param  array<int, mixed>  $categories
     * @return array<int, int>  id source -> nouvel id
     */
    private function importGradeCategories(array $categories, Course $course): array
    {
        $map = [];

        foreach ($categories as $data) {
            if (! is_array($data)) {
                continue;
            }

            $newCategory = GradeCategory::create([
                'course_id' => $course->id,
                'name'      => mb_substr(trim((string) ($data['name'] ?? 'Catégorie')) ?: 'Catégorie', 0, 255),
                'weight'    => max(0, min(100000, (float) ($data['weight'] ?? 0))),
                'position'  => (int) ($data['position'] ?? 0),
            ]);

            $oldRef = (int) ($data['_ref'] ?? 0);
            if ($oldRef > 0) {
                $map[$oldRef] = (int) $newCategory->id;
            }
        }

        return $map;
    }

    /**
     * Recrée les items du carnet de notes en REMAPPANT item_ref (vers LessonItem si
     * 'quiz', vers Assignment si 'assignment') et grade_category_ref. Un item dont la
     * cible n'a pas été importée est ignoré (référence orpheline = on ne crée rien).
     *
     * @param  array<int, mixed>  $gradeItems
     * @param  array<int, int>    $itemMap        LessonItem id source -> nouvel id
     * @param  array<int, int>    $assignmentMap  Assignment id source -> nouvel id
     * @param  array<int, int>    $gradeCategoryMap
     */
    private function importGradeItems(
        array $gradeItems,
        Course $course,
        array $itemMap,
        array $assignmentMap,
        array $gradeCategoryMap
    ): void {
        foreach ($gradeItems as $data) {
            if (! is_array($data)) {
                continue;
            }

            $itemType = (string) ($data['item_type'] ?? '');
            if (! in_array($itemType, self::GRADE_ITEM_TYPES, true)) {
                continue;
            }

            $oldItemRef = (int) ($data['item_ref'] ?? 0);
            $newItemId  = $itemType === 'quiz'
                ? ($itemMap[$oldItemRef] ?? null)
                : ($assignmentMap[$oldItemRef] ?? null);

            // Référence orpheline (cible non importée) : on ignore proprement.
            if ($newItemId === null) {
                continue;
            }

            $catRef = $data['grade_category_ref'] ?? null;
            $newCategoryId = ($catRef !== null && isset($gradeCategoryMap[(int) $catRef]))
                ? $gradeCategoryMap[(int) $catRef]
                : null;

            GradeItem::create([
                'course_id'         => $course->id,
                'item_type'         => $itemType,
                'item_id'           => $newItemId,
                'grade_category_id' => $newCategoryId,
                'weight'            => max(0, min(100000, (float) ($data['weight'] ?? 1))),
                'position'          => (int) ($data['position'] ?? 0),
            ]);
        }
    }

    /**
     * Recrée la banque de questions exportée (catégories + questions) au nom du nouvel
     * importateur (owner_id = $owner). parent_ref est remappé vers la NOUVELLE catégorie
     * parente. Deux passes pour les parents : on crée toutes les catégories à plat puis
     * on rattache les parents connus (un parent orphelin = catégorie racine).
     *
     * @param  array<string, mixed>  $bank
     * @return array<int, int>  QuestionCategory id source -> nouvel id
     */
    private function importQuestionBank(array $bank, User $owner): array
    {
        $categoriesData = is_array($bank['categories'] ?? null) ? $bank['categories'] : [];
        $questionsData  = is_array($bank['questions'] ?? null) ? $bank['questions'] : [];

        $map = [];

        // Passe 1 : créer les catégories à plat (sans parent), mémoriser la map.
        foreach ($categoriesData as $data) {
            if (! is_array($data)) {
                continue;
            }

            $newCategory = QuestionCategory::create([
                'owner_id'  => $owner->id,
                'parent_id' => null,
                'name'      => mb_substr(trim((string) ($data['name'] ?? 'Catégorie')) ?: 'Catégorie', 0, 255),
                'slug'      => $this->cleanNullableString($data['slug'] ?? null, 255),
                'position'  => (int) ($data['position'] ?? 0),
            ]);

            $oldRef = (int) ($data['_ref'] ?? 0);
            if ($oldRef > 0) {
                $map[$oldRef] = (int) $newCategory->id;
            }
        }

        // Passe 2 : rattacher les parents (seulement si le parent fait partie de l'import).
        foreach ($categoriesData as $data) {
            if (! is_array($data)) {
                continue;
            }

            $oldRef    = (int) ($data['_ref'] ?? 0);
            $parentRef = $data['parent_ref'] ?? null;

            if ($oldRef > 0 && $parentRef !== null
                && isset($map[$oldRef], $map[(int) $parentRef])) {
                QuestionCategory::where('id', $map[$oldRef])
                    ->update(['parent_id' => $map[(int) $parentRef]]);
            }
        }

        // Questions : remap category_ref ; ignorer celles sans catégorie importée.
        foreach ($questionsData as $data) {
            if (! is_array($data)) {
                continue;
            }

            $catRef = (int) ($data['category_ref'] ?? 0);
            if ($catRef <= 0 || ! isset($map[$catRef])) {
                continue;
            }

            $type = (string) ($data['type'] ?? '');
            if (! in_array($type, self::QUESTION_TYPES, true)) {
                continue;
            }

            $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

            // Borne payload anti-DoS : rejeter un payload anormalement volumineux
            // avant toute sérialisation Eloquent (protection mémoire).
            $encodedPayload = json_encode($payload);
            if ($encodedPayload === false || strlen($encodedPayload) > 200000) {
                continue;
            }

            Question::create([
                'category_id' => $map[$catRef],
                'owner_id'    => $owner->id,
                'type'        => $type,
                // Borne anti-DoS : text ≤ 65 535 caractères.
                'prompt'      => mb_substr((string) ($data['prompt'] ?? ''), 0, 65535),
                'payload'     => $payload,
                'explanation' => $this->cleanNullableString($data['explanation'] ?? null, 65535),
                'difficulty'  => $this->cleanNullableString($data['difficulty'] ?? null, 32),
                'points'      => $this->cleanNullableInt($data['points'] ?? 1, 1, 100) ?? 1,
                'is_active'   => (bool) ($data['is_active'] ?? true),
            ]);
        }

        return $map;
    }

    /**
     * REMAPPAGE des références internes des payloads d'items vers les NOUVEAUX ids :
     *   - access_restrictions.conditions[].item_id (grade/completion) -> itemMap ;
     *     une condition pointant un item non importé est RETIRÉE ; les conditions
     *     « group » sont retirées (les cohortes ne sont jamais exportées) ;
     *   - question_bank.category_id -> bankCategoryMap ; si la catégorie n'a pas été
     *     importée, on retire le lien de banque (repli automatique sur qt_bank_key).
     * Toute clé absente est laissée intacte (rétrocompatibilité stricte).
     *
     * @param  array<int, int>  $itemMap          LessonItem id source -> nouvel id
     * @param  array<int, int>  $bankCategoryMap  QuestionCategory id source -> nouvel id
     */
    private function remapItemPayloads(array $itemMap, array $bankCategoryMap): void
    {
        if ($itemMap === []) {
            return;
        }

        foreach (LessonItem::whereIn('id', array_values($itemMap))->get() as $item) {
            $payload = $item->payload;
            if (! is_array($payload)) {
                continue;
            }

            $changed = false;

            // 1. Restrictions d'accès.
            if (! empty($payload['access_restrictions']['conditions'])
                && is_array($payload['access_restrictions']['conditions'])) {
                $remapped = [];

                foreach ($payload['access_restrictions']['conditions'] as $cond) {
                    if (! is_array($cond)) {
                        continue;
                    }

                    $type = (string) ($cond['type'] ?? '');

                    // Cohortes non exportées : une condition de groupe ne peut pas suivre.
                    if ($type === 'group') {
                        $changed = true;
                        continue;
                    }

                    if (in_array($type, ['grade', 'completion'], true)) {
                        $oldRef = (int) ($cond['item_id'] ?? 0);
                        if ($oldRef <= 0 || ! isset($itemMap[$oldRef])) {
                            // Cible non importée : on retire la condition orpheline.
                            $changed = true;
                            continue;
                        }
                        $cond['item_id'] = $itemMap[$oldRef];
                        $changed = true;
                    }

                    $remapped[] = $cond;
                }

                if ($remapped === []) {
                    unset($payload['access_restrictions']);
                } else {
                    $payload['access_restrictions']['conditions'] = $remapped;
                }
            }

            // 2. Lien de banque de questions.
            if (! empty($payload['question_bank']['category_id'])) {
                $oldCat = (int) $payload['question_bank']['category_id'];
                if (isset($bankCategoryMap[$oldCat])) {
                    $payload['question_bank']['category_id'] = $bankCategoryMap[$oldCat];
                } else {
                    // Catégorie absente de l'import : on retire le lien (repli qt_bank_key).
                    unset($payload['question_bank']);
                }
                $changed = true;
            }

            if ($changed) {
                $item->forceFill(['payload' => $payload])->save();
            }
        }
    }

    /**
     * REMAPPE le critère de complétion du cours quand il cible des activités précises
     * (type 'selected_activities', items[] = LessonItem). Les ids non importés sont
     * retirés. Pour les autres types (all_required / percent / min_grade), rien à faire.
     *
     * @param  array<int, int>  $itemMap
     */
    private function remapCourseCompletionCriteria(Course $course, array $itemMap): void
    {
        $criteria = $course->completion_criteria;
        if (! is_array($criteria) || ($criteria['type'] ?? null) !== 'selected_activities') {
            return;
        }

        $newItems = [];
        foreach ((array) ($criteria['items'] ?? []) as $oldId) {
            $oldId = (int) $oldId;
            if ($oldId > 0 && isset($itemMap[$oldId])) {
                $newItems[] = $itemMap[$oldId];
            }
        }

        $criteria['items'] = array_values(array_unique($newItems));
        $course->forceFill(['completion_criteria' => $criteria])->save();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Aides (assainissement + slugs uniques)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Couleur CSS de certificat validée (liste blanche par regex) : accepte uniquement
     * #RGB, #RGBA, #RRGGBB ou #RRGGBBAA (3 à 8 chiffres hexadécimaux après le #).
     * Toute autre valeur (chaîne longue, JavaScript:, etc.) retourne null.
     */
    private function cleanCertificateColor(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return ($value !== '' && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value)) ? $value : null;
    }

    /** Chaîne assainie (trim + longueur max) ou null si vide/non-chaîne. */
    private function cleanNullableString(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    /** Entier borné [min, max] ou null si absent/non numérique. */
    private function cleanNullableInt(mixed $value, int $min, int $max): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return max($min, min($max, (int) $value));
    }

    /** Date ISO8601 valide -> Carbon, sinon null (défensif). */
    private function cleanNullableDate(mixed $value): ?\Illuminate\Support\Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Slug de cours UNIQUE dérivé d'un titre (suffixe -2, -3, … en cas de collision,
     * y compris avec des cours soft-deleted). Même règle que CourseDuplicator/CourseCreate.
     */
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
}
