<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Glossaire IA') . ' - ' . config('app.name'))
@section('meta_description', __('Comprendre les termes de l\'intelligence artificielle, simplement. Définitions, analogies et exemples concrets pour 20+ termes IA essentiels.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Glossaire IA')])
@endsection

@php
    $termsJson = $terms->map(function($term) {
        return [
            'id' => $term->id,
            'name' => $term->name,
            'slug' => $term->slug,
            'icon' => $term->icon,
            'acronymFull' => $term->acronym_full,
            'definition' => \Illuminate\Support\Str::limit(strip_tags($term->definition), 120),
            'fullDef' => strip_tags($term->definition),
            'analogy' => $term->analogy,
            'type' => $term->type,
            'typeName' => match($term->type) {
                'acronym' => __('Acronyme'),
                'ai_term' => __('Terme IA'),
                'explainer' => __('Vulgarisation'),
                default => __('Terme')
            },
            'difficulty' => $term->difficulty ?? 'beginner',
            'diffLabel' => match($term->difficulty ?? 'beginner') {
                'beginner' => __('Débutant'),
                'intermediate' => __('Intermédiaire'),
                'advanced' => __('Avancé'),
                default => __('Débutant')
            },
            'category' => $term->category?->name,
            'categoryIcon' => $term->category?->icon,
            'categoryColor' => $term->category?->color,
            'categorySlug' => $term->category ? \Illuminate\Support\Str::slug($term->category->name) : '',
            'firstLetter' => strtoupper(\Illuminate\Support\Str::substr($term->name, 0, 1)),
            'url' => route('dictionary.show', $term->slug),
            'heroImage' => dictionary_hero_image_url($term->hero_image, false),
            'heroImageWebp' => dictionary_hero_image_webp_url($term->hero_image),
        ];
    })->values();

    $categoriesForFilter = $categories->unique(fn($c) => (string) $c->name)->map(fn($c) => [
        'id' => $c->id, // requis par :key="cat.id" du x-for (sinon clés undefined → « Duplicate key on x-for »)
        // ->unique(name) : garde-fou anti-doublons d'affichage même si la table en contenait encore
        'name' => $c->name, 'icon' => $c->icon, 'color' => $c->color,
        'slug' => \Illuminate\Support\Str::slug($c->name),
    ])->values();
@endphp

