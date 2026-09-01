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

## 10. Répartition serveur / Mac 2 - une copie récente en production, sept sur le Mac 2

Rédigé le 1er septembre 2026 entre 17h30 et 18h12 Québec (21:30-22:12 UTC), mandat #2083 (deux
questions factuelles, lecture seule sur toute l'infrastructure).

**Correction à la section 3 ci-dessus.** Elle conclut « aucun filet hors-site à jour n'existe encore »
en vérifiant `/Volumes/BACKUP_SERV/cpanel_backups/db/laravel-auto/gmemora_laveille/` - ce chemin
existe bel et bien et s'arrête au 23 juin 2026, mais c'est un reliquat pré-migration. Le script actif
(`backup-cpanel-nightly.sh`, plus bas) écrit depuis le 23 juin sur un AUTRE volume,
`/Volumes/BACKUP_MAIN_WD2`, sous le même sous-chemin `BACKUP_SERV/cpanel_backups/...`. Vérifié en
direct le 1er septembre 2026 : cette copie-là contient des dumps `gmemora_laveille` jusqu'au 28 août
2026 inclus. Le filet hors-site existe et est globalement à jour ; il n'était simplement pas au chemin
que la section 3 a vérifié.

**La répartition, et pourquoi le plafond serveur n'est pas relevé.**

- *Serveur de production* : une copie récente seulement (`storage/app/private/La veille/*.zip`,
  section 1), contrainte par `config/backup.php` - `MaximumStorageInMegabytes::class => 5000`
  (moniteur Spatie, ligne 287) et `delete_oldest_backups_when_using_more_megabytes_than => 5000`
  (stratégie de nettoyage, ligne 352). Une archive complète (code + base + fichiers non versionnés)
  pèse à elle seule 3,4 à 4,1 Go sur les exemplaires vérifiés le 1er septembre (voir plus bas) - déjà
  tout près du plafond de 5000 Mo (~4,88 Go). Le serveur ne peut structurellement pas en garder deux :
  dès qu'une deuxième archive existe, la stratégie de nettoyage supprime la plus ancienne le lendemain
  (`backup:clean`, planifié quotidiennement à 04h00 en production, `routes/console.php` ligne 17).
- *Mac 2* : sept copies datées distinctes en rotation, confirmé en direct le 1er septembre 2026 -
  `/Volumes/BACKUP_MAIN_WD2/BACKUP_SERV/cpanel_backups/files/daily.0/` à `daily.6/`, chacune un
  instantané rsync complet de `public_html` (donc de `apps_diverses/laveille.ai/` en entier) à une nuit
  différente. Vérifié directement dans 4 des 7 dossiers : `daily.6` (15 août,
  `2026-08-15-03-00-23.zip`, 3,4 Go), `daily.3` (21 août, `2026-08-21-03-00-23.zip`, 3,6 Go), `daily.1`
  (24 août, `2026-08-24-03-00-23.zip`, 3,7 Go), `daily.0` (29 août, `2026-08-29-03-00-24.zip`,
  4,1 Go) - sept archives réellement distinctes (dates et poids différents, pas un même fichier
  dupliqué), croissance cohérente avec les 4,92 Go cités pour l'archive la plus récente. En plus de
  cette rotation par fichiers, un second mécanisme (`db/laravel-auto/gmemora_laveille/`, même script)
  rapatrie séparément le dump SQL seul, avec une rétention de 30 jours - une profondeur supplémentaire
  qui ne vit que sur le Mac 2.
