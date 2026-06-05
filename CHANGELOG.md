# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
