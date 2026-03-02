<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Équipes', 'subtitle' => 'Équipes'])

@section('breadcrumbs')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Équipes</li>
    </ol>
</nav>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">
            <i data-lucide="users" class="me-2"></i>Équipes ({{ $teams->total() }})
        </h5>
        <a href="{{ route('admin.teams.create') }}" class="btn btn-primary btn-sm">
            <i data-lucide="plus" class="me-1"></i> Nouvelle équipe
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="Liste des équipes">
                <thead>
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col" class="d-none d-md-table-cell">Propriétaire</th>
                        <th scope="col" class="text-center d-none d-lg-table-cell" style="width:110px">Membres</th>
                        <th scope="col" class="d-none d-lg-table-cell" style="width:140px">Créée le</th>
                        <th scope="col" class="text-end" style="width:150px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teams as $team)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i data-lucide="users" class="text-muted" style="width:16px;height:16px"></i>
                                <strong>{{ $team->name }}</strong>
                            </div>
                            @if($team->description)
                                <small class="text-muted d-block mt-1">{{ Str::limit($team->description, 80) }}</small>
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="d-flex align-items-center gap-2">
                                <i data-lucide="crown" class="text-warning" style="width:14px;height:14px"></i>
                                <span>{{ $team->owner->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="text-center d-none d-lg-table-cell">
                            <span class="badge bg-primary rounded-pill">{{ $team->members_count }}</span>
                        </td>
                        <td class="text-muted d-none d-lg-table-cell">
                            {{ $team->created_at->format('d/m/Y') }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.teams.show', $team) }}"
                               class="btn btn-sm btn-outline-secondary me-1"
                               title="Voir l'équipe"
                               aria-label="Voir l'équipe {{ $team->name }}">
                                <i data-lucide="eye"></i>
                            </a>
                            <a href="{{ route('admin.teams.edit', $team) }}"
                               class="btn btn-sm btn-outline-primary me-1"
                               title="Modifier l'équipe"
                               aria-label="Modifier l'équipe {{ $team->name }}">
                                <i data-lucide="pencil"></i>
                            </a>
                            <form action="{{ route('admin.teams.destroy', $team) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Supprimer l'équipe"
                                        aria-label="Supprimer l'équipe {{ $team->name }}"
                                        onclick="return confirm('Supprimer l\'équipe « {{ addslashes($team->name) }} » ? Cette action est irréversible.')">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i data-lucide="users" class="mb-2 d-block mx-auto" style="width:32px;height:32px;opacity:.4"></i>
                            Aucune équipe pour le moment.
                            <a href="{{ route('admin.teams.create') }}" class="d-block mt-2">Créer la première équipe</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($teams->hasPages())
        <div class="px-4 py-3">
            {{ $teams->links() }}
        </div>
        @endif
    </div>
</div>

@endsection
