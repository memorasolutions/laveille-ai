<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Gabarits de diplômes - ' . config('app.name'))
@section('meta_description', "Créez et personnalisez vos gabarits de diplômes (Phase 1) : positionnement visuel des éléments, variables système/pédagogiques/organisationnelles, aperçu en direct.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Gabarits de diplômes'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

            <nav aria-label="Fil d'Ariane" style="margin-bottom: 14px; font-size: 0.85rem; color: var(--sys-text-muted, #6B7280);">
                <a href="{{ route('academy.dashboard') }}" style="color: var(--sys-action-primary, #064E5A); text-decoration: underline;">Académie</a>
                <span aria-hidden="true"> / </span>
                <span aria-current="page">Gabarits de diplômes</span>
            </nav>

            <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">
                <span aria-hidden="true">🎓</span> Gabarits de diplômes
            </h1>
            <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280); max-width: 70ch;">
                Positionnez et personnalisez les éléments de vos diplômes (logo, nom, titre du cours, date, signature, QR de vérification, texte libre) puis prévisualisez le rendu en direct avant de les enregistrer.
            </p>

            @livewire('academy.diploma-template-editor', ['templateId' => $templateId ?? null])

        </div>
    </div>
</section>
@endsection
