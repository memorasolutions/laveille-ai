<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V5-c - Réglages des notifications courriel de l'étudiant (opt-in/opt-out par type).
 * Surface gatée auth ; chaque mutation s'applique UNIQUEMENT à auth()->user()
 * (jamais un id du client). Conformité Loi 25 / LCAP : l'utilisateur contrôle ce
 * qu'il reçoit, et le lien vers cette page figure dans chaque courriel.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Academy\Services\AcademyNotificationService;

class NotificationPreferences extends Component
{
    /**
     * Préférences effectives par type (bool), liées aux interrupteurs de la vue.
     *
     * @var array<string, bool>
     */
    public array $prefs = [];

    /** Libellés et descriptions FR des types (affichage). */
    public array $labels = [];

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);

        $service = app(AcademyNotificationService::class);
        $this->prefs = $service->preferencesFor(auth()->user());

        $this->labels = [
            AcademyNotificationService::TYPE_ANNOUNCEMENT => [
                'titre' => 'Annonces de cours',
                'desc'  => 'Être prévenu quand un formateur publie une annonce dans un cours suivi.',
            ],
            AcademyNotificationService::TYPE_FORUM_REPLY => [
                'titre' => 'Réponses dans le forum',
                'desc'  => 'Être prévenu quand quelqu\'un répond à un sujet que vous avez ouvert.',
            ],
            AcademyNotificationService::TYPE_GRADED => [
                'titre' => 'Travaux corrigés',
                'desc'  => 'Recevoir un courriel quand un devoir ou un essai est corrigé.',
            ],
            AcademyNotificationService::TYPE_COURSE_COMPLETED => [
                'titre' => 'Cours complété',
                'desc'  => 'Recevoir la confirmation et le certificat à la fin d\'un cours.',
            ],
            AcademyNotificationService::TYPE_DUE_REMINDER => [
                'titre' => 'Rappels d\'échéance',
                'desc'  => 'Recevoir un rappel avant l\'échéance d\'un devoir ou d\'un événement.',
            ],
        ];
    }

    /** Bascule un type pour l'utilisateur connecté (toujours auth, jamais un id client). */
    public function toggle(string $type): void
    {
        abort_unless(auth()->check(), 403);

        if (! in_array($type, AcademyNotificationService::TYPES, true)) {
            return;
        }

        $new = ! ($this->prefs[$type] ?? true);
        $this->prefs[$type] = $new;

        app(AcademyNotificationService::class)->setPreference(auth()->user(), $type, $new);

        session()->flash('academy_notif_prefs_status', 'Préférences enregistrées.');
    }

    public function render(): View
    {
        return view('academy::livewire.notification-preferences');
    }
}
