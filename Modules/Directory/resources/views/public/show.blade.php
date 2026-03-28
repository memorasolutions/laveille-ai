<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', $tool->name . ' - ' . __('Répertoire techno') . ' - ' . config('app.name'))
@section('meta_description', Str::limit($tool->short_description ?? strip_tags($tool->description), 160))
@section('og_type', 'article')
@if($tool->screenshot)
    @section('og_image', str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot))
@endif

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $tool->name,
        'breadcrumbItems' => [__('Répertoire'), $tool->name]
    ])
@endsection

@push('styles')
<style>
    .rt-page { padding-bottom: 60px; }
    .rt-back { display: inline-flex; align-items: center; margin: 16px 0; color: var(--c-primary); font-weight: 600; text-decoration: none; }
    .rt-back:hover { transform: translateX(-3px); color: var(--c-primary); text-decoration: none; }
    .rt-back svg { margin-right: 8px; width: 18px; height: 18px; }

    /* Header */
    .rt-header { background: #fff; border-radius: var(--r-base); padding: 28px; margin-bottom: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #E5E7EB; border-bottom: none; border-radius: var(--r-base) var(--r-base) 0 0; }
    .rt-logo { width: 64px; height: 64px; border-radius: 16px; background: #f9fafb; padding: 4px; border: 1px solid #e5e7eb; }
    .rt-name { font-family: var(--f-heading); font-size: 1.8rem; font-weight: 800; color: var(--c-dark); margin: 0; }
    .rt-visit { display: inline-block; background: var(--c-accent); color: #fff !important; padding: 10px 24px; border-radius: var(--r-btn); font-weight: 700; text-decoration: none !important; transition: transform 0.2s; }
    .rt-visit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: #fff; }
    .rt-badge { padding: 4px 10px; border-radius: 50px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-free { background: #D1FAE5; color: #065F46; }
    .badge-freemium { background: #DBEAFE; color: #1E40AF; }
    .badge-paid { background: #FEF3C7; color: #92400E; }
    .badge-open_source { background: #CCFBF1; color: #115E59; }
    .badge-enterprise { background: #EDE9FE; color: #5B21B6; }
    .rt-share-btn { display: inline-block; padding: 4px 10px; border: 1px solid #E5E7EB; border-radius: var(--r-btn); color: var(--c-dark); text-decoration: none !important; font-size: 0.75rem; font-weight: 600; }
    .rt-share-btn:hover { background: #F3F4F6; color: var(--c-dark); }

    /* Tabs */
    .rt-tabs { background: #fff; border: 1px solid #E5E7EB; border-top: none; border-radius: 0 0 var(--r-base) var(--r-base); box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden; }
    .rt-tab-bar { display: flex; border-bottom: 1px solid #E5E7EB; overflow-x: auto; background: #FAFAFA; }
    .rt-tab-btn { flex: 1; padding: 14px 8px; background: none; border: none; border-bottom: 3px solid transparent; font-weight: 600; font-size: 0.9rem; cursor: pointer; white-space: nowrap; min-width: 90px; color: #6B7280; transition: all 0.2s; }
    .rt-tab-btn:hover { color: var(--c-dark); }
    .rt-tab-active { border-bottom-color: var(--c-primary) !important; color: var(--c-primary) !important; }
    .rt-panel { padding: 28px; }

    /* Info sections */
    .rt-info-box { border-left: 4px solid var(--c-primary); background: #EEF7FF; padding: 18px; border-radius: 0 var(--r-base) var(--r-base) 0; margin: 20px 0; }
    .rt-info-box h4 { color: var(--c-primary); margin-top: 0; }
    .rt-feature { background: #F9FAFB; padding: 12px; border-radius: 8px; height: 100%; font-size: 0.95rem; }
    .rt-pro { color: #065F46; margin-bottom: 6px; } .rt-con { color: #991B1B; margin-bottom: 6px; }
    .rt-audience { background: #F3F4F6; color: var(--c-dark); padding: 5px 14px; border-radius: var(--r-btn); font-size: 0.85rem; font-weight: 600; display: inline-block; margin: 3px; }
    details { margin-bottom: 8px; } details > summary { cursor: pointer; font-weight: 600; padding: 12px; background: #F9FAFB; border-radius: 6px; }
    details > summary:hover { background: #F3F4F6; } details > div { padding: 12px 16px; color: #4B5563; line-height: 1.6; }

    /* Markdown description */
    .rt-description h2 { font-family: var(--f-heading); font-size: 1.35rem; font-weight: 700; color: var(--c-dark); margin: 28px 0 12px; padding-bottom: 8px; border-bottom: 2px solid #E5E7EB; }
    .rt-description h3 { font-size: 1.15rem; font-weight: 600; color: var(--c-dark); margin: 20px 0 8px; }
    .rt-description p { margin-bottom: 14px; }
    .rt-description ul, .rt-description ol { margin: 0 0 14px 20px; }
    .rt-description li { margin-bottom: 4px; }
    .rt-description strong { color: var(--c-dark); }
    .rt-description blockquote { border-left: 4px solid var(--c-primary); background: #EEF7FF; padding: 12px 16px; margin: 16px 0; border-radius: 0 6px 6px 0; }
    .rt-description h2 { scroll-margin-top: 70px; }

    /* TOC scrollspy bar */
    .rt-toc-bar { background: #fff; padding: 12px 16px; margin-bottom: 20px; border-bottom: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .rt-toc-fixed { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; border-radius: 0; margin: 0; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
    .rt-toc-scroll { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; padding: 2px 0; }
    .rt-toc-scroll::-webkit-scrollbar { display: none; }
    .rt-toc-link { flex-shrink: 0; padding: 6px 14px; border-radius: 20px; background: #F3F4F6; color: var(--c-dark); font-size: 13px; font-weight: 600; text-decoration: none !important; white-space: nowrap; transition: all 0.2s; }
    .rt-toc-link:hover { background: #E5E7EB; color: var(--c-dark); }
    .rt-toc-link.active { background: var(--c-primary); color: #fff !important; }

    /* Reviews */
    .rt-stars { color: #F59E0B; font-size: 1.1rem; }
    /* Resources */
    .rt-res-card { border: 1px solid #E5E7EB; padding: 12px; border-radius: 8px; display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .rt-res-card img { width: 20px; height: 20px; flex-shrink: 0; }

    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
@php
    $host = $tool->url ? parse_url($tool->url, PHP_URL_HOST) : '';
    $favicon = $host ? "https://www.google.com/s2/favicons?domain={$host}&sz=64" : '';
    $pricingLabels = ['free'=>__('Gratuit'),'freemium'=>__('Freemium'),'paid'=>__('Payant'),'open_source'=>__('Open source'),'enterprise'=>__('Entreprise')];
    $reviews = $tool->reviews()->approved()->latest()->get();
    $discussions = $tool->discussions()->approved()->topLevel()->with('replies.user', 'user')->latest()->get();
    $resources = $tool->resources()->orderByDesc('upvotes')->latest()->get();
    $screenshots = $tool->screenshots()->approved()->orderByDesc('votes_count')->get();
@endphp

<section class="section-padding" style="padding-top: 10px;">
<div class="container rt-page" x-data="{ tab: (['info','reviews','discussions','resources','screenshots','alternatives'].includes(window.location.hash.slice(1)) ? window.location.hash.slice(1) : 'info'), setTab(t) { this.tab = t; history.replaceState(null, '', '#' + t); } }">

    {{-- Back --}}
    <a href="{{ route('directory.index') }}" class="rt-back">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        {{ __('Retour au répertoire') }}
    </a>

    {{-- HEADER --}}
    <div class="rt-header">
        <div style="display: flex; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            @if($favicon)<img src="{{ $favicon }}" alt="" class="rt-logo" width="64" height="64">@endif
            <div style="flex: 1; min-width: 200px;">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <h1 class="rt-name" style="margin:0;">{{ $tool->name }}</h1>
                    @if(trait_exists(\Modules\Voting\Traits\HasCommunityVotes::class))
                        @include('voting::components.vote-button', ['item' => $tool, 'type' => 'tool'])
                    @endif
                </div>
                <p style="color: #4B5563; margin: 6px 0 0; font-size: 1rem;">{{ $tool->short_description }}</p>
            </div>
            @if($tool->url)
                <a href="{{ $tool->getVisitUrl() }}" target="_blank" rel="{{ $tool->isAffiliate() ? 'sponsored noopener' : 'noopener noreferrer nofollow' }}" class="rt-visit">{{ __('Visiter le site') }} →</a>
            @endif
        </div>
        <div style="display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; align-items: center;">
            <span class="rt-badge badge-{{ $tool->pricing }}">{{ $pricingLabels[$tool->pricing] ?? ucfirst($tool->pricing) }}</span>
            <span class="rt-badge" style="background: #F3E8FF; color: #7E22CE;">{{ ucfirst($tool->website_type ?? 'website') }}</span>
            @if($tool->launch_year)<span style="color: #9CA3AF; font-size: 0.8rem;">🚀 {{ $tool->launch_year }}</span>@endif
            <span style="color: #9CA3AF; font-size: 0.8rem;">{{ number_format($tool->clicks_count) }} {{ __('clics') }}</span>
            <div style="margin-left: auto; display: flex; gap: 8px; align-items: center;">
                @include('fronttheme::partials.bookmark-btn', ['type' => 'Modules\\Directory\\Models\\Tool', 'id' => $tool->id])
                {{-- Boutons partage inline retirés — remplacés par la floating share bar globale --}}
            </div>
        </div>
        @if($tool->categories->isNotEmpty())
        <div style="margin-top: 12px;">
            @foreach($tool->categories as $cat)<span style="background: #F3F4F6; color: #4B5563; padding: 3px 10px; border-radius: 4px; font-size: 0.8rem; margin-right: 4px;">{{ $cat->name }}</span>@endforeach
        </div>
        @endif
    </div>

    {{-- Screenshot --}}
    @if($tool->screenshot)
        <div style="margin-bottom: 20px; border-radius: var(--r-base); overflow: hidden; border: 1px solid #E5E7EB;">
            <img src="{{ str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot) }}" alt="{{ __('Capture d ecran de') }} {{ $tool->name }}" loading="lazy" style="width: 100%; max-height: 400px; object-fit: cover; display: block;">
        </div>
    @endif

    {{-- Suggérer une modification (composant réutilisable) --}}
    @include('fronttheme::partials.suggest-edit', [
        'model' => $tool,
        'route' => route('directory.suggestions.store', $tool->slug),
    ])

    {{-- Ad: tool top --}}
    @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
        {!! app(\Modules\Ads\Services\AdsRenderer::class)->render('directory-tool-top') !!}
    @endif

    {{-- Fiche technique --}}
    <div style="background: #F8FAFB; border: 1px solid #E5E7EB; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
        <div class="row" style="margin-bottom: 4px;">
            <div class="col-md-4 col-sm-4 col-xs-12" style="margin-bottom: 12px;">
                <div style="font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; font-weight: 600;">💰 {{ __('Tarification') }}</div>
                <span class="rt-badge badge-{{ $tool->pricing }}" style="font-size: 12px; padding: 4px 10px;">{{ $pricingLabels[$tool->pricing] ?? ucfirst($tool->pricing) }}</span>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-6" style="margin-bottom: 12px;">
                <div style="font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; font-weight: 600;">🌐 {{ __('Type') }}</div>
                <div style="font-weight: 700; color: #111827; font-size: 14px;">{{ ucfirst($tool->website_type ?? 'Website') }}</div>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-6" style="margin-bottom: 12px;">
                <div style="font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; font-weight: 600;">🚀 {{ __('Lancé en') }}</div>
                <div style="font-weight: 700; color: #111827; font-size: 14px;">{{ $tool->launch_year ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 col-sm-4 col-xs-12" style="margin-bottom: 12px;">
                <div style="font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; font-weight: 600;">📁 {{ __('Catégories') }}</div>
                <div style="font-weight: 700; color: #111827; font-size: 14px;">
                    {{ $tool->categories->pluck('name')->implode(', ') ?: 'N/A' }}
                    @if($tool->categories->isNotEmpty() && Route::has('directory.compare'))
                        <a href="{{ route('directory.compare', $tool->categories->first()->slug) }}" style="font-size:11px;color:var(--c-primary);font-weight:600;margin-left:6px;text-decoration:none;">📊 {{ __('Comparer') }}</a>
                    @endif
                </div>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-6" style="margin-bottom: 12px;">
                <div style="font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; font-weight: 600;">🎯 {{ __('Public') }}</div>
                <div style="font-weight: 700; color: #111827; font-size: 14px;">{{ implode(', ', array_slice($tool->target_audience ?? [], 0, 2)) ?: 'N/A' }}</div>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-6" style="margin-bottom: 12px;">
                <div style="font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; font-weight: 600;">🔗 {{ __('Site web') }}</div>
                <a href="{{ $tool->url }}" target="_blank" rel="nofollow" style="color: #2563EB; font-weight: 700; text-decoration: none; font-size: 14px;">{{ Str::limit(preg_replace('#^https?://(www\.)?#', '', $tool->url), 25) }}</a>
            </div>
        </div>
        <div style="border-top: 1px solid #E5E7EB; padding-top: 12px; margin-top: 4px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <span style="display: inline-flex; align-items: center; color: #065F46; font-weight: 600; font-size: 12px; background: #D1FAE5; padding: 4px 10px; border-radius: 99px; border: 1px solid #A7F3D0;">✓ {{ __('Vérifié par La veille') }}</span>
            <span style="font-size: 12px; color: #9CA3AF;">{{ __('Mis à jour le') }} {{ $tool->updated_at->format('d/m/Y') }}</span>
        </div>
    </div>

    {{-- TABS --}}
    <div class="rt-tabs">
        <div class="rt-tab-bar">
            <button type="button" class="rt-tab-btn" :class="tab==='info' && 'rt-tab-active'" @click="setTab('info')">📋 {{ __('Informations') }}</button>
            <button type="button" class="rt-tab-btn" :class="tab==='reviews' && 'rt-tab-active'" @click="setTab('reviews')">⭐ {{ __('Avis') }} ({{ $reviews->count() }})</button>
            <button type="button" class="rt-tab-btn" :class="tab==='discussions' && 'rt-tab-active'" @click="setTab('discussions')">💬 {{ __('Discussion') }} ({{ $discussions->count() }})</button>
            <button type="button" class="rt-tab-btn" :class="tab==='resources' && 'rt-tab-active'" @click="setTab('resources')">📚 {{ __('Tutoriels') }} ({{ $resources->count() }})</button>
            <button type="button" class="rt-tab-btn" :class="tab==='screenshots' && 'rt-tab-active'" @click="setTab('screenshots')">📸 {{ __('Screenshots') }} ({{ $screenshots->count() }})</button>
            <button type="button" class="rt-tab-btn" :class="tab==='alternatives' && 'rt-tab-active'" @click="setTab('alternatives')">🔄 {{ __('Alternatives') }}</button>
        </div>

        {{-- TAB: Informations --}}
        <div class="rt-panel" x-show="tab==='info'" x-cloak style="padding: 24px;">

            {{-- Description with TOC scrollspy --}}
            @php
                $descHtml = Str::markdown($tool->description ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]);
                $tocData = \Modules\Directory\Helpers\TocHelper::generate($descHtml);
                $toc = $tocData['toc'];
                $descHtmlWithIds = $tocData['html'];
            @endphp

            @include('directory::public.partials._toc_bar', ['toc' => $toc])

            <div style="background: #fff; padding: 32px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <h3 style="font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; font-size: 20px;">
                    👋 {{ __('À propos de') }} {{ $tool->name }}
                </h3>
                <div style="width: 50px; height: 3px; background: linear-gradient(90deg, var(--c-primary), #60a5fa); margin-bottom: 20px; border-radius: 2px;"></div>
                <div class="rt-description" style="font-size: 1.05rem; line-height: 1.8; color: #475569;">{!! $descHtmlWithIds !!}</div>
            </div>

            {{-- How to use (masque si contenu trop court/generique) --}}
            @if($tool->how_to_use && Str::length($tool->how_to_use) > 200)
            <div style="background: #fff; padding: 32px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <h3 style="font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; font-size: 20px;">
                    🛠️ {{ __('Comment utiliser') }} {{ $tool->name }} ?
                </h3>
                <div style="width: 50px; height: 3px; background: linear-gradient(90deg, var(--c-primary), #60a5fa); margin-bottom: 20px; border-radius: 2px;"></div>
                <div style="background: #eff6ff; border-left: 5px solid #2563eb; padding: 22px; border-radius: 8px; color: #1e3a8a; font-size: 1.05rem; line-height: 1.7;">
                    {{ $tool->how_to_use }}
                </div>
            </div>
            @endif

            {{-- Features --}}
            @if($tool->core_features)
            <div style="background: #F8FAFB; padding: 32px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #f1f5f9;">
                <h3 style="font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; font-size: 20px;">
                    ✨ {{ __('Fonctionnalités clés') }}
                </h3>
                <div style="width: 50px; height: 3px; background: linear-gradient(90deg, #8b5cf6, #a78bfa); margin-bottom: 20px; border-radius: 2px;"></div>
                <div class="row" style="display: flex; flex-wrap: wrap;">
                    @foreach(array_filter(explode(',', $tool->core_features)) as $f)
                    <div class="col-md-4 col-sm-6" style="margin-bottom: 16px; display: flex;">
                        <div style="background: #fff; padding: 18px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.04); width: 100%; display: flex; align-items: center; gap: 10px;">
                            <span style="background: #ecfdf5; color: #059669; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0;">✅</span>
                            <span style="font-weight: 600; color: #334155; font-size: 14px; line-height: 1.4;">{{ trim($f) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Use cases --}}
            @if($tool->use_cases)
            <div style="background: #fff; padding: 32px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <h3 style="font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; font-size: 20px;">
                    🚀 {{ __('Cas d\'usage') }}
                </h3>
                <div style="width: 50px; height: 3px; background: linear-gradient(90deg, #f59e0b, #fbbf24); margin-bottom: 20px; border-radius: 2px;"></div>
                <div class="row" style="display: flex; flex-wrap: wrap;">
                    @foreach(array_filter(explode(',', $tool->use_cases)) as $i => $u)
                    <div class="col-md-6" style="margin-bottom: 16px; display: flex;">
                        <div style="background: #F8FAFB; padding: 22px; border-radius: 12px; border: 1px solid #e2e8f0; width: 100%; position: relative; overflow: hidden;">
                            <span style="position: absolute; top: -8px; right: 0; font-size: 64px; font-weight: 900; color: #f1f5f9; line-height: 1;">{{ $i + 1 }}</span>
                            <p style="margin: 0; font-size: 1rem; font-weight: 600; color: #475569; position: relative; z-index: 1;">{{ trim($u) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Pros / Cons --}}
            @if($tool->pros || $tool->cons)
            <div style="background: #F8FAFB; padding: 32px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #f1f5f9;">
                <h3 style="font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; font-size: 20px;">
                    ⚖️ {{ __('Avantages et inconvénients') }}
                </h3>
                <div style="width: 50px; height: 3px; background: linear-gradient(90deg, #10b981, #ef4444); margin-bottom: 20px; border-radius: 2px;"></div>
                <div class="row" style="display: flex; flex-wrap: wrap;">
                    <div class="col-md-6" style="margin-bottom: 16px; display: flex;">
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 22px; width: 100%;">
                            <h4 style="color: #166534; font-weight: 700; margin: 0 0 14px; display: flex; align-items: center; gap: 8px;">
                                <span style="background: #166534; color: #fff; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">✓</span>
                                {{ __('Les plus') }}
                            </h4>
                            @foreach(array_filter(explode(',', $tool->pros ?? '')) as $p)
                            <div style="margin-bottom: 10px; color: #14532d; display: flex; align-items: center; gap: 8px; font-size: 14px; line-height: 1.4;">
                                <span style="color: #16a34a; flex-shrink: 0; font-size: 16px; line-height: 1;">✅</span> {{ trim($p) }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-6" style="margin-bottom: 16px; display: flex;">
                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 22px; width: 100%;">
                            <h4 style="color: #991b1b; font-weight: 700; margin: 0 0 14px; display: flex; align-items: center; gap: 8px;">
                                <span style="background: #991b1b; color: #fff; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">✕</span>
                                {{ __('Les moins') }}
                            </h4>
                            @foreach(array_filter(explode(',', $tool->cons ?? '')) as $c)
                            <div style="margin-bottom: 10px; color: #7f1d1d; display: flex; align-items: center; gap: 8px; font-size: 14px; line-height: 1.4;">
                                <span style="color: #dc2626; flex-shrink: 0; font-size: 16px; line-height: 1;">❌</span> {{ trim($c) }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Target audience --}}
            @if(!empty($tool->target_audience))
            <div style="background: #fff; padding: 32px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <h3 style="font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; font-size: 20px;">
                    🎯 {{ __('Public cible') }}
                </h3>
                <div style="width: 50px; height: 3px; background: linear-gradient(90deg, #ec4899, #f472b6); margin-bottom: 20px; border-radius: 2px;"></div>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    @foreach($tool->target_audience as $a)
                    <span style="background: #e0e7ff; color: #3730a3; padding: 10px 20px; border-radius: 50px; font-weight: 600; font-size: 14px; border: 1px solid #c7d2fe;">{{ $a }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- FAQ --}}
            @if(!empty($tool->faq))
            <div style="background: #F8FAFB; padding: 32px; border-radius: 12px; margin-bottom: 8px; border: 1px solid #f1f5f9;">
                <h3 style="font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; font-size: 20px;">
                    ❓ {{ __('Questions fréquentes') }}
                </h3>
                <div style="width: 50px; height: 3px; background: linear-gradient(90deg, #64748b, #94a3b8); margin-bottom: 20px; border-radius: 2px;"></div>
                @foreach($tool->faq as $q)
                <details style="margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #fff;">
                    <summary style="background: #f8fafc; padding: 14px 18px; cursor: pointer; font-weight: 600; color: #334155; display: flex; align-items: center; justify-content: space-between;">
                        <span>{{ $q['question'] }}</span>
                        <span style="color: #94a3b8; font-size: 11px;">▼</span>
                    </summary>
                    <div style="padding: 18px; border-top: 1px solid #e2e8f0; color: #475569; line-height: 1.7;">{{ $q['answer'] }}</div>
                </details>
                @endforeach
            </div>
            @endif

            {{-- CTA bottom --}}
            @if($tool->url)
            <div style="margin-top: 40px; background: linear-gradient(180deg, #F9FAFB, #F3F4F6); border: 1px solid #E5E7EB; border-radius: 16px; padding: 32px 20px; text-align: center;">
                <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 20px; font-weight: 800; color: #111827;">{{ __('Envie d\'essayer') }} {{ $tool->name }} ?</h3>
                <a href="{{ $tool->getVisitUrl() }}" target="_blank" rel="{{ $tool->isAffiliate() ? 'sponsored noopener' : 'noopener noreferrer nofollow' }}" style="display: inline-block; background: var(--c-accent); color: #fff; font-weight: 700; padding: 14px 32px; border-radius: var(--r-btn); text-decoration: none; font-size: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.2s;">{{ __('Visiter le site') }} →</a>
                <div style="margin-top: 14px; font-size: 13px; color: #059669; font-weight: 600;">✓ {{ __('Vérifié par La veille') }}</div>
            </div>
            @endif

        </div>

        {{-- TAB: Avis --}}
        <div class="rt-panel" x-show="tab==='reviews'" x-cloak style="padding: 24px;">
            @if($reviews->isEmpty())
            <div style="text-align: center; padding: 50px 20px; background: #f9fafb; border-radius: 16px; border: 1px dashed #d1d5db; margin-bottom: 24px;">
                <div style="font-size: 48px; margin-bottom: 12px;">👋</div>
                <h4 style="font-weight: 700; color: #111827; margin: 0 0 6px;">{{ __('Soyez le premier à donner votre avis !') }}</h4>
                <p style="color: #6b7280; margin: 0;">{{ __('Partagez votre expérience avec cet outil pour aider la communauté.') }}</p>
            </div>
            @endif

            @foreach($reviews as $review)
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 22px; margin-bottom: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                <div style="display: flex; align-items: center; margin-bottom: 10px; gap: 10px;">
                    <div style="color: #fbbf24;">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                    <strong style="font-size: 16px; color: #1f2937;">{{ $review->title }}</strong>
                </div>
                @if($review->body)<p style="color: #4b5563; line-height: 1.6; margin-bottom: 12px;">{{ $review->body }}</p>@endif
                @if($review->pros || $review->cons)
                <div class="row" style="margin-bottom: 12px;">
                    @if($review->pros)<div class="col-sm-6"><div style="background: #ecfdf5; border-radius: 8px; padding: 10px;"><strong style="color: #047857; font-size: 12px;">👍 {{ __('Les plus') }}</strong><br><span style="color: #064e3b; font-size: 14px;">{{ $review->pros }}</span></div></div>@endif
                    @if($review->cons)<div class="col-sm-6"><div style="background: #fef2f2; border-radius: 8px; padding: 10px;"><strong style="color: #b91c1c; font-size: 12px;">👎 {{ __('Les moins') }}</strong><br><span style="color: #7f1d1d; font-size: 14px;">{{ $review->cons }}</span></div></div>@endif
                </div>
                @endif
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f3f4f6; padding-top: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 30px; height: 30px; background: #e0e7ff; color: #4f46e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">{{ substr($review->user->name ?? '?', 0, 1) }}</div>
                        @if($review->user)<a href="{{ route('directory.profile', $review->user->id) }}" style="font-weight: 600; color: #374151; font-size: 13px; text-decoration: none;">{{ $review->user->name }}</a><span style="font-size: 11px; color: #9CA3AF; margin-left: 4px;">{{ $review->user->getLevelBadge() }}</span>@else<span style="font-weight: 600; color: #374151; font-size: 13px;">{{ __('Anonyme') }}</span>@endif
                        <span style="color: #9ca3af; font-size: 12px;">{{ $review->created_at->diffForHumans() }}</span>
                    </div>
                    <div style="display: flex; gap: 12px;" x-data="{ likes: {{ $review->upvotes }} }">
                        <button @click="fetch('{{ route('directory.community.like', ['type' => 'review', 'id' => $review->id]) }}', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(r=>r.json()).then(d=>{likes=d.upvotes})" style="background:none;border:none;cursor:pointer;color:#ef4444;font-weight:600;font-size:13px;">❤️ <span x-text="likes"></span></button>
                        <form action="{{ route('directory.community.report', ['type' => 'review', 'id' => $review->id]) }}" method="POST" style="display:inline;"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="reason" value="inappropriate"><button type="submit" style="background:none;border:none;color:#d1d5db;cursor:pointer;font-size:12px;" title="{{ __('Signaler') }}">🚩</button></form>
                    </div>
                </div>
            </div>
            @endforeach

            @auth
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; margin-top: 24px;">
                @include('fronttheme::partials.gamification-widget')
                <h4 style="margin-top: 0; margin-bottom: 18px; font-weight: 700; color: #1e293b;">{{ __('Rédiger un avis') }}</h4>
                <form action="{{ route('directory.reviews.store', $tool->slug) }}" method="POST" x-data="{ rating: 0, hover: 0 }">
                    @csrf
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; color: #475569; font-weight: 600; margin-bottom: 8px;">{{ __('Votre note') }}</label>
                        <input type="hidden" name="rating" :value="rating">
                        <div style="display: flex; gap: 4px; cursor: pointer;">
                            @for($i = 1; $i <= 5; $i++)
                            <span @click="rating = {{ $i }}" @mouseover="hover = {{ $i }}" @mouseleave="hover = 0" :style="(hover || rating) >= {{ $i }} ? 'color:#fbbf24' : 'color:#cbd5e1'" style="font-size: 30px; cursor: pointer; transition: color 0.15s;">★</span>
                            @endfor
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;"><label style="font-weight: 600; margin-bottom: 6px; display: block;">{{ __('Titre') }}</label><input type="text" name="title" class="form-control" style="border-radius: 8px; height: 40px;" placeholder="{{ __('En quelques mots...') }}" required></div>
                    <div class="row" style="margin-bottom: 14px;">
                        <div class="col-md-6"><label style="font-weight: 600; color: #047857; margin-bottom: 6px; display: block;">✅ {{ __('Points forts') }}</label><input type="text" name="pros" class="form-control" style="border-radius: 8px; height: 40px;" placeholder="{{ __('Rapide, efficace...') }}"></div>
                        <div class="col-md-6"><label style="font-weight: 600; color: #b91c1c; margin-bottom: 6px; display: block;">❌ {{ __('Points faibles') }}</label><input type="text" name="cons" class="form-control" style="border-radius: 8px; height: 40px;" placeholder="{{ __('Cher, complexe...') }}"></div>
                    </div>
                    <div class="form-group" style="margin-bottom: 18px;"><label style="font-weight: 600; margin-bottom: 6px; display: block;">{{ __('Votre expérience') }}</label><textarea name="body" class="form-control" rows="3" style="border-radius: 8px;" placeholder="{{ __('Décrivez votre expérience...') }}" required></textarea></div>
                    <button type="submit" class="btn" style="background: #4f46e5; color: #fff; border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;" :disabled="rating === 0">{{ __('Publier mon avis') }}</button>
                </form>
            </div>
            @else
                <div style="text-align: center; padding: 12px;">
                    <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour donner votre avis.') }}' })"
                        style="background: var(--c-primary); color: #fff; border: none; border-radius: var(--r-btn); padding: 10px 24px; font-weight: 600; cursor: pointer; font-size: 14px;">
                        🔐 {{ __('Se connecter') }}
                    </button>
                </div>
            @endauth
        </div>

        {{-- TAB: Discussion --}}
        <div class="rt-panel" x-show="tab==='discussions'" x-cloak style="padding: 24px;">
            @auth
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; margin-bottom: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                @include('fronttheme::partials.gamification-widget')
                <h4 style="margin-top: 0; font-weight: 700; color: #111827; font-size: 17px; margin-bottom: 14px;">{{ __('Nouveau sujet') }}</h4>
                <form action="{{ route('directory.discussions.store', $tool->slug) }}" method="POST">
                    @csrf
                    <div class="form-group" style="margin-bottom: 12px;"><input type="text" name="title" class="form-control" placeholder="{{ __('Titre de la discussion') }}" style="border-radius: 8px; height: 42px; font-weight: 600;" required></div>
                    <div class="form-group" style="margin-bottom: 12px;"><textarea name="body" class="form-control" rows="3" placeholder="{{ __('De quoi voulez-vous parler ?') }}" style="border-radius: 8px;" required></textarea></div>
                    <div style="text-align: right;"><button type="submit" class="btn" style="background: #2563eb; color: #fff; border: none; border-radius: 8px; padding: 8px 20px; font-weight: 600;">{{ __('Lancer la discussion') }}</button></div>
                </form>
            </div>
            @endauth

            @if($discussions->isEmpty())
            <div style="text-align: center; padding: 50px 20px; background: #f9fafb; border-radius: 16px; border: 1px dashed #d1d5db;">
                <div style="font-size: 48px; margin-bottom: 12px;">💬</div>
                <h4 style="font-weight: 700; color: #111827; margin: 0 0 6px;">{{ __('C\'est calme ici...') }}</h4>
                <p style="color: #6b7280; margin: 0;">{{ __('Lancez une discussion ! Quelle est votre expérience ?') }}</p>
            </div>
            @endif

            @foreach($discussions as $d)
            <div style="margin-bottom: 24px;">
                <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    @if($d->title)<h4 style="margin-top: 0; font-weight: 700; color: #1f2937; font-size: 17px;">{{ $d->title }}</h4>@endif
                    <p style="color: #4b5563; line-height: 1.6; margin-bottom: 12px;">{!! nl2br(e($d->body)) !!}</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f3f4f6; padding-top: 12px; font-size: 13px;">
                        <div style="color: #6b7280;">@if($d->user)<a href="{{ route('directory.profile', $d->user->id) }}" style="color: #374151; font-weight: 700; text-decoration: none;">{{ $d->user->name }}</a> <span style="font-size: 11px;">{{ $d->user->getLevelBadge() }}</span>@else<strong style="color: #374151;">{{ __('Anonyme') }}</strong>@endif · {{ $d->created_at->diffForHumans() }} · {{ $d->replies->count() }} {{ __('réponses') }}</div>
                        <div style="display: flex; gap: 12px;" x-data="{ likes: {{ $d->upvotes }} }">
                            <button @click="fetch('{{ route('directory.community.like', ['type' => 'discussion', 'id' => $d->id]) }}', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(r=>r.json()).then(d2=>{likes=d2.upvotes})" style="background:none;border:none;cursor:pointer;color:#ef4444;font-weight:600;">❤️ <span x-text="likes"></span></button>
                            <form action="{{ route('directory.community.report', ['type' => 'discussion', 'id' => $d->id]) }}" method="POST" style="display:inline;"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="reason" value="inappropriate"><button type="submit" style="background:none;border:none;color:#d1d5db;cursor:pointer;font-size:12px;">🚩</button></form>
                        </div>
                    </div>
                </div>
                @if($d->replies->isNotEmpty())
                <div style="margin-left: 20px; border-left: 2px solid #e5e7eb; padding-left: 16px; margin-top: 12px;">
                    @foreach($d->replies as $r)
                    <div style="background: #f9fafb; border-radius: 8px; padding: 14px; margin-bottom: 8px; border: 1px solid #f3f4f6;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <strong style="font-size: 13px; color: #374151;">{{ $r->user->name ?? __('Anonyme') }}</strong>
                            <span style="font-size: 12px; color: #9ca3af;">{{ $r->created_at->diffForHumans() }}</span>
                        </div>
                        <p style="margin: 0; color: #4b5563; font-size: 14px;">{{ $r->body }}</p>
                    </div>
                    @endforeach
                </div>
                @endif
                @auth
                <div x-data="{ replying: false }" style="margin-left: 20px; padding-left: 16px; margin-top: 8px;">
                    <button @click="replying = !replying" x-show="!replying" style="background: none; border: 1px solid #e5e7eb; border-radius: 20px; padding: 6px 14px; color: #4b5563; font-size: 13px; cursor: pointer;">↩️ {{ __('Répondre') }}</button>
                    <form x-show="replying" x-cloak action="{{ route('directory.discussions.store', $tool->slug) }}" method="POST" style="background: #fff; padding: 14px; border-radius: 8px; border: 1px solid #e5e7eb; margin-top: 8px;">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $d->id }}">
                        <div class="form-group" style="margin-bottom: 10px;"><textarea name="body" class="form-control" rows="2" placeholder="{{ __('Votre réponse...') }}" style="border-radius: 6px;" required></textarea></div>
                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                            <button type="button" @click="replying = false" style="background: none; border: none; color: #6b7280; cursor: pointer; font-size: 13px;">{{ __('Annuler') }}</button>
                            <button type="submit" class="btn btn-sm" style="background: #2563eb; color: #fff; border-radius: 6px;">{{ __('Publier') }}</button>
                        </div>
                    </form>
                </div>
                @endauth
            </div>
            @endforeach

            @guest
            <div style="text-align: center; padding: 12px;">
                <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour participer à la discussion.') }}' })"
                    style="background: var(--c-primary); color: #fff; border: none; border-radius: var(--r-btn); padding: 10px 24px; font-weight: 600; cursor: pointer; font-size: 14px;">
                    🔐 {{ __('Se connecter') }}
                </button>
            </div>
            @endguest
        </div>

        {{-- TAB: Tutoriels --}}
        <div class="rt-panel" x-show="tab==='resources'" x-cloak style="padding: 24px;">
            @if($resources->isEmpty())
            <div style="text-align: center; padding: 50px 20px; background: #f9fafb; border-radius: 16px; border: 1px dashed #d1d5db; margin-bottom: 24px;">
                <div style="font-size: 48px; margin-bottom: 12px;">📚</div>
                <h4 style="font-weight: 700; color: #111827; margin: 0 0 6px;">{{ __('Aucun tutoriel pour le moment') }}</h4>
                <p style="color: #6b7280; margin: 0;">{{ __('Connaissez-vous un bon tutoriel ? Partagez-le !') }}</p>
            </div>
            @endif

            @foreach($resources as $res)
            @php
                $isYt = !empty($res->video_id);
                $thumbUrl = $isYt ? "https://img.youtube.com/vi/{$res->video_id}/hqdefault.jpg" : ($res->thumbnail ?? null);
                $durationFormatted = $res->duration_seconds ? gmdate($res->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', $res->duration_seconds) : null;
            @endphp
            <div data-mod-item x-data="{ expanded: false }" style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;margin-bottom:14px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.03);transition:box-shadow .2s;" @mouseover="$el.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'" @mouseout="!expanded && ($el.style.boxShadow='0 2px 4px rgba(0,0,0,0.03)')">

                {{-- Carte compacte --}}
                <div @click="expanded = !expanded" style="display:flex;gap:14px;padding:14px;cursor:pointer;align-items:center;">
                    {{-- Miniature --}}
                    @if($thumbUrl)
                    <div style="position:relative;flex-shrink:0;width:140px;height:80px;border-radius:10px;overflow:hidden;background:#f1f5f9;">
                        <img src="{{ $thumbUrl }}" alt="" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                        @if($isYt)
                            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.15);">
                                <div style="width:32px;height:32px;background:rgba(255,0,0,0.9);border-radius:6px;display:flex;align-items:center;justify-content:center;">
                                    <div style="width:0;height:0;border-style:solid;border-width:6px 0 6px 10px;border-color:transparent transparent transparent #fff;margin-left:2px;"></div>
                                </div>
                            </div>
                            @if($durationFormatted)
                                <span style="position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,0.8);color:#fff;font-size:11px;font-weight:600;padding:1px 6px;border-radius:4px;">{{ $durationFormatted }}</span>
                            @endif
                        @endif
                    </div>
                    @endif

                    {{-- Infos --}}
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;color:var(--c-dark);font-size:15px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $res->title }}</div>
                        <div style="display:flex;gap:6px;margin-top:6px;align-items:center;flex-wrap:wrap;">
                            <span style="background:#eff6ff;color:#2563eb;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:600;text-transform:uppercase;">{{ $res->type }}</span>
                            <span style="background:{{ $res->language === 'fr' ? '#e0e7ff' : '#fef3c7' }};color:{{ $res->language === 'fr' ? '#3730a3' : '#92400e' }};padding:2px 8px;border-radius:4px;font-size:10px;font-weight:600;">{{ strtoupper($res->language) }}</span>
                            @if($res->channel_name)
                                <span style="color:#6b7280;font-size:12px;">📺 {{ $res->channel_name }}</span>
                            @endif
                        </div>
                        @if($res->video_summary)
                            <p style="color:var(--c-text-muted);font-size:13px;margin:6px 0 0;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ Str::limit(strip_tags(Str::markdown($res->video_summary)), 120) }}</p>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;" @click.stop>
                        @if(trait_exists(\Modules\Voting\Traits\HasCommunityVotes::class))
                            @include('voting::components.vote-button', ['item' => $res, 'type' => 'resource'])
                        @endif
                        @include('directory::components.report-button', [
                            'reportUrl' => route('directory.community.report', ['type' => 'resource', 'id' => $res->id]),
                            'csrfToken' => csrf_token(),
                        ])
                    </div>
                </div>

                {{-- Carte expandée (au clic) — template x-if pour ne pas charger l'iframe tant que fermé --}}
                <template x-if="expanded">
                <div x-transition style="border-top:1px solid #f1f5f9;padding:16px;background:#fafbfc;">
                    {{-- Embed YouTube avec fallback si vidéo supprimée --}}
                    @if($isYt)
                    <div x-data="{ ytError: false }" style="margin-bottom:16px;">
                        <div x-show="!ytError" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:12px;">
                            <iframe src="https://www.youtube-nocookie.com/embed/{{ $res->video_id }}?rel=0&modestbranding=1" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy" title="{{ $res->title }}" x-on:error="ytError = true"></iframe>
                        </div>
                        <div x-show="ytError" x-cloak style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:20px;text-align:center;">
                            <span style="font-size:24px;">🚫</span>
                            <p style="color:#dc2626;font-weight:600;margin:8px 0 4px;">{{ __('Vidéo indisponible') }}</p>
                            <p style="color:#6b7280;font-size:13px;margin:0;">{{ __('Cette vidéo a été supprimée ou rendue privée.') }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- Métadonnées --}}
                    <div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:12px;font-size:13px;color:#6b7280;">
                        @if($res->channel_name)
                            <span>📺 @if($res->channel_url)<a href="{{ $res->channel_url }}" target="_blank" rel="noopener nofollow" style="color:var(--c-primary);text-decoration:none;font-weight:600;">{{ $res->channel_name }}</a>@else{{ $res->channel_name }}@endif</span>
                        @endif
                        @if($durationFormatted)
                            <span>⏱️ {{ $durationFormatted }}</span>
                        @endif
                        <span>📅 {{ __('Soumis le') }} {{ $res->created_at->format('d/m/Y') }}</span>
                        <span>👤 {{ __('par') }} {{ $res->user->name ?? __('Anonyme') }}</span>
                    </div>

                    <div style="display:flex;gap:8px;margin-bottom:12px;">
                        @if(!$isYt)<a href="{{ $res->url }}" target="_blank" rel="nofollow noopener" style="display:inline-flex;align-items:center;gap:4px;background:var(--c-primary);color:#fff;padding:6px 14px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;">🔗 {{ __('Voir la ressource') }}</a>@endif
                        @can('view_admin_panel')
                            @include('directory::components.admin-inline-actions', [
                                'approveUrl' => route('admin.directory.moderation.resource.approve', $res->id),
                                'rejectUrl' => route('admin.directory.moderation.resource.reject', $res->id),
                                'deleteUrl' => route('admin.directory.moderation.resource.delete', $res->id),
                                'isApproved' => $res->is_approved,
                            ])
                        @endcan
                    </div>

                    {{-- Résumé IA --}}
                    @if($res->video_summary)
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">
                        <h5 style="font-weight:700;color:var(--c-dark);margin:0 0 8px;font-size:14px;">🤖 {{ __('Résumé généré par IA') }}</h5>
                        <div class="rt-description" style="font-size:14px;color:var(--c-text-secondary);line-height:1.6;">{!! \Illuminate\Support\Str::markdown($res->video_summary) !!}</div>
                    </div>
                    @endif
                </div>
                </template>
            </div>
            @endforeach

            @auth
            <style>.wz-card{display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;padding:28px 16px!important;border:1px solid #e2e8f0!important;border-radius:16px!important;background:#fff!important;cursor:pointer;text-align:center;transition:all .25s ease;min-height:140px;box-shadow:0 1px 3px rgba(0,0,0,0.04)}.wz-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,0.08)!important}.wz-card.active{border-color:var(--c-primary)!important;box-shadow:0 0 0 3px rgba(11,114,133,0.12)!important}.wz-submit{background:var(--c-accent)!important;color:#fff!important;border:none!important;border-radius:8px!important;padding:12px 24px!important;font-weight:600!important;font-size:15px!important;cursor:pointer;flex:1;transition:opacity .2s}.wz-submit:hover{opacity:.9}.wz-submit:disabled{opacity:.6!important;cursor:wait!important}</style>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; margin-top: 24px;"
                 x-data="{
                    step: 1,
                    type: '',
                    url: '',
                    title: '',
                    language: 'fr',
                    videoId: null,
                    thumbnail: null,
                    author: null,
                    duration: null,
                    channelName: null,
                    channelUrl: null,
                    submitting: false,
                    loading: false,
                    isYoutube: false,
                    videoError: null,
                    selectType(t) { this.type = t; this.step = 2; },
                    async fetchMeta() {
                        if (!this.url || this.url.length < 10) return;
                        this.videoError = null;
                        this.isYoutube = /youtube\.com\/watch|youtu\.be\//.test(this.url);
                        if (!this.isYoutube) { this.step = 3; return; }
                        this.loading = true;
                        try {
                            const res = await fetch('{{ route('directory.youtube-meta', $tool->slug) }}', {
                                method: 'POST',
                                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                                body: JSON.stringify({url: this.url})
                            });
                            const data = await res.json();
                            if (data.youtube && data.valid === false) {
                                this.videoError = data.error;
                                this.loading = false;
                                return;
                            }
                            if (data.youtube && data.valid) {
                                this.videoId = data.video_id;
                                this.title = data.title || '';
                                this.thumbnail = data.thumbnail;
                                this.author = data.author;
                                this.duration = data.duration;
                                this.channelName = data.author;
                                this.channelUrl = data.channel_url;
                                this.type = this.type || 'video';
                            }
                        } catch(e) { this.videoError = '{{ __("Erreur de connexion. Réessayez.") }}'; }
                        this.loading = false;
                        if (!this.videoError) this.step = 3;
                    },
                    back() { this.step = Math.max(1, this.step - 1); }
                 }">

                {{-- Progress bar --}}
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                    <template x-for="i in (step === 4 ? 4 : 3)" :key="i">
                        <div style="flex:1;height:4px;border-radius:2px;transition:background .3s;" :style="i <= step ? 'background:var(--c-primary)' : 'background:#e2e8f0'"></div>
                    </template>
                    <span x-show="step < 4" style="font-size:12px;color:#9ca3af;white-space:nowrap;" x-text="'Étape ' + step + '/3'"></span>
                    <span x-show="step === 4" style="font-size:12px;color:#16a34a;white-space:nowrap;">✓ {{ __('Soumis') }}</span>
                </div>

                {{-- Étape 1 : Type --}}
                <div x-show="step===1" x-transition role="region" aria-label="Étape 1 : type de ressource">
                    <h4 style="margin-top:0;font-weight:700;color:#1e293b;margin-bottom:16px;">{{ __('Quel type de ressource ?') }}</h4>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
                        <div class="wz-card" :class="type==='video' && 'active'" role="button" tabindex="0" @click="selectType('video')" @keydown.enter="selectType('video')">
                            <div style="font-size:36px;margin-bottom:12px;line-height:1;">🎬</div>
                            <div style="font-weight:700;font-size:15px;color:var(--c-dark);font-family:var(--f-heading);">{{ __('Vidéo') }}</div>
                            <div style="font-size:12px;color:var(--c-text-muted);margin-top:4px;">YouTube, Vimeo...</div>
                        </div>
                        <div class="wz-card" :class="type==='article' && 'active'" role="button" tabindex="0" @click="selectType('article')" @keydown.enter="selectType('article')">
                            <div style="font-size:36px;margin-bottom:12px;line-height:1;">📄</div>
                            <div style="font-weight:700;font-size:15px;color:var(--c-dark);font-family:var(--f-heading);">{{ __('Article') }}</div>
                            <div style="font-size:12px;color:var(--c-text-muted);margin-top:4px;">Blog, documentation</div>
                        </div>
                        <div class="wz-card" :class="type==='tutorial' && 'active'" role="button" tabindex="0" @click="selectType('tutorial')" @keydown.enter="selectType('tutorial')">
                            <div style="font-size:36px;margin-bottom:12px;line-height:1;">📖</div>
                            <div style="font-weight:700;font-size:15px;color:var(--c-dark);font-family:var(--f-heading);">{{ __('Cours') }}</div>
                            <div style="font-size:12px;color:var(--c-text-muted);margin-top:4px;">Formation, tutoriel</div>
                        </div>
                    </div>
                </div>

                {{-- Étape 2 : URL --}}
                <div x-show="step===2" x-transition role="region" aria-label="Étape 2 : URL de la ressource">
                    <h4 style="margin-top:0;font-weight:700;color:#1e293b;margin-bottom:16px;">{{ __('Collez l\'URL de la ressource') }}</h4>
                    <div style="margin-bottom:16px;">
                        <input type="url" x-model="url" @paste.debounce.500ms="fetchMeta()" @input.debounce.800ms="fetchMeta()"
                               class="form-control" placeholder="https://youtube.com/watch?v=... ou https://..." style="border-radius:8px;height:48px;font-size:15px;" required autofocus>
                    </div>

                    {{-- YouTube preview --}}
                    <div x-show="loading" style="text-align:center;padding:20px;">
                        <div class="spinner-border spinner-border-sm" style="color:#10b981;"></div>
                        <span style="margin-left:8px;color:#6b7280;font-size:14px;">{{ __('Détection en cours...') }}</span>
                    </div>

                    {{-- Erreur vidéo --}}
                    <div x-show="videoError" x-cloak style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 16px;margin-bottom:12px;display:flex!important;align-items:center!important;gap:10px;">
                        <span style="font-size:20px;">⚠️</span>
                        <div>
                            <div style="font-weight:600;color:#dc2626;font-size:14px;" x-text="videoError"></div>
                            <div style="font-size:12px;color:#6b7280;margin-top:2px;">{{ __('Vérifiez l\'URL et réessayez, ou utilisez une autre vidéo.') }}</div>
                        </div>
                    </div>

                    <div style="display:flex;gap:8px;margin-top:16px;">
                        <button type="button" @click="back()" style="padding:10px 20px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-weight:600;color:#6b7280;">← {{ __('Retour') }}</button>
                        <button type="button" @click="fetchMeta()" :disabled="!url || url.length < 10" style="padding:10px 20px;border:none;border-radius:8px;background:var(--c-primary);color:#fff;cursor:pointer;font-weight:600;flex:1;" :style="(!url || url.length < 10) && 'opacity:0.5;cursor:not-allowed'">{{ __('Continuer') }} →</button>
                    </div>
                </div>

                {{-- Étape 3 : Vérifier et soumettre --}}
                <div x-show="step===3" x-transition role="region" aria-label="Étape 3 : vérification">
                    <h4 style="margin-top:0;font-weight:700;color:#1e293b;margin-bottom:16px;">{{ __('Vérifier et soumettre') }}</h4>

                    {{-- YouTube preview card --}}
                    <div x-show="isYoutube && thumbnail" style="display:flex;gap:14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px;margin-bottom:16px;align-items:center;">
                        <img :src="thumbnail" alt="" style="width:120px;height:68px;object-fit:cover;border-radius:8px;flex-shrink:0;">
                        <div style="overflow:hidden;">
                            <div style="font-weight:600;color:#1e293b;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="title"></div>
                            <div style="font-size:12px;color:#6b7280;margin-top:2px;" x-show="author" x-text="author"></div>
                            <div style="display:inline-block;background:#fef2f2;color:#ef4444;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;margin-top:4px;">YouTube</div>
                        </div>
                    </div>

                    <form action="{{ route('directory.resources.store', $tool->slug) }}" method="POST" @submit.prevent="submitting = true; fetch($el.action, {method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:new FormData($el)}).then(r=>{if(r.ok){step=4;submitting=false}else{alert('Erreur lors de la soumission');submitting=false}}).catch(()=>{alert('Erreur réseau');submitting=false})">
                        @csrf
                        <input type="hidden" name="type" :value="type">
                        <input type="hidden" name="video_id" :value="videoId">
                        <input type="hidden" name="thumbnail" :value="thumbnail">
                        <input type="hidden" name="duration" :value="duration">
                        <input type="hidden" name="channel_name" :value="channelName">
                        <input type="hidden" name="channel_url" :value="channelUrl">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group" style="margin-bottom:12px;">
                                    <label style="font-size:13px;font-weight:600;">{{ __('Titre') }}</label>
                                    <input type="text" name="title" x-model="title" class="form-control" placeholder="{{ __('Ex: Guide complet pour débuter') }}" style="border-radius:8px;height:40px;" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group" style="margin-bottom:12px;">
                                    <label style="font-size:13px;font-weight:600;">{{ __('Langue') }}</label>
                                    <select name="language" x-model="language" class="form-control" style="border-radius:8px;height:40px;">
                                        <option value="fr">🇫🇷 {{ __('Français') }}</option>
                                        <option value="en">🇬🇧 {{ __('Anglais') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="url" :value="url">
                        <div style="display:flex;gap:8px;margin-top:8px;">
                            <button type="button" @click="back()" style="padding:10px 20px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-weight:600;color:#6b7280;">← {{ __('Modifier') }}</button>
                            <button type="submit" :disabled="submitting" class="wz-submit">
                                <span x-show="!submitting">{{ __('Soumettre pour approbation') }}</span>
                                <span x-show="submitting" x-cloak>{{ __('Envoi en cours...') }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Étape 4 : Confirmation post-soumission --}}
                <div x-show="step===4" x-transition role="region" aria-label="Étape 4 : confirmation">
                    <div style="text-align:center;padding:20px 0;">
                        <div style="font-size:48px;margin-bottom:12px;">🎉</div>
                        <h4 style="font-weight:700;color:var(--c-dark);margin-bottom:8px;">{{ __('Ressource soumise avec succès !') }}</h4>
                        <p style="color:var(--c-text-muted);margin-bottom:20px;">{{ __('Notre équipe va examiner votre soumission. Délai estimé : 24-48h.') }}</p>
                        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px;margin-bottom:20px;text-align:left;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span style="color:#16a34a;">✓</span>
                                <span style="font-weight:600;color:#15803d;">{{ __('En attente d\'approbation') }}</span>
                            </div>
                            <p style="color:#6b7280;font-size:13px;margin:0;">{{ __('Vous recevrez une notification quand votre ressource sera approuvée.') }}</p>
                        </div>
                        @if(Route::has('user.contributions'))
                        <a href="{{ route('user.contributions') }}" style="display:inline-block;padding:10px 24px;background:var(--c-primary);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;">{{ __('Voir mes contributions') }}</a>
                        @endif
                    </div>
                </div>
            </div>
            @else
                <div style="text-align: center; padding: 12px;">
                    <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour partager une ressource.') }}' })"
                        style="background: var(--c-primary); color: #fff; border: none; border-radius: var(--r-btn); padding: 10px 24px; font-weight: 600; cursor: pointer; font-size: 14px;">
                        🔐 {{ __('Se connecter') }}
                    </button>
                </div>
            @endauth
        </div>

        {{-- TAB: Screenshots --}}
        <div class="rt-panel" x-show="tab==='screenshots'" x-cloak style="padding: 24px;">
            <h3 style="font-weight: 700; color: var(--c-dark); margin-top: 0; margin-bottom: 16px; font-size: 18px;">📸 {{ __('Screenshots de la communauté') }}</h3>

            {{-- Galerie --}}
            @if($screenshots->isNotEmpty())
                <div class="row" style="margin-bottom: 24px;">
                    @foreach($screenshots as $ss)
                    <div class="col-md-4 col-sm-6 col-xs-12" style="margin-bottom: 16px;">
                        <div style="border: 1px solid #E5E7EB; border-radius: var(--r-base); overflow: hidden; background: #F9FAFB;">
                            <a href="{{ asset($ss->image_path) }}" @click.prevent="$dispatch('lightbox', { src: '{{ asset($ss->image_path) }}', alt: '{{ $ss->caption ?? $tool->name }}' })" style="display: block; cursor: zoom-in;">
                                <img src="{{ asset($ss->image_path) }}" alt="{{ $ss->caption ?? $tool->name }}" loading="lazy" style="width: 100%; height: 200px; object-fit: cover; display: block;">
                            </a>
                            <div style="padding: 10px 12px; display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 12px; color: #6B7280;">{{ $ss->caption ?? __('Screenshot') }} — {{ $ss->user->name ?? __('Anonyme') }}</span>
                                <div style="display:flex!important;align-items:center!important;gap:8px;">
                                    <button onclick="fetch('{{ route('directory.screenshots.vote', $ss->id) }}', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(r=>r.json()).then(d=>{this.closest('div').querySelector('.vote-count').textContent=d.votes})" style="background:none!important;border:none!important;cursor:pointer;color:#E74C3C;font-size:13px;font-weight:600;outline:none!important;box-shadow:none!important;">
                                        ❤️ <span class="vote-count">{{ $ss->votes_count }}</span>
                                    </button>
                                    @can('view_admin_panel')
                                    <button @click="$dispatch('confirm-action', { title: '{{ __("Supprimer") }}', message: '{{ __("Supprimer ce screenshot ?") }}', action: () => fetch('{{ route('directory.screenshots.delete', $ss->id) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(r=>{if(r.ok)$el.closest('.col-md-4').remove()}) })" style="background:none!important;border:none!important;cursor:pointer;color:#9ca3af;font-size:12px;outline:none!important;box-shadow:none!important;" title="{{ __('Supprimer') }}">✕</button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 40px 20px; background: #F9FAFB; border-radius: var(--r-base); margin-bottom: 24px;">
                    <div style="font-size: 36px; margin-bottom: 8px;">📷</div>
                    <p style="color: #6B7280; margin: 0;">{{ __('Aucun screenshot pour le moment. Soyez le premier a en partager !') }}</p>
                </div>
            @endif

            {{-- Upload form (drop + paste + file — zone cliquable, compression auto) --}}
            @auth
                <div x-data="{
                    preview: null,
                    fileName: null,
                    dragging: false,
                    compressAndSet(file) {
                        if (!file || !file.type.startsWith('image/')) { alert('{{ __('Format non supporté. Utilisez JPG, PNG ou WebP.') }}'); return; }
                        const maxW = 1920, quality = 0.85;
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const img = new Image();
                            img.onload = () => {
                                const canvas = document.createElement('canvas');
                                const scale = img.width > maxW ? maxW / img.width : 1;
                                canvas.width = img.width * scale;
                                canvas.height = img.height * scale;
                                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                                canvas.toBlob((blob) => {
                                    if (blob.size > 5 * 1024 * 1024) { alert('{{ __('Image trop lourde même après compression (max 5 Mo)') }}'); return; }
                                    this.preview = URL.createObjectURL(blob);
                                    this.fileName = file.name || 'screenshot.jpg';
                                    const dt = new DataTransfer();
                                    dt.items.add(new File([blob], this.fileName, { type: 'image/jpeg' }));
                                    this.$refs.fileInput.files = dt.files;
                                }, 'image/jpeg', quality);
                            };
                            img.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    },
                    handlePaste(e) {
                        const items = e.clipboardData?.items;
                        if (!items) return;
                        for (const item of items) {
                            if (item.type.startsWith('image/')) { e.preventDefault(); this.compressAndSet(item.getAsFile()); return; }
                        }
                    },
                    handleDrop(e) { this.dragging = false; const f = e.dataTransfer?.files?.[0]; if (f) this.compressAndSet(f); },
                    reset() { this.preview = null; this.fileName = null; this.$refs.fileInput.value = ''; }
                }"
                @paste.window="handlePaste($event)"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="handleDrop($event)">

                    <h4 style="font-weight:700;color:var(--c-dark);margin:0 0 12px;font-size:15px;">{{ __('Ajouter un screenshot') }}</h4>

                    <form method="POST" action="{{ route('directory.screenshots.store', $tool->slug) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="screenshot" accept="image/*" required x-ref="fileInput" @change="compressAndSet($event.target.files[0])" style="display:none!important;">

                        {{-- Zone drop/paste/clic --}}
                        <div x-show="!preview"
                            @click="$refs.fileInput.click()"
                            style="text-align:center;padding:40px 20px;margin-bottom:14px;border:2px dashed #cbd5e1;border-radius:12px;cursor:pointer;transition:all .2s;background:#f8fafc;"
                            :style="dragging ? 'border-color:var(--c-primary);background:#e0f2fe' : ''"
                            @mouseenter="$el.style.borderColor='var(--c-primary)';$el.style.background='#f0f9ff'"
                            @mouseleave="!dragging && ($el.style.borderColor='#cbd5e1') && ($el.style.background='#f8fafc')">
                            <div style="font-size:40px;margin-bottom:10px;">📸</div>
                            <p style="color:var(--c-dark);font-size:15px;margin:0 0 4px;font-weight:700;">{{ __('Glissez-déposez, collez (Ctrl+V) ou cliquez') }}</p>
                            <p style="color:#9ca3af;font-size:12px;margin:0;">{{ __('JPG, PNG, WebP — compression automatique, max 1920px') }}</p>
                        </div>

                        {{-- Preview --}}
                        <div x-show="preview" x-cloak style="text-align:center;margin-bottom:14px;border:1px solid #e5e7eb;border-radius:12px;padding:16px;background:#fff;">
                            <img :src="preview" alt="Preview" style="max-height:220px;max-width:100%;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                            <div style="margin-top:10px;display:flex!important;justify-content:center!important;gap:12px;align-items:center!important;">
                                <span style="font-size:12px;color:#6b7280;" x-text="fileName"></span>
                                <button type="button" @click="reset()" style="background:none;border:none;color:#ef4444;font-size:12px;cursor:pointer;font-weight:600;">✕ {{ __('Retirer') }}</button>
                            </div>
                        </div>

                        <div style="display:flex!important;gap:8px;flex-wrap:wrap!important;align-items:center!important;">
                            <input type="text" name="caption" placeholder="{{ __('Description (optionnel)') }}" maxlength="255"
                                style="flex:1;min-width:200px;height:40px;padding:0 14px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;">
                            <button type="submit" :disabled="!preview"
                                style="height:40px!important;padding:0 24px!important;background:var(--c-primary)!important;color:#fff!important;border:none!important;outline:none!important;box-shadow:none!important;border-radius:8px!important;font-weight:600!important;font-size:14px!important;cursor:pointer;transition:opacity .2s;"
                                :style="!preview && 'opacity:0.5!important;cursor:not-allowed!important'">
                                {{ __('Envoyer') }}
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div style="text-align: center; padding: 12px;">
                    <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour partager un screenshot.') }}' })"
                        style="background: var(--c-primary); color: #fff; border: none; border-radius: var(--r-btn); padding: 10px 24px; font-weight: 600; cursor: pointer; font-size: 14px;">
                        🔐 {{ __('Se connecter') }}
                    </button>
                </div>
            @endauth
        </div>

        {{-- TAB: Alternatives --}}
        <div class="rt-panel" x-show="tab==='alternatives'" x-cloak>
            @if($similarTools->isNotEmpty())
            <div class="row">
                @foreach($similarTools as $sim)
                @php $simHost = $sim->url ? parse_url($sim->url, PHP_URL_HOST) : ''; @endphp
                <div class="col-md-3 col-sm-6 col-xs-12" style="margin-bottom: 12px;">
                    <a href="{{ route('directory.show', $sim->slug) }}" style="display: block; text-align: center; border: 1px solid #E5E7EB; border-radius: var(--r-base); padding: 16px; text-decoration: none; color: inherit; transition: all 0.2s; height: 100%;">
                        @if($simHost)<img src="https://www.google.com/s2/favicons?domain={{ $simHost }}&sz=64" alt="" width="32" height="32" style="margin-bottom: 8px;">@endif
                        <div style="font-family: var(--f-heading); font-weight: 700; font-size: 0.95rem;">{{ $sim->name }}</div>
                        <span class="rt-badge badge-{{ $sim->pricing }}" style="font-size: 0.6rem; margin-top: 6px;">{{ $pricingLabels[$sim->pricing] ?? ucfirst($sim->pricing) }}</span>
                    </a>
                </div>
                @endforeach
            </div>
            @else <p style="color: #9CA3AF; text-align: center; padding: 30px;">{{ __('Aucune alternative pour le moment.') }}</p> @endif
        </div>
    </div>
</div>
</section>
@endsection

@push('scripts')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "SoftwareApplication",
    "name": "{{ $tool->name }}",
    "description": "{{ Str::limit(strip_tags($tool->description), 160) }}",
    "url": "{{ route('directory.show', $tool->slug) }}",
    "applicationCategory": "{{ $tool->categories->first()->name ?? 'Tech' }}",
    "operatingSystem": "Web"
}
</script>
@endpush
