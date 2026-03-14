<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Détail du rendez-vous')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Rendez-vous #{{ $appointment->id }}</h5>
                @php $colors = ['pending'=>'warning','confirmed'=>'success','cancelled'=>'danger','completed'=>'info','pending_approval'=>'secondary','rejected'=>'danger','no_show'=>'dark']; @endphp
                <span class="badge bg-{{ $colors[$appointment->status] ?? 'secondary' }}">{{ __($appointment->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Service</h6>
                        <p>{{ $appointment->service->name }} ({{ $appointment->service->duration_minutes }} min)</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Horaire</h6>
                        <p>{{ $appointment->start_at->format('d/m/Y H:i') }} – {{ $appointment->end_at->format('H:i') }}</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Client</h6>
                        <p>{{ $appointment->customer->full_name }}<br>
                        {{ $appointment->customer->email }}<br>
                        {{ $appointment->customer->phone ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Changer le statut</h6>
                        <form action="{{ route('admin.booking.appointments.update', $appointment) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="input-group">
                                <select name="status" class="form-select form-select-sm">
                                    @foreach(['pending'=>'En attente','confirmed'=>'Confirmé','cancelled'=>'Annulé','completed'=>'Terminé'] as $val => $label)
                                    <option value="{{ $val }}" {{ $appointment->status == $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Appliquer</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Thérapeute assigné</h6>
                        <p>{{ $appointment->assignedAdmin?->name ?? 'Non assigné' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Assigner un thérapeute</h6>
                        <form action="{{ route('admin.booking.appointments.assign', $appointment) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="input-group">
                                <select name="assigned_admin_id" class="form-select form-select-sm">
                                    <option value="">— Aucun —</option>
                                    @foreach($therapists as $t)
                                    <option value="{{ $t->id }}" {{ $appointment->assigned_admin_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Assigner</button>
                            </div>
                        </form>
                    </div>
                </div>
                @if($appointment->notes)
                <div class="mb-3">
                    <h6>Notes</h6>
                    <div class="border rounded p-3 bg-light">{{ $appointment->notes }}</div>
                </div>
                @endif
                @if($appointment->approval_note)
                <div class="mb-3">
                    <h6>Note d'approbation</h6>
                    <div class="border rounded p-3 bg-light">{{ $appointment->approval_note }}</div>
                </div>
                @endif
                @if($appointment->status === 'pending_approval')
                <div class="mb-3 d-flex gap-2">
                    <form action="{{ route('admin.booking.appointments.approve', $appointment) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <input type="text" name="note" class="form-control form-control-sm d-inline-block mb-2" placeholder="Note (optionnel)" style="max-width:300px;">
                        <button type="submit" class="btn btn-sm btn-success"><i data-lucide="check"></i> Approuver</button>
                    </form>
                    <form action="{{ route('admin.booking.appointments.reject', $appointment) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <input type="text" name="reason" class="form-control form-control-sm d-inline-block mb-2" placeholder="Raison du rejet" required style="max-width:300px;">
                        <button type="submit" class="btn btn-sm btn-danger"><i data-lucide="x"></i> Rejeter</button>
                    </form>
                </div>
                @endif
                <a href="{{ route('admin.booking.appointments.index') }}" class="btn btn-secondary">Retour</a>
            </div>
        </div>
    </div>
</div>
@endsection
