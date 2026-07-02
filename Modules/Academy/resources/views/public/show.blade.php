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
    .syllabus-lesson-link {
        display: flex;
        align-items: center;
        gap: 6px;
        width: 100%;
        color: var(--sys-text-link, var(--c-primary, #064E5A));
        text-decoration: none;
    }
    .syllabus-lesson-link:hover,
    .syllabus-lesson-link:focus-visible { text-decoration: underline; }
    /* Focus-visible + contraste AAA état déplié centralisés dans public/css/charte.css (DRY, audit 2026-07-02) */
    .accordion-button:not(.collapsed) { background: rgba(6,78,90,0.05); box-shadow: none; }
    .accordion-button:focus { box-shadow: none; }

    /* Bloc de partage du cours (marketing) - charte teal, cibles >=44px, WCAG 2.2 AA */
    .ac-share { margin-top: 0; }
    .ac-share-title { font-size: 0.95rem; font-weight: 700; color: var(--c-primary, #064E5A); margin: 0 0 10px; }
    .ac-share-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .ac-share-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        min-height: 44px; min-width: 44px; padding: 8px 14px;
        background: transparent; color: var(--c-primary, #064E5A);
        border: 2px solid var(--c-primary, #064E5A); border-radius: 999px;
        text-decoration: none; cursor: pointer; font-size: 0.85rem; font-weight: 600;
        transition: background 200ms, color 200ms, transform 100ms;
    }
    @media (prefers-reduced-motion: reduce) { .ac-share-btn { transition: none; } }
    .ac-share-btn:hover { background: var(--c-primary, #064E5A); color: #FFFFFF; }
    .ac-share-btn:focus-visible { outline: 3px solid var(--c-accent, #9A2A06); outline-offset: 2px; }
    .ac-share-btn:active { transform: scale(0.97); }
    .ac-share-btn svg { flex-shrink: 0; }
</style>
@endpush

@section('content')
<x-academy::nav />
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
                                                            {{-- Inscrit (ou staff/preview) : leçon cliquable vers sa page. --}}
                                                            <a href="{{ route('academy.lessons.show', [$course, $lesson]) }}"
                                                               class="syllabus-lesson-link">
                                                                <span aria-hidden="true">▶</span>
                                                                <span>{{ $lesson->title }}</span>
                                                                @if($lesson->estimated_minutes)
                                                                    <span class="text-muted ms-auto" style="font-size:0.8rem; white-space:nowrap;">{{ $lesson->estimated_minutes }} min</span>
                                                                @endif
                                                            </a>
                                                        @else
                                                            {{-- Non inscrit : aperçu du titre seulement, pas de lien (verrouillé). --}}
                                                            <span class="syllabus-lock">🔒</span>
                                                            {{ $lesson->title }}
                                                            @if($lesson->estimated_minutes)
                                                                <span class="text-muted ms-auto" style="font-size:0.8rem; white-space:nowrap;">{{ $lesson->estimated_minutes }} min</span>
                                                            @endif
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

                {{-- Séances en direct (visioconférence native). Visible aux INSCRITS
                     ACTIFS (ou staff en preview) seulement + drapeau live_sessions_enabled.
                     Le composant re-vérifie l'accès serveur (403 sinon) : ce @if n'est
                     qu'un affichage. Aucune fuite pour un non-inscrit. --}}
                @if($isEnrolled && config('academy.live_sessions_enabled'))
                    <div class="mb-5" style="margin-top: 36px;">
                        @livewire('academy.course-live-sessions', ['course' => $course])
                    </div>
                @endif

                {{-- Test de positionnement adaptatif (CAT). Visible seulement aux INSCRITS
                     ACTIFS + drapeau academy.placement_test_enabled. Le composant Livewire
                     re-vérifie l'accès serveur (403/404 sinon) : ce @if n'est qu'un affichage,
                     jamais la source de vérité de l'autorisation. Jamais bloquant : l'apprenant
                     garde toujours le choix d'ignorer le test et de commencer directement. --}}
                @if($isEnrolled && config('academy.placement_test_enabled'))
                    <div class="mb-5" style="margin-top: 24px; text-align: center;">
                        <a href="{{ route('academy.placement.show', $course->slug) }}"
                           style="display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 10px 22px; background: #ffffff; color: #064E5A; border: 2px solid #064E5A; border-radius: 12px; font-weight: 600; text-decoration: none;">
                            🎯 Passer un test de positionnement (2 min)
                        </a>
                    </div>
                @endif

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
                            @php($__prereqUnmet = ($prerequisitesUnmet ?? collect()))
                            @if(auth()->check() && $__prereqUnmet->isNotEmpty())
                                {{-- C4 : prérequis non satisfaits → on REMPLACE le bouton d'inscription
                                     par la liste des cours à compléter d'abord (liens vers ces cours).
                                     Gating SERVEUR : l'inscription est de toute façon refusée par
                                     EnrollmentController, ce panneau n'est qu'informatif. --}}
                                <div class="mb-3 p-3" role="status"
                                     style="background: #FFF7ED; border: 1px solid #FED7AA; border-radius: 10px;">
                                    <p class="mb-2 fw-bold" style="color: #9A3412; font-size: 0.95rem;">
                                        🔒 Prérequis à compléter d'abord
                                    </p>
                                    <ul class="mb-0" style="padding-left: 1.1rem; font-size: 0.9rem;">
                                        @foreach($__prereqUnmet as $__prereq)
                                            <li>
                                                <a href="{{ route('academy.courses.show', $__prereq->slug) }}"
                                                   style="color: #9A3412; font-weight: 600;">
                                                    {{ $__prereq->title }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @elseif(!auth()->check())
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
                                    {{-- Bug show.blade.php#2 : href calculé serveur (ProgressService::continueUrlFor
                                         dans CourseController@show) — prochaine leçon non complétée, ou certificat/
                                         dernière leçon si le cours est complété. Jamais de lien mort « # ». --}}
                                    <x-core::button :href="$continueUrl ?? route('academy.courses.show', $course->slug)" variant="primary" :block="true">
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

                            {{-- Partage marketing : visible UNIQUEMENT sur un cours réellement
                                 publié + public (jamais sur un brouillon en prévisualisation).
                                 Pur affichage (liens sortants + copie côté client) : aucune route,
                                 aucune écriture, aucune fuite (URL publique seulement). --}}
                            {{-- N.B. : assignations Blade en ligne (forme parenthésée), jamais
                                 la forme bloc, pour ne PAS introduire de jeton de fermeture en
                                 aval : sinon la regex de bloc PHP brut de Blade engloberait par
                                 erreur les assignations en ligne situées plus haut dans la vue. --}}
                            @php($__shareUrl = filled($course->slug) ? route('academy.courses.show', $course->slug) : null)
                            @php($__isPublic = ($course->status ?? null) === 'published' && ($course->visibility ?? null) === 'public')
                            @if($__isPublic && $__shareUrl)
                                @php($__shareTitle = (string) ($course->title ?? ''))
                                @php($__fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($__shareUrl))
                                @php($__liUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($__shareUrl))
                                @php($__xUrl = 'https://twitter.com/intent/tweet?text=' . urlencode($__shareTitle) . '&url=' . urlencode($__shareUrl))
                                <hr class="my-3">
                                <section class="ac-share" aria-labelledby="ac-share-h">
                                    <h2 id="ac-share-h" class="ac-share-title">Partager ce cours</h2>
                                    <div class="ac-share-row"
                                         x-data="{ copied: false, url: @js($__shareUrl), copy() { if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(this.url).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2500); }).catch(() => {}); } } }">
                                        <a href="{{ $__fbUrl }}" target="_blank" rel="noopener noreferrer"
                                           class="ac-share-btn" aria-label="Partager ce cours sur Facebook">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                            </svg>
                                            <span>Facebook</span>
                                        </a>
                                        <a href="{{ $__liUrl }}" target="_blank" rel="noopener noreferrer"
                                           class="ac-share-btn" aria-label="Partager ce cours sur LinkedIn">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zm1.782 13.019H3.555V9h3.564v11.452z"/>
                                            </svg>
                                            <span>LinkedIn</span>
                                        </a>
                                        <a href="{{ $__xUrl }}" target="_blank" rel="noopener noreferrer"
                                           class="ac-share-btn" aria-label="Partager ce cours sur X (Twitter)">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                            </svg>
                                            <span>X</span>
                                        </a>
                                        <button type="button" class="ac-share-btn"
                                                @click="copy()"
                                                :aria-pressed="copied"
                                                aria-label="Copier le lien de ce cours">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                                                <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                                            </svg>
                                            <span x-text="copied ? '✓ Copié' : 'Copier le lien'">Copier le lien</span>
                                        </button>
                                        <span role="status" aria-live="polite" aria-atomic="true" class="visually-hidden"
                                              x-text="copied ? 'Lien copié dans le presse-papiers' : ''"></span>
                                    </div>
                                </section>
                            @endif

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
