@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Gestion des incidents'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
    <li class="breadcrumb-item">{{ __('Santé') }}</li>
    <li class="breadcrumb-item active">{{ __('Incidents') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Liste des incidents') }}</h5>
        <a href="{{ route('admin.health.incidents.create') }}" class="btn btn-primary">
            <i data-lucide="plus" class="me-1"></i> {{ __('Nouvel incident') }}
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Titre') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th>{{ __('Sévérité') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incidents as $incident)
                    <tr>
                        <td>{{ $incident->title }}</td>
                        <td>
                            @php
                                $statusColors = [
                                    'investigating' => 'warning',
                                    'identified' => 'info',
                                    'monitoring' => 'secondary',
                                    'resolved' => 'success',
                                ];
                                $statusLabels = [
                                    'investigating' => __('Investigation'),
                                    'identified' => __('Identifié'),
                                    'monitoring' => __('Surveillance'),
                                    'resolved' => __('Résolu'),
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$incident->status] ?? 'secondary' }}">
                                {{ $statusLabels[$incident->status] ?? $incident->status }}
                            </span>
                        </td>
                        <td>
                            @php
                                $severityColors = [
                                    'critical' => 'danger',
                                    'major' => 'warning',
                                    'minor' => 'info',
                                ];
                                $severityLabels = [
                                    'critical' => __('Critique'),
                                    'major' => __('Majeur'),
                                    'minor' => __('Mineur'),
                                ];
                            @endphp
                            <span class="badge bg-{{ $severityColors[$incident->severity] ?? 'secondary' }}">
                                {{ $severityLabels[$incident->severity] ?? $incident->severity }}
                            </span>
                        </td>
                        <td>{{ $incident->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.health.incidents.edit', $incident) }}"
                                   class="btn btn-sm btn-outline-primary" title="{{ __('Modifier') }}">
                                    <i data-lucide="pencil"></i>
                                </a>
                                <form action="{{ route('admin.health.incidents.destroy', $incident) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('{{ __('Êtes-vous sûr de vouloir supprimer cet incident ?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Supprimer') }}">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">{{ __('Aucun incident enregistré') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($incidents->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $incidents->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
