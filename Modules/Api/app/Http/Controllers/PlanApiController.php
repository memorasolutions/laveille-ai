<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Api\Http\Resources\PlanResource;
use Modules\SaaS\Models\Plan;

/**
 * @group Plans
 *
 * Public endpoints for browsing available subscription plans.
 */
class PlanApiController extends BaseApiController
{
    /**
     * Return all active plans in display order.
     *
     * @unauthenticated
     */
    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection(Plan::active()->ordered()->get());
    }

    /**
     * Return the details of a single plan.
     *
     * @unauthenticated
     */
    public function show(Plan $plan): PlanResource
    {
        return new PlanResource($plan);
    }
}
