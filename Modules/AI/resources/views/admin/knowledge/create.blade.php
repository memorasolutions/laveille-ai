<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Ajouter un document - Base de connaissances IA'))
@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item"><span>{{ __('IA') }}</span></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ai.knowledge.index') }}">{{ __('Base de connaissances') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Ajouter') }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 d-flex align-items-center gap-2">
            <i data-lucide="file-plus" class="icon-md text-primary"></i>
            {{ __('Ajouter un document') }}
        </h4>
        <a href="{{ route('admin.ai.knowledge.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> {{ __('Retour') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.ai.knowledge.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">{{ __('Titre') }} <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('title') is-invalid @enderror"
                               id="title"
                               name="title"
                               value="{{ old('title') }}"
                               required
                               maxlength="255"
                               placeholder="{{ __('Ex: Politique de retour, Service de formation...') }}">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="source_type" class="form-label">{{ __('Type de source') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('source_type') is-invalid @enderror" id="source_type" name="source_type" required>
                            <option value="">{{ __('Choisir un type...') }}</option>
                            <option value="manual"  @selected(old('source_type') === 'manual')>{{ __('Manuel') }}</option>
                            <option value="service" @selected(old('source_type') === 'service')>{{ __('Service') }}</option>
                        </select>
                        <div class="form-text">{{ __('Les types FAQ, Page et Article sont synchronisés automatiquement depuis les modules correspondants.') }}</div>
                        @error('source_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">{{ __('Contenu') }} <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('content') is-invalid @enderror"
                              id="content"
                              name="content"
                              rows="10"
                              required
                              placeholder="{{ __('Rédigez ici le contenu du document. Il sera découpé en chunks et indexé pour le chatbot IA.') }}">{{ old('content') }}</textarea>
                    <div class="form-text">{{ __('Le contenu sera automatiquement découpé en segments (chunks) pour la recherche sémantique.') }}</div>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">{{ __('Document actif') }}</label>
                    </div>
                    <div class="form-text">{{ __('Un document inactif ne sera pas utilisé par le chatbot.') }}</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i> {{ __('Créer le document') }}
                    </button>
                    <a href="{{ route('admin.ai.knowledge.index') }}" class="btn btn-outline-secondary">
                        {{ __('Annuler') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
