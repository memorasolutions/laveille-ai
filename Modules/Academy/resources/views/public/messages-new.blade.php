<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Nouvelle conversation - ' . config('app.name'))
@section('meta_description', "Démarrer une conversation avec un formateur ou un apprenant de vos cours.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Nouvelle conversation'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">
                    @livewire('academy.direct-messages.new-conversation')
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
