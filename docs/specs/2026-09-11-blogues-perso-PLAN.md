# Plan ultra complet - blogues personnels laveille.ai (Modules/Authors)

Date : 2026-09-11. Document de décision, produit par MEMORA solutions. Aucune ligne de code
n'a été modifiée pour l'écrire, aucune migration n'a été lancée. Il s'appuie sur six documents
de travail déjà produits et ne les répète ni ne les contredit sans preuve :

1. `docs/specs/2026-09-11-blogues-perso-brief-factuel.md` - état réel du code.
2. `docs/specs/2026-09-11-blogues-perso-mesure-domaines.md` - Porkbun, prix, droit.
3. `docs/specs/2026-09-11-autossl-domaine-tiers-mesure.md` - AutoSSL sur domaine tiers, mesuré.
4. `docs/specs/2026-09-11-blogues-perso-panel-round1.md` - 5 oracles en aveugle.
5. `docs/specs/2026-09-11-blogues-perso-panel-rounds23.md` - réfutation croisée, survivantes.
6. `docs/specs/2026-09-11-blogues-perso-briques-dormantes.md` - ce qui existe déjà et dort.

Ce document répond à la demande de Stéphane : transformer les blogues personnels en un
produit comparable à ghost.org (gabarits à l'inscription, palier gratuit/payant avec domaine
personnalisé automatisable via Porkbun, quotas d'images par palier, intégrations tierces,
CSS/HTML ouvert aux payants), en tenant compte des avantages de wordpress.com, validé par un
panel de 5 oracles en 3 rounds.

---

## 1. Ce que devient ce produit, en une phrase

**Un auteur reçoit un blogue fini et déjà beau en quelques clics, jamais un chantier de
construction : gratuit sur `/@slug`, payant pour porter son propre nom de domaine et
personnaliser davantage son apparence, sans jamais redevenir, pour l'auteur ordinaire, un
éditeur de code.**

Cette phrase permet de refuser, et sert de test pour toute idée future :

- « un moteur de gabarits par métier avec ses propres champs / son propre langage de
  templating » : refusé, c'est un chantier de construction, pas un gabarit fini à choisir.
- « ouvrir l'éditeur de blogue à du HTML/CSS brut par défaut pour tout payant » : en tension
  directe avec « jamais un éditeur de code » - traité comme une question ouverte pour
  Stéphane (section 10), jamais tranché en silence par ce document.
- « un marketplace de thèmes créés par des tiers » : refusé pour l'instant, aucun fait du
  dossier ne montre une demande ni une capacité de modération de ce marché.
- « un palier Mérité décerné par une équipe éditoriale » : refusé, déjà tué par le panel
  (section 8, motif : aucune équipe éditoriale réelle pour l'administrer).
- « brancher l'éditeur existant sur le tableau de bord » : accepté, c'est exactement ce que la
  phrase exige (un blogue qu'on peut réellement utiliser aujourd'hui, pas demain).

---

## 2. Le parcours utilisateur bout en bout

### Ce qui existe et ce qui manque, aujourd'hui même

L'écran que l'auteur atteint réellement est `/auteur/dashboard`, onglet `composer` par défaut
(`Modules/Authors/app/Livewire/AuthorDashboard.php:14`). Il y voit deux boutons : « Nouveau
statut court (≤ 280 caractères) » et « Nouvel article long »
(`Modules/Authors/resources/views/livewire/author-dashboard.blade.php:25-36`). **Aucun des
deux ne porte de `wire:click` ni de `href`.** L'éditeur complet (`AuthorEditor`, auto-save,
planification, tags, révisions, 15 cas de test) n'est rendu que dans une vue de test verrouillée
à l'environnement local (`Modules/Authors/routes/web.php:162,168-179`) - 404 garanti en
production. Résultat mesuré : `author_posts` = 0 ligne en production
(`docs/specs/2026-09-11-blogues-perso-briques-dormantes.md`, tableau chiffré). **Aujourd'hui,
le nombre de gestes entre « je m'inscris » et « mon premier billet est en ligne » est infini :
le chemin est physiquement coupé.**

Le plan ne propose donc pas de reconstruire ce parcours à partir de zéro. Il propose de le
RENDRE ATTEIGNABLE, puis de le simplifier à l'entrée, dans cet ordre.

### Parcours cible, écran par écran, une fois le fil rebranché (voir section 9, étape 1)

**Écran 1 - inscription au blogue.** L'auteur voit un formulaire à trois décisions, jamais
plus : le nom de son blogue (devient le `slug`), un gabarit visuel choisi dans une galerie où
chaque vignette montre du VRAI contenu (pas une image marketing), une couleur d'accent parmi un
choix restreint garanti accessible (les 8 valeurs de `accent_color` existent déjà,
`Modules/Authors/app/Models/AuthorProfile.php:27`, mais ne sont lues par aucune vue - section 4).
Ce choix reprend le diagnostic du panel (ChatGPT, round 1) sur ce que les autres plateformes
font mal : elles laissent l'auteur CONSTRUIRE une apparence (police, interligne, marges,
boutons) quand il devrait seulement la CHOISIR. Tout le reste (typographie, largeur de lecture,
contraste, structure) reste verrouillé par le gabarit.

