# Design doc : point focal et robustesse des captures d'écran de l'annuaire

- **Auteur** : MEMORA solutions
- **Module** : `Directory` (annuaire), avec impact partagé sur `Core` et `News`
- **Statut** : décisions tranchées par panel multi-IA (3 rounds, convergence) - ce document spécifie l'implémentation, il ne rouvre pas le débat des options.
- **Portée** : 4 briques indépendamment déployables.

## 1. Contexte et problème

L'annuaire capture automatiquement une vignette 1200x630 pour chaque outil publié, via trois voies distinctes :

1. **Capture automatique Puppeteer** (`scripts/capture-screenshot.cjs`, orchestrée par `Modules/Directory/app/Services/ScreenshotService.php`).
2. **Capture assistée** dans le navigateur de l'admin (composant partagé `Modules/Core/resources/views/components/screenshot-capture.blade.php`), qui utilise `getDisplayMedia` + `ImageCapture`.
3. **Upload manuel** d'un fichier (`Modules/Core/app/Services/ScreenshotUploadService.php`).

Dans les trois cas, le cadrage final en 1200x630 est **imposé et centré**, sans aucune marge de manoeuvre pour l'admin. Le cas concret qui a motivé ce chantier : la capture assistée grabbe l'onglet partagé puis calcule un crop centré strict avant même l'upload (`screenshot-capture.blade.php:103-129`, canevas figé `width="1200" height="630"` dès la déclaration du `<canvas>`, ligne 44). Sur un site cible dont le header/logo/titre n'est pas exactement au centre vertical de la fenêtre capturée, le crop centré ampute le titre ou le logo - sans que l'admin ait eu la moindre option de recadrage avant l'upload. Le même problème existe côté Puppeteer : `capture-screenshot.cjs:240` fait un `clip: { x: 0, y: 0, width: 1200, height: 630 }` fixe sur un viewport de 1200x1400 (ligne 153) - les 770 px du bas du viewport sont capturés puis jetés sans jamais être exploitables.

Par ailleurs, deux problèmes de robustesse distincts ont été identifiés dans l'audit du code réel :

- La détection de blocage (Cloudflare/CAPTCHA) et les popups/cookies restent fragiles sur certains sites 2026 (bannières `position:fixed` non couvertes par les sélecteurs codés en dur).
- Le garde-fou anti-écrasement actuel (`ScreenshotService.php:89-105`, deux seuils en pourcentage de la taille de fichier) protège contre la régression de qualité constatée lors de l'incident **S79** (221 fichiers écrasés) mais bloque aussi des remplacements légitimes (ex. un site qui change de design et dont la nouvelle capture, plus légère car moins chargée visuellement, est pourtant meilleure).

## 2. Décisions du panel et alternatives rejetées

Le panel multi-IA (3 rounds) a convergé sur les 4 briques détaillées à la section 3. Fenêtre exacte de la décision : **unanimité contre au round 3** sur les alternatives suivantes, écartées explicitement :

