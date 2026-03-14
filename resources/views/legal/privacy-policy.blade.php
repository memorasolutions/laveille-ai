{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('layouts.legal')
@section('title', __('Politique de confidentialité'))
@section('content')
@php
    $locale = app()->getLocale();
    $company = $config['company'];
    $doc = $config['documents']['privacy_policy'];
    $categories = $config['categories'];
    $rights = $config['rights'];
    $jurisdictions = $config['jurisdictions'];
    $rightsLabels = [
        'access' => __('Droit d\'accès'),
        'rectification' => __('Droit de rectification'),
        'erasure' => __('Droit à l\'effacement'),
        'portability' => __('Droit à la portabilité'),
        'opposition' => __('Droit d\'opposition'),
        'limitation' => __('Droit à la limitation du traitement'),
        'withdrawal' => __('Droit de retrait du consentement'),
    ];
@endphp

<div class="prose max-w-none mx-auto">
    <h1 id="top">{{ __('Politique de confidentialité') }}</h1>
    <p class="text-sm text-gray-500">
        <strong>{{ __('Version') }} :</strong> {{ $doc['version'] }}<br>
        <strong>{{ __('Dernière mise à jour') }} :</strong> {{ \Carbon\Carbon::parse($doc['updated_at'])->translatedFormat('d F Y') }}
    </p>

    <nav class="my-8">
        <h2>{{ __('Table des matières') }}</h2>
        <ol class="list-decimal list-inside space-y-1">
            <li><a href="#introduction">{{ __('Introduction et lois applicables') }}</a></li>
            <li><a href="#controller">{{ __('Responsable du traitement') }}</a></li>
            <li><a href="#data-collected">{{ __('Données personnelles collectées') }}</a></li>
            <li><a href="#legal-bases">{{ __('Fondements juridiques') }}</a></li>
            <li><a href="#purposes">{{ __('Finalités du traitement') }}</a></li>
            <li><a href="#cookies">{{ __('Cookies et traceurs') }}</a></li>
            <li><a href="#sharing">{{ __('Communication et transferts') }}</a></li>
            <li><a href="#retention">{{ __('Durée de conservation') }}</a></li>
            <li><a href="#rights">{{ __('Vos droits') }}</a></li>
            <li><a href="#security">{{ __('Mesures de sécurité') }}</a></li>
            <li><a href="#minors">{{ __('Mineurs') }}</a></li>
            <li><a href="#contact">{{ __('Contact, DPO et autorités') }}</a></li>
        </ol>
    </nav>

    <h2 id="introduction">{{ __('1. Introduction et lois applicables') }}</h2>
    <p>{{ __('Cette politique explique la façon dont nous collectons, utilisons et protégeons vos données personnelles, conformément aux lois suivantes :') }}</p>
    <ul class="ml-6 list-disc">
        <li>{{ __('RGPD (Règlement général sur la protection des données, UE)') }}</li>
        <li>{{ __('Loi 25 (Loi modernisant des dispositions législatives en matière de protection des renseignements personnels, Québec)') }}</li>
        <li>{{ __('LPRPDE / PIPEDA (Loi sur la protection des renseignements personnels et les documents électroniques, Canada)') }}</li>
        <li>{{ __('Directive ePrivacy (Europe)') }}</li>
    </ul>

    <h2 id="controller">{{ __('2. Responsable du traitement') }}</h2>
    <p>
        <strong>{{ $company['name'] }}</strong><br>
        {{ $company['address'] }}<br>
        {{ __('Délégué à la protection des données (DPO)') }} : {{ $company['dpo_name'] }}<br>
        {{ __('Courriel') }} : <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a>
    </p>

    <h2 id="data-collected">{{ __('3. Données personnelles collectées') }}</h2>
    <ul class="list-disc ml-6">
        <li>{{ __('Données d\'identité (nom, prénom, courriel)') }}</li>
        <li>{{ __('Données de connexion (adresse IP, identifiants de session)') }}</li>
        <li>{{ __('Données de navigation (pages consultées, préférences)') }}</li>
        <li>{{ __('Données de consentement et choix de cookies') }}</li>
    </ul>

    <h2 id="legal-bases">{{ __('4. Fondements juridiques') }}</h2>
    <ul class="list-disc ml-6">
        <li><strong>{{ __('Consentement') }}</strong> : {{ __('pour les cookies non essentiels et le marketing') }}</li>
        <li><strong>{{ __('Exécution de contrat') }}</strong> : {{ __('pour la gestion de votre compte et la fourniture du service') }}</li>
        <li><strong>{{ __('Intérêt légitime') }}</strong> : {{ __('pour la sécurité et l\'amélioration du service') }}</li>
        <li><strong>{{ __('Obligation légale') }}</strong> : {{ __('pour la conservation requise par la loi') }}</li>
    </ul>

    <h2 id="purposes">{{ __('5. Finalités du traitement') }}</h2>
    <ul class="list-disc ml-6">
        <li>{{ __('Gestion des comptes et fourniture du service') }}</li>
        <li>{{ __('Sécurité et prévention de la fraude') }}</li>
        <li>{{ __('Analyses statistiques et amélioration du service') }}</li>
        <li>{{ __('Personnalisation de l\'expérience utilisateur') }}</li>
        <li>{{ __('Respect des obligations légales et réglementaires') }}</li>
    </ul>

    <h2 id="cookies">{{ __('6. Cookies et traceurs') }}</h2>
    <p>{{ __('Des cookies de différentes catégories sont utilisés sur notre site. Vous pouvez gérer vos préférences à tout moment via notre bannière de consentement.') }}</p>
    <div class="overflow-x-auto">
        <table class="min-w-full border text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-3 py-2 text-left">{{ __('Catégorie') }}</th>
                    <th class="border px-3 py-2 text-left">{{ __('Cookie') }}</th>
                    <th class="border px-3 py-2 text-left">{{ __('Fournisseur') }}</th>
                    <th class="border px-3 py-2 text-left">{{ __('Finalité') }}</th>
                    <th class="border px-3 py-2 text-left">{{ __('Durée') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($categories as $catKey => $category)
                @foreach($category['cookies'] as $cookie)
                <tr>
                    <td class="border px-3 py-1">{{ $locale === 'fr' ? $category['label_fr'] : $category['label_en'] }}</td>
                    <td class="border px-3 py-1 font-mono text-xs">{{ $cookie['name'] }}</td>
                    <td class="border px-3 py-1">{{ $cookie['provider'] }}</td>
                    <td class="border px-3 py-1">{{ $locale === 'fr' ? $cookie['purpose_fr'] : $cookie['purpose_en'] }}</td>
                    <td class="border px-3 py-1">{{ $cookie['duration'] }}</td>
                </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-sm mt-2">
        {{ __('Pour plus de détails, consultez notre') }} <a href="{{ url('/cookie-policy') }}">{{ __('politique des cookies') }}</a>.
    </p>

    <h2 id="sharing">{{ __('7. Communication et transferts de données') }}</h2>
    <ul class="list-disc ml-6">
        <li>{{ __('Partage avec des prestataires de service (hébergement, paiement, analyse) dans le cadre strict de la fourniture du service') }}</li>
        <li>{{ __('Transferts internationaux (hors Québec, Canada ou UE) uniquement avec des garanties appropriées (clauses contractuelles types, décisions d\'adéquation)') }}</li>
        <li>{{ __('Aucune vente de données personnelles à des tiers') }}</li>
    </ul>

    <h2 id="retention">{{ __('8. Durée de conservation') }}</h2>
    <ul class="list-disc ml-6">
        <li>{{ __('Données de compte : pendant la durée de la relation contractuelle, puis archivage légal') }}</li>
        <li>{{ __('Données de connexion : 12 mois maximum') }}</li>
        <li>{{ __('Cookies : selon les durées indiquées dans le tableau ci-dessus') }}</li>
        <li>{{ __('Preuves de consentement : 5 ans (conformément au RGPD art. 7)') }}</li>
        <li>{{ __('Demandes d\'exercice de droits : durée du traitement + 3 ans en archivage') }}</li>
    </ul>

    <h2 id="rights">{{ __('9. Vos droits') }}</h2>
    <p>{{ __('Conformément aux lois applicables, vous disposez des droits suivants :') }}</p>
    <ul class="list-disc ml-6">
        @foreach($rights['types'] as $right)
            <li>{{ $rightsLabels[$right] ?? $right }}</li>
        @endforeach
    </ul>
    <p>
        {{ __('Pour exercer vos droits, contactez notre DPO à l\'adresse') }}
        <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a>.
        {{ __('Nous répondrons dans un délai de') }}
        <strong>{{ $rights['response_delay_days'] }} {{ __('jours') }}</strong>.
        {{ __('Certaines demandes peuvent nécessiter la vérification de votre identité.') }}
    </p>

    <h2 id="security">{{ __('10. Mesures de sécurité') }}</h2>
    <p>{{ __('Nous mettons en place des mesures techniques et organisationnelles pour protéger vos données :') }}</p>
    <ul class="list-disc ml-6">
        <li>{{ __('Chiffrement des données en transit (TLS) et au repos') }}</li>
        <li>{{ __('Contrôles d\'accès stricts et authentification') }}</li>
        <li>{{ __('Audits de sécurité réguliers') }}</li>
        <li>{{ __('Formation du personnel à la protection des données') }}</li>
    </ul>

    <h2 id="minors">{{ __('11. Mineurs') }}</h2>
    <p>{{ __('Nos services ne sont pas destinés aux personnes de moins de 14 ans. Nous ne collectons pas sciemment de données auprès de mineurs sans le consentement parental requis par la loi applicable.') }}</p>

    <h2 id="contact">{{ __('12. Contact, DPO et autorités de contrôle') }}</h2>
    <p>{{ __('Pour toute question ou exercice de vos droits :') }}</p>
    <ul class="list-disc ml-6">
        <li><strong>{{ __('DPO') }} :</strong> {{ $company['dpo_name'] }} - <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a></li>
        <li><strong>{{ __('Adresse') }} :</strong> {{ $company['address'] }}</li>
    </ul>
    <p class="mt-4">{{ __('Vous pouvez également contacter les autorités de contrôle compétentes :') }}</p>
    <ul class="list-disc ml-6">
        @foreach($jurisdictions as $jKey => $jurisdiction)
            @if(!empty($jurisdiction['authorities']))
            <li>
                <strong>{{ $jurisdiction['label'] }} :</strong>
                @foreach($jurisdiction['authorities'] as $name => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener">{{ $name }}</a>@if(!$loop->last), @endif
                @endforeach
            </li>
            @endif
        @endforeach
    </ul>

    <p class="text-xs mt-8 border-t pt-4 text-gray-500">
        {{ __('Version') }} {{ $doc['version'] }} -
        {{ __('Dernière mise à jour') }} : {{ \Carbon\Carbon::parse($doc['updated_at'])->translatedFormat('d F Y') }}
    </p>
</div>
@endsection
