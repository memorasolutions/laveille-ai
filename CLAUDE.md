<!-- github-maison:start -->
## GitHub maison MEMORA - où pousser ce projet (laveille = EXCEPTION double remote)

Ce projet est versionné dans le **github maison** (Forgejo local sur le Pi) ET sur **GitHub** (qui porte la CI/CD de déploiement). NE PAS traiter laveille comme un projet forge-only.

- Organisation / client forge : `laveille`
- Dépôt forge : `la-veille-de-stef-v2`
- **`origin` = GitHub** (`https://github.com/memorasolutions/laveille-ai.git`) - **c'est lui qui DÉCLENCHE la CI GitHub Actions et le déploiement en prod**. Le déploiement PASSE par `git push origin master`.
- **`forge` = Forgejo Pi** (`http://100.66.177.50:3000/laveille/la-veille-de-stef-v2.git`) - miroir/backup local, aucune CI.

**Règle de push pour CE projet** : pousser vers les DEUX à chaque livraison : `git push origin master` (CI + déploiement) PUIS `git push forge master` (miroir). Ne jamais mettre Forgejo en `origin` ici (casserait la CI). Le MCP `github-maison` (gm_push --remote=forge) sert pour le miroir; `gm_whereami` en cas de doute.
<!-- github-maison:end -->

<!-- constructeur-prompts:frontiere-gabarits -->
## Constructeur de prompts - frontière des gabarits (anti-dérive, club des sages 2026-08-20)
Un « gabarit » / pré-prompt = un ÉTAT PRÉ-REMPLI du wizard existant (un `SavedPrompt`, qui porte déjà les `spaces`). JAMAIS un gabarit qui déclare ses PROPRES champs / son propre moteur de templating : ce serait la refonte « phrase-à-trous par métier » déjà essayée puis abandonnée le 2026-08-07 (règle projet : ne pas refondre le wizard sans demander). La version « faible » (état pré-rempli) glisse vers la version « forte » (champs propres) en quelques sprints si la frontière n'est pas tenue. Toute demande d'aller vers la version forte = décision EXPLICITE de Stéphane, jamais une dérive silencieuse. La galerie de gabarits reste CURÉE par l'équipe (flag `is_official`), ZÉRO UGC public (Loi 25, pas de modération). Design : docs/specs/2026-08-20-bibliotheque-pre-prompts-design.md.
<!-- /constructeur-prompts:frontiere-gabarits -->

## Publier depuis le portail client Memora (MCP `memora-portal`)

> Résumé opérationnel. **Détail complet de l'API (tous les codes d'erreur, les courriels de revue, la corbeille de 30 jours, les exemples) : `docs/memora-portal-connecteur.md`.** Versions 1.49.0 / 1.50.0 / 1.57.1 déployées et vérifiées en production le 2026-09-28.

### laveille.ai, et son EXCEPTION (à ne jamais oublier)

laveille.ai dans le portail : compagnie **33**, Facebook **28**, LinkedIn **54** (profil personnel de Stéphane, jeton valide au 2026-11-14; le rattachement à MEMORA a été désactivé le 2026-09-15 pour ne pas poster deux fois).

**laveille.ai (cie 33) est EXEMPTÉE de l'approbation client** (`social_api_bypass_client_approval = true`) : ses publications programmées **partent SEULES à l'heure prévue**, sans approbation. La section « La contrainte qui décide de tout » du doc de référence (« ne partira jamais toute seule ») vaut pour les pages **CLIENTES**, PAS pour laveille.ai. **Seul garde-fou laveille : montrer le texte exact à Stéphane AVANT la date.**

### Capacités : vérifier, jamais déduire

Le jeton porte `admin:read` + `social:write` + `social:manage`. Appelle **`whoami`** avant de promettre quoi que ce soit (la vérification de capacité a lieu AVANT la simulation : un `dry_run` renvoie le même 403 si le droit manque). Ne déduis jamais une capacité d'une autre. Détail des capacités par opération : doc de référence.

### Piège de lecture (mesuré)

Suppressions douces (`SoftDeletes`) : un compte ou une publication « déconnecté » garde sa ligne mais disparaît des lectures. **Ne conclus jamais « ça n'existe pas »** sur la seule réponse de l'API — dis « l'API ne m'en montre aucun ». `list_social_publications` peut renvoyer une liste vide alors que la base en contient.

