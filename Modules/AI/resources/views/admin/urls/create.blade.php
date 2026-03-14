<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Ajouter une source URL - Base de connaissances IA'))
@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item"><span>{{ __('IA') }}</span></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ai.knowledge.index') }}">{{ __('Base de connaissances') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ai.urls.index') }}">{{ __('Sources URL') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Ajouter') }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 d-flex align-items-center gap-2">
            <i data-lucide="link" class="icon-md text-primary"></i>
            {{ __('Ajouter une source URL') }}
        </h4>
        <a href="{{ route('admin.ai.urls.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> {{ __('Retour') }}
        </a>
    </div>

    <div class="card"
         x-data="{
             checking: false,
             robotsResult: null,
             robotsAllowed: null,
             checkRobots() {
                 const urlInput = document.getElementById('url');
                 const targetUrl = urlInput ? urlInput.value : '';
                 if (!targetUrl) return;
                 this.checking = true;
                 this.robotsResult = null;
                 fetch('{{ route('admin.ai.urls.check-robots') }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                         'Accept': 'application/json'
                     },
                     body: JSON.stringify({ url: targetUrl })
                 })
                 .then(r => r.json())
                 .then(data => {
                     this.robotsAllowed = data.allowed;
                     this.robotsResult = data.message;
                 })
                 .catch(() => {
                     this.robotsResult = '{{ __('Erreur lors de la vérification.') }}';
                     this.robotsAllowed = null;
                 })
                 .finally(() => { this.checking = false; });
             }
         }">
        <div class="card-body">
            <form action="{{ route('admin.ai.urls.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="url" class="form-label">{{ __('URL') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="url"
                               class="form-control @error('url') is-invalid @enderror"
                               id="url"
                               name="url"
                               value="{{ old('url') }}"
                               required
                               maxlength="500"
                               placeholder="https://exemple.com">
                        <button type="button"
                                class="btn btn-outline-secondary"
                                :disabled="checking"
                                @click="checkRobots()">
                            <span x-show="checking" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <i data-lucide="shield-check" x-show="!checking"></i>
                            {{ __('Vérifier robots.txt') }}
                        </button>
                        @error('url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mt-2" x-show="robotsResult !== null" x-cloak>
                        <span class="badge"
                              :class="robotsAllowed ? 'bg-success' : 'bg-danger'"
                              x-text="robotsAllowed ? '{{ __('Autorisé') }}' : '{{ __('Bloqué par robots.txt') }}'"></span>
                        <span class="text-muted small ms-2" x-text="robotsResult"></span>
                    </div>
                    <div class="form-text">{{ __('L\'URL racine du site à indexer (ex: https://monsite.com).') }}</div>
                </div>

                <div class="mb-3">
                    <label for="label" class="form-label">{{ __('Label') }} <span class="text-danger">*</span></label>
                    <input type="text"
                           class="form-control @error('label') is-invalid @enderror"
                           id="label"
                           name="label"
                           value="{{ old('label') }}"
                           required
                           maxlength="255"
                           placeholder="{{ __('Ex: Site institutionnel, Documentation produit...') }}">
                    @error('label')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="hidden_source_name" class="form-label">{{ __('Nom source confidentiel') }}</label>
                    <input type="text"
                           class="form-control @error('hidden_source_name') is-invalid @enderror"
                           id="hidden_source_name"
                           name="hidden_source_name"
                           value="{{ old('hidden_source_name') }}"
                           maxlength="255"
                           placeholder="{{ __('Ex: Base interne, Référentiel RH...') }}">
                    <div class="form-text">
                        <i data-lucide="eye-off" class="icon-xs me-1"></i>
                        {{ __('Ce nom ne sera JAMAIS mentionné par l\'assistant IA. Il sert uniquement à l\'organisation interne.') }}
                    </div>
                    @error('hidden_source_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="max_pages" class="form-label">{{ __('Nombre de pages max') }} <span class="text-danger">*</span></label>
                        <input type="number"
                               class="form-control @error('max_pages') is-invalid @enderror"
                               id="max_pages"
                               name="max_pages"
                               value="{{ old('max_pages', 50) }}"
                               required
                               min="1"
                               max="200">
                        <div class="form-text">{{ __('Entre 1 et 200 pages indexées par scraping.') }}</div>
                        @error('max_pages')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="scrape_frequency" class="form-label">{{ __('Fréquence de scraping') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('scrape_frequency') is-invalid @enderror"
                                id="scrape_frequency"
                                name="scrape_frequency"
                                required>
                            <option value="manual"  @selected(old('scrape_frequency') === 'manual')>{{ __('Manuel') }}</option>
                            <option value="daily"   @selected(old('scrape_frequency', 'weekly') === 'daily')>{{ __('Quotidien') }}</option>
                            <option value="weekly"  @selected(old('scrape_frequency', 'weekly') === 'weekly')>{{ __('Hebdomadaire') }}</option>
                            <option value="monthly" @selected(old('scrape_frequency') === 'monthly')>{{ __('Mensuel') }}</option>
                        </select>
                        @error('scrape_frequency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">{{ __('Source active') }}</label>
                    </div>
                    <div class="form-text">{{ __('Une source inactive ne sera pas incluse dans les scrapings automatiques.') }}</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i> {{ __('Créer la source URL') }}
                    </button>
                    <a href="{{ route('admin.ai.urls.index') }}" class="btn btn-outline-secondary">
                        {{ __('Annuler') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
