<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', $tenant->name)
@section('content')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.tenants.index') }}">Tenants</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $tenant->name }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">{{ $tenant->name }}</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.tenants.index') }}" class="btn btn-outline-secondary">
                <i data-lucide="arrow-left"></i> Retour
            </a>
            <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn btn-primary">
                <i data-lucide="edit"></i> Modifier
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Informations</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Nom</span>
                            <span class="fw-medium">{{ $tenant->name }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Slug</span>
                            <code>{{ $tenant->slug }}</code>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Domaine</span>
                            <span>{{ $tenant->domain ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Propriétaire</span>
                            <span>{{ $tenant->owner?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Statut</span>
                            @if($tenant->is_active)
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Inactif</span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Créé le</span>
                            <span>{{ $tenant->created_at->format('d/m/Y H:i') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            @if($tenant->settings)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">Paramètres</h6>
                </div>
                <div class="card-body">
                    <pre class="mb-0 small"><code>{{ json_encode($tenant->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">Équipes associées</h6>
                    <span class="badge bg-primary rounded-pill">{{ $teams->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @if($teams->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th class="text-center">Membres</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teams as $team)
                                <tr>
                                    <td class="fw-medium">{{ $team->name }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $team->members_count }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4 text-muted">
                        <i data-lucide="users" class="icon-lg mb-2"></i>
                        <p class="mb-0">Aucune équipe associée</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
