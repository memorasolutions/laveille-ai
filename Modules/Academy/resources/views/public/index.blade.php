<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Académie IA - ' . config('app.name'))
@section('meta_description', "Formations pratiques sur l'intelligence artificielle pour apprendre à intégrer l'IA dans votre quotidien professionnel au Québec.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Académie'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">Académie</h1>
                    <p style="margin-bottom: 28px;">Des formations pratiques pour maîtriser l'IA au Québec.</p>

                    {{-- Recherche --}}
                    <form method="GET" action="{{ route('academy.index') }}" class="mb-3" role="search">
                        @if($currentFilter)
                            <input type="hidden" name="filter" value="{{ $currentFilter }}">
                        @endif
                        @if($currentLevel)
                            <input type="hidden" name="level" value="{{ $currentLevel }}">
                        @endif
                        @if(!empty($currentCategory))
                            <input type="hidden" name="category" value="{{ $currentCategory }}">
                        @endif
                        <div class="d-flex flex-wrap align-items-center gap-2" style="max-width: 540px;">
                            <input type="search" name="q" value="{{ $currentSearch ?? '' }}"
                                   class="form-control" style="flex: 1 1 240px; min-width: 200px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem);"
                                   placeholder="Rechercher une formation…"
                                   aria-label="Rechercher une formation">
                            <x-core::button type="submit" variant="primary" size="sm">Rechercher</x-core::button>
                            @if(!empty($currentSearch))
                                <x-core::button :href="route('academy.index', array_filter(['filter' => $currentFilter, 'level' => $currentLevel, 'category' => $currentCategory ?? null]))" variant="ghost" size="sm">Effacer</x-core::button>
                            @endif
                        </div>
                    </form>

                    {{-- Filtres --}}
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
                        <x-core::button :href="route('academy.index', array_filter(['level' => $currentLevel, 'category' => $currentCategory ?? null]))"
                                        :variant="is_null($currentFilter) ? 'primary' : 'secondary'" size="sm">Tous</x-core::button>
                        <x-core::button :href="route('academy.index', array_filter(['filter' => 'free', 'level' => $currentLevel, 'category' => $currentCategory ?? null]))"
                                        :variant="$currentFilter === 'free' ? 'primary' : 'secondary'" size="sm">Gratuit</x-core::button>
                        <x-core::button :href="route('academy.index', array_filter(['filter' => 'paid', 'level' => $currentLevel, 'category' => $currentCategory ?? null]))"
                                        :variant="$currentFilter === 'paid' ? 'primary' : 'secondary'" size="sm">Payant</x-core::button>

                        <form method="GET" action="{{ route('academy.index') }}" class="ms-auto d-flex flex-wrap gap-2">
                            @if($currentFilter)
                                <input type="hidden" name="filter" value="{{ $currentFilter }}">
                            @endif
                            <label for="academy-level-filter" class="visually-hidden">Filtrer par niveau</label>
                            <select id="academy-level-filter" name="level" onchange="this.form.submit()" class="form-select form-select-sm"
                                    style="min-width: 180px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem);">
                                <option value="">Tous les niveaux</option>
                                <option value="intro"         {{ $currentLevel === 'intro'         ? 'selected' : '' }}>Débutant</option>
                                <option value="intermediaire" {{ $currentLevel === 'intermediaire' ? 'selected' : '' }}>Intermédiaire</option>
                                <option value="avance"        {{ $currentLevel === 'avance'        ? 'selected' : '' }}>Avancé</option>
                            </select>

                            @if($categories->isNotEmpty())
                                <label for="academy-category-filter" class="visually-hidden">Filtrer par catégorie</label>
                                <select id="academy-category-filter" name="category" onchange="this.form.submit()" class="form-select form-select-sm"
                                        style="min-width: 180px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem);">
                                    <option value="">Toutes les catégories</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ (string) $currentCategory === (string) $cat->id ? 'selected' : '' }}>
                                            {{ $cat->icon ? $cat->icon . ' ' : '' }}{{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </form>
                    </div>

                    {{-- Grille de cours --}}
                    @if($courses->count())
                        <div class="row g-4">
                            @foreach($courses as $course)
                                <div class="col-md-6">
                                    @include('academy::public.partials.course-card', ['course' => $course])
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $courses->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <p class="h5" style="color: var(--sys-text-muted, #6B7280);">Aucune formation disponible pour le moment.</p>
                            @if($currentFilter || $currentLevel || !empty($currentCategory))
                                <p class="mt-3">
                                    <x-core::button :href="route('academy.index')" variant="secondary" size="sm">Réinitialiser les filtres</x-core::button>
                                </p>
                            @endif
                        </div>
                    @endif

                </article>
            </div>
        </div>
    </div>
</section>
@endsection
