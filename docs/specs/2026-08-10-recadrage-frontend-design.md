# Design doc : recadrage Canva-style du point focal, coté public (annuaire)

- **Auteur** : MEMORA solutions
- **Module** : `Core` (nouveau composant partagé) + `Directory` (intégration publique)
- **Statut** : approuvé par le client - ce document spécifie l'implémentation, il ne rouvre pas les choix.
- **Dépend de** : `docs/specs/2026-08-10-screenshots-annuaire-design.md` (Brique 1, déjà livrée en v1.160.0 - master 1200x1400, `screenshot_focal_y`, route `admin.directory.set-focal`, `ScreenshotFocalService::deriveThumbnail()`). Ce doc n'y touche pas.

## 1. Contexte

Le point focal existe déjà côté **admin** (`Modules/Directory/resources/views/admin/edit.blade.php:197-312`, curseur Alpine avec `maxFocalY` codé en dur à `770`). Deux lacunes :

1. Aucune façon **visuelle type Canva** de choisir le cadrage (l'UI admin actuelle est un slider + drag brut, sans aperçu "zones exclues estompées").
2. Le point focal est **invisible côté public** : un modérateur qui consulte une fiche doit aller sur `/admin/directory/{id}/edit` pour corriger un cadrage raté. La capture assistée FAB de la fiche publique (`Modules/Directory/resources/views/public/show.blade.php:160-189`, composant `Modules/Core/resources/views/components/screenshot-capture.blade.php`) fait toujours un **crop centré imposé** (`screenshot-capture.blade.php:105-128`), sans jamais passer par le point focal.

## 2. Portée (3 volets)

### Volet A - Composant partagé `x-core::focal-cropper`

Nouveau fichier `Modules/Core/resources/views/components/focal-cropper.blade.php`, autonome (comme `screenshot-capture.blade.php` : markup + `<script>` inline, garde de ré-enregistrement `window.__focalCropperRegistered`). Rendu : `<dialog>` plein écran (`showModal()`, focus piégé nativement par le navigateur - même mécanisme que `core-capture-dialog` existant, aucune lib de focus-trap requise).

**Structure visuelle** : deux `<img>` identiques superposées dans un conteneur `.fc-track` dont l'`aspect-ratio` est fixé en JS à `1200 / masterHeight` :
- couche du bas, opacité `0.35` (zones exclues) ;
- couche du haut, `clip-path: inset(top% 0 bottom% 0)` recalculé à chaque frame de glissement pour ne montrer QUE la bande 630px nette (le cadre).
- `.fc-frame-indicator` : `div` `pointer-events:none` positionné/dimensionné sur la même bande, bordure blanche + grille des tiers (2 lignes H + 2 lignes V à 33/66%).

**Aucun `src` dans le markup statique** (`src=""` au chargement) - injecté uniquement par `open()`. Rien n'est donc jamais chargé pour un visiteur ni au chargement de page (CA-5).

**Interaction** : pointer events (down/move/up, `touch-action:none` limité à `.fc-track`) pour glisser verticalement ; `keydown` sur le `<dialog>` : `ArrowUp/ArrowDown` = ±10px, `+Maj` = ±1px, `preventDefault()`. Boutons "Enregistrer le cadrage" / "Annuler" (min 44×44px), `Échap` = annuler. Icônes en **SVG inline** (pas `data-lucide` : lucide n'est **pas chargé sur le thème public**, vérifié - seul `screenshot-capture.blade.php` et le FAB de `show.blade.php:163` utilisent déjà du SVG inline pour cette raison ; `show.blade.php:440` a un `data-lucide` mort, hors périmètre). Contrastes AAA : mêmes tokens que le FAB (`--c-primary #064E5A` / blanc), texte de contrôle sur fond sombre semi-opaque.

**Calculs extraits en JS pur testable** : nouveau `public/assets/directory/focal-cropper-math.js` (pattern `module.exports` déjà utilisé par `anonymizer-core.js:761`), chargé via `<script src="{{ asset(...) }}?v={{ config('version.semver') }}">`. Fonctions : `clampFocal(focalY, masterHeight)`, `maxFocal(masterHeight)`, `normalizedMasterHeight(rawHeight)` (= `max(630, min(1400, rawHeight))` - **jamais** `770` en dur), `pointerDeltaToFocalDelta(deltaScreenPx, displayScale)`, `focalPercent`.

**API JS** : `window.FocalCropper.open({ imageSrc, initialFocal, maxHauteurMaster, onSave(focalY) })`. `onSave` retourne une Promise `{ok, message?}` ; le composant ne fait **aucune requête réseau lui-même** - il attend la Promise de l'appelant (un seul enchaînement réseau, décidé par l'appelant), affiche un état "Enregistrement…" pendant l'attente, ferme le dialog si `ok:true`, affiche `message` inline et laisse le dialog ouvert si `ok:false`/exception.

### Volet B - Porte 2 : bouton "Recadrer" sur la fiche publique

- `PublicDirectoryController::show()` (`Modules/Directory/app/Http/Controllers/PublicDirectoryController.php:159-231`) : avant le `return view(...)` (ligne 230), calcule `$hasScreenshotMaster`, `$screenshotMasterUrl`, `$screenshotMasterHeight` en reprenant le pattern déjà en place dans `DirectoryAdminController::edit()` (`Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php:118-121`) + `getimagesize()` pour la hauteur (pattern déjà utilisé ailleurs : `Modules/Directory/app/Services/ScreenshotService.php:372`). Passées au `compact(...)`.
- `show.blade.php`, bloc vignette (`:407-419`) : dans le `@if($tool->screenshot)`, ajoute un bouton overlay `@can('moderate_tools')` **et** `$hasScreenshotMaster` : "Recadrer" (SVG crop inline), `position:absolute` coin haut-droit de l'image, ouvre `FocalCropper.open({ imageSrc: screenshotMasterUrl, initialFocal: tool.screenshot_focal_y, maxHauteurMaster: screenshotMasterHeight, onSave })`. `onSave` : `fetch` POST `route('admin.directory.set-focal', $tool)` (CSRF depuis `meta[name=csrf-token]`, déjà présent sur le thème public - `Modules/FrontTheme/resources/views/layouts/master.blade.php:10`) → met à jour `img.src` avec `data.screenshot_url` + **`window.toast(data.message, 'success')`**.
- Si `@can('moderate_tools')` mais **pas** de master : mini-lien discret "Cadrage indisponible - capturer d'abord" qui appelle `document.getElementById('core-capture-dialog')?.showModal()` (dialog existant `show.blade.php:165-176` ; invariant vérifié : tout rôle avec `moderate_tools` a aussi `view_admin_panel`, donc le dialog existe toujours - `Modules/Directory/database/seeders/DirectoryModeratorRoleSeeder.php:19-25`).
- Le composant `<x-core::focal-cropper />` est inclus **une fois**, dans un bloc `@can('moderate_tools')` séparé (jamais rendu pour un visiteur).

**Écart assumé vs l'énoncé initial** : le toast utilise `window.toast(...)` (helper front global, `Modules/FrontTheme/resources/views/layouts/master.blade.php:516`, écouté par `<x-core::alert-toast/>` via l'event `toast-show`) et **non** `Livewire.dispatch('toast', ...)`. Vérifié : aucun composant Livewire du thème public n'écoute un event `toast` (grep exhaustif, 0 résultat) - le pattern `Livewire.dispatch('toast', ...)` de `admin/edit.blade.php:244-246` est spécifique au **backoffice** (thème différent, propre listener). L'appliquer tel quel sur la fiche publique produirait un succès réseau **sans aucun toast visible** - régression silencieuse. `window.toast` est le mécanisme réellement câblé sur ce thème.

