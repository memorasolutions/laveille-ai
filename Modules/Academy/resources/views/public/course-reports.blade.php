<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Rapports et journaux - ' . $course->title . ' - ' . config('app.name'))
@section('meta_description', "Rapports internes du cours : participation par étudiant et journal d'activité. Données calculées et sécurisées côté serveur.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Rapports'])
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
                        <a href="{{ route('academy.courses.manage', $course->slug) }}" style="color: var(--sys-action-primary, #064E5A); text-decoration: underline;">{{ $course->title }}</a>
                        <span aria-hidden="true"> / </span>
                        <span aria-current="page">Rapports</span>
                    </nav>

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">
                        Rapports et journaux
                    </h1>
                    <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280);">
                        Participation par étudiant et journal d'activité du cours. Toutes les données sont calculées et sécurisées côté serveur, pour ce cours uniquement.
                    </p>

                    @livewire('academy.course-reports', ['course' => $course])

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
