<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academy\Http\Controllers\Concerns\AuthorizesAcademyAccess;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\QuestionBankService;
use Modules\Academy\Services\QuizBehaviour;
use Modules\Academy\Services\QuizService;

class QuizController extends Controller
{
    use AuthorizesAcademyAccess;

    /**
     * POST academy.quiz.start
     *
     * Démarre (ou redémarre) une session de quiz pour un item donné.
     * - Auth + inscription vérifiés
     * - Respecte attempts_allowed
     * - Stocke le round en session côté serveur (les questions ne sont jamais exposées en GET)
     */
    public function startQuiz(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): RedirectResponse {
        $item = LessonItem::findOrFail($itemId);
        $this->authorizeAccess($course, $lesson, $item);

        if ($item->type !== 'quiz') {
            abort(404);
        }

        $user = Auth::user();

        // Vérifier le nombre de tentatives
        $attemptsAllowed = isset($item->payload['attempts_allowed'])
            ? (int) $item->payload['attempts_allowed']
            : null;

        if ($attemptsAllowed !== null) {
            // V1-b : la limite s'appuie désormais sur l'HISTORIQUE RÉEL des tentatives
            // (1 ligne QuizAttempt par soumission), et non plus sur le comptage des
            // Completion (upsertées/idempotentes sur (user_id, lesson_item_id), donc
            // approximatif). Comportement identique pour un nouvel item (0 tentative).
            $existingAttempts = QuizAttempt::attemptCount($user->id, $item->id);

            if ($existingAttempts >= $attemptsAllowed) {
                return back()->with('error', 'Nombre de tentatives maximum atteint.');
            }
        }

        // Construire le round.
        // QB2 : si l'item est lié à une catégorie de banque (payload['question_bank']),
        // on tire N questions de CETTE catégorie (+ sous-catégories). La catégorie est
        // RE-RÉSOLUE serveur (findOrFail) ; un round vide (catégorie supprimée/vidée)
        // déclenche le REPLI sur qt_bank_key, puis « Quiz indisponible » si toujours vide.
        // Sans lien de banque → comportement existant qt_bank_key INCHANGÉ.
        $round    = [];
        $bankLink = $item->payload['question_bank'] ?? null;

        if (is_array($bankLink) && ! empty($bankLink['category_id'])) {
            $category = QuestionCategory::find((int) $bankLink['category_id']);

            if ($category !== null) {
                $drawCount = max(1, min(50, (int) ($bankLink['draw_count'] ?? 5)));
                // QB3 : inclure les sous-catégories par défaut (parité Moodle). Si la
                // clé est absente (item QB2 déjà lié), on considère true (rétrocompat).
                $includeSub = (bool) ($bankLink['include_subcategories'] ?? true);
                $round      = QuestionBankService::drawFromCategory($category, $drawCount, $includeSub);
            }
        }

        // Repli (pas de lien de banque OU round vide) : clé QT existante (inchangé).
        if (empty($round)) {
            $bankKey = (string) ($item->payload['qt_bank_key'] ?? 'qt-questions');
            $round   = QuizService::buildRound($bankKey);
        }

        if (empty($round)) {
            return back()->with('error', 'Quiz indisponible pour le moment.');
        }

        // V1-d : MÉLANGE serveur (le client ne choisit jamais l'ordre). Le round
        // mélangé devient la SOURCE DE VÉRITÉ (session + snapshot) : score() et la
        // révision V1-a lisent ce round → le scoring reste exact (l'index `correct`
        // des qcm est remappé par shuffleRound). Absents → false → aucun mélange
        // (rétrocompat stricte).
        $shuffleQuestions = (bool) ($item->payload['shuffle_questions'] ?? false);
        $shuffleAnswers   = (bool) ($item->payload['shuffle_answers'] ?? false);

        if ($shuffleQuestions || $shuffleAnswers) {
            $round = QuizService::shuffleRound($round, $shuffleQuestions, $shuffleAnswers);
        }

        // Stocker le round en session serveur (les questions restent côté serveur)
        $request->session()->put("academy.quiz.{$item->id}", [
            'questions'  => $round,
            'started_at' => now()->toIso8601String(),
        ]);

        // Marquer l'item comme démarré (idempotent)
        CompletionService::markStarted($user, $item);

        return redirect()
            ->route('academy.lessons.show', [$course, $lesson])
            ->withFragment("item-{$item->id}");
    }

