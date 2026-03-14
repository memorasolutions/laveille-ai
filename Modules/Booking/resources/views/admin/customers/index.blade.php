<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Clients')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.booking.appointments.index') }}">Réservations</a></li>
            <li class="breadcrumb-item active" aria-current="page">Clients</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Liste des clients</h5></div>
        <div class="card-body p-0">
            @if($customers->count())
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Réservations</th>
                                <th>Dépenses</th>
                                <th>Dernier RV</th>
                                <th>No-shows</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $customer)
                            <tr>
                                <td>{{ $customer->full_name }}</td>
                                <td>{{ $customer->email }}</td>
                                <td>{{ $customer->phone ?? '-' }}</td>
                                <td>{{ $customer->total_bookings ?? 0 }}</td>
                                <td>{{ number_format((float) ($customer->total_spent ?? 0), 2) }} $</td>
                                <td>{{ $customer->last_booking_at ? $customer->last_booking_at->diffForHumans() : '-' }}</td>
                                <td>
                                    @if(($customer->no_show_count ?? 0) > 0)
                                        <span class="badge bg-danger">{{ $customer->no_show_count }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.booking.customers.show', $customer) }}" class="btn btn-sm btn-outline-primary">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 text-muted">Aucun client trouvé</div>
            @endif
        </div>
        @if($customers->hasPages())
        <div class="card-footer">{{ $customers->links() }}</div>
        @endif
    </div>
</div>
@endsection
