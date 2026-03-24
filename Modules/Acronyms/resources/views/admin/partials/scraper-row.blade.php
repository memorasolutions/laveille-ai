{{-- Partial: auto-remplissage via MetaScraperService (Core) --}}
@if(class_exists(\Modules\Core\Services\MetaScraperService::class))
<div x-data="{
    scraping: false, scrapeError: '', logoPreview: '{{ old('logo_url', $acronym->logo_url ?? '') }}',
    async scrapeUrl() {
        const url = document.getElementById('website_url').value;
        if (!url || this.scraping) return;
        this.scraping = true; this.scrapeError = '';
        try {
            const res = await fetch('/api/scrape-meta', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ url })
            });
            if (!res.ok) throw new Error();
            const d = await res.json();
            const desc = document.getElementById('description');
            const logo = document.getElementById('logo_url');
            if (!desc.value.trim()) desc.value = d.og_description || d.description || '';
            if (!logo.value.trim()) { logo.value = d.og_image || d.favicon || ''; this.logoPreview = logo.value; }
        } catch { this.scrapeError = '{{ __('Impossible de recuperer les informations.') }}'; }
        finally { this.scraping = false; }
    }
}">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="website_url" class="form-label">{{ __('URL du site web') }}</label>
            <div class="input-group">
                <input type="url" class="form-control @error('website_url') is-invalid @enderror" id="website_url" name="website_url" value="{{ old('website_url', $acronym->website_url ?? '') }}" maxlength="500">
                <button class="btn btn-outline-secondary" type="button" @click="scrapeUrl()" :disabled="scraping">
                    <span x-show="scraping" x-cloak class="spinner-border spinner-border-sm" role="status"></span>
                    <span x-show="!scraping">{{ __('Auto-remplir') }}</span>
                </button>
            </div>
            @error('website_url') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            <small x-show="scrapeError" x-cloak x-text="scrapeError" class="text-danger"></small>
        </div>
        <div class="col-md-6 mb-3">
            <label for="logo_url" class="form-label">{{ __('URL du logo') }}</label>
            <div class="d-flex gap-2 align-items-start">
                <input type="url" class="form-control @error('logo_url') is-invalid @enderror" id="logo_url" name="logo_url" value="{{ old('logo_url', $acronym->logo_url ?? '') }}" maxlength="500">
                <template x-if="logoPreview">
                    <img :src="logoPreview" alt="Logo" class="rounded border" style="width: 38px; height: 38px; object-fit: contain;">
                </template>
            </div>
            @error('logo_url') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
@else
{{-- Fallback sans scraper --}}
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="website_url" class="form-label">{{ __('URL du site web') }}</label>
        <input type="url" class="form-control @error('website_url') is-invalid @enderror" id="website_url" name="website_url" value="{{ old('website_url', $acronym->website_url ?? '') }}" maxlength="500">
        @error('website_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="logo_url" class="form-label">{{ __('URL du logo') }}</label>
        <input type="url" class="form-control @error('logo_url') is-invalid @enderror" id="logo_url" name="logo_url" value="{{ old('logo_url', $acronym->logo_url ?? '') }}" maxlength="500">
        @error('logo_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
@endif
