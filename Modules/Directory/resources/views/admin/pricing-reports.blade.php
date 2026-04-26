@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Signalements tarification')])

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">
            <i data-lucide="flag" class="me-2"></i>
            {{ __('Signalements tarification') }}
        </h4>
        <a href="{{ route('admin.directory.index') }}" class="btn btn-outline-secondary">
            <i data-lucide="arrow-left" class="me-1"></i>
            {{ __('Retour') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-warning bg-opacity-10 border-warning">
                <div class="card-body">
                    <h6 class="card-title text-warning fw-bold">{{ __('En attente') }}</h6>
                    <p class="card-text display-6 mb-0">{{ $pendingCount }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-success bg-opacity-10 border-success">
                <div class="card-body">
                    <h6 class="card-title text-success fw-bold">{{ __('Examinés') }}</h6>
                    <p class="card-text display-6 mb-0">{{ $reviewedCount }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Outil') }}</th>
                            <th scope="col">{{ __('Tarif actuel') }}</th>
                            <th scope="col">{{ __('Tarif signalé') }}</th>
                            <th scope="col">{{ __('URL preuve') }}</th>
                            <th scope="col">{{ __('Notes utilisateur') }}</th>
                            <th scope="col">{{ __('Statut') }}</th>
                            <th scope="col">{{ __('Auteur') }}</th>
                            <th scope="col">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>
                                    @if($report->tool)
                                        <a href="{{ route('directory.show', $report->tool->getTranslation('slug', 'fr_CA')) }}" target="_blank" rel="noopener">
                                            {{ $report->tool->name }}
                                        </a>
                                    @else
                                        {{ __('Outil supprimé') }}
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $report->current_pricing_snapshot ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $report->reported_pricing }}</span>
                                </td>
                                <td>
                                    @if($report->evidence_url)
                                        <a href="{{ $report->evidence_url }}" target="_blank" rel="noopener">{{ __('Lien preuve') }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($report->user_notes, 80) }}</td>
                                <td>
                                    @if($report->status === 'pending')
                                        <span class="badge bg-warning">{{ __('En attente') }}</span>
                                    @elseif($report->status === 'approved')
                                        <span class="badge bg-success">{{ __('Approuvé') }}</span>
                                    @elseif($report->status === 'rejected')
                                        <span class="badge bg-secondary">{{ __('Rejeté') }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $report->user?->email ?? __('Anonyme') }}<br>
                                    <small class="text-muted">{{ $report->created_at->format('Y-m-d H:i') }}</small>
                                </td>
                                <td>
                                    @if($report->status === 'pending')
                                        <form action="{{ route('admin.directory.pricing-reports.approve', $report) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Confirmer l\'approbation de ce signalement ?') }}')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" aria-label="{{ __('Approuver') }}">
                                                <i data-lucide="check"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.directory.pricing-reports.reject', $report) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Confirmer le rejet de ce signalement ?') }}')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger" aria-label="{{ __('Rejeter') }}">
                                                <i data-lucide="x"></i>
                                            </button>
                                        </form>
                                    @else
                                        <small>
                                            {{ __('Revu par') }} {{ $report->reviewer?->email ?? '?' }}<br>
                                            {{ $report->reviewed_at?->format('Y-m-d') }}
                                            @if($report->admin_notes)
                                                <br><em>{{ \Illuminate\Support\Str::limit($report->admin_notes, 60) }}</em>
                                            @endif
                                        </small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="alert alert-info mb-0" role="alert">
                                        {{ __('Aucun signalement pour le moment') }}
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">
        {{ $reports->links() }}
    </div>
</div>
@endsection
