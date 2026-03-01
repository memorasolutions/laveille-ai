<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Modules\SaaS\Models\Plan;

class PlanObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Plan $plan): void
    {
        activity()->performedOn($plan)->log("Plan {$plan->name} créé");
    }

    public function updated(Plan $plan): void
    {
        activity()->performedOn($plan)->withProperties(['changes' => $plan->getChanges()])->log("Plan {$plan->name} modifié");
    }

    public function deleted(Plan $plan): void
    {
        activity()->performedOn($plan)->log("Plan {$plan->name} supprimé");
    }
}
