<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Répertoire techno') . ' - ' . config('app.name'))
@section('meta_description', __('Les meilleurs outils techno, testés et sélectionnés pour vous. ChatGPT, Claude, Midjourney, Perplexity et plus.'))

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
            'showUrl' => route('directory.show', $tool->slug),
            'websiteType' => $tool->website_type ?? 'website',
            'launchYear' => $tool->launch_year ?? 0,
            'avgRating' => round($tool->averageRating(), 1),
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
    .rt-featured { color: #F59E0B; position: absolute; top: 12px; right: 12px; }
    .rt-stars { color: #F59E0B; font-size: 13px; font-weight: 700; }

    .row-flex { display: flex; flex-wrap: wrap; }
    .row-flex > [class*='col-'] { display: flex; flex-direction: column; margin-bottom: 24px; }
    .rt-empty { text-align: center; padding: 60px 20px; background: #F9FAFB; border-radius: var(--r-base); }
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div x-data="{
    search: '',
    activePricing: '',
    activeCategory: '',
    sortBy: 'all',
    tools: {{ $toolsJson->toJson() }},

    get filteredTools() {
        const s = this.search.toLowerCase();
        let t = this.tools.filter(t => {
            const matchSearch = !s || t.name.toLowerCase().includes(s) || t.shortDesc.toLowerCase().includes(s);
            const matchPricing = !this.activePricing || t.pricing === this.activePricing;
            const matchCat = !this.activeCategory || t.categorySlugs.includes(this.activeCategory);
            return matchSearch && matchPricing && matchCat;
        });
        if (this.sortBy === 'rating') return [...t].sort((a,b) => b.avgRating - a.avgRating);
        if (this.sortBy === 'newest') return [...t].sort((a,b) => b.launchYear - a.launchYear);
        return t;
    },

    togglePricing(p) { this.activePricing = this.activePricing === p ? '' : p; },
    toggleCategory(c) { this.activeCategory = this.activeCategory === c ? '' : c; },
    setSort(s) {
        if (s === 'free') { this.activePricing = 'free'; this.sortBy = 'all'; }
        else { this.sortBy = s; if (this.activePricing === 'free') this.activePricing = ''; }
    },
    resetAll() { this.search = ''; this.activePricing = ''; this.activeCategory = ''; this.sortBy = 'all'; }
}">

    {{-- Hero --}}
    <div class="rt-hero">
        <div class="container text-center">
            <h1>{{ __('Répertoire techno') }}</h1>
            <p style="color: #6B7280; font-size: 1.1rem; margin-bottom: 24px;">
                <strong x-text="tools.length" style="color: var(--c-primary);"></strong> {{ __('outils testés et sélectionnés pour vous.') }}
            </p>
            <div class="rt-search">
                <svg class="rt-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" class="rt-search-input" x-model="search"
                       placeholder="{{ __('Rechercher un outil, une catégorie...') }}"
                       aria-label="{{ __('Rechercher un outil') }}">
            </div>
        </div>
    </div>

    <div class="container" style="padding-top: 30px; padding-bottom: 40px;">

        {{-- Pricing filters --}}
        <div style="display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-bottom: 16px;">
            <button type="button" class="rt-pill" :class="{ active: !activePricing }" @click="activePricing = ''">{{ __('Tous') }}</button>
            @foreach($pricingOptions as $key => $label)
                <button type="button" class="rt-pill" :class="{ active: activePricing === '{{ $key }}' }" @click="togglePricing('{{ $key }}')">
                    {{ $pricingEmojis[$key] ?? '' }} {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Category filters --}}
        @if($catCount > 1)
        <div style="display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-bottom: 20px;">
            @foreach($categories as $cat)
                <button type="button" class="rt-pill" style="font-size: 13px; padding: 5px 12px;"
                        :class="{ active: activeCategory === '{{ $cat->slug }}' }"
                        @click="toggleCategory('{{ $cat->slug }}')">{{ $cat->name }}</button>
            @endforeach
        </div>
        @endif

        {{-- Sort tabs --}}
        <div class="rt-sort-bar">
            <button type="button" class="rt-sort-tab" :class="sortBy === 'all' && activePricing !== 'free' && 'rt-sort-active'" @click="setSort('all')">{{ __('Tous') }}</button>
            <button type="button" class="rt-sort-tab" :class="sortBy === 'rating' && 'rt-sort-active'" @click="setSort('rating')">⭐ {{ __('Populaires') }}</button>
            <button type="button" class="rt-sort-tab" :class="sortBy === 'newest' && 'rt-sort-active'" @click="setSort('newest')">🆕 {{ __('Récents') }}</button>
            <button type="button" class="rt-sort-tab" :class="activePricing === 'free' && 'rt-sort-active'" @click="setSort('free')">🆓 {{ __('Gratuits') }}</button>
            <span style="margin-left: auto; color: #9CA3AF; font-size: 0.85rem; align-self: center;"><strong x-text="filteredTools.length" style="color: var(--c-primary);"></strong> {{ __('outils') }}</span>
        </div>

        {{-- Ad: directory top --}}
        @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
            {!! app(\Modules\Ads\Services\AdsRenderer::class)->render('directory-top') !!}
        @endif

        {{-- Grid --}}
        <div class="row row-flex">
            <template x-for="tool in filteredTools" :key="tool.id">
                <div class="col-lg-4 col-md-6 col-xs-12">
                    <article class="rt-card">
                        <template x-if="tool.isFeatured"><span class="rt-featured" title="{{ __('Mis en avant') }}">★</span></template>

                        <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
                            <template x-if="tool.favicon"><img :src="tool.favicon" alt="" class="rt-logo" loading="lazy" width="48" height="48"></template>
                            <div>
                                <h3 class="rt-card-name"><a :href="tool.showUrl" x-text="tool.name"></a></h3>
                                <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                    <span class="rt-badge" :class="'badge-' + tool.pricing" x-text="tool.pricingLabel"></span>
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
    "description": "{{ __('Les meilleurs outils techno, testés et sélectionnés pour vous.') }}",
    "url": "{{ route('directory.index') }}"
}
</script>
@endpush
