<!-- github-maison:start -->
## GitHub maison MEMORA - où pousser ce projet (laveille = EXCEPTION double remote)

Ce projet est versionné dans le **github maison** (Forgejo local sur le Pi) ET sur **GitHub** (qui porte la CI/CD de déploiement). NE PAS traiter laveille comme un projet forge-only.

- Organisation / client forge : `laveille`
- Dépôt forge : `la-veille-de-stef-v2`
- **`origin` = GitHub** (`https://github.com/memorasolutions/laveille-ai.git`) - **c'est lui qui DÉCLENCHE la CI GitHub Actions et le déploiement en prod**. Le déploiement PASSE par `git push origin master`.
- **`forge` = Forgejo Pi** (`http://100.66.177.50:3000/laveille/la-veille-de-stef-v2.git`) - miroir/backup local, aucune CI.

**Règle de push pour CE projet** : pousser vers les DEUX à chaque livraison : `git push origin master` (CI + déploiement) PUIS `git push forge master` (miroir). Ne jamais mettre Forgejo en `origin` ici (casserait la CI). Le MCP `github-maison` (gm_push --remote=forge) sert pour le miroir ; `gm_whereami` en cas de doute.
<!-- github-maison:end -->

<!-- constructeur-prompts:frontiere-gabarits -->
## Constructeur de prompts - frontière des gabarits (anti-dérive, club des sages 2026-08-20)
Un « gabarit » / pré-prompt = un ÉTAT PRÉ-REMPLI du wizard existant (un `SavedPrompt`, qui porte déjà les `spaces`). JAMAIS un gabarit qui déclare ses PROPRES champs / son propre moteur de templating : ce serait la refonte « phrase-à-trous par métier » déjà essayée puis abandonnée le 2026-08-07 (règle projet : ne pas refondre le wizard sans demander). La version « faible » (état pré-rempli) glisse vers la version « forte » (champs propres) en quelques sprints si la frontière n'est pas tenue. Toute demande d'aller vers la version forte = décision EXPLICITE de Stéphane, jamais une dérive silencieuse. La galerie de gabarits reste CURÉE par l'équipe (flag `is_official`), ZÉRO UGC public (Loi 25, pas de modération). Design : docs/specs/2026-08-20-bibliotheque-pre-prompts-design.md.
<!-- /constructeur-prompts:frontiere-gabarits -->


## Publier depuis le portail client Memora (MCP `memora-portal`)

> Mesuré en production le 2026-09-15 sur la version 1.28.0 du portail. Ce bloc a corrigé DEUX
> affirmations fausses faites le jour même : voir « Ce que j'avais faux » en fin de section.

**laveille.ai dans le portail** : compagnie **33**, Facebook **28**, LinkedIn **54** (profil
personnel de Stéphane, jeton valide au 2026-11-14). Le rattachement LinkedIn à MEMORA (compte 55)
a été désactivé le 2026-09-15 pour éviter de poster deux fois sur le même fil.

### La contrainte qui décide de tout

**Toute publication créée par ce canal exige l'approbation du client dans le portail.** C'est
imposé côté serveur, aucun paramètre ne le contourne. Une publication programmée **ne partira
jamais toute seule**, même avec une date : elle attend qu'un humain l'approuve. Ce n'est pas un
défaut, c'est un garde-fou - mais il faut le DIRE à Stéphane, plutôt que de le laisser découvrir
que rien n'est parti. Pour une publication qui doit partir seule, ce canal ne convient pas.

### Une fois approuvée, elle part SEULE

L'approbation du client est la seule étape humaine. Passé ce point, la publication part **toute
seule à l'heure prévue**, sans aucun geste au moment de l'envoi. Conditions exactes mesurées :
non refusée, date atteinte, approbation obtenue, statut `scheduled` ou `approved`.

### Vérifier une capacité, jamais la déduire

**Appelle `whoami` avant de promettre quoi que ce soit.** Il renvoie les capacités réelles sans
exposer le secret. **Ne déduis JAMAIS une capacité du fait qu'une autre fonctionne** : lire ne dit
rien sur le droit d'écrire. Et la vérification de capacité a lieu AVANT la simulation, donc un
`dry_run` renvoie le même 403 - une simulation qui échoue ne prouve pas que le contenu est mauvais,
seulement que le jeton ne porte pas le droit.

### Deux pièges de LECTURE, mesurés, qui induisent en erreur

