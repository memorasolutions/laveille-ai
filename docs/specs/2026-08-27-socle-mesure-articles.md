# Socle de mesure: les articles de laveille.ai et le skill qui les écrit

Mesure effectuée le 27 août 2026, 10h54 Québec (14h54 UTC), par une session Claude Code déléguée
par MEMORA solutions. Ce document ne recommande rien. Il établit des faits mesurés, datés et
sourcés, sur lesquels un panel d'IA travaillera ensuite. Chaque chiffre porte sa source et sa date;
chaque lacune de mesure est signalée explicitement plutôt que masquée.

**Périmètre**: « article » désigne exclusivement le contenu de fond du module Blog
(`Modules/Blog`, routes `/blog/{slug}`). Ce n'est ni une actualité (module News, `/actualites/...`,
fiches de veille automatisées), ni une fiche de glossaire (`/glossaire/...`). Les trois vivent sur
le même site mais dans des tables, des pipelines et des logiques éditoriales distinctes; les
confondre aurait faussé chaque chiffre de ce document.

---

## Partie 1: ce que dit le skill aujourd'hui, et ce qui n'y tient plus

### 1.1 Les trois points déjà établis (2026-08-23): confirmés

La note de mémoire `memory/socle-preuve-seo-aeo-geo-2026-08-23.md` et le fichier
`~/.claude/skills/article/references/seo-aeo-geo-2026.md` (refondu le même jour, après un club des
sages à cinq oracles et trois tours) portent déjà ce travail. Vérification faite en relisant ce
fichier en entier aujourd'hui: les trois points sont exacts et **le fichier de référence du skill
n'est plus périmé sur ces points précis**.

1. **Le 23 août, le skill notait 89 sur 100 un balisage mort depuis six semaines.** Confirmé: la
   version antérieure du fichier de référence, datée du 21 juin 2026, notait `FAQPage` à 89/100 et
   `llms.txt` à 82/100, deux semaines après le retrait effectif de `FAQPage` par Google.
2. **Google a retiré le résultat enrichi FAQ le 7 mai 2026.** Confirmé, avec chronologie complète
   dans le fichier de référence (restriction le 8 août 2023, retrait effectif le 7 mai 2026, avis de
   dépréciation le 8 mai, documentation supprimée le 15 juin). Le fichier de référence précise à
   juste titre que le balisage Schema.org `FAQPage` existe toujours; c'est son affichage enrichi par
   Google qui est mort.
3. **`llms.txt` est mort comme levier de citation mais vivant comme plan de site pour agents.**
   Confirmé, et vérifié une seconde fois aujourd'hui en interrogeant la production:
   `https://laveille.ai/llms.txt` répond HTTP 200 et affirme « 68 articles éditoriaux », un chiffre
   qui correspond exactement au compte mesuré en Partie 2. Le fichier existe, est à jour, et sert
   bien de plan de site pour agents (le `robots.txt` de production autorise explicitement GPTBot,
   ClaudeBot, anthropic-ai, Google-Extended, CCBot, cohere-ai, Diffbot et d'autres).

### 1.2 Ce qui a bougé autrement: un référentiel dédoublé et non synchronisé

C'est la découverte principale de cette partie. Le skill `/article` ne travaille pas seul sur ce
projet: sa propre règle de priorité (« Style de projet, PRIORITÉ ABSOLUE ») lui fait charger
**avant tout autre contenu** le fichier `.claude/writing-style/style.md` du dépôt, qui lui-même
s'appuie sur `GUIDE_REDACTION_ARTICLES.md` (racine du projet). Ces deux fichiers projet n'ont **pas**
suivi la refonte du 2026-08-23 et contredisent aujourd'hui, en pratique, les instructions du skill
central qu'ils sont censés compléter.

| Fichier | Dernière modification (git) | Statut vis-à-vis de la refonte du 2026-08-23 |
|---|---|---|
| `~/.claude/skills/article/SKILL.md` | (skill central, hors dépôt projet) | à jour, corrigé le 2026-08-23 |
| `~/.claude/skills/article/references/seo-aeo-geo-2026.md` | (skill central) | à jour, refondu le 2026-08-23 |
| `GUIDE_REDACTION_ARTICLES.md` (racine du projet) | **2026-05-25**, commit `64fda2e0` | jamais retouché depuis; antérieur de trois mois à la refonte |
| `.claude/writing-style/style.md` (chargé en PRIORITÉ ABSOLUE par le skill pour ce projet) | **2026-07-30**, commit `c6bfe2c9` (touché en passant par un commit sans rapport avec le SEO) | son contenu SEO/AEO/GEO n'a jamais été retouché; antérieur d'un mois à la refonte |

Conséquence concrète, vérifiée ligne par ligne:

**a) `FAQPage`: contradiction frontale et non arbitrée.** Le skill central (étape 4, section
Optimisation technique) dit noir sur blanc: « Si l'outil génère `FAQPage` ou `HowTo`, les
RETIRER de la sortie. » Le fichier `.claude/writing-style/style.md`, section « SEO / AEO / GEO
spécifiques », dit l'inverse: « Schema à générer: `BlogPosting` + `FAQPage` + `Person` (auteur)
via les helpers déjà en place: `lv_jsonld_blog_posting()`, `lv_jsonld_faq_page()`... » Ce dernier
fichier a priorité absolue de chargement sur ce projet précis. Les deux instructions ne peuvent pas
être suivies en même temps, et rien dans le dépôt ne les réconcilie.

