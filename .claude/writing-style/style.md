# Style de projet : La veille de Stef (laveille.ai)

> Construit automatiquement le 2026-07-09 par analyse de contenu réel publié
> (articles id 1, 6, 7, 9, 53 de la table `articles`), de `GUIDE_REDACTION_ARTICLES.md`
> (racine du projet, référence éditoriale existante - ce fichier la complète, ne la
> remplace pas) et de la charte graphique (`public/css/charte.css` +
> `public/css/components.css`). Priorité absolue pour `/article` dans ce projet :
> charger ce fichier avant tout profil central `styles/<slug>.md`.

```yaml
slug: la-veille-de-stef
nom: La veille de Stef (laveille.ai)
type: style-de-projet
```

## Identité de voix

- **Public cible** : professionnels, PME et curieux francophones du Québec sur
  l'IA - du néophyte (guides « c'est quoi X ? ») au lecteur technique avancé
  (séries « J'ai créé mon IA en local », comparatifs LLM/outils). Un article
  technique/comparatif ne doit pas exclure le néophyte : chaque terme pointu
  reste défini en encadré au premier usage.
- **Niveau de langue** : courant à soutenu - vulgarisation experte, jamais du
  jargon non traduit.
- **Tutoiement ou vouvoiement** : **vouvoiement** (« vous ») pour s'adresser au
  lecteur - règle stricte du guide maison. Un tutoiement isolé repéré dans une
  réponse FAQ de l'article #1 est une erreur d'édition, pas un style à imiter :
  toujours corriger « ton/tu » en « votre/vous ».
- **Personne** : « je » pour l'expérience vécue de l'auteur (tests, anecdotes,
  bancs d'essai personnels), « vous » pour s'adresser au lecteur. Le mélange
  je/vous est la signature du site (ex. « je vais vous expliquer comment je
  suis passé de... »).
- **Ton** : expert pédagogue chaleureux, curieux, un brin autodérision
  (« ok je sais ce n'est pas le plus récent »), opinion assumée (« à mon
  avis », « je le dis comme je le pense »), toujours ancré Québec (au moins un
  exemple local par article : PME, école, gouvernement, réalité d'ici).

## Lexique

- **Mots/tournures à privilégier** : « concrètement », « le point clé ici »,
  « sauf que », « en pratique », « à mon avis », « ce n'est pas un problème de
  X, mais un problème de Y » (structure contrastive), analogies du quotidien
  filées sur 2-3 phrases (le grille-pain et l'adaptateur, le divan trois
  places dans une Mini Cooper).
- **Mots/tics à bannir** : ouvertures « Bien sûr »/« Certainement » ; connecteurs
  scolaires mécaniques « Tout d'abord / Ensuite / Enfin » ; « Dans cet article,
  nous allons » ; « Il est important de noter que » ; superlatifs vagues non
  dosés ; tiret cadratin « — » (toujours virgule, parenthèses ou deux phrases).
- **Vocabulaire technique obligatoire (contenu comparatif/tech)** : garder les
  termes anglais consacrés du domaine IA/dev tels quels - *tokens/s*, *token*,
  *inference*, *LLM*, *prompt*, *benchmark*, *context window*, *fine-tuning*,
  *agentic*, *MCP* - mais définir chacun au premier usage via un encadré
  `callout-vulgarisateur` ou le composant « La réponse » (voir Encadrés
  visuels). Ne jamais laisser un acronyme technique sans traduction immédiate.
- **Anglicismes** : tolérés pour le jargon IA/dev établi ci-dessus (aucun
  équivalent français naturel) ; à éviter partout ailleurs (« courriel » plutôt
  qu'« email », etc.).

## Format par défaut

- **Longueur cible** : 1 200 à 1 500 mots pour un article standard (cible
  révisée mai 2026, cf. `GUIDE_REDACTION_ARTICLES.md`). **Pour un comparatif
  technique avec tableaux de prix/performance** : exception « dossier de
  fond » jusqu'à 1 800-2 200 mots justifiée par la densité de données
  (plusieurs tableaux, plusieurs critères) - la densité prime toujours sur le
  volume, ne pas délayer pour atteindre un compte de mots.
- **Structure préférée** : answer-first stricte.
  1. H1 formulé comme la question que poserait le lecteur à une IA (ex.
     « Quel est le meilleur LLM de codage en 2026 ? »).
  2. Réponse courte de 40 à 50 mots juste sous le H1, avant toute analogie -
     dans le composant visuel « La réponse » (voir Encadrés visuels).
  3. Analogie d'ouverture concrète et québécoise.
  4. Corps en 5 à 8 H2 narratifs/questions, maximum 3 H3 par H2, jamais de
     saut H1 → H3. Paragraphes mono-idée de 3 à 4 lignes.
  5. **Un tableau comparatif dans le premier tiers** de l'article (les
     moteurs IA/AEO citent en priorité le contenu structuré) - critère
     numéro un pour un article de comparatif technique.
  6. FAQ de 3 à 5 questions en H3, réponses de 40 à 80 mots (le guide central
     dit 40-80 ; le socle `/article` demande 80-150 - pour ce projet,
     respecter la fourchette du guide maison, plus stricte, 40-80 mots).
  7. Section Sources tout à la fin (voir SEO/AEO/GEO ci-dessous).
- **Densité de tableaux/listes** : haute pour un comparatif - au moins 1
  tableau `.tableau-article` de critères et, si pertinent, un second tableau
  de prix/performance ; listes à puces pour les blocs « Ce que vous allez
  apprendre » en intro.
- **Présence d'anecdotes/exemples chiffrés** : oui, systématique - au moins un
  test ou benchmark personnel de l'auteur (« j'ai testé », un chiffre réel
  mesuré, une capture), jamais une pure synthèse de sources tierces.
- **CTA de fin** : lien vers un article de la même série ou un contenu connexe
  (maillage interne 3-5 liens) + invitation newsletter sobre. Jamais de
  promotion commerciale appuyée.

## SEO / AEO / GEO spécifiques

- **Mot-clé principal type** : question complète (« c'est quoi X ? »,
  « X vs Y comparatif 2026 », « meilleur X pour Y »).
- **Schema à générer** : `BlogPosting` + `FAQPage` + `Person` (auteur) via les
  helpers déjà en place : `lv_jsonld_blog_posting()`, `lv_jsonld_faq_page()`,
  `lv_jsonld_breadcrumb()`, `lv_jsonld_author_stephane()`
  (`app/Helpers/jsonld.php`). Le `Person` inclut déjà `jobTitle`, `worksFor`
  et `sameAs` (LinkedIn) - ne pas le régénérer à la main.
- **Sources d'autorité à citer en priorité** : documentation officielle de
  l'éditeur (Anthropic, OpenAI, Google, Mistral...), benchmarks indépendants
  reconnus (ex. leaderboard public, étude publiée), sites gouvernementaux
  (.gouv, quebec.ca) pour tout cadrage légal. Minimum 3 sources externes
  fiables, chaque source = lien cliquable `<a href>` (jamais de texte brut),
  règle absolue du projet (`GUIDE_REDACTION_ARTICLES.md`, section 0).
- **Auteur** : chaque article signé Stéphane Lapointe, fondateur MEMORA
  solutions, avec lien vers `/auteur/stephane-lapointe`. Date de publication
  ET date de mise à jour visibles.

## Exemples de référence (few-shot, extraits réels publiés)

**Ouverture par analogie filée** (article #53, « J'ai créé mon IA en local -
partie 3 ») :

> « Charger un modèle de 32B sur 48 Go de RAM, c'est comme essayer de faire
> entrer un divan trois places dans une Mini Cooper : ça rentre, mais tu ne
> peux plus conduire. »
> *(note : « tu » ici est une erreur d'édition isolée - à corriger en « vous »)*

**Ouverture par analogie du quotidien** (article #1, « C'est quoi le MCP ? ») :

> « Imaginez que vous essayez de brancher votre grille-pain, mais que chaque
> prise de votre maison nécessite un adaptateur différent que vous devez
> fabriquer vous-même. C'est exactement le calvaire que vivaient les
> développeurs d'IA avant l'arrivée du Model Context Protocol. »

**Composant « La réponse » sous le H1** (article #1) :

> « Le MCP est un standard de communication ouvert qui permet aux modèles
> d'IA (comme Claude ou ChatGPT) de se connecter directement à vos outils
> (fichiers, bases de données, courriels). Voyez-le comme une prise
> électrique universelle : peu importe l'IA que vous utilisez, elle peut
> « se brancher » sur vos données sans codage complexe. »

**FAQ, ton direct et image concrète** (article #1) :

> « Zapier est excellent pour des flux de travail fixes (« Si ceci, alors
> cela »). Le MCP est dynamique : l'IA décide elle-même quel outil utiliser
> en fonction de votre question. C'est la différence entre un robot qui suit
> un rail et un assistant qui choisit le bon outil dans sa boîte. »

**Tableau comparatif type** (article #1, structure à réutiliser pour un
comparatif de LLM de codage - remplacer les colonnes/lignes, garder les
classes) :

```html
<div class="tableau-wrapper tableau-responsive">
<div class="tableau-titre">Comprendre les standards de communication IA</div>
<table class="tableau-article tableau-comparaison tableau-hover tableau-zebra">
<thead><tr><th>Critère</th><th>API Traditionnelle</th><th>Protocole MCP</th><th>Protocole A2A</th></tr></thead>
<tbody>
<tr><td><strong>Usage principal</strong></td><td>...</td><td>...</td><td>...</td></tr>
</tbody>
</table>
</div>
```

**Note d'auteur sur les encadrés de vulgarisation** (article #9, à réutiliser
telle quelle en tête d'un article technique dense) :

> « Note de l'auteur : Afin de rendre cet article technique accessible au
> plus grand nombre, j'ai intégré de nombreux encadrés de vulgarisation.
> Cela peut donner l'impression que l'article est très long, mais
> rassurez-vous : il se lit plus vite qu'il n'y paraît ! »

## Encadrés visuels

Le projet possède déjà un système complet de 11 encadrés (`.custom-callout`,
`public/css/components.css`, chargé après `charte.css`) réellement utilisé
dans les articles publiés. **Ne pas en inventer de nouveaux** - documenter et
réutiliser l'existant. 5 types couvrent tous les besoins d'un article
comparatif technique (mapping avec la nomenclature du skill `/article`) ;
contraste vérifié via `mcp__wcag-mcp__wcag_check_contrast` sur la paire
couleur d'en-tête / fond réel (fond = teinte de la couleur à 6 % d'opacité
sur blanc, tel que rendu à l'écran) et sur le texte de corps `#595959` hérité
des paragraphes d'article :

| Rôle (skill) | Classe | En-tête (fg) / fond (bg) rendu | Ratio en-tête | Corps `#595959` sur ce fond | Usage recommandé (comparatif technique) |
|---|---|---|---|---|---|
| Réponse rapide | `callout-conseil` | `#064E5C` / `#F0F7F8` | **8.60:1 AAA** | 6.46:1 (AA) | Recommandation actionable (« pour un budget serré, choisissez X ») |
| Donnée clé | `callout-chiffre` | `#4338CA` / `#F4F4FD` | **7.23:1 AAA** | 6.41:1 (AA) | Prix, score de benchmark, tokens/s, tout chiffre à retenir |
| Citation | `callout-citation` | `#3F4451` / `#F5F5F6` | **8.94:1 AAA** | 6.43:1 (AA) | Citation d'un expert, extrait de documentation officielle |
| Définition | `callout-vulgarisateur` | `#047857` / `#F0F9F6` | 5.11:1 (AA) | 6.53:1 (AA) | Définir un terme technique au premier usage (icône 🧑‍🏫 déjà en usage) |
| Mise en garde | `callout-attention` | `#B91C1C` / `#FDF2F2` | 5.90:1 (AA) | 6.39:1 (AA) | Limite, piège, incompatibilité entre deux outils comparés |

Tous passent AA (≥ 4,5:1, seuil minimal du socle commun). Trois passent AAA
(conseil, chiffre, citation) : à privilégier en premier choix quand le rôle le
permet. `vulgarisateur` et `attention` restent AA seulement (couleurs de
production déjà utilisées sur des dizaines d'articles publiés - non modifiées
ici pour ne pas désynchroniser le rendu réel du site ; à signaler si un futur
audit `/wcag` veut les resserrer à 7:1).

Pour un comparatif avec avantages/inconvénients par outil, les 2 classes
dédiées existent aussi et sont AAA-proches : `callout-avantage` (`#065F46` /
fond vert clair) et `callout-inconvenient` (`#991B1B` / fond rouge clair) -
à utiliser en paire pour les sections « pour / contre » de chaque outil
comparé.

Structure HTML commune (respecter exactement, `callout-icon` + `callout-title`
+ `callout-content`) :

```html
<div class="custom-callout callout-chiffre">
  <div class="callout-header">
    <span class="callout-icon">📊</span>
    <span class="callout-title">Donnée clé</span>
  </div>
  <div class="callout-content"><p>...</p></div>
</div>
```

Autres composants réutilisables du même fichier (`components.css`) :
- **Tableau comparatif** : classes `.tableau-wrapper > .tableau-titre +
  table.tableau-article.tableau-comparaison.tableau-hover.tableau-zebra`. En-tête
  blanc sur `--c-primary` `#064E5A` = **9.35:1 AAA** (vérifié).
- **Citation longue** : `.wp-block-quote` (bordure accent, fond orange très
  pâle) pour une citation d'expert étendue hors callout.
- **Réponse courte sous H1** : composant `.thought-bubble` (badge « La
  réponse ») déjà utilisé en production - réservé au tout premier bloc de
  réponse directe de l'article, pas pour d'autres usages.

## Style photo/illustration

Cohérent avec la charte du site (`public/css/charte.css` `:root`) et la
convention déjà établie pour les visuels IA du projet (miniatures glossaire/
articles via `/nanobanana`, compte Gemini de l'utilisateur, jamais d'API
payante).

- **Palette** : dominante teal `#064E5A` (primaire) avec touches accent
  `#9A2A06` (orange brûlé), sur fond clair `#F8FAFB`/blanc. Pas de bleu vif
  générique hors charte.
- **Style** : illustration 3D isométrique épurée (pas de photo-réalisme, pas
  de stock photo générique de bureau) - objets/scènes tech stylisés (puces,
  graphiques, terminaux, robots discrets) qui évoquent le sujet sans jamais
  intégrer de texte incrusté dans l'image (le texte est ajouté séparément si
  besoin, jamais généré par le modèle d'image).
- **Format** : image de couverture au format paysage standard blog (ratio
  ~16:9), export final en `.jpg`/`.webp` optimisé, cohérent avec les fichiers
  déjà servis (`storage/blog/<slug>.jpg`).
- **Ambiance pour un comparatif technique** : privilégier une composition à
  éléments multiples mis en balance (ex. deux/trois blocs stylisés
  représentant les options comparées, agencés en symétrie ou sur une bascule)
  plutôt qu'un objet unique - la composition doit suggérer visuellement
  « comparaison » avant même de lire le titre.
- **Alt text** : description factuelle en français, incluant si naturel le
  sujet de l'article (ex. « Illustration 3D isométrique comparant plusieurs
  modèles de langage pour la programmation, style teal et orange »), jamais
  de mot-clé bourré artificiellement.
