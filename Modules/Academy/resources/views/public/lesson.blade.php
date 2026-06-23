{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@php
    $isFree      = $course->access_type === 'free';
    $canWatch    = auth()->check() && $isEnrolled;
    $canPreview  = false; // sera true si l'item a payload['preview'] = true

    // M4 - Progression de l'utilisateur
    $userProgress = null;
    $resumeLesson = null;
    $firstLesson  = null;
    $completion   = null; // Achèvement configurable : progression vers le critère du cours
    if (auth()->check() && $isEnrolled && class_exists(\Modules\Academy\Models\Progress::class)) {
        try {
            $userProgress = \Modules\Academy\Models\Progress::where('user_id', auth()->id())
                ->where('course_id', $course->id)
                ->first();
            if ($userProgress !== null && class_exists(\Modules\Academy\Services\ProgressService::class)) {
                $resumeLesson = \Modules\Academy\Services\ProgressService::resumeLesson(auth()->user(), $course);
            }
            if (class_exists(\Modules\Academy\Services\CourseCompletionService::class)) {
                $completion = (new \Modules\Academy\Services\CourseCompletionService())
                    ->progressToward(auth()->user(), $course);
            }
        } catch (\Throwable) {}
    }
    if ($userProgress === null) {
        // Première leçon pour CTA "Commencer"
        try {
            $firstLesson = $course->chapters->first()?->lessons->first();
        } catch (\Throwable) {}
    }
@endphp

@section('title', $lesson->title . ' – ' . $course->title . ' - Académie - ' . config('app.name'))
@section('meta_description', $lesson->summary ?? $course->subtitle ?? 'Leçon de formation IA')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $lesson->title,
        'breadcrumbItems' => ['Académie', $course->title, $lesson->title],
    ])
@endsection

