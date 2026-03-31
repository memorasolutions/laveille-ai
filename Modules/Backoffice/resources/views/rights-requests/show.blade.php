@extends('backoffice::layouts.admin', ['title' => 'Demande ' . $rightsRequest->reference])

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.rights-requests.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Détails de la demande</h5>
                </div>
                <div class="card-body">
                    @php
                        $typeLabel = match ($rightsRequest->request_type) {
                            'access' => 'Accès',
                            'rectification' => 'Rectification',
                            'erasure' => 'Effacement',
                            'portability' => 'Portabilité',
                            'opposition' => 'Opposition',
                            'limitation' => 'Limitation',
                            'withdrawal' => 'Retrait',
                            default => ucfirst($rightsRequest->request_type),
                        };
                        $jurisdictionLabel = match ($rightsRequest->jurisdiction) {
                            'gdpr' => 'RGPD (UE)',
                            'canada_quebec' => 'Loi 25 (Québec)',
                            'pipeda' => 'LPRPDE (Canada)',
                            'ccpa' => 'CCPA (Californie)',
                            default => ucfirst($rightsRequest->jurisdiction),
                        };
                    @endphp

                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Référence</dt>
                        <dd class="col-sm-8"><code>{{ $rightsRequest->reference }}</code></dd>

                        <dt class="col-sm-4 text-muted">Type</dt>
                        <dd class="col-sm-8">{{ $typeLabel }}</dd>

                        <dt class="col-sm-4 text-muted">Nom</dt>
                        <dd class="col-sm-8">{{ $rightsRequest->name }}</dd>

                        <dt class="col-sm-4 text-muted">Courriel</dt>
                        <dd class="col-sm-8">{{ $rightsRequest->email }}</dd>

                        <dt class="col-sm-4 text-muted">Juridiction</dt>
                        <dd class="col-sm-8">{{ $jurisdictionLabel }}</dd>

                        <dt class="col-sm-4 text-muted">Statut</dt>
                        <dd class="col-sm-8">
                            @if ($rightsRequest->isOverdue())
                                <span class="badge bg-danger">En retard</span>
                            @endif
                            @switch($rightsRequest->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark">En attente</span>
                                    @break
                                @case('in_progress')
                                    <span class="badge bg-info">En cours</span>
                                    @break
                                @case('completed')
                                    <span class="badge bg-success">Terminée</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($rightsRequest->status) }}</span>
                            @endswitch
                        </dd>

                        <dt class="col-sm-4 text-muted">Date de la demande</dt>
                        <dd class="col-sm-8">{{ $rightsRequest->created_at->format('d/m/Y') }}</dd>

                        <dt class="col-sm-4 text-muted">Échéance</dt>
                        <dd class="col-sm-8 {{ $rightsRequest->isOverdue() ? 'text-danger fw-bold' : '' }}">
                            {{ $rightsRequest->deadline_at?->format('d/m/Y') ?? '—' }}
                        </dd>

                        <dt class="col-sm-4 text-muted">Répondue le</dt>
                        <dd class="col-sm-8">{{ $rightsRequest->responded_at?->format('d/m/Y') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Description</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $rightsRequest->description }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @if ($rightsRequest->status !== 'completed')
                        <form action="{{ route('admin.rights-requests.mark-completed', $rightsRequest) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Confirmer que cette demande est traitée?')">
                                <i class="bi bi-check-lg"></i> Marquer comme terminée
                            </button>
                        </form>
                    @else
                        <div class="alert alert-success mb-0">
                            Demande traitée le {{ $rightsRequest->responded_at?->format('d/m/Y') ?? '—' }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Notes administrateur</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.rights-requests.add-note', $rightsRequest) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <textarea class="form-control @error('admin_notes') is-invalid @enderror" name="admin_notes" rows="4" placeholder="Notes internes...">{{ old('admin_notes', $rightsRequest->admin_notes) }}</textarea>
                            @error('admin_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Enregistrer la note
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
