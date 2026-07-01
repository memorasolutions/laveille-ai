<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Réviser - ' . config('app.name'))
@section('meta_description', "Session de révision espacée : renforcez ce que vous avez appris avec de courtes cartes reprogrammées au moment optimal.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Réviser'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">Révision espacée</h1>
                    <p style="margin-bottom: 24px; color: var(--sys-text-muted, #6B7280);">
                        De courtes cartes, reprogrammées au meilleur moment pour ancrer durablement vos apprentissages.
                    </p>

                    @livewire('academy.srs-reviewer')

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
