<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Déclencheurs proactifs'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item">{{ __('Intelligence artificielle') }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Déclencheurs proactifs') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="zap" class="icon-md text-primary"></i>{{ __('Déclencheurs proactifs') }}
    </h4>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> {{ __('Nouveau déclencheur') }}
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Nom') }}</th>
                    <th>{{ __('Événement') }}</th>
                    <th>{{ __('Message') }}</th>
                    <th>{{ __('Délai') }}</th>
                    <th>{{ __('Actif') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $eventColors = [
                        'page_view' => 'info',
                        'idle' => 'warning',
                        'scroll_bottom' => 'success',
                        'cart_abandon' => 'danger',
                        'first_visit' => 'primary',
                    ];
                    $eventLabels = [
                        'page_view' => __('Affichage page'),
                        'idle' => __('Inactivité'),
                        'scroll_bottom' => __('Défilement bas'),
                        'cart_abandon' => __('Panier abandonné'),
                        'first_visit' => __('Première visite'),
                    ];
                @endphp
                @forelse($triggers as $trigger)
                <tr>
                    <td>{{ $trigger->name }}</td>
                    <td>
                        <span class="badge bg-{{ $eventColors[$trigger->event_type] ?? 'secondary' }}">
                            {{ $eventLabels[$trigger->event_type] ?? $trigger->event_type }}
                        </span>
                    </td>
                    <td title="{{ $trigger->message }}">{{ Str::limit($trigger->message, 50) }}</td>
                    <td>{{ $trigger->delay_seconds }}s</td>
                    <td>
                        <form action="{{ route('admin.ai.proactive-triggers.toggle', $trigger) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="badge bg-{{ $trigger->is_active ? 'success' : 'secondary' }} border-0" style="cursor:pointer;">
                                {{ $trigger->is_active ? __('Actif') : __('Inactif') }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="{{ $trigger->id }}"
                                data-name="{{ $trigger->name }}"
                                data-event-type="{{ $trigger->event_type }}"
                                data-message="{{ $trigger->message }}"
                                data-delay-seconds="{{ $trigger->delay_seconds }}"
                                data-is-active="{{ $trigger->is_active ? '1' : '0' }}">
                                <i data-lucide="edit-2" style="width:14px;height:14px;"></i>
                            </button>
                            <form action="{{ route('admin.ai.proactive-triggers.destroy', $trigger) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Êtes-vous sûr ?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Aucun déclencheur configuré.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.ai.proactive-triggers.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Nouveau déclencheur') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nom') }} *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type d\'événement') }} *</label>
                        <select class="form-select" name="event_type" required>
                            <option value="page_view">{{ __('Affichage page') }}</option>
                            <option value="idle">{{ __('Inactivité') }}</option>
                            <option value="scroll_bottom">{{ __('Défilement bas') }}</option>
                            <option value="cart_abandon">{{ __('Panier abandonné') }}</option>
                            <option value="first_visit">{{ __('Première visite') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Message') }} *</label>
                        <textarea class="form-control" name="message" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Délai (secondes)') }}</label>
                        <input type="number" class="form-control" name="delay_seconds" min="0" value="0">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="create_is_active" checked>
                        <label class="form-check-label" for="create_is_active">{{ __('Actif') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Créer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Modifier le déclencheur') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nom') }} *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type d\'événement') }} *</label>
                        <select class="form-select" id="edit_event_type" name="event_type" required>
                            <option value="page_view">{{ __('Affichage page') }}</option>
                            <option value="idle">{{ __('Inactivité') }}</option>
                            <option value="scroll_bottom">{{ __('Défilement bas') }}</option>
                            <option value="cart_abandon">{{ __('Panier abandonné') }}</option>
                            <option value="first_visit">{{ __('Première visite') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Message') }} *</label>
                        <textarea class="form-control" id="edit_message" name="message" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Délai (secondes)') }}</label>
                        <input type="number" class="form-control" id="edit_delay_seconds" name="delay_seconds" min="0">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active">
                        <label class="form-check-label" for="edit_is_active">{{ __('Actif') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Enregistrer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('editModal').addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    var form = document.getElementById('editForm');
    form.action = '{{ route("admin.ai.proactive-triggers.update", ":id") }}'.replace(':id', btn.dataset.id);
    document.getElementById('edit_name').value = btn.dataset.name;
    document.getElementById('edit_event_type').value = btn.dataset.eventType;
    document.getElementById('edit_message').value = btn.dataset.message;
    document.getElementById('edit_delay_seconds').value = btn.dataset.delaySeconds;
    document.getElementById('edit_is_active').checked = btn.dataset.isActive === '1';
});
</script>
@endpush
@endsection
