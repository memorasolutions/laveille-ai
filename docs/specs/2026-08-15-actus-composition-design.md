# Actus 2.0 - composition manuelle assistée

**Date** : 15 août 2026 (America/Toronto)
**Statut** : PROPOSITION - aucune ligne de code avant approbation explicite
**Protocole** : club des sages, deux rounds complets. Round 1 à 4 oracles sur 5, round 2 à 3 sur 5
(claude.ai indisponible malgré resynchronisation des cookies et redémarrage de session - signalé, pas
masqué). Un fait décisif a été vérifié indépendamment auprès des sources officielles.

---

## 1. Le problème, en une phrase

La publication automatique des actualités est arrêtée et prouvée arrêtée en production ; il faut
maintenant l'outil qui permet à une personne seule de composer à la main deux ou trois fiches par
semaine, plus riches, sans reproduire le défaut mesuré de l'ancienne chaîne.

## 2. Ce qui est déjà fait - à NE PAS refaire

Vérifié par lecture du code le 15 août, jamais supposé :

- **Le composant de sélection d'actualités existe** et est réutilisable : recherche, filtres, tri,
  regroupement, sélection multiple. Déjà utilisé par deux écrans d'administration. Il ne rend PAS la
  colonne des éléments retenus, ni le glisser-déposer, ni le brouillon local : ce sont des greffons
  que la page hôte fournit. **On le réutilise, on n'en écrit pas un second.**
- **La collecte tourne toujours** chaque heure : évaluation, résumé, porte de qualité, fusion,
  déduplication. Seule l'écriture de la publication est court-circuitée.
- **Une bascule manuelle de publication existe déjà** dans la liste d'administration des articles.
- **Le bilan de chaque collecte est journalisé** sur un canal visible en production (livré le
  14 août) : c'est lui qui dira combien de propositions attendent.

## 3. Ce qui existe mais ne convient pas en l'état

- **L'écran du Concentré ne crée aucun article.** Il produit un prompt à copier ailleurs, et son
  téléversement d'image dépose le fichier **brut** : aucun traitement.
- **Trois pipelines d'images coexistent sans être mutualisés.** Le meilleur est celui des auteurs :
  5 largeurs, 3 formats, image sociale JPEG 1200x630, carte 1200x600.
- **La génération d'image par IA n'est PAS programmable.** Pilotage manuel de navigateur sur le
  compte Gemini. Toute promesse d'automatisation dans l'écran serait mensongère.

---

## 4. LE FAIT VÉRIFIÉ QUI CHANGE UNE PRÉMISSE

Soulevé par Gemini, **vérifié indépendamment auprès des documentations officielles 2026** parce
qu'un chiffre faux aurait faussé tout l'arbitrage :

> **Le refus d'entraînement et la rétention pour surveillance des abus sont deux contrôles
> DISTINCTS.** Chez OpenAI comme chez Anthropic, refuser l'entraînement n'efface pas les données :
> elles sont conservées trente jours au titre de la sécurité opérationnelle. Une rétention zéro
> exige un accord entreprise approuvé, ni automatique ni sans conditions.

**Conséquence** : le refus de collecte activé le 7 août ne fait PAS disparaître le texte source de
chez le fournisseur. Il empêche l'entraînement, pas la conservation temporaire. Nous tenions le
contraire pour acquis - c'était faux.

Ce que cela ne change pas : la décision d'arrêter de publier le texte intégral côté public restait
juste, et c'était le risque principal.
Ce que cela change : on ne peut plus écrire que le texte « ne sort pas ». Il sort, et il reste
trente jours. La minimisation avant envoi devient la seule protection réelle.

---

## 5. Décisions arrêtées

### 5.1 Deux sources sont permises, et le liant reste permis

La règle « une fiche, une source » **tombe**, et son auteur l'a lui-même retirée au round 2 :
« elle confond limitation du dommage et exactitude ; le défaut était le liant, non la pluralité des
sources ».

Ses huit contraintes de remplacement tombent aussi, retirées par leur auteur : « un langage de
génération bureaucratique, coûteux à appliquer et encore insuffisant - un identifiant exact peut
soutenir une paraphrase fausse ».

**Ce qui les remplace, une seule règle dure** (Codex, round 2) :

> **Aucune causalité, comparaison ou généralisation produite par le rédacteur ne peut être présentée
> comme provenant des sources.**

Le raisonnement qui la fonde, et qui corrige une erreur de tout le panel précédent : *« le liant est
précisément le lieu du travail éditorial. Il doit être assumé comme analyse, non maquillé en fait
sourcé. »* On ne cherche plus à supprimer le liant - c'est ce que le lecteur vient chercher. On
interdit de le faire porter par les sources.

En pratique : « à mon sens, ces deux annonces vont dans le même sens » est permis. « Les deux sources
confirment que » ne l'est pas.

Chaque bloc conserve **son propre bouton vers sa source**.

### 5.2 Conservation du texte source

**Point de départ, décision du propriétaire** : garder le texte en base, jamais exposé côté public,
suppressible à tout moment.

**Correction apportée par Codex, retenue** : *« Supprimable n'est pas une politique de
conservation. »* Sans échéance, journal de suppression et effacement en cascade, le texte survivra
dans les sauvegardes, les journaux d'activité et les exports.

**Ce qu'on conserve durablement** : l'adresse de la source, la date de capture, une empreinte du
contenu, **et les extraits effectivement cités**. Ces extraits sont courts et couverts par le droit
de citation.

**Ce qui devient supprimable sans perte** : le texte intégral. Puisque les extraits invoqués sont
conservés, la preuve subsiste après suppression.

**Écarté** : l'empreinte SEULE, à la place du texte. Gemini l'a qualifiée d'illusion au round 1
(« un hash ne prouve rien sans le texte pour le recalculer ») puis proposée comme idée neuve au
round 2 - contradiction consignée. Elle ne survit qu'en complément des extraits, jamais seule.

