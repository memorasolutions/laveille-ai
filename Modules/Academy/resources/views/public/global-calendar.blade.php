<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Mon calendrier - Académie - ' . config('app.name'))
@section('meta_description', "Vue mensuelle de vos échéances de devoirs, séances en direct et événements à travers tous vos cours de l'Académie.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Mon calendrier'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">Mon calendrier</h1>
                    <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280);">
                        Toutes vos échéances et séances en direct, à travers tous vos cours, en un seul endroit.
                    </p>

                    @livewire('academy.global-calendar')

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
