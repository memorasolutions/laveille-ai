<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('booking.brand.booking_page_title', 'Prendre rendez-vous') }}</title>
    <link href="{{ asset('build/nobleui/plugins/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        .step { display: none; }
        .step.active { display: block; }
        .service-card { cursor: pointer; border: 2px solid #dee2e6; transition: all 0.2s; }
        .service-card:hover { border-color: #adb5bd; }
        .service-card.selected { border-color: {{ $color }}; }
        .btn-booking { background-color: {{ $color }}; border-color: {{ $color }}; color: #fff; }
        .btn-booking:hover { opacity: 0.9; color: #fff; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4">{{ config('booking.brand.booking_page_title', 'Prendre rendez-vous') }}</h4>

                        <!-- Étape 1 : choix du service -->
                        <div id="step1" class="step active">
                            <h6 class="mb-3">Choisissez un service</h6>
                            @foreach($services as $service)
                            <div class="service-card card p-3 mb-2" data-id="{{ $service->id }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $service->name }}</strong>
                                        @if($service->description)
                                            <p class="text-muted small mb-0">{{ Str::limit($service->description, 80) }}</p>
                                        @endif
                                    </div>
                                    <div class="text-end text-nowrap">
                                        <span class="badge bg-secondary">{{ $service->duration_minutes }} min</span>
                                        @if($service->price > 0)
                                            <div class="mt-1 fw-bold">{{ number_format($service->price, 2) }} $</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            <button class="btn btn-booking mt-3 w-100" onclick="nextStep(2)">Continuer</button>
                        </div>

                        <!-- Étape 2 : date et heure -->
                        <div id="step2" class="step">
                            <h6 class="mb-3">Choisissez une date et une heure</h6>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <select class="form-select" id="dateSelect" onchange="updateTimeSlots()">
                                    <option value="">Sélectionner une date</option>
                                    @foreach($availableSlots as $date => $slots)
                                    <option value="{{ $date }}" data-slots="{{ json_encode($slots) }}">{{ \Carbon\Carbon::parse($date)->translatedFormat('l j F Y') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Heure</label>
                                <select class="form-select" id="timeSelect" disabled>
                                    <option value="">Sélectionner une heure</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary flex-grow-1" onclick="goStep(1)">Retour</button>
                                <button class="btn btn-booking flex-grow-1" onclick="nextStep(3)">Continuer</button>
                            </div>
                        </div>

                        <!-- Étape 3 : informations client -->
                        <div id="step3" class="step">
                            <h6 class="mb-3">Vos informations</h6>
                            <form id="bookingForm">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <input type="text" class="form-control" name="first_name" placeholder="Prénom" required>
                                    </div>
                                    <div class="col-6">
                                        <input type="text" class="form-control" name="last_name" placeholder="Nom" required>
                                    </div>
                                    <div class="col-12">
                                        <input type="email" class="form-control" name="email" placeholder="Courriel" required>
                                    </div>
                                    <div class="col-12">
                                        <input type="tel" class="form-control" name="phone" placeholder="Téléphone">
                                    </div>
                                    <div class="col-12">
                                        <textarea class="form-control" name="notes" rows="2" placeholder="Notes (facultatif)"></textarea>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button type="button" class="btn btn-outline-secondary flex-grow-1" onclick="goStep(2)">Retour</button>
                                    <button type="submit" class="btn btn-booking flex-grow-1">Confirmer</button>
                                </div>
                            </form>
                        </div>

                        <!-- Confirmation -->
                        <div id="success" class="text-center py-4" style="display: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="{{ $color }}" viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                            </svg>
                            <h5 class="mt-3">Rendez-vous confirmé</h5>
                            <p class="text-muted">{{ config('booking.brand.confirmation_message') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
var selectedService = null;
var slots = @json($availableSlots);

function goStep(n) {
    document.querySelectorAll('.step').forEach(function(s) { s.classList.remove('active'); });
    document.getElementById('step' + n).classList.add('active');
    resize();
}

function nextStep(n) {
    if (n === 2 && !selectedService) return alert('Veuillez choisir un service.');
    if (n === 3 && (!document.getElementById('dateSelect').value || !document.getElementById('timeSelect').value))
        return alert('Veuillez choisir une date et une heure.');
    goStep(n);
}

document.querySelectorAll('.service-card').forEach(function(card) {
    card.addEventListener('click', function() {
        document.querySelectorAll('.service-card').forEach(function(c) { c.classList.remove('selected'); });
        this.classList.add('selected');
        selectedService = this.dataset.id;
    });
});

function updateTimeSlots() {
    var date = document.getElementById('dateSelect').value;
    var ts = document.getElementById('timeSelect');
    ts.innerHTML = '<option value="">Sélectionner une heure</option>';
    ts.disabled = !date;
    if (date && slots[date]) {
        slots[date].forEach(function(t) {
            var o = document.createElement('option');
            o.value = t; o.textContent = t;
            ts.appendChild(o);
        });
    }
}

document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    var body = {
        service_id: selectedService,
        date: document.getElementById('dateSelect').value,
        start_time: document.getElementById('timeSelect').value,
        customer: {
            first_name: fd.get('first_name'),
            last_name: fd.get('last_name'),
            email: fd.get('email'),
            phone: fd.get('phone'),
            notes: fd.get('notes')
        }
    };
    fetch('/api/booking', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(body)
    }).then(function(r) {
        if (r.ok) {
            document.getElementById('step3').style.display = 'none';
            document.getElementById('success').style.display = 'block';
            resize();
        } else { alert('Erreur. Veuillez réessayer.'); }
    }).catch(function() { alert('Erreur réseau.'); });
});

function resize() {
    parent.postMessage({ type: 'booking-widget-resize', height: document.body.scrollHeight }, '*');
}
window.addEventListener('load', resize);
window.addEventListener('resize', resize);
</script>
</body>
</html>
