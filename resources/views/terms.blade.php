@extends('fronttheme::themes.gosass.layouts.app')

@section('title', __('Conditions d\'utilisation').' - '.config('app.name'))
@section('description', __('Conditions générales d\'utilisation de la plateforme').' '.config('app.name').'.')

@section('content')

<div class="cs_height_85 cs_height_lg_80"></div>
<div class="container text-center">
    <h1 class="cs_fs_50 cs_mb_15 wow fadeInDown">{{ __('Conditions d\'utilisation') }}</h1>
    <p class="mb-0 wow fadeInUp">{{ __('Veuillez lire attentivement les conditions suivantes avant d\'utiliser notre plateforme.') }}</p>
</div>

<div class="cs_height_64 cs_height_lg_50"></div>
<div class="container">
    <div class="cs_radius_15 cs_white_bg p-4 p-lg-5 wow fadeIn">

        <p class="text-muted mb-4"><small>{{ __('Dernière mise à jour') }} : {{ now()->format('d/m/Y') }}</small></p>

        {{-- 1. Objet --}}
        <h4 class="cs_mb_15">1. {{ __('Objet') }}</h4>
        <p>{{ __('Les présentes conditions générales d\'utilisation (ci-après « CGU ») régissent l\'accès et l\'utilisation de la plateforme') }} <strong>{{ config('app.name') }}</strong> {{ __('(ci-après « la Plateforme »), accessible à l\'adresse') }} <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>.</p>
        <p>{{ __('La Plateforme offre des services numériques dont les fonctionnalités précises sont décrites sur le site. Ces CGU s\'appliquent indépendamment du type de services proposés (abonnement SaaS, commerce en ligne, services professionnels ou autres).') }}</p>

        <hr class="my-4">

        {{-- 2. Acceptation --}}
        <h4 class="cs_mb_15">2. {{ __('Acceptation des conditions') }}</h4>
        <p>{{ __('En accédant à la Plateforme ou en créant un compte, vous acceptez intégralement et sans réserve les présentes CGU. Si vous n\'acceptez pas ces conditions, vous devez cesser immédiatement toute utilisation de la Plateforme.') }}</p>
        <p>{{ __('Les présentes CGU constituent un contrat juridiquement contraignant entre vous (ci-après « l\'Utilisateur ») et') }} {{ config('app.name') }}.</p>

        <hr class="my-4">

        {{-- 3. Inscription et compte --}}
        <h4 class="cs_mb_15">3. {{ __('Inscription et compte utilisateur') }}</h4>
        <p>{{ __('Pour accéder à certaines fonctionnalités, vous devez créer un compte. Lors de l\'inscription, vous vous engagez à :') }}</p>
        <ul>
            <li>{{ __('Être âgé d\'au moins 16 ans (ou avoir le consentement d\'un parent ou tuteur légal)') }}</li>
            <li>{{ __('Fournir des informations exactes, complètes et à jour') }}</li>
            <li>{{ __('Maintenir la confidentialité de vos identifiants de connexion') }}</li>
            <li>{{ __('Nous informer immédiatement de toute utilisation non autorisée de votre compte') }}</li>
            <li>{{ __('Ne pas créer plusieurs comptes pour une même personne') }}</li>
        </ul>
        <p>{{ __('Vous êtes entièrement responsable de toute activité effectuée sous votre compte. Nous nous réservons le droit de suspendre ou supprimer tout compte en cas de violation des présentes CGU.') }}</p>

        <hr class="my-4">

        {{-- 4. Services et abonnements --}}
        <h4 class="cs_mb_15">4. {{ __('Services et abonnements') }}</h4>
        <p>{{ __('La Plateforme peut proposer des services gratuits et payants. Les abonnements payants sont soumis aux conditions suivantes :') }}</p>
        <ul>
            <li>{{ __('Les tarifs et fonctionnalités de chaque plan sont indiqués sur la page de tarification') }}</li>
            <li>{{ __('Les abonnements sont facturés selon la fréquence choisie (mensuelle ou annuelle)') }}</li>
            <li>{{ __('Le renouvellement est automatique sauf annulation avant la date de renouvellement') }}</li>
            <li>{{ __('Les prix peuvent être modifiés avec un préavis de 30 jours') }}</li>
            <li>{{ __('Un remboursement peut être accordé dans les 14 jours suivant la souscription, selon les conditions du plan') }}</li>
        </ul>
        <p>{{ __('Les paiements sont traités par notre processeur de paiement tiers (Stripe). Nous ne stockons aucune information de carte de crédit.') }}</p>

        <hr class="my-4">

        {{-- 5. Propriété intellectuelle --}}
        <h4 class="cs_mb_15">5. {{ __('Propriété intellectuelle') }}</h4>
        <p>{{ __('L\'ensemble des éléments de la Plateforme (code source, design, logos, textes, images, marques, base de données) est la propriété exclusive de') }} {{ config('app.name') }} {{ __('ou fait l\'objet d\'une licence d\'utilisation. Toute reproduction, représentation, modification ou exploitation non autorisée est strictement interdite.') }}</p>
        <p>{{ __('L\'Utilisateur conserve la propriété de tout contenu qu\'il publie sur la Plateforme. En publiant du contenu, vous accordez à') }} {{ config('app.name') }} {{ __('une licence non exclusive, mondiale, gratuite et sous-licenciable pour utiliser, afficher et distribuer ce contenu dans le cadre du fonctionnement de la Plateforme.') }}</p>

        <hr class="my-4">

        {{-- 6. Contenu utilisateur --}}
        <h4 class="cs_mb_15">6. {{ __('Contenu utilisateur') }}</h4>
        <p>{{ __('Vous êtes seul responsable du contenu que vous publiez, téléversez ou partagez via la Plateforme. Il est strictement interdit de publier du contenu :') }}</p>
        <ul>
            <li>{{ __('Illégal, frauduleux ou trompeur') }}</li>
            <li>{{ __('Diffamatoire, injurieux, obscène ou haineux') }}</li>
            <li>{{ __('Portant atteinte aux droits de propriété intellectuelle de tiers') }}</li>
            <li>{{ __('Contenant des virus, logiciels malveillants ou tout code nuisible') }}</li>
            <li>{{ __('Constituant du spam, de la publicité non sollicitée ou du hameçonnage') }}</li>
            <li>{{ __('Violant la vie privée ou les données personnelles d\'autrui') }}</li>
        </ul>
        <p>{{ __('Nous nous réservons le droit de supprimer tout contenu contraire aux présentes CGU sans préavis et sans obligation de justification.') }}</p>

        <hr class="my-4">

        {{-- 7. Responsabilités de l'utilisateur --}}
        <h4 class="cs_mb_15">7. {{ __('Responsabilités de l\'utilisateur') }}</h4>
        <p>{{ __('En utilisant la Plateforme, vous vous engagez à :') }}</p>
        <ul>
            <li>{{ __('Respecter les lois et réglementations applicables') }}</li>
            <li>{{ __('Ne pas tenter d\'accéder de manière non autorisée aux systèmes ou données de la Plateforme') }}</li>
            <li>{{ __('Ne pas perturber ou interrompre le fonctionnement de la Plateforme') }}</li>
            <li>{{ __('Ne pas utiliser de robots, scripts ou outils automatisés sans autorisation') }}</li>
            <li>{{ __('Ne pas contourner les mesures de sécurité ou d\'authentification') }}</li>
            <li>{{ __('Ne pas usurper l\'identité d\'une autre personne ou entité') }}</li>
        </ul>

        <hr class="my-4">

        {{-- 8. Limitation de responsabilité --}}
        <h4 class="cs_mb_15">8. {{ __('Limitation de responsabilité') }}</h4>
        <p>{{ __('La Plateforme est fournie « EN L\'ÉTAT » et « TELLE QUE DISPONIBLE ». Dans les limites permises par la loi applicable :') }}</p>
        <ul>
            <li>{{ __('Nous ne garantissons pas que la Plateforme sera exempte d\'erreurs, d\'interruptions ou de vulnérabilités') }}</li>
            <li>{{ __('Nous ne sommes pas responsables des dommages indirects, accessoires, spéciaux ou consécutifs') }}</li>
            <li>{{ __('Notre responsabilité totale ne pourra excéder le montant que vous nous avez versé au cours des 12 derniers mois') }}</li>
            <li>{{ __('Nous ne sommes pas responsables du contenu publié par les utilisateurs') }}</li>
        </ul>
        <p>{{ __('Ces limitations s\'appliquent dans toute la mesure permise par la loi, mais n\'excluent pas les responsabilités qui ne peuvent être légalement exclues.') }}</p>

        <hr class="my-4">

        {{-- 9. Données personnelles --}}
        <h4 class="cs_mb_15">9. {{ __('Données personnelles et vie privée') }}</h4>
        <p>{{ __('La collecte et le traitement de vos données personnelles sont régis par notre') }} <a href="{{ route('privacy') }}">{{ __('Politique de confidentialité') }}</a>, {{ __('qui fait partie intégrante des présentes CGU.') }}</p>
        <p>{{ __('En utilisant la Plateforme, vous consentez à la collecte et au traitement de vos données tel que décrit dans notre politique de confidentialité, conformément aux lois applicables (PIPEDA, Loi 25, RGPD, CCPA).') }}</p>

        <hr class="my-4">

        {{-- 10. Résiliation --}}
        <h4 class="cs_mb_15">10. {{ __('Résiliation') }}</h4>
        <p>{{ __('Vous pouvez fermer votre compte à tout moment depuis les paramètres de votre profil ou en nous contactant par courriel.') }}</p>
        <p>{{ __('En cas de résiliation :') }}</p>
        <ul>
            <li>{{ __('Les abonnements en cours ne sont pas remboursés (sauf dans les cas prévus par la loi)') }}</li>
            <li>{{ __('Vos données personnelles seront traitées conformément à notre politique de confidentialité') }}</li>
            <li>{{ __('Votre contenu pourra être supprimé après un délai de grâce de 30 jours') }}</li>
            <li>{{ __('Les licences que vous nous avez accordées sur votre contenu prendront fin') }}</li>
        </ul>
        <p>{{ __('Nous nous réservons le droit de suspendre ou résilier votre compte immédiatement en cas de violation grave des présentes CGU, sans préavis ni indemnité.') }}</p>

        <hr class="my-4">

        {{-- 11. Droit applicable --}}
        <h4 class="cs_mb_15">11. {{ __('Droit applicable et juridiction') }}</h4>
        <p>{{ __('Les présentes CGU sont régies et interprétées conformément aux lois en vigueur au Canada et dans la province de Québec. Tout litige relatif à l\'interprétation ou à l\'exécution des présentes sera soumis à la compétence exclusive des tribunaux de la province de Québec, district de Montréal.') }}</p>
        <p>{{ __('Si une disposition des présentes CGU est jugée invalide ou inapplicable, les autres dispositions demeureront en vigueur.') }}</p>

        <hr class="my-4">

        {{-- 12. Modifications --}}
        <h4 class="cs_mb_15">12. {{ __('Modifications des conditions') }}</h4>
        <p>{{ __('Nous nous réservons le droit de modifier les présentes CGU à tout moment. Les modifications substantielles seront communiquées par :') }}</p>
        <ul>
            <li>{{ __('Notification par courriel aux utilisateurs inscrits, au moins 30 jours avant l\'entrée en vigueur') }}</li>
            <li>{{ __('Bannière d\'avis sur la Plateforme') }}</li>
            <li>{{ __('Mise à jour de la date de « Dernière mise à jour » en haut de cette page') }}</li>
        </ul>
        <p>{{ __('Votre utilisation continue de la Plateforme après l\'entrée en vigueur des modifications constitue votre acceptation des nouvelles conditions.') }}</p>

        <hr class="my-4">

        {{-- 13. Contact --}}
        <h4 class="cs_mb_15">13. {{ __('Contact') }}</h4>
        <p>{{ __('Pour toute question concernant les présentes conditions d\'utilisation, vous pouvez nous contacter à :') }}</p>
        <p>
            <strong>{{ config('app.name') }}</strong><br>
            {{ __('Courriel') }} : <a href="mailto:{{ config('mail.from.address', 'contact@example.com') }}">{{ config('mail.from.address', 'contact@example.com') }}</a>
        </p>

    </div>
</div>

<div class="cs_height_85 cs_height_lg_80"></div>

@endsection
