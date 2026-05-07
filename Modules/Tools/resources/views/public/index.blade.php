<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Outils gratuits') . ' - ' . config('app.name'))
@section('meta_description', __('Des outils gratuits pour votre quotidien numérique : calculatrice de taxes, générateur de mots de passe, code QR, simulateur fiscal, roue de tirage et plus.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Outils gratuits')])
@endsection

@php
    // #190 Catégories libellées (ordre fixé pour cohérence UI)
    $catLabels = [
        'jeux' => '🎮 Jeux',
        'generation' => '✨ Génération',
        'calcul' => '🧮 Calcul',
        'communication' => '💬 Communication',
        'securite' => '🔒 Sécurité',
    ];
    // Top 3 views_count pour badge Tendance
    $topViewsIds = $tools->sortByDesc('views_count')->take(3)->pluck('id')->toArray();
    // Construire payload JSON pour Alpine (évite N+1 et XSS)
    $toolsPayload = $tools->map(function ($t) use ($topViewsIds) {
        $isNew = $t->created_at && $t->created_at->gt(now()->subDays(30));
        $isTrending = in_array($t->id, $topViewsIds, true) && (int) $t->views_count >= 2;
        return [
            'id' => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
            'description' => $t->description,
            'category' => $t->category ?? 'autre',
            'views' => (int) $t->views_count,
            'order' => (int) $t->sort_order,
            'created_ts' => $t->created_at?->timestamp ?? 0,
            'image' => $t->featured_image && file_exists(public_path($t->featured_image))
                ? asset($t->featured_image) . '?v=' . filemtime(public_path($t->featured_image))
                : null,
            'icon' => $t->icon ?? '🔧',
            'show_url' => route('tools.show', $t->slug),
            'under_construction' => false,
            'trending' => $isTrending,
            'new' => $isNew,
        ];
    })->values()->toArray();
@endphp

