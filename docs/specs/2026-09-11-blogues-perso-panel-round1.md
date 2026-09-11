# Panel des 5 oracles - Round 1 (génération divergente en aveugle)

Date : 2026-09-11. Sujet : transformer les blogues personnels (Modules/Authors, laveille.ai)
en produit comparable à ghost.org - gabarits à l'inscription, forfait gratuit/payant avec
domaine personnalisé, quotas d'images par forfait, intégrations tierces, CSS/HTML ouvert aux
payants. Socle factuel imposé : `docs/specs/2026-09-11-blogues-perso-brief-factuel.md` et
`docs/specs/2026-09-11-blogues-perso-mesure-domaines.md`.

Méthode : chaque oracle a reçu le même socle factuel, sa question propre, et les deux
questions communes (mécanisme unique ; idée neuve), SANS voir la réponse des autres. Les 5
ont répondu - aucun échec, aucune session déconnectée. Deux incidents méthodologiques à
signaler : (1) la première réponse de Perplexity à sa question de données s'est contentée de
refléter le contexte fourni sans données chiffrées - une deuxième série de requêtes ciblées a
corrigé le tir ; (2) le navigateur Playwright est partagé avec une autre session active sur ce
même projet, qui a navigué l'onglet claude.ai vers `laveilledestef.test` à deux reprises
pendant l'attente de la réponse - la conversation a chaque fois été retrouvée par son URL
explicite (`https://claude.ai/chat/05abbd75-3966-4ae6-9091-99f2e12d017f`), sans perte de
contenu.

---

## 1. Perplexity (`mcp__perplexity-pro-playwright__pp_search`)

**Question posée** : que facturent RÉELLEMENT Ghost(Pro) et WordPress.com en 2026, et quelles
données existent sur ce qui fait basculer un utilisateur gratuit vers payant sur une
plateforme de blogue ? Chiffres et sources, pas d'impressions.

### Grille de prix Ghost(Pro), 2026 (source : ghost.org/pricing, relevée en direct)

| Palier | Prix | Membres inclus | Ce qui distingue |
|---|---|---|---|
| Starter | 18 $US/mois | 1 000 | Domaine perso inclus, réglages de design simples, 5 Mo/fichier, **pas** d'abonnements payants ni de pourboires |
| Publisher | 29 $US/mois | 1 000 | **Thèmes personnalisés**, 8000+ intégrations, abonnements payants, pourboires, 100 Mo/fichier, 3 paliers premium |
| Business | 199 $US/mois | 10 000 | 15 utilisateurs, support prioritaire, 250 Mo/fichier, 10 paliers premium |
| Custom | sur devis | illimité | IP dédiée, SLA 99,9 % |

Confirme et précise le socle factuel : le domaine est acquis dès Starter, le thème
personnalisé n'arrive qu'à Publisher (+11 $/mois), et le Mo est un plafond par fichier.

### Grille WordPress.com, 2026 (source : wordpress.com/pricing)

| Palier | Prix observable | Stockage | Domaine perso |
|---|---|---|---|
| Free | 0 $ | 1 Go | Non - sous-domaine `.wordpress.com` |
| Personal | dès 2,75 $US/mois (3 ans) ou 4 $US/mois (annuel) | 6 Go | Oui, gratuit 1 an si annuel+ |
| Premium/Business/Commerce | tarif variable selon devise/durée (page publique ne l'expose pas de façon stable) | 13/50/50 Go | Oui, gratuit 1 an si annuel+ |

Point à retenir pour laveille.ai : WordPress.com n'offre le domaine perso qu'à partir du
**premier palier payant**, jamais au gratuit - contrairement à Ghost qui l'inclut dès son
palier le moins cher, mais cohérent avec lui sur le principe que le domaine n'est PAS la
frontière de différenciation coûteuse.

### Données sur la conversion freemium -> payant (sources : ChartMogul, OpenView, Fiscallion,
Sixteen Ventures - citées par Perplexity, non vérifiées de seconde main par ce panel)

