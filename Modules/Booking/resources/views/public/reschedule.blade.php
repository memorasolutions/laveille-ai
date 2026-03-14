<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('booking::public.layouts.public')

@section('content')
<div class="container py-5">
    <h2 class="mb-4">Replanifier votre rendez-vous</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="card mb-4 border-info">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Rendez-vous actuel</h5>
        </div>
        <div class="card-body">
            <p class="mb-1"><strong>Service :</strong> {{ $appointment->service->name }}</p>
            <p class="mb-0"><strong>Date et heure :</strong> {{ $appointment->start_at->translatedFormat('l j F Y \\à H:i') }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Sélectionner une nouvelle date</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('booking.processReschedule', $appointment->cancel_token) }}">
                @csrf

                <div class="mb-3">
                    <label for="date" class="form-label">Date *</label>
                    <select class="form-select" id="date" name="date" required>
                        <option value="">Choisir une date</option>
                        @foreach($availableSlots as $date => $slots)
                            <option value="{{ $date }}" data-slots="{{ json_encode($slots) }}">
                                {{ \Carbon\Carbon::parse($date)->translatedFormat('l j F Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="start_time" class="form-label">Heure *</label>
                    <select class="form-select" id="start_time" name="start_time" required disabled>
                        <option value="">Choisir une heure</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Confirmer la nouvelle date</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var dateSelect = document.getElementById('date');
    var timeSelect = document.getElementById('start_time');
    dateSelect.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        var slots = opt.dataset.slots ? JSON.parse(opt.dataset.slots) : [];
        timeSelect.innerHTML = '<option value="">Choisir une heure</option>';
        timeSelect.disabled = slots.length === 0;
        slots.forEach(function(s) {
            var o = document.createElement('option');
            o.value = s;
            o.textContent = s;
            timeSelect.appendChild(o);
        });
    });
});
</script>
@endsection
