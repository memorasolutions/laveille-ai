<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Modifier le document - Base de connaissances IA'))
@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item"><span>{{ __('IA') }}</span></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ai.knowledge.index') }}">{{ __('Base de connaissances') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Modifier') }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 d-flex align-items-center gap-2">
            <i data-lucide="file-edit" class="icon-md text-primary"></i>
            {{ __('Modifier le document') }}
        </h4>
        <a href="{{ route('admin.ai.knowledge.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> {{ __('Retour') }}
        </a>
    </div>

    @if($document->chunks_count !== null || $document->last_synced_at)
    <div class="row mb-4">
        @if($document->chunks_count !== null)
        <div class="col-auto">
            <div class="d-flex align-items-center gap-2 text-muted small">
                <i data-lucide="layers" class="icon-sm"></i>
                <span>{{ $document->loadCount('chunks')->chunks_count ?? 0 }} {{ __('chunk(s) indexé(s)') }}</span>
            </div>
        </div>
        @endif
        @if($document->last_synced_at)
        <div class="col-auto">
            <div class="d-flex align-items-center gap-2 text-muted small">
                <i data-lucide="clock" class="icon-sm"></i>
                <span>{{ __('Dernière sync :') }} {{ $document->last_synced_at->diffForHumans() }}</span>
            </div>
        </div>
        @endif
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.ai.knowledge.update', $document) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">{{ __('Titre') }} <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('title') is-invalid @enderror"
                               id="title"
                               name="title"
                               value="{{ old('title', $document->title) }}"
                               required
                               maxlength="255">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="source_type" class="form-label">{{ __('Type de source') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('source_type') is-invalid @enderror" id="source_type" name="source_type" required>
                            <option value="manual"  @selected(old('source_type', $document->source_type) === 'manual')>{{ __('Manuel') }}</option>
                            <option value="faq"     @selected(old('source_type', $document->source_type) === 'faq')>{{ __('FAQ') }}</option>
                            <option value="page"    @selected(old('source_type', $document->source_type) === 'page')>{{ __('Page') }}</option>
                            <option value="article" @selected(old('source_type', $document->source_type) === 'article')>{{ __('Article') }}</option>
                            <option value="service" @selected(old('source_type', $document->source_type) === 'service')>{{ __('Service') }}</option>
                        </select>
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
                              required>{{ old('content', $document->content) }}</textarea>
                    <div class="form-text">{{ __('Modifier le contenu recréera automatiquement tous les chunks d\'indexation.') }}</div>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $document->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">{{ __('Document actif') }}</label>
                    </div>
                    <div class="form-text">{{ __('Un document inactif ne sera pas utilisé par le chatbot.') }}</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i> {{ __('Enregistrer') }}
                    </button>
                    <a href="{{ route('admin.ai.knowledge.index') }}" class="btn btn-outline-secondary">
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
            <p class="text-muted small mb-3">{{ __('La suppression est définitive. Tous les chunks associés à ce document seront également supprimés.') }}</p>
            <form action="{{ route('admin.ai.knowledge.destroy', $document) }}" method="POST" x-data>
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-outline-danger btn-sm"
                        @click="$dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js(__('Supprimer définitivement ce document et tous ses chunks ?')), action: () => $el.closest('form').submit() })">
                    <i data-lucide="trash-2"></i> {{ __('Supprimer ce document') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
