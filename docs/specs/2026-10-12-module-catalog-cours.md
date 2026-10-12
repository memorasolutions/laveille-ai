<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
# Module `Catalog` — catalogue public des cours Moodle à laveille.ai/cours (#3069)

> Spec figée le 2026-10-12. Source : coordination avec le pair Moodle (`formations-laveille-moodle-6f`)
> + carte des conventions du dépôt (gabarit `ShortUrl`, grille `Blog`). Contrat d'implémentation
> pour délégation. Zéro casse, lecture seule, module désactivable. AUCUNE dépendance composer nouvelle
> (règle 18) : uniquement `Http`, `Cache`, `Storage` déjà présents dans Laravel.

## Objectif

Remplacer le listing natif Moodle (laid) `/formations/course/` par un catalogue Laravel en cartes,
servi à **`laveille.ai/cours`** (route confirmée libre : 404 le 2026-10-12). Lecture seule du Moodle
externe via son web service REST. Le pair pose ensuite le 301 `/formations/course/` +
`/course/index.php?categoryid=*` → `/cours`, SEULEMENT quand `/cours` est en ligne et prouvé.

## Données (mesurées par le pair, NE JAMAIS coder en dur)

Au 2026-10-12 : 9 cours (données de DÉMO, à supprimer au lancement), répartis en plusieurs catégories
(4 thématiques : « Intelligence artificielle », « Cybersécurité et Loi 25 », « Productivité et
bureautique », « Marketing numérique » + « Catégorie 1 » démo). Le vrai catalogue aura d'autres
cours/catégories. **Concevoir pour N catégories dynamiques** : groupement par catégorie quand il y en
a plusieurs, repli en grille simple quand il n'y en a qu'une. Filtrer `visible == 1`.

## Web service Moodle

- Endpoint : `config('catalog.moodle_ws_url')` = env `MOODLE_WS_URL`, défaut
  `https://laveille.ai/formations/webservice/rest/server.php`.
- Format : toujours `moodlewsrestformat=json`.
- Jeton : `config('catalog.moodle_ws_token')` = env `MOODLE_WS_TOKEN` (déposé côté serveur depuis
  1Password « laveille - Moodle WS - token svc_laveille », JAMAIS en clair, JAMAIS journalisé,
  JAMAIS exposé au navigateur).
- Fonctions : `core_course_get_categories` (liste des catégories visibles) puis
  `core_course_get_courses` (tous les cours) — regrouper côté PHP par `categoryid`. Exclure le cours
  « site » (id 1) et tout cours `visible != 1`.
- Appel : façade `Http::` (JAMAIS `->withoutVerifying()`), `Http::timeout(15)`, paramètres en query
  (`wstoken`, `wsfunction`, `moodlewsrestformat=json`).

## Fichiers à créer (gabarit = `Modules/ShortUrl`)

1. `Modules/Catalog/module.json` — `name=Catalog`, `alias=catalog`,
   `providers=["Modules\\Catalog\\Providers\\CatalogServiceProvider"]`, `files=[]`.
2. `Modules/Catalog/app/Providers/CatalogServiceProvider.php` — `extends BaseModuleServiceProvider`,
   `$name='Catalog'`, `$nameLower='catalog'`, `boot(){ $this->bootModule(); }`,
   `register(){ $this->app->register(RouteServiceProvider::class); }`.
