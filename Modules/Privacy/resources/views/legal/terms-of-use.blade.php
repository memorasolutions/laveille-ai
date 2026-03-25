{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('privacy::layouts.legal')
@section('title', __('Conditions d\'utilisation'))
@section('content')
@php
    $company = $config['company'];
    $doc = $config['documents']['terms'];
@endphp

<div class="prose max-w-none mx-auto">
    <h1>{{ __('Conditions d\'utilisation') }}</h1>
    <p class="text-sm text-gray-500">
        <strong>{{ __('Version') }}&nbsp;:</strong> {{ $doc['version'] }}<br>
        <strong>{{ __('Date d\'entrée en vigueur') }}&nbsp;:</strong> {{ \Carbon\Carbon::parse($doc['updated_at'])->translatedFormat('d F Y') }}
    </p>

    <nav class="my-8">
        <h2>{{ __('Table des matières') }}</h2>
        <ol class="list-decimal list-inside space-y-1">
            <li><a href="#acceptation">{{ __('Acceptation des conditions') }}</a></li>
            <li><a href="#service">{{ __('Description du service') }}</a></li>
            <li><a href="#compte">{{ __('Création et responsabilité du compte') }}</a></li>
            <li><a href="#usage">{{ __('Utilisation acceptable') }}</a></li>
            <li><a href="#propriete">{{ __('Propriété intellectuelle') }}</a></li>
            <li><a href="#contenu">{{ __('Contenu généré par l\'utilisateur') }}</a></li>
            <li><a href="#paiement">{{ __('Paiement et facturation') }}</a></li>
            <li><a href="#disponibilite">{{ __('Disponibilité du service') }}</a></li>
            <li><a href="#responsabilite">{{ __('Limitation de responsabilité') }}</a></li>
            <li><a href="#resiliation">{{ __('Résiliation') }}</a></li>
            <li><a href="#litige">{{ __('Règlement des litiges') }}</a></li>
            <li><a href="#modification">{{ __('Modification des conditions') }}</a></li>
            <li><a href="#contact">{{ __('Coordonnées') }}</a></li>
        </ol>
    </nav>

    <h2 id="acceptation">{{ __('1. Acceptation des conditions') }}</h2>
    <p>{{ __('En accédant au service :company ou en l\'utilisant, vous acceptez d\'être lié par les présentes conditions d\'utilisation. Si vous n\'acceptez pas ces conditions, veuillez ne pas utiliser notre plateforme.', ['company' => $company['name']]) }}</p>

    <h2 id="service">{{ __('2. Description du service') }}</h2>
    <p>{{ __(':company est une plateforme logicielle en tant que service (SaaS) qui fournit des outils de gestion en ligne. Les fonctionnalités disponibles dépendent du plan d\'abonnement choisi.', ['company' => $company['name']]) }}</p>

    <h2 id="compte">{{ __('3. Création et responsabilité du compte') }}</h2>
    <p>{{ __('Vous devez créer un compte pour accéder au service. Vous êtes responsable de&nbsp;:') }}</p>
    <ul class="list-disc ml-6">
        <li>{{ __('La confidentialité de vos identifiants de connexion') }}</li>
        <li>{{ __('Toute activité effectuée via votre compte') }}</li>
        <li>{{ __('L\'exactitude des informations fournies lors de l\'inscription') }}</li>
    </ul>

    <h2 id="usage">{{ __('4. Utilisation acceptable') }}</h2>
    <p>{{ __('Il est strictement interdit d\'utiliser le service pour&nbsp;:') }}</p>
    <ul class="list-disc ml-6">
        <li>{{ __('Contrevenir aux lois applicables, incluant la Loi 25 et le RGPD') }}</li>
        <li>{{ __('Diffuser, transmettre ou stocker du contenu illégal, abusif ou offensant') }}</li>
        <li>{{ __('Tenter d\'accéder à des systèmes ou données sans autorisation') }}</li>
        <li>{{ __('Nuire à la sécurité, à l\'intégrité ou à la disponibilité du service') }}</li>
        <li>{{ __('Envoyer du spam ou tout contenu commercial non sollicité') }}</li>
        <li>{{ __('Utiliser le service pour du minage de cryptomonnaies ou toute activité consommant des ressources de manière abusive') }}</li>
    </ul>

    <h2 id="propriete">{{ __('5. Propriété intellectuelle') }}</h2>
    <p>{{ __('L\'ensemble du contenu, des marques, logos et éléments visuels du service sont la propriété exclusive de :company ou de ses concédants de licence. Aucune disposition des présentes ne vous confère un droit de propriété intellectuelle sur ces éléments.', ['company' => $company['name']]) }}</p>

    <h2 id="contenu">{{ __('6. Contenu généré par l\'utilisateur') }}</h2>
    <p>{{ __('En publiant du contenu sur la plateforme, vous accordez à :company une licence mondiale, non exclusive et gratuite pour utiliser, reproduire et afficher ce contenu dans le cadre de l\'exploitation du service.', ['company' => $company['name']]) }}</p>
    <p>{{ __('Nous nous réservons le droit de modérer ou retirer tout contenu contraire aux présentes conditions ou aux lois en vigueur.') }}</p>

    <h2 id="paiement">{{ __('7. Paiement et facturation') }}</h2>
    <ul class="list-disc ml-6">
        <li>{{ __('Le service fonctionne par abonnement mensuel ou annuel') }}</li>
        <li>{{ __('Les frais sont facturés à l\'avance selon le plan choisi') }}</li>
        <li>{{ __('Sauf indication contraire ou obligation légale, aucun remboursement ne sera effectué après paiement') }}</li>
        <li>{{ __('Nous nous réservons le droit de modifier les tarifs avec un préavis de 30 jours') }}</li>
    </ul>

    <h2 id="disponibilite">{{ __('8. Disponibilité du service') }}</h2>
    <p>{{ __('Nous nous efforçons d\'assurer une disponibilité continue du service. Cependant, des interruptions peuvent survenir pour maintenance ou pour des causes indépendantes de notre volonté. Les engagements de disponibilité spécifiques, le cas échéant, sont décrits dans une convention de niveau de service (SLA) séparée.') }}</p>

    <h2 id="responsabilite">{{ __('9. Limitation de responsabilité') }}</h2>
    <p>{{ __('Dans les limites permises par la loi applicable, :company décline toute responsabilité pour les dommages directs ou indirects résultant de l\'utilisation ou de l\'impossibilité d\'utiliser le service, incluant mais sans s\'y limiter&nbsp;: la perte de données, le manque à gagner ou l\'interruption d\'activité.', ['company' => $company['name']]) }}</p>

    <h2 id="resiliation">{{ __('10. Résiliation') }}</h2>
    <p>{{ __('Vous pouvez résilier votre compte à tout moment depuis les paramètres de votre profil. Nous pouvons suspendre ou résilier votre accès en cas de manquement aux présentes conditions, sans préjudice de tout autre recours. Les données seront conservées conformément à notre politique de confidentialité.') }}</p>

    <h2 id="litige">{{ __('11. Règlement des litiges') }}</h2>
    <p>{{ __('Les présentes conditions sont régies par les lois de la province de Québec et les lois fédérales du Canada applicables. Tout litige sera soumis à la compétence exclusive des tribunaux de la province de Québec, district judiciaire de Montréal.') }}</p>

    <h2 id="modification">{{ __('12. Modification des conditions') }}</h2>
    <p>{{ __('Nous pouvons modifier ces conditions à tout moment. Vous serez notifié de tout changement substantiel par courriel ou via le service. La poursuite de l\'utilisation du service après notification vaut acceptation des nouvelles conditions.') }}</p>

    <h2 id="contact">{{ __('13. Coordonnées') }}</h2>
    <p>{{ __('Pour toute question concernant ces conditions&nbsp;:') }}</p>
    <ul class="list-none ml-0">
        <li><strong>{{ $company['name'] }}</strong></li>
        <li>{{ $company['address'] }}</li>
        <li>{{ __('Courriel') }}&nbsp;: <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a></li>
        <li>{{ __('Téléphone') }}&nbsp;: {{ $company['phone'] }}</li>
    </ul>

    <p class="text-xs mt-8 border-t pt-4 text-gray-500">
        {{ __('Version') }} {{ $doc['version'] }} -
        {{ __('Date d\'entrée en vigueur') }}&nbsp;: {{ \Carbon\Carbon::parse($doc['updated_at'])->translatedFormat('d F Y') }}
    </p>
</div>
@endsection
