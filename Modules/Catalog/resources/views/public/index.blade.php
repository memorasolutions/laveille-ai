<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Nos formations').' - '.config('app.name'))

@if(config('catalog.noindex'))
    @section('page_noindex', true)
@endif

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Nos formations')])
@endsection

@push('styles')
<style>
    .catalog { padding: 28px 0; background: #fff; color: #111827; }
    .catalog h1, .catalog h2, .catalog h3 { color: #111827; }
    .catalog .catalog-filters { display: flex; flex-wrap: wrap; gap: 10px; margin: 18px 0; }
    .catalog .catalog-filters[x-cloak] { display: none !important; }
    .catalog .catalog-filter { border: 1px solid #134e4a; border-radius: 6px; padding: 6px 14px; background: #fff; color: #134e4a; cursor: pointer; font-size: 14px; }
    .catalog .catalog-filter[aria-pressed="true"] { background: #134e4a; color: #fff; }
    .catalog .catalog-price { position: absolute; top: 10px; left: 10px; z-index: 2; pointer-events: none; padding: 5px 12px; border-radius: 999px; background: #e7f6ec; color: #0f5132; font-size: 13px; font-weight: 600; box-shadow: 0 1px 3px #00000026; }
    .catalog .grid { margin-top: 20px; }
    .catalog .grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px; }
    .catalog article { position: relative; background: #fff; border: 1px solid #f3f4f6; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px #0000000d; transition: box-shadow .2s; }
    .catalog article:hover { box-shadow: 0 4px 6px #0000001a; }
    .catalog .catalog-thumb-link { display: block; }
    .catalog .catalog-image { width: 100%; height: 150px; object-fit: cover; }
    .catalog .catalog-placeholder { height: 150px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #134e4a, #115e59); color: #fff; }
    .catalog .catalog-placeholder svg { width: 48px; height: 48px; }
    .catalog .catalog-card-body { padding: 14px 16px; }
    .catalog .catalog-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; background: #f0fdfa; color: #134e4a; font-size: 13px; }
    .catalog .catalog-card-title { margin: 8px 0 6px; font-size: 17px; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .catalog p { color: #374151; }
    .catalog .catalog-card-body p { font-size: 14px; line-height: 1.45; margin: 0 0 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .catalog a { color: #134e4a; text-decoration: underline; }
    .catalog a:hover { color: #111827; }
    .catalog a:focus-visible, .catalog button:focus-visible { outline: 3px solid #134e4a; outline-offset: 4px; }
    @media (min-width: 768px) { .catalog .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .catalog .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .catalog .catalog-cta { margin-top: 32px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 14px; padding: 22px 24px; }
    .catalog .catalog-cta h2 { margin: 0 0 4px; font-size: 20px; }
    .catalog .catalog-cta p { margin: 0; font-size: 15px; max-width: 560px; }
    .catalog .catalog-cta-btn { flex: 0 0 auto; background: #134e4a; color: #fff; border-radius: 8px; padding: 12px 22px; font-size: 16px; font-weight: 600; text-decoration: none; min-height: 48px; display: inline-flex; align-items: center; }
    .catalog .catalog-cta-btn:hover { background: #0f3d3a; color: #fff; }
    /* En-tete : titre a gauche, raccourci "Mes cours" a droite (visible seulement si connecte,
       vers le tableau de bord de l'academie) - D-20261015-01, marier laveille et l'academie. */
    .catalog .catalog-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
    .catalog .catalog-head h1 { margin: 0; }
    .catalog .catalog-mycourses { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 8px; background: #134e4a; color: #fff; text-decoration: none; border-radius: 8px; padding: 10px 18px; font-size: 15px; font-weight: 600; min-height: 44px; }
    .catalog .catalog-mycourses:hover { background: #0f3d3a; color: #fff; text-decoration: none; }
    .catalog .catalog-mycourses svg { width: 18px; height: 18px; flex: 0 0 auto; }
    /* Bandeau d'accueil "jeune académie qui s'ouvre" (ouverture publique, #3127, 2026-10-15).
       Fond teal de la charte, texte clair; la pastille orange #C2410C passe le contraste AA avec le blanc. */
    .catalog .catalog-welcome { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 16px; background: linear-gradient(135deg, #0f3d3a 0%, #134e4a 55%, #115e59 100%); color: #fff; border-radius: 16px; padding: 26px 28px; }
    .catalog .catalog-welcome-copy { flex: 1 1 320px; min-width: 0; }
    .catalog .catalog-welcome-badge { display: inline-block; padding: 4px 12px; border-radius: 999px; background: #C2410C; color: #fff; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .catalog .catalog-welcome-title { color: #fff; margin: 12px 0 10px; font-size: 26px; line-height: 1.2; }
    .catalog .catalog-welcome-lead { color: #ecfdf5; font-size: 16px; line-height: 1.55; margin: 0 0 10px; max-width: 640px; }
    .catalog .catalog-welcome-note { color: #d1faf4; font-size: 14.5px; line-height: 1.5; margin: 0; max-width: 640px; }
    .catalog .catalog-welcome .catalog-mycourses { background: #fff; color: #134e4a; }
    .catalog .catalog-welcome .catalog-mycourses:hover { background: #ecfdf5; color: #0f3d3a; }
    .catalog .catalog-intro { margin: 24px 0 0; font-size: 16px; font-weight: 600; color: #134e4a; }
    .catalog .catalog-soon { border-style: dashed; border-color: #99f6e4; background: #f0fdfa; }
    .catalog .catalog-soon .catalog-placeholder { background: linear-gradient(135deg, #99f6e4, #5eead4); color: #0f3d3a; }
    @media (min-width: 768px) { .catalog .catalog-welcome { padding: 32px 36px; } .catalog .catalog-welcome-title { font-size: 30px; } }
</style>
@endpush

@section('content')
<div class="catalog" x-data="{ filter: '{{ $activeTheme ?? 'all' }}' }">
    <div class="container">
        {{-- Bandeau d'accueil : l'Académie vient d'ouvrir au public (ouverture décidée le 2026-10-15,
             supersède #48). Ton chaleureux, "jeune académie qui s'enrichit". Le raccourci "Mes cours"
             reste réservé aux personnes connectées (SSO silencieux vers le tableau de bord Moodle). --}}
        <div class="catalog-welcome">
            <div class="catalog-welcome-copy">
                <span class="catalog-welcome-badge">{{ __('Nouveau') }}</span>
                <h1 class="catalog-welcome-title">{{ __("Bienvenue dans l'Académie") }}</h1>
                <p class="catalog-welcome-lead">{{ __("Notre académie vient tout juste d'ouvrir. Vous y trouverez des formations courtes et concrètes pour mettre l'intelligence artificielle au service de votre travail de tous les jours - sans jargon, droit au but.") }}</p>
                <p class="catalog-welcome-note">{{ __("C'est un tout nouveau coin du site, et il s'enrichit vite. De nouvelles formations s'ajoutent régulièrement. Revenez bientôt, il y en aura davantage à chaque visite.") }}</p>
            </div>
            @auth
                <a href="{{ rtrim((string) config('catalog.moodle_base_url'), '/').'/my' }}" class="catalog-mycourses">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="4" width="18" height="14" rx="2" />
                        <path d="M3 9h18M8 20h8" />
                    </svg>
                    {{ __('Mes cours') }}
                </a>
            @endauth
        </div>
        @if(empty($categories))
            <p class="catalog-intro">{{ __('Les formations seront bientôt disponibles.') }}</p>
        @else
            <p class="catalog-intro">{{ __('Pour commencer, voici un premier aperçu.') }}</p>
            @php $catalogTotal = collect($categories)->sum(fn ($g) => count($g['courses'] ?? [])); @endphp
            <div class="catalog-filters" role="group" aria-label="{{ __('Filtrer par catégorie') }}" x-cloak>
                <button type="button" class="catalog-filter" data-filter="all" aria-pressed="true"
                        x-bind:aria-pressed="filter === 'all'" x-on:click="filter = $el.dataset.filter">{{ __('Toutes') }}</button>
                @foreach($categories as $group)
                    <button type="button" class="catalog-filter" data-filter="{{ $group['slug'] }}" aria-pressed="false"
                            x-bind:aria-pressed="filter === $el.dataset.filter" x-on:click="filter = $el.dataset.filter">{{ $group['category'] }}</button>
                @endforeach
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($categories as $group)
                    @foreach($group['courses'] as $course)
                        <article data-category="{{ $group['slug'] }}" x-show="filter === 'all' || filter === $el.dataset.category" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                            <a href="{{ $course['course_url'] }}" class="catalog-thumb-link" aria-label="{{ __('Voir la formation').' : '.$course['fullname'] }}">
                                @if($course['image_local'])
                                    <img src="{{ $course['image_local'] }}" alt="{{ $course['fullname'] }}" class="catalog-image w-full h-48 object-cover" loading="lazy">
                                @else
                                    <div class="catalog-placeholder">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M3 4h6a3 3 0 0 1 3 3v14a4 4 0 0 0-4-3H3V4Zm18 0h-6a3 3 0 0 0-3 3v14a4 4 0 0 1 4-3h5V4Z" />
                                        </svg>
                                    </div>
                                @endif
                            </a>
                            <div class="catalog-card-body p-5">
                                <span class="catalog-badge">{{ $group['category'] }}</span>
                                <span class="catalog-price">{{ $course['price_display'] }}</span>
                                <h2 class="catalog-card-title">{{ $course['fullname'] }}</h2>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($course['summary']), 140) }}</p>
                                <a href="{{ $course['course_url'] }}" aria-label="{{ __('Voir la formation').' : '.$course['fullname'] }}">{{ __('Voir la formation') }}</a>
                            </div>
                        </article>
                    @endforeach
                @endforeach
                {{-- Carte teaser : tant que l'offre est jeune (un seul cours public), on signale que
                     d'autres formations arrivent pour que la grille ne paraisse pas vide. Visible
                     seulement en vue "Toutes" pour ne pas parasiter un filtre de catégorie. --}}
                @if(($catalogTotal ?? 0) < 2)
                    <article class="catalog-soon" x-show="filter === 'all'">
                        <div class="catalog-placeholder">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 7.5v5l3.2 1.9" />
                            </svg>
                        </div>
                        <div class="catalog-card-body p-5">
                            <span class="catalog-badge">{{ __('Bientôt') }}</span>
                            <h2 class="catalog-card-title">{{ __("D'autres formations arrivent") }}</h2>
                            <p>{{ __('De nouvelles formations IA, courtes et concrètes, sont en préparation. Revenez bientôt.') }}</p>
                        </div>
                    </article>
                @endif
            </div>
        @endif

        @if(config('catalog.request_enabled'))
            <div class="catalog-cta">
                <div>
                    <h2>{{ __('Vous ne trouvez pas votre sujet?') }}</h2>
                    <p>{{ __('Nous concevons des formations sur mesure pour votre équipe, selon vos besoins et votre horaire.') }}</p>
                </div>
                <a href="{{ route('catalog.request') }}" class="catalog-cta-btn">{{ __('Demander un cours personnalisé') }}</a>
            </div>
        @endif
    </div>
</div>
@endsection
