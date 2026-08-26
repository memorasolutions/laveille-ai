# Popups natives du navigateur - inventaire complet (26 août 2026)

La règle 7 du projet interdit `alert()`, `confirm()` et `prompt()` natifs, et prévoit un grep
**avant chaque commit**. Ce garde-fou n'a jamais mordu : le dépôt en compte une vingtaine. Ce
document les liste toutes, classées par **atteignabilité réelle** et non par simple présence dans
le code, parce que la moitié vit dans des modules désactivés ou du code mort.

Aucune correction n'est encore appliquée : la suite de tests complète tournait au moment du
relevé, et modifier du code pendant une passe rend son résultat inexploitable.

---

## Méthode et faux positifs

35 correspondances brutes au grep, dont **15 faux positifs** écartés après lecture : le mot
« prompt » employé comme nom commun dans un titre ou un commentaire (« Negative prompt (Stable
Diffusion) », les commentaires du constructeur de prompts), et les commentaires qui énoncent la
règle elle-même (`contact-messages/show.blade.php`, `competency-manager.blade.php`).

**Il reste 20 appels réels.**

---

## Classement par atteignabilité

### Pages d'administration, modules ACTIFS - 10 sites

Atteignables par tout compte admin. C'est le gros du lot.

| Fichier | Ligne | Appel |
|---|---|---|
| `Modules/Core/resources/views/dupe-models/index.blade.php` | 43 | `confirm()` |
| `Modules/Core/resources/views/test-models/index.blade.php` | 43 | `confirm()` |
| `Modules/Backoffice/resources/views/themes/backend/failed-jobs/index.blade.php` | 102 | `confirm()` |
| `Modules/Directory/resources/views/admin/edit.blade.php` | 329 | `confirm()` |
| `Modules/Directory/resources/views/admin/moderation.blade.php` | 97, 132, 166, 200 | `confirm()` ×4 |
| `Modules/Blog/resources/views/admin/revisions/show.blade.php` | 40 | `confirm()` |
| `Modules/Blog/resources/views/admin/revisions/diff.blade.php` | 117 | `confirm()` |

### Page publique, sur lien partagé - 2 sites

`Modules/Tools/resources/views/public/tools/crossword/jeu.blade.php`, lignes **682** (`alert()`)
et **727** (`confirm()`).

**Nuance vérifiée plutôt que supposée** : la route est publique et sans authentification, mais la
grille vit à `/jeumc/{identifiant}`. La page d'accueil `/jeumc` a été récupérée en production et ne
contient **aucun** des trois appels ; aucune grille n'est listée au sitemap ni liée depuis
l'accueil. Un visiteur n'y arrive donc qu'avec un lien qu'on lui a donné. Ce n'est pas « tout le
monde le voit », ce n'est pas non plus du code mort.

### Composant public rendu, module ACTIF - 1 site

`Modules/Authors/resources/views/components/share-buttons.blade.php`, ligne 37 - `prompt()` pour
saisir l'instance Mastodon. Rendu par `Modules/Authors/resources/views/mini-site/post.blade.php`.

**À noter** : le commentaire de `config/version.php` affirme que le module Authors est
« DÉSACTIVÉ dans modules_statuses.json - pas de risque prod ». **C'est périmé** :
`modules_statuses.json` le donne ACTIF au 26 août 2026. Ne pas se fier à ce commentaire.

### Code mort - 1 site

`Modules/Authors/resources/views/components/distribution-buttons.blade.php`, ligne 22 - `prompt()`.
Aucun appelant dans tout le dépôt. À supprimer plutôt qu'à corriger.

### Modules DÉSACTIVÉS, donc dormants - 6 sites

`Booking` (gift-cards 62, coupons 58, packages 48, services 49) et `FormBuilder`
(submissions/show 24, submissions/index 87).

Nwidart n'enregistre ni les routes ni les vues d'un module désactivé : ces pages ne sont pas
servies. Attention à ne pas confondre avec l'autoload des CLASSES, qui lui charge bien tous les
modules par le merge-plugin de Composer, `modules_statuses.json` n'y changeant rien - la
distinction avait déjà induit en erreur lors de l'audit du 25 août.

---

## Ce qu'il faut réutiliser, et ne surtout pas réinventer

Deux mécanismes maison existent déjà et sont documentés dans le code :

1. **Dialogue modal du thème** - `Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php:1708`
   le décrit explicitement comme le remplaçant de `confirm()` natif.
2. **Confirmation inline à deux temps** - `Modules/Journal/resources/views/livewire/journal-builder.blade.php:103`
   et `Modules/Academy/resources/views/livewire/competency-manager.blade.php`, tous deux annotés
   « jamais de popup native ».

Le composant `action-menu.blade.php` porte déjà une clé `confirm` qui n'est **pas** un appel natif
(vérifié) : c'est le bon canal pour les listes d'administration qui l'emploient déjà.

---

## Ordre de traitement suggéré

1. Les **2 sites publics** du jeu de mots-croisés, seuls à pouvoir être vus par un visiteur.
2. Le **`prompt()` Mastodon** de `share-buttons`, puis suppression de `distribution-buttons`
   (code mort).
3. Les **10 sites d'administration**, en réutilisant `action-menu` là où la liste l'emploie déjà.
4. Les **6 sites dormants** de Booking et FormBuilder, en dernier - ils ne coûtent rien
   aujourd'hui, mais réactiver un module ramènerait la violation avec lui.

## Pour que le garde-fou morde enfin

La règle prévoit un grep manuel avant commit, et il n'a pas été fait. Un hook `PreToolUse` ou un
test d'architecture (`tests/Architecture/ArchTest.php` existe déjà) rendrait le contrôle
déterministe plutôt que dépendant de la mémoire de celui qui commite.
