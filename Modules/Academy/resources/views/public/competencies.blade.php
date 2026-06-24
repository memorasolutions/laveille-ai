<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Compétences - ' . config('app.name'))
@section('meta_description', "Gérez votre référentiel de compétences (résultats d'apprentissage) de l'Académie IA et suivez leur acquisition par vos étudiants.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Compétences'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

            <nav aria-label="Fil d'Ariane" style="margin-bottom: 14px; font-size: 0.85rem; color: var(--sys-text-muted, #6B7280);">
                <a href="{{ route('academy.dashboard') }}" style="color: var(--sys-action-primary, #064E5A); text-decoration: underline;">Académie</a>
                <span aria-hidden="true"> / </span>
                <span aria-current="page">Compétences</span>
            </nav>

            <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">
                <span aria-hidden="true">🎯</span> Compétences
            </h1>
            <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280); max-width: 70ch;">
                Définissez vos compétences (résultats d'apprentissage), associez-les à vos cours et à leurs items dans l'éditeur de cours, puis suivez leur acquisition par vos étudiants. L'acquisition est dérivée automatiquement de l'achèvement et, si vous fixez un seuil, des notes obtenues.
            </p>

            @livewire('academy.competency-manager')

        </div>
    </div>
</section>
@endsection