Vérification empirique sur dix articles réellement publiés (les cinq plus lus et les cinq moins
lus de la Partie 2, HTML de production récupéré par `curl` aujourd'hui): **aucun des dix
n'émet de bloc `FAQPage` dans son JSON-LD** (`BlogPosting` présent dans les dix, `FAQPage` présent
dans zéro). La fonction `lv_jsonld_faq_page()` existe toujours dans le code
(`app/Helpers/jsonld.php:206`) mais n'est appelée par aucun contrôleur qui rend `/blog/{slug}` (le
seul appelant trouvé, `Modules/Authors/app/Http/Controllers/PostController.php`, sert une route
différente). **La pratique réelle a donc déjà tranché dans le sens du skill central (zéro
`FAQPage`), mais le document qui a priorité de lecture continue d'instruire le contraire.** C'est
un piège pour la prochaine session qui suivrait `style.md` à la lettre.

**b) Longueur cible: le guide et la réalité publiée divergent du simple au double.**
`GUIDE_REDACTION_ARTICLES.md` (section 1, ADN éditorial) et `.claude/writing-style/style.md`
(section Format par défaut) répètent tous les deux, mot pour mot: « 1 200 à 1 500 mots... cible
révisée mai 2026 », avec une exception « dossier de fond » jusqu'à 1 800, exceptionnellement
2 200 mots. Mesure sur les 68 articles réellement publiés (Partie 2, méthode identique):
**moyenne 2 841 mots, médiane 2 811 mots**. Seuls 3 articles sur 68 (4 %) se situent dans la
fourchette cible de 1 200 à 1 500 mots; 60 sur 68 (88 %) dépassent même le plafond « rarement »
de 1 800 mots. L'« exception dossier de fond » est devenue la norme sur la quasi-totalité du
corpus, sans que le texte du guide ait été ajusté en conséquence. Fait notable: le même fichier
`GUIDE_REDACTION_ARTICLES.md` se contredit lui-même sur ce point précis, sa propre checklist de fin
(section 6) exigeant « 2 300+ mots », un chiffre proche de la réalité mesurée mais incompatible avec
la cible de 1 200 à 1 500 mots énoncée quatre sections plus haut dans le même document.

**c) Règle absolue « Sources en fin de chaque article »: violée sur un format entier.**
`GUIDE_REDACTION_ARTICLES.md` énonce en ouverture une « Règle absolue... Sans exception »: chaque
article se termine par une section Sources, y compris un article d'opinion. Sur l'échantillon de
dix articles vérifiés en Partie 2c, deux formats publiés en violent la lettre: les articles
« Concentré IA - semaine du... » et « Fréquence Numérique » (recension hebdomadaire de nouvelles,
19 articles sur 68, voir Partie 2c et 3) ne comportent **aucune** section Sources identifiable dans
leur HTML rendu. Ce n'est pas nécessairement une erreur (ces formats citent leurs sources en ligne,
item par item, plutôt qu'en bloc final), mais la règle telle qu'écrite ne le prévoit pas comme
exception, et rien ne documente cet écart.

