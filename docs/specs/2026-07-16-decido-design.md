# Décido — outil de sondage collectif (type Framadate/Doodle, en mieux)

Date : 2026-07-16 (America/Toronto). Design approuvé par l'utilisateur via `/go` après validation
croisée pp_search + Codex + second avis (Hermes/deepseek-reasoner).

## Nom

**Décido** — slogan : « Décido — trouvons le bon moment ou le bon choix ».

## Contexte et problème

Framadate reste, malgré son rafraîchissement d'avril 2026, limité à un simple vote par options
fixes : pas de durée paramétrable pour calculer automatiquement les bons créneaux, UX de
modification de vote confuse, collisions de noms, rétention limitée (180 jours). Décido corrige ces
points précis avec du code neuf (aucune réutilisation du code Framadate).

## Objectifs V1 (dans le scope)

1. **Sondage de dates** : durée de rencontre déterminée D'ABORD → plage horaire admissible (ex.
   9h-17h) + pas de temps (15/30/60 min) → dates candidates → créneaux calculés automatiquement
   (durée + pas) → vote oui/peut-être/non par créneau → heat map du meilleur créneau (calcul
   local, zéro API payante).
2. **Sondage classique** : options libres, deux modes seulement en V1 : choix unique OU
   approbation (multi-sélection). Pas de ranked-choice/Condorcet en V1 (complexité trop élevée
   pour le gain immédiat, confirmé par validation croisée).
3. Vote via lien public, pseudonyme, aucun compte requis. Jeton privé par votant (UUID) permettant
   de modifier son propre vote sans confusion ni collision — corrige le point de friction n°1 de
   Framadate.
4. Création réservée aux utilisateurs connectés (`middleware(['auth'])`).
5. Lien admin distinct du lien public de vote (`admin_token`, long, **stocké haché**, révocable).
   Le compte créateur connecté reste la voie normale d'administration ; le lien admin permet la
   délégation sans partager le compte.
6. Export CSV des votes bruts (toujours disponible). Export ICS limité au créneau final choisi,
   disponible seulement après clôture du sondage (ne pas exporter des propositions non tranchées
   comme événements calendrier — trompeur pour qui les importe).
7. Fuseau horaire de la rencontre verrouillé par le créateur (IANA, ex. `America/Toronto`, jamais
   un simple décalage UTC), affiché en clair aux votants avec conversion si leur fuseau diffère.
8. Cycle de vie explicite : `draft` → `open` → `closed` (avec choix final pour les sondages de
   dates) → expiration automatique (6 mois post-clôture, aligné sur la politique Framadate 2026).
9. Gate « en construction » : visible seulement au superadmin pendant le développement, 503 +
   noindex pour tout le monde d'autre (pattern copié d'`AcademyUnderConstruction`/
   `BooksUnderConstruction`).

## Non-objectifs V1 (reportés en V2, explicitement hors scope)

- Vote par classement (ranked-choice) et Condorcet.
- Disponibilité par plage horaire glissante (glisser-déposer sur une frise).
- Duplication de sondage en template réutilisable.
- Notifications par courriel (nécessiterait une intégration Brevo transactionnelle à évaluer
  séparément).

## Architecture

