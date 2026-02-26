<?php

declare(strict_types=1);

namespace Modules\Tenancy\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Pennant\Feature;
use Modules\Tenancy\Models\Tenant;

class TenantService
{
    private ?Tenant $currentTenant = null;

    public function isEnabled(): bool
    {
        return Feature::active('module-tenancy');
    }

    public function getCurrent(): ?Tenant
    {
        return $this->currentTenant;
    }

    public function switchTo(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    public function clear(): void
    {
        $this->currentTenant = null;
    }

    public function getAll(): Collection
    {
        return Tenant::all();
    }

    public function getActive(): Collection
    {
        return Tenant::active()->get();
    }

    public function findById(int $id): ?Tenant
    {
        return Tenant::find($id);
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::where('slug', $slug)->first();
    }

    public function findByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)->first();
    }

    public function getForUser(User $user): Collection
    {
        return Tenant::where('owner_id', $user->id)->get();
    }

    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant->fresh();
    }

    public function delete(Tenant $tenant): bool
    {
        return (bool) $tenant->delete();
    }

    public function getCount(): int
    {
        return Tenant::count();
    }
}