1. **Les suppressions douces rendent un enregistrement INVISIBLE alors qu'il existe.** Le
   2026-09-13, l'API annonçait un seul compte LinkedIn ; il y en avait deux, celui de laveille.ai
   étant supprimé en douceur depuis le 20 mai. Sur cette lecture, une publication cliente a été
   retirée d'un lot à tort. **Ne jamais conclure « ça n'existe pas »** sur la seule réponse de
   l'API : dire « l'API ne m'en montre aucun », ce qui est différent.
2. **`list_social_publications` peut renvoyer une liste vide** alors que la base en contient, pour
   la même raison.

### Médias : le portail SAIT publier un carrousel

| Réseau | Médias par publication | Format |
|---|---|---|
| Facebook | 10 | album multi-images |
| Instagram | 10 | album |
| **LinkedIn** | **1** | **carrousel = document PDF** (`publishWithDocument`) |
| Google Business | 1 | image unique |

LinkedIn ne publie pas plusieurs images séparées **parce que LinkedIn lui-même ne le fait pas** :
son carrousel EST un PDF. Depuis le 2026-09-15, un média marqué pour un réseau **REMPLACE** les
médias communs pour ce réseau, il ne s'y ajoute pas.

> ⛔ **MESURE DU 2026-09-19 QUI DÉMENT LE PARAGRAPHE CI-DESSUS SUR UN POINT PRÉCIS :
> `media_urls` REFUSE un PDF.** Publication 295, média 373, URL
> `https://laveille.ai/carrousels/plan-de-cours.pdf` servie en HTTP 200,
> `content-type: application/pdf`, 144 351 octets, aucune redirection. Le portail a répondu
> `download_error: "not_an_image"` et n'a rien téléchargé (`downloaded_at` resté nul).
>
> **La nuance qui évite de surcorriger, et il faut la tenir** : ceci ne prouve PAS que le portail
> est incapable de publier un carrousel. `publishWithDocument()` existe bel et bien dans son code.
> Ce qui est mesuré, c'est que **la voie d'INGESTION par `media_urls` valide le fichier comme une
> image et rejette tout le reste**. Publier un document et en télécharger un sont deux capacités
> distinctes ; la seconde est fermée, la première n'a pas été testée par ce canal.
>
> **Historique de cette question, qui a basculé trois fois** : « le portail ne dépose pas de PDF »
> (2026-09-13), puis « FAUX, il le fait, vérifié dans le code » (2026-09-14, conclusion tirée de la
> LECTURE du code), puis la mesure ci-dessus (2026-09-19, tirée d'un APPEL RÉEL). Lire le code dit
> ce qu'il PEUT faire ; l'appeler dit ce qu'il FAIT. Quand les deux divergent, c'est l'appel qui
> tranche.
>
> **Conséquence pratique** : pour un carrousel LinkedIn de laveille.ai, la voie du portail est
> fermée tant que ce refus n'est pas levé. Le choix se fait alors entre publier à la main le jour
> dit (mode direct du skill `/publier`) et publier par le portail sans carrousel — ce qui oblige à
> réécrire le texte, puisqu'il ne peut plus renvoyer à « la dernière diapositive ».

### Paramètres qui coûtent cher quand on les rate

- **`scheduled_at` doit porter le décalage horaire** : `2026-10-12T09:00:00-04:00`. Le portail
  stocke en UTC ; sans fuseau, la publication part 4 ou 5 heures à côté. Québec = -04:00 de mars à
  novembre, -05:00 le reste de l'année.
- **`first_comment` est UNIQUE pour toute la publication**, jamais par réseau (non implémenté
  côté serveur). Il n'est posté que sur Facebook et LinkedIn ; **Google Business n'a pas de
  commentaires**, donc pour ce réseau les coordonnées vont dans le texte.
- **`media_urls` doivent être DIRECTES.** Une adresse qui redirige **échoue en silence** et la
  publication est créée sans image. Vérifier `download_error` avec `get_social_publication`.
- **`social_account_content`** donne un texte différent par réseau. Tout identifiant y figurant
  doit aussi être dans `social_account_ids` (sinon 422), et les doublons sont rejetés.
- Les comptes doivent appartenir à `company_id` et être **actifs** : un mélange entre clients est
  rejeté (422). Un compte connecté un jour n'est pas forcément utilisable aujourd'hui.
- **Essai à blanc d'abord** : `dry_run` vaut `true` par défaut, le garder, lire le résultat, puis
  écrire avec `dry_run: false` et **relire** avec `get_social_publication`.

### Ce que le jeton ne peut pas

`admin:read` + `social:write`. Il ne peut PAS modifier une compagnie ou un client (403, exige
`admin:write`), ni modifier ou supprimer un compte social. **Aucune route DELETE n'existe dans
cette API, pour aucune ressource** : ce n'est pas une permission mal réglée, c'est une absence
d'endpoint. Impossible de casser un rattachement de page Facebook, même par erreur - ce qui
compte, puisque toutes les pages clientes partent du compte personnel de Stéphane.

### ⚠ EXCEPTION laveille.ai : PAS de premier commentaire sur LinkedIn

La documentation générale du portail dit « LinkedIn : lien en premier commentaire ». **C'est la
règle pour les pages CLIENTES, elle ne s'applique PAS à laveille.ai.**

Décision de Stéphane du 2026-09-11, qui prime : sur laveille.ai, le lien court **et** le code QR
vivent sur la **dernière diapositive du carrousel**. Il n'y a **AUCUN premier commentaire** sur
LinkedIn. Motif mesuré : les hyperliens d'un PDF sont inertes dans la visionneuse LinkedIn, le code
QR est donc le seul élément réellement actionnable, et le commentaire porteur d'un lien est mal
classé jusqu'à 80 % du temps.

Le texte doit en revanche DIRE où se trouve le lien (« sur la dernière diapositive »), sinon le
lecteur ignore qu'il existe une sortie. C'est le seul panneau indicateur du dispositif.

### Rédaction : jamais les mêmes mots sur trois réseaux

- **Facebook** : du récit, 60 à 110 mots, aucune adresse web dans le corps (elle va en premier
  commentaire).
- **LinkedIn** : aucune adresse web dans le corps.
- **Google Business** : une centaine de mots, factuel, ancré localement, appel à l'action simple.

### Ce qu'il ne faut JAMAIS faire

- **Ne jamais rien affirmer sur la disponibilité d'une entreprise** : ni ouverte, ni fermée, ni ses
  horaires, ni « on s'occupera de ça après le congé ». La faute a été commise le 2026-09-13 sur
  **35 textes** annonçant une fermeture un jour férié, alors qu'un lave-auto ou une clinique peut
  très bien être ouvert. Elle s'est reproduite déguisée : « laissez faire ça pour aujourd'hui » dit
  la même chose sans le mot « fermé ».
- **Ne jamais inventer un fait** : ancienneté, effectif, prix, rabais, récompense, territoire,
  certification. En santé, un titre professionnel inventé est un problème déontologique.
- **Ne rien promettre au nom du client**, et **ne jamais énumérer ses mots-clés** : ils servent à
  trouver l'angle, pas à être recopiés.
- **Fêtes** : le Québec est laïque. Angle du CONGÉ et des PERSONNES qui en profitent, jamais la
  gratitude à l'américaine.

### Changements du portail qui nous concernent

- **1.27.0** : une publication dont la date est dépassée de plus de **14 jours** ne part plus, elle
  est marquée « périmée ». Le portail alerte quand une publication planifiée n'est pas partie.
- **1.28.0** : un échec d'authentification marque le compte social et prévient les administrateurs.
  Un compte « actif » n'est donc plus une garantie, mais un compte marqué en erreur, si.

### Ce que j'avais faux, et qui doit servir

1. J'ai affirmé le 2026-09-15 que **« le portail ne sait pas publier un carrousel »**. FAUX : il
   l'implémente pour LinkedIn, en PDF. J'avais lu la description du paramètre `media_urls`
   (« image URLs ») et j'en avais tiré une impossibilité - deuxième fois que je confonds une
   description de paramètre avec un contrat.
2. J'ai affirmé qu'**il n'existait qu'un seul compte LinkedIn, celui de MEMORA**. Ma lecture était
   incomplète : le compte 54 était supprimé en douceur, donc invisible. C'est exactement le piège
   décrit plus haut.

**Toujours** : français du Québec avec tous les accents, jamais de tiret cadratin, une ou deux
émojis au maximum. Et **montrer le texte exact à Stéphane AVANT qu'il parte** : c'est lui qui
répond de ce qui est publié au nom de ses clients.