| Modèle | Médiane | Top 10-25 % |
|---|---|---|
| Freemium self-serve | 3-5 % | 6-8 % |
| Freemium sales-assisted | 5-7 % | 10-15 % |
| Essai gratuit opt-in (sans carte) | 8,9-18,2 % | - |
| Essai gratuit opt-out (carte requise) | 31,4-48,8 % | 50-60 % |

Le ratio visiteur -> payant tombe sous 0,5 % une fois tout l'entonnoir compté. Trois
déclencheurs d'upgrade reviennent dans les études citées : le blocage concret d'une limite de
palier, un moment de réalisation de valeur (premier succès mesurable), et la croissance
d'usage/d'équipe qui dépasse le forfait gratuit.

### Mécanisme unique (réponse à la question commune)

Perplexity propose un **capability registry déclaratif** : manifestes versionnés dans le code
(ce qui est possible et sûr) + définitions activées en base par tenant (ce que l'auteur
utilise) + moteur d'entitlements central (le tenant a-t-il le droit, dans quelle limite) +
adaptateurs génériques par TYPE de capacité (pas par exemplaire). Nom retenu par Perplexity :
« Registry + manifest + policy engine + adapter/strategy + bus d’événements ». Cela évite
explicitement l'explosion de contrôleurs par intégration et les `if ($plan === ...)` dispersés.

### Idée neuve

Non obtenue clairement : la réponse à la relance sur ce point s'est interrompue avant
d'atteindre la partie « idée de monétisation peu connue » - à ne pas compter comme une idée
neuve produite par cet oracle dans ce round.

---

## 2. ChatGPT (chatgpt.com, via Playwright, compte Stéphane Lapointe Pro)

**Question posée** : où les plateformes de blogue se plantent-elles précisément sur l'éditeur
de thème pour néophyte, et que faut-il RETIRER (pas ajouter) pour qu'un enseignant québécois
publie son premier billet sans aide ?

### Diagnostic par plateforme

| Plateforme | Ce qu'elle fait bien | Où elle donne trop de liberté |
|---|---|---|
| Ghost | Séparation contenu/thème, réglages déclaratifs par thème (type sélection, booléen, couleur, image, texte) | L'expérience varie selon le créateur du thème |
| WordPress.com | Écosystème énorme, variations de styles prédéfinies | Trop de niveaux conceptuels : thème, style, modèle, partie de modèle, bloc, réglage de bloc, page, Site Editor |
| Squarespace | Cohérence visuelle supérieure à WordPress/Wix | Permet encore de modifier polices, couleurs, boutons, formulaires, espacements, animations |
| Wix | Liberté maximale, retour visuel immédiat | Liberté maximale justement : élément par élément |
| Substack | Peu de possibilités donc difficile à casser | Personnalisation trop pauvre pour une identité distinctive |

Diagnostic central : ces plateformes proposent un système où l'utilisateur **construit** une
apparence (police, H1/H2, interligne, marges, cartes, boutons...) alors qu'un néophyte devrait
seulement **choisir** une apparence déjà correcte.

### Ce qu'il faut RETIRER

À l'inscription, ChatGPT ne garde que quatre champs : nom du blogue, style visuel (5 à 8
gabarits maximum, chaque vignette montrant du vrai contenu, pas une image marketing), une
seule couleur d'identité (8 couleurs garanties accessibles, sélecteur HEX caché sous
« Avancé »), logo facultatif. Tout le reste - police, échelle typographique, largeur de
lecture, interligne, marges, contraste, boutons, cartes, navigation, responsive - reste
verrouillé par le gabarit et **ne doit jamais pouvoir être cassé** par l'auteur gratuit ou
payant dans le parcours normal.

Reprise explicite d'une idée de WordPress.com : les « variations de style » qui changent
couleurs ET typographie ensemble plutôt que propriété par propriété - ChatGPT propose de
pousser cette logique plus loin que WordPress.com lui-même.

