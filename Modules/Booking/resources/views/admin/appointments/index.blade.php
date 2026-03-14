<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Rendez-vous')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Rendez-vous</h4>
    <form method="GET" class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach(['pending' => 'En attente', 'confirmed' => 'Confirmé', 'cancelled' => 'Annulé', 'completed' => 'Terminé'] as $val => $label)
            <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="therapist" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Tous les thérapeutes</option>
            @foreach($therapists as $therapist)
            <option value="{{ $therapist->id }}" {{ request('therapist') == $therapist->id ? 'selected' : '' }}>{{ $therapist->name }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date et heure</th>
                        <th>Service</th>
                        <th>Client</th>
                        <th>Thérapeute</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $appointment)
                    <tr>
                        <td>{{ $appointment->start_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $appointment->service->name }}</td>
                        <td>{{ $appointment->customer->full_name }}</td>
                        <td>{{ $appointment->assignedAdmin?->name ?? '—' }}</td>
                        <td>
                            @php $colors = ['pending'=>'warning','confirmed'=>'success','cancelled'=>'danger','completed'=>'info']; @endphp
                            <span class="badge bg-{{ $colors[$appointment->status] ?? 'secondary' }}">
                                {{ __($appointment->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.booking.appointments.show', $appointment) }}" class="btn btn-sm btn-outline-primary">
                                <i data-lucide="eye" class="icon-sm"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">Aucun rendez-vous.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $appointments->withQueryString()->links() }}
    </div>
</div>
@endsection
