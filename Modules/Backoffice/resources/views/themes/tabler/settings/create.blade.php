@extends('backoffice::layouts.admin', ['title' => 'Paramètres', 'subtitle' => 'Ajouter'])

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Ajouter un paramètre</h3></div>
    <div class="card-body">
        <form action="{{ route('admin.settings.store') }}" method="POST">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">Clé</label>
                    <input type="text" name="key" class="form-control @error('key') is-invalid @enderror" value="{{ old('key') }}" required>
                    @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Groupe</label>
                    <input type="text" name="group" class="form-control @error('group') is-invalid @enderror" value="{{ old('group', 'general') }}">
                    @error('group') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror">
                        <option value="text" {{ old('type') == 'text' ? 'selected' : '' }}>Texte</option>
                        <option value="boolean" {{ old('type') == 'boolean' ? 'selected' : '' }}>Booléen</option>
                        <option value="integer" {{ old('type') == 'integer' ? 'selected' : '' }}>Entier</option>
                        <option value="json" {{ old('type') == 'json' ? 'selected' : '' }}>JSON</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Valeur</label>
                    <input type="text" name="value" class="form-control @error('value') is-invalid @enderror" value="{{ old('value') }}">
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