**Écran 2 - tableau de bord, onglet composer.** Le bouton « Nouvel article long » ouvre
directement l'éditeur existant (`AuthorEditor`) au lieu de ne rien faire. Le bouton « Nouveau
statut court » écrit réellement dans `author_statuses` (aujourd'hui lu mais jamais écrit,
`docs/specs/2026-09-11-blogues-perso-briques-dormantes.md`, point 9).

**Écran 3 - l'éditeur.** Déjà complet et déjà testé : titre, corps Markdown, auto-save, tags,
choix de visibilité (public/abonnés/premium - avec une réserve, section 6/8), bouton Publier.
Rien à construire ici, seulement à afficher.

**Écran 4 - confirmation.** L'article est en ligne à `/@{slug}/{postSlug}`. C'est la preuve
d'arrivée.

**Décompte des gestes réels, une fois le fil rebranché** : (1) remplir le nom du blogue, (2)
choisir un gabarit, (3) choisir une couleur, (4) cliquer « Nouvel article long », (5) écrire un
titre et un texte, (6) cliquer Publier. **Six gestes.** C'est un chiffre raisonnable pour un
enseignant qui n'a jamais publié en ligne - comparable à l'inscription Ghost ou WordPress.com,
qui demandent toutes deux un choix de nom, un choix visuel minimal et un premier brouillon avant
la première publication. Si un test réel avec un utilisateur non technique montre que l'étape
« choisir un gabarit » ou « choisir une couleur » crée une hésitation (paralysie du choix), la
réduction suivante est de proposer un gabarit et une couleur PAR DÉFAUT déjà sélectionnés,
modifiables ensuite - ce qui ramène le chemin critique à quatre gestes (nom, « Nouvel article
long », texte, Publier) sans supprimer la personnalisation, seulement son caractère obligatoire
au premier passage.

### Parcours cible - domaine personnalisé (palier payant)

**Écran 5 - paramètres > domaine.** L'auteur payant entre un domaine qu'il possède déjà (ou en
achète un via Porkbun si cette brique est construite, section 9 étape 7 - en second temps).
Il voit une instruction unique : « pointez un enregistrement A vers telle adresse IP », avec un
bouton de copie.

**Écran 6 - vérification.** Un bouton « Vérifier » interroge la résolution DNS publique du
domaine. Dès qu'elle pointe vers notre serveur, le système ajoute automatiquement le domaine
comme domaine addon cPanel et affiche un état « SSL en cours d'émission... » puis « Domaine
actif » - mécanisme mesuré fonctionnel en moins de 3 minutes
(`docs/specs/2026-09-11-autossl-domaine-tiers-mesure.md`, section 2.2), sans qu'aucune action
manuelle de notre part ne soit nécessaire.

