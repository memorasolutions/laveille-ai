<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Préférences de notification - ' . config('app.name'))
@section('meta_description', "Gérez les courriels de notification que vous recevez de l'Académie IA.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Préférences de notification'])
@endsection

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 12px;">Préférences de notification</h1>
                    <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280);">
                        Choisissez les courriels que vous souhaitez recevoir de l'Académie. Vous pouvez modifier ces réglages à tout moment.
                    </p>

                    @livewire('academy.notification-preferences')

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