### 1.3 Table complète des affirmations de fond du skill

| Affirmation | Source et date | Encore vraie aujourd'hui? | Ce qui la remplace si morte |
|---|---|---|---|
| `FAQPage` inutile pour l'affichage enrichi Google | Retrait Google confirmé le 2026-05-07; skill corrigé le 2026-08-23 | Oui | Rien à ajouter; ne plus le baliser |
| `FAQPage` doit être RETIRÉ de toute sortie de `content-optimizer` | Skill central, corrigé 2026-08-23 | Oui, **mais contredit par `.claude/writing-style/style.md` (2026-07-30), qui a priorité de chargement sur ce projet** | Réconcilier les deux fichiers (hors périmètre de ce document, qui ne recommande pas) |
| `llms.txt` mort comme levier de citation, vivant comme plan de site agents | Doc Google 2026-06-15; SE Ranking ~300 000 domaines; mémoire projet 2026-08-23 | Oui, confirmé aujourd'hui (HTTP 200, contenu à jour, robots.txt ouvert aux robots IA) | Rien; le garder pour cet usage précis seulement |
| Données structurées (JSON-LD riche) sans effet mesuré comme levier de citation générative | Étude Ahrefs (1 885 pages vs 4 000 témoins), −4,6 % AI Overviews; panel 2026-08-20 | Oui | JSON-LD minimal `Article`/`BlogPosting` + auteur + dates seulement |
| Longueur d'article sans corrélation avec la citation générative (Spearman 0,04) | Ahrefs, cité dans le socle 2026-08-23 | Oui pour la citation générative | Ne dit rien sur la LECTURE humaine (voir Partie 2c et 3: ni le texte le plus court ni le plus long ne domine dans l'échantillon mesuré) |
| Cible de longueur éditoriale 1 200 à 1 500 mots | `GUIDE_REDACTION_ARTICLES.md`, 2026-05-25; répété dans `style.md`, 2026-07-30 | **Périmée dans les faits**: 88 % des articles publiés dépassent 1 800 mots (Partie 2) | Aucun remplacement écrit; la pratique s'est déplacée sans que le texte de référence ait été corrigé |
| Schema à générer: `BlogPosting` + `FAQPage` + `Person` | `.claude/writing-style/style.md`, section SEO/AEO/GEO, 2026-07-30 | **Périmée**, contredit le skill central du 2026-08-23 et la pratique réelle (0/10 articles vérifiés) | Aligner sur le skill central: `Article`/`BlogPosting` + auteur + dates, sans `FAQPage` |
| « Chaque article se termine par une section Sources, sans exception » | `GUIDE_REDACTION_ARTICLES.md`, règle absolue, 2026-05-25 | Violée sur au moins 19 articles (formats Concentré/Fréquence Numérique), voir 1.2c | Non arbitré; à documenter comme exception ou à corriger |
| Hiérarchie des règles éditoriales A à J' (proximité affirmation-source en tête) | Club des sages, clos le 2026-08-23 | Oui, rien ne l'a remis en cause depuis | - |
| Corrélation code/chiffres/définitions/comparaisons/procédures avec l'absorption en citation | Zhang, He et Yao, 29 avril 2026 (602 requêtes, 21 143 citations) | Oui, et le statut CORRÉLATION (pas causalité) reste correctement affiché dans le socle | - |
| Une recette généralisée cesse de fonctionner (C-SEO Bench, 3/54 combinaisons positives) | Cité dans le socle 2026-08-23 | Oui | - |

---

## Partie 2: ce que font réellement les articles publiés

### 2.0 Combien d'articles, où, et un premier écart méthodologique

Deux méthodes indépendantes contre la PRODUCTION, jamais contre la base locale:

- **Plan du site**: `curl -s https://laveille.ai/sitemap.xml`, préfixe `/blog/{slug}`: **68 URLs**
  distinctes, zéro doublon, zéro URL d'index nue.
