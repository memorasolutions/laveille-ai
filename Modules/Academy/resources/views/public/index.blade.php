<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Académie IA - ' . config('app.name'))
@section('meta_description', "Formations pratiques sur l'intelligence artificielle pour apprendre à intégrer l'IA dans votre quotidien professionnel au Québec.")

@push('head')
@php
    $academyBreadcrumbJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Accueil',
                'item' => route('home'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Académie',
                'item' => url()->current(),
            ],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($academyBreadcrumbJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@push('styles')
<style>
    .academy-hero {
        background: linear-gradient(135deg, var(--c-primary, #064E5A) 0%, #0B7285 100%);
        color: #fff;
        padding: 3rem 0 2.5rem;
        margin-bottom: 2rem;
    }
    .academy-hero h1 { font-family: var(--f-heading); font-weight: 800; margin-bottom: 0.5rem; }
    .academy-breadcrumb {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.85);
        margin-bottom: 0.75rem;
    }
    .academy-breadcrumb a {
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
    }
    .academy-breadcrumb a:hover,
    .academy-breadcrumb a:focus { color: #fff; text-decoration: underline; }
    .academy-breadcrumb .sep { margin: 0 0.4rem; opacity: 0.7; }
    .academy-breadcrumb [aria-current="page"] { color: #fff; font-weight: 600; }
    .academy-pill {
        display: inline-flex;
        align-items: center;
        padding: 6px 18px;
        min-height: 40px;
        border-radius: 50rem;
        border: 1px solid #E5E7EB;
        background: #fff;
        color: #374151;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: background 0.15s, color 0.15s, border-color 0.15s;
    }
    .academy-pill:hover { background: #F3F4F6; color: var(--c-dark, #1A1D23); }
    .academy-pill.active { background: var(--c-primary, #064E5A); color: #fff; border-color: var(--c-primary, #064E5A); }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    {{-- Hero --}}
    <div class="academy-hero">
        <div class="container text-center">
            <nav class="academy-breadcrumb" aria-label="Fil d'Ariane">
                <a href="{{ route('home') }}">Accueil</a>
                <span class="sep" aria-hidden="true">/</span>
                <span aria-current="page">Académie</span>
            </nav>
            <h1>Académie</h1>
            <p class="lead mb-0">Des formations pratiques pour maîtriser l'IA au Québec.</p>
        </div>
    </div>

    <div class="container">
        {{-- M7 - Recherche --}}
        <form method="GET" action="{{ route('academy.index') }}" class="mb-3" role="search">
            @if($currentFilter)
                <input type="hidden" name="filter" value="{{ $currentFilter }}">
            @endif
            @if($currentLevel)
                <input type="hidden" name="level" value="{{ $currentLevel }}">
            @endif
            <div class="input-group" style="max-width: 480px;">
                <input type="search" name="q" value="{{ $currentSearch ?? '' }}"
                       class="form-control" placeholder="Rechercher une formation…"
                       aria-label="Rechercher une formation">
                <button class="btn btn-primary" type="submit" style="background: var(--c-primary); border-color: var(--c-primary);">
                    Rechercher
                </button>
                @if(!empty($currentSearch))
                    <a href="{{ route('academy.index', array_filter(['filter' => $currentFilter, 'level' => $currentLevel])) }}"
                       class="btn btn-outline-secondary" title="Effacer la recherche">×</a>
                @endif
            </div>
        </form>

        {{-- Filtres --}}
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <a href="{{ route('academy.index', array_filter(['level' => $currentLevel])) }}"
               class="academy-pill {{ is_null($currentFilter) ? 'active' : '' }}">Tous</a>
            <a href="{{ route('academy.index', array_filter(['filter' => 'free', 'level' => $currentLevel])) }}"
               class="academy-pill {{ $currentFilter === 'free' ? 'active' : '' }}">Gratuit</a>
            <a href="{{ route('academy.index', array_filter(['filter' => 'paid', 'level' => $currentLevel])) }}"
               class="academy-pill {{ $currentFilter === 'paid' ? 'active' : '' }}">Payant</a>

            <form method="GET" action="{{ route('academy.index') }}" class="ms-auto">
                @if($currentFilter)
                    <input type="hidden" name="filter" value="{{ $currentFilter }}">
                @endif
                <label for="academy-level-filter" class="visually-hidden">Filtrer par niveau</label>
                <select id="academy-level-filter" name="level" onchange="this.form.submit()" class="form-select form-select-sm"
                        style="min-width: 180px; border-radius: 50rem;">
                    <option value="">Tous les niveaux</option>
                    <option value="intro"         {{ $currentLevel === 'intro'         ? 'selected' : '' }}>Débutant</option>
                    <option value="intermediaire" {{ $currentLevel === 'intermediaire' ? 'selected' : '' }}>Intermédiaire</option>
                    <option value="avance"        {{ $currentLevel === 'avance'        ? 'selected' : '' }}>Avancé</option>
                </select>
            </form>
        </div>

        {{-- Grille de cours --}}
        @if($courses->count())
            <div class="row g-4">
                @foreach($courses as $course)
                    <div class="col-md-6 col-lg-4">
                        @include('academy::public.partials.course-card', ['course' => $course])
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $courses->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <p class="h5 text-muted">Aucune formation disponible pour le moment.</p>
                @if($currentFilter || $currentLevel)
                    <a href="{{ route('academy.index') }}" class="mt-3 d-inline-block"
                       style="color: var(--c-primary);">Réinitialiser les filtres</a>
                @endif
            </div>
        @endif
    </div>
</section>
@endsection