### Mécanisme unique (Feature Registry)

Chaque gabarit, quota ou intégration devient une entrée de données (`key`, `type`, `module`,
`plans`, `configuration`/`configuration_schema`, `renderer`/`handler`, `enabled`) lue par un
moteur d'entitlements unique (`$features->available()`, `$features->limit()`,
`$features->enabled()`). Règle centrale : « le forfait ne doit pas connaître les thèmes, le
thème ne doit pas connaître le forfait, l'intégration ne doit pas connaître le forfait, la vue
ne doit pas connaître le forfait ». Un gabarit lui-même n'est pas un fichier Blade mais une
configuration de design tokens + composition de composants partagés (20 gabarits peuvent
réutiliser 3 layouts et 4 variantes de carte). Les intégrations passent par un
`IntegrationManager` + `IntegrationProviderInterface` avec un manifeste par fournisseur,
interface générée depuis le schéma.

### Idée neuve : le « contrat visuel »

Chaque gabarit porte un contrat inviolable (largeur de texte 680-760 px, contraste minimum AA,
maximum 2 polices, maximum 1 couleur forte, ratios de titres, longueur de ligne 50-80
caractères). Même le CSS personnalisé des payants pourrait être validé contre ces règles
critiques. Principe : « vous pouvez personnaliser autant que vous voulez tant que vous ne
pouvez pas accidentellement le rendre mauvais » - un garde-fou de qualité visuelle, pas
seulement un système de permissions. Complément : au choix d'un gabarit, poser une question
d'intention (« sobre / chaleureux / magazine ») plutôt qu'une propriété CSS, le système
traduisant l'intention en tokens.

---

## 3. claude.ai (Opus 5 Élevé, via Playwright, compte Stéphane Lapointe Max)

**Question posée** : quel est le risque que personne ne voit dans ce projet aujourd'hui,
visible seulement après six mois d'exploitation réelle ?

### Le risque : le forfait gratuit prête la réputation du domaine

Sous `/@slug`, chaque auteur gratuit publie sur la même origine que les fiches vérifiées de
laveille.ai, ses liens de newsletter et ses pages monétisées par AdSense. Rien ne se voit au
lancement ; vers le 2e-3e mois, des pages `/@slug` commencent à se positionner grâce à
l'autorité du domaine - exactement le signal recherché par les opérateurs de parasite SEO et
d'hameçonnage (hébergement gratuit, domaine propre, sans historique de pourriel). Les
sanctions tombent ensuite en différé, par quatre canaux qui ne se parlent pas :

1. **Google** : la politique anti-abus de réputation de site (mise à jour novembre 2024, selon
   claude.ai) vise précisément les sous-répertoires de sites réputés hébergeant du contenu
   tiers de faible qualité - la forme `/@slug` correspond à ce cas.
2. **AdSense** : des infractions répétées près de contenu interdit peuvent restreindre la
   diffusion à l'échelle du domaine ENTIER, pas seulement de la page fautive.
3. **La newsletter** : les listes de blocage évaluent les domaines présents dans les liens des
   courriels ; une seule page d'hameçonnage sous `laveille.ai` peut faire glisser chaque numéro
   de La Veille de Stef vers les indésirables.
4. **La crédibilité éditoriale** : le même domaine qui appose l'étiquette « contenu généré par
   IA » sur les fiches vérifiées hébergera, sans étiquette, des blogues d'auteurs remplis de
   contenu généré - une contradiction exploitable par un critique.

Pourquoi ça reste invisible : chaque canal a son propre tableau de bord, chacun réagit avec des
semaines/mois de retard, et personne ne les surveille tous à la fois. Facteur aggravant : les
liens entrants s'accumulent mois après mois, rendant un futur déménagement vers un domaine
d'hébergement distinct de plus en plus coûteux (redirections 301 pour tous les auteurs).