### 5.3 L'écran est un assistant de composition, jamais un générateur

- **Aucun bouton « générer l'image ».** Libellé exact : **« copier le prompt et ouvrir Gemini »**.
- **Aucun indicateur de progression fictif** : l'application ne sait rien de l'autre onglet.
- **Le flux ne bloque jamais sur l'image.** La fiche s'enregistre sans illustration.
- **Validation automatique du fichier rapporté** : dimensions, poids, format, orientation, présence
  du JPEG social - vérifiés par la machine avant publication.
- **Le brouillon conserve le texte source** (décision 5.2), ce qui règle le piège trouvé par Codex :
  un brouillon ne pouvait pas reprendre une composition dont la matière n'existait qu'en mémoire.

### 5.4 Le standard d'images

**Fait vérifié (Perplexity)** : Facebook accepte WebP pour l'image de partage, X aussi, **LinkedIn
pas de façon fiable** ; AVIF n'est fiable sur aucun des trois. Le JPEG social est le seul
dénominateur commun sûr.

- **Image sociale : JPEG 1200x630, obligatoire, toujours.** Antécédent : 107 images du glossaire
  rattrapées faute d'équivalent JPEG.
- **Budget mesuré** : 200 à 600 Ko d'images au chargement initial, moins d'un mégaoctet au total
  (Web Almanac 2025).
- **Le texte alternatif décrit l'image ; il ne contient pas de mots-clés.** Correction de Codex :
  « référencement maximal » pousse au bourrage, ce qui dégrade l'accessibilité sans bénéfice.
- **À tester avant de figer** : les outils d'inspection de partage des trois réseaux. Perplexity
  déclare non vérifiées les dates exactes et la limite de poids de LinkedIn.

**Divergence consignée sur la mutualisation.** DeepSeek et Codex la réclamaient au round 1 ; Codex
l'a retirée au round 2 : « avec trois pipelines et une automatisation impossible, centraliser
maintenant concentre les pannes sans supprimer le travail manuel ». **Arbitrage retenu : reportée.**
On applique le standard à l'écran des actualités sans toucher aux trois pipelines existants. La
mutualisation redeviendra pertinente si la génération devient un jour programmable.

---

## 6. Le standard est surdimensionné - ce qu'on garde et ce qu'on jette

Les trois oracles du round 2 convergent : des contraintes conçues pour brider une machine coûtent
plus qu'elles ne rapportent quand un humain écrit trois fiches par semaine. Mais - et c'est ce qui
justifie de garder quelque chose - *« la baisse de volume réduit la probabilité totale, pas la
responsabilité par fiche »*.

**GARDÉ** : publication manuelle ; provenance visible pour chaque affirmation contestable ; relecture
de la phrase contre sa source ; distinction explicite entre fait et analyse ; aperçu avant
publication ; JPEG social testé ; budget d'images ; texte alternatif descriptif.

**JETÉ** : la source unique ; l'interdiction générale du liant ; la duplication des affirmations
multisources ; le modèle de validation bloquant ; la file d'images complexe tant que le volume ne la
justifie pas ; le service d'images central avant automatisation réelle.

---

## 7. L'idée retenue du round 2

**La fiche de preuve éditoriale interne** (Codex). Chaque passage risqué affiche simultanément la
phrase publiée, l'extrait source, l'adresse, et une décision binaire **fait / analyse**. La
validation humaine n'est obligatoire que pour ces passages.

*« Elle bat les contraintes globales en concentrant l'effort exactement où vivait l'erreur. »*

C'est la seule mécanique du panel qui applique la règle 5.1 sans imposer de carcan à tout le texte.

---

## 8. Phases proposées

Aucune phase ne démarre avant approbation. Chacune se termine par une preuve, jamais par une
affirmation.

**Phase A - l'écran de composition.** Réutilisation du composant de sélection existant, colonne des
articles retenus, champ de texte source par article, construction du prompt, dépôt manuel de l'image,
validation automatique du fichier, prévisualisation, publication. Preuve : parcours complet en
navigateur visible, de la sélection à la fiche publiée.

**Phase B - la fiche de preuve éditoriale.** Marquage des passages risqués, affichage côte à côte
phrase / extrait / adresse, décision fait ou analyse. Preuve : jeu de tests avec des cas qui doivent
échouer - une généralisation présentée comme sourcée, une causalité absente de la source.

**Phase C - la conservation et sa politique.** Extraits invoqués, empreinte, date de capture,
suppression du texte intégral avec journal et effacement en cascade. Preuve : suppression réelle
suivie d'une vérification que rien ne subsiste dans les journaux ni les exports.

**Phase D - le standard d'images appliqué à cet écran**, sans toucher aux trois pipelines existants.
Preuve : test réel des aperçus de partage sur les trois réseaux.

**L'ordre A puis B est imposé** ; C peut avancer en parallèle ; D vient en dernier.

---

## 9. Ce qui reste à trancher, par qui

**Par une mesure, jamais par un oracle** : les dimensions et formats réellement acceptés par les
trois réseaux ; le taux de faux positifs de la détection de généralisations en français.

**Par le propriétaire seul** : combien de fiches par semaine ; le sort de la fonctionnalité de fiches
comparatives déjà en production ; le sort des 274 fiches indexées à risque, toujours en attente ; et
surtout - au vu du fait vérifié en section 4 - **quel fournisseur de modèle utiliser pour les
résumés, sachant que le texte y reste trente jours quoi qu'on fasse**.

**Par un juriste** : la valeur probante d'une empreinte et d'extraits si la source disparaît ou
change.

---

## 10. Explicitement écarté

- **La règle « une fiche, une source »** : retirée par son propre auteur.
- **Les huit contraintes de fragmentation** : retirées par leur propre auteur.
- **L'interdiction du liant** : le liant est le travail éditorial, pas le défaut.
- **L'empreinte seule** à la place du texte : illusion de rigueur.
- **Un modèle de validation bloquant** : « il déplace l'erreur vers un classificateur opaque et crée
  une fausse assurance ».
