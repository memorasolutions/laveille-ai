@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Modifier : ' . $definition->name])

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <h4 class="mb-3 mb-md-0">Modifier : {{ $definition->name }}</h4>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('admin.custom-fields.update', $definition) }}" method="POST" x-data="{ fieldType: '{{ old('type', $definition->type) }}' }">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $definition->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="key" class="form-label">Clé</label>
                        <input type="text" name="key" id="key" class="form-control @error('key') is-invalid @enderror" value="{{ old('key', $definition->key) }}">
                        @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required x-model="fieldType">
                                @foreach(\Modules\CustomFields\Models\CustomFieldDefinition::TYPES as $t)
                                    <option value="{{ $t }}" {{ old('type', $definition->type) === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="model_type" class="form-label">Modèle <span class="text-danger">*</span></label>
                            <select name="model_type" id="model_type" class="form-select @error('model_type') is-invalid @enderror" required>
                                @foreach(\Modules\CustomFields\Models\CustomFieldDefinition::MODEL_TYPES as $key => $label)
                                    <option value="{{ $key }}" {{ old('model_type', $definition->model_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('model_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" name="description" id="description" class="form-control" value="{{ old('description', $definition->description) }}">
                    </div>

                    <div class="mb-3">
                        <label for="placeholder" class="form-label">Placeholder</label>
                        <input type="text" name="placeholder" id="placeholder" class="form-control" value="{{ old('placeholder', $definition->placeholder) }}">
                    </div>

                    <div class="mb-3">
                        <label for="default_value" class="form-label">Valeur par défaut</label>
                        <input type="text" name="default_value" id="default_value" class="form-control" value="{{ old('default_value', $definition->default_value) }}">
                    </div>

                    <div class="mb-3" x-show="['select','radio','checkbox'].includes(fieldType)" x-cloak>
                        <label for="options" class="form-label">Options</label>
                        <input type="text" name="options" id="options" class="form-control" value="{{ old('options', is_array($definition->options) ? implode(', ', $definition->options) : $definition->options) }}">
                        <div class="form-text">Séparez les options par des virgules.</div>
                    </div>

                    <div class="mb-3">
                        <label for="validation_rules" class="form-label">Règles de validation</label>
                        <input type="text" name="validation_rules" id="validation_rules" class="form-control" value="{{ old('validation_rules', $definition->validation_rules) }}" placeholder="ex: min:3|max:255">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" name="is_required" id="is_required" value="1" {{ old('is_required', $definition->is_required) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_required">Requis</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" {{ old('is_active', $definition->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Actif</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('admin.custom-fields.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
