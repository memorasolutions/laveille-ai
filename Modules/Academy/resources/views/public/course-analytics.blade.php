<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Statistiques - ' . $course->title . ' - ' . config('app.name'))
@section('meta_description', "Tableau de bord d'analytics du cours : inscrits, complétion, décrochage par leçon et activité récente.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Statistiques'])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <nav aria-label="Fil d'Ariane" style="margin-bottom: 14px; font-size: 0.85rem; color: var(--sys-text-muted, #6B7280);">
                        <a href="{{ route('academy.dashboard') }}" style="color: var(--sys-action-primary, #064E5A);">Académie</a>
                        <span aria-hidden="true"> / </span>
                        <a href="{{ route('academy.courses.manage', $course->slug) }}" style="color: var(--sys-action-primary, #064E5A);">{{ $course->title }}</a>
                        <span aria-hidden="true"> / </span>
                        <span aria-current="page">Statistiques</span>
                    </nav>

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">
                        Statistiques de la formation
                    </h1>
                    <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280);">
                        Inscrits, taux de complétion, point de décrochage par leçon et activité récente. Toutes les données sont calculées et sécurisées côté serveur, pour ce cours uniquement.
                    </p>

                    @livewire('academy.course-analytics', ['course' => $course])

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
