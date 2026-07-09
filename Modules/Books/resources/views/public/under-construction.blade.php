{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Squelette calqué sur Modules/Academy/resources/views/public/under-construction.blade.php --}}
@extends(fronttheme_layout())

@section('title', 'Livres - bientôt disponible · ' . config('app.name'))
@section('meta_description', "La bibliothèque de livres de La veille de Stef est en construction. Nous travaillons activement à son lancement public sur laveille.ai.")
@section('page_noindex', true)

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Livres'])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 16px;">Bibliothèque en construction</h1>

                    <p>Notre bibliothèque de livres est en préparation. Nous y travaillons activement pour t'offrir des guides clairs et concrets sur l'IA et le numérique. Reviens bientôt !</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">Avancement prévu</h2>
                    <ul>
                        <li><strong>Conception</strong> - terminée</li>
                        <li><strong>Développement</strong> - en cours</li>
                        <li><strong>Lancement public</strong> - à venir</li>
                    </ul>

                    <p style="margin-top: 28px;">
                        <x-core::button :href="route('home')" variant="secondary">Retour à l'accueil</x-core::button>
                    </p>

                </article>
            </div>
        </div>
    </div>
</section>
@endsection