**Précédents cités** (à vérifier indépendamment, non recoupés par ce panel) : Netlify a déplacé
en avril 2020 les sites sans domaine personnalisé de `netlify.com` vers `netlify.app` pour des
raisons de sécurité/stabilité ; `netlify.app` figure sur la Public Suffix List. GitHub Pages
fait de même avec `github.io`, Blogger vit sur `blogspot.com`, pas sur `google.com`.

**Parade proposée** : domaine d'hébergement distinct avec sous-domaine par auteur et
inscription à la Public Suffix List (démarche lente, à lancer tôt) ; code personnalisé servi
uniquement sur ce domaine ; sentinelles dès le premier jour (ratio pages d'auteurs
indexées/pages éditoriales, test de placement newsletter, vérification Safe Browsing des pages
d'auteurs).

### Mécanisme unique : le manifeste de capacité

Un gabarit, un quota et une intégration sont trois formes de la même chose : une capacité
déclarée en données, accordée par forfait, configurée par formulaire, appliquée à un point
unique, retirée selon une politique déclarée (`on_revoke`). Architecture en quatre pièces,
chacune écrite une seule fois : (1) un registre en table/JSON versionné, un enregistrement par
exemplaire, zéro fichier zéro contrôleur ; (2) un résolveur unique `Entitlements::for($author)`
qui remplace `EnsurePremium` et `TierManagementService` ; (3) trois points d'application
génériques (rendu via emplacements nommés dans un Blade unique - où `accent_color` et
`font_family` redeviennent utiles comme variables CSS -, validateur de téléversement unique
lisant les `limit`, composant Livewire unique générant les formulaires depuis un schéma) ; (4)
la politique de repli `on_revoke`, décrite comme « la pièce que tout le monde oublie » -
que devient un thème payant ou une intégration active quand la carte expire, cas jamais exercé
aujourd'hui puisque le palier n'a jamais eu d'effet réel. Piste d'implémentation citée :
Laravel Pennant, avec un piège signalé (son pilote base de données garde la première valeur
résolue - une purge est nécessaire au changement de forfait, sinon un auteur rétrogradé reste
payant). Critère de complétude : un neuvième thème ou une troisième intégration se résume à une
ligne de données.

### Idée neuve : l'échelon « Mérité »

Trois niveaux d'adresse au lieu de deux : Gratuit (sous-domaine isolé), **Mérité**
(`laveille.ai/@slug`, accordé sur invitation éditoriale selon des critères publics -
ancienneté, sources citées, aucune étiquette du module de vérification, relecture humaine ; ne
s'achète pas, peut être retiré), Payant (domaine personnalisé, combinable avec le statut
mérité, affiché comme « auteur associé laveille.ai »). Argument : la liste évidente (boutique
de thèmes, assistant IA, commentaires, stats, adhésions, import WordPress) fait déjà mieux chez
Ghost/WordPress.com avec des équipes de centaines de personnes. L'échelon mérité vend ce
qu'eux ne peuvent pas offrir - la caution éditoriale d'une marque québécoise dotée d'un module
de vérification - et transforme le risque de réputation en fossé défensif (modération sur des
dizaines de candidats, pas des milliers d'inscriptions).

---

## 4. Gemini (`agy -p`, Gemini 3.1 Pro High, compte Google AI Pro)

**Question posée** : matrice de l'extrême local - quelle plateforme a survécu dans un marché
subissant une contrainte plus dure que le Québec, et par quel mécanisme transposable ?

### La contrainte et le marché plus extrême

Contrainte identifiée : le petit bassin québécois crée un dilemme - bloquer le contenu ferme la
porte à la croissance (bassin déjà petit), tout laisser gratuit empêche d'en vivre (trafic
insuffisant pour la publicité). Marché plus extrême retenu : l'industrie du webtoon/fiction
sérielle numérique en Corée du Sud/Chine, où le pouvoir d'achat du lecteur cible est quasi nul
ET la concurrence du divertissement gratuit est infinie.

Plateformes citées : KakaoPage et Webtoon. **Chiffre non vérifié par ce panel** : Gemini avance
que KakaoPage aurait généré « plus de 1,5 milliard de dollars US » selon ses rapports
financiers historiques - affirmation de l'oracle, non recoupée indépendamment ici.

### Le mécanisme : paywall temporel (« wait-or-pay »)

Le contenu n'est jamais verrouillé de façon permanente : un nouveau chapitre est exclusif aux
lecteurs payants à sa sortie, puis se déverrouille gratuitement après un délai fixe (7 à 14
jours). Transposition proposée pour laveille.ai : un article est réservé aux abonnés payants
pendant deux semaines après publication, puis devient public. Ce mécanisme segmente
l'audience par le TEMPS plutôt que par une barrière permanente : il capte la valeur des
lecteurs impatients aujourd'hui, tout en garantissant que 100 % du contenu finit par nourrir le
référencement et l'acquisition organique.

### Mécanisme unique : moteur de capacités déclaratives

Bannir la logique conditionnelle (`if tier == premium`) du code pour la transférer dans les
données. Un registre de configuration JSON par forfait/gabarit/intégration, un résolveur
unique chargé en middleware qui construit un objet `context` de capacités pour la requête en
cours, puis application transversale : les gabarits deviennent un unique layout HTML qui lit
le dictionnaire et injecte des variables CSS natives (ressuscitant `accent_color`/
`font_family`) ; les quotas sont lus depuis `context.limits` par un test universel abstrait ;
les intégrations tierces sont une liste d'injections (`{"type":"script","url":...}`) rendue par
une seule boucle en fin de page. Ajouter un palier ou une intégration standard ne demande
aucune modification du code source, seulement une nouvelle entrée en base.

### Idée neuve : le passeport lecteur centralisé

Le domaine personnalisé donne à l'auteur l'apparence d'un site indépendant (SEO, prestige),
mais laveille.ai conserve en arrière-plan une identité de lecteur centralisée : un flux de
redirection transparent vers `auth.laveille.ai` reconnaît un lecteur déjà abonné à un autre
auteur du réseau et transforme le bouton d'inscription en « s'abonner en un clic », sans
nouveau mot de passe. Argument : Ghost offre l'indépendance mais isole chaque lecteur (compte
et carte de crédit à ressaisir par site) ; Substack offre le réseau mais impose sa marque et
prive du domaine personnalisé simple. L'idée combine l'apparence d'un logiciel auto-hébergé
avec le taux de conversion d'un réseau fermé - un avantage qu'aucun des deux ne peut offrir
sous cette forme selon Gemini.

