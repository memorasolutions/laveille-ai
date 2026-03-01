@extends('fronttheme::themes.gosass.layouts.app')

@section('title', __('Mentions légales').' - '.config('app.name'))
@section('description', __('Mentions légales et informations juridiques de').' '.config('app.name').'.')

@section('content')

<div class="cs_height_85 cs_height_lg_80"></div>
<div class="container text-center">
    <h2 class="cs_fs_50 cs_mb_15 wow fadeInDown">{{ __('Mentions légales') }}</h2>
    <p class="mb-0 wow fadeInUp">{{ __('Informations légales relatives à l\'utilisation') }}<br>{{ __('de la plateforme') }} {{ config('app.name') }}.</p>
</div>

<div class="cs_height_64 cs_height_lg_50"></div>
<div class="container">
    <div class="cs_radius_15 cs_white_bg p-4 p-lg-5 wow fadeIn">
        <h4 class="cs_mb_15">{{ __('Éditeur du site') }}</h4>
        <p>{{ __('Le site') }} {{ config('app.url') }} {{ __('est édité par') }} {{ config('app.name') }}.</p>
        <p>{{ __('Adresse') }} : {{ Settings::get('legal.company_address') ?: __('Non renseignée') }}<br>
        {{ __('Courriel') }} : {{ config('mail.from.address', 'contact@example.com') }}<br>
        {{ __('Directeur de la publication') }} : {{ Settings::get('legal.director_name') ?: __('Non renseigné') }}</p>

        <hr class="my-4">

        <h4 class="cs_mb_15">{{ __('Hébergement') }}</h4>
        <p>{{ __('Le site est hébergé par') }} {{ Settings::get('legal.hosting_name') ?: __('Non renseigné') }}.<br>
        {{ __('Adresse') }} : {{ Settings::get('legal.hosting_address') ?: __('Non renseignée') }}<br>
        {{ __('Téléphone') }} : {{ Settings::get('legal.hosting_phone') ?: __('Non renseigné') }}</p>

        <hr class="my-4">

        <h4 class="cs_mb_15">{{ __('Propriété intellectuelle') }}</h4>
        <p>{{ __('L\'ensemble des contenus présents sur le site (textes, images, logos, icônes, logiciels) est la propriété exclusive de') }} {{ config('app.name') }} {{ __('ou fait l\'objet d\'une autorisation d\'utilisation. Toute reproduction, représentation, modification ou diffusion, même partielle, est strictement interdite sans autorisation préalable écrite.') }}</p>

        <hr class="my-4">

        <h4 class="cs_mb_15">{{ __('Limitation de responsabilité') }}</h4>
        <p>{{ config('app.name') }} {{ __('s\'efforce de maintenir des informations exactes et à jour sur ce site. Toutefois, l\'éditeur ne saurait être tenu responsable des erreurs, omissions ou résultats obtenus suite à l\'utilisation de ces informations.') }}</p>
        <p>{{ config('app.name') }} {{ __('décline toute responsabilité quant au contenu des sites externes accessibles via des liens hypertextes présents sur ce site.') }}</p>

        <hr class="my-4">

        <h4 class="cs_mb_15">{{ __('Droit applicable') }}</h4>
        <p>{{ __('Les présentes mentions légales sont régies par le droit applicable au Québec et au Canada. Tout litige sera soumis à la compétence exclusive des tribunaux du Québec.') }}</p>
    </div>
</div>

<div class="cs_height_85 cs_height_lg_80"></div>

@endsection
