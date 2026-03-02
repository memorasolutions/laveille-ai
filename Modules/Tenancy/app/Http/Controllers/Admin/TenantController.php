<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tenancy\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Team\Models\Team;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Services\TenantService;

class TenantController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function index(): View
    {
        $tenants = Tenant::with('owner')->paginate(15);

        return view('tenancy::admin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        $users = User::all();

        return view('tenancy::admin.tenants.create', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tenants,slug'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ]);

        $this->tenantService->create($validated);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant créé avec succès.');
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load('owner');

        $teams = Team::where('tenant_id', $tenant->id)
            ->withCount('members')
            ->get();

        return view('tenancy::admin.tenants.show', compact('tenant', 'teams'));
    }

    public function edit(Tenant $tenant): View
    {
        $users = User::all();

        return view('tenancy::admin.tenants.edit', compact('tenant', 'users'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($tenant->id)],
            'owner_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ]);

        $this->tenantService->update($tenant, $validated);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant mis à jour avec succès.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->tenantService->delete($tenant);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant supprimé avec succès.');
    }
}
