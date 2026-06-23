<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * CORRECTION DES ESSAIS d'un cours (type Moodle « Essay »). Composant Livewire rendu
 * sous l'éditeur de cours, à côté de la correction des devoirs. Liste les tentatives
 * de quiz EN ATTENTE de correction (needs_grading=true) des quiz de CE cours ; pour
 * chaque essai : énoncé + consignes de correction (grader_info) + réponse de
 * l'étudiant, saisie des points (bornés au barème de l'essai) + feedback.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE), calqué sur
 * CourseAssignments :
 *  - $courseId figé au montage (source de vérité serveur) ;
 *  - corriger un essai → 'manageEnrollments' (admin OU owner/instructor du cours),
 *    RÉ-AUTORISÉ à CHAQUE action ;
 *  - la tentative ciblée est RE-RÉSOLUE et SCOPÉE à CE cours (course_id) avant toute
 *    écriture (anti-IDOR) : une tentative d'un autre cours → ModelNotFound, rien écrit ;
 *  - les points sont validés SERVEUR contre [0..barème de l'essai] (lu dans le round
 *    snapshoté, jamais le client) ; graded_by = auth()->id() ;
 *  - la réponse de l'étudiant + grader_info sont rendues anti-XSS (renderRichText) ;
 *  - tout le recalcul (auto + manuel) + la pose de complétion sont EN TRANSACTION.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\EssayGradingService;

class EssayGrading extends Component
{
    /** Identifiant du cours géré (figé au montage, source de vérité serveur). */
    public int $courseId;

    /** Tentative en cours de correction (id), null = aucune. */
    public ?int $gradingAttempt = null;

    /** Points saisis par essai : {index_essai: chaîne du DOM}. */
    public array $essayScores = [];

    /** Feedback saisi par essai : {index_essai: chaîne du DOM}. */
    public array $essayFeedback = [];

    /**
     * Entrée. Pour voir/corriger les essais il faut pouvoir gérer les inscriptions
     * (admin OU owner/instructor du cours). Vraie garde = authorize() serveur.
     */
    public function mount(Course $course): void
    {
        $this->authorize('manageEnrollments', $course);

        $this->courseId = $course->id;
    }

