<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Modifier workflow', 'subtitle' => 'Marketing'])

@section('content')
<form method="POST" action="{{ route('admin.newsletter.workflows.update', $workflow) }}" id="workflowForm">
    @csrf @method('PUT')
    <div class="row gy-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <h6 class="mb-0">Modifier : {{ $workflow->name }}</h6>
                    <a href="{{ route('admin.newsletter.workflows.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                        <i data-lucide="arrow-left"></i> Retour
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $workflow->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Déclencheur <span class="text-danger">*</span></label>
                            <select name="trigger_type" class="form-select @error('trigger_type') is-invalid @enderror" required>
                                @foreach(['signup' => 'Inscription', 'purchase' => 'Achat', 'custom_event' => 'Événement custom', 'date_based' => 'Date', 'manual' => 'Manuel'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('trigger_type', $workflow->trigger_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('trigger_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                @foreach(['draft' => 'Brouillon', 'active' => 'Actif', 'paused' => 'Pause', 'archived' => 'Archivé'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', $workflow->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description', $workflow->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Étapes du workflow</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addStep()">
                        <i data-lucide="plus"></i> Ajouter une étape
                    </button>
                </div>
                <div class="card-body">
                    <div id="stepsContainer"></div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-3">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('admin.newsletter.workflows.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Informations</h6></div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="text-muted text-sm">Créé par</dt>
                        <dd>{{ $workflow->creator?->name ?? '-' }}</dd>
                        <dt class="text-muted text-sm">Créé le</dt>
                        <dd>{{ $workflow->created_at->format('d/m/Y H:i') }}</dd>
                        <dt class="text-muted text-sm">Modifié le</dt>
                        <dd class="mb-0">{{ $workflow->updated_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</form>

@push('custom-scripts')
<script>
let stepIndex = 0;
const templates = @json($templates);
const existingSteps = @json($workflow->steps);

function addStep(type = 'send_email', config = {}, templateId = null) {
    const container = document.getElementById('stepsContainer');
    const div = document.createElement('div');
    div.className = 'card mb-2 step-item';

    let templateOptions = '<option value="">-- Sélectionner --</option>';
    templates.forEach(t => {
        const selected = templateId == t.id ? 'selected' : '';
        templateOptions += `<option value="${t.id}" ${selected}>${t.name}</option>`;
    });

    div.innerHTML = `
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i data-lucide="grip-vertical" class="text-muted" style="cursor:grab;width:16px;height:16px"></i>
                <span class="badge bg-primary">${stepIndex + 1}</span>
                <select name="steps[${stepIndex}][type]" class="form-select form-select-sm" style="width:auto">
                    <option value="send_email" ${type === 'send_email' ? 'selected' : ''}>Envoyer email</option>
                    <option value="delay" ${type === 'delay' ? 'selected' : ''}>Délai</option>
                    <option value="condition" ${type === 'condition' ? 'selected' : ''}>Condition</option>
                    <option value="action" ${type === 'action' ? 'selected' : ''}>Action</option>
                </select>
                <button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="this.closest('.step-item').remove()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div>
                ${type === 'send_email' ? `<select name="steps[${stepIndex}][template_id]" class="form-select form-select-sm">${templateOptions}</select>` : ''}
                ${type === 'delay' ? `<div class="input-group input-group-sm"><input type="number" name="steps[${stepIndex}][config][delay_hours]" class="form-control" value="${config.delay_hours || 24}" min="1"><span class="input-group-text">heures</span></div>` : ''}
                ${type === 'condition' ? `<select name="steps[${stepIndex}][config][condition_type]" class="form-select form-select-sm"><option value="is_active" ${config.condition_type === 'is_active' ? 'selected' : ''}>Abonné actif</option><option value="is_confirmed" ${config.condition_type === 'is_confirmed' ? 'selected' : ''}>Abonné confirmé</option></select>` : ''}
                ${type === 'action' ? `<input type="text" name="steps[${stepIndex}][config][action_type]" class="form-control form-control-sm" placeholder="Type d'action" value="${config.action_type || ''}">` : ''}
            </div>
        </div>
    `;

    container.appendChild(div);
    stepIndex++;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// Load existing steps
existingSteps.forEach(step => addStep(step.type, step.config || {}, step.template_id));
</script>
@endpush
@endsection
