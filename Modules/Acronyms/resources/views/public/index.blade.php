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
        background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-dark) 100%);
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

    @keyframes spin { to { transform: rotate(360deg); } }

    .row-flex { display: flex; flex-wrap: wrap; }
    .row-flex > [class*='col-'] { display: flex; flex-direction: column; margin-bottom: 20px; }
</style>
@endpush

@section('content')
<div class="acr-wrapper">
    <div class="container" x-data="acronymApp()">

        {{-- Hero + 2-step wizard wrapper --}}
        <div x-data="{
            step: 0, submitted: false, acronym: '', fullname: '',
            website_url: '', logo_url: '', descriptionText: '', scraping: false, scrapeError: '',
            async scrapeUrl() {
                if (!this.website_url || this.scraping) return;
                this.scraping = true;
                this.scrapeError = '';
                try {
                    const res = await fetch('/api/scrape-meta', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                        body: JSON.stringify({ url: this.website_url })
                    });
                    if (!res.ok) throw new Error();
                    const d = await res.json();
                    if (!this.descriptionText) this.descriptionText = (this.fullname ? this.fullname + ' (' + this.acronym + ') — ' : '') + (d.og_description || d.description || '');
                    if (!this.logo_url) this.logo_url = d.og_image || d.favicon || '';
                } catch { this.scrapeError = '{{ __('Impossible de récupérer les informations automatiquement.') }}'; }
                finally { this.scraping = false; }
            }
        }">
            <div class="acr-hero">
                <h1>🎓 {{ __('Acronymes de l\'éducation au Québec') }}</h1>
                <p>{{ __('Le glossaire complet pour naviguer dans le jargon du système éducatif québécois') }}</p>

                <div style="display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap;">
                    <span class="acr-stats-badge">
                        <span x-text="filteredItems.length"></span> {{ __('acronymes répertoriés') }}
                    </span>
                    @if(class_exists(\Modules\Roadmap\Models\Board::class))
                        @auth
                            <button type="button" x-show="step === 0 && !submitted" @click="step = 1"
                                style="background: rgba(255,255,255,0.15); color: #fff; font-weight: 600; padding: 8px 20px; border-radius: var(--r-btn); border: 1px solid rgba(255,255,255,0.4); cursor: pointer; font-size: 13px; transition: all 0.2s;"
                                onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                                + {{ __('Proposer un acronyme') }}
                            </button>
                        @else
                            <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour proposer un acronyme.') }}' })"
                                style="background: rgba(255,255,255,0.15); color: #fff; font-weight: 600; padding: 8px 20px; border-radius: var(--r-btn); border: 1px solid rgba(255,255,255,0.4); cursor: pointer; font-size: 13px;">
                                {{ __('Proposer un acronyme') }}
                            </button>
                        @endauth
                    @endif
                </div>

                @auth
                {{-- Step 1 inline: Acronyme + Nom complet --}}
                <div x-show="step === 1" x-cloak x-transition.duration.300ms
                     style="margin-top: 20px; background: rgba(255,255,255,0.12); border-radius: var(--r-base); padding: 20px; max-width: 560px; margin-left: auto; margin-right: auto;">
                    <div style="font-size: 11px; color: rgba(255,255,255,0.5); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">{{ __('Étape 1 sur 2 – Identification') }}</div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <input type="text" x-model="acronym" placeholder="{{ __('Acronyme (ex: RÉCIT)') }}" aria-label="{{ __('Acronyme') }}"
                            style="flex: 0 0 140px; height: 42px; padding: 0 14px; border: 2px solid #E5E7EB; border-radius: var(--r-base); font-size: 15px; font-weight: 700; text-transform: uppercase; background: #fff; color: var(--c-dark); outline: none;">
                        <input type="text" x-model="fullname" placeholder="{{ __('Nom complet') }}" aria-label="{{ __('Nom complet de l acronyme') }}"
                            style="flex: 1; min-width: 180px; height: 42px; padding: 0 14px; border: 2px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; background: #fff; color: var(--c-dark); outline: none;">
                        <button type="button" @click="if(acronym.trim() && fullname.trim()) { descriptionText = fullname + ' (' + acronym + ') — '; step = 2; }"
                            :style="'height:42px;padding:0 20px;background:var(--c-primary);color:#fff;font-weight:700;border:2px solid rgba(255,255,255,0.3);border-radius:var(--r-btn);cursor:pointer;font-size:14px;white-space:nowrap;transition:all 0.2s;' + ((!acronym.trim() || !fullname.trim()) ? 'opacity:0.5;cursor:not-allowed;' : '')"
                            onmouseover="this.style.background='var(--c-dark)'" onmouseout="this.style.background='var(--c-primary)'">
                            {{ __('Continuer') }} →
                        </button>
                    </div>
                    <div style="text-align: right; margin-top: 6px;">
                        <button type="button" @click="step = 0; acronym = ''; fullname = ''" style="background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 12px;">{{ __('Annuler') }}</button>
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

            {{-- Step 2: Details (white card below hero) --}}
            @auth
            @if(class_exists(\Modules\Roadmap\Models\Board::class))
            <div x-show="step === 2" x-cloak x-transition.duration.400ms
                 style="background: #fff; border: 2px solid #E5E7EB; border-top: none; border-radius: 0 0 var(--r-base) var(--r-base); padding: 28px; max-width: 100%; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <span style="font-size: 11px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 1px;">{{ __('Étape 2 sur 2 – Détails') }}</span>
                        <h3 style="font-family: var(--f-heading); color: var(--c-dark); margin: 4px 0 0; font-size: 16px;">
                            {{ __('Complétez les informations pour') }} <strong x-text="acronym" style="color: var(--c-primary);"></strong>
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
                    <input type="hidden" name="source" value="acronymes">
                    <input type="hidden" name="category" value="Acronymes éducation">
                    <input type="hidden" name="title" :value="acronym">
                    {{-- Description = nom complet + détails --}}

                    <div class="row">
                        <div class="col-md-6" style="margin-bottom: 14px;">
                            <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Site web officiel') }}</label>
                            <div style="display: flex; gap: 6px;">
                                <input type="url" name="website_url" x-model="website_url" placeholder="https://exemple.qc.ca" aria-label="{{ __('Site web officiel') }}"
                                    @blur="scrapeUrl()"
                                    style="flex: 1; height: 40px; padding: 0 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; outline: none;"
                                    onfocus="this.style.borderColor='var(--c-primary)'" onblur="this.style.borderColor='#E5E7EB'">
                                <button type="button" @click="scrapeUrl()" :disabled="scraping || !website_url"
                                    :style="(!website_url || scraping) ? 'opacity:0.5;cursor:not-allowed' : ''"
                                    style="height: 40px; padding: 0 12px; background: #F3F4F6; border: 1px solid #E5E7EB; border-radius: var(--r-base); cursor: pointer; font-size: 12px; font-weight: 600; color: var(--c-dark); white-space: nowrap; display: flex; align-items: center; gap: 4px;">
                                    <svg x-show="scraping" x-cloak width="14" height="14" viewBox="0 0 24 24" style="animation: spin 0.8s linear infinite;">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" opacity="0.25"></circle>
                                        <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round"></path>
                                    </svg>
                                    <span x-text="scraping ? '' : '{{ __('Auto-remplir') }}'"></span>
                                </button>
                            </div>
                            <p x-show="scrapeError" x-cloak x-text="scrapeError" style="color: #B91C1C; font-size: 11px; margin: 4px 0 0;"></p>
                        </div>
                        <div class="col-md-6" style="margin-bottom: 14px;">
                            <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('URL du logo') }} <small style="color: #9CA3AF; font-weight: 400;">({{ __('auto-détecté') }})</small></label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="url" name="logo_url" x-model="logo_url" placeholder="https://exemple.qc.ca/logo.png" aria-label="{{ __('URL du logo') }}"
                                    style="flex: 1; height: 40px; padding: 0 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; outline: none;"
                                    onfocus="this.style.borderColor='var(--c-primary)'" onblur="this.style.borderColor='#E5E7EB'">
                                <template x-if="logo_url">
                                    <img :src="logo_url" style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain; border: 1px solid #E5E7EB;" alt="Logo">
                                </template>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Description courte') }} <span style="color: #E74C3C;">*</span></label>
                        <textarea name="description" required rows="3" x-model="descriptionText" aria-label="{{ __('Description courte') }}"
                            :placeholder="'{{ __('Décrivez') }} ' + acronym + ' {{ __('en 2-3 phrases : mission, rôle, public cible...') }}'"
                            style="width: 100%; padding: 10px 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; outline: none; resize: vertical;"
                            onfocus="this.style.borderColor='var(--c-primary)'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6" style="margin-bottom: 14px;">
                            <label style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 4px; font-size: 13px;">{{ __('Catégorie') }}</label>
                            <select name="acr_category"
                                style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; background: #fff;">
                                <option value="">{{ __('Choisir...') }}</option>
                                @isset($categories)
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->name }}">{{ $cat->icon ?? '' }} {{ $cat->name }}</option>
                                    @endforeach
                                @endisset
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

                    <p style="font-size: 12px; color: #9CA3AF; margin: 4px 0 0;">
                        {{ __('La communauté votera sur votre proposition dans la section Idées et votes.') }}
                    </p>
                </form>
            </div>
            @endif
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
        <div class="acr-counter" aria-live="polite">
            <span x-text="visibleItems.length"></span> {{ __('sur') }} <span x-text="filteredItems.length"></span> {{ __('résultat(s)') }}
        </div>

        {{-- Grid --}}
        <div class="row row-flex">
            <template x-for="item in visibleItems" :key="item.id">
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
                            @if(trait_exists(\Modules\Voting\Traits\HasCommunityVotes::class))
                            <span @click.prevent.stop style="display:inline-flex;align-items:center;gap:3px;"
                                  x-data="{ vc: item.vote_count || 0, vd: false }"
                                  @click="
                                      @auth
                                          fetch('/community/vote/acronym/' + item.id, {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>r.json()).then(d=>{vc=d.count;vd=d.voted})
                                      @else
                                          $dispatch('open-auth-modal', {message:'{{ __('Connectez-vous pour voter.') }}'})
                                      @endauth
                                  ">
                                <svg :style="vd ? 'color:#1877F2' : 'color:#9CA3AF'" width="14" height="14" viewBox="0 0 24 24" :fill="vd ? '#1877F2' : 'none'" stroke="currentColor" stroke-width="2"><path d="M7 22V11l5-9a2 2 0 0 1 2 2v4h5.5a2 2 0 0 1 2 2.1l-1.5 9A2 2 0 0 1 18 21H7z"/><path d="M2 13v8a1 1 0 0 0 1 1h3V12H3a1 1 0 0 0-1 1z"/></svg>
                                <span x-show="vc > 0" x-text="vc" :style="vd ? 'color:#1877F2;font-weight:600' : 'color:#9CA3AF'" style="font-size:12px;"></span>
                            </span>
                            @endif
                            <span class="acr-link">{{ __('Voir la fiche') }} →</span>
                        </div>
                    </a>
                </div>
            </template>
        </div>

        {{-- Sentinel : charge plus au scroll --}}
        <div x-show="hasMore" x-intersect="loadMore()" class="text-center" style="padding: 24px 0;" role="status" aria-label="{{ __('Chargement en cours') }}">
            <div style="display: inline-block; width: 24px; height: 24px; border: 3px solid #E5E7EB; border-top-color: var(--c-primary); border-radius: 50%; animation: spin 0.6s linear infinite;"></div>
            <p style="color: #9CA3AF; font-size: 13px; margin-top: 8px;">{{ __('Chargement...') }}</p>
        </div>

        {{-- Empty state --}}
        <div class="acr-empty" x-show="filteredItems.length === 0" x-cloak>
            <div style="font-size: 48px; margin-bottom: 12px;">🔍</div>
            <h3 style="font-family: var(--f-heading); color: var(--c-dark);">{{ __('Aucun résultat trouvé') }}</h3>
            <p>{{ __('Essayez de modifier vos filtres ou votre recherche.') }}</p>
            <button class="ct-btn ct-btn-outline ct-btn-sm" @click="reset()" style="margin-top:8px;">{{ __('Réinitialiser les filtres') }}</button>
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
        displayCount: 30,
        _lastFilterKey: '',

        get filterKey() { return this.search + '|' + this.activeLetter + '|' + this.activeCategory; },

        get filteredItems() {
            const key = this.filterKey;
            if (key !== this._lastFilterKey) { this.displayCount = 30; this._lastFilterKey = key; }
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

        get visibleItems() { return this.filteredItems.slice(0, this.displayCount); },
        get hasMore() { return this.displayCount < this.filteredItems.length; },
        loadMore() { if (this.hasMore) this.displayCount += 30; },

        setLetter(l) { this.activeLetter = l; this.search = ''; },
        reset() { this.search = ''; this.activeLetter = ''; this.activeCategory = ''; }
    }
}
</script>
@endpush