### Volet C - Porte 1 : capture assistée en mode cadrage (opt-in)

- `screenshot-capture.blade.php` : nouveau prop `framingMode` (défaut `false`, ligne 1-6). `false` → **zéro changement** de comportement (News, Directory admin edit intacts - non touchés).
- `true` (posé uniquement sur l'instance FAB de `show.blade.php:170-175`) : après `grabFrame()` (ligne 100), si `framingMode` : au lieu du crop centré (lignes 105-128), normaliser comme le serveur - `scale` largeur 1200 (ratio conservé), si hauteur résultante `> 1400` tronquer au TOP à 1400 (même règle que `DirectoryAdminController::deriveMasterFromUpload()`, lignes 280-283). Si hauteur normalisée `<= 630` : repli sur le crop centré actuel + message "Fenêtre trop courte pour le cadrage - vignette centrée appliquée." (zone de message existante, ligne 34-39). Sinon : `canvas.toDataURL('image/jpeg', 0.9)` → `FocalCropper.open({ imageSrc: dataURL, initialFocal: 0, maxHauteurMaster: hauteurNormalisée, onSave })`.
- `onSave(focalY)` (deux appels réseau chaînés, comme spécifié) : (1) upload du blob normalisé complet vers `uploadUrl` (réutilise `upload()` existant, lignes 159-202, en le rendant appelable avec un blob externe plutôt que celui du crop centré) ; (2) si `payload.ok`, `fetch` POST `set-focal` avec `focalY` ; puis `finishAndReload()` (ligne 150-157, inchangée). Si l'étape (1) échoue : message d'erreur existant, dialog cropper reste fermé (l'upload a déjà échoué, rien à raffiner). Si (2) échoue après (1) réussi : `onSave` résout `{ok:false, message: "Cadrage non enregistré - réessayez via le bouton Recadrer sur la fiche."}` ; la vignette reste celle de l'upload (comportement demandé).

## 3. Sécurité et accessibilité

- Aucune nouvelle route, aucun nouveau contrôleur : Volet B et C réutilisent `set-focal` et `upload-screenshot`, déjà gatés `auth + EnsureIsAdmin + can:moderate_tools` (`routes/web.php:89-99`) et déjà clampés côté serveur (`DirectoryAdminController::setFocal()` ligne 308, `ScreenshotFocalService::deriveThumbnail()` lignes 54-56 - reborne indépendamment, jamais de confiance dans la valeur client).
- Master jamais exposé au HTML pour un visiteur ni pour un modérateur hors interaction (CA-5).
- Cibles ≥44px, `aria-label` FR sur tous les contrôles, focus natif du `<dialog>`, grille des tiers `aria-hidden="true"` (décorative).

## 4. Tests

- **Pest** `Modules/Directory/tests/Feature/PublicFocalCropperTest.php` (fixtures GD comme `ScreenshotAdminFocalTest.php:49-60`) : CA-1 à CA-4, CA-8.
- **JS pur** `tests/js/focal-cropper-math.test.cjs` (pattern `prompt-verifier-rules-detect.test.cjs`) : CA-6.
- Suites complètes `Modules/Directory` + `Modules/News` + `--filter=Screenshot` : CA-7.

## 5. Critères d'acceptation

- **CA-1** : un visiteur non authentifié chargeant `/annuaire/{slug}` ne reçoit dans le HTML ni bouton "Recadrer" ni le chemin `screenshots/masters/`.
- **CA-2** : un utilisateur `moderate_tools` sur une fiche avec master voit le bouton "Recadrer".
- **CA-3** : un utilisateur `moderate_tools` sur une fiche sans master voit le lien "Cadrage indisponible", jamais le bouton "Recadrer".
- **CA-4** : le rendu invité de la fiche (200, bloc vignette présent) est inchangé après l'ajout des nouvelles variables au contrôleur.
- **CA-5** : le HTML de `focal-cropper.blade.php` ne contient aucun `src` pointant vers un master au chargement (image injectée seulement par `open()`).
- **CA-6** : `clampFocal`/`maxFocal`/`normalizedMasterHeight` bornent correctement pour hauteurs 630, 900, 1400, >1400 (défensif), testé par `node tests/js/focal-cropper-math.test.cjs`.
- **CA-7** : `framingMode` par défaut (`false`) ne modifie aucun comportement existant - suites `Modules/News` et `Modules/Directory` (dont `ScreenshotOverwriteGuardTest.php`) inchangées et vertes.
- **CA-8** : `php artisan test --filter=Screenshot` + `Modules/Directory` + `Modules/News` : 0 échec après implémentation.

## 6. Corrections de revue adversariale (Codex, 2026-08-10)

Une revue indépendante a trouvé 4 défauts réels après l'implémentation initiale. Les 4 sont corrigés :

1. **CRITIQUE - incohérence de gates** : le FAB (`show.blade.php:161-190`) est visible sous `view_admin_panel`, mais `x-core::focal-cropper` n'est rendu que sous `moderate_tools` (`:196`, permission réellement exigée par `set-focal` côté serveur). Un `editor` (`view_admin_panel` sans `moderate_tools`) qui activait `framingMode` aboutissait à "composant indisponible" sans upload - régression du comportement actuel pour ce rôle. **Correctif** : `:framingMode="auth()->user()?->can('moderate_tools') ?? false"` sur l'instance FAB de `show.blade.php` - un `editor` retrouve le crop centré + upload existant, un modérateur obtient le cadrage. Le rendu de `focal-cropper` n'a PAS été élargi (il reste gated `moderate_tools`, seule permission valable côté serveur).
2. **CRITIQUE - master périmé** : quand une capture/upload normalisé fait ≤630px de haut, `deriveMasterFromUpload()` (`DirectoryAdminController.php`) retournait sans rien faire, laissant un ANCIEN master en place - "Recadrer" aurait alors travaillé sur la vieille image et écrasé la nouvelle vignette. **Correctif** : dans ce cas, le master existant du slug est supprimé (`File::delete`, artefact dérivé régénérable, pas une donnée utilisateur) ; le reset du focal à 0 (déjà existant dans `uploadScreenshot()`) reste inchangé.
3. **ÉLEVÉE - WYSIWYG divergent** : le serveur décidait sur la hauteur BRUTE de la source (`$source->height() <= 630`, avant tout scale) alors que le client (Volet C) décide APRÈS normalisation à 1200 de large - une source étroite mais haute (ex. 600x600) ouvrait le cadrage côté client sans produire aucun master côté serveur. **Correctif** : `deriveMasterFromUpload()` fait désormais `scale(width: 1200)` D'ABORD, puis teste la hauteur RÉSULTANTE `> 630` - une source étroite haute devient donc un master valide, exactement comme le calcul déjà en place côté client (`screenshot-capture.blade.php`, branche `framingMode`, vérifié identique : scale sur la largeur puis test de la hauteur obtenue).
4. **MOYENNE - fermeture pendant mutation** : `Échap` et `Annuler` (`focal-cropper.blade.php`) fermaient le dialog même pendant que `onSave()` (upload + set-focal) était en vol, laissant la mutation continuer en silence, invisible et sans recours. **Correctif** : nouvel état `state.saving` - `Échap` et les boutons `Annuler`/X sont des no-op tant que `saving` est vrai, et les boutons `Annuler`/X sont visuellement désactivés (`disabled`, opacité 0.5) pendant l'enregistrement.

Nouveaux tests couvrant ces 4 points : `Modules/Directory/tests/Feature/DeriveMasterFromUploadTest.php` (#2, #3 - preuves négatives : la version d'avant le correctif aurait fait échouer ces deux tests) et 2 tests ajoutés à `PublicFocalCropperTest.php` (#1, assertion sur `framingMode: true/false` selon le rôle).
