<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Paramètres de réservation')

@section('content')
<div class="card">
    <div class="card-header"><h5 class="mb-0">Paramètres de réservation</h5></div>
    <div class="card-body">
        <form action="{{ route('admin.booking.settings.update') }}" method="POST">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="business_name">Nom de l'entreprise</label>
                    <input type="text" class="form-control" id="business_name" name="settings[business_name]" value="{{ $settings['business_name'] ?? config('app.name') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="booking_page_title">Titre de la page</label>
                    <input type="text" class="form-control" id="booking_page_title" name="settings[booking_page_title]" value="{{ $settings['booking_page_title'] ?? 'Prendre rendez-vous' }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="confirmation_message">Message de confirmation</label>
                <textarea class="form-control" id="confirmation_message" name="settings[confirmation_message]" rows="3">{{ $settings['confirmation_message'] ?? '' }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="min_notice_hours">Préavis minimum (heures)</label>
                    <input type="number" class="form-control" id="min_notice_hours" name="settings[min_notice_hours]" value="{{ $settings['min_notice_hours'] ?? 48 }}" min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="max_advance_days">Réservation max (jours)</label>
                    <input type="number" class="form-control" id="max_advance_days" name="settings[max_advance_days]" value="{{ $settings['max_advance_days'] ?? 60 }}" min="1">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="buffer_minutes">Tampon entre RDV (minutes)</label>
                    <input type="number" class="form-control" id="buffer_minutes" name="settings[buffer_minutes]" value="{{ $settings['buffer_minutes'] ?? 15 }}" min="0">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>
</div>
@endsection
