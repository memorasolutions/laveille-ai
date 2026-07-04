<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Catégories de cours - Réglages admin - ' . config('app.name'))
@section('meta_description', "Créer, renommer et réordonner les catégories de cours de l'Académie.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Catégories de cours (admin)'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">Catégories de cours</h1>
                    <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280);">
                        Taxonomie partagée par toute l'Académie. Un formateur choisit une catégorie existante pour ses cours depuis l'éditeur de cours.
                    </p>

                    @livewire('academy.course-category-manager')

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
