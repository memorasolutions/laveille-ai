@extends('backoffice::themes.backend.layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Planificateur') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i data-lucide="calendar-clock" class="text-primary icon-md"></i>
                {{ __('Tâches planifiées') }} ({{ count($tasks) }})
            </h4>
        </div>
    </div>
    <div class="card-body p-0">
        @if(empty($tasks))
            <div class="text-center py-5">
                <i data-lucide="calendar" class="text-muted mb-3" style="width:64px;height:64px;opacity:.3;"></i>
                <p class="text-muted">{{ __('Aucune tâche planifiée configurée.') }}</p>
                @if($rawOutput)
                    <details class="mt-3 text-start mx-auto" style="max-width:600px;">
                        <summary class="text-muted small" style="cursor:pointer;">
                            {{ __('Sortie brute') }}
                        </summary>
                        <pre class="mt-2 p-3 bg-light rounded small text-body" style="overflow-x:auto;">{{ $rawOutput }}</pre>
                    </details>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Expression cron') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Commande') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Prochaine exécution') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                        <tr>
                            <td class="py-3 px-4">
                                <code class="text-primary small bg-primary bg-opacity-10 px-2 py-1 rounded">
                                    {{ $task['expression'] }}
                                </code>
                            </td>
                            <td class="py-3 px-4 small text-muted">{{ $task['command'] }}</td>
                            <td class="py-3 px-4">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                    {{ $task['next_due'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <span class="text-muted small">{{ count($tasks) }} {{ __('tâche(s) planifiée(s)') }}</span>
            </div>
        @endif
    </div>
</div>

@endsection