Nouveau module nwidart dédié `Modules/Decido` (pas sous `Modules/Tools` : trop d'état persistant
multi-utilisateur pour ce pattern — même besoin qu'Academy/Books/Journal). Routes dédiées sous
`/decido`.

## Modèle de données

- **`decido_polls`** : `id`, `public_id` (aléatoire non-devinable, pattern `SavedCrosswordPreset`
  déjà en place dans le projet — `Str::random(12)` + boucle d'unicité), `custom_slug` (optionnel),
  `admin_token_hash` (haché, distinct du `public_id`), `creator_id` (FK users), `title`,
  `description`, `type` (`date` | `classic`), `vote_mode` (**chaîne validée par un enum PHP, PAS un
  ENUM SQL** — un ENUM SQL casserait justement l'extensibilité recherchée puisqu'ajouter un mode
  exigerait quand même une migration ; valeurs V1 : `yes_no_maybe` pour `date`, `single_choice` ou
  `approval` pour `classic`), `timezone` (IANA), `status` (`draft` | `open` | `closed`),
  `duration_minutes` (nullable, type `date` seulement), `range_start_time`/`range_end_time`
  (nullable, type `date` seulement), `step_minutes` (nullable, type `date` seulement),
  `final_option_id` (nullable, FK vers l'option gagnante une fois fermé), `expires_at`,
  timestamps.
- **`decido_poll_options`** : `id`, `poll_id` (FK), `label` (texte libre pour `classic` ; date+heure
  calculée pour `date`), `sort_order`, timestamps. Pour le type `date`, les créneaux sont calculés
  à la volée depuis `duration_minutes`+`range_start_time`/`range_end_time`+`step_minutes` par date
  candidate, puis persistés en options seulement une fois le sondage publié (pas d'explosion de
  lignes en brouillon).
- **`decido_poll_votes`** : `id`, `poll_id` (FK), `option_id` (FK), `voter_token` (UUID privé,
  transmis par URL/cookie, permet la modification du propre vote), `voter_pseudonym` (unicité
  imposée par sondage), `value` (`yes`/`maybe`/`no` pour `yes_no_maybe` ; booléen implicite pour
  `single_choice`/`approval`), timestamps.

## Sécurité et permissions

- Création : `middleware(['auth'])`, compte connecté du site.
- Vote : lien public, aucune authentification, jeton privé (`voter_token`) pour modifier son
  propre vote uniquement.
- Administration : créateur connecté (voie normale) OU détenteur du lien admin (`admin_token`
  haché, révocable/régénérable).
- Gate under_construction : middleware `DecidoUnderConstruction` copié ligne à ligne du pattern
  `AcademyUnderConstruction`/`BooksUnderConstruction`, `config('decido.under_construction', true)`,
  seul `$user->isSuperAdmin()` bypass.
- Accessibilité : navigation 100 % clavier, `lang="fr"`, contrastes AAA (tokens `--c-primary`/
  `--c-accent` de la charte existante), mobile-first strict.

## Critères d'acceptation

1. Un créateur connecté peut créer un sondage de dates en précisant la durée avant les dates, et
   le système propose les bons créneaux automatiquement selon la plage horaire et le pas choisis.
2. Un créateur connecté peut créer un sondage classique en choix unique ou approbation.
3. Un votant sans compte peut voter via le lien public et modifier son vote ensuite sans créer de
   doublon ni de confusion (jeton privé).
4. Le lien admin est distinct du lien de vote et permet de fermer le sondage / voir les résultats
   détaillés / choisir le créneau final.
5. Un visiteur non-superadmin reçoit un 503 non indexé tant que l'outil est en construction ; le
   superadmin voit l'outil réel.
6. Export CSV des votes fonctionnel en tout temps ; export ICS disponible seulement après clôture
   avec le créneau final choisi.
7. Aucune dépendance à une API tierce payante.
8. Tests Pest couvrant : création (les deux types), vote + modification de vote, gate under
   construction, exports CSV/ICS, permissions (créateur vs lien admin vs anonyme).

## Points DRY (réutiliser, ne pas dupliquer)

- Pattern de lien de partage `SavedCrosswordPreset` (public_id + custom_slug + résolution par les
  deux) pour `public_id`/`custom_slug` de `decido_polls`.
- Gate under_construction copié du pattern Academy/Books.
- Tokens de charte existants (`--c-primary`, `--c-accent`, `--f-heading`, `--f-body`), composants
  `<x-core::button>`.
- Export ICS : vérifier si un package Composer déjà présent dans le projet couvre la génération
  iCalendar avant d'en ajouter un nouveau (zéro coût, mais éviter une dépendance redondante).
