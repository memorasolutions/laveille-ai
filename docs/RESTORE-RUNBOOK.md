# Guide de restauration - laveille.ai

Dédié à laveille.ai. `RESTORE-RUNBOOK.md` n'existe nulle part ailleurs sur ce poste sous ce nom
(recherche exhaustive du disque le 31 août 2026) : le fichier le plus proche par le nom
(`lucidnest/scripts/runbook-backup-restore.md`) couvre un tout autre projet et un tout autre
chantier. Ce fichier est donc un fichier DÉDIÉ, neuf, placé à la racine de `docs/` pour suivre la
convention déjà en place ici (`CONTRAINTES-SOUS-AGENTS.md`, `HISTORIQUE-VERSIONS.md`).

Sections : 0. Avertissement · 1. Où vit l'archive · 2. Contenu · 3. Copie hors-site · 4. Ordre de
restauration · 5. Procédure · 6. Vérification · 7. Temps à prévoir · 8. Pièges connus ·
9. Provenance et limites de ce guide.

## 0. Avertissement en tête - lire avant tout le reste

Vérifié EN DIRECT le 31 août 2026 (lecture du `.env` de production, compte cPanel `gmemora`) :

- Le mécanisme de chiffrement de l'archive existe dans le code (`config/backup.php`, clé
  `BACKUP_ARCHIVE_PASSWORD`, AES-256 via `'encryption' => 'default'`).
- **Cette clé n'est PAS posée dans le `.env` de production actuel.** L'archive produite chaque
  nuit n'est donc PAS chiffrée aujourd'hui : un simple `unzip` suffit, aucun mot de passe n'est
  nécessaire pour l'instant.
