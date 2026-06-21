<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@php
    $levelLabels  = ['intro' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'];
    $accessLabels = ['free' => 'Gratuit', 'paid_one_time' => 'Accès unique'];

    $totalLessons   = $course->chapters->sum(fn ($ch) => $ch->lessons->count());
    $totalChapters  = $course->chapters->count();
@endphp

@section('title', $course->title . ' - Académie - ' . config('app.name'))
@section('meta_description', $course->summary ?? $course->subtitle ?? 'Formation IA')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $course->title,
        'breadcrumbItems' => ['Académie', $course->title],
    ])
@endsection

@php
    // M6 - JSON-LD Course (tableau PHP, jamais package spatie/schema-org)
    $__courseJsonLd = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Course',
        'name'        => $course->title,
        'inLanguage'  => 'fr-CA',
        'provider'    => [
            '@type' => 'Organization',
            'name'  => config('app.name'),
            'url'   => url('/'),
        ],
    ];
    if (filled($course->summary ?? $course->description)) {
        $__courseJsonLd['description'] = \Illuminate\Support\Str::limit(
            strip_tags((string) ($course->summary ?? $course->description)),
            300,
            ''
        );
    }
    if (filled($course->slug)) {
        $__courseJsonLd['url'] = route('academy.courses.show', $course->slug);
    }
    $__courseJsonLdEncoded = json_encode($__courseJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
@push('head')
<script type="application/ld+json">{!! $__courseJsonLdEncoded !!}</script>
@endpush

@push('styles')
<style>
    .syllabus-lessons { list-style: none; padding-left: 0; margin-bottom: 0; }
    .syllabus-lessons li {
        padding: 0.45rem 0;
        border-bottom: 1px solid rgba(0,0,0,.05);
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .syllabus-lessons li:last-child { border-bottom: none; }
    .syllabus-lock { color: var(--sys-text-muted, #9CA3AF); font-size: 0.85rem; }
    .accordion-button:not(.collapsed) { background: rgba(6,78,90,0.05); color: var(--c-primary, #064E5A); box-shadow: none; }
    .accordion-button:focus { box-shadow: none; }
</style>
@endpush

@section('content')
{{-- Bandeau de prévisualisation : visible UNIQUEMENT à un gérant du cours (gate serveur
     dans CourseController@show). a11y : role=status, contraste AA, cible de retour ≥24px. --}}
@if ($isPreview ?? false)
    <div role="status"
         style="background: var(--sys-action-primary, #064E5A); color: #FFFFFF; padding: 12px 16px;">
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span style="font-weight: 600; font-size: 0.95rem;">
                👁️ Mode prévisualisation — vous voyez ce cours comme un étudiant.
            </span>
            <a href="{{ route('academy.courses.manage', $course->slug) }}"
               style="display: inline-flex; align-items: center; min-height: 24px; padding: 5px 14px; border-radius: 999px; background: #FFFFFF; color: var(--sys-action-primary, #064E5A); font-weight: 700; font-size: 0.85rem; text-decoration: none;">
                Quitter la prévisualisation
            </a>
        </div>
    </div>
@endif
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row g-5">

            {{-- Colonne principale --}}
            <div class="col-lg-8">
                {{-- Image de couverture (média Spatie, conversion « large ») --}}
                @php($showCover = $course->coverUrl('large'))
                @if ($showCover)
                    <img src="{{ $showCover }}" alt="Image de couverture du cours « {{ $course->title }} »" loading="eager"
                         style="width: 100%; aspect-ratio: 16/9; object-fit: cover; border-radius: var(--sys-radius-md, 0.75rem); margin-bottom: 1.25rem;">
                @endif

                <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 0.75rem;">
                    {{ $course->title }}
                </h1>

                @if($course->subtitle)
                    <p class="mb-4" style="color: var(--sys-text-muted, #6B7280); font-size: 1.05rem;">{{ $course->subtitle }}</p>
                @endif

                {{-- Badges meta --}}
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="badge rounded-pill"
                          style="border: 1px solid var(--c-primary, #064E5A); color: var(--c-primary, #064E5A); background: transparent; font-size: 0.8rem; padding: 5px 12px;">
                        {{ $levelLabels[$course->level] ?? ucfirst($course->level) }}
                    </span>
                    <span class="badge rounded-pill bg-light text-dark" style="font-size: 0.8rem; padding: 5px 12px;">
                        {{ $accessLabels[$course->access_type] ?? ucfirst(str_replace('_', ' ', $course->access_type)) }}
                    </span>
                    @if($course->duration_minutes)
                        <span class="badge rounded-pill bg-light text-dark" style="font-size: 0.8rem; padding: 5px 12px;">
                            {{ $course->duration_minutes }} min
                        </span>
                    @endif
                </div>

                {{-- Description --}}
                @if($course->description)
                    <div class="mb-5">
                        <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px; margin-bottom: 16px;">À propos de ce cours</h2>
                        <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">{!! nl2br(e($course->description)) !!}</div>
                    </div>
                @endif

                {{-- Syllabus --}}
                <div class="mb-5">
                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px; margin-bottom: 4px;">
                        Contenu du cours
                    </h2>
                    <p class="small mb-3" style="color: var(--sys-text-muted, #6B7280);">{{ $totalChapters }} chapitre{{ $totalChapters > 1 ? 's' : '' }} · {{ $totalLessons }} leçon{{ $totalLessons > 1 ? 's' : '' }}</p>

                    @if($course->chapters->isEmpty())
                        <p style="color: var(--sys-text-muted, #6B7280);">Le contenu détaillé sera disponible prochainement.</p>
                    @else
                        <div class="accordion" id="syllabusAccordion">
                            @foreach($course->chapters as $chapter)
                                <div class="accordion-item" style="border-radius: 8px; margin-bottom: 6px; overflow: hidden; border: 1px solid #E5E7EB;">
                                    <h3 class="accordion-header" id="ch-heading-{{ $chapter->id }}">
                                        <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#ch-collapse-{{ $chapter->id }}"
                                                aria-expanded="false"
                                                aria-controls="ch-collapse-{{ $chapter->id }}">
                                            <strong>{{ $chapter->title }}</strong>
                                            <span class="badge bg-light text-muted ms-2" style="font-weight: 600; font-size: 0.75rem;">
                                                {{ $chapter->lessons->count() }} leçon{{ $chapter->lessons->count() > 1 ? 's' : '' }}
                                            </span>
                                        </button>
                                    </h3>
                                    <div id="ch-collapse-{{ $chapter->id }}"
                                         class="accordion-collapse collapse"
                                         aria-labelledby="ch-heading-{{ $chapter->id }}"
                                         data-bs-parent="#syllabusAccordion">
                                        <div class="accordion-body pt-2 pb-3">
                                            <ul class="syllabus-lessons">
                                                @foreach($chapter->lessons as $lesson)
                                                    <li>
                                                        @if($isEnrolled)
                                                            <span style="color: var(--c-primary, #064E5A);">▶</span>
                                                        @else
                                                            <span class="syllabus-lock">🔒</span>
                                                        @endif
                                                        {{ $lesson->title }}
                                                        @if($lesson->estimated_minutes)
                                                            <span class="text-muted ms-auto" style="font-size:0.8rem; white-space:nowrap;">{{ $lesson->estimated_minutes }} min</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- FAQ placeholder --}}
                @if(!empty($course->faq_dictionary_ids))
                    <div class="mb-5">
                        <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px; margin-bottom: 16px;">Questions fréquentes</h2>
                        <p style="color: var(--sys-text-muted, #6B7280);">Contenu à venir.</p>
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <div class="card shadow-sm" style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem);">
                        <div class="card-body p-4">
                            {{-- Prix / badge gratuit --}}
                            @if($course->price_cents && $course->price_cents > 0)
                                <div class="h3 mb-0" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23);">
                                    {{ number_format($course->price_cents / 100, 2, ',', ' ') }} $ CA
                                </div>
                                <p class="small mb-3" style="color: var(--sys-text-muted, #6B7280);">{{ $accessLabels[$course->access_type] ?? '' }}</p>
                            @else
                                <span class="badge rounded-pill mb-3"
                                      style="background: #D1FAE5; color: #065F46; font-size: 0.95rem; padding: 7px 16px; font-weight: 700;">
                                    Gratuit
                                </span>
                            @endif

                            {{-- CTA --}}
                            @if(!auth()->check())
                                <div class="mb-3">
                                    <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" :block="true">
                                        Se connecter pour s'inscrire
                                    </x-core::button>
                                </div>
                            @elseif(!$isEnrolled && $isFree)
                                <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="mb-3">
                                    @csrf
                                    <x-core::button type="submit" variant="primary" :block="true">
                                        S'inscrire gratuitement
                                    </x-core::button>
                                </form>
                            @elseif(!$isEnrolled && !$isFree)
                                <div class="mb-3">
                                    <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" :block="true">
                                        Acheter ce cours
                                    </x-core::button>
                                    @if(session('error'))
                                        <p class="text-danger small mt-1 mb-0">{{ session('error') }}</p>
                                    @endif
                                </div>
                            @else
                                <div class="mb-3">
                                    <x-core::button href="#" variant="primary" :block="true">
                                        Continuer le cours
                                    </x-core::button>
                                </div>
                            @endif

                            {{-- Meta liste --}}
                            <ul class="list-unstyled mb-0" style="font-size: 0.9rem;">
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span style="color: var(--sys-text-muted, #6B7280);">Niveau</span>
                                    <strong>{{ $levelLabels[$course->level] ?? ucfirst($course->level) }}</strong>
                                </li>
                                @if($course->duration_minutes)
                                    <li class="d-flex justify-content-between py-2 border-bottom">
                                        <span style="color: var(--sys-text-muted, #6B7280);">Durée</span>
                                        <strong>{{ $course->duration_minutes }} min</strong>
                                    </li>
                                @endif
                                <li class="d-flex justify-content-between py-2">
                                    <span style="color: var(--sys-text-muted, #6B7280);">Langue</span>
                                    <strong>Français (CA)</strong>
                                </li>
                            </ul>

                            <hr class="my-3">

                            <a href="{{ route('academy.index') }}"
                               style="color: var(--sys-text-link, #064E5A); font-weight: 600; text-decoration: none; font-size: 0.9rem;">
                                ← Retour à l'Académie
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection
