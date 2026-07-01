<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Test de positionnement - ' . $course->title . ' - ' . config('app.name'))

@section('meta_description', "Découvrez votre niveau en quelques questions et obtenez une recommandation de leçon de départ personnalisée pour " . $course->title . ".")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Test de positionnement'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">🎯 Test de positionnement</h1>
                    <p style="margin-bottom: 24px; color: var(--sys-text-muted, #6B7280);">
                        Répondez à quelques questions sur « {{ $course->title }} » : la difficulté s'ajuste à vos réponses, et nous vous recommandons où commencer.
                    </p>

                    @livewire('academy.placement-test', ['course' => $course])

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