3. `Modules/Catalog/app/Providers/RouteServiceProvider.php` — `extends BaseRouteServiceProvider`,
   `$mapApi=false` (aucune route API pour l'instant).
4. `Modules/Catalog/config/config.php` — `return [`
   `'name'=>'Catalog',`
   `'moodle_ws_url'=>env('MOODLE_WS_URL','https://laveille.ai/formations/webservice/rest/server.php'),`
   `'moodle_ws_token'=>env('MOODLE_WS_TOKEN'),`
   `'moodle_base_url'=>env('MOODLE_BASE_URL','https://laveille.ai/formations'),`
   `'cache_ttl'=>(int) env('CATALOG_CACHE_TTL',1800),`
   `'http_timeout'=>(int) env('CATALOG_HTTP_TIMEOUT',15),`
   `];` (lu via `config('catalog.*')`).
5. `Modules/Catalog/routes/web.php` — même motif middleware FrontTheme que ShortUrl :
   `Route::middleware($frontMiddleware)->name('catalog.')->group(function(){ Route::get('/cours', [PublicCatalogController::class,'index'])->name('cours'); });`
6. `Modules/Catalog/app/Services/MoodleCatalogService.php` :
   - `getCatalogue(): array` → `Cache::remember('catalog.courses', config('catalog.cache_ttl'), fn()=>$this->fetch())`.
     Retourne une structure `[ ['category'=>..., 'courses'=>[ ['id','fullname','summary','image_local','course_url'], ... ] ], ... ]`.
   - `fetch()` : si `moodle_ws_token` vide OU module non activé → retourne `[]` SANS erreur (zéro casse).
     Appelle le WS (catégories + cours), regroupe par catégorie visible, filtre `visible==1`, exclut id 1.
     Toute exception/`$response->failed()` → journaliser un warning SANS le jeton, retourner `[]`
     (une panne WS ne casse jamais la page).
   - `cachedImage(array $course): ?string` : si `overviewfiles[0].fileurl` présent, télécharger
     `fileurl.'?token='.token` via `Http::timeout(...)->get()`, `Storage::disk('public')->put("catalog/course-{$id}.{$ext}", $body)` SEULEMENT si pas déjà en cache local, retourner le chemin public
     (`asset('storage/catalog/...')`). Échec → `null` (la vue met un visuel de repli). Le jeton
     n'apparaît JAMAIS dans le HTML rendu.
   - `courseUrl($id)` : `config('catalog.moodle_base_url').'/course/view.php?id='.$id`.
7. `Modules/Catalog/app/Http/Controllers/PublicCatalogController.php` :
   `index(): View { $categories = app(MoodleCatalogService::class)->getCatalogue(); return view('catalog::public.index', compact('categories')); }`.
8. `Modules/Catalog/resources/views/public/index.blade.php` :
   - En-tête `<!-- Author: MEMORA solutions... -->`, `@extends(fronttheme_layout())`,
     `@section('title', __('Nos formations').' - '.config('app.name'))`, breadcrumb via
     `@include('fronttheme::partials.breadcrumb', ...)`.
   - Si `count($categories) <= 1` → grille plate ; sinon, une section par catégorie (titre `<h2>` =
     nom de catégorie) puis grille.
   - Grille Tailwind (comme Blog) : `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6` ; carte
     `article` `bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition`.
   - Carte : image locale proxifiée OU visuel de repli (dégradé sarcelle + icône, pas d'image distante
     avec jeton), titre `fullname`, extrait `summary` (strip_tags + Str::limit 140), badge catégorie,
     lien « Voir la formation » → `course_url`. Libellés via `__()`.
   - Si `$categories` vide → message neutre « Les formations seront bientôt disponibles. » (jamais une
     page cassée).
   - Contraste AAA (vérifier paires fond/texte avec wcag si couleurs neuves).
9. `Modules/Catalog/tests/Feature/CatalogServiceTest.php` (Pest) :
   - `Http::fake` avec un JSON d'exemple (2 catégories, 3 cours dont 1 `visible=0` et le cours site id 1).
   - Asserte : groupement par catégorie, exclusion du `visible=0` et de l'id 1, repli `[]` quand
     `MOODLE_WS_TOKEN` vide, aucune exception quand le WS répond en erreur.
10. `modules_statuses.json` : ajouter `"Catalog": true` (fait par le superviseur, 1 ligne).

## Garde-fous

- Module désactivable (`modules_statuses.json`), aucune route si désactivé.
- Jeton WS : jamais en clair, jamais journalisé, jamais dans le HTML. Image proxifiée côté serveur.
- Panne WS / jeton absent → page avec message neutre, jamais d'erreur 500.
- Aucune écriture vers Moodle (lecture seule).
- Aucune dépendance composer nouvelle.
- « En ligne et prouvé » = `/cours` répond 200 en prod avec le VRAI WS branché (après déverrouillage
  1Password) ET cartes visibles vérifiées au navigateur → alors seulement, ping au pair pour le 301.

## Dépendances externes (hors écriture du code)

- Déverrouillage 1Password sur le Mac (pair relaie au fondateur) → pour brancher `MOODLE_WS_TOKEN`
  côté serveur et tester le WS live.
- Ping du pair pour poser le 301, APRÈS preuve prod.
