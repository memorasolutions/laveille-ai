# Module Ads - placement AdSense intelligent + alternance pub directe (design + contrat de build)

> Décision fondateur 2026-10-01 : installer AdSense « de façon intelligente », placement informé par
> les oracles, sans gâcher la navigation des visiteurs ni des membres, avec alternance pub directe
> (vendue par MEMORA) et pub Google. Panel : DeepSeek, ChatGPT, claude.ai (navigateur); Gemini a
> refusé/était déconnecté; Perplexity hors ligne. Verbatims :
> `storage/app/travaux-session-2026-10-01/oracles-adsense/` et `.../oracles-pub-politique/`.

## Ce qui existe DÉJÀ (ne pas refaire)
- Module `Modules/Ads` activé. Entité unique `AdPlacement` (table `ads_placements`) : `key` (unique),
  `name`, `description`, `ad_code` (HTML/Blade), `is_active`, `is_external`, `sort_order`.
- `AdsRenderer::render($key)` : 1 ligne active par clé, cache par `key:jour` (America/Toronto, 600 s),
  compile `<x-...>` via Blade, enveloppe les pubs INTERNES d'un label « Publicité » (les externes non).
- 12 emplacements nommés déjà câblés dans les gabarits (voir table plus bas).
- Chargeur AdSense + consentement Loi 25 dans `master.blade.php` (ne charge `adsbygoogle.js` qu'après
  consentement analytique). CSP `frame-src` Google OK. `public/ads.txt` OK (pub-2358625447182467).
- **Déjà corrigé cette session** : `clearCache()` visait la mauvaise clé (suffixe jour oublié);
  `is_external` n'était pas validé au controller (impossible de créer un emplacement externe).
- **Déjà corrigé cette session** : `master.blade.php` ne charge plus AdSense pour les membres connectés
  (`auth()->guest()` ajouté) - membres = zéro AdSense.

## Décisions de politique (tranchées, club des sages)
1. **Aucun mur anti-bloqueur.** Unanime (perçu 2/10). On informe sans bloquer.
2. **Message de récupération NATIF d'AdSense** (refermable, maintenu par Google, zéro code maison),
   anonymes seulement, orienté « crée un compte gratuit = sans pub ». Activé au tableau de bord AdSense.