---

## 5. DeepSeek (`mcp__hermes__model_invoke`, deepseek-r1, task_type=reasoning)

**Question posée** : démolis l'idée - pourquoi laveille.ai n'a-t-il aucune raison de
concurrencer Ghost, et que faudrait-il de précis pour que cette conclusion soit fausse ?

### Avertissement obligatoire sur les chiffres

Conformément au piège connu, DeepSeek a produit une avalanche de chiffres présentés avec une
précision trompeuse. **Aucun de ces chiffres n'est traité comme un fait dans ce panel** ; ils
sont listés ici comme DONNÉES DE L'ORACLE, non vérifiées, plusieurs manifestement fabriquées :

- « < 0,1 % du marché francophone des blogs », « > 40 % de conversion nécessaire », « < 5 %
  des visiteurs interagissent (mesuré via Matomo) » - **aucune trace d'un outil Matomo dans le
  code de ce projet** ; chiffre inventé.
- « Ghost.org : 3,4 millions de sites actifs (2023) » - non recoupé ici.
- « Budget mensuel minimal de 2 000 $/mois pour l'infrastructure DNS seule » - estimation
  non sourcée présentée comme un fait.
- **Erreur factuelle avérée** : DeepSeek affirme que le palier Starter de Ghost coûte « 9 $/mois
  après remise annuelle ». C'est FAUX et contredit directement le socle factuel du projet ET la
  vérification Perplexity du même jour (18 $US/mois, facturé annuellement, sans remise
  supplémentaire menant à 9 $). Ce point doit être signalé nominativement comme une
  fabrication de DeepSeek, pas une divergence légitime.
