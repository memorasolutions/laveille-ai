<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
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

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="bar-chart-2" class="icon-md text-primary"></i>{{ __('Analytiques IA') }}</h4>
    <x-backoffice::help-modal id="helpAnalyticsModal" :title="__('Analytiques IA')" icon="bar-chart-2" :buttonLabel="__('Aide')">
        @include('ai::admin.analytics._help')
    </x-backoffice::help-modal>
</div>

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
<div class="row mb-4">
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

{{-- Helpdesk KPIs --}}
<h5 class="fw-bold mb-3 d-flex align-items-center gap-2"><i data-lucide="ticket" class="icon-md text-primary"></i>{{ __('Helpdesk') }}</h5>
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Tickets totaux') }}</h6>
                        <h3 class="mb-0">{{ number_format($totalTickets) }}</h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded"><i data-lucide="ticket" class="text-primary"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Tickets ouverts') }}</h6>
                        <h3 class="mb-0">{{ number_format($openTickets) }}</h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded"><i data-lucide="alert-circle" class="text-warning"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Tickets résolus') }}</h6>
                        <h3 class="mb-0">{{ number_format($resolvedTickets) }}</h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded"><i data-lucide="check-circle" class="text-success"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Messages canaux (30j)') }}</h6>
                        <h3 class="mb-0">{{ number_format($channelMessages) }}</h3>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded"><i data-lucide="inbox" class="text-info"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tickets by priority + CSAT --}}
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('Tickets par priorité') }}</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Priorité') }}</th>
                                <th class="text-end">{{ __('Nombre') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $priorityColors = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
                                $priorityLabels = ['low' => __('Basse'), 'medium' => __('Moyenne'), 'high' => __('Haute'), 'urgent' => __('Urgente')];
                            @endphp
                            @forelse($ticketsByPriority as $tp)
                            @php $pVal = $tp->priority->value ?? $tp->priority; @endphp
                            <tr>
                                <td><span class="badge bg-{{ $priorityColors[$pVal] ?? 'secondary' }}">{{ $priorityLabels[$pVal] ?? $pVal }}</span></td>
                                <td class="text-end">{{ number_format($tp->count) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">{{ __('Aucune donnée disponible') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('CSAT - Tendance 30 jours') }}</h5></div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div>
                        <span class="text-muted small">{{ __('Score moyen') }}</span>
                        <h4 class="mb-0 fw-bold">{{ $csatAvg ? number_format($csatAvg, 1) : '-' }}<small class="text-muted">/5</small></h4>
                    </div>
                    <div>
                        <span class="text-muted small">{{ __('Total') }}</span>
                        <h4 class="mb-0 fw-bold">{{ $csatTotal }}</h4>
                    </div>
                </div>
                @if($csatTrend->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>{{ __('Date') }}</th><th class="text-end">{{ __('Score moy.') }}</th><th class="text-end">{{ __('Réponses') }}</th></tr></thead>
                        <tbody>
                            @foreach($csatTrend as $ct)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($ct->date)->format('d/m') }}</td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $ct->avg_score >= 4 ? 'success' : ($ct->avg_score >= 3 ? 'warning' : 'danger') }}">
                                        {{ number_format($ct->avg_score, 1) }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $ct->count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center">{{ __('Aucune donnée disponible') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
