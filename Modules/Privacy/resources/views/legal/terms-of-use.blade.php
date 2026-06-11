{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', __('Conditions d\'utilisation') . ' - ' . config('app.name'))
@section('meta_description', __('Conditions d\'utilisation de laveille.ai — veille technologique et intelligence artificielle.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Conditions d\'utilisation')])
@endsection

@section('content')
@php
    $company = config('privacy.company');
    $doc = config('privacy.documents.terms');
@endphp
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post">
                        <h2>{{ __('Conditions d\'utilisation') }}</h2>
                        <p style="color: #999; font-size: 13px;">
                            <strong>{{ __('Version') }}&nbsp;:</strong> {{ $doc['version'] }}<br>
                            <strong>{{ __('Date d\'entrée en vigueur') }}&nbsp;:</strong> {{ \Carbon\Carbon::parse($doc['updated_at'])->translatedFormat('d F Y') }}
                        </p>

                        <div class="entry-details" style="line-height: 1.8;">
                            <div style="background: #f8f9fa; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px;">
                                <h4 style="margin-top: 0;">{{ __('Table des matières') }}</h4>
                                <ol>
                                    <li><a href="#acceptation">{{ __('Acceptation des conditions') }}</a></li>
                                    <li><a href="#service">{{ __('Description du service') }}</a></li>
                                    <li><a href="#compte">{{ __('Comptes utilisateurs') }}</a></li>
                                    <li><a href="#contenu">{{ __('Contenu généré par les utilisateurs') }}</a></li>
                                    <li><a href="#ia">{{ __('Résumés par intelligence artificielle') }}</a></li>
                                    <li><a href="#avis-pro">{{ __('Avis de non-responsabilité professionnelle') }}</a></li>
                                    <li><a href="#shorturl">{{ __('Raccourcisseur d\'URL (veille.la, go3.ca et tout autre domaine exploité)') }}</a></li>
                                    <li><a href="#outils">{{ __('Outils interactifs') }}</a></li>
                                    <li><a href="#rss">{{ __('Agrégation RSS et contenu tiers') }}</a></li>
                                    <li><a href="#affiliation">{{ __('Liens d\'affiliation') }}</a></li>
                                    <li><a href="#propriete">{{ __('Propriété intellectuelle') }}</a></li>
                                    <li><a href="#anti-scraping">{{ __('Interdiction de moissonnage et d\'entraînement d\'IA') }}</a></li>
                                    <li><a href="#usage">{{ __('Comportement acceptable') }}</a></li>
                                    <li><a href="#moderation">{{ __('Modération') }}</a></li>
                                    <li><a href="#retrait">{{ __('Procédure de retrait de contenu contrefaisant') }}</a></li>
                                    <li><a href="#infolettre">{{ __('Infolettre') }}</a></li>
                                    <li><a href="#transparence">{{ __('Transparence algorithmique') }}</a></li>
                                    <li><a href="#disponibilite">{{ __('Disponibilité, modification et fermeture du service') }}</a></li>
                                    <li><a href="#garantie">{{ __('Exclusion de garantie') }}</a></li>
                                    <li><a href="#responsabilite">{{ __('Limitation de responsabilité et plafond de dommages') }}</a></li>
                                    <li><a href="#force-majeure">{{ __('Force majeure') }}</a></li>
                                    <li><a href="#indemnisation">{{ __('Indemnisation') }}</a></li>
                                    <li><a href="#loi">{{ __('Loi applicable') }}</a></li>
                                    <li><a href="#modification">{{ __('Modification des conditions') }}</a></li>
                                    <li><a href="#divisibilite">{{ __('Divisibilité') }}</a></li>
                                    <li><a href="#survie">{{ __('Survie des obligations') }}</a></li>
                                    <li><a href="#cession">{{ __('Cession et transfert') }}</a></li>
                                    <li><a href="#non-renonciation">{{ __('Non-renonciation') }}</a></li>
                                    <li><a href="#confidentialite">{{ __('Protection des renseignements personnels') }}</a></li>
                                    <li><a href="#accessibilite">{{ __('Accessibilité') }}</a></li>
                                    <li><a href="#contact">{{ __('Coordonnées') }}</a></li>
                                </ol>
                            </div>

                            {{-- SECTION 1 : ACCEPTATION --}}
                            <h3 id="acceptation">{{ __('1. Acceptation des conditions') }}</h3>
                            <p>{{ __('En accédant au site laveille.ai (ci-après le « Service »), vous acceptez d\'être lié par les présentes conditions d\'utilisation. Si vous n\'acceptez pas ces conditions, vous ne devez pas utiliser le Service. Le Service est exploité par MEMORA solutions (incorporation), 1501, rue Saint-Benoit, L\'Ancienne-Lorette (Québec) G2E 1P2, Canada (ci-après l\'« exploitant »). NEQ : 1170260492.') }}</p>

                            {{-- SECTION 2 : DESCRIPTION DU SERVICE --}}
                            <h3 id="service">{{ __('2. Description du service') }}</h3>
                            <p>{{ __('Le Service est une plateforme de veille technologique et d\'intelligence artificielle (IA) gratuite. Les fonctionnalités incluent :') }}</p>
                            <ul>
                                <li>{{ __('L\'agrégation de nouvelles spécialisées en technologie et IA') }}</li>
                                <li>{{ __('Un répertoire d\'outils d\'IA') }}</li>
                                <li>{{ __('Un glossaire terminologique sur l\'IA') }}</li>
                                <li>{{ __('Une liste d\'acronymes liés au milieu de l\'éducation au Québec') }}</li>
                                <li>{{ __('Un service de raccourcisseur d\'URL (veille.la, go3.ca et tout autre domaine associé)') }}</li>
                                <li>{{ __('Un blogue éditorial proposant des analyses et réflexions') }}</li>
                                <li>{{ __('Une boutique en ligne de produits imprimés à la demande (les achats sont régis par nos') }} <a href="{{ route('legal.sales') }}">{{ __('conditions de vente') }}</a>)</li>
                                <li>{{ __('Des outils interactifs gratuits (calculatrices, générateurs, compteurs)') }}</li>
                                <li>{{ __('Une infolettre hebdomadaire sur l\'IA et la technologie') }}</li>
                            </ul>

                            {{-- SECTION 3 : COMPTES --}}
                            <h3 id="compte">{{ __('3. Comptes utilisateurs') }}</h3>
                            <p>{{ __('L\'accès à certaines fonctionnalités peut nécessiter la création d\'un compte. Le Service utilise une authentification sans mot de passe via l\'envoi d\'un code unique (OTP) par courriel ou par connexion sociale (Google, GitHub). Vous êtes responsable de maintenir la confidentialité de l\'accès à votre boîte courriel ou à vos comptes tiers. Toute activité effectuée sous votre compte est réputée être la vôtre.') }}</p>
                            <p>{{ __('Vous devez avoir au moins 16 ans pour créer un compte. Pour effectuer des achats sur la boutique, vous devez avoir au moins 18 ans ou avoir l\'autorisation d\'un parent ou tuteur. L\'exploitant se réserve le droit de suspendre ou de supprimer tout compte, sans préavis, en cas de violation des présentes conditions, d\'inactivité prolongée ou de comportement abusif.') }}</p>

                            {{-- SECTION 4 : UGC --}}
                            <h3 id="contenu">{{ __('4. Contenu généré par les utilisateurs') }}</h3>
                            <p>{{ __('En soumettant du contenu (notamment des suggestions de corrections, des votes, des idées pour la feuille de route ou des signalements de bogues), vous accordez à l\'exploitant une licence mondiale, non exclusive, gratuite, perpétuelle et transférable pour utiliser, reproduire, modifier, adapter, publier et exploiter ce contenu dans le but exclusif d\'améliorer le Service. Vous garantissez détenir tous les droits nécessaires sur ledit contenu et renoncez expressément, conformément à l\'article 14.1(2) de la Loi sur le droit d\'auteur (L.R.C. 1985, ch. C-42), à tout droit moral (notamment le droit à l\'attribution et le droit au respect de l\'intégrité de l\'œuvre) sur ce contenu, pour toute la durée de la protection accordée par la loi, au profit de l\'exploitant ainsi que de ses ayants cause et licenciés, dans la mesure permise par la loi.') }}</p>

                            {{-- SECTION 5 : IA --}}
                            <h3 id="ia">{{ __('5. Résumés par intelligence artificielle') }}</h3>
                            <p>{{ __('L\'utilisateur reconnaît que les résumés, analyses et contenus produits par les outils d\'intelligence artificielle (IA) sur la plateforme peuvent comporter des « hallucinations », des biais algorithmiques, des omissions ou des inexactitudes factuelles. L\'exploitant ne peut être tenu responsable de la véracité ou de l\'exhaustivité de ces contenus. Il incombe à l\'utilisateur de valider systématiquement les informations auprès des sources originales. Le contenu généré par IA ne constitue en aucun cas une validation officielle ou un avis professionnel.') }}</p>
                            <p>{{ __('Les modèles d\'IA utilisés sont fournis par des prestataires tiers (notamment OpenRouter, Google Gemini, OpenAI, Anthropic et Mistral) et peuvent varier selon la disponibilité et les performances. L\'exploitant se réserve le droit de changer de modèle ou de prestataire sans préavis. Les prompts sauvegardés par les utilisateurs ne sont pas utilisés pour l\'entraînement de modèles d\'IA et restent la propriété de l\'utilisateur, sous licence d\'utilisation accordée à l\'exploitant selon la section 4.') }}</p>

                            {{-- SECTION 6 : AVIS NON-RESPONSABILITÉ PROFESSIONNELLE --}}
                            <h3 id="avis-pro">{{ __('6. Avis de non-responsabilité professionnelle') }}</h3>
                            <p>{{ __('Le contenu diffusé sur laveille.ai, incluant les articles de veille, le glossaire et les outils d\'analyse, est fourni à des fins strictement informatives et éducatives. Ce contenu ne constitue pas et ne doit pas être interprété comme un conseil juridique, financier, technique, médical ou professionnel. L\'utilisateur ne doit pas agir sur la base de ces informations sans avoir préalablement obtenu l\'avis d\'un professionnel qualifié.') }}</p>

                            {{-- SECTION 7 : SHORTURL --}}
                            <h3 id="shorturl">{{ __('7. Raccourcisseur d\'URL (veille.la, go3.ca et tout autre domaine exploité)') }}</h3>
                            <p>{{ __('L\'utilisation du service de raccourcissement d\'URL est soumise au respect des lois en vigueur. Il est strictement interdit de raccourcir des liens menant vers du contenu illégal, malveillant (hameçonnage, virus), haineux ou pornographique. L\'exploitant collecte des statistiques anonymes de clics pour assurer le bon fonctionnement et la sécurité du service.') }}</p>
                            <p>{{ __('Les liens créés au moyen du raccourcisseur d\'URL veille.la sont fournis sans aucune garantie de disponibilité, de pérennité ou d\'accessibilité, et laveille.ai se réserve le droit de supprimer tout lien raccourci à tout moment, sans préavis ni obligation de justification. L\'exploitant décline toute responsabilité si la page de destination d\'un lien raccourci est supprimée, modifiée, déplacée ou devient autrement inaccessible, et n\'assume aucune responsabilité quant au contenu, à l\'exactitude ou à la légalité des sites vers lesquels ces liens redirigent. L\'utilisateur reconnaît qu\'il utilise le raccourcisseur d\'URL à ses propres risques.') }}</p>
                            <p>{{ __('laveille.ai agit uniquement comme un intermédiaire technique offrant un service de référence et de redirection automatisée vers des contenus de tiers ; elle ne crée pas, ne sélectionne pas, ne modifie pas et ne contrôle pas les contenus accessibles au moyen des liens raccourcis. Conformément à la Loi concernant le cadre juridique des technologies de l\'information (RLRQ, c. C-1.1, notamment son article 22), elle n\'est tenue à aucune obligation générale de surveillance des contenus vers lesquels ces liens redirigent, et sa responsabilité ne pourrait être engagée que si, ayant de fait connaissance du caractère illicite d\'un lien, elle omettait d\'agir promptement pour le retirer ou en bloquer l\'accès.') }}</p>
                            <p>{{ __('Seul l\'utilisateur ayant créé un lien raccourci est responsable, de manière exclusive, de l\'existence, de l\'accessibilité, de l\'exactitude et de la licéité du contenu vers lequel ce lien redirige, de même que des titres, descriptions et métadonnées qu\'il y associe. En aucun cas laveille.ai ou MEMORA solutions ne pourront être tenues responsables du contenu des sites, pages ou ressources de tiers accessibles par l\'intermédiaire de ces liens, ni d\'une atteinte aux droits de propriété intellectuelle, aux droits de la personnalité, à la vie privée ou à tout autre droit de tiers en résultant.') }}</p>
                            <p>{{ __('L\'utilisateur garantit que les liens qu\'il crée respectent l\'ensemble des lois applicables ainsi que les droits des tiers. Il s\'engage à indemniser et à tenir indemnes laveille.ai et MEMORA solutions, ainsi que leurs dirigeants et préposés, de toute réclamation, plainte, poursuite, perte, dommage, coût ou dépense (y compris les honoraires raisonnables d\'avocats) découlant directement ou indirectement des liens qu\'il a créés ou partagés, ou de tout manquement de sa part aux présentes conditions. La présente exclusion de responsabilité s\'applique dans toute la mesure permise par la loi, sous réserve des cas de faute intentionnelle ou de faute lourde (art. 1474 C.c.Q.).') }}</p>
                            <p>{{ __('Tout lien raccourci expire automatiquement après une période d\'inactivité continue de douze (12) mois, définie par l\'absence totale de visites. Chaque consultation du lien réinitialise ce délai pour une nouvelle période de douze (12) mois. Les utilisateurs inscrits disposent de la faculté de prolonger manuellement la validité de leurs liens à partir de leur tableau de bord, sans toutefois excéder la durée maximale de douze (12) mois par intervention.') }}</p>
                            <p>{{ __('Un avis de fin de validité sera transmis par courrier électronique trente (30) jours avant l\'échéance à titre de message transactionnel. À l\'expiration du délai, le lien est supprimé de façon définitive et irréversible. L\'exploitant décline toute responsabilité quant aux conséquences découlant de la suppression automatique des liens expirés ou de l\'impossibilité de récupérer les données y afférentes.') }}</p>

                            {{-- SECTION 8 : OUTILS INTERACTIFS --}}
                            <h3 id="outils">{{ __('8. Outils interactifs') }}</h3>
                            <p>{{ __('laveille.ai met à la disposition de ses utilisateurs divers outils interactifs — notamment des calculatrices, des générateurs de mots de passe, des compteurs et d\'autres utilitaires — fournis « tels quels » et « selon la disponibilité », à des fins informatives et éducatives uniquement, sans aucune garantie de précision, d\'exhaustivité, de fiabilité ou d\'adéquation à un usage particulier. Les résultats produits par ces outils ne constituent en aucun cas un conseil professionnel de quelque nature que ce soit, y compris, sans s\'y limiter, un conseil fiscal, juridique, financier, technique ou médical, et ne sauraient se substituer à la consultation d\'un professionnel qualifié. L\'exploitant décline toute responsabilité à l\'égard de tout dommage direct, indirect, accessoire ou consécutif pouvant découler de l\'utilisation de ces outils ou de la confiance accordée à leurs résultats.') }}</p>
                            <p>{{ __('Rien dans la présente section n\'a pour effet d\'exclure ou de limiter la responsabilité qui ne peut l\'être en vertu de la loi, notamment en cas de préjudice corporel ou moral, de faute lourde ou intentionnelle (article 1474 du Code civil du Québec), ni les garanties et droits impératifs conférés au consommateur par la Loi sur la protection du consommateur.') }}</p>

                            {{-- SECTION 9 : RSS --}}
                            <h3 id="rss">{{ __('9. Agrégation RSS et contenu tiers') }}</h3>
                            <p>{{ __('Le Service agrège des flux RSS provenant de sources tierces. Ce contenu est affiché avec une attribution claire vers la source originale. L\'exploitant n\'exerce aucun contrôle éditorial sur ces sites tiers et décline toute responsabilité quant à leur contenu, leur légalité ou leur disponibilité.') }}</p>

                            {{-- SECTION 10 : AFFILIATION --}}
                            <h3 id="affiliation">{{ __('10. Liens d\'affiliation') }}</h3>
                            <p>{{ __('Par souci de transparence, l\'utilisateur est informé que certains liens présents sur le Service peuvent être des liens d\'affiliation. Cela signifie que l\'exploitant peut percevoir une commission si vous effectuez un achat ou une action sur le site partenaire, sans frais supplémentaires pour vous.') }}</p>

                            {{-- SECTION 11 : PI --}}
                            <h3 id="propriete">{{ __('11. Propriété intellectuelle') }}</h3>
                            <p>{{ __('Le contenu éditorial original, la structure du site, le code source et le design appartiennent à MEMORA solutions (incorporation) ou à ses partenaires. Le contenu tiers (articles de presse, logos de logiciels tiers) demeure la propriété exclusive de ses auteurs ou ayants droit respectifs. Toute reproduction non autorisée du contenu éditorial est interdite sans l\'accord écrit préalable de l\'exploitant.') }}</p>

                            {{-- SECTION 12 : ANTI-SCRAPING --}}
                            <h3 id="anti-scraping">{{ __('12. Interdiction de moissonnage et d\'entraînement d\'IA') }}</h3>
                            <p>{{ __('L\'exploitant autorise expressément les moteurs de recherche, les assistants d\'intelligence artificielle (notamment GPTBot, ClaudeBot, Google-Extended, PerplexityBot) et les autres robots respectant les directives du fichier robots.txt publié sur le site laveille.ai à indexer, explorer et citer publiquement le contenu du site, à condition que toute utilisation comporte une attribution claire accompagnée d\'un lien actif vers la source originale. Cette autorisation vise à soutenir la visibilité du site dans les moteurs de recherche et les moteurs génératifs (optimisation GEO/AEO) et ne constitue en aucun cas une licence générale d\'exploitation commerciale ou de reproduction intégrale.') }}</p>
                            <p>{{ __('Il demeure strictement interdit de procéder à un moissonnage massif, à une extraction systématique, au contournement des mesures techniques mises en place (y compris les règles du robots.txt, les limites de débit ou tout autre mécanisme de contrôle d\'accès), ainsi qu\'à toute reproduction, redistribution ou utilisation commerciale du contenu sans autorisation expresse. L\'utilisation du contenu à des fins d\'entraînement, de développement ou d\'amélioration de modèles d\'intelligence artificielle ou de grands modèles de langage (LLM) n\'est permise que dans les limites définies par le fichier robots.txt et sous réserve d\'une attribution conforme. L\'exploitant se réserve le droit de modifier, de restreindre ou de révoquer ces autorisations à tout moment, unilatéralement et sans préavis, en mettant à jour ledit fichier robots.txt.') }}</p>

                            {{-- SECTION 13 : COMPORTEMENT --}}
                            <h3 id="usage">{{ __('13. Comportement acceptable') }}</h3>
                            <p>{{ __('Vous vous engagez à utiliser le Service de manière responsable. Sont strictement interdits :') }}</p>
                            <ul>
                                <li>{{ __('le pollupostage (spam), la publicité non sollicitée ou le démarchage commercial') }}</li>
                                <li>{{ __('l\'utilisation de robots, de scripts ou d\'outils automatisés pour le moissonnage de données (scraping) sans autorisation') }}</li>
                                <li>{{ __('les tentatives d\'intrusion, de piratage ou de contournement des mesures de sécurité') }}</li>
                                <li>{{ __('le harcèlement, les menaces, l\'intimidation ou tout comportement abusif envers les autres utilisateurs ou l\'exploitant') }}</li>
                                <li>{{ __('la diffusion de discours haineux, discriminatoire, diffamatoire ou incitant à la violence (Code criminel, art. 319)') }}</li>
                                <li>{{ __('l\'usurpation d\'identité ou la création de faux comptes') }}</li>
                                <li>{{ __('la diffusion de contenu illégal, obscène, pornographique ou portant atteinte aux droits de tiers') }}</li>
                                <li>{{ __('toute tentative de décompiler, désassembler ou procéder à l\'ingénierie inverse du Service') }}</li>
                            </ul>

                            {{-- SECTION 14 : MODÉRATION --}}
                            <h3 id="moderation">{{ __('14. Modération') }}</h3>
                            <p>{{ __('L\'exploitant se réserve le droit discrétionnaire de modérer, de refuser ou de supprimer tout contenu soumis par un utilisateur (commentaires, suggestions, liens raccourcis) qui contreviendrait aux présentes conditions ou qui serait jugé inapproprié, sans préavis ni justification.') }}</p>

                            {{-- SECTION 15 : RETRAIT CONTENU --}}
                            <h3 id="retrait">{{ __('15. Procédure de retrait de contenu contrefaisant') }}</h3>
                            <p>{{ __('L\'exploitant respecte les droits de propriété intellectuelle. Si vous croyez qu\'un contenu diffusé sur la plateforme porte atteinte à vos droits d\'auteur, vous pouvez soumettre une notification de retrait à l\'adresse politiques@memora.ca. La notification doit inclure une description précise de l\'œuvre protégée et la localisation de l\'infraction alléguée. L\'exploitant s\'engage à traiter ces demandes et, le cas échéant, à retirer le contenu litigieux dans un délai raisonnable.') }}</p>

                            {{-- SECTION 16 : INFOLETTRE --}}
                            <h3 id="infolettre">{{ __('16. Infolettre') }}</h3>
                            <p>{{ __('L\'inscription à l\'infolettre est volontaire et nécessite votre consentement exprès, conformément à la Loi canadienne anti-pourriel (LCAP, L.C. 2010, ch. 23). Vous pouvez vous désinscrire en tout temps via le lien de désabonnement inclus dans chaque envoi ou en contactant l\'exploitant. Après votre désabonnement, votre adresse courriel est conservée dans une liste de suppression pendant 2 ans afin d\'éviter toute réinscription involontaire et de respecter nos obligations légales.') }}</p>

                            {{-- SECTION 17 : TRANSPARENCE ALGORITHMIQUE --}}
                            <h3 id="transparence">{{ __('17. Transparence algorithmique') }}</h3>
                            <p>{{ __('Conformément à l\'article 12.1 de la Loi sur la protection des renseignements personnels dans le secteur privé (Loi 25), l\'exploitant informe l\'utilisateur que le service utilise des systèmes d\'intelligence artificielle pour générer des résumés et classer l\'information. L\'utilisateur a le droit de demander des précisions sur les paramètres principaux ayant mené à une décision ou à une recommandation automatisée le concernant, le cas échéant, en communiquant avec le responsable de la protection des renseignements personnels.') }}</p>
                            <p>{{ __('Lorsqu\'une décision est fondée exclusivement sur un traitement automatisé, l\'utilisateur a également le droit de faire corriger les renseignements personnels utilisés et de présenter ses observations à une personne en mesure de réviser ladite décision.') }}</p>

                            {{-- SECTION 18 : DISPONIBILITÉ, MODIFICATION ET FERMETURE --}}
                            <h3 id="disponibilite">{{ __('18. Disponibilité, modification et fermeture du service') }}</h3>

                            <h4>{{ __('Panne et indisponibilité') }}</h4>
                            <p>{{ __('Le service laveille.ai est fourni « tel quel » et sans aucune garantie de disponibilité continue. Nous ne garantissons pas que le site sera exempt d\'erreurs, de pannes, d\'interruptions ou de défaillances techniques. Nous déclinons toute responsabilité pour toute perte ou tout dommage résultant de pannes, de travaux de maintenance, de cyberattaques, de défaillances techniques ou de toute autre cause d\'indisponibilité du service. L\'utilisation du site se fait à vos propres risques.') }}</p>

                            <h4>{{ __('Perte de données') }}</h4>
                            <p>{{ __('Nous ne fournissons aucune garantie quant à la sauvegarde, la conservation ou la restitution des données utilisateur, y compris, mais sans s\'y limiter, les favoris, les liens raccourcis, les contributions ou les informations de compte. Il incombe à l\'utilisateur de sauvegarder ses propres données. Aucune indemnisation ne sera versée en cas de perte de données. Cependant, en cas d\'incident de confidentialité impliquant des renseignements personnels, une notification sera effectuée conformément aux dispositions de la Loi 25 (art. 3.5).') }}</p>

                            <h4>{{ __('Fermeture du service') }}</h4>
                            <p>{{ __('Nous nous réservons le droit de fermer, de suspendre ou de cesser de fournir le service laveille.ai, en tout ou en partie, à tout moment, sans préavis ni obligation de notre part. Vous reconnaissez qu\'aucun droit acquis ne vous confère un droit sur le service ou ses fonctionnalités.') }}</p>

                            <h4>{{ __('Modification du service') }}</h4>
                            <p>{{ __('Nous nous réservons le droit de modifier, de suspendre ou de supprimer toute fonctionnalité du service laveille.ai, à tout moment, sans préavis. Votre utilisation continue du service après toute modification constitue votre acceptation de ces modifications.') }}</p>

                            {{-- SECTION 19 : EXCLUSION DE GARANTIE --}}
                            <h3 id="garantie">{{ __('19. Exclusion de garantie') }}</h3>
                            <p>{{ __('Le service laveille.ai est fourni gratuitement, « tel quel » et « selon sa disponibilité », sans aucune représentation, garantie ou condition de quelque nature que ce soit, expresse ou implicite. L\'exploitant décline expressément toute garantie de qualité marchande, d\'adéquation à un usage particulier, d\'absence de contrefaçon ou d\'exactitude. Aucun engagement n\'est pris quant à la disponibilité continue du service, à l\'absence d\'erreurs, de virus ou d\'interruptions techniques. L\'utilisateur reconnaît utiliser le service à ses propres risques.') }}</p>
                            <p>{{ __('Ces exclusions s\'appliquent dans la mesure permise par la loi et ne portent pas atteinte aux garanties légales impératives prévues par la Loi sur la protection du consommateur lorsque celle-ci s\'applique.') }}</p>

                            {{-- SECTION 20 : LIMITATION RESPONSABILITÉ + CAP --}}
                            <h3 id="responsabilite">{{ __('20. Limitation de responsabilité et plafond de dommages') }}</h3>
                            <p>{{ __('Étant donné la nature gratuite du service, la responsabilité totale et cumulative de l\'exploitant, de ses employés ou représentants, pour toute réclamation découlant des présentes ou de l\'utilisation du site, est limitée à un montant maximal de cent dollars canadiens (100,00 $ CAN). En aucun cas l\'exploitant ne sera responsable des dommages indirects, spéciaux, punitifs, accessoires ou consécutifs, incluant notamment la perte de profits, la perte de données, l\'interruption des affaires ou les dommages réputationnels.') }}</p>
                            <p>{{ __('Toute réclamation découlant de l\'utilisation du Service doit être formulée avec diligence. Les délais de prescription prévus par la loi s\'appliquent, notamment celui de trois (3) ans prévu à l\'article 2925 du Code civil du Québec. Aucune disposition des présentes conditions ne modifie la durée légale de la prescription, conformément à l\'article 2884 du Code civil du Québec.') }}</p>
                            <p>{{ __('Malgré ce qui précède, aucune disposition n\'exclut ni ne limite la responsabilité de l\'exploitant pour le préjudice corporel ou moral causé à autrui, pour sa faute lourde ou intentionnelle (article 1474 du Code civil du Québec), ni pour toute responsabilité qui ne peut être limitée en vertu de la loi applicable.') }}</p>

                            {{-- SECTION 21 : FORCE MAJEURE --}}
                            <h3 id="force-majeure">{{ __('21. Force majeure') }}</h3>
                            <p>{{ __('Conformément à l\'article 1470 du Code civil du Québec, l\'exploitant ne pourra être tenu responsable de l\'inexécution ou du retard dans l\'exécution de ses obligations si ce manquement résulte d\'un cas de force majeure. Sont considérés comme tels, sans s\'y limiter : les catastrophes naturelles, les cyberattaques d\'envergure, les pannes généralisées des réseaux de télécommunications, les conflits armés, les grèves ou les mesures gouvernementales restrictives.') }}</p>

                            {{-- SECTION 22 : INDEMNISATION --}}
                            <h3 id="indemnisation">{{ __('22. Indemnisation') }}</h3>
                            <p>{{ __('Vous acceptez d\'indemniser et de dégager de toute responsabilité MEMORA solutions, ses dirigeants, employés et représentants uniquement contre les réclamations de tiers découlant (i) d\'un manquement fautif de votre part à vos obligations, (ii) d\'une violation des présentes conditions ou (iii) d\'une atteinte illicite aux droits d\'un tiers, à concurrence des montants raisonnables et proportionnés réellement engagés. Cette obligation d\'indemnisation ne s\'applique pas dans la mesure où le dommage résulte de la faute de l\'exploitant.') }}</p>

                            {{-- SECTION 23 : LOI APPLICABLE --}}
                            <h3 id="loi">{{ __('23. Loi applicable') }}</h3>
                            <p>{{ __('Les présentes conditions sont régies par les lois de la province de Québec et les lois fédérales du Canada applicables. Tout litige relatif au Service sera soumis à la compétence exclusive des tribunaux du district judiciaire de Québec.') }}</p>

                            {{-- SECTION 24 : MODIFICATION --}}
                            <h3 id="modification">{{ __('24. Modification des conditions') }}</h3>
                            <p>{{ __('L\'exploitant se réserve le droit de modifier les présentes conditions à tout moment. Les modifications entrent en vigueur dès leur publication sur le site. La poursuite de l\'utilisation du Service après publication vaut acceptation des nouvelles conditions.') }}</p>

                            {{-- SECTION 25 : DIVISIBILITÉ --}}
                            <h3 id="divisibilite">{{ __('25. Divisibilité') }}</h3>
                            <p>{{ __('Si une disposition des présentes conditions est jugée invalide, illégale ou inapplicable par un tribunal compétent, cette disposition sera interprétée de manière à refléter l\'intention initiale des parties dans la mesure permise par la loi, et les autres dispositions des conditions d\'utilisation demeureront pleinement en vigueur.') }}</p>

                            {{-- SECTION 26 : SURVIE --}}
                            <h3 id="survie">{{ __('26. Survie des obligations') }}</h3>
                            <p>{{ __('Les dispositions qui, par leur nature, doivent survivre à la fin des présentes conditions continueront de s\'appliquer après la résiliation de l\'accès au service ou la fermeture du compte de l\'utilisateur. Cela inclut, sans s\'y limiter, les sections relatives à la propriété intellectuelle, aux limitations de responsabilité, aux exclusions de garantie, à l\'indemnisation et au droit applicable.') }}</p>

                            {{-- SECTION 27 : CESSION --}}
                            <h3 id="cession">{{ __('27. Cession et transfert') }}</h3>
                            <p>{{ __('L\'exploitant se réserve le droit de céder, de transférer ou de déléguer ses droits et obligations en vertu des présentes à un tiers, notamment dans le cadre d\'une fusion, d\'une acquisition ou d\'une vente d\'actifs, sans le consentement préalable de l\'utilisateur. L\'utilisateur ne peut céder ses droits ou obligations sans l\'accord écrit exprès de l\'exploitant.') }}</p>

                            {{-- SECTION 28 : NON-RENONCIATION --}}
                            <h3 id="non-renonciation">{{ __('28. Non-renonciation') }}</h3>
                            <p>{{ __('Le fait pour l\'exploitant de ne pas se prévaloir d\'un manquement à l\'une quelconque des obligations contenues dans les présentes conditions, ou de tarder à exercer un droit qui lui est conféré, ne saurait être interprété comme une renonciation définitive à ce droit ou à l\'exécution de ladite obligation pour l\'avenir.') }}</p>

                            {{-- SECTION 29 : CONFIDENTIALITÉ --}}
                            <h3 id="confidentialite">{{ __('29. Protection des renseignements personnels') }}</h3>
                            <p>{{ __('La collecte et le traitement de vos renseignements personnels sont régis par notre') }} <a href="{{ route('legal.privacy') }}">{{ __('politique de confidentialité') }}</a>{{ __(', qui fait partie intégrante des présentes conditions. Nos pratiques sont conformes à la Loi 25 du Québec (Loi sur la protection des renseignements personnels dans le secteur privé) et à la Loi sur la protection des renseignements personnels et les documents électroniques (LPRPDE) du Canada.') }}</p>

                            {{-- SECTION 30 : ACCESSIBILITÉ --}}
                            <h3 id="accessibilite">{{ __('30. Accessibilité') }}</h3>
                            <p>{{ __('L\'exploitant s\'efforce de rendre le Service accessible au plus grand nombre et vise à respecter les normes WCAG 2.2 niveau AA, sans toutefois garantir une conformité intégrale et continue. Si vous rencontrez des difficultés d\'accessibilité, veuillez nous contacter à politiques@memora.ca afin que nous puissions y remédier dans la mesure du possible.') }}</p>

                            {{-- SECTION 31 : CONTACT --}}
                            <h3 id="contact">{{ __('31. Coordonnées') }}</h3>
                            <p>{{ __('Pour toute question concernant ces conditions :') }}</p>
                            <ul>
                                <li><strong>MEMORA solutions</strong> ({{ __('exploitation') }} : laveille.ai)</li>
                                <li>1501, rue Saint-Benoit, L'Ancienne-Lorette (Québec) G2E 1P2, Canada</li>
                                <li>{{ __('Courriel') }}&nbsp;: <a href="mailto:politiques@memora.ca">politiques@memora.ca</a></li>
                                <li>{{ __('Téléphone') }}&nbsp;: 418-800-6656 / {{ __('sans frais') }} : 1-833-363-6672</li>
                            </ul>

                            <hr>
                            <p style="color: #999; font-size: 12px;">
                                {{ __('Version') }} {{ $doc['version'] }} -
                                {{ __('Date d\'entrée en vigueur') }}&nbsp;: {{ \Carbon\Carbon::parse($doc['updated_at'])->translatedFormat('d F Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
