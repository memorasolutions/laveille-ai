<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => $workflow->name, 'subtitle' => 'Workflow'])

@section('content')
<div class="row gy-3">
    <div class="col-lg-8">
        {{-- Stats --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-primary">{{ $stats['active'] }}</div>
                        <small class="text-muted">Actifs</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-success">{{ $stats['completed'] }}</div>
                        <small class="text-muted">Terminés</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-info">{{ $stats['total_sent'] }}</div>
                        <small class="text-muted">Envoyés</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-danger">{{ $stats['total_failed'] }}</div>
                        <small class="text-muted">Échoués</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Steps --}}
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Étapes ({{ $workflow->steps->count() }})</h6></div>
            <div class="card-body">
                @forelse($workflow->steps as $step)
                    <div class="d-flex align-items-start gap-3 {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:28px;height:28px;min-width:28px">{{ $loop->iteration }}</span>
                        <div>
                            <strong>
                                @switch($step->type)
                                    @case('send_email') Envoyer email @break
                                    @case('delay') Délai @break
                                    @case('condition') Condition @break
                                    @case('action') Action @break
                                @endswitch
                            </strong>
                            @if($step->type === 'send_email' && $step->template)
                                <br><small class="text-muted">Template : {{ $step->template->name }}</small>
                            @endif
                            @if($step->type === 'delay')
                                <br><small class="text-muted">{{ $step->config['delay_hours'] ?? 24 }}h d'attente</small>
                            @endif
                            @if($step->type === 'condition')
                                <br><small class="text-muted">Vérifier : {{ $step->config['condition_type'] ?? 'is_active' }}</small>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Aucune étape configurée.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Détails</h6></div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt class="text-muted text-sm">Statut</dt>
                    <dd>
                        @switch($workflow->status)
                            @case('active')<span class="badge bg-success">Actif</span>@break
                            @case('paused')<span class="badge bg-warning">Pause</span>@break
                            @case('archived')<span class="badge bg-secondary">Archivé</span>@break
                            @default<span class="badge bg-info">Brouillon</span>
                        @endswitch
                    </dd>
                    <dt class="text-muted text-sm">Déclencheur</dt>
                    <dd>{{ $workflow->trigger_type }}</dd>
                    @if($workflow->description)
                        <dt class="text-muted text-sm">Description</dt>
                        <dd>{{ $workflow->description }}</dd>
                    @endif
                    <dt class="text-muted text-sm">Créé par</dt>
                    <dd>{{ $workflow->creator?->name ?? '-' }}</dd>
                    <dt class="text-muted text-sm">Créé le</dt>
                    <dd>{{ $workflow->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="text-muted text-sm">Annulés</dt>
                    <dd class="mb-0">{{ $stats['cancelled'] }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Actions</h6></div>
            <div class="card-body d-grid gap-2">
                @if($workflow->status !== 'active')
                    <form method="POST" action="{{ route('admin.newsletter.workflows.activate', $workflow) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm w-100">Activer</button>
                    </form>
                @endif
                @if($workflow->status === 'active')
                    <form method="POST" action="{{ route('admin.newsletter.workflows.pause', $workflow) }}">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm w-100">Mettre en pause</button>
                    </form>
                @endif
                <a href="{{ route('admin.newsletter.workflows.edit', $workflow) }}" class="btn btn-outline-primary btn-sm">Modifier</a>
            </div>
        </div>
    </div>
</div>
@endsection
