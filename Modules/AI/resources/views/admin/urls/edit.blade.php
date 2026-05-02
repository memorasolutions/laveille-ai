<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Modifier la source URL - Base de connaissances IA'))
@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item"><span>{{ __('IA') }}</span></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ai.knowledge.index') }}">{{ __('Base de connaissances') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ai.urls.index') }}">{{ __('Sources URL') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Modifier') }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 d-flex align-items-center gap-2">
            <i data-lucide="link" class="icon-md text-primary"></i>
            {{ __('Modifier la source URL') }}
        </h4>
        <a href="{{ route('admin.ai.urls.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> {{ __('Retour') }}
        </a>
    </div>

    {{-- Infos de scraping --}}
    <div class="row mb-4 g-3">
        <div class="col-auto">
            <div class="d-flex align-items-center gap-2 text-muted small">
                <i data-lucide="file-text" class="icon-sm"></i>
                <span>{{ $documentsCount }} {{ __('document(s) indexé(s)') }}</span>
            </div>
        </div>
        @if($url->last_scraped_at)
        <div class="col-auto">
            <div class="d-flex align-items-center gap-2 text-muted small">
                <i data-lucide="clock" class="icon-sm"></i>
                <span>{{ __('Dernier scraping :') }} {{ $url->last_scraped_at->diffForHumans() }}</span>
            </div>
        </div>
        @endif
        <div class="col-auto">
            @php
                $statusBadge = match($url->scrape_status) {
                    'pending'        => 'bg-secondary',
                    'scraping'       => 'bg-info',
                    'completed'      => 'bg-success',
                    'failed'         => 'bg-danger',
                    'robots_blocked' => 'bg-warning text-dark',
                    default          => 'bg-secondary',
                };
                $statusLabel = match($url->scrape_status) {
                    'pending'        => __('En attente'),
                    'scraping'       => __('En cours'),
                    'completed'      => __('Terminé'),
                    'failed'         => __('Échec'),
                    'robots_blocked' => __('Robots bloqué'),
                    default          => $url->scrape_status,
                };
            @endphp
            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
        </div>
        <div class="col-auto">
            <form action="{{ route('admin.ai.urls.scrape', $url) }}" method="POST" class="d-inline" x-data>
                @csrf
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        @click="$dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js(__('Lancer le scraping de cette URL maintenant ?')), action: () => $el.closest('form').submit() })">
                    <i data-lucide="refresh-cw"></i> {{ __('Lancer le scraping') }}
                </button>
            </form>
        </div>
    </div>

    @if($url->scrape_error)
    <div class="alert alert-danger mb-4" role="alert">
        <i data-lucide="alert-circle" class="me-2"></i>
        <strong>{{ __('Erreur de scraping :') }}</strong> {{ $url->scrape_error }}
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i data-lucide="check-circle" class="me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i data-lucide="alert-circle" class="me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
    @endif

    <div class="card"
         x-data="{
             checking: false,
             robotsResult: null,
             robotsAllowed: {{ $url->robots_allowed ? 'true' : 'false' }},
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
                 })
                 .finally(() => { this.checking = false; });
             }
         }">
        <div class="card-body">
            <form action="{{ route('admin.ai.urls.update', $url) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="url" class="form-label">{{ __('URL') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="url"
                               class="form-control @error('url') is-invalid @enderror"
                               id="url"
                               name="url"
                               value="{{ old('url', $url->url) }}"
                               required
                               maxlength="500">
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
                    <div class="mt-2">
                        <span class="badge"
                              :class="robotsAllowed ? 'bg-success' : 'bg-danger'"
                              x-text="robotsAllowed ? '{{ __('Autorisé') }}' : '{{ __('Bloqué par robots.txt') }}'"></span>
                        <span class="text-muted small ms-2" x-show="robotsResult !== null" x-text="robotsResult"></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="label" class="form-label">{{ __('Label') }} <span class="text-danger">*</span></label>
                    <input type="text"
                           class="form-control @error('label') is-invalid @enderror"
                           id="label"
                           name="label"
                           value="{{ old('label', $url->label) }}"
                           required
                           maxlength="255">
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
                           value="{{ old('hidden_source_name', $url->hidden_source_name) }}"
                           maxlength="255">
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
                               value="{{ old('max_pages', $url->max_pages) }}"
                               required
                               min="1"
                               max="200">
                        <div class="form-text">{{ __('Pages indexées lors du dernier scraping :') }} {{ $url->pages_scraped }}</div>
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
                            <option value="manual"  @selected(old('scrape_frequency', $url->scrape_frequency) === 'manual')>{{ __('Manuel') }}</option>
                            <option value="daily"   @selected(old('scrape_frequency', $url->scrape_frequency) === 'daily')>{{ __('Quotidien') }}</option>
                            <option value="weekly"  @selected(old('scrape_frequency', $url->scrape_frequency) === 'weekly')>{{ __('Hebdomadaire') }}</option>
                            <option value="monthly" @selected(old('scrape_frequency', $url->scrape_frequency) === 'monthly')>{{ __('Mensuel') }}</option>
                        </select>
                        @error('scrape_frequency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $url->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">{{ __('Source active') }}</label>
                    </div>
                    <div class="form-text">{{ __('Une source inactive ne sera pas incluse dans les scrapings automatiques.') }}</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i> {{ __('Enregistrer') }}
                    </button>
                    <a href="{{ route('admin.ai.urls.index') }}" class="btn btn-outline-secondary">
                        {{ __('Annuler') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4 border-danger">
        <div class="card-body">
            <h6 class="text-danger fw-bold mb-3">
                <i data-lucide="alert-triangle" class="me-1"></i>{{ __('Zone de danger') }}
            </h6>
            <p class="text-muted small mb-3">{{ __('La suppression est définitive. Tous les documents indexés depuis cette URL seront également supprimés.') }}</p>
            <form action="{{ route('admin.ai.urls.destroy', $url) }}" method="POST" x-data>
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-outline-danger btn-sm"
                        @click="$dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js(__('Supprimer définitivement cette source URL et tous ses documents indexés ?')), action: () => $el.closest('form').submit() })">
                    <i data-lucide="trash-2"></i> {{ __('Supprimer cette source URL') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
