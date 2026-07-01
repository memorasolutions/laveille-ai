<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Gérer - ' . $course->title . ' - ' . config('app.name'))
@section('meta_description', "Éditeur de cours de l'Académie IA : métadonnées, chapitres et leçons.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Gérer'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <nav aria-label="Fil d'Ariane" style="margin-bottom: 14px; font-size: 0.85rem; color: var(--sys-text-muted, #6B7280);">
                        <a href="{{ route('academy.dashboard') }}" style="color: var(--sys-action-primary, #064E5A); text-decoration: underline;">Académie</a>
                        <span aria-hidden="true"> / </span>
                        <span>{{ $course->title }}</span>
                        <span aria-hidden="true"> / </span>
                        <span aria-current="page">Gérer</span>
                    </nav>

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">
                        Gérer la formation
                    </h1>
                    <p style="margin-bottom: 18px; color: var(--sys-text-muted, #6B7280);">
                        Modifiez les métadonnées, organisez les chapitres et les leçons. Chaque modification est enregistrée et sécurisée côté serveur.
                    </p>

                    {{-- D1 — Accès au tableau de bord d'analytics du cours. Gâté
                         manageEnrollments ; la vraie garde reste authorize() serveur.
                         F15 - Export (.json) de la structure du cours, gâté manageStructure
                         (la vraie garde reste authorize() dans le contrôleur). --}}
                    <p class="d-flex flex-wrap gap-2" style="margin-bottom: 28px;">
                        @can('manageEnrollments', $course)
                            <x-core::button
                                :href="route('academy.courses.analytics', $course->slug)"
                                variant="secondary" size="sm">
                                <span aria-hidden="true">📊</span> Voir les statistiques
                            </x-core::button>
                            {{-- F23 - Rapports et journaux (participation + journal d'activité).
                                 Gâté manageEnrollments ; la vraie garde reste authorize() serveur. --}}
                            <x-core::button
                                :href="route('academy.courses.reports', $course->slug)"
                                variant="secondary" size="sm">
                                <span aria-hidden="true">📋</span> Rapports et journaux
                            </x-core::button>
                        @endcan
                        @can('manageStructure', $course)
                            <x-core::button
                                :href="route('academy.courses.export', $course->slug)"
                                variant="ghost" size="sm">
                                <span aria-hidden="true">💾</span> Exporter le cours (.json)
                            </x-core::button>
                        @endcan
                    </p>

                    @livewire('academy.course-editor', ['course' => $course])

                    {{-- PHASE E (E2) - Devoirs (assignments) du cours. Rendu si la
                         personne peut gérer la structure (admin OU owner/instructor/editor) ;
                         la correction + le carnet de notes à l'intérieur sont en plus
                         gâtés manageEnrollments. Vraie garde = authorize() serveur. --}}
                    @can('manageStructure', $course)
                        <div style="margin-top: 28px;">
                            <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 8px;">
                                Devoirs et carnet de notes
                            </h2>
                            @livewire('academy.course-assignments', ['course' => $course])
                        </div>
                    @endcan

                    {{-- F22 - Compétences (résultats) : association au cours/items (gâté
                         manageStructure) + suivi d'acquisition par étudiant (le rapport
                         à l'intérieur est en plus gâté manageEnrollments). Vraie garde =
                         authorize() serveur (CourseCompetencies). --}}
                    @can('manageStructure', $course)
                        <div style="margin-top: 28px;">
                            <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 8px;">
                                Compétences et résultats
                            </h2>
                            @livewire('academy.course-competencies', ['course' => $course])
                        </div>
                    @endcan

                    {{-- Séances en direct (visioconférence native). Gâté manageStructure
                         + drapeau academy.live_sessions_enabled (OFF => section absente).
                         Le composant re-vérifie l'autorisation ET le drapeau à chaque
                         mutation (anti-IDOR). Vraie garde = authorize() serveur. --}}
                    @can('manageStructure', $course)
                        @if(config('academy.live_sessions_enabled'))
                            <div style="margin-top: 28px;">
                                <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 8px;">
                                    Séances en direct
                                </h2>
                                @livewire('academy.live-sessions-manager', ['course' => $course])
                            </div>
                        @endif
                    @endcan

                    {{-- ESSAI (type « Essay ») - Correction des essais de quiz en attente.
                         Gâté manageEnrollments (admin OU owner/instructor) ; tentative
                         re-résolue scopée au cours (anti-IDOR). Vraie garde = serveur. --}}
                    @can('manageEnrollments', $course)
                        <div style="margin-top: 28px;">
                            <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 8px;">
                                Essais à corriger
                            </h2>
                            @livewire('academy.essay-grading', ['course' => $course])
                        </div>
                    @endcan

                    {{-- PHASE 4 (FE-4) - Inscriptions + équipe. Rendu seulement si la
                         personne peut gérer les inscriptions (admin OU owner/instructor) ;
                         la section « équipe » à l'intérieur est en plus gâtée par
                         manageRoles (owner/admin). Vraie garde = authorize() serveur. --}}
                    @can('manageEnrollments', $course)
                        <div style="margin-top: 28px;">
                            <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 8px;">
                                Inscriptions et équipe
                            </h2>
                            @livewire('academy.course-roster', ['course' => $course])
                        </div>
                    @endcan

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
