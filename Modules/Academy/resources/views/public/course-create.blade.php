<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Créer un cours - ' . config('app.name'))
@section('meta_description', "Créez un nouveau cours de l'Académie IA : titre, niveau, langue et accès. Le contenu se construit ensuite dans l'éditeur.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Créer un cours'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <nav aria-label="Fil d'Ariane" style="margin-bottom: 14px; font-size: 0.85rem; color: var(--sys-text-muted, #6B7280);">
                        <a href="{{ route('academy.dashboard') }}" style="color: var(--sys-action-primary, #064E5A); text-decoration: underline;">Académie</a>
                        <span aria-hidden="true"> / </span>
                        <span aria-current="page">Créer un cours</span>
                    </nav>

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">
                        Créer un cours
                    </h1>
                    <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280);">
                        Donnez un titre et quelques réglages de base. Votre cours sera créé en brouillon ; vous ajouterez ensuite chapitres, leçons et contenus dans l'éditeur.
                    </p>

                    @livewire('academy.course-create')

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