- **Runner de production** (script tinker autoportant, cf. `docs/CONTRAINTES-SOUS-AGENTS.md`):
  `\Modules\Blog\Models\Article::query()->count()` = **68**, dont **68** au statut `published`. Les
  deux méthodes s'accordent exactement.

**Écart signalé**: la base de données LOCALE ne contient que **53** articles (`published`), soit
15 de moins qu'en production. C'est exactement la mise en garde de la mission: toute mesure
« contre le local » aurait sous-compté le corpus de 22 %. Ce document ne s'appuie que sur les
chiffres de production ci-dessus.

**Cadence de publication** (date `published_at`, mesurée en production, fuseau serveur):

| Mois | Articles publiés |
|---|---|
| 2025-08 (lancement) | 23 |
| 2025-09 | 10 |
| 2025-10 | 4 |
| 2025-11 | 2 |
| 2025-12 | 3 |
| 2026-01 | 8 |
| 2026-02 | 1 |
| 2026-03 | 1 |
| 2026-04 | 3 |
| 2026-05 | 6 |
| 2026-06 | 2 |
| 2026-07 | 4 |
| 2026-08 (au 27) | 1 |

Un tiers du corpus entier (23 sur 68, soit 34 %) a été publié durant le seul premier mois
d'existence du site. La cadence n'est jamais revenue à ce rythme depuis.

### 2a. Fréquentation (GA4, propriété 500300528)

Deux fenêtres, mesurées aujourd'hui, dimension `pagePath` filtrée sur `/blog/`:

| Fenêtre | Articles avec au moins 1 vue | Vues totales cumulées (tous articles) | Articles à zéro vue |
|---|---|---|---|
| 28 jours | 13 / 68 (19 %) | 91 | 55 / 68 (81 %) |
| 90 jours | 39 / 68 (57 %) | 285 | 29 / 68 (43 %) |

Classement des 6 articles les plus vus sur 90 jours (vues, durée moyenne de session, taux
d'engagement):

| Article | Vues (90j) | Durée moy. session | Taux d'engagement |
|---|---|---|---|
| IA générative en classe (guide Québec enseignants) | 49 | 3 min | 77,55 % |
| Guide NotebookLM | 34 | 3 min 12 s | 72,22 % |
| Concentré IA, semaine du 29 juin au 5 juillet | 26 | 12 min 32 s | 58,33 % |
| Concentré IA, semaine du 8 au 14 juin | 21 | 5 min 57 s | 78,95 % |
| Étudiants TDAH et IA, guide complet | 21 | 3 min 5 s | 62,50 % |
| Enseignement explicite augmenté par l'IA | 16 | 3 min 19 s | 70,00 % |

Pour comparaison, l'échelle du site: la page d'accueil seule a reçu 269 vues en 28 jours et le
constructeur de prompts 200 à 211 vues sur la même période. Aucun article de blog n'apparaît dans
le top 5 des pages les plus vues du site sur 28 jours; le premier article (« IA générative en
classe ») arrive en 6ᵉ position toutes pages confondues.

### 2b. Référencement (GSC, `sc-domain:laveille.ai`)

Sur 90 jours, dimension `page` filtrée `/blog/`, dédoublonnée par URL de base (fragments `#ancre`
et variante `www.` regroupés à l'article):

- **52 / 68 articles** (76 %) ont reçu au moins une impression dans les résultats Google.
- **16 / 68 articles** (24 %) n'ont reçu **aucune** impression en 90 jours: ils n'existent tout
  simplement pas pour la recherche Google sur cette période.
- **97 clics** et **12 766 impressions** au total sur l'ensemble du corpus blog en 90 jours (CTR
  global 0,76 %).
- **Seuls 15 / 68 articles** (22 %) ont reçu au moins un clic réel depuis Google Search en 90 jours.
- **37 des 52 articles apparus dans la recherche (71 %) n'ont reçu aucun clic malgré des
  impressions**: c'est le signal demandé par la mission, un article bien positionné mais jamais
  cliqué. Le cas le plus net:

| Article | Impressions (90j) | Clics | Position moyenne | CTR |
|---|---|---|---|---|
| Concentré IA, semaine du 8 au 14 juin | 1 708 | 0 | 6,8 | 0,00 % |
| Concentré IA, semaine du 1er au 7 juin | 3 534 | 2 | 5,2 | 0,06 % |
| Concentré IA, semaine du 29 juin au 5 juillet | 235 | 0 | 6,8 | 0,00 % |
| Protéger le droit d'auteur humain (effet pervers) | 77 | 0 | 6,1 | 0,00 % |

Position 5 à 7, soit le haut de la première page, avec un CTR pratiquement nul. C'est exactement
la signature « titre ou méta-description qui ne donnent pas envie » que la mission demandait de
repérer.

Sur **28 jours**, le signal est encore plus sévère: seulement **10 URLs de blog distinctes** ont
reçu ne serait-ce qu'une impression, et **aucune n'a reçu le moindre clic** sur cette fenêtre
courte.

**Requêtes qui font apparaître les articles** (90 jours, échantillon des 50 premières lignes): les
deux seuls articles à générer un volume de clics significatif le font sur des requêtes génériques
et évergreens, pas sur le nom du site ni une marque: « comment utiliser notebook lm », « tuto
notebooklm », « notebook lm » pour le guide NotebookLM (34 clics, 2 604 impressions, position
moyenne 12,9); « ia tdah », « adhd ia » pour l'article TDAH (31 clics, 465 impressions, position
moyenne 9,8). Les articles au format « Concentré IA... » apparaissent, eux, sur des requêtes
temporelles et vagues (« actualités ia juin 2026 », « actualité intelligence artificielle mai
2026 »), avec un volume d'impressions parfois élevé mais un CTR quasi nul (détail en 2c et
Partie 3).

