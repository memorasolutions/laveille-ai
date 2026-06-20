<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Mon espace - ' . config('app.name'))
@section('meta_description', "Votre espace personnel de l'Académie IA : vos formations, votre progression et vos certificats.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Mon espace'])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">Mon espace</h1>
                    <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280);">
                        Retrouvez ici vos formations, votre progression et, si vous en gérez, vos cours.
                    </p>

                    @livewire('academy.dashboard')

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
