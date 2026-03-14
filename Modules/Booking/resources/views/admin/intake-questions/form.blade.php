<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title')
    @isset($intakeQuestion)
        Modifier la question
    @else
        Nouvelle question
    @endisset
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        @isset($intakeQuestion)
                            Modifier la question
                        @else
                            Nouvelle question pour {{ $service->name }}
                        @endisset
                    </h5>
                </div>
                <form method="POST"
                    action="@isset($intakeQuestion){{ route('admin.booking.intake-questions.update', $intakeQuestion) }}@else{{ route('admin.booking.intake-questions.store', $service) }}@endisset">
                    @csrf
                    @isset($intakeQuestion)
                        @method('PUT')
                    @endisset

                    <div class="card-body">
                        <div class="mb-3">
                            <label for="label" class="form-label">Libellé *</label>
                            <input type="text" class="form-control @error('label') is-invalid @enderror" id="label" name="label" value="{{ old('label', $intakeQuestion->label ?? '') }}" required>
                            @error('label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Type de champ *</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">Sélectionnez</option>
                                @foreach(['text' => 'Texte court', 'textarea' => 'Zone de texte', 'select' => 'Liste déroulante', 'checkbox' => 'Case à cocher', 'radio' => 'Boutons radio'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ old('type', $intakeQuestion->type ?? '') == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" id="options-field" style="display: none;">
                            <label for="options" class="form-label">Options (une par ligne)</label>
                            <textarea class="form-control @error('options') is-invalid @enderror" id="options" name="options" rows="4" placeholder="Option 1&#10;Option 2">{{ old('options', isset($intakeQuestion) && is_array($intakeQuestion->options) ? implode("\n", $intakeQuestion->options) : '') }}</textarea>
                            @error('options')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_required" name="is_required" value="1" {{ old('is_required', $intakeQuestion->is_required ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_required">Champ obligatoire</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Ordre d'affichage</label>
                            <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $intakeQuestion->sort_order ?? 0) }}" min="0">
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('admin.booking.intake-questions.index', $service) }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const optionsField = document.getElementById('options-field');
    function toggle() {
        optionsField.style.display = ['select','checkbox','radio'].includes(typeSelect.value) ? 'block' : 'none';
    }
    toggle();
    typeSelect.addEventListener('change', toggle);
});
</script>
@endpush
@endsection