- *Pourquoi ne pas relever le plafond serveur* (décision prise, non rouverte ici) : la profondeur utile
  vit déjà sur le Mac 2 (sept nuits de fichiers, jusqu'à 30 jours de base seule). Relever le plafond
  serveur ne gagnerait qu'une ou deux archives de plus au prix d'un disque de production déjà proche de
  sa limite pour ce poste, alors que le Mac 2 a 406 Go libres sur `BACKUP_SERV` et un disque externe
  dédié (`BACKUP_MAIN_WD2`) pour ce trafic. Une archive de plus sur la production protégerait moins
  qu'une de plus sur le Mac 2.

**Question 1 - somme de contrôle : NON, ni sur les fichiers ni sur la base, telles que stockées.**

Vérifié en lisant directement le contenu des dossiers (pas seulement leur nom) :

- *Archives fichiers* (les sept `daily.N/.../La veille/*.zip` ci-dessus) : chaque dossier ne contient
  QUE le zip Spatie. Aucun `.sha256`, `.md5` ou `.sig` à côté, dans aucun des 4 dossiers inspectés.
- *Dumps base seule* (`db/laravel-auto/gmemora_laveille/*.sql.gz`, 19 fichiers du 31 juillet au
  28 août 2026 vérifiés) : même constat, aucun fichier de somme à côté.
- *Le mécanisme de rapatriement lui-même ne calcule ni ne vérifie rien après coup pour laveille.* Dans
  `backup-cpanel-nightly.sh` (Mac 2, `/Users/memora/scripts/`), l'étape qui rapatrie les dumps Laravel
  (dont `gmemora_laveille`) ne contrôle que le code de sortie du `rsync` - rien n'ouvre le `.sql.gz`
  pour confirmer qu'il est lisible. Preuve la plus parlante : le MÊME script, une section plus haut, le
  fait pour un AUTRE projet (LucidNest) - `gzip -t` + seuil de taille (< 1 000 000 octets = suspect) +
  âge (> 26h = périmé) avant de considérer le dump sain. Ce garde-fou existe donc dans l'outillage
  Memora, appliqué à côté de laveille, pas sur laveille.
- *Le zip fichiers n'a pas non plus de test d'ouverture après le rsync* (pas de `unzip -t` ni
  équivalent dans le script, pour aucun projet).
- *Note de portée* : ce constat couvre l'archive telle que stockée sur le Mac 2, ce que le mandat
  demande explicitement de vérifier. Le script qui génère le dump côté serveur
  (`~/dump_laravel_dbs.sh`, exécuté par SSH depuis le Mac 2 mais hébergé sur le compte cPanel) n'a pas
  été lu : il est hors du périmètre d'accès de ce mandat (MCP `mac2` uniquement, lecture seule).
- *Signal voisin, mais éteint* : `config/backup.php` déclare un moniteur Spatie natif
  (`monitor_backups`, `MaximumAgeInDays::class => 1`, notifications mail configurées -
  `BackupHasFailedNotification`, `UnhealthyBackupWasFoundNotification`, etc.). Vérifié dans
  `routes/console.php` : seuls `backup:run` (03h00) et `backup:clean` (04h00) sont planifiés - la
  commande `backup:monitor`, qui déclencherait ces notifications, n'apparaît nulle part dans le
  planificateur. Ce moniteur est configuré mais jamais exécuté ; et même actif, il ne vérifie que
  fraîcheur et taille, pas l'intégrité du contenu (ce n'est pas un test d'ouverture).

**Question 2 - signal de vie : NON pour l'arrêt total, OUI seulement pour un échec pendant une
exécution en cours.**

Le mécanisme (`backup-cpanel-nightly.sh` + `com.memora.cpanel-backup.plist`, `StartCalendarInterval`
02h30 quotidien) envoie déjà des courriels d'alerte réels quand une brique échoue AU COURS d'une
exécution - volume introuvable, dump LucidNest périmé/corrompu, tier-backup GDrive inactif, fichiers
`public_html` périmés (>48h), échec rsync par répertoire - relayés par `exim` cPanel vers
`chatgptpro@gomemora.com` (le Mac ne livre pas de courriel externe directement). Ces alertes
fonctionnent : vérifié dans le journal du 28 août, l'échec de montage à 02h30 a suivi exactement ce
chemin (3 tentatives de remontage par UUID, échec, abandon propre, alerte envoyée).

Mais rien ne surveille que le mécanisme se déclenche et se termine tout court. Preuve en direct, pas
hypothétique : le fichier `~/logs/cpanel-backup-heartbeat.log` - créé le 7 août 2026 précisément pour
rendre visibles les nuits sautées par launchd - s'arrête au **28 août 2026 16h52** au moment de la
vérification (1er septembre, 18h12 Québec) : aucune ligne pour les 29, 30, 31 août ni le 1er
septembre, soit 4 jours sans le moindre déclenchement journalisé. Cause reconstituée à partir du
journal détaillé `cpanel-backup-20260828.log` : la tentative de 02h30 le 28 août a échoué proprement
(volume introuvable, alerte envoyée), mais une seconde tentative à 16h52 le même jour a démarré,
obtenu le verrou (`flock`), puis enchaîné un blocage inexpliqué d'environ 21h (les seules opérations du
script à cet endroit - six renommages de dossiers de rotation sur le même volume - sont normalement
quasi instantanées et n'expliquent pas ce délai), un rsync `public_html` qui a consommé tout son
plafond de 18h avant d'être tué par `gtimeout`, puis une dernière étape (purge des dumps de plus de
30 jours, puis calcul de la taille totale via `du -sh` sur 903 Gio) qui a duré environ 44h - le tout ne
s'est terminé que le 1er septembre à 05h22 (durée totale mesurée par le script lui-même : 304221s, soit
84,5h). Pendant ces ~85 heures, le verrou est resté détenu : launchd n'a donc pas pu (et n'a pas
cherché à) relancer une nouvelle instance aux 4 occurrences de 02h30 tombées dans cette fenêtre. Ni le
script ni rien d'autre n'a signalé cette absence de déclenchement - elle n'était visible qu'en lisant
le fichier de traces directement, exactement le geste que ce mandat vient de faire.