### 2c. Le croisement forme/performance

Les cinq articles les plus vus (90 jours) et cinq articles sans aucune trace mesurable (0 vue GA4
**et** 0 impression GSC en 90 jours, sur 12 candidats possibles, voir 2d) ont été ouverts et
comparés (HTML de production récupéré aujourd'hui, mesure directe, pas d'estimation):

| Article | Groupe | Mots (corps) | Nb de H2 | Chiffre au titre | Section Sources | Liens externes en Sources | Date de publication | Âge (au 27 août 2026) |
|---|---|---|---|---|---|---|---|---|
| IA générative en classe | TOP | 4 447 | 16 | Non | Oui | 6 | 2026-08-21 | 6 jours |
| Guide NotebookLM | TOP | 5 140 | 13 | Oui | Oui | 32 | 2025-08-31 | 361 jours |
| Concentré, 29 juin-5 juillet | TOP | 5 223 | 24 | Oui | Non | 0 | 2026-07-05 | 53 jours |
| Étudiants TDAH et IA | TOP | 4 806 | 9 | Non | Oui | 24 | 2025-10-20 | 311 jours |
| Concentré, 8-14 juin | TOP | 3 978 | 24 | Oui | Non | 0 | 2026-06-15 | 73 jours |
| Peur du remplacement / prime à l'humain | BAS | 5 123 | 12 | Non | Oui | 20 | 2025-09-05 | 356 jours |
| Déception GPT-5 | BAS | 4 073 | 7 | Oui | Oui | 20 | 2025-08-14 | 378 jours |
| Fréquence Numérique S1 É24 | BAS | 4 477 | 8 | Oui | Non | 0 | 2025-08-05 | 387 jours |
| GPT-OSS et souveraineté économique | BAS | 3 706 | 8 | Non | Oui | 24 | 2025-08-10 | 382 jours |
| Loi 25, avantage concurrentiel caché | BAS | 3 841 | 7 | Oui | Oui | 31 | 2025-08-26 | 366 jours |

Moyennes: TOP = 4 719 mots, 17,2 H2. BAS = 4 244 mots, 8,4 H2.

**Ce qui NE distingue PAS les deux groupes dans cet échantillon**:
- La longueur: les deux groupes se chevauchent (3 706 à 5 223 mots), pas d'écart net.
- Le chiffre dans le titre: 3 sur 5 dans chaque groupe (60 %), identique.
- La présence d'une section Sources: présente dans 3/5 du groupe TOP et 4/5 du groupe BAS, pas
  discriminante.
- Le nombre de liens externes en Sources quand la section existe: TOP moyenne 20,7, BAS moyenne
  23,75, écart négligeable et dans le mauvais sens si on croyait « plus de sources égale plus de
  lecture ».
