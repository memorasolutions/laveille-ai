<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Répertoire techno') . ' - ' . config('app.name'))
@section('meta_description', __('Les meilleurs outils techno sélectionnés pour vous. ChatGPT, Claude, Midjourney, Perplexity et plus.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Répertoire techno')])
@endsection

@php
    $toolsJson = $tools->map(function($tool) use ($pricingOptions) {
        $host = $tool->url ? parse_url($tool->url, PHP_URL_HOST) : '';
        return [
            'id' => $tool->id,
            'name' => $tool->name,
            'slug' => $tool->slug,
            'shortDesc' => $tool->short_description ?? '',
            'url' => $tool->url,
            'pricing' => $tool->pricing,
            'pricingLabel' => $pricingOptions[$tool->pricing] ?? ucfirst($tool->pricing),
            'isFeatured' => (bool) $tool->is_featured,
            'categories' => $tool->categories->pluck('name')->toArray(),
            'categorySlugs' => $tool->categories->pluck('slug')->toArray(),
            'favicon' => $host ? "https://www.google.com/s2/favicons?domain={$host}&sz=64" : '',
            'screenshot' => $tool->screenshot ? (str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot)) : '',
            'showUrl' => route('directory.show', $tool->slug),
            'websiteType' => $tool->website_type ?? 'website',
            'launchYear' => $tool->launch_year ?? 0,
            'createdTs' => $tool->created_at ? $tool->created_at->timestamp : 0,
            'avgRating' => round($tool->averageRating(), 1),
            'gradientFrom' => ['#0B7285','#1a365d','#8E44AD','#E67E22','#2ECC71','#E74C3C','#3498DB','#F39C12'][crc32($tool->name) % 8 < 0 ? (crc32($tool->name) % 8) + 8 : crc32($tool->name) % 8],
            'gradientTo' => ['#1a365d','#0B7285','#2C3E50','#C0392B','#16A085','#8E44AD','#2980B9','#D35400'][crc32($tool->name) % 8 < 0 ? (crc32($tool->name) % 8) + 8 : crc32($tool->name) % 8],
            'hasEduPricing' => (bool) $tool->has_education_pricing,
        ];
    })->values();

    $pricingEmojis = ['free' => '🆓', 'freemium' => '💎', 'paid' => '💰', 'open_source' => '🔓', 'enterprise' => '🏢'];
    $catCount = $categories->count();
@endphp

