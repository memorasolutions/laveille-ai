@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Zones de livraison')

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Tableau de bord</a></li>
        <li class="breadcrumb-item active" aria-current="page">Zones de livraison</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-title mb-0">Liste des zones</h6>
                    <a href="{{ route('admin.ecommerce.shipping-zones.create') }}" class="btn btn-primary btn-icon-text">
                        <i class="btn-icon-prepend" data-lucide="plus"></i>
                        Ajouter
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Régions</th>
                                <th>Méthodes</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($zones as $zone)
                            <tr>
                                <td>{{ $zone->name }}</td>
                                <td>
                                    @foreach($zone->regions ?? [] as $region)
                                        <span class="badge bg-secondary">{{ $region }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">{{ $zone->methods_count }}</span>
                                </td>
                                <td>
                                    @if($zone->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.ecommerce.shipping-zones.edit', $zone) }}" class="btn btn-sm btn-outline-primary btn-icon" title="Modifier">
                                            <i data-lucide="edit"></i>
                                        </a>
                                        <form action="{{ route('admin.ecommerce.shipping-zones.destroy', $zone) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Supprimer cette zone ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Supprimer">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">Aucune zone de livraison.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $zones->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
