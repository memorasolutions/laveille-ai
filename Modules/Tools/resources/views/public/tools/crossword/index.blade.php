{{-- 2026-05-05 #94 v2 : index publique grilles mots-croisés - aligné charte Memora + WCAG 2.2 AAA --}}
@extends(fronttheme_layout())

@section('title', $pageTitle . ' - ' . config('app.name'))
@section('meta_description', $pageDescription)
@section('og_type', 'website')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Mots-croisés en ligne')])
@endsection

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $pageDescription,
    'url' => url('/jeumc'),
    'mainEntity' => [
        '@type' => 'ItemList',
        'numberOfItems' => $totalPublic,
        'itemListElement' => $presets->take(12)->values()->map(fn ($p, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $p->name,
            'url' => url('/jeumc/'.$p->public_id),
        ])->all(),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@push('styles')
<style>
    /* 2026-05-05 #94 v3 : utilise les classes standards .ct-btn .ct-btn-primary du theme pour hover/focus uniformes */
    .cwi-page-intro { color: var(--c-text-muted); margin-bottom: 1.5rem; font-size: 1rem; max-width: 70ch; }
    .cwi-stats-line { display: flex; flex-wrap: wrap; gap: 1.25rem; align-items: center; margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: var(--c-primary-light); border-radius: var(--r-base); border: 1px solid #c7e9ee; }
    .cwi-stats-line strong { color: var(--c-primary-hover); font-size: 1.125rem; font-weight: 800; }
    .cwi-stats-line .ct-btn { margin-left: auto; }

    .cwi-filters { background: #fff; border: 1px solid #e5e7eb; border-radius: var(--r-base); padding: 1rem; margin-bottom: 1.5rem; display: grid; gap: 0.75rem; grid-template-columns: 1fr; }
    @media (min-width: 640px) { .cwi-filters { grid-template-columns: 1.5fr 1fr 1fr 1fr; } }
    .cwi-filter-group label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--c-text-secondary); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.4px; font-family: var(--f-heading); }
    .cwi-filters .form-control, .cwi-filters .form-select { min-height: 44px; font-size: 0.95rem; color: var(--c-dark); }
    .cwi-search-wrap { position: relative; }
    .cwi-search-wrap .icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--c-text-secondary); pointer-events: none; z-index: 1; }
    .cwi-search-wrap .form-control { padding-left: 2.25rem; }

    .cwi-results-summary { font-size: 0.9rem; color: var(--c-text-secondary); margin-bottom: 1rem; }
    .cwi-results-summary a { color: var(--c-primary); font-weight: 700; text-decoration: underline; }
    .cwi-results-summary a:hover, .cwi-results-summary a:focus-visible { color: var(--c-primary-hover); }

    .cwi-grid { display: grid; gap: 1.25rem; grid-template-columns: 1fr; margin-bottom: 1.5rem; }
    @media (min-width: 640px) { .cwi-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .cwi-grid { grid-template-columns: repeat(3, 1fr); } }

    .cwi-card { background: #fff; border: 1px solid #e5e7eb; border-radius: var(--r-base); padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s; }
    .cwi-card:hover, .cwi-card:focus-within { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(11, 114, 133, 0.15); border-color: var(--c-primary); }
    .cwi-card-thumb { width: 100%; aspect-ratio: 16 / 9; background: linear-gradient(135deg, var(--c-primary), var(--c-primary-hover)); border-radius: var(--r-btn); display: flex; align-items: center; justify-content: center; color: #FFF7ED; font-family: var(--f-heading); font-weight: 800; font-size: 1.75rem; letter-spacing: 1.5px; flex-shrink: 0; position: relative; overflow: hidden; min-height: 44px; transition: filter 0.2s; }
    .cwi-card-thumb:hover, .cwi-card-thumb:focus-visible { filter: brightness(1.08); }
    .cwi-card-thumb::before { content: ""; position: absolute; inset: 0; background-image: linear-gradient(45deg, rgba(255,247,237,0.05) 25%, transparent 25%, transparent 50%, rgba(255,247,237,0.05) 50%, rgba(255,247,237,0.05) 75%, transparent 75%); background-size: 14px 14px; }
    .cwi-card-thumb span { position: relative; z-index: 1; text-align: center; line-height: 1.1; padding: 0.5rem; }

    .cwi-card-title { font-family: var(--f-heading); font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--c-dark); line-height: 1.3; }
    .cwi-card-title a { color: inherit; text-decoration: none; display: block; min-height: 44px; padding: 0.25rem 0; }
    .cwi-card-title a:hover, .cwi-card-title a:focus-visible { color: var(--c-primary-hover); text-decoration: underline; }

    .cwi-meta { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
    .cwi-badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.625rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-family: var(--f-heading); }
    .cwi-badge-easy { background: #d1fae5; color: #064e3b; }
    .cwi-badge-medium { background: #fef3c7; color: #78350f; }
    .cwi-badge-hard { background: #fee2e2; color: #7f1d1d; }
    .cwi-theme-tag { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.8rem; color: var(--c-text-secondary); font-weight: 500; }

    .cwi-stats { display: flex; flex-wrap: wrap; gap: 0.875rem; font-size: 0.85rem; color: var(--c-text-secondary); margin-top: auto; }
    .cwi-stats span { display: inline-flex; align-items: center; gap: 0.25rem; }
    .cwi-stats svg { color: var(--c-primary); flex-shrink: 0; }

    /* Bouton "Jouer" : utilise .ct-btn .ct-btn-primary mais avec largeur 100% pour cohérence card */
    .cwi-card .ct-btn-primary { justify-content: center; min-height: 44px; margin-top: 0.5rem; }

    .cwi-empty { text-align: center; padding: 3rem 1.5rem; background: var(--c-primary-light); border: 2px dashed #c7e9ee; border-radius: var(--r-base); color: var(--c-text-secondary); }
    .cwi-empty h2 { margin: 0 0 0.5rem; color: var(--c-dark); font-family: var(--f-heading); font-weight: 700; }
    .cwi-empty p { color: var(--c-text-secondary); margin-bottom: 1rem; }

    .cwi-pagination { margin-top: 2rem; display: flex; justify-content: center; }
    .cwi-pagination .pagination { flex-wrap: wrap; justify-content: center; gap: 0.25rem; }
    .cwi-pagination .page-link { color: var(--c-primary); border-color: #cbd5e1; min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s, color 0.2s, border-color 0.2s; }
    .cwi-pagination .page-item.active .page-link { background: var(--c-primary); border-color: var(--c-primary); color: #fff; }
    .cwi-pagination .page-link:hover, .cwi-pagination .page-link:focus-visible { background: var(--c-primary); border-color: var(--c-primary); color: #fff; }

    @media (max-width: 639px) {
        .cwi-stats-line .ct-btn { margin-left: 0; width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <h1 style="font-family: var(--f-heading); margin-bottom: 0.5rem; color: var(--c-dark); font-weight: 800;">{{ __('Mots-croisés à jouer en ligne') }}</h1>
        <p class="cwi-page-intro">{{ __('Grilles partagées par la communauté laveille.ai — créées par des enseignants, formateurs et passionnés. Toutes gratuites.') }}</p>

        {{-- Stats line + CTA création --}}
        <div class="cwi-stats-line" role="region" aria-label="{{ __('Statistiques de la collection') }}">
            <span><strong>{{ number_format($totalPublic) }}</strong> {{ __('grilles publiques') }}</span>
            <a href="{{ url('/outils/mots-croises') }}" class="ct-btn ct-btn-primary d-inline-flex align-items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Créer ma propre grille') }}
            </a>
        </div>

        {{-- Filtres --}}
        <form method="GET" action="{{ url('/jeumc') }}" class="cwi-filters" role="search" aria-label="{{ __('Filtres de recherche') }}">
            <div class="cwi-filter-group">
                <label for="cwi-q">{{ __('Recherche') }}</label>
                <div class="cwi-search-wrap">
                    <span class="icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input id="cwi-q" type="search" name="q" value="{{ $search }}" placeholder="{{ __('Titre ou thème...') }}" class="form-control" autocomplete="off">
                </div>
            </div>
            <div class="cwi-filter-group">
                <label for="cwi-diff">{{ __('Difficulté') }}</label>
                <select id="cwi-diff" name="difficulty" class="form-select" onchange="this.form.submit()">
                    <option value="">{{ __('Toutes') }}</option>
                    <option value="Facile" @selected($difficulty === 'Facile')>{{ __('Facile') }}</option>
                    <option value="Moyen" @selected($difficulty === 'Moyen')>{{ __('Moyen') }}</option>
                    <option value="Difficile" @selected($difficulty === 'Difficile')>{{ __('Difficile') }}</option>
                </select>
            </div>
            <div class="cwi-filter-group">
                <label for="cwi-period">{{ __('Période') }}</label>
                <select id="cwi-period" name="period" class="form-select" onchange="this.form.submit()">
                    <option value="">{{ __('Toutes') }}</option>
                    <option value="7d" @selected($period === '7d')>{{ __('7 derniers jours') }}</option>
                    <option value="30d" @selected($period === '30d')>{{ __('30 derniers jours') }}</option>
                </select>
            </div>
            <div class="cwi-filter-group">
                <label for="cwi-sort">{{ __('Trier par') }}</label>
                <select id="cwi-sort" name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="recent" @selected($sort === 'recent')>{{ __('Plus récents') }}</option>
                    <option value="popular" @selected($sort === 'popular')>{{ __('Plus populaires') }}</option>
                    <option value="oldest" @selected($sort === 'oldest')>{{ __('Plus anciens') }}</option>
                </select>
            </div>
        </form>

        {{-- Résumé recherche --}}
        @if($search || $difficulty || $period)
            <p class="cwi-results-summary">
                <strong>{{ $presets->total() }}</strong> {{ __('grille(s) trouvée(s)') }}
                @if($search) {{ __('pour') }} <em>"{{ $search }}"</em>@endif
                @if($difficulty) · <em>{{ $difficulty }}</em>@endif
                @if($period === '7d') · <em>{{ __('7 jours') }}</em>@endif
                @if($period === '30d') · <em>{{ __('30 jours') }}</em>@endif
                · <a href="{{ url('/jeumc') }}">{{ __('Réinitialiser') }}</a>
            </p>
        @endif

        {{-- Grid ou empty --}}
        @if($presets->isEmpty())
            <div class="cwi-empty">
                <h2>{{ __('Aucune grille ne correspond à votre recherche.') }}</h2>
                <p>{{ __('Essayez d\'élargir vos filtres ou créez la première !') }}</p>
                <a href="{{ url('/outils/mots-croises') }}" class="ct-btn ct-btn-primary d-inline-flex align-items-center gap-2">
                    {{ __('Créer ma première grille') }}
                </a>
            </div>
        @else
            <div class="cwi-grid">
                @foreach($presets as $preset)
                    @php
                        $diff = $preset->difficulty ?? 'Moyen';
                        $diffClass = match($diff) {
                            'Facile' => 'cwi-badge-easy',
                            'Difficile' => 'cwi-badge-hard',
                            default => 'cwi-badge-medium',
                        };
                        $authorName = optional($preset->user)->name ?? __('Anonyme');
                        $thumbInitials = mb_strtoupper(mb_substr(preg_replace('/[^a-zA-Z]/', '', $preset->name) ?: '🧩', 0, 3, 'UTF-8'));
                    @endphp
                    <article class="cwi-card">
                        <a href="{{ url('/jeumc/'.$preset->public_id) }}" class="cwi-card-thumb" aria-hidden="true" tabindex="-1">
                            <span>{{ $thumbInitials }}</span>
                        </a>
                        <h2 class="cwi-card-title"><a href="{{ url('/jeumc/'.$preset->public_id) }}">{{ $preset->name }}</a></h2>
                        <div class="cwi-meta">
                            <span class="cwi-badge {{ $diffClass }}">{{ $diff }}</span>
                            @if($preset->theme)
                                <span class="cwi-theme-tag">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                    {{ $preset->theme }}
                                </span>
                            @endif
                        </div>
                        <div class="cwi-stats">
                            <span title="{{ __('Auteur') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                {{ $authorName }}
                            </span>
                            <span title="{{ __('Date de mise à jour') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ $preset->updated_at->diffForHumans() }}
                            </span>
                            <span title="{{ __('Nombre de mots dans la grille') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 7V4h16v3"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
                                {{ $preset->word_count }} {{ __('mots') }}
                            </span>
                            @if(($preset->play_count ?? 0) > 0)
                                <span title="{{ __('Nombre de parties jouées') }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                    {{ $preset->play_count }}
                                </span>
                            @endif
                        </div>
                        <a href="{{ url('/jeumc/'.$preset->public_id) }}" class="ct-btn ct-btn-primary d-inline-flex align-items-center justify-content-center gap-2" aria-label="{{ __('Jouer à la grille') }} {{ $preset->name }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            {{ __('Jouer') }}
                        </a>
                        {{-- 2026-05-05 #116 : actions propriétaire (modifier + rendre privée) si auth + owner --}}
                        @auth
                            @if(auth()->id() === $preset->user_id)
                            @php
                                $togglePrivateUrl = url('/user/mots-croises/'.$preset->public_id.'/toggle-public');
                                $confirmTogglePrivate = __('Rendre privée la grille « :name » ? Le lien public sera désactivé immédiatement.', ['name' => $preset->name]);
                            @endphp
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                <a href="{{ url('/outils/mots-croises?preset='.$preset->public_id) }}"
                                   class="ct-btn ct-btn-outline d-inline-flex align-items-center justify-content-center gap-2"
                                   style="min-height:36px;font-size:.75rem;flex:1"
                                   aria-label="{{ __('Modifier ma grille') }} {{ $preset->name }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    <span>{{ __('Modifier ma grille') }}</span>
                                </a>
                                <button type="button"
                                        class="ct-btn ct-btn-outline d-inline-flex align-items-center justify-content-center gap-2"
                                        style="min-height:36px;font-size:.75rem;flex:1"
                                        onclick="window.dispatchEvent(new CustomEvent('open-confirm-global', { detail: { message: @js($confirmTogglePrivate), callback: () => { var tk=document.querySelector('meta[name=csrf-token]')?.content; fetch(@js($togglePrivateUrl),{method:'POST',headers:{'X-CSRF-TOKEN':tk,'Accept':'application/json'}}).then(r=>r.json()).then(d=>{ if(d.success){ document.querySelectorAll('.cwi-card').forEach(c=>{ if(c.contains(event.target)) c.style.display='none'; }); window.dispatchEvent(new CustomEvent('toast-show',{detail:{message:d.message||@js(__('Grille passée en privée.')),variant:'success'}})); } else { window.dispatchEvent(new CustomEvent('toast-show',{detail:{message:d.message||@js(__('Erreur.')),variant:'error'}})); } }); } } }))"
                                        aria-label="{{ __('Rendre la grille privée') }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <span>{{ __('Rendre privée') }}</span>
                                </button>
                            </div>
                            @endif
                        @endauth

                        {{-- 2026-05-05 #115 : modération admin (suppression via modale Memora confirm-global) --}}
                        @can('view_admin_panel')
                        @php
                            $deleteUrl = url('/admin/jeumc/'.$preset->public_id.'/moderate-delete');
                            $confirmMessage = __('Supprimer la grille « :name » de :author ? Action de modération réversible (soft delete).', ['name' => $preset->name, 'author' => $authorName]);
                        @endphp
                        <button type="button"
                                class="ct-btn ct-btn-outline d-inline-flex align-items-center justify-content-center gap-2"
                                style="min-height:36px;font-size:.75rem;margin-top:.5rem;border-color:#dc2626;color:#dc2626"
                                onclick="window.dispatchEvent(new CustomEvent('open-confirm-global', { detail: { message: @js($confirmMessage), callback: () => { var btn=this; var tk=document.querySelector('meta[name=csrf-token]')?.content; fetch(@js($deleteUrl),{method:'POST',headers:{'X-CSRF-TOKEN':tk,'Accept':'application/json'}}).then(r=>r.json()).then(d=>{ if(d.success){ document.querySelectorAll('.cwi-card').forEach(c=>{ if(c.contains(event.target)) c.style.display='none'; }); window.dispatchEvent(new CustomEvent('toast-show',{detail:{message:d.message||@js(__('Grille supprimée.')),variant:'success'}})); } else { window.dispatchEvent(new CustomEvent('toast-show',{detail:{message:d.message||@js(__('Erreur de suppression.')),variant:'error'}})); } }); } } }))"
                                aria-label="{{ __('Modérer : supprimer cette grille') }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            <span>{{ __('Modérer (admin)') }}</span>
                        </button>
                        @endcan
                    </article>
                @endforeach
            </div>

            @if($presets->hasPages())
                <nav class="cwi-pagination" aria-label="{{ __('Navigation par pages') }}">
                    {!! $presets->links() !!}
                </nav>
            @endif
        @endif
    </div>
</section>
@endsection
