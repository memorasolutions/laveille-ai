<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Banque de questions - ' . config('app.name'))
@section('meta_description', "Gérez votre banque de questions réutilisables de l'Académie IA : catégories et questions des 4 types (choix multiple, vrai/faux, réponse courte, appariement).")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Banque de questions'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

            <nav aria-label="Fil d'Ariane" style="margin-bottom: 14px; font-size: 0.85rem; color: var(--sys-text-muted, #6B7280);">
                <a href="{{ route('academy.dashboard') }}" style="color: var(--sys-action-primary, #064E5A); text-decoration: underline;">Académie</a>
                <span aria-hidden="true"> / </span>
                <span aria-current="page">Banque de questions</span>
            </nav>

            <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">
                🏛️ Banque de questions
            </h1>
            <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280); max-width: 70ch;">
                Constituez une banque de questions réutilisables, organisée en catégories. Vos quiz de cours peuvent ensuite tirer un nombre choisi de questions dans une catégorie, sans ressaisie.
            </p>

            @livewire('academy.question-bank-manager')

        </div>
    </div>
</section>
@endsection