**Décompte** : (1) entrer le nom de domaine, (2) configurer le A record chez son propre
registraire (hors de notre interface, mais l'instruction est affichée), (3) cliquer Vérifier.
**Trois gestes de notre côté**, plus une étape externe que nous ne contrôlons pas (le
changement DNS chez le registraire du client) - c'est exactement la limite documentée par la
mesure AutoSSL : le mécanisme fonctionne, mais il dépend d'une configuration correcte que le
client fait lui-même, avec les risques d'échec réels et nommés (CAA restrictif, proxy/CDN
devant le domaine, redirection d'URL au lieu d'un vrai A record, propagation lente, limites de
débit Let's Encrypt - `docs/specs/2026-09-11-autossl-domaine-tiers-mesure.md`, section 2.6).
L'écran de vérification doit donc afficher un diagnostic clair en cas d'échec (« votre domaine
ne pointe pas encore vers nous » plutôt qu'une erreur technique muette), pas seulement un
succès silencieux.

---

## 3. Les forfaits

### La frontière retenue, et pourquoi

**Gratuit** : blogue sur `/@slug`, galerie de gabarits complète (choisir un gabarit ne coûte
rien à produire une fois le mécanisme construit, section 7), quota d'image de base.
**Payant** : domaine personnalisé, quota d'image plus généreux, et un niveau de
personnalisation visuelle plus profond (tokens de design plus larges - section 4).

Ce choix suit la formulation même de Stéphane (« un forfait payant qui permet EN PLUS un
domaine personnalisé ») et converge avec les DEUX plateformes de référence mesurées : **ni
Ghost ni WordPress.com ne donnent jamais le domaine personnalisé gratuitement**
(`docs/specs/2026-09-11-blogues-perso-mesure-domaines.md`, section 6 ; confirmé indépendamment
par Perplexity au panel round 1). Elles divergent seulement sur l'étage où il apparaît : Ghost
l'inclut dès son palier le MOINS cher (18 $US/mois, Starter), WordPress.com ne le donne qu'à son
premier palier payant après un strict gratuit sans domaine. **Les deux s'accordent sur le
principe qui compte pour ce plan : le domaine n'est jamais gratuit, mais il n'est pas non plus
ce qui coûte le plus cher à produire.** Ce qui coûte réellement cher chez Ghost, et qui
distingue ses propres paliers payants entre eux, c'est le THÈME PERSONNALISÉ (accessible
seulement à 29 $US/mois, pas à 18 $US) - pas le domaine.

**Décision retenue pour laveille.ai : un seul palier payant au lancement**, pas trois comme
Ghost. Motif : le produit compte aujourd'hui 1 profil auteur et 0 article publié
(`docs/specs/2026-09-11-blogues-perso-briques-dormantes.md`, tableau chiffré) - construire trois
paliers payants avant la moindre preuve de demande reproduit exactement l'erreur nommée par le
panel (DeepSeek, round 1 : « sans une preuve de demande réelle, le projet reste un pari »). Un
seul palier payant regroupe domaine personnalisé + quota d'image relevé + accès aux tokens de
personnalisation avancés (section 4). S'il s'avère, avec des clients réels, que certains veulent
le domaine sans la personnalisation avancée (ou l'inverse), scinder en deux paliers devient une
décision informée par un fait, pas une anticipation.

### La marge, jamais sur le prix d'appel

Le calcul de rentabilité du domaine doit se faire sur le RENOUVELLEMENT, jamais sur
l'inscription : `.ca` s'inscrit en promotion à 8,91 $US mais renouvelle à 9,18 $US (le tarif
régulier est 11,12/11,39 $US) ; `.blog` s'inscrit à 2,57 $US mais renouvelle à 21,11 $US - huit
fois le prix d'appel ; `.io` passe de 28,12 $US à 51,80 $US (facteur 1,8)
(`docs/specs/2026-09-11-blogues-perso-mesure-domaines.md`, section 2). Un forfait qui couvrirait
son coût de domaine à l'achat mais pas au renouvellement deviendrait déficitaire dès la
deuxième année pour chaque client. Il n'existe aucun programme de volume Porkbun (section 2 du
même document) : le prix payé par MEMORA est celui de son propre compte, sans remise de gros.
**Le prix du palier payant doit donc être fixé en tenant le coût de RENOUVELLEMENT réel comme
plancher, jamais le prix d'inscription** - un chiffre exact de prix (en dollars canadiens, avec
marge cible) est une question ouverte pour Stéphane (section 10), ce document ne l'invente pas.

### Ce que le prix DOIT couvrir en plus du domaine

Le stockage d'images réel (section 5), le temps de support pour un client dont le DNS est mal
configuré (risque nommé, non nul), et le renouvellement récurrent du certificat (automatique,
sans coût de main-d'oeuvre mesuré grâce à AutoSSL - c'est le seul poste vraiment gratuit une
fois construit).

---

## 4. Les gabarits visuels

### Ce qui existe déjà et ce qui n'existe pas

Les colonnes `accent_color` (8 valeurs : teal, indigo, rose, amber, emerald, violet, sky,
fuchsia) et `font_family` (3 valeurs : jakarta, inter, merriweather) existent en base et sont
`fillable` (`Modules/Authors/app/Models/AuthorProfile.php:27-28`), mais grep exhaustif confirme
qu'AUCUNE vue ne les lit (`docs/specs/2026-09-11-blogues-perso-brief-factuel.md`, section 4) :
ce sont des colonnes mortes, pas un système de gabarits qui aurait cessé de fonctionner. Les
trois vues publiques (`show.blade.php`, `post.blade.php`, `tag-archive.blade.php`) sont chacune
un document HTML autonome avec son propre `<!DOCTYPE html>` et des couleurs codées en dur (ex.
`#064E5A`, `show.blade.php:29`) - **ce ne sont pas des composants, ce sont des documents
monolithiques.**

