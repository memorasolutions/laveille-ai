<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Jobs échoués') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="alert-triangle" class="icon-md text-primary"></i>{{ __('Tâches échouées') }}</h4>
    <x-backoffice::help-modal id="helpFailedJobsModal" :title="__('Tâches échouées')" icon="alert-triangle" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.failed-jobs._help')
    </x-backoffice::help-modal>
</div>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i data-lucide="alert-triangle" class="icon-md text-danger"></i>
                {{ __('Jobs échoués') }} ({{ $failedJobs->count() }})
            </h4>
            @if($failedJobs->isNotEmpty())
                <form action="{{ route('admin.failed-jobs.destroy-all') }}" method="POST"
                      data-confirm="{{ __('Supprimer tous les jobs échoués ?') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-2">
                        <i data-lucide="trash-2" class="icon-sm"></i>
                        {{ __('Tout supprimer') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
    <div class="p-4">
        @if($failedJobs->isEmpty())
            <div class="text-center py-5">
                <i data-lucide="check-circle" class="text-success d-block mx-auto mb-3" style="width:48px;height:48px;opacity:.3;"></i>
                <p class="text-muted">{{ __('Aucun job en échec. Tout fonctionne correctement.') }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body">ID</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('File') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">Job</th>
                            <th class="py-3 px-4 fw-semibold text-body">Exception</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Date') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedJobs as $job)
                        @php
                            $payload = json_decode($job->payload, true);
                        @endphp
                        <tr>
                            <td class="py-3 px-4 text-muted small">{{ $job->id }}</td>
                            <td class="py-3 px-4">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                    {{ $job->queue }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <code class="text-primary small">
                                    {{ \Illuminate\Support\Str::limit($payload['displayName'] ?? 'N/A', 50) }}
                                </code>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-danger small" style="max-width:200px;word-break:break-word;display:inline-block;">
                                    {{ \Illuminate\Support\Str::limit(explode("\n", $job->exception)[0] ?? '', 80) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-muted small">{{ $job->failed_at }}</td>
                            <td class="py-3 px-4">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px;" type="button" data-bs-toggle="dropdown">
                                        <i data-lucide="more-vertical" class="icon-sm"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <form action="{{ route('admin.failed-jobs.retry', $job->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                                    <i data-lucide="rotate-ccw" class="icon-sm"></i>
                                                    {{ __('Réessayer') }}
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.failed-jobs.destroy', $job->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('{{ __('Supprimer ce job ?') }}')" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                                    <i data-lucide="trash-2" class="icon-sm"></i>
                                                    {{ __('Supprimer') }}
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
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