- Toutes les pages comptent exactement 19 balises `<img>` dans leur zone `<article>`: c'est très
  probablement un artefact de gabarit (icônes d'interface, vignettes d'articles liés) et non un
  signal de richesse visuelle du contenu; ce nombre est donc **écarté comme non pertinent**, pas
  interprété.

**Ce qui DISTINGUE le plus nettement les deux groupes**:
- **La densité de sous-titres (H2)**: le groupe TOP en compte environ le double du groupe BAS
  (17,2 contre 8,4). Confondu en partie par le format: deux des cinq articles TOP sont des
  « Concentré » qui comptent structurellement un H2 par nouvelle de la semaine (jusqu'à 24), ce qui
  gonfle mécaniquement leur moyenne sans que ce soit un choix éditorial comparable à un article
  narratif classique.
- **La date de publication, de très loin le signal le plus net et le moins ambigu de
  l'échantillon**: les cinq articles du groupe BAS sont TOUS publiés entre le 5 août et le
  5 septembre 2025, soit le tout premier mois d'existence du site (356 à 387 jours d'âge). Le
  groupe TOP est plus étalé, avec deux exceptions notables qui datent elles aussi de cette période
  de lancement (NotebookLM, 361 jours; TDAH, 311 jours) mais qui ont, elles, capté une demande de
  recherche générique forte et durable (voir 2b: « notebook lm », « ia tdah »).

**Hypothèses formulées, explicitement non tranchées, et testables**:

1. *Hypothèse de fenêtre de lancement*: les articles publiés durant le premier mois du site, sur
   un domaine encore sans autorité ni maillage interne dense, auraient subi un désavantage de
   départ qui persiste indépendamment de leur qualité de contenu. Testable en comparant, pour des
   articles de qualité éditoriale jugée équivalente par un tiers, la performance selon leur rang de
   publication (1ᵉʳ mois contre mois suivants), en contrôlant pour le sujet.
2. *Hypothèse de la demande de requête préexistante*: un article ne performe durablement que s'il
   répond à une requête générique déjà cherchée en volume (« notebooklm », « ia tdah »), peu importe
   par ailleurs sa forme ou son âge. Testable en croisant, pour chaque article, le volume de
   recherche préalable estimé de son sujet principal avec ses clics GSC à 90 jours.
3. *Hypothèse de la densité de sous-titres comme signal de structure, pas de qualité*: un H2 par
   item plutôt qu'un H2 par idée pourrait aider le référencement structuré (voir Zhang et al.,
   Partie 1) sans aider la lecture humaine, ce qui expliquerait un CTR très bas malgré des
   impressions élevées pour les « Concentré » (voir 2b et Partie 3). Testable en comparant le CTR
   des articles à haute densité de H2 contre les articles narratifs à H2 peu nombreux, à position
   moyenne comparable.
4. *Hypothèse nulle sur la longueur et le chiffre au titre*: dans cet échantillon précis, ni l'un
   ni l'autre ne distingue les groupes. Cela ne prouve pas leur absence d'effet en général
   (échantillon de dix), mais rien ici ne les soutient comme leviers pour CE site.

### 2d. Citabilité par les assistants IA

GA4 fournit un regroupement de canal natif nommé « AI Assistant » depuis la mise à jour de son
regroupement de canaux par défaut. Mesure directe, propriété entière (tout le site, pas seulement
le blog):

| Fenêtre | Sessions « AI Assistant » | Vues de page | Part du trafic total du site |
|---|---|---|---|
| 28 jours | 7 | 11 | 7 / 593 sessions = 1,18 % |
| 90 jours | 17 | 25 | 17 / 2 148 sessions = 0,79 % |

Détail des sources (90 jours): `chatgpt.com` (14 sessions), `copilot.com` (1), `gemini.google.com`
(1), `perplexity.ai` (1, médium `ai-assistant`), plus une session `perplexity` à médium non défini
qui n'est pas comptée dans ce regroupement.

