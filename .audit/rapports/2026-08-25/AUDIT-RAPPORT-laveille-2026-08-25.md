# Audit ultra exhaustif de laveille.ai

**Date** : 2026-08-25 (America/Toronto) · **Portée** : complet, 11 dimensions, aucune retirée
**Cible** : laveille.ai en production (lecture seule) + dépôt local `@ec5195e3` (v1.219.0)
**Volume** : 4 913 fichiers PHP, 54 modules nwidart, 942 routes, 1 178 vues Blade, 542 migrations
**Grille** : OWASP Top 10 **édition 2025** (renumérotée : A02 = mauvaise configuration, A03 = chaîne
d'approvisionnement), OWASP LLM Top 10 2025, WCAG 2.2 AA, Loi 25, RGPD. Source de la grille :
`pp_search` du 2026-08-25.

---

## 1. Matrice de couverture

| Dimension | Statut | Couverture | Confiance |
|---|---|---|---|
| securite-applicative | complété | ~40 % (modules prioritaires) | moyenne |
| securite-infra | complété | 100 % | élevée |
| qualite-code-DRY | complété | ~35 % (échantillon raisonné + greps exhaustifs) | moyenne |
| performance | complété | 2 gabarits mesurés | moyenne |
| accessibilite | complété | 2 gabarits (accueil, annuaire) | moyenne |
| UX-UI | complété | accueil + actualités | moyenne |
| SEO-GEO-AEO | complété | 100 % (données GSC réelles) | élevée |
| conformite-Loi25-RGPD | complété | 8 exigences sur 8 | élevée |
| tests-couverture | complété | 100 % | élevée |
| dependances-CVE-licences | complété | 100 % | élevée |
| hygiene-serveur | complété (blocage externe consigné) | local 100 %, prod par HTTP | moyenne |

**Blocage externe consigné** : les MCP `cpanel` et `memora-multi` renvoient un interstitiel de
chargement et « Could not fetch WHM accounts ». La liste des tâches planifiées en production n'a
donc pas pu être lue. Pour débloquer : ouvrir une session cPanel valide, puis relancer
`cpanel_cron_list`. Tout ce qui était vérifiable par requête HTTP l'a été (10 chemins sensibles).

---

## 2. Scores

| Dimension | Note | Justification |
|---|---|---|
| securite-infra | **92/100** | En-têtes grade A (7/8), HSTS, SPF + DMARC + DKIM, aucun fichier sensible exposé. Retrait : CSP réduite à `frame-src`. |
| conformite-Loi25-RGPD | **45/100** | *Abaissée de 58 : l'API expose les courriels de tous les comptes (H11), ce qui est une communication de renseignements personnels plus directe que le défaut de témoins.* AdSense contourne le consentement (prouvé). Registre d'incidents absent. |
| securite-applicative | **32/100** | *Note abaissée de 62 à 38 après la passe adversariale.* Le rôle « admin » vaut une exécution de commandes système (C1), l'API publie sans autorisation (H8), les webhooks sortants n'ont aucune garde SSRF (H9). Points forts réels par ailleurs : gardes SSRF des services de récolte, throttling systématique, anti-rejeu SAML. |
| SEO-GEO-AEO | **45/100** | Perte de 90 % de la visibilité, non récupérée depuis 5 semaines. L'état technique ACTUEL est sain, mais un candidat technique daté (sitemap cassé) n'est pas écarté. |
| dependances-CVE | **55/100** | 19 avis dont 14 hauts. Licences toutes permissives. |
| qualite-code-DRY | **74/100** | *Note relevée de 70 à 74 : mon finding « 4 modèles casseraient » était faux, réfuté empiriquement.* Architecture modulaire saine, DRY réellement appliqué. Restent 15 popups natives et 4 méthodes mortes. |
| performance | **72/100** | Chargement rapide (885 ms) mais 2 397 Ko par page. |
| accessibilite | **80/100** | Annuaire sans défaut de contraste ; défauts réels limités, l'essentiel des alertes étant des faux positifs. |
| tests-couverture | **85/100** | 6 800+ tests au vert, 3 modules sans aucun test. |
| UX-UI | **68/100** | Deux sollicitations simultanées à l'arrivée, bandeaux empilés. |
| hygiene-serveur | **82/100** | Rien d'exposé en production ; deux résidus locaux. |

**Score global pondéré : 48/100** *(68 avant contradiction, puis 55, puis 48 : chaque round adversarial a fait baisser la note)*. Les zones non
explorées sont comptées comme inconnues, jamais comme saines.

> **Ce que cette révision dit de l'audit lui-même.** La première passe avait conclu « aucun finding
> critique ». C'était faux : elle n'avait pas ouvert `Modules/Backoffice`, `Modules/Api`,
> `Modules/Webhooks` ni les seeders de rôles, c'est-à-dire précisément là où vivent les deux
> findings critiques. Elle avait par ailleurs inscrit en « haute » un finding (H6) qui ne tenait
> pas. Sans la passe adversariale imposée par le protocole, ce rapport aurait été rassurant et
> faux sur ses deux extrémités.
>
> **Et le constat le plus important pour la suite** : chaque round adversarial a encore trouvé des
> défauts réels, y compris le troisième, dans des modules que les précédents n'avaient jamais
> ouverts (Search, Import, Books). Le protocole demande de boucler jusqu'à deux verdicts vides
> consécutifs ; ce n'est **pas atteint**. Sur 4 913 fichiers et 54 modules, la conclusion honnête
> n'est pas « la plateforme a été auditée entièrement », mais « chaque nouvelle lentille braquée
> sur une zone jamais lue y trouve quelque chose ». La section 8 dit précisément ce qui reste.

---

## 3. Findings, par sévérité

### CRITIQUE

*Ces deux findings ont été trouvés par la passe adversariale `/100`, pas par la passe initiale.
Celle-ci n'avait ouvert ni `Modules/Backoffice`, ni `Modules/Api`, ni `Modules/Webhooks`. Je les
ai vérifiés moi-même dans le code avant de les inscrire ici.*

**C1 — Le rôle « admin » permet d'exécuter des commandes système arbitraires**
`Modules/Backoffice/app/Http/Controllers/SchedulerController.php:203-214` + `routes/console.php:206-214`
· A01 · corroboré (lecture du code applicatif ET du framework) · confiance élevée

L'écran `/admin/scheduler` (permission `manage_system`) enregistre un champ `command` validé
seulement par `'required|string|max:255'` : **aucune liste blanche, aucun échappement**. Le
planificateur exécute ensuite `Schedule::command($task->command)` pour chaque tâche active. Or
Laravel construit la chaîne par `sprintf` **sans échapper** l'argument
(`Illuminate/Console/Application.php:109-112`) puis la passe à `Process::fromShellCommandline()`,
donc à `/bin/sh -c`. Tout métacaractère (`;`, `|`, `$()`) s'exécute avec les droits du processus.

Ce qui rend le finding grave : `manage_system` est présenté dans l'interface comme
« Maintenance, cache, scheduler, jobs échoués », et le rôle **`admin`** (niveau 80, distinct de
`super_admin`) reçoit **toutes les permissions sauf la gestion des rôles**
(`RolesAndPermissionsSeeder.php:68-73`). Une permission de maintenance vaut donc en réalité une
exécution de code sur le serveur.

**Objection examinée puis écartée** : « si seul le fondateur est admin, ce n'est pas une faille,
c'est un accès d'administration normal ». Vérifié, et l'argument ne tient pas :
`Modules/Backoffice/app/Http/Controllers/UserController.php:52-54` permet à quiconque détient
`update_users` — donc à tout compte `admin` — d'assigner le rôle `admin` à un autre utilisateur via
`syncRoles()`. Le rôle est donc **conçu pour être distribué** à des employés ou modérateurs. Le
système définit d'ailleurs deux paliers (`admin` niveau 80, exclu de la gestion des rôles ;
`super_admin` niveau 100) précisément pour borner `admin` : ce défaut **annule cette séparation
voulue**. La cotation « critique » est maintenue.