- « Images hébergées dans un dossier local, saturé à 500 Go » - aucune preuve dans le brief
  factuel (`ImagePipelineService` utilise le disque `public` de Laravel, pas un dossier
  décrit comme saturé) ; chiffre inventé.
- « TTFB actuel > 800 ms en moyenne » - jamais mesuré, aucune source.
- « 1000 abonnés à 15 $/mois en 18 mois », « budget de 200 000 $ pour 5000 utilisateurs à
  40 $/utilisateur », « audit de sécurité XSS > 20 000 $ » - tous non sourcés.

### L'argument de démolition, débarrassé des chiffres inventés

Une fois les chiffres retirés, l'argument structurel de DeepSeek reste : (1) l'audience de
laveille.ai est une niche de veille IA, pas un public généraliste de blogueurs - le
désalignement de marché est réel indépendamment du pourcentage cité ; (2) la complexité
technique (thèmes dynamiques, domaines tiers avec SSL, gestion du cycle de vie d'un
abonnement) est sous-estimée par rapport à ce qui existe aujourd'hui dans le code (colonnes
mortes, aucun test de palier réel) ; (3) l'ouverture du CSS/HTML aux payants introduit un
risque XSS qui n'a jamais été audité ; (4) sans une preuve de demande réelle (des auteurs qui
demandent déjà un domaine perso ou plus de personnalisation), le projet reste un pari.

### Ce qui rendrait la démolition fausse

Un signal de demande réel et mesuré (pas un chiffre projeté) : un nombre non trivial d'auteurs
existants qui demandent explicitement un domaine personnalisé ou plus de personnalisation
visuelle ; une preuve que le trafic actuel des mini-sites d'auteurs justifie l'investissement ;
un test de charge réel avant d'promettre un SLA de disponibilité.

### Mécanisme unique

DeepSeek propose un registre de modules avec un fichier `manifest.yml` **par composant** (ex.
`ghost_integration.yml`, `pro_quota.yml`) plus un `ModuleLoader` central et un `QuotaEnforcer`.
**Réserve à noter** : un manifeste par composant reste, dans les faits, un artefact par
exemplaire (même si c'est un fichier de données plutôt qu'un fichier de code) - cette
proposition respecte moins strictement la règle du fondateur que celles de ChatGPT, claude.ai
et Gemini, qui logent explicitement TOUTES les instances dans une table/un registre unique
sans fichier par exemplaire.

### Idée neuve : le score d'impact IA

Score automatisé (ex. 72/100) croisant les citations académiques (Semantic Scholar, Crossref)
et les signaux sociaux (Twitter/Mastodon) pour mesurer l'impact réel d'un article de veille IA,
avec recommandations (« citez cet article pour +15 points »). Absent des comparatifs
Ghost/WordPress.com puisqu'ils se concentrent sur la publication, pas l'analyse. Monétisable
via un palier « Analytics Pro » à 29 $/mois (chiffre non sourcé, aligné par coïncidence sur le
palier Publisher de Ghost).

---

## Synthèse rapide des 5 réponses au mécanisme unique

Quatre oracles sur cinq (Perplexity, ChatGPT, claude.ai, Gemini) convergent, indépendamment et
sans se voir, vers la MÊME architecture : un registre déclaratif de capacités (gabarit, quota,
intégration = trois types de la même chose), stocké en base/JSON, résolu par un point d'entrée
unique par forfait, et appliqué par un petit nombre de points d'application génériques - jamais
un fichier ni un contrôleur par exemplaire. Le cinquième (DeepSeek) converge sur l'esprit mais
propose un manifeste PAR COMPOSANT, ce qui reste plus proche du fichier par exemplaire que les
quatre autres réponses.
