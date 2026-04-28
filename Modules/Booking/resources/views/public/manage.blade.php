<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer mon rendez-vous</title>
    <link href="{{ asset('build/nobleui/plugins/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 600px; margin-top: 3rem;">
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h4 class="mb-4">Mon rendez-vous</h4>

                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Service</div>
                    <div class="col-sm-8 fw-semibold">{{ $appointment->service->name }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Date et heure</div>
                    <div class="col-sm-8">{{ $appointment->start_at->format('d/m/Y H:i') }} – {{ $appointment->end_at->format('H:i') }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Client</div>
                    <div class="col-sm-8">{{ $appointment->customer->full_name }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Statut</div>
                    <div class="col-sm-8">
                        @php $colors = ['pending'=>'warning','confirmed'=>'success','cancelled'=>'danger','completed'=>'info']; @endphp
                        <span class="badge bg-{{ $colors[$appointment->status] ?? 'secondary' }}">{{ __($appointment->status) }}</span>
                    </div>
                </div>

                @if(! in_array($appointment->status, ['cancelled', 'completed']))
                <hr>
                <h6 class="text-danger mb-3">Annuler ce rendez-vous</h6>
                <form action="{{ route('booking.cancel', $appointment->cancel_token) }}" method="POST" data-confirm="Êtes-vous sûr de vouloir annuler ?">
                    @csrf
                    <div class="mb-3">
                        <textarea name="reason" class="form-control" rows="2" placeholder="Raison de l'annulation (optionnel)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">Annuler le rendez-vous</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
