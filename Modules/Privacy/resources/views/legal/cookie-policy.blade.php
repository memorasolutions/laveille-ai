{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', __('Politique des cookies') . ' - ' . config('app.name'))
@section('meta_description', __('Politique des cookies de laveille.ai — gestion des témoins de connexion.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Politique des cookies')])
@endsection

@section('content')
@php
    $locale = app()->getLocale();
    $company = config('privacy.company');
    $doc = config('privacy.documents.cookie_policy');
    $categories = config('privacy.categories');
    $expiration = config('privacy.consent.expiration');
@endphp
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post">
                        <h2>{{ __('Politique des cookies') }}</h2>
                        <p style="color: #999; font-size: 13px;">
                            <strong>{{ __('Version') }}&nbsp;:</strong> {{ $doc['version'] }}<br>
                            <strong>{{ __('Dernière mise à jour') }}&nbsp;:</strong> {{ $doc['updated_at'] }}
                        </p>

                        <div class="entry-details" style="line-height: 1.8;">
                            <div style="background: #f8f9fa; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px;">
                                <h4 style="margin-top: 0;">{{ __('Table des matières') }}</h4>
                                <ol>
                                    <li><a href="#what-are-cookies">{{ __('Que sont les cookies ?') }}</a></li>
                                    <li><a href="#why-we-use">{{ __('Pourquoi utilisons-nous des cookies ?') }}</a></li>
                                    <li><a href="#categories">{{ __('Catégories de cookies') }}</a></li>
                                    <li><a href="#manage-cookies">{{ __('Comment gérer les cookies') }}</a></li>
                                    <li><a href="#consent-duration">{{ __('Durée du consentement') }}</a></li>
                                    <li><a href="#third-party">{{ __('Cookies tiers') }}</a></li>
                                    <li><a href="#ga4">{{ __('Google Analytics 4 et mode consentement') }}</a></li>
                                    <li><a href="#eu-cookies">{{ __('Droits spécifiques des visiteurs européens') }}</a></li>
                                    <li><a href="#updates">{{ __('Mises à jour de cette politique') }}</a></li>
                                    <li><a href="#contact">{{ __('Contact') }}</a></li>
                                </ol>
                            </div>

                            <h3 id="what-are-cookies">{{ __('1. Que sont les cookies ?') }}</h3>
                            <p>{{ __('Les cookies (ou témoins de connexion) sont de petits fichiers texte déposés sur votre appareil lorsque vous visitez notre site. Ils permettent au site de mémoriser vos actions et préférences sur une période donnée.') }}</p>

                            <h3 id="why-we-use">{{ __('2. Pourquoi utilisons-nous des cookies ?') }}</h3>
                            <ul>
                                <li>{{ __('Assurer le fonctionnement technique du site (authentification, sécurité)') }}</li>
                                <li>{{ __('Mémoriser vos préférences (langue, thème)') }}</li>
                                <li>{{ __('Analyser le trafic et améliorer nos services') }}</li>
                                <li>{{ __('Personnaliser votre expérience') }}</li>
                            </ul>

                            <h3 id="categories">{{ __('3. Catégories de cookies') }}</h3>
                            @foreach($categories as $catKey => $category)
                            <div style="margin-bottom: 20px;">
                                <h4>
                                    {{ $locale === 'fr' ? $category['label_fr'] : $category['label_en'] }}
                                    @if($category['required'])
                                        <span class="label label-info" style="font-size: 11px; vertical-align: middle;">{{ __('Obligatoire') }}</span>
                                    @endif
                                </h4>
                                @if(!empty($category['cookies']))
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" style="font-size: 13px;">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Nom') }}</th>
                                                <th>{{ __('Fournisseur') }}</th>
                                                <th>{{ __('Finalité') }}</th>
                                                <th>{{ __('Durée') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($category['cookies'] as $cookie)
                                            <tr>
                                                <td><code>{{ $cookie['name'] }}</code></td>
                                                <td>{{ $cookie['provider'] }}</td>
                                                <td>{{ $locale === 'fr' ? $cookie['purpose_fr'] : $cookie['purpose_en'] }}</td>
                                                <td>{{ $cookie['duration'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @endif
                            </div>
                            @endforeach

                            <h3 id="manage-cookies">{{ __('4. Comment gérer les cookies') }}</h3>
                            <h4>{{ __('Via notre bannière de consentement') }}</h4>
                            <p>{{ __('Lors de votre première visite, notre bannière vous permet de choisir quelles catégories de cookies vous acceptez. Vous pouvez modifier vos choix à tout moment en cliquant sur le bouton de gestion des cookies en bas à gauche de l\'écran.') }}</p>
                            <h4>{{ __('Via les paramètres de votre navigateur') }}</h4>
                            <p>{{ __('Vous pouvez configurer votre navigateur pour refuser les cookies ou être alerté lorsqu\'un cookie est déposé. Consultez l\'aide de votre navigateur pour connaître la procédure.') }}</p>

                            <h3 id="consent-duration">{{ __('5. Durée du consentement par juridiction') }}</h3>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" style="font-size: 13px;">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Juridiction') }}</th>
                                            <th>{{ __('Durée de validité (jours)') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($expiration as $jurisdiction => $days)
                                        <tr>
                                            <td>
                                                @switch($jurisdiction)
                                                    @case('gdpr') RGPD (UE) @break
                                                    @case('canada_quebec') {{ __('Loi 25 (Québec)') }} @break
                                                    @case('pipeda') LPRPDE / PIPEDA @break
                                                    @case('ccpa') CCPA (Californie) @break
                                                    @default {{ $jurisdiction }}
                                                @endswitch
                                            </td>
                                            <td>{{ $days }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <h3 id="third-party">{{ __('6. Cookies tiers') }}</h3>
                            <p>{{ __('Certains cookies sont déposés par des services tiers (Google Analytics, Facebook, Stripe, etc.). Ces cookies sont soumis aux politiques de confidentialité de ces tiers. Nous vous encourageons à consulter leurs politiques respectives.') }}</p>

                            <h3 id="ga4">{{ __('7. Google Analytics 4 et mode consentement') }}</h3>
                            <p>{{ __('Ce site utilise Google Analytics 4 (GA4) en mode consentement v2. Aucun cookie à finalité analytique n\'est déposé sans votre consentement explicite (opt-in). Votre adresse IP est anonymisée par défaut, conformément aux recommandations de la CNIL. Les données collectées sont conservées au maximum 14 mois. Le traitement des données est effectué dans le cadre du Data Processing Framework UE-États-Unis et des clauses contractuelles types (SCC), garantissant ainsi une conformité au RGPD.') }}</p>

                            <h3 id="eu-cookies">{{ __('8. Droits spécifiques des visiteurs européens') }}</h3>
                            <p>{{ __('Conformément au RGPD et à la Directive ePrivacy (2002/58/CE), les cookies non essentiels ne sont utilisés qu\'après votre consentement préalable. Ce consentement peut être refusé aussi facilement qu\'il peut être accepté. Aucun « cookie wall » n\'est mis en place : l\'accès au site ne dépend pas de l\'acceptation des cookies non essentiels. Vous pouvez retirer votre consentement à tout moment via le bouton « Gérer les témoins » présent sur le site. Vous disposez également du droit d\'introduire une réclamation auprès d\'une autorité de contrôle compétente, telle que la CNIL en France.') }}</p>

                            <h3 id="updates">{{ __('9. Mises à jour de cette politique') }}</h3>
                            <p>{{ __('Cette politique peut être mise à jour pour refléter les changements technologiques, légaux ou opérationnels. La version et la date de mise à jour en haut de cette page indiquent la version en vigueur.') }}</p>

                            <h3 id="contact">{{ __('10. Contact') }}</h3>
                            <p>{{ __('Pour toute question concernant cette politique des cookies :') }}</p>
                            <p>
                                <strong>{{ $company['name'] }}</strong><br>
                                {{ __('Délégué à la protection des données') }}<br>
                                <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a>
                            </p>

                            <hr>
                            <p style="color: #999; font-size: 12px;">
                                {{ __('Version') }} {{ $doc['version'] }} -
                                {{ __('Dernière mise à jour') }}&nbsp;: {{ $doc['updated_at'] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
