@extends('fronttheme::themes.gosass.layouts.app')

@section('title', __('Politique de confidentialité').' - '.config('app.name'))
@section('description', __('Politique de confidentialité et protection des données personnelles. Conformité PIPEDA, Loi 25, RGPD.'))

@section('content')

<div class="cs_height_85 cs_height_lg_80"></div>
<div class="container text-center">
    <h1 class="cs_fs_50 cs_mb_15 wow fadeInDown">{{ __('Politique de confidentialité') }}</h1>
    <p class="mb-0 wow fadeInUp">{{ __('Comment nous collectons, utilisons et protégeons vos données personnelles.') }}</p>
</div>

<div class="cs_height_64 cs_height_lg_50"></div>
<div class="container">
    <div class="cs_radius_15 cs_white_bg p-4 p-lg-5 wow fadeIn">

        <p class="text-muted mb-4"><small>{{ __('Dernière mise à jour') }} : {{ now()->format('d/m/Y') }}</small></p>

        <p>{{ __('La présente politique de confidentialité décrit comment') }} <strong>{{ config('app.name') }}</strong> {{ __('(ci-après « nous », « notre » ou « la Plateforme ») collecte, utilise, divulgue et protège vos données personnelles conformément aux lois applicables, notamment :') }}</p>
        <ul>
            <li>{{ __('La Loi sur la protection des renseignements personnels et les documents électroniques (LPRPDE/PIPEDA) - Canada') }}</li>
            <li>{{ __('La Loi 25 modernisant la protection des renseignements personnels - Québec') }}</li>
            <li>{{ __('Le Règlement général sur la protection des données (RGPD) - Union européenne') }}</li>
            <li>{{ __('Le California Consumer Privacy Act (CCPA/CPRA) - Californie, États-Unis') }}</li>
        </ul>

        <hr class="my-4">

        {{-- 1. Responsable du traitement --}}
        <h4 class="cs_mb_15">1. {{ __('Responsable du traitement') }}</h4>
        <p>{{ __('Le responsable du traitement de vos données personnelles est :') }}</p>
        <p>
            <strong>{{ config('app.name') }}</strong><br>
            {{ __('Adresse') }} : {{ Settings::get('legal.company_address') ?: __('Non renseignée') }}<br>
            {{ __('Courriel') }} : <a href="mailto:{{ config('mail.from.address', 'privacy@example.com') }}">{{ config('mail.from.address', 'privacy@example.com') }}</a><br>
            {{ __('Site web') }} : <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
        </p>
        <p>{{ __('Le responsable de la protection des renseignements personnels (DPO) peut être contacté à la même adresse courriel.') }}</p>

        <hr class="my-4">

        {{-- 2. Données collectées --}}
        <h4 class="cs_mb_15">2. {{ __('Données collectées') }}</h4>
        <p>{{ __('Nous collectons les catégories de données suivantes :') }}</p>

        <h6 class="mt-3"><strong>a) {{ __('Données de compte') }}</strong></h6>
        <p>{{ __('Nom, adresse courriel, mot de passe (chiffré), photo de profil, préférences de langue, informations de profil (biographie).') }}</p>

        <h6><strong>b) {{ __('Données d\'utilisation') }}</strong></h6>
        <p>{{ __('Pages visitées, fonctionnalités utilisées, interactions avec le contenu, préférences, historique des recherches.') }}</p>

        <h6><strong>c) {{ __('Données techniques') }}</strong></h6>
        <p>{{ __('Adresse IP, type et version du navigateur, système d\'exploitation, identifiants de l\'appareil, résolution d\'écran, fuseau horaire, langue du navigateur.') }}</p>

        <h6><strong>d) {{ __('Données de paiement') }}</strong></h6>
        <p>{{ __('Les informations de paiement (carte de crédit, adresse de facturation) sont traitées directement par notre processeur de paiement (Stripe). Nous ne stockons jamais vos numéros de carte. Nous conservons uniquement l\'identifiant client Stripe et l\'historique des transactions.') }}</p>

        <h6><strong>e) {{ __('Données de communication') }}</strong></h6>
        <p>{{ __('Correspondances via le formulaire de contact, demandes de support, commentaires et avis.') }}</p>

        <hr class="my-4">

        {{-- 3. Finalités et bases légales --}}
        <h4 class="cs_mb_15">3. {{ __('Finalités et bases légales du traitement') }}</h4>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Finalité') }}</th>
                        <th>{{ __('Base légale') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __('Créer et gérer votre compte') }}</td>
                        <td>{{ __('Exécution du contrat') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Fournir les services et fonctionnalités de la Plateforme') }}</td>
                        <td>{{ __('Exécution du contrat') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Traiter les paiements et abonnements') }}</td>
                        <td>{{ __('Exécution du contrat') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Envoyer des notifications liées au service') }}</td>
                        <td>{{ __('Intérêt légitime') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Envoyer des communications marketing') }}</td>
                        <td>{{ __('Consentement') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Analyser l\'utilisation et améliorer nos services') }}</td>
                        <td>{{ __('Intérêt légitime / Consentement') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Assurer la sécurité et prévenir la fraude') }}</td>
                        <td>{{ __('Intérêt légitime / Obligation légale') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Respecter les obligations légales et réglementaires') }}</td>
                        <td>{{ __('Obligation légale') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <hr class="my-4">

        {{-- 4. Cookies --}}
        <h4 class="cs_mb_15">4. {{ __('Cookies et technologies de suivi') }}</h4>
        <p>{{ __('Nous utilisons des cookies et technologies similaires classés en trois catégories :') }}</p>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Catégorie') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Consentement') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>{{ __('Essentiels') }}</strong></td>
                        <td>{{ __('Session, CSRF, préférences de langue, consentement cookies. Nécessaires au fonctionnement du site.') }}</td>
                        <td>{{ __('Non requis') }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('Analytiques') }}</strong></td>
                        <td>{{ __('Mesure d\'audience, statistiques de visite, performance du site.') }}</td>
                        <td>{{ __('Requis (opt-in)') }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('Marketing') }}</strong></td>
                        <td>{{ __('Publicités personnalisées, suivi inter-sites, remarketing.') }}</td>
                        <td>{{ __('Requis (opt-in)') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p>{{ __('Vous pouvez gérer vos préférences de cookies à tout moment via le bandeau de consentement affiché sur le site ou en modifiant les paramètres de votre navigateur.') }}</p>
        <p>{{ __('Conformément à la Loi 25 du Québec et au RGPD, les cookies non essentiels ne sont activés qu\'après votre consentement explicite.') }}</p>

        <hr class="my-4">

        {{-- 5. Partage --}}
        <h4 class="cs_mb_15">5. {{ __('Partage et divulgation des données') }}</h4>
        <p>{{ __('Vos données personnelles peuvent être partagées avec :') }}</p>
        <ul>
            <li><strong>{{ __('Fournisseurs de services') }}</strong> : {{ __('hébergement, traitement des paiements (Stripe), envoi de courriels, analytiques - agissant en tant que sous-traitants sous nos instructions.') }}</li>
            <li><strong>{{ __('Autorités compétentes') }}</strong> : {{ __('lorsque requis par la loi, une ordonnance judiciaire ou une procédure légale.') }}</li>
            <li><strong>{{ __('Acquéreur potentiel') }}</strong> : {{ __('en cas de fusion, acquisition ou vente d\'actifs, vos données pourraient être transférées au successeur.') }}</li>
        </ul>
        <p><strong>{{ __('Nous ne vendons jamais vos données personnelles à des tiers.') }}</strong> {{ __('(Conforme CCPA : nous ne « vendons » ni ne « partageons » vos informations personnelles au sens de la loi californienne.)') }}</p>

        <hr class="my-4">

        {{-- 6. Transferts internationaux --}}
        <h4 class="cs_mb_15">6. {{ __('Transferts internationaux') }}</h4>
        <p>{{ __('Vos données peuvent être transférées et traitées dans des pays autres que votre pays de résidence. Lorsque nous transférons des données en dehors du Canada ou de l\'Espace économique européen, nous mettons en place des garanties appropriées :') }}</p>
        <ul>
            <li>{{ __('Clauses contractuelles types approuvées par la Commission européenne') }}</li>
            <li>{{ __('Certification de conformité adéquate (ex. : décision d\'adéquation)') }}</li>
            <li>{{ __('Accords de traitement des données avec chaque sous-traitant') }}</li>
        </ul>

        <hr class="my-4">

        {{-- 7. Conservation --}}
        <h4 class="cs_mb_15">7. {{ __('Conservation des données') }}</h4>
        <p>{{ __('Nous conservons vos données uniquement pendant la durée nécessaire aux finalités décrites :') }}</p>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Type de données') }}</th>
                        <th>{{ __('Durée de conservation') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __('Données de compte') }}</td>
                        <td>{{ __('Durée de l\'abonnement + 30 jours après suppression') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Données de facturation') }}</td>
                        <td>{{ __('10 ans (obligations comptables)') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Journaux d\'activité') }}</td>
                        <td>{{ __('12 mois') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Données analytiques') }}</td>
                        <td>{{ __('26 mois (anonymisées après)') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Données de contact') }}</td>
                        <td>{{ __('3 ans après le dernier contact') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <hr class="my-4">

        {{-- 8. Droits --}}
        <h4 class="cs_mb_15">8. {{ __('Vos droits') }}</h4>
        <p>{{ __('Selon votre lieu de résidence, vous disposez des droits suivants sur vos données personnelles :') }}</p>

        <h6 class="mt-3"><strong>{{ __('Droits universels (PIPEDA, Loi 25, RGPD, CCPA)') }}</strong></h6>
        <ul>
            <li><strong>{{ __('Droit d\'accès') }}</strong> - {{ __('Obtenir une copie de vos données personnelles') }}</li>
            <li><strong>{{ __('Droit de rectification') }}</strong> - {{ __('Corriger des données inexactes ou incomplètes') }}</li>
            <li><strong>{{ __('Droit à l\'effacement') }}</strong> - {{ __('Demander la suppression de vos données') }}</li>
            <li><strong>{{ __('Droit au retrait du consentement') }}</strong> - {{ __('Retirer votre consentement à tout moment') }}</li>
        </ul>

        <h6 class="mt-3"><strong>{{ __('Droits additionnels selon votre juridiction') }}</strong></h6>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Droit') }}</th>
                        <th>RGPD</th>
                        <th>{{ __('Loi 25') }}</th>
                        <th>PIPEDA</th>
                        <th>CCPA</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __('Portabilité des données') }}</td>
                        <td class="text-center">&#10003;</td>
                        <td class="text-center">&#10003;</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                    </tr>
                    <tr>
                        <td>{{ __('Opposition au traitement') }}</td>
                        <td class="text-center">&#10003;</td>
                        <td class="text-center">&#10003;</td>
                        <td class="text-center">&#10003;</td>
                        <td class="text-center">-</td>
                    </tr>
                    <tr>
                        <td>{{ __('Limitation du traitement') }}</td>
                        <td class="text-center">&#10003;</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                    </tr>
                    <tr>
                        <td>{{ __('Non-discrimination') }}</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">&#10003;</td>
                    </tr>
                    <tr>
                        <td>{{ __('Opt-out de la vente de données') }}</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">&#10003;</td>
                    </tr>
                    <tr>
                        <td>{{ __('Décision automatisée (information)') }}</td>
                        <td class="text-center">&#10003;</td>
                        <td class="text-center">&#10003;</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                    </tr>
                    <tr>
                        <td>{{ __('Droit d\'action privée') }}</td>
                        <td class="text-center">-</td>
                        <td class="text-center">&#10003;</td>
                        <td class="text-center">-</td>
                        <td class="text-center">&#10003;</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p>{{ __('Pour exercer vos droits, utilisez la section « Mon profil » de votre compte (export et suppression de données) ou contactez-nous par courriel. Nous répondrons dans un délai de 30 jours.') }}</p>

        <hr class="my-4">

        {{-- 9. Sécurité --}}
        <h4 class="cs_mb_15">9. {{ __('Sécurité des données') }}</h4>
        <p>{{ __('Nous mettons en oeuvre des mesures techniques et organisationnelles appropriées pour protéger vos données :') }}</p>
        <ul>
            <li>{{ __('Chiffrement TLS/SSL pour toutes les communications') }}</li>
            <li>{{ __('Authentification à deux facteurs (2FA) disponible') }}</li>
            <li>{{ __('Mots de passe chiffrés avec bcrypt (12 rounds)') }}</li>
            <li>{{ __('Journalisation et surveillance des accès') }}</li>
            <li>{{ __('Sauvegardes régulières chiffrées') }}</li>
            <li>{{ __('Principe du moindre privilège pour l\'accès aux données') }}</li>
        </ul>
        <p>{{ __('En cas de violation de données susceptible de présenter un risque pour vos droits, nous vous en informerons dans les meilleurs délais conformément aux obligations légales applicables.') }}</p>

        <hr class="my-4">

        {{-- 10. Mineurs --}}
        <h4 class="cs_mb_15">10. {{ __('Mineurs') }}</h4>
        <p>{{ __('Notre service s\'adresse aux personnes âgées de 16 ans ou plus. Nous ne collectons pas sciemment de données personnelles auprès de mineurs de moins de 16 ans. Si nous découvrons que nous avons collecté des données d\'un mineur, nous les supprimerons dans les meilleurs délais.') }}</p>

        <hr class="my-4">

        {{-- 11. Modifications --}}
        <h4 class="cs_mb_15">11. {{ __('Modifications de cette politique') }}</h4>
        <p>{{ __('Nous pouvons modifier cette politique de confidentialité à tout moment. Les modifications substantielles seront communiquées par :') }}</p>
        <ul>
            <li>{{ __('Notification par courriel aux utilisateurs inscrits') }}</li>
            <li>{{ __('Bannière d\'avis sur la Plateforme') }}</li>
            <li>{{ __('Mise à jour de la date de « Dernière mise à jour »') }}</li>
        </ul>
        <p>{{ __('Votre utilisation continue de la Plateforme après la publication des modifications constitue votre acceptation de la politique révisée.') }}</p>

        <hr class="my-4">

        {{-- 12. Contact et plaintes --}}
        <h4 class="cs_mb_15">12. {{ __('Contact et plaintes') }}</h4>
        <p>{{ __('Pour toute question relative à la protection de vos données personnelles :') }}</p>
        <p>
            <strong>{{ __('Responsable de la protection des renseignements personnels') }}</strong><br>
            {{ config('app.name') }}<br>
            {{ __('Courriel') }} : <a href="mailto:{{ config('mail.from.address', 'privacy@example.com') }}">{{ config('mail.from.address', 'privacy@example.com') }}</a>
        </p>

        <p class="mt-3">{{ __('Si vous n\'êtes pas satisfait de notre réponse, vous pouvez déposer une plainte auprès de l\'autorité compétente :') }}</p>
        <ul>
            <li><strong>{{ __('Canada') }}</strong> : {{ __('Commissariat à la protection de la vie privée du Canada') }} - <a href="https://www.priv.gc.ca" target="_blank" rel="noopener">www.priv.gc.ca</a></li>
            <li><strong>{{ __('Québec') }}</strong> : {{ __('Commission d\'accès à l\'information du Québec (CAI)') }} - <a href="https://www.cai.gouv.qc.ca" target="_blank" rel="noopener">www.cai.gouv.qc.ca</a></li>
            <li><strong>{{ __('Union européenne') }}</strong> : {{ __('Votre autorité nationale de protection des données (ex. : CNIL en France)') }} - <a href="https://www.cnil.fr" target="_blank" rel="noopener">www.cnil.fr</a></li>
            <li><strong>{{ __('Californie') }}</strong> : {{ __('California Privacy Protection Agency') }} - <a href="https://cppa.ca.gov" target="_blank" rel="noopener">cppa.ca.gov</a></li>
        </ul>

    </div>
</div>

<div class="cs_height_85 cs_height_lg_80"></div>

@endsection
