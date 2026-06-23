<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Importer un cours - ' . config('app.name'))
@section('meta_description', "Importez un cours de l'Académie IA depuis un fichier de sauvegarde (.json). Le cours est recréé en brouillon, prêt à être personnalisé.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Importer un cours'])
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
                        <span aria-current="page">Importer un cours</span>
                    </nav>

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">
                        Importer un cours
                    </h1>
                    <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280);">
                        Téléversez un fichier de sauvegarde (.json) exporté depuis l'Académie. Le cours sera recréé en brouillon et vous en deviendrez le propriétaire. Aucune donnée d'étudiant n'est importée : seule la structure (chapitres, leçons, contenus, devoirs, barèmes) est restaurée.
                    </p>

                    @livewire('academy.course-import')

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
