<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Gestion des tenants')
@section('content')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tenants</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Tenants</h4>
        <a href="{{ route('admin.tenants.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i> Créer un tenant
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if($tenants->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th class="d-none d-md-table-cell">Slug</th>
                            <th class="d-none d-lg-table-cell">Domaine</th>
                            <th class="d-none d-md-table-cell">Propriétaire</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenants as $tenant)
                        <tr>
                            <td class="fw-medium">{{ $tenant->name }}</td>
                            <td class="d-none d-md-table-cell"><code>{{ $tenant->slug }}</code></td>
                            <td class="d-none d-lg-table-cell">
                                @if($tenant->domain)
                                    <span class="text-primary">{{ $tenant->domain }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="d-none d-md-table-cell">{{ $tenant->owner?->name ?? '-' }}</td>
                            <td>
                                @if($tenant->is_active)
                                    <span class="badge bg-success">Actif</span>
                                @else
                                    <span class="badge bg-secondary">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-sm btn-outline-secondary" title="Voir">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                                        <i data-lucide="edit"></i>
                                    </a>
                                    <form action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="if(confirm('Supprimer ce tenant ?')) this.closest('form').submit()">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $tenants->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i data-lucide="building-2" class="icon-xl text-muted mb-3"></i>
                <h5 class="text-muted">Aucun tenant</h5>
                <p class="text-muted mb-4">Créez votre premier tenant pour activer le multi-tenant.</p>
                <a href="{{ route('admin.tenants.create') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i> Créer un tenant
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
