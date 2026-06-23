<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Calendrier - ' . $course->title . ' - ' . config('app.name'))
@section('meta_description', 'Calendrier des echeances et evenements de la formation : devoirs, examens, sessions en direct.')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Calendrier'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <nav aria-label="Fil d'Ariane"
                         style="margin-bottom: 14px; font-size: 0.85rem; color: var(--sys-text-muted, #6B7280);">
                        <a href="{{ route('academy.dashboard') }}"
                           style="color: var(--sys-action-primary, #064E5A); text-decoration: underline;">Academie</a>
                        <span aria-hidden="true"> / </span>
                        <a href="{{ route('academy.courses.show', $course->slug) }}"
                           style="color: var(--sys-action-primary, #064E5A); text-decoration: underline;">{{ $course->title }}</a>
                        <span aria-hidden="true"> / </span>
                        <span aria-current="page">Calendrier</span>
                    </nav>

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 8px;">
                        Calendrier de la formation
                    </h1>
                    <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280); max-width: 620px;">
                        Toutes les echeances et evenements de ce cours, calcules et securises cote serveur.
                        Les devoirs incluent automatiquement leur date de remise.
                    </p>

                    @livewire('academy.course-calendar', ['course' => $course])

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
