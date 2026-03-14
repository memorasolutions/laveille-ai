{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('layouts.legal')
@section('title', __('Politique des cookies'))
@section('content')
@php
    $locale = app()->getLocale();
    $company = $config['company'];
    $doc = $config['documents']['cookie_policy'];
    $categories = $config['categories'];
    $expiration = $config['consent']['expiration'];
@endphp

<div class="prose max-w-none mx-auto">
    <h1>{{ __('Politique des cookies') }}</h1>
    <p class="text-sm text-gray-500">
        <strong>{{ __('Version') }} :</strong> {{ $doc['version'] }}<br>
        <strong>{{ __('Dernière mise à jour') }} :</strong> {{ $doc['updated_at'] }}
    </p>

    <nav class="my-8">
        <h2>{{ __('Table des matières') }}</h2>
        <ol class="list-decimal list-inside space-y-1">
            <li><a href="#what-are-cookies">{{ __('Que sont les cookies ?') }}</a></li>
            <li><a href="#why-we-use">{{ __('Pourquoi utilisons-nous des cookies ?') }}</a></li>
            <li><a href="#categories">{{ __('Catégories de cookies') }}</a></li>
            <li><a href="#manage-cookies">{{ __('Comment gérer les cookies') }}</a></li>
            <li><a href="#consent-duration">{{ __('Durée du consentement') }}</a></li>
            <li><a href="#third-party">{{ __('Cookies tiers') }}</a></li>
            <li><a href="#updates">{{ __('Mises à jour de cette politique') }}</a></li>
            <li><a href="#contact">{{ __('Contact') }}</a></li>
        </ol>
    </nav>

    <h2 id="what-are-cookies">{{ __('1. Que sont les cookies ?') }}</h2>
    <p>{{ __('Les cookies (ou témoins de connexion) sont de petits fichiers texte déposés sur votre appareil lorsque vous visitez notre site. Ils permettent au site de mémoriser vos actions et préférences sur une période donnée.') }}</p>

    <h2 id="why-we-use">{{ __('2. Pourquoi utilisons-nous des cookies ?') }}</h2>
    <ul class="list-disc ml-6">
        <li>{{ __('Assurer le fonctionnement technique du site (authentification, sécurité)') }}</li>
        <li>{{ __('Mémoriser vos préférences (langue, thème)') }}</li>
        <li>{{ __('Analyser le trafic et améliorer nos services') }}</li>
        <li>{{ __('Personnaliser votre expérience') }}</li>
    </ul>

    <h2 id="categories">{{ __('3. Catégories de cookies') }}</h2>
    @foreach($categories as $catKey => $category)
    <div class="mb-6">
        <h3>
            {{ $locale === 'fr' ? $category['label_fr'] : $category['label_en'] }}
            @if($category['required'])
                <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2 not-prose">{{ __('Obligatoire') }}</span>
            @endif
        </h3>
        @if(!empty($category['cookies']))
        <div class="overflow-x-auto">
            <table class="min-w-full border text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-3 py-2 text-left">{{ __('Nom') }}</th>
                        <th class="border px-3 py-2 text-left">{{ __('Fournisseur') }}</th>
                        <th class="border px-3 py-2 text-left">{{ __('Finalité') }}</th>
                        <th class="border px-3 py-2 text-left">{{ __('Durée') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($category['cookies'] as $cookie)
                    <tr>
                        <td class="border px-3 py-1 font-mono text-xs">{{ $cookie['name'] }}</td>
                        <td class="border px-3 py-1">{{ $cookie['provider'] }}</td>
                        <td class="border px-3 py-1">{{ $locale === 'fr' ? $cookie['purpose_fr'] : $cookie['purpose_en'] }}</td>
                        <td class="border px-3 py-1">{{ $cookie['duration'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endforeach

    <h2 id="manage-cookies">{{ __('4. Comment gérer les cookies') }}</h2>
    <h3>{{ __('Via notre bannière de consentement') }}</h3>
    <p>{{ __('Lors de votre première visite, notre bannière vous permet de choisir quelles catégories de cookies vous acceptez. Vous pouvez modifier vos choix à tout moment en cliquant sur le bouton de gestion des cookies en bas à droite de l\'écran.') }}</p>
    <h3>{{ __('Via les paramètres de votre navigateur') }}</h3>
    <p>{{ __('Vous pouvez configurer votre navigateur pour refuser les cookies ou être alerté lorsqu\'un cookie est déposé. Consultez l\'aide de votre navigateur pour connaître la procédure.') }}</p>

    <h2 id="consent-duration">{{ __('5. Durée du consentement par juridiction') }}</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full border text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-3 py-2 text-left">{{ __('Juridiction') }}</th>
                    <th class="border px-3 py-2 text-left">{{ __('Durée de validité (jours)') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expiration as $jurisdiction => $days)
                <tr>
                    <td class="border px-3 py-1">
                        @switch($jurisdiction)
                            @case('gdpr') RGPD (UE) @break
                            @case('canada_quebec') {{ __('Loi 25 (Québec)') }} @break
                            @case('pipeda') LPRPDE / PIPEDA @break
                            @case('ccpa') CCPA (Californie) @break
                            @default {{ $jurisdiction }}
                        @endswitch
                    </td>
                    <td class="border px-3 py-1">{{ $days }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2 id="third-party">{{ __('6. Cookies tiers') }}</h2>
    <p>{{ __('Certains cookies sont déposés par des services tiers (Google Analytics, Facebook, Stripe, etc.). Ces cookies sont soumis aux politiques de confidentialité de ces tiers. Nous vous encourageons à consulter leurs politiques respectives.') }}</p>

    <h2 id="updates">{{ __('7. Mises à jour de cette politique') }}</h2>
    <p>{{ __('Cette politique peut être mise à jour pour refléter les changements technologiques, légaux ou opérationnels. La version et la date de mise à jour en haut de cette page indiquent la version en vigueur.') }}</p>

    <h2 id="contact">{{ __('8. Contact') }}</h2>
    <p>{{ __('Pour toute question concernant cette politique des cookies :') }}</p>
    <div class="bg-gray-50 p-4 rounded-lg not-prose">
        <p class="font-semibold">{{ $company['name'] }}</p>
        <p>{{ __('Délégué à la protection des données') }}</p>
        <p><a href="mailto:{{ $company['dpo_email'] }}" class="text-blue-600 hover:underline">{{ $company['dpo_email'] }}</a></p>
    </div>

    <p class="text-xs mt-8 border-t pt-4 text-gray-500">
        {{ __('Version') }} {{ $doc['version'] }} -
        {{ __('Dernière mise à jour') }} : {{ $doc['updated_at'] }}
    </p>
</div>
@endsection
