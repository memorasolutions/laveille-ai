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
    if (auth()->check() && $isEnrolled && class_exists(\Modules\Academy\Models\Progress::class)) {
        try {
            $userProgress = \Modules\Academy\Models\Progress::where('user_id', auth()->id())
                ->where('course_id', $course->id)
                ->first();
            if ($userProgress !== null && class_exists(\Modules\Academy\Services\ProgressService::class)) {
                $resumeLesson = \Modules\Academy\Services\ProgressService::resumeLesson(auth()->user(), $course);
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
                👁️ Mode prévisualisation — vous voyez ce cours comme un étudiant.
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
                                'video' => '▶',
                                'quiz'  => '✏️',
                                default => '📄',
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
                    @endphp

                    <div class="mb-5" id="item-{{ $item->id }}">

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
                            @elseif($itemCriterion === 'manual' && in_array($item->type, ['video', 'doc', 'document']))
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

                {{-- M6 - Certificat : affiché quand 100% complété (jamais en prévisualisation) --}}
                @if(auth()->check() && $isEnrolled && !($isPreview ?? false) && ($userProgress?->percent ?? 0) >= 100)
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
                                Félicitations ! Tu as complété ce cours à 100 %.
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
