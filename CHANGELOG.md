# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.66.0] - 2026-07-01

### Added
- **Académie — répétition espacée (SRS) native** : après une leçon complétée, l'apprenant peut réviser de courtes cartes (concepts et mini-quiz) reprogrammées au meilleur moment par l'algorithme SM-2. Un bouton « Réviser » apparaît dans l'espace personnel, une session plein écran présente chaque carte avec auto-évaluation (Facile / Correct / Difficile / À revoir), et une relance quotidienne par courriel invite à réviser (au plus une fois par jour). Fonctionnalité entièrement activable et désactivable (drapeau `ACADEMY_SRS_ENABLED`, désactivée par défaut) : lorsqu'elle est désactivée, aucune carte n'est créée et rien ne s'affiche.

## [1.65.264] - 2026-06-18

### Fixed
- **Annuaire — étiquettes de langue des tutoriels fiabilisées** : la détection privilégie désormais les indices clairement français du titre (la langue audio déclarée par les créateurs étant souvent erronée), et les tutoriels existants ont été reclassés. Les vidéos anglaises ne sont plus marquées « FR ».

## [1.65.263] - 2026-06-18

### Fixed
- **Annuaire — détection de langue des tutoriels** : correction de la cause des tutoriels marqués « FR » mais en anglais. La langue provient maintenant de la vraie langue audio de la vidéo (et non plus du titre, que YouTube traduit parfois), et l'enrichissement « Sonar » ne force plus « FR ». Les nouveaux tutoriels seront correctement étiquetés ; les anciens sont reclassés par un traitement de correction.

## [1.65.262] - 2026-06-18

### Added
- **Constructeur de prompts — bouton « Ouvrir dans Gemini »** (copie le prompt et ouvre Gemini ; Gemini ne permet pas le pré-remplissage par lien, le prompt est donc copié à coller).
- **Constructeur de prompts — bouton « Recommencer »** pour réinitialiser l'outil à zéro (confirmation en deux temps).
- **Encadré « ✦ En bref » — fermé par défaut + mémoire d'état** : l'encadré est replié par défaut et se souvient ensuite de votre choix (ouvert/fermé) au rafraîchissement.

## [1.65.261] - 2026-06-18

### Fixed
- **Constructeur de prompts — menu « Définir la persona » réparé** : correction d'une régression (le menu des personas s'affichait vide) en rendant la lecture des listes robuste, quel que soit leur format de stockage. Les personas (dont les nouveaux) réapparaissent.

## [1.65.260] - 2026-06-18