@push('styles')
<style>
    /* Search */
    .gl-search-wrapper {
        position: relative;
        margin-bottom: 20px;
    }
    .gl-search-input {
        width: 100%;
        height: 50px;
        padding: 0 20px 0 48px;
        border: 2px solid #E5E7EB;
        border-radius: var(--r-base);
        font-size: 16px;
        transition: border-color 0.3s;
        background: #fff;
        outline: none;
    }
    .gl-search-input:focus {
        border-color: var(--c-primary);
        box-shadow: 0 0 0 4px rgba(11, 114, 133, 0.1);
    }
    .gl-search-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #6B7280;
        width: 20px;
        height: 20px;
    }

    /* Filter buttons */
    .gl-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }
    .gl-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 16px;
        border-radius: var(--r-btn);
        background: #F3F4F6;
        color: var(--c-dark);
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    .gl-pill:hover { background: #E5E7EB; }
    .gl-pill.active { background: var(--c-primary); color: #fff; }

    /* A-Z nav */
    .gl-az-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    .gl-az-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #fff;
        color: #374151;
        font-weight: 600;
        font-size: 13px;
        border: 1px solid #E5E7EB;
        cursor: pointer;
        transition: all 0.2s;
    }
    .gl-az-btn:hover, .gl-az-btn.active {
        background: var(--c-primary-hover);
        color: #fff;
        border-color: var(--c-primary-hover);
    }
    .gl-az-tous {
        width: auto;
        padding: 0 14px;
        border-radius: var(--r-btn);
    }

    /* Counter */
    .gl-counter {
        color: var(--c-dark);
        font-size: 15px;
        margin-bottom: 20px;
    }
    .gl-counter strong { color: var(--c-primary); }

    /* Cards */
    .gl-card {
        background: #fff;
        border-radius: var(--r-base);
        padding: 24px;
        margin-bottom: 24px;
        height: 100%;
        border: 1px solid #E5E7EB;
        border-left-width: 5px;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
    }
    .gl-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
    }
    .border-acronym { border-left-color: #F59E0B; }
    .border-ai_term { border-left-color: var(--c-primary); }
    .border-explainer { border-left-color: #8E44AD; }

    .gl-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
        gap: 8px;
    }
    .gl-term-name {
        font-family: var(--f-heading);
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        color: var(--c-dark);
        line-height: 1.3;
    }
    .gl-term-name a { color: inherit; text-decoration: none; }
    .gl-term-name a:hover { color: var(--c-primary); }

    .gl-badge {
        font-size: 10px;
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .badge-acronym { background: #FEF3C7; color: #78350F; }
    .badge-ai_term { background: var(--c-primary-badge); color: var(--c-primary); }
    .badge-explainer { background: #F3E8FF; color: #7E22CE; }

    .gl-category {
        font-size: 12px;
        color: #6B7280;
        margin-bottom: 12px;
    }
    .gl-category::before { content: "📁 "; }

    .gl-def {
        color: #4B5563;
        font-size: 14px;
        line-height: 1.65;
        margin-bottom: 16px;
        flex-grow: 1;
    }
    .gl-link {
        color: var(--c-primary);
        font-weight: 600;
        text-decoration: none;
        font-size: 14px;
    }
    .gl-link:hover { text-decoration: underline; }

    /* Category badge */
    .gl-cat-badge {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 4px;
        border: 1px solid #ccc;
        color: #555;
        background: #fff;
    }

    /* Difficulty badges */
    .gl-diff-badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .diff-beginner { background: #D1FAE5; color: #065F46; }
    .diff-intermediate { background: #FEF3C7; color: #92400E; }
    .diff-advanced { background: #FEE2E2; color: #991B1B; }

    /* Analogy preview */
    .gl-analogy {
        font-size: 13px;
        color: #6B7280;
        font-style: italic;
        margin-bottom: 10px;
        line-height: 1.5;
        padding: 8px 10px;
        background: #F9FAFB;
        border-radius: 6px;
        border-left: 3px solid #E5E7EB;
    }

    /* Grid fix BS3 */
    .row-flex { display: flex; flex-wrap: wrap; }
    .row-flex > [class*='col-'] { display: flex; flex-direction: column; }

    /* Empty state */
    .gl-empty {
        text-align: center;
        padding: 60px 20px;
        background: #F9FAFB;
        border-radius: var(--r-base);
    }
    .gl-empty h3 { font-family: var(--f-heading); color: var(--c-dark); }

    @keyframes spin { to { transform: rotate(360deg); } }

    .gl-hero {
        background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-dark) 100%);
        color: #fff; padding: 40px 0; text-align: center; margin-bottom: 24px; border-radius: var(--r-base);
    }
    .gl-hero h1 { font-family: var(--f-heading); font-weight: 800; font-size: 2rem; color: #fff; margin: 0 0 8px; }
    .gl-hero p { font-size: 1.05rem; color: rgba(255,255,255,0.95); margin: 0 0 12px; }
    .gl-stats-badge { background: rgba(255,255,255,0.25); color: #fff; padding: 4px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }

    /* === Toolbar sticky compacte (recherche + filtres dropdown + chips) === */
    /* Le .page-wrapper du thème a overflow:hidden, ce qui crée un conteneur de défilement
       et casse position:sticky. Scopé à cette page : on clipe seulement en X (anti scroll
       horizontal), on rétablit l'axe Y en visible pour que le sticky fonctionne. */
    .page-wrapper { overflow-x: clip !important; overflow-y: visible !important; }
    .gl-toolbar {
        position: sticky;
        top: 0;
        z-index: 50;
        background: #fff;
        padding: 10px 0;
        margin-bottom: 12px;
        border-bottom: 1px solid #eee;
        transition: top .25s ease;
    }
    .gl-toolbar.gl-header-visible { top: 90px; }
    @media (max-width: 991px) { .gl-toolbar.gl-header-visible { top: 60px; } }
    .gl-bar-inner { position: relative; }
    .gl-toolbar-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .gl-search-wrapper { flex: 1; min-width: 200px; margin: 0; }
    .gl-search-input { height: 44px; }
    .gl-filter-toggle {
        height: 44px; min-width: 44px; border: 1px solid #E5E7EB;
        border-radius: var(--r-btn, 8px); background: #fff; padding: 0 14px;
        display: inline-flex; align-items: center; gap: 6px; font-weight: 600;
        cursor: pointer; position: relative; color: var(--c-dark); font-size: 14px;
    }
    .gl-filter-toggle.active { border-color: var(--c-primary); color: var(--c-primary); }
    .gl-filter-badge {
        background: var(--c-primary, #0B7285); color: #fff; border-radius: 999px;
        min-width: 18px; height: 18px; padding: 0 5px; font-size: 11px;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .gl-reset {
        height: 44px; min-width: 44px; background: none; border: none; color: #6B7280;
        font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    }
    .gl-reset:hover { color: var(--c-dark); }
    .gl-counter { margin: 0; white-space: nowrap; font-size: 14px; color: var(--c-dark); }
    .gl-counter strong { color: var(--c-primary); }
    .gl-chips { display: flex; flex-wrap: wrap; gap: 6px; margin: 8px 0 0; }
    .gl-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--c-primary-badge, #e6f4f6); color: var(--c-primary, #0B7285);
        border: none; border-radius: 999px; padding: 5px 12px; font-size: 13px;
        font-weight: 600; cursor: pointer; min-height: 32px;
    }
    .gl-chip:hover { filter: brightness(.96); }
    .gl-chip .x { font-weight: 700; font-size: 15px; line-height: 1; }
    .gl-filter-panel {
        position: absolute; left: 0; right: 0; top: calc(100% + 6px);
        background: #fff; border: 1px solid #E5E7EB; border-radius: var(--r-base, 10px);
        box-shadow: 0 12px 28px -8px rgba(0,0,0,.18); padding: 14px 16px;
        z-index: 60; max-height: 70vh; overflow: auto;
    }
    .gl-filter-panel .gl-filters { margin-bottom: 12px; }
    .gl-filter-panel .gl-az-nav { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
    .gl-filter-toggle:focus-visible, .gl-chip:focus-visible, .gl-reset:focus-visible {
        outline: 2px solid var(--c-primary, #0B7285); outline-offset: 2px;
    }
    html { scroll-padding-top: 150px; }
    @media (max-width: 991px) {
        html { scroll-padding-top: 120px; }
        .gl-counter { width: 100%; order: 5; }
    }
    @media (max-height: 480px) { .gl-toolbar { position: static; } }

    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<section class="section-padding" style="padding-top: 20px;">
    <div class="container"
         x-data="{
            search: '',
            activeType: '',
            activeLetter: '',
            activeCategory: '',
            displayCount: 30,
            _lastFilterKey: '',
            terms: {{ $termsJson->toJson() }},
            categories: {{ $categoriesForFilter->toJson() }},

            get filterKey() { return this.search + '|' + this.activeType + '|' + this.activeLetter + '|' + this.activeCategory; },

            get filteredTerms() {
                const key = this.filterKey;
                if (key !== this._lastFilterKey) { this.displayCount = 30; this._lastFilterKey = key; }
                const s = this.search.toLowerCase();
                return this.terms.filter(t => {
                    const matchSearch = !s || t.name.toLowerCase().includes(s) || t.fullDef.toLowerCase().includes(s);
                    const matchType = !this.activeType || t.type === this.activeType;
                    const matchLetter = !this.activeLetter || t.firstLetter === this.activeLetter;
                    const matchCat = !this.activeCategory || t.categorySlug === this.activeCategory;
                    return matchSearch && matchType && matchLetter && matchCat;
                });
            },

            get visibleTerms() { return this.filteredTerms.slice(0, this.displayCount); },
            get hasMore() { return this.displayCount < this.filteredTerms.length; },
            loadMore() { if (this.hasMore) this.displayCount += 30; },

            toggleType(type) { this.activeType = this.activeType === type ? '' : type; },
            toggleLetter(l) { this.activeLetter = this.activeLetter === l ? '' : l; },
            toggleCategory(c) { this.activeCategory = this.activeCategory === c ? '' : c; },
            resetAll() { this.search = ''; this.activeType = ''; this.activeLetter = ''; this.activeCategory = ''; },

            filtersOpen: false,
            get hasActiveFilters() { return !!(this.search || this.activeType || this.activeLetter || this.activeCategory); },
            get activeFilterCount() { return [this.search, this.activeType, this.activeLetter, this.activeCategory].filter(Boolean).length; },
            typeLabels: { acronym: '🔤 Acronymes', ai_term: '🤖 Termes IA', explainer: '📖 Vulgarisations' },
            get activeCategoryLabel() { const c = this.categories.find(x => x.slug === this.activeCategory); return c ? c.icon + ' ' + c.name : ''; }
         }"
         >

        {{-- Hero + 2-step wizard wrapper --}}
        <div x-data="{ step: 0, submitted: false, termName: '', termDef: '' }">
            <div class="gl-hero">
                <h1>{{ __('Glossaire IA') }}</h1>
                <p>{{ __('Comprendre les termes de l\'intelligence artificielle, simplement.') }}</p>

                <div style="display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap;">
                    <span class="gl-stats-badge">
                        <span x-text="filteredTerms.length"></span> {{ __('termes répertoriés') }}
                    </span>
                    @if(class_exists(\Modules\Roadmap\Models\Board::class))
                        @auth
                            <button type="button" x-show="step === 0 && !submitted" @click="step = 1"
                                style="background: rgba(255,255,255,0.15); color: #fff; font-weight: 600; padding: 8px 20px; border-radius: var(--r-btn); border: 1px solid rgba(255,255,255,0.4); cursor: pointer; font-size: 13px; transition: all 0.2s;"
                                onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                                + {{ __('Proposer un terme') }}
                            </button>
                        @else
                            <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour proposer un terme.') }}' })"
                                style="background: rgba(255,255,255,0.15); color: #fff; font-weight: 600; padding: 8px 20px; border-radius: var(--r-btn); border: 1px solid rgba(255,255,255,0.4); cursor: pointer; font-size: 13px;">
                                {{ __('Proposer un terme') }}
                            </button>
                        @endauth
                    @endif
                </div>

                @auth
                {{-- Step 1 inline : nom du terme --}}
                <div x-show="step === 1" x-cloak x-transition.duration.300ms
                     style="margin-top: 20px; background: rgba(255,255,255,0.12); border-radius: var(--r-base); padding: 20px; max-width: 560px; margin-left: auto; margin-right: auto;">
                    <div style="font-size: 11px; color: rgba(255,255,255,0.5); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">{{ __('Étape 1 sur 2 – Identification') }}</div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <input type="text" x-model="termName" placeholder="{{ __('Nom du terme (ex: Transformer, RAG, Fine-tuning...)') }}" aria-label="{{ __('Nom du terme') }}"
                            style="flex: 1; min-width: 200px; height: 42px; padding: 0 14px; border: 2px solid #E5E7EB; border-radius: var(--r-base); font-size: 15px; font-weight: 700; background: #fff; color: var(--c-dark); outline: none;">
                        <button type="button" @click="if(termName.trim()) step = 2"
                            :style="'height:42px;padding:0 20px;background:var(--c-primary);color:#fff;font-weight:700;border:2px solid rgba(255,255,255,0.3);border-radius:var(--r-btn);cursor:pointer;font-size:14px;white-space:nowrap;transition:all 0.2s;' + (!termName.trim() ? 'opacity:0.5;cursor:not-allowed;' : '')"
                            onmouseover="this.style.background='var(--c-dark)'" onmouseout="this.style.background='var(--c-primary)'">
                            {{ __('Continuer') }} →
                        </button>
                    </div>
                    <div style="text-align: right; margin-top: 6px;">
                        <button type="button" @click="step = 0; termName = ''; termDef = ''" style="background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 12px;">{{ __('Annuler') }}</button>
                    </div>
                </div>

                {{-- Success --}}
                <div x-show="submitted" x-cloak x-transition style="margin-top: 16px;">
                    <span style="background: rgba(255,255,255,0.2); padding: 10px 24px; border-radius: var(--r-btn); font-size: 14px; font-weight: 600;">
                        ✓ {{ __('Merci ! Votre proposition est soumise au vote de la communauté.') }}
                    </span>
                </div>
                @endauth
            </div>

            {{-- Step 2 : Details (white card below hero) --}}
            @auth
            @if(class_exists(\Modules\Roadmap\Models\Board::class))
            <div x-show="step === 2" x-cloak x-transition.duration.400ms
                 style="background: #fff; border: 2px solid #E5E7EB; border-top: none; border-radius: 0 0 var(--r-base) var(--r-base); padding: 28px; max-width: 100%; margin-top: -24px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <span style="font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: 1px;">{{ __('Étape 2 sur 2 – Détails') }}</span>
                        <h3 style="font-family: var(--f-heading); color: var(--c-dark); margin: 4px 0 0; font-size: 16px;">
                            {{ __('Complétez les informations pour') }} <strong x-text="termName" style="color: var(--c-primary);"></strong>
                        </h3>
                    </div>
                    <button type="button" @click="step = 1" style="background: none; border: none; color: var(--c-primary); cursor: pointer; font-size: 13px; font-weight: 600;">← {{ __('Retour') }}</button>
                </div>

                <form method="POST" action="{{ route('roadmap.ideas.store', ['board' => 'glossaire-communautaire']) }}"
                      @submit.prevent="
                        fetch($el.action, { method: 'POST', body: new FormData($el) })
                        .then(r => { if(r.ok || r.redirected) { submitted = true; step = 0; } })
                        .catch(() => { $el.submit(); })
                      ">
                    @csrf
                    <input type="hidden" name="source" value="glossaire">
                    <input type="hidden" name="title" :value="termName">

                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Définition courte') }} <span style="color: #B91C1C;">*</span></label>
                        <textarea name="description" required rows="3" x-model="termDef" aria-label="{{ __('Définition courte') }}"
                            :placeholder="'{{ __('Décrivez') }} ' + termName + ' {{ __('en 2-3 phrases simples...') }}'"
                            style="width: 100%; padding: 10px 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; outline: none; resize: vertical; background: #fff; color: var(--c-dark);"
                            onfocus="this.style.borderColor='var(--c-primary)'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6" style="margin-bottom: 14px;">
                            <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Catégorie') }}</label>
                            <select name="category" aria-label="{{ __('Catégorie du terme') }}"
                                style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; background: #fff; color: var(--c-dark);">
                                <option value="">{{ __('Choisir...') }}</option>
                                <option value="Concepts fondamentaux">{{ __('Concepts fondamentaux') }}</option>
                                <option value="Acronymes et sigles">{{ __('Acronymes et sigles') }}</option>
                                <option value="Sécurité et éthique">{{ __('Sécurité et éthique') }}</option>
                                <option value="Outils et techniques">{{ __('Outils et techniques') }}</option>
                                <option value="Données et traitement">{{ __('Données et traitement') }}</option>
                                <option value="Tendances 2026">{{ __('Tendances 2026') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6" style="margin-bottom: 14px; display: flex; align-items: flex-end;">
                            <button type="submit"
                                style="width: 100%; height: 40px; background: var(--c-primary); color: #fff; font-weight: 700; border: none; border-radius: var(--r-btn); cursor: pointer; font-size: 14px; transition: background 0.2s;"
                                onmouseover="this.style.background='var(--c-dark)'" onmouseout="this.style.background='var(--c-primary)'">
                                {{ __('Soumettre la proposition') }}
                            </button>
                        </div>
                    </div>

                    <p style="font-size: 12px; color: #6B7280; margin: 4px 0 0;">
                        {{ __('La communauté votera sur votre proposition dans la section Idées et votes.') }}
                    </p>
                </form>
            </div>
            @endif
            @endauth
        </div>

        {{-- Toolbar compacte sticky : recherche + bouton Filtres (dropdown) + chips d'état --}}
        <div id="glToolbar" class="gl-toolbar">
            <div class="gl-bar-inner">
                <div class="gl-toolbar-row">
                    <div class="gl-search-wrapper">
                        <svg class="gl-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" class="gl-search-input"
                               placeholder="{{ __('Rechercher un terme (ex: LLM, prompt, transformer...)') }}"
                               x-model="search" aria-label="{{ __('Rechercher dans le glossaire') }}">
                    </div>
                    <button type="button" class="gl-filter-toggle" :class="{ active: filtersOpen || hasActiveFilters }"
                            @click.stop="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen" aria-controls="glFilterPanel">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
                        </svg>
                        <span>{{ __('Filtres') }}</span>
                        <span class="gl-filter-badge" x-show="activeFilterCount > 0" x-text="activeFilterCount" x-cloak></span>
                    </button>
                    <button type="button" class="gl-reset" x-show="hasActiveFilters" x-cloak
                            @click="resetAll()" aria-label="{{ __('Tout réinitialiser') }}">✕</button>
                    <div class="gl-counter" aria-live="polite">
                        <strong x-text="visibleTerms.length"></strong> {{ __('sur') }} <strong x-text="filteredTerms.length"></strong> {{ __('termes') }}
                    </div>
                </div>

                {{-- Chips d'état actif --}}
                <div class="gl-chips" x-show="hasActiveFilters" x-cloak>
                    <template x-if="search">
                        <button type="button" class="gl-chip" @click="search = ''" aria-label="{{ __('Supprimer le filtre de recherche') }}">
                            <span>«&nbsp;<span x-text="search"></span>&nbsp;»</span><span class="x">×</span>
                        </button>
                    </template>
                    <template x-if="activeType">
                        <button type="button" class="gl-chip" @click="activeType = ''" :aria-label="`{{ __('Supprimer le filtre') }} ${typeLabels[activeType]}`">
                            <span x-text="typeLabels[activeType]"></span><span class="x">×</span>
                        </button>
                    </template>
                    <template x-if="activeLetter">
                        <button type="button" class="gl-chip" @click="activeLetter = ''" aria-label="{{ __('Supprimer le filtre alphabétique') }}">
                            <span>{{ __('Lettre') }}&nbsp;:&nbsp;<span x-text="activeLetter"></span></span><span class="x">×</span>
                        </button>
                    </template>
                    <template x-if="activeCategory">
                        <button type="button" class="gl-chip" @click="activeCategory = ''" aria-label="{{ __('Supprimer le filtre de catégorie') }}">
                            <span x-text="activeCategoryLabel"></span><span class="x">×</span>
                        </button>
                    </template>
                </div>

                {{-- Panneau de filtres (dropdown overlay) --}}
                <div id="glFilterPanel" class="gl-filter-panel" x-show="filtersOpen" x-transition @click.outside="filtersOpen = false" x-cloak>
                    <h2 class="sr-only">{{ __('Filtres') }}</h2>
                    <select x-model="activeCategory" aria-label="{{ __('Filtrer par catégorie') }}"
                            style="width: 100%; height: 48px; border-radius: var(--r-base); border: 2px solid #E5E7EB; padding: 0 36px 0 16px; font-size: 14px; font-weight: 600; color: var(--c-dark); background: #fff; cursor: pointer; -webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%236B7280%22 stroke-width=%222%22%3E%3Cpath d=%22M6 9l6 6 6-6%22/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 14px center; margin-bottom: 12px;">
                        <option value="">{{ __('Toutes les catégories') }}</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.slug" x-text="cat.icon + ' ' + cat.name"></option>
                        </template>
                    </select>
                    <div class="gl-filters">
                        <button type="button" class="gl-pill" :class="{ active: activeType === '' }" @click="activeType = ''">{{ __('Tous les types') }}</button>
                        <button type="button" class="gl-pill" :class="{ active: activeType === 'acronym' }" @click="toggleType('acronym')">🔤 {{ __('Acronymes') }}</button>
                        <button type="button" class="gl-pill" :class="{ active: activeType === 'ai_term' }" @click="toggleType('ai_term')">🤖 {{ __('Termes IA') }}</button>
                        <button type="button" class="gl-pill" :class="{ active: activeType === 'explainer' }" @click="toggleType('explainer')">📖 {{ __('Vulgarisations') }}</button>
                    </div>
                    <nav class="gl-az-nav" aria-label="{{ __('Navigation alphabétique') }}">
                        <button type="button" class="gl-az-btn gl-az-tous" :class="{ active: activeLetter === '' }" @click="activeLetter = ''">{{ __('Tous') }}</button>
                        @foreach(range('A','Z') as $char)
                            <button type="button" class="gl-az-btn" :class="{ active: activeLetter === '{{ $char }}' }" @click="toggleLetter('{{ $char }}')">{{ $char }}</button>
                        @endforeach
                    </nav>
                </div>
            </div>
        </div>

        {{-- Sync : décale la toolbar sous le header sticky du site quand il est visible --}}
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toolbar = document.getElementById('glToolbar');
            var headerNav = document.querySelector('.wpo-site-header .navigation');
            if (!toolbar || !headerNav) return;
            var sync = function () { toolbar.classList.toggle('gl-header-visible', headerNav.classList.contains('sticky-on')); };
            new MutationObserver(sync).observe(headerNav, { attributes: true, attributeFilter: ['class'] });
            sync();
        });
        </script>

        {{-- Ad: glossary top --}}
        @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
            {!! app(\Modules\Ads\Services\AdsRenderer::class)->render('glossary-top') !!}
        @endif

        {{-- Cards grid --}}
        <div class="row row-flex">
            <template x-for="term in visibleTerms" :key="term.id">
                <div class="col-lg-4 col-md-6 col-xs-12">
                    <article class="gl-card" :class="'border-' + term.type">
                        {{-- Hero image or icon --}}
                        <template x-if="term.heroImage">
                            <a :href="term.url" style="display: block; margin: -16px -18px 12px; overflow: hidden; border-radius: 8px 8px 0 0;">
                                <picture>
                                    <source :srcset="term.heroImageWebp" type="image/webp">
                                    <img :src="term.heroImage" :alt="term.name" loading="lazy" style="width: 100%; height: 140px; object-fit: cover; display: block;">
                                </picture>
                            </a>
                        </template>
                        <div class="gl-card-top">
                            <h3 class="gl-term-name" role="heading" aria-level="2">
                                <template x-if="!term.heroImage">
                                    <span x-text="term.icon" style="margin-right: 4px;"></span>
                                </template>
                                <a :href="term.url" x-text="term.name"></a>
                            </h3>
                            <span class="gl-badge" :class="'badge-' + term.type" x-text="term.typeName"></span>
                        </div>
                        <template x-if="term.acronymFull">
                            <p style="color: #6B7280; font-size: 0.8rem; font-style: italic; margin: -6px 0 8px; line-height: 1.3;" x-text="term.acronymFull"></p>
                        </template>

                        {{-- Badges catégorie + difficulté --}}
                        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px;">
                            <template x-if="term.category">
                                <span class="gl-cat-badge" :style="'border-color:' + (term.categoryColor || '#ccc')">
                                    <span x-text="term.categoryIcon"></span> <span x-text="term.category"></span>
                                </span>
                            </template>
                            <span class="gl-diff-badge" :class="'diff-' + term.difficulty" x-text="term.diffLabel"></span>
                        </div>

                        {{-- Analogie (preview) --}}
                        <template x-if="term.analogy">
                            <p class="gl-analogy">
                                💡 <span x-text="term.analogy"></span>
                            </p>
                        </template>

                        <p class="gl-def" x-text="term.definition"></p>

                        <a :href="term.url" class="gl-link" :aria-label="'{{ __('Lire la définition de') }} ' + term.name">
                            {{ __('Lire la définition') }} →
                        </a>
                    </article>
                </div>
            </template>
        </div>

        {{-- Sentinel : charge plus au scroll --}}
        <div x-show="hasMore" x-intersect="loadMore()" class="text-center" style="padding: 24px 0;" role="status" aria-label="{{ __('Chargement en cours') }}">
            <div style="display: inline-block; width: 24px; height: 24px; border: 3px solid #E5E7EB; border-top-color: var(--c-primary); border-radius: 50%; animation: spin 0.6s linear infinite;"></div>
            <p style="color: #6B7280; font-size: 13px; margin-top: 8px;">{{ __('Chargement...') }}</p>
        </div>

        {{-- Empty state --}}
        <div x-show="filteredTerms.length === 0" x-cloak>
            <div class="gl-empty">
                <div style="font-size: 40px; margin-bottom: 10px;">🤔</div>
                <h3>{{ __('Aucun terme trouvé') }}</h3>
                <p>{{ __('Essayez de modifier vos filtres ou votre recherche.') }}</p>
                <button type="button" @click="resetAll()" class="btn" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn); margin-top: 10px;">
                    {{ __('Réinitialiser les filtres') }}
                </button>
            </div>
        </div>
    </div>

    {{-- S134 SEO : cluster bidirectionnel — le hub glossaire pointe vers les piliers thématiques (les 5). --}}
    <div class="container">
        @include('fronttheme::partials.pillars-related')
    </div>

</section>
@endsection

@push('scripts')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "DefinedTermSet",
    "name": "{{ __('Glossaire IA') }}",
    "description": "{{ __('Comprendre les termes de l\'intelligence artificielle, simplement.') }}",
    "url": "{{ route('dictionary.index') }}"
}
</script>
@endpush
