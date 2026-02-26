<?php

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Api\Http\Resources\PlanResource;
use Modules\SaaS\Models\Plan;

class PlanApiController extends BaseApiController
{
    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection(Plan::active()->ordered()->get());
    }

    public function show(Plan $plan): PlanResource
    {
        return new PlanResource($plan);
    }
}