**Sur les articles de blog spécifiquement**, une seule page de blog apparaît comme page
d'atterrissage du canal « AI Assistant » en 90 jours: *Concentré IA, semaine du 1er au 7 juin
2026*, avec 4 sessions et 11 vues. Aucune autre page de blog n'apparaît dans les 100 lignes les
plus significatives du rapport croisé canal/page d'atterrissage.

**Il faut le dire franchement**: le volume est dérisoire. Moins de 1 % du trafic total du site sur
90 jours, et une seule page de blog concernée sur 68, pour 4 sessions en trois mois. Sur la base de
cette seule mesure, la troisième priorité énoncée par le fondateur (être cité par les assistants)
ne produit aujourd'hui aucun trafic mesurable en retour pour le module Blog. Cela ne dit rien sur
la citation SANS clic (une IA peut citer un fait sans que le lecteur clique vers la source, ce que
GA4 ne mesure pas du tout); c'est un angle mort documenté en fin de document, pas une conclusion
sur l'utilité réelle du travail d'optimisation GEO.

---

## Partie 3: les tensions, formulées et non tranchées

Chaque tension ci-dessous s'appuie sur un fait mesuré en Partie 1 ou 2, pas sur une intuition.

**1. Le guide vise court, la pratique publie long, et ni l'un ni l'autre ne domine en lecture.**
`GUIDE_REDACTION_ARTICLES.md` et `.claude/writing-style/style.md` visent 1 200 à 1 500 mots; la
moyenne réelle publiée est 2 841 mots, près du double. Dans l'échantillon comparé (2c), le groupe le
plus lu n'est pas plus court que le groupe le moins lu (4 719 contre 4 244 mots en moyenne). Rien ne
prouve ici que raccourcir rapprocherait le site de sa cible affichée de lisibilité, et rien ne
prouve non plus que la longueur actuelle nuit à la lecture. La tension est que le document qui est
censé trancher cette question (le guide) donne une cible que la pratique ignore depuis des mois,
sans que personne n'ait constaté et arbitré l'écart.

**2. Le format le plus structuré pour la machine est celui que les lecteurs cliquent le moins.**
Les 19 articles au format « Concentré IA » ou « Fréquence Numérique » (28 % du corpus) sont,
structurellement, les plus proches de l'idéal « blocs extractibles » de la Partie 1: un H2 par
nouvelle, jusqu'à 24 sous-titres dans un seul article. Mesure GSC sur ces 19 articles (90 jours,
14 avec données): **6 397 impressions pour 3 clics, CTR 0,047 %**, soit environ 16 fois pire que
le CTR déjà faible de l'ensemble du corpus blog (0,76 %). C'est exactement le format que le guide
lui-même note le plus bas de son propre tableau de formats (72/100, « SEO périssable, ne pas en
faire le moteur principal », voir `GUIDE_REDACTION_ARTICLES.md` section 5), et la mesure confirme
ce jugement a posteriori. La tension: ce format sert peut-être un objectif que GA4/GSC ne captent
pas bien (fidélisation d'un lectorat déjà abonné, habitude hebdomadaire plutôt que découverte), mais
consomme un quart du volume éditorial pour un rendement de clic mesurable proche de zéro.

**3. Le seul outil qui pourrait concilier les trois objectifs est aujourd'hui en contradiction avec
lui-même.** La Partie 1 documente que le fichier chargé en PRIORITÉ ABSOLUE pour ce projet
(`.claude/writing-style/style.md`) instruit encore de générer `FAQPage`, alors que le skill central
qu'il est censé compléter interdit explicitement ce même balisage depuis le 2026-08-23, pour un
motif mesuré (retiré par Google le 2026-05-07). La pratique réelle a déjà tranché (0 sur 10
articles vérifiés n'émettent `FAQPage`), mais le document qui fait autorité de lecture ne le sait
pas encore. Tant que cet écart n'est pas corrigé, chaque prochaine invocation du skill sur ce projet
risque de réintroduire un balisage mort, ou de créer une nouvelle divergence entre ce qui est
documenté et ce qui est publié.

**4. La citabilité par les assistants coûte un budget éditorial réel pour un rendement de trafic
actuellement proche de zéro.** Moins de 1 % des sessions du site (0,79 % sur 90 jours) proviennent
d'un assistant IA, et une seule page de blog en a bénéficié en trois mois. Or les règles éditoriales
les mieux notées par le club des sages du 2026-08-23 pour l'objectif GEO (blocs extractibles,
contexte québécois précis, structure de preuve) demandent un travail d'écriture spécifique, qui
n'est pas gratuit en temps de rédaction ni neutre pour le style de lecture. La tension: investir
dans un objectif dont l'impact mesurable est aujourd'hui dérisoire, en misant sur un pari
d'anticipation (le trafic IA pourrait croître), contre concentrer l'effort sur les deux canaux qui
apportent aujourd'hui la quasi-totalité du trafic mesuré (recherche organique et direct, 691 et 743
sessions sur 90 jours respectivement, soit plus de 40 fois le volume du canal IA).

