<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Services de réservation')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Services de réservation</h4>
    <a href="{{ route('admin.booking.services.create') }}" class="btn btn-primary">
        <i data-lucide="plus" class="icon-sm me-1"></i> Nouveau service
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Durée</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                    <tr>
                        <td>
                            @if($service->color)
                            <span class="d-inline-block rounded-circle me-2" style="width:12px;height:12px;background:{{ $service->color }}"></span>
                            @endif
                            {{ $service->name }}
                        </td>
                        <td>{{ $service->duration_minutes }} min</td>
                        <td>{{ $service->price ? number_format($service->price, 2) . ' $' : '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $service->is_active ? 'success' : 'secondary' }}">
                                {{ $service->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.booking.services.edit', $service) }}" class="btn btn-sm btn-outline-primary">
                                <i data-lucide="edit-2" class="icon-sm"></i>
                            </a>
                            <form action="{{ route('admin.booking.services.destroy', $service) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce service ?')">
                                    <i data-lucide="trash-2" class="icon-sm"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">Aucun service configuré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
