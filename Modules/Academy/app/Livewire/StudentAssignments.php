<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ÉTUDIANT - DEVOIRS (Phase E / E2). Composant Livewire rendu dans l'espace
 * personnel. L'étudiant voit les devoirs PUBLIÉS des cours où il est inscrit
 * ACTIF, soumet/édite SA remise (corps markdown + pièce jointe optionnelle) tant
 * qu'elle n'est pas corrigée, et voit sa note + feedback une fois corrigé.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - Toute mutation exige une session connectée + une INSCRIPTION ACTIVE au cours
 *    du devoir, RE-VÉRIFIÉE serveur à chaque appel (jamais de confiance au client).
 *  - Le devoir est RE-RÉSOLU serveur et doit être PUBLIÉ ET appartenir à un cours
 *    où l'utilisateur est inscrit actif (sinon ModelNotFound → anti-IDOR).
 *  - user_id de la remise = TOUJOURS auth()->id() (firstOrNew scopé au user) :
 *    un étudiant ne peut JAMAIS soumettre ni voir pour un autre.
 *  - Une remise déjà CORRIGÉE (graded_at non null) n'est plus éditable (verrou
 *    serveur), même si l'UI était contournée.
 *  - Le corps markdown est rendu SÛR (anti-XSS) côté affichage ; la pièce jointe
 *    est validée mime+taille SERVEUR, nom non devinable.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Submission;

class StudentAssignments extends Component
{
    use WithFileUploads;

    /** Taille max de la pièce jointe (~10 Mo), validée serveur. */
    private const ATTACHMENT_MAX_KB = 10240;

    /** Devoir en cours de soumission/édition (id), null = aucun formulaire ouvert. */
    public ?int $openAssignment = null;

    public string $body = '';
    /** Pièce jointe en attente (TemporaryUploadedFile) ou null. */
    public $attachment = null;

    public function mount(): void
    {
        abort_unless(Auth::check(), 403);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Résolution + garde serveur (anti-IDOR, inscription active obligatoire)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * IDs des cours où l'utilisateur courant est inscrit ACTIF (source de vérité
     * serveur du droit d'accès aux devoirs). Recalculé à chaque appel.
     *
     * @return Collection<int, int>
     */
    private function activeCourseIds(): Collection
    {
        return Enrollment::where('user_id', Auth::id())
            ->where('status', 'active')
            ->pluck('course_id');
    }

    /**
     * Re-résout un devoir PUBLIÉ d'un cours où l'utilisateur est inscrit ACTIF.
     * Un devoir brouillon, ou d'un cours non suivi → ModelNotFound (anti-IDOR).
     */
    private function resolveAccessibleAssignment(int $assignmentId): Assignment
    {
        return Assignment::where('id', $assignmentId)
            ->where('is_published', true)
            ->whereIn('course_id', $this->activeCourseIds())
            ->firstOrFail();
    }

    /**
     * La remise de l'utilisateur COURANT pour ce devoir (user_id forcé = auth),
     * ou null si aucune. Jamais celle d'un autre étudiant.
     */
    private function ownSubmission(Assignment $assignment): ?Submission
    {
        return Submission::where('assignment_id', $assignment->id)
            ->where('user_id', Auth::id())
            ->first();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Soumettre / éditer SA remise
    // ─────────────────────────────────────────────────────────────────────────────

    /** Ouvre le formulaire de remise d'un devoir accessible (pré-remplit si existante). */
    public function openSubmission(int $assignmentId): void
    {
        $assignment = $this->resolveAccessibleAssignment($assignmentId);
        $existing   = $this->ownSubmission($assignment);

        $this->openAssignment = $assignment->id;
        $this->body           = $existing?->body ?? '';
        $this->attachment     = null;
        $this->resetErrorBag(['body', 'attachment']);
    }

    public function closeSubmission(): void
    {
        $this->reset('openAssignment', 'body', 'attachment');
        $this->resetErrorBag(['body', 'attachment']);
    }

    /**
     * Enregistre/édite la remise de l'utilisateur courant. user_id = auth()->id()
     * (jamais le client). Verrou serveur : une remise déjà CORRIGÉE n'est plus
     * éditable. Pièce jointe optionnelle validée mime+taille serveur.
     */
    public function submit(): void
    {
        $assignment = $this->resolveAccessibleAssignment((int) $this->openAssignment);

        $this->validate([
            'body'       => 'required|string|max:20000',
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:'.self::ATTACHMENT_MAX_KB],
        ]);

        // firstOrNew SCOPÉ à auth()->id() : un étudiant ne crée/édite QUE pour lui.
        $submission = Submission::firstOrNew([
            'assignment_id' => $assignment->id,
            'user_id'       => Auth::id(),
        ]);

        // Verrou : une remise corrigée ne peut plus être modifiée (anti-altération).
        if ($submission->exists && $submission->isGraded()) {
            $this->addError('body', 'Cette remise a déjà été corrigée et ne peut plus être modifiée.');

            return;
        }

        $submission->fill([
            'body'         => $this->body,
            'submitted_at' => now(),
        ])->save();

        // Pièce jointe optionnelle : mime réel relu par Spatie, nom non devinable.
        if ($this->attachment !== null) {
            $submission->addMedia($this->attachment)
                ->usingFileName($this->safeFileName($this->attachment))
                ->toMediaCollection('attachment');
        }

        $this->reset('attachment');
        session()->flash('academy_student_assignments_status', 'Remise enregistrée.');
    }

    /** Retire la pièce jointe de SA remise (re-résolue scopée user, anti-IDOR). */
    public function removeAttachment(int $assignmentId): void
    {
        $assignment = $this->resolveAccessibleAssignment($assignmentId);
        $submission = $this->ownSubmission($assignment);

        if ($submission === null || $submission->isGraded()) {
            return;
        }

        $submission->clearMediaCollection('attachment');
        session()->flash('academy_student_assignments_status', 'Pièce jointe retirée.');
    }

    /** Nom de fichier non devinable, extension restreinte à une liste sûre. */
    private function safeFileName($file): string
    {
        $ext  = strtolower((string) $file->getClientOriginalExtension());
        $safe = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx'], true) ? $ext : 'bin';

        return 'remise-'.Str::random(16).'.'.$safe;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Lecture (affichage) - devoirs accessibles + état de MA remise
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Devoirs PUBLIÉS des cours où l'utilisateur est inscrit ACTIF, chacun
     * accompagné de SA remise (user_id = auth). Aucun devoir d'un cours non suivi,
     * aucun brouillon. Aucune remise d'un autre étudiant n'est exposée.
     *
     * @return Collection<int, array{assignment: Assignment, submission: ?Submission}>
     */
    #[Computed]
    public function items(): Collection
    {
        $courseIds = $this->activeCourseIds();

        if ($courseIds->isEmpty()) {
            return collect();
        }

        $assignments = Assignment::whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->with(['course:id,slug,title', 'lesson:id,title'])
            ->orderBy('course_id')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        // MES remises uniquement (user_id = auth), indexées par devoir.
        $mine = Submission::whereIn('assignment_id', $assignments->pluck('id'))
            ->where('user_id', Auth::id())
            ->get()
            ->keyBy('assignment_id');

        return $assignments->map(fn (Assignment $assignment): array => [
            'assignment' => $assignment,
            'submission' => $mine->get($assignment->id),
        ])->values();
    }

    public function render()
    {
        return view('academy::livewire.student-assignments');
    }
}
