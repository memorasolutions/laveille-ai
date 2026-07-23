<?php

declare(strict_types=1);

namespace Modules\Directory\Observers;

use Modules\Directory\Models\Tool;
use Modules\Directory\Services\EcosystemCountService;
use Modules\Directory\Services\ScreenshotService;

class ToolObserver
{
    public function saved(Tool $tool): void
    {
        if (
            $tool->wasChanged('status')
            && $tool->status === 'published'
            && empty($tool->screenshot_locked)
            && (empty($tool->screenshot) || str_starts_with((string) $tool->screenshot, 'http'))
            && ScreenshotService::isAvailable()
        ) {
            (new ScreenshotService)->captureWithRetry($tool);
        }

        // Invalidation ecosystem_tag sur update réel uniquement (wasChanged() fiable ici, cf.
        // updated() ci-dessous pour la création — jamais wasRecentlyCreated dans saved() :
        // cette propriété reste `true` pour toute la durée de vie de l'instance PHP après un
        // insert, même lors d'un save() ultérieur sans rapport, ce qui invaliderait le cache
        // à chaque fois qu'un job/script garde le même objet Tool en mémoire).
        if ($tool->wasChanged(['ecosystem_tag', 'status'])) {
            EcosystemCountService::flushCache();
        }
    }

    public function created(Tool $tool): void
    {
        // wasChanged()/getChanges() sont TOUJOURS vides sur une création (Eloquent n'appelle
        // syncChanges() que dans performUpdate(), jamais dans performInsert() — vérifié sur ce
        // projet le 2026-07-23, cf. vendor/laravel/framework/.../Model.php). L'event created()
        // ne fire qu'une seule fois, exactement à l'insertion : c'est le bon endroit pour
        // invalider sans dépendre de wasChanged() ni du flag instable wasRecentlyCreated.
        EcosystemCountService::flushCache();
    }

    public function deleted(Tool $tool): void
    {
        if ($tool->ecosystem_tag !== null) {
            EcosystemCountService::flushCache();
        }
    }
}
