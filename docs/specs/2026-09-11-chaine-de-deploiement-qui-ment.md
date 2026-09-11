# La chaîne de déploiement qui ment - audit et correctifs

**Date** : 2026-09-11
**Auteur** : MEMORA solutions <info@memora.ca> (https://memora.solutions)
**Portée** : `.github/workflows/deploy.yml` (transport rsync/SSH), `public/_lvgit.php` (secours),
témoins de version affichés sur laveille.ai.
**Contrainte respectée** : aucun `git push`, aucun commit, aucun déploiement effectué ici -
uniquement mesure et préparation de fichiers locaux (backupés avant écriture).

---

## Défaut A - un déploiement vert peut ne pas transporter le fichier corrigé

### 1. Liste exacte des motifs d'exclusion en place aujourd'hui

Lus directement dans `.github/workflows/deploy.yml`. **État AVANT mon intervention** (lignes
116-131 du fichier tel qu'il existait au début de cet audit, commit `b5f6d996` = HEAD) :

```
--exclude='.git/'
--exclude='.github/'
--exclude='/vendor/'
--exclude='node_modules/'
--exclude='storage/'
--exclude='.env'
--exclude='.env.example'
--exclude='public/screenshots/'
--exclude='bootstrap/cache/'
--exclude='tests/'
--filter='P public/_*.php'
--filter='P /_*.php'
--exclude='*.log'
--exclude='error_log'
```

Fait important établi avant même de commencer cet audit : **les deux derniers motifs sur
`_*.php` étaient déjà passés de `--exclude` à `--filter='P ...'` (protect, pas exclude) au
commit `d3b3c42f` (sujet de commit, paraphrasé : « le déploiement corrige les fichiers
`_*.php` qui étaient exclus du transfert, pas seulement de la suppression », v1.258.3), poussé
et déployé AVANT le début de ce mandat**
(vérifié : `git log origin/master -- .github/workflows/deploy.yml` inclut ce commit ; `HEAD`
local = `origin/master`). C'est l'incident même que ce mandat me demande de documenter -
`.github/workflows/deploy.yml:104-114` porte le récit complet écrit à ce commit : un correctif
de sécurité sur `public/_lvgit.php` a été commité, la CI est passée au vert, le déploiement a
« réussi », et le serveur a continué de servir l'ancien code vulnérable, sans aucun signal.

### 2. Pour chaque motif : qu'attrape-t-il RÉELLEMENT aujourd'hui ?

Mesuré par `git -c core.quotePath=false ls-files` (liste exhaustive du dépôt, exécuté en
local) recoupé avec `rsync -an --out-format='%n'` (dry-run réel du même jeu de flags contre
une cible vide, exécuté en local avec rsync 3.5.0 - même version que le runner
`ubuntu-latest`) :

| Motif | Fichiers suivis par git réellement attrapés | Verdict |
|---|---|---|
| `.git/`, `.github/` | tout le dossier VCS et CI/CD (des milliers d'entrées) | légitime, inerte hors sujet |
| `/vendor/` (ancré racine) | le `vendor/` composer à la racine (non suivi par git de toute façon - `.gitignore:` `/vendor`) | légitime, corrige un incident PASSÉ (v1.98.1, voir §3) |
| `node_modules/` (non ancré) | **0 fichier suivi** (`git ls-files \| grep -c "node_modules/"` → `0`) | inerte/redondant avec `.gitignore` - aucun risque, aucun bénéfice mesuré aujourd'hui |
| `storage/` (non ancré) | uniquement l'arbre `storage/` à la racine (framework/cache, framework/sessions, framework/views, logs, un artefact temp media-library) - aucun module n'a son propre `storage/` suivi | légitime |
| `.env` | rien (jamais suivi) | légitime, défense en profondeur |
| `.env.example` | 1 fichier, à la racine, un gabarit jamais lu par le code | inerte sans risque - ne prive l'app d'aucun comportement |
| `public/screenshots/` | le dossier de captures générées/uploadées en prod (mémoire projet : anti-écrasement screenshots) | légitime |
| `bootstrap/cache/` | les caches Laravel compilés (routes, configuration, événements), reconstruits par les étapes suivantes du même pipeline | légitime |
| `tests/` (non ancré) | **57 répertoires** : `tests/` racine + `Modules/*/tests/` (55 modules) + `stubs/nwidart-stubs/tests/` - aucun n'a de code applicatif exécuté en prod | légitime |
| `*.log`, `error_log` | 0 fichier suivi aujourd'hui | inerte/redondant avec `.gitignore` |
| `public/_*.php`, `/_*.php` (filter P, pas exclude) | `public/_lvgit.php` (le seul fichier du dépôt qui matche) | **c'est l'ancienne victime** - protégée de la suppression, plus jamais du transfert depuis le commit `d3b3c42f` |

Commande exacte utilisée (locale) :
```
git -c core.quotePath=false ls-files | sort > /tmp/lv-tracked.txt
rsync -an --out-format='%n' <les 14 flags ci-dessus> ./ /tmp/lv-dryrun-target/ | sort > /tmp/lv-would-transfer.txt
comm -23 /tmp/lv-tracked.txt /tmp/lv-would-transfer.txt
```
16 357 entrées évaluées par le dry-run rsync. Après avoir retiré les zones légitimes
(`.git/`, `.github/`, `vendor/`, `node_modules/`, `storage/`, `tests/`,
`public/screenshots/`, `bootstrap/cache/`, `.env`, `.env.example`, `*.log`, `error_log`) :
**0 fichier de code vivant piégé aujourd'hui.**

### 3. Chaque exclusion a-t-elle une raison encore valable ?

Oui pour toutes, avec deux nuances honnêtes :
- `node_modules/` et `*.log`/`error_log` sont **redondants avec `.gitignore`** (ces chemins ne
  peuvent de toute façon jamais être suivis par git, donc jamais présents dans un `actions/
  checkout` propre côté CI). Ce sont des motifs qui « n'excluent plus rien » au sens strict de
  la consigne, mais ils ne sont pas dangereux - je les garde : un filet de sécurité redondant
  ne coûte rien et protégerait quand même un futur commit accidentel local (rsync utilisé
  directement en dehors de la CI, par exemple).
- `.env.example` est exclu sans qu'aucun code n'en dépende en prod (grep exhaustif : aucune
  référence). Exclusion inoffensive, ni à retirer ni à justifier davantage.
- Toutes les autres exclusions protègent soit des artefacts VCS/CI (`.git/`, `.github/`), soit
  des dépendances reconstruites par une étape ultérieure du MÊME pipeline (`vendor/` via
  composer install, lignes 133-146 ; les caches via `optimize:clear`/`route:cache-atomic`,
  lignes 164-212), soit du contenu généré EN PROD que le dépôt écraserait sinon
  (`public/screenshots/`, `bootstrap/cache/`, `storage/`), soit des suites de tests qui n'ont
  aucune fonction en prod (`tests/`). Aucune à retirer.

### 4. Le piège s'est-il déjà refermé sur un fichier ? Comparaison prod vs dépôt.

**Oui, sur `public/_lvgit.php`, avant le correctif `d3b3c42f`.** Preuve directe recueillie
aujourd'hui sur le serveur (cPanel, `cpanel_file_list` puis `cpanel_file_read` - Shell API
cPanel indisponible, donc pas de `sha256sum` distant, mesure par lecture de contenu complet) :
le dossier `public/` du serveur contient un fichier
`_lvgit.php.avant-durcissement-serveur-20260911-0730` (1 000 octets) dont le contenu **est
déjà neutralisé** (`http_response_code(410); exit;`) par une intervention antérieure du même
jour, avec ce commentaire écrit dans le fichier lui-même (contenu original sans accents,
paraphrasé ici en français correct plutôt que cité mot pour mot) : ce fichier portait la
version pré-durcissement de `_lvgit.php` (jeton accepté dans la chaîne de requête, et
exécution d'une semence possible depuis la requête) ; la copie vulnérable restait donc
joignable à côté du fichier corrigé, ce qui annulait entièrement le correctif de sécurité du
2026-09-11.

C'est la preuve comportementale que le piège a RÉELLEMENT mordu : une version antérieure et
vulnérable de `_lvgit.php` (jeton accepté en `?t=`, option `&seed=` exécutable) est restée
active côté serveur assez longtemps pour qu'une sauvegarde web-servable en subsiste, pendant
que le dépôt portait déjà le correctif. Le fichier de sauvegarde a depuis été neutralisé (par
une action antérieure à cet audit, non par moi).

**État actuel, mesuré aujourd'hui, après le correctif `d3b3c42f`** : j'ai lu le contenu intégral
de `public/_lvgit.php` sur le serveur via `cpanel_file_read` et calculé son empreinte SHA-256
localement contre le fichier du dépôt.

```
local  : 2b917e52ac176b7dbc0e16a6b91f4e4e7f96919313b7e697865c3525c21b10f0  public/_lvgit.php
serveur: 2b917e52ac176b7dbc0e16a6b91f4e4e7f96919313b7e697865c3525c21b10f0  (contenu lu via cpanel_file_read, écrit dans un fichier local puis hashé)
```
**Empreintes identiques.** `diff -u` entre les deux confirme aussi une identité byte à byte
(152 lignes). Le piège est donc refermé sur le PASSÉ (la sauvegarde vulnérable, déjà
neutralisée par ailleurs) mais **actuellement ouvert et sain** : prod = dépôt pour ce fichier,
confirmé par mesure et non déduit.

Corroboration indépendante : `cpanel_file_list` sur `public/` liste `_lvgit.php` à 6,79 Ko -
cohérent avec les 152 lignes du fichier actuel (bien plus long que l'ancienne version
pré-durcissement de 1 000 octets trouvée dans la sauvegarde neutralisée).

### 5. Antécédents de la même classe de défaut (contexte, pas mesure du jour)

Ce n'est pas la première fois. `docs/HISTORIQUE-VERSIONS.md:1373` (v1.98.1, 2026-07-09) :
`--exclude='vendor/'` non ancré excluait aussi `public/vendor/` (StPageFlip vendorisé), 404 en
prod, corrigé en ancrant `/vendor/` - **c'est pourquoi le motif actuel porte un `/` en tête**.
`docs/HISTORIQUE-VERSIONS.md:1990` (v1.65.3, 2026-06-02) : `--exclude='public/build/'`
bloquait TOUS les assets Vite compilés, jamais déployés malgré les commits - retiré. Même
classe de défaut, trois occurrences en trois mois sur ce seul pipeline.

### Correctif appliqué (Défaut A)

Le mandat le dit explicitement : retirer des lignes n'est pas le correctif, puisque plus aucune
exclusion active ne piège de code vivant aujourd'hui. Le vrai trou est l'ABSENCE de tout
mécanisme qui rendrait ce silence impossible à l'avenir - le même type de motif a déjà piégé du
code vivant trois fois (vendor/, public/build/, `_*.php`) sans qu'aucun garde-fou ne le
détecte avant un audit humain.

**Ajouté dans `.github/workflows/deploy.yml`, étape « Deploy via rsync »** (avant le transfert
réel, dans le MÊME bloc `run:` - un seul tableau `EXCLUDES`/`PROTECT`, jamais une deuxième
liste dupliquée) : un garde-fou qui calcule, via un vrai `rsync --dry-run --out-format='%n'`
avec les mêmes flags que le transfert réel, la liste des fichiers suivis par git qu'une
exclusion laisserait de côté, retire les zones reconnues comme légitimes (`.git/`, `.github/`,
`vendor/`, `node_modules/`, `storage/`, `tests/`, `public/screenshots/`, `bootstrap/cache/`,
`.env`, `.env.example`, `*.log`, `error_log`), et **fait échouer le job avec `::error::`** si un
seul fichier reste - exactement le cas `public/_lvgit.php` avant `d3b3c42f`.

Validé deux fois en local avant écriture dans le fichier (les messages `echo` du script
lui-même restent volontairement sans accents dans le fichier YAML - convention déjà en place
dans ce pipeline, ex. l'étape « Deploy notification » existante ; paraphrasés ici en français
correct) :
1. **Contre l'état réel du dépôt** (le script exact du fichier, extrait et exécuté) → message
   de succès, aucun fichier de code vivant piégé par une exclusion, code de sortie 0.
2. **Contre une régression simulée** (remise de `public/_*.php` et `/_*.php` en `--exclude` au
   lieu de `--filter='P ...'`, reproduisant exactement l'incident d'origine) → le script
   affiche une erreur nommant `public/_lvgit.php` comme fichier suivi par git piégé par une
   exclusion rsync hors zone reconnue, code de sortie 1.

Le garde-fou est donc prouvé sensible ET spécifique sur le cas réel qui a motivé ce mandat.

---

## Défaut B - le témoin de version affiche 1.63.22 alors que la prod sert 1.258.1 (mesuré aujourd'hui : 1.259.0)

### 1. Où vit ce témoin, et qui l'écrit

Deux mécanismes de version totalement distincts coexistent sur ce projet - c'est la racine du
problème :

**(a) Le vrai footer (public + admin), correct et vivant.** Source unique :
`config/version.php:14-16` (`$lvMajor=1; $lvMinor=259; $lvPatch=0;`, `semver` dérivé
automatiquement ligne 28, jamais figé en dur - commentaire explicite ligne 27 : « incident déjà
survenu »). Lu par `app/Helpers/version.php:24` (`lv_semver()` → `config('version.semver',
'1.0.0')`), assemblé par `lv_version()` (lignes 61-76, avec SHA git optionnel via
`lv_git_sha()` qui lit `.git/HEAD` sur le serveur). Affiché dans trois templates :
`Modules/FrontTheme/resources/views/partials/footer.blade.php:160`,
`Modules/Backoffice/resources/views/themes/backend/components/footer.blade.php:6`,
`Modules/Backoffice/resources/views/themes/backend/partials/footer.blade.php:6` - les trois via
`lv_version(false)` (sans SHA). **Ce témoin lit déjà la source unique, jamais un numéro
recopié à la main.**

**(b) Le fichier statique orphelin `public/_lvversion.txt`, faux.** Écrit UNIQUEMENT par
`public/_lvgit.php:140-150`, et seulement quand ce point d'entrée de secours est appelé avec
l'option `&cache=1` (jeton `X-Lv-Git-Token` requis). Ce fichier n'est lu par AUCUN code
applicatif (`grep -rn "lvversion"` sur tout le dépôt : les 3 seules occurrences sont dans
`_lvgit.php` lui-même, toutes en écriture). C'est un pur artefact diagnostique, servi tel quel
en statique par le serveur web (aucun `noindex` puisqu'aucun code PHP n'intervient pour un
fichier `.txt` statique).

### 2. Pourquoi il n'est jamais mis à jour

Le mécanisme n'a **jamais été branché sur le bon déploiement**. Le pipeline qui déploie
RÉELLEMENT 100 % des mises en ligne depuis avril est `.github/workflows/deploy.yml`
(rsync/SSH, déclenché par push puis par `workflow_run` sur la CI). Ce pipeline ne contenait
AUCUNE étape qui touche `_lvversion.txt` - seul le point d'entrée de secours `_lvgit.php`
l'écrit, et seulement quand un humain (ou un agent) l'appelle manuellement avec `&cache=1`, ce
qui n'arrive que si la voie normale (SSH direct) est indisponible. Le journal du projet l'avait
déjà mesuré le 2026-09-08 (`QUESTIONS-CLAUDE.html`, entrée ticket #2291) : « son témoin de
version affiche encore 1.63.22 alors que la prod sert 1.255.0 - il n'a donc pas servi depuis
des mois, c'est mesuré », avec la recommandation de GARDER le point d'entrée (secours légitime)
sans corriger le témoin à ce moment-là.

### 3. Tous les endroits qui affichent une version, mesurés en PRODUCTION aujourd'hui (pas déduits)

| Endroit | Mécanisme | Valeur mesurée aujourd'hui (2026-09-11) | Commande |
|---|---|---|---|
| Footer public (bas de chaque page, ex. `laveille.ai/`) | `lv_version(false)` ← `config('version.semver')` ← `config/version.php` | **v1.259.0** | `curl -s https://laveille.ai/ \| grep -o 'Version applicative">[^<]*'` → `Version applicative">v1.259.0` |
| Footer admin (backoffice, 2 vues identiques) | idem, même source | v1.259.0 (non re-mesuré en direct - nécessite une session admin authentifiée ; même code que le footer public, même source, aucune raison de diverger) | lecture de code, pas de mesure séparée |
| `public/_lvversion.txt` (fichier statique orphelin) | écrit UNIQUEMENT par `_lvgit.php?cache=1`, jamais lu par l'app | **1.63.22** (HTTP 200) | `curl -s -w '\nHTTP_CODE:%{http_code}\n' https://laveille.ai/_lvversion.txt` → `1.63.22` / `HTTP_CODE:200` |
| `config/version.php` (source de vérité, dépôt) | déclaré | 1.259.0 (`major=1, minor=259, patch=0, codename=espace-auteur`) | lecture directe du fichier, commit `b5f6d996` |
| `package.json` (`"version"`) | statique, jamais lu par l'app, jamais affiché | 1.0.0 (n'a jamais bougé) | `grep '"version"' package.json` |
| `composer.json` | pas de champ `version` | n/a | `grep -n '"version"' composer.json` → aucune correspondance |
| Système d'info admin (`Modules/Backoffice/.../SystemInfoController.php`) | `PHP_VERSION` / `app()->version()` | version de PHP et du FRAMEWORK Laravel, pas de l'application - hors périmètre de ce défaut | lecture de code |

**Conclusion mesurée** : le footer (public ET admin, seule surface visible par un humain qui
navigue le site) est déjà exact et cohérent - **1.259.0 partout où un utilisateur regarde**. Le
seul témoin qui ment est le fichier statique orphelin `_lvversion.txt`, jamais consulté par
l'application, mais publiquement accessible en URL et donc trompeur pour QUICONQUE l'audite -
exactement ce qui s'est produit le 2026-09-08 puis dans ce mandat.

### Correctif appliqué (Défaut B)

Contrainte du mandat : « le correctif du témoin de version doit lire la source de vérité
unique, jamais un numéro recopié à la main ». J'ai donc **ajouté une étape dans le pipeline qui
déploie réellement le site** (`.github/workflows/deploy.yml`, nouvelle étape « Rafraîchir le
témoin statique de version (public/_lvversion.txt) », placée après « Purge Cloudflare cache »
et avant « Deploy notification », SANS `if: always()` - si une étape antérieure sans filet
`|| true` comme `migrate --force` fait échouer le job, ce témoin n'est PAS rafraîchi non plus, pour
ne jamais affirmer un déploiement réussi qui ne l'a pas été) :

```
SEMVER=$(php -r '$c = include "config/version.php"; echo $c["semver"] ?? "unknown";')
ssh gmemora@server.memora.pro "echo '${SEMVER}' > .../public/_lvversion.txt && cat .../public/_lvversion.txt"
```

`$SEMVER` est calculé sur le runner en lisant littéralement `config/version.php` du commit
`DEPLOY_SHA` qui vient d'être transporté - **exactement la même source unique** que
`app/Helpers/version.php::lv_semver()`, jamais un numéro saisi une seconde fois à la main.
Validé en local : `php -r '$c = include "config/version.php"; echo $c["semver"] ?? "unknown";'`
→ `1.259.0`, identique au footer déjà mesuré en prod. Le point d'entrée de secours
`_lvgit.php:140-150` continue lui aussi d'écrire ce fichier depuis la MÊME source
(`config/version.php`) quand il est invoqué - aucune contradiction possible entre les deux
écrivains, ils lisent la même vérité.

Je n'ai pas touché à `public/_lvgit.php` ni à son comportement (hors périmètre de ce mandat -
la sécurité de ce point d'entrée a déjà été traitée par ailleurs le 2026-09-10/11, vérifiée
identique dépôt=prod au §Défaut A point 4).

---

## Fichiers modifiés et sauvegardes

- `.github/workflows/deploy.yml` - sauvegardé avant écriture dans
  `.github/workflows/deploy.yml.avant-garde-fou-transport-et-temoin-version-20260911-1147`
  (ignoré par git, motif `*.avant-*` de `.gitignore:104`). Deux ajouts : le garde-fou de
  transport dans l'étape « Deploy via rsync », et la nouvelle étape de rafraîchissement de
  `public/_lvversion.txt`. Zéro exclusion retirée. YAML validé (`python3 -c "import yaml;
  yaml.safe_load(...)"` → OK, 14 étapes dans l'ordre attendu) et les deux blocs `run:` modifiés
  validés séparément par `bash -n` (syntaxe propre).
- Aucun autre fichier modifié. Aucun commit, aucun push, aucun déploiement.

## Ce qui n'a PAS été corrigé, et pourquoi

- **`public/_lvgit.php.avant-durcissement-serveur-20260911-0730`** (serveur) et
  **`public/_lvgit.php.avant-durcissement-20260910-1514`** (dépôt local, non suivi par git,
  déjà couvert par `.gitignore:104` `*.avant-*`) : ces deux sauvegardes sont hors périmètre de
  ce mandat (Défauts A et B seulement). La première est déjà neutralisée (410 Gone) par une
  action antérieure ; la seconde ne peut jamais être commitée ni déployée. Aucune action prise,
  aucune suppression - conformément à l'interdit du mandat.
- **`node_modules/`, `*.log`, `error_log`** dans la liste d'exclusion : signalés comme inertes/
  redondants avec `.gitignore` (§Défaut A point 3), gardés tels quels - retirer une ligne inerte
  ne rapporte rien et le mandat demande explicitement de ne pas retirer sans preuve de bénéfice.
- Le footer ADMIN n'a pas été re-mesuré par requête HTTP en direct (nécessite une session
  authentifiée) - son code est identique au footer public déjà mesuré, donc affirmé par lecture
  de code et non par une seconde mesure séparée. Je le signale explicitement plutôt que de le
  présenter comme mesuré.
