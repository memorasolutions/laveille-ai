<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0"><i data-lucide="search" class="me-2"></i>Assistant SEO</h5>
    </div>

    <div class="card-body">
        <div class="mb-3">
            <label for="seoTitleInput" class="form-label">Titre de l'article</label>
            <input type="text" class="form-control" id="seoTitleInput" wire:model="title" placeholder="Titre de l'article">
        </div>

        <div class="mb-4">
            <label for="seoContentInput" class="form-label">Contenu de l'article</label>
            <textarea class="form-control" id="seoContentInput" rows="4" wire:model="content" placeholder="Contenu de l'article"></textarea>
        </div>

        <button class="btn btn-primary" wire:click="generate" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="generate">Générer le SEO</span>
            <span wire:loading wire:target="generate">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Génération...
            </span>
        </button>

        @if(!empty($seoResult))
        <div class="mt-4 pt-4 border-top">
            <h6 class="mb-3">Résultats SEO générés</h6>

            <div class="mb-3">
                <label class="form-label fw-semibold">Title SEO</label>
                <input type="text" class="form-control" value="{{ $seoResult['title'] ?? '' }}" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Meta description</label>
                <textarea class="form-control" rows="2" readonly>{{ $seoResult['description'] ?? '' }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Keywords</label>
                <input type="text" class="form-control" value="{{ $seoResult['keywords'] ?? '' }}" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">OG Title</label>
                <input type="text" class="form-control" value="{{ $seoResult['og_title'] ?? '' }}" readonly>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">OG Description</label>
                <textarea class="form-control" rows="2" readonly>{{ $seoResult['og_description'] ?? '' }}</textarea>
            </div>

            <button class="btn btn-outline-secondary" wire:click="clear">
                <i data-lucide="x" class="me-1"></i>Effacer
            </button>
        </div>
        @endif
    </div>
</div>
