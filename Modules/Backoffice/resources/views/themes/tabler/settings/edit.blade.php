@extends('backoffice::layouts.admin', ['title' => 'Paramètres', 'subtitle' => 'Modifier'])

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Modifier le paramètre : {{ $setting->key }}</h3></div>
    <div class="card-body">
        <form action="{{ route('admin.settings.update', $setting) }}" method="POST">
            @csrf @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">Clé</label>
                    <input type="text" name="key" class="form-control @error('key') is-invalid @enderror" value="{{ old('key', $setting->key) }}" required>
                    @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Groupe</label>
                    <input type="text" name="group" class="form-control @error('group') is-invalid @enderror" value="{{ old('group', $setting->group) }}">
                    @error('group') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror">
                        <option value="text" {{ old('type', $setting->type) == 'text' ? 'selected' : '' }}>Texte</option>
                        <option value="boolean" {{ old('type', $setting->type) == 'boolean' ? 'selected' : '' }}>Booléen</option>
                        <option value="integer" {{ old('type', $setting->type) == 'integer' ? 'selected' : '' }}>Entier</option>
                        <option value="json" {{ old('type', $setting->type) == 'json' ? 'selected' : '' }}>JSON</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Valeur</label>
                    <input type="text" name="value" class="form-control @error('value') is-invalid @enderror" value="{{ old('value', $setting->value) }}">
                    @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Enregistrer</button>
                <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-danger">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
