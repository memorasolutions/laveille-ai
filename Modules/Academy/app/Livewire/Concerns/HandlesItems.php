<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseEditor — CRUD des items de leçon :
 * création, mise à jour, suppression, bascule is_required + confirmations
 * inline à 2 temps, ainsi que tous les helpers privés exclusifs aux items
 * (validation, construction de payload par type, catégories de banque).
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse() et
 * ré-autorise 'manageStructure' (anti-IDOR), exactement comme dans le composant
 * d'origine. La suppression et la mise à jour vérifient l'appartenance de l'item
 * à CE cours via resolveItemFor() (anti-IDOR remontant item→leçon→cours) avant
 * toute écriture. Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuestionCategory;

trait HandlesItems
{
    // ─────────────────────────────────────────────────────────────────────────────
    // ITEMS DE LEÇON (vidéo ScreenPal / document / quiz) - FE-3b
    //
    // Chaque mutation : resolveCourse() → authorize('manageStructure') →
    // resolveLessonFor / resolveItemFor (anti-IDOR) → validate → écrire.
    // Le `type` est toujours validé contre la liste blanche ITEM_TYPES.
    // ─────────────────────────────────────────────────────────────────────────────

    public function addItem(int $lessonId, string $type): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Anti-IDOR : la leçon doit appartenir à un chapitre de CE cours.
        $lesson = $this->resolveLessonFor($course, $lessonId);

        $input          = $this->newItem[$lessonId] ?? [];
        $input['type']  = $type;

        $data    = $this->validateItem($input);
        $payload = $this->buildItemPayload($data['type'], $input);

        $position = (int) LessonItem::where('lesson_id', $lesson->id)->max('position') + 1;

        $item = LessonItem::create([
            'lesson_id'         => $lesson->id,
            'type'              => $data['type'],
            'title'             => $data['title'],
            'position'          => $position,
            'payload'           => $payload,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'is_required'       => (bool) ($input['is_required'] ?? false),
            'external_ref'      => $data['external_ref'] ?? null,
        ]);

        // F19 - WIKI : garantir la page d'accueil dès la création de l'item (parité Moodle).
        if ($data['type'] === 'wiki') {
            \Modules\Academy\Services\WikiService::ensureHomePage($item, (int) auth()->id() ?: null);
        }

        // F20 - BASE DE DONNÉES : synchroniser le SCHÉMA (champs) saisi à la création.
        if ($data['type'] === 'database') {
            \Modules\Academy\Services\DatabaseService::syncFields($item, $input['database_fields'] ?? []);
        }

        // F21 - ATELIER : synchroniser la GRILLE (critères) saisie à la création.
        if ($data['type'] === 'workshop') {
            \Modules\Academy\Services\WorkshopService::syncCriteria($item, $input['workshop_criteria'] ?? []);
        }