**Le piège nommé par l'inventaire, à ne pas répéter** : poser un sélecteur de gabarit
par-dessus ces trois fichiers tels qu'ils sont aujourd'hui produirait un `<select>` qui ne
changerait rien - exactement le même défaut que `accent_color`/`font_family` aujourd'hui.
**Un vrai gabarit de mise en page (une deuxième structure visuelle, pas seulement d'autres
couleurs) est un AUTRE chantier**, qui exige d'abord de sortir le HTML/CSS des trois vues vers
des composants Blade partagés et des variables de thème. Ce document ne le cache pas : au
lancement, un « gabarit » sera une COMBINAISON de tokens (couleur d'accent + police) appliquée à
UNE seule mise en page, pas une deuxième mise en page. Une vraie diversité de mises en page
(magazine, minimaliste, portfolio) reste un chantier distinct, à chiffrer séparément si la
demande le justifie.

### Ce qui distingue le palier gratuit du palier payant sur les gabarits

Gratuit : galerie complète de combinaisons couleur/police déjà prêtes (8 couleurs x 3 polices =
24 combinaisons possibles sans écrire une ligne de code, une fois les tokens branchés dans les
vues). Payant : accès à des tokens supplémentaires (largeur de lecture, densité de mise en page,
peut-être une deuxième mise en page si construite plus tard) au sein des « zones de
personnalisation sûres » définies section 7, jamais un accès à du CSS/HTML arbitraire sans
validation - voir la tension explicite avec la demande de Stéphane, tranchée en question ouverte
(section 10).

`modules_visible` (JSON, 8 sections activables/désactivables, seule personnalisation
RÉELLEMENT écrite en production aujourd'hui - `AuthorSettings.php:34-48`) reste un levier
DISTINCT du thème visuel : c'est une question de « quelles sections apparaissent », pas
« comment elles sont peintes ». Les deux règles évolueront pour des raisons différentes et ne
doivent pas fusionner (DRY nuancé, section 7).

---

## 5. Les quotas d'images

### Le choix Ghost, retenu

Ghost borne la taille MAXIMALE PAR FICHIER selon le palier (5 Mo Starter, 100 Mo Publisher,
250 Mo Business), jamais un volume cumulé
(`docs/specs/2026-09-11-blogues-perso-mesure-domaines.md`, section 6). C'est le mécanisme déjà
à moitié en place ici : `ImagePipelineService::process()` impose déjà une limite de 10 Mo par
fichier, mais FIXE et identique pour tout le monde, sans lien avec `tier`
(`Modules/Authors/app/Services/ImagePipelineService.php:41-44` ; grep confirmé, le mot `tier`
n'apparaît dans aucun des trois fichiers d'upload -
`docs/specs/2026-09-11-blogues-perso-briques-dormantes.md`, point 10). **Le choix retenu : garder
un plafond par fichier, mais le faire varier par palier** (par exemple 10 Mo gratuit, un
plafond plus généreux payant) en le sortant du nombre magique codé en dur vers le registre de
capacités (section 7).

### Ce que ce choix implique en stockage réel, honnêtement

Un plafond par FICHIER n'empêche pas l'accumulation : chaque image source produit déjà 15
variantes (5 largeurs x 3 formats) plus deux images sociales, stockées sur le disque `public`
de Laravel (`ImagePipelineService.php:23,84-172`). Rien ne borne aujourd'hui le NOMBRE d'images
qu'un auteur peut accumuler. Ghost peut se permettre cette absence de plafond cumulé parce qu'il
opère à l'échelle d'une infrastructure de stockage objet dimensionnée pour ça ; MEMORA héberge
sur un compte cPanel partagé. **Le choix Ghost est retenu pour le PRODUIT (simple à expliquer,
impossible à contourner en multipliant les petits fichiers), mais il doit être accompagné d'une
surveillance OPÉRATIONNELLE du volume total par auteur** (une alerte, pas un plafond dur annoncé
au client) plutôt que d'un deuxième quota produit qui complexifierait la promesse. C'est un choix
de simplicité commerciale payé par une vigilance d'exploitation, pas un chiffre business inventé
ici.

---

## 6. Les intégrations tierces

### Le patron à reproduire

Une seule intégration du module a été pensée interface-d'abord : le contrat `NewsletterProvider`
(`Modules/Authors/app/Contracts/NewsletterProvider.php`) avec son implémentation `BrevoProvider`
(`connect`, `listAudiences`, `createCampaign`, `sendCampaign` via `api.brevo.com`,
`Modules/Authors/app/Services/Newsletter/BrevoProvider.php`). **C'est le bon geste, même si son
résultat concret est aujourd'hui mort** : `BrevoProvider` n'est jamais instancié, jamais lié au
conteneur de services (`docs/specs/2026-09-11-blogues-perso-brief-factuel.md`, section 6) - la
newsletter par auteur envoie réellement par SMTP standard Laravel, pas par Brevo. **Ce n'est pas
un défaut de conception, c'est un défaut de branchement** - exactement le même défaut, à plus
petite échelle, que l'éditeur d'article (section 2/9).

### Ce qu'il ne faut PAS imiter

Trois services traitent le même besoin - « transformer une URL externe en balisage intégré
dans un article » - sans aucun contrat commun : `EmbedsRichService` (184 lignes : YouTube,
Spotify, Twitter, Bluesky, Figma, Codepen, GitHub, Instagram, TikTok - jamais appelé),
`GoogleDriveEmbedService` (156 lignes, jamais appelé), et `OembedService` (le seul réellement
branché à `AuthorEditor`, `OembedService.php:18,37,58`). Un quatrième service isolé pour une
future intégration (Notion, Airtable, Canva) reproduirait le même éparpillement. Encore plus
trompeur : `WebPushService` a exactement la FORME d'une intégration terminée (modèle, migration,
service, tests unitaires), mais son coeur ne fait rien - `send()` journalise et s'arrête,
avec l'aveu écrit dans son propre code : `// Silent fail MVP — real send via minteractive/web-push S115+` (`Modules/Authors/app/Services/WebPushService.php:73`). Une future intégration copiée sur
cette forme semblerait finie sans jamais l'être.

### La règle pour toute future intégration

Reprendre la forme `NewsletterProvider`/`BrevoProvider` : un contrat par FAMILLE de capacité
(un contrat `EmbedProvider` pour « transformer une URL en balisage », distinct d'un contrat
`NotificationProvider` pour « alerter un lecteur », distinct de `NewsletterProvider` pour
« gérer une liste de diffusion externe » - ce sont trois règles métier différentes qui
évolueront pour des raisons différentes, donc trois contrats, pas un méga-contrat unique), puis
enregistrer chaque implémentation dans le registre de capacités (section 7) au lieu d'écrire un
service isolé de plus. `Oembed` et `GoogleDrive` doivent migrer vers un futur contrat
`EmbedProvider` commun ; `EmbedsRichService`, mort et jamais appelé, n'a pas besoin d'être
sauvé - le retirer (section 8/9) plutôt que le brancher tel quel.

---

## 7. L'architecture - ce qu'on écrit une seule fois

### Le mécanisme unique

Quatre oracles sur cinq, indépendamment et sans se voir, ont convergé au round 1 vers la même
réponse : un gabarit, un quota et une intégration sont TROIS FORMES DE LA MÊME CHOSE - une
capacité déclarée en DONNÉES, accordée par palier, appliquée à un petit nombre de points
d'application génériques
(`docs/specs/2026-09-11-blogues-perso-panel-round1.md`, synthèse finale). Le round 3 a
CASSÉ ce registre sur trois axes indépendants (ChatGPT, Gemini, DeepSeek) et l'a réduit à un
périmètre précis, qui est la conclusion retenue par ce plan : **le registre répond uniquement
à « qu'est-ce que cet auteur a le droit de faire, et dans quelle limite » - il ne doit JAMAIS
devenir l'orchestrateur métier, ni le gestionnaire d'état d'infrastructure, ni un exécuteur
asynchrone** (`docs/specs/2026-09-11-blogues-perso-panel-rounds23.md`, Cible 1, round 3).

Concrètement, quatre pièces, chacune écrite une seule fois :

1. **Un registre** : une table (ou un JSON versionné) où chaque capacité - un gabarit, un
   quota, une intégration - est UNE LIGNE, jamais un fichier. Champs : clé, type, palier(s)
   concerné(s), configuration, état (actif/inactif).
2. **Un résolveur unique** : un point d'entrée (`Entitlements::for($author)` ou équivalent) qui
   remplace `EnsurePremium` (jamais monté sur une route de production,
   `Modules/Authors/app/Http/Middleware/EnsurePremium.php:11-27`) et `TierManagementService`
   (jamais instancié, `docs/specs/2026-09-11-blogues-perso-brief-factuel.md`, section 1).
3. **Trois points d'application génériques** : le rendu des gabarits (variables CSS injectées
   dans le layout Blade unique - `accent_color`/`font_family` redeviennent vivants), le
   validateur d'upload unique (lit la limite depuis le registre au lieu du `10 * 1024 * 1024`
   codé en dur, `ImagePipelineService.php:41-44`), et le gestionnaire d'intégrations (lit les
   fournisseurs actifs par palier, appelle le contrat correspondant - section 6).
