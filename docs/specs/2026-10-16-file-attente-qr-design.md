# Outil gratuit "File d'attente par code QR" - plan de conception

> Auteur : MEMORA solutions (info@memora.ca). Statut : BROUILLON de conception (v0.1), en attente
> de la ronde de réfutation des oracles navigateur (ChatGPT, Gemini, claude.ai). Oracle déjà consulté :
> Hermes/DeepSeek (reasoning). Perplexity NON consulté ce soir (défi Cloudflare, signalé).
> Ronde 1 (génération) faite; ce document est la cible de la ronde 2 (réfutation).

## 1. Besoin (mots du fondateur, 2026-10-16)

Un animateur (un prof, une salle d'attente, etc.) inscrit des personnes, une par ligne. L'outil exporte
en PDF tous les codes QR (une ou plusieurs feuilles), chaque QR avec le nom de la personne dessous.
Chaque personne scanne SON QR pour rejoindre une file, affichée dans l'ORDRE d'arrivée. La page de
l'animateur permet de réordonner et de retirer. Une autre page affiche la file en TEMPS RÉEL. Une
personne ne peut pas scanner deux fois pour apparaître deux fois (dédoublonnage). Option, au choix de
l'animateur : un 2e scan retire la personne de la file. Système à rendre performant et intuitif.

## 2. Décisions d'architecture (tranchées, avec le pourquoi)

### 2.1 Un module dédié, exportable - `Modules/FileAttente`
Module nwidart autonome, activable/désactivable (`modules_statuses.json`), exportable vers un autre
projet Laravel (règle fondateur). Il RÉUTILISE (DRY, aucune dépendance nouvelle) :
- `Modules\Core\Services\QrCodeService` pour les QR (PNG, contraste WCAG, presets charte, logo).
- `barryvdh/laravel-dompdf` (déjà présent) pour le PDF, via `Pdf::loadView(...)->setPaper('A4')`.
- Le patron d'outil public du module `Tools` : une entrée `Tool` (slug `file-attente`) pointe vers la
  page publique du module; référencement dans `/outils` sans dupliquer la logique.

### 2.2 Aucun compte requis - session d'animateur anonyme
L'animateur crée un "tableau" sans s'inscrire. Trois URL, chacune portant un jeton aléatoire
non devinable (jamais d'identifiant séquentiel) :
- **gérer** : `/file-attente/g/{manage_token}` - page animateur (réordonner, retirer, réglages).
- **afficher** : `/file-attente/a/{display_token}` - affichage public temps réel (plein écran).
- **scanner** : `/file-attente/s/{qr_token}` - un jeton par personne, encodé dans son QR.
Les trois jetons sont distincts : partager l'affichage public n'expose jamais la gestion, et un QR de
personne n'expose ni la gestion ni la liste complète.

### 2.3 Modèle de données (deux tables, éphémères)
- `fa_boards` : `id`, `manage_token`, `display_token`, `title` (optionnel), `allow_rescan_toggle`
  (bool, défaut faux), `mask_names` (bool, défaut faux), `last_activity_at`, `purge_after`,
  `created_at`.
- `fa_participants` : `id`, `board_id`, `name`, `qr_token` (unique), `status`
  (`pending` | `in_list` | `removed`), `joined_seq` (entier monotone par board, voir 2.5),
  `joined_at`, `manual_position` (nullable), `created_at`.
Migrations réversibles (`down()` supprime les tables). Aucune donnée d'un AUTRE module touchée.

### 2.4 Dédoublonnage + sémantique du rescan (machine d'états SANS ambiguïté)
Le JETON du QR est l'identité (pas l'IP). Transition pilotée serveur, idempotente :
- Option OFF (défaut) : 1er scan `pending → in_list`. Tout scan suivant = **sans effet** (jamais de
  doublon). C'est la garantie "pas deux fois dans la liste".
- Option ON (`allow_rescan_toggle`) : le scan BASCULE la présence. `pending/removed → in_list`
  (rejoint EN FIN de file), `in_list → removed`. Un 4e scan rebascule. Intuitif : "scanne pour
  entrer, rescanne pour sortir". Jamais deux entrées pour un même jeton, quel que soit le nombre de
  scans.
La page de scan confirme l'état ("Tu es dans la liste, position N" / "Tu es retiré de la liste").

### 2.5 Ordre d'arrivée sans collision, et cohabitation avec le réordonnancement manuel
- `joined_seq` : entier attribué par un `INCREMENT` atomique au niveau du board (transaction courte +
  verrou de ligne sur `fa_boards`, ou colonne compteur `next_seq`), PAS un horodatage. Trente scans en
  deux secondes reçoivent trente rangs distincts garantis, insensibles à la précision d'horloge.
- Tri d'affichage : `COALESCE(manual_position, 1e9) ASC`, puis `joined_seq ASC`. Un réordonnancement
  manuel fixe `manual_position`; les nouvelles arrivées continuent de s'ajouter en fin (après les
  positions manuelles) sans perturber l'ordre choisi par l'animateur.