        unset($this->newItem[$lessonId]);
        $this->flashSaved('Élément ajouté.');
    }

    /**
     * Met à jour un item. Les champs facultatifs propres à un type (vidéo / quiz)
     * sont passés en tableau associatif $extra pour éviter une signature trop longue
     * et rester rétrocompatible : un champ absent vaut null (ignoré au build).
     *
     * @param  array<string, mixed>  $extra  player_url, poster_url, duration_minutes,
     *                                        external_ref, rich_text, qt_bank_key,
     *                                        passing_score, attempts_allowed
     */
    public function updateItem(
        int $itemId,
        string $type,
        string $title,
        $estimatedMinutes = null,
        array $extra = []
    ): void {
        // Livewire v4 : un champ number vide arrive en chaine '' depuis le DOM.
        // Normaliser ''/null -> null, sinon caster en int (evite TypeError sur ?int).
        $estimatedMinutes = ($estimatedMinutes === '' || $estimatedMinutes === null)
            ? null
            : (int) $estimatedMinutes;
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Anti-IDOR : l'item doit appartenir à une leçon d'un chapitre de CE cours.
        $item = $this->resolveItemFor($course, $itemId);

        // F16 : le contenu d'un item h5p (h5p_path) n'est PAS éditable via ce
        // formulaire générique (il se remplace via replaceH5pPackage). On le
        // PRÉSERVE en l'injectant dans l'entrée pour que buildItemPayload ne
        // l'écrase pas (le formulaire ne modifie que le titre / l'achèvement).
        $h5pPreserve = [];
        if ($type === 'h5p') {
            $h5pPreserve = [
                'h5p_path'  => $item->payload['h5p_path'] ?? null,
                'h5p_title' => $item->payload['title'] ?? null,
            ];
        }

        $input = [
            'type'              => $type,
            'title'             => $title,
            'estimated_minutes' => $estimatedMinutes,
            'external_ref'      => $extra['external_ref']     ?? null,
            'player_url'        => $extra['player_url']       ?? null,
            'poster_url'        => $extra['poster_url']       ?? null,
            'duration_minutes'  => $extra['duration_minutes'] ?? null,
            'rich_text'         => $extra['rich_text']        ?? null,
            'qt_bank_key'       => $extra['qt_bank_key']      ?? null,
            'passing_score'     => $extra['passing_score']    ?? null,
            'attempts_allowed'  => $extra['attempts_allowed'] ?? null,
            // V1-c : méthode de notation sur tentatives (highest/average/first/last).
            'grading_method'    => $extra['grading_method']   ?? null,
            // QB2 : lien optionnel vers une catégorie de MA banque + nb à tirer.
            'bank_category_id'  => $extra['bank_category_id'] ?? null,
            'bank_draw_count'   => $extra['bank_draw_count']  ?? null,
            // QB3 : toggle « inclure les sous-catégories » (défaut true si absent).
            'bank_include_subcategories' => $extra['bank_include_subcategories'] ?? null,
            // V1-d : mélange (questions / réponses), limite de temps, options de révision.
            'shuffle_questions'   => $extra['shuffle_questions']   ?? null,
            'shuffle_answers'     => $extra['shuffle_answers']     ?? null,
            'time_limit_minutes'  => $extra['time_limit_minutes']  ?? null,
            'review_options'      => $extra['review_options']      ?? null,
            // V1-f : comportement de question (deferred par défaut / immediate / adaptive).
            'question_behaviour'  => $extra['question_behaviour']  ?? null,
            // ADAPTATIF : pénalité par essai raté (% saisi) + nb d'essais maximal.
            'adaptive_penalty'    => $extra['adaptive_penalty']    ?? null,
            'adaptive_max_tries'  => $extra['adaptive_max_tries']  ?? null,
            // V2-c : critère d'achèvement configurable (manual / view / min_grade / vote).
            'completion'          => $extra['completion']          ?? null,
            // CHOICE : énoncé + options (chaîne multiligne ou tableau) + réglages.
            'choice_question'     => $extra['choice_question']     ?? null,
            'choice_options'      => $extra['choice_options']      ?? null,
            'allow_multiple'      => $extra['allow_multiple']      ?? null,
            'anonymous'           => $extra['anonymous']           ?? null,
            'results_visibility'  => $extra['results_visibility']  ?? null,
            // FORUM : intro + réglages (allow_student_topics / locked).
            'forum_intro'          => $extra['forum_intro']          ?? null,
            'allow_student_topics' => $extra['allow_student_topics'] ?? null,
            'locked'               => $extra['locked']               ?? null,
            // WIKI : intro + édition collaborative (allow_student_edit).
            'wiki_intro'           => $extra['wiki_intro']           ?? null,
            'allow_student_edit'   => $extra['allow_student_edit']   ?? null,
        ] + $h5pPreserve;

        $data    = $this->validateItem($input);
        $payload = $this->buildItemPayload($data['type'], $input);

        $item->update([
            'type'              => $data['type'],
            'title'             => $data['title'],
            'payload'           => $payload,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'external_ref'      => $data['external_ref'] ?? null,
        ]);

        $this->flashSaved('Élément mis à jour.');
    }

    public function deleteItem(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        // F16 : nettoyer le dossier H5P extrait (disque public) avant suppression
        // pour ne pas laisser de contenu orphelin. delete() borne le chemin à
        // academy-h5p/ (anti-traversal).
        if ($item->type === 'h5p') {
            (new \Modules\Academy\Services\H5pPackageService())->delete($item->payload['h5p_path'] ?? null);
        }

        $item->delete();

        $this->confirmingItemDeletion = null;
        $this->flashSaved('Élément supprimé.');
    }

    public function toggleRequired(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);
        $item->update(['is_required' => ! $item->is_required]);

        $this->flashSaved('Élément mis à jour.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Confirmations inline à 2 temps — ITEMS (jamais de popup native)
    // ─────────────────────────────────────────────────────────────────────────────

    public function confirmItemDeletion(int $itemId): void
    {
        $this->confirmingItemDeletion = $itemId;
    }

    public function cancelItemDeletion(): void
    {
        $this->confirmingItemDeletion = null;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVÉS — validation + construction de payload
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Validation commune d'un item. Le `type` est contraint à la liste blanche
     * ITEM_TYPES (un type forgé hors liste est rejeté avant toute écriture).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validateItem(array $input): array
    {
        // V2-c : un critère d'achèvement vide ('' venu du <select> « Défaut ») signifie
        // « absent » → null, pour ne pas échouer la règle Rule::in (et laisser le défaut
        // du type s'appliquer via ActivityCompletionService au build).
        if (($input['completion'] ?? null) === '') {
            $input['completion'] = null;
        }

        $rules = [
            'type'              => ['required', Rule::in(self::ITEM_TYPES)],
            'title'             => 'required|string|max:255',
            'estimated_minutes' => 'nullable|integer|min:1',
            'external_ref'      => 'nullable|string|max:500',
            // Vidéo : URL d'intégration ScreenPal (champ canonique lu par le lecteur),
            // affiche (URL pour l'instant), durée estimée en minutes.
            'player_url'        => 'nullable|url|max:2000',
            'poster_url'        => 'nullable|url|max:2000',
            'duration_minutes'  => 'nullable|integer|min:1|max:1440',
            'rich_text'         => 'nullable|string|max:20000',
            // Quiz : clé de banque + seuil de réussite (0-100) + tentatives (>= 1, vide = illimité).
            'qt_bank_key'       => 'nullable|string|max:120',
            'passing_score'     => 'nullable|integer|min:0|max:100',
            'attempts_allowed'  => 'nullable|integer|min:1|max:99',
            // V1-c : méthode de notation (liste blanche stricte ; vide = défaut au build).
            'grading_method'    => ['nullable', Rule::in(\Modules\Academy\Services\QuizGradeService::METHODS)],
            // QB2 : catégorie de banque (validée comme MIENNE au build) + nb à tirer (1..50).
            'bank_category_id'  => 'nullable|integer',
            'bank_draw_count'   => 'nullable|integer|min:1|max:50',
            // QB3 : inclure les sous-catégories au tirage (parité Moodle, défaut true).
            'bank_include_subcategories' => 'nullable|boolean',
            // V1-d : mélange + limite de temps + options de révision (toutes facultatives).
            'shuffle_questions'  => 'nullable|boolean',
            'shuffle_answers'    => 'nullable|boolean',
            'time_limit_minutes' => 'nullable|integer|min:1|max:240',
            'review_options'     => 'nullable|array',
            // V1-f : comportement de question (liste blanche stricte ; vide/inconnu =
            // défaut « deferred » appliqué au build → rétrocompat stricte).
            'question_behaviour' => ['nullable', Rule::in(\Modules\Academy\Services\QuizBehaviour::BEHAVIOURS)],
            // ADAPTATIF : pénalité par essai raté (en %, 0..100) + nb d'essais (1..10).
            // Persistées (au build) UNIQUEMENT si le mode adaptatif est choisi.
            'adaptive_penalty'   => 'nullable|integer|min:0|max:100',
            'adaptive_max_tries' => 'nullable|integer|min:1|max:10',
            // V2-c : critère d'achèvement (liste blanche globale ; l'autorisation par
            // TYPE est appliquée au build, où min_grade sur un non-quiz est ignoré).
            'completion'         => ['nullable', Rule::in(\Modules\Academy\Services\ActivityCompletionService::CRITERIA)],
            // CHOICE : énoncé + réglages (les options sont validées conditionnellement).
            'choice_question'    => 'nullable|string|max:1000',
            'allow_multiple'     => 'nullable|boolean',
            'anonymous'          => 'nullable|boolean',
            'results_visibility' => ['nullable', Rule::in(\Modules\Academy\Services\ChoiceService::VISIBILITIES)],
            // FEEDBACK : intro facultative ; les questions sont validées conditionnellement.
            'feedback_intro'     => 'nullable|string|max:2000',
            // FORUM : intro facultative + réglages booléens (le défaut « allow_student_topics »
            // = true est appliqué au build quand la clé est absente).
            'forum_intro'         => 'nullable|string|max:2000',
            'allow_student_topics' => 'nullable|boolean',
            'locked'              => 'nullable|boolean',
            // WIKI : intro facultative + édition collaborative (le défaut « allow_student_edit »
            // = true est appliqué au build quand la clé est absente).
            'wiki_intro'          => 'nullable|string|max:2000',
            'allow_student_edit'  => 'nullable|boolean',
            // F20 - BASE DE DONNÉES : intro facultative + réglages booléens (les défauts
            // « allow_student_add » = true et « require_approval » = false sont appliqués au
            // build quand la clé est absente). Le schéma (champs) est synchronisé à part.
            'database_intro'      => 'nullable|string|max:2000',
            'allow_student_add'   => 'nullable|boolean',
            'require_approval'    => 'nullable|boolean',
            // F21 - ATELIER : intro facultative + nb d'évaluations par pair (1..REVIEWS_MAX)
            // + anonymat (les défauts reviews_per_student=2 et anonymous=true sont appliqués
            // au build quand la clé est absente). La grille (critères) est synchronisée à
            // part ; la phase est validée côté contrôleur, jamais ici.
            'workshop_intro'      => 'nullable|string|max:2000',
            'reviews_per_student' => 'nullable|integer|min:1|max:'.\Modules\Academy\Services\WorkshopService::REVIEWS_MAX,
            'workshop_anonymous'  => 'nullable|boolean',
            'workshop_phase'      => ['nullable', Rule::in(\Modules\Academy\Services\WorkshopService::PHASES)],
        ];

        // FEEDBACK : un questionnaire EXIGE AU MOINS UNE question valide. On normalise les
        // questions (types/énoncés/options/échelles) AVANT validation pour que « min:1 »
        // porte sur le tableau réel des questions retenues (clé d'erreur : feedback_questions).
        if (($input['type'] ?? null) === 'feedback') {
            $input['feedback_questions'] = \Modules\Academy\Services\FeedbackService::normalizeQuestions(
                $input['feedback_questions'] ?? []
            );
            $rules['feedback_questions'] = 'required|array|min:1';
        }

        // CHOICE : un sondage EXIGE un énoncé et AU MOINS 2 options. On parse les options
        // (chaîne multiligne ou tableau) AVANT validation pour que la règle « min:2 »
        // porte sur le tableau réel (clé d'erreur lisible : choice_options).
        if (($input['type'] ?? null) === 'choice') {
            $input['choice_options']    = $this->parseChoiceOptions($input);
            $rules['choice_question']   = 'required|string|max:1000';
            $rules['choice_options']    = 'required|array|min:2|max:'.\Modules\Academy\Services\ChoiceService::MAX_OPTIONS;
            $rules['choice_options.*']  = 'required|string|max:255';
        }

        return validator($input, $rules)->validate();
    }

    /**
     * Parse les options d'un sondage depuis l'entrée brute : soit un tableau (déjà
     * structuré), soit une chaîne MULTILIGNE (une option par ligne). Trim, retrait des
     * lignes vides, dédoublonnage des libellés identiques, réindexation, cap au maximum.
     *
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    private function parseChoiceOptions(array $input): array
    {
        $raw = $input['choice_options'] ?? ($input['options'] ?? []);

        if (is_string($raw)) {
            $raw = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        }

        $clean = [];
        foreach ((array) $raw as $label) {
            if (! is_string($label)) {
                continue;
            }
            $label = trim($label);
            if ($label !== '' && ! in_array($label, $clean, true)) {
                $clean[] = $label;
            }
        }

        return array_slice($clean, 0, \Modules\Academy\Services\ChoiceService::MAX_OPTIONS);
    }

    /**
     * Construit le payload (array casté) propre à chaque type, de façon défensive.
     *
     *  - video    : champ CANONIQUE « player_url » (l'URL d'intégration ScreenPal lue
     *               par le lecteur public, cf. public/lesson.blade.php). On conserve
     *               external_ref (colonne) et on accepte un repli historique payload.embed
     *               si player_url est absent (rétrocompatibilité des anciens items).
     *               Ajoute « poster » (URL) et « duration_seconds » (dérivé des minutes).
     *  - document : texte riche / markdown simple (payload.rich_text).
     *  - quiz     : clé de banque QT (qt_bank_key) + passing_score + attempts_allowed,
     *               consommés côté serveur par QuizController (seuil + limite de tentatives).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildItemPayload(string $type, array $input): array
    {
        $payload = match ($type) {
            'video'    => $this->buildVideoPayload($input),
            'document' => ['rich_text' => (string) ($input['rich_text'] ?? '')],
            'quiz'     => $this->buildQuizPayload($input),
            'choice'   => $this->buildChoicePayload($input),
            'feedback' => $this->buildFeedbackPayload($input),
            'forum'    => $this->buildForumPayload($input),
            'wiki'     => $this->buildWikiPayload($input),
            'database' => $this->buildDatabasePayload($input),
            'workshop' => $this->buildWorkshopPayload($input),
            'h5p'      => $this->buildH5pPayload($input),
            default    => [],
        };

        // V2-c : CRITÈRE D'ACHÈVEMENT. On n'écrit la clé QUE si le critère choisi est
        // VALIDE pour le type ET DIFFÉRENT du défaut du type (rétrocompat : un item sans
        // clé → ActivityCompletionService::criterionFor applique le défaut historique).
        // min_grade posé sur un non-quiz est ignoré (normalizeForStorage → null).
        $completion = \Modules\Academy\Services\ActivityCompletionService::normalizeForStorage(
            $type,
            $input['completion'] ?? null
        );
        if ($completion !== null) {
            $payload['completion'] = $completion;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildVideoPayload(array $input): array
    {
        // Champ canonique : player_url. Repli historique : external_ref puis payload.embed.
        $playerUrl = trim((string) ($input['player_url'] ?? ''));
        if ($playerUrl === '') {
            $playerUrl = trim((string) ($input['external_ref'] ?? ''));
        }
        if ($playerUrl === '') {
            $playerUrl = trim((string) ($input['embed'] ?? ''));
        }

        $poster   = trim((string) ($input['poster_url'] ?? ''));
        $minutes  = $input['duration_minutes'] ?? null;
        $duration = ($minutes !== null && $minutes !== '' && (int) $minutes > 0)
            ? (int) $minutes * 60
            : null;

        $payload = ['player_url' => $playerUrl !== '' ? $playerUrl : null];
        if ($poster !== '') {
            $payload['poster'] = $poster;
        }
        if ($duration !== null) {
            $payload['duration_seconds'] = $duration;
        }

        return $payload;
    }

    /**
     * CHOICE : construit le payload d'un sondage. Énoncé + options (>= 2, parsées),
     * allow_multiple + anonymous (booléens, défaut false), results_visibility (liste
     * blanche, défaut after_vote). Les options et l'énoncé sont stockés bruts ;
     * l'échappement anti-XSS est fait AU RENDU (e() / renderRichText).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildChoicePayload(array $input): array
    {
        $visibility = $input['results_visibility'] ?? null;
        if (! in_array($visibility, \Modules\Academy\Services\ChoiceService::VISIBILITIES, true)) {
            $visibility = \Modules\Academy\Services\ChoiceService::DEFAULT_VISIBILITY;
        }

        return [
            'question'           => trim((string) ($input['choice_question'] ?? '')),
            'options'            => $this->parseChoiceOptions($input),
            'allow_multiple'     => $this->truthy($input['allow_multiple'] ?? null),
            'anonymous'          => $this->truthy($input['anonymous'] ?? null),
            'results_visibility' => $visibility,
        ];
    }

    /**
     * FEEDBACK : construit le payload d'un questionnaire de rétroaction. Intro
     * facultative, questions NORMALISÉES (>= 1 ; types/énoncés/options/échelles validés
     * par FeedbackService), anonyme (booléen, défaut false). Énoncés et options sont
     * stockés bruts ; l'échappement anti-XSS est fait AU RENDU (e()).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildFeedbackPayload(array $input): array
    {
        return [
            'intro'     => trim((string) ($input['feedback_intro'] ?? '')),
            'questions' => \Modules\Academy\Services\FeedbackService::normalizeQuestions(
                $input['feedback_questions'] ?? []
            ),
            'anonymous' => $this->truthy($input['anonymous'] ?? null),
        ];
    }

    /**
     * FORUM : construit le payload d'un forum de discussion. Intro facultative,
     * allow_student_topics (booléen, DÉFAUT true → stocké true quand la clé est absente
     * pour préserver le défaut « les étudiants peuvent ouvrir des sujets »), locked
     * (booléen, défaut false). Le contenu des sujets/réponses n'est PAS stocké ici
     * (tables dédiées) ; l'échappement anti-XSS est fait au rendu (renderRichText).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildForumPayload(array $input): array
    {
        $allow = $input['allow_student_topics'] ?? null;

        return [
            'intro'                => trim((string) ($input['forum_intro'] ?? '')),
            'allow_student_topics' => $allow === null ? true : $this->truthy($allow),
            'locked'               => $this->truthy($input['locked'] ?? null),
        ];
    }

    /**
     * F19 - WIKI : intro facultative + édition collaborative (allow_student_edit, défaut
     * true quand la clé est absente). Aucune nouvelle colonne (payload), comme le forum.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildWikiPayload(array $input): array
    {
        $allow = $input['allow_student_edit'] ?? null;

        return [
            'intro'              => trim((string) ($input['wiki_intro'] ?? '')),
            'allow_student_edit' => $allow === null ? true : $this->truthy($allow),
        ];
    }

    /**
     * F20 - BASE DE DONNÉES : intro facultative + allow_student_add (DÉFAUT true quand la
     * clé est absente => les inscrits peuvent ajouter une fiche) + require_approval (défaut
     * false). Aucune nouvelle colonne (payload). Le SCHÉMA (champs) vit dans une table
     * dédiée et est synchronisé à part (DatabaseService::syncFields), pas dans ce payload.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildDatabasePayload(array $input): array
    {
        $allowAdd = $input['allow_student_add'] ?? null;

        return [
            'intro'             => trim((string) ($input['database_intro'] ?? '')),
            'allow_student_add' => $allowAdd === null ? true : $this->truthy($allowAdd),
            'require_approval'  => $this->truthy($input['require_approval'] ?? null),
        ];
    }

    /**
     * F21 - ATELIER : intro facultative + reviews_per_student (DÉFAUT 2, borné 1..REVIEWS_MAX)
     * + anonymous (DÉFAUT true) + phase (préservée ; défaut « submission » via le service quand
     * absente). Aucune nouvelle colonne (payload). La GRILLE (critères) vit dans une table
     * dédiée et est synchronisée à part (WorkshopService::syncCriteria), pas dans ce payload.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildWorkshopPayload(array $input): array
    {
        $anon    = $input['workshop_anonymous'] ?? null;
        $reviews = $input['reviews_per_student'] ?? null;
        $reviews = ($reviews === '' || $reviews === null)
            ? \Modules\Academy\Services\WorkshopService::REVIEWS_DEFAULT
            : max(1, min(\Modules\Academy\Services\WorkshopService::REVIEWS_MAX, (int) $reviews));

        $payload = [
            'intro'               => trim((string) ($input['workshop_intro'] ?? '')),
            'reviews_per_student' => $reviews,
            'anonymous'           => $anon === null ? true : $this->truthy($anon),
        ];

        // PHASE : on ne l'écrit QUE si une valeur valide est fournie (préservation à l'édition).
        // Absente à la création => le service applique le défaut « submission » (rétrocompat).
        $phase = $input['workshop_phase'] ?? null;
        if (in_array($phase, \Modules\Academy\Services\WorkshopService::PHASES, true)) {
            $payload['phase'] = $phase;
        }

        return $payload;
    }

    /**
     * F16 - H5P : le payload PRÉSERVE le chemin du dossier extrait (h5p_path) et le
     * titre lu dans h5p.json. Le contenu lui-même ne se modifie JAMAIS par ce
     * formulaire générique (il se remplace via replaceH5pPackage) : on se contente
     * de reporter les valeurs existantes injectées par updateItem (rétrocompat).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildH5pPayload(array $input): array
    {
        $payload = [];

        $path = $input['h5p_path'] ?? null;
        if (is_string($path) && $path !== '') {
            $payload['h5p_path'] = $path;
        }

        $title = $input['h5p_title'] ?? null;
        if (is_string($title) && $title !== '') {
            $payload['title'] = $title;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildQuizPayload(array $input): array
    {
        $payload = ['qt_bank_key' => trim((string) ($input['qt_bank_key'] ?? '')) ?: null];

        $passing = $input['passing_score'] ?? null;
        if ($passing !== null && $passing !== '') {
            $payload['passing_score'] = max(0, min(100, (int) $passing));
        }

        $attempts = $input['attempts_allowed'] ?? null;
        if ($attempts !== null && $attempts !== '') {
            $payload['attempts_allowed'] = max(1, (int) $attempts);
        }

        // V1-c : méthode de notation sur tentatives. Liste blanche stricte ; toute
        // valeur absente/inconnue retombe sur le défaut Moodle 'highest' (jamais en
        // dur dans le payload si vide → QuizGradeService::methodFor applique le défaut).
        $method = $input['grading_method'] ?? null;
        if (is_string($method)
            && in_array($method, \Modules\Academy\Services\QuizGradeService::METHODS, true)
        ) {
            $payload['grading_method'] = $method;
        }

        // QB2 : si une catégorie de banque VALIDE (m'appartenant) est choisie, on
        // l'enregistre dans payload['question_bank']. ANTI-IDOR : la catégorie doit
        // figurer dans la liste blanche de MES catégories (re-résolue serveur) ;
        // un id forgé (catégorie d'un autre formateur) est simplement ignoré → on
        // retombe sur le comportement qt_bank_key existant. Aucune nouvelle table.
        $bankCategoryId = $input['bank_category_id'] ?? null;
        if ($bankCategoryId !== null && $bankCategoryId !== '') {
            $bankCategoryId = (int) $bankCategoryId;

            if ($bankCategoryId > 0 && in_array($bankCategoryId, $this->ownedCategoryIds(), true)) {
                $draw = $input['bank_draw_count'] ?? null;
                $drawCount = ($draw !== null && $draw !== '') ? max(1, min(50, (int) $draw)) : 5;

                // QB3 : inclure les sous-catégories (parité Moodle). Défaut true si le
                // champ est absent (rétrocompat des items QB2 déjà liés). Normalise
                // toute valeur du DOM ('1'/'0'/true/false/'on'/'') en booléen.
                $rawInclude   = $input['bank_include_subcategories'] ?? null;
                $includeSubs  = $rawInclude === null
                    ? true
                    : filter_var($rawInclude, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

                $payload['question_bank'] = [
                    'category_id'          => $bankCategoryId,
                    'draw_count'           => $drawCount,
                    'include_subcategories' => $includeSubs,
                ];
            }
        }

        // V1-d — MÉLANGE : 2 booléens. N'écrits QUE si true (un item sans ces clés
        // → pas de mélange = comportement actuel inchangé, rétrocompat stricte).
        if ($this->truthy($input['shuffle_questions'] ?? null)) {
            $payload['shuffle_questions'] = true;
        }
        if ($this->truthy($input['shuffle_answers'] ?? null)) {
            $payload['shuffle_answers'] = true;
        }

        // V1-d — LIMITE DE TEMPS : minutes (1..240). Vide → aucune limite (clé absente).
        $limit = $input['time_limit_minutes'] ?? null;
        if ($limit !== null && $limit !== '') {
            $payload['time_limit_minutes'] = max(1, min(240, (int) $limit));
        }

        // V1-d — OPTIONS DE RÉVISION : on persiste UNIQUEMENT les toggles désactivés
        // (false). Défaut = tout vrai (QuizReviewOptions). Donc un item sans choix
        // explicite → aucune clé → révision complète V1-a (rétrocompat stricte).
        $rawReview = $input['review_options'] ?? null;
        if (is_array($rawReview)) {
            $review = [];
            foreach (\Modules\Academy\Services\QuizReviewOptions::KEYS as $key) {
                if (array_key_exists($key, $rawReview)) {
                    $review[$key] = (bool) (filter_var(
                        $rawReview[$key],
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    ) ?? true);
                }
            }
            if ($review !== []) {
                $payload['review_options'] = $review;
            }
        }

        // V1-f — COMPORTEMENT DE QUESTION : on n'écrit la clé QUE si un mode VALIDE et
        // NON DÉFAUT est choisi (= 'immediate'). Un item sans la clé → 'deferred' via
        // QuizBehaviour (rétrocompat stricte : mode différé actuel inchangé).
        $behaviour = $input['question_behaviour'] ?? null;
        if (is_string($behaviour)
            && in_array($behaviour, \Modules\Academy\Services\QuizBehaviour::BEHAVIOURS, true)
            && $behaviour !== \Modules\Academy\Services\QuizBehaviour::DEFAULT_BEHAVIOUR
        ) {
            $payload['question_behaviour'] = $behaviour;

            // ADAPTATIF : pénalité + nb d'essais : écrits SEULEMENT pour ce mode (les
            // autres modes n'ont pas ces réglages → clés absentes, rétrocompat stricte).
            // La pénalité est saisie en POURCENTAGE (0..100) et convertie en FRACTION
            // (0..1) pour QuizBehaviour::penaltyFor. Vide → clé absente → défauts (1/3, 3).
            if ($behaviour === \Modules\Academy\Services\QuizBehaviour::ADAPTIVE) {
                $penaltyPct = $input['adaptive_penalty'] ?? null;
                if ($penaltyPct !== null && $penaltyPct !== '') {
                    $payload['adaptive_penalty'] = max(0, min(100, (int) $penaltyPct)) / 100;
                }

                $maxTries = $input['adaptive_max_tries'] ?? null;
                if ($maxTries !== null && $maxTries !== '') {
                    $payload['adaptive_max_tries'] = max(1, min(10, (int) $maxTries));
                }
            }
        }

        return $payload;
    }

    /**
     * V1-d : normalise une valeur du DOM ('1'/'0'/true/false/'on'/'' / null) en booléen.
     */
    private function truthy(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return (bool) (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);
    }

    /**
     * Liste blanche des ids de catégories de banque que l'utilisateur courant peut
     * lier (anti-IDOR). Un admin (academy.manage) peut lier n'importe quelle
     * catégorie ; un formateur UNIQUEMENT les siennes (owner_id = auth). Re-résolu
     * serveur à chaque build, jamais une valeur du navigateur.
     *
     * @return array<int, int>
     */
    private function ownedCategoryIds(): array
    {
        $user = Auth::user();

        $query = QuestionCategory::query();
        if (! ($user?->can('academy.manage'))) {
            $query->where('owner_id', $user?->id);
        }

        return $query->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    /**
     * Catégories de banque liables par l'utilisateur (affichage du sélecteur dans le
     * formulaire d'item quiz). Même périmètre owner-scopé que ownedCategoryIds()
     * (sert l'AFFICHAGE ; l'autorisation reste serveur au build du payload).
     *
     * @return \Illuminate\Support\Collection<int, QuestionCategory>
     */
    #[Computed]
    public function bankCategories(): \Illuminate\Support\Collection
    {
        $user = Auth::user();

        $query = QuestionCategory::query()
            ->withCount('children')
            ->orderBy('parent_id')
            ->orderBy('position')
            ->orderBy('name');

        if (! ($user?->can('academy.manage'))) {
            $query->where('owner_id', $user?->id);
        }

        $categories = $query->get();

        // QB3 : compteur RÉEL de questions ACTIVES incluant la descendance (parité
        // Moodle). Affiché « (N questions, sous-catégories incluses) » si la catégorie
        // a des enfants, « (N questions) » sinon. Reste owner-scopé via descendantIds().
        return $categories->each(function (QuestionCategory $cat): void {
            $cat->deep_active_count = $cat->activeQuestionCountDeep();
        });
    }
}