- C'est un problème, mais pas celui qu'on croirait : cette archive NON chiffrée contient le
  `.env` complet de production (clés Stripe live, mots de passe base de données et admin, toutes
  les clés API - `.env` n'est exclu nulle part dans `config/backup.php`). Tant qu'elle reste non
  chiffrée, quiconque met la main sur ce zip a tout.
- Le journal du projet (`QUESTIONS-CLAUDE.html`, entrée #228, 31 août 2026 05h16 Québec
  [09:16 UTC], statut **en attente**) demande à Stéphane de déposer un mot de passe d'archive
  dans le coffre 1Password (`projet:laveille`) **avant** de l'activer en production - pour ne
  jamais reproduire le problème qu'il doit résoudre (un secret dont l'unique copie vit au même
  endroit que ce qu'il protège).
- **Tant que cette entrée reste "en attente" : suivre ce guide tel quel, aucun mot de passe à
  fournir.** Le jour où `BACKUP_ARCHIVE_PASSWORD` apparaît dans le `.env` de production, revenir
  modifier l'étape 3 de la section 5 : un mot de passe devient alors obligatoire, et sa seule
  copie doit déjà être dans le coffre à ce moment-là.

**Correction de prémisse.** Ce guide a été commandé en indiquant qu'une restauration complète
avait déjà été exécutée et prouvée le 30 août 2026 (264 tables, 78 comptes, 6248 actualités,
54 secondes). Cette exécution reste introuvable malgré une recherche complète (historique git en
entier, mémoire du projet, journal `QUESTIONS-CLAUDE.html`) - et l'entrée #228 ci-dessus, la plus
récente et la plus proche du sujet, dit au contraire que le mot de passe n'est pas déposé et que
la copie hors-site est "en cours de mise en place", pas terminée. Détail complet en section 9. Ce
guide documente donc un **mécanisme vérifié**, pas une **exécution vérifiée**.

## 1. Où vit l'archive

**Sur le serveur de production** (compte cPanel `gmemora`) :

```
/home/gmemora/public_html/apps_diverses/laveille.ai/storage/app/private/La veille/
```

`APP_NAME` vaut littéralement `La veille` (avec l'espace, vérifié dans le `.env` de production) -
Spatie l'utilise tel quel comme nom de dossier, sans le nettoyer. Toujours citer ce chemin entre
guillemets dans une commande shell. Chaque fichier : `AAAA-MM-JJ-HH-mm-ss.zip` (ex.
`2026-08-31-03-00-00.zip`) - format vérifié dans le paquet lui-même
(`vendor/spatie/laravel-backup/src/Tasks/Backup/BackupJob.php`, constante `FILENAME_FORMAT`).

**Depuis l'admin du site - le chemin le plus simple, à préférer** : il contourne les deux pannes
cPanel de la section 8.

```
https://laveille.ai/admin/backups
```

Liste chaque archive (nom, taille, date), la télécharge via un flux HTTP authentifié (fonctionne
même pour un gros fichier - contourne le gestionnaire de fichiers cPanel cassé) et peut en
déclencher une nouvelle immédiatement. Vérifié dans le code : `Modules/Backoffice/routes/web.php`
(groupe `admin.`, routes `backups.index` / `backups.download` / `backups.run`) et
`Modules/Backoffice/app/Http/Controllers/BackupController.php`. Nécessite un compte avec la
permission `view_backups` (téléchargement et déclenchement : `manage_backups`) - le compte
superadmin (`stephane@memora.ca`, `SUPER_ADMIN_EMAIL` en prod) les a.

Le module nwidart `Backup` (désactivé dans `modules_statuses.json`, local ET prod) est un
scaffold mort sans rapport avec cet écran - ne pas chercher la fonctionnalité de ce côté, elle
vit entièrement dans le module Backoffice ci-dessus.

## 2. Ce que contient l'archive

Une seule archive = tout, vérifié dans `config/backup.php` :

- **Le code de l'application au complet** (`base_path()`), sauf `vendor/`, `node_modules/`,
  `.git/`, `storage/logs/`, les caches Laravel, `.idea/`, `.vscode/`.
- **`.env` de production inclus** (absent de la liste d'exclusion) - voir l'avertissement de la
  section 0.
- **`storage/app/public/`** (fichiers/images téléversés) et **`public/screenshots/`** (captures
  générées, jamais versionnées dans git) - la SEULE copie de ces fichiers en dehors du serveur
  lui-même.
- **Un dump de la base de données**, nommé exactement `mysql-gmemora_laveille.sql` à l'intérieur
  du zip (vérifié dans `BackupJob.php` : type de connexion + nom réel de la base ;
  `database_dump_compressor` vaut `null`, donc aucune compression séparée sur ce fichier - seule
  la compression du zip s'applique).

**Ce qui n'a normalement pas besoin de cette archive** : le code applicatif est déjà versionné
deux fois (GitHub = `origin`, déclenche la CI et le déploiement ; Forgejo du Pi = miroir - voir
`CLAUDE.md` du projet). Ne restaurer le CODE depuis ce zip que si un redéploiement git normal
n'est pas une option ; sinon un redéploiement est plus sûr et plus rapide, et n'écrase pas des
correctifs déployés après la date de cette archive.

## 3. Copie hors-site - où elle vit, et son état réel

Vérifié en direct sur Mac 2 le 31 août 2026 :

- `/Volumes/BACKUP_SERV/cpanel_backups/db/laravel-auto/gmemora_laveille/` contient des dumps
  `.sql.gz` quotidiens de la base... qui s'arrêtent au **23 juin 2026**
  (`gmemora_laveille-2026-06-23.sql.gz`, 104 Mo). **69 jours de retard au moment de cette
  vérification.** Ce mécanisme générique (partagé avec d'autres sites Laravel de l'hébergement)
  semble arrêté depuis fin juin - à confirmer/relancer ; ce n'est plus un filet fiable en l'état.
- Aucun dossier dédié « laveille » à jour n'existe à la racine de `BACKUP_SERV`. À comparer avec
  `clinique-alexandre-blouin/archives` (dossier du 28 août 2026, 3 jours) qui montre le patron
  récent attendu pour un site à jour - pas encore répliqué pour laveille.
- Aucune sauvegarde cPanel native disponible (`Aucun backup disponible` au moment de la
  vérification). Le seul backup complet du compte cPanel entier trouvé sur Mac 2
  (`BACKUP CPANEL/backup-2.9.2026_21-09-00_gmemora.tar.gz`, 31 Go) date du 9 février 2026 - trop
  vieux pour servir de filet à laveille.ai spécifiquement, et couvre les 46 bases du compte, pas
  seulement la sienne.
- Ce constat correspond exactement au journal du projet (entrée #228, section 0) : la copie
  hors-site est « en cours de mise en place ».

**Conséquence pratique aujourd'hui : la seule copie fraîche de l'archive de laveille.ai est celle
qui vit SUR le serveur de production lui-même (section 1). Aucun filet hors-site à jour n'existe
encore.**

## 4. Ordre de restauration recommandé

Non testé de bout en bout à ce jour (section 9) - ordre justifié par la structure réelle du
projet (section 2), pas par un précédent observé :

1. **Base de données d'abord.** C'est la seule donnée réellement irremplaçable et la cause la
   plus probable d'un incident (corruption, suppression accidentelle). L'application peut rester
   en mode maintenance pendant cette étape.
2. **Fichiers non versionnés ensuite, de façon ciblée** : extraire uniquement
   `storage/app/public/` et `public/screenshots/` (et tout autre chemin explicitement absent de
   git) depuis le zip. **Ne jamais extraire le zip complet par-dessus le déploiement en place** :
   il contient une copie du code figée à l'heure de la sauvegarde ; l'écraser annulerait tout
   correctif déployé depuis.
3. **Le code applicatif**, s'il doit être restauré, vient de git (dépôt + pipeline de
   déploiement habituels du projet), jamais de ce zip.

## 5. Procédure pas à pas

Sauvegarder l'état courant AVANT toute écriture (garde-fou permanent du projet - un `cp -a` du
dossier visé suffit pour un rollback rapide).

**Étape 1 - confirmer que l'archive existe et récupérer son nom exact.**
Se connecter à `https://laveille.ai/admin/backups` (compte superadmin). La liste affiche nom,
taille et date de chaque zip présent dans `storage/app/private/La veille/`. Ne PAS utiliser
`cpanel_file_list` sur ce dossier - piège connu, voir section 8 : il répond « Empty or not
accessible » même quand le dossier est plein.

**Étape 2 - télécharger l'archive.**
Bouton de téléchargement de la ligne voulue (flux HTTP authentifié, route
`admin.backups.download`). Ne PAS tenter de rapatrier ce fichier via `cpanel_file_read` (MCP) :
l'archive contient une base d'environ 1,5 Go (taille réelle de `gmemora_laveille` au 31 août
2026) compressée avec le reste du site - largement au-delà de ce qu'un outil de lecture de
fichier texte peut gérer correctement.

**Étape 3 - déchiffrer si nécessaire.**
Aujourd'hui (section 0) : aucun mot de passe n'est requis.

```bash
unzip "2026-08-31-03-00-00.zip" -d restauration-laveille/
```

Le jour où `BACKUP_ARCHIVE_PASSWORD` est posé en prod (et alors seulement, une fois sa valeur
dans le coffre 1Password `projet:laveille`) :

```bash
unzip -P "<mot de passe - coffre 1Password projet:laveille>" "AAAA-MM-JJ-HH-mm-ss.zip" -d restauration-laveille/
```

**Étape 4 - restaurer la base de données.**
Le dump se trouve à `restauration-laveille/mysql-gmemora_laveille.sql`. Les identifiants de
connexion (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) sont dans le `.env`
de production - ne pas les dupliquer ici. Deux chemins, du plus simple au plus certain sur ce
compte :

- **phpMyAdmin** (fonctionnalité cPanel standard, distincte du module Terminal cassé - section
  8) : importer `mysql-gmemora_laveille.sql` directement via son écran d'import. **Non vérifié
  dans le cadre de la rédaction de ce guide** - à essayer en premier si l'accès existe, car le
  plus direct.
- **À défaut** : passer par le même patron déjà établi et éprouvé sur ce compte pour exécuter du
  code côté serveur malgré le Terminal cassé (section 8) - `scripts/prod-artisan.sh` +
  `scripts/templates/prod-oneshot.php.tpl`. Ce gabarit ne couvre aujourd'hui QUE les commandes
  `/actu2` (`COMMANDES_AUTORISEES` vérifié le 31 août 2026) ; une restauration réelle par cette
  voie exige d'abord d'y ajouter une commande dédiée qui lit le `.sql` et l'exécute via la
  connexion MySQL déjà configurée par Laravel (`DB::unprepared()` ou équivalent, en PHP pur -
  aucun besoin du client `mysql` en ligne de commande, qui n'est pas accessible sans terminal).
  Cet ajout est hors du périmètre de ce guide, qui documente ce qui existe déjà ; le prévoir AVANT
  d'en avoir besoin en urgence est le principal chantier de suivi de ce document.

**Étape 5 - restaurer les fichiers non versionnés.**
Depuis le serveur, chemins relatifs à la racine du projet
(`/home/gmemora/public_html/apps_diverses/laveille.ai/`) :

```bash
cp -a restauration-laveille/storage/app/public/. storage/app/public/
cp -a restauration-laveille/public/screenshots/. public/screenshots/
```

**Étape 6 - vérifier.** Voir section 6.

**Étape 7 - nettoyage.** Supprimer le dossier `restauration-laveille/` et toute copie
intermédiaire de l'archive. Si un fichier one-shot a été déposé (étape 4) : confirmer sa
disparition par un appel qui doit répondre 404 (patron déjà en place dans
`scripts/prod-artisan.sh`, étape 3 de ses instructions). Purger les caches (skill `/clear-cache`)
et valider visuellement le site (Playwright, navigateur visible) avant de considérer l'incident
clos.

## 6. Vérification qui prouve la réussite

Trois comptages, contre les tables réelles du projet (vérifiées le 31 août 2026 :
`database/migrations/0001_01_01_000000_create_users_table.php` et
`Modules/News/database/migrations/2026_03_29_000000_create_news_tables.php`) :

```sql
SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM news_articles;
```

**Aucun chiffre cible fixe n'est donné ici.** Le jeu de chiffres qui circulait pour cette
procédure (264 tables, 78 comptes, 6248 actualités) n'a pu être retracé nulle part (section 9) -
il serait plus dangereux de les recopier comme repères que de ne pas en donner : un chiffre faux
qui rassure à tort coûte plus cher qu'une case vide. Comparer plutôt ces trois comptages à la
dernière valeur connue AVANT l'incident (tableau de bord `/admin`, export GA4, ou une note prise
juste avant) - un chiffre de référence n'a de valeur que reproductible et daté du même incident.
Un exemple du format à viser une fois qu'une exécution réelle existera :
`lucidnest/scripts/runbook-backup-restore.md`, section « Preuves d'exécution » (comptages avant,
checksum, comptages après, collés tels quels).

## 7. Temps à prévoir

Non mesuré pour laveille.ai - aucune exécution retrouvée (section 9). Seul repère fiable au
31 août 2026 : la base de production pèse environ 1,52 Go (`gmemora_laveille`, vérifié en
direct). Le chiffre de 54 secondes qui circulait pour ce guide n'a pas pu être retracé - ne pas
s'y fier comme engagement de temps ; le mesurer réellement à la première exécution et remplacer
cette section par le chiffre observé.

## 8. Pièges connus de ce compte (reproduits en direct le 31 août 2026)

- **Terminal cPanel hors service** (module serveur manquant). Erreur exacte obtenue en testant
  `echo TERMINAL_OK` :
  `Can't locate Cpanel/API/Shell.pm in @INC`. Ceci touche tout accès shell sur ce compte, humain
  ou automatisé - pas seulement les outils utilisés pour ce guide.
- **Gestionnaire de fichiers / listing cPanel cassé** : répond « Empty or not accessible » même
  sur des dossiers réels et non vides. Reproduit deux fois (`storage/app` et
  `storage/app/private`, tous deux certainement non vides sur une application qui tourne). Ne
  jamais conclure « le dossier est vide » depuis ce canal - utiliser `/admin/backups` (section 1)
  ou lire un nom de fichier déjà connu par ailleurs.
- **Contournement déjà établi** pour exécuter du code côté serveur malgré les deux pannes
  ci-dessus : `scripts/prod-artisan.sh` (reste local, jamais déployé) génère un fichier PHP
  autonome à partir de `scripts/templates/prod-oneshot.php.tpl`, déposé dans `public/`, protégé
  par jeton, avec liste blanche de commandes ET d'arguments (`COMMANDES_AUTORISEES` /
  `ARGUMENTS_AUTORISES`), expiration automatique testée en premier (45 minutes), auto-suppression
  sur demande explicite (`last=1`). Vérifié le 31 août 2026 : la liste blanche actuelle ne couvre
  QUE les commandes `/actu2` (`news:brief`, `news:source`, `news:apply`, `news:create-draft`,
  `news:backfill-auto-tools`) - aucune commande de sauvegarde ou de restauration n'y figure
  encore (voir étape 4, section 5).

## 9. Provenance de ce guide et limites

- Rédigé le 31 août 2026 (mandat #2084) par une session dédiée à la documentation de ce dépôt.
- Tout ce qui porte une référence de fichier (`config/backup.php`, `routes/console.php`,
  `vendor/spatie/laravel-backup/...`, `Modules/Backoffice/...`, `scripts/prod-artisan.sh`,
  `scripts/templates/prod-oneshot.php.tpl`, `modules_statuses.json`) a été lu directement dans le
  dépôt le jour de la rédaction. La version installée de `spatie/laravel-backup` est la 9.4.1
  (`composer.lock`) ; ce paquet ne fournit que quatre commandes (`backup:run`, `backup:clean`,
  `backup:list`, `backup:monitor`) - **aucune commande `backup:restore` n'existe dans ce paquet**,
  malgré ce que pourrait laisser croire un gabarit générique trouvé ailleurs sur ce poste
  (`laravel_vierge/docs/DEPLOY-BACKUP-DRILL.md`, qui appartient à un tout autre projet et cite
  cette commande sans qu'elle existe réellement - à ne surtout pas copier telle quelle).
- Tout ce qui porte une heure de vérification (`.env` de production, liste des tâches planifiées
  cPanel, liste des bases MySQL, test du Terminal cPanel, listing Mac 2) a été interrogé en
  direct le 31 août 2026 entre 09h50 et 10h20 Québec (13h50-14h20 UTC).
- **Ce qui n'a pas pu être vérifié, malgré une recherche complète** : une exécution réelle et
  réussie de cette procédure pour laveille.ai. Recherche effectuée dans l'historique git complet
  (toutes les branches locales et distantes, le reflog, le stash), la mémoire de session du
  projet, les rapports (`.audit/`, `.rapports_projet/`) et le journal `QUESTIONS-CLAUDE.html` en
  entier. Le jeu de chiffres annoncé pour cette exécution (264 tables, 78 comptes, 6248
  actualités, 54 secondes, 30 août 2026) ne s'y trouve nulle part. L'entrée la plus récente et la
  plus proche du sujet dans le journal (#228, 31 août 2026 05h16 Québec, statut toujours « en
  attente ») dit au contraire que le mot de passe n'est pas déposé et que la copie hors-site est
  « en cours de mise en place » - deux affirmations incompatibles avec une restauration complète
  déjà réussie la veille.
- **Conséquence directe : ce guide documente un mécanisme vérifié, pas une exécution vérifiée.**
  La première exécution réelle (même partielle, même en environnement de test) devrait remplacer
  les sections 4, 6 et 7 par des faits observés, preuve collée à l'appui - sur le modèle de
  `lucidnest/scripts/runbook-backup-restore.md`, section « Preuves d'exécution », pour un chantier
  différent mais un même souci de ne documenter que ce qui a réellement tourné.
