<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Exceptions de dates')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Exceptions de dates</h4>
    <a href="{{ route('admin.booking.date-overrides.create') }}" class="btn btn-primary">
        <i data-lucide="plus" class="icon-sm me-1"></i> Nouvelle exception
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Journée</th>
                        <th>Plage horaire</th>
                        <th>Raison</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overrides as $override)
                    <tr>
                        <td>{{ $override->date->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $override->override_type === 'blocked' ? 'danger' : 'success' }}">
                                {{ $override->override_type === 'blocked' ? 'Bloqué' : 'Disponible' }}
                            </span>
                        </td>
                        <td>{{ $override->all_day ? 'Oui' : 'Non' }}</td>
                        <td>{{ !$override->all_day ? $override->start_time . ' – ' . $override->end_time : '—' }}</td>
                        <td>{{ Str::limit($override->reason, 40) }}</td>
                        <td>
                            <a href="{{ route('admin.booking.date-overrides.edit', $override) }}" class="btn btn-sm btn-outline-primary" aria-label="Modifier">
                                <i data-lucide="edit-2" class="icon-sm"></i>
                            </a>
                            <form action="{{ route('admin.booking.date-overrides.destroy', $override) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ?')" aria-label="Supprimer">
                                    <i data-lucide="trash-2" class="icon-sm"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">Aucune exception.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $overrides->links() }}
    </div>
</div>
@endsection