4. **Une politique de retrait explicite** (`on_revoke`) : que devient un thème payant ou une
   intégration active quand la carte expire ou que le palier redescend. Cas jamais exercé
   aujourd'hui puisque `tier` n'a aucun effet réel (`docs/specs/2026-09-11-blogues-perso-panel-round1.md`,
   réponse de claude.ai, mécanisme unique) - donc à écrire avant le premier vrai palier payant,
   pas après.

**Ce qu'on écrit à chaque nouvel exemplaire : une ligne de données.** Un neuvième gabarit ou une
troisième intégration standard (même famille qu'une déjà branchée) ne demande aucune migration,
aucun contrôleur, aucun fichier Blade nouveau - seulement une entrée dans le registre. Une
intégration d'une famille ENTIÈREMENT nouvelle (le premier fournisseur de notifications push
réel, par exemple) exige d'écrire l'adaptateur UNE FOIS pour cette famille, conformément au
contrat de la section 6 - ce n'est pas une exception à la règle, c'est la définition même
d'une famille : elle se construit une fois, ses membres suivants sont des lignes.

### Ce qu'on ne factorise PAS, et pourquoi

Le round 3 a explicitement démontré qu'une capacité comme `custom_domain = true` ne dit rien
sur son état réel : `dns_pending`, `ownership_verified`, `autossl_pending`,
`certificate_issued`, `certificate_failed`, `renewal_failed`
(`docs/specs/2026-09-11-blogues-perso-panel-rounds23.md`, Cible 1). **Le provisionnement d'un
domaine (DNS + AutoSSL + renouvellement) reste une machine à états SÉPARÉE du registre de
capacités**, que le registre se contente d'autoriser ou non ("cet auteur a-t-il le droit
d'essayer") sans jamais la piloter lui-même. Motif du DRY nuancé du projet : le registre encode
une règle d'ABONNEMENT (qui a payé pour quoi), la machine à états encode une règle
D'INFRASTRUCTURE (comment Let's Encrypt et la résolution DNS publique se comportent) - ces deux
connaissances évolueront pour des raisons totalement différentes (un changement de politique
Let's Encrypt n'a rien à voir avec un changement de grille tarifaire), donc elles ne doivent
jamais fusionner malgré leur ressemblance de surface (toutes les deux « des états »).

