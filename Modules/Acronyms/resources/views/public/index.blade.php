<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Acronymes de l\'éducation au Québec') . ' - ' . config('app.name'))
@section('meta_description', __('Le glossaire complet des 314 acronymes du système éducatif québécois. Ministères, associations, formations, technologies et plus.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Acronymes éducation')])
@endsection

@push('styles')
<style>
    .acr-wrapper { padding: 10px 0 60px; min-height: 60vh; }

    .acr-hero {
        background: linear-gradient(135deg, var(--c-primary) 0%, #1a365d 100%);
        color: #fff; padding: 40px 0; text-align: center; margin-bottom: 24px; border-radius: var(--r-base);
    }
    .acr-hero h1 { font-family: var(--f-heading); font-weight: 800; font-size: 2rem; color: #fff; margin: 0 0 8px; }
    .acr-hero p { font-size: 1.05rem; color: rgba(255,255,255,0.95); margin: 0 0 12px; }
    .acr-stats-badge { background: rgba(255,255,255,0.25); color: #fff; padding: 4px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }

    .acr-sticky-nav {
        position: sticky; top: 0; z-index: 100; background: rgba(255,255,255,0.97);
        border-bottom: 1px solid #E5E7EB; padding: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    }
    .acr-letters { display: flex; flex-wrap: wrap; justify-content: center; gap: 4px; }
    .acr-letter-btn {
        border: 1px solid #E5E7EB; background: #fff; color: var(--c-dark);
        width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: var(--r-btn); cursor: pointer; font-weight: 600; font-size: 12px; transition: all 0.2s;
    }
    .acr-letter-btn:hover { background: #F3F4F6; }
    .acr-letter-active { background: var(--c-primary) !important; color: #fff !important; border-color: var(--c-primary) !important; }

    .acr-search { margin: 20px 0 16px; }
    .acr-search-input {
        border-radius: var(--r-base); height: 44px; font-size: 15px;
        border: 1px solid #E5E7EB; padding: 0 16px; width: 100%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .acr-cat-select {
        border-radius: var(--r-base); height: 44px; font-size: 14px; font-weight: 600;
        border: 1px solid #E5E7EB; padding: 0 36px 0 16px; background: #fff; color: var(--c-dark);
        cursor: pointer; min-width: 200px;
        -webkit-appearance: none; -moz-appearance: none; appearance: none;
        background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%236B7280%22 stroke-width=%222%22%3E%3Cpath d=%22M6 9l6 6 6-6%22/%3E%3C/svg%3E');
        background-repeat: no-repeat; background-position: right 14px center;
    }
    .acr-cat-select:focus { border-color: var(--c-primary); outline: none; box-shadow: 0 0 0 2px rgba(11,114,133,0.2); }

    .acr-card {
        background: #fff; border: 1px solid #E5E7EB; border-radius: var(--r-base);
        padding: 20px; height: 100%; display: flex; flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s; text-decoration: none !important;
    }
    .acr-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1); border-color: var(--c-primary); }
    .acr-card-header { display: flex; align-items: center; margin-bottom: 10px; gap: 12px; }
    .acr-logo {
        width: 44px; height: 44px; flex-shrink: 0; border-radius: 50%; overflow: hidden;
        display: flex; align-items: center; justify-content: center; background: #F3F4F6;
    }
    .acr-logo img { width: 100%; height: 100%; object-fit: contain; }
    .acr-logo-fallback {
        width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 18px; border-radius: 50%;
    }
    .acr-title { font-family: var(--f-heading); font-size: 1.2rem; font-weight: 700; color: var(--c-dark); margin: 0; }
    .acr-fullname { color: #6B7280; font-size: 0.88rem; line-height: 1.5; flex-grow: 1; margin-bottom: 12px; }
    .acr-footer { display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 10px; border-top: 1px solid #F3F4F6; gap: 8px; }
    .acr-badge { font-size: 11px; padding: 2px 10px; border-radius: 12px; font-weight: 600; color: #fff; max-width: 60%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex-shrink: 1; }
    .acr-link { color: var(--c-primary); font-weight: 600; font-size: 13px; text-decoration: none; white-space: nowrap; flex-shrink: 0; }
    .acr-link:hover { text-decoration: underline; }

    .acr-empty { text-align: center; padding: 60px 20px; color: #6B7280; }
    .acr-counter { text-align: center; color: #9CA3AF; font-size: 13px; margin-bottom: 16px; }

    .row-flex { display: flex; flex-wrap: wrap; }
    .row-flex > [class*='col-'] { display: flex; flex-direction: column; margin-bottom: 20px; }
</style>
@endpush

@section('content')
<div class="acr-wrapper">
    <div class="container" x-data="acronymApp()">

        {{-- Hero with inline propose form --}}
        <div class="acr-hero" x-data="{ proposing: false, submitted: false }">
            <h1>🎓 {{ __('Acronymes de l\'éducation au Québec') }}</h1>
            <p>{{ __('Le glossaire complet pour naviguer dans le jargon du système éducatif québécois') }}</p>

            <div style="display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap;">
                <span class="acr-stats-badge">
                    <span x-text="filteredItems.length"></span> {{ __('acronymes répertoriés') }}
                </span>

                @if(class_exists(\Modules\Roadmap\Models\Board::class))
                    @auth
                        <button type="button" x-show="!proposing && !submitted" @click="proposing = true"
                            style="background: rgba(255,255,255,0.15); color: #fff; font-weight: 600; padding: 8px 20px; border-radius: var(--r-btn); border: 1px solid rgba(255,255,255,0.4); cursor: pointer; font-size: 13px; transition: all 0.2s;"
                            onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                            + {{ __('Proposer un acronyme') }}
                        </button>
                    @else
                        <a href="{{ route('login') }}" style="background: rgba(255,255,255,0.15); color: #fff; font-weight: 600; padding: 8px 20px; border-radius: var(--r-btn); text-decoration: none; font-size: 13px; border: 1px solid rgba(255,255,255,0.4);">
                            {{ __('Proposer un acronyme') }}
                        </a>
                    @endauth
                @endif
            </div>

            {{-- Inline form — progressive disclosure --}}
            @auth
            <div x-show="proposing && !submitted" x-cloak x-transition.duration.300ms
                 style="margin-top: 20px; background: rgba(255,255,255,0.12); border-radius: var(--r-base); padding: 20px; max-width: 600px; margin-left: auto; margin-right: auto;">
                <form method="POST" action="{{ route('roadmap.ideas.store', ['board' => 'glossaire-communautaire']) }}"
                      @submit.prevent="
                        fetch($el.action, { method: 'POST', body: new FormData($el) })
                        .then(r => { if(r.ok || r.redirected) { submitted = true; proposing = false; } })
                        .catch(() => { $el.submit(); })
                      ">
                    @csrf
                    <input type="hidden" name="source" value="acronymes">
                    <input type="hidden" name="category" value="Acronymes éducation">

                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <input type="text" name="title" required placeholder="{{ __('Acronyme (ex: RÉCIT)') }}"
                            style="flex: 0 0 140px; height: 42px; padding: 0 14px; border: 2px solid rgba(255,255,255,0.3); border-radius: var(--r-base); font-size: 15px; font-weight: 700; text-transform: uppercase; background: rgba(255,255,255,0.1); color: #fff; outline: none;"
                            onfocus="this.style.borderColor='#fff'" onblur="this.style.borderColor='rgba(255,255,255,0.3)'">
                        <input type="text" name="description" required placeholder="{{ __('Nom complet de l\'acronyme') }}"
                            style="flex: 1; min-width: 200px; height: 42px; padding: 0 14px; border: 2px solid rgba(255,255,255,0.3); border-radius: var(--r-base); font-size: 14px; background: rgba(255,255,255,0.1); color: #fff; outline: none;"
                            onfocus="this.style.borderColor='#fff'" onblur="this.style.borderColor='rgba(255,255,255,0.3)'">
                        <button type="submit"
                            style="height: 42px; padding: 0 20px; background: #fff; color: var(--c-primary); font-weight: 700; border: none; border-radius: var(--r-btn); cursor: pointer; font-size: 14px; white-space: nowrap;">
                            {{ __('Soumettre') }}
                        </button>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                        <span style="font-size: 11px; color: rgba(255,255,255,0.6);">{{ __('Votre proposition sera soumise au vote de la communauté') }}</span>
                        <button type="button" @click="proposing = false" style="background: none; border: none; color: rgba(255,255,255,0.6); cursor: pointer; font-size: 12px;">{{ __('Annuler') }}</button>
                    </div>
                </form>
            </div>

            {{-- Success --}}
            <div x-show="submitted" x-cloak x-transition style="margin-top: 16px;">
                <span style="background: rgba(255,255,255,0.2); padding: 8px 20px; border-radius: var(--r-btn); font-size: 14px; font-weight: 600;">
                    ✓ {{ __('Merci ! Votre proposition est soumise au vote.') }}
                </span>
            </div>
            @endauth
        </div>

        {{-- A-Z Navigation --}}
        <div class="acr-sticky-nav">
            <div class="acr-letters">
                <button class="acr-letter-btn" :class="activeLetter === '' && 'acr-letter-active'" @click="setLetter('')">✱</button>
                @foreach(range('A', 'Z') as $char)
                    <button class="acr-letter-btn" :class="activeLetter === '{{ $char }}' && 'acr-letter-active'" @click="setLetter('{{ $char }}')">{{ $char }}</button>
                @endforeach
            </div>
        </div>

        {{-- Search + Category filter --}}
        <div class="acr-search">
            <div class="row">
                <div class="col-md-6 col-md-offset-1 col-sm-8 col-xs-12" style="margin-bottom: 10px;">
                    <input type="text" class="acr-search-input" x-model="search"
                           placeholder="{{ __('Rechercher un acronyme, un organisme ou un terme...') }}"
                           aria-label="{{ __('Rechercher un acronyme') }}"
                           @input="activeLetter = ''">
                </div>
                <div class="col-md-4 col-sm-4 col-xs-12">
                    <select class="acr-cat-select" x-model="activeCategory"
                            aria-label="{{ __('Filtrer par catégorie') }}" style="width: 100%;">
                        <option value="">{{ __('Toutes les catégories') }}</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.icon + ' ' + cat.name"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        {{-- Counter --}}
        <div class="acr-counter">
            <span x-text="filteredItems.length"></span> {{ __('résultat(s)') }}
        </div>

        {{-- Grid --}}
        <div class="row row-flex">
            <template x-for="item in filteredItems" :key="item.id">
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                    <a :href="'/acronymes-education/' + item.slug" class="acr-card">
                        <div class="acr-card-header">
                            <div class="acr-logo">
                                <template x-if="item.logo_url">
                                    <img :src="item.logo_url" :alt="item.acronym" loading="lazy">
                                </template>
                                <template x-if="!item.logo_url">
                                    <div class="acr-logo-fallback" :style="'background:' + (item.cat_color || '#6B7280')">
                                        <span x-text="item.acronym.charAt(0)"></span>
                                    </div>
                                </template>
                            </div>
                            <h3 class="acr-title" x-text="item.acronym"></h3>
                        </div>
                        <div class="acr-fullname" x-text="item.full_name"></div>
                        <div class="acr-footer">
                            <span class="acr-badge" :style="'background:' + (item.cat_color || '#6B7280')" x-text="item.cat_name"></span>
                            <span class="acr-link">{{ __('Voir la fiche') }} →</span>
                        </div>
                    </a>
                </div>
            </template>
        </div>

        {{-- Empty state --}}
        <div class="acr-empty" x-show="filteredItems.length === 0" x-cloak>
            <div style="font-size: 48px; margin-bottom: 12px;">🔍</div>
            <h3 style="font-family: var(--f-heading); color: var(--c-dark);">{{ __('Aucun résultat trouvé') }}</h3>
            <p>{{ __('Essayez de modifier vos filtres ou votre recherche.') }}</p>
            <button class="btn btn-default" @click="reset()" style="margin-top: 8px;">{{ __('Réinitialiser les filtres') }}</button>
        </div>

    </div>


</div>
@endsection

@push('scripts')
<script type="application/ld+json">
{
    "@@context": "https://schema.org/",
    "@@type": "DefinedTermSet",
    "@@id": "{{ route('acronyms.index') }}",
    "name": "{{ __('Acronymes de l\'éducation au Québec') }}",
    "description": "{{ __('Liste des acronymes et abréviations utilisés dans le système éducatif québécois.') }}"
}
</script>
<script>
function acronymApp() {
    return {
        items: @json($acronymsJson),
        categories: @json($categoriesJson),
        search: '',
        activeLetter: '',
        activeCategory: '',

        get filteredItems() {
            let r = this.items;
            if (this.search) {
                const q = this.search.toLowerCase();
                r = r.filter(i => i.acronym.toLowerCase().includes(q) || i.full_name.toLowerCase().includes(q));
            }
            if (this.activeLetter && !this.search) {
                r = r.filter(i => i.acronym.charAt(0).toUpperCase() === this.activeLetter);
            }
            if (this.activeCategory) {
                r = r.filter(i => i.cat_id == this.activeCategory);
            }
            return r.sort((a, b) => a.acronym.localeCompare(b.acronym));
        },

        setLetter(l) { this.activeLetter = l; this.search = ''; },
        reset() { this.search = ''; this.activeLetter = ''; this.activeCategory = ''; }
    }
}
</script>
@endpush
