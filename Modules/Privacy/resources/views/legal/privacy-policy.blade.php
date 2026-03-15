{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('privacy::layouts.legal')
@section('title', __('Politique de confidentialite'))
@section('content')
@php
    $locale = app()->getLocale();
    $company = $config['company'];
    $doc = $config['documents']['privacy_policy'];
    $categories = $config['categories'];
    $rights = $config['rights'];
    $jurisdictions = $config['jurisdictions'];
    $rightsLabels = [
        'access' => __('Droit d\'acces'),
        'rectification' => __('Droit de rectification'),
        'erasure' => __('Droit a l\'effacement'),
        'portability' => __('Droit a la portabilite'),
        'opposition' => __('Droit d\'opposition'),
        'limitation' => __('Droit a la limitation du traitement'),
        'withdrawal' => __('Droit de retrait du consentement'),
    ];
@endphp

<div class="prose max-w-none mx-auto">
    <h1 id="top">{{ __('Politique de confidentialite') }}</h1>
    <p class="text-sm text-gray-500">
        <strong>{{ __('Version') }} :</strong> {{ $doc['version'] }}<br>
        <strong>{{ __('Derniere mise a jour') }} :</strong> {{ \Carbon\Carbon::parse($doc['updated_at'])->translatedFormat('d F Y') }}
    </p>

    <nav class="my-8">
        <h2>{{ __('Table des matieres') }}</h2>
        <ol class="list-decimal list-inside space-y-1">
            <li><a href="#introduction">{{ __('Introduction et lois applicables') }}</a></li>
            <li><a href="#controller">{{ __('Responsable du traitement') }}</a></li>
            <li><a href="#data-collected">{{ __('Donnees personnelles collectees') }}</a></li>
            <li><a href="#legal-bases">{{ __('Fondements juridiques') }}</a></li>
            <li><a href="#purposes">{{ __('Finalites du traitement') }}</a></li>
            <li><a href="#cookies">{{ __('Cookies et traceurs') }}</a></li>
            <li><a href="#sharing">{{ __('Communication et transferts') }}</a></li>
            <li><a href="#retention">{{ __('Duree de conservation') }}</a></li>
            <li><a href="#rights">{{ __('Vos droits') }}</a></li>
            <li><a href="#security">{{ __('Mesures de securite') }}</a></li>
            <li><a href="#efvp">{{ __('Evaluation des facteurs relatifs a la vie privee (EFVP)') }}</a></li>
            <li><a href="#minors">{{ __('Mineurs') }}</a></li>
            <li><a href="#contact">{{ __('Contact, DPO et autorites') }}</a></li>
        </ol>
    </nav>

    <h2 id="introduction">{{ __('1. Introduction et lois applicables') }}</h2>
    <p>{{ __('Cette politique explique la facon dont nous collectons, utilisons et protegeons vos donnees personnelles, conformement aux lois suivantes :') }}</p>
    <ul class="ml-6 list-disc">
        <li>{{ __('RGPD (Reglement general sur la protection des donnees, UE)') }}</li>
        <li>{{ __('Loi 25 (Loi modernisant des dispositions legislatives en matiere de protection des renseignements personnels, Quebec)') }}</li>
        <li>{{ __('LPRPDE / PIPEDA (Loi sur la protection des renseignements personnels et les documents electroniques, Canada)') }}</li>
        <li>{{ __('Directive ePrivacy (Europe)') }}</li>
    </ul>

    <h2 id="controller">{{ __('2. Responsable du traitement') }}</h2>
    <p>
        <strong>{{ $company['name'] }}</strong><br>
        {{ $company['address'] }}<br>
        {{ __('Delegue a la protection des donnees (DPO)') }} : {{ $company['dpo_name'] }}<br>
        {{ __('Courriel') }} : <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a>
    </p>

    <h2 id="data-collected">{{ __('3. Donnees personnelles collectees') }}</h2>
    <ul class="list-disc ml-6">
        <li>{{ __('Donnees d\'identite (nom, prenom, courriel)') }}</li>
        <li>{{ __('Donnees de connexion (adresse IP, identifiants de session)') }}</li>
        <li>{{ __('Donnees de navigation (pages consultees, preferences)') }}</li>
        <li>{{ __('Donnees de consentement et choix de cookies') }}</li>
    </ul>

    <h2 id="legal-bases">{{ __('4. Fondements juridiques') }}</h2>
    <ul class="list-disc ml-6">
        <li><strong>{{ __('Consentement') }}</strong> : {{ __('pour les cookies non essentiels et le marketing') }}</li>
        <li><strong>{{ __('Execution de contrat') }}</strong> : {{ __('pour la gestion de votre compte et la fourniture du service') }}</li>
        <li><strong>{{ __('Interet legitime') }}</strong> : {{ __('pour la securite et l\'amelioration du service') }}</li>
        <li><strong>{{ __('Obligation legale') }}</strong> : {{ __('pour la conservation requise par la loi') }}</li>
    </ul>

    <h2 id="purposes">{{ __('5. Finalites du traitement') }}</h2>
    <ul class="list-disc ml-6">
        <li>{{ __('Gestion des comptes et fourniture du service') }}</li>
        <li>{{ __('Securite et prevention de la fraude') }}</li>
        <li>{{ __('Analyses statistiques et amelioration du service') }}</li>
        <li>{{ __('Personnalisation de l\'experience utilisateur') }}</li>
        <li>{{ __('Respect des obligations legales et reglementaires') }}</li>
    </ul>

    <h2 id="cookies">{{ __('6. Cookies et traceurs') }}</h2>
    <p>{{ __('Des cookies de differentes categories sont utilises sur notre site. Vous pouvez gerer vos preferences a tout moment via notre banniere de consentement.') }}</p>
    <div class="overflow-x-auto">
        <table class="min-w-full border text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-3 py-2 text-left">{{ __('Categorie') }}</th>
                    <th class="border px-3 py-2 text-left">{{ __('Cookie') }}</th>
                    <th class="border px-3 py-2 text-left">{{ __('Fournisseur') }}</th>
                    <th class="border px-3 py-2 text-left">{{ __('Finalite') }}</th>
                    <th class="border px-3 py-2 text-left">{{ __('Duree') }}</th>
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
        {{ __('Pour plus de details, consultez notre') }} <a href="{{ url('/cookie-policy') }}">{{ __('politique des cookies') }}</a>.
    </p>

    <h2 id="sharing">{{ __('7. Communication et transferts de donnees') }}</h2>
    <ul class="list-disc ml-6">
        <li>{{ __('Partage avec des prestataires de service (hebergement, paiement, analyse) dans le cadre strict de la fourniture du service') }}</li>
        <li>{{ __('Transferts internationaux (hors Quebec, Canada ou UE) uniquement avec des garanties appropriees (clauses contractuelles types, decisions d\'adequation)') }}</li>
        <li>{{ __('Aucune vente de donnees personnelles a des tiers') }}</li>
    </ul>

    <h2 id="retention">{{ __('8. Duree de conservation') }}</h2>
    <ul class="list-disc ml-6">
        <li>{{ __('Donnees de compte : pendant la duree de la relation contractuelle, puis archivage legal') }}</li>
        <li>{{ __('Donnees de connexion : 12 mois maximum') }}</li>
        <li>{{ __('Cookies : selon les durees indiquees dans le tableau ci-dessus') }}</li>
        <li>{{ __('Preuves de consentement : 5 ans (conformement au RGPD art. 7)') }}</li>
        <li>{{ __('Demandes d\'exercice de droits : duree du traitement + 3 ans en archivage') }}</li>
    </ul>

    <h2 id="rights">{{ __('9. Vos droits') }}</h2>
    <p>{{ __('Conformement aux lois applicables, vous disposez des droits suivants :') }}</p>
    <ul class="list-disc ml-6">
        @foreach($rights['types'] as $right)
            <li>{{ $rightsLabels[$right] ?? $right }}</li>
        @endforeach
    </ul>
    <p>
        {{ __('Pour exercer vos droits, utilisez notre') }}
        <a href="{{ route('legal.rights') }}">{{ __('formulaire de demande') }}</a>
        {{ __('ou contactez notre DPO a l\'adresse') }}
        <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a>.
        {{ __('Nous repondrons dans un delai de') }}
        <strong>{{ $rights['response_delay_days'] }} {{ __('jours') }}</strong>.
        {{ __('Certaines demandes peuvent necessiter la verification de votre identite.') }}
    </p>

    <h2 id="security">{{ __('10. Mesures de securite') }}</h2>
    <p>{{ __('Nous mettons en place des mesures techniques et organisationnelles pour proteger vos donnees :') }}</p>
    <ul class="list-disc ml-6">
        <li>{{ __('Chiffrement des donnees en transit (TLS) et au repos') }}</li>
        <li>{{ __('Controles d\'acces stricts et authentification') }}</li>
        <li>{{ __('Audits de securite reguliers') }}</li>
        <li>{{ __('Formation du personnel a la protection des donnees') }}</li>
    </ul>

    <h2 id="efvp">{{ __('11. Evaluation des facteurs relatifs a la vie privee (EFVP)') }}</h2>
    <p>{{ __('Conformement a l\'article 3.3 de la Loi modernisant des dispositions legislatives en matiere de protection des renseignements personnels (Loi 25, Quebec), notre organisation s\'engage a effectuer une Evaluation des facteurs relatifs a la vie privee (EFVP) pour tout projet impliquant la collecte, l\'utilisation, la communication, la conservation ou la destruction de renseignements personnels.') }}</p>
    <p>{{ __('Les types de projets vises par cette obligation incluent notamment :') }}</p>
    <ul class="list-disc ml-6">
        <li>{{ __('Tout nouveau traitement de renseignements personnels') }}</li>
        <li>{{ __('L\'implantation d\'une nouvelle technologie ayant un impact sur la vie privee') }}</li>
        <li>{{ __('Tout transfert de renseignements personnels a l\'exterieur du Quebec') }}</li>
        <li>{{ __('Les projets impliquant du profilage ou la prise de decision automatisee') }}</li>
    </ul>
    <p>{{ __('Le processus d\'EFVP comprend :') }}</p>
    <ul class="list-disc ml-6">
        <li>{{ __('L\'identification des risques d\'atteinte a la vie privee') }}</li>
        <li>{{ __('La mise en place de mesures d\'attenuation appropriees') }}</li>
        <li>{{ __('La consultation de la Commission d\'acces a l\'information (CAI) lorsqu\'un risque eleve est identifie') }}</li>
    </ul>
    <p>
        {{ __('Une copie de l\'EFVP est disponible sur demande aupres de notre responsable de la protection des renseignements personnels a l\'adresse :') }}
        <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a>.
    </p>

    <h2 id="minors">{{ __('12. Mineurs') }}</h2>
    <p>{{ __('Nos services ne sont pas destines aux personnes de moins de 14 ans. Nous ne collectons pas sciemment de donnees aupres de mineurs sans le consentement parental requis par la loi applicable.') }}</p>

    <h2 id="contact">{{ __('13. Contact, DPO et autorites de controle') }}</h2>
    <p>{{ __('Pour toute question ou exercice de vos droits :') }}</p>
    <ul class="list-disc ml-6">
        <li><strong>{{ __('DPO') }} :</strong> {{ $company['dpo_name'] }} - <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a></li>
        <li><strong>{{ __('Adresse') }} :</strong> {{ $company['address'] }}</li>
    </ul>
    <p class="mt-4">{{ __('Vous pouvez egalement contacter les autorites de controle competentes :') }}</p>
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
        {{ __('Derniere mise a jour') }} : {{ \Carbon\Carbon::parse($doc['updated_at'])->translatedFormat('d F Y') }}
    </p>
</div>
@endsection
