@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Demandes de retrait')])

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h4>{{ __('Demandes de retrait') }}</h4>
        <span class="badge bg-danger fs-6">{{ $pendingCount }} {{ __('en attente') }}</span>
    </div>
</div>

@forelse($requests as $r)
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <strong>#{{ $r->id }}</strong>
                @php
                    $statusLabels = [
                        'pending' => ['label' => __('En attente'), 'class' => 'bg-warning text-dark'],
                        'reviewed' => ['label' => __('Révisée'), 'class' => 'bg-info text-white'],
                        'actioned' => ['label' => __('Actionnée'), 'class' => 'bg-success text-white'],
                        'rejected' => ['label' => __('Rejetée'), 'class' => 'bg-secondary text-white'],
                    ];
                    $status = $statusLabels[$r->status] ?? ['label' => $r->status, 'class' => 'bg-light text-muted'];
                @endphp
                <span class="badge {{ $status['class'] }} ms-2">{{ $status['label'] }}</span>
                <span class="text-muted ms-auto">{{ $r->created_at->format('Y-m-d H:i') }}</span>
            </div>

            <dl class="row mb-0 small">
                <dt class="col-sm-3">{{ __('Outil') }}</dt>
                <dd class="col-sm-9">{{ $r->tool?->name ?? '–' }}</dd>

                <dt class="col-sm-3">{{ __('URL visée') }}</dt>
                <dd class="col-sm-9">
                    @if($r->target_url)
                        <a href="{{ $r->target_url }}" target="_blank" rel="nofollow noopener">{{ $r->target_url }}</a>
                    @else
                        –
                    @endif
                </dd>

                <dt class="col-sm-3">{{ __('Demandeur') }}</dt>
                <dd class="col-sm-9">{{ $r->requester_name }} &lt;{{ $r->requester_email }}&gt;</dd>

                <dt class="col-sm-3">{{ __('Organisation') }}</dt>
                <dd class="col-sm-9">{{ $r->requester_organization ?? '–' }}</dd>

                <dt class="col-sm-3">{{ __('Rôle') }}</dt>
                <dd class="col-sm-9">{{ $r->requester_role ?? '–' }}</dd>

                <dt class="col-sm-3">{{ __('Type de droit') }}</dt>
                <dd class="col-sm-9">{{ $r->right_type ?? '–' }}</dd>

                <dt class="col-sm-3">{{ __('Preuve / Détails') }}</dt>
                <dd class="col-sm-9">{{ $r->right_details ?? '–' }}</dd>

                <dt class="col-sm-3">{{ __('Description') }}</dt>
                <dd class="col-sm-9">{{ $r->description ?? '–' }}</dd>

                <dt class="col-sm-3">{{ __('Déclaration acceptée') }}</dt>
                <dd class="col-sm-9">{{ $r->declaration_accepted ? '✓ ' . __('Oui') : '✗ ' . __('Non') }}</dd>

                <dt class="col-sm-3">{{ __('IP') }}</dt>
                <dd class="col-sm-9">{{ $r->ip_address ?? '–' }}</dd>
            </dl>
        </div>

        <div class="card-footer bg-white d-flex gap-2 flex-wrap">
            @php
                $actions = [
                    'reviewed' => __('Marquer comme révisée'),
                    'actioned' => __('Marquer comme actionnée'),
                    'rejected' => __('Rejeter'),
                    'pending' => __('Remettre en attente'),
                ];
            @endphp

            @foreach($actions as $statusValue => $label)
                @if($r->status !== $statusValue)
                    <form method="POST" action="{{ route('admin.directory.takedown-requests.status', $r->id) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="status" value="{{ $statusValue }}">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $label }}</button>
                    </form>
                @endif
            @endforeach
        </div>
    </div>
@empty
    <div class="alert alert-info">{{ __('Aucune demande de retrait pour le moment.') }}</div>
@endforelse

<script>
    if (window.lucide) lucide.createIcons();
</script>
@endsection