3. **Membres connectés = zéro AdSense** (argument d'inscription + meilleur INP). Les pubs directes
   « maison » (encart livre, promo LucidNest) peuvent rester, elles sont légères.
4. **Outils interactifs = zéro AdSense dans la zone de travail** (`@section('no_ads')`), au plus une
   pub sous le résultat/texte SEO (phase 2).
5. **Densité** : 3-4 bons emplacements valent mieux que 7 médiocres. Jamais entre un titre et sa
   première réponse, ni près d'un bouton/menu/pagination. Anti-CLS : réserver `min-height`.
6. **Header leaderboard et footer** : rendement faible + risque LCP → NON activés au lancement.

## CAPACITÉ à construire (contrat de build - indépendant de l'approbation AdSense)

### 1. Migration `ads_placements` (réversible)
Ajouter : `ad_slot` string(32) nullable; `ad_format` string(20) nullable default `'auto'`;
`min_height` unsignedSmallInteger nullable; `lazy` boolean default true. `down()` les retire.

### 2. Modèle `AdPlacement`
- `$fillable` += `ad_slot`, `ad_format`, `min_height`, `lazy`. Casts : `lazy` bool, `min_height` int.
- Helper `isAdsense(): bool` = `! empty($this->ad_slot)`.
- Helper `hasDirect(): bool` = `! empty($this->ad_code)`.

### 3. `AdsRenderer::render($key)` - logique cible
- Résoudre la ligne active (cache léger `ad_placement_row:{key}:{jour}`, 600 s).
- `$isMember = auth()->check();` `$hasAdsense = isAdsense() && config('services.adsense.client_id');`
  `$hasDirect = hasDirect();`
- **Choix** :
  - Les deux présents → **alternance quotidienne déterministe** : `now('America/Toronto')->dayOfYear % 2`
    (pair = AdSense, impair = direct). MAIS un membre reçoit TOUJOURS la pub directe (jamais AdSense).
  - AdSense seul → rendre AdSense si anonyme; **`null` si membre** (pas de pub du tout).
  - Direct seul → rendre la pub directe pour tout le monde (anonyme et membre).
- **Rendu direct** : chemin actuel (Blade::render si `<x-`, sinon HTML brut; label « Publicité » si
  `! is_external`). Garder le cache de la version compilée (`ad_placement:{key}:{jour}`), car la
  compilation Blade de l'encart livre est le coût réel. Le gate membre/alternance se décide HORS cache
  (sinon on servirait une version mise en cache à la mauvaise audience).
- **Rendu AdSense** (`renderAdsense(AdPlacement $ad)`) : produire
  ```html
  <div class="ad-wrapper ad-external lv-adsense-wrap" style="min-height:{min_height|280}px">
    <ins class="adsbygoogle lv-adsense" style="display:block;min-height:{min_height|280}px"
         data-ad-client="{config services.adsense.client_id}" data-ad-slot="{ad_slot}"
         data-ad-format="{ad_format|auto}" data-full-width-responsive="true"
         {lazy ? 'data-lv-lazy=1' : ''}></ins>
  </div>
  ```
  Pas de label « Publicité » (AdSense s'auto-étiquette). Jamais mis en cache spécifique au membre.
  Si `lazy` faux : ajouter le `<script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>`
  immédiatement après le `<ins>`. Si `lazy` vrai : laisser le JS d'observation pousser (section 4).
- **`clearCache()`** : vider AUSSI la nouvelle clé `ad_placement_row:{key}:{jour}` (en plus de
  `ad_placement:{key}:{jour}` déjà corrigée).

### 4. Chargement différé (anti-CLS + Core Web Vitals)
Partiel `Modules/Ads/resources/views/partials/lazy-loader.blade.php`, inclus UNE fois (par ex. dans
le pied du layout via une directive déjà existante, sinon l'inclure depuis le master - demander avant
de toucher au master au-delà de l'existant). Script : IntersectionObserver (rootMargin ~400px) qui
pousse `(adsbygoogle=window.adsbygoogle||[]).push({})` pour chaque `.lv-adsense[data-lv-lazy]` à
l'approche du viewport, une seule fois par élément. Tolérant si `adsbygoogle.js` pas encore chargé
(le tableau se met en file). Ne rien faire s'il n'y a aucun `.lv-adsense` sur la page. Idempotent.

### 5. Admin (`create.blade.php`, `edit.blade.php`)
Ajouter : champ texte `ad_slot` (« Identifiant d'emplacement AdSense (data-ad-slot) - laisser vide
pour une pub directe maison »); select `ad_format` (auto / horizontal / rectangle / vertical / fluid);
champ nombre `min_height` (px, aide : « hauteur réservée, anti-saut de page »); interrupteur `lazy`
(défaut activé). Aide : « Remplir À LA FOIS le code de pub directe ET l'identifiant AdSense fait
alterner les deux chaque jour. » `ad_code` n'est plus obligatoire si `ad_slot` est fourni.

### 6. Controller `AdPlacementController` (validation)
`ad_code` → `nullable|string|required_without:ad_slot`; `ad_slot` → `nullable|string|max:32`;
`ad_format` → `nullable|in:auto,horizontal,rectangle,vertical,fluid`; `min_height` → `nullable|integer|min:0|max:2000`.
`is_active`, `is_external`, `lazy` → via `$request->boolean(...)` (déjà fait pour is_active/is_external).

### 7. Tests (Pest, calqués sur le style existant du dépôt)
Fichier `Modules/Ads/tests/Feature/AdsRendererTest.php` (+ au besoin controller) :
- emplacement AdSense : rend `<ins ... data-ad-slot="...">` pour un anonyme, **`null` pour un membre**;
- pub directe (`is_external=false`) : rend avec label « Publicité » pour anonyme ET membre;
- emplacement externe (`is_external=true`) créable via le controller (régression du bug corrigé);
- alternance (les deux remplis) : un membre obtient toujours la pub directe;
- `clearCache($key)` vide bien les deux clés du jour.
Zéro test en arrière-plan. Lancer `php artisan test --filter=Ads` (ou le binaire Pest du projet) et
COLLER la sortie réelle dans le rapport final.

## Carte des emplacements (table 12 clés câblées → usage recommandé)
| Clé | Où (gabarit) | Usage recommandé (oracles) | Format / dimension | Activer au lancement? |
|---|---|---|---|---|
| `header-leaderboard` | master (haut) | faible rendement + LCP | 728x90 | NON |
| `footer-banner` | master (bas) | faible | responsive | NON |
| `sidebar-rectangle` | sidebar | bon, collant desktop | 300x250 ou 300x600 | OUI |
| `article-top` | blog/show | après l'intro = souvent le + rentable | in-article responsive, min-h 280 | OUI |
| `article-inline` | blog/show (après 3e §) | milieu, coupure sémantique | in-article responsive | OUI (occupé par encart livre - alterner) |
| `article-bottom` | blog/show (fin) | fin avant « À lire aussi » | Multiplex/responsive | OUI |
| `between-posts` | blog/index (itér. 3) | in-feed liste | in-feed | OPTION |
| `tools-top` | Tools/index | outils = pas de pub en zone de travail | - | NON (déclarer no_ads) |
| `directory-top` | Directory/index | bannière haut | responsive | OPTION (faible) |
| `directory-bottom` | Directory/index | bas de liste | responsive | OUI |
| `directory-tool-top` | Directory/show | fiche d'outil, après description | responsive | OUI |
| `glossary-top` | Dictionary/index | après la définition, jamais avant | responsive | OUI |

**Trous à combler (phase 2, additions de gabarit)** : la fiche d'ACTUALITÉ (news show) n'a AUCUN
emplacement; la fiche de GLOSSAIRE (entry/show) non plus; l'annuaire manque un vrai in-feed après
6-8 résultats. À ajouter après confirmation de l'approbation AdSense.

## Dépendance DURE (hors build)
Le rendu `<ins data-ad-slot>` suppose : (a) le site **approuvé** par AdSense pour la diffusion;
(b) des **unités d'annonce créées** (chaque `data-ad-slot` vient d'une unité du tableau de bord). Si
non approuvé : la capacité est construite et testée, mais le SEED réel attend l'approbation. État
réel vérifié par l'agent dédié (lecture seule) : `storage/app/travaux-session-2026-10-01/adsense-etat/`.

## Contraintes MEMORA (rappel pour le sous-agent)
Attribution MEMORA solutions uniquement, jamais Claude/IA. Module désactivable sans casse
(`class_exists`), exportable. DRY : réutiliser le rendu existant, ne pas dupliquer. Typographie
québécoise (deux-points précédés d'une espace insécable, pas de tiret cadratin). `declare(strict_types=1)`.
Aucune suppression de données. Aucun travail en arrière-plan. Lire chaque fichier avant de l'écrire.