- **Un bouton de génération d'image** : techniquement impossible, donc mensonger.
- **La mutualisation immédiate des pipelines d'images** : reportée, pas abandonnée.
- **Le bourrage de mots-clés dans le texte alternatif.**

---

## CLÔTURE - 16 août 2026, 19h15 Québec (23:15 UTC)

**Actus 2.0 est LIVRÉ en production.**

- **v1.180.0** (17h02 Québec) - phase A : écran de composition, sélection d'une actualité, texte
  source interne jamais exposé (non-fuite prouvée par marqueur en local et vérifiée en production).
- **v1.181.0** (19h05 Québec) - le reste : courriel de veille quotidien (7h15, curseur dans la table
  des réglages persistée, insensible aux purges de cache), prompt de rédaction incorporant le
  standard du panel, fiche de preuve éditoriale (validation par sous-chaîne exacte, paires
  survivant à la suppression du texte), flux d'image manuel assumé (prompt à copier vers Gemini,
  dépôt, validation MIME réel, JPEG social 1200x630 + WebP), conservation (empreinte SHA-256 et
  date de capture survivant à la suppression).

**Preuves finales** : 244 tests News (204 au matin), 671 assertions, zéro échec ; validation
visuelle complète en navigateur (7 points sur 7) ; non-fuite vérifiée en production sur une
actualité réelle (zéro occurrence des trois champs internes dans le HTML servi) ; planification
confirmée dans la liste réelle des tâches de production.

**Le skill `/actu`** (~/.claude/skills/actu/SKILL.md) fige le flux complet et le standard - créé et
mis à jour pour refléter le code livré, pas le plan.

**Écarts assumés par rapport au plan initial** : la mutualisation des trois pipelines d'images reste
reportée (décision du panel, round 2) ; le point encore ouvert de la section 4 (le texte part chez le
fournisseur de modèle pendant la génération du résumé automatique) demeure - la minimisation avant
envoi reste la seule protection, décision du propriétaire du 15 août appliquée.

---

## Révision 2026-08-17 - prompt d'orchestration Claude Code CLI

**Décision du propriétaire** : le prompt généré par l'écran de composition cible désormais **Claude
Code CLI** (agent local avec accès au projet) comme exécutant complet - rédaction, preuve
éditoriale, image via le compte Gemini du propriétaire, ET écriture en base - plutôt qu'un simple
texte à copier dans un outil d'IA externe passif. **Panel de 5 IA unanime** : l'agent ne doit
JAMAIS écrire librement en base (aucun Eloquent, aucun SQL, aucun tinker) - une commande Artisan
bornée est la SEULE porte d'écriture.

### Ce qui a été livré

- **`generatePrompt` accepte le texte source EN LIGNE** (paramètre `source_text`) - corrige le
  blocage « Colle d'abord le texte source » quand l'admin colle le texte et clique directement sur
  Générer sans passer par Enregistrer d'abord. Persisté AVANT génération avec exactement la même
  règle de provenance que `update()` (empreinte SHA-256 + date de capture) - la logique a été
  extraite dans `NewsCompositionController::applySourceProvenance()`, un seul point de vérité pour
  les deux entrées.
- **Gabarit de prompt réécrit** (`_composition_prompt_template.blade.php`, via
  `CompositionPromptBuilder::build(NewsArticle $article, string $angle)` - signature changée) :
  mission, règles de sécurité par **spotlighting à nonce aléatoire** (délimiteurs
  `<<<SOURCE-{nonce}>>>` générés à CHAQUE appel via `Str::random(8)`, jamais réutilisés,
  déclaration avant et rappel après le bloc source), interdictions nommées (jamais publier,
  jamais `.env`/secrets, jamais de migration/déploiement, jamais une autre fiche, jamais exposer
  le texte source), métadonnées de fraîcheur (id, slug, empreinte, `updated_at`), les quatre
  étapes (rédaction - standard antérieur conservé intégralement -, preuve, écriture bornée via
  `php artisan news:apply`, image reprenable seule). Version du gabarit journalisée via la
  constante `CompositionPromptBuilder::PROMPT_TEMPLATE_VERSION`.
- **Commande `news:apply`** (`Modules\News\Console\NewsApplyCommand`, signature
  `{article} {--payload=} {--image=}`) : refuse toute fiche introuvable ou déjà publiée ; mode
  `--payload` avec liste blanche stricte (`seo_title`, `summary`, `editorial_proof_pairs` -
  toute autre clé, y compris `is_published`/`published_at`, fait refuser explicitement) et double
  protection anti-écrasement (`expected_source_hash` + `expected_updated_at` doivent correspondre
  à la fiche réelle) ; mode `--image` avec les mêmes validations que le dépôt web (type MIME réel,
  poids, dimensions minimales) via `NewsImageService::processFromLocalFile()` (nouvelle méthode,
  refactor DRY de `processFromUploadedFile()` autour d'un pipeline commun
  `processImageAtPath()`) ; jamais de publication, jamais `is_published`/`published_at` touchés ;
  journalisation sur le canal dédié `composition` (niveau fixé à `info`, indépendant de
  `LOG_LEVEL` - même parade que les canaux `fusion`/`quality_gate`/`directory_screenshots`
  existants).
- Les paires de preuve éditoriale apportées par `--payload` **complètent** les paires déjà
  présentes (fusion, jamais un remplacement intégral) - une fiche peut déjà porter des paires
  ajoutées à la main via l'écran.

### Idées explicitement écartées à ce round - ne pas les re-proposer

- **Jeton à usage unique** (consommé après le premier `news:apply`, invalidant tout rejeu) :
  reporté - sur-ingénierie pour un usage solo (un seul propriétaire, un seul agent local à la
  fois). La double protection empreinte + `updated_at` suffit à détecter une fiche modifiée entre
  la génération du prompt et l'écriture ; un jeton à usage unique protégerait contre un scénario
  de rejeu concurrent qui n'existe pas dans ce contexte d'usage.
