# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.107.10] - 2026-07-16

### Fixed
- **Décido — dates candidates ou options en double faussaient silencieusement le décompte des votes.** Aucune règle de validation n'empêchait de soumettre deux fois la même date candidate, ou deux options classiques au libellé strictement identique — `PollManageController::store()` créait alors deux `PollOption` distinctes en tout point identiques. Les votants qui cliquaient l'une ou l'autre carte voyaient leur vote silencieusement scindé entre les deux lignes en base, faussant le résultat final sans jamais faire remonter d'erreur (ex. 5 votes réels pour « Pizza » affichés 3/2 sur deux lignes séparées au lieu de révéler la vraie majorité). Règle `distinct` ajoutée sur `candidate_dates.*` et `options.*`.

Trouvé par une passe adversariale indépendante (skill `/100`, round 11, angle intégrité des données de vote). 41/41 tests Pest verts.

## [1.107.9] - 2026-07-16

### Added
- **Décido listé sur `/outils`, marqué « En construction ».** Migration réversible `2026_07_16_120000_seed_decido_tool_entry.php` ajoute l'entrée `decido` à la table `tools` (`is_under_construction=true`, pattern `updateOrInsert` identique à Minuteur visuel/Anonymiseur). Le carton apparaît pour tous les visiteurs sur `/outils` avec le badge « Bientôt », mais son lien pointe directement vers `/decido` (module dédié avec ses propres routes/contrôleurs — aucune colonne `external_url` n'existe dans le schéma `tools`, contrairement aux outils à vue générique). L'accès réel reste entièrement gouverné par le middleware `DecidoUnderConstruction` déjà en place (superadmin uniquement, testé) : un invité qui clique est redirigé vers la connexion, un utilisateur connecté non-superadmin reçoit 503.

### Fixed
- **Décido — pages privées indexables une fois le module public.** Aucune vue Décido (`vote.blade.php`, `results.blade.php`, `create.blade.php`, `manage/index.blade.php`) ne déclarait `@section('page_noindex', true)` — une fois `DECIDO_UNDER_CONSTRUCTION=false`, les pages contenant pseudonymes et choix de vote seraient devenues indexables par défaut. Ajouté aux 4 vues, avec preuve HTTP réelle (présence de la balise `<meta name="robots" content="noindex">`).

Trouvés par une passe adversariale indépendante (skill `/100`, round 10, angle SEO/confidentialité). 39/39 tests Pest verts.

## [1.107.8] - 2026-07-16

### Fixed
- **Décido — export ICS sans pliage de ligne conforme RFC 5545.** Un titre de sondage long ou contenant des caractères unicode produisait une ligne `SUMMARY:` de plusieurs centaines d'octets, dépassant largement la limite RFC 5545 §3.1 (75 octets/ligne) — risque de troncature par des lecteurs de calendrier stricts (Outlook/Exchange). `PollExportService::foldIcsLine()` ajouté, plie chaque ligne de contenu ICS sans jamais couper au milieu d'une séquence UTF-8 multi-octets.
- **Décido — aucune borne sur le nombre de dates candidates ni sur le volume total de créneaux générés.** Contrairement au type de sondage classique (déjà plafonné à 20 options), le type "date" n'avait aucune limite — 3800 options créées en test réel avec 40 dates candidates × une large plage horaire × un pas de 15 minutes. Ajout d'un plafond de 60 dates candidates et d'un plafond de 500 créneaux au total.

Trouvés par une passe adversariale indépendante (skill `/100`, round 9, angle réversibilité des migrations + cas limites de données — cycle complet rollback/remigrate testé réellement sans erreur, titre 255 caractères, unicode/emoji et XSS stocké via `voter_pseudonym` tous vérifiés propres). 38/38 tests Pest verts.

## [1.107.7] - 2026-07-16

### Fixed
- **Décido — boutons "+ Ajouter" / "Retirer" sous la cible tactile AAA sur `/decido/creer`.** Le fix touch-target des rounds 6-7 n'avait jamais été porté sur cette vue. La classe `.decido-touch-target` a été déplacée de `results.blade.php` vers `public/css/charte.css` (utilitaire global réutilisable, DRY) et appliquée aux 4 boutons concernés.
- **Décido — grille de vote peu utilisable au pouce sur mobile.** Les radios/checkboxes natifs (~14×14px, libellé cliquable ~22×21px) étaient bien sous 44×44px — jusqu'à 144 cibles trop petites pour un sondage de dates multi-jours. `public/vote.blade.php` utilise désormais des libellés pleine taille en pilules/blocs (44px minimum, `:has(input:checked)`/`:has(input:focus-visible)` en CSS pur, sans JavaScript) pour les 3 modes de vote.
- **Décido — créneaux incohérents lors des changements d'heure (DST).** L'arithmétique de `SlotGenerationService` opérait en heure locale (`America/Toronto`), traversant silencieusement les changements d'heure : un créneau de 30 minutes à cheval sur le passage à l'heure d'été durait en réalité 90 minutes une fois relu. Déplacée entièrement en UTC (sans DST par nature) — la durée d'un créneau est désormais toujours exacte, quel que soit le jour candidat.
- **Décido — libellés de créneaux ambigus au retour à l'heure normale.** Deux créneaux UTC distincts pouvaient produire un libellé local strictement identique (l'heure locale se produit deux fois ce jour-là), rendant impossible pour un votant de savoir lequel choisir. Le service ajoute désormais automatiquement le décalage UTC en désambiguïsation, uniquement sur les libellés en collision.
- **Décido — `class_exists()` ne détecte pas un module ShortUrl réellement désactivé.** `class_exists()` reste vrai même quand un module est désactivé via `modules_statuses.json` (nwidart garde les classes en autoload, seul le boot du `ServiceProvider` est coupé) — un lien court "fantôme" (pointant vers des routes jamais enregistrées, 404 réel) pouvait être créé et affiché à l'organisateur sans le moindre avertissement. Remplacé par `Modules\Core\Services\ModuleChecker::isAvailable()` (utilitaire DRY déjà existant dans le projet, vérifie `Module::has()`+`isEnabled()`) dans `Poll::shortUrl()`/`getShortUrlString()` et `PollManageController::createShortLink()`.

