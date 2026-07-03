<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Mes messages - ' . config('app.name'))
@section('meta_description', "Messagerie directe avec vos formateurs et apprenants de l'Académie.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Mes messages'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">
                    @livewire('academy.direct-messages.conversation-list')
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
