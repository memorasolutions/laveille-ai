<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Forfaits')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Forfaits</h4>
    <a href="{{ route('admin.booking.packages.create') }}" class="btn btn-primary">
        <i data-lucide="plus" class="icon-sm me-1"></i> Nouveau forfait
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Séances</th>
                        <th>Prix</th>
                        <th>Prix régulier</th>
                        <th>Validité</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                    <tr>
                        <td>{{ $package->name }}</td>
                        <td>{{ $package->session_count }}</td>
                        <td>{{ number_format($package->price, 2) }} $</td>
                        <td>{{ $package->regular_price ? number_format($package->regular_price, 2) . ' $' : '—' }}</td>
                        <td>{{ $package->validity_days }} jours</td>
                        <td>
                            <span class="badge bg-{{ $package->is_active ? 'success' : 'secondary' }}">
                                {{ $package->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.booking.packages.edit', $package) }}" class="btn btn-sm btn-outline-primary">
                                <i data-lucide="edit-2" class="icon-sm"></i>
                            </a>
                            <form action="{{ route('admin.booking.packages.destroy', $package) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce forfait ?')">
                                    <i data-lucide="trash-2" class="icon-sm"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">Aucun forfait configuré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
