@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Gestion des menus')
@section('content')
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Menus</h4>
        <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i> Créer un menu
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if($menus->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Emplacement</th>
                            <th>Éléments</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($menus as $menu)
                        <tr>
                            <td class="fw-medium">{{ $menu->name }}</td>
                            <td>{{ $locations[$menu->location] ?? $menu->location ?? '—' }}</td>
                            <td>{{ $menu->allItems()->count() }}</td>
                            <td>
                                @if($menu->is_active)
                                <span class="badge bg-success">Actif</span>
                                @else
                                <span class="badge bg-secondary">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                                    <i data-lucide="edit"></i>
                                </a>
                                <form action="{{ route('admin.menus.destroy', $menu) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce menu ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i data-lucide="list" class="icon-xl text-muted mb-3"></i>
                <h5 class="text-muted">Aucun menu</h5>
                <p class="text-muted mb-4">Créez votre premier menu pour gérer la navigation du site.</p>
                <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i> Créer un menu
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
