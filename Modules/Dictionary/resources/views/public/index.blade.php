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
            'heroImage' => $term->hero_image ? asset(str_replace('.png', '.webp', $term->hero_image)) : null,
            'heroImageFallback' => $term->hero_image ? asset($term->hero_image) : null,
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
                        {{-- Hero image or icon --}}
                        <template x-if="term.heroImage">
                            <a :href="term.url" style="display: block; margin: -16px -18px 12px; overflow: hidden; border-radius: 8px 8px 0 0;">
                                <picture>
                                    <source :srcset="term.heroImage" type="image/webp">
                                    <img :src="term.heroImageFallback" :alt="term.name" loading="lazy" style="width: 100%; height: 140px; object-fit: cover; display: block;">
                                </picture>
                            </a>
                        </template>
                        <div class="gl-card-top">
                            <h3 class="gl-term-name">
                                <template x-if="!term.heroImage">
                                    <span x-text="term.icon" style="margin-right: 4px;"></span>
                                </template>
                                <a :href="term.url" x-text="term.name"></a>
                            </h3>
                            <span class="gl-badge" :class="'badge-' + term.type" x-text="term.typeName"></span>
                        </div>
                        <template x-if="term.acronymFull">
                            <p style="color: #9CA3AF; font-size: 0.8rem; font-style: italic; margin: -6px 0 8px; line-height: 1.3;" x-text="term.acronymFull"></p>
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

    {{-- CTA Proposer un terme --}}
    @if(class_exists(\Modules\Roadmap\Models\Board::class))
    <div x-data="{ showForm: false, submitted: false }" style="margin-top: 40px;">
        <div style="background: linear-gradient(135deg, var(--c-primary) 0%, #1a5276 100%); border-radius: var(--r-base); padding: 40px 30px; color: #fff; text-align: center;">
            <h2 style="font-family: var(--f-heading); font-size: 24px; font-weight: 700; margin: 0 0 8px;">
                {{ __('Vous ne trouvez pas un terme ?') }}
            </h2>
            <p style="font-size: 16px; opacity: 0.9; margin-bottom: 20px;">
                {{ __('Proposez un nouveau terme pour le glossaire et la communaute votera !') }}
            </p>

            @auth
                <button type="button" @click="showForm = !showForm" x-show="!submitted"
                    style="background: #fff; color: var(--c-primary); font-weight: 700; padding: 12px 28px; border-radius: var(--r-btn); border: none; cursor: pointer; font-size: 15px; transition: all 0.2s;"
                    onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='#fff'">
                    <span x-text="showForm ? '{{ __('Fermer') }}' : '{{ __('Proposer un terme') }}'"></span>
                </button>
            @else
                <a href="{{ route('login') }}" style="background: #fff; color: var(--c-primary); font-weight: 700; padding: 12px 28px; border-radius: var(--r-btn); text-decoration: none; display: inline-block; font-size: 15px;">
                    {{ __('Connectez-vous pour proposer un terme') }}
                </a>
            @endauth

            {{-- Success message --}}
            <div x-show="submitted" x-cloak style="background: rgba(255,255,255,0.15); border-radius: var(--r-base); padding: 20px; margin-top: 20px;">
                <div style="font-size: 32px; margin-bottom: 8px;">&#10003;</div>
                <p style="font-weight: 600; font-size: 16px;">{{ __('Merci ! Votre proposition a ete soumise. La communaute pourra voter dessus dans les idees et votes.') }}</p>
            </div>
        </div>

        @auth
        <div x-show="showForm && !submitted" x-cloak x-transition
             style="background: #fff; border: 2px solid #E5E7EB; border-top: none; border-radius: 0 0 var(--r-base) var(--r-base); padding: 30px;">
            <h3 style="font-family: var(--f-heading); color: var(--c-dark); margin: 0 0 20px; font-size: 18px;">
                {{ __('Soumettre une proposition de terme') }}
            </h3>
            <form method="POST" action="{{ route('roadmap.ideas.store', ['board' => 'glossaire-communautaire']) }}"
                  @submit.prevent="
                    fetch($el.action, { method: 'POST', body: new FormData($el) })
                    .then(r => { if(r.ok || r.redirected) { submitted = true; showForm = false; } })
                    .catch(() => { $el.submit(); })
                  ">
                @csrf
                <input type="hidden" name="source" value="glossaire">

                <div style="margin-bottom: 16px;">
                    <label for="gl-term-name" style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 6px; font-size: 14px;">
                        {{ __('Nom du terme') }} <span style="color: #E74C3C;">*</span>
                    </label>
                    <input type="text" id="gl-term-name" name="title" required placeholder="{{ __('Ex: Apprentissage par transfert, XAI, Tokenisation...') }}"
                        style="width: 100%; height: 44px; padding: 0 14px; border: 2px solid #E5E7EB; border-radius: var(--r-base); font-size: 15px; outline: none;"
                        onfocus="this.style.borderColor='var(--c-primary)'" onblur="this.style.borderColor='#E5E7EB'">
                </div>

                <div style="margin-bottom: 16px;">
                    <label for="gl-term-def" style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 6px; font-size: 14px;">
                        {{ __('Definition courte') }} <span style="color: #E74C3C;">*</span>
                    </label>
                    <textarea id="gl-term-def" name="description" rows="3" required placeholder="{{ __('Decrivez ce terme en 2-3 phrases simples...') }}"
                        style="width: 100%; padding: 10px 14px; border: 2px solid #E5E7EB; border-radius: var(--r-base); font-size: 15px; outline: none; resize: vertical;"
                        onfocus="this.style.borderColor='var(--c-primary)'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6" style="margin-bottom: 16px;">
                        <label for="gl-term-cat" style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 6px; font-size: 14px;">
                            {{ __('Categorie') }}
                        </label>
                        <select id="gl-term-cat" name="category"
                            style="width: 100%; height: 44px; padding: 0 14px; border: 2px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; background: #fff;">
                            <option value="Concepts fondamentaux">{{ __('Concepts fondamentaux') }}</option>
                            <option value="Acronymes et sigles">{{ __('Acronymes et sigles') }}</option>
                            <option value="Securite et ethique">{{ __('Securite et ethique') }}</option>
                            <option value="Outils et techniques">{{ __('Outils et techniques') }}</option>
                            <option value="Donnees et traitement">{{ __('Donnees et traitement') }}</option>
                            <option value="Tendances 2026">{{ __('Tendances 2026') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6" style="margin-bottom: 16px; display: flex; align-items: flex-end;">
                        <button type="submit"
                            style="width: 100%; height: 44px; background: var(--c-primary); color: #fff; font-weight: 700; border: none; border-radius: var(--r-btn); cursor: pointer; font-size: 15px; transition: all 0.2s;"
                            onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                            {{ __('Soumettre ma proposition') }}
                        </button>
                    </div>
                </div>

                <p style="font-size: 12px; color: #9CA3AF; margin: 0;">
                    {{ __('Votre proposition apparaitra dans la section Idees et votes ou la communaute pourra voter. Les termes les plus populaires seront ajoutes au glossaire.') }}
                </p>
            </form>
        </div>
        @endauth
    </div>
    @endif

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
