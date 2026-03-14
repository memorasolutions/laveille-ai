<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Services;

use Illuminate\Database\Eloquent\Collection;
use Laravel\Pennant\Feature;
use Modules\SaaS\Models\Plan;

class BillingService
{
    public function isEnabled(): bool
    {
        return Feature::active('module-saas');
    }

    public function getActivePlans(): Collection
    {
        return Plan::active()->ordered()->get();
    }

    public function getMonthlyPlans(): Collection
    {
        return Plan::active()->monthly()->ordered()->get();
    }

    public function getYearlyPlans(): Collection
    {
        return Plan::active()->yearly()->ordered()->get();
    }

    public function findPlan(int $id): ?Plan
    {
        return Plan::find($id);
    }

    public function findPlanBySlug(string $slug): ?Plan
    {
        return Plan::where('slug', $slug)->first();
    }

    public function createPlan(array $data): Plan
    {
        return Plan::create($data);
    }

    public function updatePlan(Plan $plan, array $data): Plan
    {
        $plan->update($data);

        return $plan->fresh();
    }

    public function deletePlan(Plan $plan): bool
    {
        return (bool) $plan->delete();
    }

    public function getPlansCount(): int
    {
        return Plan::count();
    }

    public function getActivePlansCount(): int
    {
        return Plan::active()->count();
    }
}
