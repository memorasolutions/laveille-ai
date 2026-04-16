<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Planificateur') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="calendar-clock" class="text-primary icon-md"></i>
        {{ __('Tâches planifiées') }}
        <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal fs-6">{{ count($systemTasks) + $customTasks->count() }}</span>
    </h4>
    <div class="d-flex gap-2">
        <x-backoffice::help-modal id="helpSchedulerModal" :title="__('Planificateur de tâches')" icon="clock" :buttonLabel="__('Aide')">
            @include('backoffice::themes.backend.scheduler._help')
        </x-backoffice::help-modal>
        <a href="{{ route('admin.scheduler.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
            <i data-lucide="plus"></i>
            {{ __('Nouvelle tâche') }}
        </a>
    </div>
</div>

{{-- Jobs échoués (queue) — visibilité ops --}}
@if(isset($failedJobs))
    @if($failedJobs['total'] === 0)
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
                <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-circle">
                    <i data-lucide="check-circle" style="width:20px;height:20px;"></i>
                </span>
                <span class="fw-semibold text-muted">{{ __('Aucun job échoué — la queue fonctionne parfaitement.') }}</span>
            </div>
        </div>
    @else
        <div class="card mb-3">
            <div class="card-header py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="alert-triangle" class="text-danger icon-sm"></i>
                    {{ __('Jobs échoués (queue)') }}
                </h5>
                <span class="badge bg-danger bg-opacity-10 text-danger">
                    {{ $failedJobs['total'] }} {{ __('échoué') }}{{ $failedJobs['total'] > 1 ? 's' : '' }}
                </span>
            </div>
            <div class="card-body py-3 px-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 text-center h-100">
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                                <i data-lucide="database" class="text-secondary icon-sm"></i>
                                <span class="text-muted small fw-semibold text-uppercase">{{ __('Total') }}</span>
                            </div>
                            <span class="fs-3 fw-bold text-danger">{{ number_format($failedJobs['total']) }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 text-center h-100">
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                                <i data-lucide="calendar" class="text-secondary icon-sm"></i>
                                <span class="text-muted small fw-semibold text-uppercase">{{ __('7 derniers jours') }}</span>
                            </div>
                            <span class="fs-3 fw-bold {{ $failedJobs['last_7_days'] > 0 ? 'text-warning' : 'text-success' }}">
                                {{ number_format($failedJobs['last_7_days']) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 text-center h-100">
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                                <i data-lucide="clock" class="text-secondary icon-sm"></i>
                                <span class="text-muted small fw-semibold text-uppercase">{{ __('24 dernières heures') }}</span>
                            </div>
                            <span class="fs-3 fw-bold {{ $failedJobs['last_24h'] > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($failedJobs['last_24h']) }}
                            </span>
                        </div>
                    </div>
                </div>

                @if($failedJobs['by_class']->count() > 0)
                    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-1">
                        <i data-lucide="bar-chart-2" class="text-muted icon-sm"></i>
                        {{ __('Top jobs problématiques') }}
                    </h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="py-2 px-3 fw-semibold text-body small">{{ __('Classe') }}</th>
                                    <th class="py-2 px-3 fw-semibold text-body small text-center" style="width:100px;">{{ __('Échecs') }}</th>
                                    <th class="py-2 px-3 fw-semibold text-body small text-end" style="width:180px;">{{ __('Dernier') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($failedJobs['by_class'] as $row)
                                    <tr>
                                        <td class="py-2 px-3">
                                            <code class="small">{{ class_basename($row->class) }}</code>
                                            <span class="d-block text-muted small text-truncate" style="max-width:400px;">{{ $row->class }}</span>
                                        </td>
                                        <td class="py-2 px-3 text-center">
                                            <span class="badge bg-danger bg-opacity-10 text-danger">{{ $row->count }}</span>
                                        </td>
                                        <td class="py-2 px-3 text-end small text-muted">
                                            {{ $row->last_failed->locale('fr')->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="text-end">
                    <a href="{{ route('admin.failed-jobs.index') }}" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                        <i data-lucide="external-link" class="icon-sm"></i>
                        {{ __('Voir tous les jobs échoués') }} ({{ $failedJobs['total'] }})
                    </a>
                </div>
            </div>
        </div>
    @endif
@endif

{{-- Kill switches automatisations (Laravel Pennant) --}}
@if(!empty($killSwitches))
@php
    $activeKs = collect($killSwitches)->where('active', true)->count();
    $totalKs = count($killSwitches);
@endphp
<div class="card mb-3">
    <div class="card-header py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
        <h5 class="fw-semibold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="shield-alert" class="text-warning icon-sm"></i>
            {{ __('Kill switches automatisations') }}
        </h5>
        <span class="badge bg-{{ $activeKs === $totalKs ? 'success' : 'warning' }} bg-opacity-10 text-{{ $activeKs === $totalKs ? 'success' : 'warning' }}">
            {{ $activeKs }} / {{ $totalKs }} {{ __('actifs') }}
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="py-3 px-4 fw-semibold text-body" style="width:22%">{{ __('Flag') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Automatisation') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body" style="width:10%">{{ __('Statut') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body text-end" style="width:14%">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($killSwitches as $ks)
                    <tr>
                        <td class="py-3 px-4 align-middle">
                            <code class="text-primary small bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $ks['flag'] }}</code>
                        </td>
                        <td class="py-3 px-4 align-middle">
                            <div class="fw-medium text-body">{{ $ks['label'] }}</div>
                            <small class="text-muted">{{ $ks['description'] }}</small>
                        </td>
                        <td class="py-3 px-4 align-middle">
                            @if($ks['active'])
                                <span class="badge bg-success">{{ __('Actif') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('En pause') }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 align-middle text-end">
                            <form action="{{ route('admin.scheduler.kill-switch.toggle', ['flag' => $ks['flag']]) }}" method="POST" class="d-inline">
                                @csrf
                                @if($ks['active'])
                                    <button type="submit" class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1" title="{{ __('Mettre en pause') }}" onclick="return confirm('{{ __('Désactiver cette automatisation ?') }}')">
                                        <i data-lucide="pause" class="icon-sm"></i> {{ __('Pause') }}
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1" title="{{ __('Réactiver') }}">
                                        <i data-lucide="play" class="icon-sm"></i> {{ __('Activer') }}
                                    </button>
                                @endif
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Tâches système --}}
<div class="card mb-3">
    <div class="card-header py-3 px-4 border-bottom">
        <h5 class="fw-semibold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="shield" class="text-secondary icon-sm"></i>
            {{ __('Tâches système') }} ({{ count($systemTasks) }})
        </h5>
    </div>
    <div class="card-body p-0">
        @if(empty($systemTasks))
            <div class="text-center py-5">
                <i data-lucide="calendar" class="text-muted mb-3" style="width:48px;height:48px;opacity:.3;"></i>
                <p class="text-muted">{{ __('Aucune tâche système détectée.') }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:18%">{{ __('Expression cron') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Commande') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:20%">{{ __('Prochaine exécution') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:10%">{{ __('Type') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($systemTasks as $task)
                        <tr>
                            <td class="py-3 px-4">
                                <code class="text-primary small bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $task['expression'] }}</code>
                            </td>
                            <td class="py-3 px-4 small text-muted">{{ $task['command'] }}</td>
                            <td class="py-3 px-4">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $task['next_due'] }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="badge bg-secondary d-inline-flex align-items-center gap-1">
                                    <i data-lucide="lock" class="icon-sm"></i> {{ __('Système') }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Tâches personnalisées --}}
<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <h5 class="fw-semibold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="settings" class="text-primary icon-sm"></i>
            {{ __('Tâches personnalisées') }} ({{ $customTasks->count() }})
        </h5>
    </div>
    <div class="card-body p-0">
        @if($customTasks->isEmpty())
            <div class="text-center py-5">
                <i data-lucide="calendar" class="text-muted mb-3" style="width:48px;height:48px;opacity:.3;"></i>
                <p class="text-muted">{{ __('Aucune tâche personnalisée.') }}</p>
                <a href="{{ route('admin.scheduler.create') }}" class="btn btn-outline-primary btn-sm">
                    <i data-lucide="plus" class="icon-sm"></i> {{ __('Créer une tâche') }}
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:15%">{{ __('Expression cron') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Commande') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Description') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:8%">{{ __('Statut') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:12%">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customTasks as $task)
                        <tr>
                            <td class="py-3 px-4">
                                <code class="text-primary small bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $task->cron_expression }}</code>
                            </td>
                            <td class="py-3 px-4 small fw-medium text-body">{{ $task->command }}</td>
                            <td class="py-3 px-4 small text-muted">{{ $task->description ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @if($task->is_active)
                                    <span class="badge bg-success">{{ __('Actif') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactif') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.scheduler.edit', $task) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Modifier') }}">
                                        <i data-lucide="edit" class="icon-sm"></i>
                                    </a>
                                    <form action="{{ route('admin.scheduler.toggle', $task) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $task->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $task->is_active ? __('Désactiver') : __('Activer') }}">
                                            <i data-lucide="{{ $task->is_active ? 'pause' : 'play' }}" class="icon-sm"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.scheduler.destroy', $task) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Supprimer') }}" onclick="return confirm('{{ __('Supprimer cette tâche ?') }}')">
                                            <i data-lucide="trash-2" class="icon-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
