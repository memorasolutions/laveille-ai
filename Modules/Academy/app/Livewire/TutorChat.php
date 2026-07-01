<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Composant Livewire v4 — chat tuteur IA ancré à la leçon courante
 * SELF: < 5 lignes de logique propre dans chaque méthode
 * RAISON: Interface conversationnelle 24/7, gating serveur strict, Loi 25 (pas de PII stocké)
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Services\TutorAccessService;

class TutorChat extends Component
{
    // -------------------------------------------------------------------------
    // Props verrouillées (lecture seule côté client, anti-falsification)
    // -------------------------------------------------------------------------

    #[Locked]
    public Lesson $lesson;

    #[Locked]
    public Course $course;

    // -------------------------------------------------------------------------
    // État mutable
    // -------------------------------------------------------------------------

    public string $question = '';

    /** @var array<int, array{role: string, content: string, error: bool}> */
    public array $messages = [];

    public bool $isLoading = false;

    /**
     * TUTEUR IA — Fenêtre d'accès + quota (drapeau academy.ai_tutor_access_control_enabled,
     * défaut OFF = jamais bloqué). Vrai quand la fenêtre est expirée ou le quota mensuel
     * atteint : le CHAT reste visible (historique conservé), seule la saisie est désactivée.
     */
    public bool $accessBlocked = false;

    // -------------------------------------------------------------------------
    // Cycle de vie
    // -------------------------------------------------------------------------

    public function mount(Lesson $lesson, Course $course): void
    {
        $this->lesson   = $lesson;
        $this->course   = $course;
        $this->messages = [];

        // Reflète l'état d'accès dès l'ouverture (NO-OP si le drapeau est désactivé
        // ou si l'utilisateur n'est pas encore authentifié : gate anti-IDOR inchangée).
        if (auth()->check()) {
            /** @var \App\Models\User $user */
            $user   = auth()->user();
            $access = app(TutorAccessService::class)->isAccessAllowed($user, $course);
            $this->accessBlocked = ! $access['allowed'];
        }
    }

    // -------------------------------------------------------------------------
    // Actions publiques (chacune re-vérifie l'autorisation côté serveur, anti-IDOR)
    // -------------------------------------------------------------------------

    /**
     * Envoie la question au tuteur IA et ajoute la réponse à l'historique de session.
     * Loi 25 : jamais de stockage de l'historique en base (tout en RAM Livewire, session).
     */
    public function sendQuestion(): void
    {
        // ACTION: Gate serveur re-vérifié à chaque action (anti-IDOR)
        if (! $this->isAuthorized()) {
            abort(403);
        }

        $this->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        // TUTEUR IA — Fenêtre d'accès + quota (NO-OP tant que le drapeau
        // academy.ai_tutor_access_control_enabled est désactivé, défaut). Le CHAT
        // reste visible (historique conservé) ; seule la saisie est désactivée
        // côté vue (accessBlocked). Message CALME, jamais de popup native.
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();
        $access      = app(TutorAccessService::class)->isAccessAllowed($currentUser, $this->course);

        if (! $access['allowed']) {
            $this->accessBlocked = true;
            $this->messages[]    = [
                'role'    => 'assistant',
                'content' => $this->accessDeniedMessage($access['reason']),
                'error'   => false,
            ];
            $this->question = '';

            return;
        }

        // Rate-limit : 10 questions par minute par utilisateur
        $key = 'tutor:' . (string) auth()->id();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->messages[] = [
                'role'    => 'assistant',
                'content' => 'Vous avez posé trop de questions en peu de temps. Attendez une minute avant de continuer.',
                'error'   => true,
            ];

            return;
        }

        RateLimiter::hit($key, 60);

        $this->messages[] = [
            'role'    => 'user',
            'content' => $this->question,
            'error'   => false,
        ];

        $this->isLoading = true;

        try {
            $result = app(\Modules\Academy\Services\AcademyTutorService::class)
                ->ask($this->lesson, $this->course, $this->question, $this->buildApiHistory());

            $this->messages[] = [
                'role'    => 'assistant',
                'content' => $result['answer'],
                'error'   => $result['error'],
            ];

            // Quota mensuel : incrémenté UNIQUEMENT sur une question réussie
            // (NO-OP tant que le drapeau est désactivé ou sans grant calculé).
            if (! $result['error']) {
                app(TutorAccessService::class)->incrementUsage($currentUser, $this->course);
            }
        } catch (\Exception) {
            $this->messages[] = [
                'role'    => 'assistant',
                'content' => 'Une erreur est survenue. Veuillez réessayer.',
                'error'   => true,
            ];
        }

        $this->isLoading  = false;
        $this->question   = '';

        // Cap : Loi 25 — limiter la mémoire de session (pas de PII accumulé)
        if (count($this->messages) > 20) {
            $this->messages = array_slice($this->messages, -20);
        }
    }

    /**
     * Vide l'historique de conversation (contrôle utilisateur, Loi 25).
     */
    public function clearHistory(): void
    {
        if (! $this->isAuthorized()) {
            abort(403);
        }

        $this->messages = [];
    }

    // -------------------------------------------------------------------------
    // Rendu
    // -------------------------------------------------------------------------

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('academy::livewire.tutor-chat');
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    /**
     * Construit l'historique au format API (sans la clé 'error').
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function buildApiHistory(): array
    {
        $result = [];

        foreach ($this->messages as $m) {
            if (! ($m['error'] ?? false)) {
                $result[] = ['role' => $m['role'], 'content' => $m['content']];
            }
        }

        return $result;
    }

    /**
     * Message CALME et EXPLICITE affiché quand l'accès au tuteur est refusé.
     * Précise TOUJOURS que seul le tuteur est coupé, jamais le reste du cours.
     */
    private function accessDeniedMessage(string $reason): string
    {
        return match ($reason) {
            TutorAccessService::REASON_EXPIRED => 'Ton accès au tuteur IA pour ce cours est terminé, mais tu gardes accès à tout le reste du cours.',
            TutorAccessService::REASON_QUOTA_EXCEEDED => 'Tu as atteint ton quota de questions ce mois-ci pour ce cours. Le reste du cours reste accessible ; ton quota sera renouvelé au prochain mois.',
            default => 'Le tuteur IA n\'est pas disponible pour le moment, mais le reste du cours reste accessible.',
        };
    }

    /**
     * Vérifie que l'utilisateur courant est autorisé à utiliser le tuteur.
     * SOURCE UNIQUE de la règle : inscrit actif OU superadmin OU rôle sur le cours.
     */
    private function isAuthorized(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Rôle formateur/assistant sur ce cours
        if ($this->course->hasRole($user)) {
            return true;
        }

        // Inscription active
        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $this->course->id)
            ->where('status', 'active')
            ->exists();
    }
}
