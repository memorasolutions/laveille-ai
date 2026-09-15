<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{--
    Générateur de politique d'utilisation de l'IA (#2584).

    TOUT L'ASSEMBLAGE SE FAIT DANS LE NAVIGATEUR, et c'est le coeur de l'offre plutôt qu'un détail
    technique : une PME qui nomme ses outils, ses fournisseurs et son responsable des renseignements
    personnels décrit sa surface d'attaque. Lui demander d'envoyer cela sur un serveur pour obtenir
    un document sur la protection des données serait une contradiction que n'importe quel lecteur
    attentif relèverait. Aucun appel réseau ici, et un test le vérifie.
--}}
@extends(fronttheme_layout())

@section('title', $tool->name . ' - ' . config('app.name'))
@section('meta_description', "Générateur gratuit de politique d'utilisation de l'IA pour les PME du Québec. Dix questions, un document prêt à adapter. Tes réponses ne quittent jamais ton navigateur.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection

@push('styles')
<style>
    /* Le thème masque les cases à cocher natives (`display: none`) pour les remplacer par un
       décor posé sur le label. Ce décor n'existe pas dans ce formulaire, si bien que la question 6
       s'affichait sans aucune case cliquable - mesuré au navigateur avant livraison. On rétablit
       donc la case ici, sans toucher au thème, pour ne rien changer ailleurs sur le site. */
    #pia-outils .form-check-input {
        display: inline-block !important;
        width: 1em;
        height: 1em;
        margin-right: .4rem;
        vertical-align: middle;
    }

    #pia-outils .form-check {
        display: flex;
        align-items: center;
        padding-left: 0;
    }

    #pia-form label { font-weight: 600; }
    #pia-resultat pre { tab-size: 2; }
</style>
@endpush