@push('styles')
<style>
    .rt-hero { background: linear-gradient(135deg, #fff 0%, #F0F4F8 100%); padding: 40px 0 30px; border-bottom: 1px solid #E5E7EB; }
    .rt-hero h1 { font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin-bottom: 10px; }
    .rt-search { position: relative; max-width: 600px; margin: 0 auto; }
    .rt-search-input { width: 100%; padding: 14px 20px 14px 48px; border-radius: var(--r-btn); border: 2px solid #E5E7EB; font-size: 17px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); outline: none; background: #fff; }
    .rt-search-input:focus { border-color: var(--c-primary); box-shadow: 0 4px 15px rgba(11,114,133,0.1); }
    .rt-search-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #9CA3AF; width: 20px; height: 20px; }

    .rt-pill { display: inline-flex; align-items: center; gap: 4px; padding: 7px 16px; border-radius: var(--r-btn); background: #F3F4F6; color: var(--c-dark); font-weight: 600; font-size: 14px; border: none; cursor: pointer; transition: all 0.2s; }
    .rt-pill:hover { background: #E5E7EB; }
    .rt-pill.active { background: var(--c-primary); color: #fff; }

    .rt-sort-bar { display: flex; border-bottom: 1px solid #E5E7EB; margin-bottom: 20px; }
    .rt-sort-tab { padding: 10px 16px; font-weight: 600; font-size: 0.9rem; color: #6B7280; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s; background: none; border-top: none; border-left: none; border-right: none; }
    .rt-sort-tab:hover { color: var(--c-dark); }
    .rt-sort-active { color: var(--c-primary) !important; border-bottom-color: var(--c-primary) !important; }

    .rt-card { background: #fff; border-radius: var(--r-base); padding: 24px; height: 100%; display: flex; flex-direction: column; border: 1px solid #E5E7EB; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: transform 0.25s, box-shadow 0.25s; position: relative; }
    .rt-card:hover { transform: translateY(-4px); box-shadow: 0 12px 25px -5px rgba(0,0,0,0.1); }
    .rt-logo { width: 48px; height: 48px; border-radius: 12px; background: #f9fafb; padding: 3px; border: 1px solid #e5e7eb; flex-shrink: 0; }
    .rt-card-name { font-family: var(--f-heading); font-size: 1.1rem; font-weight: 700; color: var(--c-dark); margin: 0 0 4px; }
    .rt-card-name a { color: inherit; text-decoration: none; }
    .rt-card-name a:hover { color: var(--c-primary); }
    .rt-badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-free { background: #D1FAE5; color: #065F46; }
    .badge-freemium { background: #DBEAFE; color: #1E40AF; }
    .badge-paid { background: #FEF3C7; color: #92400E; }
    .badge-open_source { background: #CCFBF1; color: #115E59; }
    .badge-enterprise { background: #EDE9FE; color: #5B21B6; }
    .rt-desc { color: #4B5563; font-size: 14px; line-height: 1.6; margin-bottom: 14px; flex-grow: 1; }
    .rt-tag { font-size: 12px; color: #6b7280; background: #f3f4f6; padding: 2px 8px; border-radius: 4px; }
    .rt-actions { display: flex; gap: 8px; margin-top: auto; padding-top: 14px; border-top: 1px solid #F3F4F6; align-items: center; }
    .rt-btn-visit { background: var(--c-accent); color: #fff !important; border: none; padding: 7px 16px; border-radius: var(--r-btn); font-weight: 600; text-decoration: none !important; font-size: 13px; transition: opacity 0.2s; }
    .rt-btn-visit:hover { opacity: 0.9; color: #fff; }
    .rt-btn-details { color: var(--c-dark); font-weight: 600; font-size: 13px; text-decoration: none; }
    .rt-btn-details:hover { color: var(--c-primary); }
    .rt-featured { position: absolute; top: 12px; right: 12px; background: #0B7285; color: #fff; font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 12px; z-index: 2; letter-spacing: 0.3px; }
    .rt-stars { color: #F59E0B; font-size: 13px; font-weight: 700; }

    .row-flex { display: flex; flex-wrap: wrap; }
    .row-flex > [class*='col-'] { display: flex; flex-direction: column; margin-bottom: 24px; }
    .rt-empty { text-align: center; padding: 60px 20px; background: #F9FAFB; border-radius: var(--r-base); }
    [x-cloak] { display: none !important; }

    /* Highlights section - slider */
    .rt-highlights { padding: 30px 0 10px; }
    .rt-hl-section { margin-bottom: 8px; }
    .rt-hl-title { font-family: var(--f-heading); font-size: 1.15rem; font-weight: 700; color: var(--c-dark); margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }
    .rt-hl-slider { position: relative; display: flex; align-items: center; }
    .rt-hl-track { display: flex; gap: 16px; overflow-x: auto; scroll-behavior: smooth; scrollbar-width: none; -ms-overflow-style: none; flex: 1; padding: 4px 0; }
    .rt-hl-track::-webkit-scrollbar { display: none; }
    .rt-hl-card { display: block; flex: 0 0 196px; background: #fff; border-radius: var(--r-base); border: 1px solid #E5E7EB; overflow: hidden; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; }
    .rt-hl-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); text-decoration: none; }
    .rt-hl-img { height: 100px; overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center; }
    .rt-hl-img img { width: 100%; height: 100%; object-fit: cover; }
    .rt-hl-img-text { color: #fff; font-weight: 700; font-size: 14px; text-shadow: 0 1px 3px rgba(0,0,0,0.3); }
    .rt-hl-body { padding: 10px 12px; }
    .rt-hl-name { font-weight: 700; font-size: 13px; color: var(--c-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
    .rt-hl-arrow { flex-shrink: 0; width: 36px; height: 36px; border-radius: 50%; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; cursor: pointer; border: none; color: var(--c-dark); font-size: 14px; margin: 0 4px; }
    .rt-hl-arrow:hover { background: var(--c-primary); color: #fff; }

    /* Filter bar + pricing dropdown */
    .rt-filter-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
    .rt-filter-count { color: #9CA3AF; font-size: 0.85rem; margin-left: auto; }
    .rt-pricing-dropdown { position: absolute; top: 100%; left: 0; z-index: 50; background: #fff; border: 1px solid #E5E7EB; border-radius: var(--r-base); box-shadow: 0 8px 25px rgba(0,0,0,0.1); padding: 6px; min-width: 180px; margin-top: 4px; }
    .rt-pricing-dropdown button { display: block; width: 100%; text-align: left; padding: 8px 12px; border: none; background: none; cursor: pointer; font-size: 14px; border-radius: 4px; color: var(--c-dark); }
    .rt-pricing-dropdown button:hover { background: #F3F4F6; }
    .rt-pricing-dropdown button.active { background: var(--c-primary); color: #fff; }

    /* Category slider styles: voir partials/_category_slider.blade.php */
</style>
@endpush

@section('content')
<div x-data="{
    search: '',
    activePricing: '',
    activeCategory: '',
    eduFilter: false,
    sortBy: 'all',
    tools: {{ $toolsJson->toJson() }},

    get filteredTools() {
        const s = this.search.toLowerCase();
        let t = this.tools.filter(t => {
            const matchSearch = !s || t.name.toLowerCase().includes(s) || t.shortDesc.toLowerCase().includes(s);
            const matchPricing = !this.activePricing || t.pricing === this.activePricing;
            const matchCat = !this.activeCategory || t.categorySlugs.includes(this.activeCategory);
            const matchEdu = !this.eduFilter || t.hasEduPricing;
            return matchSearch && matchPricing && matchCat && matchEdu;
        });
        if (this.sortBy === 'rating') return [...t].sort((a,b) => b.avgRating - a.avgRating);
        if (this.sortBy === 'newest') return [...t].sort((a,b) => b.createdTs - a.createdTs);
        return t;
    },

    togglePricing(p) { this.activePricing = this.activePricing === p ? '' : p; },
    toggleCategory(c) { this.activeCategory = this.activeCategory === c ? '' : c; },
    setSort(s) {
        if (s === 'free') { this.activePricing = 'free'; this.sortBy = 'all'; }
        else { this.sortBy = s; if (this.activePricing === 'free') this.activePricing = ''; }
    },
    resetAll() { this.search = ''; this.activePricing = ''; this.activeCategory = ''; this.eduFilter = false; this.sortBy = 'all'; }
}">

    {{-- Hero + wizard wrapper --}}
    <div x-data="{
        wStep: 0, submitted: false, scraping: false, submitting: false,
        scrapeError: '', duplicates: [],
        toolUrl: '', toolName: '', toolDesc: '', toolShortDesc: '', toolPricing: '', screenshotUrl: '',
        selectedCollections: [], newCollectionName: '',
        authEmail: '', authCode: '', authSending: false, authVerifying: false, authSent: false, authError: '',
        async analyzeUrl() {
            if (!this.toolUrl || this.scraping) return;
            this.scraping = true; this.scrapeError = ''; this.duplicates = [];
            try {
                const res = await fetch('{{ route('directory.scrape-detect') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ url: this.toolUrl })
                });
                if (!res.ok) { const e = await res.json(); throw new Error(e.error || '{{ __('Erreur lors de l analyse.') }}'); }
                const d = await res.json();
                this.toolName = d.translated_name || d.original_name || '';
                this.toolDesc = d.translated_description || d.original_description || '';
                this.toolShortDesc = (d.translated_description || '').substring(0, 200);
                this.screenshotUrl = d.screenshot || '';
                if (d.duplicates && d.duplicates.length > 0) { this.duplicates = d.duplicates; }
                else { this.wStep = 2; }
            } catch(e) { this.scrapeError = e.message; }
            finally { this.scraping = false; }
        },
        async submitTool() {
            if (!this.toolName || !this.toolPricing || this.submitting) return;
            this.submitting = true; this.scrapeError = '';
            try {
                const res = await fetch('{{ route('directory.submit') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ url: this.toolUrl, name: this.toolName, description: this.toolDesc, short_description: this.toolShortDesc, pricing: this.toolPricing, screenshot: this.screenshotUrl, collection_ids: this.selectedCollections, new_collection_name: this.newCollectionName })
                });
                const d = await res.json();
                if (d.auth_required) { this.wStep = 3; this.authError = ''; }
                else if (d.success) { this.submitted = true; this.wStep = 0; }
                else { this.scrapeError = d.message || '{{ __('Erreur lors de la soumission.') }}'; }
            } catch(e) { this.scrapeError = '{{ __('Erreur réseau.') }}'; }
            finally { this.submitting = false; }
        },
        async sendMagicLink() {
            if (!this.authEmail || this.authSending) return;
            this.authSending = true; this.authError = '';
            try {
                const res = await fetch('{{ route('magic-link.api.send') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.authEmail })
                });
                const d = await res.json();
                if (d.success) { this.authSent = true; }
                else { this.authError = d.message; }
            } catch(e) { this.authError = '{{ __('Erreur réseau.') }}'; }
            finally { this.authSending = false; }
        },
        async verifyCode() {
            if (!this.authCode || this.authCode.length !== 6 || this.authVerifying) return;
            this.authVerifying = true; this.authError = '';
            try {
                const res = await fetch('{{ route('magic-link.api.verify') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.authEmail, token: this.authCode })
                });
                const d = await res.json();
                if (d.success) { this.submitTool(); }
                else { this.authError = d.message; }
            } catch(e) { this.authError = '{{ __('Erreur réseau.') }}'; }
            finally { this.authVerifying = false; }
        },
        resetWizard() { this.wStep = 0; this.toolUrl = ''; this.toolName = ''; this.toolDesc = ''; this.toolShortDesc = ''; this.toolPricing = ''; this.screenshotUrl = ''; this.duplicates = []; this.scrapeError = ''; this.selectedCollections = []; this.newCollectionName = ''; this.authEmail = ''; this.authCode = ''; this.authSent = false; this.authError = ''; }
    }">
    <div class="rt-hero">
        <div class="container text-center">
            <h1>{{ __('Répertoire techno') }}</h1>
            <p style="color: #6B7280; font-size: 1.1rem; margin-bottom: 16px;">
                <strong x-text="tools.length" style="color: var(--c-primary);"></strong> {{ __('outils sélectionnés pour vous.') }}
            </p>

            {{-- Bouton proposer (visible etape 0) — auth requise --}}
            <div x-show="wStep === 0 && !submitted" style="margin-bottom: 20px;">
                @auth
                <button type="button" @click="wStep = 1"
                    style="background: var(--c-primary); color: #fff; font-weight: 600; padding: 10px 24px; border-radius: var(--r-btn); border: none; cursor: pointer; font-size: 14px; transition: all 0.2s;"
                    onmouseover="this.style.background='var(--c-dark)'" onmouseout="this.style.background='var(--c-primary)'">
                    + {{ __('Proposer un outil') }}
                </button>
                @else
                <a href="{{ route('login') }}" @click.prevent="$dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour proposer un outil') }}' })"
                    style="display: inline-block; background: var(--c-primary); color: #fff; font-weight: 600; padding: 10px 24px; border-radius: var(--r-btn); cursor: pointer; font-size: 14px; text-decoration: none; transition: all 0.2s;"
                    onmouseover="this.style.background='var(--c-dark)'" onmouseout="this.style.background='var(--c-primary)'">
                    + {{ __('Proposer un outil') }}
                </a>
                @endauth
            </div>

            {{-- Succes --}}
            <div x-show="submitted" x-cloak x-transition style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <span style="background: #D1FAE5; color: #065F46; padding: 10px 24px; border-radius: var(--r-btn); font-size: 14px; font-weight: 600;">
                    ✓ {{ __('Merci ! L\'outil a été ajouté au répertoire.') }}
                </span>
                <button type="button" @click="submitted=false;wStep=1;toolUrl='';toolName='';toolDesc='';toolShortDesc='';toolPricing='';screenshotUrl='';scrapeError='';duplicates=[]" style="padding:8px 20px;background:#fff;color:var(--c-primary);border:2px solid var(--c-primary);border-radius:var(--r-btn);font-weight:600;cursor:pointer;font-size:13px;">{{ __('Soumettre un autre outil') }}</button>
            </div>

            {{-- Etape 1 : URL --}}
            <div x-show="wStep === 1" x-cloak x-transition.duration.300ms
                 style="margin-bottom: 20px; background: rgba(11,114,133,0.08); border: 2px solid var(--c-primary); border-radius: var(--r-base); padding: 24px; max-width: 600px; margin-left: auto; margin-right: auto;">
                <div style="font-size: 11px; color: #6B7280; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">{{ __('Étape 1 sur 2 – URL du site') }}</div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input type="url" x-model="toolUrl" placeholder="https://chatgpt.com" aria-label="{{ __('URL du site a proposer') }}"
                        style="flex: 1; min-width: 240px; height: 44px; padding: 0 14px; border: 2px solid #E5E7EB; border-radius: var(--r-base); font-size: 15px; background: #fff; color: var(--c-dark); outline: none;"
                        @keydown.enter="analyzeUrl()">
                    <button type="button" @click="analyzeUrl()" :disabled="scraping || !toolUrl"
                        :style="'height:44px;padding:0 20px;background:var(--c-primary);color:#fff;font-weight:700;border:none;border-radius:var(--r-btn);cursor:pointer;font-size:14px;white-space:nowrap;transition:all 0.2s;' + (scraping || !toolUrl ? 'opacity:0.5;cursor:not-allowed;' : '')">
                        <span x-show="!scraping">{{ __('Analyser') }} →</span>
                        <span x-show="scraping" x-data="{dots:''}" x-init="setInterval(()=>{dots=dots.length>=3?'':dots+'.'},400)" style="display:inline-flex;align-items:center;gap:6px"><span style="width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;display:inline-block;animation:wsp .6s linear infinite"></span><span>{{ __('Analyse en cours') }}<span x-text="dots" style="display:inline-block;width:1.2em;text-align:left"></span></span></span>
                        <style>@keyframes wsp{to{transform:rotate(360deg)}}</style>
                    </button>
                </div>

                {{-- Doublons detectes --}}
                <template x-if="duplicates.length > 0">
                    <div style="margin-top: 14px; background: #FEF3C7; border: 1px solid #F59E0B; border-radius: var(--r-base); padding: 14px; text-align: left;">
                        <strong style="color: #92400E;">⚠️ {{ __('Cet outil semble déjà dans notre répertoire :') }}</strong>
                        <template x-for="dup in duplicates" :key="dup.id">
                            <div style="margin-top: 6px;">
                                <a :href="'/annuaire/' + dup.slug" target="_blank" style="color: #92400E; font-weight: 600;" x-text="dup.name"></a>
                                <span style="font-size: 12px; color: #B45309;" x-text="'(' + dup.confidence + ')'"></span>
                            </div>
                        </template>
                        <button type="button" @click="duplicates = []; wStep = 2;" style="margin-top: 10px; background: #F59E0B; color: #fff; border: none; padding: 6px 16px; border-radius: var(--r-btn); font-weight: 600; font-size: 13px; cursor: pointer;">
                            {{ __('Proposer quand même') }}
                        </button>
                    </div>
                </template>

                {{-- Erreur --}}
                <div x-show="scrapeError && duplicates.length === 0" x-cloak style="margin-top: 10px; color: #DC2626; font-size: 13px;" x-text="scrapeError"></div>

                <div style="text-align: right; margin-top: 8px;">
                    <button type="button" @click="resetWizard()" style="background: none; border: none; color: #9CA3AF; cursor: pointer; font-size: 12px;">{{ __('Annuler') }}</button>
                </div>
            </div>

            <div class="rt-search" x-show="wStep === 0">
                <svg class="rt-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" class="rt-search-input" x-model="search"
                       placeholder="{{ __('Rechercher un outil, une catégorie...') }}"
                       aria-label="{{ __('Rechercher un outil') }}">
            </div>
        </div>
    </div>

    {{-- Etape 2 : Details (card blanche sous hero) --}}
    <div x-show="wStep === 2" x-cloak x-transition.duration.400ms
         style="background: #fff; border: 2px solid #E5E7EB; border-radius: var(--r-base); padding: 28px; max-width: 800px; margin: -10px auto 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.06);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <span style="font-size: 11px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 1px;">{{ __('Étape 2 sur 2 – Détails') }}</span>
                <h3 style="font-family: var(--f-heading); color: var(--c-dark); margin: 4px 0 0; font-size: 16px;">
                    {{ __('Complétez les informations pour') }} <strong x-text="toolName" style="color: var(--c-primary);"></strong>
                </h3>
            </div>
            <button type="button" @click="wStep = 1" style="background: none; border: none; color: var(--c-primary); cursor: pointer; font-size: 13px; font-weight: 600;">← {{ __('Retour') }}</button>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Nom de l outil') }} <span style="color: #E74C3C;">*</span></label>
                    <input type="text" x-model="toolName" aria-label="{{ __('Nom de l outil') }}"
                        style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; outline: none; color: var(--c-dark);">
                </div>
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Description courte') }}</label>
                    <input type="text" x-model="toolShortDesc" maxlength="255" aria-label="{{ __('Description courte') }}"
                        style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; outline: none; color: var(--c-dark);">
                </div>
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Description') }}</label>
                    <textarea x-model="toolDesc" rows="4" aria-label="{{ __('Description complete') }}"
                        style="width: 100%; padding: 10px 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; outline: none; resize: vertical; color: var(--c-dark);"></textarea>
                </div>
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Modele economique') }} <span style="color: #E74C3C;">*</span></label>
                    <select x-model="toolPricing" aria-label="{{ __('Modele economique') }}"
                        style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; background: #fff; color: var(--c-dark);">
                        <option value="">{{ __('Choisir...') }}</option>
                        <option value="free">🆓 {{ __('Gratuit') }}</option>
                        <option value="freemium">💎 {{ __('Freemium') }}</option>
                        <option value="paid">💰 {{ __('Payant') }}</option>
                        <option value="open_source">🔓 {{ __('Open source') }}</option>
                        <option value="enterprise">🏢 {{ __('Entreprise') }}</option>
                    </select>
                </div>

                {{-- Ajouter à mes collections (optionnel) --}}
                <div style="margin-top: 6px; margin-bottom: 14px;">
                    <label style="display: block; font-weight: 600; color: var(--c-dark); font-size: 13px; margin-bottom: 4px;">
                        📂 {{ __('Ajouter à mes collections') }}
                        <span style="font-weight: 400; font-size: 12px; color: #6B7280;">({{ __('optionnel') }})</span>
                    </label>
                    <p style="font-size: 12px; color: #6B7280; margin: 0 0 10px 0; line-height: 1.4;">
                        {{ __('Classe cet outil dans une ou plusieurs de tes collections (privées par défaut).') }}
                    </p>
                    @auth
                        @if(isset($userCollections) && $userCollections->count() > 0)
                            <div style="max-height: 110px; overflow-y: auto; border: 1px solid #E5E7EB; border-radius: var(--r-base); padding: 8px 10px; background: #fff; margin-bottom: 10px;">
                                @foreach($userCollections as $collection)
                                    <label
                                        style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; margin: 3px 4px 3px 0; border-radius: 9999px; font-size: 12px; cursor: pointer; user-select: none; transition: all .15s;"
                                        :style="selectedCollections.includes({{ $collection->id }})
                                            ? 'background: rgba(11,114,133,0.12); border: 1px solid var(--c-primary); color: var(--c-primary); font-weight: 600;'
                                            : 'background: #F9FAFB; border: 1px solid #E5E7EB; color: #374151; font-weight: 400;'"
                                    >
                                        <input type="checkbox" value="{{ $collection->id }}" x-model.number="selectedCollections" style="display: none;">
                                        <span x-show="selectedCollections.includes({{ $collection->id }})" style="color: var(--c-primary); font-size: 13px;">✓</span>
                                        <span>{{ $collection->name }}</span>
                                        <span style="font-size: 10px; color: #9CA3AF; margin-left: 2px;" title="{{ $collection->is_public ? __('Publique') : __('Privée') }}">{{ $collection->is_public ? '🌐' : '🔒' }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    @endauth
                    <input type="text" x-model="newCollectionName" maxlength="100"
                        placeholder="{{ __('Ou créer une nouvelle collection privée...') }}"
                        aria-label="{{ __('Nom de la nouvelle collection') }}"
                        style="width: 100%; height: 38px; background: #fff; border: 1px solid #E5E7EB; padding: 0 12px; border-radius: var(--r-base); font-size: 13px; color: var(--c-dark); outline: none;">
                </div>
            </div>
            <div class="col-md-4 text-center">
                <template x-if="screenshotUrl">
                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Apercu') }}</label>
                        <img :src="screenshotUrl" :alt="toolName" loading="lazy" style="max-width: 100%; max-height: 200px; object-fit: cover; border-radius: var(--r-base); border: 1px solid #E5E7EB;">
                    </div>
                </template>
                <div style="font-size: 12px; color: #9CA3AF; margin-top: 8px;">
                    <span x-text="toolUrl" style="word-break: break-all;"></span>
                </div>
            </div>
        </div>

        {{-- Erreur --}}
        <div x-show="scrapeError" x-cloak style="margin-top: 10px; color: #DC2626; font-size: 13px;" x-text="scrapeError"></div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
            <button type="button" @click="resetWizard()" style="background: #F3F4F6; color: var(--c-dark); border: none; padding: 10px 20px; border-radius: var(--r-btn); font-weight: 600; font-size: 14px; cursor: pointer;">
                {{ __('Annuler') }}
            </button>
            <button type="button" @click="submitTool()" :disabled="!toolName || !toolPricing || submitting"
                :style="'padding:10px 24px;background:var(--c-primary);color:#fff;font-weight:700;border:none;border-radius:var(--r-btn);cursor:pointer;font-size:14px;transition:all 0.2s;' + (!toolName || !toolPricing || submitting ? 'opacity:0.5;cursor:not-allowed;' : '')">
                <span x-show="!submitting">{{ __('Soumettre la proposition') }}</span>
                <span x-show="submitting" x-data="{dots:''}" x-init="setInterval(()=>{dots=dots.length>=3?'':dots+'.'},400)" style="display:inline-flex;align-items:center;gap:6px"><span style="width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;display:inline-block;animation:wsp .6s linear infinite"></span><span>{{ __('Soumission en cours') }}<span x-text="dots" style="display:inline-block;width:1.2em;text-align:left"></span></span></span>
            </button>
        </div>
    </div>

    {{-- Etape 3 : Auth inline (expand) --}}
    <div x-show="wStep === 3" x-cloak x-transition.duration.400ms
         style="background: #fff; border: 2px solid var(--c-primary); border-radius: var(--r-base); padding: 28px; max-width: 500px; margin: -10px auto 24px; box-shadow: 0 4px 15px rgba(11,114,133,0.1);">
        <div style="text-align: center; margin-bottom: 16px;">
            <div style="font-size: 28px; margin-bottom: 8px;">🔐</div>
            <h3 style="font-family: var(--f-heading); color: var(--c-dark); margin: 0 0 4px; font-size: 16px;">{{ __('Connexion requise') }}</h3>
            <p style="color: #6B7280; font-size: 13px; margin: 0;">{{ __('Connectez-vous pour soumettre votre proposition. Votre formulaire est sauvegarde.') }}</p>
        </div>

        {{-- Email input --}}
        <div x-show="!authSent" style="margin-bottom: 14px;">
            <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Adresse courriel') }}</label>
            <div style="display: flex; gap: 8px;">
                <input type="email" x-model="authEmail" placeholder="vous@exemple.com" aria-label="{{ __('Adresse courriel') }}"
                    @keydown.enter="sendMagicLink()"
                    style="flex: 1; height: 42px; padding: 0 14px; border: 2px solid #E5E7EB; border-radius: var(--r-base); font-size: 15px; outline: none; color: var(--c-dark);">
                <button type="button" @click="sendMagicLink()" :disabled="authSending || !authEmail"
                    :style="'height:42px;padding:0 20px;background:var(--c-primary);color:#fff;font-weight:700;border:none;border-radius:var(--r-btn);cursor:pointer;font-size:14px;white-space:nowrap;' + (authSending || !authEmail ? 'opacity:0.5;cursor:not-allowed;' : '')">
                    <span x-show="!authSending">{{ __('Envoyer le code') }}</span>
                    <span x-show="authSending">⏳</span>
                </button>
            </div>
        </div>

        {{-- OTP code input --}}
        <div x-show="authSent" x-transition>
            <div style="background: #D1FAE5; color: #065F46; padding: 10px 14px; border-radius: var(--r-base); font-size: 13px; margin-bottom: 14px;">
                ✓ {{ __('Code envoyé à') }} <strong x-text="authEmail"></strong>. {{ __('Vérifiez vos courriels.') }}
            </div>
            <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Code a 6 chiffres') }}</label>
            <div style="display: flex; gap: 8px;">
                <input type="text" x-model="authCode" maxlength="6" placeholder="000000" aria-label="{{ __('Code de connexion') }}" autocomplete="one-time-code" inputmode="numeric"
                    @keydown.enter="verifyCode()" @input="if(authCode.length === 6) verifyCode()"
                    style="flex: 1; height: 42px; padding: 0 14px; border: 2px solid #E5E7EB; border-radius: var(--r-base); font-size: 20px; font-weight: 700; letter-spacing: 8px; text-align: center; outline: none; color: var(--c-dark);">
                <button type="button" @click="verifyCode()" :disabled="authVerifying || authCode.length !== 6"
                    :style="'height:42px;padding:0 20px;background:var(--c-primary);color:#fff;font-weight:700;border:none;border-radius:var(--r-btn);cursor:pointer;font-size:14px;white-space:nowrap;' + (authVerifying || authCode.length !== 6 ? 'opacity:0.5;cursor:not-allowed;' : '')">
                    <span x-show="!authVerifying">{{ __('Valider') }}</span>
                    <span x-show="authVerifying">⏳</span>
                </button>
            </div>
            <button type="button" @click="authSent = false; authCode = ''" style="background: none; border: none; color: var(--c-primary); cursor: pointer; font-size: 12px; margin-top: 8px;">{{ __('Renvoyer le code') }}</button>
        </div>

        {{-- Error --}}
        <div x-show="authError" x-cloak style="margin-top: 10px; color: #DC2626; font-size: 13px;" x-text="authError"></div>

        <div style="text-align: center; margin-top: 14px;">
            <button type="button" @click="wStep = 2; authError = ''" style="background: none; border: none; color: #9CA3AF; cursor: pointer; font-size: 12px;">← {{ __('Retour au formulaire') }}</button>
        </div>
    </div>
    </div>

    {{-- Highlights : recents + populaires (masqué quand recherche active) --}}
    <div class="container" x-show="!search" x-transition>
        @include('directory::public.partials._highlights')
    </div>

    <div class="container" style="padding-top: 30px; padding-bottom: 40px;">

        {{-- Filters bar : Tous + dropdown pricing + compteur --}}
        <div class="rt-filter-bar">
            <button type="button" class="rt-pill" :class="{ active: !activePricing && !activeCategory }" @click="resetAll()">{{ __('Tous') }}</button>

            <div x-data="{ open: false }" style="position: relative; display: inline-block;">
                <button type="button" class="rt-pill" :class="{ active: activePricing !== '' }" @click="open = !open">
                    <span x-show="!activePricing">💰 Tarification <i class="ti-angle-down"></i></span>
                    <span x-show="activePricing" x-cloak x-text="({free:'🆓 Gratuit',freemium:'💎 Freemium',paid:'💰 Payant',open_source:'🔓 Open source',enterprise:'🏢 Enterprise'})[activePricing]"></span>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak class="rt-pricing-dropdown">
                    @foreach($pricingOptions as $key => $label)
                        <button type="button" @click="togglePricing('{{ $key }}'); open = false"
                                :class="{ 'active': activePricing === '{{ $key }}' }">
                            {{ $pricingEmojis[$key] ?? '' }} {{ $label }}
                        </button>
                    @endforeach
                    <button type="button" @click="activePricing = ''; open = false" x-show="activePricing">
                        <i class="ti-close"></i> {{ __('Effacer') }}
                    </button>
                </div>
            </div>

            <span class="rt-filter-count"><strong x-text="filteredTools.length" style="color: var(--c-primary);"></strong> {{ __('outils') }}</span>
        </div>

        {{-- Category slider (partial réutilisable) --}}
        @include('directory::public.partials._category_slider', [
            'categories' => $categories,
            'currentRoute' => 'index',
            'activeSlug' => null,
        ])

        {{-- Bandeau résultats de recherche (visible seulement quand recherche active) --}}
        <div x-show="search" x-cloak x-transition style="background: var(--c-primary-light); border: 1px solid var(--c-primary); border-radius: var(--r-base); padding: 12px 20px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 15px; color: var(--c-dark);">
                    <strong x-text="filteredTools.length" style="color: var(--c-primary); font-size: 20px;"></strong>
                    <span x-text="filteredTools.length === 1 ? 'résultat pour' : 'résultats pour'"></span>
                    « <strong x-text="search" style="color: var(--c-primary);"></strong> »
                </span>
            </div>
            <button type="button" @click="search = ''" style="background: none; border: 1px solid var(--c-primary); color: var(--c-primary); padding: 4px 12px; border-radius: var(--r-btn); font-size: 13px; font-weight: 600; cursor: pointer;">
                <i class="ti-close" style="font-size: 10px;"></i> {{ __('Effacer') }}
            </button>
        </div>

        {{-- Sort tabs --}}
        <div class="rt-sort-bar">
            <button type="button" class="rt-sort-tab" :class="sortBy === 'all' && activePricing !== 'free' && 'rt-sort-active'" @click="setSort('all')">{{ __('Tous') }}</button>
            <button type="button" class="rt-sort-tab" :class="sortBy === 'rating' && 'rt-sort-active'" @click="setSort('rating')">⭐ {{ __('Populaires') }}</button>
            <button type="button" class="rt-sort-tab" :class="sortBy === 'newest' && 'rt-sort-active'" @click="setSort('newest')">🆕 {{ __('Récents') }}</button>
            <button type="button" class="rt-sort-tab" :class="activePricing === 'free' && 'rt-sort-active'" @click="setSort('free')">🆓 {{ __('Gratuits') }}</button>
            <button type="button" class="rt-sort-tab" :class="eduFilter && 'rt-sort-active'" @click="eduFilter = !eduFilter" :style="eduFilter ? 'background:#ecfdf5;color:#065f46;border-color:#065f46;' : ''">🎓 {{ __('Éducation') }}</button>
            @if($categories->isNotEmpty())
                <a href="{{ route('directory.compare', $categories->first()->slug) }}" class="rt-sort-tab" style="text-decoration:none!important;margin-left:auto;">📊 {{ __('Comparatifs') }}</a>
            @endif
        </div>

        {{-- Section mise de l'avant (masqué quand recherche active) --}}
        @if(isset($featuredTools) && $featuredTools->isNotEmpty())
        <div x-show="!search" x-transition style="background:linear-gradient(135deg,#f0fafb 0%,#e0f4f7 100%);border:1px solid #b2e0e6;border-radius:14px;padding:20px;margin-bottom:24px;">
            <div style="display:flex!important;justify-content:space-between!important;align-items:center!important;margin-bottom:14px;">
                <h3 style="font-family:var(--f-heading);font-weight:700;font-size:1.1rem;color:var(--c-dark);margin:0;">{{ __('En vedette') }}</h3>
                <span style="font-size:12px;color:#0B7285;font-weight:600;">{{ __('Sponsorise') }}</span>
            </div>
            <div style="display:flex!important;gap:14px;overflow-x:auto;padding-bottom:8px;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;">
                @foreach($featuredTools as $ft)
                @php $ftHost = $ft->url ? parse_url($ft->url, PHP_URL_HOST) : ''; @endphp
                <a href="{{ route('directory.show', $ft->slug) }}" style="flex-shrink:0;width:220px;scroll-snap-align:start;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;text-decoration:none!important;color:inherit;transition:transform .2s,box-shadow .2s;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                    <div style="display:flex!important;align-items:center!important;gap:10px;margin-bottom:10px;">
                        @if($ftHost)<img src="https://www.google.com/s2/favicons?domain={{ $ftHost }}&sz=32" alt="" width="24" height="24" loading="lazy" style="border-radius:4px;">@endif
                        <span style="font-weight:700;font-size:15px;">{{ $ft->name }}</span>
                    </div>
                    <p style="font-size:12px;color:#6b7280;margin:0 0 8px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ Str::limit($ft->short_description, 70) }}</p>
                    @if($ft->categories->isNotEmpty())
                        <span style="display:inline-block;background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:600;">{{ $ft->categories->first()->name }}</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Section trending : plus votés (masqué quand recherche active) --}}
        @if(isset($topVoted) && $topVoted->count() >= 5)
        <div style="margin-bottom:32px;" x-show="!search" x-transition>

            {{-- Plus votés par la communauté (minimum 5 outils votés pour afficher) --}}
            @if(isset($topVoted) && $topVoted->count() >= 5)
            <div>
                <div style="display:flex!important;justify-content:space-between!important;align-items:center!important;margin-bottom:12px;">
                    <h3 style="font-family:var(--f-heading);font-weight:700;font-size:1.1rem;color:var(--c-dark);margin:0;">🔥 {{ __('Les plus votés') }}</h3>
                </div>
                <div style="display:flex!important;gap:14px;overflow-x:auto;padding-bottom:8px;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;">
                    @foreach($topVoted as $tv)
                    @php $tvHost = $tv->url ? parse_url($tv->url, PHP_URL_HOST) : ''; @endphp
                    <a href="{{ route('directory.show', $tv->slug) }}" style="flex-shrink:0;width:200px;scroll-snap-align:start;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px;text-decoration:none!important;color:inherit;transition:transform .2s,box-shadow .2s;">
                        <div style="display:flex!important;align-items:center!important;gap:8px;margin-bottom:8px;">
                            @if($tvHost)<img src="https://www.google.com/s2/favicons?domain={{ $tvHost }}&sz=32" alt="" width="20" height="20" loading="lazy" style="border-radius:4px;">@endif
                            <span style="font-weight:700;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $tv->name }}</span>
                        </div>
                        <div style="display:flex!important;align-items:center!important;gap:4px;margin-bottom:6px;">
                            <span style="color:#ef4444;font-weight:700;font-size:13px;">👍 {{ $tv->community_votes_count }}</span>
                            <span style="color:#9ca3af;font-size:11px;">{{ __('votes') }}</span>
                        </div>
                        <p style="font-size:12px;color:#6b7280;margin:0;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ Str::limit($tv->short_description, 60) }}</p>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Ad: directory top --}}
        @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
            {!! app(\Modules\Ads\Services\AdsRenderer::class)->render('directory-top') !!}
        @endif

        {{-- Grid --}}
        <div class="row row-flex">
            <template x-for="tool in filteredTools" :key="tool.id">
                <div class="col-lg-4 col-md-6 col-xs-12">
                    <article class="rt-card">
                        <template x-if="tool.isFeatured"><span class="rt-featured">{{ __('En vedette') }}</span></template>

                        <a :href="tool.showUrl" style="display: block; margin: -24px -24px 12px; overflow: hidden; border-radius: var(--r-base) var(--r-base) 0 0; height: 140px; border-bottom: 1px solid #E5E7EB; position: relative;">
                            <template x-if="tool.screenshot">
                                <div style="position: relative; height: 140px;">
                                    <img :src="tool.screenshot" :alt="tool.name" loading="lazy" style="width: 100%; height: 140px; object-fit: cover; display: block;">
                                    <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.55) 100%); box-shadow: inset 0 0 0 1px rgba(0,0,0,0.08);"></div>
                                </div>
                            </template>
                            <template x-if="!tool.screenshot">
                                <div :style="'width:100%;height:140px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,' + tool.gradientFrom + ' 0%,' + tool.gradientTo + ' 100%);'">
                                    <div style="text-align: center; color: rgba(255,255,255,0.9);">
                                        <template x-if="tool.favicon"><img :src="tool.favicon" alt="" style="width: 40px; height: 40px; border-radius: 10px; margin-bottom: 6px; background: rgba(255,255,255,0.2); padding: 4px;" loading="lazy"></template>
                                        <div style="font-family: var(--f-heading); font-weight: 700; font-size: 1rem; text-shadow: 0 1px 3px rgba(0,0,0,0.3);" x-text="tool.name"></div>
                                    </div>
                                </div>
                            </template>
                        </a>

                        <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
                            <template x-if="tool.favicon"><img :src="tool.favicon" alt="" class="rt-logo" loading="lazy" width="48" height="48"></template>
                            <div>
                                <h3 class="rt-card-name"><a :href="tool.showUrl" x-text="tool.name"></a></h3>
                                <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                    <span class="rt-badge" :class="'badge-' + tool.pricing" x-text="tool.pricingLabel"></span>
                                    <template x-if="tool.hasEduPricing"><span style="background:#ecfdf5;color:#065f46;font-size:10px;padding:2px 8px;border-radius:4px;font-weight:600;">🎓 {{ __('Éducation') }}</span></template>
                                    <template x-if="tool.launchYear > 0"><span style="color: #9CA3AF; font-size: 0.75rem;" x-text="'🚀 ' + tool.launchYear"></span></template>
                                </div>
                            </div>
                        </div>

                        <p class="rt-desc" x-text="tool.shortDesc"></p>

                        <template x-if="tool.categories.length > 0">
                            <div style="margin-bottom: 12px;">
                                <template x-for="cat in tool.categories.slice(0,2)" :key="cat">
                                    <span class="rt-tag" x-text="'#' + cat"></span>
                                </template>
                            </div>
                        </template>

                        <div class="rt-actions">
                            <template x-if="tool.avgRating > 0"><span class="rt-stars">★ <span x-text="tool.avgRating"></span></span></template>
                            <a :href="tool.showUrl" class="rt-btn-details" :aria-label="'{{ __('Détails de') }} ' + tool.name">{{ __('Détails') }}</a>
                            <template x-if="tool.url"><a :href="tool.url" target="_blank" rel="noopener noreferrer nofollow" class="rt-btn-visit" style="margin-left: auto;">{{ __('Visiter') }} →</a></template>
                        </div>
                    </article>
                </div>
            </template>
        </div>

        {{-- Ad: directory bottom --}}
        @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
            {!! app(\Modules\Ads\Services\AdsRenderer::class)->render('directory-bottom') !!}
        @endif

        {{-- Empty --}}
        <div x-show="filteredTools.length === 0" x-cloak>
            <div class="rt-empty">
                <div style="font-size: 40px; margin-bottom: 10px;">🔍</div>
                <h3 style="font-family: var(--f-heading); color: var(--c-dark);">{{ __('Aucun outil trouvé') }}</h3>
                <p>{{ __('Essayez de modifier vos filtres.') }}</p>
                <button type="button" @click="resetAll()" class="btn" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn);">{{ __('Réinitialiser') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('Répertoire techno') }}",
    "description": "{{ __('Les meilleurs outils techno sélectionnés pour vous.') }}",
    "url": "{{ route('directory.index') }}"
}
</script>
@endpush