- **Sélection multi-candidats** (générer plusieurs crops et laisser l'admin choisir parmi une grille de vignettes) : rejetée - coût de calcul et de stockage disproportionné par rapport au gain, et l'admin doit déjà visualiser puis choisir, ce qu'un curseur de point focal fait plus simplement.
- **Cadrage automatique par analyse du DOM** (repérer le `<h1>`/logo de la page cible et centrer dessus au moment de la capture) : rejetée - fragile face aux sites qui masquent le DOM réel derrière du contenu injecté en JS après capture, complexité d'implémentation élevée pour un gain marginal face à un simple curseur manuel.
- **Recadrage par apprentissage automatique (ML) / détection de saillance visuelle** : rejetée - sur-ingénierie pour un backoffice interne à faible volume (annuaire, pas un produit grand public), aucune donnée d'entraînement disponible en interne, dépendance externe non justifiée.

Le choix retenu (point focal vertical manuel sur une image maître conservée en entier) est le plus simple des quatre options évaluées, le seul qui redonne un contrôle direct et immédiat à l'admin, et le seul qui ne modifie aucun contrat d'affichage public existant.

## 3. Les 4 briques

### Brique 1 - Point focal vertical sur image maître

**Principe** : ne plus jeter l'information capturée hors du cadre 1200x630. Conserver une image maître complète, et permettre à l'admin de choisir QUELLE tranche de 630 px de haut en extraire.

- `scripts/capture-screenshot.cjs:240` doit produire **deux fichiers** par capture réussie plutôt qu'un seul `page.screenshot()` clippé :
  - le **master** : capture du viewport complet 1200x1400 (JPEG qualité 85), stocké dans `public/screenshots/masters/{slug}.jpg` ;
  - la **vignette dérivée** 1200x630, écrite au chemin actuel `public/screenshots/{slug}.jpg` (aucun changement de contrat d'affichage, ni pour `show.blade.php:407-419` ni pour `index.blade.php:1323-1339`).
- Migration additive sur `directory_tools` (voir section 4) : `screenshot_focal_y` (entier non signé, nullable, défaut 0) = décalage vertical en pixels du haut du crop dans le master (0 = haut du master).
- Nouveau service `Modules/Directory/app/Services/ScreenshotFocalService.php`, méthode `deriveThumbnail(Tool $tool): bool` :
  1. lit le master ;
  2. crop 1200x630 à `y = focal_y`, borné à `[0, hauteur_master - 630]` ;
  3. écrit la vignette via la méthode centralisée déjà en place, `ScreenshotService::safeWriteScreenshot()` (`ScreenshotService.php:274-298`, écriture atomique tmp + `File::move`) - pas de nouvelle méthode d'écriture, réutilisation stricte de l'existant ;
  4. met à jour `updated_at` du `Tool` via `saveQuietly()` (cohérent avec le cache-bust `?v=` déjà utilisé partout : `show.blade.php:410`, `index.blade.php:34`) ;
  5. purge Cloudflare ciblée du fichier de vignette.
  - La purge Cloudflare est actuellement une méthode **privée** du contrôleur (`DirectoryAdminController::purgeCloudflareScreenshot()`, lignes 242-258). Pour éviter la duplication (DRY), elle doit être **extraite** en méthode statique partagée (candidat naturel : `ScreenshotService::purgeCloudflareFile(string $relativePath): void`, réutilisant `config('services.cloudflare.zone_id')`/`api_token` déjà lus au même endroit) et appelée à la fois par le contrôleur existant et par `ScreenshotFocalService`.
- UI admin (`Modules/Directory/resources/views/admin/edit.blade.php`, sous le bloc d'aperçu actuel `lignes 182-195`) :
  - si le master existe : un cadre de prévisualisation au ratio 1200:630 affichant le master, `object-position` vertical piloté par Alpine.js (cohérent avec le reste du composant `x-core::screenshot-capture` déjà en Alpine, lignes 8-46 du composant) ;
  - interaction : glisser verticalement à la souris **et** boutons haut/bas accessibles au clavier **et** un `<input type="range">` en secours (parité stricte avec la charte WCAG 2.2 AAA du projet - aucune interaction souris-seulement) ;
  - bouton « Appliquer ce cadrage » → `POST route('admin.directory.set-focal', $tool)` avec `focal_y` ;
  - contrôleur (`DirectoryAdminController`, nouvelle méthode `setFocal`) : valide `focal_y` entre 0 et 770 (borne haute = `1400 - 630`), sauvegarde, appelle `ScreenshotFocalService::deriveThumbnail()`, retourne la nouvelle URL cache-bustée (`asset(...) . '?v=' . $tool->updated_at->timestamp`, même formule que `ScreenshotUploadService.php:83`) ;
  - si le master est absent (anciens outils capturés avant cette brique) : message « Recapture nécessaire pour activer le repositionnement » + réutilisation du bouton « Capturer screenshot (Puppeteer) » déjà présent (`edit.blade.php:198-203`), aucun nouveau bouton.
- **Réinitialisation du focal** : toute nouvelle capture automatique (`ScreenshotService::capture()`) réécrit master + vignette + remet `screenshot_focal_y` à 0. Décision assumée du panel : un ancien focal pointant sur un master remplacé pointerait dans le vide après une refonte du site cible - mieux vaut repartir de 0 et laisser l'admin réajuster que d'appliquer un focal obsolète sur une image différente.
- Le réglage manuel du focal **ne verrouille pas** `screenshot_locked` (`Tool.php:172`, `directory_tools.screenshot_locked`). Le verrou reste réservé à l'upload manuel (`DirectoryAdminController::uploadScreenshot`, ligne 229 : `$tool->screenshot_locked = true;` après un upload réussi) - comportement existant strictement inchangé. Conséquence assumée à documenter dans l'UI : sur un outil non verrouillé, une nouvelle capture automatique planifiée (cron `directory:capture-screenshots`, voir `CaptureScreenshotsCommand.php`) écrase master + vignette + focal sans avertissement, comme c'est déjà le cas aujourd'hui pour la vignette seule.
- **Capture assistée** (`screenshot-capture.blade.php`) : le composant produit aujourd'hui, de façon **hardcodée**, un canevas fixé à `1200x630` (attributs `width`/`height` du `<canvas>` ligne 44, puis `targetW`/`targetH` figés lignes 105-106) - il ne peut structurellement pas fournir une image plus haute que 630 px. Tant que ce composant n'est pas modifié pour capturer un cadre plus haut (hors périmètre de cette brique, voir section 9), **aucun master n'est produit par ce chemin** : le focal reste indisponible pour les captures assistées, message « Recapture nécessaire » identique au cas des anciens outils.
- **Upload manuel** (`ScreenshotUploadService::upload()`, `ScreenshotUploadService.php:49-53`) applique aujourd'hui `->cover(1200, 630)` de façon inconditionnelle et immédiate, sans jamais conserver le fichier source original - et ce service est **partagé avec le module News** (`AdminNewsController.php`) et avec la modération de l'annuaire (`ModerationController.php`). Pour ne pas toucher au contrat partagé (risque de régression News), **`ScreenshotUploadService` reste inchangé**. La dérivation d'un master à partir d'un upload manuel se fait exclusivement côté `DirectoryAdminController::uploadScreenshot()` (lignes 210-240), qui a déjà accès au fichier source brut via `$request->file('screenshot')` avant l'appel au service : si les dimensions du fichier source dépassent 630 px de haut, un master est dérivé séparément (largeur ramenée à 1200 par `cover` horizontal, hauteur conservée jusqu'à 1400 px maximum) et sauvegardé dans `public/screenshots/masters/{slug}.jpg` en plus de l'appel normal à `ScreenshotUploadService::upload()`. Sinon, pas de master : focal indisponible pour cette image, comportement identique à aujourd'hui.
- **Hors périmètre explicite** : le troisième mécanisme d'alimentation de la colonne `screenshot`, la commande `directory:enrich-tools` (`DirectoryEnrichToolsCommand.php:42-49`), stocke directement l'URL externe de l'og:image (`MetaScraperService::captureScreenshot()`, `MetaScraperService.php:138-150`) **sans jamais télécharger de fichier local**. Un outil dans cet état (`str_starts_with($tool->screenshot, 'http')`, déjà testé à plusieurs endroits : `edit.blade.php:187`, `ToolObserver.php:19`) n'a et n'aura pas de master tant qu'il n'a pas subi une vraie capture Puppeteer ou un upload manuel - le focal y est simplement indisponible, aucun changement requis sur ce chemin.

### Brique 2 - Capture automatique stabilisée (`scripts/capture-screenshot.cjs`)

- **Injection CSS ciblée** avant capture, ajoutée aux passes existantes (`dismissCookies`/`dismissPopups`, lignes 27-68) :
  ```
  * { animation: none !important; transition: none !important; caret-color: transparent !important; }
  ```
  Volontairement **sans** `transform: none` global : un `transform` en `!important` casserait les mises en page qui positionnent des éléments par `transform` (carrousels, sticky headers via `translate3d`, etc.) - risque de régression visuelle plus grand que le bénéfice de stabilité. À documenter en commentaire dans le script pour éviter qu'un futur ajout ne l'introduise sans discussion.
- **Masquage géométrique générique**, exécuté APRÈS les passes existantes de `dismissCookies`/`dismissPopups`/`dismissByText` (après la boucle de retry, ligne 191) : tout élément `position: fixed` ou `sticky` dont le rectangle touche un bord du viewport (`top <= 0` ou `bottom >= hauteur_viewport`) **ET** couvre plus de 20 % de la surface du viewport **ET** a un `z-index >= 100` est masqué (`visibility: hidden`). Seuil de 20 % choisi précisément pour ne **jamais** masquer un header légitime de hero (souvent < 15 % de la surface d'un viewport 1200x1400) tout en attrapant les bandeaux plein écran non couverts par les sélecteurs codés en dur (`COOKIE_HIDE`/`POPUP_HIDE`, lignes 20 et 24).
- **Attentes réseau et rendu**, remplaçant partiellement `capture-screenshot.cjs:174` (`waitUntil: 'networkidle2'`, timeout 30 s conservé tel quel) :
  - le statut réel de `page.goto()` est désormais loggué explicitement dans le JSON de sortie (`loaded` / `timeout-partial` / `blocked`) au lieu d'être avalé silencieusement par le `try/catch` vide actuel (ligne 175) ;
  - ajout d'un `await document.fonts.ready` borné à 3 s (évite les captures avec police de fallback qui décale les hauteurs de texte) ;
  - une partie des attentes fixes (le `setTimeout` de 5000 ms ligne 181 et les 3 x 1500 ms de la boucle de retry lignes 186-191) est remplacée par une **attente de stabilité bornée** : deux mesures espacées de 700 ms de la hauteur du DOM (`document.body.scrollHeight`) et du nombre d'images chargées (`document.images.length` avec `.complete === true`) ; si stables entre les deux mesures, capture anticipée (gain de temps) ; sinon, capture au délai maximal actuel (**aucune augmentation** du temps total par rapport à aujourd'hui).
- **Format JSON de sortie enrichi** : ajout des champs `method`, `goto_status`, `master_path` en plus des champs existants (`success`, `path`, `size`, `error`, `blocked`, `tooSmall`, `ogUrl` - visibles dans les multiples `console.log(JSON.stringify(...))` du script, lignes 228 à 277). `ScreenshotService::capture()` (lecture du JSON ligne 58) doit rester **tolérant** à l'ancien format (champs absents = `null`/comportement par défaut), le déploiement du script Node et du code PHP n'étant pas garanti simultané sur ce pipeline de déploiement.

### Brique 3 - Fallback og:image normalisé

Le téléchargement brut de l'og:image a lieu aujourd'hui **côté Node** (`downloadFile()`, `capture-screenshot.cjs:120-140`, appelée aux lignes 226 et 261) : le fichier est écrit tel quel sur disque, sans jamais passer par une bibliothèque d'image. Modifier cette normalisation en JavaScript introduirait une dépendance supplémentaire (`sharp` ou équivalent) dans un script qui n'en a aucune aujourd'hui. La normalisation est donc insérée **côté PHP**, dans `ScreenshotService::capture()`, entre la réception du fichier temporaire (`$tempPath`, existant depuis la ligne 49) et son déplacement final (`File::move`, ligne 108) - uniquement quand `$json['method'] === 'og:image'` :

- **Garde anti-bombe**, appliquée en premier, avant tout décodage complet : rejet si `filesize($tempPath) > 10 * 1024 * 1024` (10 Mo) ou si les dimensions déclarées par `getimagesize($tempPath)` (lecture d'en-tête seule, sans décodage complet des pixels) dépassent 8000 px sur un côté. En cas de rejet, le comportement actuel est conservé : le fichier temporaire est supprimé et le gradient fallback (`generateFallbackGradient`, section suivante) prend le relais via `captureWithRetry()`.
- **Normalisation par ratio**, via Intervention Image (déjà dépendance du projet, `composer.json` : `intervention/image: ^3.0`, driver GD déjà utilisé identiquement dans `ScreenshotUploadService.php:49`) :
  - ratio largeur/hauteur source entre 1,2 et 3,0 → `cover(1200, 630)` (comportement actuel de `ScreenshotUploadService`, réutilisé tel quel) ;
  - sinon (logo carré, bannière très large) → composition `contain` sur un canevas 1200x630 dont le fond est la même image agrandie et floutée (`blur`) - convergence explicite du panel sur le principe « zéro contenu coupé » pour ce cas.
- **Pas de master** produit pour un fallback og:image : cohérent avec la Brique 1, le focal reste indisponible pour ces vignettes (elles ne proviennent pas d'une vraie capture de la page).

### Brique 4 - Mort du garde-fou anti-écrasement par octets

`ScreenshotService.php:89-105` contient deux protections fondées uniquement sur la taille en octets :

- **Protection #1** (ligne 91) : refuse un nouveau fichier < 50 % de la taille de l'existant si l'existant dépasse 20 Ko.
- **Protection #2** (ligne 100) : refuse un nouveau fichier < 90 % de la taille de l'existant si l'existant dépasse 50 Ko.

Les deux sont **supprimées**. Elles naissent de l'incident **S79** (221 fichiers écrasés, référencé explicitement dans le code aux lignes 170 et 267 du même fichier), où l'absence de toute protection avait permis à des fallbacks de mauvaise qualité d'écraser silencieusement de vraies captures. Mais une heuristique purement dimensionnelle produit aussi des faux positifs : une nouvelle capture légitimement plus légère (site redesigné, moins d'images, meilleure compression) est aujourd'hui bloquée à tort. Elle est remplacée par un triptyque qui protège **mieux** contre le scénario S79 tout en éliminant les faux positifs :

- **(a) `screenshot_locked` = interdiction absolue**, conservée telle quelle (déjà vérifiée en tout premier dans `ScreenshotService::capture()`, lignes 20-25, et dans `captureWithRetry()`, lignes 126-129) - c'est la protection la plus forte possible : un choix humain explicite, jamais contourné automatiquement.
- **(b) Validation de contenu de la NOUVELLE image**, avant tout remplacement : décodable par Intervention Image, dimensions exactement 1200x630 pour une capture Puppeteer (garantit qu'aucune image tronquée ou mal formée ne passe), non quasi-uniforme (échantillonnage de pixels en grille régulière - typiquement 10x10 points - si plus de 98 % des échantillons tombent dans la même teinte à tolérance proche, rejet : signe caractéristique d'une page blanche/erreur/placeholder), et rejet si le script Node a déjà signalé un blocage (`blocked: true` dans le JSON, déjà détecté par `capture-screenshot.cjs:117-118` et transmis en ligne 236).
- **(c) `generateFallbackGradient` continue de refuser d'écraser un fichier >= 5 Ko** (protection déjà en place, `ScreenshotService.php:171` - inchangée) et **`safeWriteScreenshot()` reste le point d'écriture unique** (lignes 274-298, écriture atomique tmp + `move`).
- **Backup `.bak` étendu** : le pattern déjà en place dans `ScreenshotUploadService.php:45-46` (`copy($fullPath, $fullPath . '.bak')` avant tout remplacement, upload manuel uniquement) est étendu à la **capture automatique** également, dans `ScreenshotService::capture()` juste avant `File::move` (ligne 108). Un seul `.bak` par fichier, **écrasé à chaque remplacement** (pas d'accumulation historique) - suffisant pour un rollback immédiat sans faire grossir indéfiniment `public/screenshots/`.

Ce triptyque protège mieux le scénario S79 précis : à l'époque, aucun verrou n'existait ; aujourd'hui `screenshot_locked` bloque tout ce qu'un admin a validé, la validation de contenu bloque le générique (page blanche, capture bloquée), et le `.bak` garantit un retour arrière immédiat même dans le pire cas résiduel.

## 4. Modèle de données

Une seule migration additive, `Modules/Directory/database/migrations/2026_08_10_120000_add_screenshot_focal_y_to_directory_tools.php` (convention de nommage identique aux migrations existantes du module, ex. `2026_05_30_120000_add_screenshot_locked_to_tools.php`) :

```php
public function up(): void
{
    Schema::table('directory_tools', function (Blueprint $table): void {
        $table->unsignedSmallInteger('screenshot_focal_y')->default(0)->nullable()->after('screenshot_locked');
    });
}

public function down(): void
{
    Schema::table('directory_tools', function (Blueprint $table): void {
        $table->dropColumn('screenshot_focal_y');
    });
}
```

Table cible confirmée par la migration correctrice existante (`2026_05_30_130000_fix_screenshot_locked_target_table.php`) : le modèle `Tool` (`Modules/Directory/app/Models/Tool.php`) persiste bien sur `directory_tools`, pas `tools`. `screenshot_focal_y` doit être ajouté au `$fillable` (ligne 89) et au `$casts` (`'screenshot_focal_y' => 'integer'`, à côté de `'screenshot_locked' => 'boolean'` ligne 172). `unsignedSmallInteger` (max 65535) est largement suffisant pour une valeur bornée à 770.

## 5. Rayon d'impact

- **`Modules/News`** : `ScreenshotUploadService` reste **strictement inchangé** (Brique 1) - aucun impact sur `AdminNewsController.php`. Le composant partagé `x-core::screenshot-capture` (capture assistée) est réutilisé tel quel par News (`Modules/News/resources/views/admin/articles/edit.blade.php:86`, `Modules/News/resources/views/public/show.blade.php:90`) sans aucune modification de son comportement (Brique 1 ne touche pas ce composant).
- **Composant Blade partagé** `Modules/Core/resources/views/components/screenshot-capture.blade.php` : non modifié par ce chantier (voir Brique 1, capture assistée sans master pour l'instant).
- **Vues publiques** (`show.blade.php:407-419`, `index.blade.php:1323-1339`) : **aucun changement**. Elles continuent de lire la colonne `screenshot` (vignette 1200x630) avec `object-fit: cover` et le cache-bust `?v=` existant - le point focal n'est qu'un nouveau **producteur** de cette même vignette, jamais un nouveau consommateur côté front public.
- **`Modules/Directory/app/Observers/ToolObserver.php:13-23`** : déclenche déjà `captureWithRetry()` sur publication. Compatible tel quel avec les Briques 2, 3 et 4 (aucun changement de signature).
- **`CaptureScreenshotJob.php`**, **`CaptureScreenshotsCommand.php`**, **`LockScreenshotsCommand.php`** : aucun changement de signature requis, ils appellent tous `ScreenshotService::capture()`/`captureWithRetry()` dont le comportement interne change mais pas l'API publique.
- **Route de modération publique** (`Modules/Directory/routes/web.php:82-85`, soumissions communautaires de captures) : hors périmètre, mécanisme entièrement distinct (table `screenshots` liée par `screenshots()` dans `Tool.php:314`, pas la colonne `screenshot` unique).

## 6. Sécurité

- **Validation bornée de `focal_y`** : le contrôleur clamp systématiquement la valeur reçue à `[0, hauteur_master - 630]` avant tout appel à `ScreenshotFocalService` - jamais de confiance dans une valeur brute venant du formulaire, même si l'UI JS applique déjà une borne côté client.
- **Garde anti-bombe décodage** (Brique 3) : taille et dimensions déclarées vérifiées **avant** tout appel à `ImageManager::read()`, pour ne jamais exposer le décodeur GD à un fichier hostile de grande taille.
- **Routes gatées** : correction par rapport à l'hypothèse initiale - le groupe `admin/directory` (`Modules/Directory/routes/web.php:89`) est protégé par le middleware `EnsureIsAdmin` **et** le gate `can:moderate_tools`, pas `view_admin_panel` (ce dernier gate n'est utilisé que sur les routes de modération communautaire, lignes 84-85, un groupe distinct). La nouvelle route `admin.directory.set-focal` doit être ajoutée **dans ce même groupe** (lignes 89-129), héritant automatiquement des deux gardes existants - aucune nouvelle règle d'autorisation à écrire.
- **Purge Cloudflare ciblée** (extraite en Brique 1) : continue de purger uniquement le fichier concerné (`files: [url complet de la vignette]`), jamais un `purge_everything` - comportement déjà en place à conserver strictement (`purgeCloudflareScreenshot`, ligne 253).

## 7. Critères d'acceptation testables

- **CA-1** : après réglage `focal_y = 200` et appel à `deriveThumbnail()`, la vignette 1200x630 résultante correspond pixel pour pixel à la tranche `y = 200..830` du master (vérifiable par hash MD5 d'une bande de test découpée manuellement du master).
- **CA-2** : une valeur `focal_y` hors bornes envoyée au contrôleur (négative ou supérieure à `hauteur_master - 630`) est clampée automatiquement, jamais rejetée par une erreur 500 ni acceptée telle quelle.
- **CA-3** : une nouvelle capture automatique (`CaptureScreenshotJob`) régénère un nouveau master et réinitialise `screenshot_focal_y` à 0, même si un focal manuel non nul existait avant.
- **CA-4** : une nouvelle image invalide (uniforme à plus de 98 % de ses pixels échantillonnés, ou signal `blocked: true` du script Node) ne remplace **jamais** une vignette existante, qu'elle soit plus petite ou plus grande en octets que l'ancienne.
- **CA-5** : un outil avec `screenshot_locked = true` accepte toujours un réglage focal manuel déclenché volontairement par l'admin (le verrou ne bloque que la capture automatique et le gradient fallback, jamais l'action explicite de l'admin sur son propre outil).
- **CA-6** : un fallback og:image dont le ratio source est hors `[1.2, 3.0]` est composé sur un canevas 1200x630 par `contain` + fond flouté agrandi, sans qu'aucun pixel du sujet ne soit coupé par un `cover`.
- **CA-7** : les tests existants du module News touchant `ScreenshotUploadService` (upload d'image d'article) passent inchangés, sans aucune régression sur le contrat `cover(1200, 630)`.
- **CA-8** : un fichier og:image annoncé à plus de 10 Mo ou plus de 8000 px de côté est rejeté avant tout appel à `ImageManager::read()`, et bascule immédiatement sur le gradient fallback existant.
- **CA-9** : l'admin peut ajuster le cadrage focal entièrement au clavier (boutons haut/bas + `input range`), sans dépendre de la souris, conforme à la cible WCAG 2.2 AAA du projet.
- **CA-10** : après application d'un nouveau focal, l'URL de la vignette change (cache-bust `?v=` basé sur `updated_at`) et seule la purge Cloudflare ciblée du fichier concerné est déclenchée, jamais une purge globale.

## 8. Stratégie de tests

Aucun test automatisé n'existe aujourd'hui pour `ScreenshotService` ni `ScreenshotUploadService` (vérifié : recherche exhaustive sur `tests/` et `Modules/Directory/tests/{Feature,Unit}`, `Modules/News/tests/` - aucune correspondance). Ce chantier doit donc créer sa propre couverture, pas seulement l'étendre :

- **Unit** (`Modules/Directory/tests/Unit/ScreenshotFocalServiceTest.php`, dossier actuellement vide) : `deriveThumbnail()` sur un master de test fixe (fixture JPEG committée dans `Modules/Directory/tests/Fixtures/`), couvrant CA-1 et le bornage de CA-2 au niveau service.
- **Feature** (`Modules/Directory/tests/Feature/ScreenshotAdminFocalTest.php`, à côté de `AdminDirectoryRbacTest.php` déjà présent) : route `set-focal` complète (auth admin, gate `moderate_tools`, clamp CA-2, réponse JSON avec URL cache-bustée CA-10).
- **Feature** (`Modules/Directory/tests/Feature/ScreenshotOverwriteGuardTest.php`) : couvre CA-4 et CA-5 (`screenshot_locked` + validation de contenu) en simulant le retour JSON du script Node via un mock du `Process` facade Laravel (`Process::fake()`), sans dépendre de Puppeteer réellement installé en CI.
- **Régression News** (CA-7) : lancer la suite complète (`Modules/News/tests/Feature`) sans aucune modification attendue - conforme à la règle projet « toujours rouler la suite complète, pas seulement le scope du module modifié » (un seeder ou service partagé a déjà causé une régression hors-module par le passé sur ce projet).
- **Visuel** : validation manuelle Playwright de l'interaction de glisser/déposer et des boutons clavier sur `admin/directory/{tool}/edit`, avec capture avant/après pour prouver visuellement le déplacement du cadrage (garde-fou projet : jamais de « terminé » sans preuve visuelle).

## 9. Non-objectifs

- **Recadrage horizontal** : le point focal de cette brique est **vertical uniquement**. Un recadrage horizontal (utile pour des captures très larges) n'est pas traité ici.
- **File de revue centralisée** (« brique 5 » évoquée en discussion, une interface listant tous les outils à faible confiance de cadrage pour revue en lot) : non spécifiée dans ce document, à traiter séparément si le besoin se confirme après usage réel.
- **Extension navigateur** dédiée à la capture (remplacement de la capture assistée par `getDisplayMedia`) : hors périmètre.
- **Apprentissage automatique / détection de saillance** : explicitement rejeté par le panel (section 2), non reconsidéré ici.
- **Modification du composant `screenshot-capture.blade.php`** pour lui permettre de produire un master plus haut que 630 px : identifié comme limitation connue (Brique 1) mais volontairement hors périmètre - un changement de ce composant partagé avec News mérite sa propre revue dédiée plutôt qu'un effet de bord de ce chantier.
- **Backfill des masters manquants** pour les outils déjà publiés avant ce chantier : aucune tâche de fond ne régénère rétroactivement de master pour les captures existantes. Le focal reste indisponible sur un outil tant qu'il n'a pas subi une nouvelle capture automatique ou un nouvel upload manuel après le déploiement de cette brique.
- **Modification de `directory:enrich-tools` / `MetaScraperService`** (le troisième chemin og:image en URL externe brute) : explicitement exclu, voir section 3, Brique 1.

## 10. Rollback

- **Migration** : `down()` symétrique (section 4), suppression propre de `screenshot_focal_y` sans effet sur les autres colonnes.
- **Fichiers** : le dossier `public/screenshots/masters/` est purement additif - sa suppression complète n'affecte jamais l'affichage public (les vignettes `public/screenshots/{slug}.jpg` restent le seul chemin lu par les vues). Un rollback de code peut donc être suivi d'une suppression du dossier `masters/` sans aucune perte fonctionnelle visible pour les visiteurs.
- **`.bak`** (Brique 4) : disponible pour restauration immédiate de la dernière vignette remplacée, un seul niveau d'historique par fichier - suffisant pour un rollback à chaud sans attendre un redéploiement.
- **Tag git** : un tag de version doit être posé juste avant le déploiement de ce chantier (convention SemVer déjà en place sur le projet, `config/version.php`), permettant un `git revert` ciblé si une régression est constatée après coup.
- **Interrupteur applicatif recommandé** : le projet a déjà un précédent direct pour ce genre de garde-fou - `Settings::get('directory.assisted_screenshot_enabled', true)` contrôle l'affichage du bloc de capture assistée (`edit.blade.php:209`). Le même pattern (`directory.focal_point_enabled`) permettrait de masquer l'UI du point focal instantanément, sans revert de code, si un problème est détecté en production après livraison.
