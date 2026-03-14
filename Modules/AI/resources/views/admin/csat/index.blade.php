<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Enquêtes CSAT'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item">{{ __('Intelligence artificielle') }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Enquêtes CSAT') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="star" class="icon-md text-primary"></i>{{ __('Satisfaction client (CSAT)') }}
    </h4>
</div>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-{{ ($avgScore ?? 0) >= 4 ? 'success' : (($avgScore ?? 0) >= 3 ? 'warning' : 'danger') }} bg-opacity-10">
                    <i data-lucide="star" class="text-{{ ($avgScore ?? 0) >= 4 ? 'success' : (($avgScore ?? 0) >= 3 ? 'warning' : 'danger') }}" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <p class="text-muted mb-0 small">{{ __('Score moyen') }}</p>
                    <h4 class="fw-bold mb-0">{{ $avgScore ? number_format($avgScore, 1) : '-' }} <small class="text-muted">/5</small></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-primary bg-opacity-10">
                    <i data-lucide="clipboard-list" class="text-primary" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <p class="text-muted mb-0 small">{{ __('Total enquêtes') }}</p>
                    <h4 class="fw-bold mb-0">{{ $totalSurveys }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        @php
            $satisfiedCount = $totalSurveys > 0 ? \Modules\AI\Models\CsatSurvey::where('score', '>=', 4)->count() : 0;
            $satisfactionRate = $totalSurveys > 0 ? round(($satisfiedCount / $totalSurveys) * 100, 1) : 0;
        @endphp
        <div class="card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-info bg-opacity-10">
                    <i data-lucide="thumbs-up" class="text-info" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <p class="text-muted mb-0 small">{{ __('Taux satisfaction') }}</p>
                    <h4 class="fw-bold mb-0">{{ $satisfactionRate }}%</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Ticket') }}</th>
                    <th>{{ __('Utilisateur') }}</th>
                    <th>{{ __('Score') }}</th>
                    <th>{{ __('Commentaire') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($surveys as $survey)
                <tr>
                    <td>{{ $survey->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($survey->ticket)
                        <a href="{{ route('admin.ai.tickets.show', $survey->ticket) }}" class="btn btn-sm btn-outline-primary">
                            #{{ $survey->ticket->id }}
                        </a>
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $survey->user->name ?? __('Anonyme') }}</td>
                    <td>
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $survey->score)
                            <i data-lucide="star" style="width:16px;height:16px;color:#f59e0b;fill:#f59e0b;display:inline;"></i>
                            @else
                            <i data-lucide="star" style="width:16px;height:16px;color:#d1d5db;display:inline;"></i>
                            @endif
                        @endfor
                        <small class="text-muted ms-1">({{ $survey->score }}/5)</small>
                    </td>
                    <td title="{{ $survey->comment }}">{{ Str::limit($survey->comment, 40) ?: '-' }}</td>
                    <td>
                        <form action="{{ route('admin.ai.csat.destroy', $survey) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Êtes-vous sûr ?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Aucune enquête CSAT.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($surveys->hasPages())
    <div class="card-footer">{{ $surveys->links() }}</div>
    @endif
</div>
@endsection
