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
    .academy-wiki { max-width: 72ch; }
    .academy-wiki-intro { color: var(--sys-text-muted, #6B7280); margin: 0 0 1rem; font-size: 0.95rem; }
    .academy-wiki-layout { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start; }
    .academy-wiki-nav { flex: 0 0 220px; min-width: 200px; border: 1px solid #E5E7EB; border-radius: 10px; padding: 10px 12px; background: #fff; }
    .academy-wiki-nav ul { list-style: none; margin: 6px 0 0; padding: 0; }
    .academy-wiki-nav li { margin: 2px 0; }
    .academy-wiki-nav a { display: block; padding: 4px 8px; border-radius: 6px; color: var(--sys-text-default, #1A1D23); text-decoration: none; font-size: 0.88rem; }
    .academy-wiki-nav a[aria-current="page"] { background: #E0F2F1; color: #064E5A; font-weight: 700; }
    .academy-wiki-main { flex: 1 1 320px; min-width: 280px; }
    .academy-wiki-page-title { font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 0; }
    .academy-wiki-meta { font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 2px 0 10px; }
    .academy-wiki-badge { display: inline-block; font-size: 0.7rem; font-weight: 700; padding: 1px 7px; border-radius: 999px; background: #E0F2F1; color: #064E5A; margin-left: 6px; }
    .academy-wiki-actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin: 10px 0; }
    .academy-wiki-field { width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 8px; font: inherit; margin-bottom: 8px; }
    .academy-wiki-text { width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 8px; resize: vertical; font: inherit; }
    .academy-wiki-rev { padding: 8px 10px; margin: 6px 0; background: #F9FAFB; border-radius: 8px; font-size: 0.85rem; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; }
    .academy-wiki-link { color: #064E5A; text-decoration: underline; }
    .academy-wiki-missing { color: #B45309; text-decoration: underline dotted; }
    /* F20 - base de données collaborative */
    .academy-db { max-width: 100%; }
    .academy-db-intro { color: var(--sys-text-muted, #6B7280); margin: 0 0 1rem; font-size: 0.95rem; }
    .academy-db-entries { display: flex; flex-direction: column; gap: 12px; }
    .academy-db-entry { border: 1px solid #E5E7EB; border-radius: 10px; padding: 12px 14px; background: #fff; }
    .academy-db-entry.is-pending { border-style: dashed; background: #FFFBEB; }
    .academy-db-meta { font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 8px; }
    .academy-db-badge { display: inline-block; font-size: 0.7rem; font-weight: 700; padding: 1px 7px; border-radius: 999px; background: #FEF3C7; color: #92400E; margin-left: 6px; }
    .academy-db-row { margin: 0 0 6px; font-size: 0.9rem; }
    .academy-db-row dt { font-weight: 700; color: var(--sys-text-default, #1A1D23); font-size: 0.8rem; }
    .academy-db-row dd { margin: 0 0 4px; color: var(--sys-text-default, #1A1D23); }
    .academy-db-empty { color: #9CA3AF; }
    .academy-db-link { color: #064E5A; text-decoration: underline; word-break: break-all; }
    .academy-db-field { width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 8px; font: inherit; }
    .academy-db-actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 8px; }
    /* F18 - notes (étoiles) + commentaires */
    .academy-engage { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #E5E7EB; max-width: 64ch; }
    .academy-rating { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 12px; }
    .academy-rating-avg { font-weight: 700; color: var(--sys-text-default, #1A1D23); }
    .academy-rating-count { font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); }
    .academy-rating-stars { letter-spacing: 1px; font-size: 1.05rem; }
    .academy-rating-form { margin-top: 8px; }
    .academy-rating-fieldset { border: 0; padding: 0; margin: 0; display: flex; flex-wrap: wrap; align-items: center; gap: 4px; }
    .academy-rating-legend { font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 8px 0 0; padding: 0; float: none; width: auto; }
    .academy-star { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; min-height: 28px; cursor: pointer; border-radius: 6px; }
    .academy-star input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .academy-star > span[aria-hidden] { font-size: 1.3rem; color: #D1D5DB; line-height: 1; }
    .academy-star input:checked + span[aria-hidden] { color: #F59E0B; }
    .academy-star input:focus-visible + span[aria-hidden] { outline: 2px solid var(--sys-action-primary, #064E5A); outline-offset: 2px; border-radius: 4px; }
    .academy-comments { margin-top: 1rem; }
    .academy-comments-title { font-size: 0.95rem; font-weight: 700; color: var(--sys-text-default, #1A1D23); margin: 0 0 8px; }
    .academy-comment { padding: 10px 12px; margin: 8px 0; background: #F9FAFB; border-radius: 8px; }
    .academy-comment-meta { font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin-bottom: 4px; }
    .academy-comment-del { margin-top: 6px; }
    .academy-comment-del > summary { cursor: pointer; font-size: 0.8rem; color: #9B1C1C; display: inline-block; }
    .academy-comment-form { margin-top: 10px; }
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
                                'wiki'     => '📖',
                                'database' => '🗃️',
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

                {{-- Narration TTS (accessibilité) : Web Speech API 100% navigateur, gâtée par
                     academy.tts_enabled. Texte = titre + résumé + contenu texte des items « doc »,
                     débarrassé du HTML côté serveur avant d'être injecté (voir tts-button.blade.php). --}}
                @if (config('academy.tts_enabled', false))
                    @php
                        $__ttsParts = array_filter([$lesson->title, $lesson->summary]);
                        foreach ($lesson->lessonItems as $__ttsItem) {
                            if ($__ttsItem->type === 'doc' && ! empty($__ttsItem->payload['rich_text'])) {
                                $__ttsHtml = \Modules\Academy\Models\LessonItem::renderRichText($__ttsItem->payload['rich_text']);
                                $__ttsText = trim(strip_tags($__ttsHtml));
                                if ($__ttsText !== '') {
                                    $__ttsParts[] = $__ttsText;
                                }
                            }
                        }
                        $__ttsFullText = implode('. ', $__ttsParts);
                    @endphp
                    <div class="mb-4">
                        <x-academy::tts-button :text="$__ttsFullText" />
                    </div>
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

                {{-- DeckPlayer : activé via academy.lesson_deck_mode=true (config/env), ou prévisualisation superadmin via ?deck=1.
                     LIMITE DOCUMENTÉE (TTS) : le bouton de narration audio (x-academy::tts-button, ci-dessus) n'est câblé
                     QUE dans la vue classique. Le DeckPlayer est un composant Livewire séparé (carte plein écran par item) ;
                     y ajouter le TTS demanderait de dupliquer/partager l'état Alpine entre cartes, hors scope de cette
                     phase. Choix assumé : vue classique seulement, zéro impact sur le DeckPlayer (aucun risque de casse). --}}
                @if(config('academy.lesson_deck_mode', false) || (request()->query('deck') === '1' && auth()->user()?->isSuperAdmin()))
                    @livewire('academy.deck-player', [
                        'lesson'             => $lesson,
                        'course'             => $course,
                        'isEnrolled'         => $isEnrolled,
                        'isPreview'          => $isPreview,
                        'choiceVotes'        => isset($choiceVotes) ? $choiceVotes->toArray() : [],
                        'feedbackResponses'  => isset($feedbackResponses) ? $feedbackResponses->toArray() : [],
                        'itemRestrictions'   => $itemRestrictions ?? [],
                        'itemRatingStats'    => isset($itemRatingStats) ? $itemRatingStats->toArray() : [],
                        'userRatings'        => isset($userRatings) ? $userRatings->toArray() : [],
                        'itemComments'       => isset($itemComments) ? $itemComments->toArray() : [],
                        'prerequisitesUnmet' => isset($prerequisitesUnmet) ? $prerequisitesUnmet->toArray() : [],
                        'isDripLocked'       => $isDripLocked ?? false,
                        'dripAvailableAt'    => isset($dripAvailableAt) ? $dripAvailableAt->toIso8601String() : null,
                        'dripLockedLessonIds'=> $dripLockedLessonIds ?? [],
                        'courseCompleted'    => $courseCompleted ?? false,
                    ])
                @else
                {{-- Vue classique (longue page, comportement historique) --}}
                <div class="lesson-classic-view" data-view="classic">
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

                        {{-- Rendu de l'item : partial DRY partagé avec DeckPlayer. --}}
                        @include('academy::public.partials.item-body', [
                            'item'              => $item,
                            'hasAccess'         => $hasAccess,
                            'isEnrolled'        => $isEnrolled,
                            'isPreview'         => $isPreview,
                            'isFree'            => $isFree,
                            'course'            => $course,
                            'lesson'            => $lesson,
                            'choiceVotes'       => $choiceVotes ?? [],
                            'feedbackResponses' => $feedbackResponses ?? [],
                        ])
                        {{-- Contenu inline supprimé et déplacé dans item-body.blade.php (DRY) --}}
                        @if(false) {{-- Bloc de rendu désactivé - voir partial item-body.blade.php --}}
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

                        {{-- ── TYPE WIKI (F19) : pages collaboratives + historique ── --}}
                        @elseif($item->type === 'wiki')
                            @php
                                $wikiIntro       = \Modules\Academy\Services\WikiService::intro($item);
                                $wikiAllowEdit   = \Modules\Academy\Services\WikiService::allowsStudentEdit($item);
                                // Gérant de CE cours (admin OU owner/instructor) : modère ET contribue
                                // même hors inscription. L'autorisation réelle est TOUJOURS re-vérifiée
                                // serveur (WikiController) ; ici c'est de l'affichage.
                                $wikiCanModerate = auth()->check() && auth()->user()->can('manageEnrollments', $course);
                                $wikiPages       = ($hasAccess || $wikiCanModerate)
                                    ? \Modules\Academy\Services\WikiService::pages($item)
                                    : collect();
                                // Page courante : ?wpage_{id}=slug, sinon accueil, sinon 1re page.
                                $wikiSlug    = request('wpage_'.$item->id);
                                $wikiCurrent = $wikiSlug ? $wikiPages->firstWhere('slug', $wikiSlug) : null;
                                $wikiCurrent = $wikiCurrent ?: ($wikiPages->firstWhere('is_home', true) ?: $wikiPages->first());
                                // Peut créer une page : gérant toujours ; étudiant si inscrit + édition permise.
                                $wikiCanCreate = $wikiCanModerate || ($hasAccess && auth()->check() && $wikiAllowEdit);
                                // Peut éditer la page courante : gérant ; ou inscrit + édition permise + page non verrouillée.
                                $wikiCanEdit = $wikiCurrent && ($wikiCanModerate || ($hasAccess && auth()->check() && $wikiAllowEdit && ! $wikiCurrent->is_locked));
                                // Peut restaurer : gérant ; ou auteur (created_by) sous les mêmes règles d'édition.
                                $wikiCanRestore = $wikiCurrent && ($wikiCanModerate || ($wikiCanEdit && (int) ($wikiCurrent->created_by ?? 0) === (int) auth()->id()));
                                // Historique demandé pour la page courante ?
                                $wikiHistOpen  = $wikiCurrent && (int) request('whist_'.$item->id) === (int) $wikiCurrent->id;
                                $wikiRevisions = $wikiHistOpen ? \Modules\Academy\Services\WikiService::revisions($wikiCurrent) : null;
                            @endphp
                            @if($hasAccess || $wikiCanModerate)
                                <div class="academy-wiki">
                                    @if($wikiIntro !== '')
                                        <p class="academy-wiki-intro">{{ $wikiIntro }}</p>
                                    @endif

                                    @if(! $wikiAllowEdit)
                                        <p class="text-muted p-2 rounded" style="background: #F3F4F6; font-size: 0.85rem;">
                                            <span aria-hidden="true">🔒</span> Wiki en lecture seule pour les étudiants.
                                            @if($wikiCanModerate) (Vous pouvez tout de même éditer en tant que gérant.) @endif
                                        </p>
                                    @endif

                                    <div class="academy-wiki-layout">
                                        {{-- Navigation : pages (accueil en tête) + nouvelle page. --}}
                                        <nav class="academy-wiki-nav" aria-label="Pages du wiki">
                                            <strong style="font-size: 0.8rem;">Pages</strong>
                                            <ul>
                                                @forelse($wikiPages as $p)
                                                    <li>
                                                        <a href="?wpage_{{ $item->id }}={{ urlencode($p->slug) }}#item-{{ $item->id }}"
                                                           @if($wikiCurrent && $p->id === $wikiCurrent->id) aria-current="page" @endif>
                                                            @if($p->is_home)<span aria-hidden="true" title="Accueil">🏠</span> @endif
                                                            {{ $p->title }}
                                                            @if($p->is_locked)<span aria-hidden="true" title="Verrouillée">🔒</span><span class="visually-hidden">Verrouillée</span>@endif
                                                        </a>
                                                    </li>
                                                @empty
                                                    <li class="text-muted" style="font-size: 0.82rem; padding: 4px 8px;">Aucune page pour l'instant.</li>
                                                @endforelse
                                            </ul>

                                            @if($wikiCanCreate)
                                                <details class="mt-2">
                                                    <summary style="cursor: pointer; font-size: 0.82rem; font-weight: 600;">+ Nouvelle page</summary>
                                                    <form method="POST" action="{{ route('academy.wiki.pages.create', [$course, $lesson, $item->id]) }}" class="mt-1">
                                                        @csrf
                                                        {{-- Honeypot MAISON : doit rester vide ; hors écran, non focusable. --}}
                                                        <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                            <label for="wiki-hp-{{ $item->id }}">Ne pas remplir</label>
                                                            <input type="text" id="wiki-hp-{{ $item->id }}" name="{{ \Modules\Academy\Services\WikiService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                        </div>
                                                        <label for="wiki-new-title-{{ $item->id }}" style="font-size: 0.8rem; font-weight: 600;">Titre de la page</label>
                                                        <input type="text" id="wiki-new-title-{{ $item->id }}" name="title" class="academy-wiki-field"
                                                               maxlength="{{ \Modules\Academy\Services\WikiService::TITLE_MAX }}" required>
                                                        <label for="wiki-new-body-{{ $item->id }}" style="font-size: 0.8rem; font-weight: 600;">Contenu (markdown ; lien interne : [[Titre]])</label>
                                                        <textarea id="wiki-new-body-{{ $item->id }}" name="body" class="academy-wiki-text" rows="4"
                                                                  maxlength="{{ \Modules\Academy\Services\WikiService::BODY_MAX }}"></textarea>
                                                        <div class="mt-2"><x-core::button type="submit" variant="primary" size="sm">Créer la page</x-core::button></div>
                                                    </form>
                                                </details>
                                            @endif
                                        </nav>

                                        {{-- Contenu de la page courante. --}}
                                        <div class="academy-wiki-main">
                                            @if($wikiCurrent)
                                                <h3 class="academy-wiki-page-title">
                                                    {{ $wikiCurrent->title }}
                                                    @if($wikiCurrent->is_locked)<span class="academy-wiki-badge"><span aria-hidden="true">🔒</span> Verrouillée</span>@endif
                                                </h3>
                                                <p class="academy-wiki-meta">
                                                    Version {{ $wikiCurrent->revision }} · modifiée par {{ $wikiCurrent->editor?->name ?? '(inconnu)' }}
                                                    @if($wikiCurrent->updated_at) · {{ $wikiCurrent->updated_at->diffForHumans() }} @endif
                                                </p>

                                                {{-- SÉCURITÉ : renderBody = markdown html_input=strip (anti-XSS) + liens [[..]] internes. --}}
                                                <div class="prose academy-richtext">{!! \Modules\Academy\Services\WikiService::renderBody($item, $wikiCurrent, $wikiPages) !!}</div>

                                                <div class="academy-wiki-actions">
                                                    {{-- Historique (lecture seule) : bascule via paramètre de requête. --}}
                                                    @if($wikiHistOpen)
                                                        <x-core::button :href="'?wpage_'.$item->id.'='.urlencode($wikiCurrent->slug).'#item-'.$item->id" variant="ghost" size="sm">Masquer l'historique</x-core::button>
                                                    @else
                                                        <x-core::button :href="'?wpage_'.$item->id.'='.urlencode($wikiCurrent->slug).'&whist_'.$item->id.'='.$wikiCurrent->id.'#item-'.$item->id" variant="ghost" size="sm">Historique ({{ $wikiCurrent->revision - 1 }})</x-core::button>
                                                    @endif

                                                    {{-- Modération (gérant) : verrouiller (bascule) + supprimer (sauf accueil, 2 temps). --}}
                                                    @if($wikiCanModerate)
                                                        <form method="POST" action="{{ route('academy.wiki.pages.lock', [$course, $lesson, $item->id, $wikiCurrent->id]) }}">
                                                            @csrf
                                                            <x-core::button type="submit" variant="ghost" size="sm">{{ $wikiCurrent->is_locked ? 'Déverrouiller' : 'Verrouiller' }}</x-core::button>
                                                        </form>
                                                        @unless($wikiCurrent->is_home)
                                                            <details>
                                                                <summary style="cursor: pointer; font-size: 0.82rem; color: var(--sys-action-danger, #DC2626);">Supprimer la page</summary>
                                                                <form method="POST" action="{{ route('academy.wiki.pages.delete', [$course, $lesson, $item->id, $wikiCurrent->id]) }}" class="mt-1">
                                                                    @csrf
                                                                    <x-core::button type="submit" variant="ghost" size="sm">Confirmer la suppression de la page</x-core::button>
                                                                </form>
                                                            </details>
                                                        @endunless
                                                    @endif
                                                </div>

                                                {{-- Éditer la page courante (collaboratif). Confirmation par dépliage, jamais de popup. --}}
                                                @if($wikiCanEdit)
                                                    <details>
                                                        <summary style="cursor: pointer; font-size: 0.85rem; font-weight: 600;">Modifier cette page</summary>
                                                        <form method="POST" action="{{ route('academy.wiki.pages.update', [$course, $lesson, $item->id, $wikiCurrent->id]) }}" class="mt-1">
                                                            @csrf
                                                            <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                                <label for="wiki-ehp-{{ $wikiCurrent->id }}">Ne pas remplir</label>
                                                                <input type="text" id="wiki-ehp-{{ $wikiCurrent->id }}" name="{{ \Modules\Academy\Services\WikiService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                            </div>
                                                            <label for="wiki-edit-title-{{ $wikiCurrent->id }}" style="font-size: 0.8rem; font-weight: 600;">Titre</label>
                                                            <input type="text" id="wiki-edit-title-{{ $wikiCurrent->id }}" name="title" class="academy-wiki-field"
                                                                   value="{{ $wikiCurrent->title }}" maxlength="{{ \Modules\Academy\Services\WikiService::TITLE_MAX }}" required>
                                                            <label for="wiki-edit-body-{{ $wikiCurrent->id }}" style="font-size: 0.8rem; font-weight: 600;">Contenu (markdown ; lien interne : [[Titre]])</label>
                                                            <textarea id="wiki-edit-body-{{ $wikiCurrent->id }}" name="body" class="academy-wiki-text" rows="8"
                                                                      maxlength="{{ \Modules\Academy\Services\WikiService::BODY_MAX }}">{{ $wikiCurrent->body }}</textarea>
                                                            <div class="mt-2"><x-core::button type="submit" variant="secondary" size="sm">Enregistrer la page</x-core::button></div>
                                                        </form>
                                                    </details>
                                                @endif

                                                {{-- Panneau historique (révisions, lecture seule + restauration gatée). --}}
                                                @if($wikiHistOpen && $wikiRevisions)
                                                    <div class="mt-3" style="border-top: 1px dashed #E5E7EB; padding-top: 10px;">
                                                        <strong style="font-size: 0.85rem;">Historique des révisions</strong>
                                                        @forelse($wikiRevisions as $rev)
                                                            <div class="academy-wiki-rev" wire:key="wiki-rev-{{ $rev->id }}">
                                                                <span>Version {{ $rev->revision }} · {{ $rev->user?->name ?? '(inconnu)' }} · {{ $rev->snapshot_at?->diffForHumans() }}</span>
                                                                @if($wikiCanRestore)
                                                                    <form method="POST" action="{{ route('academy.wiki.pages.restore', [$course, $lesson, $item->id, $wikiCurrent->id, $rev->id]) }}">
                                                                        @csrf
                                                                        <x-core::button type="submit" variant="ghost" size="sm">Restaurer cette version</x-core::button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        @empty
                                                            <p class="text-muted" style="font-size: 0.85rem;">Aucune révision : cette page n'a pas encore été modifiée.</p>
                                                        @endforelse
                                                        @if($wikiRevisions->hasPages())
                                                            <div class="mt-2">{{ $wikiRevisions->withQueryString()->links() }}</div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @else
                                                <p class="text-muted" style="font-size: 0.9rem;">Ce wiki n'a pas encore de page. @if($wikiCanCreate) Créez la première page (elle deviendra l'accueil). @endif</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour accéder au wiki
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder au wiki
                                        @else
                                            Wiki en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour consulter et contribuer au wiki.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit : inscrivez-vous pour contribuer.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant : achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">Se connecter</x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">Créer un compte</x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">S'inscrire gratuitement</x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">Acheter ce cours</x-core::button>
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

                        {{-- ── TYPE BASE DE DONNÉES (F20, parité Moodle « Database ») ── --}}
                        @elseif($item->type === 'database')
                            @php
                                $dbIntro          = \Modules\Academy\Services\DatabaseService::intro($item);
                                $dbAllowAdd       = \Modules\Academy\Services\DatabaseService::allowsStudentAdd($item);
                                // Gérant de CE cours (admin OU owner/instructor) : modère ET contribue
                                // même hors inscription. L'autorisation réelle est TOUJOURS re-vérifiée
                                // serveur (DatabaseController) ; ici c'est de l'affichage.
                                $dbCanModerate = auth()->check() && auth()->user()->can('manageEnrollments', $course);
                                $dbFields  = ($hasAccess || $dbCanModerate)
                                    ? \Modules\Academy\Services\DatabaseService::fields($item)
                                    : collect();
                                $dbEntries = ($hasAccess || $dbCanModerate)
                                    ? \Modules\Academy\Services\DatabaseService::entries($item, auth()->id(), $dbCanModerate)
                                    : null;
                                // Peut ajouter une fiche : gérant toujours ; inscrit si l'ajout est permis.
                                $dbCanAdd = $dbCanModerate || ($hasAccess && auth()->check() && $dbAllowAdd);
                            @endphp
                            @if($hasAccess || $dbCanModerate)
                                <div class="academy-db">
                                    @if($dbIntro !== '')
                                        <p class="academy-db-intro">{{ $dbIntro }}</p>
                                    @endif

                                    @if(! $dbAllowAdd)
                                        <p class="text-muted p-2 rounded" style="background: #F3F4F6; font-size: 0.85rem;">
                                            <span aria-hidden="true">🔒</span> L'ajout de fiches est réservé au formateur.
                                            @if($dbCanModerate) (Vous pouvez tout de même ajouter une fiche en tant que gérant.) @endif
                                        </p>
                                    @endif

                                    {{-- Collection des fiches (approuvées pour tous ; en attente visibles à
                                         l'auteur + au gérant ; déjà filtrées côté service, anti-fuite). --}}
                                    <div class="academy-db-entries">
                                        @forelse($dbEntries as $entry)
                                            @php $dbVals = \Modules\Academy\Services\DatabaseService::valuesByField($entry); @endphp
                                            @php $dbIsOwner = auth()->check() && (int) ($entry->user_id ?? 0) === (int) auth()->id(); @endphp
                                            <div class="academy-db-entry @if(! $entry->is_approved) is-pending @endif" wire:key="db-entry-{{ $entry->id }}">
                                                <p class="academy-db-meta">
                                                    Par {{ $entry->author?->name ?? '(inconnu)' }}
                                                    @if($entry->created_at) · {{ $entry->created_at->diffForHumans() }} @endif
                                                    @unless($entry->is_approved)<span class="academy-db-badge">En attente d'approbation</span>@endunless
                                                </p>
                                                <dl class="academy-db-row">
                                                    @foreach($dbFields as $field)
                                                        <dt>{{ $field->label }}</dt>
                                                        <dd>{!! \Modules\Academy\Services\DatabaseService::renderValue($field, $dbVals[$field->id] ?? null) !!}</dd>
                                                    @endforeach
                                                </dl>

                                                <div class="academy-db-actions">
                                                    {{-- Modération : approuver une fiche en attente (gérant). --}}
                                                    @if($dbCanModerate && ! $entry->is_approved)
                                                        <form method="POST" action="{{ route('academy.database.entries.approve', [$course, $lesson, $item->id, $entry->id]) }}">
                                                            @csrf
                                                            <x-core::button type="submit" variant="primary" size="sm">Approuver</x-core::button>
                                                        </form>
                                                    @endif

                                                    {{-- Supprimer SA fiche (ou n'importe laquelle si gérant) : 2 temps, jamais de popup. --}}
                                                    @if($dbIsOwner || $dbCanModerate)
                                                        <details>
                                                            <summary style="cursor: pointer; font-size: 0.82rem; color: var(--sys-action-danger, #DC2626);">Supprimer cette fiche</summary>
                                                            <form method="POST" action="{{ route('academy.database.entries.delete', [$course, $lesson, $item->id, $entry->id]) }}" class="mt-1">
                                                                @csrf
                                                                <x-core::button type="submit" variant="ghost" size="sm">Confirmer la suppression</x-core::button>
                                                            </form>
                                                        </details>
                                                    @endif
                                                </div>

                                                {{-- Éditer SA fiche (collaboratif : seul l'auteur ou un gérant). --}}
                                                @if(($dbIsOwner || $dbCanModerate) && $dbFields->isNotEmpty())
                                                    <details>
                                                        <summary style="cursor: pointer; font-size: 0.85rem; font-weight: 600;">Modifier cette fiche</summary>
                                                        <form method="POST" action="{{ route('academy.database.entries.update', [$course, $lesson, $item->id, $entry->id]) }}" class="mt-1" style="display: flex; flex-direction: column; gap: 8px;">
                                                            @csrf
                                                            <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                                <label for="db-ehp-{{ $entry->id }}">Ne pas remplir</label>
                                                                <input type="text" id="db-ehp-{{ $entry->id }}" name="{{ \Modules\Academy\Services\DatabaseService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                            </div>
                                                            @foreach($dbFields as $field)
                                                                @include('academy::public.partials.database-field', ['field' => $field, 'value' => $dbVals[$field->id] ?? '', 'entryId' => $entry->id])
                                                            @endforeach
                                                            <div><x-core::button type="submit" variant="secondary" size="sm">Enregistrer la fiche</x-core::button></div>
                                                        </form>
                                                    </details>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="text-muted" style="font-size: 0.9rem;">Aucune fiche pour l'instant.@if($dbCanAdd) Ajoutez la première ci-dessous.@endif</p>
                                        @endforelse
                                    </div>

                                    @if($dbEntries && $dbEntries->hasPages())
                                        <div class="mt-2">{{ $dbEntries->withQueryString()->links() }}</div>
                                    @endif

                                    {{-- Ajouter une fiche (gaté allow_student_add ; le gérant toujours). --}}
                                    @if($dbCanAdd && $dbFields->isNotEmpty())
                                        <details class="mt-3">
                                            <summary style="cursor: pointer; font-size: 0.88rem; font-weight: 700;">+ Ajouter une fiche</summary>
                                            <form method="POST" action="{{ route('academy.database.entries.create', [$course, $lesson, $item->id]) }}" class="mt-2" style="display: flex; flex-direction: column; gap: 8px;">
                                                @csrf
                                                {{-- Honeypot MAISON : doit rester vide ; hors écran, non focusable. --}}
                                                <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                    <label for="db-hp-{{ $item->id }}">Ne pas remplir</label>
                                                    <input type="text" id="db-hp-{{ $item->id }}" name="{{ \Modules\Academy\Services\DatabaseService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                </div>
                                                @foreach($dbFields as $field)
                                                    @include('academy::public.partials.database-field', ['field' => $field, 'value' => '', 'entryId' => 'new-'.$item->id])
                                                @endforeach
                                                @if(\Modules\Academy\Services\DatabaseService::requiresApproval($item) && ! $dbCanModerate)
                                                    <p class="text-muted" style="font-size: 0.8rem;">Votre fiche sera visible après l'approbation du formateur.</p>
                                                @endif
                                                <div><x-core::button type="submit" variant="primary" size="sm">Ajouter la fiche</x-core::button></div>
                                            </form>
                                        </details>
                                    @elseif($dbCanAdd && $dbFields->isEmpty())
                                        <p class="text-muted mt-2" style="font-size: 0.85rem;">Cette base de données n'a pas encore de champ. Définissez le schéma dans l'éditeur de cours.</p>
                                    @endif
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien de sensible dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour accéder à la base de données
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder à la base de données
                                        @else
                                            Base de données en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour consulter et contribuer.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit : inscrivez-vous pour contribuer.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant : achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">Se connecter</x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">Créer un compte</x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">S'inscrire gratuitement</x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">Acheter ce cours</x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE ATELIER (workshop : évaluation par les pairs, parité Moodle « Workshop ») ── --}}
                        @elseif($item->type === 'workshop')
                            @php
                                $wsIntro    = \Modules\Academy\Services\WorkshopService::intro($item);
                                $wsPhase    = \Modules\Academy\Services\WorkshopService::phase($item);
                                $wsAnon     = \Modules\Academy\Services\WorkshopService::isAnonymous($item);
                                // Gérant de CE cours (admin OU owner/instructor) : l'autorisation réelle
                                // est TOUJOURS re-vérifiée serveur (WorkshopController) ; ici, affichage.
                                $wsManage = auth()->check() && auth()->user()->can('manageEnrollments', $course);
                                $wsCriteria = ($hasAccess || $wsManage)
                                    ? \Modules\Academy\Services\WorkshopService::criteria($item)
                                    : collect();
                                $wsMine = ($hasAccess && auth()->check())
                                    ? \Modules\Academy\Services\WorkshopService::submissionFor($item, auth()->id())
                                    : null;
                                $wsAssignments = ($hasAccess && auth()->check() && $wsPhase === 'assessment')
                                    ? \Modules\Academy\Services\WorkshopService::assignmentsFor($item, (int) auth()->id())
                                    : collect();
                                $wsPhaseLabel = ['setup' => 'Préparation', 'submission' => 'Soumission', 'assessment' => 'Évaluation', 'closed' => 'Notes'][$wsPhase] ?? $wsPhase;
                            @endphp
                            @if($hasAccess || $wsManage)
                                <div class="academy-workshop">
                                    @if($wsIntro !== '')
                                        <p class="academy-db-intro">{{ $wsIntro }}</p>
                                    @endif

                                    <p class="text-muted p-2 rounded" style="background: #F3F4F6; font-size: 0.85rem;">
                                        Phase courante : <strong>{{ $wsPhaseLabel }}</strong>.
                                        @if($wsPhase === 'submission') Remettez votre travail ci-dessous.
                                        @elseif($wsPhase === 'assessment') Évaluez les travaux qui vous sont attribués.
                                        @elseif($wsPhase === 'closed') Consultez votre note finale.
                                        @else L'atelier est en préparation.
                                        @endif
                                    </p>

                                    {{-- ── TABLEAU DE BORD GÉRANT : phase, progression, actions ── --}}
                                    @if($wsManage)
                                        @php
                                            $wsSubs     = \Modules\Academy\Services\WorkshopService::submissions($item);
                                            $wsProgress = \Modules\Academy\Services\WorkshopService::assessmentProgress($item);
                                        @endphp
                                        <div class="academy-db-entry" style="background: #FAFAFA;">
                                            <p class="academy-db-meta"><strong>Pilotage de l'atelier (gérant)</strong></p>
                                            <p style="font-size: 0.85rem; margin: 0 0 6px;">
                                                Travaux remis : {{ $wsSubs->count() }} ·
                                                Évaluations attribuées : {{ $wsProgress['allocated'] }} ·
                                                rendues : {{ $wsProgress['submitted'] }}
                                            </p>

                                            <form method="POST" action="{{ route('academy.workshop.phase', [$course, $lesson, $item->id]) }}" style="display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 8px;">
                                                @csrf
                                                <span style="display: flex; flex-direction: column; gap: 4px;">
                                                    <label for="ws-phase-{{ $item->id }}" style="font-size: 0.78rem; font-weight: 600;">Changer la phase</label>
                                                    <select id="ws-phase-{{ $item->id }}" name="phase" style="padding: 6px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                        @foreach(['setup' => 'Préparation', 'submission' => 'Soumission', 'assessment' => 'Évaluation (attribue les évaluations)', 'closed' => 'Notes'] as $pv => $pl)
                                                            <option value="{{ $pv }}" @selected($wsPhase === $pv)>{{ $pl }}</option>
                                                        @endforeach
                                                    </select>
                                                </span>
                                                <x-core::button type="submit" variant="primary" size="sm">Appliquer la phase</x-core::button>
                                            </form>

                                            <form method="POST" action="{{ route('academy.workshop.allocate', [$course, $lesson, $item->id]) }}">
                                                @csrf
                                                <x-core::button type="submit" variant="secondary" size="sm">Attribuer les évaluations</x-core::button>
                                            </form>

                                            @if($wsSubs->isNotEmpty())
                                                <details class="mt-2">
                                                    <summary style="cursor: pointer; font-size: 0.85rem; font-weight: 600;">Voir les travaux et leurs notes</summary>
                                                    <ul style="list-style: none; padding: 0; margin: 8px 0 0;">
                                                        @php $wsScoreMap = \Modules\Academy\Services\WorkshopService::batchFinalScores($wsSubs); @endphp
                                                        @foreach($wsSubs as $sub)
                                                            @php $subScore = $wsScoreMap[$sub->id] ?? null; @endphp
                                                            <li style="border-top: 1px solid #E5E7EB; padding: 6px 0; font-size: 0.85rem;">
                                                                <strong>{{ $sub->title }}</strong> - {{ $sub->author?->name ?? '(inconnu)' }}
                                                                · Note : {{ $subScore === null ? 'en attente' : number_format($subScore, 1, ',', ' ').' %' }}
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </details>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- ── PHASE SOUMISSION : remettre / consulter SON travail ── --}}
                                    @if($wsPhase === 'submission' && $hasAccess && auth()->check())
                                        <details class="mt-3" @if($wsMine === null) open @endif>
                                            <summary style="cursor: pointer; font-size: 0.88rem; font-weight: 700;">{{ $wsMine ? 'Modifier mon travail' : '+ Remettre mon travail' }}</summary>
                                            <form method="POST" action="{{ route('academy.workshop.submit', [$course, $lesson, $item->id]) }}" class="mt-2" style="display: flex; flex-direction: column; gap: 8px;">
                                                @csrf
                                                <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                    <label for="ws-hp-{{ $item->id }}">Ne pas remplir</label>
                                                    <input type="text" id="ws-hp-{{ $item->id }}" name="{{ \Modules\Academy\Services\WorkshopService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                </div>
                                                <label for="ws-title-{{ $item->id }}" style="font-size: 0.78rem; font-weight: 600;">Titre de mon travail</label>
                                                <input id="ws-title-{{ $item->id }}" type="text" name="title" value="{{ $wsMine->title ?? '' }}" maxlength="{{ \Modules\Academy\Services\WorkshopService::TITLE_MAX }}" required
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                <label for="ws-body-{{ $item->id }}" style="font-size: 0.78rem; font-weight: 600;">Mon travail</label>
                                                <textarea id="ws-body-{{ $item->id }}" name="body" rows="6" maxlength="{{ \Modules\Academy\Services\WorkshopService::BODY_MAX }}"
                                                          style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;">{{ $wsMine->body ?? '' }}</textarea>
                                                <div><x-core::button type="submit" variant="primary" size="sm">{{ $wsMine ? 'Mettre à jour mon travail' : 'Remettre mon travail' }}</x-core::button></div>
                                            </form>
                                        </details>
                                        @if($wsMine)
                                            <div class="academy-db-entry mt-2">
                                                <p class="academy-db-meta">Mon travail remis - <strong>{{ $wsMine->title }}</strong></p>
                                                <div>{!! \Modules\Academy\Services\WorkshopService::renderText($wsMine->body) !!}</div>
                                            </div>
                                        @endif
                                    @endif

                                    {{-- ── PHASE ÉVALUATION : noter les travaux attribués (anonymisés) ── --}}
                                    @if($wsPhase === 'assessment' && $hasAccess && auth()->check())
                                        <h4 style="font-size: 0.95rem; font-weight: 700; margin: 14px 0 6px;">Travaux à évaluer</h4>
                                        @forelse($wsAssignments as $assessment)
                                            @php $wsScores = \Modules\Academy\Services\WorkshopService::scoresByCriterion($assessment); @endphp
                                            <div class="academy-db-entry" wire:key="ws-assess-{{ $assessment->id }}">
                                                <p class="academy-db-meta">
                                                    <strong>{{ $assessment->submission->title ?? 'Travail' }}</strong>
                                                    @unless($wsAnon) - {{ $assessment->submission->author?->name ?? '(inconnu)' }} @endunless
                                                    @if($assessment->submitted_at)<span class="academy-db-badge">Évaluation rendue</span>@endif
                                                </p>
                                                <div style="margin-bottom: 8px;">{!! \Modules\Academy\Services\WorkshopService::renderText($assessment->submission->body ?? '') !!}</div>

                                                <form method="POST" action="{{ route('academy.workshop.assess', [$course, $lesson, $item->id, $assessment->id]) }}" style="display: flex; flex-direction: column; gap: 8px;">
                                                    @csrf
                                                    <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                        <label for="ws-ahp-{{ $assessment->id }}">Ne pas remplir</label>
                                                        <input type="text" id="ws-ahp-{{ $assessment->id }}" name="{{ \Modules\Academy\Services\WorkshopService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                    </div>
                                                    @foreach($wsCriteria as $criterion)
                                                        <span style="display: flex; flex-direction: column; gap: 4px;">
                                                            <label for="ws-score-{{ $assessment->id }}-{{ $criterion->id }}" style="font-size: 0.8rem; font-weight: 600;">
                                                                {{ $criterion->label }} <span class="text-muted">(0 à {{ $criterion->max_score }})</span>
                                                            </label>
                                                            @if($criterion->description)<span class="text-muted" style="font-size: 0.78rem;">{{ $criterion->description }}</span>@endif
                                                            <input id="ws-score-{{ $assessment->id }}-{{ $criterion->id }}" type="number" min="0" max="{{ $criterion->max_score }}"
                                                                   name="scores[{{ $criterion->id }}]" value="{{ $wsScores[$criterion->id] ?? '' }}"
                                                                   style="width: 110px; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                        </span>
                                                    @endforeach
                                                    <label for="ws-fb-{{ $assessment->id }}" style="font-size: 0.8rem; font-weight: 600;">Commentaire (facultatif)</label>
                                                    <textarea id="ws-fb-{{ $assessment->id }}" name="feedback" rows="3" maxlength="{{ \Modules\Academy\Services\WorkshopService::FEEDBACK_MAX }}"
                                                              style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;">{{ $assessment->feedback }}</textarea>
                                                    <div><x-core::button type="submit" variant="primary" size="sm">Enregistrer mon évaluation</x-core::button></div>
                                                </form>
                                            </div>
                                        @empty
                                            <p class="text-muted" style="font-size: 0.9rem;">Aucun travail ne vous est attribué pour l'instant. Le formateur attribuera les évaluations.</p>
                                        @endforelse
                                    @endif

                                    {{-- ── PHASE NOTES : ma note finale + retours reçus ── --}}
                                    @if($wsPhase === 'closed' && $hasAccess && auth()->check())
                                        @php
                                            $wsFinal = \Modules\Academy\Services\WorkshopService::finalGradeForStudent($item, auth()->id());
                                        @endphp
                                        <div class="academy-db-entry mt-2">
                                            <p class="academy-db-meta"><strong>Ma note finale</strong></p>
                                            @if($wsMine === null)
                                                <p class="text-muted" style="font-size: 0.9rem;">Vous n'avez pas remis de travail pour cet atelier.</p>
                                            @elseif($wsFinal === null)
                                                <p class="text-muted" style="font-size: 0.9rem;">Votre travail n'a pas encore reçu d'évaluation.</p>
                                            @else
                                                <p style="font-size: 1.1rem; font-weight: 700;">{{ number_format($wsFinal, 1, ',', ' ') }} %</p>
                                                @php $wsReceived = \Modules\Academy\Services\WorkshopService::receivedFeedbacks($wsMine)->filter(fn ($a) => filled($a->feedback)); @endphp
                                                @if($wsReceived->isNotEmpty())
                                                    <p class="academy-db-meta" style="margin-top: 8px;">Retours reçus</p>
                                                    @foreach($wsReceived as $rec)
                                                        <div style="border-top: 1px solid #E5E7EB; padding: 6px 0;">
                                                            <div>{!! \Modules\Academy\Services\WorkshopService::renderText($rec->feedback) !!}</div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            @endif
                                        </div>
                                    @endif

                                    @if($wsCriteria->isEmpty())
                                        <p class="text-muted mt-2" style="font-size: 0.85rem;">Cet atelier n'a pas encore de grille d'évaluation. Définissez les critères dans l'éditeur de cours.</p>
                                    @endif
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien de sensible dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour accéder à l'atelier
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder à l'atelier
                                        @else
                                            Atelier en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour remettre un travail et évaluer vos pairs.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit : inscrivez-vous pour participer.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant : achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">Se connecter</x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">Créer un compte</x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">S'inscrire gratuitement</x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">Acheter ce cours</x-core::button>
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
                        @endif {{-- /if(false) - fin du bloc de rendu inline désactivé --}}

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
                            @elseif($itemCriterion === 'manual' && in_array($item->type, ['video', 'doc', 'document', 'choice', 'forum', 'h5p', 'wiki', 'database', 'workshop']))
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

                        {{-- F18 - NOTES (étoiles) + COMMENTAIRES (parité Moodle ratings/comments).
                             Affiché quand l'utilisateur a accès à l'item ($hasAccess). La moyenne
                             et les commentaires sont visibles par tout utilisateur ayant accès ; le
                             contrôle de note et le formulaire de commentaire ne s'affichent QUE pour
                             un inscrit réel (hors prévisualisation : un gérant ne note/commente pas).
                             Données préchargées dans LessonController (anti N+1). L'autorisation
                             d'écrire est TOUJOURS revalidée serveur (trait AuthorizesAcademyAccess). --}}
                        @if($hasAccess)
                            @php
                                $__ratingStat   = ($itemRatingStats ?? collect())->get($item->id);
                                $__ratingCount  = $__ratingStat ? (int) $__ratingStat->votes_count : 0;
                                $__ratingAvg    = $__ratingStat ? round((float) $__ratingStat->avg_value, 1) : 0.0;
                                $__userRating   = (int) (($userRatings ?? collect())->get($item->id) ?? 0);
                                $__itemComments = ($itemComments ?? collect())->get($item->id) ?? collect();
                                $__canEngage    = ($isEnrolled ?? false) && !($isPreview ?? false) && auth()->check();
                                $__canModerate  = auth()->check() && auth()->user()->can('manageEnrollments', $course);
                            @endphp

                            <div class="academy-engage">
                                {{-- ── Note moyenne + nombre de notes ── --}}
                                <div class="academy-rating">
                                    <span class="academy-rating-stars" aria-hidden="true">@for($__s = 1; $__s <= 5; $__s++)<span style="color: {{ $__s <= round($__ratingAvg) ? '#F59E0B' : '#D1D5DB' }};">★</span>@endfor</span>
                                    <span class="academy-rating-avg">{{ $__ratingCount > 0 ? number_format($__ratingAvg, 1) : '-' }}/5</span>
                                    <span class="academy-rating-count">{{ $__ratingCount }} note{{ $__ratingCount > 1 ? 's' : '' }}</span>
                                </div>

                                {{-- ── Contrôle de note (inscrit réel uniquement) ── --}}
                                @if($__canEngage)
                                    <form method="POST" action="{{ route('academy.items.rate', [$course, $lesson, $item->id]) }}" class="academy-rating-form">
                                        @csrf
                                        <fieldset class="academy-rating-fieldset" role="radiogroup" aria-label="Votre note sur 5 pour : {{ $item->title ?? 'cet élément' }}">
                                            <legend class="academy-rating-legend">Votre note</legend>
                                            @for($__s = 1; $__s <= 5; $__s++)
                                                <label class="academy-star">
                                                    <input type="radio" name="value" value="{{ $__s }}" @checked($__userRating === $__s) @if($__s === 1) required @endif>
                                                    <span aria-hidden="true">★</span>
                                                    <span class="visually-hidden">{{ $__s }} étoile{{ $__s > 1 ? 's' : '' }}</span>
                                                </label>
                                            @endfor
                                            <x-core::button type="submit" variant="secondary" size="sm">
                                                {{ $__userRating > 0 ? 'Modifier ma note' : 'Enregistrer ma note' }}
                                            </x-core::button>
                                        </fieldset>
                                    </form>
                                @endif

                                {{-- ── Commentaires ── --}}
                                <div class="academy-comments">
                                    <h3 class="academy-comments-title">Commentaires ({{ $__itemComments->count() }})</h3>

                                    @forelse($__itemComments as $__comment)
                                        <div class="academy-comment" id="comment-{{ $__comment->id }}">
                                            <div class="academy-comment-meta">
                                                {{ $__comment->user?->name ?? '(inconnu)' }}
                                                @if($__comment->created_at)
                                                    · {{ $__comment->created_at->timezone('America/Toronto')->format('d/m/Y H:i') }}
                                                @endif
                                            </div>
                                            <div class="academy-comment-body prose academy-richtext">{!! $__comment->renderedBody() !!}</div>

                                            @if($__canModerate || (auth()->check() && (int) $__comment->user_id === (int) auth()->id()))
                                                <details class="academy-comment-del">
                                                    <summary>Supprimer</summary>
                                                    <form method="POST" action="{{ route('academy.items.comments.delete', [$course, $lesson, $item->id, $__comment->id]) }}" class="mt-2">
                                                        @csrf
                                                        <p class="mb-2" style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280);">Confirmer la suppression de ce commentaire ?</p>
                                                        <x-core::button type="submit" variant="danger" size="sm">Oui, supprimer</x-core::button>
                                                    </form>
                                                </details>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-muted" style="font-size: 0.9rem;">Aucun commentaire pour l'instant.@if($__canEngage) Soyez le premier à en publier un.@endif</p>
                                    @endforelse

                                    @if($__canEngage)
                                        <form method="POST" action="{{ route('academy.items.comments.store', [$course, $lesson, $item->id]) }}" class="academy-comment-form">
                                            @csrf
                                            {{-- Honeypot anti-bot (caché, doit rester vide) - vérifié serveur. --}}
                                            <div style="position: absolute; left: -9999px; top: -9999px;" aria-hidden="true">
                                                <label>Ne pas remplir ce champ
                                                    <input type="text" name="{{ \Modules\Academy\Services\ItemEngagementService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                </label>
                                            </div>
                                            <label for="comment-body-{{ $item->id }}" class="visually-hidden">Votre commentaire sur : {{ $item->title ?? 'cet élément' }}</label>
                                            <textarea id="comment-body-{{ $item->id }}" name="body" rows="3" maxlength="{{ \Modules\Academy\Services\ItemEngagementService::COMMENT_MAX }}" required class="academy-forum-text" placeholder="Partagez un commentaire (2000 caractères max)"></textarea>
                                            <div class="mt-2">
                                                <x-core::button type="submit" variant="primary" size="sm">Publier le commentaire</x-core::button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            </div>
                            @php unset($__ratingStat, $__ratingCount, $__ratingAvg, $__userRating, $__itemComments, $__canEngage, $__canModerate); @endphp
                        @endif

                    </div>
                @empty
                    <p class="text-muted">Cette leçon ne contient pas encore de contenu.</p>
                @endforelse
                </div>{{-- /lesson-classic-view --}}
                @endif {{-- /else du drapeau deck_mode (vue classique) --}}
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

{{-- ══ Tuteur IA (drapeau academy.ai_tutor_enabled + inscrit/staff) ══ --}}
{{-- ACTION: Injecte le panneau tuteur flottant si le drapeau est actif  --}}
{{-- RAISON: Gating serveur dans le composant TutorChat (anti-IDOR)      --}}
@if(config('academy.ai_tutor_enabled', false) && auth()->check() && ($isEnrolled || auth()->user()->isSuperAdmin()))
    @livewire('academy.tutor-chat', ['lesson' => $lesson, 'course' => $course], key('tutor-' . $lesson->id))
@endif

@endsection