Deuxième cas de non-factorisation, plus petit : `accent_color`/`font_family` (thème visuel) et
`modules_visible` (visibilité des sections) restent deux mécanismes distincts dans le registre
plutôt qu'un seul objet « personnalisation » fusionné (section 4) - ils encodent deux règles
différentes qui évolueront séparément.

---

## 8. Ce qu'on ne fait pas

Neuf idées ont été tuées par le panel (rounds 2 et 3), chacune avec un motif nommé. Elles ne
doivent pas être reproposées sans un fait nouveau qui inverse le motif de mise à mort.

1. **Inscription figée à exactement 4 champs** (I2, proposée puis tuée par son propre auteur,
   ChatGPT) - motif : « contrainte produit arbitraire sans validation utilisateur, confond bonne
   UX et règle architecturale ». **Ce qui reste valide** : l'esprit (réduire les décisions au
   minimum, choisir plutôt que construire, section 2) - mais aucun nombre de champs ne doit être
   gravé dans le code comme une règle.
2. **Moteur de validation automatique du CSS personnalisé** (I4, ChatGPT, tuée par Gemini et par
   son propre auteur) - motif : « deviendrait vite un pseudo-navigateur CSS complexe, fragile et
   coûteux ». Remplacée par « tokens + zones de personnalisation sûres » (section 4).
3. **Échelon « Mérité »** (I7, claude.ai) - motif consolidé (ChatGPT + Gemini) : aucune équipe
   éditoriale réelle identifiable pour l'administrer, et le coût de RETRAIT du statut est
   toxique en relations publiques et non chiffrable aujourd'hui. Tuée définitivement.
4. **Paywall temporel « wait-or-pay »** (I9, Gemini, tuée par son propre auteur) - motif :
   incompatible avec une veille technologique où le contenu périme en jours, pas en semaines.
5. **Passeport lecteur centralisé comme PRIORITÉ IMMÉDIATE** (I10, Gemini) - pas tuée en
   totalité, mais son urgence l'est : elle ne survit que SÉQUENCÉE après que le domaine
   personnalisé soit prouvé en usage réel, jamais avant.
6. **Manifeste par composant (un fichier YAML par capacité)** (I11, DeepSeek, tuée par son
   propre auteur) - motif : reste un artefact PAR EXEMPLAIRE malgré la forme « données »,
   violant directement la règle du fondateur (jamais un fichier par gabarit).
7. **La démolition générale comme conclusion/architecture** (I12, DeepSeek, tuée par son propre
   auteur) - motif : chiffres fabriqués, ignore la décision déjà prise de poursuivre le projet.
   Son ARGUMENT structurel désarmé de ses chiffres (audience niche, complexité sous-estimée,
   risque XSS jamais audité, absence de preuve de demande) reste une prudence utile, mais pas
   une conclusion en soi.
8. **Score d'impact IA par article** (I13, DeepSeek, tuée par son propre auteur) - motif : hors
   périmètre du produit Authors, prix non sourcé, complexité disproportionnée par rapport au
   besoin validé.
9. **Proxy/serveur frontal dédié (Caddy ou Nginx) pour la délivrance SSL** (idée née
   indépendamment chez Gemini et DeepSeek au round 2, tuée par les trois oracles disponibles au
   round 3, y compris ses deux auteurs) - motif : le serveur de production est un cPanel/WHM où
   Apache/LiteSpeed occupe déjà les ports 80/443 et où le compte utilisateur n'a pas les
   privilèges root pour installer un nouveau serveur frontal. **Remplacée par l'AutoSSL cPanel
   natif**, qui n'est pas seulement plausible : il a été VÉRIFIÉ fonctionnel le même jour, en
   moins de 3 minutes (`docs/specs/2026-09-11-autossl-domaine-tiers-mesure.md`).

Deux nuances à ne pas perdre en cours de route :
- Le risque de réputation de domaine partagé (I5, claude.ai) n'est PAS dans cette liste des 9
  tuées : il a été DÉGRADÉ, pas éliminé. Trois oracles ont cherché un précédent daté de moins de
  90 jours à une échelle comparable et n'en ont trouvé aucun ; il redevient un « principe de
  confinement à coût quasi nul », plus un risque produit démontré
  (`docs/specs/2026-09-11-blogues-perso-panel-rounds23.md`, Cible 3).
- Le registre de capacités (section 7) a survécu, mais amputé, pas intact - le confondre avec sa
  version round 1 (orchestrateur universel) serait reproduire l'erreur que le round 3 a
  justement corrigée.

---

## 9. L'ordre d'exécution

Chaque étape porte sa preuve de fin, qui est une observation, jamais une intention.

