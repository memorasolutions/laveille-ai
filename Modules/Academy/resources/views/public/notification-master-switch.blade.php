<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Notifications courriel - Réglages admin - ' . config('app.name'))
@section('meta_description', "Activer ou désactiver l'interrupteur maître des notifications courriel de l'Académie.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Notifications courriel (admin)'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">Notifications courriel de l'Académie</h1>
                    <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280);">
                        Interrupteur maître réservé aux gestionnaires. Il pilote l'envoi des courriels de notification (annonces, corrections, rappels) pour l'ensemble de la plateforme.
                    </p>

                    @livewire('academy.notification-master-switch')

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