    /**
     * POST academy.quiz.submit
     *
     * Soumet les réponses du quiz actif en session.
     * - Si score >= passing_score → marque l'item 'completed'
     * - Flash le résultat pour affichage immédiat
     */
    public function submitQuiz(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): RedirectResponse {
        $item = LessonItem::findOrFail($itemId);
        $this->authorizeAccess($course, $lesson, $item);

        $user       = Auth::user();
        $sessionKey = "academy.quiz.{$item->id}";
        $quizData   = $request->session()->get($sessionKey);

        if (! $quizData) {
            return back()->with('error', 'Session de quiz expirée. Recommencez le quiz.');
        }

        $questions = $quizData['questions'] ?? [];

        // V1-f / ADAPTATIF : en mode PAR QUESTION (immédiat OU adaptatif), les réponses
        // ont été VERROUILLÉES côté serveur (session) au fil des validations par
        // question : on les reconstruit ici (le client n'en soumet aucune au
        // « Terminer »). En mode différé (défaut), on lit les réponses du formulaire
        // comme aujourd'hui (comportement INCHANGÉ).
        if (QuizBehaviour::isPerQuestion($item->payload)) {
            $validated = is_array($quizData['validated'] ?? null) ? $quizData['validated'] : [];
            $answers   = [];
            foreach ($validated as $i => $v) {
                $answers[(string) $i] = is_array($v) ? ($v['answer'] ?? null) : null;
            }

            // ESSAI - en mode immédiat, un essai n'a PAS de bouton « Vérifier » : sa
            // réponse n'est jamais verrouillée en session. Elle est saisie dans le
            // formulaire « Terminer » et fusionnée ICI (uniquement pour les index
            // d'essais du round serveur). Les autres types restent reconstruits depuis
            // la session (le client ne peut pas forger une réponse auto-notée tardive).
            $posted = $request->input('answers', []);
            if (is_array($posted)) {
                foreach ($questions as $qi => $q) {
                    if (is_array($q) && ($q['type'] ?? null) === 'essai') {
                        $raw = $posted[$qi] ?? ($posted[(string) $qi] ?? null);
                        $answers[(string) $qi] = is_string($raw) ? $raw : '';
                    }
                }
            }
        } else {
            $answers = $request->input('answers', []);
        }

        // M1 — BORNE DES RÉPONSES (anti-abus mémoire/forge). Le client ne peut pas
        // soumettre plus de réponses qu'il n'y a de questions dans le round serveur.
        // On normalise d'abord (jamais un scalaire) puis on rejette PROPREMENT (pas
        // de 500) un tableau surnuméraire. Le mode immédiat reconstruit `$answers`
        // depuis la session (toujours <= nb de questions) → garde sans effet pour lui.
        if (! is_array($answers)) {
            $answers = [];
        }

        if (count($answers) > count($questions)) {
            return back()->with('error', 'Réponses invalides. Recommencez le quiz.');
        }

        // C4 : BORNE DE LONGUEUR DES RÉPONSES D'ESSAI (anti-abus mémoire/DoS). Une réponse
        // rédigée ne peut dépasser 50 000 caractères. On lit les index d'essais dans le
        // round SERVEUR (jamais le client) et on rejette PROPREMENT (pas de 500). Les
        // autres types ne sont pas concernés (réponses courtes/numériques déjà bornées).
        foreach ($questions as $qi => $q) {
            if (is_array($q) && ($q['type'] ?? null) === 'essai') {
                $raw = $answers[(string) $qi] ?? ($answers[$qi] ?? null);
                if (is_string($raw) && mb_strlen($raw) > 50000) {
                    return back()->with('error', 'Votre réponse rédigée est trop longue (50 000 caractères maximum).');
                }
            }
        }

        // SCORING SERVEUR : même chemin que le mode différé sur les MÊMES réponses →
        // le score final du mode immédiat est, par construction, identique à celui
        // qu'aurait donné une soumission différée des mêmes réponses.
        $scoreResult = QuizService::score($questions, $answers);

        // ADAPTATIF : application de la pénalité AU SCORE FINAL. score() recalcule la
        // justesse BRUTE (chemin commun, donc `correct` = nb de bonnes réponses pour le
        // badge « sans faute »). On REMPLACE ensuite points_earned / percent par la somme
        // des points PÉNALISÉS figés question par question en session (essais × pénalité,
        // bornés >= 0) : le client n'a jamais envoyé la justesse ni la pénalité, le
        // serveur seul les a comptées. points_possible et correct restent ceux de score().
        if (QuizBehaviour::isAdaptive($item->payload)) {
            $validatedState = is_array($quizData['validated'] ?? null) ? $quizData['validated'] : [];

            $penalizedEarned = 0.0;
            foreach ($validatedState as $v) {
                // Seules les questions VERROUILLÉES portent des points figés (anti-forge :
                // une question non verrouillée ne contribue pas au total).
                if (is_array($v) && ($v['locked'] ?? false)) {
                    $penalizedEarned += (float) ($v['points_earned'] ?? 0);
                }
            }

            $pointsPossible = (int) ($scoreResult['points_possible'] ?? 0);
            $penalizedDisplay = self::normalizePoints($penalizedEarned);
            $scoreResult['points_earned'] = $penalizedDisplay;
            $scoreResult['score']         = $penalizedDisplay;
            $scoreResult['percent']       = $pointsPossible > 0
                ? (int) round(($penalizedEarned / $pointsPossible) * 100)
                : 0;
        }

        $passingScore = isset($item->payload['passing_score'])
            ? (int) $item->payload['passing_score']
            : 60;

        // ESSAI - le round contient au moins un essai → la tentative est EN ATTENTE de
        // correction : le score auto est PROVISOIRE, la complétion n'est PAS posée et la
        // réussite reste indéterminée tant que le formateur n'a pas attribué les points.
        // Un quiz SANS essai → needs_grading=false → comportement auto-noté INCHANGÉ.
        $needsGrading = (bool) ($scoreResult['needs_grading'] ?? false);

        // « passed » provisoire : tant que la correction n'est pas faite, on ne déclare
        // jamais réussi (false). Recalculé définitivement à la correction (EssayGrading).
        $passed = ! $needsGrading && $scoreResult['percent'] >= $passingScore;

        // H1 — limite de tentatives (récupérée comme dans startQuiz). Re-vérifiée
        // DANS la transaction ci-dessous, AVANT la création (anti-TOCTOU : plusieurs
        // onglets soumis en parallèle ne peuvent plus dépasser attempts_allowed).
        $attemptsAllowed = isset($item->payload['attempts_allowed'])
            ? (int) $item->payload['attempts_allowed']
            : null;

        // V1-b : horodatage de début du round (posé par startQuiz en session).
        $startedAt = null;
        if (! empty($quizData['started_at'])) {
            try {
                $startedAt = \Illuminate\Support\Carbon::parse($quizData['started_at']);
            } catch (\Throwable) {
                $startedAt = null;
            }
        }

        // V1-d : GARDE SERVEUR DE LA LIMITE DE TEMPS. Le serveur ne fait JAMAIS
        // confiance au client : il re-calcule l'écoulement depuis started_at (posé
        // serveur) et la limite du payload. Comme Moodle, la soumission tardive est
        // ACCEPTÉE (on ne pénalise pas une fermeture/latence), mais on MARQUE la
        // tentative comme hors-temps (timed_out=true) pour l'analytics/affichage.
        // Tolérance de 10 s (latence réseau, horloge). Sans limite → jamais timed_out.
        $timedOut          = false;
        $timeLimitMinutes  = isset($item->payload['time_limit_minutes'])
            ? (int) $item->payload['time_limit_minutes']
            : null;

        if ($timeLimitMinutes !== null && $timeLimitMinutes > 0 && $startedAt !== null) {
            $elapsedSeconds = now()->diffInSeconds($startedAt, true);
            $allowedSeconds = $timeLimitMinutes * 60 + 10; // tolérance 10 s
            $timedOut       = $elapsedSeconds > $allowedSeconds;
        }

        // Supprimer la session (une tentative = une soumission)
        $request->session()->forget($sessionKey);

        // V1-b : enregistrer l'historique de la tentative ET marquer la complétion
        // (si réussi) dans une SEULE transaction. La complétion reste INCHANGÉE
        // (toujours appelée si réussi) ; la table d'historique démarre vide
        // (rétrocompat des Completion existantes).
        // H1 : `$limitReached` est positionné DANS la transaction si la limite de
        // tentatives est déjà atteinte → aucune tentative créée (idempotence).
        $limitReached = false;

        DB::transaction(function () use ($user, $item, $scoreResult, $passed, $needsGrading, $answers, $questions, $startedAt, $timedOut, $attemptsAllowed, &$limitReached): void {
            // H1 — RE-VÉRIFICATION ANTI-TOCTOU. La limite est relue ICI, dans la même
            // transaction que la création, sur l'HISTORIQUE RÉEL (QuizAttempt). Sans
            // ce garde, N onglets soumis simultanément contournaient le contrôle de
            // startQuiz. Limite atteinte → on n'insère RIEN et on signale au-dessus.
            if ($attemptsAllowed !== null
                && QuizAttempt::attemptCount($user->id, $item->id) >= $attemptsAllowed) {
                $limitReached = true;

                return;
            }

            // V1-c : l'historique conserve le PERCENT pondéré (champ déterminant pour
            // la méthode de notation). max_score reste le NB de questions ; on ne
            // change PAS la sémantique des colonnes existantes (rétrocompat V1-b).
            // V1-d : `timed_out` écrit directement (colonne déployée en prod, migration
            // additive idempotente présente) → plus d'introspection Schema par soumission.
            QuizAttempt::create([
                'user_id'            => $user->id,
                'lesson_item_id'     => $item->id,
                'course_id'          => $this->resolveCourseId($item),
                'score'              => $scoreResult['correct'],
                'max_score'          => $scoreResult['total'],
                'percent'            => $scoreResult['percent'],
                'passed'             => $passed,
                'timed_out'          => $timedOut,
                // ESSAI : marque la tentative à corriger (défaut false → rétrocompat stricte).
                'needs_grading'      => $needsGrading,
                'answers'            => $answers,
                'questions_snapshot' => $questions,
                'started_at'         => $startedAt,
                'submitted_at'       => now(),
            ]);

            // V2-c : la complétion à la SOUMISSION ne s'applique qu'au critère
            // « min_grade » (= défaut historique d'un quiz : réussi → complété). Un
            // quiz dont le gérant a explicitement choisi « view » est complété à la
            // consultation (LessonController), pas ici ; « manual » exigerait un clic.
            // Un quiz SANS critère retombe sur min_grade → comportement INCHANGÉ.
            $criterion = ActivityCompletionService::criterionFor($item);

            if ($passed && $criterion === 'min_grade') {
                // SECURITY/RÉTROCOMPAT : Completion.score = NB de bonnes réponses
                // (= 'correct'), JAMAIS les points pondérés. Le badge « sans faute »
                // (BadgeService::hasPerfectQuiz) compare score === nb de questions :
                // il doit rester correct quel que soit le barème en points.
                CompletionService::markComplete($user, $item, $scoreResult['correct']);
            }
        });

        // H1 — limite déjà atteinte (re-vérifiée en transaction) : aucune tentative
        // n'a été créée. On renvoie le MÊME message que startQuiz (cohérence UX).
        if ($limitReached) {
            return back()->with('error', 'Nombre de tentatives maximum atteint.');
        }

        // V5a-1 : recalcul défensif post-soumission pour les cours à critère min_grade.
        // CompletionService::markComplete est idempotent : si l'item est déjà complété
        // (première réussite antérieure), il retourne sans appeler recalculate(). Une
        // tentative ultérieure améliorant la note du carnet ne déclencherait donc jamais
        // l'émission du certificat. Ce recalcul force la vérification isComplete() sur
        // chaque soumission (lecture seule, aucune duplication grâce à firstOrCreate).
        try {
            if (class_exists(\Modules\Academy\Services\ProgressService::class)) {
                \Modules\Academy\Services\ProgressService::recalculate($user, $course);
            }
        } catch (\Throwable) {
            // Silencieux : ne jamais bloquer la soumission pour un recalcul raté.
        }

        // Gamification - crédit XP sur un quiz RÉUSSI (idempotent, drapeau
        // academy.gamification_enabled OFF par défaut). GamificationService::award()
        // vérifie l'idempotence via la clé (user, quiz_attempt, "quiz_passed") :
        // une resoumission ou un recalcul répété ne peut jamais créditer deux fois.
        try {
            if ($passed && class_exists(\Modules\Academy\Services\GamificationService::class)) {
                $attempt = QuizAttempt::where('user_id', $user->id)
                    ->where('lesson_item_id', $item->id)
                    ->latest('id')
                    ->first();

                if ($attempt !== null) {
                    app(\Modules\Academy\Services\GamificationService::class)
                        ->awardQuizPassed($user, $course, $attempt);
                }
            }
        } catch (\Throwable) {
            // Silencieux : ne jamais bloquer la soumission pour un crédit XP raté.
        }

        // xAPI léger - statement 'attempted'/'quiz' (dette F16, drapeau
        // academy.xapi_enabled OFF par défaut). onceOnly=false : CHAQUE
        // tentative soumise est une action pédagogique distincte à tracer
        // (contrairement à la complétion, qui n'arrive qu'une fois).
        try {
            if (class_exists(\Modules\Academy\Services\XapiRecorderService::class)) {
                $latestAttempt = QuizAttempt::where('user_id', $user->id)
                    ->where('lesson_item_id', $item->id)
                    ->latest('id')
                    ->first();

                if ($latestAttempt !== null) {
                    app(\Modules\Academy\Services\XapiRecorderService::class)->record(
                        $user,
                        \Modules\Academy\Services\XapiRecorderService::VERB_ATTEMPTED,
                        \Modules\Academy\Services\XapiRecorderService::OBJECT_QUIZ,
                        $latestAttempt->id,
                        [
                            'score'   => $latestAttempt->score,
                            'max'     => $latestAttempt->max_score,
                            'percent' => $latestAttempt->percent,
                            'passed'  => $latestAttempt->passed,
                        ],
                        ['course_id' => $course->id, 'lesson_item_id' => $item->id],
                        onceOnly: false,
                    );
                }
            }
        } catch (\Throwable) {
            // Silencieux : ne jamais bloquer la soumission pour un enregistrement xAPI raté.
        }

        // Flasher le résultat (item_id inclus pour affichage ciblé en vue).
        // ESSAI : `needs_grading` → le lecteur affiche « En attente de correction »
        // au lieu d'un score final (le pourcentage auto est provisoire).
        $request->session()->flash('academy.quiz_result', [
            'item_id'         => $item->id,
            'passed'          => $passed,
            'percent'         => $scoreResult['percent'],
            'correct'         => $scoreResult['correct'],
            'total'           => $scoreResult['total'],
            // V1-d : soumission hors-temps (garde serveur).
            'timed_out'       => $timedOut,
            // V1-c : points pondérés pour l'affichage « X / Y points ».
            'points_earned'   => $scoreResult['points_earned'],
            'points_possible' => $scoreResult['points_possible'],
            // ESSAI : tentative en attente de correction manuelle.
            'needs_grading'   => $needsGrading,
        ]);

        return redirect()
            ->route('academy.lessons.show', [$course, $lesson])
            ->withFragment("item-{$item->id}");
    }