@push('styles')
<style>
    /* ── Mise en page lecteur ── */
    .academy-lesson-layout { display: flex; gap: 0; min-height: 70vh; }

    /* Sidebar navigation */
    .academy-lesson-sidebar {
        width: 280px;
        flex-shrink: 0;
        border-right: 1px solid #E5E7EB;
        padding: 1.25rem 0;
        position: sticky;
        top: 80px;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }
    .academy-lesson-sidebar::-webkit-scrollbar { width: 4px; }
    .academy-lesson-sidebar::-webkit-scrollbar-thumb { background: #D1D5DB; border-radius: 2px; }
    .sidebar-chapter-title {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #6B7280;
        padding: 0.75rem 1.25rem 0.4rem;
    }
    .sidebar-lesson-link {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0.55rem 1.25rem;
        font-size: 0.88rem;
        color: #374151;
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: background 0.15s, border-color 0.15s;
        line-height: 1.3;
    }
    .sidebar-lesson-link:hover { background: #F9FAFB; color: var(--c-primary, #064E5A); }
    .sidebar-lesson-link.is-active {
        background: rgba(6,78,90,0.07);
        border-left-color: var(--c-primary, #064E5A);
        color: var(--c-primary, #064E5A);
        font-weight: 600;
    }
    .sidebar-lesson-icon { font-size: 0.8rem; flex-shrink: 0; }

    /* Zone contenu */
    .academy-lesson-content { flex: 1; min-width: 0; padding: 2rem 2.5rem; }

    /* Filigrane vidéo (styles supplémentaires définis inline dans le composant) */
    .academy-watermark { pointer-events: none !important; }

    /* Panneau CTA (accès refusé) */
    .academy-gated-panel {
        border: 2px dashed #D1FAE5;
        border-radius: 12px;
        background: #F0FDF4;
        padding: 2.5rem;
        text-align: center;
    }
    .academy-gated-panel .gated-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
    .academy-gated-panel .gated-title { font-family: var(--f-heading); font-weight: 700; font-size: 1.3rem; color: var(--sys-text-default, #1A1D23); margin-bottom: 0.5rem; }
    .academy-gated-panel .gated-sub { color: var(--sys-text-muted, #6B7280); margin-bottom: 1.5rem; font-size: 0.95rem; }

    /* Navigation préc/suiv */
    .academy-lesson-nav { display: flex; justify-content: space-between; gap: 1rem; margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid #E5E7EB; }
    .academy-lesson-nav a { flex: 1; padding: 0.75rem 1rem; border: 1px solid #E5E7EB; border-radius: 8px; text-decoration: none; color: #374151; font-size: 0.9rem; transition: border-color 0.15s, color 0.15s; }
    .academy-lesson-nav a:hover { border-color: var(--c-primary, #064E5A); color: var(--c-primary, #064E5A); }
    .academy-lesson-nav .nav-prev { text-align: left; }
    .academy-lesson-nav .nav-next { text-align: right; }
    .academy-lesson-nav .nav-label { display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6B7280; margin-bottom: 3px; }

    @media (max-width: 768px) {
        .academy-lesson-layout { flex-direction: column; }
        .academy-lesson-sidebar { width: 100%; position: static; max-height: none; border-right: none; border-bottom: 1px solid #E5E7EB; }
        .academy-lesson-content { padding: 1.25rem 1rem; }
    }

    /* ── V5-d : Item verrouillé par restriction d'accès (grisé, a11y) ── */
    .academy-restricted-panel {
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        background: #F9FAFB;
        padding: 14px 16px;
        opacity: 0.75;
    }

    /* ── Contenu « document » : markdown rendu SÛR (.academy-richtext) ── */
    .academy-richtext { line-height: 1.75; color: #374151; max-width: 72ch; }
    .academy-richtext > :first-child { margin-top: 0; }
    .academy-richtext h1,
    .academy-richtext h2,
    .academy-richtext h3,
    .academy-richtext h4 {
        font-family: var(--f-heading);
        color: var(--sys-text-default, #1A1D23);
        line-height: 1.3;
        margin: 1.4em 0 0.5em;
    }
    .academy-richtext h1 { font-size: 1.5rem; }
    .academy-richtext h2 { font-size: 1.25rem; }
    .academy-richtext h3 { font-size: 1.1rem; }
    .academy-richtext h4 { font-size: 1rem; }
    .academy-richtext p { margin: 0 0 1em; }
    .academy-richtext ul,
    .academy-richtext ol { margin: 0 0 1em; padding-left: 1.5rem; }
    .academy-richtext li { margin-bottom: 0.35em; }
    .academy-richtext a {
        color: var(--sys-action-primary, #064E5A);
        text-decoration: underline;
    }
    .academy-richtext a:hover { text-decoration: none; }
    .academy-richtext strong { font-weight: 700; color: var(--sys-text-default, #1A1D23); }
    .academy-richtext em { font-style: italic; }
    .academy-richtext code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 0.9em;
        background: #F3F4F6;
        padding: 0.1em 0.35em;
        border-radius: 4px;
    }
    .academy-richtext blockquote {
        margin: 0 0 1em;
        padding: 0.25rem 0 0.25rem 1rem;
        border-left: 3px solid var(--sys-action-primary, #064E5A);
        color: var(--sys-text-muted, #6B7280);
    }

    /* ── Sondage (choice) ── */
    .academy-choice { max-width: 56ch; }
    .academy-choice-question { font-weight: 600; color: var(--sys-text-default, #1A1D23); margin: 0 0 0.75rem; }
    .academy-choice-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        margin-bottom: 8px;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.95rem;
        color: #374151;
        transition: border-color 0.15s, background 0.15s;
    }
    .academy-choice-option:hover { border-color: var(--c-primary, #064E5A); background: #F9FAFB; }
    .academy-choice-result { margin-bottom: 10px; }
    .academy-choice-result-head { display: flex; justify-content: space-between; gap: 1rem; font-size: 0.88rem; color: #374151; margin-bottom: 4px; }
    .academy-choice-result-count { color: var(--sys-text-muted, #6B7280); white-space: nowrap; }
    .academy-choice-bar { height: 10px; background: #E5E7EB; border-radius: 999px; overflow: hidden; }
    .academy-choice-bar-fill { display: block; height: 100%; background: var(--c-primary, #064E5A); border-radius: 999px; transition: width 0.3s; }

    /* ── Rétroaction / questionnaire (feedback) ── */
    .academy-feedback { max-width: 60ch; }
    .academy-feedback-intro { color: var(--sys-text-muted, #6B7280); margin: 0 0 1rem; font-size: 0.95rem; }
    .academy-feedback-q { margin: 0 0 1.25rem; border: 0; padding: 0; }
    .academy-feedback-q legend { font-weight: 600; color: var(--sys-text-default, #1A1D23); font-size: 0.95rem; padding: 0; margin-bottom: 0.5rem; }
    .academy-feedback-required { color: var(--sys-action-danger, #DC2626); }
    .academy-feedback-rating { display: flex; flex-wrap: wrap; gap: 6px; }
    .academy-feedback-rating label {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px; min-height: 24px; border: 1px solid #E5E7EB; border-radius: 999px;
        cursor: pointer; font-size: 0.9rem; color: #374151;
    }
    .academy-feedback-rating label:hover { border-color: var(--c-primary, #064E5A); background: #F9FAFB; }
    .academy-feedback-opt { display: flex; align-items: center; gap: 10px; padding: 8px 12px; margin-bottom: 6px; border: 1px solid #E5E7EB; border-radius: 8px; cursor: pointer; font-size: 0.92rem; color: #374151; }
    .academy-feedback-opt:hover { border-color: var(--c-primary, #064E5A); background: #F9FAFB; }
    .academy-feedback-text { width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 8px; resize: vertical; font: inherit; }
    .academy-feedback-result { margin-bottom: 10px; }
    .academy-feedback-result-head { display: flex; justify-content: space-between; gap: 1rem; font-size: 0.86rem; color: #374151; margin-bottom: 4px; }
    .academy-feedback-bar { height: 10px; background: #E5E7EB; border-radius: 999px; overflow: hidden; }
    .academy-feedback-bar-fill { display: block; height: 100%; background: var(--c-primary, #064E5A); border-radius: 999px; }
    .academy-feedback-texts { list-style: none; padding: 0; margin: 6px 0 0; }
    .academy-feedback-texts li { padding: 8px 12px; margin-bottom: 6px; background: #F9FAFB; border-left: 3px solid var(--c-primary, #064E5A); border-radius: 4px; font-size: 0.9rem; color: #374151; }

    /* ── Forum (discussion) ── */
    .academy-forum { max-width: 64ch; }
    .academy-forum-intro { color: var(--sys-text-muted, #6B7280); margin: 0 0 1rem; font-size: 0.95rem; }
    .academy-forum-topic { border: 1px solid #E5E7EB; border-radius: 10px; margin-bottom: 12px; background: #fff; }
    .academy-forum-topic > summary { list-style: none; cursor: pointer; padding: 12px 14px; display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; }
    .academy-forum-topic > summary::-webkit-details-marker { display: none; }
    .academy-forum-topic-title { font-weight: 600; color: var(--sys-text-default, #1A1D23); }
    .academy-forum-meta { font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); white-space: nowrap; }
    .academy-forum-badge { display: inline-block; font-size: 0.7rem; font-weight: 700; padding: 1px 7px; border-radius: 999px; background: #E0F2F1; color: #064E5A; margin-left: 6px; }
    .academy-forum-body { padding: 0 14px 12px; }
    .academy-forum-post { padding: 10px 12px; margin: 8px 0; background: #F9FAFB; border-radius: 8px; }
    .academy-forum-post-meta { font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin-bottom: 4px; }
    .academy-forum-text { width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 8px; resize: vertical; font: inherit; }
    .academy-forum-field { width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 8px; font: inherit; margin-bottom: 8px; }
    .academy-forum-mod { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 8px; }
</style>
@endpush

@section('content')
<x-academy::nav />
{{-- Bandeau de prévisualisation : visible UNIQUEMENT à un gérant du cours (gate serveur
     dans LessonController@show). a11y : role=status, contraste AA, cible de retour ≥24px. --}}
@if ($isPreview ?? false)
    <div role="status"
         style="background: var(--sys-action-primary, #064E5A); color: #FFFFFF; padding: 12px 16px;">
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span style="font-weight: 600; font-size: 0.95rem;">
                👁️ Mode prévisualisation : vous voyez ce cours comme un étudiant.
            </span>
            <a href="{{ route('academy.courses.manage', $course->slug) }}"
               style="display: inline-flex; align-items: center; min-height: 24px; padding: 5px 14px; border-radius: 999px; background: #FFFFFF; color: var(--sys-action-primary, #064E5A); font-weight: 700; font-size: 0.85rem; text-decoration: none;">
                Quitter la prévisualisation
            </a>
        </div>
    </div>
@endif
<section class="section-padding">
    <div class="container-fluid" style="max-width: 1280px;">

        <div class="academy-lesson-layout">

            {{-- ══ Sidebar : navigation du cours ══ --}}
            <nav class="academy-lesson-sidebar" aria-label="Contenu du cours">
                <div class="sidebar-chapter-title" style="padding-top: 0; margin-bottom: 0.25rem;">
                    <a href="{{ route('academy.courses.show', $course) }}"
                       style="color: var(--c-primary, #064E5A); text-decoration: none; font-size: 0.8rem; font-weight: 600;">
                        ← {{ Str::limit($course->title, 30) }}
                    </a>
                </div>

                @foreach($course->chapters as $chapter)
                    <div class="sidebar-chapter-title">{{ $chapter->title }}</div>
                    @foreach($chapter->lessons as $chLesson)
                        @php
                            $isCurrentLesson = $chLesson->id === $lesson->id;
                            $firstItem = $chLesson->lessonItems->first();
                            $lessonType = $firstItem?->type ?? 'doc';
                            $icon = match($lessonType) {
                                'video'    => '▶',
                                'quiz'     => '✏️',
                                'choice'   => '📊',
                                'feedback' => '📝',
                                'forum'    => '💬',
                                'h5p'      => '🧩',
                                default    => '📄',
                            };
                        @endphp
                        @php
                            // C4 : cadenas drip dans la sidebar (calcul SERVEUR transmis par le
                            // contrôleur via $dripLockedLessonIds : id => date de disponibilité).
                            $chLessonDripDate = ($dripLockedLessonIds ?? [])[$chLesson->id] ?? null;
                        @endphp
                        <a href="{{ route('academy.lessons.show', [$course, $chLesson]) }}"
                           class="sidebar-lesson-link {{ $isCurrentLesson ? 'is-active' : '' }}"
                           @if($isCurrentLesson) aria-current="page" @endif>
                            <span class="sidebar-lesson-icon">{{ $chLessonDripDate ? '🔒' : $icon }}</span>
                            <span>{{ $chLesson->title }}</span>
                            @if($chLessonDripDate)
                                <span class="text-muted ms-auto" style="font-size: 0.72rem; white-space: nowrap;"
                                      title="Disponible le {{ $chLessonDripDate->locale('fr')->isoFormat('D MMM YYYY') }}">
                                    {{ $chLessonDripDate->locale('fr')->isoFormat('D MMM') }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>

            {{-- ══ Zone contenu principal ══ --}}
            <div class="academy-lesson-content">

                {{-- M4 - Barre de progression --}}
                @include('academy::public.partials.progress-bar', [
                    'progress'     => $userProgress,
                    'course'       => $course,
                    'resumeLesson' => $resumeLesson,
                    'firstLesson'  => $firstLesson,
                    'completion'   => $completion ?? null,
                ])

                {{-- Titre + meta --}}
                <h1 style="font-family: var(--f-heading); font-size: 1.6rem; color: var(--sys-text-default, #1A1D23); margin-bottom: 0.5rem;">
                    {{ $lesson->title }}
                </h1>

                @if($lesson->summary)
                    <p class="mb-4" style="color: var(--sys-text-muted, #6B7280); font-size: 1rem; line-height: 1.6;">{{ $lesson->summary }}</p>
                @endif

                {{-- C4 : prérequis non satisfaits → bandeau + liens, AUCUN contenu rendu
                     (le contrôleur a coupé l'accès : $isEnrolled=false → gating $hasAccess). --}}
                @if(!($isPreview ?? false) && ($prerequisitesUnmet ?? collect())->isNotEmpty())
                    <div class="mb-4 p-4" role="status"
                         style="background: #FFF7ED; border: 1px solid #FED7AA; border-radius: 12px;">
                        <p class="mb-2 fw-bold" style="color: #9A3412;">🔒 Prérequis à compléter d'abord</p>
                        <ul class="mb-0" style="padding-left: 1.1rem; font-size: 0.92rem;">
                            @foreach($prerequisitesUnmet as $__prereq)
                                <li>
                                    <a href="{{ route('academy.courses.show', $__prereq->slug) }}"
                                       style="color: #9A3412; font-weight: 600;">{{ $__prereq->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- C4 : leçon verrouillée par la libération progressive (drip). Le contenu
                     n'est PAS injecté dans le DOM (le contrôleur a mis $isEnrolled=false) ;
                     on affiche uniquement la date de disponibilité. Calcul 100 % serveur. --}}
                @if(!($isPreview ?? false) && ($isDripLocked ?? false) && ($dripAvailableAt ?? null))
                    @php
                        $__dripDaysLeft = max(0, (int) ceil(now()->floatDiffInDays($dripAvailableAt, false)));
                    @endphp
                    <div class="academy-gated-panel mb-4" role="status">
                        <div class="gated-icon">🔒</div>
                        <div class="gated-title">Cette leçon n'est pas encore disponible</div>
                        <p class="gated-sub mb-0">
                            Disponible le {{ $dripAvailableAt->locale('fr')->isoFormat('D MMMM YYYY') }}
                            @if($__dripDaysLeft > 0)
                                (dans {{ $__dripDaysLeft }} jour{{ $__dripDaysLeft > 1 ? 's' : '' }})
                            @endif.
                        </p>
                    </div>
                @endif

                {{-- C4 : si la leçon est verrouillée (prérequis non satisfaits OU drip),
                     on n'affiche PAS la liste des items (le bandeau ci-dessus explique
                     pourquoi). Le contenu reste de toute façon hors du DOM via le gating
                     $hasAccess ; ce garde-fou évite seulement des panneaux trompeurs. --}}
                @php
                    $__lessonLocked = !($isPreview ?? false)
                        && ((($prerequisitesUnmet ?? collect())->isNotEmpty()) || ($isDripLocked ?? false));
                @endphp
                {{-- Items de la leçon --}}
                @if(!$__lessonLocked)
                @forelse($lesson->lessonItems as $item)
                    @php
                        $itemPreview = (bool) ($item->payload['preview'] ?? false);
                        $hasAccess = $canWatch || $itemPreview;

                        // V5-d : restrictions d'accès par item (calcul SERVEUR dans LessonController).
                        // En prévisualisation : le gérant voit tout (aucune restriction appliquée).
                        // Rétrocompat : clé absente dans la map = accès ouvert (défaut).
                        $__restrict = (!($isPreview ?? false) && $canWatch)
                            ? ($itemRestrictions[$item->id] ?? ['allowed' => true, 'hidden' => false, 'reasons' => []])
                            : ['allowed' => true, 'hidden' => false, 'reasons' => []];
                    @endphp

                    {{-- Item complètement masqué (hide=true sur au moins une condition non remplie). --}}
                    @if($__restrict['hidden'])
                        @continue
                    @endif

                    <div class="mb-5" id="item-{{ $item->id }}">

                    {{-- Item grisé avec cadenas + raison(s) : inscrit mais restriction non remplie. --}}
                    @if(!$__restrict['allowed'])
                        <div class="academy-restricted-panel"
                             role="region"
                             aria-label="Contenu verrouillé : {{ $item->title }}">
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <span aria-hidden="true" style="font-size: 1.5rem; flex-shrink: 0;">🔒</span>
                                <div>
                                    @if($item->title)
                                        <p style="font-weight: 700; color: #374151; margin: 0 0 4px;">{{ $item->title }}</p>
                                    @endif
                                    <ul style="margin: 0; padding-left: 1.1rem; font-size: 0.9rem; color: #6B7280;">
                                        @foreach($__restrict['reasons'] as $__reason)
                                            <li>{{ $__reason }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        </div>{{-- ferme le div.mb-5 ouvert juste avant (item verrouillé) --}}
                        @php unset($__restrict, $__reason); @endphp
                        @continue
                    @endif
                    @php unset($__restrict); @endphp

                        @if($item->title)
                            <h2 class="h5 mb-3" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23);">
                                {{ $item->title }}
                            </h2>
                        @endif

                        {{-- ── TYPE VIDEO ── --}}
                        @if($item->type === 'video')
                            @php
                                // Champ canonique : player_url. Repli rétrocompat : ancien payload['embed'].
                                $videoUrl = $item->payload['player_url'] ?? ($item->payload['embed'] ?? null);
                            @endphp
                            @if($hasAccess && !empty($videoUrl))
                                {{--
                                    GATING CRITIQUE :
                                    L'URL vidéo n'est injectée dans le DOM QUE si $hasAccess === true.
                                    Côté serveur, Blade ne rend pas le composant si la condition est fausse.
                                    Aucune URL vidéo ne fuite dans le HTML rendu au visiteur non-inscrit.
                                --}}
                                <x-academy::video-player
                                    :playerUrl="$videoUrl"
                                    :poster="$item->posterUrl()"
                                    :title="$item->title ?? $lesson->title"
                                />

                                @if(isset($item->payload['duration_seconds']))
                                    <p class="text-muted mt-2" style="font-size: 0.85rem;">
                                        ⏱ Durée : {{ ceil($item->payload['duration_seconds'] / 60) }} min
                                    </p>
                                @endif

                            @else
                                {{-- Panneau d'appel à l'action (pas d'URL dans le DOM) --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour visionner
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder à cette vidéo
                                        @else
                                            Contenu en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour accéder aux leçons vidéo.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour regarder toutes les leçons.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        {{-- M5 : CTA Acheter depuis la leçon (cours payant) --}}
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE QUIZ ── --}}
                        @elseif($item->type === 'quiz')
                            @if($isPreview ?? false)
                                {{-- En prévisualisation : le quiz n'est PAS proposé (aucune progression
                                     enregistrée pour le gérant). Note discrète à la place. --}}
                                <p class="text-muted p-3 rounded" style="background: #F3F4F6; font-size: 0.9rem;">
                                    Les actions (progression, quiz) sont désactivées en prévisualisation.
                                </p>
                            @else
                                @php
                                    $qr         = session('academy.quiz_result');
                                    $quizResult = ($qr && ($qr['item_id'] ?? null) === $item->id) ? $qr : null;
                                @endphp
                                <x-academy::quiz-player
                                    :item="$item"
                                    :isEnrolled="$isEnrolled"
                                    :course="$course"
                                    :lesson="$lesson"
                                    :quizResult="$quizResult"
                                />
                            @endif

                        {{-- ── TYPE CHOICE (sondage / vote simple, non noté) ── --}}
                        @elseif($item->type === 'choice')
                            @php
                                $choiceOptions  = \Modules\Academy\Services\ChoiceService::options($item);
                                $choiceQuestion = \Modules\Academy\Services\ChoiceService::question($item);
                                $choiceMultiple = \Modules\Academy\Services\ChoiceService::allowsMultiple($item);
                                $choiceAnon     = \Modules\Academy\Services\ChoiceService::isAnonymous($item);
                                // Le formateur visualise via le mode prévisualisation : il voit
                                // toujours les résultats, mais ne vote pas (aucune progression).
                                $choiceIsManager = (bool) ($isPreview ?? false);
                                // C3 (anti N+1) : on consulte la map préchargée par le
                                // contrôleur (1 requête pour toute la leçon) au lieu de
                                // requêter par item. Repli inoffensif si la variable
                                // n'est pas fournie (ex. autre point d'entrée).
                                $choiceUserVote  = (! $choiceIsManager && auth()->check())
                                    ? \Modules\Academy\Services\ChoiceService::userVote($item, auth()->user(), $choiceVotes ?? null)
                                    : null;
                                $choiceHasVoted    = $choiceUserVote !== null;
                                $choiceShowResults = $choiceIsManager
                                    || \Modules\Academy\Services\ChoiceService::resultsVisibleToStudent($item, $choiceHasVoted);
                                $choiceTally = $choiceShowResults
                                    ? \Modules\Academy\Services\ChoiceService::tally($item)
                                    : null;
                            @endphp
                            @if($hasAccess && count($choiceOptions) >= 2)
                                <div class="academy-choice">
                                    {{-- Énoncé : e() (anti-XSS) ; l'énoncé est du texte simple. --}}
                                    @if($choiceQuestion !== '')
                                        <p class="academy-choice-question">{{ $choiceQuestion }}</p>
                                    @endif

                                    @if($choiceIsManager)
                                        {{-- Prévisualisation : pas de vote (le gérant n'enregistre rien). --}}
                                        <p class="text-muted p-3 rounded" style="background: #F3F4F6; font-size: 0.9rem;">
                                            Le vote est désactivé en prévisualisation. Les résultats ci-dessous reflètent les votes réels.
                                        </p>
                                    @else
                                        {{-- Formulaire de vote a11y (radio = choix unique, case = choix multiple).
                                             Le vote est modifiable : la sélection courante est pré-cochée. --}}
                                        <form method="POST"
                                              action="{{ route('academy.choice.vote', [$course, $lesson, $item->id]) }}"
                                              class="academy-choice-form">
                                            @csrf
                                            <fieldset style="border: 0; padding: 0; margin: 0;">
                                                <legend class="visually-hidden">{{ $choiceQuestion !== '' ? $choiceQuestion : 'Sondage' }}</legend>
                                                @foreach($choiceOptions as $ci => $optLabel)
                                                    <label class="academy-choice-option" for="choice-{{ $item->id }}-{{ $ci }}">
                                                        <input type="{{ $choiceMultiple ? 'checkbox' : 'radio' }}"
                                                               id="choice-{{ $item->id }}-{{ $ci }}"
                                                               name="{{ $choiceMultiple ? 'choices[]' : 'choice' }}"
                                                               value="{{ $ci }}"
                                                               @checked(is_array($choiceUserVote) && in_array($ci, $choiceUserVote, true))
                                                               style="width: 24px; height: 24px; flex: 0 0 auto; margin: 0;">
                                                        <span>{{ $optLabel }}</span>
                                                    </label>
                                                @endforeach
                                            </fieldset>
                                            <div class="mt-3">
                                                <x-core::button type="submit" variant="primary" size="sm">
                                                    {{ $choiceHasVoted ? 'Modifier mon vote' : 'Voter' }}
                                                </x-core::button>
                                            </div>
                                        </form>

                                        @if($choiceHasVoted)
                                            <p class="mt-2" role="status" style="font-size: 0.85rem; color: #166534;">
                                                <span aria-hidden="true">✅</span> Votre vote est enregistré. Vous pouvez le modifier à tout moment.
                                            </p>
                                        @endif
                                    @endif

                                    {{-- Résultats agrégés (selon la visibilité). On ne montre QUE des
                                         comptes/pourcentages anonymisés, jamais l'identité des votants. --}}
                                    @if($choiceShowResults && $choiceTally)
                                        <div class="academy-choice-results mt-3" role="group" aria-label="Résultats du sondage">
                                            @foreach($choiceTally['options'] as $row)
                                                <div class="academy-choice-result">
                                                    <div class="academy-choice-result-head">
                                                        <span>{{ $row['label'] }}</span>
                                                        <span class="academy-choice-result-count">{{ $row['count'] }} ({{ $row['percent'] }}%)</span>
                                                    </div>
                                                    <div class="academy-choice-bar" role="presentation">
                                                        <span class="academy-choice-bar-fill" style="width: {{ $row['percent'] }}%;"></span>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <p class="text-muted mt-1" style="font-size: 0.8rem;">
                                                {{ $choiceTally['total_voters'] }} {{ $choiceTally['total_voters'] === 1 ? 'votant' : 'votants' }}
                                            </p>
                                        </div>

                                        {{-- Liste des votants : UNIQUEMENT au formateur (prévisualisation) ET
                                             si le sondage n'est PAS anonyme. Jamais affichée à un étudiant. --}}
                                        @if($choiceIsManager && !$choiceAnon)
                                            @php
                                                $choiceVoters = \Modules\Academy\Services\ChoiceService::voters($item);
                                            @endphp
                                            @if($choiceVoters->isNotEmpty())
                                                <div class="mt-2" style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">
                                                    <strong>Votants :</strong>
                                                    {{ $choiceVoters->map(fn ($u) => $u->name ?? '(nom inconnu)')->implode(', ') }}
                                                </div>
                                            @endif
                                        @endif
                                    @elseif(!$choiceIsManager && \Modules\Academy\Services\ChoiceService::visibility($item) === 'after_vote' && !$choiceHasVoted)
                                        <p class="text-muted mt-2" style="font-size: 0.85rem;">
                                            Les résultats s'afficheront après votre vote.
                                        </p>
                                    @endif
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour participer au sondage
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour participer à ce sondage
                                        @else
                                            Sondage en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour voter.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour participer.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE FEEDBACK (questionnaire de rétroaction, multi-questions, non noté) ── --}}
                        @elseif($item->type === 'feedback')
                            @php
                                $fbQuestions = \Modules\Academy\Services\FeedbackService::questions($item);
                                $fbIntro     = \Modules\Academy\Services\FeedbackService::intro($item);
                                $fbAnon      = \Modules\Academy\Services\FeedbackService::isAnonymous($item);
                                // Le formateur visualise via la prévisualisation : il voit les
                                // résultats agrégés, ne répond pas (aucune progression).
                                $fbIsManager = (bool) ($isPreview ?? false);
                                $fbResponded = (! $fbIsManager && auth()->check())
                                    ? \Modules\Academy\Services\FeedbackService::hasResponded($item, auth()->user())
                                    : false;
                                // Pré-remplissage d'un sondage NOMMÉ (réponse modifiable) : les
                                // réponses précédentes de l'utilisateur courant, PRÉCHARGÉES par le
                                // contrôleur (C2 : aucune requête dans la vue). Jamais en anonyme
                                // (aucune réponse n'est liée à une identité ; previousAnswers le borne).
                                $fbPrev = (! $fbIsManager && auth()->check())
                                    ? \Modules\Academy\Services\FeedbackService::previousAnswers($item, auth()->user(), $feedbackResponses ?? null)
                                    : [];
                                // Résultats UNIQUEMENT pour le formateur (jamais l'étudiant).
                                $fbResults = $fbIsManager ? \Modules\Academy\Services\FeedbackService::results($item) : null;
                            @endphp
                            @if($hasAccess && count($fbQuestions) >= 1)
                                <div class="academy-feedback">
                                    @if($fbIntro !== '')
                                        <p class="academy-feedback-intro">{{ $fbIntro }}</p>
                                    @endif

                                    @if($fbIsManager)
                                        {{-- Prévisualisation formateur : aucune réponse, résultats AGRÉGÉS
                                             et anonymisés (jamais d'identité, même si non anonyme). --}}
                                        <p class="text-muted p-3 rounded" style="background: #F3F4F6; font-size: 0.9rem;">
                                            La réponse est désactivée en prévisualisation. Les résultats ci-dessous (agrégés et anonymisés) reflètent les réponses réelles.
                                        </p>
                                        <div class="academy-feedback-results" role="group" aria-label="Résultats du sondage">
                                            @foreach($fbResults['questions'] as $qr)
                                                <div style="margin-bottom: 1rem;">
                                                    <p style="font-weight: 600; margin: 0 0 0.4rem;">{{ $qr['label'] }}</p>
                                                    @if($qr['type'] === 'rating')
                                                        @for($s = 1; $s <= $qr['scale']; $s++)
                                                            @php
                                                                $cnt = $qr['counts'][$s] ?? 0;
                                                                $pct = ($qr['answered'] ?? 0) > 0 ? (int) round($cnt / $qr['answered'] * 100) : 0;
                                                            @endphp
                                                            <div class="academy-feedback-result">
                                                                <div class="academy-feedback-result-head"><span>{{ $s }}</span><span>{{ $cnt }} ({{ $pct }}%)</span></div>
                                                                <div class="academy-feedback-bar" role="presentation"><span class="academy-feedback-bar-fill" style="width: {{ $pct }}%;"></span></div>
                                                            </div>
                                                        @endfor
                                                        @if(! is_null($qr['average']))
                                                            <p class="text-muted" style="font-size: 0.8rem;">Moyenne : {{ $qr['average'] }} / {{ $qr['scale'] }}</p>
                                                        @endif
                                                    @elseif($qr['type'] === 'choice')
                                                        @foreach($qr['options'] as $oi => $ol)
                                                            @php
                                                                $cnt = $qr['counts'][$oi] ?? 0;
                                                                $pct = ($qr['answered'] ?? 0) > 0 ? (int) round($cnt / $qr['answered'] * 100) : 0;
                                                            @endphp
                                                            <div class="academy-feedback-result">
                                                                <div class="academy-feedback-result-head"><span>{{ $ol }}</span><span>{{ $cnt }} ({{ $pct }}%)</span></div>
                                                                <div class="academy-feedback-bar" role="presentation"><span class="academy-feedback-bar-fill" style="width: {{ $pct }}%;"></span></div>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        @if(count($qr['texts']) > 0)
                                                            <ul class="academy-feedback-texts">
                                                                @foreach($qr['texts'] as $t)
                                                                    <li>{{ $t }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <p class="text-muted" style="font-size: 0.85rem;">Aucune réponse pour l'instant.</p>
                                                        @endif
                                                    @endif
                                                </div>
                                            @endforeach
                                            <p class="text-muted" style="font-size: 0.8rem;">
                                                {{ $fbResults['total'] }} {{ $fbResults['total'] === 1 ? 'réponse' : 'réponses' }}
                                            </p>
                                        </div>
                                    @elseif($fbAnon && $fbResponded)
                                        {{-- Sondage anonyme déjà rempli (borne de session) : remerciement seul,
                                             jamais les résultats (le retour va au formateur). --}}
                                        <p role="status" style="font-size: 0.9rem; color: #166534;">
                                            <span aria-hidden="true">✅</span> Merci, votre réponse anonyme a été enregistrée.
                                        </p>
                                    @else
                                        {{-- Formulaire de réponse a11y (fieldset/legend par question). L'étudiant
                                             ne voit JAMAIS les résultats : un sondage est un retour au formateur. --}}
                                        <form method="POST"
                                              action="{{ route('academy.feedback.submit', [$course, $lesson, $item->id]) }}"
                                              class="academy-feedback-form">
                                            @csrf
                                            @foreach($fbQuestions as $qi => $q)
                                                <fieldset class="academy-feedback-q">
                                                    <legend>{{ $q['label'] }}@if($q['required']) <span class="academy-feedback-required" aria-hidden="true">*</span><span class="visually-hidden"> (obligatoire)</span>@endif</legend>
                                                    @if($q['type'] === 'rating')
                                                        <div class="academy-feedback-rating" role="radiogroup" aria-label="{{ $q['label'] }}">
                                                            @for($s = 1; $s <= $q['scale']; $s++)
                                                                <label for="fb-{{ $item->id }}-{{ $qi }}-{{ $s }}">
                                                                    <input type="radio" id="fb-{{ $item->id }}-{{ $qi }}-{{ $s }}" name="answers[{{ $qi }}]" value="{{ $s }}"
                                                                           @checked((string) ($fbPrev[$qi] ?? '') === (string) $s) @required($q['required'])
                                                                           style="width: 20px; height: 20px; margin: 0;">
                                                                    <span>{{ $s }}</span>
                                                                </label>
                                                            @endfor
                                                        </div>
                                                    @elseif($q['type'] === 'choice')
                                                        @foreach($q['options'] as $oi => $ol)
                                                            <label class="academy-feedback-opt" for="fb-{{ $item->id }}-{{ $qi }}-{{ $oi }}">
                                                                <input type="radio" id="fb-{{ $item->id }}-{{ $qi }}-{{ $oi }}" name="answers[{{ $qi }}]" value="{{ $oi }}"
                                                                       @checked((string) ($fbPrev[$qi] ?? '') === (string) $oi) @required($q['required'])
                                                                       style="width: 24px; height: 24px; flex: 0 0 auto; margin: 0;">
                                                                <span>{{ $ol }}</span>
                                                            </label>
                                                        @endforeach
                                                    @else
                                                        <textarea class="academy-feedback-text" name="answers[{{ $qi }}]" rows="3"
                                                                  maxlength="{{ \Modules\Academy\Services\FeedbackService::MAX_TEXT }}"
                                                                  aria-label="{{ $q['label'] }}" @required($q['required'])>{{ is_string($fbPrev[$qi] ?? null) ? $fbPrev[$qi] : '' }}</textarea>
                                                    @endif
                                                </fieldset>
                                            @endforeach
                                            <div class="mt-2">
                                                <x-core::button type="submit" variant="primary" size="sm">
                                                    {{ (! $fbAnon && $fbResponded) ? 'Modifier ma réponse' : 'Envoyer ma réponse' }}
                                                </x-core::button>
                                            </div>
                                        </form>

                                        @if(! $fbAnon && $fbResponded)
                                            <p class="mt-2" role="status" style="font-size: 0.85rem; color: #166534;">
                                                <span aria-hidden="true">✅</span> Votre réponse est enregistrée. Vous pouvez la modifier à tout moment.
                                            </p>
                                        @endif
                                        @if($fbAnon)
                                            <p class="text-muted mt-2" style="font-size: 0.8rem;">
                                                <span aria-hidden="true">🔒</span> Ce sondage est anonyme : votre réponse n'est associée à aucune identité.
                                            </p>
                                        @endif
                                    @endif
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour répondre au sondage
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour répondre à ce sondage
                                        @else
                                            Sondage en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour répondre.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour répondre.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE FORUM (discussions attachées à la leçon, type Moodle « Forum ») ── --}}
                        @elseif($item->type === 'forum')
                            @php
                                $forumIntro       = \Modules\Academy\Services\ForumService::intro($item);
                                $forumLocked      = \Modules\Academy\Services\ForumService::isLocked($item);
                                $forumAllowTopics = \Modules\Academy\Services\ForumService::allowsStudentTopics($item);
                                // Gérant de CE cours (admin OU owner/instructor) : peut modérer ET
                                // contribuer même hors inscription. L'autorisation réelle est TOUJOURS
                                // re-vérifiée côté serveur (ForumController) ; ici c'est de l'affichage.
                                $forumCanModerate = auth()->check() && auth()->user()->can('manageEnrollments', $course);
                                // Peut ouvrir un sujet : un gérant toujours ; un étudiant si l'accès
                                // est accordé, que les sujets étudiants sont permis et le forum non verrouillé.
                                $forumCanCreate = $forumCanModerate
                                    || ($hasAccess && auth()->check() && $forumAllowTopics && ! $forumLocked);
                                $forumTopics = ($hasAccess || $forumCanModerate)
                                    ? \Modules\Academy\Services\ForumService::topics($item)
                                    : null;
                            @endphp
                            @if($hasAccess || $forumCanModerate)
                                <div class="academy-forum">
                                    @if($forumIntro !== '')
                                        <p class="academy-forum-intro">{{ $forumIntro }}</p>
                                    @endif

                                    @if($forumLocked)
                                        <p class="text-muted p-2 rounded" style="background: #F3F4F6; font-size: 0.85rem;">
                                            <span aria-hidden="true">🔒</span> Ce forum est en lecture seule.
                                            @if($forumCanModerate) (Vous pouvez tout de même contribuer en tant que gérant.) @endif
                                        </p>
                                    @endif

                                    {{-- Nouveau sujet (a11y : champs étiquetés ; honeypot caché anti-spam). --}}
                                    @if($forumCanCreate)
                                        <details class="academy-forum-topic" style="border-style: dashed;">
                                            <summary>
                                                <span class="academy-forum-topic-title">+ Ouvrir un nouveau sujet</span>
                                            </summary>
                                            <div class="academy-forum-body">
                                                <form method="POST" action="{{ route('academy.forum.topics.create', [$course, $lesson, $item->id]) }}">
                                                    @csrf
                                                    {{-- Honeypot MAISON : doit rester vide ; hors écran, non focusable. --}}
                                                    <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                        <label for="forum-hp-{{ $item->id }}">Ne pas remplir</label>
                                                        <input type="text" id="forum-hp-{{ $item->id }}" name="{{ \Modules\Academy\Services\ForumService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                    </div>
                                                    <label for="forum-title-{{ $item->id }}" style="font-size: 0.82rem; font-weight: 600;">Titre du sujet</label>
                                                    <input type="text" id="forum-title-{{ $item->id }}" name="title" class="academy-forum-field"
                                                           maxlength="{{ \Modules\Academy\Services\ForumService::TITLE_MAX }}" required>
                                                    <label for="forum-body-{{ $item->id }}" style="font-size: 0.82rem; font-weight: 600;">Message</label>
                                                    <textarea id="forum-body-{{ $item->id }}" name="body" class="academy-forum-text" rows="3"
                                                              maxlength="{{ \Modules\Academy\Services\ForumService::BODY_MAX }}" required></textarea>
                                                    <div class="mt-2">
                                                        <x-core::button type="submit" variant="primary" size="sm">Publier le sujet</x-core::button>
                                                    </div>
                                                </form>
                                            </div>
                                        </details>
                                    @elseif(! $forumAllowTopics && ! $forumLocked)
                                        <p class="text-muted" style="font-size: 0.85rem;">Seul le formateur peut ouvrir de nouveaux sujets ; vous pouvez répondre aux sujets existants.</p>
                                    @endif

                                    {{-- Liste des sujets : épinglés en tête, puis récents (pagination simple). --}}
                                    @forelse($forumTopics as $topic)
                                        @php
                                            $topicReplyAllowed = $forumCanModerate || (! $forumLocked && ! $topic->is_locked && $hasAccess && auth()->check());
                                        @endphp
                                        <details class="academy-forum-topic" wire:key="forum-topic-{{ $topic->id }}" @if(request('forum_open') == $topic->id) open @endif>
                                            <summary>
                                                <span class="academy-forum-topic-title">
                                                    @if($topic->is_pinned)<span aria-hidden="true" title="Épinglé">📌</span><span class="visually-hidden">Épinglé</span> @endif
                                                    @if($topic->is_locked)<span aria-hidden="true" title="Verrouillé">🔒</span><span class="visually-hidden">Verrouillé</span> @endif
                                                    {{ $topic->title }}
                                                    <span class="academy-forum-badge">{{ $topic->posts_count }} {{ $topic->posts_count === 1 ? 'réponse' : 'réponses' }}</span>
                                                </span>
                                                <span class="academy-forum-meta">{{ $topic->user?->name ?? '(inconnu)' }} · {{ $topic->created_at?->diffForHumans() }}</span>
                                            </summary>
                                            <div class="academy-forum-body">
                                                {{-- SÉCURITÉ : renderRichText() (markdown, html_input=strip) neutralise tout HTML brut → anti-XSS. --}}
                                                <div class="prose academy-richtext">{!! \Modules\Academy\Models\LessonItem::renderRichText($topic->body) !!}</div>

                                                @foreach($topic->posts as $post)
                                                    <div class="academy-forum-post" wire:key="forum-post-{{ $post->id }}">
                                                        <div class="academy-forum-post-meta">{{ $post->user?->name ?? '(inconnu)' }} · {{ $post->created_at?->diffForHumans() }}</div>
                                                        <div class="prose academy-richtext">{!! \Modules\Academy\Models\LessonItem::renderRichText($post->body) !!}</div>
                                                        @if($forumCanModerate)
                                                            {{-- Modération : confirmation INLINE 2 temps (details), jamais de confirm() natif. --}}
                                                            <details class="mt-1">
                                                                <summary style="cursor: pointer; font-size: 0.78rem; color: var(--sys-action-danger, #DC2626);">Supprimer cette réponse</summary>
                                                                <form method="POST" action="{{ route('academy.forum.posts.delete', [$course, $lesson, $item->id, $post->id]) }}" class="mt-1">
                                                                    @csrf
                                                                    <x-core::button type="submit" variant="ghost" size="sm">Confirmer la suppression</x-core::button>
                                                                </form>
                                                            </details>
                                                        @endif
                                                    </div>
                                                @endforeach

                                                {{-- Liste bornée (ForumService::POSTS_PER_TOPIC) : si le sujet a plus de
                                                     réponses que celles chargées, on l'indique sans charger le fil entier. --}}
                                                @if($topic->posts_count > $topic->posts->count())
                                                    <p class="text-muted mt-1" style="font-size: 0.8rem;">
                                                        {{ $topic->posts->count() }} des {{ $topic->posts_count }} réponses affichées (les plus anciennes en premier).
                                                    </p>
                                                @endif

                                                {{-- Répondre --}}
                                                @if($topicReplyAllowed)
                                                    <form method="POST" action="{{ route('academy.forum.topics.reply', [$course, $lesson, $item->id, $topic->id]) }}" class="mt-2">
                                                        @csrf
                                                        <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                            <label for="forum-rhp-{{ $topic->id }}">Ne pas remplir</label>
                                                            <input type="text" id="forum-rhp-{{ $topic->id }}" name="{{ \Modules\Academy\Services\ForumService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                        </div>
                                                        <label for="forum-reply-{{ $topic->id }}" style="font-size: 0.82rem; font-weight: 600;">Répondre</label>
                                                        <textarea id="forum-reply-{{ $topic->id }}" name="body" class="academy-forum-text" rows="2"
                                                                  maxlength="{{ \Modules\Academy\Services\ForumService::BODY_MAX }}" required></textarea>
                                                        <div class="mt-2">
                                                            <x-core::button type="submit" variant="secondary" size="sm">Répondre</x-core::button>
                                                        </div>
                                                    </form>
                                                @elseif($topic->is_locked)
                                                    <p class="text-muted mt-2" style="font-size: 0.82rem;"><span aria-hidden="true">🔒</span> Ce sujet est verrouillé.</p>
                                                @endif

                                                {{-- Modération du sujet (gérant) : épingler / verrouiller (bascule) + supprimer (2 temps). --}}
                                                @if($forumCanModerate)
                                                    <div class="academy-forum-mod">
                                                        <form method="POST" action="{{ route('academy.forum.topics.pin', [$course, $lesson, $item->id, $topic->id]) }}">
                                                            @csrf
                                                            <x-core::button type="submit" variant="ghost" size="sm">{{ $topic->is_pinned ? 'Désépingler' : 'Épingler' }}</x-core::button>
                                                        </form>
                                                        <form method="POST" action="{{ route('academy.forum.topics.lock', [$course, $lesson, $item->id, $topic->id]) }}">
                                                            @csrf
                                                            <x-core::button type="submit" variant="ghost" size="sm">{{ $topic->is_locked ? 'Déverrouiller' : 'Verrouiller' }}</x-core::button>
                                                        </form>
                                                        <details>
                                                            <summary style="cursor: pointer; font-size: 0.82rem; color: var(--sys-action-danger, #DC2626);">Supprimer le sujet</summary>
                                                            <form method="POST" action="{{ route('academy.forum.topics.delete', [$course, $lesson, $item->id, $topic->id]) }}" class="mt-1">
                                                                @csrf
                                                                <x-core::button type="submit" variant="ghost" size="sm">Confirmer la suppression du sujet</x-core::button>
                                                            </form>
                                                        </details>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @empty
                                        <p class="text-muted" style="font-size: 0.9rem;">Aucun sujet pour l'instant. @if($forumCanCreate) Soyez le premier à en ouvrir un. @endif</p>
                                    @endforelse

                                    @if($forumTopics && $forumTopics->hasPages())
                                        <div class="mt-3">{{ $forumTopics->withQueryString()->links() }}</div>
                                    @endif
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour accéder au forum
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour participer au forum
                                        @else
                                            Forum en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour participer aux discussions.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour participer.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE DOC ── --}}
                        @elseif(in_array($item->type, ['doc', 'document'], true))
                            @if($hasAccess)
                                {{--
                                    GATING DOC (identique au gating vidéo) :
                                    Le contenu textuel n'est injecté dans le DOM QUE si $hasAccess === true.
                                    Un visiteur non inscrit ne voit PAS le rich_text dans le HTML rendu.
                                --}}
                                <div class="prose academy-richtext">
                                    @php
                                        $renderedDoc = \Modules\Academy\Models\LessonItem::renderRichText($item->payload['rich_text'] ?? null);
                                    @endphp
                                    @if($renderedDoc !== '')
                                        {{-- SÉCURITÉ : renderRichText() interprète le markdown avec html_input=strip
                                             (tout HTML brut est retiré) → liste blanche, aucune XSS stockée possible. --}}
                                        {!! $renderedDoc !!}
                                    @else
                                        <p class="text-muted">Contenu du document à venir.</p>
                                    @endif

                                    @if(!empty($item->payload['attachments']))
                                        <div class="mt-3">
                                            <strong style="font-size: 0.9rem;">Pièces jointes :</strong>
                                            <ul class="mt-1">
                                                @foreach($item->payload['attachments'] as $attachment)
                                                    <li><a href="{{ $attachment['url'] ?? '#' }}" target="_blank" rel="noopener">{{ $attachment['name'] ?? 'Télécharger' }}</a></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @else
                                {{-- Panneau d'accès refusé - même logique que le type video (pas de contenu dans le DOM) --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour lire ce document
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder à ce document
                                        @else
                                            Contenu en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour accéder aux documents de ce cours.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour lire tous les documents.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE H5P (contenu interactif, parité Moodle « H5P ») ── --}}
                        @elseif($item->type === 'h5p')
                            @php $h5pPath = $item->payload['h5p_path'] ?? null; @endphp
                            @if($hasAccess && !empty($h5pPath))
                                {{--
                                    ISOLATION (le contenu H5P exécute du JS tiers) :
                                    le contenu est rendu DANS UN IFRAME SANDBOX pointant vers une page
                                    dédiée (H5pPlayerController) qui charge le player h5p-standalone.
                                    sandbox="allow-scripts allow-same-origin" = le MINIMUM nécessaire
                                    (scripts du player + fetch same-origin du content.json). On NE donne
                                    PAS allow-forms / allow-popups / allow-top-navigation / allow-modals :
                                    le contenu tiers ne peut donc PAS naviguer la page hôte, ouvrir de
                                    popups ni poster vers la session. L'URL du dossier extrait n'est
                                    jamais injectée ici : elle vit dans la page player, elle-même gatée.

                                    RISQUE CONNU (dette v2) : « allow-same-origin » + « allow-scripts »
                                    laisse le JS H5P s'exécuter dans NOTRE origine ; il peut donc lire le
                                    DOM parent (p. ex. le jeton CSRF). On l'ACCEPTE car le téléversement
                                    d'un paquet .h5p est restreint aux ADMINS de confiance (permission
                                    « academy.manage », cf. CourseEditor::canUploadH5p) + audit manuel.
                                    Fix définitif : servir le contenu sur un SOUS-DOMAINE distinct (origine
                                    isolée) pour que le sandbox same-origin ne soit plus la nôtre.
                                --}}
                                <div class="academy-h5p-wrapper">
                                    <iframe
                                        src="{{ route('academy.h5p.play', [$course, $lesson, $item->id]) }}"
                                        title="{{ $item->title ?? $lesson->title }}"
                                        sandbox="allow-scripts allow-same-origin"
                                        loading="lazy"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        style="width: 100%; min-height: 480px; border: 1px solid #E5E7EB; border-radius: 8px; background: #fff;"
                                    ></iframe>
                                </div>
                            @else
                                {{-- Accès refusé : aucune URL de contenu dans le DOM (même logique que la vidéo). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour ce contenu interactif
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder à ce contenu interactif
                                        @else
                                            Contenu interactif en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour accéder aux activités H5P.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour accéder au contenu interactif.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        @else
                            {{-- Type inconnu : rendu défensif --}}
                            <div class="text-muted p-3 rounded" style="background: #F3F4F6; font-size: 0.9rem;">
                                <em>Type de contenu « {{ $item->type }} » non reconnu.</em>
                            </div>
                        @endif

                        {{-- M4 / V2-c - État d'achèvement par item (inscrit, hors prévisualisation).
                             Le critère (manual / view / min_grade) détermine l'affichage :
                               • manual  → bouton « Marquer comme terminé » (vidéo/document) ;
                               • view    → achèvement automatique à la consultation (déjà posé
                                           par le LessonController) → état seul, pas de bouton ;
                               • min_grade → achèvement en réussissant le quiz → état seul.
                             Masqué en prévisualisation : un gérant n'enregistre pas sa progression. --}}
                        @if($isEnrolled && !($isPreview ?? false))
                            @php
                                $isItemCompleted = false;
                                try {
                                    $isItemCompleted = \Modules\Academy\Models\Completion::where('user_id', auth()->id())
                                        ->where('lesson_item_id', $item->id)
                                        ->where('status', 'completed')
                                        ->exists();
                                } catch (\Throwable) {}
                                $itemCriterion = class_exists(\Modules\Academy\Services\ActivityCompletionService::class)
                                    ? \Modules\Academy\Services\ActivityCompletionService::criterionFor($item)
                                    : 'manual';
                                $itemModeLabel = class_exists(\Modules\Academy\Services\ActivityCompletionService::class)
                                    ? \Modules\Academy\Services\ActivityCompletionService::modeLabel($itemCriterion)
                                    : 'à marquer comme terminé';
                            @endphp
                            @if($isItemCompleted)
                                <p class="mt-3" style="font-size: 0.9rem; color: #166534;" role="status">
                                    ✅ Terminé
                                </p>
                            @elseif($itemCriterion === 'manual' && in_array($item->type, ['video', 'doc', 'document', 'choice', 'forum', 'h5p']))
                                <form method="POST"
                                      action="{{ route('academy.lessons.complete', [$course, $lesson, $item->id]) }}"
                                      class="mt-3">
                                    @csrf
                                    <x-core::button type="submit" variant="secondary" size="sm" icon="✓">
                                        Marquer comme terminé
                                    </x-core::button>
                                </form>
                            @else
                                <p class="mt-3" style="font-size: 0.9rem; color: var(--sys-text-muted, #6B7280);" role="status">
                                    ◯ À faire <span style="font-size: 0.82rem;">({{ $itemModeLabel }})</span>
                                </p>
                            @endif
                        @endif

                        {{-- Note discrète : en prévisualisation, les actions de progression sont désactivées
                             (video/doc). Le quiz affiche déjà sa propre note plus haut. --}}
                        @if(($isPreview ?? false) && in_array($item->type, ['video', 'doc', 'document']))
                            <p class="text-muted mt-3" style="font-size: 0.85rem;">
                                Les actions (progression, quiz) sont désactivées en prévisualisation.
                            </p>
                        @endif

                    </div>
                @empty
                    <p class="text-muted">Cette leçon ne contient pas encore de contenu.</p>
                @endforelse
                @endif {{-- /!$__lessonLocked --}}

                {{-- M6 - Certificat : affiché quand le cours est complété selon son critère configuré
                     (CourseCompletionService - variable $courseCompleted du contrôleur), jamais en prévisualisation --}}
                @if(auth()->check() && $isEnrolled && !($isPreview ?? false) && ($courseCompleted ?? false))
                    @php
                        $__certificate = null;
                        try {
                            if (class_exists(\Modules\Academy\Services\CertificateService::class)) {
                                $__certSvc   = new \Modules\Academy\Services\CertificateService();
                                $__certificate = $__certSvc->issueFor(auth()->user(), $course);
                            }
                        } catch (\Throwable) {}
                    @endphp
                    @if($__certificate)
                        <div class="mt-4 mb-2 p-4 text-center"
                             style="background: #ECFDF5; border: 1px solid #6EE7B7; border-radius: 12px;">
                            <div style="font-size: 1.5rem; margin-bottom: 0.4rem;">🎓</div>
                            <p class="mb-3 fw-bold" style="color: #065F46;">
                                Félicitations ! Tu as complété ce cours.
                            </p>
                            <x-core::button :href="route('academy.certificates.show', $__certificate->public_url_slug)" variant="primary">
                                Obtenir mon certificat
                            </x-core::button>
                        </div>
                    @endif
                @endif

                {{-- ══ Navigation préc/suiv ══ --}}
                <div class="academy-lesson-nav">
                    <div>
                        @if($prevLesson)
                            <a href="{{ route('academy.lessons.show', [$course, $prevLesson]) }}" class="nav-prev">
                                <span class="nav-label">← Leçon précédente</span>
                                {{ Str::limit($prevLesson->title, 50) }}
                            </a>
                        @else
                            <a href="{{ route('academy.courses.show', $course) }}" class="nav-prev">
                                <span class="nav-label">← Retour au cours</span>
                                {{ Str::limit($course->title, 50) }}
                            </a>
                        @endif
                    </div>
                    <div>
                        @if($nextLesson)
                            <a href="{{ route('academy.lessons.show', [$course, $nextLesson]) }}" class="nav-next">
                                <span class="nav-label">Leçon suivante →</span>
                                {{ Str::limit($nextLesson->title, 50) }}
                            </a>
                        @endif
                    </div>
                </div>

            </div>{{-- /academy-lesson-content --}}
        </div>{{-- /academy-lesson-layout --}}
    </div>
</section>
@endsection
