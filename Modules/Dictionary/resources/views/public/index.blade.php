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
        ];
    })->values();

    $categoriesForFilter = $categories->map(fn($c) => [
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
        color: #9CA3AF;
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
        color: #6B7280;
        font-weight: 600;
        font-size: 13px;
        border: 1px solid #E5E7EB;
        cursor: pointer;
        transition: all 0.2s;
    }
    .gl-az-btn:hover, .gl-az-btn.active {
        background: var(--c-primary);
        color: #fff;
        border-color: var(--c-primary);
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
    .badge-acronym { background: #FEF3C7; color: #D97706; }
    .badge-ai_term { background: var(--c-primary-badge); color: var(--c-primary); }
    .badge-explainer { background: #F3E8FF; color: #7E22CE; }

    .gl-category {
        font-size: 12px;
        color: #9CA3AF;
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
            terms: {{ $termsJson->toJson() }},
            categories: {{ $categoriesForFilter->toJson() }},

            get filteredTerms() {
                const s = this.search.toLowerCase();
                return this.terms.filter(t => {
                    const matchSearch = !s || t.name.toLowerCase().includes(s) || t.fullDef.toLowerCase().includes(s);
                    const matchType = !this.activeType || t.type === this.activeType;
                    const matchLetter = !this.activeLetter || t.firstLetter === this.activeLetter;
                    const matchCat = !this.activeCategory || t.categorySlug === this.activeCategory;
                    return matchSearch && matchType && matchLetter && matchCat;
                });
            },

            toggleType(type) { this.activeType = this.activeType === type ? '' : type; },
            toggleLetter(l) { this.activeLetter = this.activeLetter === l ? '' : l; },
            toggleCategory(c) { this.activeCategory = this.activeCategory === c ? '' : c; },
            resetAll() { this.search = ''; this.activeType = ''; this.activeLetter = ''; }
         }"
         >

        {{-- Subtitle --}}
        <p class="text-center" style="font-size: 1.15em; color: #6B7280; margin-bottom: 24px;">
            {{ __('Comprendre les termes de l\'intelligence artificielle, simplement.') }}
        </p>

        {{-- Search + Category dropdown --}}
        <div class="row" style="margin-bottom: 16px;">
            <div class="col-md-7 col-sm-8 col-xs-12" style="margin-bottom: 10px;">
                <div class="gl-search-wrapper" style="margin-bottom: 0;">
                    <svg class="gl-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           class="gl-search-input"
                           placeholder="{{ __('Rechercher un terme (ex: LLM, prompt, transformer...)') }}"
                           x-model="search"
                           aria-label="{{ __('Rechercher dans le glossaire') }}">
                </div>
            </div>
            <div class="col-md-5 col-sm-4 col-xs-12">
                <select x-model="activeCategory"
                        aria-label="{{ __('Filtrer par catégorie') }}"
                        style="width: 100%; height: 50px; border-radius: var(--r-base); border: 2px solid #E5E7EB; padding: 0 36px 0 16px; font-size: 14px; font-weight: 600; color: var(--c-dark); background: #fff; cursor: pointer; -webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%236B7280%22 stroke-width=%222%22%3E%3Cpath d=%22M6 9l6 6 6-6%22/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 14px center;">
                    <option value="">{{ __('Toutes les catégories') }}</option>
                    <template x-for="cat in categories" :key="cat.slug">
                        <option :value="cat.slug" x-text="cat.icon + ' ' + cat.name"></option>
                    </template>
                </select>
            </div>
        </div>

        {{-- Type filters --}}
        <div class="gl-filters">
            <button type="button" class="gl-pill" :class="{ active: activeType === '' }" @click="activeType = ''">
                {{ __('Tous les types') }}
            </button>
            <button type="button" class="gl-pill" :class="{ active: activeType === 'acronym' }" @click="toggleType('acronym')">
                🔤 {{ __('Acronymes') }}
            </button>
            <button type="button" class="gl-pill" :class="{ active: activeType === 'ai_term' }" @click="toggleType('ai_term')">
                🤖 {{ __('Termes IA') }}
            </button>
            <button type="button" class="gl-pill" :class="{ active: activeType === 'explainer' }" @click="toggleType('explainer')">
                📖 {{ __('Vulgarisations') }}
            </button>
        </div>

        {{-- A-Z nav --}}
        <nav class="gl-az-nav" aria-label="{{ __('Navigation alphabétique') }}">
            <button type="button" class="gl-az-btn gl-az-tous" :class="{ active: activeLetter === '' }" @click="activeLetter = ''">
                {{ __('Tous') }}
            </button>
            @foreach(range('A','Z') as $char)
                <button type="button" class="gl-az-btn" :class="{ active: activeLetter === '{{ $char }}' }" @click="toggleLetter('{{ $char }}')">
                    {{ $char }}
                </button>
            @endforeach
        </nav>

        {{-- Counter --}}
        <div class="gl-counter">
            <strong x-text="filteredTerms.length"></strong> {{ __('termes') }}
        </div>

        {{-- Ad: glossary top --}}
        @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
            {!! app(\Modules\Ads\Services\AdsRenderer::class)->render('glossary-top') !!}
        @endif

        {{-- Cards grid --}}
        <div class="row row-flex">
            <template x-for="term in filteredTerms" :key="term.id">
                <div class="col-lg-4 col-md-6 col-xs-12">
                    <article class="gl-card" :class="'border-' + term.type">
                        <div class="gl-card-top">
                            <h3 class="gl-term-name">
                                <span x-text="term.icon" style="margin-right: 4px;"></span>
                                <a :href="term.url" x-text="term.name"></a>
                            </h3>
                            <span class="gl-badge" :class="'badge-' + term.type" x-text="term.typeName"></span>
                        </div>

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