### 2.6 Temps réel : `wire:poll` (Livewire) pour le v1 - PAS SSE, PAS Reverb
Arbitrage mesuré (divergence avec l'oracle DeepSeek, qui proposait SSE) :
- **SSE** garde une connexion longue par client. Sur l'hébergement cPanel partagé (PHP-FPM), trente
  connexions longues **épuisent les workers** et peuvent figer le site. Écarté pour le v1.
- **Reverb/WebSocket** : installé dans le dépôt mais branché à AUCUNE vue; exigerait de lancer le
  serveur Reverb et d'écrire l'abonnement front. Trop d'infra pour un outil gratuit à faible enjeu.
- **`wire:poll` (2 s)** : seul mécanisme live réellement en place dans le projet, zéro infra, patron
  prouvé. Une latence de 2 s est sans conséquence pour une file d'attente. **Retenu.** La page
  d'affichage et la page animateur interrogent un composant Livewire (ou un endpoint JSON) toutes les
  2 s. Voie d'amélioration documentée : passer la page d'affichage à SSE/Reverb si un usage à grande
  échelle le justifie un jour (mesuré, pas par anticipation).

### 2.7 Export PDF des QR
`Pdf::loadView('fileattente::pdf.feuille', [...])->setPaper('A4')` avec une grille (p. ex. 3 x 4 = 12
QR par feuille, paginée automatiquement), chaque QR en data URI (PNG du `QrCodeService`), le nom
dessous, et le lien court de scan sous le nom en petit (repli si la caméra manque). Nom de fichier =
slug du titre du board.

### 2.8 Vie privée (Loi 25) - les noms peuvent être ceux d'élèves mineurs, affichés en public
Conception respectueuse, par défaut minimale :
1. **Aucun compte, aucune collecte durable** : les données vivent dans le board, purgé
   automatiquement après inactivité (défaut 24 h; réglable, borne dure p. ex. 7 j). Un cron de purge
   léger (ou une purge à la volée à l'ouverture) supprime les boards expirés.
2. **Minimisation** : on ne stocke que le nom saisi par l'animateur (aucun courriel, aucun
   identifiant d'appareil, aucune IP conservée au-delà de l'anti-abus immédiat).
3. **Masquage public optionnel** (`mask_names`) : l'affichage public montre "Prénom + initiale" ou
   un numéro, au choix; l'animateur voit les noms complets sur sa page gérée. Avis clair à
   l'animateur : c'est LUI le responsable des noms qu'il saisit (il peut utiliser des prénoms seuls).

### 2.9 Sécurité / anti-abus - léger, et JAMAIS par IP
- Dédoublonnage et verrou par **jeton**, pas par IP (une classe partage une IP; un plafond par IP
  bloquerait tout le monde - leçon #3103).
- Anti-rejeu par jeton : un même `qr_token` ne peut déclencher une transition qu'une fois toutes les
  ~2 s (anti double-tap), au niveau de la ligne.
- Jetons de board et de scan = aléatoires longs (non devinables), donc on ne peut pas énumérer les
  boards ni deviner le QR d'autrui. La possession de la feuille = l'identité (l'animateur distribue);
  acceptable pour un outil public à faible enjeu, renforcé par `mask_names`.
- CSRF sur toutes les mutations (création, réordonnancement, retrait, réglages).
- Plafond d'import (p. ex. 500 personnes par board) et taille de nom bornée.
- `responsecache` contourné sur les pages vivantes (scan, afficher, gérer).

## 3. Pages et parcours
1. `/outils/file-attente` (ou page d'accueil du module) : l'animateur saisit les noms (textarea, 1 par
   ligne, patron tirage-présentations), choisit les réglages (toggle rescan, masquage), crée le board.
2. Il obtient : le lien **gérer**, le lien **afficher** (à projeter), et le bouton **exporter le PDF**.
3. Il imprime le PDF, découpe/distribue les QR.
4. Chaque personne scanne son QR → page de confirmation (état + position).
5. L'animateur suit la file en direct sur **gérer**, réordonne (glisser-déposer) et retire au besoin.
6. **afficher** projette la file qui se met à jour seule (2 s).

## 4. Points soumis à la réfutation des oracles (round 2)
- 2.6 `wire:poll` vs SSE vs Reverb sur cPanel partagé : l'arbitrage tient-il? Un endpoint JSON polled
  (hors Livewire) serait-il plus léger que `wire:poll`?
- 2.4 la sémantique "toggle" du rescan activable est-elle la plus intuitive, ou faut-il "2e scan =
  retrait définitif jusqu'à réinscription par l'animateur"?
- 2.5 compteur monotone par board : verrou de ligne vs séquence; risque de contention à 30 scans/2 s?
- 2.8 durée de purge par défaut (24 h? fin de journée?) et masquage par défaut (ON plutôt qu'OFF,
  puisque mineurs possibles?).
- La SEULE amélioration non évidente qui rendrait l'outil nettement plus intuitif (DeepSeek proposait
  un son à chaque arrivée sur l'affichage public).

## 5. Ce qu'on réutilise vs ce qu'on crée (DRY)
RÉUTILISÉ : QrCodeService (Core), dompdf, patron Tools, patron liste (tirage/roue), `wire:poll`.
CRÉÉ : le module `FileAttente` (2 modèles, 2 migrations, 1 service de board, 3 pages, 1 vue PDF, 1
composant Livewire d'affichage, 1 entrée Tool + seeder idempotent). Aucune dépendance composer nouvelle.


---

## 6. Décisions FINALES (v0.2) - après la ronde de réfutation des oracles (2026-10-16)

Oracles consultés : DeepSeek (Hermes, génération); claude.ai (Opus 5.5) et ChatGPT (GPT-6.1 Sol) en réfutation. NON consultés, signalés : Gemini (session Google déconnectée au navigateur, exige ia-sync); Perplexity (défi Cloudflare). Critère d'arrêt atteint : les deux réfuteurs convergent sur les décisions clés; la seule divergence (mode borne vs appeler-le-suivant) est résolue en ADOPTANT les deux, complémentaires. Ces décisions SUPERSÈDENT les points correspondants de la section 2.

### 6.1 Le scan N'AGIT PAS (GET sans effet) - correction critique
Le QR encode une URL d'AFFICHAGE qui montre "Voici ta place / Rejoindre la file", JAMAIS une URL qui mute l'état. Entrée et sortie passent par un POST explicite (bouton), protégé CSRF. Motif : lecteurs de QR, aperçus de liens (Teams, iMessage) et préchargement navigateur déclenchent un GET avant l'humain, ce qui créerait des entrées/sorties fantômes.

### 6.2 Pas de bascule : rejoindre / quitter explicites et idempotents
La bascule au 2e scan est SUPPRIMÉE. La page de scan montre l'état courant; si la personne est déjà dans la file, un bouton "Quitter la file" (si l'animateur l'autorise) la retire. Rejoindre et quitter sont idempotents (rejouer rejoindre ne duplique pas; rejouer quitter ne fait rien). Après retrait, réinscription possible. Pas de piège "retrait définitif".

### 6.3 Ordre : clé de tri unique espacée + version optimiste
Une seule colonne "sort_key" (entier). Arrivée : sort_key = max + 1024. Déplacement manuel : milieu entre les deux voisins; renumérotation espacée quand l'écart tombe à 1. Plus de "position_manuelle" nullable (évite le bug NULL-first de MySQL où les nouveaux sautent devant tout le monde). Le réordonnancement porte la "version" attendue du board et est REJETÉ si périmé (une sauvegarde sur un état ancien n'écrase pas l'ordre récent). Un "joined_seq" (compteur atomique par board) reste comme rang d'arrivée immuable, distinct de sort_key.

### 6.4 Temps réel : endpoint JSON versionné, PAS wire:poll
Un numéro de version par board, tenu en cache (fichier/APCu), incrémenté à chaque mutation. GET "/f/{display_token}/etat?v=N" : si v == version courante -> 204 sans toucher MySQL; sinon -> JSON de la file. Interrogation par fetch (une requête à la fois, temporisation après erreur, PAUSE sur visibilitychange). Cadences : affichage 2 s, animateur 3 s, participant 15 s ou aucune. Livewire réservé aux ACTIONS de l'animateur (wire:poll re-rend tout et casse le glisser-déposer). Mitige le plafond d'entry-processes CloudLinux (~20/compte, risque de 508).

### 6.5 Vie privée (Loi 25) - protecteur par défaut
- Masquage ON par défaut : l'affichage public montre prénom + initiale, un pseudonyme ou un numéro. Le JSON public ne contient JAMAIS le nom complet (seulement côté animateur).
- Purge : plafond 24 h depuis la création (sans prolongation par consultation) + bouton "Terminer et supprimer" (purge immédiate). Cron de purge, PDF temporaires compris.
- Fuite de jeton : l'URL d'action n'a pas de jeton (POST); l'URL d'affichage porte un display_token distinct, haché en base (SHA-256). "Referrer-Policy: no-referrer", "noindex". Les journaux Apache et les sauvegardes JetBackup (~30 j) sont nommés dans l'avis comme rétention résiduelle hors contrôle de l'outil.
- Avis clair : l'organisation (l'école) est responsable des noms saisis; MEMORA est sous-traitant. Afficher des noms en public exige une justification; l'outil propose par défaut le pseudonyme/numéro pour ne pas exposer des mineurs.

### 6.6 Sécurité (léger, JAMAIS par IP)
Trois jetons/rôles distincts (gérer / afficher / participer), l'affichage public sans pouvoir d'écriture. Jetons longs non devinables, hachés en base. Dédoublonnage atomique : UPDATE ... WHERE token_hash=? AND status='pending'. CSRF sur tout POST. Échappement XSS des noms. Quotas PAR BOARD (max 60 personnes, longueur de nom bornée, nombre de boards créés par heure), jamais de throttle par IP (une classe partage une IP, leçon #3103). Aucun script tiers. Ajout manuel d'une personne par l'animateur.

### 6.7 Deux modes de scan (couvre TOUTE la portée du fondateur)
- Mode BORNE (défaut écoles/mineurs) : la page d'affichage utilise la caméra de l'appareil qui projette (BarcodeDetector natif, repli jsQR) pour scanner les cartes présentées. Aucun téléphone d'élève requis (cellulaire interdit au primaire/secondaire au Québec), un seul client sérialise les scans (zéro polling participant), et le QR peut porter un code court plutôt qu'une URL.
- Mode TÉLÉPHONE (adultes / salle d'attente) : chaque personne scanne avec son téléphone et voit la page rejoindre/quitter.
Le mode est un réglage du board.

### 6.8 Confort animateur : état "appelé"
En plus de dans-la-file / retiré, un état "appelé" : bouton "Appeler le suivant" qui marque la personne en tête et l'affiche en grand ("C'est ton tour"), sans la supprimer ni forcer un glisser-déposer. Annulation possible pendant 5 s plutôt qu'une confirmation.

### 6.9 Modèle de données révisé (supersède 2.3)
- fa_boards : manage_token_hash, display_token_hash, title, version (int), mask_mode (initial|pseudo|numero, défaut initial), scan_mode (borne|telephone), allow_self_leave (bool), last_activity_at, purge_after (<= création + 24 h), created_at.
- fa_participants : board_id, full_name (animateur seulement), display_label (public), token_hash (unique), status (pending|in_list|called|removed), joined_seq (immuable), sort_key (int, mutable), joined_at, created_at.
Aucune réponse publique ne porte full_name. Migrations réversibles.

---

## 7. Décisions prises À LA CONSTRUCTION (16 octobre 2026) - précisent/supersèdent la section 6

Construit en 5 lots. `./vendor/bin/pest Modules/FileAttente` = 36 tests verts, 610 assertions, 0 risky (gate repassé en direct). Rien n'est encore committé : le déploiement se fait module DÉSACTIVÉ d'abord (#3139), validation visuelle + passe oracles, PUIS activation.

### 7.1 AUCUNE dépendance ajoutée - précise le §6.4
Le §6.4 évoquait Livewire pour les ACTIONS de l'animateur. À la construction, ce choix a été ÉCARTÉ : **POST classiques (contrôleur, CSRF) + rafraîchissement JavaScript vanilla `fetch` contre l'endpoint JSON versionné + glisser-déposer HTML5 natif**. Motifs : (a) plus léger sur le plafond d'entry-processes CloudLinux (~20/compte) que `wire:poll`; (b) module totalement exportable, zéro couplage Livewire; (c) règle 18 "aucune dépendance sauf si nécessaire". Mode borne = API `BarcodeDetector` native UNIQUEMENT (message clair si absente), jamais jsQR ni script tiers (§6.6). Aucune dépendance composer nouvelle : QR via `Modules\Core\Services\QrCodeService`, PDF via `barryvdh/laravel-dompdf` déjà présent.

### 7.2 Gabarit autonome du module
Pages publiques sur `fileattente::layout` (pas `fronttheme_layout()`) : exportable, plein écran pour la projection, garantit `noindex` + aucun script tiers. Charte #064E5A / #9A2A06.

### 7.3 Carte /outils conditionnée à l'état du module
`/outils` lit la table `Tool`. La carte "File d'attente par code QR" est injectée à l'exécution par un view composer branché depuis le ServiceProvider du module : rien n'est seedé, et la carte DISPARAÎT quand le module est désactivé (provider non chargé). `/outils` est en `cacheResponse:600` : purger le responsecache après activation.

### 7.4 Quota de création PAR SESSION, jamais par IP (§6.6, leçon #3103)
`config('fileattente.boards_per_hour')` (défaut 20) compté par session de l'animateur anonyme (horodatages en session), jamais par IP (une classe partage une IP). Refus propre, jamais de 500.

### 7.5 Purge
Commande `fileattente:purge` planifiée `->hourly()->withoutOverlapping()` (enregistrée seulement si le module est actif) : tableaux dont `purge_after` est dépassé + participants + clés de cache + PDF temporaires. Idempotente. Données ÉPHÉMÈRES du propre outil.

### 7.6 i18n fr / en / fr_CA
Chaînes via `fileattente::ui.*`, parité de clés vérifiée par test. `fr_CA` = alias vers `fr`. Typographie québécoise (insécable U+00A0 avant `:`, guillemets droits).

### 7.7 Réimpression PDF + lien d'affichage récupérable
`token_cipher` (participants) et `display_token_cipher` (board) : jetons chiffrés au repos (`Crypt`, réversibles avec la clé d'app) EN PLUS des `*_token_hash` (index aveugle). Permet de régénérer le PDF et de réafficher le lien de projection depuis la page de gestion, sans stocker aucun jeton en clair. Dégradation propre si cipher absent (tableau d'avant la migration) : message "recrée le tableau", pas de 500. Limite : clé d'app perdue = régénérations impossibles.

### 7.8 Masquage des noms par défaut (clarification produit, 16 octobre)
Les élèves (mineurs inclus) sont dans la liste; le masquage ne concerne QUE l'écran de PROJECTION public. Défaut `mask_mode = initial` (prénom + initiale), modifiable (nom complet / initiale / pseudonyme / numéro). La page de scan indique à chaque personne SON libellé pour qu'elle se reconnaisse. La page d'animateur (jeton de gestion) montre les noms complets. Défaut à reconfirmer avec le fondateur (prénom+initiale vs prénom complet).