**1. Rebrancher l'éditeur d'article existant.** Brancher les deux boutons de l'onglet
`composer` (`Modules/Authors/resources/views/livewire/author-dashboard.blade.php:25-36`) sur
`AuthorEditor` et sur l'écriture réelle d'un statut court dans `author_statuses`. Un correctif
est déjà en cours selon le mandat. *Preuve de fin* : un article réellement créé par un compte
de test, visible à `/@{slug}/{postSlug}` en production, et `author_posts` > 0 en base
(aujourd'hui 0, `docs/specs/2026-09-11-blogues-perso-briques-dormantes.md`).

**2. Rendre le téléverseur d'images accessible depuis l'éditeur.** `ImageUploader` fonctionne
déjà (`Modules/Authors/app/Livewire/ImageUploader.php`) mais n'est rendu nulle part
(`docs/specs/2026-09-11-blogues-perso-briques-dormantes.md`, point 2) - dépend directement de
l'étape 1. *Preuve de fin* : `author_image_variants` > 0 en base (aujourd'hui 0) après un
téléversement réel depuis l'écran de l'éditeur.

**3. Réconcilier les deux signaux « premium » en une seule source de vérité.** `AuthorProfile::
isPremium()` lit `tier` (`AuthorProfile.php:123-125`) ; `UpgradeController::show()` calcule
`$isPremium` depuis `subscribed('default')` sans jamais lire `tier`
(`UpgradeController.php:20`) - deux vérités parallèles avant même le premier quota. Décision
retenue par ce plan (justifiée section 10) : `tier` devient la seule source, le webhook Stripe
continue de l'écrire comme il le fait déjà (`StripeWebhookController::syncTier()`), et
`UpgradeController` doit être corrigé pour LIRE `tier` plutôt que recalculer un booléen distinct.
*Preuve de fin* : un test automatisé qui prouve qu'un compte `education` (jamais un abonnement
Cashier) obtient les mêmes droits que prévu, CI verte.

**4. Construire le registre de capacités, périmètre limité aux droits (section 7).** Y
accrocher en premier le quota d'images par palier (remplaçant le `10 Mo` fixe,
`ImagePipelineService.php:41-44`), puisque c'est le cas le plus simple et déjà à moitié câblé.
*Preuve de fin* : un compte gratuit et un compte payant de test obtiennent des limites de
téléversement RÉELLEMENT différentes (upload accepté ou refusé selon le palier, observé, pas
seulement lu dans une configuration).

**5. Sortir le CSS des trois vues mini-site vers des tokens partagés.** Condition préalable à
tout sélecteur de gabarit (section 4) - sans cette étape, `accent_color`/`font_family` restent
des colonnes mortes même si on les branche. *Preuve de fin* : capture visuelle de deux profils
de test avec deux `accent_color` différents, rendu visuellement distinct sans avoir touché au
HTML des vues.

**6. Construire le parcours d'inscription réduit.** Nom du blogue, galerie de gabarits avec
vrai contenu, couleur d'accent (section 2) - dépend de l'étape 5. *Preuve de fin* : QC visuel
Playwright du parcours complet, captures à l'appui, décompte réel des clics observés (cible :
six gestes ou moins jusqu'à la première publication, section 2).

**7. Construire le flux « domaine personnalisé ».** Écran de paramètres, instruction DNS, bouton
Vérifier, ajout automatique du domaine addon cPanel, suivi de l'état SSL (utilise le mécanisme
déjà VÉRIFIÉ). *Preuve de fin* : test réel de bout en bout depuis l'INTERFACE (pas seulement
depuis un appel cPanel direct comme la mesure du 2026-09-11) avec un second domaine possédé par
MEMORA, restauré après coup selon le même protocole que `lnest.io`
(`docs/specs/2026-09-11-autossl-domaine-tiers-mesure.md`, phase 3).

**8. (Second temps, dépend de conditions non confirmées) Achat de domaine automatisé via
Porkbun.** Les trois conditions mesurées (compte Porkbun ayant déjà enregistré au moins un
domaine, solde prépayé provisionné, courriel et téléphone vérifiés -
`docs/specs/2026-09-11-blogues-perso-mesure-domaines.md`, section 1) n'ont pas été confirmées
comme remplies dans ce dossier. *Preuve de fin* : un achat réel test (domaine peu coûteux)
effectué depuis l'interface, facturé au client, domaine visible dans le compte Porkbun MEMORA.

**9. Unifier les intégrations tierces sous des contrats par famille (section 6).** Créer le
contrat `EmbedProvider`, y migrer `Oembed`/`GoogleDrive`, décider du sort de `BrevoProvider`
(question 10). *Preuve de fin* : au moins un fournisseur tiers autre qu'Oembed réellement
invocable depuis l'éditeur, testé.

**10. Nettoyer le code mort inventorié**, en parallèle des étapes précédentes mais après avoir
confirmé qu'aucune pièce n'est récupérable pour l'étape 9 : `AuthorsController` (scaffold
nwidart intact), `AuthorShortUrlService`, `QrCodeService` (copie Authors), `EmbedsRichService`,
`GoogleDriveEmbedService`, `CrossPromotionService`, `MigrationImportService`, `WebPushService` +
`AuthorPushSubscription`, trois composants Livewire jamais rendus
(`AuthorAnalyticsWidget`, `AuthorRecentNotifications`, `AuthorSearch`), trois routes stub
`{"todo": true}` (`docs/specs/2026-09-11-blogues-perso-briques-dormantes.md`, sections RETIRER).
*Preuve de fin* : grep confirmant zéro référence restante, `AuthorsModuleStructureTest.php` mis
à jour pour ne plus vérifier l'existence de fichiers retirés.

