<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Politiques SLA'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item">{{ __('Intelligence artificielle') }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Politiques SLA') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="shield-check" class="icon-md text-primary"></i>{{ __('Politiques SLA') }}
    </h4>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createSlaModal">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> {{ __('Nouvelle politique') }}
    </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Nom') }}</th>
                    <th>{{ __('Priorité') }}</th>
                    <th>{{ __('Réponse initiale (h)') }}</th>
                    <th>{{ __('Résolution (h)') }}</th>
                    <th>{{ __('Actif') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slaPolicies as $sla)
                <tr>
                    <td>{{ $sla->name }}</td>
                    <td>
                        @php
                            $pColors = ['low' => 'light', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
                        @endphp
                        <span class="badge bg-{{ $pColors[$sla->priority->value] ?? 'secondary' }} {{ in_array($sla->priority->value, ['low', 'high']) ? 'text-dark' : '' }}">
                            {{ __($sla->priority->value) }}
                        </span>
                    </td>
                    <td>{{ $sla->first_response_hours }}</td>
                    <td>{{ $sla->resolution_hours }}</td>
                    <td>
                        @if($sla->is_active)
                        <span class="badge bg-success">{{ __('Oui') }}</span>
                        @else
                        <span class="badge bg-secondary">{{ __('Non') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSlaModal{{ $sla->id }}">
                            <i data-lucide="edit-2" style="width:14px;height:14px;"></i>
                        </button>
                        <form method="POST" action="{{ route('admin.ai.sla.destroy', $sla) }}" class="d-inline" onsubmit="return confirm('{{ __('Supprimer cette politique SLA ?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                {{-- Edit modal --}}
                <div class="modal fade" id="editSlaModal{{ $sla->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('admin.ai.sla.update', $sla) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Modifier la politique SLA') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Nom') }} *</label>
                                        <input type="text" class="form-control" name="name" value="{{ $sla->name }}" required maxlength="255">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Priorité') }} *</label>
                                        <select class="form-select" name="priority" required>
                                            @foreach(['low', 'medium', 'high', 'urgent'] as $p)
                                            <option value="{{ $p }}" {{ $sla->priority->value === $p ? 'selected' : '' }}>{{ __($p) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('Réponse initiale (heures)') }} *</label>
                                            <input type="number" class="form-control" name="first_response_hours" value="{{ $sla->first_response_hours }}" required min="1">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('Résolution (heures)') }} *</label>
                                            <input type="number" class="form-control" name="resolution_hours" value="{{ $sla->resolution_hours }}" required min="1">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                                    <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Aucune politique SLA.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Create modal --}}
<div class="modal fade" id="createSlaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.ai.sla.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Nouvelle politique SLA') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nom') }} *</label>
                        <input type="text" class="form-control" name="name" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Priorité') }} *</label>
                        <select class="form-select" name="priority" required>
                            <option value="low">{{ __('low') }}</option>
                            <option value="medium">{{ __('medium') }}</option>
                            <option value="high">{{ __('high') }}</option>
                            <option value="urgent">{{ __('urgent') }}</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Réponse initiale (heures)') }} *</label>
                            <input type="number" class="form-control" name="first_response_hours" required min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Résolution (heures)') }} *</label>
                            <input type="number" class="form-control" name="resolution_hours" required min="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Créer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