### Added
- **Constructeur de prompts — plus de choix utiles** : nouveaux formats de sortie (questionnaire/QCM avec corrigé, grille d'évaluation, fiche pratique, gabarit réutilisable, FAQ), tons (neutre et factuel, empathique, motivant) et personas (concepteur pédagogique, gestionnaire de médias sociaux, rédacteur publicitaire, formateur, adjoint administratif), particulièrement utiles pour les enseignants et les PME.

## [1.65.259] - 2026-06-18

### Fixed
- **Constructeur de prompts — « Ouvrir dans » réparé** : les boutons « Ouvrir dans ChatGPT/Claude/Perplexity » transmettent maintenant le prompt (le seuil de longueur était trop bas et le bloquait dans la plupart des cas) ; un message confirme que le prompt est copié.
- **Constructeur de prompts — formulation** : correction du double article (« Tu es un(e) un… ») quand la persona personnalisée commence par un article.
- **Constructeur de prompts — confirmation de copie** : un message « Prompt copié ! » s'affiche clairement au clic.

### Added
- **Encadré « ✦ En bref » repliable** : l'encadré résumé en haut des pages d'outils peut maintenant être replié/déplié (accordéon accessible), tout en restant lisible par les IA.

## [1.65.258] - 2026-06-18

### Added
- **Collection « Top outils IA pour le secteur public »** : une sélection curée de 7 outils (ChatGPT, Claude, Perplexity, NotebookLM, Copilot, Gemini, DeepL), accessible à `/collections/top-outils-ia-secteur-public` et reliée au dossier secteur public.

## [1.65.257] - 2026-06-18

### Added
- **Dossier secteur public — 2 nouveaux guides** : « Rédiger avec l'IA dans le secteur public : bonnes pratiques » et « IA et Loi 25 : protéger les renseignements personnels », reliés à la page pilier et à l'anonymiseur. Le dossier « IA pour le secteur public » devient une véritable grappe de contenu.

## [1.65.256] - 2026-06-18

### Added
- **Dossier « IA pour le secteur public québécois »** : nouvelle page pilier (`/ia-secteur-public-quebec`) qui explique comment les organismes publics et parapublics peuvent utiliser l'IA de façon encadrée (principes du ministère de la Cybersécurité et du Numérique, Loi 25), avec un encadré réponse-rapide, une FAQ et des liens vers l'anonymiseur, l'annuaire et le glossaire. Premier dossier d'une série par métier pour élargir l'audience au-delà des enseignants.

## [1.65.255] - 2026-06-17

### Added
- **llms.txt** : ajout d'un fichier `/llms.txt` qui présente le site et ses pages clés aux IA (ChatGPT, Perplexity, Google AI), pour favoriser des citations exactes vers nos outils et ressources.

## [1.65.254] - 2026-06-17

### Fixed
- **Formulaire de contact — répondre facilement** : le courriel reçu affiche maintenant clairement le nom, l'adresse et le sujet de la personne, avec un rappel que « Répondre » écrit directement au visiteur. L'expéditeur reste l'adresse du site (pour la livraison), mais on voit enfin d'un coup d'œil qui a écrit et on peut lui répondre.

## [1.65.253] - 2026-06-17

### Fixed
- **Formulaire de contact — anti-pourriel** : ajout d'une protection invisible (piège à robots) et d'un filtre qui bloque silencieusement les messages bourrés de liens. Cela met fin aux courriels indésirables reçus via le formulaire de contact, qui semblaient « venir de votre propre adresse » alors qu'il s'agissait du formulaire du site (pas d'un piratage).

## [1.65.252] - 2026-06-17

### Added
- **Outils mieux compris par les IA (GEO/AEO)** : chaque outil interactif publie désormais des données structurées (Schema.org WebApplication) et peut afficher un encadré « réponse rapide » au-dessus du contenu, pour être mieux cité par ChatGPT, Perplexity et les aperçus IA de Google.
- **Constructeur de prompts — ouvrir dans une IA** : nouveaux boutons « Ouvrir dans ChatGPT / Claude / Perplexity » qui copient le prompt et l'ouvrent directement dans l'assistant choisi.
- **Articles — éditeur « réponse rapide »** : le tableau de bord permet maintenant de rédiger un résumé direct et des points clés pour chaque article, pour une meilleure visibilité dans les réponses des IA.
- **Blogue — liens utiles en haut d'article** : un encadré « Pour aller plus loin » oriente vers le constructeur de prompts et des articles reliés, dès le haut de la page (réduit le rebond).

## [1.65.169] - 2026-06-12

### Added
- **Annuaire — alerte qualité des tutoriels** : une vérification automatique quotidienne contrôle que les tutoriels importés sont en français/anglais et pertinents, désapprouve automatiquement ceux qui ne le sont pas, et envoie un courriel récapitulatif d'alerte. Surveillance continue sans intervention.

## [1.65.167] - 2026-06-12

### Fixed
- **Annuaire — enrichissement de tutoriels débloqué** : correction d'un blocage qui faisait re-scanner sans fin les mêmes outils populaires sans tutoriel, empêchant les autres outils d'être traités. De plus, l'enrichissement écarte désormais le contenu sans rapport (jeux, films, clips musicaux) pour éviter les faux tutoriels par homonymie de nom.

## [1.65.165] - 2026-06-12

### Fixed
- **Annuaire — doublons archivés redirigent vers l'outil canonique** : la fiche d'un outil marqué comme doublon (archivé avec remplaçant) redirige désormais en 301 vers l'outil conservé, au lieu d'afficher une page en double. Les autres outils archivés restent consultables comme avant.

## [1.65.164] - 2026-06-12

### Added
- **Annuaire — tutoriels en français/anglais seulement** : l'enrichissement automatique de tutoriels YouTube écarte désormais les vidéos clairement dans une autre langue (titres en arabe, chinois, espagnol, etc.), pour ne garder que des tutoriels pertinents pour l'audience québécoise (FR/EN).

## [1.65.163] - 2026-06-11

### Fixed
- **Raccourcisseur — boutons de copie des adresses jumelles** : au clic, le bouton affiche maintenant « ✅ Copié ! » (en plus du changement de couleur), comme le bouton de copie standard.

## [1.65.162] - 2026-06-11

### Added
- **Raccourcisseur — adresses jumelles copiables** : quand l'entrée « 1lien.ca / unlien.ca » est choisie dans le sélecteur, un message rappelle que les deux adresses mènent au même endroit. Une fois le lien créé, chaque adresse (1lien.ca et unlien.ca) a son propre bouton de copie, pour partager celle qu'on préfère. Comportement inchangé pour les autres domaines.

## [1.65.161] - 2026-06-11

### Changed
- **Raccourcisseur — 1lien.ca et unlien.ca regroupés** : dans le sélecteur de domaine, les deux adresses jumelles « un lien » apparaissent comme une seule entrée « 1lien.ca / unlien.ca » ; les autres adresses (veille.la, go3.ca, lurl.ca) restent distinctes. Le lien créé via cette entrée utilise 1lien.ca (joignable partout), tandis qu'unlien.ca continue de rediriger normalement. Mise en place propre via deux champs en base (libellé d'affichage et masquage du menu), sans toucher à la résolution des liens.

## [1.65.160] - 2026-06-11

### Changed
- **Raccourcisseur — sélecteur de domaine plus distinct** : le bloc de choix d'adresse (membre) est désormais présenté dans un panneau au fond foncé (couleur du thème) avec le contenu en blanc, pour bien le démarquer du reste du formulaire. Champs (domaine + slug) en blanc, badge du nombre d'adresses et note « toutes ces adresses mènent au même lien » adaptés au fond foncé. Aucun changement de logique.

## [1.65.159] - 2026-06-11

### Added
- **Raccourcisseur — note « adresses jumelles » dynamique** : dans le créateur de liens, dès qu'un domaine est choisi dans le sélecteur, un message data-driven nomme les autres adresses actives et rappelle qu'elles mènent toutes au même lien court (la résolution se fait par slug global, donc un lien créé sur une adresse fonctionne sur toutes). Aucun nom de domaine codé en dur : la liste vient des domaines actifs ; toute nouvelle adresse (ex. unlien.ca) y apparaîtra automatiquement. Remplace l'ancienne note fixe (plus clair, se met à jour selon le domaine sélectionné).

## [1.65.158] - 2026-06-11

### Changed
- **Conditions d'utilisation — raccourcisseur** : renforcement de la clause de non-responsabilité (section 7). Trois ajouts conformes au droit québécois : statut d'intermédiaire technique (LCCJTI art. 22), responsabilité exclusive de l'utilisateur qui crée le lien quant au contenu de destination, et garantie/indemnisation de laveille.ai et MEMORA solutions par l'utilisateur. À faire valider par un juriste.

## [1.65.157] - 2026-06-11

### Added
- **Raccourcisseur — choix du domaine plus évident** : quand plusieurs adresses sont disponibles, le créateur de liens affiche clairement un sélecteur (« Choisis ton adresse » + nombre d'adresses disponibles) et une note rassurante « Adresse différente, même destination : toutes ces adresses mènent au même lien court ».

## [1.65.156] - 2026-06-11

### Fixed
- **Liens en milieu de phrase** : quand une URL est introduite par un mot de liaison (« Accessible via https://…, il repose »), le retrait du lien ne laisse plus de mot orphelin — la phrase devient « Accessible, il repose ». Les tournures sans lien (« via une API », « sur le marché ») restent intactes.

## [1.65.155] - 2026-06-11

### Added
- **Post social de l'annuaire — nombre de tutoriels** : le post d'un outil affiche désormais une ligne de preuve sociale dynamique « 🎓 {N} tutoriels pour bien démarrer t'attendent déjà sur la veille » (accord singulier/pluriel), uniquement si l'outil a au moins un tutoriel, sans lien. Le compte suit exactement celui de la fiche /annuaire.

## [1.65.154] - 2026-06-11

### Fixed
- **Post social des actualités — moins de redondance** : le « 👉 » (point clé) ne répète plus le « En clair » (résumé). Le post choisit automatiquement un point clé, une citation ou un « pourquoi c'est important » réellement distinct du résumé (sinon il est omis).

## [1.65.153] - 2026-06-11

### Fixed
- **Typographie française dans les contenus de partage** : l'espace avant `: ; ! ?` est préservée (seuls les espaces parasites avant `. , …` sont retirés).

## [1.65.152] - 2026-06-11

### Fixed
- **Liens entre parenthèses** : le retrait d'une URL ne laisse plus de parenthèse ouvrante orpheline (« Nom ( est… »).

## [1.65.151] - 2026-06-11

### Fixed
- **Nettoyage des liens dans les contenus de partage** : après le retrait d'une URL entre parenthèses, on supprime la parenthèse vide laissée (« Nom ( est… » → « Nom est… »), on réduit les espaces multiples et on recolle la ponctuation isolée. S'applique à tous les posts sociaux et résumés NotebookLM.

## [1.65.150] - 2026-06-11

### Changed
- **Post réseaux sociaux du bouton Admin — format « 2026 » partout** : le glossaire, l'annuaire, le blog et les actualités utilisent désormais le même format engageant que les acronymes (accroche curiosity-gap + « En clair : » + « 👉 » + appel à commenter + hashtags), **sans lien ni signature promotionnelle**, avec une accroche adaptée à chaque type. Réutilise `buildEngagingSocialPost()` + `smartTrim()` (zéro duplication). L'ancienne signature « Plus de contenu IA… sur LaVeille AI » est retirée de ces posts.

## [1.65.149] - 2026-06-11

### Fixed
- **Post social — troncature propre** : les blocs « En clair : » et « 👉 » sont coupés à la fin d'une phrase complète (sinon au dernier mot + « … ») au lieu d'être tronqués en plein milieu d'un mot.

## [1.65.148] - 2026-06-11

### Changed
- **Post réseaux sociaux du bouton Admin (acronymes) — refonte « 2026 »** : le post copié est désormais plus riche et attirant, selon les meilleures pratiques de juin 2026 (recherche Perplexity). Format : accroche qui ouvre une boucle de curiosité + « En clair : » (définition sans jargon) + « 👉 » (fait à retenir) + un appel à commenter (CTA conversationnel) + hashtags. **Aucun lien, aucune signature promotionnelle.** Nouvelle méthode réutilisable `buildEngagingSocialPost()` (les autres sections gardent leur format actuel pour l'instant).

## [1.65.147] - 2026-06-11

### Changed
- **Acronymes — liste cohérente avec la fiche** : les cartes de la liste `/acronymes-education` affichent l'icône emoji de catégorie dans leur vignette (au lieu du favicon), pour un rendu net et cohérent avec la fiche.

## [1.65.146] - 2026-06-11

### Fixed
- **Acronymes — fin des logos déformés sur la fiche** : les fichiers de logos sont des canevas carrés 64×64 où les logos rectangulaires (wordmarks) avaient été écrasés (déformation dans le fichier, incorrigeable en CSS) et tous pixelisés à l'affichage. Le re-téléchargement depuis les sites officiels s'est révélé non fiable (og:image = photos/bannières, favicons 32×32 ou 404). La fiche affiche désormais l'**icône emoji de catégorie** (vectorielle, nette, cohérente, zéro déformation). `logo_url` est conservé en base (réversible).

## [1.65.145] - 2026-06-11

### Fixed
- **Acronymes — hauteur du logo portée à 90 px** : le logo de la fiche ne se rendait qu'à ~76 px (le padding interne rognait la hauteur). L'image porte maintenant `height: 90 px` avec `object-fit: contain`, ce qui garde la hauteur de mise en forme et garantit l'absence de déformation, y compris pour un logo très large.

## [1.65.144] - 2026-06-11

### Changed
- **Acronymes — bouton « Admin » (NotebookLM) remonté en haut de la fiche** : les 3 copies superadmin (Résumé NotebookLM, NotebookLM Infographie, Post réseaux sociaux) sont désormais dans la barre d'action en haut, juste après l'en-tête — comme sur le glossaire et les actualités (auparavant en bas de page, donc peu visible). Zéro duplication, le partage social reste en bas.

### Fixed
- **Acronymes — logos non déformés** : la boîte de logo de la fiche n'est plus un carré figé 90×90 (qui écrasait les logos rectangulaires). Le logo respecte maintenant son ratio natif (largeur auto) avec une hauteur fixe de 90 px et une largeur max de 240 px, conservant la mise en forme. La vignette circulaire de la liste/index (44×44, `object-fit:contain`) est inchangée.

## [1.65.143] - 2026-06-10

### Added
- **Acronymes — icônes emoji par catégorie** : chaque acronyme publié (312) reçoit l'emoji de sa catégorie (🏛️ ministères et organismes gouvernementaux, 🤝 associations et organismes professionnels, 🔧 formation professionnelle et technique, 🎓 formation générale et diplômes, 💻 technologies éducatives et numérique, 🧩 services aux élèves et adaptation, 🏫 centres de services scolaires, 📋 gestion et administration scolaire). Affiché dans l'en-tête de la fiche et sur les chips. Donnée seulement (la vue v1.65.142 lisait déjà `icon`).
- **Acronymes — maillage broader/narrower (graphe de connaissances)** : ~82 relations hiérarchiques parent→enfant générées par IA (OpenRouter qwen3-max), **intra-catégorie**, avec garde-fou anti-hallucination (validation serveur des slugs contre la liste réelle + symétrisation broader↔narrower + `temperature` 0.1). 105 acronymes maillés (77 « Catégorie parente », 34 « Sous-acronymes »). Les associations professionnelles (catégorie sans hiérarchie) restent volontairement sans maillage. Affiché en chips « Acronymes liés » (la vue v1.65.142 lisait déjà `broader_slugs`/`narrower_slugs`).

### Notes
- Aucun code applicatif modifié (enrichissement de **données** uniquement) ; aucun cron ; backups conservés (`storage/app/backup-acronyms-icons`, `storage/app/backup-acronyms-mesh`). Rollback : remettre `icon`/`broader_slugs`/`narrower_slugs` à `NULL` (la migration #304 peut aussi `down()` ces colonnes).

## [1.65.136] - 2026-06-10

### Added
- **Menu de partage admin étendu au glossaire, à l'annuaire et au blog** (superadmin only), avec **contenu adapté par type** pour maximiser les vues réseaux sociaux (veille juin 2026) : glossaire = explainer éducatif, annuaire = revue par cas d'usage, blog = teaser insight. Chaque type expose les 3 copies (Résumé NotebookLM, NotebookLM Infographie, Post réseaux sociaux).
- **Trait partagé `Modules\Core\Concerns\HasAdminShareContents`** (zéro-duplication) : `infographiePrompt()`, `buildSocialPost()`, `stripLinks()`, `normalizeShareHashtag()`. Utilisé par `Term`, `Tool`, `Article` et **`NewsArticle` (refactorisé)**. Branché via `$adminShareItems` dans les 3 vues `show` (le composant `<x-core::admin-copy-menu>` est réutilisé tel quel).

## [1.65.133] - 2026-06-09

### Added
- **News — bouton « Admin » superadmin sur la page actualité** (barre de partage), ouvrant un menu de 3 actions de copie : (1) **Résumé pour NotebookLM** (`structured_summary` → Markdown avec titres de section, sans liens), (2) **Prompt NotebookLM** (consignes infographie fixes), (3) **Post réseaux sociaux** natif optimisé 2026 (hook + 3 points + CTA-question + hashtags ciblés, ton québécois, sans lien externe). Visible uniquement si `auth()->user()?->isSuperAdmin()`.
- **Composant générique réutilisable `<x-core::admin-copy-menu>`** (`Modules/Core/.../components/admin-copy-menu.blade.php`) : bouton + menu Alpine + copie presse-papier multi-lignes (textarea ref + fallback `execCommand`), CSS `@once`. Zéro logique métier → réemployable sur d'autres sections. La génération du contenu vit dans `NewsArticle::adminShareContents()` (séparation UI / contenu, zéro duplication).

## [1.65.132] - 2026-06-09

### Added
- **SEO/AEO — `llms.txt` + `llms-full.txt` générés dynamiquement** (audit utilisateur : fichiers statiques périmés, chiffres contradictoires, `llms-full` faux « full » sans accents, contradiction training). Nouveau `App\Http\Controllers\LlmsController` (routes racine `/llms.txt` + `/llms-full.txt`, `Cache::remember` 1h) avec **compteurs en temps réel** (outils/termes/articles/acronymes/actualités publiés). `/llms.txt` = index AEO (pitch chiffré, sections, expertise, politique IA, ressources machines, date Québec). `/llms-full.txt` = **vrai dump** (glossaire complet + outils + articles + acronymes + 100 actualités récentes, Markdown, accents fr-CA). Politique tranchée : **entraînement ET citation autorisés** (aligné `robots.txt`). Modules désactivables gérés (`class_exists` + try/catch).

### Removed
- Fichiers statiques `public/llms.txt` et `public/llms-full.txt` (périmés, remplacés par la génération dynamique). Backup : `.rapports/llms-backup-2026-06-09/` + historique git.

## [1.65.131] - 2026-06-09

### Fixed
- **News — logo œil pixelisé dans le visuel auto** (signalé par l'utilisateur). Le logo `logo-eye-white.svg` (viewBox 52×52) était lu par Imagick à sa taille native (~52 px) puis agrandi à 200 px (`resizeImage`, ×3,8 upscale) → bords pixelisés. Correction : `$logo->setResolution(1200, 1200)` **avant** `readImage()` → le SVG est rasterisé à ~870 px puis réduit à 200 px (Lanczos) = rendu net.

## [1.65.130] - 2026-06-09

### Fixed
- **News — centrage du texte dans le badge « pill » de catégorie** (signalé par l'utilisateur : le texte débordait par le haut du badge, surtout avec les accents majuscules É/Ô). Cause : la formule de baseline avait le signe inversé (`500 - (asc+desc)/2`) → texte ~17 px trop haut. Correction : `$baseline = $pillCenterY + ($asc - $desc)/2` (valeurs absolues des métriques, robuste quel que soit le signe renvoyé par Imagick) → le centre du glyphe tombe exactement sur le centre du pill. La hauteur du pill passe à `(asc+desc)+26` (marge verticale pour les accents montants) et le rayon des coins à 16.

## [1.65.129] - 2026-06-09

### Changed
- **News — palettes du visuel auto alignées sur les VRAIES catégories** : relevé des 18 tags réels en base (« IA générative » 3333, « Autre » 2956, « Cybersécurité » 888, « Infrastructure » 824, « Robotique », « Startup », « Cloud », « Données », « Éducation tech »…). Les anciennes clés de palette (`ia`, `securite`…) ne correspondaient à quasi aucun tag réel → la couleur tombait presque toujours sur le repli déterministe `id % 10`. Désormais la table `$palettes` est ré-indexée sur les tags normalisés (IA générative = teal signature, Cybersécurité = rouge, Données = vert, Cloud = bleu ciel, Éducation tech = indigo, Énergie renouvelable = vert nature…), et la normalisation `$catKey` translittère correctement les accents (`mb_strtolower` + `strtr` : « Cybersécurité » → `cybersecurite`). Le pill affiche le tag réel accentué en majuscules. La couleur du visuel est maintenant **sémantiquement liée** à la catégorie de l'article.

## [1.65.128] - 2026-06-09

### Changed
- **News — affinage du visuel « réseau de neurones » suite validation visuelle** (agent Playwright sur 6 témoins → 6,5/10, 3 défauts corrigés) : (1) **bloquant** — un nœud chevauchait « laveille.ai » → les nœuds sont désormais cantonnés aux **marges latérales** (index pair = gauche x[20,380], impair = droite x[820,1180]) avec y borné à [20,470] (épargne la bande du titre ET le footer) ; (2) **asymétrie** (motif massé dans un coin) → l'alternance gauche/droite garantit l'équilibre (2 grappes propres, arêtes < 300 px) ; (3) **gros nœuds** bornés à un rayon 9–11 (n'éclipsent plus le logo). Le label de catégorie devient un **badge « pill »** (roundRectangle couleur d'accent à 85 % + texte en majuscules blanc centré via `queryFontMetrics`) au lieu du texte gris brut. Imagick pur, déterministe.

## [1.65.127] - 2026-06-09

### Added
- **News — visuel auto « réseau de neurones » génératif (design choisi par l'utilisateur, veille pp_search juin 2026, 91/100)** : `NewsImageService::generateFallbackImage` superpose désormais `drawNeuralPattern()` sur le dégradé de marque — un motif déterministe **nœuds + arêtes unique par titre** (PRNG LCG seedé sur `crc32($title)` → même titre = même motif). Arêtes blanches 10 % entre nœuds proches (< 320 px), nœuds à 22 % d'opacité (3 « gros » à 16 % avec anneau-halo), 1 nœud sur 4 en couleur d'accent de la palette de catégorie. La bande centrale du titre (y 250–560) est préservée (nœuds repoussés vers le haut). Thématiquement IA, subtil, lisible, Imagick pur (≤ ~30 primitives, ~0,2 s), **zéro dépendance externe, zéro droit d'auteur**. Sert au robot (nouveaux articles) ET au rattrapage de masse des anciennes images. Code délégué à Hermes (qwen3-max), intégré + affiné (contour des disques neutralisé, halo des gros nœuds à rayon+6).

## [1.65.125] - 2026-06-09

### Fixed
- Actualités / **droits d'auteur** — le robot d'agrégation **ne télécharge/ré-héberge plus aucune image de source** (photos de presse). À la place, il génère une **image de marque libre de droits** (fond La veille + titre de l'article). Stoppe la récidive des réclamations type PicRights/Reuters. Couvre tous les chemins (fetch, rescrape, reprocess). Réversible (le code de téléchargement est conservé mais neutralisé). L'article litigieux a par ailleurs été corrigé (photo remplacée par une image libre + crédit retiré).

## [1.65.124] - 2026-06-09

### Added
- Newsletter — **override HTML par édition** (`content.custom_html`). Une édition peut désormais figer un **HTML validé** envoyé tel quel aux abonnés (et au test), sans régénération par le gabarit. Le lien de désabonnement reste personnalisé par abonné. Sans `custom_html`, le comportement est strictement inchangé. Permet d'envoyer exactement l'aperçu approuvé.

## [1.65.123] - 2026-06-09

### Fixed
- Anonymiseur (moteur) — **qualité d'anonymisation** : trois défauts repérés par la simulation E2E sont corrigés. (1) **Anti-collision** : un faux nom ne peut plus réutiliser un vrai nom présent ailleurs dans le texte (qui créait une ambiguïté). (2) **Aucune fuite du vrai nom dans le faux courriel** : la partie locale d'un faux courriel ne laisse plus passer un vrai nom de famille, même abrégé ou accentué (ex. « Côté-Pelletier » → « cote »), et même en mode jetons. (3) **Prénom isolé** : un prénom employé seul (« Geneviève » après « Geneviève Côté-Pelletier ») est maintenant masqué dans les deux modes. (4) **Cohérence** : le faux courriel correspond toujours au faux nom complet affiché. Validé par banc d'essai (17/17 + 6/6 non-régression, restauration 100 % préservée). Réversible.

## [1.65.122] - 2026-06-09

### Changed
- Anonymiseur — **accordéon de confidentialité « Je comprends »**. Le bloc « 🛡️ 100 % local » (rappel Loi 25 / RGPD, texte inchangé) s'affiche maintenant **ouvert au premier affichage**. Un bouton **« ✓ Je comprends »** à l'intérieur le **ferme et mémorise le choix** dans le navigateur (`localStorage`) : il **reste fermé** lors des visites suivantes, mais l'utilisateur peut le **rouvrir/refermer à volonté** via son en-tête. Un script inline (anti-flash) applique l'état mémorisé avant l'affichage, sans clignotement. Le composant générique `<x-core::accordion>` n'est pas modifié ; seule la page de l'anonymiseur l'est. Accessible (aria-expanded, clavier, focus visible). Réversible.

## [1.65.121] - 2026-06-08

### Added
- Glossaire — **nouveau terme « Bluetooth »**, catégorie « Concepts fondamentaux ». Fiche complète au gabarit standard (définition d'environ 270 mots, analogie, exemple, « le saviez-vous » [le nom vient du roi viking Harald Blåtand et le logo combine ses initiales runiques], réponse en une phrase, FAQ FAQPage, 2 sources Wikipédia vérifiées). Dérivés en `aliases` pour l'auto-liaison : Bluetooth Low Energy, BLE, Bluetooth LE. Image hero générée sur le compte Gemini de l'utilisateur (3D isométrique teal/orange, sans texte), fournie en `bluetooth.jpg` (og:image — réseaux sociaux refusent WebP/AVIF) + `bluetooth.webp`, 1200×669 compressées, nom de fichier = slug. Migration réversible.
- Glossaire — **nouveau terme « PowerShell »**, catégorie « Outils ». Fiche complète au gabarit standard (définition d'environ 285 mots, analogie, exemple de pipeline `Get-Process | …`, « le saviez-vous » sur le pipeline d'objets .NET, réponse en une phrase, FAQ FAQPage, 2 sources vérifiées : Wikipédia + Microsoft Learn). Dérivés en `aliases` : pwsh, PowerShell Core, PowerShell 7, Windows PowerShell. Image hero générée sur le compte Gemini de l'utilisateur (console isométrique teal/orange, sans texte lisible), fournie en `powershell.jpg` (og:image) + `powershell.webp`, 1200×669 compressées, nom de fichier = slug. Migration réversible.

## [1.65.120] - 2026-06-08

### Added
- Glossaire — **nouveau terme « Firmware » (micrologiciel)**, catégorie « Concepts fondamentaux ». Fiche complète au même gabarit que les autres termes (définition d'environ 290 mots, analogie, exemple concret, « le saviez-vous » [le mot a été forgé par Ascher Opler en 1967 dans Datamation], réponse en une phrase, FAQ avec balisage FAQPage, 2 sources Wikipédia vérifiées). Les dérivés et synonymes français (micrologiciel, microprogramme, firmwares) sont gérés en `aliases` pour l'auto-liaison automatique dans les articles. Image hero générée sur le compte Gemini de l'utilisateur (illustration 3D isométrique teal/orange, sans texte) et fournie en deux formats : `firmware.jpg` (og:image — les réseaux sociaux refusent WebP/AVIF) et `firmware.webp` (affichage), en 1200×669 compressées, nom de fichier = slug pour le référencement. Insertion via migration réversible.

## [1.65.119] - 2026-06-08

### Fixed
- Sudoku — **message « non classé » honnête** (défaut trouvé en testant une partie Diabolique complète). La modale de victoire affichait **toujours** « non classé : temps trop court » dès qu'un score n'était pas publié, alors que la publication au classement exige **deux** conditions : temps ≥ minimum **ET** utilisateur **connecté**. Un joueur **anonyme** avec un bon temps voyait donc un message **faux** (« temps trop court » alors que son temps était suffisant). Correctif : l'API renvoie désormais `publish_reason` (`published` / `anonymous` / `too_fast`) et `min_time` ; la modale affiche le bon message — connecté mais trop rapide → « Non classé : temps trop court (minimum X s) » ; anonyme → « Connectez-vous pour apparaître au classement » ; publié → « Rang du jour : N ». (Le reste du test Diabolique complet est PASS : 24 indices de départ, saisie clavier, notes, erreur+correction, indice, pause, auto-détection de victoire, soumission.)

## [1.65.118] - 2026-06-08

### Added
- Sudoku — **avertissement de persistance locale + indicateur de grille terminée** (demande utilisateur : « le dernier sudoku reste dans le navigateur… ajouter un avertissement »). (1) Note permanente dans le panneau latéral : « Votre partie est enregistrée sur cet appareil et restaurée si vous rechargez la page (rien n'est envoyé au serveur tant que vous ne soumettez pas un score) ; elle disparaît si vous changez d'appareil/navigateur ou videz les données du site. » (2) Bandeau (visible quand la grille est terminée, y compris après rechargement d'une grille finie) : « ✅ Grille terminée. Cliquez « Nouvelle grille » pour rejouer. » — clarifie pourquoi la grille est verrouillée.

## [1.65.117] - 2026-06-08

### Fixed
- Sudoku — **vraie cause du titre « Bravo ! » illisible** : le titre s'affichait en **foncé** (`#1A1D23`) sur le fond teal foncé, et non en blanc. Cause = le passage du titre de `<h5>` à `<h2>` (v1.65.112) : la règle globale `h2 { color: #1A1D23 }` l'emportait sur la couleur `#fff` héritée de l'en-tête. Correctif : `color:#fff` explicite sur le `<h2>` du titre (l'inline bat la règle globale). Désormais blanc sur `#064E5A` = **9.35:1** (AAA). Complète le dégradé AAA de la v1.65.116.

## [1.65.116] - 2026-06-08

### Fixed
- Sudoku — **modale de victoire** (retours utilisateur). **(1) Contraste WCAG 2.2 AAA du titre « Bravo ! »** : l'en-tête utilisait un dégradé `#0B7285 → #053d4a` ; le blanc sur `#0B7285` (extrémité claire) ne donnait que **5.58:1** (AA, mais pas AAA). Nouveau dégradé `#064E5A → #053d4a` → blanc = **9.35:1** et **11.85:1** (≥ 7:1, AAA, vérifié). **(2) Pseudo prérempli avec le nom du compte si connecté** : le composant reçoit le nom de l'utilisateur authentifié (`auth()->user()->name`) ; à l'ouverture, le champ « Pseudo (pour le classement) » est prérempli avec ce nom. Hors connexion, comportement inchangé (dernier pseudo en localStorage).

## [1.65.115] - 2026-06-08

### Fixed
- Sudoku — **auto-détection de fin de grille** (retour utilisateur : « quand j'ai terminé, pas de félicitation ? pas d'envoi au classement ? »). `verifyComplete()` n'était déclenché **que** par le bouton « Vérifier la grille » : un joueur qui remplissait sa grille sans cliquer ce bouton ne voyait jamais la modale de félicitations ni le classement. Nouvelle méthode `checkCompletion()` (si la grille est pleine → `verifyComplete` = félicitations + soumission au classement) appelée **après chaque saisie** (`inputValue`) **et chaque indice** (`useHint`). Grille pleine et valide → modale « Bravo ! » automatique ; pleine mais avec une erreur → message d'erreur ciblé (comportement inchangé). Le bouton « Vérifier la grille » reste disponible.

## [1.65.114] - 2026-06-08

### Fixed
- Sudoku — **2 bugs du mode notes** (retours utilisateur). **(1) Le crayon rouge cachait le chiffre** : l'icône ✎ (pseudo-élément `::after` au coin haut-droit de la case sélectionnée en mode notes) recouvrait la note affichée à cette position — la note « 3 » s'affiche justement en haut-droite de la mini-grille 3×3. C'est aussi ce qui donnait l'impression que « la note n'apparaît pas, mais est là après avoir changé de case » (le crayon suit la case sélectionnée). Vérifié : la note **s'affiche bien immédiatement** (la réactivité fonctionne — ce n'était pas un bug de rendu). Correctif : l'icône ✎ est **retirée** ; le mode notes reste clairement signalé par le contour + le fond rouges de la case, le pavé numérique rouge et le bouton « Notes » enfoncé. **(2) Le bouton « Notes » volait le focus** : après avoir cliqué « Notes », il fallait recliquer la case pour que le clavier fonctionne, car le clic plaçait le focus sur le bouton (hors de la grille) → la frappe n'atteignait plus la grille. Correctif : `toggleNotesMode()` bascule le mode notes **puis redonne le focus** à la case sélectionnée (helper `focusCell` partagé avec `selectCell`).

## [1.65.113] - 2026-06-08

### Fixed
- Sudoku — **saisie au clavier fiable dans les cases** (demande utilisateur : « pourquoi je ne peux pas utiliser mon clavier en plus des numéros en bas ? »). Le clavier ne fonctionnait que si la cellule **exacte** avait le focus DOM (le gestionnaire `handleKey` était attaché `@keydown` sur chaque cellule), or sélectionner une case ne déplaçait pas le focus → dès qu'on cliquait une case-indice, le pavé, ou ailleurs, la frappe ne faisait rien. Refonte selon la meilleure pratique de juin 2026 (widget composite, source de vérité unique `selectedCell`, périmètre = la grille, **pas** de gestionnaire global `window`) : (1) un **seul** gestionnaire `@keydown` au niveau du **conteneur de la grille** (rendu focusable, `tabindex=0`) qui route les touches vers la cellule sélectionnée ; (2) `selectCell` **synchronise désormais le focus DOM** sur la cellule sélectionnée (au clic **et** aux flèches) ; (3) retrait du `@keydown` par cellule (anti double-traitement). Chiffres 1-9 = saisie, Backspace/Suppr/0 = effacer, flèches = déplacer. **Notes** : via le bouton « Notes » existant (la saisie respecte le mode notes) + raccourci Maj+chiffre conservé. Pavé numérique du bas inchangé.

## [1.65.112] - 2026-06-08

### Fixed
- Sudoku — **accessibilité WCAG 1.3.1 (ordre des titres)** : les titres de **dialogue** créaient des sauts de niveau (overlay « Partie en pause » `<h3>` après le `<h1>` ; modale de victoire `<h5>` après le `<h3>`). Tous les titres de dialogue (pause, victoire, changement de niveau, nouvelle grille) sont passés à `<h2>`, avec la **taille visuelle préservée** via les classes utilitaires Bootstrap `.h3`/`.h5`. La hiérarchie de la page est désormais `<h1>` « Sudoku quotidien » puis uniquement des `<h2>` → plus aucun saut. L'`id="winModalLabel"` est conservé (`aria-labelledby` intact).

## [1.65.111] - 2026-06-08

### Fixed
- Sudoku — **accessibilité WCAG 4.1.2** (suite v1.65.110). Le retrait de `role="gridcell"` avait laissé un `aria-label` sur des `<div>` sans rôle valide (invalide : « aria-label cannot be used on a div with no valid role »). Correctif : seules les **cases éditables** reçoivent `role="button"` (rôle valide pour `aria-label`, aucun parent ARIA requis, et elles sont réellement activables) + `tabindex=0` + `aria-label` ; les **cases-indices** (données fixes) deviennent du texte simple (sans rôle/aria-label/focus). Audit WCAG : `1.3.1` (grid/tablist) **et** `4.1.2` résolus ; layout 3×3 et ordre vertical intacts ; ne restent que les faux positifs documentés (blanc/blanc dû à l'en-tête foncé mal lu par le scanner, skip-link 1×1 site-wide, modale infolettre masquée).

## [1.65.110] - 2026-06-08

### Fixed
- Sudoku — **accessibilité WCAG 1.3.1 (structure ARIA)**, reco P2 issue du bilan de simulation. (1) **Grille** : `role="grid"` → `role="group"` et `role="gridcell"` retiré des cellules. Un `role="grid"` impose un maillage strict `grid > row > gridcell` ; sans conteneur `role="row"` intermédiaire, l'audit signalait « grid must contain row » + « gridcell must be contained by row ». La solution `display:contents` sur un `role="row"` n'étant **pas fiable cross-navigateur en 2026** (recherche), on retire la promesse ARIA invalide ; l'information de position reste portée par l'`aria-label` de chaque cellule (« Ligne X, colonne Y, vide/valeur N ») et la navigation aux flèches déjà fonctionnelle. **Zéro changement de CSS/layout** (blocs 3×3 et ordre vertical intacts). (2) **Navigation du haut** : `role="tablist"` + `role="presentation"` retirés (ce sont des **liens** entre pages — Jouer/Classements/Mes parties — pas un widget d'onglets) + `aria-current="page"` sur le lien actif. (3) **Pills de difficulté** : `role="tablist"` → `role="group"` (boutons bascule `aria-pressed` à tabulation indépendante, pas des onglets). Amélioration future possible : grille en `<table>` natif + roving tabindex.

## [1.65.109] - 2026-06-08

### Changed
- Sudoku — endpoint indice : limite de débit **60 → 120 requêtes/min**. En vérifiant le correctif v1.65.108 dans le navigateur (remplir toute la grille uniquement avec « Indice »), le throttle de 60/min introduit en v108 pouvait s'épuiser sur une partie résolue surtout par indices (Diabolique ≈ 57 cases vides). 120/min reste anti-abus (la solution n'est jamais exposée, une seule case par appel, pénalité de temps par indice) sans jamais bloquer un joueur légitime. Vérification du correctif v108 : Facile = 41 indices sur 41 trous → grille **complète, 0 conflit, 0 erreur** (chaque indice pose la bonne valeur).

## [1.65.108] - 2026-06-08

### Fixed
- Sudoku — **bouton « Indice » pouvait remplir une mauvaise valeur** (bug trouvé pendant la simulation E2E complète des 5 niveaux). `useHint()` devinait côté client la première valeur **sans conflit** au lieu d'utiliser la vraie solution (jamais envoyée au navigateur pour empêcher la triche) → sur certaines cases à plusieurs candidats, l'indice posait un chiffre faux, puis générait des erreurs. Correctif : nouvel endpoint serveur `POST /api/sudoku/hint/{puzzle_id}` (corps `{row, col}`, throttle 60/min) qui révèle **une seule** case « trou » depuis `SudokuPuzzle::solution` (refuse une case-indice ou une valeur invalide → 422) ; `useHint()` devient asynchrone et appelle cet endpoint (jeton CSRF, message de repli si indisponible). **Anti-triche préservé** : la solution complète ne quitte jamais le serveur, une seule case par appel, compteur d'indices et pénalité de temps inchangés. Reproduit sur Facile/Difficile avant le correctif, indice correct après.

## [1.65.107] - 2026-06-08

### Fixed
- Sudoku — **VRAI « problème de cases » corrigé : les blocs 3×3 affichaient des bandes 4/3/2 au lieu de 3/3/3**. Diagnostic Playwright : la grille `display:grid` était rendue **verticalement inversée** (data-row 0 en bas, data-row 8 en haut) ; les bordures de blocs (correctement sur data-row 2 et 5) tombaient alors après les 4e et 7e rangées visuelles → grandes cases de 4, 3 puis 2 petites cases. Correctif robuste indépendant de la cause : **placement explicite** de chaque cellule via `grid-row`/`grid-column` (data-row 0 → rangée 1 = haut). Vérifié : data-row 0 en haut, 8 en bas, blocs parfaitement découpés en 3×3 (3/3/3). (Les diagnostics précédents — densité de givens v1.65.105, sauvegarde locale v1.65.106 — étaient des améliorations valides mais à côté du vrai défaut structurel.)

## [1.65.106] - 2026-06-08

### Fixed
- Sudoku — **la sauvegarde locale obsolète masquait un puzzle régénéré** (« rien n'a changé » côté joueur). La grille de jeu est sauvée en localStorage sous `sudoku_state_<puzzle_id>` ; quand un puzzle est régénéré côté serveur en gardant le même id, l'ancienne grille était restaurée, écrasant la nouvelle. Correctif : `saveLocalState()` enregistre désormais une **signature des givens** (`init`), et `restoreLocalState()` **invalide la sauvegarde** si la grille initiale serveur diffère (helper `givensMatch()`, avec repli de validation cellule par cellule pour les anciennes sauvegardes). Un puzzle régénéré force ainsi un repartir propre depuis le serveur. (Le service worker `sw.js` est déjà en mode cleanup ; non impliqué.)

## [1.65.105] - 2026-06-08

### Fixed
- Sudoku — **les niveaux déterminent désormais un nombre de chiffres donnés (givens) DISTINCT et croissant** (« problème de cases » signalé). Avant : le retrait glouton en une seule passe se bloquait vers ~24 indices, donc Difficile/Expert/Diabolique étaient quasi identiques (24-25 indices) et `clues_count` stockait la cible et non le réel. Maintenant : nouveau `digHoles()` en **retrait multi-passes** (avec garantie d'unicité conservée) atteignant des cibles distinctes — **Facile 40 · Moyen 34 · Difficile 30 · Expert 26 · Diabolique ~22-24** — et stockage du **compte réel** d'indices. Garde-fou temps (budget 12 s) contre les pics de génération sur grilles très creuses. Cibles fondées sur les best practices juin 2026 (fourchettes NYT/Conceptis/Sudoku Coach). Aucune donnée touchée (les puzzles existants conservent scores/parties ; le nouveau barème s'applique aux puzzles à venir). Amélioration recommandée ensuite : classement par technique de résolution (gold standard).

## [1.65.104] - 2026-06-08

### Fixed
- Glossaire — **arbitrage des 4 paires limites** (décision éditoriale finale). Après lecture du contenu réel : 3 paires sont des **concepts hiérarchiques distincts** (pas des synonymes) et sont **conservées séparées** — embeddings/vectorisation, ia-multimodale/modele-multimodal, llm/modele-de-langage (ex. : un LLM est un *type* de modèle de langage). Seule l'entrée **« spoiler »** — mal nommée (le vrai « Spoiler » est une faille CPU) et dont le contenu décrivait en réalité l'empoisonnement de données — est **fusionnée** vers `data-poisoning` (dépubliée + redirigée 301). `data-poisoning` reçoit la catégorie « Sécurité et éthique » et l'alias « empoisonnement de données ». Correction d'un lien taxonomique inversé : `embeddings` est désormais correctement rattaché comme sous-type de `vectorisation`. Migration réversible, aucun DELETE.

## [1.65.103] - 2026-06-08

### Fixed
- Glossaire — **8 doublons sémantiques consolidés** (audit prod-wide, fusion dans « Aussi appelé ») : `tokens`→`token`, `moe`→`mixture-of-experts`, `context-window`→`fenetre-de-contexte`, `shadow-ai`→`ia-fantome`, `infiltration-de-requete`→`prompt-injection`, `knowledge-distillation`→`distillation-de-modele`, `affinage`→`fine-tuning`, `edge-ai`→`ia-embarquee`. Pour chaque paire (même concept sous 2 fiches, le doublon étant l'entrée admin sans catégorie) : nom + alias uniques fusionnés dans « Aussi appelé » de la fiche canonique, doublon **dépublié** (réversible, aucun DELETE), liens broader/narrower nettoyés (self-refs retirés, `byoai.broader` shadow-ai→ia-fantome), ancien slug **redirigé 301**. Les paires limites (embeddings/vectorisation, ia-multimodale/modele-multimodal, llm/modele-de-langage) et l'entrée douteuse « spoiler » sont volontairement laissées pour décision éditoriale (concepts potentiellement distincts).

## [1.65.102] - 2026-06-08

### Fixed
- Glossaire — **liens internes cassés corrigés** (audit prod-wide) : 8 références `broader_slugs`/`narrower_slugs` invalides. Les renvois vers des doublons dépubliés sont remappés vers la fiche canonique (`differential-privacy` → `confidentialite-differentielle` sur anonymisation et k-anonymity) ; les renvois vers des slugs inexistants sont retirés (`protection-vie-privee` ×4, `hash-sha-256`, `hallucination-ia`). Migration réversible, aucun terme supprimé. Audit confirme aussi : 0 fiche sans image hero (les alertes initiales étaient des faux positifs dus au suffixe `?v=` dans le champ hero_image).

## [1.65.101] - 2026-06-07

### Fixed
- Glossaire — **2 doublons supplémentaires consolidés** (révélés par un audit prod-wide après le cas MCP) : `differential-privacy` → canonique `confidentialite-differentielle`, et `hallucination-ia` → canonique `hallucination`. Même traitement réversible : alias uniques fusionnés dans la fiche canonique (« differential privacy », « hallucination IA », « Hallucination LLM »…), doublon **dépublié** (aucun DELETE), ancien slug **redirigé en 301**. Les fiches canoniques (originaux du seeder, contenu propre) sont conservées ; les doublons venaient d'ajouts manuels via l'admin (le doublon `hallucination-ia` avait des artefacts markdown bruts).

## [1.65.100] - 2026-06-07

### Fixed
- Glossaire — **consolidation du doublon « MCP »** : deux fiches existaient pour le même concept (`mcp`, acronyme issu du seeder d'origine, contenu propre ; et `mcp-model-context-protocol`, ajouté via l'admin sur prod, avec des artefacts markdown bruts). La fiche canonique `/glossaire/mcp` (slug court, contenu propre) est conservée et enrichie des alias uniques du doublon (« serveur MCP », « MCP server », « protocole MCP ») ; le doublon est **dépublié** (migration réversible, aucun DELETE) et son ancien slug **redirige en 301** vers `/glossaire/mcp` (préserve le SEO, évite le contenu dupliqué et tout 404). Cause : ajout manuel via l'admin sans voir l'acronyme existant.

## [1.65.99] - 2026-06-07

### Added
- Glossaire : terme **Latence** (latency, cat Concepts fondamentaux) — délai entre une demande et le début de la réponse ; distinction latence de bout en bout / TTFT (temps jusqu'au premier token), facteurs réseau et calcul, différence avec le débit (throughput). Fiche complète (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées : Wikipédia, NVIDIA), image hero générée via le compte Gemini de l'utilisateur (jpg + webp 1200×670, sans texte). Migration réversible, anti-doublon par slug.

## [1.65.98] - 2026-06-07

### Changed
- Glossaire (/glossaire) — refonte de la zone recherche+filtres en **toolbar sticky compacte** (best practice UX 2026 : Baymard, NN/g, eBay, Material). La barre slim (recherche + bouton « Filtres » avec compteur d'actifs + compteur de résultats) suit désormais le scroll de façon non envahissante (~65px) ; les filtres (catégorie, type, A-Z) sont déplacés dans un **panneau dropdown** ouvert à la demande ; les filtres actifs s'affichent en **chips supprimables**. Synchronisation avec le header sticky du site (offset 90px desktop / 60px mobile, jamais de chevauchement) via MutationObserver sur `.sticky-on`. WCAG 2.2 : `scroll-padding-top` (focus non masqué), cibles ≥44px, focus visible, `position:static` en très faible hauteur (reflow). Correctif `position:sticky` (override `overflow` du `.page-wrapper`) **scopé à la seule page glossaire** (`!important`), zéro impact site-wide (vérifié sur /blog). Filtrage Alpine 100% client inchangé.

## [1.65.97] - 2026-06-07

### Added
- Glossaire : terme **Tokenpocalypse** (apocalypse des tokens, cat Intelligence artificielle) — néologisme 2026 décrivant l'explosion des coûts de tokens (agents IA, jusqu'à 1000×), le durcissement des limites de contexte/quotas et la fin des forfaits illimités. Fiche complète (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées : Stanford Digital Economy Lab, Yahoo Finance), image hero générée via le compte Gemini de l'utilisateur (jpg + webp 1200×670, sans texte). Migration réversible, anti-doublon par slug.

## [1.65.96] - 2026-06-07

### Added
- Glossaire (batch #13, dernier lot du backlog audit) : 3 termes « boucle d'entraînement » — **Époque** (epoch), **Batch** (lot d'entraînement), **Itération** (cat Concepts fondamentaux). Fiches complètes (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées chacun), images hero générées via le compte Gemini de l'utilisateur (jpg + webp 1200×670). Migration réversible, anti-doublon par slug. **Backlog audit glossaire clos : 405 termes au total.**

## [1.65.95] - 2026-06-07

### Added
- Glossaire (batch #12) : 3 termes « calcul & métriques » — **CUDA** (Compute Unified Device Architecture, cat Acronymes et sigles), **F1-score** (score F1, cat Données et traitement), **Perplexité** (perplexity, cat Données et traitement). Fiches complètes (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées chacun), images hero générées via le compte Gemini de l'utilisateur (jpg + webp 1200×670). Migration réversible, anti-doublon par slug.

## [1.65.94] - 2026-06-07

### Added
- Glossaire (batch #11) : 3 termes « média génératif » — **Inpainting** (retouche par masque, cat Outils et techniques), **Upscaling** (super-résolution, cat Outils et techniques), **Text-to-video** (texte vers vidéo, cat IA). Fiches complètes (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées chacun), images hero générées via le compte Gemini de l'utilisateur (jpg + webp 1200×670). Migration réversible, anti-doublon par slug.

## [1.65.93] - 2026-06-07

### Added
- Glossaire (batch #10) : 3 termes « alignement / capacités IA » — **Sycophancy** (flagornerie de l'IA, cat Sécurité et éthique), **Reward hacking** (piratage de la récompense, cat Sécurité et éthique), **Frontière dentelée** (jagged frontier, cat IA). Fiches complètes (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées chacun), images hero générées via le compte Gemini de l'utilisateur (jpg + webp 1200×670). Migration réversible, anti-doublon par slug.

## [1.65.92] - 2026-06-07

### Added
- Blog — image éditoriale du **concentré IA hebdomadaire (semaine du 1 au 7 juin 2026)** générée via le compte Gemini de l'utilisateur (isométrique, charte Memora navy/orange, sans texte) ; jpg 1200×670 (89 Ko) + webp (60 Ko) dans `public/images/blog/`. L'article (20 actualités, catégorie LE CONCENTRÉ) est publié en base.

## [1.65.91] - 2026-06-07

### Added
- Glossaire (batch #9) : 3 termes « capacités IA 2026 » — **Computer use** (usage de l'ordinateur, cat IA), **Deep research** (recherche approfondie, cat IA), **Instruction tuning** (ajustement par instructions, cat Concepts fondamentaux). Fiches complètes (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées chacun), images hero générées via le compte Gemini de l'utilisateur (jpg + webp 1200×669). Migration réversible, anti-doublon par slug.

## [1.65.90] - 2026-06-07

### Added
- **Glossaire — 3 termes « fiabilité LLM/RAG »** (batch #8) : **Reranking (reclassement)**, **Grounding
  (ancrage)**, **Sortie structurée**. Fiches complètes au standard (sources vérifiées 200 : Pinecone, Jina,
  Google Vertex, IBM, OpenAI, JSON Schema). Images via le compte Gemini de l'utilisateur. Migration
  réversible. Glossaire à 390 termes.

## [1.65.89] - 2026-06-07

### Added
- **Glossaire — 3 termes « architecture Transformer »** (batch #7, catégorie « Concepts fondamentaux ») :
  **Espace latent**, **Encodeur-décodeur**, **Encodage positionnel**. Fiches complètes au standard
  (définition, analogie, exemple, le saviez-vous, AEO, FAQPage, sources vérifiées 200 : DataFranca, IBM,
  Vaswani 2017, d2l). Images via le compte Gemini de l'utilisateur en Playwright. Migration réversible.
  Glossaire à 387 termes.

## [1.65.88] - 2026-06-07

### Added
- **Glossaire — 3 termes « agents & sûreté 2026 »** (batch #6, catégorie « IA ») : **Garde-fous (guardrails)**,
  **A2A (Agent-to-Agent)**, **Effondrement de modèle (model collapse)**. Fiches complètes au standard
  (définition, analogie, exemple, le saviez-vous, AEO, FAQPage, sources vérifiées 200 : IBM, Microsoft,
  GitHub A2A, Nature 2024). Images générées via le compte Gemini de l'utilisateur en Playwright (full-res).
  Migration réversible. Glossaire à 384 termes.

## [1.65.87] - 2026-06-07

### Added
- **Glossaire — 3 termes « tendances 2025-2026 »** (batch #5, catégorie « IA ») : **SLM (petit modèle de
  langage)**, **Modèle frontière**, **Poids ouverts**. Fiches complètes au standard (définition, analogie,
  exemple, le saviez-vous, AEO, FAQPage, sources vérifiées 200, image hero `.webp` + og:image `.jpg`).
  **Images générées via le compte Gemini de l'utilisateur en Playwright** (méthode imposée, full-res via
  « Télécharger en taille réelle »). Migration réversible. Glossaire à 381 termes.

## [1.65.86] - 2026-06-07

### Improved
- **Élagage SEO actualités — R4 : whitelist de rubriques protégées** (best-practice 2026 « hard-exclusions ») :
  nouvelle clé `config/news/seo_prune.php` → `protect_categories` (liste de `category_tag` jamais élagués,
  quelles que soient l'ancienneté/les vues). Défaut **vide** (aucun effet → 100 % additif et sûr). Les
  `category_tag` NULL restent élageables. Validé MySQL (rubrique protégée → index, autre → noindex).
  Rend la décision **multi-signal** (âge + vues + rubrique). R2 (signal GSC) et R6 restent différés.

## [1.65.85] - 2026-06-07

### Improved
- **Élagage SEO des actualités — remédiations post-audit** (audit v1.65.84, note 78/100) :
  - **R1** — la commande `news:prune-seo` **journalise** désormais chaque exécution (`Log::info`) et **notifie
    IndexNow** (`IndexNowService::submitBatch`) des URLs passées en noindex → déindexation plus rapide + traçabilité
    (corrige le bypass des observers par le mass-update + le cron muet).
  - **R3** — **auto-healing** : une actualité noindex redevenue performante (`views_count >= max_views`) repasse
    automatiquement en `index` (symétrie, évite de pénaliser un regain de trafic).
  - **R5** — **test automatisé** (`PruneSeoCommandTest`, Pest) + validation fonctionnelle MySQL (noindex /
    auto-healing / reset / dry-run / disabled = 5/5).
  - `--dry-run` affiche maintenant aussi les candidats « ré-index ». Toujours 100 % réversible.
  Différé (décisions structurelles) : R2 multi-signal GSC, R4 whitelist/maillage, R6 batchs+monitoring.

## [1.65.84] - 2026-06-07

### Added
- **Élagage SEO automatique et réversible des anciennes actualités** (anti-index-bloat, best practice 2026) :
  nouvelle colonne `news_articles.seo_status` (index|noindex|gone) + commande `news:prune-seo`
  (`--dry-run`, `--reset`) planifiée **mensuellement** (scheduler Laravel existant — aucun cron ajouté).
  Politique pilotée par `config/news/seo_prune.php` (zéro hardcode) : les actualités publiées depuis
  > 12 mois ET vues < 30 fois passent en **`noindex, follow`** (sorties de l'index + du sitemap, mais
  accessibles et l'autorité circule) ; les performantes restent indexées. Tier **410 Gone** disponible
  mais **désactivé** par défaut. 100 % réversible (flag DB, aucune suppression ; `--reset` annule).
  Évite la pénalité « index bloat » / Helpful Content tout en préservant le trafic longue traîne (données GSC).
  Master layout : robots `noindex,follow` par page via `@section('page_noindex')`. Réversible (`down()` + tag git).

## [1.65.83] - 2026-06-07

### Added
- **Glossaire — 3 termes « évaluation des modèles »** (batch P0 #4) : **Précision et rappel**,
  **Matrice de confusion** (catégorie « Données et traitement »), **LLM-as-a-judge** (catégorie « IA »).
  Fiches complètes au standard (définition, analogie, exemple chiffré, le saviez-vous, AEO, FAQPage,
  sources vérifiées 200, image hero `.webp` + og:image `.jpg`). Migration réversible. Glossaire à 378 termes.

## [1.65.82] - 2026-06-07

### Added
- **Glossaire — 3 termes fondamentaux ML/réseaux** (batch P0 #3) : **Sous-apprentissage** (complète la paire
  avec Surapprentissage), **Généralisation**, **Fonction d'activation** (catégorie « Concepts fondamentaux »).
  Fiches complètes au standard (définition, analogie, exemple, le saviez-vous, AEO, FAQPage, sources vérifiées,
  image hero `.webp` + og:image `.jpg`). Migration réversible. Glossaire à 375 termes.

## [1.65.81] - 2026-06-07

### Added
- **Glossaire — 3 termes « mécanique du RAG »** (batch P0 #2) : **Chunking**, **Recherche sémantique**,
  **Similarité cosinus** (catégorie « Données et traitement »). Fiches complètes au standard (définition,
  analogie, exemple chiffré, le saviez-vous, AEO, FAQPage 2 Q/R, sources GEO vérifiées 200, image hero
  `.webp` + og:image `.jpg` 1200×669). Migration réversible (anti-doublon par slug, `down()`). Glossaire à 372 termes.

## [1.65.80] - 2026-06-06

### Added
- **Glossaire — 3 termes fondamentaux d'entraînement ML** (batch P0 #1, audit des manques) :
  **Rétropropagation**, **Descente de gradient**, **Fonction de perte** (catégorie « Concepts fondamentaux »).
  Fiches complètes conformes au standard : définition, analogie, exemple chiffré, « le saviez-vous »,
  réponse AEO (one_sentence_answer), FAQPage (2 Q/R), sources GEO vérifiées ({label,url} 200), image hero
  `{slug}.webp` + og:image `{slug}.jpg` (1200×669, compressées). Migration réversible (anti-doublon par slug,
  `down()` supprime par slug). Contenu via délégation MCP (gpt-4o-mini) + faits sourcés (sonar-pro),
  images via multi-ai-mcp (gemini-2.5-flash-image, session Playwright indisponible), affiné par le superviseur.

## [1.65.79] - 2026-06-06

### Fixed
- **Glossaire — dédoublonnage des catégories (données prod)** : la table `dictionary_categories`
  contenait des lignes dupliquées (catégories ré-insérées), d'où un `<select>` de filtre avec chaque
  catégorie en triple. Migration **réversible** `2026_06_06_030000_dedup_dictionary_categories` :
  sauvegarde complète (`dict_categories_dedup_bak` + mapping `dict_terms_catmap_dedup_bak`),
  groupe par `name` brut (ne fusionne QUE les doublons identiques), **réassigne** les termes des
  doublons vers la catégorie canonique (icône non-nulle puis plus petit id) AVANT suppression
  (FK `nullOnDelete`), puis supprime les doublons. **Zéro perte de termes**. `down()` restaure tout.
  Garde-fou additionnel : `->unique('name')` sur le filtre du glossaire (anti-doublons d'affichage futurs).
  Testé en local (up + down sans erreur). Réversible (tag `backup-pre-glossaire-dedup-v1.65.78`).

## [1.65.78] - 2026-06-06

### Fixed
- **Glossaire — « Duplicate key on x-for » (17×)** : le tableau `$categoriesForFilter` (filtre du
  dictionnaire) ne contenait pas de champ `id`, alors que le `<template x-for="cat in categories"
  :key="cat.id">` l'utilisait comme clé → clés `undefined` dupliquées. Ajout de `'id' => $c->id`.
  Le filtrage par catégorie se fait par `slug` (inchangé) → zéro impact comportemental, 366 termes
  rendus normalement. Découvert pendant la vérif Alpine (v1.65.77).

## [1.65.77] - 2026-06-06

### Fixed
- **Warning « Detected multiple instances of Alpine running » (site-wide)** : le thème chargeait Alpine 3
  via CDN EN PLUS de Livewire 4 (qui embarque déjà Alpine + ses plugins). Le **core Alpine CDN est retiré**
  du master ; seul le plugin `@alpinejs/intersect` reste (il s'attache à l'Alpine de Livewire via `alpine:init`).
  Tous les `Alpine.data()` du site sont déjà enregistrés sous `alpine:init` → compatibles. Une seule instance
  Alpine désormais. Sourcé pp_search (doc Livewire 4, juin 2026). Réversible (`backup-pre-p2-alpine-panel-v1.65.76`).
- **Panneau d'anonymisation du constructeur trop serré (~39 ch/volet)** : l'éditeur imbriqué dans la card
  étroite (col-lg-8) affichait 2 colonnes de ~309 px. Il est désormais **empilé** (`#cpAnonPanel .anon-grid`
  en 1 colonne) → volets pleine largeur (~83 ch), bien plus lisibles. Scoppé au constructeur ; l'anonymiseur
  autonome conserve son affichage 2 colonnes.

## [1.65.76] - 2026-06-06

### Fixed
- **Bouton « Copier » ne recouvre plus le texte (toutes largeurs, audit 1440 px)** : le bouton flottant
  `position:absolute` en haut-droite de la boîte de sortie masquait la 1re ligne du texte anonymisé à
  **toutes** les largeurs (mobile → desktop 1440). Il est désormais placé dans une **ligne d'en-tête**
  (`.anon-pane-head`, au-dessus de la boîte, à droite du label) → zéro chevauchement. Les deux volets
  reçoivent une `.anon-pane-head` de même hauteur → l'alignement des boîtes en mode 2 colonnes est
  préservé. Compact (~2.2em) sur desktop, ≥44 px en tactile (≤860 px). Composant + CSS, appliqué aux
  2 outils (anonymiseur + constructeur). Réversible (tag `backup-pre-copybtn-header-v1.65.75`).

## [1.65.75] - 2026-06-06

### Fixed
- **UX tablette éditeur d'anonymisation** (audit Playwright 768×1024 + 1024×768) : les correctifs tactiles
  (bouton « Copier » en flux normal hors du texte + bascule de vue ≥44 px) passent du breakpoint mobile
  (≤480 px) à **≤860 px** → couvre la tablette portrait, où le bouton « Copier » flottant chevauchait la
  première ligne du texte anonymisé (overlap 8 px mesuré). À ≤860 px la grille est déjà empilée, donc
  aucun impact sur l'alignement des volets en mode 2 colonnes (≥1024 px inchangé). Police 16 px reste
  scoppée ≤480 px (anti-zoom iOS iPhone). CSS uniquement, desktop inchangé.

### Notes
- « Split » à 768 px portrait = les 2 volets empilés et visibles (comportement tablette voulu, lisible) —
  pas un défaut. Forcer 2 colonnes à 768 px cramperait l'éditeur riche.

## [1.65.74] - 2026-06-06

### Changed
- **Pop-up infolettre retirée des pages outils** (`outils/*`) : le scroll-trigger (bottom-sheet ~234 px sur mobile,
  ~29 % de l'écran) n'apparaît plus pendant l'usage d'un outil (éditeur/formulaire = tâche focalisée), où il
  masquait la barre d'outils et risquait la pénalité Google « interstitiels mobiles intrusifs ». Conservée sur le
  contenu (blog, articles, index) où le déclenchement au scroll reste pertinent. Décision sourcée (pp_search NN/g,
  juin 2026). Réversible (retrait du `@unless`). Aucune autre page affectée, aucune donnée supprimée.

### Notes
- Modale cookies : déjà conforme (`max-height: min(90vh,640px); overflow-y:auto`) — le « bouton hors viewport »
  de l'audit était un artefact Playwright (clic avant scroll), aucun correctif nécessaire.

## [1.65.73] - 2026-06-06

### Fixed
- **UX mobile éditeur d'anonymisation** (anonymiseur + panneau du constructeur, audit Playwright 375 px) :
  - police des champs éditables portée à 16 px sous 480 px → supprime le zoom automatique de Safari iOS au focus ;
  - bascule de vue (Éditeur/Split/Aperçu) : cibles tactiles ≥44 px, pleine largeur sur mobile ;
  - bouton « Copier » flottant remis en flux normal sous 480 px → ne recouvre plus le texte de sortie, cible ≥44 px.
  - CSS uniquement, scoppé `@media (max-width:480px)` ; desktop inchangé.

## [1.65.72] - 2026-06-06

### Fixed
- **Constructeur de prompts** : icône du bouton « Insérer dans la tâche » illisible (emoji ➕ sombre
  sur fond teal foncé) remplacée par une icône SVG `+` en `currentColor` (blanche, contraste correct).

## [1.65.71] - 2026-06-06

### Changed
- **DRY — éditeur d'anonymisation réutilisable** : extraction de l'éditeur de `/outils/anonymiseur`
  (barre d'outils, bulle de sélection, surlignage/annotation, modes réaliste/jetons, popover d'occurrence)
  dans un composant Blade unique `<x-tools::anonymizer-editor>` + un partial scripts partagé
  `tools::partials.anonymizer-scripts`. Slot `previewActions` pour adapter les boutons à chaque page.
- **Constructeur de prompts** : le panneau « 🛡️ Anonymiser un texte » réutilise désormais l'éditeur
  COMPLET (même UX que l'anonymiseur : sélection, surligner, anonymiser) au lieu d'un mini-formulaire.
  Le bouton « Insérer dans la tâche » lit le texte anonymisé partagé (`window.lvAnonUI.anonPlain`).
- `anonymizer-ui.js` expose `window.lvAnonUI` (init défensif uniquement si l'éditeur est présent).
- Aucune duplication de markup ni de logique entre les deux outils ; zéro régression sur l'anonymiseur.

## [1.65.70] - 2026-06-06

### Added

- **Pied de page — crédit « Conçu et hébergé par MEMORA solutions · Entreprise canadienne 🍁 »** : ligne discrète sous le copyright (site-wide), d'après les best practices juin 2026 (sous le copyright, typo réduite, couleur atténuée WCAG, ancre = nom de marque). Lien `rel="nofollow noopener noreferrer" target="_blank"` vers https://memora.solutions (évite un profil de liens artificiel sur un lien site-wide).

## [1.65.69] - 2026-06-06

### Changed / Fixed

- **Anonymiseur — la colonne « anonymisé » suit la colonne de gauche en TEMPS RÉEL** : dès qu'on colle/écrit dans l'éditeur, le panneau de droite se met à jour (anti-rebond ~120 ms), sans devoir cliquer « Détecter et anonymiser ». Avant masquage : la droite reflète le texte ; après : anonymisé en direct.
- **Anonymiseur — le popover d'occurrence se ferme au clic à l'extérieur** (+ Échap) : il restait ouvert quand on cliquait dans le texte (le handler excluait la zone annotée). Cliquer ailleurs le ferme désormais ; cliquer une autre entité le rouvre.
- **Anonymiseur — « Réinitialiser » et « Oublier mes données » sont maintenant distincts** : « ↺ Réinitialiser le masquage » efface l'anonymisation mais **conserve votre texte** (pour re-masquer autrement) ; « 🗑️ Oublier mes données » efface **tout** (texte + correspondances). Lèvent la confusion (les deux faisaient la même chose).

## [1.65.68] - 2026-06-06

### Changed

- **Anonymiseur — un seul bouton « 🕵️ Détecter et anonymiser »** (demande utilisateur) : remplace les deux boutons séparés de la barre d'outils ; détecte puis anonymise tout en un clic. Les actions séparées « 🔍 Détecter seulement » et « 🕵️ Tout anonymiser » sont déplacées dans le menu ⋯ Actions (toujours disponibles). Nouvelle méthode `detectAndAnonymizeAll()` ; `detect(silent)` pour éviter le double toast. **Vérifié Playwright** : un clic → 3 entités détectées + anonymisées (0 candidat restant, données réelles absentes), 0 erreur console.

## [1.65.67] - 2026-06-06

### Changed

- **Anonymiseur — « Tout anonymiser » remonte dans la barre d'outils** (demande utilisateur) : à droite de « 🔍 Détecter », le bouton est désormais « 🕵️ Tout anonymiser » (action la plus courante après détection). « ✍️ Anonymiser la sélection » est déplacé dans le menu ⋯ Actions (fonction inchangée). Aucun changement de logique (ids conservés).

## [1.65.66] - 2026-06-06

### Added

- **Anonymiseur — le courriel reprend le MÊME faux nom que la personne (cohérence)** : quand le nom d'une personne apparaît dans la partie locale d'un courriel (« martin.rousseau@hexasoft.io »), le faux courriel utilise désormais le même faux nom que la personne (« Martin Rousseau » → « André Gauthier » ⇒ « andre.gauthier@… ») au lieu d'un nom aléatoire incohérent. Nouvelle fonction `relinkEmails()` (moteur) appelée après chaque anonymisation et changement de mode ; remplace les jetons du nom dans la partie locale, conserve les séparateurs (`.`/`_`/`-`) et le faux domaine, garantit l'unicité (réversibilité préservée). Les courriels sans nom lié restent aléatoires. **Vérifié** : test Node (round-trip 100 % sur cas variés) + Playwright UI (cohérence prénom.nom + restauration exacte).

## [1.65.65] - 2026-06-06

### Added

- **Anonymiseur — le texte de l'éditeur est conservé dans le navigateur (restauré à votre retour)** : demande utilisateur. Le contenu est sauvegardé en `localStorage` (clé `lv_anon_source_v3`, **stable et non purgée aux mises à jour** → survit aux déploiements ; **jamais envoyé à un serveur**) à chaque saisie, et restauré au chargement avec sa mise en forme. Effacé uniquement par « Réinitialiser » ou « 🗑️ Oublier mes données ». « Réinitialiser » efface désormais **tout le contenu** (texte + correspondances + sauvegarde). Note de confidentialité mise à jour (transparence + rappel d'effacer sur un poste partagé). **Vérifié Playwright** : saisie → rechargement → texte + format restaurés ; reset → vidé et persistant.

## [1.65.64] - 2026-06-06

### Fixed

- **Accessibilité/SEO — `h1` manquant ajouté sur 2 outils** (oscilloscope-rlc, roue-tirage) : ces pages n'avaient aucun `<h1>` (uniquement des `h2`). Ajout d'un `h1` accessible (sr-only, technique clip — lu par Google et les lecteurs d'écran, zéro impact visuel sur ces outils canvas/app dont le titre s'affiche déjà via l'UI et le fil d'Ariane). Chaque page outil a désormais exactement un `h1`.

## [1.65.63] - 2026-06-06

### Changed

- **Anonymiseur — surlignage optimisé (fast-path) sur les longs documents** (audit P2, plan validé) : `highlightEntitiesInElement` ne lance plus chaque regex sur chaque nœud texte (O(N×M)). Pré-calcul du 1er mot normalisé de chaque entité ; pour un nœud, on saute une entité si son 1er mot n'y figure pas (`indexOf`) — skip **sûr** (le 1er mot doit être présent pour tout match, même avec espaces flexibles). **Vérifié Playwright** : surlignage identique (cas piège « Jean  Dubé » double espace OK), 200 paragraphes / 800 surlignages en **10 ms**, 0 régression.
- **Anonymiseur — `execCommand('insertHTML')` conservé volontairement** (recherche juin 2026) : c'est le seul levier qui préserve l'annuler/refaire natif ; un remplacement par `Range.insertNode` casserait l'undo. Décision documentée en commentaire (pas de refactor à régression).

## [1.65.62] - 2026-06-06

### Changed

- **Publicités AdSense retirées des pages d'outils traitant des données personnelles** (décision suite à l'audit ; posture de confiance Loi 25) : le chargeur AdSense du layout (`master.blade.php`) ne se déclenche plus sur les pages déclarant `@section('no_ads')`. Activé sur l'**anonymiseur** et le **constructeur de prompts** (qui manipulent du texte potentiellement personnel). Mécanisme scopé via section Blade : **aucun impact sur les autres pages** (les pubs restent actives partout ailleurs — revenu préservé). Liste extensible à tout futur outil sensible.

## [1.65.61] - 2026-06-06

### Added / Fixed

- **Anonymiseur — bouton « 🗑️ Oublier mes données » (vie privée, audit P0)** : nouvel item du menu ⋯ Actions qui efface TOUT de ce navigateur (texte, sortie, réponse IA, **table de correspondance** `lv_anon_rules_v3`/`overrides_v3` en localStorage). Note explicite ajoutée dans l'accordéon « 100 % local » (effacer sur un poste partagé). Répond au constat d'audit : les correspondances vraie↔fictive persistaient en localStorage.
- **Anonymiseur — défense en profondeur XSS (audit P1)** : `renderAnnotated` et `updateOutput` re-sanitizent désormais le HTML de l'éditeur (`sanitizePastedHtml`) avant toute injection `innerHTML`, au lieu de se fier uniquement à la sanitisation au collage. Vérifié Playwright : le formatage (gras, listes) reste préservé.
- **Constructeur de prompts — méta-description enrichie (SEO, audit P2)** : `tools.description` passe de 53 à ~165 car. (migration `2026_06_06_020000`, réversible) — décrit persona/tâche/audience/format/techniques + modèles cibles (ChatGPT, Claude, Gemini, Mistral).

## [1.65.60] - 2026-06-06

### Fixed

- **Anonymiseur — comble 3 fuites de détection identifiées par l'audit (NAS, montants format québécois, noms abrégés)** : l'audit exhaustif des outils (rapport `.outils/audit-anonymiseur-constructeur-2026-06-06.md`) a mesuré une détection automatique de 80 % avec des faux négatifs sensibles. Ajout à `detectEntities` : (1) **NAS** (numéro d'assurance sociale) — contextuel (étiquette « NAS »/« assurance sociale ») + isolé validé par **algorithme de Luhn** (évite les faux positifs sur tout numéro à 9 chiffres) ; (2) **montants format québécois** où le « $ » suit le nombre (« 1 250,00 $ », « 2 750$ ») ; (3) **noms abrégés** initiale + nom après titre (« Mme L. Gagnon », « Dr. A. Roy »). **Vérifié (test Node, corpus 12 cas PII québécois)** : détection 80 % → **100 % (40/40)**, réversibilité round-trip **100 %**, **zéro régression** (cas noms/médicaux), **zéro faux positif** (numéros non-Luhn et téléphones non confondus).

## [1.65.59] - 2026-06-05

### Fixed

- **Accessibilité — icônes SVG du bouton plein écran marquées décoratives** (audit WCAG de l'anonymiseur fraîchement publié). Le bouton porte déjà `aria-label="Plein écran"` ; ses 2 SVG reçoivent `aria-hidden="true" focusable="false"` (cohérent avec le bouton « partager »), ce qui lève le signalement WCAG 1.1.1 (« SVG missing accessible name ») sans double annonce pour les lecteurs d'écran. Passe qualité de mise en ligne : indexabilité OK (`robots: index,follow`, présent au sitemap, canonical), contraste du nouveau panneau d'anonymisation du constructeur conforme AA (6,77:1 et 7,34:1). Les autres signalements de l'audit headless sont des faux positifs connus (blanc/blanc = fond foncé du header / modale cachée non vus par le scanner ; « Tab » = éléments dans des panneaux volontairement masqués).

## [1.65.58] - 2026-06-05

### Fixed

- **Bouton plein écran des outils — icône « brisée » corrigée** (signalé sur le constructeur de prompts, partial partagé `tools::partials.fullscreen-btn`). Cause : la règle responsive globale `svg { max-width:100%; height:auto }` (charte.css) s'appliquait à la SVG inline 16×16 du bouton ; comme ce bouton est `ct-btn-ghost ct-btn-xs` (largeur **auto**, contrairement au bouton « partager » en `ct-btn-icon` 44×44 fixe), le dimensionnement devenait circulaire et l'icône se réduisait/déformait. Correctif ciblé **zéro risque** : taille forcée en style inline (`width:16px;height:16px;flex-shrink:0`) sur les 2 SVG du partial (bat la règle globale). Corrige l'icône sur **tous** les outils, sans toucher aux autres médias.

## [1.65.57] - 2026-06-05

### Added

- **Anonymiseur ↔ Constructeur de prompts — liaison des deux outils (utilisables séparément OU ensemble, 100 % local)** : d'après la recherche best practices juin 2026 (Perplexity ; privacy-by-design, pas de PII en URL), approche hybride notée 93/100 (module partagé in-page) + 88/100 (handoff sessionStorage), évitant le deep-link URL (35/100, fuite PII).
  - **Module partagé in-page** (pattern 2) : le constructeur de prompts charge le moteur `window.AnonymizerCore` et expose un panneau repliable « 🛡️ Anonymiser un texte (optionnel) » (progressive disclosure) — anonymise un texte localement puis l'insère dans le champ « Objet de la tâche » (`prompt-anon-panel.js`, vanilla, 100 % local).
  - **Handoff sessionStorage** (pattern 1) : bouton « ✨ Créer un prompt → » dans l'anonymiseur qui transmet **uniquement le texte anonymisé** via `sessionStorage` (volatile, same-origin — **jamais dans l'URL**) ; le constructeur l'importe automatiquement, affiche un toast et **efface la clé** (one-time). Lien « ↗ Anonymiseur complet » côté constructeur.
  - Les deux outils restent **100 % autonomes**. Aucune donnée personnelle ne quitte le navigateur.

## [1.65.56] - 2026-06-05

### Changed

- **Anonymiseur de texte — PUBLIÉ publiquement** (GO user « publie l'outil ») après la refonte UX/UI complète (v1.65.43→55) et la certification E2E intégrée PASS. Migration `2026_06_05_210000_publish_anonymiseur_go_user` : `tools.is_under_construction = false` pour `slug='anonymiseur'` (le déploiement exécute `php artisan migrate --force` puis vide les caches). Seeder par défaut aligné sur `false`. L'outil n'est plus en placeholder « en construction » : il est accessible à tous sur `/outils/anonymiseur` et listé sans badge « Bientôt ». Réversible via le `down()` de la migration.

## [1.65.55] - 2026-06-05

### Added

- **Anonymiseur — les données restaurées sont surlignées + leur ancienne valeur anonyme se révèle au survol/focus** : dans « Résultat avec vos vraies données », chaque vraie donnée remise en place est **surlignée en vert** (= restaurée). Au **survol OU au focus clavier**, un tooltip accessible affiche « Anonymisé : *faux* » (fermable avec Échap, survolable/persistant — conforme **WCAG 2.2 §1.4.13**, pas le `title` natif). Bouton **« 👁️ Voir les valeurs anonymes »** : bascule globale qui révèle tous les faux en ligne « vrai (faux) » pour relecture/audit (mobile/clavier-friendly). Approche notée 92/100 (recherche pp_search juin 2026 : tooltip accessible custom + bascule globale > badge inline > `title` natif). `#restoredOutput` passe de `textarea` à div riche ; la copie du résultat reste le texte exact (`restoredPlain`). **Vérifié Playwright** : 3 données surlignées avec `data-fake`+`aria-label`, tooltip hover **et** focus, fermeture Échap, bascule `aria-pressed`, 0 erreur console.

## [1.65.54] - 2026-06-05

### Fixed

- **Anonymiseur — restauration plus robuste quand la réponse IA est collée sans séparateurs + bornes de mots sensibles aux accents** : trouvé lors d'une certification E2E intégrée. (1) `restore()` utilise désormais `buildAccentInsensitiveUnboundedRegex` (sans `\b`) car les pseudonymes sont uniques par construction — une valeur dont la fin touche le mot suivant (ex. `…01RAMQ…` dans un texte collé) est désormais restaurée. (2) `buildAccentInsensitiveBoundedRegex` (détection/anonymisation) : les bornes `\b` (ASCII seulement) deviennent des bornes explicites incluant les lettres accentuées `À-ÿ` → meilleures limites de mots pour « Gagné », « Émilie », etc. **Vérifié (test Node)** : détection inchangée, **round-trip 100 % (3/3)**, restauration d'adjacence corrigée. **Certification E2E intégrée PASS** : 7 entités, format préservé des 2 côtés, restauration complète, rapport structuré, 0 erreur console.

## [1.65.53] - 2026-06-05

### Added

- **Anonymiseur — la colonne de droite (texte anonymisé) surligne aussi les valeurs, pour comparer facilement** : le panneau résultat passe de `textarea` à une vue riche miroir de la colonne gauche. Les valeurs remplacées y sont **surlignées** (fond teal) et les candidats non encore masqués **soulignés**, exactement aux mêmes endroits qu'à gauche → comparaison original ↔ anonymisé immédiate. La mise en forme (gras, listes) est conservée des deux côtés. La fonction `highlightEntitiesInElement` accepte un remplacement par marque + un mode non interactif (pas de boutons/`tabindex` inertes à droite). **La copie vers l'IA reste le texte simple exact** (`anonPlain` via l'anonymisation à plat, avec les overrides), indépendant de l'affichage riche. **Vérifié Playwright** : surlignage à droite (faux affichés, vraies valeurs absentes), surlignage imbriqué dans `<strong>`, listes préservées, alignement gauche/droite conservé, 0 bouton inerte, 0 erreur console.

## [1.65.52] - 2026-06-05

### Fixed

- **Anonymiseur — meilleure détection des noms dans les lettres (médicales/admin)** : « Patient Louise Gagnon » détectait « Patient Louise » (le mot « Patient » en début de phrase pris pour un prénom) et ratait le vrai nom. Ajout des mots courants qui précèdent un nom aux mots ignorés (`patient`, `patiente`, `usager`, `bénéficiaire`, `médecin`, `concernant`, `référence`, `sujet`, `destinataire`, `dossier`, `date`) + **rembobinage du scan** : quand le 1er mot d'une paire est un mot courant, on ne consomme pas le 2e mot et on rescanne pour capter le vrai nom complet derrière. **Vérifié (test Node)** : « Patient Louise Gagnon » → « Louise Gagnon », « Concernant Julie Morin » → « Julie Morin », « Le bénéficiaire Marc Tremblay » → « Marc Tremblay », sans régression (« Dr Jean Dubé » → « Jean Dubé », « Dr Lavoie » → « Lavoie »).

## [1.65.51] - 2026-06-05

### Changed

- **Anonymiseur — bouton « Copier » accessible en haut du panneau résultat (plus seulement en bas)** : d'après les meilleures pratiques juin 2026 (Perplexity ; éviter « Copier » uniquement en bas sur un long contenu), ajout d'un bouton « 📋 Copier » flottant en haut-droite du panneau « Texte anonymisé » (pattern bloc de code, overlay → ne casse pas l'alignement gauche/droite). Le bouton « Copier pour l'IA » du bas est conservé (2e accès pour les longs contenus) et « J'ai la réponse de l'IA → » reste en bas comme action de progression séparée. Feedback « ✓ Copié » sur les boutons. **Vérifié Playwright** : bouton flottant en `position:absolute` haut-droite, colonnes split toujours alignées (262.5px=262.5px), 0 erreur console.

## [1.65.50] - 2026-06-05

### Changed

- **Anonymiseur — rapport de restauration restructuré (UX lisible)** : la longue phrase « X valeur(s) restaurée(s) sur N. Non retrouvées : « … », « … », … » est remplacée par un rapport structuré : en-tête avec icône + compte (✅ si ≥1 restaurée, ⚠️ si 0), une note explicative (« absentes de la réponse collée — normal si l'IA ne les a pas reprises »), puis les valeurs non retrouvées en **puces** lisibles. **Déduplication du bruit** : un nom de famille ou prénom seul (« Louise », « Gagnon ») n'apparaît plus si le nom complet (« Louise Gagnon ») est déjà listé. Nouveau `buildRestoreReportHtml()` dans `anonymizer-rich.js`. **Vérifié Playwright** : 3 puces correctes (Roy / Louise Gagnon / Julie Morin), sous-parties dédupliquées, 0 erreur console.

## [1.65.49] - 2026-06-05

### Fixed

- **Anonymiseur — débordement horizontal sur mobile (375px) corrigé** : trouvé lors d'une passe QA proactive (Playwright). La `.anon-textarea` avait `width:100%` sans `box-sizing:border-box` → padding + bordure provoquaient un débordement de 18px à 375px. Ajout de `box-sizing:border-box`. **Passe QA complète PASS 13/13** : 3 vues (Éditeur/Split/Aperçu), pipeline collage riche → détection → anonymisation (•/1. + faux, nom seul vs complet) → restauration exacte, clavier (Entrée sur entité), responsive 375/768/1280 sans débordement, 0 erreur console.

## [1.65.48] - 2026-06-05

### Fixed

- **Anonymiseur — les deux champs (original / anonymisé) démarrent maintenant au même niveau** : le label de gauche « Votre texte (cliquez les passages soulignés pour les anonymiser) » occupait 3 lignes (texte sur 2 lignes + le « ? » qui retombait dessous), poussant la boîte de gauche bien plus bas que celle de droite. Corrigé : label raccourci à « Votre texte » (le détail reste dans l'aide « ? » et la légende), et `.anon-pane-label` passe en hauteur fixe égale avec `flex-wrap:nowrap` (le « ? » reste à côté du texte). **Vérifié Playwright** : labels 32px = 32px, les deux champs démarrent au même Y (262.5px = 262.5px).

## [1.65.47] - 2026-06-05

### Fixed

- **Anonymiseur — espacement identique entre le volet original (gauche) et anonymisé (droite)** : le volet gauche était plus aéré (line-height 1.7 + marges de paragraphes/listes) que le textarea de sortie (line-height 1.5, sans marges), ce qui nuisait à la comparaison côte à côte. Uniformisé en CSS : line-height 1.5 partout, marges de bloc (p/ul/ol/li/titres) à 0 dans l'éditeur riche pour épouser le rythme du textarea, hauteur min des labels égalisée (`min-height` → les 2 boîtes démarrent au même Y), hauteur min des 2 boîtes alignée. **Vérifié Playwright** : line-height (24px=24px), padding-left (16px=16px), hauteur des labels (38px=38px) et position top des 2 boîtes (268.4px=268.4px) strictement identiques.

## [1.65.46] - 2026-06-05

### Changed

- **Anonymiseur — la sortie texte conserve la vraie puce « • » des listes à puces (au lieu d'un tiret « - »)** : suite à une remarque utilisateur (les puces de l'éditeur devenaient des tirets dans le texte anonymisé). `richToText()` sérialise désormais les `<ul>` avec « • » (identique à l'affichage de l'éditeur) ; les `<ol>` gardent « 1. / 2. ». La sortie envoyée à l'IA ressemble ainsi exactement à l'éditeur. **Vérifié Playwright** : `• Tension`/`• LDL`, `1. Analyse`/`2. Suivi`, puce conservée après anonymisation, 0 erreur console.

## [1.65.45] - 2026-06-05

### Fixed

- **Anonymiseur — un nom seul (prénom OU nom de famille) n'est plus remplacé par un nom complet inventé** : « Bonjour Dr Lavoie » devenait « Bonjour Dr Nathalie Morin » (prénom + nom fabriqués). Désormais un seul mot → un seul faux. Trois corrections : (1) `detectEntities` — un seul mot après un titre de civilité (Dr/M/Mme…) est classé `lastName` au lieu de `name` ; (2) `buildRules` — un `'name'` à un seul mot (ex. sélection manuelle) utilise un faux unique au lieu d'un prénom + nom ; (3) `guessCategory` (ui) — un mot capitalisé seul → `lastName`, deux mots ou plus → `name`. Les noms complets (« Dr Jean Dubé » → « Dr Isabelle Morin ») restent complets ; cohérence préservée entre un nom de famille seul et le même nom dans un nom complet. **Vérifié (test unitaire Node)** : « Dr Lavoie »→« Dr Fortin », « Mme Gagnon »→« Mme Lavoie », « Dr Jean Dubé »→« Dr Isabelle Morin », phrase mixte OK.

## [1.65.44] - 2026-06-05

### Added

- **Anonymiseur — la sortie vers l'IA conserve aussi les marqueurs de liste (`1.`, `2.`, `-`)** : complément du v1.65.43. Le texte simple dérivé de l'éditeur riche passe d'`innerText` (qui perdait les puces/numéros générés par CSS) à un nouveau `richToText()` (dans `anonymizer-rich.js`) qui sérialise `<ol>`/`<ul>` en marqueurs texte (`1. `, `- `, indentation des listes imbriquées). Les listes survivent donc de bout en bout : éditeur → texte anonymisé copié à l'IA → restauration. Détection, anonymisation et restauration intactes (les marqueurs ne font pas partie des valeurs d'entités). **Vérifié banc d'essai Playwright** : `richToText` 1./2./- corrects sans indentation parasite niveau 1, sortie anonymisée conserve les listes, anonymisation nom+courriel OK, restauration 3/3, 0 erreur console.

## [1.65.43] - 2026-06-05

### Added

- **Anonymiseur — l'éditeur conserve la mise en forme (gras, italique, listes à puces et numérotées, titres) au collage** : le champ de saisie passe de `textarea` (texte brut, qui supprimait tout format) à un éditeur riche `contenteditable`. Approche retenue après recherche best practices juin 2026 (Perplexity, doc ProseMirror/Tiptap paste-handler) : **éditeur riche + anonymisation sur les nœuds texte** (note 90/100), supérieure au Markdown round-trip (68) et au textarea brut (38), sans réintroduire de dépendance Tiptap (les bugs passés y étaient liés).
  - Nouveau fichier additif `anonymizer-rich.js` : `sanitizePastedHtml()` (liste blanche stricte `p/br/b/strong/i/em/u/ul/ol/li/h1-3/blockquote/a[href]`, nettoyage du HTML Word/Google Docs : styles, classes, `<span>`, scripts, balises `mso`/`o:p`) + `highlightEntitiesInElement()` (surlignage injecté **dans les nœuds texte** d'un clone du HTML riche → la mise en forme reste intacte ET les entités restent cliquables).
  - **Zéro régression sur le moteur réversible** : détection, anonymisation et restauration continuent sur le texte (`innerText`), la sortie pour l'IA reste en texte simple (c'est ce que l'IA reçoit). Bulle de sélection, popover par occurrence, modes réaliste/jetons, valeur personnalisée, bascule de vue : tous conservés.
  - **Vérifié en banc d'essai local (Playwright)** : sanitize Word 9/9, `<strong>/<ul>/<ol>` préservés à travers détection → annotation → sortie, 5 entités détectées+anonymisées (les vraies données disparaissent de la sortie), restauration 3/3 exacte, **0 erreur console**.

## [1.65.42] - 2026-06-05

### Fixed

- **Anonymiseur — boutons d'aide alignés sur la charte réelle du site** : mes boutons utilisaient `.ct-help-btn` avec le glyphe « ⓘ » (un caractère cercle-i → effet cercle-dans-cercle, présent seulement sur calculatrice). La charte dominante (constructeur-prompts, simulateur-fiscal, code-qr, roue-tirage) utilise un **« ? » rond** `ct-btn ct-btn-ghost ct-btn-xs` (22px, border-radius 50%). Basculé sur ce style **byte-identique** au bouton de référence (même classes + même style inline), en conservant `data-help-key` pour ouvrir la popup complète. Conforme à la capture utilisateur (bouton « ? » de la section persona du constructeur de prompts).

## [1.65.41] - 2026-06-05

### Fixed

- **Anonymiseur — boutons d'aide alignés sur la charte du site** : uniformisation des ⓘ (un seul glyphe « ⓘ » partout — un « ? » résiduel retiré ; un seul ⓘ par section ; l'explication « Seulement ici »/« Ma valeur » fusionnée dans l'aide « masquer »). **Vérifié visuellement** (Playwright) : identique à la référence `.ct-help-btn` du site (22×22px, cercle teal #064E5A).
- **Anonymiseur — rester en haut de l'éditeur après collage d'un long texte** : le champ auto-extensible faisait « tomber » la page en bas ; un handler de collage ramène la vue en haut du champ (offset toolbar). **Vérifié visuellement** : après 60 lignes collées, le haut de l'éditeur reste visible.

## [1.65.40] - 2026-06-05

### Added

- **Anonymiseur — boutons d'aide ⓘ (popups du thème) + valeur personnalisée partout (en construction/admin)**.
  - **Aides contextuelles** : boutons ⓘ sur les sections clés (affichage des volets, « comment ça marche », masquer une donnée, **éléments déjà masqués / « Différent ici »**, restauration), via le **composant officiel `<x-core::help-modal>`** (déjà global) + `window.HELP_CONTENT` → **100 % uniforme avec la charte**. Explications grand public.
  - **Valeur personnalisée (anti-régression)** rendue intuitive et disponible partout : la bulle de sélection et le popover d'une donnée masquée offrent **« ✎ Ma valeur »** (je choisis le remplacement, partout) ; le popover ajoute **« 🔀 Seulement ici »** (valeur distincte pour cette occurrence) et **« ↩︎ Annuler »**. `setCustomReplacement` (global) + `addOverride` (par occurrence). Validé **E2E Playwright** : 5/5 popups d'aide + 4/4 valeur personnalisée (sélection, globale, par occurrence) avec **restauration exacte**.

## [1.65.39] - 2026-06-05

### Added

- **Anonymiseur — bascule de vue « ✍️ Éditeur · ⬓ Split · 👁️ Aperçu »** (en construction/admin) : un *segmented control* au-dessus de l'éditeur permet d'**agrandir un volet à pleine largeur** (Éditeur seul, ou Aperçu seul, en masquant l'autre) ou de revenir au **Split** côte à côte. Choix recommandé par la recherche juin 2026 (Apple HIG/UX Planet/W3C, option 95/100 : très découvrable, état visible, accessible clavier, excellent mobile). État **persisté** (localStorage `lv_anon_view`). Validé **E2E Playwright 5/5** (Éditeur 1000px/Aperçu masqué et inverse, retour split, persistance au rechargement, 0 erreur console).

## [1.65.38] - 2026-06-05

### Added

- **Anonymiseur — anonymisation par occurrence (« rendre cette occurrence différente »)**. Réponse à la demande : par défaut un même contenu reçoit toujours le même faux (cohérence) ; en cliquant sur une occurrence déjà anonymisée, un popover offre **« ✎ Différent ici »** pour donner à **cette occurrence précise** une valeur de remplacement distincte (les autres restent identiques), ou **« ↩︎ Annuler »**. Construit sur le moteur durci (passe par intervalles + overrides) : `renderAnnotated` numérote les occurrences (`data-occ`), overrides persistés (`lv_anon_overrides_v3`, versionnés). Validé **E2E Playwright 9/9** : cohérence par défaut (3× même faux), override sur la 2ᵉ occurrence seulement, et **restauration exacte des 3 occurrences** (réversibilité préservée). Option A retenue (refactor strangler-fig + golden/round-trip 100 %), sans régression.

## [1.65.37] - 2026-06-05

### Fixed

- **Anonymiseur — durcissement du moteur : réversibilité garantie (~73 % → 100 %)**. En auditant une demande d'évolution (anonymisation par occurrence), découverte d'un défaut latent : ~1 aller-retour sur 4 échouait à cause de **collisions de valeurs factices** (remplacements en cascade + deux personnes recevant le même faux). Refonte best-practice (recherche juin 2026 : single-pass interval tokenizer) : `anonymize` **et** `restore` réécrits en **passe unique par intervalles** (plus de re-remplacement en cascade) ; `buildRules` génère des faux **globalement uniques** (aucun faux n'égale un original ni un autre faux) avec garantie finale d'unicité. Résultat : **aller-retour 100 % sur 30 000 cas** (y compris adversariaux : 6 personnes même nom, répétitions). Préliminaire au support de l'anonymisation par occurrence (overrides). Détection et UI inchangées.

## [1.65.36] - 2026-06-05

### Fixed

- **Anonymiseur — garde-fou anti-fuite : le faux n'égale jamais l'original** : par collision aléatoire, une valeur factice pouvait égaler la vraie (ex. faux prénom « Jean » = vrai prénom « Jean »), laissant fuiter une donnée. Ajout de `safeFake()` (régénère jusqu'à 8× si le faux normalisé == l'original) ; `buildRules` compose les noms à partir de **parties prénom/nom garanties différentes** des vraies (et cohérentes entre occurrences). **Confirmation** : les répétitions d'un même contenu reçoivent **toujours le même** faux (cohérence pour l'IA) et la restauration reste parfaite. Testé Node : 18 000 règles, **0 collision**.

## [1.65.35] - 2026-06-05

### Fixed

- **Anonymiseur — règles « fantômes » persistantes** : des règles créées par d'anciennes versions (avant les correctifs de détection) restaient dans `localStorage` et re-surlignaient à tort des termes (« Vieux-Québec », « Téléphone »…) même si la détection actuelle ne les crée plus. Fix : les règles sont **estampillées avec la version de l'outil** (`window.LV_ANON_VERSION`) ; au chargement, si la version a changé, on **repart d'un état propre** (purge automatique). Plus de règles périmées après un déploiement. (La détection actuelle sur le texte médical de référence est propre : 15 entités, toutes correctes.)

## [1.65.34] - 2026-06-05

### Fixed

- **Anonymiseur — faux nom détecté à cheval sur un saut de ligne** : la regex de noms utilisait `\s+` (qui traverse les retours à la ligne) → deux mots capitalisés en fin/début de lignes voisines (ex. « CLSC de **Rosemont** » + « **Référence** en cardiologie ») étaient fusionnés en un faux nom, avec l'espace surligné. Fix : entre les deux mots (regex `name` et `titled`), n'autoriser que l'espace **sur la même ligne** (`[^\S\r\n]+`). Vérifié Node : plus de fusion cross-ligne **et zéro régression** sur les vrais noms (Jean Dubé, Jean-François Tremblay, Dr Lavoie, Louise Gagnon, Marie Roy, espaces insécables).

## [1.65.33] - 2026-06-05

### Fixed

- **Anonymiseur — Cmd/Ctrl+A sélectionnait toute la page** : la vue annotée est un `div` (non éditable nativement) → le raccourci sélectionnait tout le document. Désormais intercepté pour **confiner la sélection au seul contenu du champ annoté** (`Range.selectNodeContents`). Validé E2E Playwright (sélection limitée à `#anonAnnotated`, rien hors champ).

## [1.65.32] - 2026-06-05

### Fixed

- **Anonymiseur — faux respectant le format (en construction/admin)** : (1) un **code postal** « H2K 1E5 » devenait une rue → produit désormais un **faux code postal** valide (« H8H 8N9 »), tandis qu'une adresse de rue reste une rue. (2) les **dates** gardent le **format de l'entrée** : « 12 mars 1982 »→« 24 mai 1958 » (J mois AAAA), « 2023-05-15 »→« AAAA-MM-JJ », « 15/05/2023 »→« JJ/MM/AAAA ».
- **Anonymiseur — passage à l'étape 2 remonte en haut de l'outil** : « J'ai la réponse de l'IA → » faisait rester dans le footer → `scrollIntoView` de la nav d'étapes au changement d'étape.

### Added

- **Anonymiseur — valeur de remplacement personnalisée** : la bulle de sélection offre, à côté de « 🕵️ Anonymiser » (auto), un bouton **✎** qui ouvre un champ pour **saisir sa propre valeur** de remplacement (préremplie d'une suggestion) → règle sur mesure. Validé **E2E Playwright 4/4** (code postal, dates FR/ISO format-préservé, valeur perso « 120/80 », scroll remonté).

## [1.65.31] - 2026-06-05

### Fixed

- **Anonymiseur — pseudonyme incohérent en anonymisation MANUELLE (bug critique, en construction/admin)** : sélectionner « Jean-François Tremblay » ou « 12 mars 1982 » donnait un nom **d'entreprise** (« Groupe Solva »…). Cause : `guessCategory()` échouait sur les noms à trait d'union et les dates → catégorie `other` → faux d'entreprise ; et la catégorie `id` (RAMQ/permis) tombait aussi sur « entreprise ». Fix : `guessCategory` **réutilise le moteur de détection** sur le passage sélectionné (nom→name, date→date, RAMQ→id, courriel→email, tél→phone, adresse→address) ; `generateFake('id')` masque chiffres **et** lettres en gardant le format (RAMQ « TREM 8203 12 01 »→« ODWL 6764 33 54 », permis « 123456 »→« 864904 »). Vérifié : nom→faux nom, date→fausse date, RAMQ→numéro masqué — plus aucune entreprise parasite.

### Added

- **Anonymiseur — bulle contextuelle « 🕵️ Anonymiser » à la sélection** (anonymisation manuelle enfin intuitive). Recherche juin 2026 (W3C/Notion) : **hybride** (option 96/100) = bouton fixe conservé **+** bulle flottante qui apparaît juste au-dessus du passage sélectionné à la souris (pattern Medium/Notion), même action, avec l'extrait sélectionné dans le libellé. Consigne d'amorçage clarifiée. Validé **E2E Playwright** (vraie sélection souris → bulle positionnée → clic → bonne catégorie, 10/10).

## [1.65.30] - 2026-06-05

### Added

- **Anonymiseur — champs auto-extensibles + plein écran (en construction/admin)** : sur un long texte, les champs (texte source, aperçu anonymisé, réponse IA, résultat) **s'allongent automatiquement** avec le contenu (auto-resize sur saisie + après détection/anonymisation/restauration), **sans scrollbar interne** — la page défile, la barre d'actions reste collante/accessible. Recalcul au redimensionnement de la fenêtre. Le bouton **plein écran** existant (API Fullscreen native) est conservé pour donner toute la largeur/hauteur. Validé **E2E Playwright** : #anonSource 216px→2936px sur 40 lignes, output étendu, zéro scroll interne, recalcul responsive OK.

## [1.65.29] - 2026-06-05

### Fixed

- **Anonymiseur — 3 bugs corrigés + simplification UI (audit UX/UI complet, en construction/admin)**. Audit fonctionnel Playwright (texte médical réel) + recherche pp_search (heuristiques Nielsen, WCAG 2.2, tendances juin 2026, options notées /100).
  - **BUG détection (moteur)** : la regex captait « Bonjour Dr » (salutation+titre) et ratait « Dr Lavoie ». Réécriture de `detectEntities` : gestion des **titres de civilité** (Dr/M./Mme/Me/Pr → capture le nom : « Dr Lavoie »→« Lavoie », « Dr Louise Gagnon »→« Louise Gagnon »), **stopwords de salutation** (Bonjour/Merci/Est/Ouest…), **prénoms composés** (« Jean-François Tremblay »), + nouvelles entités **RAMQ**, **code postal**, **n° de permis/matricule**. Zéro faux positif sur le texte médical.
  - **BUG sélection (UI)** : « Anonymiser la sélection » ne marchait pas car le clic du bouton **effaçait la sélection** avant lecture. Fix : **capture continue** de la sélection (mouseup/keyup/select) → on peut enchaîner plusieurs sélections manuelles.
  - **BUG réinitialisation** : « Réinitialiser » laissait des règles fantômes. Fix : purge `localStorage` + retour en mode édition → **état vierge garanti** et réutilisable immédiatement.
- **Anonymiseur — surcharge de boutons remplacée par un menu « ⋯ Actions »** (tendance 2026, option 96/100) : toolbar réduite à **Détecter** + **Anonymiser la sélection** + menu accessible (WAI-ARIA `role=menu`, Échap, clic-extérieur) regroupant Tout anonymiser · Modifier le texte · Mode · Réinitialiser. Légende clarifiée (souligné=à anonymiser / surligné=anonymisé, cliquer pour basculer). Validé **E2E Playwright** (3 bugs corrigés + menu + toggle, 0 erreur JS).

## [1.65.28] - 2026-06-05

### Removed

- **Anonymiseur — élimination de la dette technique de l'ancienne version** : suppression des 13 assets devenus **morts** après la refonte (plus référencés par la vue) : `app.js`, `enhancements*.js` (×7), `sw.js`, `manifest.webmanifest` (local à l'outil), `styles.css`, `detect-panel.css`, `compromise.min.js` (351 Ko). Le dossier ne garde que les 3 fichiers actifs (`anonymizer-core.js`, `anonymizer-ui.js`, `anon-v2.css`). Assets partagés **non touchés** (`tiptap-frontend.js`, `/manifest.webmanifest` global). Rollback git garanti.

### Fixed

- **Anonymiseur — désinscription de l'ancien Service Worker** : snippet ajouté à la vue qui désinscrit toute registration de SW scope `/outils/anonymiseur` (l'ancien `sw.js` network-first, retiré) et purge ses caches → garantit que les utilisateurs (admin) voient la version actuelle, pas une version périmée servie par le SW.
- **Test `AnonymiseurToolTest` aligné sur la refonte** : les assertions vérifiaient les anciens marqueurs/assets (`#sourceText`, `app.js`, `styles.css`, `enhancements.js`) cassés par la refonte → mises à jour vers les nouveaux (`#anonSource`, `#anonAnnotated`, `#anonOutput`, `#btnRestore`, `anonymizer-core.js`, `anonymizer-ui.js`, `anon-v2.css`). CI (MySQL migré) repasse au vert.

## [1.65.27] - 2026-06-05

### Added

- **Anonymiseur — mode optionnel « jetons stables » (défaut OFF, en construction/admin)** : nouveau bouton de bascule dans la toolbar (🎭 Réaliste ↔ 🏷️ Jetons). En mode jetons, les données deviennent des balises stables `[PERSONNE_1]`, `[DOSSIER_1]`, `[ADRESSE_1]`, etc. (même donnée → même jeton, numérotation continue, aucune sous-règle) — **restauration la plus fiable** même quand l'IA reformule beaucoup (recommandation recherche juin 2026). Consigne affichée : « demandez à l'IA de garder les jetons intacts ». Le **mode réaliste reste le défaut** (comportement inchangé) ; basculer régénère les règles existantes dans le nouveau mode. Persisté (localStorage `lv_anon_mode`). Moteur : `buildRules(selections, {mode, existing})` + `tokenLabel()`. Validé Node (2 modes + numérotation stable + non-régression pseudo) + **E2E Playwright 10/10** (activation, jetons, restauration 3/3, aller-retour réaliste↔jetons↔réaliste).

## [1.65.26] - 2026-06-05

### Changed

- **Anonymiseur — refonte UX en éditeur annoté inline (en construction/admin)** : l'empilement vertical (textarea + boutons + détections) était difficile à travailler. Nouveau paradigme validé par la recherche juin 2026 (Microsoft Presidio inline highlights + WAI-ARIA toolbar, options notées /100, choix 97/100) : **le texte source est la surface de travail**. Les données repérées sont **soulignées** (« sera anonymisé »), un **clic** les **surligne** (« anonymisé ») et inversement ; barre d'outils **collante** (Détecter · Anonymiser la sélection · Tout anonymiser · Modifier le texte · Réinitialiser), **aperçu anonymisé en direct côte-à-côte** (empilé sur mobile). La **sélection d'un passage** + bouton anonymise directement (remplace définitivement l'ancienne popup Tiptap). Navigation simplifiée à **2 étapes** (Anonymiser → Restaurer). Accessibilité : entités focusables (role=button, Entrée/Espace), toolbar ARIA. Zéro Tiptap, zéro popup native. Moteur `anonymizer-core.js` inchangé. Validé **E2E Playwright 15/15** (détection, clic souligné↔surligné, aperçu live, tout anonymiser, sélection, aller-retour, basculement inverse).

## [1.65.25] - 2026-06-05

### Added

- **Anonymiseur — « Anonymiser la sélection » (sélection native, en construction/admin)** : retour du geste « sélectionner un passage du texte puis l'anonymiser » qui causait beaucoup de bugs dans l'ancien outil (popup Tiptap en conflit avec la détection auto). Réimplémenté proprement sur le **textarea natif** (`selectionStart/End`) : sélectionner du texte → bouton « ✍️ Anonymiser la sélection » préremplit la règle manuelle (texte + choix du type) → coexiste sans conflit avec la détection automatique (règles dédoublonnées, tri longueur décroissante anti-chevauchement). **Zéro Tiptap, zéro popup native.** Moteur : la catégorie « Autre »/organisation génère désormais un **faux réaliste** (entreprise fictive) au lieu de `***`, donc réversible. Validé : moteur Node + **E2E Playwright combiné (auto + sélection + restauration) 8/8**.

## [1.65.24] - 2026-06-05

### Changed

- **Anonymiseur — refonte complète du moteur (réversibilité fiable, en construction/admin)** : l'aller-retour échouait car la restauration cherchait les valeurs factices par **correspondance exacte** dans la réponse IA reformulée. Reconstruction « simple d'abord » inspirée de l'ancien outil éprouvé : nouveau moteur pur `anonymizer-core.js` (détection regex FR/QC : nom, n° de dossier, adresse, courriel, téléphone, montant, date ; pseudonymes réalistes québécois ; **sous-règles nom complet + prénom seul + nom seul**) + restauration **durcie** (regex bornée **insensible à la casse ET aux accents**, espaces flexibles, tri longueur décroissante) → survit à la reformulation IA et aux variantes (« Dubé » seul, minuscules). Nouveau contrôleur `anonymizer-ui.js` (vanilla, toasts du thème, zéro popup native) + vue Blade **simplifiée** (3 étapes, textareas) qui **retire la couche fragile** (Tiptap, PWA/Service Worker, 7 scripts d'enhancement). Validé : moteur testé en Node + **E2E Playwright navigateur 100 %** sur l'exemple de référence (dossier #86734 / Jean Dubé / 15 rue de la gare → anonymisé → réponse IA reformulée → désanonymisé exact). Reste `is_under_construction=true` (visible admin seulement).

## [1.65.23] - 2026-06-05

### Added

- **Nouveau terme au glossaire IA : « CTAP (Client to Authenticator Protocol) »** (catégorie Sécurité et éthique, type technique) — protocole de la **FIDO Alliance** définissant le dialogue **plateforme↔authentificateur** (navigateur/OS ↔ clé de sécurité, téléphone) sur USB/NFC/BLE. C'est la **2e brique de FIDO2**, complémentaire de **WebAuthn** (qui gère le côté navigateur↔site web). Fait vérifié : **CTAP1 = ancien FIDO U2F (2FA) ; CTAP2 = version FIDO2 sans mot de passe (CBOR, clés résidentes)**. Relié au **knowledge graph bidirectionnel** (CTAP `broader`=fido2 ↔ FIDO2 `narrower`=ctap) et renvoie à WebAuthn et aux YubiKey/clés de sécurité. Image Gemini 1200×669 (`ctap.jpg` og:image + `ctap.webp`), sources vérifiées (FIDO Alliance, Wikipedia). **Cluster FIDO2 désormais complet : ses 4 enfants (passkey, WebAuthn, YubiKey, CTAP) sont maillés.**

## [1.65.22] - 2026-06-05

### Added

- **Nouveau terme au glossaire IA : « YubiKey »** (catégorie Sécurité et éthique, type outil) — **clé de sécurité matérielle** de Yubico, authentificateur physique **multi-protocole** (FIDO2/WebAuthn, FIDO U2F, OTP, PIV, OpenPGP) pour l'authentification forte (2FA/MFA) et la connexion sans mot de passe ; formats USB-A/USB-C/NFC/Lightning, activation par **contact tactile** (présence humaine, anti-hameçonnage). Fait vérifié : **Yubico fondée en 2007, première YubiKey en 2008**. Reliée au **knowledge graph bidirectionnel** (YubiKey `broader`=fido2 ↔ FIDO2 `narrower`=yubikey) et renvoie à WebAuthn et aux passkeys (qu'une YubiKey peut stocker). Image Gemini 1200×669 (`yubikey.jpg` og:image + `yubikey.webp`), sources vérifiées (Yubico, Wikipédia).

## [1.65.21] - 2026-06-04

### Added

- **Nouveau terme au glossaire IA : « WebAuthn (Web Authentication API) »** (catégorie Sécurité et éthique) — **API standardisée par le W3C** (avec la FIDO Alliance) permettant aux navigateurs d’authentifier **sans mot de passe** par cryptographie à clé publique, exposée via `navigator.credentials`. C’est la **brique web** de FIDO2 (côté navigateur/serveur), complémentaire de CTAP (côté authentificateur). Fait vérifié inclus : **recommandation officielle du W3C depuis mars 2019**. Relié au **knowledge graph bidirectionnel** (WebAuthn `broader`=fido2 ↔ FIDO2 `narrower`=webauthn) et renvoie aux passkeys. Pour éviter le conflit, « WebAuthn » a été **retiré des aliases de FIDO2** (il a désormais sa propre fiche). Image Gemini 1200×669 (`webauthn.jpg` og:image + `webauthn.webp`), sources vérifiées (W3C, MDN).

## [1.65.20] - 2026-06-04

### Added

- **Nouveau terme au glossaire IA : « passkey (clé d'accès) »** (catégorie Sécurité et éthique) — identifiant d'authentification **sans mot de passe** basé sur FIDO2, déverrouillé par biométrie/NIP, synchronisable entre appareils (iCloud, Google). Relié à FIDO2 via le **knowledge graph bidirectionnel** (passkey `broader`=fido2 ↔ FIDO2 `narrower`=passkey). Pour éviter le conflit, « passkey » et « clé d'accès » ont été **retirés des aliases de FIDO2** (ils appartiennent désormais au terme passkey). Contenu cross-référençant FIDO2 et le mot de passe. Image Gemini 1200×669 (`passkey.jpg` og:image + `passkey.webp`), sources vérifiées (FIDO Alliance, Wikipédia).

## [1.65.19] - 2026-06-03

### Added

- **Nouveau terme au glossaire IA : « FIDO2 »** (catégorie Sécurité et éthique) — standard d'authentification **sans mot de passe** (WebAuthn + CTAP, cryptographie à clé publique, **résistant au hameçonnage** car les clés sont liées au domaine du site). Synonymes/notions proches en **aliases** (WebAuthn, passkey, clé d'accès, clé de sécurité FIDO2). Contenu cross-référençant mot de passe / OTP / MFA sans les redéfinir. Définition, analogie, exemple, « le saviez-vous », FAQ (Schema.org), sources vérifiées (IBM, Wikipedia), JSON-LD. Image Gemini 1200×669 (`fido2.jpg` og:image + `fido2.webp`).

## [1.65.18] - 2026-06-03

### Added

- **Nouveau terme au glossaire IA : « MFA (authentification multifacteur) »** — traité comme **entité distincte** du 2FA (anti-duplication, approche entity-based 2026) : les vrais synonymes (« authentification multifacteur », « multi-factor authentication ») sont des **aliases** (pas de pages dupliquées), et MFA est relié au 2FA via le **knowledge graph Schema.org bidirectionnel** (MFA `narrower` = 2fa, 2FA `broader` = mfa) avec un lien visible vers /glossaire/2fa. Le contenu renvoie au 2FA (cas particulier à 2 facteurs) sans le redéfinir. Image Gemini 1200×669 (`mfa.jpg` og:image + `mfa.webp`), 3 catégories de facteurs (savoir/posséder/être), sources vérifiées (Wikipédia, Pensez cybersécurité Canada).

## [1.65.17] - 2026-06-03

### Added

- **Nouveau terme au glossaire IA : « SSO (authentification unique) »** (catégorie Sécurité et éthique) — mise en page identique aux autres termes : définition, analogie, exemple concret, « le saviez-vous », FAQ (Schema.org), sources vérifiées (Wikipédia, Okta), réponse AEO en une phrase, JSON-LD. **Image** générée via Gemini (`gemini-2.5-flash-image`), recadrée au standard **1200×669**, déclinée en **`sso.jpg`** (og:image — compatible réseaux sociaux) + **`sso.webp`** (affichage), compressées (~40 Ko / ~16 Ko).

## [1.65.16] - 2026-06-03

### Added

- **Badge « 🚧 Bientôt » sur les outils en construction (liste `/outils`)** : la carte d'un outil dont `is_under_construction = true` affiche désormais un badge « Bientôt » (accent marque, blanc AAA), au lieu de rester sans indication (le champ `under_construction` du composant carte était figé à `false`). L'outil **reste listé** ; sa page affiche « En construction » pour le public tandis que le super-admin garde l'accès complet (amélioration/corrections). Premier cas : l'anonymiseur.

## [1.65.15] - 2026-06-03

### Added

- **Lien LinkedIn dans les liens sociaux** : ajout du profil LinkedIn (Stéphane Lapointe) à côté de Facebook et Messenger, dans la barre du haut (header) et le footer « Communauté ». URL servie par `lv_social('linkedin')` (setting `social.linkedin_url` mis à `https://www.linkedin.com/in/lapointestephane/` + défaut du helper corrigé).

## [1.65.14] - 2026-06-03

### Changed

- **Boutique en maintenance — retrait des liens résiduels** : pendant `SHOP_MAINTENANCE=true`, les liens « Boutique » du menu et du footer s'affichaient encore pour les super-admins (bypass de test). Le bypass est retiré côté menu → liens cachés pour tous. De plus, l'entrée « Mes commandes » (lien `/boutique/...` qui menait à un 503) est filtrée du menu utilisateur pendant la maintenance. Cohérent avec l'icône panier déjà masquée (1.65.13). Entièrement réversible : tout réapparaît quand `SHOP_MAINTENANCE=false`. Le super-admin garde l'accès direct via `/admin/shop` et l'URL `/boutique` (le middleware le laisse passer).

## [1.65.13] - 2026-06-03

### Fixed

- **Icône panier visible alors que la boutique est désactivée** : le mini-cart du header était inclus sans tenir compte du kill switch `SHOP_MAINTENANCE`. Inclusion désormais gatée par `@unless(config('shop.maintenance'))` → l'icône panier disparaît du menu tant que la boutique est en maintenance (réversible : réapparaît quand `SHOP_MAINTENANCE=false`). Cohérent avec les liens « Boutique » déjà masqués.

## [1.65.12] - 2026-06-03

### Fixed

- **Page publique « Collections de la communauté » (`/collections`) — cartes trop larges / débordement** : même cause que `/user/collections`, la grille Bootstrap `col-md-4` débordait le `.container` (4ᵉ carte coupée au bord). Remplacée par une **grille CSS responsive** (`repeat(auto-fill, minmax(280px, 1fr))`) contenue dans le conteneur → plus de débordement, cartes bien alignées.

## [1.65.11] - 2026-06-03

### Fixed

- **Page « Mes collections » (`/user/collections`) — mise en page incohérente / cartes trop larges** : la vue utilisait le layout générique `fronttheme::layouts.master` (pleine largeur, sans la sidebar « Mon espace ») avec une grille Bootstrap `col-md-4` qui débordait, contrairement aux autres pages de l'espace utilisateur. Migrée vers `auth::layouts.user-frontend` (sidebar + colonne centrée) avec une **grille CSS responsive** (`repeat(auto-fill, minmax(230px, 1fr))`) → plus de débordement, rendu aligné sur les autres pages (favoris, contributions, sauvegardes).

## [1.65.10] - 2026-06-03

### Changed

- **Menu — compteur dynamique d'acronymes** : dans la variante de méga-menu « Référence », l'entrée « Acronymes éducation » affichait le texte fixe « Sigles du Québec » au lieu d'un compteur, contrairement aux autres références (Glossaire, Répertoire). Ajout de `$acronymsCount` (cache 3600s, même pattern que `$dictionaryCount`/`$directoryCount`) → affiche désormais « N acronymes du Québec ».

## [1.65.9] - 2026-06-03

### Fixed

- **Erreur 500 sur `/mes-favoris`** : le modèle `Bookmark` (`$timestamps = false`, sans `$casts`) renvoyait `created_at` comme **chaîne**, donc `$bookmark->created_at?->format('d/m/Y')` dans la vue déclenchait *« Call to a member function format() on string »* (le `?->` ne protège que `null`, pas une string). Ajout de `protected $casts = ['created_at' => 'datetime']` → `created_at` redevient un `Carbon` en lecture. Vérifié par rendu complet de la vue (date affichée, aucune exception).

## [1.65.8] - 2026-06-03

### Changed

- **Taille des « ? » d'aide inline (outils)** : les boutons d'aide circulaires inline (à côté des libellés de champs, `.ct-btn-xs`) passent de 44px à **24×24** (cercle, conforme WCAG 2.2 AA — exception « cible inline »), pour un rendu plus léger. Les boutons icône de barre d'outils (`.ct-btn-icon`) restent à **44px AAA**. Suite du correctif ovales→cercles (1.65.7).

## [1.65.7] - 2026-06-03

### Fixed

- **Boutons icône ovales → cercles (tous les outils)** : les boutons icône circulaires (`border-radius:50%`) des outils — notamment les « ? » d'aide — apparaissaient **ovales** car le composant `x-core::button` impose `.ct-btn { min-height: 44px }` (cible tactile WCAG 2.2 AAA), ce qui étirait la hauteur de boutons à largeur fixe (32/22px). Correctif dans `charte.css` : `.ct-btn-icon` et tout `.ct-btn[style*="border-radius:50%"]` forcés à `width = height = 44px` → **cercle parfait, conforme AAA**. Couvre les 6 outils concernés (constructeur-prompts, code-qr, liens-google, roue-tirage, simulateur-fiscal, anonymiseur). Vérifié visuellement (44×44, ratio 1:1).

## [1.65.6] - 2026-06-02

### Fixed

- **Contraste WCAG 2.2 AAA — newsletter digest-weekly** : les boutons CTA cyan (`#3dc9d8`) situés dans les blocs à fond foncé (ex. « Construire mon prompt → », « Raccourcir un lien → ») héritaient de la règle générique « liens sur fond foncé » qui force le texte en cyan clair `#5eead4` → bouton cyan-sur-cyan illisible. Ajout d'une règle CSS plus spécifique (sélecteur sur l'attribut `background-color`) qui restaure le texte foncé `#0c1427` sur ces boutons (**9.21:1 = AAA**), sans toucher les liens texte (qui restent `#5eead4`).

## [1.65.5] - 2026-06-02

### Added

- **Générateur de prompt newsletter — menus déroulants cherchables + facettes** : les 6 sections « contenu du site » (Actualité vedette, Top actualités, Outil de la semaine, Terme IA, Article de blogue, Outil interactif) passent du texte libre à un **combobox cherchable** (recherche AJAX en base, ARIA combobox/listbox, navigation clavier) avec **chips** de sélection (simple ou multiple jusqu'à 5). Les sections Actualités ajoutent des **facettes** : dates (Du/Au) + filtres rapides par **compagnie** (OpenAI, Anthropic, Google, Meta, Mistral, Microsoft, Apple, xAI, DeepSeek — liste en config). Le prompt généré émet directement les **IDs sélectionnés** (`content['tool_id'] = 93`, `content['top_news_ids'] = [2]`) — aucune recherche manuelle requise côté Claude Code.
- Nouveau service `PromptBuilderSearchService` (recherche DB sécurisée : `class_exists()` pour modules désactivables, requêtes paramétrées, contenus publiés uniquement) + endpoint `GET admin/newsletter/prompt-builder/search` (gardé par `permission:view_newsletter` + `throttle:60,1`). Vérifié E2E en local (combobox → suggestions → chip → prompt).

## [1.65.4] - 2026-06-02

### Fixed

- **Anonymiseur — application des règles** : après avoir enregistré une règle, le résultat anonymisé apparaît (bascule auto à l'étape 2) et le mot est surligné dans l'éditeur (décorations Tiptap), au lieu de rien. Le bouton « Effacer » vide maintenant vraiment l'éditeur (visait un élément invisible). Vérifié E2E (vrai drag souris).

## [1.65.3] - 2026-06-02

### Fixed

- **Déploiement des assets compilés (CRITIQUE)** : le rsync de `deploy.yml` excluait `public/build/` → aucun asset Vite recompilé n'arrivait en prod (build figé). Le fix anonymiseur (1.65.2) ne s'appliquait donc pas. Exclude retiré (dossier 100% versionné) ; les assets buildés se déploient désormais.

## [1.65.2] - 2026-06-02

### Fixed

- **Anonymiseur — sélection souris pour anonymiser** : le listener était attaché à un élément `#sourceText` devenu invisible (ghost hors-écran) depuis l'éditeur Tiptap ; désormais câblé sur l'éditeur visible (`.ProseMirror`). Sélectionner du texte ouvre à nouveau la modale de règle. Vérifié E2E.

## [1.65.1] - 2026-06-02

### Changed

- **Prompt newsletter plus précis** : pour chaque section personnalisée, le prompt généré indique maintenant la **forme exacte** attendue dans `NewsletterIssue.content` (éditorial = HTML, défi = structure `wellness_challenge`/`weekly_prompt`, sections par ID = lookup DB). Claude Code CLI remplit chaque section sans deviner.

## [1.65.0] - 2026-06-02

### Changed

- **Générateur de prompt newsletter repensé en « override de sections »** : au lieu d'un prompt libre, il liste les 8 sections du gabarit `digest-weekly` (Éditorial, Défi, Actu vedette, Top actus, Outil, Terme IA, Article blog, Outil interactif), chacune en **Auto** ou **Personnaliser**. Le contenant reste identique ; on ne remplace que les sections choisies, le reste garde le contenu automatique. Le prompt généré cible le `NewsletterIssue` de la semaine (clés réelles de `content`) + l'envoi test. Email test externalisé (`NEWSLETTER_TEST_EMAIL`).

## [1.64.4] - 2026-06-02

### Changed

- **Menu admin Newsletter regroupé** : sous-en-tête de section « NEWSLETTER » + entrées indentées (Vue d'ensemble, Campagnes, Workflows, Templates, Abonnés, Générateur de prompt) pour qu'on voie clairement qu'elles forment un groupe.

### Fixed

- **Suppression de preset (prompt-builder)** : ajoute une modale de confirmation (`confirm-action` du layout admin) — la suppression ne s'exécute plus sans confirmation.

## [1.64.3] - 2026-06-02

### Fixed

- **Scroll infini sur toutes les pages admin** : `infinite-scroll.js` (script du front public) était chargé dans le layout admin et détournait la pagination des listes (annuaire…) → page qui grossit sans fin + icônes d'action vides sur les lignes chargées. Script retiré du layout admin.
- **Bouton « Générer le prompt » (prompt-builder)** : n'apparaissait qu'à l'étape 5 → ajout d'un bouton « Générer » persistant dans l'aperçu, accessible depuis toutes les étapes.

## [1.64.2] - 2026-06-02

### Changed

- **Retrait du dark mode du back-office** (non utilisé ; signalé comme faisant planter Chrome) : mode clair forcé (`data-bs-theme="light"` en dur + nettoyage `localStorage.theme`), JS de bascule `color-modes.js` débranché, toggle supprimé, CSS dark mort retiré. Vérifié sans crash sur toutes les pages admin.

## [1.64.1] - 2026-06-02

### Fixed

- **Dark mode back-office WCAG 2.2 AA** : le branding inline (`--bs-body-bg`/`--bs-app-bg`) en `:root` écrasait le thème sombre → fond blanc et texte illisible (corps 1.46:1, tableaux 1:1). Surcharges branding scopées `:root:not([data-bs-theme="dark"])` + overrides tokens dark conformes AA (corps 12.57:1, bouton primaire 5.28:1, badges 10.14:1). Mode clair inchangé, pas de rebuild d'assets.

## [1.64.0] - 2026-06-02

### Added

- **Générateur de prompt newsletter (back-office)** : page admin `/admin/newsletter/prompt-builder` — assistant multi-étapes (stepper éditable : onglets cliquables + Suivant/Précédent, ARIA tablist, navigation clavier) pour composer un prompt prêt à coller dans Claude Code CLI. 5 étapes (éditorial, défi de la semaine, actualités, sections custom, options + courriel test), aperçu live, copie en 1 clic (toast), presets réutilisables (note pour la prochaine newsletter). Toute section laissée vide → le prompt instruit l'IA d'appliquer le comportement automatique par défaut. Permissions granulaires, throttle, validation liste blanche, structure newsletter best-practice intégrée.

## [1.63.28] - 2026-06-02

### Fixed

- **Courriels « No hint path for [mail] »** : `WelcomeMail` rend désormais `emails.welcome` via `markdown:` (la vue utilise des composants `mail::`) au lieu de `view:`, ce qui initialise le renderer Markdown. Bouton du courriel pointé vers `/dashboard` au lieu de `/admin`.
- **Redirection post-connexion d'un non-admin vers `/admin` (403)** : nouvelle méthode role-aware `User::homeRoute()` (source unique DRY) remplace 3 redirections codées en dur vers `admin.dashboard` dans `TwoFactorChallenge`, `SocialAuthController` et `MagicLinkController::verify`.

## [1.1.0] - 2026-03-02

### Added

**Multi-tenant avancé (module Tenancy)**
- Trait `BelongsToTenant` pour scope automatique des modèles par tenant
- 3 middlewares : identification tenant, scope global, isolation données
- Domaines custom par tenant avec vérification DNS
- Admin centralisé : gestion tenants, domaines, plans, statistiques
- Migration `add_tenant_id_to_tables` pour les tables existantes

**Marketing automation (module Newsletter)**
- Workflows email automatisés (drip campaigns, séquences)
- Modèles `EmailWorkflow`, `WorkflowStep`, `WorkflowEnrollment`, `WorkflowStepLog`
- Templates marketing avec éditeur visuel
- Enrollments automatiques basés sur événements (inscription, achat, etc.)
- Commande `newsletter:process-workflows` pour traitement planifié
- Admin : gestion workflows, templates, statistiques d'envoi

**API GraphQL v2 (Lighthouse)**
- Endpoint `/graphql` avec schema-first approach
- Queries : articles, categories, pages, FAQ, subscribers
- Mutations : CRUD articles, gestion newsletter, contact
- Authentification Sanctum via directive `@guard`
- Pagination relay cursor-based
- Sécurité : query depth limiting, introspection désactivée en production

**Module Team**
- Organisations multi-utilisateurs avec invitations
- Rôles par équipe (owner, admin, member)
- Gestion des membres et permissions

**Commandes**
- `app:audit` : audit complet du projet (sécurité, performances, qualité)
- `make:crud {module} {model}` : générateur CRUD avec options `--fields=`, `--with-api`, `--force`

**Polish CMS (P1-P8)**
- Content versioning : trait `HasRevisions`, `ContentRevision` model, diff et restauration (max 50 par contenu)
- Scheduled publishing : trait `HasScheduledPublishing`, champs `published_at`/`expired_at` sur Article, StaticPage, FAQ
- URL redirections : modèle `UrlRedirect` dans SEO, exact + wildcard, compteur de hits, admin CRUD
- Announcements/changelog : modèle `Announcement` dans Core, admin CRUD, page publique `/changelog`
- Breadcrumbs dynamiques : `@yield('breadcrumbs')` dans admin layout, 14 vues enrichies
- Media manager : métadonnées SEO (titre, alt_text, légende, description), dossiers, compression WebP (6 conversions), composant `<x-media::picture>`
- Preview avant publication : aperçu articles et pages sans publier, bannière admin, bouton dans les formulaires d'édition

### Changed
- Tests : 2463 → 2734+ tests (0 échec)
- Modules : 33 → 34 (ajout Team)
- Permissions : 39 → 43
- Feature flags enrichis dans `core:new-project` avec catégories de modules

## [1.0.0] - 2026-03-01

### Added

**Modules (34 total)**
- RBAC: 39 permissions, 4 roles (super_admin, admin, editor, user), Gate::before super_admin, per-route middleware
- Stripe billing: plans, checkout, trial, webhooks, cancellation flow (Laravel Cashier)
- Blog: articles, categories, tags, comments, media picker, TipTap rich editor
- CMS / Pages: static pages with template support, configurable homepage (landing or static page)
- Newsletter: subscriber management, campaigns, unsubscribe flow
- FAQ: CRUD admin, public page, JSON-LD Schema.org structured data
- Menu: drag-and-drop builder (SortableJS), cache, Blade component for frontend
- Widgets: configurable dashboard widgets per role
- Form builder: dynamic forms with field types, submissions storage
- Custom fields: attach arbitrary fields to any entity
- Import / Export: CSV/XLSX import-export with queue support
- A/B testing: variant management and conversion tracking
- AI module: OpenRouter integration (chat, article generation, moderation, SEO, translation)
- PWA: manifest, service worker, install prompt
- Push notifications: Web Push (VAPID), Reverb WebSocket channel
- Two-factor authentication: TOTP (Google Authenticator compatible)
- Social login: OAuth2 via Laravel Socialite (Google, GitHub)
- GDPR compliance: personal data export and anonymization commands
- Session management: active session list, remote session revocation
- Password policy: HIBP breach check, complexity rules, expiry
- Email notifications: trial ending, payment succeeded/failed, subscription cancelled
- Contact messages: storage, admin UI (read/unread, filters, detail view)
- Search: Laravel Scout integration (Meilisearch / database driver)
- Media: Spatie Media Library, admin media picker, upload API
- Editor: TipTap with image upload, link, code block extensions
- Backups: automated backups with Spatie Backup, admin restore UI
- Health: system health checks dashboard
- Logging: structured log viewer with level filter and tail mode
- Tenancy: multi-tenant scaffolding (single database)
- Storage: S3-compatible driver support, presigned URLs
- Translation: UI string management, locale switcher
- SEO: meta tags, Open Graph, JSON-LD service, sitemap
- SaaS: plan comparison page, usage metering, upgrade/downgrade flow
- Webhooks: outgoing webhook delivery with retry and log

**Security**
- Content Security Policy (CSP) headers
- HTTP Strict Transport Security (HSTS)
- XSS filtering via mews/purifier on all rich-text inputs
- Honeypot on public forms
- Rate limiting on login, registration, API endpoints
- IP blocking (admin-managed blocklist)
- Audit logging for sensitive admin actions

**Developer experience**
- PHPStan level 6, 0 errors
- 2655+ tests (Pest 3, parallel execution)
- Playwright E2E test suite
- Docker Compose setup for local development
- CI/CD pipeline (GitHub Actions): Pint, PHPStan, tests
- Makefile shortcuts: `make test`, `make check`, `make check-quick`
- Artisan commands: `app:install`, `app:demo`, `app:status`, `app:check`, `app:make-module`, `app:logs`, `app:setup-hooks`
- NobleUI Bootstrap 5.3.8 admin theme with Lucide icons
- Authero guest theme (Tailwind, Tabler icons)
- GoSass frontend theme

**Architecture**
- `BaseRouteServiceProvider` shared by all modules (DRY route registration)
- `SettingsReaderInterface` in Core module, implemented by Settings module (Core/Settings decoupled)
- Plugin manifest (`plugin.json`) per module for metadata and dependency declaration
- Theme resolution in module ServiceProviders (theme-aware view loading)

[Unreleased]: https://github.com/memora-solutions/laravel-saas/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/memora-solutions/laravel-saas/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/memora-solutions/laravel-saas/releases/tag/v1.0.0
