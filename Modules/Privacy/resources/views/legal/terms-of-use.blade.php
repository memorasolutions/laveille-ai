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
            <li><a href="#compte">{{ __('Comptes utilisateurs') }}</a></li>
            <li><a href="#contenu">{{ __('Contenu généré par les utilisateurs') }}</a></li>
            <li><a href="#ia">{{ __('Résumés par intelligence artificielle') }}</a></li>
            <li><a href="#shorturl">{{ __('Raccourcisseur d\'URL (veille.la)') }}</a></li>
            <li><a href="#rss">{{ __('Agrégation RSS et contenu tiers') }}</a></li>
            <li><a href="#affiliation">{{ __('Liens d\'affiliation') }}</a></li>
            <li><a href="#propriete">{{ __('Propriété intellectuelle') }}</a></li>
            <li><a href="#usage">{{ __('Comportement acceptable') }}</a></li>
            <li><a href="#moderation">{{ __('Modération') }}</a></li>
            <li><a href="#infolettre">{{ __('Infolettre') }}</a></li>
            <li><a href="#responsabilite">{{ __('Limitation de responsabilité') }}</a></li>
            <li><a href="#indemnisation">{{ __('Indemnisation') }}</a></li>
            <li><a href="#loi">{{ __('Loi applicable') }}</a></li>
            <li><a href="#modification">{{ __('Modification des conditions et contact') }}</a></li>
        </ol>
    </nav>

    <h2 id="acceptation">{{ __('1. Acceptation des conditions') }}</h2>
    <p>{{ __('En accédant au site laveille.ai (ci-après le « Service »), vous acceptez d\'être lié par les présentes conditions d\'utilisation. Si vous n\'acceptez pas ces conditions, vous ne devez pas utiliser le Service. Le Service est exploité par :name, personne physique résidant au Québec, Canada.', ['name' => $company['dpo_name']]) }}</p>

    <h2 id="service">{{ __('2. Description du service') }}</h2>
    <p>{{ __('Le Service est une plateforme de veille technologique et d\'intelligence artificielle (IA) gratuite. Les fonctionnalités incluent :') }}</p>
    <ul class="list-disc ml-6">
        <li>{{ __('L\'agrégation de nouvelles spécialisées en technologie et IA') }}</li>
        <li>{{ __('Un répertoire de plus de 75 outils d\'IA') }}</li>
        <li>{{ __('Un glossaire terminologique sur l\'IA') }}</li>
        <li>{{ __('Une liste d\'acronymes liés au milieu de l\'éducation au Québec') }}</li>
        <li>{{ __('Un service de raccourcisseur d\'URL (veille.la)') }}</li>
        <li>{{ __('Un blogue éditorial proposant des analyses et réflexions') }}</li>
    </ul>

    <h2 id="compte">{{ __('3. Comptes utilisateurs') }}</h2>
    <p>{{ __('L\'accès à certaines fonctionnalités peut nécessiter la création d\'un compte. Le Service utilise une authentification sans mot de passe via l\'envoi d\'un code unique (OTP) par courriel ou par connexion sociale (Google, GitHub). Vous êtes responsable de maintenir la confidentialité de l\'accès à votre boîte courriel ou à vos comptes tiers. Toute activité effectuée sous votre compte est réputée être la vôtre.') }}</p>

    <h2 id="contenu">{{ __('4. Contenu généré par les utilisateurs') }}</h2>
    <p>{{ __('En soumettant du contenu (suggestions de corrections, votes, idées pour la feuille de route, signalements de bogues), vous accordez à l\'exploitant une licence mondiale, non exclusive, gratuite et perpétuelle pour utiliser, reproduire et modifier ce contenu afin d\'améliorer le Service. Vous garantissez que vous détenez les droits nécessaires sur ce contenu.') }}</p>

    <h2 id="ia">{{ __('5. Résumés par intelligence artificielle') }}</h2>
    <p>{{ __('Certains contenus affichés sur le Service sont enrichis ou résumés par des modèles d\'intelligence artificielle. Ces contenus sont clairement identifiés comme tels. L\'exploitant ne garantit pas l\'exactitude, la fiabilité ou l\'exhaustivité des résumés générés par IA, qui sont fournis à titre informatif uniquement.') }}</p>

    <h2 id="shorturl">{{ __('6. Raccourcisseur d\'URL (veille.la)') }}</h2>
    <p>{{ __('L\'utilisation du service de raccourcissement d\'URL est soumise au respect des lois en vigueur. Il est strictement interdit de raccourcir des liens menant vers du contenu illégal, malveillant (hameçonnage, virus), haineux ou pornographique. L\'exploitant collecte des statistiques anonymes de clics pour assurer le bon fonctionnement et la sécurité du service.') }}</p>

    <h2 id="rss">{{ __('7. Agrégation RSS et contenu tiers') }}</h2>
    <p>{{ __('Le Service agrège des flux RSS provenant de sources tierces. Ce contenu est affiché avec une attribution claire vers la source originale. L\'exploitant n\'exerce aucun contrôle éditorial sur ces sites tiers et décline toute responsabilité quant à leur contenu, leur légalité ou leur disponibilité.') }}</p>

    <h2 id="affiliation">{{ __('8. Liens d\'affiliation') }}</h2>
    <p>{{ __('Par souci de transparence, l\'utilisateur est informé que certains liens présents sur le Service peuvent être des liens d\'affiliation. Cela signifie que l\'exploitant peut percevoir une commission si vous effectuez un achat ou une action sur le site partenaire, sans frais supplémentaires pour vous.') }}</p>

    <h2 id="propriete">{{ __('9. Propriété intellectuelle') }}</h2>
    <p>{{ __('Le contenu éditorial original, la structure du site, le code source et le design appartiennent à :name. Le contenu tiers (articles de presse, logos de logiciels tiers) demeure la propriété exclusive de ses auteurs ou ayants droit respectifs. Toute reproduction non autorisée du contenu éditorial est interdite.', ['name' => $company['dpo_name']]) }}</p>

    <h2 id="usage">{{ __('10. Comportement acceptable') }}</h2>
    <p>{{ __('Vous vous engagez à utiliser le Service de manière responsable. Sont strictement interdits : le pollupostage (spam), l\'utilisation de robots pour le moissonnage de données (scraping) sans autorisation, les tentatives d\'intrusion dans les systèmes et tout comportement abusif envers les autres utilisateurs ou l\'exploitant.') }}</p>

    <h2 id="moderation">{{ __('11. Modération') }}</h2>
    <p>{{ __('L\'exploitant se réserve le droit discrétionnaire de modérer, de refuser ou de supprimer tout contenu soumis par un utilisateur (commentaires, suggestions, liens raccourcis) qui contreviendrait aux présentes conditions ou qui serait jugé inapproprié, sans préavis ni justification.') }}</p>

    <h2 id="infolettre">{{ __('12. Infolettre') }}</h2>
    <p>{{ __('L\'inscription à l\'infolettre est volontaire et nécessite votre consentement exprès, conformément à la Loi canadienne anti-pourriel (LCAP). Vous pouvez vous désinscrire en tout temps via le lien de désabonnement inclus dans chaque envoi ou en contactant l\'exploitant.') }}</p>

    <h2 id="responsabilite">{{ __('13. Limitation de responsabilité') }}</h2>
    <p>{{ __('Le Service est fourni gratuitement et « tel quel », sans aucune garantie de performance, de disponibilité ou d\'exactitude. L\'exploitant ne pourra être tenu responsable des dommages directs ou indirects résultant de l\'utilisation ou de l\'impossibilité d\'utiliser le Service, incluant la perte de données ou les erreurs de contenu.') }}</p>

    <h2 id="indemnisation">{{ __('14. Indemnisation') }}</h2>
    <p>{{ __('Vous acceptez d\'indemniser et de dégager de toute responsabilité :name contre les réclamations, dommages ou frais (incluant les honoraires d\'avocat) découlant de votre violation des présentes conditions ou de votre utilisation du Service.', ['name' => $company['dpo_name']]) }}</p>

    <h2 id="loi">{{ __('15. Loi applicable') }}</h2>
    <p>{{ __('Les présentes conditions sont régies par les lois de la province de Québec et les lois fédérales du Canada applicables. Tout litige relatif au Service sera soumis à la compétence exclusive des tribunaux du district judiciaire de Montréal, Québec.') }}</p>

    <h2 id="modification">{{ __('16. Modification des conditions et contact') }}</h2>
    <p>{{ __('L\'exploitant se réserve le droit de modifier les présentes conditions à tout moment. Les modifications entrent en vigueur dès leur publication sur le site.') }}</p>
    <p>{{ __('Pour toute question concernant ces conditions :') }}</p>
    <ul class="list-none ml-0">
        <li><strong>{{ $company['dpo_name'] }}</strong></li>
        @if($company['address'] !== '123 Privacy Street, City, Country')
            <li>{{ $company['address'] }}</li>
        @endif
        <li>{{ __('Courriel') }}&nbsp;: <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a></li>
    </ul>

    <p class="text-xs mt-8 border-t pt-4 text-gray-500">
        {{ __('Version') }} {{ $doc['version'] }} -
        {{ __('Date d\'entrée en vigueur') }}&nbsp;: {{ \Carbon\Carbon::parse($doc['updated_at'])->translatedFormat('d F Y') }}
    </p>
</div>
@endsection