@section('content')
    <h1 class="sr-only">{{ __('Outils gratuits') }} — {{ config('app.name') }}</h1>
    <section class="wpo-blog-pg-section section-padding"
             x-data="toolsFilter()"
             x-init="init()">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="wpo-section-title" style="margin-bottom: 1.5rem;">
                        <h2>{{ __('Outils gratuits') }}</h2>
                        <p>{{ __('Des outils pratiques pour votre quotidien numérique.') }} <span x-text="filteredCount === total ? total + ' ' + '{{ __('outils disponibles') }}' : filteredCount + ' / ' + total + ' ' + '{{ __('résultats') }}'" class="text-muted" style="font-size: 0.9em;"></span></p>
                    </div>

                    {{-- Ad: tools top --}}
                    @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
                        {!! app(\Modules\Ads\Services\AdsRenderer::class)->render('tools-top') !!}
                    @endif

                    {{-- #190 : barre filtres : recherche + catégories chips + tri --}}
                    <div class="card shadow-sm mb-4" style="border-radius: var(--r-base); border: 1px solid #e5e7eb;">
                        <div class="card-body p-3 p-md-4">
                            <div class="row g-3 align-items-center">
                                {{-- Search --}}
                                <div class="col-12 col-md-6">
                                    <label for="tools-search" class="form-label visually-hidden">{{ __('Rechercher un outil') }}</label>
                                    <div class="position-relative">
                                        <span aria-hidden="true" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:18px;color:#6b7280;">🔍</span>
                                        <input
                                            type="search"
                                            id="tools-search"
                                            x-model="search"
                                            placeholder="{{ __('Rechercher un outil...') }}"
                                            aria-label="{{ __('Rechercher un outil') }}"
                                            class="form-control"
                                            style="padding-left:42px;height:46px;border-radius:999px;border:1px solid #d1d5db;font-size:0.95rem;"
                                            autocomplete="off">
                                        <button
                                            type="button"
                                            x-show="search.length > 0"
                                            @click="search = ''"
                                            aria-label="{{ __('Effacer la recherche') }}"
                                            style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:18px;cursor:pointer;color:#6b7280;padding:6px;"
                                            x-cloak>✕</button>
                                    </div>
                                </div>
                                {{-- Sort --}}
                                <div class="col-12 col-md-6">
                                    <label for="tools-sort" class="form-label visually-hidden">{{ __('Trier par') }}</label>
                                    <select id="tools-sort" x-model="sortBy"
                                            class="form-select"
                                            style="height:46px;border-radius:999px;border:1px solid #d1d5db;font-size:0.95rem;font-weight:600;color:#053D4A;">
                                        <option value="popularity">{{ __('Trier : Popularité (+ utilisés)') }}</option>
                                        <option value="alpha">{{ __('Trier : Alphabétique (A → Z)') }}</option>
                                        <option value="recent">{{ __('Trier : Plus récents') }}</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Chips catégories --}}
                            <div class="d-flex flex-wrap gap-2 mt-3" role="group" aria-label="{{ __('Filtrer par catégorie') }}">
                                <button type="button"
                                        @click="selectedCategory = 'all'"
                                        :class="selectedCategory === 'all' ? 'tools-chip tools-chip-active' : 'tools-chip'"
                                        :aria-pressed="selectedCategory === 'all'">
                                    {{ __('Tous') }} <span class="tools-chip-count" x-text="total"></span>
                                </button>
                                @foreach($catLabels as $catKey => $catLabel)
                                    @if(! empty($categories[$catKey]))
                                        <button type="button"
                                                @click="selectedCategory = '{{ $catKey }}'"
                                                :class="selectedCategory === '{{ $catKey }}' ? 'tools-chip tools-chip-active' : 'tools-chip'"
                                                :aria-pressed="selectedCategory === '{{ $catKey }}'">
                                            {{ $catLabel }} <span class="tools-chip-count">{{ $categories[$catKey] }}</span>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Grid résultats --}}
                    <div class="row" id="tools-grid">
                        <template x-for="tool in filteredTools" :key="tool.id">
                            <div class="col-lg-4 col-md-6 col-12 mb-4">
                                <div class="card h-100 shadow-sm position-relative tools-card" style="border-radius: var(--r-base); overflow: hidden; transition: transform 0.2s;">
                                    {{-- Badges --}}
                                    <div style="position:absolute;top:10px;left:10px;z-index:10;display:flex;flex-direction:column;gap:6px;">
                                        <span x-show="tool.trending"
                                              class="tools-badge tools-badge-trending"
                                              x-cloak>🔥 {{ __('Tendance') }}</span>
                                        <span x-show="tool.new"
                                              class="tools-badge tools-badge-new"
                                              x-cloak>✨ {{ __('Nouveau') }}</span>
                                    </div>

                                    <template x-if="tool.image">
                                        <img :src="tool.image" class="card-img-top" :alt="tool.name" width="320" height="180" style="height: 180px; object-fit: cover;" loading="lazy" decoding="async">
                                    </template>
                                    <template x-if="! tool.image">
                                        <div style="height: 180px; background: linear-gradient(135deg, var(--c-primary), var(--c-primary-hover)); display: flex; align-items: center; justify-content: center;">
                                            <span style="font-size: 48px; color: rgba(255,255,255,0.3);" x-text="tool.icon"></span>
                                        </div>
                                    </template>

                                    <div class="card-body d-flex flex-column">
                                        <h3 class="card-title" style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.15rem;" x-text="tool.name"></h3>
                                        <p class="card-text text-muted flex-grow-1" x-text="tool.description"></p>
                                        <a :href="tool.show_url" class="ct-btn ct-btn-accent mt-2">
                                            {{ __('Utiliser') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="filteredCount === 0" x-cloak class="row">
                        <div class="col-12">
                            <div class="alert alert-info text-center p-4" role="status">
                                <div style="font-size:48px;margin-bottom:12px;">🔍</div>
                                <h4 style="font-weight:700;color:var(--c-dark);">{{ __('Aucun outil ne correspond') }}</h4>
                                <p class="text-muted mb-3" x-text="search ? '{{ __('Aucun résultat pour') }} : « ' + search + ' »' : '{{ __('Essayez une autre catégorie.') }}'"></p>
                                <button type="button" @click="search = ''; selectedCategory = 'all'" class="ct-btn ct-btn-outline">
                                    {{ __('Réinitialiser les filtres') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @verbatim
        <script>
            function toolsFilter() {
                return {
                    tools: @endverbatim @json($toolsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) @verbatim,
                    search: '',
                    selectedCategory: 'all',
                    sortBy: 'popularity',
                    init() {
                        const params = new URLSearchParams(window.location.search);
                        if (params.get('q')) this.search = params.get('q');
                        if (params.get('cat')) this.selectedCategory = params.get('cat');
                        if (params.get('sort')) this.sortBy = params.get('sort');
                    },
                    get total() { return this.tools.length; },
                    get filteredTools() {
                        const q = this.search.trim().toLowerCase();
                        let list = this.tools.filter(t => {
                            if (this.selectedCategory !== 'all' && t.category !== this.selectedCategory) return false;
                            if (q.length === 0) return true;
                            const haystack = (t.name + ' ' + t.description + ' ' + t.category).toLowerCase();
                            return haystack.includes(q);
                        });
                        if (this.sortBy === 'popularity') {
                            list.sort((a, b) => b.views - a.views || a.order - b.order);
                        } else if (this.sortBy === 'alpha') {
                            list.sort((a, b) => a.name.localeCompare(b.name, 'fr'));
                        } else if (this.sortBy === 'recent') {
                            list.sort((a, b) => b.created_ts - a.created_ts);
                        }
                        return list;
                    },
                    get filteredCount() { return this.filteredTools.length; },
                };
            }
        </script>
        @endverbatim

        <style>
            .tools-chip {
                background: #f3f4f6;
                color: #053D4A;
                border: 1px solid #d1d5db;
                padding: 8px 16px;
                border-radius: 999px;
                font-size: 0.875rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 150ms ease;
                min-height: 36px;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .tools-chip:hover { background: #e5e7eb; border-color: #9ca3af; }
            .tools-chip:focus-visible { outline: 3px solid #C2410C; outline-offset: 2px; }
            .tools-chip-active {
                background: #053D4A;
                color: #fff;
                border-color: #053D4A;
            }
            .tools-chip-active:hover { background: #064E5A; border-color: #064E5A; }
            .tools-chip-count {
                background: rgba(255,255,255,.25);
                padding: 2px 8px;
                border-radius: 999px;
                font-size: 0.75rem;
                font-weight: 700;
            }
            .tools-chip:not(.tools-chip-active) .tools-chip-count {
                background: #053D4A;
                color: #fff;
            }
            .tools-badge {
                font-size: 0.7rem;
                font-weight: 700;
                padding: 4px 10px;
                border-radius: 999px;
                box-shadow: 0 2px 6px rgba(0,0,0,.12);
                display: inline-flex;
                align-items: center;
                gap: 3px;
            }
            .tools-badge-trending { background: #DC2626; color: #fff; }
            .tools-badge-new { background: #059669; color: #fff; }
            .tools-card { transition: transform .2s ease, box-shadow .2s ease; }
            .tools-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08) !important; }
            #tools-search:focus { border-color: #053D4A; box-shadow: 0 0 0 3px rgba(5, 61, 74, .15); }
            #tools-sort:focus { border-color: #053D4A; box-shadow: 0 0 0 3px rgba(5, 61, 74, .15); }
        </style>
    </section>
@endsection