**11. (Dernier, agréable à construire, seulement après un usage réel) Explorer les idées
survivantes à faible urgence.** Le carnet de décisions éditoriales version publique (score
8,66/10, synergie avec le module de vérification déjà existant du site) et le Headless Hub /
syndication (score 9,38/10, contourne le problème de domaine/SSL entièrement) - toutes deux
n'ont de sens qu'une fois qu'il existe de vrais auteurs actifs et de vrais articles publiés à
faire connaître ou à syndiquer. *Preuve de fin* : reportée, dépend du succès des étapes 1 à 10.

---

## 10. Les questions qui restent pour Stéphane

**1. La frontière gratuit/payant retenue (domaine + quota + personnalisation avancée en un seul
palier payant) te convient-elle, ou préfères-tu d'emblée deux paliers payants comme Ghost ?**
*Recommandation : un seul palier au lancement.* Motif : 1 profil auteur, 0 article publié en
production aujourd'hui - construire trois paliers avant la moindre preuve de demande répète
l'erreur nommée par le panel (DeepSeek round 1). Scinder en deux paliers plus tard, si des
clients réels le demandent, coûte moins cher que défaire une grille trop complexe.

**2. Le prix du palier payant.** *Recommandation : ne PAS fixer de chiffre définitif dans ce
document.* Ce qui est établi : le plancher doit couvrir le coût de RENOUVELLEMENT réel du
domaine (jamais le prix d'appel - un `.blog` renouvelle 8 fois son prix d'inscription,
`docs/specs/2026-09-11-blogues-perso-mesure-domaines.md`), plus le stockage et le support. Fixer
un chiffre exact exige une passe de calcul de coûts dédiée (coût serveur réel, temps de support
estimé, marge cible) que ce document n'a pas les moyens de produire sans l'inventer.

**3. Le sort des deux signaux « premium » contradictoires.** *Recommandation : `AuthorProfile.
tier` devient la seule source de vérité*, `UpgradeController` doit être corrigé pour le lire au
lieu de recalculer `subscribed('default')`. Motif : `tier` couvre déjà 4 états (dont `education`
et `premium_manual`, jamais des abonnements Cashier) alors que `subscribed()` n'en connaît que 2
- garder `subscribed()` comme référence laisserait un compte `education` sans aucun droit
premium reconnu, un bug déjà présent en germe avant même le premier quota (section 9, étape 3).

**4. CSS/HTML brut pour les payants, comme tu l'as demandé explicitement.** *Recommandation :
ne pas l'ouvrir sans audit de sécurité, remplacer par des tokens de design étendus dans des
« zones de personnalisation sûres ».* Motif : l'ouverture du CSS/HTML a été identifiée par le
panel (DeepSeek) comme un risque XSS jamais audité, et la version « moteur de validation CSS »
a été tuée par ses propres défenseurs comme sur-ingénierie fragile (section 8, item 2). C'est
une divergence directe avec ta demande initiale - je la signale explicitement plutôt que de la
trancher en silence : si tu veux vraiment du CSS/HTML brut malgré le risque, c'est ton appel,
mais il doit être pris en connaissance du risque, pas par défaut.

**5. Achat de domaine automatisé via Porkbun dès le lancement, ou en second temps ?**
*Recommandation : en second temps (étape 8).* Motif : les conditions préalables mesurées (compte
Porkbun avec au moins un domaine déjà enregistré, solde prépayé provisionné, vérifications
courriel/téléphone) n'ont pas été confirmées comme remplies. Le flux « le client pointe son
propre domaine existant » suffit pour lancer et prouver la demande avant d'investir dans
l'automatisation d'achat.

**6. Faut-il activer `BrevoProvider` pour la newsletter par auteur, ou garder le SMTP standard
actuel ?** *Recommandation : garder le SMTP standard pour l'instant.* Motif : `author_subscribers`
compte 0 ligne en production aujourd'hui - aucune preuve qu'un vrai fournisseur d'infolettre
(délivrabilité, statistiques d'ouverture) soit nécessaire avant un volume réel d'abonnés. À
reconsidérer dès qu'un auteur atteint quelques centaines d'abonnés.

**7. Le palier `education` (approbation manuelle, jamais un abonnement Stripe) reste-t-il une
offre publique, ou devient-il un cas interne réservé à des partenariats que tu approuves
toi-même ?** *Recommandation : le garder comme cas interne*, approuvé à la main via
`TierManagementService::approveEducation()` (aujourd'hui jamais appelé, à réactiver à l'étape 3)
plutôt que d'en faire un bouton public en libre-service - motif : aucun fait du dossier ne montre
de demande ni de processus de vérification d'un statut « enseignant/établissement » aujourd'hui.
