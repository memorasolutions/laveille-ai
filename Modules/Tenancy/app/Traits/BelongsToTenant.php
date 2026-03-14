<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tenancy\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Services\TenantService;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            /** @var TenantService $service */
            $service = app(TenantService::class);
            $currentTenant = $service->getCurrent();

            if ($currentTenant) {
                $builder->where($builder->getModel()->qualifyColumn('tenant_id'), $currentTenant->getKey());
            }
        });

        static::creating(function (Model $model) {
            if (isset($model->tenant_id)) {
                return;
            }

            /** @var TenantService $service */
            $service = app(TenantService::class);
            $currentTenant = $service->getCurrent();

            if ($currentTenant) {
                $model->tenant_id = $currentTenant->getKey();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeWithoutTenancy(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    public function scopeForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->withoutGlobalScope('tenant')
            ->where($this->qualifyColumn('tenant_id'), $tenant->getKey());
    }
}