    /** Re-résout TOUJOURS le cours depuis la base (jamais depuis le navigateur). */
    private function resolveCourse(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /**
     * Re-résout une tentative ET vérifie qu'elle appartient à CE cours (anti-IDOR),
     * via la colonne dénormalisée course_id. Une tentative d'un autre cours →
     * ModelNotFound, aucune écriture possible.
     */
    private function resolveAttemptFor(Course $course, int $attemptId): QuizAttempt
    {
        return QuizAttempt::where('id', $attemptId)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }

    private function flashSaved(string $msg): void
    {
        session()->flash('academy_essays_status', $msg);
    }

    /** Ouvre une tentative de CE cours en correction (anti-IDOR), pré-remplit la saisie. */
    public function startGrading(int $attemptId): void
    {
        $course  = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $attempt = $this->resolveAttemptFor($course, $attemptId);

        $this->gradingAttempt = $attempt->id;
        $this->essayScores    = [];
        $this->essayFeedback  = [];

        $manualScores   = is_array($attempt->manual_scores) ? $attempt->manual_scores : [];
        $manualFeedback = is_array($attempt->manual_feedback) ? $attempt->manual_feedback : [];

        foreach (EssayGradingService::essayIndexes($attempt) as $i) {
            $score = $manualScores[$i] ?? ($manualScores[(string) $i] ?? null);
            $fb    = $manualFeedback[$i] ?? ($manualFeedback[(string) $i] ?? null);

            $this->essayScores[$i]   = ($score !== null && $score !== '') ? (string) $score : '';
            $this->essayFeedback[$i] = is_string($fb) ? $fb : '';
        }

        $this->resetErrorBag();
    }

    public function cancelGrading(): void
    {
        $this->reset('gradingAttempt', 'essayScores', 'essayFeedback');
        $this->resetErrorBag();
    }

    /**
     * Enregistre la correction de TOUS les essais d'une tentative de CE cours (anti-IDOR).
     * Chaque note est bornée SERVEUR à [0..barème de l'essai] (lu dans le round snapshoté).
     * Tous les essais doivent être notés en une fois. À l'enregistrement : recalcul de la
     * note (auto + manuel), needs_grading=false, passed/percent/score recalculés, complétion
     * posée si réussi (critère min_grade), graded_at/graded_by. EN TRANSACTION.
     */
    public function gradeAttempt(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        if ($this->gradingAttempt === null) {
            return;
        }

        $attempt = $this->resolveAttemptFor($course, (int) $this->gradingAttempt);

        // C3 : Charger les relations EN AMONT (une seule fois) : la logique ci-dessous lit
        // $attempt->lessonItem (passing_score, critère) et $attempt->user (complétion). Sans
        // ce loadMissing, ce sont 2 requêtes paresseuses dont une DANS la transaction.
        $attempt->loadMissing(['lessonItem', 'user']);

        $indexes = EssayGradingService::essayIndexes($attempt);

        if ($indexes === []) {
            // Tentative sans essai (ne devrait pas arriver via cet écran) → on solde.
            $this->cancelGrading();

            return;
        }

        // Validation SERVEUR par essai : entier dans [0..barème], REQUIS (tous notés).
        $manualScores   = [];
        $manualFeedback = [];
        $hasError       = false;

        foreach ($indexes as $i) {
            $max = EssayGradingService::essayMaxPoints($attempt, $i);
            $raw = trim((string) ($this->essayScores[$i] ?? ''));

            if ($raw === '' || ! ctype_digit($raw)) {
                $this->addError("essayScores.{$i}", "Indiquez une note entière entre 0 et {$max}.");
                $hasError = true;

                continue;
            }

            $value = (int) $raw;
            if ($value < 0 || $value > $max) {
                $this->addError("essayScores.{$i}", "La note doit être comprise entre 0 et {$max}.");
                $hasError = true;

                continue;
            }

            $manualScores[$i] = $value;

            $fb = trim((string) ($this->essayFeedback[$i] ?? ''));
            if (mb_strlen($fb) > 20000) {
                $this->addError("essayFeedback.{$i}", 'Le feedback est trop long (20000 caractères max).');
                $hasError = true;

                continue;
            }
            if ($fb !== '') {
                $manualFeedback[$i] = $fb;
            }
        }

        if ($hasError) {
            return;
        }

        // Recalcul DÉFINITIF (auto + manuel) à partir du round snapshoté.
        $result       = EssayGradingService::recompute($attempt, $manualScores);
        $passingScore = isset($attempt->lessonItem->payload['passing_score'])
            ? (int) $attempt->lessonItem->payload['passing_score']
            : 60;
        $passed = $result['percent'] >= $passingScore;

        // C2 : NB de bonnes réponses COHÉRENT (colonne `score` + score de complétion).
        // Sémantique conservée : `score` = nombre de questions RÉUSSIES (jamais les points
        // pondérés), socle du badge « sans faute » (BadgeService compare score === nb de
        // questions). On y AJOUTE chaque essai noté AU MAXIMUM de son barème (= question
        // réussie). Sans cela, un quiz 100 % essai posait toujours score=0 même corrigé au
        // maximum (incohérence : la complétion valait « 0 bonne réponse »). Un essai noté
        // partiellement n'est pas « sans faute » → non compté (cohérent avec le badge).
        $correctCount = (int) $result['correct'];
        foreach ($indexes as $i) {
            $max = EssayGradingService::essayMaxPoints($attempt, $i);
            if (isset($manualScores[$i]) && (int) $manualScores[$i] >= $max) {
                $correctCount++;
            }
        }

        DB::transaction(function () use ($attempt, $manualScores, $manualFeedback, $result, $passed, $correctCount): void {
            $attempt->update([
                'manual_scores'   => $manualScores,
                'manual_feedback' => $manualFeedback !== [] ? $manualFeedback : null,
                'score'           => $correctCount,
                'percent'         => $result['percent'],
                'passed'          => $passed,
                'needs_grading'   => false,
                'graded_at'       => now(),
                'graded_by'       => auth()->id(),
            ]);

            // Pose la complétion SI réussi et SI le critère de l'item est min_grade
            // (comportement par défaut d'un quiz), exactement comme à la soumission auto.
            // C2 : on transmet le MÊME nombre de bonnes réponses cohérent que la colonne
            // `score` (jamais 0 quand l'essai vaut des points), pour le badge « sans faute ».
            $item = $attempt->lessonItem;
            if ($passed && $item !== null) {
                $criterion = ActivityCompletionService::criterionFor($item);
                if ($criterion === 'min_grade' && $attempt->user !== null) {
                    CompletionService::markComplete($attempt->user, $item, $correctCount);
                }
            }
        });

        $this->cancelGrading();
        $this->flashSaved('Essai corrigé : note enregistrée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Lecture (affichage) - scopée à CE cours, anti-IDOR
    // ─────────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function course(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /** Tentatives EN ATTENTE de correction des quiz de CE cours (course_id scopé). */
    #[Computed]
    public function pendingAttempts(): EloquentCollection
    {
        return QuizAttempt::query()
            ->where('course_id', $this->courseId)
            ->needsGrading()
            ->with(['user:id,name,email', 'lessonItem:id,title,lesson_id'])
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Détails des essais de la tentative en correction (énoncé + consignes + réponse),
     * scopés à CE cours (anti-IDOR d'affichage). Liste vide si aucune correction ouverte.
     * Les textes sont rendus anti-XSS (renderRichText) dans la vue ; ici on transporte
     * le brut + le barème.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function gradingEssays(): array
    {
        if ($this->gradingAttempt === null) {
            return [];
        }

        $attempt = QuizAttempt::where('id', $this->gradingAttempt)
            ->where('course_id', $this->courseId)
            ->first();

        if ($attempt === null) {
            return [];
        }

        $questions = is_array($attempt->questions_snapshot) ? $attempt->questions_snapshot : [];
        $answers   = is_array($attempt->answers) ? $attempt->answers : [];

        $out = [];
        foreach (EssayGradingService::essayIndexes($attempt) as $i) {
            $q   = $questions[$i] ?? ($questions[(string) $i] ?? []);
            $ans = $answers[(string) $i] ?? ($answers[$i] ?? '');

            $out[] = [
                'index'       => $i,
                'prompt'      => (string) ($q['question'] ?? ''),
                'grader_info' => (string) ($q['grader_info'] ?? ''),
                'answer'      => is_string($ans) ? $ans : '',
                'max'         => EssayGradingService::essayMaxPoints($attempt, $i),
            ];
        }

        return $out;
    }

    /** La tentative en correction (objet frais), ou null. */
    #[Computed]
    public function gradedAttempt(): ?QuizAttempt
    {
        if ($this->gradingAttempt === null) {
            return null;
        }

        return QuizAttempt::where('id', $this->gradingAttempt)
            ->where('course_id', $this->courseId)
            ->with('user:id,name,email')
            ->first();
    }

    public function render()
    {
        return view('academy::livewire.essay-grading');
    }
}