@section('content')
<div class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">

                <div class="alert alert-info" role="note" style="border-left:4px solid var(--c-primary,#064E5A);">
                    <strong>Tes réponses ne quittent jamais ton navigateur.</strong>
                    Rien n'est envoyé sur nos serveurs, rien n'est enregistré. Tu peux fermer la page
                    en tout temps : le document disparaît avec elle.
                </div>

                <h1 class="mt-4">{{ $tool->name }}</h1>

                <p>
                    Une politique d'utilisation de l'intelligence artificielle est un document court qui
                    dit à ton équipe ce qu'elle peut faire avec ces outils, ce qu'elle ne doit pas y
                    déposer, et qui décide quand un cas nouveau se présente. Sans elle, chacun improvise,
                    et les décisions se prennent au moment le plus pressé.
                </p>

                <p>
                    Ce générateur assemble un point de départ en dix questions. Il ne remplace ni un
                    avocat, ni une analyse de tes risques réels : il te donne une base écrite que tu
                    adaptes ensuite à ta réalité.
                </p>

                <h2 class="h4 mt-5">Ce que la Loi 25 exige, et ce qu'elle n'exige pas</h2>

                <p>
                    La Loi 25 n'est pas une loi sur l'intelligence artificielle et n'en crée aucune. Elle
                    encadre la protection des renseignements personnels. Ses obligations te concernent
                    donc dès qu'un outil, intelligent ou non, traite des renseignements sur des personnes
                    identifiables : clients, employés, candidats, fournisseurs.
                </p>

                <p>
                    La conséquence est simple. Un outil d'IA qui ne voit que des données publiques ne
                    déclenche rien de particulier ; le même outil auquel on confie un dossier client
                    entre pleinement dans le champ de la loi. C'est l'usage qui décide, jamais la
                    technologie.
                </p>

                <hr class="my-5">

                <h2 class="h4">Dix questions, puis ton document</h2>

                <form id="pia-form" class="card p-4 mt-3" style="background:var(--c-surface,#F8FAFB);">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="pia-entreprise">1. Nom de l'entreprise</label>
                            <input type="text" class="form-control" id="pia-entreprise" placeholder="Votre entreprise" autocomplete="organization">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="pia-secteur">2. Secteur</label>
                            <select class="form-select" id="pia-secteur">
                                <option value="services professionnels">Services professionnels</option>
                                <option value="commerce">Commerce</option>
                                <option value="santé">Santé</option>
                                <option value="construction">Construction</option>
                                <option value="éducation">Éducation</option>
                                <option value="organisme sans but lucratif">Organisme sans but lucratif</option>
                                <option value="autre" selected>Autre</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="pia-taille">3. Nombre de personnes</label>
                            <select class="form-select" id="pia-taille">
                                <option value="moins de 5">Moins de 5</option>
                                <option value="5 à 20" selected>5 à 20</option>
                                <option value="21 à 100">21 à 100</option>
                                <option value="plus de 100">Plus de 100</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="pia-approbation">4. Qui approuve un nouvel outil</label>
                            <select class="form-select" id="pia-approbation">
                                <option value="une personne désignée">Une personne désignée</option>
                                <option value="un comité">Un comité</option>
                                <option value="la direction" selected>La direction</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="pia-responsable">5. Titre du responsable des renseignements personnels</label>
                            <input type="text" class="form-control" id="pia-responsable" value="la direction">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="pia-courriel">10. Courriel de signalement d'un incident</label>
                            <input type="email" class="form-control" id="pia-courriel" placeholder="securite@votreentreprise.ca">
                        </div>

                        <div class="col-12">
                            <span class="form-label d-block" id="pia-outils-libelle">6. Outils déjà utilisés</span>
                            <div id="pia-outils" role="group" aria-labelledby="pia-outils-libelle" class="d-flex flex-wrap gap-3">
                                @foreach ([
                                    'ChatGPT', 'Copilot', 'Gemini', 'Claude',
                                    'des outils de transcription', 'des outils de traduction',
                                ] as $i => $outil)
                                    <div class="form-check">
                                        <input class="form-check-input pia-outil" type="checkbox" value="{{ $outil }}" id="pia-outil-{{ $i }}">
                                        <label class="form-check-label" for="pia-outil-{{ $i }}">{{ $outil }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="pia-agents">7. Agents et automatisation</label>
                            <select class="form-select" id="pia-agents">
                                <option value="interdits">Interdits</option>
                                <option value="permis avec approbation" selected>Permis avec approbation</option>
                                <option value="permis">Permis</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="pia-comptes">8. Comptes personnels au travail</label>
                            <select class="form-select" id="pia-comptes">
                                <option value="interdits">Interdits</option>
                                <option value="tolérés, sans données d'affaires" selected>Tolérés, sans données d'affaires</option>
                                <option value="permis avec approbation">Permis avec approbation</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="pia-revision">9. Fréquence de révision</label>
                            <select class="form-select" id="pia-revision">
                                <option value="tous les six mois">Tous les six mois</option>
                                <option value="une fois par année" selected>Une fois par année</option>
                                <option value="tous les deux ans">Tous les deux ans</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary" id="pia-generer">Générer ma politique</button>
                        </div>
                    </div>
                </form>

                <section id="pia-resultat" class="mt-4" hidden>
                    <h2 class="h4">Ta politique</h2>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-primary" id="pia-copier">Copier le texte</button>
                        <button type="button" class="btn btn-outline-secondary" id="pia-txt">Télécharger en .txt</button>
                        <button type="button" class="btn btn-outline-secondary" id="pia-md">Télécharger en .md</button>
                    </div>

                    <p id="pia-message" role="status" aria-live="polite" class="text-success"></p>

                    <pre id="pia-sortie" style="white-space:pre-wrap;background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:1.5rem;max-height:60vh;overflow-y:auto;font-family:var(--f-body),system-ui,sans-serif;font-size:14px;line-height:1.7;"></pre>
                </section>

                <h2 class="h4 mt-5">Trois erreurs fréquentes</h2>

                <ol>
                    <li class="mb-2">
                        <strong>Interdire l'IA sans rien offrir.</strong> L'usage ne disparaît pas, il
                        devient invisible : les gens prennent leur compte personnel sur leur téléphone, et
                        tu perds toute possibilité de savoir ce qui sort de l'entreprise.
                    </li>
                    <li class="mb-2">
                        <strong>Écrire une politique de quinze pages.</strong> Personne ne la lit, donc
                        personne ne l'applique. Deux pages appliquées valent mieux que quinze pages
                        classées.
                    </li>
                    <li class="mb-2">
                        <strong>Ne jamais la réviser.</strong> Une politique écrite il y a deux ans nomme
                        des outils qui n'existent plus et ignore ceux que ton équipe utilise aujourd'hui.
                    </li>
                </ol>

                <h2 class="h4 mt-5">Questions fréquentes</h2>

                <details class="mb-2">
                    <summary><strong>Est-ce que ce document rend mon entreprise en règle avec la loi ?</strong></summary>
                    <p class="mt-2">
                        Non. C'est un point de départ, pas une attestation. Une politique bien écrite ne
                        dit rien de ce que tu fais réellement : ce sont tes pratiques qui comptent, et
                        elles s'évaluent au cas par cas, idéalement avec un professionnel qualifié.
                    </p>
                </details>

                <details class="mb-2">
                    <summary><strong>Mes réponses sont-elles enregistrées quelque part ?</strong></summary>
                    <p class="mt-2">
                        Non. L'assemblage se fait entièrement dans ton navigateur et aucune réponse n'est
                        transmise. Tu peux le vérifier toi-même en ouvrant l'inspecteur réseau de ton
                        navigateur pendant que tu cliques sur « Générer ma politique » : il ne se passe
                        rien.
                    </p>
                </details>

                <details class="mb-2">
                    <summary><strong>Faut-il une politique même à trois personnes ?</strong></summary>
                    <p class="mt-2">
                        Oui, et elle sera courte. L'intérêt n'est pas la longueur du texte mais le fait
                        d'avoir tranché à l'avance deux ou trois questions : ce qu'on ne dépose jamais
                        dans un outil grand public, et qui décide quand un cas nouveau se présente.
                    </p>
                </details>

                <details class="mb-2">
                    <summary><strong>Puis-je modifier le document obtenu ?</strong></summary>
                    <p class="mt-2">
                        Tu le dois. Le texte est volontairement générique : il faut y mettre les noms
                        réels, les outils que tu utilises vraiment, et retirer ce qui ne s'applique pas
                        chez toi.
                    </p>
                </details>

                <div class="alert alert-warning mt-5" role="note">
                    <strong>Avertissement.</strong> Ce document est un modèle de départ. Il ne constitue
                    pas un avis juridique et ne garantit aucun résultat devant un tribunal ou une
                    autorité de surveillance. Pour évaluer tes obligations réelles, consulte un
                    professionnel qualifié.
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * Assemblage local de la politique. Aucune requête réseau, c'est la promesse de l'outil.
 * Les valeurs entre crochets du gabarit sont remplacées par les réponses du formulaire.
 */
(function () {
    'use strict';

    var formulaire = document.getElementById('pia-form');
    if (!formulaire) { return; }

    var sortie = document.getElementById('pia-sortie');
    var resultat = document.getElementById('pia-resultat');
    var message = document.getElementById('pia-message');

    function valeur(id, repli) {
        var e = document.getElementById(id);
        var v = e && e.value ? e.value.trim() : '';
        return v !== '' ? v : repli;
    }

    function outilsCoches() {
        var coches = Array.prototype.slice.call(document.querySelectorAll('.pia-outil:checked'));
        if (coches.length === 0) { return "les outils approuvés par l'entreprise"; }
        return coches.map(function (c) { return c.value; }).join(', ');
    }

    function sections(r) {
        return [
            ['Objet et portée',
                "Cette politique encadre l'utilisation des outils d'intelligence artificielle par les employés, dirigeants, pigistes et fournisseurs de " + r.entreprise + ". Elle vise les outils d'IA générative, de transcription, d'analyse de données, d'automatisation de tâches et les agents conversationnels. Les comptes personnels utilisés pour des activités professionnelles sont inclus. Elle s'applique à l'ensemble des activités de l'entreprise, qui compte " + r.taille + " personnes et oeuvre dans le secteur " + r.secteur + "."],

            ['Principes directeurs',
                "L'utilisation de l'IA doit servir une finalité d'affaires légitime et clairement identifiable. Nous privilégions les outils qui limitent la collecte de données au strict nécessaire. Toute décision produite ou suggérée par un outil automatisé est supervisée par une personne avant d'avoir un effet. La responsabilité de ce qui est fait avec l'IA appartient à la personne qui l'utilise, jamais à l'outil. Nous respectons les droits de propriété intellectuelle et évitons de produire du contenu susceptible de porter atteinte à des droits d'auteur ou à des marques."],

            ['Rôles et responsabilités',
                "L'approbation d'un nouvel outil relève de " + r.approbation + ". " + r.responsable.charAt(0).toUpperCase() + r.responsable.slice(1) + " répond de la gestion des renseignements personnels traités par ces outils. Tout incident, notamment une fuite de données, est signalé sans délai à " + r.responsable + ". Chaque personne de l'équipe collabore avec ces responsables et suit les procédures établies."],

            ["Classification des données et règle d'or",
                "Les données se classent en quatre niveaux. Publique : information diffusable sans restriction, par exemple un communiqué. Interne : usage interne seulement, par exemple une procédure. Confidentielle : secret commercial, données financières, contrats. Renseignements personnels ou sensibles : santé, coordonnées bancaires, dossiers clients ou employés.\n\nRÈGLE D'OR : ne jamais déposer de données confidentielles ni de renseignements personnels dans un outil d'IA grand public accessible par le web. Pour ces données, seuls les outils approuvés sont permis."],

            ['Approbation et registre des outils',
                "Aucun nouvel outil d'IA, extension de navigateur ou connecteur ne peut être mis en service sans l'approbation préalable de " + r.approbation + ". Un registre central consigne les outils autorisés : nom, fournisseur, finalité, types de données traitées et date d'approbation. Ce registre est tenu à jour et reste consultable sur demande. Les outils absents du registre ne sont pas autorisés. À ce jour, l'entreprise utilise " + r.outils + "."],

            ['Supervision humaine obligatoire',
                "Avant d'utiliser, de publier ou de transmettre à un client une sortie produite par un outil d'IA, une personne vérifie l'exactitude des informations, l'absence de biais discriminatoire, le respect des droits d'auteur et la cohérence avec les politiques internes. Cette vérification est consignée sommairement, par exemple par une note au dossier. L'IA ne remplace pas le jugement professionnel. Les agents et l'automatisation sont " + r.agents + "."],

            ['Ce qui est interdit',
                "1. Utiliser un outil d'IA qui n'a pas été approuvé.\n2. Saisir des renseignements personnels ou des données confidentielles dans un outil grand public.\n3. Produire du contenu trompeur, diffamatoire ou illégal.\n4. Laisser un système décider seul d'une question touchant une personne, notamment en embauche, en évaluation ou en fin d'emploi.\n5. Contourner une mesure de sécurité pour accéder à un outil d'IA.\n\nLes comptes personnels au travail sont " + r.comptes + "."],

            ["Signalement d'un incident",
                "Si tu crois qu'un renseignement personnel ou une donnée confidentielle a été saisi par erreur dans un outil non approuvé, signale-le dans l'heure qui suit à " + r.courriel + ". Indique l'outil utilisé, les données concernées et les circonstances. Ne tente pas de corriger la situation seul sans autorisation : certaines actions, comme supprimer un historique, peuvent compliquer l'évaluation de ce qui a réellement été exposé."],

            ['Formation et mise à jour',
                "Les personnes concernées reçoivent une formation sur cette politique au moment de son adoption, puis à leur arrivée. La politique est révisée " + r.revision + ", ou plus tôt si un changement important survient : nouvel outil largement adopté, incident, ou modification des obligations applicables. Les versions antérieures sont conservées."],

            ['Avertissement',
                "Ce document est un modèle de départ pour encadrer l'utilisation de l'intelligence artificielle. " + r.entreprise + " doit l'adapter à sa réalité opérationnelle et à ses risques propres. Il ne constitue pas un avis juridique. Pour évaluer les obligations légales applicables, notamment lorsque des renseignements personnels sont traités, consulte un professionnel qualifié."],
        ];
    }

    function assembler() {
        var r = {
            entreprise: valeur('pia-entreprise', 'notre entreprise'),
            secteur: valeur('pia-secteur', 'autre'),
            taille: valeur('pia-taille', '5 à 20'),
            approbation: valeur('pia-approbation', 'la direction'),
            responsable: valeur('pia-responsable', 'la direction'),
            outils: outilsCoches(),
            agents: valeur('pia-agents', 'permis avec approbation'),
            comptes: valeur('pia-comptes', "tolérés, sans données d'affaires"),
            revision: valeur('pia-revision', 'une fois par année'),
            courriel: valeur('pia-courriel', 'la personne responsable'),
        };

        var date = new Date().toLocaleDateString('fr-CA', { year: 'numeric', month: 'long', day: 'numeric' });
        var lignes = ["POLITIQUE D'UTILISATION DE L'INTELLIGENCE ARTIFICIELLE", r.entreprise, 'Version du ' + date, ''];

        sections(r).forEach(function (s, i) {
            lignes.push((i + 1) + '. ' + s[0].toUpperCase());
            lignes.push('');
            lignes.push(s[1]);
            lignes.push('');
        });

        return lignes.join('\n');
    }

    function versMarkdown(texte) {
        return texte
            .replace(/^([A-ZÉÈÀÇ'’ ]{10,})$/gm, '# $1')
            .replace(/^(\d+)\. ([A-ZÉÈÀÇ].*)$/gm, '## $1. $2');
    }

    function telecharger(contenu, nom) {
        var blob = new Blob([contenu], { type: 'text/plain;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var lien = document.createElement('a');
        lien.href = url;
        lien.download = nom;
        document.body.appendChild(lien);
        lien.click();
        document.body.removeChild(lien);
        URL.revokeObjectURL(url);
    }

    formulaire.addEventListener('submit', function (evenement) {
        evenement.preventDefault();

        sortie.textContent = assembler();
        resultat.hidden = false;
        message.textContent = '';
        resultat.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // On compte un GESTE, jamais une réponse : aucun contenu du formulaire n'est transmis.
        if (typeof gtag === 'function') {
            gtag('event', 'politique_ia_generee');
        }
    });

    document.getElementById('pia-copier').addEventListener('click', function () {
        if (!navigator.clipboard) {
            message.textContent = "Ton navigateur ne permet pas la copie automatique. Sélectionne le texte, puis copie-le.";
            return;
        }
        navigator.clipboard.writeText(sortie.textContent).then(function () {
            message.textContent = 'Texte copié.';
        }, function () {
            message.textContent = "La copie n'a pas fonctionné. Sélectionne le texte, puis copie-le.";
        });
    });

    document.getElementById('pia-txt').addEventListener('click', function () {
        telecharger(sortie.textContent, 'politique-ia.txt');
        message.textContent = 'Fichier .txt téléchargé.';
    });

    document.getElementById('pia-md').addEventListener('click', function () {
        telecharger(versMarkdown(sortie.textContent), 'politique-ia.md');
        message.textContent = 'Fichier .md téléchargé.';
    });
}());
</script>
@endpush
