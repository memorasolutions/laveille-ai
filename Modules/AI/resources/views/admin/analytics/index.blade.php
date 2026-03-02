@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Analytiques IA'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item">{{ __('Intelligence artificielle') }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Analytiques') }}</li>
    </ol>
</nav>

{{-- KPI Cards --}}
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Conversations totales') }}</h6>
                        <h3 class="mb-0">{{ number_format($totalConversations) }}</h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i data-lucide="message-square" class="text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Conversations actives') }}</h6>
                        <h3 class="mb-0">{{ number_format($activeConversations) }}</h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i data-lucide="activity" class="text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Messages totaux') }}</h6>
                        <h3 class="mb-0">{{ number_format($totalMessages) }}</h3>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i data-lucide="bar-chart-3" class="text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Moy. messages/conv') }}</h6>
                        <h3 class="mb-0">{{ number_format($avgMessagesPerConversation, 1) }}</h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i data-lucide="trending-up" class="text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Activity and Model Usage --}}
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Activité des 30 derniers jours') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th class="text-end">{{ __('Messages') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyActivity as $activity)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($activity->date)->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ number_format($activity->count) }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">{{ __('Aucune donnée disponible') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Utilisation par modèle') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Modèle') }}</th>
                                <th class="text-end">{{ __('Utilisation') }}</th>
                                <th style="width: 150px;">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalUsage = $modelUsage->sum('count'); @endphp
                            @forelse($modelUsage as $model)
                            @php $pct = $totalUsage > 0 ? ($model->count / $totalUsage) * 100 : 0; @endphp
                            <tr>
                                <td><span class="badge bg-secondary">{{ $model->model }}</span></td>
                                <td class="text-end">{{ number_format($model->count) }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-info" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <small class="text-muted ms-2">{{ number_format($pct, 1) }}%</small>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">{{ __('Aucune donnée disponible') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Feedback --}}
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Feedback') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Type') }}</th>
                                <th class="text-end">{{ __('Nombre') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($feedbackStats as $feedback)
                            <tr>
                                <td>
                                    @if($feedback->feedback === 'up')
                                        <span class="badge bg-success"><i data-lucide="thumbs-up" style="width:14px;height:14px;" class="me-1"></i>Positif</span>
                                    @elseif($feedback->feedback === 'down')
                                        <span class="badge bg-danger"><i data-lucide="thumbs-down" style="width:14px;height:14px;" class="me-1"></i>Négatif</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($feedback->feedback) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($feedback->count) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">{{ __('Aucune donnée disponible') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
