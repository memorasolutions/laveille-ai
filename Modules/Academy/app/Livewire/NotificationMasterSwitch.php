<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * D02 - Interrupteur MAÎTRE des notifications courriel de l'Académie, pilotable
 * depuis l'admin (sans toucher au .env). La source de vérité reste le service
 * (AcademyNotificationService) : ce composant ne fait que lire/écrire le réglage
 * « academy_notifications_enabled » (table settings) via le service.
 *
 * GATING : la classe est entièrement réservée à `can('academy.manage')` (admin /
 * gestionnaire). L'autorisation est RÉ-VÉRIFIÉE à chaque action (anti-IDOR : une
 * action Livewire publique contourne le middleware de route).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Academy\Services\AcademyNotificationService;

class NotificationMasterSwitch extends Component
{
    /** Vrai si l'interrupteur maître est actuellement activé sur la plateforme. */
    public bool $enabled = false;

    /** Vrai si la persistance admin est disponible (module Settings présent). */
    public bool $persistable = true;

    private const PERMISSION = 'academy.manage';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can(self::PERMISSION), 403);

        $service           = app(AcademyNotificationService::class);
        $this->enabled     = $service->isMasterEnabled();
        $this->persistable = class_exists(\Modules\Settings\Models\Setting::class);
    }

    /** Bascule l'interrupteur maître (autorisation re-vérifiée côté serveur). */
    public function toggle(): void
    {
        abort_unless(auth()->check() && auth()->user()->can(self::PERMISSION), 403);

        $service = app(AcademyNotificationService::class);

        // SÉCURITÉ : on relit l'état RÉEL depuis le service (jamais $this->enabled,
        // qui provient du navigateur), puis on inverse.
        $new = ! $service->isMasterEnabled();

        if (! $service->setMasterEnabled($new)) {
            $this->persistable = false;
            session()->flash(
                'academy_master_status',
                "Le module Réglages est absent : l'interrupteur reste piloté par la variable .env « ACADEMY_NOTIFICATIONS_ENABLED »."
            );

            return;
        }

        $this->enabled = $new;

        session()->flash(
            'academy_master_status',
            $new
                ? 'Notifications courriel ACTIVÉES sur la plateforme.'
                : 'Notifications courriel DÉSACTIVÉES sur la plateforme.'
        );
    }

    public function render(): View
    {
        return view('academy::livewire.notification-master-switch');
    }
}
