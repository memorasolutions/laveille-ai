{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', __('Politique de confidentialité') . ' - ' . config('app.name'))
@section('meta_description', __('Politique de confidentialité de laveille.ai — RGPD, Loi 25, LPRPDE.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Politique de confidentialité')])
@endsection

@section('content')
@php
    $locale = app()->getLocale();
    $company = config('privacy.company');
    $doc = config('privacy.documents.privacy_policy');
    $categories = config('privacy.categories');
    $rights = config('privacy.rights');
    $jurisdictions = config('privacy.jurisdictions');
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
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post">
                        <h2>{{ __('Politique de confidentialité') }}</h2>
                        <p style="color: #999; font-size: 13px;">
                            <strong>{{ __('Version') }}&nbsp;:</strong> {{ $doc['version'] }}<br>
                            <strong>{{ __('Dernière mise à jour') }}&nbsp;:</strong> {{ \Carbon\Carbon::parse($doc['updated_at'])->translatedFormat('d F Y') }}
                        </p>

                        <div class="entry-details" style="line-height: 1.8;">
                            <div style="background: #f8f9fa; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px;">
                                <h4 style="margin-top: 0;">{{ __('Table des matières') }}</h4>
                                <ol>
                                    <li><a href="#introduction">{{ __('Introduction et lois applicables') }}</a></li>
                                    <li><a href="#controller">{{ __('Responsable du traitement') }}</a></li>
                                    <li><a href="#data-collected">{{ __('Données personnelles collectées') }}</a></li>
                                    <li><a href="#legal-bases">{{ __('Fondements juridiques') }}</a></li>
                                    <li><a href="#purposes">{{ __('Finalités du traitement') }}</a></li>
                                    <li><a href="#contributions">{{ __('Contributions des membres') }}</a></li>
                                    <li><a href="#ia">{{ __('Résumés par intelligence artificielle') }}</a></li>
                                    <li><a href="#shorturl">{{ __('Raccourcisseur d\'URL (veille.la)') }}</a></li>
                                    <li><a href="#rss">{{ __('Agrégation RSS et contenu tiers') }}</a></li>
                                    <li><a href="#newsletter">{{ __('Infolettre') }}</a></li>
                                    <li><a href="#cookies">{{ __('Cookies et traceurs') }}</a></li>
                                    <li><a href="#sharing">{{ __('Communication et transferts') }}</a></li>
                                    <li><a href="#retention">{{ __('Durée de conservation') }}</a></li>
                                    <li><a href="#rights">{{ __('Vos droits') }}</a></li>
                                    <li><a href="#security">{{ __('Mesures de sécurité') }}</a></li>
                                    <li><a href="#efvp">{{ __('Évaluation des facteurs relatifs à la vie privée (EFVP)') }}</a></li>
                                    <li><a href="#minors">{{ __('Mineurs') }}</a></li>
                                    <li><a href="#contact">{{ __('Contact, DPO et autorités') }}</a></li>
                                </ol>
                            </div>

                            <h3 id="introduction">{{ __('1. Introduction et lois applicables') }}</h3>
                            <p>{{ __('Cette politique explique la façon dont nous collectons, utilisons et protégeons vos données personnelles, conformément aux lois suivantes :') }}</p>
                            <ul>
                                <li>{{ __('RGPD — Règlement (UE) 2016/679 (Règlement général sur la protection des données)') }}</li>
                                <li>{{ __('Loi 25 — Loi modernisant des dispositions législatives en matière de protection des renseignements personnels (2021, c. 25, Québec)') }}</li>
                                <li>{{ __('LPRPDE / PIPEDA — Loi sur la protection des renseignements personnels et les documents électroniques (L.C. 2000, ch. 5)') }}</li>
                                <li>{{ __('Directive ePrivacy — Directive 2002/58/CE (protection de la vie privée dans le secteur des communications électroniques)') }}</li>
                            </ul>

                            <h3 id="controller">{{ __('2. Responsable du traitement') }}</h3>
                            <p>
                                <strong>{{ $company['name'] }}</strong><br>
                                {{ $company['address'] }}<br>
                                {{ __('Délégué à la protection des données (DPO)') }}&nbsp;: {{ $company['dpo_name'] }}<br>
                                {{ __('Courriel') }}&nbsp;: <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a>
                            </p>

                            <h3 id="data-collected">{{ __('3. Données personnelles collectées') }}</h3>
                            <ul>
                                <li>{{ __('Données d\'identité (nom, prénom, courriel)') }}</li>
                                <li>{{ __('Données de connexion (adresse IP, identifiants de session)') }}</li>
                                <li>{{ __('Données de navigation (pages consultées, préférences)') }}</li>
                                <li>{{ __('Données de consentement et choix de cookies') }}</li>
                            </ul>

                            <h3 id="legal-bases">{{ __('4. Fondements juridiques') }}</h3>
                            <ul>
                                <li><strong>{{ __('Consentement') }}</strong>&nbsp;: {{ __('pour les cookies non essentiels et le marketing') }}</li>
                                <li><strong>{{ __('Exécution de contrat') }}</strong>&nbsp;: {{ __('pour la gestion de votre compte et la fourniture du service') }}</li>
                                <li><strong>{{ __('Intérêt légitime') }}</strong>&nbsp;: {{ __('pour la sécurité et l\'amélioration du service') }}</li>
                                <li><strong>{{ __('Obligation légale') }}</strong>&nbsp;: {{ __('pour la conservation requise par la loi') }}</li>
                            </ul>

                            <h3 id="purposes">{{ __('5. Finalités du traitement') }}</h3>
                            <ul>
                                <li>{{ __('Gestion des comptes et fourniture du service') }}</li>
                                <li>{{ __('Sécurité et prévention de la fraude') }}</li>
                                <li>{{ __('Analyses statistiques et amélioration du service') }}</li>
                                <li>{{ __('Personnalisation de l\'expérience utilisateur') }}</li>
                                <li>{{ __('Respect des obligations légales et réglementaires') }}</li>
                            </ul>

                            <h3 id="contributions">{{ __('6. Contributions des membres') }}</h3>
                            <p>{{ __('Lorsque vous participez à l\'amélioration de notre plateforme via les suggestions, les votes sur les fonctionnalités, le partage d\'idées pour notre feuille de route ou le signalement de bogues, nous collectons les informations que vous choisissez de nous transmettre. Ces données incluent votre identifiant d\'utilisateur, le contenu de votre message et les métadonnées de soumission.') }}</p>
                            <p>{{ __('La base juridique de ce traitement repose sur votre consentement lors de l\'envoi de la contribution, ainsi que sur notre intérêt légitime à améliorer l\'expérience utilisateur et la performance technique de nos services.') }}</p>

                            <h3 id="ia">{{ __('7. Résumés par intelligence artificielle') }}</h3>
                            <p>{{ __('La plateforme utilise des technologies d\'intelligence artificielle pour générer des résumés de contenus de veille. Nous appliquons une politique stricte de protection de la vie privée : aucune donnée personnelle identifiable n\'est transmise aux modèles d\'IA lors de ces traitements automatisés.') }}</p>
                            <p>{{ __('Les contenus générés par IA sont clairement identifiés comme tels dans l\'interface. Conformément à la Loi 25 (art. 12.1), vous disposez d\'un droit d\'opposition au traitement automatisé et pouvez demander des précisions sur les paramètres ayant mené à la génération d\'un contenu spécifique vous concernant, le cas échéant.') }}</p>

                            <h3 id="shorturl">{{ __('8. Raccourcisseur d\'URL (veille.la)') }}</h3>
                            <p>{{ __('Dans le cadre de la diffusion de la veille, nous utilisons le service de raccourcissement d\'URL veille.la. Lorsqu\'un utilisateur clique sur un lien raccourci, nous collectons les données techniques suivantes :') }}</p>
                            <ul>
                                <li>{{ __('L\'adresse IP (anonymisée pour les statistiques)') }}</li>
                                <li>{{ __('L\'agent utilisateur (type de navigateur et système d\'exploitation)') }}</li>
                                <li>{{ __('Le référent (la page de provenance du clic)') }}</li>
                                <li>{{ __('L\'horodatage précis du clic') }}</li>
                            </ul>
                            <p>{{ __('Ces données sont traitées exclusivement à des fins de statistiques d\'utilisation et de mesure d\'audience. Elles sont conservées pour une durée maximale de 12 mois avant d\'être supprimées ou anonymisées de manière irréversible.') }}</p>

                            <h3 id="rss">{{ __('9. Agrégation RSS et contenu tiers') }}</h3>
                            <p>{{ __('Notre service agrège des flux RSS provenant de diverses sources publiques. Ce processus est automatisé et ne collecte aucune donnée personnelle auprès des éditeurs tiers ou des utilisateurs lors de la simple synchronisation des flux. Nous respectons l\'attribution de la source originale pour chaque contenu affiché.') }}</p>
                            <p>{{ __('La responsabilité de laveille.ai est limitée à son propre contenu éditorial. Nous ne pouvons être tenus responsables des pratiques de confidentialité ou du contenu des sites tiers vers lesquels les flux RSS pointent.') }}</p>

                            <h3 id="newsletter">{{ __('10. Infolettre') }}</h3>
                            <p>{{ __('Pour l\'envoi de nos actualités et alertes de veille, nous collectons votre adresse courriel et, de manière optionnelle, votre prénom. Ce service est géré via le prestataire tiers Brevo, dont les serveurs assurent la sécurité des envois.') }}</p>
                            <p>{{ __('Ce traitement est effectué conformément à la Loi canadienne anti-pourriel (LCAP, L.C. 2010, ch. 23) et repose sur votre consentement exprès. Vous pouvez vous désinscrire à tout moment via le lien de désabonnement présent dans chaque envoi. Vos données sont conservées tant que vous demeurez abonné, puis archivées pour une période de 3 ans après votre désinscription à des fins de preuve de conformité, avant suppression définitive.') }}</p>

                            <h3 id="cookies">{{ __('11. Cookies et traceurs') }}</h3>
                            <p>{{ __('Des cookies de différentes catégories sont utilisés sur notre site. Vous pouvez gérer vos préférences à tout moment via notre bannière de consentement.') }}</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" style="font-size: 13px;">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Catégorie') }}</th>
                                            <th>{{ __('Cookie') }}</th>
                                            <th>{{ __('Fournisseur') }}</th>
                                            <th>{{ __('Finalité') }}</th>
                                            <th>{{ __('Durée') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($categories as $catKey => $category)
                                        @foreach($category['cookies'] as $cookie)
                                        <tr>
                                            <td>{{ $locale === 'fr' ? $category['label_fr'] : $category['label_en'] }}</td>
                                            <td><code>{{ $cookie['name'] }}</code></td>
                                            <td>{{ $cookie['provider'] }}</td>
                                            <td>{{ $locale === 'fr' ? $cookie['purpose_fr'] : $cookie['purpose_en'] }}</td>
                                            <td>{{ $cookie['duration'] }}</td>
                                        </tr>
                                        @endforeach
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p style="font-size: 13px;">
                                {{ __('Pour plus de détails, consultez notre') }} <a href="{{ url('/cookie-policy') }}">{{ __('politique des cookies') }}</a>.
                            </p>

                            <h3 id="sharing">{{ __('12. Communication et transferts de données') }}</h3>
                            <ul>
                                <li>{{ __('Partage avec des prestataires de service (hébergement, paiement, analyse) dans le cadre strict de la fourniture du service') }}</li>
                                <li>{{ __('Transferts internationaux (hors Québec, Canada ou UE) uniquement avec des garanties appropriées (clauses contractuelles types, décisions d\'adéquation)') }}</li>
                                <li>{{ __('Aucune vente de données personnelles à des tiers') }}</li>
                            </ul>

                            <h3 id="retention">{{ __('13. Durée de conservation') }}</h3>
                            <ul>
                                <li>{{ __('Données de compte : pendant la durée de la relation contractuelle, puis archivage légal') }}</li>
                                <li>{{ __('Données de connexion : 12 mois maximum') }}</li>
                                <li>{{ __('Cookies : selon les durées indiquées dans le tableau ci-dessus') }}</li>
                                <li>{{ __('Preuves de consentement : 5 ans (conformément au RGPD art. 7)') }}</li>
                                <li>{{ __('Demandes d\'exercice de droits : durée du traitement + 3 ans en archivage') }}</li>
                            </ul>

                            <h3 id="rights">{{ __('14. Vos droits') }}</h3>
                            <p>{{ __('Conformément aux lois applicables, vous disposez des droits suivants :') }}</p>
                            <ul>
                                @foreach($rights['types'] as $right)
                                    <li>{{ $rightsLabels[$right] ?? $right }}</li>
                                @endforeach
                            </ul>
                            <p>
                                {{ __('Pour exercer vos droits, utilisez notre') }}
                                <a href="{{ route('legal.rights') }}">{{ __('formulaire de demande') }}</a>
                                {{ __('ou contactez notre DPO à l\'adresse') }}
                                <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a>.
                                {{ __('Nous répondrons dans un délai de') }}
                                <strong>{{ $rights['response_delay_days'] }} {{ __('jours') }}</strong>.
                                {{ __('Certaines demandes peuvent nécessiter la vérification de votre identité.') }}
                            </p>

                            <h3 id="security">{{ __('15. Mesures de sécurité') }}</h3>
                            <p>{{ __('Nous mettons en place des mesures techniques et organisationnelles pour protéger vos données :') }}</p>
                            <ul>
                                <li>{{ __('Chiffrement des données en transit (TLS) et au repos') }}</li>
                                <li>{{ __('Contrôles d\'accès stricts et authentification') }}</li>
                                <li>{{ __('Audits de sécurité réguliers') }}</li>
                                <li>{{ __('Formation du personnel à la protection des données') }}</li>
                            </ul>

                            <h3 id="efvp">{{ __('16. Évaluation des facteurs relatifs à la vie privée (EFVP)') }}</h3>
                            <p>{{ __('Conformément à l\'article 3.3 de la Loi modernisant des dispositions législatives en matière de protection des renseignements personnels (Loi 25, Québec), notre organisation s\'engage à effectuer une Évaluation des facteurs relatifs à la vie privée (EFVP) pour tout projet impliquant la collecte, l\'utilisation, la communication, la conservation ou la destruction de renseignements personnels.') }}</p>
                            <p>{{ __('Les types de projets visés par cette obligation incluent notamment :') }}</p>
                            <ul>
                                <li>{{ __('Tout nouveau traitement de renseignements personnels') }}</li>
                                <li>{{ __('L\'implantation d\'une nouvelle technologie ayant un impact sur la vie privée') }}</li>
                                <li>{{ __('Tout transfert de renseignements personnels à l\'extérieur du Québec') }}</li>
                                <li>{{ __('Les projets impliquant du profilage ou la prise de décision automatisée') }}</li>
                            </ul>
                            <p>{{ __('Le processus d\'EFVP comprend :') }}</p>
                            <ul>
                                <li>{{ __('L\'identification des risques d\'atteinte à la vie privée') }}</li>
                                <li>{{ __('La mise en place de mesures d\'atténuation appropriées') }}</li>
                                <li>{{ __('La consultation de la Commission d\'accès à l\'information (CAI) lorsqu\'un risque élevé est identifié') }}</li>
                            </ul>
                            <p>
                                {{ __('Une copie de l\'EFVP est disponible sur demande auprès de notre responsable de la protection des renseignements personnels à l\'adresse :') }}
                                <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a>.
                            </p>

                            <h3 id="minors">{{ __('17. Mineurs') }}</h3>
                            <p>{{ __('Conformément à l\'article 14.1 de la Loi sur la protection des renseignements personnels dans le secteur privé (RLRQ c P-39.1), nos services ne sont pas destinés aux personnes de moins de 14 ans. Nous ne collectons pas sciemment de données auprès de mineurs sans le consentement parental requis par la loi applicable.') }}</p>

                            <h3 id="contact">{{ __('18. Contact, DPO et autorités de contrôle') }}</h3>
                            <p>{{ __('Pour toute question ou exercice de vos droits :') }}</p>
                            <ul>
                                <li><strong>{{ __('DPO') }}&nbsp;:</strong> {{ $company['dpo_name'] }} - <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a></li>
                                <li><strong>{{ __('Adresse') }}&nbsp;:</strong> {{ $company['address'] }}</li>
                            </ul>
                            <p>{{ __('Vous pouvez également contacter les autorités de contrôle compétentes :') }}</p>
                            <ul>
                                @foreach($jurisdictions as $jKey => $jurisdiction)
                                    @if(!empty($jurisdiction['authorities']))
                                    <li>
                                        <strong>{{ $jurisdiction['label'] }}&nbsp;:</strong>
                                        @foreach($jurisdiction['authorities'] as $name => $url)
                                            <a href="{{ $url }}" target="_blank" rel="noopener">{{ $name }}</a>@if(!$loop->last), @endif
                                        @endforeach
                                    </li>
                                    @endif
                                @endforeach
                            </ul>

                            <hr>
                            <p style="color: #999; font-size: 12px;">
                                {{ __('Version') }} {{ $doc['version'] }} -
                                {{ __('Dernière mise à jour') }}&nbsp;: {{ \Carbon\Carbon::parse($doc['updated_at'])->translatedFormat('d F Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
