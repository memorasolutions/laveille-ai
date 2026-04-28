<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('booking::public.layouts.public')

@section('title', 'Mon portail - Rendez-vous')

@section('content')
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Bonjour {{ $customer->full_name }}</h1>
            <p class="text-muted mb-0">{{ $customer->email }} | {{ $customer->phone }}</p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">Prochains rendez-vous</h2>
                <a href="{{ route('booking.portal.ical', $customer->portal_token) }}" class="btn btn-outline-primary btn-sm">
                    Télécharger iCal
                </a>
            </div>

            @if($upcoming->isEmpty())
                <div class="alert alert-info">
                    Aucun rendez-vous à venir
                </div>
            @else
                <div class="row g-4">
                    @foreach($upcoming as $appointment)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0">{{ $appointment->service->name }}</h5>
                                        @if($appointment->status === 'pending')
                                            <span class="badge bg-warning">En attente</span>
                                        @elseif($appointment->status === 'confirmed')
                                            <span class="badge bg-success">Confirmé</span>
                                        @endif
                                    </div>

                                    <p class="card-text">
                                        {{ $appointment->start_at->format('d/m/Y H:i') }}
                                    </p>

                                    <div class="mt-4">
                                        <form method="POST" action="{{ route('booking.portal.cancel', [$customer->portal_token, $appointment->id]) }}"
                                              data-confirm="Êtes-vous sûr de vouloir annuler ce rendez-vous ?">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                                Annuler le rendez-vous
                                            </button>
                                        </form>
                                    </div>

                                    <div class="mt-3">
                                        <small class="text-muted">
                                            Annulation possible jusqu'à {{ config('booking.min_notice_hours', 48) }}h avant
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if(!$past->isEmpty())
        <div class="row">
            <div class="col-12">
                <h2 class="h4 mb-4">Historique</h2>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Service</th>
                                <th scope="col">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($past as $appointment)
                                <tr>
                                    <td>{{ $appointment->start_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $appointment->service->name }}</td>
                                    <td>
                                        @if($appointment->status === 'cancelled')
                                            <span class="badge bg-danger">Annulé</span>
                                        @else
                                            <span class="badge bg-secondary">Terminé</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
