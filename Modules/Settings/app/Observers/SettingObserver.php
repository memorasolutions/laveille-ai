<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Settings\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Modules\Settings\Models\Setting;

class SettingObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Setting $setting): void
    {
        activity()->performedOn($setting)->log("Paramètre {$setting->key} créé");
    }

    public function updated(Setting $setting): void
    {
        activity()->performedOn($setting)->withProperties(['changes' => $setting->getChanges()])->log("Paramètre {$setting->key} modifié");
    }

    public function deleted(Setting $setting): void
    {
        activity()->performedOn($setting)->log("Paramètre {$setting->key} supprimé");
    }
}
