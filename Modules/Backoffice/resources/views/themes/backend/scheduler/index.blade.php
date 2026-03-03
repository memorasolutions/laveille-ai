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
    <a href="{{ route('admin.scheduler.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
        <i data-lucide="plus"></i>
        {{ __('Nouvelle tâche') }}
    </a>
</div>

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