Rien d'externe ne surveille non plus ce silence. Vérifié via Robotalp (déjà dans l'outillage Memora,
lecture seule, hors périmètre Mac 2 donc consulté sans restriction particulière) : recherche « backup »
et « mac2 » sur l'ensemble des 16 espaces de travail et 75 robots du compte - zéro résultat.
Structurellement, les types de robots disponibles (uptime, ping, port, API, SSL, DNS, mot-clé,
changement de page...) surveillent des services accessibles par le réseau ; le Mac 2 étant derrière un
filtrage réseau distinct, un robot de ce genre ne peut de toute façon pas lire l'horodatage d'un
fichier local sur le Mac 2.

*Ce qu'il faudrait, sans l'installer* :
1. Une vérification de fraîcheur indépendante sur le fichier de battements
   (`cpanel-backup-heartbeat.log`) ou sur la dernière ligne « FIN backup » du journal daté - le même
   patron que le script applique déjà à deux autres mécanismes dans ce même fichier (dump LucidNest
   périmé après 26h, journal tier-backup périmé après 48h). Ironie précise à corriger : le script
   surveille le pouls de deux mécanismes voisins, jamais le sien.
2. Un plafond de durée totale pour l'ensemble du script (distinct des plafonds déjà en place par
   étape), pour qu'un blocage de plusieurs jours déclenche une alerte au lieu d'absorber silencieusement
   plusieurs nuits.
3. Idéalement, cette vérification tournerait hors du verrou (`flock`) du script principal, pour qu'un
   run bloqué ne bloque pas aussi le signal qui devrait avertir qu'il est bloqué.

**Le script qui porte déjà les garde-fous manquants - trouvé, partiellement réutilisable.**

`/Users/memora/planifize-backup/backup.sh` (Mac 2, réécrit le 29 août 2026 pour un tout autre projet,
Planifize) porte les trois garde-fous cités dans le mandat, et son propre commentaire explique
pourquoi il a été ajouté : un test `mount | grep` seul s'est avéré insuffisant le 29 août 2026 - le
volume apparaissait monté (confirmé par `mount` ET `diskutil info`), mais l'écriture était quand même
refusée (« Operation not permitted », restriction TCC probable sous launchd) - exactement la classe de
panne silencieuse que la question 2 vise.

Les trois garde-fous, littéralement dans ce script :
1. *Double vérification de montage* : `mount | grep` ET `diskutil info`, avec tentative de remontage
   par UUID stable avant d'abandonner.
2. *Test d'écriture par fichier témoin* : `WD2_TEST_FILE="$DEST/.write_test_$$"`,
   `trap 'rm -f "$WD2_TEST_FILE"' EXIT` pour un nettoyage garanti, écriture réelle testée AVANT de
   toucher aux vraies données.
3. *Alerte courriel* : relais SSH vers `exim` sur le compte cPanel de production (le Mac ne livre pas
   de courriel externe directement), même destinataire que le script principal.

Réutilisabilité pour laveille, honnêtement évaluée : le script lui-même n'est PAS réutilisable tel
quel - il est câblé en dur pour Planifize (hôte distant, clé SSH, script de dump, chemin rsync source,
rotation à 14 dumps). Mais `backup-cpanel-nightly.sh` (le mécanisme qui couvre réellement laveille) a
déjà le garde-fou 1 (sous une forme encore plus étoffée, avec 3 tentatives espacées) et déjà le
garde-fou 3 (alertes plus nombreuses que celles de Planifize). Le seul manquant réel est le garde-fou
2 : `backup-cpanel-nightly.sh` vérifie le montage puis passe directement à `mkdir -p` et aux opérations
réelles, sans jamais prouver la capacité d'écriture par un fichier témoin. Le bloc à copier (une
dizaine de lignes) est directement transposable : `backup-cpanel-nightly.sh` a déjà les variables
équivalentes (`$DEST`, `$WD2_MOUNT`, `$LOG`) et un patron d'alerte déjà éprouvé dans le même fichier -
il ne s'agirait pas d'importer Planifize, mais d'ajouter localement le même test, au même endroit
logique (juste après la vérification de montage existante, avant les premières écritures). Non fait
ici, conformément au mandat (constat, pas déploiement).

**Provenance de cette section.** Lecture seule via le MCP `mac2` (`mac2_status`, `mac2_list_dir`,
`mac2_read_file` - jamais `mac2_exec`, bloqué côté serveur par `MAC2_READ_ONLY=true`, ni
`mac2_upload`/`mac2_write_file`). Chemins et contenus cités ici ont tous été lus directement le jour de
la rédaction : `backup-cpanel-nightly.sh`, `com.memora.cpanel-backup.plist`,
`cpanel-backup-heartbeat.log`, `cpanel-backup-20260828.log`, `planifize-backup/backup.sh`, les
listings de `cpanel_backups/files/daily.0` à `daily.6` et de `cpanel_backups/db/laravel-auto/
gmemora_laveille`. `config/backup.php` et `routes/console.php` ont été lus dans ce dépôt local
(fichiers de configuration versionnés, aucun secret). Robotalp interrogé en lecture seule
(`robotalp_search`) pour confirmer l'absence de moniteur externe. Aucune écriture n'a été faite sur le
Mac 2 ; la seule écriture de ce mandat est ce fichier.
