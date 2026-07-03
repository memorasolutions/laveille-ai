<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Conversation - ' . config('app.name'))
@section('meta_description', "Fil de messagerie directe de l'Académie.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Conversation'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">
                    @livewire('academy.direct-messages.conversation-thread', ['conversation' => $conversation])
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
