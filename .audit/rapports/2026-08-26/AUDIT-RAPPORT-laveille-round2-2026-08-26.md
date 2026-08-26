# Audit de laveille.ai - round 2, et correctifs appliqués

**Date** : 2026-08-26 (America/Toronto) · **Portée** : les zones que le rapport du 25 août
déclarait non couvertes en section 8. Ce document ne rejoue pas l'audit précédent, il le complète.
**Livré en** : v1.220.0

---

## 1. Matrice de couverture

| Zone | Statut | Preuve |
|---|---|---|
| Modules/Academy (557 fichiers) | complété | Aucune faille sur les 4 axes. 20 fichiers ouverts. |
| 368 occurrences `{!! !!}` | complété | Triées par provenance de la donnée. **1 XSS public trouvé** (Journal). |
| Jobs de file + commandes planifiées | complété | 2 findings retenus, 1 recoté après vérification. |
| Newsletter / Media / Notifications | complété | Rien trouvé. 1 faux positif écarté par test (SVG). |
| Decido / Journal | complété | Decido : rien. Journal : policy correcte, **rendu fautif**. |
| Books / Menu / Widget | complété (partiel) | Widget propre. Menu : routes déclarées dans Backoffice, **non lues**. |
| accessibilité (gabarits supplémentaires) | complété | 3 défauts réels mesurés sur le fond effectif. |
| performance (gabarits supplémentaires) | complété | **Finding majeur** : facteur 10 à 16 à froid. |
| securite-infra (SSL) | complété | **Grade A+**, le point en suspens du 25 est clos. |

---

## 2. Le finding majeur : le site était douze fois trop lent à froid

Mesuré en production sur cinq pages : première visite d'une fiche d'outil **4,4 à 10,6 s**,
seconde visite **0,5 s**.

| Page | Froide | Chaude | Facteur |
|---|---|---|---|
| nomacapp-1 | 10,63 s | 0,66 s | ×16 |
| leni | 6,65 s | 0,65 s | ×10 |
| pullcard | 5,26 s | 0,49 s | ×11 |

**Démarche de diagnostic**, reproductible :

| Mesure | Résultat | Ce qu'elle élimine |
|---|---|---|
| Total vs SQL | 2 605 ms dont **43 ms de SQL sur 132 requêtes** | Ce n'est pas la base : 98 % hors SQL |
| Requêtes répétées | **7 `INSERT` dans `favicon_cache`** | Le rendu ÉCRIT, donc il résout en direct |
| Lecture du service | `Http::timeout(3)` × 3 fournisseurs | Chaîne prouvée : 9 s par domaine inconnu |

Le `INSERT` pendant le rendu d'une page publique est le signal qui a désigné le coupable : une page
qui lit ne devrait pas écrire.

**Cause** : `smart-favicon.blade.php` appelait `FaviconResolverService::resolve()` **depuis une
vue**, donc pendant le rendu.

**Correctif** : `resolveCached()` lit le cache et rien d'autre ; `ResolveFaviconJob` fait le réseau
en file dédiée. Résultat mesuré : **2 605 → 204 ms**, SQL **132 → 49**, **0 écriture au rendu**,
et un domaine inconnu répond en **7,9 ms** au lieu de 9 s.

**Enjeu** : l'annuaire compte 1 544 pages et Googlebot explore surtout des pages froides. Il
subissait ce délai systématiquement, sur un site dont le robot n'était plus repassé depuis le
4 août. C'est une piste sérieuse sur l'effondrement de juillet, **non une démonstration**.

---

## 3. Findings de sécurité, tous corrigés

**S1 — XSS stocké sur une page publique (Journal)** · haute · reproduit
`Modules/Journal/resources/views/show.blade.php:211` affichait `{!! $block->payload['html'] !!}`
en brut. Ce HTML est saisi par l'utilisateur, `JournalPolicy::view()` autorise la lecture à **tout
visiteur anonyme** dès publication, et la route est déclarée **hors du groupe `auth`**. La policy
était correcte : c'est le rendu qui ne l'était pas. Corrigé par `safeHtml()` sur le modèle,
purifiant à l'**affichage** pour couvrir aussi les blocs déjà enregistrés. 4 tests.

**S2 — Fuite des courriels par l'API de recherche** · haute · corroboré
Corrigé en filtrant l'**accès** et non l'index : désindexer `User` aurait cassé la recherche
légitime du back-office. `getSearchableModelsFor()`, fail-closed. 4 tests dont la non-régression.

**S3 — Publication d'articles sans autorisation** · haute · **reproduit**
`ArticleApiController::store()` était la seule action d'écriture sans `authorize()`. Éprouvé rouge
avant correctif, vert après.

**S4 — Trois sorties de modèle de langage en HTML brut** · haute · reproduit
Dont **une sur la page publique du blog, jamais signalée par les audits précédents**.

**S5 — Commande de démonstration exécutable en production** · moyenne · corroboré
`app:demo` insère de faux contenus **publiés** dans les vraies tables. Refuse désormais sans
`--force`. *Recoté de « élevée » à « moyenne » après vérification : sa suppression est
**strictement bornée** aux adresses `%@demo.test`, aucune donnée d'utilisateur réel n'était en jeu.
Le risque était la création, pas l'effacement.*

**S6 — Cron temporaire permanent** · basse · corroboré
Un correctif ponctuel annonçait « retiré après exec » mais tournait encore chaque minute.

---

## 4. Accessibilité : 3 défauts réels

