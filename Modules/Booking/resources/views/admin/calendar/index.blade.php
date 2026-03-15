<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Calendrier des réservations')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.booking.appointments.index') }}">Réservations</a></li>
            <li class="breadcrumb-item active" aria-current="page">Calendrier</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Calendrier interactif</h5>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge" style="background-color:#ffc107;color:#000;">En attente</span>
                <span class="badge" style="background-color:#198754;">Confirmé</span>
                <span class="badge" style="background-color:#dc3545;">Annulé</span>
                <span class="badge" style="background-color:#0dcaf0;color:#000;">Terminé</span>
                <span class="badge" style="background-color:#6c757d;">Approbation</span>
                <span class="badge" style="background-color:#212529;">No-show</span>
            </div>
        </div>
        <div class="card-body">
            <div id="bookingCalendar" style="min-height:600px;"></div>
        </div>
    </div>
</div>
@endsection

@push('plugin-scripts')
<script src="{{ asset('build/nobleui/plugins/fullcalendar/index.global.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('bookingCalendar');
    if (!calendarEl || typeof FullCalendar === 'undefined') return;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        headerToolbar: {
            left: 'prev,today,next',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('{{ route("admin.booking.calendar.events") }}?start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => successCallback(data))
            .catch(err => failureCallback(err));
        },
        eventClick: function(info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.href = info.event.url;
            }
        },
        slotMinTime: '07:00:00',
        slotMaxTime: '21:00:00',
        navLinks: true,
        nowIndicator: true,
        dayMaxEvents: 3,
        height: 'auto',
        editable: false
    });

    calendar.render();
});
</script>
@endpush
