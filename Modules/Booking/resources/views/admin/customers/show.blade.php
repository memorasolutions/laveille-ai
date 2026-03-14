<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', $customer->full_name)

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.booking.appointments.index') }}">Réservations</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.booking.customers.index') }}">Clients</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $customer->full_name }}</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Informations</h5></div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt>Nom</dt><dd class="mb-2">{{ $customer->full_name }}</dd>
                        <dt>Email</dt><dd class="mb-2">{{ $customer->email }}</dd>
                        <dt>Téléphone</dt><dd class="mb-2">{{ $customer->phone ?? '-' }}</dd>
                        <dt>Fuseau</dt><dd class="mb-2">{{ $customer->timezone ?? 'America/Toronto' }}</dd>
                        <dt>Inscrit</dt><dd class="mb-2">{{ $customer->created_at->format('d/m/Y') }}</dd>
                    </dl>
                    <hr>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="h4 fw-bold">{{ $customer->total_bookings ?? 0 }}</div>
                            <small class="text-muted">RV</small>
                        </div>
                        <div class="col-4">
                            <div class="h4 fw-bold">{{ number_format((float) ($customer->total_spent ?? 0), 2) }} $</div>
                            <small class="text-muted">Dépenses</small>
                        </div>
                        <div class="col-4">
                            <div class="h4 fw-bold {{ ($customer->no_show_count ?? 0) > 0 ? 'text-danger' : '' }}">{{ $customer->no_show_count ?? 0 }}</div>
                            <small class="text-muted">No-shows</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Rendez-vous</h5></div>
                <div class="card-body p-0">
                    @if($appointments->count())
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr><th>Date</th><th>Service</th><th>Statut</th><th>Notes</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($appointments as $appt)
                                    <tr>
                                        <td>{{ $appt->start_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $appt->service->name }}</td>
                                        <td>
                                            @php
                                                $cls = match($appt->status) {
                                                    'confirmed' => 'bg-success', 'pending' => 'bg-warning',
                                                    'cancelled' => 'bg-danger', 'completed' => 'bg-info',
                                                    'no_show' => 'bg-dark', 'rejected' => 'bg-danger',
                                                    'pending_approval' => 'bg-secondary', default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $cls }}">{{ $appt->status }}</span>
                                        </td>
                                        <td>{{ Str::limit($appt->notes, 40) ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">Aucun rendez-vous</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
