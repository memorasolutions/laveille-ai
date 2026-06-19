{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@php
    $isFree      = $course->access_type === 'free';
    $canWatch    = auth()->check() && $isEnrolled;
    $canPreview  = false; // sera true si l'item a payload['preview'] = true

    // M4 — Progression de l'utilisateur
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
    .academy-gated-panel .gated-title { font-family: var(--f-heading); font-weight: 700; font-size: 1.3rem; color: var(--c-dark, #1A1D23); margin-bottom: 0.5rem; }
    .academy-gated-panel .gated-sub { color: #6B7280; margin-bottom: 1.5rem; font-size: 0.95rem; }

    /* Navigation préc/suiv */
    .academy-lesson-nav { display: flex; justify-content: space-between; gap: 1rem; margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid #E5E7EB; }
    .academy-lesson-nav a { flex: 1; padding: 0.75rem 1rem; border: 1px solid #E5E7EB; border-radius: 8px; text-decoration: none; color: #374151; font-size: 0.9rem; transition: border-color 0.15s, color 0.15s; }
    .academy-lesson-nav a:hover { border-color: var(--c-primary, #064E5A); color: var(--c-primary, #064E5A); }
    .academy-lesson-nav .nav-prev { text-align: left; }
    .academy-lesson-nav .nav-next { text-align: right; }
    .academy-lesson-nav .nav-label { display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #9CA3AF; margin-bottom: 3px; }

    @media (max-width: 768px) {
        .academy-lesson-layout { flex-direction: column; }
        .academy-lesson-sidebar { width: 100%; position: static; max-height: none; border-right: none; border-bottom: 1px solid #E5E7EB; }
        .academy-lesson-content { padding: 1.25rem 1rem; }
    }
</style>
@endpush

@section('content')
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
                        <a href="{{ route('academy.lessons.show', [$course, $chLesson]) }}"
                           class="sidebar-lesson-link {{ $isCurrentLesson ? 'is-active' : '' }}"
                           @if($isCurrentLesson) aria-current="page" @endif>
                            <span class="sidebar-lesson-icon">{{ $icon }}</span>
                            <span>{{ $chLesson->title }}</span>
                        </a>
                    @endforeach
                @endforeach
            </nav>

            {{-- ══ Zone contenu principal ══ --}}
            <div class="academy-lesson-content">

                {{-- M4 — Barre de progression --}}
                @include('academy::public.partials.progress-bar', [
                    'progress'     => $userProgress,
                    'course'       => $course,
                    'resumeLesson' => $resumeLesson,
                    'firstLesson'  => $firstLesson,
                ])

                {{-- Titre + meta --}}
                <h1 style="font-family: var(--f-heading); font-weight: 800; font-size: 1.6rem; color: var(--c-dark, #1A1D23); margin-bottom: 0.5rem;">
                    {{ $lesson->title }}
                </h1>

                @if($lesson->summary)
                    <p class="text-muted mb-4" style="font-size: 1rem; line-height: 1.6;">{{ $lesson->summary }}</p>
                @endif

                {{-- Items de la leçon --}}
                @forelse($lesson->lessonItems as $item)
                    @php
                        $isPreview = (bool) ($item->payload['preview'] ?? false);
                        $hasAccess = $canWatch || $isPreview;
                    @endphp

                    <div class="mb-5" id="item-{{ $item->id }}">

                        @if($item->title)
                            <h2 class="h5 mb-3" style="font-family: var(--f-heading); color: #1A1D23;">
                                {{ $item->title }}
                            </h2>
                        @endif

                        {{-- ── TYPE VIDEO ── --}}
                        @if($item->type === 'video')
                            @if($hasAccess && isset($item->payload['player_url']))
                                {{--
                                    GATING CRITIQUE :
                                    player_url n'est injectée dans le DOM QUE si $hasAccess === true.
                                    Côté serveur, Blade ne rend pas le composant si la condition est fausse.
                                    Aucune URL vidéo ne fuite dans le HTML rendu au visiteur non-inscrit.
                                --}}
                                <x-academy::video-player
                                    :playerUrl="$item->payload['player_url']"
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
                                            Ce cours est gratuit — inscrivez-vous pour regarder toutes les leçons.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant — achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <a href="{{ Route::has('login') ? route('login') : '#' }}"
                                           class="btn ct-btn ct-btn-primary me-2">
                                            Se connecter
                                        </a>
                                        <a href="{{ Route::has('register') ? route('register') : '#' }}"
                                           class="btn btn-outline-secondary">
                                            Créer un compte
                                        </a>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn ct-btn ct-btn-primary">
                                                S'inscrire gratuitement
                                            </button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        {{-- M5 : CTA Acheter depuis la leçon (cours payant) --}}
                                        <a href="{{ route('academy.courses.purchase', $course) }}"
                                           class="btn ct-btn ct-btn-primary">
                                            Acheter ce cours
                                        </a>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE QUIZ ── --}}
                        @elseif($item->type === 'quiz')
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

                        {{-- ── TYPE DOC ── --}}
                        @elseif($item->type === 'doc')
                            <div class="prose" style="line-height: 1.75; color: #374151; max-width: 72ch;">
                                @if(isset($item->payload['rich_text']))
                                    {!! nl2br(e($item->payload['rich_text'])) !!}
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
                            {{-- Type inconnu : rendu défensif --}}
                            <div class="text-muted p-3 rounded" style="background: #F3F4F6; font-size: 0.9rem;">
                                <em>Type de contenu « {{ $item->type }} » non reconnu.</em>
                            </div>
                        @endif

                        {{-- M4 — Bouton « Marquer comme terminé » (video + doc uniquement, inscrit) --}}
                        @if($isEnrolled && in_array($item->type, ['video', 'doc']))
                            @php
                                $isItemCompleted = false;
                                try {
                                    $isItemCompleted = \Modules\Academy\Models\Completion::where('user_id', auth()->id())
                                        ->where('lesson_item_id', $item->id)
                                        ->where('status', 'completed')
                                        ->exists();
                                } catch (\Throwable) {}
                            @endphp
                            @if($isItemCompleted)
                                <p class="mt-3" style="font-size: 0.9rem; color: #166534;">
                                    ✅ Terminé
                                </p>
                            @else
                                <form method="POST"
                                      action="{{ route('academy.lessons.complete', [$course, $lesson, $item->id]) }}"
                                      class="mt-3">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm"
                                            style="border: 1px solid var(--c-primary, #064E5A); color: var(--c-primary, #064E5A); background: #fff; font-size: 0.85rem;">
                                        ✓ Marquer comme terminé
                                    </button>
                                </form>
                            @endif
                        @endif

                    </div>
                @empty
                    <p class="text-muted">Cette leçon ne contient pas encore de contenu.</p>
                @endforelse

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
