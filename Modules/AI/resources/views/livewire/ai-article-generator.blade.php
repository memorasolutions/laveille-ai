<div>
@if($enabled)
    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="openModal" aria-label="{{ __('Générer avec l\'IA') }}">
        <i data-lucide="wand-2" class="me-1"></i>{{ __('Générer avec l\'IA') }}
    </button>

    @if($showModal)
        <div class="modal-backdrop fade show" style="z-index: 9997;"></div>
        <div class="modal fade show d-block" style="z-index: 9999;" aria-modal="true" role="dialog" aria-labelledby="aiArticleModalLabel" @keydown.escape.window="$wire.closeModal()">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="aiArticleModalLabel">{{ __('Génération d\'article IA') }}</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeModal" aria-label="{{ __('Fermer') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="ai-topic" class="form-label fw-semibold">{{ __('Sujet de l\'article') }} <span class="text-danger">*</span></label>
                            <textarea id="ai-topic" class="form-control @error('topic') is-invalid @enderror" wire:model="topic" rows="2" placeholder="{{ __('Ex : Les meilleures pratiques Laravel en 2026') }}"></textarea>
                            @error('topic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="ai-tone" class="form-label">{{ __('Ton') }}</label>
                                <select id="ai-tone" class="form-select" wire:model="tone">
                                    <option value="professional">{{ __('Professionnel') }}</option>
                                    <option value="casual">{{ __('Décontracté') }}</option>
                                    <option value="creative">{{ __('Créatif') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="ai-length" class="form-label">{{ __('Longueur') }}</label>
                                <select id="ai-length" class="form-select" wire:model="length">
                                    <option value="short">{{ __('Court (~500 mots)') }}</option>
                                    <option value="medium">{{ __('Moyen (~1000 mots)') }}</option>
                                    <option value="long">{{ __('Long (~2000 mots)') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="ai-locale" class="form-label">{{ __('Langue') }}</label>
                                <select id="ai-locale" class="form-select" wire:model="locale">
                                    <option value="fr">{{ __('Français') }}</option>
                                    <option value="en">English</option>
                                </select>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary" wire:click="generate" @if($isGenerating) disabled @endif>
                            @if($isGenerating)
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                {{ __('Génération en cours...') }}
                            @else
                                <i data-lucide="wand-2" class="me-1"></i>
                                {{ __('Générer') }}
                            @endif
                        </button>

                        @if($error)
                            <div class="alert alert-danger mt-3" role="alert">{{ $error }}</div>
                        @endif

                        @if(!empty($generatedContent) && isset($generatedContent['title']))
                            <hr class="my-3">
                            <h6 class="mb-3 fw-semibold">{{ __('Résultats générés') }}</h6>

                            <div class="card mb-2 border">
                                <div class="card-body py-2 d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted fw-semibold">{{ __('Titre') }}</small>
                                        <p class="mb-0">{{ $generatedContent['title'] }}</p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success flex-shrink-0 ms-2" wire:click="applyField('title')">{{ __('Appliquer') }}</button>
                                </div>
                            </div>

                            <div class="card mb-2 border">
                                <div class="card-body py-2 d-flex justify-content-between align-items-start">
                                    <div class="overflow-hidden">
                                        <small class="text-muted fw-semibold">{{ __('Contenu') }}</small>
                                        <p class="mb-0 text-truncate" style="max-width: 500px;">{{ Str::limit(strip_tags($generatedContent['content'] ?? ''), 200) }}</p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success flex-shrink-0 ms-2" wire:click="applyField('content')">{{ __('Appliquer') }}</button>
                                </div>
                            </div>

                            <div class="card mb-2 border">
                                <div class="card-body py-2 d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted fw-semibold">{{ __('Extrait') }}</small>
                                        <p class="mb-0">{{ $generatedContent['excerpt'] ?? '' }}</p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success flex-shrink-0 ms-2" wire:click="applyField('excerpt')">{{ __('Appliquer') }}</button>
                                </div>
                            </div>

                            <div class="card mb-2 border">
                                <div class="card-body py-2 d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted fw-semibold">{{ __('Meta description') }}</small>
                                        <p class="mb-0">{{ $generatedContent['meta_description'] ?? '' }}</p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success flex-shrink-0 ms-2" wire:click="applyField('meta_description')">{{ __('Appliquer') }}</button>
                                </div>
                            </div>

                            <div class="card mb-3 border">
                                <div class="card-body py-2 d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted fw-semibold">{{ __('Tags') }}</small>
                                        <div class="mt-1">
                                            @foreach(($generatedContent['tags'] ?? []) as $tag)
                                                <span class="badge bg-primary bg-opacity-10 text-primary me-1">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success flex-shrink-0 ms-2" wire:click="applyField('tags')">{{ __('Appliquer') }}</button>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="button" class="btn btn-success" wire:click="applyAll">
                                    <i data-lucide="check-circle" class="me-1"></i>
                                    {{ __('Appliquer tout') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
</div>
