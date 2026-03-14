@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Créer un incident'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
    <li class="breadcrumb-item">{{ __('Santé') }}</li>
    <li class="breadcrumb-item"><a href="{{ route('admin.health.incidents.index') }}">{{ __('Incidents') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Nouveau') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('Créer un nouvel incident') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.health.incidents.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="title" class="form-label">{{ __('Titre') }} *</label>
                <input type="text"
                       class="form-control @error('title') is-invalid @enderror"
                       id="title"
                       name="title"
                       value="{{ old('title') }}"
                       required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description"
                          name="description"
                          rows="4">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">{{ __('Statut') }}</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="investigating" {{ old('status') == 'investigating' ? 'selected' : '' }}>{{ __('Investigation') }}</option>
                            <option value="identified" {{ old('status') == 'identified' ? 'selected' : '' }}>{{ __('Identifié') }}</option>
                            <option value="monitoring" {{ old('status') == 'monitoring' ? 'selected' : '' }}>{{ __('Surveillance') }}</option>
                            <option value="resolved" {{ old('status') == 'resolved' ? 'selected' : '' }}>{{ __('Résolu') }}</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="severity" class="form-label">{{ __('Sévérité') }}</label>
                        <select class="form-select @error('severity') is-invalid @enderror" id="severity" name="severity">
                            <option value="minor" {{ old('severity', 'minor') == 'minor' ? 'selected' : '' }}>{{ __('Mineur') }}</option>
                            <option value="major" {{ old('severity') == 'major' ? 'selected' : '' }}>{{ __('Majeur') }}</option>
                            <option value="critical" {{ old('severity') == 'critical' ? 'selected' : '' }}>{{ __('Critique') }}</option>
                        </select>
                        @error('severity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ route('admin.health.incidents.index') }}" class="btn btn-secondary me-2">{{ __('Annuler') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Créer l\'incident') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
