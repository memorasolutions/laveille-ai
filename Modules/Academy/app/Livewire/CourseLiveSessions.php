<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Séances en direct - VUE APPRENANT (inscrit actif du cours uniquement).
 * Rendu sur la page publique du cours pour un inscrit.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR, anti-IDOR) :
 *  - Le drapeau academy.live_sessions_enabled est re-vérifié à l'entrée ET dans
 *    chaque action (une action Livewire est un endpoint public : elle contourne
 *    le middleware de la route -> re-vérif systématique).
 *  - L'accès est réservé aux INSCRITS ACTIFS du cours (le staff en preview est
 *    accepté en LECTURE mais n'enregistre jamais de présence). Un non-inscrit ne
 *    voit aucune séance et ne peut pas joindre.
 *  - Au clic « Rejoindre », la présence est enregistrée de façon IDEMPOTENTE
 *    (firstOrCreate sur la clé unique (session,user)), après re-vérification que
 *    la séance appartient bien à CE cours (anti-IDOR).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\LiveSession;
use Modules\Academy\Models\LiveSessionAttendance;

class CourseLiveSessions extends Component
{
    #[Locked]
    public Course $course;

    public function mount(Course $course): void
    {
        abort_unless($this->featureEnabled(), 404);
        // Accès réservé aux inscrits actifs (ou au staff, lecture seule).
        abort_unless($this->canAccess(), 403);
        $this->course = $course;
    }

    /** Drapeau maître de la fonctionnalité (défaut FALSE). */
    private function featureEnabled(): bool
    {
        return (bool) config('academy.live_sessions_enabled', false);
    }

    /** L'utilisateur est-il inscrit actif OU membre du staff de CE cours ? (autorisation SERVEUR) */
    private function canAccess(): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        if ($user->can('academy.manage') || $this->course->hasRole($user, ['owner', 'instructor', 'editor', 'assistant'])) {
            return true;
        }

        return $this->isActiveEnrollee();
    }

    /** L'utilisateur est-il un INSCRIT ACTIF de CE cours ? (seul cas qui enregistre la présence) */
    private function isActiveEnrollee(): bool
    {
        return Enrollment::query()
            ->where('course_id', $this->course->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();
    }

    /** Séances à venir du cours (les plus proches d'abord). */
    #[Computed]
    public function upcoming()
    {
        return LiveSession::query()
            ->where('course_id', $this->course->id)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->get();
    }

    /** Séances passées du cours (les plus récentes d'abord). */
    #[Computed]
    public function past()
    {
        return LiveSession::query()
            ->where('course_id', $this->course->id)
            ->where('starts_at', '<', now())
            ->orderByDesc('starts_at')
            ->limit(20)
            ->get();
    }

    /**
     * Enregistre la présence (IDEMPOTENT) puis renvoie l'URL à ouvrir.
     * Le clic ouvre join_url en nouvel onglet côté client (rel=noopener) ;
     * l'écriture de présence se fait ici, serveur. Ne fait rien pour un non-inscrit.
     */
    public function join(int $sessionId): void
    {
        abort_unless($this->featureEnabled(), 404);

        // Séance re-résolue scopée à CE cours (anti-IDOR).
        $session = LiveSession::query()
            ->whereKey($sessionId)
            ->where('course_id', $this->course->id)
            ->firstOrFail();

        // Seul un INSCRIT ACTIF enregistre sa présence (le staff/preview ne pollue pas les stats).
        if ($this->isActiveEnrollee()) {
            LiveSessionAttendance::firstOrCreate(
                ['live_session_id' => $session->id, 'user_id' => Auth::id()],
                ['joined_at' => now()],
            );
        }

        // Ouverture du lien en nouvel onglet, côté client (rel=noopener géré par la vue).
        $this->dispatch('open-live-session', url: $session->join_url);
    }

    public function render()
    {
        return view('academy::livewire.course-live-sessions');
    }
}