    /**
     * POST academy.quiz.verify  (V1-f — rétroaction IMMÉDIATE uniquement)
     *
     * Valide UNE question du round actif en session :
     *  - le scoring de la question est calculé SERVEUR (QuizService::score sur une
     *    tranche d'une question lue dans le round serveur) → le client ne décide
     *    JAMAIS de la justesse, et les bonnes réponses ne sont pas exposées avant ;
     *  - la réponse validée est VERROUILLÉE en session (idempotent : une question
     *    déjà validée ne peut plus changer) ;
     *  - le résultat (justesse + points) est relu par le lecteur pour afficher la
     *    rétroaction immédiate puis verrouiller la question.
     *
     * Sécurité : auth + inscription (authorizeAccess), anti-IDOR (item re-résolu et
     * rattaché à la leçon/cours), round + bonnes réponses jamais côté client. Le mode
     * différé n'expose PAS cette route (abort 404) → comportement actuel intact.
     */
    public function verifyQuestion(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): RedirectResponse {
        $item = LessonItem::findOrFail($itemId);
        $this->authorizeAccess($course, $lesson, $item);

        // V1-f / ADAPTATIF : la validation par question s'applique aux deux modes de
        // rétroaction par question (immédiat ET adaptatif). Le mode différé n'expose
        // PAS cette route (abort 404) → comportement actuel intact.
        if ($item->type !== 'quiz' || ! QuizBehaviour::isPerQuestion($item->payload)) {
            abort(404);
        }

        $sessionKey = "academy.quiz.{$item->id}";
        $quizData   = $request->session()->get($sessionKey);

        if (! $quizData) {
            return back()->with('error', 'Session de quiz expirée. Recommencez le quiz.');
        }

        $questions = $quizData['questions'] ?? [];
        $index     = (int) $request->input('index', -1);

        // L'index doit exister DANS le round serveur (jamais une question forgée).
        if ($index < 0 || ! array_key_exists($index, $questions)) {
            abort(404);
        }

        $validated = is_array($quizData['validated'] ?? null) ? $quizData['validated'] : [];
        $existing  = is_array($validated[$index] ?? null) ? $validated[$index] : null;

        // Idempotent / anti-triche : une question VERROUILLÉE ne peut plus changer. En
        // immédiat, toute entrée est verrouillée ; en adaptatif, seules les entrées
        // marquées `locked` le sont (une entrée « en cours de réessai » reste modifiable).
        $isAdaptive = QuizBehaviour::isAdaptive($item->payload);
        $isLocked   = $existing !== null
            && ($isAdaptive ? (bool) ($existing['locked'] ?? false) : true);

        if ($isLocked) {
            return redirect()
                ->route('academy.lessons.show', [$course, $lesson])
                ->withFragment("item-{$item->id}");
        }

        // SCORING SERVEUR de CETTE question : on score une tranche d'UNE question. La
        // bonne réponse est lue dans le round serveur (le client envoie seulement SA
        // réponse). `score` indexe answers par numéro de question → clé '0' ici.
        $given  = $request->input('answer', null);
        $single = QuizService::score([$questions[$index]], ['0' => $given]);
        $detail = $single['details'][0] ?? ['correct' => false];

        $isCorrect = (bool) ($detail['correct'] ?? false);
        // Crédit partiel : conserve la valeur native de score() (int si entier, float
        // ex. 0,5 si fractionnaire), surtout PAS de cast int qui tronquerait.
        $rawEarned = $single['points_earned'] ?? 0;
        $possible  = (int) ($single['points_possible'] ?? 0);

        if (! $isAdaptive) {
            // ── MODE IMMÉDIAT (inchangé) : une seule validation, verrouillage direct. ──
            $validated[$index] = [
                'answer'          => $given,
                'correct'         => $isCorrect,
                'points_earned'   => $rawEarned,
                'points_possible' => $possible,
            ];
        } else {
            // ── MODE ADAPTATIF : réessai PÉNALISÉ, décompte des essais 100 % SERVEUR. ──
            // FORMULE (parité Moodle « Adaptive mode »), 100 % serveur :
            //   - `n`        = nb d'essais RATÉS (jamais soumis par le client) ;
            //   - multiplier = max(0, 1 - n × pénalité)        (borné >= 0) ;
            //   - points     = multiplier × justesse_brute      (borné >= 0).
            // Correct au n-ième essai (n ratés avant) → multiplier × points pleins.
            // Jamais correct (max essais) → multiplier × MEILLEURE justesse atteinte.
            $penalty  = QuizBehaviour::penaltyFor($item->payload);
            $maxTries = QuizBehaviour::maxTriesFor($item->payload);

            // État accumulé serveur de la question (essais ratés + meilleure justesse).
            $prevTries = (int) ($existing['tries'] ?? 0);
            $bestRaw   = max((float) ($existing['best_raw'] ?? 0.0), (float) $rawEarned);

            if ($isCorrect) {
                // Réussite : on fige avec la pénalité des essais ratés AVANT celui-ci.
                $multiplier = QuizBehaviour::penaltyMultiplier($prevTries, $penalty);
                $earned     = $multiplier * (float) $rawEarned;

                $validated[$index] = [
                    'answer'             => $given,
                    'correct'            => true,
                    'locked'             => true,
                    'tries'              => $prevTries,
                    'penalty_multiplier' => $multiplier,
                    'points_earned'      => self::normalizePoints($earned),
                    'points_possible'    => $possible,
                    'best_raw'           => self::normalizePoints($bestRaw),
                ];
            } else {
                // Échec : un essai raté de plus. BORNE SERVEUR : au max d'essais, la
                // question se VERROUILLE (un client qui spamme « Vérifier » ne dépasse
                // jamais max_tries) ; sinon elle reste ouverte au réessai.
                $newTries = $prevTries + 1;
                $reached  = $newTries >= $maxTries;

                if ($reached) {
                    $multiplier = QuizBehaviour::penaltyMultiplier($newTries, $penalty);
                    $earned     = $multiplier * $bestRaw;

                    $validated[$index] = [
                        'answer'             => $given,
                        'correct'            => false,
                        'locked'             => true,
                        'tries'              => $newTries,
                        'penalty_multiplier' => $multiplier,
                        'points_earned'      => self::normalizePoints($earned),
                        'points_possible'    => $possible,
                        'best_raw'           => self::normalizePoints($bestRaw),
                    ];
                } else {
                    $validated[$index] = [
                        'answer'          => $given,
                        'correct'         => false,
                        'locked'          => false,
                        'tries'           => $newTries,
                        'best_raw'        => self::normalizePoints($bestRaw),
                        'points_possible' => $possible,
                    ];
                }
            }
        }

        $quizData['validated'] = $validated;
        $request->session()->put($sessionKey, $quizData);

        return redirect()
            ->route('academy.lessons.show', [$course, $lesson])
            ->withFragment("item-{$item->id}");
    }

    /**
     * ADAPTATIF : normalise des points (float) en int si la valeur est entière (DRY,
     * même règle d'affichage que QuizService::score : 3.0 → 3, 0,6667 → reste float).
     */
    private static function normalizePoints(float $value): float|int
    {
        $rounded = round($value, 4);

        return $rounded === (float) (int) $rounded ? (int) $rounded : $rounded;
    }

    /**
     * V1-b : résout l'ID du cours d'un item (via item→lesson→chapter), dénormalisé
     * dans academy_quiz_attempts pour scoper l'analytics. Retombe sur 0 si introuvable.
     */
    private function resolveCourseId(LessonItem $item): int
    {
        try {
            $item->loadMissing('lesson.chapter');

            return (int) ($item->lesson->chapter->course_id ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }
}
