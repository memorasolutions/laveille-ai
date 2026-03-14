<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Nouveau champ personnalisé'))

@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.custom-fields.index') }}">{{ __('Champs personnalisés') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Nouveau champ') }}</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <h4 class="mb-3 mb-md-0">{{ __('Nouveau champ personnalisé') }}</h4>
</div>

<form action="{{ route('admin.custom-fields.store') }}" method="POST" x-data="{ fieldType: '{{ old('type', '') }}' }">
    @csrf
    <div class="row">
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Nom') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="key" class="form-label">{{ __('Clé') }}</label>
                        <input type="text" name="key" id="key" class="form-control @error('key') is-invalid @enderror" value="{{ old('key') }}" placeholder="{{ __('Généré automatiquement si vide') }}">
                        <div class="form-text">{{ __('Identifiant unique pour accéder à la valeur dans le code.') }}</div>
                        @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required x-model="fieldType">
                                <option value="">{{ __('Choisir...') }}</option>
                                @foreach(\Modules\CustomFields\Models\CustomFieldDefinition::TYPES as $t)
                                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="model_type" class="form-label">{{ __('Modèle') }} <span class="text-danger">*</span></label>
                            <select name="model_type" id="model_type" class="form-select @error('model_type') is-invalid @enderror" required>
                                <option value="">{{ __('Choisir...') }}</option>
                                @foreach(\Modules\CustomFields\Models\CustomFieldDefinition::MODEL_TYPES as $key => $label)
                                    <option value="{{ $key }}" {{ old('model_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('model_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description') }}</label>
                        <input type="text" name="description" id="description" class="form-control" value="{{ old('description') }}">
                        <div class="form-text">{{ __('Texte d\'aide affiché sous le champ.') }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="placeholder" class="form-label">{{ __('Placeholder') }}</label>
                        <input type="text" name="placeholder" id="placeholder" class="form-control" value="{{ old('placeholder') }}">
                    </div>

                    <div class="mb-3">
                        <label for="default_value" class="form-label">{{ __('Valeur par défaut') }}</label>
                        <input type="text" name="default_value" id="default_value" class="form-control" value="{{ old('default_value') }}">
                    </div>

                    <div class="mb-3" x-show="['select','radio','checkbox','repeater'].includes(fieldType)" x-cloak>
                        <label for="options" class="form-label">{{ __('Options') }}</label>
                        <input type="text" name="options" id="options" class="form-control" value="{{ old('options') }}">
                        <div class="form-text" x-show="fieldType !== 'repeater'">{{ __('Séparez les options par des virgules (ex: Rouge, Vert, Bleu).') }}</div>
                        <div class="form-text" x-show="fieldType === 'repeater'" x-cloak>{{ __('Noms des sous-champs séparés par des virgules (ex: Nom, Email, Téléphone).') }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="validation_rules" class="form-label">{{ __('Règles de validation') }}</label>
                        <input type="text" name="validation_rules" id="validation_rules" class="form-control" value="{{ old('validation_rules') }}" placeholder="ex: min:3|max:255">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" name="is_required" id="is_required" value="1" {{ old('is_required') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_required">{{ __('Requis') }}</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">{{ __('Actif') }}</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Créer') }}</button>
                        <a href="{{ route('admin.custom-fields.index') }}" class="btn btn-outline-secondary">{{ __('Annuler') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