- **Commande `news:brief`** (qui aurait généré un résumé structuré séparé du prompt) : redondante
  - le prompt collé dans Claude Code CLI EST déjà le brief complet (mission, règles, étapes),
  une commande distincte aurait dupliqué la même information sous une autre forme.
- **Purge automatique du texte source après application** : décision du propriétaire, **en
  attente** - le texte source reste supprimable manuellement à tout moment
  (`destroySourceText()`, section 5.2 ci-dessus), mais rien n'automatise sa suppression après un
  `news:apply` réussi. Ne pas l'implémenter sans une décision explicite du propriétaire.

## Récupération automatique Markdown + Publier-et-purger (2026-08-17)

**Décisions du propriétaire, arbitrées par le panel de 5 IA** : (1) à la sélection d'une
actualité dans l'écran de composition, le texte source complet est désormais récupéré
**automatiquement** en Markdown (jusque-là, l'admin devait le coller à la main) ; (2) un seul
bouton **Publier-et-purger** bascule `is_published`, horodate `published_at` et supprime
`internal_source_text` dans le MÊME geste - jamais deux actions séparées qui laisseraient une
fenêtre où une fiche déjà en ligne garde encore son texte source intégral en base.

### Ce qui a été livré

- **`Modules\News\Services\SourceMarkdownFetcher`** : étape 1 requête HTTP directe (TLS vérifié,
  un seul essai, 12 s, 403/429 → échec immédiat, jamais d'acharnement) ; étape 2 repli Puppeteer
  (`scripts/extract-article.cjs`, calqué sur `extract-og-image.cjs`, 20 s) uniquement si l'étape
  1 échoue ou produit un contenu invalide ; même parse Readability PHP des deux côtés, puis
  conversion en Markdown via `league/html-to-markdown`. Garde SSRF légère (schéma http/https
  seulement, hôte résolu et vérifié public - ni privé, ni de bouclage, ni réservé - avant toute
  requête sortante). Validation tout-ou-rien : plancher de 50 mots, détection de marqueurs de mur
  d'abonnement (échec explicite « colle le texte manuellement »), comparaison grossière du titre
  extrait au titre attendu (avertissement non bloquant seulement).
- **`POST /admin/news/composition/{article}/fetch-source`** : refuse d'écraser un texte source
  déjà présent sans confirmation explicite (`replace`, 409) ; échec → rien n'est persisté ; succès
  → une seule écriture (`internal_source_text`, provenance via `applySourceProvenance()`
  réutilisée telle quelle, `source_acquisition` avec l'empreinte SHA-256 du Markdown BRUT figée
  au moment de la récupération).
- **`POST /admin/news/composition/{article}/publish`** (bouton Publier-et-purger) : refuse une
  fiche déjà publiée (409) ; prérequis serveur (titre publié, résumé, au moins une paire de
  preuve) → 422 avec la liste complète des manquants ; revalide à 100 % les paires « fait » contre
  le texte source COURANT (pas celui du moment de la génération du prompt) - une seule paire
  invalide fait échouer toute la publication, rien ne part, rien n'est purgé.
- **`NewsArticle::publishAndPurgeSource()`** : la mécanique d'écriture de « publier = purger »
  (bascule + horodatage + purge) est extraite dans le MODÈLE plutôt que dupliquée dans chaque
  contrôleur - DRY explicite, une seule implémentation partagée par `publish()` ci-dessus ET par
  `AdminNewsController::toggleArticle()` (voir addendum ci-dessous).

### Colonnes ajoutées (migration additive réversible)

- `source_acquisition` (JSON, nullable) : trace de la récupération automatique (méthode
  http/puppeteer, URL finale, statut HTTP, nombre de mots, date de capture, empreinte SHA-256 du
  Markdown brut).
- `published_at` (timestamp, nullable) : **aucune colonne équivalente n'existait avant cette
  migration** - vérifié dans le modèle et dans toutes les migrations du module avant de l'ajouter.
  Volontairement distincte de `pub_date` (date de publication originale chez l'éditeur source,
  jamais écrasée, utilisée par l'index d'accueil) : `published_at` porte le moment où LA VEILLE
  elle-même a publié la fiche.

### Addendum (même jour) - « purge garantie sur tous les chemins de publication »

Exigence du propriétaire reçue après la première livraison : *« important de ne jamais garder les
articles originaux, important de vérifier »*. Deux ajouts :

- **`AdminNewsController::toggleArticle()`** (bascule rapide de `/admin/news/articles`) publiait
  jusque-là sans purger - trou bouché en la faisant appeler `publishAndPurgeSource()` quand elle
  publie (jamais quand elle dépublie : le texte est déjà parti dès la première publication, et une
  republication future ne le fait pas renaître).
- **`news:verify-source-purge`** (commande sans argument, idempotente) : filet de vérification
  quotidien (`routes/console.php`, 07h05, avant le digest de 07h15, `withoutOverlapping`) - trouve
  toute fiche `is_published=true` dont `internal_source_text` est encore non NULL, peu importe le
  chemin de publication emprunté, la purge, journalise chaque cas sur le canal `composition` (id,
  slug, longueur du texte purgé). Le vrai filet : même un chemin de publication futur qui
  oublierait d'appeler `publishAndPurgeSource()` serait rattrapé sous 24 h.

### Arbitrages du panel de 5 IA

- **`robots.txt` non vérifié** : geste unitaire déclenché à la main par le propriétaire pour UNE
  fiche à la fois (pas un robot d'indexation de masse) - claude.ai et Gemini pour l'omission
  (proportionnalité), Codex et DeepSeek pour une vérification systématique par prudence. Tranché
  en faveur de l'omission : la charge d'ingénierie (parser, cache, respect des règles
  `Crawl-delay`) est disproportionnée pour une action manuelle et occasionnelle.
- **Paywall jamais contourné** (art. 41.1 de la Loi sur le droit d'auteur) : aucune tentative de
  connexion, de résolution de CAPTCHA ou de contournement technique - un mur détecté échoue avec
  un message invitant à coller le texte manuellement. Unanimité du panel, aucune divergence.
- **UA navigateur conservé** (même chaîne que `ContentExtractor`, pas un UA « bot » identifiable) :
  divergence consignée - claude.ai plaidait pour un UA distinctif (transparence vis-à-vis des
  éditeurs), les quatre autres pour la cohérence avec l'existant (un second comportement de
  scraping sur le même domaine aurait été plus détectable, pas moins).
- **Jina.ai Reader rejeté** : dépendance à un tiers externe pour une fonctionnalité qui doit
  fonctionner hors ligne/sans clé API tierce et sans exposer les URLs sources à un service externe
  non contractualisé.
- **Diff complet HTML original vs Markdown rejeté** : aurait exigé de conserver le HTML brut
  quelque part pour comparer - stockage double, exactement contraire à l'objectif de purge.
  `raw_markdown_hash` (empreinte seule, pas le contenu) suffit à prouver toute retouche
  ultérieure sans rien conserver de plus.
- **File d'attente asynchrone (job/queue) rejetée** : sur-ingénierie pour une action déclenchée
  à la main par un seul propriétaire, une fiche à la fois - `set_time_limit(40)` côté requête
  synchrone suffit, le repli Puppeteer (20 s max) restant largement sous ce plafond.

## Note datée 2026-08-17 (fin de journée) - l'agent publie lui-même via `news:apply --publish`

**Décision propriétaire 2026-08-17 (fin de journée) : l'agent publie lui-même via `news:apply
--publish` et fournit le lien d'inspection - renverse l'arbitrage « l'agent ne publie jamais » du
panel du même jour (section « Révision 2026-08-17 - prompt d'orchestration Claude Code CLI »
ci-dessus, et l'interdiction nommée correspondante dans le gabarit de prompt) ; mitigation : mêmes
prérequis que le bouton manuel, porte unique.**

Concrètement : l'agent Claude Code CLI exécutant le prompt d'orchestration de l'écran de
composition exécute désormais, en toute fin de flux (nouvelle ÉTAPE 6, après le texte de l'étape
3, l'image de l'étape 4 ET la révision adversariale obligatoire de l'étape 5 - addendum reçu
pendant cette même révision, détaillé plus bas), `php artisan news:apply {id} --publish`, puis
donne au propriétaire le lien public direct de la fiche - qui l'inspecte donc APRÈS publication
plutôt qu'avant.

Ce que ça change concrètement :

- **`NewsApplyCommand`** gagne un troisième mode indépendant `--publish`, combinable avec
  `--payload`/`--image` dans le même appel ou utilisable seul dans un appel séparé. Applique
  EXACTEMENT les mêmes prérequis que le bouton manuel Publier-et-purger de l'écran de composition
  (`NewsCompositionController::publish()`) : `seo_title` non vide, `summary` non vide, au moins
  une paire de preuve, et revalidation à 100 % des paires « fact » contre le texte source COURANT.
  Refuse si la fiche est déjà publiée (même garde générique que les modes `--payload`/`--image`).
- **DRY strict** : la règle « prêt à publier » (prérequis + revalidation) était dupliquée dans le
  seul contrôleur avant cette révision. Elle est désormais extraite dans une méthode UNIQUE,
  `NewsArticle::publishReadinessCheck()`, réutilisée telle quelle par
  `NewsCompositionController::publish()` (bouton manuel) ET `NewsApplyCommand` (`--publish`,
  porte bornée de l'agent) - aucune divergence possible entre les deux chemins. La mécanique
  d'écriture reste celle déjà existante, `NewsArticle::publishAndPurgeSource()` (règle unique
  « publier = purger »), inchangée.
- **Gabarit de prompt** (`_composition_prompt_template.blade.php`) : l'interdiction nommée « ne
  publie jamais cette fiche » devient « la publication passe EXCLUSIVEMENT par
  `news:apply --publish`, jamais par un autre moyen, et SEULEMENT à l'étape 6, après texte, image
  ET révision adversariale appliqués ». Toutes les autres interdictions (`.env`, migrations,
  autres fiches, exposition du texte source) restent inchangées. La fin du prompt rappelle le lien
  public à transmettre, ce que la révision de l'étape 5 a trouvé et corrigé, et que la fiche reste
  dépubliable depuis `/admin/news/articles` si l'inspection post-publication révèle un problème.
  Version du gabarit incrémentée UNE SEULE FOIS pour l'ensemble des addenda de cette révision
  (`CompositionPromptBuilder::PROMPT_TEMPLATE_VERSION`, `2026-08-17.2`).
- **Deux seuls endroits du code entier** écrivent désormais `is_published`/`published_at` :
  `NewsCompositionController::publish()` (bouton manuel, HTTP) et `NewsApplyCommand` en mode
  `--publish` (agent, CLI) - jamais un troisième chemin, jamais un Eloquent/SQL/tinker direct.

### Trois addenda reçus PENDANT cette même révision (même jour, un seul incrément de version)

1. **Recherche avant rédaction (règle de rédaction n°7, ÉTAPE 1)** : si le texte source laisse une
   question factuelle ouverte, l'agent DOIT chercher (`pp_search` ou équivalent) avant d'écrire
   « je n'ai eu accès à aucune source confirmant X » - cette issue reste valide, mais seulement
   après une recherche réellement tentée, jamais comme raccourci. Ce qui vient d'une recherche
   complémentaire est attribué à sa propre source et ne peut alimenter qu'une paire de preuve
   « analysis » (jamais « fact », réservée au texte source de la fiche).
2. **structured_summary effacé au profit de la composition (défaut découvert en production)** :
   la fiche publique (`show.blade.php`, bloc `@if($ss) ... @elseif($article->summary)`) affiche
   `structured_summary` (résumé MACHINE de la collecte) EN PRIORITÉ sur `summary` - tant qu'il
   subsistait, une composition manuelle appliquée via `news:apply --payload` restait invisible sur
   le site. Extrait dans une méthode UNIQUE, `NewsArticle::logStructuredSummaryOverride()`
   (journalise l'ancienne valeur sur le canal `composition` avant l'effacement, jamais perdue en
   silence), réutilisée par DEUX endroits SEULEMENT : `NewsApplyCommand` (mode `--payload`, dès
   qu'un champ de contenu est appliqué) et `NewsCompositionController::publish()` (juste avant
   `publishAndPurgeSource()`). Volontairement PAS dans `publishAndPurgeSource()` elle-même (sinon
   `AdminNewsController::toggleArticle()` et `news:verify-source-purge` effaceraient aussi le
   résumé machine de fiches jamais passées par l'écran de composition - hors mandat), ni dans
   `update()` (l'admin peut retoucher le texte sans forcer la bascule d'affichage).
3. **ÉTAPE 5 - RÉVISION ADVERSARIALE, obligatoire avant toute publication** : renumérote la
   publication de l'étape 5 à l'étape 6. Avant d'exécuter `--publish`, l'agent relit la fiche
   TELLE QU'APPLIQUÉE (pas son brouillon) avec le mandat de la démentir sur trois axes - VRAI
   (chaque affirmation appuyée par une paire « fact » ou une recherche sourcée), VÉRIFIABLE
   (attribution dans la phrase), PARFAITEMENT VULGARISÉ (compréhensible par un lecteur non
   initié, termes techniques expliqués, phrases courtes). Un défaut trouvé → correction, ré-
   application via `news:apply --payload` (ré-applicable tant que la fiche n'est pas publiée), et
   nouvelle révision - la publication n'a lieu que quand la révision ne trouve plus rien, et le
   rapport final au propriétaire liste ce qui a été trouvé et corrigé (« rien trouvé » est une
   conclusion à énoncer, jamais une esquive silencieuse).

## Bonification panel 2026-08-17 (soir)

**Décision du propriétaire, panel de 5 IA, 2026-08-17 (soir) : les fiches doivent CITER l'original
(sources primaires visibles) et porter une PHOTO créditée plutôt qu'une illustration.**

### Synthèse

- **Fait-primaire avec préséance** : un 3e type de paire de preuve éditoriale, `primary_fact`,
  s'ajoute à `fact`/`analysis` (section 5.1 ci-dessus). Une paire `primary_fact` cite l'original mot
  pour mot et exige un `source_url` (URL http/https valide) ; contrairement à `fact`, son extrait
  N'EST JAMAIS revalidé en sous-chaîne du texte source collé pour l'agent - c'est précisément ce qui
  lui donne PRÉSÉANCE sur un `fact` construit à partir du texte secondaire quand les deux se
  contredisent : la source primaire fait foi, pas le texte collé. `NewsArticle::
  publishReadinessCheck()` (réutilisée par `NewsCompositionController::publish()` ET
  `NewsApplyCommand --publish`, aucune divergence possible entre les deux portes) revalide
  uniquement la présence d'un `source_url` non vide pour ce type, jamais une sous-chaîne.
- **Verdict de divergence** : quand le texte source collé et une source primaire citée divergent sur
  un fait, l'agent doit trancher explicitement - paire `primary_fact` faisant foi, ou mention
  nommée de la divergence dans la révision adversariale de l'étape 6 - plutôt que de laisser
  cohabiter silencieusement deux versions contradictoires dans la fiche publiée.
- **Ordre révision-puis-photo** : dans le prompt d'orchestration (gabarit
  `_composition_prompt_template.blade.php`/`CompositionPromptBuilder.php`, hors périmètre de cette
  bonification côté code - agent parallèle), la révision adversariale de l'étape 6 précède
  désormais la recherche et le dépôt de la photo, pour qu'une correction de texte trouvée en
  révision ne rende jamais une photo déjà choisie inadéquate à l'angle final de la fiche.
- **Reconstitution aveugle** : technique retenue pour la révision adversariale - l'agent reconstruit
  mentalement les faits de la fiche à partir des seules paires de preuve (`fact`/`primary_fact`),
  sans relire son propre brouillon, pour détecter les affirmations qui ne tiennent que par la
  mémoire du brouillon plutôt que par une preuve traçable.
- **Porte rester-brouillon** : l'agent peut choisir de NE PAS appeler `--publish` si la révision
  adversariale trouve un défaut non corrigible dans l'immédiat - la fiche reste un brouillon
  exploitable plus tard, plutôt que publiée avec un défaut connu. Cohérent avec la porte bornée
  existante : rien n'oblige `--publish` à conclure chaque exécution du prompt.
- **Photos libres de droits créditées** : remplace l'illustration générée par défaut - une photo
  réelle, sous licence libre de droits, avec un crédit obligatoire (`image_credit`, ex. « Photo :
  Untel, Unsplash ») affiché discrètement sous l'image principale de la fiche publique.
- **Sources primaires affichées** : `primary_sources` (tableau `{label, url, note?}`) est désormais
  un champ PUBLIC - contrairement à `internal_source_text`/`editorial_proof_pairs`, jamais lus par
  un chemin public - affiché en fin de fiche (`Modules\News\resources\views\public\show.blade.php`,
  section « Sources », jamais une citation par affirmation) : les sources primaires d'abord, puis le
  relais média existant renommé « Relais média » puisque la source primaire prime désormais.

### Colonnes ajoutées (migration additive réversible)

`primary_sources` (JSON, nullable) et `image_credit` (string, nullable) sur `news_articles` -
migration `2026_08_17_180000_add_primary_sources_and_image_credit_to_news_articles.php`, garde
`hasColumn()` dans les deux sens comme toutes les migrations précédentes de ce design doc. Seul
écrivain : `NewsApplyCommand` (`--payload`), même porte bornée que les champs de composition
existants - aucune dérogation, voir « Écartés » ci-dessous.

### Écartés à ce round - ne pas les re-proposer

- **Fraîcheur/rectificatifs** : pas de mécanisme de suivi des rectificatifs publiés après coup par
  la source primaire - hors périmètre ; une fiche `laveille.ai` n'est pas un flux d'actualité vivant
  qui se met à jour après publication.
- **Quota d'analyse** : pas de limite chiffrée sur le nombre de paires `analysis` par fiche - la
  porte de qualité reste la révision adversariale (agent puis, en aval, le propriétaire), pas un
  compteur arbitraire.
- **Dérogation à la porte bornée** : aucune exception à `news:apply` comme SEULE porte d'écriture de
  l'agent, même pour `primary_sources`/`image_credit` - même garde-fou que tous les champs de
  composition existants : jamais un Eloquent/SQL/tinker direct par l'agent, jamais un autre chemin.
- **Registre `claim_id` complet** : pas de registre séparé identifiant chaque affirmation par un
  identifiant unique traçable à travers tout le pipeline - `editorial_proof_pairs` reste la seule
  structure de traçabilité, jugée suffisante à l'échelle actuelle du site.

### Divergence consignée

claude.ai proposait une inspection du propriétaire AVANT la publication - un retour à l'arbitrage du
2026-08-17 après-midi (section « Révision 2026-08-17 - prompt d'orchestration Claude Code CLI »
ci-dessus). Décision du propriétaire, tranchée : l'inspection reste APRÈS `--publish` (note datée
2026-08-17, fin de journée, ci-dessus - jamais renversée par cette bonification). Mitigation
retenue : la fiche telle qu'appliquée est affichée intégralement dans le rapport final de l'agent au
propriétaire, AVANT l'appel à `--publish` dans ce même rapport - l'inspection réelle porte donc sur
du contenu déjà visible, même si le geste de publication précède sa lecture effective par le
propriétaire.

## Implémentation /actu2 - volet serveur (2026-08-17)

**Contexte** : un skill Claude Code LOCAL (`/actu2 <url> fiche:<id>`) orchestre désormais la
composition d'une fiche à partir d'UN LIEN plutôt qu'à partir de matière déjà collée dans l'écran
(retrouve l'original, récolte, rédige, prouve, révise, illustre, publie). Les règles VÉRIFIABLES
restent serveur, exactement comme le reste de ce design doc - le skill n'écrit jamais librement en
base, il ne peut appliquer son travail que par les portes bornées ci-dessous. Cette section fige
les contrats exacts que le skill consomme.

### Colonnes ajoutées (migration additive réversible)

Migration `2026_08_17_190000_add_actu2_orchestration_fields_to_news_articles.php`, garde
`hasColumn()` dans les deux sens comme toutes les migrations précédentes de ce design doc, sur
`news_articles` :

- `nature_original` (string, nullable) - classification INTERNE de la nature de l'original
  retrouvé : `annonce_commerciale`, `etude_evaluee`, `preimpression`, `message_personnel`. Jamais
  affiché tel quel sur la fiche publique.
- `niveau_preuve` (string, nullable) - `primaire` | `mixte` | `relais`. PUBLIC, mais toujours
  traduit en français courant côté fiche (badge sobre près de la section « Sources »), jamais
  l'étiquette technique brute : « Fondée sur la source originale » / « Sources originale et
  média » / « D'après un média relais ».
- `original_post` (JSON, nullable) - `{text, author?, handle?, date?, url?}`, citation STATIQUE
  d'un post X quand l'ORIGINAL est lui-même un post. PUBLIC, affichée après le résumé
  (`show.blade.php`, classes `nw-post-quote*`) - JAMAIS le widget `platform.x.com` (script tiers
  interdit : pistage, CSP, fragilité).

Seul écrivain des trois : `NewsApplyCommand` (`--payload`), même porte bornée que les champs de
composition existants - aucune dérogation.

### `news:brief {article}` - point d'entrée du skill, LECTURE SEULE

`Modules\News\Console\NewsBriefCommand`. N'écrit RIEN (aucun `update()`). Sort un JSON canonique
sur stdout :

```json
{
  "id": 123,
  "slug": "titre-de-la-fiche",
  "title": "Titre de collecte (RSS)",
  "url": "https://exemple.com/article-collecte",
  "resolved_url": "https://exemple.com/article-collecte-resolue",
  "is_published": false,
  "source_content_hash": "sha256...ou null",
  "source_captured_at": "2026-08-17T10:00:00-04:00 (ISO 8601, ou null)",
  "updated_at": "2026-08-17T10:05:00-04:00 (ISO 8601)",
  "primary_sources": [{"label": "...", "url": "...", "note": null}],
  "nature_original": "etude_evaluee (ou null)",
  "niveau_preuve": "primaire (ou null)",
  "has_image": false,
  "policy_version": "valeur courante de CompositionPromptBuilder::PROMPT_TEMPLATE_VERSION",
  "site_url": "https://laveille.ai/actualites/titre-de-la-fiche"
}
```

Fiche introuvable → code de sortie non nul, message d'erreur sur `error()` (jamais de JSON
partiel). `policy_version` permet au skill de détecter un désalignement avec les règles
d'orchestration en vigueur avant de composer.

### `news:source {article} {url} [--replace]` - récolte de l'ORIGINAL

`Modules\News\Console\NewsSourceCommand`. Deuxième porte d'écriture bornée, aux côtés de
`NewsApplyCommand` - n'écrit QUE `internal_source_text`, `source_acquisition` et la provenance
dérivée (`source_content_hash`/`source_captured_at`, via `NewsArticle::sourceProvenanceUpdates()`,
extraite de l'ancien code privé du contrôleur pour ce mandat - DRY strict, aucune duplication).
Réutilise ENTIÈREMENT `SourceMarkdownFetcher::fetch()` (même garde SSRF, même refus de paywall,
même repli Puppeteer que l'écran de composition).

`{url}` est l'URL de l'ORIGINAL trouvé par le skill - pas nécessairement l'URL déjà collectée par
le flux RSS (le skill peut avoir remonté jusqu'au communiqué, au post X ou à l'étude source).

Garde-fous, dans l'ordre : refuse sur une fiche déjà publiée (même message que
`NewsApplyCommand`) ; refuse d'écraser un texte source déjà présent sans `--replace` explicite
(même règle que `NewsCompositionController::fetchSource()`) ; sur échec de la récupération, ne
persiste rien. Journalise sur le canal `composition`.

Sortie JSON sur succès (le skill recopie ces deux valeurs telles quelles dans son payload
`news:apply`, comme `expected_source_hash`/`expected_updated_at`) :

```json
{"success": true, "article_id": 123, "source_content_hash": "sha256...", "updated_at": "2026-08-17T10:06:00-04:00"}
```

### `news:apply --payload=` - clés de contenu ajoutées

Trois clés rejoignent la liste blanche stricte de `NewsApplyCommand::ALLOWED_PAYLOAD_KEYS`, mêmes
garde-fous de validation que les clés existantes (refus explicite, jamais un enregistrement
partiel) :

- `nature_original` (string) - une des quatre valeurs ci-dessus, sinon refus.
- `niveau_preuve` (string) - une des trois valeurs ci-dessus, sinon refus.
- `original_post` (objet) - `text` obligatoire (chaîne non vide, max 1000 caractères) ; `author`,
  `handle`, `date` optionnels (chaînes) ; `url` optionnelle mais doit être une URL http/https
  valide si fournie ; toute clé hors de cette liste fait refuser tout le payload.

Comme les autres clés de contenu, `nature_original`/`niveau_preuve`/`original_post` REMPLACENT
intégralement la valeur existante à chaque application (aucune accumulation, contrairement à
`editorial_proof_pairs`) et SURVIVENT à `publishAndPurgeSource()` - même garde-fou que
`primary_sources`/`image_credit`.

### Écran de composition - bouton principal remplacé

Le bouton « Enregistrer et générer le prompt Claude Code » devient « 📋 Copier le prompt /actu2 » :
construit CÔTÉ CLIENT (aucun appel serveur) le mini-prompt `/actu2 {source_url} fiche:{id}` à
partir de `selectedArticle.source_url` (déjà calculé côté serveur par `show()` :
`resolved_url ?: url`) et `selectedArticle.id`, copié au presse-papier. Fonctionne même sans texte
source collé - c'est le skill qui récolte l'original via `news:source`. L'ancien flux (gros
gabarit, `generatePrompt()`/`copyPrompt()`) reste inchangé et accessible, déplacé dans le volet
replié « Édition manuelle (filet de secours) », étiqueté « (déprécié - l'ancien gros prompt) ».

### Écarté à ce round

- **Aucune dérogation à la porte bornée** : `news:source` n'écrit que le texte source et sa
  provenance, jamais un champ de la liste blanche de `news:apply` - un skill qui aurait besoin
  d'appliquer du contenu passe TOUJOURS par `news:apply --payload` ensuite, jamais un raccourci.
- **Aucun format de sortie alternatif** (YAML, texte libre) pour `news:brief`/`news:source` : JSON
  strict sur stdout uniquement, pour rester trivialement analysable par le skill.

## Améliorations en attente (consignées le 2026-08-17, après le PREMIER cycle /actu2 réel - fiche 33530)

Par ordre de priorité. Chacune attend son propre cycle (veille au besoin, implémentation, tests, déploiement).

1. **Commande `news:create-draft {url} [--title=]`** (haute) : le premier test a prouvé qu'aucune
   création manuelle de fiche n'existe - un post X hors collecte RSS n'a pas de fiche à composer.
   Créée en supervision cette fois (source « Soumission manuelle », brouillon) ; formaliser en
   commande bornée avec test, et l'écran de composition pourrait offrir « Créer une fiche depuis
   un lien ».
2. **Outillage de l'exécution en PRODUCTION du cycle /actu2** (haute) : le premier cycle a exigé
   8 scripts one-shot écrits à la main (création, lecture du texte, application, révision, image,
   publication, correctifs). Fluidifier : un runner générique réutilisable (un seul script
   paramétré par action+payload, même sécurité jeton+auto-suppression) ou une exécution via
   session serveur. Le skill documente déjà que le choix appartient au superviseur.
3. **Mise en page des fiches - v1.187.0** (arbitrée par le panel du 17 août, à exécuter) : badge
   unique en français clair au lieu de « 8/10 »/« Élevé » opaques ; « Résumé IA » → « L'essentiel »
   avec transparence reformulée en force dessous ; haut de page allégé (titre non répété,
   métadonnées réduites, boutons sous le résumé) ; ligne « D'après [original], relayé par
   [média] » sous les métadonnées ; fin de page dégraissée (3 liens contextuels, « article
   précédent » supprimé) ; partage natif mobile (Web Share API).
4. **Articles connexes par ENTITÉS partagées** (idée neuve claude.ai retenue) : index d'entités
   (entreprises, modèles, lois) par fiche - connexes réellement pertinents sans modération, plus
   auto-liaison glossaire à la rédaction.
5. **Courriel de veille 7h15 : inclure le mini-prompt /actu2 copiable** pour chaque actu listée
   (aujourd'hui le lien mène à l'écran, d'où il faut recliquer « Copier »).
6. **`pp_search_many` (requêtes parallèles Perplexity) en panne** : TimeoutError sur les onglets
   parallèles (constaté 2 fois le 17 août) - la requête simple fonctionne ; réparer le MCP.
7. **Session Gemini navigateur non connectée** : `ia-sync` AVANT redémarrage de session (ordre
   documenté en mémoire) - encore utile pour les images d'autres sections (les actus sont passées
   aux photos).
8. **Fiches canoniques du skill** (`references/fiche-*.md`) : à confronter aux 3-4 premières
   vraies fiches et ajuster le ton si dérive.