Trouvés par une passe adversariale indépendante (skill `/100`, round 8, angle responsive mobile réel + cas limites DST + frontières d'intégration entre modules — vérifiés en conditions réelles via Playwright et script PHP autonome). 35/35 tests Pest verts.

## [1.107.6] - 2026-07-16

### Fixed
- **Décido — requêtes redondantes (N+1) sur `Poll::getShortUrlString()`.** Un `ShortUrl::find()` brut, jamais mis en cache, était exécuté à chaque appel — la page de résultats appelant cette méthode 3 fois par chargement (6 requêtes `short_urls`/`short_url_domains` redondantes observées via query log réel). Remplacé par `$this->shortUrl` (relation Eloquent, mise en cache après le premier accès), nouveau test de non-régression comptant les requêtes réelles.
- **Décido — `decido:purge-expired` chargeait tous les sondages expirés en mémoire avant de les supprimer un par un.** Défaut de conception qui empire linéairement avec le volume (aucun problème aujourd'hui, confirmé par exécution réelle). Remplacé par un `DELETE` en masse — comportement strictement identique (aucun hook Eloquent `deleting`/`deleted` enregistré sur `Poll`, cascades options/votes déjà au niveau contrainte FK de la base de données).

Trouvés par une passe adversariale indépendante (skill `/100`, round 7, angle performance/N+1 + vérification end-to-end réelle : création/vote/clôture de sondages réels, contenu des exports CSV/ICS lu et validé, `decido:purge-expired` exécuté réellement). 32/32 tests Pest verts.

## [1.107.5] - 2026-07-16

### Fixed
- **Décido — suppression du compte créateur orpheline désormais le sondage au lieu de le cascader.** Décision explicite de l'utilisateur suite au finding round 5 : `cascadeOnDelete()` sur `creator_id` détruisait intégralement un sondage (créneaux + tous les votes de tiers) dès que le créateur supprimait son compte, sans préavis possible pour les votants anonymes (aucun compte requis pour voter). Nouvelle migration `2026_07_16_160000_orphan_instead_of_cascade_decido_polls_creator.php` : `creator_id` devient nullable + `nullOnDelete()` (réversible). Le sondage et tous les votes des participants survivent désormais, seule la gestion via compte devient indisponible (accès toujours possible via le lien admin à jeton).

## [1.107.4] - 2026-07-16

### Fixed
- **Décido — sélecteur "Type de sondage" inaccessible au clavier.** Les radios de `/decido/creer` utilisaient `class="d-none"` (display:none), les retirant de l'ordre de tabulation — violation WCAG 2.1.1 (niveau A) sur le tout premier champ du formulaire de création. Remplacé par `visually-hidden` (masqué visuellement, reste focalisable/actionnable au clavier) avec un anneau de focus visible sur la carte via `:has(input:focus-visible)`.
- **Décido — bug de données : votants homonymes silencieusement fusionnés.** Deux votants distincts partageant le même pseudonyme voyaient un de leurs deux votes disparaître du résumé et du tableau croisé de la page de résultats — `totalVoters`/`voterNames`/`matrix` étaient clés par `voter_pseudonym` (texte libre) au lieu de `voter_token` (identifiant réellement unique par votant). Reclé par `voter_token`, nouveau test de non-régression.
- **Décido — race condition (TOCTOU) entre vote en cours et clôture du sondage.** Le statut du sondage n'était vérifié qu'une seule fois en tout début de traitement d'un vote, sans verrou — un vote soumis dans la fenêtre entre cette vérification et l'écriture pouvait être accepté silencieusement même si l'organisateur venait de clôturer le sondage entre-temps. `PublicPollController::vote()` enveloppé dans `DB::transaction()` avec `lockForUpdate()` et re-vérification du statut à l'intérieur de la transaction.
- **Décido — contraste WCAG AAA du badge "Fermé" + cibles tactiles + accessibilité du drill-down.** Badge `#6c757d` (4.69:1, sous le seuil AAA 7:1) remplacé par `var(--c-dark)`. Six boutons secondaires (Copier ×3, Créer un lien court, Voir qui a répondu, Télécharger le QR code) sous la cible tactile AAA de 44×44px — le layout public du module n'hérite pas de la règle `.user-space` qui l'impose ailleurs sur le site — corrigés via une classe utilitaire `.decido-touch-target`. Bouton "Voir qui a répondu" doté de `aria-expanded`/`aria-controls`.
- **Décido — cartes "Type de sondage" sans état sélectionné visible.** Signalé directement par l'utilisateur (capture d'écran) : les 2 cartes de `/decido/creer` n'affichaient aucune différence visuelle entre l'état sélectionné et non sélectionné. Ajout d'une classe `.decido-poll-type-selected` (bordure + fond `var(--c-primary-light)`) plus un badge "✓ Sélectionné" (icône+texte, jamais la couleur seule).

Les 4 correctifs ci-dessus ont été trouvés par une passe adversariale indépendante (skill `/100`, round 6, angle WCAG 2.2 AAA + qualité du français + concurrence/données réelles). Un point supplémentaire a été signalé à l'utilisateur pour décision plutôt que corrigé unilatéralement : la suppression du compte créateur cascade la suppression intégrale d'un sondage, y compris les votes de tiers.

## [1.107.3] - 2026-07-16

### Fixed
- **Décido — injection de formule CSV (OWASP CSV Injection).** `voter_pseudonym`, texte libre contrôlé par un votant anonyme non authentifié, était écrit verbatim dans les cellules du CSV exporté par l'organisateur. Une valeur commençant par `=`, `+`, `-`, `@`, une tabulation ou un retour chariot est interprétée comme une formule active par Excel/Google Sheets à l'ouverture (ex. `=HYPERLINK(...)` pouvant exfiltrer des données). Nouvelle méthode `PollExportService::sanitizeCsvCell()` qui préfixe d'une apostrophe toute valeur à risque, appliquée à `voter_pseudonym` et `option->label`. Trouvé par une passe adversariale indépendante (skill `/100`, round 5).
- **Décido — aucun anti-abus sur la création de sondage ni le vote anonyme.** `decido.store` et `decido.vote.store` n'avaient aucune limite de fréquence, permettant en théorie un bourrage d'urnes (cookies `decido_voter_*` illimités) ou un spam de création de sondages. Ajout de `throttle:10,1` (création) et `throttle:20,1` (vote).
- **Décido — politique de rétention `expires_at` jamais appliquée.** Le champ était écrit à la clôture d'un sondage (`PollManageController::close()`) mais jamais relu nulle part ailleurs dans le module — aucune purge réelle ne se produisait malgré le commentaire de config l'annonçant. Nouvelle commande `decido:purge-expired` (pattern calqué sur `shorturl:cleanup-expired`), planifiée quotidiennement à 06h15 (`routes/console.php`).

## [1.107.2] - 2026-07-16

### Fixed
- **Décido — paramètres de génération de créneaux jamais persistés sur le sondage.** `duration_minutes`, `range_start_time`, `range_end_time` et `step_minutes` étaient validés et déjà présents dans `Poll::$fillable`, mais `PollManageController::store()` ne les assignait jamais à l'objet `$poll` avant sauvegarde — toujours `NULL` en base pour tout sondage de type date, bien que les créneaux eux-mêmes soient générés correctement (le service recevait les valeurs directement, pas via le modèle). Bloquait silencieusement toute fonctionnalité future de modification/régénération de créneaux. Trouvé par une passe adversariale indépendante (skill `/100`, round 4). Nouveau test Pest vérifie ces 4 colonnes après un vrai `fresh()` depuis la DB.
- **Décido — impasse UX : aucun lien vers la gestion d'un sondage depuis « Mes sondages ».** Un créateur de sondage connecté qui perdait le lien admin à jeton reçu à la création n'avait plus aucun moyen d'accéder à la gestion de son propre sondage, malgré un bypass propriétaire déjà présent dans `PollManageController::authorizeManage()` (`Auth::id() === $poll->creator_id`) — ce bypass n'était simplement jamais exploité par aucune vue. Ajout d'un bouton **« Gérer »** sur chaque ligne de la liste `/decido`, exploitant ce bypass existant. Vérifié visuellement (navigation réelle jusqu'à la page de résultats, 200, aucune erreur 403/404) et par un nouveau test Pest.

## [1.107.1] - 2026-07-16

### Fixed
- **Décido — fuseau horaire manquant dans `PollExportService::exportIcs()`.** Le même bug corrigé dans `results.blade.php` (v1.107.0) était aussi présent dans l'export ICS : `DTSTART`/`DTEND` utilisaient `->utc()` directement sur une valeur `Carbon` déjà mal étiquetée par le cast Eloquent (`config('app.timezone')` = `America/Toronto` réinterprète à tort la valeur UTC stockée comme étant déjà en heure de Québec), causant un décalage de 4h dans le fichier `.ics` téléchargé. Trouvé par une passe adversariale indépendante (skill `/100`), reproduit empiriquement (`20260801T180000Z` au lieu de `20260801T140000Z`), corrigé par reparse explicite de la valeur brute comme UTC. Nouveau test Pest asserte la valeur `DTSTART`/`DTEND` exacte après un vrai `fresh()` depuis la DB — condition nécessaire pour déclencher le bug, que l'ancien test ne couvrait pas.

## [1.107.0] - 2026-07-16

