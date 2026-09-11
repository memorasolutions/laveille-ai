# Panel des 5 oracles - Rounds 2 et 3 (réfutation croisée, attaque des survivantes)

Date : 2026-09-11. Suite de `docs/specs/2026-09-11-blogues-perso-panel-round1.md`. Socle factuel :
`docs/specs/2026-09-11-blogues-perso-brief-factuel.md` et
`docs/specs/2026-09-11-blogues-perso-mesure-domaines.md`. Une mesure produite EN PARALLÈLE par un
autre agent, `docs/specs/2026-09-11-autossl-domaine-tiers-mesure.md`, est venue confirmer
factuellement un point discuté au round 3 (note en fin de document) - je ne l'ai pas modifiée,
je la cite.

## Oracles - disponibilité réelle (à lire avant tout le reste)

| Oracle | Round 2 | Round 3 |
|---|---|---|
| **ChatGPT** (chatgpt.com, Playwright) | Complet, conforme au mandat, tue 7 idées dont 1 des siennes | Complet, très approfondi, avec recherche web réelle citant des sources cPanel datées |
| **Gemini** (`agy -p`, Gemini 3.1 Pro Élevé) | Complet, conforme, tue 4 idées dont 1 des siennes | Complet, conforme, tue sa propre idée S4 (Caddy) |
| **DeepSeek** (`mcp__hermes__model_invoke`, deepseek-r1) | Complet, conforme, tue ses 3 propres idées + 1 autre | Complet, conforme, tue sa propre idée S4 (proxy inverse) |
| **Perplexity** (`pp_search`) | **Partiel** - 6 requêtes nécessaires, format non tenu (voir note ci-dessous) | **Non exploitable** - a mal interprété le mot « survivantes » comme thème éditorial et a produit une proposition d'architecture générique hors mandat, malgré reformulation |
| **claude.ai** (Playwright) | **INDISPONIBLE** - 3 tentatives, même cause | **INDISPONIBLE** - 2 tentatives supplémentaires, même cause |

**Perplexity, panne de format (pas une panne de connexion)** : `pp_search` en mode `auto` a
échoué 3 fois de suite à exécuter une tâche de réfutation structurée à partir d'un catalogue
fourni dans la requête - une fois en demandant « envoie-moi les idées » (alors qu'elles étaient
dans la requête), une fois en produisant un tableau tronqué au milieu, une fois en générant une
proposition d'architecture générique sans lien avec le mandat. Le mode `pro` a été essayé une
fois : réponse « colle les 13 idées » alors qu'elles étaient présentes - signature du défaut de
contamination de session documenté dans CLAUDE.md (les Tasks Perplexity Pro polluent les
recherches suivantes), raison pour laquelle ce mode est déconseillé par défaut. Seules des
requêtes très courtes (moins de 100 mots demandés, une ou deux questions à la fois) ont produit
une réponse utilisable. Au round 3, même la version courte a échoué : Perplexity a lu
« attaque des survivantes » comme un ANGLE ÉDITORIAL à traiter en série d'articles plutôt que
comme la méthode du round, et a rendu un modèle de données Laravel générique sans rapport avec
les 7 éléments à trancher. Signalé nominativement, non inventé : Perplexity n'a donc produit
qu'une contribution round 2 partielle (2 mises à mort nommées + 1 idée neuve + 1 vérification de
fait) et aucune contribution round 3 utilisable.

**claude.ai, panne de plateforme documentée (pas une question de contenu)** : 5 tentatives sur 2
conversations différentes, y compris un test minimal (« dis juste ok ») qui a réussi en moins de
10 secondes. Chaque tentative substantielle (round 2 x3, round 3 x2) s'est bloquée indéfiniment
sur « Vérification de la réponse à votre message » (plus de 4 minutes chacune, rechargement de
page sans effet), avec la MÊME erreur console reproduite à chaque fois :
`Failed to load resource: the server responded with a status of 405 () @
https://claude.ai/v1/toolbox/shttp/mcp/b07e23bc-53a6-4006-a9c5-b8a55da32c54`. Diagnostic : un
connecteur/outil MCP rattaché à ce compte claude.ai répond 405 (méthode non autorisée) quand le
modèle tente de l'invoquer pendant une réponse d'analyse complexe, ce qui bloque la génération
entière - même après avoir explicitement demandé « aucun outil, aucune recherche ». Ce n'est PAS
un écran de connexion (le compte est authentifié, un message trivial passe), donc pas un problème
de transport de session : c'est un problème d'intégration côté plateforme, signalé ici plutôt que
contourné en silence. Les propres idées de claude.ai (I5, I6, I7 du round 1) ont néanmoins été
évaluées par les 4 autres oracles disponibles.