**5. Un mauvais départ semble ne jamais se rattraper, ce qu'aucune optimisation sur la page ne peut
corriger a posteriori.** Les 12 articles sans aucune trace mesurable en 90 jours (0 vue GA4 et 0
impression GSC, Partie 2d) sont TOUS publiés durant le mois de lancement du site (5 août au
5 septembre 2025). À l'inverse, deux articles publiés durant cette même fenêtre de lancement
(NotebookLM, TDAH) comptent parmi les mieux vus du corpus entier grâce à une demande de recherche
générique forte. La tension: la qualité intrinsèque d'un article rédigé pour être « captivant à
lire » ne semble pas suffire à elle seule; le moment et le contexte de sa publication (autorité du
domaine encore jeune, maillage interne encore mince à l'époque) paraissent avoir un effet qui
persiste un an plus tard. Aucune règle éditoriale du skill ne traite ce facteur, qui n'est pourtant
pas anodin dans l'échantillon mesuré.

---

## Ce qui n'a pas pu être mesuré, et pourquoi

- **La citation SANS clic.** GA4 et GSC ne mesurent que des sessions et des impressions dans les
  résultats de recherche classiques. Aucun outil disponible dans cette session ne mesure si un
  assistant IA (ChatGPT, Perplexity, Gemini, Claude) cite un article de laveille.ai dans une réponse
  sans que l'utilisateur clique vers le site. Le chiffre de 0,79 % de trafic « AI Assistant »
  (Partie 2d) mesure donc un plancher de visibilité générative, pas l'usage réel du contenu par les
  modèles.
- **Le CTR moyen attendu par position, pour évaluer objectivement le sous-clic.** Aucune donnée de
  référence sectorielle n'a été chargée dans cette session pour comparer, par exemple, le 0,06 % de
  CTR de l'article à la position moyenne 5,2 contre une norme externe (typiquement 5 à 10 % à cette
  position selon les études d'industrie généralistes, non vérifiées ici faute d'accès à la
  recherche web dans cette session). Le jugement « CTR anormalement bas » de la Partie 2b repose sur
  la comparaison interne au corpus, pas sur une norme externe sourcée.
- **Le contenu détaillé des 58 articles hors échantillon.** Seuls 10 des 68 articles ont été
  ouverts et analysés en détail (Partie 2c); les affirmations sur la présence/absence de section
  Sources sur « au moins 19 articles » (Partie 1.2c) reposent sur la reconnaissance du format par le
  slug (Concentré/Fréquence Numérique), pas sur une vérification HTML systématique des 19.
- **Toute recherche web externe.** La consigne de cette mission interdisait `WebSearch`/`WebFetch`,
  et les outils de repli documentés (`mcp__openrouter__chat_with_model` avec `perplexity/sonar-pro`,
  puis les comptes 1min.ai) n'ont pas été nécessaires: chaque affirmation de ce document s'appuie
  sur une source déjà vérifiée par le socle du 2026-08-23, sur une mesure directe de production, ou
  sur la lecture du dépôt de code. Aucune veille externe fraîche n'a donc été effectuée aujourd'hui;
  si le panel qui suit ce document a besoin de reconfirmer une norme externe (CTR sectoriel par
  position, par exemple), cette étape reste à faire.
- **La conversion (newsletter, contact) attribuable spécifiquement aux articles de blog.** Hors
  périmètre de cette mission, non mesurée ici.

---

*MEMORA solutions - info@memora.ca*