Mesurés sur le **fond effectif** (en remontant l'arbre), et non sur la remontée naïve du DOM qui
avait produit les faux positifs du 25 août.

| Élément | Avant | Après | Norme visée |
|---|---|---|---|
| Badge « Avancé » | 3,76:1 | 6,8:1 | AA atteint |
| « Mis à jour le… » | 2,54:1 | **7,09:1** | AAA |
| « Ajouté ! / Copié ! » | 2,54:1 | **7,68:1** | AAA |

Corrigés en **réutilisant ce qui existait** : le token `--c-text-muted` de `charte.css` et la
combinaison rouge déjà employée dans `Dictionary/index`. Aucune couleur inventée.

**Vérification qui a évité une casse** : sur les 20 occurrences de `#9ca3af`, **seules 2 étaient
le défaut**. Les autres sont des bordures, des fonds de cases de jeu, des échantillons de couleur
et du Tailwind généré.

---

## 5. Ce qui a été écarté, et pourquoi

**SVG malveillant accepté au téléversement** — **faux positif tranché par test**. Deux agents se
contredisaient. En Laravel 12, `$mimes[] = 'svg'` est conditionné à `in_array('allow_svg',
$parameters)` ; la validation du projet est `image` sans paramètre. Test réel :

```php
Validator::make(['file' => $svg], ['file' => 'image'])->fails();  // true : REFUSE
```

**Trois tests passés au rouge après le correctif S3** — ce n'est pas une régression. Ces tests
vérifiaient qu'un utilisateur **simplement authentifié** pouvait publier, c'est-à-dire exactement
la faille. Leur échec prouve que le correctif mord. Mis à jour pour porter la nouvelle règle, après
recherche de **tous** les tests du même motif d'un coup.

---

## 6. Modules déclarés propres après lecture

À noter pour ne pas les réauditer à vide, et parce qu'un rapport qui ne relève que les défauts
donne une image fausse :

- **Academy** : accès recalculé serveur à chaque requête, réponses de quiz en session serveur
  jamais envoyées au navigateur, limite de tentatives revérifiée **dans la transaction**
  (protège contre plusieurs onglets), certificat à identifiant non énumérable.
- **Decido** : jeton admin haché en SHA-256 **jamais stocké en clair**, `hash_equals`,
  `lockForUpdate` anti-course, identifiant public non énumérable.
- **Newsletter** : jetons `Str::random(64)`, honeypot, `throttle:5,1`, webhook en `hash_equals`.
- **Notifications** : aperçu de courriel sur données factices uniquement.
- **Widget** : le seul `{!! !!}` sur donnée utilisateur passe par `Purifier::clean()`.
- `LessonItem::renderRichText()` applique déjà `html_input => strip`, ce qui couvre des dizaines
  d'appels du module Academy.

---

## 7. Ce qui reste ouvert

- ~~**`Modules/Menu`** : le contrôle des permissions n'est pas confirmé~~ - **VÉRIFIÉ ET CLOS le
  2026-08-26 : le module est propre.** Les six routes portent chacune leur `permission:`
  (`view_menus`, `create_menus`, `update_menus`, `delete_menus`), le groupe englobant impose
  `web`, `auth`, `two.factor` et `EnsureIsAdmin`, les quatre permissions sont bien créées par la
  boucle « Pattern A » du seeder (`menus` figure dans `$patternAEntities`), et la vue conditionne
  les boutons Modifier et Supprimer aux mêmes permissions - l'affordance et le contrôle d'accès
  sont tous deux présents. Une fausse piste écartée en chemin : le rôle éditeur n'a
  volontairement pas `delete_menus`, ce qui n'est pas une permission manquante mais un moindre
  privilège assumé.
- ~~**`withoutOverlapping()`** absent sur une quinzaine de tâches planifiées~~ - **MESURÉ ET
  LARGEMENT ÉCARTÉ le 2026-08-26.** Le compte réel est 25 sur 42, mais compter n'est pas coter :
  - `newsletter:digest --send` porte **déjà sa propre idempotence** (`Cache::lock` de 30 minutes,
    commentée « empêche double envoi si cron rerun ») et ses deux lignes de planification sont
    **commentées** : la tâche ne tourne même pas.
  - `notifications:send-digest` marque les notifications `read_at` juste après l'envoi, si bien
    qu'une seconde passe ne trouve plus rien. Une fenêtre de course subsiste entre l'envoi et la
    marque, mais la tâche est quotidienne : l'ajout reste souhaitable, la gravité est basse.
  - `backup:run` tourne une fois par jour : un chevauchement supposerait une sauvegarde de plus
    de 24 heures.
  - Les `queue:work` ne doivent **surtout pas** recevoir ce verrou : plusieurs consommateurs en
    parallèle sont le fonctionnement normal d'une file.
  - Le reste est du ménage idempotent (`telescope:prune`, `activitylog:clean`,
    `queue:prune-batches`, `horizon:snapshot`).

  Conclusion : la formulation initiale laissait croire à une quinzaine de correctifs à poser. Il
  n'y en a en pratique aucun d'urgent, et un changement massif aurait été du bruit.
- **`SendCampaignEmailJob`** n'a pas le garde-fou d'idempotence que possède `SendDigestJob`.
  Signal, non confirmé.
- **JSON-LD de Books** sans `JSON_HEX_TAG`. Non exploitable aujourd'hui (aucun formulaire admin),
  défensif.
- **`Modules/Shop`** : `Str::markdown($product->description)` non filtré. Volontairement **non
  corrigé** : contenu éditorial de confiance, et impossible de vérifier depuis le local si des
  descriptions de production contiennent du HTML voulu. Zéro casse prime.
- Les findings du rapport du 25 août non traités ici : C1, C2, H3, H4, H7, H9, H10, H12.