### Les outils

- **Lire** : `list_companies`, `get_company`, `list_social_accounts`, `list_social_publications`, `get_social_publication`, `get_social_publication_results`, `suggest_publication_slots`, `whoami`, `portal_health`.
- **Publications (écriture)** : `create_social_publication`; `update_` / `reschedule_` / `archive_` / `restore_social_publication`; `publish_now_` / `duplicate_social_publication`; `add_` / `remove_` / `reorder_social_publication_media`.
- **Courriels de revue** : `schedule_` / `list_` / `get_` / `cancel_` / `archive_` / `restore_review_email`.
- **Tous les outils d'écriture : `dry_run=True` par défaut** (simule, n'écrit rien). Lis la simulation, montre-la à Stéphane, puis rappelle avec `dry_run=False`. Modifier / replanifier / archiver exige de relire la `version` d'abord (verrou optimiste). Contrats, codes d'erreur et exemples : doc de référence.

### Paramètres qui coûtent cher

- **`scheduled_at` AVEC le décalage horaire** : `2026-10-12T09:00:00-04:00`. Québec = -04:00 de mars à novembre, -05:00 le reste de l'année. Sans fuseau, la publication part 4 à 5 heures à côté.
- **`first_comment` est UNIQUE** pour toute la publication (jamais par réseau). Posté sur Facebook et LinkedIn seulement; Google Business n'a pas de commentaires.
  - **CONSÉQUENCE sur le NOMBRE de publications** : grouper Facebook + LinkedIn dans un seul enregistrement enverrait le premier commentaire AUSSI sur LinkedIn. Donc : aucun premier commentaire → un seul enregistrement peut viser les deux (texte par réseau via `social_account_content`); un premier commentaire nécessaire → **DEUX publications séparées**.
- **`media_urls` DIRECTES** (une redirection échoue en silence, publication créée sans image; vérifie `download_error`).
- **`social_account_content`** : texte différent par réseau; chaque identifiant doit aussi figurer dans `social_account_ids`.

### Médias : le portail SAIT publier un carrousel

| Réseau | Médias | Format |
|---|---|---|
| Facebook / Instagram | 10 | album multi-images |
| **LinkedIn** | **1** | **carrousel = document PDF** |
| Google Business | 1 | image unique |

Un média marqué pour un réseau REMPLACE les médias communs pour ce réseau. Le PDF LinkedIn est plafonné à 20 Mo côté portail. Vérifier après dépôt d'un document : `type` vaut `"document"`, `downloaded_at` non nul, `download_error` nul.

### ⚠ EXCEPTION laveille.ai : PAS de premier commentaire sur LinkedIn

Décision du 2026-09-11 : sur laveille.ai, le lien court **et** le code QR vivent sur la **dernière diapositive** du carrousel, **aucun premier commentaire** sur LinkedIn (les hyperliens d'un PDF sont inertes dans la visionneuse LinkedIn, le code QR est le seul élément actionnable). Le texte doit DIRE où est le lien (« sur la dernière diapositive »).

### Rédaction : jamais les mêmes mots sur trois réseaux

- **Facebook** : récit, 60 à 110 mots, aucune adresse web dans le corps.
- **LinkedIn** : aucune adresse web dans le corps.
- **Google Business** : une centaine de mots, factuel, ancré localement, appel à l'action simple.

### Ce qu'il ne faut JAMAIS faire

- **Ne jamais rien affirmer sur la disponibilité d'une entreprise** (ni ouverte, ni fermée, ni horaires, ni « on s'occupera de ça après le congé »). Faute commise le 2026-09-13 sur 35 textes annonçant une fermeture un jour férié.
- **Ne jamais inventer un fait** (ancienneté, effectif, prix, rabais, récompense, certification). En santé, un titre professionnel inventé est un problème déontologique.
- **Ne rien promettre au nom du client**, et **ne jamais énumérer ses mots-clés** : ils servent à trouver l'angle, pas à être recopiés.
- **Fêtes** : le Québec est laïque. Angle du CONGÉ et des PERSONNES qui en profitent, jamais la gratitude à l'américaine.

**Toujours** : français du Québec avec tous les accents, jamais de tiret cadratin, une ou deux émojis au maximum. Et **montrer le texte exact à Stéphane AVANT qu'il parte** — c'est lui qui répond de ce qui est publié au nom de ses clients.