*Correction* : liste blanche stricte des commandes autorisées (les signatures artisan réellement
planifiables), validée à l'enregistrement ET à l'exécution. *Rayon élevé : proposé, non appliqué.*

**C2 — Compte de démonstration à mot de passe en clair, avec le rôle admin, sans garde d'environnement**
`database/seeders/DatabaseSeeder.php:38-46` · A07 · corroboré (code) / **à confirmer en production**

```php
$admin = User::firstOrCreate(['email' => 'moderator@laravel-core.test'],
    ['name' => 'Modérateur', 'password' => bcrypt('password'), ...]);
$admin->assignRole('admin');
```

Aucun `if (! app()->isProduction())` autour de ce bloc, **alors que le bloc suivant en a un** pour
les cinq comptes de démo, et que le superadmin juste au-dessus tire son mot de passe de la
configuration. Combiné à C1, ce compte vaudrait une exécution de code à distance non
authentifiée par mot de passe deviné.

**Deuxième chemin d'exécution, trouvé au 3e round** : `app/Console/Commands/InstallCommand.php:188`
lance `['db:seed', ['--force' => true]]` **sans aucune garde d'environnement**. C'est l'installeur
du socle, plausiblement employé une fois pour amorcer la production. Vérifié par ailleurs :
`.github/workflows/deploy.yml` et `scripts/deploy.sh` ne lancent **jamais** `db:seed` (seulement
`migrate --force`), ce qui est rassurant sur le déploiement courant — mais deux autres chemins
(l'installeur, et le runner `_lvgit.php`) peuvent le déclencher.

**Ce qui reste à vérifier, et que je n'ai pas fait** : ce compte existe-t-il réellement en base de
production ? Il est **absent de la base locale** (vérifié). Contrôle à faire par requête SQL
directe (`SELECT id FROM users WHERE email='moderator@laravel-care.test'`), **jamais par une
tentative de connexion**, qui serait une exploitation. Tant que ce point n'est pas tranché, le
finding reste « à confirmer » quant à son effet réel, mais le défaut de code, lui, est certain.

**Aggravation trouvée au 2e round adversarial, que j'avais manquée en examinant M2 et C2
séparément** : `public/_lvgit.php:99-107` autorise explicitement le préfixe `Database\Seeders\`
dans sa liste blanche de seeders. Vérifié. Quiconque détient le `LV_GIT_TOKEN` peut donc
**(re)créer ce compte à la demande** en production — et ce jeton voyage justement en clair dans la
chaîne de requête (M2), donc dans les journaux d'accès. La question n'est pas seulement « le compte
existe-t-il », mais « qui peut le faire apparaître ».

*Correction* : entourer le bloc de la même garde que ses voisins ; retirer `Database\Seeders\`
de la liste blanche du runner (ne laisser que les seeders de contenu) ; passer le jeton en en-tête
(M2) ; et si le compte existe en production, le supprimer.

### HAUTE

**H1 — AdSense dépose des témoins publicitaires avant tout consentement**
`Modules/FrontTheme/resources/views/layouts/master.blade.php:20-38` · Loi 25 art. 8.1, RGPD art. 6
· niveau de preuve : **reproduit** · confiance élevée

**Le RGPD s'applique bien de fait** : les données GA4 des 30 derniers jours placent la France au
**3e rang du trafic** du site. La question n'est donc pas seulement québécoise.

Reproduit sur `/actualites` en visiteur neuf : consentement `(aucun)`, bandeau **encore affiché**,
et déjà **10 requêtes** parties vers `pagead2.googlesyndication.com` et `googleads.g.doubleclick.net`,
`adsbygoogle` chargé. Le script s'injecte sans aucune vérification, seul `@section('no_ads')` le
retient. C'est d'autant plus net que le reste du dispositif est correct : bandeau opt-in réel avec
« Tout refuser » au même niveau que « Tout accepter », GA4 en Consent Mode v2, infolettre en double
opt-in. AdSense n'est par ailleurs **nommé nulle part** dans la politique de confidentialité.

*Correction* : gater sur `choices.marketing === true`, exactement comme le Pixel Facebook l'est déjà
via `config('privacy.scripts')` (`cookie-consent.blade.php:294-313`), ou à défaut charger en mode
non personnalisé tant que le consentement marketing manque. Ajouter AdSense au tableau des
prestataires. *Rayon élevé (conformité + revenus) : correctif proposé, pas appliqué.*

**H2 — XSS stocké via la sortie d'un modèle de langage**
`Modules/Directory/resources/views/public/show.blade.php:1114` · A03 + LLM01 · niveau de preuve :
**reproduit** (rendu) / corroboré (chaîne complète) · confiance élevée

`{!! Str::markdown($res->video_summary) !!}` sans options : `html_input` vaut `allow` par défaut.
Prouvé mécaniquement : `<img src=x onerror="...">` **passe intact** (la balise `<script>` est
échappée, mais un attribut d'événement suffit). `video_summary` est la sortie brute d'un LLM
résumant une description YouTube **contrôlée par l'attaquant**, et la ressource est
**auto-approuvée** (`$autoApprove = true`, `CommunityController.php:59`) pour tout utilisateur
connecté.

Ce qui rend le diagnostic sûr : **le même champ est filtré ailleurs dans le même fichier** — ligne
1050 par `strip_tags()`, lignes 586 et 613 par `['html_input' => 'strip']`. C'est un oubli, pas un
choix. Réserve honnête : l'exploitation de bout en bout suppose que le modèle recopie le HTML
injecté dans son résumé, ce qui n'est pas garanti ; le défaut de rendu, lui, est certain.

*Correction* : `Str::markdown($res->video_summary, ['html_input' => 'strip', 'allow_unsafe_links' => false])`.
9 autres appels `Str::markdown` sans options existent dans le projet, à passer en revue.

**H3 — La politique de sécurité de contenu ne protège rien**
`Modules/Core/app/Http/Middleware/SecurityHeaders.php:31` · A02 (2025) · corroboré · confiance élevée

La CSP ne déclare que `frame-src` : ni `default-src`, ni `script-src`, ni `object-src`. Confirmé
par `sec_headers` sur le site en ligne. Elle n'oppose donc aucune défense en profondeur à H2. Déjà
signalé le 22 juillet, toujours ouvert.

**H4 — 14 vulnérabilités hautes dans les dépendances**
corroboré (outils officiels) · confiance élevée

`composer audit` : 9 avis, dont **5 hauts** — `guzzlehttp/guzzle` (contournement de vérification
d'hôte, CVE-2026-69246), `league/commonmark` (6 avis de déni de service). CommonMark est
directement atteignable : le pipeline `/actu2` récolte et rend du Markdown externe.
`npm audit` : 10 avis, dont **9 hauts** (`extract-zip`, `brace-expansion`, `ip-address`, `js-yaml`).

*Correction* : `guzzle >= 7.15.2`, `league/commonmark >= 2.9.0`, `sodium_compat >= 2.5.1`.
*Rayon élevé (dépendances) : proposé, jamais appliqué automatiquement.*

**H5 — Effondrement de la visibilité organique, non récupéré**
corroboré (données GSC + confirmation externe) · confiance élevée

| Période | Impressions/jour | Clics/jour | Position |
|---|---|---|---|
| Pic, 22 juin | 1 800 | 41 | 8,3 |
| 18 juillet | 481 | 4 | 14,6 |
| **19 juillet** | **46** | 0 | 15,3 |
| Août | 7 à 33 | 0 à 2 | 29 à 74 |

Chute de 90 % en une journée. **Correction importante apportée par la passe adversariale : je ne peux PAS écarter la cause technique.** Ma première conclusion était prématurée, parce que je n'avais vérifié que l'état ACTUEL du site, jamais l'historique au moment de la chute. Un candidat concret et daté existe : le commit `862d4ad4` du **18 juillet à 17h55**, la veille de l'effondrement, corrige un `route('directory.show', $tool->slug)` qui levait une `UrlGenerationException` **non interceptée** dans une boucle sur TOUS les outils de `SitemapController::index()`. Une seule exception y interrompt la génération entière de `/sitemap.xml`. Le bug a été introduit le 23 mars et ne se déclenchait que si un outil avait un slug renseigné en `fr` sans `fr_CA` : l'ajout d'un tel outil suffisait à casser le sitemap du jour au lendemain. Le même correctif touchait aussi la page d'accueil. Je n'ai pas les journaux serveur de juillet pour prouver que ce bug s'est déclenché, ni pendant combien de temps — mais c'est une piste sérieuse, que j'aurais dû chercher avant de conclure. Réserve dans l'autre sens : le correctif date du 18 et le trafic n'est pas revenu depuis, ce qu'un sitemap réparé n'explique pas.

Ce qui reste vrai sur l'état actuel : les pages qui portaient le trafic
répondent 200, sont en `index, follow`, ont une canonical propre, figurent au sitemap (3 599 URL),
et l'inspection GSC renvoie `Submitted and indexed`, `robotsTxtState: ALLOWED`. Une volatilité SERP
majeure est documentée par 13-14 outils de suivi les 18-19 juillet, **sans core update confirmé par
Google**. Deux signaux aggravants : dernier passage du robot le **4 août** (3 semaines), et maillage
interne très faible (l'inspection ne remonte qu'une seule URL référente, la politique de
confidentialité).

*Je ne conclus pas à une cause unique* : la coïncidence avec la volatilité externe est forte, mais
non démontrée. Piste d'action : renforcer le maillage interne, et surveiller la reprise du crawl.

**H6 — RETIRÉ après réfutation.** J'avais écrit que 4 modèles casseraient si le module SEO était
désactivé. **C'est faux, et la passe adversariale l'a démontré empiriquement.** Désactiver un
module nwidart ne fait que basculer un booléen dans `modules_statuses.json` : cela ne retire rien
du plan d'autoload de Composer, fusionné statiquement pour tous les modules
(`composer.json:141-154`, `merge-plugin`). Vérifié par moi-même :
`trait_exists("Modules\\Tenancy\\Traits\\BelongsToTenant")` renvoie **`true`** alors que Tenancy
est désactivé, et il en va de même du trait SEO. Aucune erreur fatale n'est donc possible par
simple désactivation.

Ce qui subsiste est **l'inverse, et plus faible** : le trait est déjà gardé en interne par
`class_exists(IndexNowService::class)` (`NotifiesIndexNow.php:33`), mais rien ne vérifie
`Module::isEnabled('SEO')` — la notification IndexNow continuerait donc d'agir même module
« désactivé ». Reclassé en **basse** (défaut d'isolation, pas de panne). Voir B7.

**H7 — Vérification de signature de webhook qui échoue OUVERTE (zone argent)**
`Modules/Shop/app/Services/StripeService.php:138-149` · A01 · corroboré · confiance élevée

```php
$secret = config('shop.stripe.webhook_secret');
if (! $secret) {
    Log::warning('... non configuré — vérification désactivée');
    return true;   // accepte le webhook SANS signature valide
}
```

Si le secret est absent ou vidé, un POST forgé vers l'endpoint peut marquer une commande `paid`
sans paiement réel. Même motif sur le webhook Gelato (`WebhookController.php:25`). **Le bon patron
existe déjà dans le même dépôt** : `Modules/Booking/app/Services/PaymentService.php:57-68` utilise
`\Stripe\Webhook::constructEvent()`, qui lève une exception si la signature est invalide. Seul Shop
l'a réimplémenté à la main, et à l'envers. *Correction : refuser (fail-closed) quand le secret
manque.* Non vérifié si la variable est réellement absente en production (je ne lis pas `.env`) —
le patron reste le défaut, quel que soit l'état actuel.

**H8 — L'API REST publie sans vérifier aucune autorisation**
`Modules/Api/app/Http/Controllers/ArticleApiController.php:30-45` · A01 · corroboré · confiance élevée

`store()` n'appelle **jamais** `$this->authorize()`, alors que `update()` (ligne 52) et `destroy()`
(ligne 70) le font juste en dessous — l'incohérence dans le même fichier signe l'oubli.
`StoreArticleRequest::authorize()` retourne `true`. La seule protection est `auth:sanctum`. Un
visiteur qui s'inscrit via l'API reçoit le rôle `user` et peut publier immédiatement un article de
blogue (`published_at = now()` posé automatiquement si `status: published`). Le back-office web,
lui, exige correctement `permission:create_articles`. *Correction : ajouter l'autorisation dans
`store()`, comme ses voisines.*

**H9 — SSRF sortant sur les webhooks, sans aucune garde**
`Modules/Webhooks/app/Jobs/DispatchWebhookJob.php:42` · A10 · corroboré · confiance élevée

L'URL de webhook est validée par `'url'` seulement, puis `Http::timeout(10)->post($endpoint->url, ...)`
part sans résolution DNS ni exclusion des plages privées. Le contraste est frappant avec le reste
du projet, que ce rapport félicite en section 4 : `MetaScraperService` et `SourceMarkdownFetcher`
ont une vraie garde SSRF (IPv4 + IPv6, fail-closed). Ce module-ci n'en a aucune. *Correction :
réutiliser la garde déjà écrite.*

**H10 — Provisionnement SCIM sans filtre d'organisation (IDOR inter-tenant)**
`Modules/Sso/app/Http/Controllers/Scim/ScimUserController.php:99-111` · A01 · corroboré · confiance moyenne

`store()` fait `User::where('email', $attributes['email'])->first()` — une recherche **globale**,
sans filtre de configuration SSO, alors que `show/update/patch/destroy` passent tous par
`findProvisionedOrFail()` scopé au tenant. Le fichier documente pourtant lui-même (lignes 27-32)
que l'isolation vaut pour **toutes** les actions : l'invariant est contredit par son propre code.
Conséquence : une organisation détenant un jeton SCIM valide peut provisionner un utilisateur avec
l'adresse d'un compte existant d'une **autre** organisation, écraser ses données, puis s'attacher
comme propriétaire du provisionnement — ce qui lui ouvre ensuite légitimement `PUT`/`DELETE`.

Le module `Sso` est **actif** (vérifié). Confiance moyenne plutôt qu'élevée : l'exploitation
suppose qu'une configuration SSO tierce avec jeton valide existe, ce que je n'ai pas vérifié.
*Correction : scoper la recherche à la configuration SSO courante, comme les autres actions.*

**H11 — L'API de recherche expose le nom et le COURRIEL de tous les comptes**
`Modules/Search/app/Services/SearchService.php:26-37` + `Modules/Search/config/config.php:13-14` +
`app/Models/User.php:49-55` · A01 + Loi 25 / RGPD · corroboré · confiance élevée

Trouvé au 3e round, dans un module que rien n'avait ouvert. Vérifié par moi-même, maillon par
maillon :

- `\App\Models\User::class` est en **première position** de `config('search.models')`, sans
  `class_exists()`, donc toujours indexé.
- `User::toSearchableArray()` retourne `['name' => ..., 'email' => ...]`.
- `User` **n'implémente pas** `shouldBeSearchable()` (0 occurrence) : rien ne l'exclut.
- `SearchService::search()` — celle qu'appelle l'API — **n'applique aucun filtre**, alors que
  `searchFront()` (ligne 119-122), utilisée par la recherche publique du site, applique bien
  `scopePublished()` ou `is_published`. L'écart entre les deux méthodes signe l'oubli.
- La route `GET /v1/search` n'exige que `auth:sanctum` + `throttle:search` : **aucune permission**
  (`Modules/Search/routes/api.php:14-16`). Module `Search` actif, route joignable (401 sans jeton).

La chaîne est complète et ne demande aucun privilège : l'inscription est libre, tout utilisateur
connecté peut s'émettre lui-même un jeton Sanctum sans restriction de portée depuis son tableau de
bord (`UserApiTokenController.php:33`), puis interroger `/api/v1/search?q=...&model=User`.

C'est une **communication de renseignements personnels** (courriels) à un tiers non autorisé, au
sens de la Loi 25 — plus directement qualifiable que H1, qui porte sur des témoins.

*Correction* : ajouter `shouldBeSearchable(): bool { return false; }` sur `User`, ou restreindre
`v1/search` à une permission d'administration. Le même défaut touche `Setting`, partiellement
atténué (`shouldBeSearchable()` y exclut les groupes `security`/`secrets`, mais pas
`is_public = false`).

**H12 — Traversée de répertoire dans l'import, lecture de fichiers arbitraires**
`Modules/Import/app/Http/Controllers/ImportController.php:56-72` · A01 · corroboré · confiance élevée

`execute()` valide `'file_path' => 'required|string'` — **aucune contrainte de préfixe, aucun rejet
de `..`** — puis appelle `Storage::disk('local')->path($validated['file_path'])`, qui concatène sans
normaliser (`PathPrefixer::prefixPath()`). Le fichier est ensuite parsé comme CSV et **mappé vers
les champs d'un modèle**, dont `page`. Un contenu ainsi lu peut donc atterrir dans une page
publiable, ce qui en fait un canal d'exfiltration.

La route est gardée par `permission:manage_imports`, que le rôle `admin` détient (cf. C1) : le
défaut est donc atteignable par un rôle qui ne devrait avoir qu'un droit d'import de contenu.
*Correction : dériver `file_path` côté serveur depuis l'étape de prévisualisation, ou `realpath()`
et vérifier que le chemin reste sous `storage_path('app/imports')`.*

### MOYENNE

**M1 — Registre des incidents de confidentialité absent** (Loi 25 art. 3.5/3.7). La politique
promet une procédure de notification, mais rien ne matérialise le registre interne obligatoire. La
seule table « incidents » du projet (`health_incidents`) est une page d'état de disponibilité.

**M2 — Jeton de déploiement passé en query string.** `public/_lvgit.php:29` lit `$_GET['t']` : le
jeton se retrouve dans les journaux d'accès du serveur et de Cloudflare. Le reste de l'endpoint est
**bien conçu** et je l'ai vérifié : `hash_equals` (résistant au timing), `proc_open` recevant un
**tableau** donc aucune injection possible, seeder filtré par liste blanche, et refus effectif en
production (403 testé sans jeton et avec jeton invalide). *Correction : passer le jeton en en-tête.*

**M3 — SSRF par réattribution DNS.** `MetaScraperService.php:84-95` et
`SourceMarkdownFetcher.php:320-355` valident l'IP puis laissent le client HTTP refaire sa propre
résolution : un domaine hostile peut répondre une IP publique à la validation puis une IP privée à
la requête. La garde existante est par ailleurs au-dessus du standard habituel (IPv4 + IPv6,
fail-closed en production). *Correction : épingler l'IP validée via `CURLOPT_RESOLVE`.*

**M4 — Politique de mot de passe incohérente.** L'inscription applique `PasswordPolicyRule` +
`PasswordNotCompromisedRule`, mais la réinitialisation (`ResetPassword.php:29`) et le changement
(`UserDashboardController.php:249`) n'exigent que 8 caractères — précisément le chemin emprunté
après une fuite.

**M5 — Isolation multi-locataire ouverte par défaut.** `BelongsToTenant.php:23-31` ne filtre que
si un locataire courant est résolu ; sinon la requête renvoie **toutes** les lignes. Le test du
projet lui-même le démontre. Les middlewares censés fixer le locataire ne sont câblés nulle part.
Sans effet aujourd'hui (module `Tenancy` désactivé), mais fuite silencieuse le jour où il servira.
*Correction : rendre le scope fail-closed.*

**M6 — 15 popups natives.** `onclick="return confirm(...)"` dans 12 fichiers Blade, alors que le
mécanisme conforme `data-confirm` existe et est utilisé **53 fois** ailleurs. Violation d'une règle
non négociable du projet.

**M7 — 3 modules sans aucun test** : `Ads`, `Community`, `Voting`. Déjà signalé le 22 juillet.
`Ads` porte la régie publicitaire, donc le revenu.

**M8 — Deux sollicitations simultanées à l'arrivée.** Capture à l'appui : le bandeau de consentement
et la modale d'infolettre s'affichent en même temps, avec deux bandeaux empilés au-dessus du titre.
On demande un consentement légal et une inscription commerciale dans le même écran.

**M12 — Aucune limitation de débit sur cinq écritures communautaires, et un vote sans déduplication.**
`Modules/Directory/routes/web.php:75,76,79,80,81` n'ont aucun `throttle`, alors que leurs voisines
immédiates du même groupe en ont un. Surtout, `CommunityController::toggleLike()` (lignes 330-356)
incrémente `upvotes` et attribue de la réputation **à chaque appel, sans déduplication** — alors que
`voteScreenshot()`, 130 lignes plus bas dans le **même fichier**, pose correctement un verrou de
cache de 30 jours par utilisateur. Un compte peut donc gonfler indéfiniment la réputation d'un
contenu. *Correction : répliquer le verrou déjà écrit.*

**M13 — Raccourcisseur d'URL public sans restriction de schéma.**
`Modules/Tools/app/Http/Controllers/QrDynamicLinkController.php:32` valide `['required','url']`,
là où `Modules/ShortUrl` valide `'url:http,https'`. Route publique (`throttle:20,1`, sans captcha)
créant un lien hébergé sous un domaine réputé : vecteur d'hameçonnage attractif.

**M14 — Les livres ignorent leur propre scope de publication.**
`Modules/Books/app/Http/Controllers/PublicBookController.php:26-42` fait `Book::orderBy(...)->get()`
et `Book::where('slug',...)->first()` **sans jamais appeler `->published()`**, alors que le modèle
porte le trait `HasPublishedState` et une colonne `is_published`. Tout brouillon serait donc public.
Impact actuel faible (un seul livre, déjà publié, aucun CRUD admin), mais le défaut vit en
production.

**M11 — Webhook SMS entrant sans vérification de signature (latent).**
`Modules/Booking/app/Http/Controllers/SmsInboundController.php:39-68` : aucune signature n'est
vérifiée, la route est exemptée de CSRF (légitime pour un webhook), et le client est identifié par
les **10 derniers chiffres** du numéro fourni dans le POST. Un mot-clé du corps déclenche
directement `$appointment->update(['status' => 'confirmed'|'cancelled'])`. Un POST forgé
confirmerait ou annulerait le rendez-vous de n'importe quel client.

**Classé moyen et non haut, après vérification de ma part** : le module `Booking` est **désactivé**
et `POST /webhook/sms/booking` renvoie **404 en production** (testé). C'est une bombe dormante, pas
une faille active — mais elle s'armerait le jour où le module serait réactivé, sans que personne
ne repense à la signature. *Correction : vérifier la signature du fournisseur avant toute
mutation.*

**M10 — Frais de port contrôlés par le client (zone argent).**
`Modules/Shop/app/Http/Controllers/CheckoutController.php:24,53,59` : `shipping_cost` est validé
`numeric|min:1|max:100` puis intégré tel quel au total, sans recalcul serveur. Les prix des
articles, eux, sont bien revalidés (`revalidatePrices()`). Impact borné (~99 $ par commande).

**M9 — 2 397 Ko par page** (images 998 Ko, styles 839 Ko, scripts 535 Ko) pour 77 requêtes.
Confirme le finding de juillet : styles de thème hérités (~470 Ko) et double pile jQuery/Bootstrap
(~375 Ko) coexistant avec Alpine.

### BASSE

**B1** — Duplication réelle du bloc Schema.org `DefinedTerm` entre `TermSchemaService` et
`Acronyms/.../show.blade.php:553-586` (même règle métier, deux implémentations).
**B2** — 4 méthodes mortes confirmées (`AiService::estimateCost`, `getAvailableModels`,
`JsonLdService::definedTerm`, `webPage`), dont deux déjà documentées mortes dans le projet.
**B3** — Clé de configuration inexistante `app.frontend_theme` (`master.blade.php:352`) là où le
reste du fichier utilise `frontend.theme` : bug dormant sur le cache-bust.
**B4** — Résidus locaux : `opcache_reset.php`, `audit-console-errors.log`, `.bak-*` de
`ToolDiscoveryService`.
**B5** — HSTS sans `preload`.
**B11** — `IngestController.php:20-27` compare le jeton d'ingestion par `!==` au lieu de
`hash_equals()`, et sa route n'a aucun `throttle` — alors que le patron correct est employé ailleurs
dans le projet (`_lvgit.php`, webhook Brevo).
**B10** — Second secret de webhook exposé dans l'URL (même famille que M2, autre endroit) :
`/api/webhooks/brevo/{secret}` (`BrevoWebhookController.php:23-29`). La comparaison est correcte
(`hash_equals`), seule l'exposition dans le chemin pose problème.
**B7** — Le trait `NotifiesIndexNow` ne vérifie pas `Module::isEnabled('SEO')` : la notification
continuerait d'agir module « désactivé » (défaut d'isolation, pas de panne — voir H6 retiré).
**B8** — Cron de correction ponctuelle laissé en `->everyMinute()` permanent
(`routes/console.php:185-204`), contrairement à la règle du projet. Il se neutralise par un fichier
drapeau, mais tourne chaque minute indéfiniment.
**B9** — Mon décompte des résidus était faux : `find Modules -iname "*.bak*"` en retourne **23**,
pas 3. Tous sont dans `.gitignore` (vérifié), donc jamais déployés.
**B6** — 6 commits portent encore une signature d'IA, contraire à la règle du projet (réécriture
d'historique nécessaire : décision du fondateur).

---

## 4. Ce qui est bien fait

À signaler, car un audit qui ne relève que les défauts donne une image fausse :

- **Gardes SSRF** dans les deux services de récolte : résolution DNS, exclusion des plages privées
  et réservées, IPv4 et IPv6, fail-closed en production. Au-dessus de la pratique courante.
- **Confidentialité des appels LLM** : `OpenRouterPrivacy` centralise `data_collection=deny` et
  `zdr=true`, appliqué systématiquement.
- **`PromptFromDraftService`** applique explicitement « ne jamais faire confiance à la sortie du
  modèle » : liste blanche stricte des clés, validation d'ancrage.
- **Anti-rejeu SAML** complet (signature, fenêtre temporelle, garde `InResponseTo`).
- **Purges automatiques** conformes au tableau de rétention publié, ligne à ligne.
- **Double opt-in** infolettre avec désabonnement RFC 8058.
- **Export et suppression de compte** réellement implémentés.
- **Anonymiseur** : vérifié par grep exhaustif sur 1 897 lignes, **aucun** appel réseau. La promesse
  de traitement local est tenue.
- **6 800+ tests** au vert, dont 52 modules sur 54 couverts.

---

## 5. Signalements écartés après vérification

Le skill impose de ne jamais porter au rapport un finding non reproduit. Ces cinq-là ont été
testés, puis écartés :

1. **Exécution de code à distance via `Blade::render()`** (signalée « critique »). Écartée, mais
   la vérification a demandé quatre passes et a corrigé deux fois mon propre test. Ce qui est
   mesuré, en conditions réelles :
   - **Garde 1** : `scopePublished()` exige `published_at <= now()`, or `UserArticleController::store()`
     ne renseigne jamais ce champ, aucun observer ni state ne le fait, et la colonne est nullable
     sans défaut. Mesuré : `published_at` nul, `Article::published()->count()` à 0, page en **404**.
   - **Garde 2**, indépendante : même en forçant `published_at`, la page rend 200 **sans exécuter**
     le contenu. Le modèle expose `safeContent()` qui applique `Purifier::clean(..., 'article')`
     (`Article.php:211-217`).
   - **Mais le danger est réel, pas imaginaire** : appelé hors de ce contexte, `Blade::render()`
     exécute bel et bien le `@php` d'un contenu contenant « <x- » (mesuré). Ce ne sont donc pas les
     gabarits qui sont inoffensifs, ce sont ces deux gardes qui tiennent - dont l'une, `published_at`,
     est **accidentelle** : elle protège par omission, pas par intention.
   Verrouillé par `Modules/Blog/tests/Feature/BladeRenderContenuArticleTest.php` (2 tests, verts),
   qui documente explicitement ce qui se passerait si l'une des deux gardes sautait.

   *Deux erreurs de ma part pendant cette vérification, corrigées* : un marqueur « 42 » qui
   apparaît fortuitement dans une page de 196 Ko, et un composant `<x-nexistepas/>` qui faisait
   lever `Blade::render()` avant l'exécution - donnant l'illusion d'une protection qui n'en était
   pas une.

2. **Skip link manquant / cible de 1x1 px** (WCAG 2.4.1 et 2.5.8). Faux positif : le lien **est**
   le premier élément tabulable et passe à **233×51 px** au focus, blanc sur fond sombre. Technique
   standard « masqué jusqu'au focus ». Le scanner mesure l'état non focalisé.
3. **Contrastes de 1:1, « blanc sur blanc »** (28 signalements). Faux positifs : les titres sont
   posés sur des **images sombres**, vérifié par capture ; le scanner remonte l'arbre jusqu'au fond
   blanc de la page. Les autres sont dans une **modale fermée**.
4. **~200 éléments « non atteignables au clavier »**. Très majoritairement des éléments de menus et
   carrousels fermés : 189 des 355 éléments interactifs ont une dimension nulle au moment du scan.
5. **7 requêtes SQL brutes avec concaténation**. Vérifié : la concaténation construit la **valeur
   liée** par `?`, jamais la requête. Aucune injection possible. De même, `app()->getLocale()`
   interpolé est strictement borné à `fr`/`en` en amont.

---

## 6. Priorités

| Ordre | Action | Effort | Pourquoi d'abord |
|---|---|---|---|
| 1 | **Vérifier si `moderator@laravel-core.test` existe en production** (C2), par requête SQL, jamais par tentative de connexion | très faible | Tranche entre « défaut de code » et « porte ouverte » |
| 2 | Liste blanche sur le champ `command` du planificateur (C1) | moyen | Le rôle « admin » vaut aujourd'hui une exécution de code serveur |
| 3 | Gater AdSense sur le consentement (H1) | faible | Non-conformité légale active, correctif connu et local |
| 4 | Filtrer `Str::markdown` ligne 1114 (H2) | très faible | XSS prouvé, une ligne, motif déjà présent |
| 5 | Fail-closed sur la signature Stripe (H7) | faible | Zone argent, le bon patron existe déjà dans le dépôt |
| 6 | Autorisation dans `ArticleApiController::store()` (H8) | très faible | Une ligne, ses voisines l'ont déjà |
| 7 | Garde SSRF sur les webhooks sortants (H9) | faible | Réutiliser la garde déjà écrite |
| 8 | Compléter la CSP (H3) | moyen | Rend H2 et ses semblables inopérants |
| 9 | Mettre à jour guzzle et commonmark (H4) | faible | 14 avis hauts, CommonMark atteignable |
| 10 | Registre d'incidents Loi 25 (M1) | moyen | Obligation légale, rien n'existe |

---

## 6 bis. Ce que la passe adversariale a changé

Le protocole `/100` impose de faire démentir ses conclusions par des agents frais. Deux ont été
lancés, à lentilles opposées. Bilan, sans complaisance :

| Ce que disait la 1re passe | Verdict après contradiction |
|---|---|
| « Aucun finding critique » | **Faux.** Deux critiques trouvés (C1, C2) dans des modules jamais ouverts |
| H6 « 4 modèles casseraient » (haute) | **Réfuté empiriquement.** Reclassé en basse (B7) |
| H5 « cause technique écartée » | **Prématuré.** Un candidat daté existait dans l'historique git |
| « 3 fichiers résiduels » | **Faux compte.** 23 fichiers (tous gitignorés) |
| H1, H2, et les 5 faux positifs écartés | **Tiennent**, l'un des adversaires les a même renforcés |

## 8. Ce que cet audit ne couvre PAS, et pourquoi je ne dis pas « 100 % »

Le protocole `/100` interdit d'annoncer la complétude sans deux verdicts adversariaux vides
consécutifs. **Ce n'est pas atteint, et je ne le maquillerai pas.** Quatre lentilles adversariales
ont été braquées sur cet audit ; voici ce qu'elles ont donné :

| Round | Verdict | Ce qu'il a changé |
|---|---|---|
| 1a — findings faux | `false` | H6 réfuté empiriquement, H5 déclaré prématuré |
| 1b — ce qui manque | `false` | **2 critiques** (C1, C2) + 4 hauts, dans des modules jamais ouverts |
| 2 — vérification | `erreurs: []` + 3 manques | H10, M11, B10 ajoutés ; lien C2 ↔ runner trouvé |
| 3a — validité | **`complet: true`, `erreurs: []`** | Rapport confirmé ligne par ligne, 3 renforts |
| 3b — zones restantes | 6 findings | **H11 (fuite de courriels)**, H12, M12-M14, B11 |

**La régularité de ces trouvailles est le vrai résultat.** Le round 3b a ouvert Search, Import et
Books — jamais lus jusque-là — et y a trouvé une fuite de renseignements personnels et une
traversée de répertoire. Sur 4 913 fichiers et 54 modules, il faut en tirer la conclusion
honnête : **ce n'est pas la plateforme qui a été auditée en entier, ce sont les zones sur
lesquelles une lentille a été braquée.**

### Zones jamais lues en profondeur, à traiter en priorité au prochain passage

- `Modules/Academy` (le plus gros module non couvert : cours, quiz, certificats, imports SCORM/H5P
  — seuls les points d'upload ont été vus)
- `Modules/Newsletter` (envoi, gabarits, campagnes) au-delà des contrôleurs publics
- `Modules/Decido`, `Modules/Journal`, `Modules/Notifications`, `Modules/Media` : survolés, pas lus
- `Modules/Books`, `Modules/Menu`, `Modules/Widget` : routes vues, code non lu
- Les **jobs de file d'attente** et les **commandes planifiées** dans leur ensemble
- Les **366 occurrences de `{!! !!}`** : environ 80 échantillonnées, ~285 jamais contrôlées une à
  une — c'est la plus grosse zone d'ombre restante pour le XSS stocké
- L'**accessibilité** : 2 gabarits testés sur des dizaines ; la **performance** : 2 gabarits
- Les **crons de production** : blocage externe (cPanel), consigné en section 1

### Ce que je peux affirmer, et ce que je ne peux pas

**Affirmable** : les 2 critiques, les 12 findings hauts et les 14 moyens listés ici sont **réels et
vérifiés dans le code**, la plupart par mes propres contrôles et non sur la parole d'un agent. Les
5 signalements écartés en section 5 l'ont été **par mesure**, pas par confort.

**Non affirmable** : que la plateforme ne contient pas d'autres défauts de gravité équivalente.
L'expérience de ces quatre rounds suggère fortement le contraire.

---

## 7. Preuve de nettoyage

- Aucun cron temporaire créé (aucun n'a été nécessaire ; la lecture des crons de production est le
  blocage externe consigné en section 1).
- Fichiers temporaires de l'audit : `_preuve_md_tmp.php` supprimé après usage (vérifié),
  `public/__qc-focal-cropper.html` supprimé, captures déplacées hors du dépôt.
- `browser_close` exécuté en fin de session Playwright.
- Aucune écriture en production. Aucune modification de code appliquée : tous les correctifs de
  rayon élevé sont **proposés**, conformément au skill.