### Changed
- **Décido — refonte UX de la page de résultats (superadmin).** L'ancien design (une carte pleine largeur par créneau candidat, jusqu'à 16+ cartes empilées pour un sondage de dates = page extrêmement longue) est remplacé par une architecture en divulgation progressive : un résumé **« Meilleurs créneaux »** toujours visible en haut de page (tous les ex-æquo au meilleur score, avec le compte réel oui/peut-être/non/sans réponse — jamais un simple pourcentage isolé) avec un **drill-down interactif** (Alpine.js) qui affiche qui a répondu quoi sans avoir à ouvrir la grille complète, puis une section **« Comparer toutes les réponses »** repliée par défaut (élément HTML natif `<details>`, accessible clavier sans JS custom) contenant le tableau croisé complet (vrai `<table>` sémantique avec `<caption>` et `<th scope>`, colonnes groupées par jour pour un sondage de dates, en-têtes et première colonne figées, icônes + texte pour coder l'état — jamais la couleur seule, conforme WCAG 2.2 AAA). Design établi par recherche `pp_search` (bonnes pratiques listes longues et pattern Framadate, juillet 2026) puis validé indépendamment par Codex (93-96/100) et Gemini via `agy` (92/100), les deux convergeant sur la même architecture sans concertation.

### Fixed
- **Décido — en-têtes du tableau croisé affichaient l'heure en UTC brute au lieu de l'heure du fuseau du sondage** (ex. « 13h00 » au lieu de « 9h00 »), découvert lors de la vérification visuelle de la refonte ci-dessus. Cause racine : `config('app.timezone')` de l'application est `America/Toronto` ; `starts_at` est stocké en UTC par `SlotGenerationService`, donc le cast Eloquent `datetime` réinterprète à tort la valeur brute comme étant déjà en heure de Québec à la lecture (pas de conversion automatique) — un simple `->timezone()` appliqué sur cette instance déjà mal étiquetée ne changeait donc rien. Fix : reparser explicitement la valeur brute comme UTC (`Carbon::parse($valeur->format('Y-m-d H:i:s'), 'UTC')`) avant de convertir vers le fuseau du sondage.

## [1.106.0] - 2026-07-16

### Added
- **Nouvel outil Décido** (`Modules/Decido`, nwidart) : générateur de sondages type Framadate repensé au complet (aucun code Framadate réutilisé). Deux types de sondages : **sondage de dates** (l'organisateur choisit d'abord la durée de la rencontre, la plage horaire et le pas entre créneaux ; `SlotGenerationService` génère automatiquement tous les créneaux candidats à partir des dates proposées) et **sondage classique** (options libres, mode `single_choice` ou `approval`). Vote anonyme sans compte requis (identité par cookie signé `decido_voter_{public_id}`, UUID, `updateOrCreate` idempotent pour la revote). Gestion sans compte pour l'organisateur non plus : lien admin à jeton (`admin_token_hash` SHA-256, `hash_equals`), généré une seule fois et affiché une seule fois. Export CSV et ICS (RFC 5545 minimal, sans dépendance Composer) disponibles depuis la page de gestion, ICS uniquement après clôture avec créneau final choisi. Réservé aux utilisateurs connectés pour la création ; **en construction (503 + `noindex`, superadmin-only)** jusqu'à mise en ligne publique. 20 tests Pest (création, vote, revote, clôture, exports, permissions admin-token, gate under-construction).

### Fixed
- **Décido — `TypeError` sur la création d'un sondage de dates** : `SlotGenerationService::generateSlots()` déclare `int $durationMinutes`/`int $stepMinutes` (typage strict) mais `PollManageController::store()` transmettait directement les valeurs de `$request->validate(['duration_minutes' => 'integer', ...])`, qui restent des **strings** après validation (la règle Laravel `integer` valide le format, elle ne caste pas la valeur). Les tests Pest passaient des entiers PHP natifs directement au service et ne l'ont donc jamais détecté ; découvert seulement à la vérification visuelle Playwright (soumission d'un vrai formulaire HTML → POST `application/x-www-form-urlencoded` → toutes les valeurs sont des strings). Fix : cast `(int)` explicite au point d'appel dans le contrôleur.
- **Décido — validation du vote `yes_no_maybe` bloquait tout vote partiel.** Chaque créneau généré (potentiellement 16+ pour une seule journée) portait la règle `required`, forçant un votant à répondre Oui/Peut-être/Non à **tous** les créneaux avant de pouvoir soumettre — contraire au principe même de l'outil (répondre seulement aux créneaux pertinents), découvert en testant un vote réel via Playwright. Fix : règle par créneau passée à `sometimes`, avec `min:1` sur le tableau `votes` global pour continuer à refuser une soumission totalement vide.

## [1.105.1] - 2026-07-12

### Fixed
- **Bandeau `.wpo-breadcumb-area` (titre de page + fil d'Ariane, en haut de presque toutes les pages du site) prenait trop de place verticale.** `min-height: 400px` → `250px` (aligné sur la valeur déjà utilisée en mobile via media query `<767px`, désormais redondante et retirée). Vérifié visuellement via Playwright sur 2 gabarits (`/glossaire`, `/academie`) × 3 résolutions (desktop 1440px, tablette 768px, mobile 390px) : titre et fil d'Ariane restent bien centrés, aucun chevauchement avec le contenu qui suit.

## [1.105.0] - 2026-07-12

### Added
- **Consolidation des 3 widgets admin flottants en un seul menu déroulant.** Les pages publiques accumulaient jusqu'à 3 badges superposés pour un superadmin (badge+menu "⋮" `admin-bar`, toggle "Lecture/Édition" `mode-toggle`, pastille "Modifié il y a X" `admin-activity-mini`) — collision déjà documentée dans un commentaire de `table-of-contents.blade.php`. Le composant `Modules/Core/resources/views/components/admin-bar.blade.php` accepte désormais deux props optionnelles, `model` (ajoute une ligne d'information "Modifié il y a X · causer" dans le menu, si Activitylog est disponible pour le modèle) et `editUrl` (ajoute une bascule Lecture/Édition dans le menu, préservant exactement le mécanisme existant : `localStorage` clé `laveille.edit_mode`, classe `body.edit-mode`, script de délégation de clic sur `[data-editable]`). `admin-action-menu.blade.php` gagne deux nouveaux types d'item (`info`, `alpineClick`) pour supporter ces entrées sans dupliquer sa logique existante (wireClick/method+url/url restent inchangés).
- Appliqué sur les **11 pages publiques** qui affichaient au moins un des 3 anciens widgets ou auraient dû en afficher un : Glossaire, Actualités, Annuaire, Acronymes, Blog (widgets fusionnés), Journal, Livres, Académie (cours), Collections Annuaire, Outils (vue générique), mini-site Auteurs (widget ajouté). Sur Journal spécifiquement, **aucune bascule Lecture/Édition n'a été ajoutée** (choix délibéré) : un superadmin peut modérer/supprimer un journal mais plus l'éditer silencieusement (cf. correctif sécurité v1.104.0) — proposer un raccourci d'édition aurait contredit cette décision.
- Gate de la pastille "Modifié" resserrée de `@auth` (n'importe quel utilisateur connecté) à `@can('view_admin_panel')`, cohérent avec le reste du menu.
- Nouveau helper global `reading_time_minutes(?string $text): int` (`Modules/Core/app/Helpers/helpers.php`), centralise la formule `max(1, ceil(str_word_count(strip_tags($text)) / 200))` dupliquée à 3 endroits (`Modules/News/resources/views/public/show.blade.php`, `partials/article-card.blade.php`, `Modules/Authors/app/Livewire/AuthorEditor.php::computeReadingTime()`).

### Fixed
- **Le menu déroulant du profil (avatar, header) pouvait s'afficher tronqué/masqué derrière d'autres éléments flottants** (widget admin consolidé, onglets sticky de l'Académie, clone `.sticky-header` du script de scroll) — signalé par capture d'écran en cours de session. Cause racine : `.wpo-site-header .header-right { position: relative; z-index: 991 }` crée son propre contexte d'empilement CSS, ce qui plafonne tous ses enfants — dont le dropdown profil (`z-index: 9999` inline, `header.blade.php`) — à 991 face à n'importe quel élément `position: fixed` **hors** du header, indépendamment du z-index inline déclaré sur le dropdown lui-même. `z-index: 991` → `10000` (`public/themes/bloggar/sass/style.css`), confirmé par diagnostic Playwright réel (inspection des contextes d'empilement) puis par vérification visuelle avant/après scroll. Corrigé pour de bon un bug latent qui existait déjà avant l'ajout de l'admin-bar consolidé (les onglets Académie, présents depuis plus longtemps, provoquaient déjà la même collision).
- **`style.css` (thème Bloggar) n'avait aucun cache-bust** contrairement à `charte.css`/`components.css`/`fonts.css` — un visiteur ayant déjà ce fichier en cache n'aurait jamais reçu le correctif de z-index ci-dessus. Aligné sur le pattern `?v={{ filemtime(...) }}` déjà en place (`master.blade.php`).
- Régression introduite puis corrigée pendant la même session : une balise `@endauth` orpheline dans `Modules/News/resources/views/public/show.blade.php` (mon édition initiale avait retiré le `@auth` d'ouverture sans retirer le `@endauth` correspondant, situé après un bloc `@can` intercalé pour la capture d'écran assistée) cassait la compilation Blade de la page — détectée par la suite Pest (`NewsComicViewerTest`), corrigée, suite complète revérifiée à 0 échec (2280 passed, 209 skipped).

## [1.104.1] - 2026-07-12

### Fixed
- **Incident P0 production (2026-07-11) : 500 pour tout utilisateur connecté sur Actualités/Glossaire/Annuaire, cause racine complète et durcissement du pipeline CI.** Un fichier de migration (`Modules/News/database/migrations/2026_07_10_160000_backfill_auto_tool_detection.php`) avait été supprimé du dépôt git (commit `9502674a`) mais était resté physiquement présent en production, car le workflow `.github/workflows/deploy.yml` déploie via `rsync` sans le flag `--delete` : les fichiers retirés de git n'étaient jamais retirés du serveur. Ce fichier zombie contenait un `chunkById()` non borné qui faisait systématiquement timeout l'étape `php artisan migrate --force` à chaque déploiement — mais le `|| true` de cette étape (ajouté 2026-05-03, fix L15) avalait cet échec silencieusement depuis l'origine, empêchant TOUTES les migrations postérieures de s'exécuter, dont les 3 migrations du nouveau module Journal (2026-07-11) et la migration `add_review_tracking_to_reports_table` : le composant "+ Ajouter à mon journal", intégré sur ces trois familles de pages publiques, requêtait alors une table `journals` inexistante.
- Correctifs déjà appliqués directement en production (hors dépôt) avant ce commit : fichier de migration zombie neutralisé en no-op via cPanel, puis `php artisan migrate --force` rejoué manuellement avec succès (3 migrations Journal + 1 migration reports confirmées `DONE`).
- Durcissement `.github/workflows/deploy.yml` : retrait du `|| true` sur `php artisan migrate --force` (tout échec de migration fait désormais échouer le job CI visiblement, au lieu d'être masqué) + ajout d'un `timeout 300` (5 min) pour continuer à borner le risque qu'un futur backfill non borné bloque indéfiniment le pipeline, sans pour autant masquer l'échec. `--delete`/`--delete-after` sur rsync délibérément **NON activé** après audit : `public/fonts/` (police self-hébergée Caveat, v1.104.0) est présent en production, gitignoré localement et absent de la liste `--exclude` du rsync — l'activer aurait supprimé les polices en prod au prochain déploiement. Risque documenté en commentaire dans `deploy.yml` avec la marche à suivre pour l'activer un jour en sécurité (exclusions complémentaires + `--dry-run` obligatoire).

## [1.104.0] - 2026-07-12

### Added
- **Refonte visuelle "accents papier discrets" de la page publique du Journal** (`show.blade.php`) : police manuscrite self-hébergée `Caveat` (poids 600, latin+latin-ext, `public/fonts/caveat/`) appliquée uniquement à la date et aux citations, jamais au corps de texte ; papier ligné très subtil en fond des blocs (`repeating-linear-gradient`, opacité ~0.045) ; coin corné discret sur les photos du gabarit Carnet photo. Génération du CSS déléguée à `mcp__hermes__model_invoke` (Qwen3-max), validée et corrigée par revue avant intégration.
- **Migration complète des boutons du module Journal vers le composant DRY `<x-core::button>`** (4 vues) — remplace 25 boutons Bootstrap bruts par le composant tokenisé de la charte (focus AAA, variants primary/secondary/danger déjà éprouvés site-wide).

### Fixed
- **Sécurité — le superadmin pouvait éditer silencieusement le journal privé de n'importe quel utilisateur.** Le bypass global `Gate::before()` (`Modules/RolesPermissions`) accordait un accès total à toutes les policies, y compris `JournalPolicy::update()` qui n'avait volontairement aucune exception admin. Corrigé par une exclusion ciblée (ability `update` sur `Journal` uniquement) — le pouvoir de modération/suppression admin reste intact. Confirmé juridiquement pertinent par veille (Loi 25/PIPEDA/RGPD : l'édition non consentie de contenu personnel excède la finalité de modération légitime).
- **Assignation de rôle non-atomique et `email_verified_at` jamais posé sur connexion OTP** (`Modules/Auth/MagicLinkController`), trouvés par simulation E2E : un échec partiel de `assignRole()` laissait un compte orphelin sans rôle de façon permanente ; un utilisateur connecté uniquement par code OTP était bloqué par les routes gatées `verified` alors que le code prouve déjà la possession du courriel. Les deux corrigés (transaction DB + `email_verified_at` posé sur vérification OTP réussie), 31/31 tests Auth verts.
- **Bug de compilation Blade** : la directive `@js()` ne se compile pas correctement à l'intérieur d'un attribut de balise composant (`<x-core::button @click="...@js(...)...">`), cassait le bouton "Supprimer" de `/journaux`. Corrigé en pré-calculant via `{{ Illuminate\Support\Js::from(...) }}` (echo standard) au lieu d'imbriquer la directive.
- **Cache-bust manquant sur `fonts.css`** (`master.blade.php`) : les visiteurs ayant déjà ce fichier en cache ne recevraient jamais une police nouvellement ajoutée (repli silencieux sur `cursive` générique). Aligné sur le pattern `?v={{ filemtime(...) }}` déjà utilisé pour `charte.css`/`components.css`.
- 2 bugs de spécificité CSS trouvés par vérification visuelle (citation manuscrite écrasée par une règle globale du thème sur le `<p>` enfant généré par Tiptap ; couleur de la date écrasée par `.wpo-blog-single-section p`), corrigés par sélecteurs qualifiés/ciblage explicite.

### Verified
- Simulation E2E complète du module Journal (skill `/simulation`) : 4 rôles (guest, owner, other_user, admin) testés avec régression complète relancée après chaque correctif, jusqu'à un passage 100% propre sans aucune correction nécessaire. Anti-IDOR vérifié rigoureusement (URL directe, DELETE forgé, appel Livewire direct sur ressource étrangère) — tous bloqués correctement.

## [1.103.0] - 2026-07-11

### Added
- **Journal personnel** (nouveau module `Modules/Journal`) : chaque utilisateur connecté peut créer des journaux privés ou publiés (`/journaux`, `/journal/creer`, `/journaux/{slug}/editer`, `/journaux/{slug}`), composés de blocs de contenu réordonnables (texte riche, image, vidéo YouTube, source liée) via un constructeur Livewire (`JournalBuilder`) avec 4 gabarits de mise en page. Intégration « + Ajouter à mon journal » sur les pages Actualités, Glossaire et Annuaire (dropdown des journaux de l'utilisateur, ajout instantané par requête `fetch`, gate d'autorisation serveur anti-IDOR à chaque action). Page publique de lecture avec JSON-LD Article, réutilisation du système Signaler + extension du régime avis-et-avis (`/annuaire/retrait`) au contenu Journal. 33 tests Pest (modèle/policy, service de blocs, cycle de vie Livewire, HTTP/modération) — zéro régression sur 256 tests Journal+Directory+Authors.

### Fixed
- **Éditeur de texte riche (Tiptap) non fonctionnel dans le constructeur Journal.** Le panneau « + Texte » affichait une barre d'outils aux icônes vides puis, une fois corrigé, un éditeur complètement inerte (`ReferenceError: tiptapEditor is not defined`) : cause racine réelle = condition de course entre le chargement asynchrone du script de l'éditeur (`resources/js/tiptap-frontend.js`, module Vite) et le morph Livewire qui insère et évalue immédiatement le `x-data` Alpine correspondant, déclenché par le clic sur « + Texte » (contenu absent du rendu initial de la page, contrairement aux autres usages déjà en production de ce composant partagé sur Annuaire/Auteurs). Corrigé en chargeant le script au niveau racine du composant Livewire `JournalBuilder`, dès le rendu initial de la page d'édition — même mécanisme déjà éprouvé pour le plugin de réordonnancement par glisser-déposer dans ce même fichier.
- **Erreur 500 sur `/admin` en environnement local** (colonne `newsletter_subscribers.deleted_at` manquante) et **~150 migrations en retard sur la base de données de développement locale**, dont une table `dictionary_categories` jamais peuplée par une migration versionnée (seedée manuellement en production à l'origine) — reliquat d'une restauration incomplète après un incident `migrate:fresh` accidentel du 2026-07-04. Nouvelle migration idempotente et réversible qui comble définitivement cette lacune pour tout environnement futur (local neuf, CI). Aucun impact production (déjà correctement peuplée, migration sans effet si déjà appliquée).

## [1.102.0] - 2026-07-10

### Added
- **Auto-détection des outils annuaire à la publication d'une actualité.** Le bouton manuel « Suggérer les outils détectés » nécessitait une action admin ; les outils mentionnés dans une actualité sont désormais liés automatiquement dès la publication (`is_published` false→true, couvrant la publication auto par le cron `news:fetch` et la bascule manuelle admin), via `AutoDetectNewsToolsJob` (queue `news-tools`, calqué sur `PurgeCloudflareCacheJob`), déclenché depuis `NewsArticleObserver`. Les liaisons automatiques sont marquées `source=auto` en base et n'écrasent jamais une sélection manuelle existante (`NewsToolSyncAction::attachAuto()`, ajout pur) ; le bouton manuel reste disponible pour compléter/ajuster (`source=manual`, comportement inchangé). Worker de queue planifié (hébergement mutualisé sans démon, même convention que la queue `newsletters`) + commande manuelle bornée `news:backfill-auto-tools --limit=200` pour les actualités déjà publiées sans outil lié. 6 nouveaux tests Pest (24/24 verts sur le module News, aucune régression).

### Fixed
- **Incident de déploiement évité de justesse.** Une première version du backfill (migration non bornée) a bloqué le pipeline CI plus de 10 minutes sur le backlog réel de production. Run annulé avant toute réplication en base (transaction Laravel jamais validée) ; migration retirée au profit de la commande manuelle bornée ci-dessus, rejouable sans risque.

## [1.101.1] - 2026-07-10

### Added
- **Planche assemblée de la BD « Itération »** déployée sur `/glossaire/iteration` (`public/bd/iteration/`, formats avif/webp/jpg en 1600px, 1024px et miniature 600px, `manifest.json`). Standard `ComicLibrary`/`comic-viewer` déjà éprouvé (rançongiciel, deepfake, etc.) — contenu statique uniquement, aucun code touché.

## [1.101.0] - 2026-07-10

### Added
- **6 nouveaux termes de glossaire** : MTIA (puce IA custom de Meta), Broadcom, TSMC, AMD, PyTorch et DMA (Digital Markets Act, règlement européen — orthographe officielle vérifiée « Markets » au pluriel, ajoutée en `acronym_full` et en alias pour l'auto-lien site-wide). Standard 10 champs du skill `/glossaire` respecté (définition, analogie, exemple, anecdote, réponse en une phrase, FAQ, sources datées et signées, alias, icône, type/difficulté). Contenu rédigé via `mcp__hermes__model_invoke` à partir de faits vérifiés (recherche `pp_search`/fallback `sonar-pro`), images générées via `/nanobanana` (compte Gemini Workspace), migrations réversibles (`Modules/Dictionary/database/migrations/2026_07_10_*`).
- **Bande dessinée pédagogique « Itération »** (personnage Octopus) pour vulgariser `/glossaire/iteration` : 5 illustrations de case livrées (flux narratif définir → répéter → nommer l'époque → résumer) accompagnées du fichier `iteration-structure.md` (textes de bulles/encadrés fact-checkés). Conforme au périmètre resserré du skill `/bd` (2026-07-07) : images de contenu seules, sans contour ni bulle rendue, assemblage laissé à l'utilisateur.

### Fixed
- Investigation approfondie (round 2) du signalement « Service Worker was updated because 'Update on reload' » répété : confirmé qu'il ne s'agit pas d'une boucle serveur (5 minutes d'observation continue sans croissance des messages, aucun minuteur caché dans le code). La cause la plus probable est un comportement natif de Chrome DevTools (message émis à chaque reload réel tant que la case « Update on reload » est cochée), amplifié par « Preserve log ». Aucun correctif de code nécessaire.

## [1.100.0] - 2026-07-09

### Added
- **État de chargement du lecteur flip-reader avec LQIP, squelette et optimisation des priorités.** Sur signalement utilisateur de « pages blanches constantes » à l'ouverture des extraits, un état de chargement complet a été implémenté dans le composant flip-reader. La solution repose sur une veille des meilleures pratiques 2026 (squelette + blur-up + priorité de chargement) plutôt qu'un simple spinner générique, jugé moins performant pour un contenu à mise en page connue. Détails techniques : génération d'images LQIP (~40 px de large, ~4 Ko chacune via ImageMagick) pour les 97 pages d'extraits existantes (5 livres), affichées instantanément et floutées en attendant l'image nette avec un fondu CSS de 220 ms ; `Book::excerptPages()` (`Modules/Books/app/Models/Book.php`) retourne désormais une clé `lqip` par page (chemin ou null si absent), avec correction d'un bug réel au passage : le glob `page-*.jpg` comptait aussi les nouveaux fichiers `-lqip.jpg` comme des pages, désormais filtrés explicitement ; squelette shimmer ajouté au composant générique `Modules/FrontTheme/resources/views/components/flip-reader.blade.php`, désactivé automatiquement sous `prefers-reduced-motion` ; retrait de `loading="lazy"` sur l'image de la page actuelle (`fetchpriority="high"` à la place), ce lazy-load étant inapproprié pour du contenu déjà à l'écran ; `aria-busy` sur la case en cours de chargement et annonce `aria-live` sobre (« Chargement de la page… », reprend le compteur de pages une fois chargée), sans duplication du mécanisme d'annonce existant. Vérifié visuellement avec un réseau ralenti simulé (CDP) confirmant le bon affichage du squelette et du flou LQIP pendant le chargement sur deux livres différents ; navigation clavier/souris et absence de rognage (`object-fit:contain`) reconfirmées sans régression. 12/12 tests Pest verts (3 nouveaux).

## [1.99.1] - 2026-07-09

### Fixed
- **Régression visuelle sur le lecteur flip-reader (page rognée sur grands écrans).** Le correctif précédent (v1.99.0) avait résolu le clic souris bloqué sur le bouton "Page suivante" mais avait introduit une régression non détectée : sur fenêtres hautes (ex. 1717x1151), le titre de la page 1 apparaissait rogné en haut. Cause : `.fpr-book` combinait `width:100%` explicite avec `aspect-ratio` et `max-height:100%`, or l'algorithme CSS "transferred size" ne réduit la largeur que si `width` est `auto`. La hauteur était plafonnée mais la largeur restait à 900px, créant une boîte 900x1063 au lieu de 708x1063, et `object-fit:cover` rognait le haut/bas. Tentative de `width:auto` (boîte effondrée à 0x0, aucune dimension pour amorcer aspect-ratio). Correctif final (`Modules/FrontTheme/resources/views/components/flip-reader.blade.php`) : `.fpr-book` utilise `width:100%; height:100%; max-width:900px` sans `aspect-ratio` ; l'image passe en `object-fit:contain` (plus de rognage) ; StPageFlip en mode `stretch` préserve son ratio en interne. Vérifié par mesures DOM et captures sur 2 tailles de fenêtre (1717x1151, 1280x800) et 2 livres à ratios de page différents : plus aucun rognage, clic souris toujours fonctionnel. 9/9 tests Pest verts.

## [1.99.0] - 2026-07-09

### Fixed
- **Lecteur flip-reader : bouton "Page suivante" inaccessible.** Le lecteur "feuilleter" livré en 1.98.0 présentait un bug bloquant au clic souris : le bouton "Page suivante" (›) devenait injoignable (timeout Playwright confirmé, utilisateur signalant "impossible de lire les pages de prévisualisation"). La cause racine, identifiée par mesure DOM directe (`document.elementFromPoint` aux coordonnées du bouton retournait la balise IMG, pas le bouton), venait de l'absence de `max-height` sur `.fpr-book`. Un simple `aspect-ratio` dérivait la hauteur de la largeur : pour des pages portrait dans la modale (scène de hauteur fixe), le livre calculait une hauteur supérieure à l'espace disponible et débordait symétriquement (centré par le flex parent) par-dessus la barre de navigation `.fpr-bar`. Correction (`Modules/FrontTheme/resources/views/components/flip-reader.blade.php`, CSS uniquement) : ajout de `max-height: 100%` sur `.fpr-book` (force le navigateur à contraindre aussi la largeur via l'algorithme de "transferred size" de `aspect-ratio`, comme un `object-fit: contain`), plus des `z-index` explicites (`.fpr-bar` à 2, `.fpr-stage` à 1) en filet de sécurité pour garantir la cliquabilité de la barre au-dessus de tout contenu injecté par StPageFlip. Revérifié par clics souris réels (pas seulement au clavier) sur 2 livres à ratios de page différents : navigation avant et arrière fonctionnelle sur plusieurs essais consécutifs. 9/9 tests Pest toujours verts.

### Changed
- **Titre de section catalogue : "Essais" remplacé par "Guides pratiques".** Sur demande de l'utilisateur, le titre de section du catalogue `/livres` passe de "Essais" à "Guides pratiques" (`Modules/Books/resources/views/public/index.blade.php`), un intitulé jugé plus accessible que le terme littéraire "essais" pour désigner les 2 livres pratiques (conformité IA pour PME, parentalité numérique). La section "Fiction" (trilogie Nexus Neural) n'est pas touchée.

## [1.98.1] - 2026-07-09

### Fixed
- **La librairie StPageFlip vendorisée (flip-reader) ne se déployait jamais en prod (404).** Le pipeline `.github/workflows/deploy.yml` exclut `vendor/` du rsync pour ne jamais copier le vrai dossier `vendor/` composer, mais le motif n'était pas ancré à la racine (`vendor/` au lieu de `/vendor/`) - il excluait donc aussi `public/vendor/page-flip/`, livré en 1.98.0. Détecté par vérification directe en production (`curl` sur `page-flip.browser.js` -> 404) après le déploiement de 1.98.0. Corrigé en ancrant le motif (`--exclude='/vendor/'`), aucun impact sur l'exclusion du vrai `vendor/` composer.

## [1.98.0] - 2026-07-09

### Added
- **Nouveau lecteur "feuilleter" (flip-reader) intégré dans l'onglet Extrait des fiches livre.** Composant Blade générique et réutilisable `Modules/FrontTheme/resources/views/components/flip-reader.blade.php` avec partial partagé `partials/flip-reader-body.blade.php` (modal/inline, zéro duplication), basé sur la librairie StPageFlip vendorisée localement à `public/vendor/page-flip/page-flip.browser.js` (npm pack, aucun CDN externe pour respecter la Content-Security-Policy). Nouveau helper `Book::excerptPages()` qui scanne `public/images/livres-extraits/{slug}/page-*.jpg` (tri naturel, dimensions lues via getimagesize) et affiche 15 à 26 pages par livre (couverture, table des matières, extraits de chapitres réels) générées depuis les dernières versions vérifiées des manuscrits sources (deux corrections de fraîcheur appliquées : Livre 1 utilisait un PDF du 7 mai remplacé par la version du 1er juillet avec différences de contenu réelles ; Tome 1 utilisait un PDF du 26 décembre remplacé par la version du 5-6 janvier avec conversion typographique dialogue tiret vers guillemets). Accessibilité complète : navigation clavier (flèches, Home/End, Échap avec restauration du focus), mode simplifié automatique si `prefers-reduced-motion` ou échec de chargement de la librairie, annonce `aria-live="polite"` sobre (uniquement au changement de page), cibles tactiles 44x44px, contrastes WCAG AAA (8,81:1 à 18,65:1). Composant volontairement générique (props: pages/triggerLabel/title/mode/downloadable) sans concept de "livre" en dur, prévu pour une réutilisation future (lecteur d'actualités/glossaire).

### Changed
- **CTA "version papier" passé en primaire pour les 5 livres.** Auparavant Kindle était primaire pour la trilogie Nexus Neural, changé sur demande explicite (le papier est le format préféré des lecteurs).
- **Fil d'ariane : l'entrée "Livres" est désormais cliquable partout.** Ajout dans la table `$breadcrumbRoutes` de `Modules/FrontTheme/resources/views/partials/breadcrumb.blade.php`, corrige automatiquement tous les usages.
- **9/9 tests Pest verts** (`BooksLibraryTest.php`, 3 nouveaux tests ajoutés pour le compte de pages d'extrait et la présence du bouton du lecteur).

## [1.97.2] - 2026-07-09

### Fixed
- **« Pourquoi lire ce livre » déplacé du système d'onglets vers le hero.** Sur demande de l'utilisateur, ce bloc doit être visible immédiatement, sans interaction - déplacé dans la colonne droite du hero (entre le paragraphe auteur et le CTA), avec un nouveau titre `h2` au contraste ~18:1 (AAA). Les onglets passent de 5 à 4 (Extrait, Structure, Auteur, FAQ), avec Extrait comme onglet actif par défaut. L'ancien override CSS mobile qui inversait l'ordre corps/couverture a été retiré - l'ordre DOM naturel (couverture → titre/sous-titre/auteur → Pourquoi lire → CTA) suffit désormais. 6/6 tests Pest verts, aucune adaptation nécessaire.

## [1.97.1] - 2026-07-09

### Fixed
- **Deux problèmes signalés sur la fiche livre suite à la refonte 1.97.0.** (1) Le bandeau « Trilogie Nexus Neural » s'était retrouvé entre le hero et la section « Pourquoi lire », créant un grand espace vide - déplacé après le premier bloc CTA, « Pourquoi lire » suit désormais directement le hero sans rien entre les deux. (2) Remplacement du sommaire flottant par ancres par de **vrais onglets ARIA** (`role="tablist"/"tab"/"tabpanel"`, `aria-selected`, navigation clavier flèches gauche/droite) pour les sections Pourquoi lire (actif par défaut), Extrait, Structure, Auteur et FAQ - un seul panneau visible à la fois, mais les 5 panneaux restent présents dans le HTML brut (masquage CSS uniquement, pas de chargement AJAX) pour préserver le SEO/AEO. Correction additionnelle : couleur du texte des onglets inactifs ajustée de `#6B7280` (contraste 4,83:1, AA) à `#4B5563` (7,55:1, AAA). 6 tests Pest verts, vérifié desktop et mobile.

## [1.97.0] - 2026-07-09

### Added
- **Refonte de l'ordre de la page fiche livre (`/livres/{slug}`).** Suite à une veille `pp_search` (best practices pages de vente de livres, juillet 2026) : pour un livre conceptuel d'un auteur moins connu, le CTA doit rester tôt, mais la section « Pourquoi lire ce livre » doit arriver immédiatement après le hero - les onglets classiques qui cachent du contenu sont déconseillés pour une fiche livre (nuisent à la découvrabilité et à l'indexation AEO/GEO), un sommaire flottant par ancres est recommandé. Nouvel ordre : hero compact (couverture/titre/sous-titre/auteur, sans gros bloc CTA) → « Pourquoi lire ce livre » → 1er bloc CTA principal → sommaire flottant par ancres (réutilisation du composant DRY `x-fronttheme::table-of-contents`, déjà utilisé sur le blog et l'Académie) → reste de la page inchangé (preuve, extrait, structure, auteur, FAQ, CTA final) → nouveau bandeau CTA sticky sur mobile (contraste AAA 9,35:1, cible tactile 44px). Bug découvert et corrigé en cours de route : le widget « Gérer les témoins » chevauchait le bandeau sticky mobile, corrigé par une règle CSS scopée à cette page. 6/6 tests Pest verts, navigation inter-tomes toujours fonctionnelle.

### Fixed
- **Catalogue `/livres` - cartes Essais en pleine largeur avec espace vide.** Les 2 cartes de la section « Essais » s'empilaient à 100 % de largeur, laissant un espace disproportionné sur grand écran. Corrigé par une grille CSS (`display:grid`, `repeat(auto-fit, minmax(360px,1fr))`) donnant 2 cartes côte à côte sur desktop et un repli naturel à 1 colonne sur mobile - la section « Trilogie Nexus Neural » n'était pas touchée (déjà en grille).
- **Couvertures Nexus Neural avec filigrane Gemini visible.** Les 3 couvertures de la trilogie portaient un filigrane Gemini (aucune version propre trouvée dans les dossiers sources locaux après recherche exhaustive). Remplacées par les couvertures officielles récupérées depuis les fiches produit Amazon en direct (1600×2560, éditions françaises), confirmées sans filigrane, régénérées en 4 variantes pour les 3 tomes.

## [1.96.3] - 2026-07-09

### Fixed
- **Recherche `/annuaire` donnait l'impression de recharger la page.** Diagnostic Playwright : ce n'était pas une vraie navigation (aucune requête réseau de navigation, aucun `beforeunload`), mais un jank causé par le champ Alpine.js `x-model="search"` sans debounce, qui recalculait le filtrage/tri/rendu d'environ 391 outils à chaque frappe. Corrigé par l'ajout de `.debounce.200ms` sur le `x-model` (`Modules/Directory/resources/views/public/index.blade.php`) - la saisie reste instantanée, seul le filtrage est différé de 200 ms. Vérifié par test Playwright (focus/valeur intacts, aucune requête répétée) et 26/26 tests Pest du module Directory, aucune régression. Deux problèmes secondaires signalés dans les logs (bruit console CSP/AdSense, 404 favicons Google pour 2 outils) ont été investigués et confirmés sans lien avec ce bug - non corrigés dans cette passe, documentés pour plus tard.

## [1.96.2] - 2026-07-09

### Fixed
- **Fuite mineure de défense en profondeur (règle CSS `.nw-shared-dot`).** Vérification post-déploiement de 1.96.1 : la règle CSS `.nw-shared-dot` (composant `admin-shared-dot.blade.php`) était poussée via `@once @push('styles')` **avant** la vérification `isSuperAdmin()`, la rendant visible dans la balise `<style>` du HTML pour tout visiteur anonyme - aucune donnée sensible n'était exposée (ni `shared_at`, ni article), mais cela ne respectait pas l'exigence "zéro trace dans le HTML pour un non-admin". Corrigé en déplaçant le bloc `@once`/`@push` à l'intérieur du `@if(isSuperAdmin())`. Vérifié : compilation Blade OK, 10/10 tests Pest `NewsArticleShareTrackingTest` toujours verts, `curl` en production confirme l'absence totale de `nw-shared-dot` dans le HTML anonyme.

## [1.96.1] - 2026-07-09

### Fixed
- **Point rouge admin-only "déjà publié" manquant sur la liste publique des actualités.** Le point rouge livré en 1.96.0 sur la fiche individuelle et la liste admin manquait sur la grille de cartes publique `/actualites`, créant une incohérence pour les admins qui parcourent la liste. Ajouté dans le partial `article-card.blade.php` et refactorisé en composant Blade partagé `x-news::admin-shared-dot` pour éliminer la duplication du markup Alpine/aria - réutilisé maintenant sur la fiche individuelle et la liste publique (la liste admin garde son propre markup statique préexistant). Vérifié par 10/10 tests verts (2 nouveaux : présence pour superadmin après marquage, absence totale du HTML pour un visiteur anonyme même avec des données en base) et 99/99 sur toute la suite News (230 assertions), zéro régression.

## [1.96.0] - 2026-07-09

### Added
- **Glossaire — nouveau terme "PinPoint Test".** Test sanguin de dépistage/triage du cancer basé sur l'IA (machine learning), utilisé dans le NHS (Royaume-Uni). Analyse ~30-33 biomarqueurs sanguins routiniers combinés à des données démographiques (âge, sexe) dans un modèle entraîné sur plus de 370 000 patients (jeu rétrospectif), avec un suivi prospectif de 17 000 patients sur 5 ans. Logiciel de diagnostic in vitro (Software IVD) réglementé CE/UKCA, utilisé comme outil de triage pour 9 groupes de cancers (sein, gynécologique, hématologique, tête et cou, gastro-intestinal haut et bas, poumon, peau, urologique) - un outil d'aide à la décision, pas un substitut au diagnostic clinique. 3 sources vérifiées (BMJ Open 2022, Pinpoint Data Science 2026, AI News 2026). Image générée via `/nanobanana`.
- **Actualités — point rouge admin-only "déjà publié" sur LinkedIn/Facebook.** Quand un admin clique "Post LinkedIn" ou "Post Facebook" (menu de copie presse-papier existant, aucun appel API externe), un point rouge apparaît désormais avant le titre de l'actualité (page publique et liste admin), indiquant que le texte de partage a déjà été copié pour ce réseau. Nouvelles colonnes `linkedin_shared_at`/`facebook_shared_at` sur `news_articles`. Le tracking a été ajouté de façon générique et rétrocompatible dans le composant partagé `Modules/Core/admin-copy-menu.blade.php` (clé optionnelle `track_url` par item, zéro impact sur les 3 autres usages du composant - Acronyme/Terme/Outil/Article). Une route POST admin-only (`isSuperAdmin` strict, liste blanche de plateformes, idempotente) marque le timestamp ; le point se met à jour instantanément sans recharger la page. Vérifié : le point et les données de tracking sont totalement absents du HTML pour un visiteur non-admin, même si les champs sont remplis en base - et l'indicateur porte un `aria-label`/`title` explicite (pas de couleur seule). 72 tests Pest verts (8 nouveaux + 64 régression).

## [1.95.0] - 2026-07-09

### Added
- **Bibliothèque de livres `/livres` (nouveau module `Modules/Books`).** Catalogue + fiche riche par livre, calqué sur le module Dictionary (modèle `Book` avec `HasPublishedState`/`Searchable`, `BookSchemaService` générant un JSON-LD `@graph` `Book`+`Offer[]`+`BreadcrumbList`+`FAQPage`+`Person`). 5 livres publiés : "L'IA sans se faire poursuivre" et "L'IA pour les parents" (essais), trilogie "Nexus Neural" (3 tomes de science-fiction). Chaque fiche est optimisée SEO/AEO/GEO : hero avec 2 CTA (papier/Kindle vers Amazon), bénéfices, extrait, structure/table des matières, biographie de l'auteur, FAQ de 5 à 10 questions - toutes les données (prix, ASIN, ISBN, disponibilité) ont été vérifiées en direct sur Amazon via Playwright avant la rédaction, aucune donnée inventée. Navigation cliquable ajoutée entre les 3 tomes de la trilogie (badge "Tome N/3", tome courant non cliquable avec `aria-current`). Correctif inclus : les boutons d'achat étaient repoussés sous la ligne de flottaison mobile (390px) par l'ordre du flex du hero - corrigé par un `order` CSS scopé au module. La section est techniquement en ligne mais invisible au public : middleware `BooksUnderConstruction` (503 pour tout visiteur non-superadmin, piloté par `BOOKS_UNDER_CONSTRUCTION`) + `@section('page_noindex')` en défense en profondeur. Aucun lien de menu ajouté - la section reste invisible tant qu'elle n'est pas activée explicitement. 6 tests Pest verts (gate 503/200, contenu, JSON-LD, 404 propre sur slug inexistant).

### Fixed
- **Icône "réinitialiser le zoom" du visionneur BD minuscule/difforme.** Le bouton utilisait le caractère Unicode `⟳` (U+27F3), mal supporté par les polices système, ce qui le rendait visuellement cassé comparé aux autres icônes du même bandeau (`-`, `+`, `‹`, `›`, `⬇`, `✕`). Remplacé par une icône SVG inline 18×18px (`stroke="currentColor"`, style refresh/rotate cohérent avec Feather/Lucide). Vérifié visuellement (icône désormais cohérente en taille et en poids avec les autres) et 9 tests Pest du module Dictionary toujours verts.

## [1.94.4] - 2026-07-08

### Fixed
- **Visionneur BD ne naviguait pas entre les planches multi-pages.** Le composant `comic-viewer.blade.php` utilisait `$planche = $comic['planches'][0] ?? null` pour l'ensemble du rendu de la lightbox, limitant l'affichage à la première planche du manifest.json. En production, la BD deepfake (2 pages) ne permettait pas d'accéder à la seconde planche, malgré l'annonce du README. Correctif : le composant charge désormais le tableau complet des planches en JSON dans l'état Alpine.js, avec un index de page courant, des boutons précédent/suivant, un compteur "X / Y" (affiché seulement si plus d'une planche), une navigation clavier (PageUp/PageDown, virgule/point) et un lien de téléchargement pointant vers la planche affichée. Le zoom/pan/fit existant reste intact. 9 tests Pest verts (module Dictionary), dont un nouveau test vérifiant le rendu de la navigation multi-planches sur la BD deepfake réelle.

## [1.94.3] - 2026-07-08

### Added
- **Glossaire — BD pédagogique "Octopus face au deepfake"** (2 planches). Ajout d'une nouvelle bande dessinée pédagogique de deux pages sur le glossaire `/glossaire/deepfake`. La page 1 explique le deepfake (définition, réalisme, mécanisme d'IA, menaces et arnaques) ; la page 2 présente des mesures de protection (mot de passe familial, règle des 10 minutes, vérification de la source), sourcées via la veille pp_search de juillet 2026. Les personnages sont Octopus (héros), Hibou (mentor), Enfant (novice) et Pirate (menace). La BD a été produite le 2026-07-07 via le nouveau workflow `/bd` : Claude Code a généré les 8 images de case (skill nanobanana/Gemini), l'utilisateur a assemblé bulles, encadrés, branding et QR code dans son propre outil. Déployée dans `public/bd/deepfake/` (manifest.json décrivant les 2 planches, fichiers avif/webp/jpg + variante 1024 + miniature par page). La détection automatique par `ComicLibrary` ajoute un bouton "Lire la BD" sur la fiche glossaire. Un défaut de forme de bulle de pensée interdite sur une case a été corrigé par régénération ciblée de cette seule case.

## [1.94.2] - 2026-07-08

### Fixed
- **Service Worker interceptait /admin/* et tous les POST Livewire — lenteur sur /admin/users.** Le scope site-wide `/` du Service Worker captait aussi le backoffice et enveloppait CHAQUE requête POST (dont `/livewire/update`, utilisé par tout composant interactif) dans un `BackgroundSyncPlugin` (file de retry 24h, prévu pour de vrais formulaires hors-ligne, pas pour l'AJAX temps réel Livewire) — d'où l'attente perçue entre chaque sélection sur `/admin/users`. Des requêtes cross-origin (ex. AdSense) tombaient aussi dans le handler par défaut du SW, provoquant des erreurs réseau en console. Corrigé par 3 routes `NetworkOnly` passthrough prioritaires dans `sw-source.js` (avant les routes de cache) : `/admin/*`, `/livewire/*`, et tout cross-origin — zéro interception, zéro cache, zéro background sync sur ces requêtes.

## [1.94.1] - 2026-07-08

### Fixed
- **Conflit de scope Service Worker — rechargements infinis, surtout /actualites.** `/sw-authors.js` (mini-site auteur `/@slug`) était enregistré sans scope explicite, héritant du scope racine `/` identique au Service Worker vite-pwa principal (déjà widened via `Service-Worker-Allowed`). Résultat : ping-pong install/activate à chaque navigation entre pages publiques et mini-sites, visible côté DevTools comme "Service Worker was updated because 'Update on reload' was checked" s'incrémentant indéfiniment. Corrigé par un scope explicite `{scope: '/@'}` (`Modules/Authors/resources/views/mini-site/show.blade.php`) + un nettoyage rétroactif dans `resources/js/pwa.js` qui désenregistre toute ancienne registration `sw-authors.js` au scope racine, pour les visiteurs déjà affectés.

## [1.94.0] - 2026-07-07

### Added
- **Glossaire — BD pédagogique "Octopus et le rançongiciel".** Nouvelle bande dessinée sur `/glossaire/rancongiciel` (personnage Octopus, 6 cases : chiffrement des fichiers, WannaCry 2017, hameçonnage, rançon en cryptomonnaie, sauvegardes hors ligne, ne jamais payer). Déployée via `public/bd/rancongiciel/` (manifest.json + avif/webp/jpg/thumb), détectée automatiquement par `ComicLibrary` (bouton "Lire la BD" sur la fiche). Premier livrable du nouveau workflow `/bd` (2026-07-07) : Claude Code génère les images de case, l'utilisateur assemble bulles/encadrés/contours/branding.

## [1.93.0] - 2026-07-07

### Added
- **Glossaire — nouveau terme AlphaFold.** Systèmes d'IA de Google DeepMind qui prédisent la structure 3D des protéines (CASP13 2018, percée AlphaFold2 CASP14 2020, AlphaFold3 2024 pour les complexes biomoléculaires) - prix Nobel de chimie 2024 attribué à Demis Hassabis et John Jumper, partagé avec David Baker. Lien bidirectionnel avec le terme existant "transformer" (architecture Evoformer). Image générée via `/nanobanana`.

## [1.92.0] - 2026-07-07

### Added
- **Glossaire — 3 nouveaux termes.** JadePuffer (premier rançongiciel entièrement autonome piloté par un agent LLM, Sysdig Threat Research Team, juillet 2026), Cybermenaces (terme umbrella liant 15 termes de menaces déjà présents, taxonomie ENISA/ANSSI/CISA), Bitcoin (réseau monétaire décentralisé, Satoshi Nakamoto 2008-2009). Images générées via `/nanobanana`. Graphe de connaissances bidirectionnel construit (broader/narrower_slugs).

## [1.91.0] - 2026-07-06

### Fixed
- **Glossaire — 13 images manquantes.** Comparaison du sitemap public (446 URLs) contre le listing réel des fichiers en production a révélé 13 termes publiés sans aucune image (applescript, blindspot-pass, fable-5, fate-h-fate-x, interface-pam, javascript, lean-4, leanstral, licence-apache-2-0, minif2f, putnambench, thariq-shihipar, unknown-unknowns). Images générées via `/nanobanana` (Gemini), compressées 1200x669 (jpg+webp), `hero_image` mis à jour via migration réversible.

## [1.90.0] - 2026-07-06

### Added
- **PWA Académie — raccourci manifest.** Ajout de "Académie" aux raccourcis PWA (parité avec Actualités/Répertoire/Glossaire/Outils).

### Fixed
- **Scope du service worker PWA limité à `/build/` (site-wide).** Le service worker (vite-plugin-pwa) n'était en réalité enregistré et actif que sur les fichiers sous `/build/` - aucune page du site n'était contrôlée ni mise en cache hors-ligne, malgré la stratégie NetworkFirst configurée dans le code source du SW. Corrigé via `scope:'/'` (vite.config.js) + en-tête `Service-Worker-Allowed: /` (public/.htaccess) - les deux mécanismes sont nécessaires ensemble pour élargir le scope au-delà du répertoire du fichier SW.

## [1.89.0] - 2026-07-06

### Added
- **Minuteur visuel — mise en ligne publique.** L'outil `/outils/minuteur-visuel`, développé et affiné en gate superadmin-only depuis son introduction, est maintenant public. Levé après régression complète du module Tools (33 tests verts) et vérification de l'accès invité (plus de gate "En construction", présence confirmée dans `/outils`).

## [1.88.0] - 2026-07-06

### Added
- **Minuteur visuel — durée personnalisée en secondes.** Le champ "Durée personnalisée" accepte désormais un champ Secondes (0-59) en plus des Minutes, permettant des durées comme "1 min 30 s" ou "45 s" seules. Les durées épinglées et le partage d'URL (`?minutes=X&seconds=Y`) suivent le même format ; les anciens liens `?minutes=X` restent identiques (rétrocompatibilité vérifiée).

## [1.87.5] - 2026-07-06

### Fixed
- **Collision CSS site-wide `.ct-btn` (composant `x-core::button`).** Un composant Blade du module Core injectait un style global redéfinissant `.ct-btn` (bordure 1px, rayon 0.75rem), collisionnant silencieusement avec `.ct-btn-outline`/`.ct-btn-primary` de la charte graphique (bordure 2px, rayon 0.5rem) dès que les deux coexistaient sur une même page - signalé via le chip "durée épinglée" du minuteur visuel (ligne intérieure visible + contour disproportionné). Corrigé en renommant toutes les classes du composant Core en `core-btn`/`core-btn--xxx` (zéro collision possible). En complément, le chip du minuteur a été redesigné en bordure unique portée par le conteneur (pattern 2026 confirmé), immunisé contre toute collision future similaire.

## [1.87.4] - 2026-07-06

### Fixed
- **Minuteur visuel — texte "X minutes restantes" redondant sous le cadran.** Ce texte était en fait une annonce ARIA (`aria-live="polite"`) pensée pour les lecteurs d'écran, mais affichée visuellement alors qu'elle dupliquait le chiffre mm:ss déjà visible en continu au centre du cadran. Masqué visuellement (pattern sr-only standard), l'annonce reste fonctionnelle pour les lecteurs d'écran.

## [1.87.3] - 2026-07-06

### Fixed
- **Minuteur visuel — les fonctions personnalisées prenaient encore trop de place.** Le disclosure « Favoris, couleur par défaut, récentes » (v1.87.2) a été fusionné directement dans le panneau « Réglages », renommé « Réglages et personnalisations », organisé en 4 sous-sections groupées (🎨 Personnalisation des couleurs, ♿ Accessibilité, 🍅 Minuteur Pomodoro, 🚦 Feu de circulation), visibles selon le style actif. Décision confirmée par veille pp_search 2026 : un seul accordéon avec sous-sections légères plutôt que plusieurs tiroirs empilés ou des onglets imbriqués. Aucune fonctionnalité perdue.

## [1.87.2] - 2026-07-05

### Fixed
- **Minuteur visuel — bloc couleur beaucoup trop haut avant le cadran.** L'ajout incrémental des favoris (étoile), de la couleur par défaut du compte et de l'historique récent empilait chacun sa propre rangée toujours visible, portant le bloc à 4-5 rangées (~200px) avant même d'atteindre le cadran. Consolidé dans un disclosure natif replié par défaut (« ★ Favoris, couleur par défaut, récentes »), calqué sur le pattern « Réglages » déjà présent sur la page : 28px replié contre ~200px avant, aucune fonctionnalité perdue.

## [1.87.1] - 2026-07-05

### Fixed
- **Minuteur visuel — bouton × des chips épinglés (durées et couleurs favorites) redevenu un rond flottant.** Une règle CSS globale du site ciblant tout élément dont l'attribut `aria-label` contient « Retirer » (pensée pour un bouton vote/soutenir ailleurs sur le site, en `!important`) entrait accidentellement en collision avec nos boutons ×, qui utilisent le même mot pour l'accessibilité. Corrigé en renforçant la spécificité de nos sélecteurs CSS sans toucher à la règle globale.

## [1.87.0] - 2026-07-05

### Added
- **Minuteur visuel — couleur par défaut du compte (connectés)** : bouton « Définir comme couleur par défaut » près du sélecteur de couleur, sauvegarde la teinte active (curatée ou personnalisée) comme défaut multi-appareils. S'applique automatiquement sur tout nouvel appareil ou navigateur connecté n'ayant encore fait aucun choix de couleur local, sans jamais écraser une personnalisation déjà faite sur un appareil existant.

## [1.86.1] - 2026-07-05

### Fixed
- **Minuteur visuel — seuils du feu de circulation : confirmation visible manquante.** Les 3 boutons de profils fonctionnaient réellement (préférence bien appliquée et persistée), mais le feu de circulation reste vert tant que le décompte n'a pas commencé, donc cliquer un profil ne changeait visiblement rien avant le démarrage du minuteur. Ajout d'une confirmation textuelle immédiate à côté des boutons, indépendante de l'état du feu.

## [1.86.0] - 2026-07-05

### Added
- **Minuteur visuel — couleurs favorites épinglables (connectés)** : jusqu'à 2 couleurs favorites via une étoile ☆/★, bascule explicite (même comportement que les durées épinglées), distinctes de l'historique roulant automatique des couleurs personnalisées récentes.
- **Minuteur visuel — seuils du feu de circulation configurables (connectés)** : 3 profils préréglés en un clic (Standard 50 %/20 %, Alerte précoce 70 %/40 %, Sprint final 30 %/10 %) + repli « Personnalisé » (2 champs en pourcentage). Option retenue après veille : hybride préréglés + champs numériques, plus simple et plus fiable qu'un double curseur de plage.

### Changed
- **Minuteur visuel — retrait de la pulsation du style Chiffres** : l'effet de zoom (scale) déclenché à chaque seconde de décompte, jugé fatiguant par un utilisateur, a été retiré (anti-pattern UX confirmé par veille : le changement du chiffre suffit déjà comme signal, sans animation supplémentaire).

## [1.85.0] - 2026-07-05

### Changed
- **Minuteur visuel — palette de couleurs élargie à 6 teintes** : retrait de « Orange » (une rouille perçue comme un second rouge redondant avec le rouge classique TimeTimer), ajout de « Rose poudré » (#E8A9AE) et « Sable pâle » (#DCC3A0), deux teintes pâles tendance 2026 confirmées par veille. Le contraste du texte affiché reste calculé automatiquement (WCAG AAA) sur les 3 styles supportant la palette (disque, anneau, chiffres).
- **Minuteur visuel — bouton de retrait d'une durée personnalisée épinglée redessiné** : l'ancien petit rond flottant (18x18px, hors du cadre du bouton, sous le seuil de cible tactile WCAG) est remplacé par un segment intégré à même la pastille (28x28px), pattern chip « dismissible tag » (Material 3/shadcn) plus lisible et tendance 2026.

## [1.76.9] - 2026-07-04

### Changed
- **Renommage « Glossaire IA » → « Glossaire Techno »** (décision produit) : changement de libellé site-wide (menu, fil d'Ariane, pied de page, pages piliers SEO, module Dictionary, `llms.txt`, infolettre, admin). Aucun changement de schéma DB ni d'URL (`/glossaire` inchangé).

## [1.71.0] - 2026-07-01

### Added
- **Académie — Tuteur IA : fenêtre d'accès + quota + rappel** (recommandation de veille juillet 2026). Le formateur peut limiter (optionnel) la durée pendant laquelle un apprenant peut utiliser le tuteur IA d'un cours (aucune fenêtre, X jours après l'inscription, X jours après le lancement du cours, ou date fixe) et/ou un quota mensuel de questions, réglables à tout moment dans l'éditeur de cours. Le contenu du cours reste **toujours** accessible, même après la fin de l'accès au tuteur. Un rappel calme est envoyé par courriel avant l'échéance (une semaine avant, puis la veille). Modifier ces réglages n'affecte jamais un apprenant déjà inscrit : seules les nouvelles inscriptions suivent la nouvelle configuration. Activable via `ACADEMY_AI_TUTOR_ACCESS_CONTROL_ENABLED` (désactivé par défaut — le tuteur IA se comporte comme avant).

## [1.70.0] - 2026-07-01

### Added
- **Académie — traduction IA d'un champ de cours (formateur, brouillon)** : panneau « 🌐 Traduction IA » dans l'éditeur de cours — le formateur colle un texte, l'IA propose une traduction, il relit et modifie l'aperçu, puis VALIDE. Aucune écriture automatique dans le cours (les cours ne stockent pas encore de contenu multilingue) : le résultat reste un brouillon à copier soi-même. Activable via `ACADEMY_AI_TRANSLATION_ENABLED` (désactivé par défaut).
- **Académie — narration audio d'une leçon (accessibilité)** : bouton « 🔊 Écouter cette leçon » sur la page de leçon, basé exclusivement sur la synthèse vocale native du navigateur (aucun service tiers, aucun coût). Contrôles lecture/pause/reprise/arrêt, voix française privilégiée si disponible. Activable via `ACADEMY_TTS_ENABLED` (désactivé par défaut).

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