Le navigateur Playwright a été PARTAGÉ avec au moins une autre session active sur ce même projet
pendant les deux rounds (conversations « Distinguer le blogue personnel de la crédibilité
éditoriale », « Sécuriser l'authentification... » apparues dans l'historique claude.ai sans
action de ma part) - chaque conversation a été ciblée par son URL explicite (`/chat/<uuid>`),
jamais par « l'onglet courant », conformément à la consigne.

---

## ROUND 2 - Réfutation croisée

Catalogue complet des 13 idées soumises : voir le round 1. Rappel des 4 idées de convergence
(I1/I3/I6/I8) et de la réserve déjà notée sur I11 (manifeste par composant) - non répétés ici.

### Éliminations nommées, par oracle

**ChatGPT** (7 mises à mort, motif précis chacune) :
- **I9** (paywall temporel, Gemini) - « répond à un problème de monétisation qui n'est pas celui
  posé... n'ajoute rien aux risques techniques identifiés ».
- **I13** (score d'impact IA, DeepSeek) - « hors périmètre du produit Authors... prix 29$/mois non
  sourcé ».
- **I12** (démolition générale, DeepSeek) - « pas une solution, ses chiffres sont explicitement
  non sourcés ».
- **I11** (manifeste par composant, DeepSeek) - « la règle du fondateur le disqualifie
  directement... pas suffisamment générique ».
- **I7** (Échelon Mérité, claude.ai) - « mélange tarification et jugement éditorial... multiplie
  les états combinatoires avant même que le palier payant actuel ait un effet réel ».
- **I10** (passeport lecteur, Gemini) - « SSO, cookies inter-domaines... alors que le routage par
  domaine perso n'est même pas validé ».
- **I2** (inscription 4 champs, SA PROPRE idée) - « figer exactement 4 champs et 5-8 gabarits
  crée une contrainte produit arbitraire sans validation utilisateur ; confond bonne UX et règle
  architecturale ».

Autocritique conforme au mandat sur ses 3 idées : I2 tuée (ci-dessus), **I3 survit sous
condition** (risque de devenir un « God Registry » si mal borné), **I4 ne survit PAS telle
quelle** (« valider arbitrairement le CSS personnalisé... devient vite un pseudo-navigateur CSS
complexe, fragile et coûteux ») - remplacée par « tokens + zones de personnalisation sûres ».

**Gemini** (4 mises à mort, dont 1 des siennes) :
- **I11** (DeepSeek) - « disqualification directe par la règle du fondateur... c'est le contraire
  d'un système modulaire activable ».
- **I9** (SA PROPRE idée) - « une mécanique issue de la fiction est incompatible avec la veille
  technologique. En IA, une nouvelle périme en quelques jours ; différer l'accès gratuit de deux
  semaines rend le contenu totalement obsolète ».
- **I13** (DeepSeek) - « totalement hors-sujet et coûteux... introduit une dette technique massive
  pour un besoin non validé ».
- **I4** (ChatGPT) - « sur-ingénierie délirante... le principe même d'un palier payant offrant le
  CSS libre est de laisser au client le droit de ruiner son propre design s'il le souhaite ».

Défend ses 2 autres idées (I8, I10) en expliquant pourquoi elles résistent selon son propre
examen - conforme au mandat qui autorise la défense justifiée, pas seulement la mise à mort.

**DeepSeek** (4 mises à mort, dont ses 3 propres) :
- **I11** (SA PROPRE idée) - « viole la règle du fondateur MEMORA en créant un artefact par
  exemplaire... contraire à l'exigence de blocs dynamiques réutilisables ».
- **I12** (SA PROPRE idée) - « repose sur des chiffres fabriqués... et ignore la décision actuelle
  de poursuivre le projet malgré les risques ».
- **I13** (SA PROPRE idée) - « introduit une complexité non prioritaire... détournant l'effort du
  socle critique sans preuve de demande ».
- **I9** (Gemini) - « inadapté à la niche (veille IA où l'actualité se déprécie vite), ajoutant
  une complexité technique disproportionnée ».

**Perplexity** (2 mises à mort claires, sur les 3 minimum demandées - voir note de
disponibilité) :
- **I9** et **I13** rejetées explicitement (« je rejette I9 et I13 comme mécanismes de produit
  initiaux »).
- Sur SA PROPRE idée I1 : pas de mise à mort nette malgré 6 relances, mais une réserve réelle et
  utilisable - « la proposition [registre générique] échouera si cela signifie une table settings
  avec JSON arbitraire... transfère la complexité vers des règles de validation opaques, des
  migrations difficiles, une administration non sécurisable » (applicable à I1/I3/I6/I8 dans leur
  ensemble, pas seulement I1).
- Sur I11 : n'a PAS tué, contrairement aux 3 autres oracles - « bonne granularité d'implémentation,
  non l'architecture produit principale ». **Divergence à noter** (voir plus bas).

### Idées neuves nées de la destruction (round 2)

1. **ChatGPT - « Plan de migration réversible par capacités »** : chaque nouvelle capacité
   introduite derrière le même moteur d'entitlements avec 3 états (`off`/`internal`/`public`) plus
   une stratégie de rollback. Justification : `tier` n'a aujourd'hui aucun effet réel, le risque
   principal n'est pas de bien concevoir les forfaits mais de les activer sans casser la
   production.
2. **Gemini - « Délégation SSL dynamique via Caddy on-demand TLS »** : placer Caddy en frontal
   avec `on_demand_tls`, qui interroge Laravel via un point d'entrée interne pour savoir si le
   domaine est payé, puis gère le certificat sans code applicatif. Convergence indépendante avec
   l'idée suivante.
3. **DeepSeek - « Proxy inverse dédié avec gestion SSL centralisée »** : même direction que
   Gemini (Nginx/Cloudflare), proposée indépendamment - **deuxième convergence du panel**, celle-
   ci entre 2 oracles sur le mécanisme technique réel identifié comme goulot par le brief factuel
   (routage par nom d'hôte + AutoSSL jamais testé).
4. **Perplexity - « Carnet de décisions éditoriales »** : chaque article garde publiquement ses
   hypothèses, sources initiales, prédictions vérifiables, avec mise à jour datée lorsque les
   faits évoluent - différenciation par traçabilité intellectuelle plutôt que par flux de veille.

### Divergence franche à consigner (round 2)

**I11 (manifeste par composant)** : ChatGPT, Gemini et DeepSeek (y compris son propre auteur) le
tuent sans réserve au nom de la règle du fondateur. Perplexity le maintient comme « bonne
granularité d'implémentation ». Arbitrage : la règle du fondateur (« jamais un fichier par
gabarit ni un contrôleur par intégration ») est explicite et non négociable dans CLAUDE.md - la
majorité (3 sur 4 oracles disponibles, dont l'auteur de l'idée lui-même) a raison au sens de la
règle écrite. La nuance de Perplexity reste utile à un autre niveau (un manifeste par
FOURNISSEUR, pas par INSTANCE, est déjà ce que fait I3/I6 - donc la divergence se résout en
vérifiant qu'on ne confond pas les deux niveaux, pas en gardant I11 tel que proposé).

---

## ROUND 3 - Attaque des survivantes

### Cible 1 - Le registre de capacités convergent (I1+I3+I6+I8)

**ChatGPT** (round 3, après avoir défendu ce noyau au round 2) l'attaque frontalement comme
demandé : le danger concret est de modéliser `custom_domain`, `image_quota`, `theme`,
`integration_x` comme des capacités homogènes résolues par un seul `Entitlements::for($author)`
alors qu'elles ne le sont pas - `custom_domain = true` ne dit rien sur `dns_pending`,
`ownership_verified`, `autossl_pending`, `certificate_issued`, `certificate_failed`,
`renewal_failed`. Ce que cela rend **pratiquement impossible plus tard sans refonte** : facturer
une capacité selon une métrique contextuelle (ex. 3 domaines inclus puis facturation par domaine
supplémentaire, chaque domaine ayant son propre état SSL/DNS) - un entitlement scalaire au niveau
auteur n'est plus le bon agrégat. Verdict de ChatGPT : « le registre doit seulement répondre à
quoi cet auteur a-t-il droit, il ne doit jamais devenir l'orchestrateur métier ».

**Gemini** casse le registre sur un autre axe : « en forçant tout dans un JSON/base générique, il
rend impossible l'optimisation matérielle ciblée » - exemple, une intégration nécessitant ses
propres index de recherche vectorielle ou une file de workers échoue car le registre ne gère que
de l'état (on/off/quota), pas des ressources d'infrastructure dédiées.

**DeepSeek** : casse sur la scalabilité des modifications concurrentes (deux intégrations
modifiant le même registre en prod) et sur l'impossibilité de traitement asynchrone natif
(webhooks tiers), obligeant tout à passer par un résolveur synchrone.

**Verdict consolidé** : les 3 oracles disponibles cassent le registre sur des axes DIFFÉRENTS mais
convergents dans leur conséquence - le registre reste la bonne architecture pour les DROITS
(qui a accès à quoi), mais ne doit jamais devenir le lieu de la LOGIQUE MÉTIER, de l'ÉTAT
D'INFRASTRUCTURE ni de l'EXÉCUTION ASYNCHRONE. C'est un raffinement du round 1, pas une mise à
mort : le registre survit avec ce périmètre réduit et explicite.

### Cible 2 - Échelon « Mérité » (I7, claude.ai)

**ChatGPT** : « je ne dispose d'aucun fait établissant aujourd'hui une équipe éditoriale MEMORA
dédiée... le seul administrateur opérationnel explicitement identifiable est le fondateur. Donner
un nombre supérieur serait fabriqué. » Sur le retrait : techniquement l'auteur redescend vers son
sous-domaine isolé ; SI redirection, le retrait est presque invisible ; SI absence de
redirection, liens cassés et indexation perturbée. « Peut-on chiffrer cette perte aujourd'hui ?
Non. Aucun chiffre mesurable n'est fourni. » Verdict : **« Je le tue définitivement. »**

**Gemini** : « MEMORA n'a pas l'équipe pour ça. L'administration exige un travail éditorial
continu (1 à 3 personnes dédiées minimum) pour évaluer, accorder et surveiller, ce qui ne scale
pas. Pire, le coût de retrait est un suicide de relations publiques : retirer le statut Mérité
est une insulte éditoriale publique. » **Tué.**

**DeepSeek** : seul oracle à NE PAS tuer - « administré par l'équipe éditoriale (2 personnes
max)... coût du retrait : perte de trafic estimée à 30-70%... risque de fuite vers
Substack/Mirror ». **Ces deux chiffres (2 personnes, 30-70%) sont NON SOURCÉS et traités comme
fabriqués** au sens de la règle du panel - DeepSeek invente une équipe et un pourcentage qui
n'existent dans aucun fait du dossier.

**Verdict consolidé** : 2 mises à mort nommées et solidement argumentées (aucune équipe réelle,
coût de retrait non chiffrable/toxique) contre 1 défense construite sur des chiffres fabriqués.
**I7 est tuée.** L'instinct sous-jacent (une caution éditoriale vaut quelque chose) n'est pas
sans valeur, mais aucune version testée au round 3 ne survit à l'épreuve de l'administration
réelle.

### Cible 3 - Risque de réputation de domaine partagé (I5, claude.ai)

**ChatGPT** a explicitement CHERCHÉ un précédent daté de moins de 90 jours pour une plateforme de
taille comparable et n'en a **pas trouvé** ; une étude du 9 août 2026 existe mais porte sur des
hébergeurs du « top 1 million Cloudflare Radar » - « toujours pas un analogue crédible de
laveille.ai à petite échelle ». Correction explicite de sa propre position du round 2 :
**« I5 ne mérite plus le statut de risque produit majeur démontré. C'est un risque
architectural plausible, mais non démontré à cette échelle. »** Verdict : survit seulement comme
« principe de confinement à faible coût », pas comme justification d'une architecture complexe
(domaine d'hébergement distinct + PSL).

**Gemini** : « le risque est fantasmé et emprunté à une échelle démesurée... aveu d'absence :
aucun précédent documenté de moins de 90 jours d'une plateforme de cette petite taille... ce
risque est NUL à ce stade. »

**DeepSeek** : « aucun cas documenté depuis janvier 2024... les exemples Netlify/GitHub/Blogger
concernent plus d'un million de sites. Le risque est jugé non transposable à l'échelle de
laveille.ai. » (La date « janvier 2024 » et le seuil « 500 auteurs » cités sont eux-mêmes non
sourcés - traités comme estimation, pas comme fait vérifié, même si la conclusion converge avec
les 2 autres oracles.)

**Verdict consolidé** : 3 oracles sur 3 disponibles à s'être exprimés concluent, indépendamment,
qu'AUCUN précédent daté et comparable en taille n'existe, et que le risque n'est pas démontré à
l'échelle du module Authors. **claude.ai, l'auteur de ce risque, n'a pas pu défendre sa position
au round 3** (panne de plateforme documentée ci-dessus) - cette correction reste donc à sens
unique, provisoire tant que claude.ai n'a pas pu répondre. Le risque garde une valeur de PRINCIPE
(isoler réduit le rayon d'impact, à coût quasi nul si fait tôt) mais perd son statut de risque
majeur démontré.

### Idées neuves du round 2, évaluées au round 3

**Délégation SSL frontale (Caddy/Nginx/Cloudflare, Gemini+DeepSeek)** - confrontée au fait dur
suivant, fourni dans le prompt : le serveur de production est un cPanel/WHM classique, le
Terminal cPanel tourne comme utilisateur cPanel (pas root), Apache/LiteSpeed occupe déjà les
ports 80/443. **Les 3 oracles disponibles la tuent, y compris ses 2 propres auteurs** :
- Gemini : « l'idée de Caddy MEURT face à la réalité de l'infrastructure... on s'adapte à la
  contrainte, on ne réécrit pas le serveur. »
- DeepSeek : « S4 inviable dans cPanel/WHM... l'utilisateur cPanel ne peut pas installer
  Nginx/Caddy en frontal. »
- ChatGPT : « je tue Caddy on-demand TLS sur ce serveur... Apache/LiteSpeed possède déjà ces
  ports, et le Terminal cPanel n'a pas les privilèges root nécessaires » - et va plus loin en
  citant deux pages de support cPanel datées (UAPI d'inclusion AutoSSL, correctif récent sur
  l'exclusion `www`) pour recommander le remplacement par l'AutoSSL DÉJÀ disponible via l'API
  cPanel.

**Fait mesuré en parallèle, après coup, qui confirme cette conclusion** : un autre agent a
effectivement TESTÉ ce mécanisme le même jour sur ce même compte cPanel
(`docs/specs/2026-09-11-autossl-domaine-tiers-mesure.md`) : un domaine tiers pointé par un simple
enregistrement A obtient un certificat Let's Encrypt valide, couvrant `www`, **en moins de 3
minutes**, sans aucune action manuelle - confirmant que la voie « AutoSSL cPanel natif »
recommandée par les 3 oracles n'est pas seulement plausible, elle est VÉRIFIÉE. La même mesure
liste aussi les risques réels pour un vrai client externe (CAA restrictif, proxy/CDN devant son
domaine, redirection de registraire au lieu d'un A record, propagation DNS lente, limites de
débit Let's Encrypt, erreur humaine) - donc le mécanisme fonctionne, mais la promesse
commerciale « domaine personnalisé » reste conditionnelle à ce que le CLIENT configure
correctement son DNS.

**Plan de migration réversible (ChatGPT, round 2)** : survit mais amputée - ChatGPT retire
l'idée qu'un simple rollback suffise ; pour `custom_domain`, désactiver la capacité ne doit pas
supprimer automatiquement DNS/vhost/certificat, il faut une politique de déprovisionnement
séparée et idempotente. Devient un « mécanisme de release/feature flag indépendant des
entitlements et des workflows métier ».

**Carnet de décisions éditoriales (Perplexity, round 2)** : ChatGPT tue la version privée/interne
(« change log glorifié, n'importe quel CMS peut copier cela »), garde uniquement une version
PUBLIQUE, structurée et vérifiable (« nous affirmions X le 4 septembre, nouvelle information Y le
18 septembre, conclusion corrigée ») - là où laveille.ai vend sa trajectoire de vérification, pas
seulement l'article final, ce qui est cohérent avec un module de vérification qui existe déjà
réellement sur le site (fait, pas hypothèse).

### Idées neuves nées de la destruction (round 3)

1. **ChatGPT - « Capability Contract Tests »** : chaque type de capacité doit passer une suite de
   tests contractuelle générique obligatoire avant activation (`attach -> verify -> provision ->
   renew simulation -> revoke -> reattach`), indépendamment du fournisseur d'infrastructure.
   Argument : « les interfaces seules garantissent la forme du code, pas son comportement » -
   corrige directement la faille de la Cible 1. **Limite méthodologique à noter** : cette idée
   est née dans la réponse de ChatGPT APRÈS que les 3 autres oracles disponibles avaient déjà
   répondu au round 3 - elle n'a donc été attaquée par AUCUN autre oracle. Elle survit par
   absence d'épreuve, pas par une épreuve réussie.
2. **Gemini - « Headless Hub » (syndication as a service)** : au lieu de se battre sur
   l'hébergement de domaines personnalisés (rendant la Cible 3 et l'idée SSL obsolètes), le
   module Authors devient l'outil central de rédaction qui POUSSE le contenu par API vers les
   plateformes où les auteurs sont déjà (LinkedIn, Substack, sites WordPress existants) -
   « on ne vend pas un clone de Ghost, on vend la tour de contrôle éditoriale ». Contourne
   entièrement le problème d'infrastructure de domaine.
3. **DeepSeek - « Archivage crypté des sources »** : chaque article lie un hash IPFS des données
   brutes utilisées (fichiers, transcripts), chiffré via une clé publique, accessible sur demande
   justifiée - garantit l'auditabilité sans surcharge éditoriale. Chiffre d'implémentation cité
   (« 1 jour-dev par article ») **non sourcé**, traité comme estimation non vérifiée.

### Divergence franche à arbitrer (round 3) - « Passeport lecteur centralisé » (I10)

ChatGPT l'avait tué au round 2 (SSO prématuré). Gemini l'avait défendu (« seul vrai fossé
défensif »). Au round 3, Gemini lui-même arbitre avec un œil neuf : **« ChatGPT a raison sur la
chronologie... mais j'avais raison sur la stratégie. Sans ce passeport unifié, laveille.ai n'a
aucun effet de réseau et n'est qu'un clone inférieur de Ghost. »** Compromis proposé par Gemini :
le passeport doit d'abord vivre exclusivement sous le domaine racine laveille.ai (où le SSO est
trivial, pas de cookies inter-domaines) pour créer la base d'utilisateurs, avant d'être exporté
vers les domaines personnalisés. **Arbitrage retenu** : les deux avaient raison sur des axes
différents - ChatGPT sur le SÉQUENÇAGE (pas maintenant, le domaine perso n'est pas encore
prouvé), Gemini sur la VALEUR STRATÉGIQUE (sans effet réseau, pas de fossé défensif). L'idée
survit sous forme séquencée, pas comme priorité immédiate.

---

## Fait dur découvert en cours de rédaction, qui doit peser sur toute lecture de ce document

Un autre agent a produit, le même jour, un inventaire de production
(`docs/specs/2026-09-11-blogues-perso-briques-dormantes.md`) qui mesure ce qu'aucun des 5 oracles
ne savait en répondant : **l'éditeur d'article (`AuthorEditor`) n'est atteignable NULLE PART en
production** - les deux boutons du tableau de bord auteur (« Nouveau statut court », « Nouvel
article long ») sont des boutons de maquette sans `wire:click` ni `href`. Conséquence mesurée :
`author_posts` = 0 ligne en production, malgré un profil auteur existant. Toute la discussion des
rounds 2 et 3 (registre de capacités, échelon Mérité, risque de réputation de domaine, SSL) porte
donc sur des raffinements d'un produit dont la fonction la plus élémentaire - écrire et publier
un article - est aujourd'hui cassée dans l'écran réel que l'auteur atteint. Ce fait ne change
aucune des mises à mort ci-dessus, mais il doit peser sur la priorisation finale : brancher
l'éditeur passe avant tout le reste, sans exception.

---

## Tableau final des survivantes, notées sur 3 axes (produit, pas moyenne)

Note méthodologique : VRAI/PERÇU/DÉFENDABLE sont notés sur 100 par le superviseur (moi), à partir
des attaques et défenses documentées ci-dessus - PAS redemandés aux oracles, pour respecter le
critère d'arrêt. Produit = (VRAI x PERÇU x DÉFENDABLE) / 10 000, pour rester lisible ; le
classement, pas la valeur absolue, est ce qui compte.

| # | Idée survivante | VRAI | PERÇU | DÉFENDABLE | Produit | Justification courte |
|---|---|---|---|---|---|---|
| 1 | **Headless Hub / syndication vers les plateformes existantes** (Gemini, round 3) | 50 | 75 | 25 | **9,38** | Contourne domaine+SSL entièrement ; bénéfice visible (« publier une fois, ça part partout ») ; mais Buffer/Zapier font déjà de la syndication - pas neuf en soi, jamais testé contre les limites API réelles |
| 2 | **Carnet de décisions éditoriales, version publique** (Perplexity round 2, restreint par ChatGPT round 3) | 55 | 45 | 35 | **8,66** | Synergie réelle avec le module de vérification DÉJÀ existant du site (fait, pas hypothèse) ; visible pour un lecteur régulier ; copiable en format mais moins en substance sans un tel module |
| 3 | **AutoSSL cPanel natif** (convergence ChatGPT/Gemini/DeepSeek, MESURÉ fonctionnel en prod le même jour) | 80 | 55 | 15 | **6,60** | Le mécanisme est désormais VÉRIFIÉ (moins de 3 min, <90j) ; débloque la promesse commerciale domaine perso ; mais c'est une fonctionnalité cPanel standard, aucun avantage défendable face à un concurrent |
| 4 | **Registre de capacités, périmètre réduit aux droits** (I1+I3+I6+I8, raffiné par les 3 attaques) | 85 | 15 | 20 | **2,55** | Résout un vrai problème DRY déjà mesuré (colonnes/services morts) ; totalement invisible pour l'utilisateur ; pattern d'architecture connu, non défendable |
| 5 | **Archivage crypté des sources IPFS** (DeepSeek, round 3) | 25 | 10 | 40 | **1,00** | Besoin de vérifiabilité réel, mais solution disproportionnée, jamais testée, chiffre de dev non sourcé |
| 6 | **Risque domaine partagé, en principe de confinement seulement** (I5 dégradé) | 40 | 5 | 30 | **0,60** | Aucun précédent daté/comparable trouvé par 3 oracles ; totalement invisible ; peu coûteux à faire tôt donc « défendable » au sens du coût d'attente, pas au sens concurrentiel |
| 7 | **Migration réversible par capacités, amputée** (ChatGPT round 2/3) | 75 | 5 | 10 | **0,375** | Hygiène d'ingénierie justifiée par le fait que `tier` n'a aucun effet réel ; invisible ; pratique DevOps standard |
| 8 | **Capability Contract Tests** (ChatGPT round 3, JAMAIS attaquée par un autre oracle) | 55 | 5 | 10 | **0,275** | Raisonnement solide mais non éprouvé contre une contre-attaque ; totalement invisible ; pratique de test connue, copiable |

**Idées tuées, absentes du tableau** : I2, I4 (version CSS-policée), I7 (Échelon Mérité), I9
(paywall temporel), I10 (passeport lecteur, en tant que priorité immédiate - survit seulement
séquencé après le domaine perso), I11 (manifeste par composant), I12 (démolition comme
architecture), I13 (score d'impact IA), et l'idée « Caddy/Nginx frontal » (S4 original,
remplacée par sa version cPanel native, qui elle survit au rang 3).

---

## Critère d'arrêt appliqué

**Le critère appliqué est celui du round 3 atteint (plafond fixé à l'avance)**, pas l'arrêt
anticipé : les deux rounds ont produit des idées neuves passant le filtre (round 2 : plan de
migration réversible, délégation SSL, carnet éditorial ; round 3 : Headless Hub, Capability
Contract Tests, archivage crypté) - le critère « 2 rounds consécutifs sans idée neuve » ne s'est
jamais déclenché. Conformément au mandat, l'arrêt intervient donc après le round 3, pas avant.

## Oracles défaillants, résumé nominatif final

- **claude.ai** : indisponible aux rounds 2 ET 3 pour cause de panne de plateforme reproduite 5
  fois (erreur 405 sur un connecteur MCP du compte, bloquant la génération de réponse sur toute
  requête analytique complexe - un message trivial passe). Ses propres idées (I5, I6, I7) ont
  néanmoins été intégralement évaluées par les 4 autres oracles.
- **Perplexity** : disponible mais avec une conformité de format très dégradée - a fourni un
  round 2 utilisable seulement après 6 relances progressivement raccourcies, et un round 3
  totalement hors-sujet malgré reformulation (a mal interprété la consigne comme un thème
  éditorial). Sa contribution réelle (2 mises à mort, 1 idée neuve, 1 vérification de prix Ghost)
  est intégrée ci-dessus ; son round 3 est absent du tableau final.
