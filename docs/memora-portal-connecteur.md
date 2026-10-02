<!--
  Doc de référence du connecteur memora-portal pour laveille.ai.
  Le résumé opérationnel vit dans CLAUDE.md (section « Publier depuis le portail client Memora ») ;
  CE fichier porte le détail complet de l'API (codes d'erreur, courriels de revue, corbeille 30 j).
  Collé verbatim le 2026-10-02 depuis le prompt du fondateur (versions 1.49.0 / 1.50.0 / 1.57.1
  déployées et vérifiées en production le 2026-09-28).
-->

# ⚠️ CORRECTION EN TÊTE — laveille.ai NE suit PAS « la contrainte qui décide de tout »

**laveille.ai (compagnie 33) est EXEMPTÉE de l'approbation client** : `social_api_bypass_client_approval = true`.
Ses publications programmées **partent SEULES à l'heure prévue, sans approbation** (mesuré le
2026-09-25, mémoire `laveille-portail-bypass-approbation-2026-09-25`). La section « La contrainte
qui décide de tout » ci-dessous (« ne partira jamais toute seule ») décrit les pages **CLIENTES**,
PAS laveille.ai — le document le confirme lui-même plus bas (section 1.54.0 : « laveille.ai reste
exemptée de l'approbation pour ce que tu crées toi-même »). **Seul garde-fou pour laveille.ai :
montrer le texte exact à Stéphane AVANT la date.**

---

# Prompt à coller dans le Claude du projet laveille.ai

À copier tel quel dans le `CLAUDE.md` de laveille.ai, ou à coller en début de session.
La base (création de publication) a été MESURÉE en production le 2026-09-15 sur la version
1.28.0 du portail. Ce ne sont pas des suppositions, et deux affirmations d'une version encore
antérieure de ce document se sont révélées fausses à la vérification : elles ont été corrigées.

**Versions 1.49.0 (MODIFIER, REPLANIFIER, ARCHIVER, RESTAURER) et 1.50.0 (PUBLIER MAINTENANT,
MÉDIAS, DUPLIQUER, RÉSULTATS, CRÉNEAUX SUGGÉRÉS, CORBEILLE DE 30 JOURS) : DÉPLOYÉES ET VÉRIFIÉES
en production le 2026-09-28** (essais à blanc réels sur une publication de laveille.ai, sans
aucune modification). Le jeton du connecteur porte `admin:read`, `social:write` et
`social:manage`. Redémarre ta session pour voir les nouveaux outils du connecteur.

---

## Publier sur les réseaux sociaux depuis le portail client Memora

Tu peux créer des publications sociales programmées dans le portail client Memora
(portail.memora.solutions) sans quitter ce projet, via le serveur MCP `memora-portal`.

### Avant la première utilisation

Si les outils `mcp__memora-portal__*` n'apparaissent pas, le serveur n'est pas branché sur ce
projet. Il vit dans `~/.claude/mcp-servers/memora-portal/server.py` et se déclare dans
`~/.claude.json`, sous la clé racine `mcpServers` (JAMAIS dans `~/.claude/settings.json`, le CLI
l'ignore à cet endroit). Un changement de configuration n'est actif qu'après redémarrage.

### La contrainte qui décide de tout

**Toute publication créée par ce canal exige une approbation du client dans le portail.** C'est
imposé côté serveur, ce n'est pas un réglage, et aucun paramètre ne permet de le contourner.
Combiné au contrôle d'approbation du portail, cela signifie qu'une publication créée ainsi **ne
partira jamais toute seule**, même avec une date : elle attendra qu'un humain l'approuve.

Ce n'est pas un défaut, c'est un garde-fou. Mais dis-le à Stéphane au lieu de le laisser
découvrir que rien n'est parti : « elle est créée et programmée, mais elle attend ton approbation
dans le portail, sinon elle ne partira pas ».

Si un jour il faut une publication qui parte seule, ce canal ne convient pas : il faut passer par
l'écran d'administration.

### Ce que ton jeton peut, et surtout ce qu'il ne peut pas

**VÉRIFIE-LE TOI-MÊME AVANT DE PROMETTRE QUOI QUE CE SOIT** : appelle `whoami`. Il renvoie les
capacités réelles du jeton sans jamais exposer le secret. Ne déduis JAMAIS une capacité du fait
qu'une autre fonctionne - c'est l'erreur commise le 2026-09-15 dans une version précédente de ce
document, qui affirmait `social:write` sans l'avoir testé.

Au 2026-09-15, `whoami` répond : `"abilities": ["admin:read"]`. UNE SEULE capacité.

| Tu peux | Tu ne peux PAS |
|---|---|
| lire compagnies, clients, comptes sociaux, factures, publications | **créer une publication** (403, il faut `social:write`) |
| | modifier une compagnie ou un client (403, il faut `admin:write`) |
| | modifier ou supprimer un compte social |
| | supprimer quoi que ce soit |

**La vérification de capacité a lieu AVANT la simulation** : un `dry_run` renvoie le même 403, il
ne contourne rien. Si tu obtiens « required_ability: admin:write|social:write », le jeton n'a pas
le droit d'écrire, et aucun réglage de ton côté n'y changera quoi que ce soit.

**Capacité requise par opération sur une publication** (lu dans `Modules/Api/routes/api.php`,
2026-09-28) :

| Opération | Capacité exigée (l'une OU l'autre suffit) |
|---|---|
| Lire (`list`/`get`, résultats, créneaux suggérés) | `admin:read` OU `social:manage` |
| Créer | `admin:write` OU `social:write` |
| Modifier / replanifier / archiver / restaurer | `admin:write` OU `social:manage` |
| Publier maintenant, ajouter/retirer/réordonner des médias, dupliquer (1.50.0) | `admin:write` OU `social:manage` |

Note l'asymétrie : `social:manage` donne la lecture ET toutes les opérations de correction, mais
PAS la création (`store` n'accepte que `admin:write`/`social:write`) - un jeton mandaté pour
« corriger » n'a donc pas forcément le droit de créer, et vice-versa. Seule exception assumée :
`duplicate` (1.50.0) crée une COPIE en brouillon avec `social:manage`, comme les autres
opérations de correction.

Le jeton vit dans `~/.claude/mcp-servers/memora-portal/.env`, variable `MEMORA_API_TOKEN`.
**Ce chemin est GLOBAL, pas propre à ce projet** : le même serveur et le même jeton servent à
toutes les sessions Claude de cette machine. Le modifier affecte les autres.
Ne lis jamais ce fichier et n'y touche jamais : c'est un secret, c'est à Stéphane de le coller,
et sa place de référence est 1Password (coffre AI-Claude).

**CORRECTION (2026-09-28) : il existe maintenant UNE route `DELETE`, mais ce n'est PAS une
suppression.** L'affirmation précédente (« aucune route de suppression, zéro `DELETE` ») était
vraie le 2026-09-15 et ne l'est plus : `DELETE /admin/social-publications/{id}` existe désormais,
mais c'est l'ARCHIVAGE (corbeille, `SoftDeletes`), pas un effacement. **Il n'existe toujours
AUCUNE route qui efface définitivement une publication** (aucun `forceDelete` dans le module Api
ni dans le service de l'API, gardé par un test d'architecture dédié) - « supprimer » une
publication par ce canal veut dire : elle disparaît des lectures normales, reste dans la corbeille
(`list_social_publications(archived=1)`), et `restore_social_publication` la ramène **pendant 30
jours**. **À partir de la 1.50.0, la corbeille n'est plus éternelle** : une tâche planifiée du
portail supprime définitivement une archive au-delà de 30 jours (voir « La corbeille de 30
jours » plus bas). Deux routes `DELETE` de plus existent en 1.50.0 : le retrait d'UN média d'une
publication (`DELETE .../{id}/media/{mediaId}`), définitif pour ce média. Aucune route
de ce genre n'existe pour les comptes sociaux, les compagnies ou les clients : tu ne peux
toujours pas casser un rattachement de page Facebook. Toutes les pages Facebook clientes partent
du compte personnel de Stéphane : en perdre une coûterait une reconnexion manuelle, client par
client.

### DEUX PIÈGES DE LECTURE, mesurés, qui t'induiront en erreur si tu les ignores

**1. Un enregistrement supprimé en douceur est INVISIBLE, et il existe quand même.**
Les modèles du portail utilisent les suppressions douces (`SoftDeletes`). Un compte social, une
publication ou un média « déconnecté » depuis l'interface garde sa ligne en base mais disparaît
de toutes les lectures. Conséquence directe et vécue : le 2026-09-13, l'API annonçait un seul
compte LinkedIn alors qu'il y en avait deux ; celui de laveille.ai était supprimé en douceur
depuis le 20 mai. Sur la foi de cette lecture, une publication cliente a été retirée d'un lot.

**Ne conclus JAMAIS « ce compte n'existe pas » ou « il n'y a aucune publication » sur la seule
réponse de l'API.** Dis « l'API ne m'en montre aucun », ce qui est différent, et demande à
Stéphane de vérifier dans le portail si ça compte.

**2. `list_social_publications` peut renvoyer une liste vide alors que la base en contient.**
Même cause que ci-dessus. Si un compte importe, dis que tu ne peux pas le garantir.

### Les outils

**Lecture** : `list_companies`, `get_company`, `list_clients`, `get_client`,
`list_social_accounts`, `list_social_publications`, `get_social_publication`, `list_invoices`,
`portal_health`, `whoami`.

**Écriture** : `create_social_publication` (création) ; en 1.49.0 `update_social_publication`
(modifier), `reschedule_social_publication` (replanifier), `archive_social_publication` (archiver,
= corbeille de 30 jours), `restore_social_publication` (restaurer) ; et - **1.50.0, pas encore
déployée**, voir l'avertissement en tête de document - `publish_now_social_publication`,
`add_social_publication_media`, `remove_social_publication_media`,
`reorder_social_publication_media`, `duplicate_social_publication`. Détail complet dans les
sections « Modifier, replanifier, archiver, restaurer » et « Nouveautés de la 1.50.0 » plus bas.

**Lecture ajoutée en 1.50.0** : `get_social_publication_results` (résultat
par cible) et `suggest_publication_slots` (créneaux libres).

`get_company` n'expose PAS les champs éditoriaux (`seo_keywords`, `social_keywords`,
`social_tone`). Si tu en as besoin pour rédiger, demande-les à Stéphane ou consulte le site de
l'entreprise avec `pp_search`. Ne les invente pas.

### laveille.ai dans le portail

| | |
|---|---|
| Compagnie | **33**, nommée `laveille.ai` (plus « La veille de Stef ») |
| Site | **https://laveille.ai** (laveilledestef.com redirige en 301) |
| Compte Facebook | **28** |
| Compte LinkedIn | **54** (profil personnel de Stéphane, jeton valide au 2026-11-14) |

Le profil LinkedIn personnel de Stéphane ne représente plus QUE laveille.ai. Il existait aussi
sur MEMORA ; ce second rattachement a été désactivé le 2026-09-15 pour éviter de poster deux
fois sur le même fil.

### Comment créer une publication, dans l'ordre

1. **Trouve la compagnie** avec `list_companies`, note son identifiant.
2. **Trouve ses comptes sociaux ACTIFS** avec `list_social_accounts` (`status: "active"`). Un
   compte connecté un jour n'est pas forcément utilisable aujourd'hui : vérifie, ne suppose pas.
3. **Écris le contenu** en respectant les règles de réseau ci-dessous.
4. **Essai à blanc d'abord** : `dry_run` vaut `true` par défaut, garde-le. Lis ce que l'outil
   annonce, vérifie que la compagnie et les comptes sont les bons.
5. **Puis écris pour de vrai** avec `dry_run: false`.
6. **Relis le résultat** avec `get_social_publication` et dis à Stéphane ce qui existe vraiment.

### Modifier, replanifier, archiver, restaurer une publication existante

Quatre opérations lues dans le code (`Modules/SocialPublication/app/Services/GestionPublicationParApi.php`)
pour corriger toi-même une publication déjà créée, sans devoir en recréer une nouvelle. **Rappel :
déployées en production le 2026-09-28**.

#### Mécanique commune aux quatre opérations

- **Verrou optimiste (`version`)** : relis toujours la publication avant (`get_social_publication`
  ou `list_social_publications`, champ `version`) pour connaître sa valeur courante, et passe-la
  au paramètre `version` de l'outil MCP. Sur l'API brute, ce verrou est l'en-tête HTTP `If-Match` -
  le serveur MCP fait cette traduction pour toi, tu n'as jamais à composer l'en-tête toi-même.
  Absent côté API → **428 VERSION_REQUIRED**. Périmé (quelqu'un d'autre a modifié le
  CONTENU entre-temps : titre, texte, textes par réseau, premier commentaire, lien ou cibles) →
  **412 VERSION_CONFLICT** : relis et recommence, ne réessaie jamais avec la même valeur. La
  `version` ne suit que le contenu : une replanification, un archivage ou une restauration ne la
  font pas avancer, donc deux replanifications successives avec la même version réussissent
  toutes les deux et la dernière date l'emporte.
- **`Idempotency-Key`** obligatoire (8 à 100 caractères) côté API. Absente → **422
  IDEMPOTENCY_KEY_REQUIRED**. Rejouée avec la MÊME clé et la MÊME charge → la même réponse est
  renvoyée sans réécrire (mémoire de 24 h côté serveur). Rejouée avec la MÊME clé et une charge
  DIFFÉRENTE → **422 IDEMPOTENCY_KEY_REUSED**. Jamais consommée en `dry_run`.
- **Une clé = une opération (1.50.0).** Sans `idempotency_key` fournie, chaque APPEL d'outil MCP
  reçoit une clé neuve (`uuid4`) : une nouvelle clé signifie une nouvelle opération, même avec la
  même version et le même motif. Ne fabrique JAMAIS une clé à partir du contenu : dans les 24 h,
  « archiver, restaurer, archiver » avec la même clé rejouerait le premier archivage sans archiver.
- **Réessai sans réponse** : si le serveur ne répond pas (délai dépassé, connexion coupée), l'outil
  réessaie déjà lui-même deux fois, avec la MÊME clé et le même corps. S'il échoue encore sans
  réponse, **réessaie en repassant l'`idempotency_key` reçue dans le résultat** : c'est le seul
  moyen de ne rien faire en double (l'opération a peut-être déjà eu lieu). Toute réponse du
  serveur, même une erreur, n'est jamais réessayée automatiquement.
- **Marqueur de rejeu** : une réponse rejouée (clé déjà traitée) porte `"rejeu": true` dans le
  corps JSON et l'en-tête `Idempotent-Replayed: true` ; l'outil MCP le remonte (`rejeu: true`).
  Elle ne dit PAS que l'opération vient d'avoir lieu : elle renvoie ce qui avait été répondu la
  première fois. Une première exécution ne porte jamais ce marqueur.
- **`reason`** obligatoire (3 à 500 caractères) sur les quatre opérations (et sur les cinq écritures de la 1.50.0) : jamais publié, motive
  seulement la trace d'audit. Écris toujours pourquoi (« coquille signalée par le client », « report
  d'une semaine demandé »...).
- **`dry_run` vaut `True` par défaut sur les DIX outils d'écriture de publication**
  (`create_`, `update_`, `reschedule_`, `archive_`, `restore_social_publication`, et en 1.50.0
  `publish_now_`, `duplicate_social_publication`, `add_`, `remove_`, `reorder_social_publication_media`) :
  sans rien préciser, l'outil simule et n'écrit rien. Lis la réponse (elle porte `dry_run: true`), puis
  rappelle l'outil avec `dry_run=False` pour écrire réellement.
- Un appel qui échoue pour une raison métier (409/412/422/428/429) n'est jamais mémorisé comme
  rejoué : corrige et rejoue avec la même `Idempotency-Key`.
- Un jeton borné à une ou plusieurs entreprises (capacité `company:<id>`) ne voit ni ne peut agir
  sur la publication d'une AUTRE entreprise : la réponse est **404 NOT_FOUND**, jamais 403 (pour ne
  jamais confirmer que l'identifiant existe ailleurs) - même règle que pour la lecture.

#### Codes d'erreur (`{"error": "<CODE>", "message": "..."}`)

| Code | Statut | Opération(s) | Cause |
|---|---|---|---|
| NOT_FOUND | 404 | toutes, et la lecture (résultats compris) | publication inexistante, ou hors de la portée du jeton |
| VERSION_REQUIRED | 428 | toutes les écritures | en-tête `If-Match` absent ou vide |
| VERSION_CONFLICT | 412 | toutes les écritures | `If-Match` ne correspond plus à `version` |
| IDEMPOTENCY_KEY_REQUIRED | 422 | toutes les écritures | `Idempotency-Key` absente ou hors de 8-100 caractères |
| IDEMPOTENCY_KEY_REUSED | 422 | toutes les écritures | même clé, charge différente |
| MEDIA_NOT_EDITABLE | 422 | modifier | `media`/`media_urls` fournis (jamais éditables ici) : le message renvoie vers les routes de médias de la 1.50.0 |
| ACCOUNT_TEXT_NOT_EDITABLE | 422 | modifier | `social_account_content` fourni (utilise `variants`) |
| INVALID_NETWORK | 422 | modifier | une clé de `variants` n'est pas un réseau supporté |
| COMPANY_NOT_ELIGIBLE | 422 | modifier, replanifier, et en 1.50.0 publier maintenant, médias, dupliquer | compagnie inactive, ou consentement Loi 25 absent |
| INVALID_TARGET | 422 | modifier, replanifier, dupliquer (1.50.0) | un compte visé existe et appartient à la compagnie, mais il est INACTIF (dupliquer : une cible n'appartient plus à l'entreprise) |
| CONTENT_TOO_LONG | 422 (+`network`) | modifier, replanifier | le texte résolu dépasse la limite du réseau visé |
| FORBIDDEN_TERM | 422 | modifier, replanifier | terme bloqué (SocialCompliance) dans le texte résolu ou le premier commentaire |
| FIELD_NOT_EDITABLE | 422 | modifier | `scheduled_at`, `status`, `link`, `company_id` ou `campaign_id` présent dans le corps, même avec sa valeur actuelle : n'envoie QUE les champs que tu modifies, jamais la représentation complète lue par GET |
| IDEMPOTENCY_IN_PROGRESS | 409 | toutes les écritures | une requête avec la même `Idempotency-Key` est encore en cours : réessaie dans quelques secondes avec la MÊME clé, tu recevras sa réponse |
| INVALID_SCHEDULE | 422 | replanifier | `scheduled_at` sans décalage horaire explicite, ou hors de 15 minutes-365 jours |
| RESCHEDULE_LIMIT | 429 | replanifier | 10 replanifications déjà faites en 24 h glissantes |
| POST_ALREADY_PUBLISHED | 409 | modifier, replanifier, archiver, publier maintenant, médias | publication déjà publiée, ou une cible déjà publiée |
| POST_SENDING | 409 | modifier, replanifier, archiver, publier maintenant, médias | envoi en cours |
| POST_FROZEN | 409 | modifier, replanifier, archiver, médias | planifiée/approuvée à moins de 10 minutes de l'envoi |
| POST_NOT_ARCHIVED | 409 | restaurer | la publication n'est pas dans la corbeille |
| ARCHIVE_EXPIRED | 410 | restaurer (1.50.0) | archivée il y a plus de 30 jours (et après le début de la règle) : plus restaurable, la purge la supprimera |
| APPROVAL_REQUIRED | 409 | publier maintenant (1.50.0) | l'approbation du client est exigée et ne vaut pas pour la version COURANTE |
| POST_EXPIRED | 409 | publier maintenant (1.50.0) | date planifiée dépassée de plus de 14 jours : replanifier d'abord |
| POST_NOT_PUBLISHABLE | 409 | publier maintenant (1.50.0) | ni planifiée ni approuvée (brouillon, en attente d'approbation, échouée...), ou sans compte |
| MEDIA_LIMIT | 422 | ajouter des médias (1.50.0) | la publication compterait plus de 5 médias au total |
| MEDIA_NOT_FOUND | 404 | retirer un média (1.50.0) | média inexistant, ou qui n'appartient pas à CETTE publication |
| MEDIA_NOT_REMOVABLE | 409 | retirer un média (1.50.0) | visuel TÉLÉVERSÉ dans le portail (sans adresse d'origine) : « Ce visuel a été téléversé dans le portail : retirez-le depuis la fiche de la publication. » Rien n'est touché |
| INVALID_MEDIA_ORDER | 422 | réordonner (1.50.0) | `media_ids` ne contient pas exactement les médias de la publication, chacun une fois |
| COMPANY_OUT_OF_SCOPE | 422 | créer, créneaux suggérés (1.50.0) | jeton borné à une entreprise (`company:<id>`) qui vise une autre entreprise |

**Ne confonds pas deux formats de 422.** Ce tableau porte le format `{"error": "<CODE>",
"message": "..."}`. Seule exception : un jeton BORNÉ à une entreprise qui crée pour une autre
entreprise reçoit `{"error": "COMPANY_OUT_OF_SCOPE", ...}` (tableau ci-dessus). Hors ce cas, un
`company_id`, un `social_account_id` ou un `campaign_id` invalide,
inexistant OU appartenant à une AUTRE entreprise - à la création comme dans `social_account_ids`
fourni à la modification - échoue plus tôt, à la validation générique de la requête : `{"message":
"...", "errors": {"champ": ["..."]}}`, SANS champ `error`. Les deux sont des 422, mais un appelant
automatisé doit regarder la présence du champ `error` pour savoir lequel il a reçu. Dans les deux
formats, un compte ou une campagne d'une autre entreprise produit EXACTEMENT la même réponse qu'un
identifiant inexistant : impossible de deviner qu'il existe ailleurs.

#### MODIFIER - `update_social_publication`

- HTTP : `PATCH` (ou `PUT`, même contrat) `/api/v1/admin/social-publications/{id}`.
- Capacité exigée : `admin:write` OU `social:manage`.
- Corps (tout facultatif sauf `reason`) : `title`, `content`, `first_comment`, `variants` (texte
  par réseau, clé = slug de plateforme connu - `variants: null` retire TOUTES les variantes,
  `{"facebook": null}` retire celle d'un seul réseau), `social_account_ids` (remplace l'ENSEMBLE des
  cibles).
- **JAMAIS modifiables ici** : `company_id`, `link`, `campaign_id`, `status`, `scheduled_at` (endpoint
  dédié, voir REPLANIFIER) → **422 FIELD_NOT_EDITABLE** dès que le champ est présent,
  avec un message qui indique la bonne opération, les médias (`media`/`media_urls` → 422 MEDIA_NOT_EDITABLE), le texte propre à
  UN compte (`social_account_content` → 422 ACCOUNT_TEXT_NOT_EDITABLE, utilise `variants` à la
  place - qui vise le réseau, pas le compte).
- États autorisés : tout statut SAUF `published`/`publishing`, et seulement si AUCUNE cible n'est
  déjà publiée. Une publication `scheduled`/`approved` prévue dans moins de 10 minutes est aussi
  refusée (POST_FROZEN).
- **Effet sur l'approbation du client** : si le contenu, les variantes OU les cibles changent,
  l'approbation déjà donnée (`client_approved_at`) est TOUJOURS effacée - un ancien « oui » ne
  certifie jamais un nouveau texte, même pour une entreprise exemptée. En PRIME, si l'entreprise
  n'est PAS exemptée (`social_api_bypass_client_approval` faux), l'approbation redevient EXIGÉE, et
  si le statut était `scheduled`/`approved`/`pending_approval`, il repasse à `pending_approval`
  (le client en est avisé). Une entreprise exemptée qui n'exige toujours rien ne change jamais de
  statut, même si son approbation existante disparaît.
- Réponse 200 : `{id, version, status, requires_client_approval, approbation_effacee, variants, avertissements, diff}`.

Exemple - corriger une coquille (publication 512, version courante 3) :

```http
PATCH /api/v1/admin/social-publications/512
If-Match: 3
Idempotency-Key: 8f3a7d2c-correction-titre
Content-Type: application/json

{"content": "Texte corrigé, sans la coquille.", "reason": "Coquille signalée par le client."}
```

```json
{
  "id": 512,
  "version": 4,
  "status": "pending_approval",
  "requires_client_approval": true,
  "approbation_effacee": true,
  "variants": null,
  "avertissements": [],
  "diff": {
    "content": {"avant": "Texte avec une coquille.", "apres": "Texte corrigé, sans la coquille."},
    "client_approved_at": {"avant": "2026-09-20T14:00:00+00:00", "apres": null},
    "status": {"avant": "approved", "apres": "pending_approval"}
  }
}
```

#### REPLANIFIER - `reschedule_social_publication`

- HTTP : `POST /api/v1/admin/social-publications/{id}/reschedule`.
- Capacité exigée : `admin:write` OU `social:manage`.
- Corps : `scheduled_at` (obligatoire, ISO 8601 AVEC décalage horaire explicite, ex.
  `2026-10-12T09:00:00-04:00`), `reason` (obligatoire).
- Fenêtre : la nouvelle date doit être entre 15 MINUTES et 365 JOURS dans le futur (sinon 422
  INVALID_SCHEDULE). Plafond : 10 replanifications par publication sur une fenêtre glissante de
  24 heures (429 RESCHEDULE_LIMIT au-delà).
- États autorisés : `draft`, `failed`, `expired` (quitter l'un de ces trois EST l'acte de
  programmer l'envoi - contenu et cibles sont alors REVALIDÉS comme à la création), ainsi que
  `scheduled`/`approved`/`pending_approval` (seule la date bouge). Refusé si `published`/
  `publishing`, si une cible est déjà publiée, ou si la fenêtre de gel de 10 minutes est atteinte.
- **Effet sur l'approbation du client** : en quittant `draft`/`failed`/`expired`, si l'entreprise
  n'est pas exemptée OU si la publication exigeait déjà une approbation, le statut devient
  `pending_approval` (avis envoyé au client) plutôt que `scheduled` directement - un brouillon
  importé sans approbation ne saute jamais cette étape simplement parce qu'on le replanifie. Exception :
  une publication déjà approuvée pour sa version COURANTE (par exemple approuvée, puis échouée à
  l'envoi, sans modification depuis) passe directement en `scheduled`, sans nouvel avis. Une
  approbation donnée sur une version PRÉCÉDENTE ne compte pas : si le contenu a été modifié depuis
  (même par l'équipe MEMORA dans le portail), la publication repasse en `pending_approval`, son
  approbation est effacée et le client est avisé. Sans vote enregistré (approbation historique ou
  posée par l'équipe), l'approbation ne vaut que si la publication n'a jamais été modifiée
  (`version` 1).
- Réponse 200 : `{id, version, status, scheduled_at}`. Changer seulement la date ne change PAS la `version` (la date n'est pas un contenu approuvé par le client ; les approbations déjà données restent valides).

Exemple - reprogrammer une publication `failed` (id 780, version 1) au surlendemain 9h Québec :

```http
POST /api/v1/admin/social-publications/780/reschedule
If-Match: 1
Idempotency-Key: 2b9e-replan-780
Content-Type: application/json

{"scheduled_at": "2026-10-02T09:00:00-04:00", "reason": "Échec corrigé, on reprogramme."}
```

```json
{"id": 780, "version": 1, "status": "pending_approval", "scheduled_at": "2026-10-02T13:00:00+00:00"}
```

#### ARCHIVER - `archive_social_publication`

- HTTP : `DELETE /api/v1/admin/social-publications/{id}` (le verbe `DELETE` standard EST
  l'archivage - il n'existe pas de route `POST .../archive` séparée).
- Capacité exigée : `admin:write` OU `social:manage`.
- Corps : `reason` (obligatoire) - dans le JSON, ou dans l'en-tête `X-Reason` si le client HTTP
  n'envoie pas de corps avec `DELETE`.
- **Ce n'est PAS une suppression définitive immédiate** : suppression douce (`SoftDeletes`),
  restaurable via RESTAURER **pendant 30 jours** (1.50.0 : au-delà, la publication est supprimée
  définitivement par la purge quotidienne, voir « La corbeille de 30 jours »). `version` n'est PAS
  incrémentée par l'archivage (ce n'est pas un changement éditorial).
- États autorisés : les mêmes que MODIFIER (refusé si `published`/`publishing`, cible déjà
  publiée, ou fenêtre de gel de 10 minutes). Une publication `failed` s'archive normalement.
- Réponse 200 : `{id, version, archived: true, deleted_at}`.

Exemple - archiver un brouillon devenu inutile (id 900, version 1) :

```http
DELETE /api/v1/admin/social-publications/900
If-Match: 1
Idempotency-Key: a1c4-archive-900
Content-Type: application/json

{"reason": "Événement annulé par le client."}
```

```json
{"id": 900, "version": 1, "archived": true, "deleted_at": "2026-09-28T15:04:00+00:00"}
```

#### RESTAURER - `restore_social_publication`

- HTTP : `POST /api/v1/admin/social-publications/{id}/restore`.
- Capacité exigée : `admin:write` OU `social:manage`.
- Corps : `reason` (obligatoire). `dry_run=true` (outil MCP : `dry_run=True`) vérifie sans rien écrire.
- **Pour trouver l'id et la version d'une publication archivée** : `get_social_publication`
  répond 404 sur une publication archivée (comme si elle n'existait pas). Passe par
  `list_social_publications(archived=1)` (la corbeille), jamais par `get_social_publication`.
- Refusé si la publication n'est PAS dans la corbeille (409 POST_NOT_ARCHIVED).
- **1.50.0** : refusé avec **410 ARCHIVE_EXPIRED** si elle a été archivée il y a plus de 30 jours
  (et après la mise en service de la 1.50.0) - elle n'est plus restaurable. Lis
  `purge_prevue_le` dans `list_social_publications(archived=1)` AVANT : c'est la date limite.
- **Une restauration NE republie JAMAIS et ne reprogramme JAMAIS toute seule.** Si le statut au
  moment de l'archivage était `scheduled`, `approved`, `publishing` ou `pending_approval` (un
  envoi était en attente), la publication revient en `draft` - PAS dans son état d'avant
  archivage. Un statut `published`, `failed`, `draft` ou `expired` est restauré TEL QUEL. Il faut
  ensuite appeler REPLANIFIER pour reprogrammer un envoi.
- Réponse 200 : `{id, version, status, archived: false}`.

Exemple - restaurer par erreur une publication qui était `scheduled` au moment de son archivage
(id 900, version 1 dans la corbeille) :

```http
POST /api/v1/admin/social-publications/900/restore
If-Match: 1
Idempotency-Key: 77f0-restore-900
Content-Type: application/json

{"reason": "Archivage fait par erreur, événement toujours actif."}
```

```json
{"id": 900, "version": 1, "status": "draft", "archived": false}
```

Le statut revient à `draft`, pas à `scheduled` : il faut REPLANIFIER pour la reprogrammer.

### Nouveautés de la 1.50.0

Toutes ces écritures reprennent EXACTEMENT la mécanique commune ci-dessus : `version` (en-tête
`If-Match`, 428/412), `Idempotency-Key` (422, rejeu 24 h, 409 IDEMPOTENCY_IN_PROGRESS), `reason`
obligatoire, `dry_run` qui n'écrit rien, jeton borné `company:<id>` (404 NOT_FOUND hors portée).
Lu dans `Modules/SocialPublication/app/Services/GestionPublicationParApi.php`,
`ConsultationPublicationParApi.php` et `Modules/Api/routes/api.php`.

#### PUBLIER MAINTENANT - `publish_now_social_publication`

- HTTP : `POST /api/v1/admin/social-publications/{id}/publish-now`. Capacité : `admin:write` OU
  `social:manage`. Corps : `reason`.
- Met en file le MÊME job que le bouton « Publier » de l'administration, avec la MÊME règle :
  seule une publication `scheduled` ou `approved` avec au moins un compte part (sinon **409
  POST_NOT_PUBLISHABLE** - un brouillon se REPLANIFIE d'abord).
- **409 APPROVAL_REQUIRED** si l'approbation du client est exigée (entreprise NON exemptée, OU
  publication qui l'exige) et ne vaut pas pour la version COURANTE. L'API ne publie jamais au nom
  d'un client un texte qu'il n'a pas vu.
- **409 POST_EXPIRED** si la date planifiée est dépassée de plus de 14 jours (la tâche d'envoi la
  marquerait « périmée » au lieu de l'envoyer) : replanifie d'abord.
- 409 POST_ALREADY_PUBLISHED / POST_SENDING, 422 COMPANY_NOT_ELIGIBLE comme les autres écritures.
- Réponse réelle **202** `{id, version, status, mise_en_file: true}` : l'envoi est ASYNCHRONE, la
  réponse ne dit PAS que c'est parti. Vérifie ensuite avec `get_social_publication_results`.
  Réponse `dry_run` : 200 `{dry_run: true, id, version, status, serait_mise_en_file: true}`.

```http
POST /api/v1/admin/social-publications/512/publish-now
If-Match: 4
Idempotency-Key: 5d1e-publier-512
Content-Type: application/json

{"reason": "Le client a approuvé, il veut que ça parte maintenant."}
```

```json
{"id": 512, "version": 4, "status": "approved", "mise_en_file": true}
```

#### MÉDIAS APRÈS CRÉATION - `add_`, `remove_`, `reorder_social_publication_media`

- **Ajouter** : `POST /api/v1/admin/social-publications/{id}/media`, corps `{"media_urls": [...],
  "reason": "..."}`. Mêmes règles que `media_urls` à la création : adresses publiques DIRECTES
  (anti-SSRF, jamais de redirection suivie), 1 à 5 par appel (erreur de validation standard
  sinon), et **au plus 5 médias AU TOTAL sur la publication par cette voie (422 MEDIA_LIMIT)**.
  Le type et le poids RÉELS (JPEG/PNG/WEBP jusqu'à 25 Mo, PDF jusqu'à 20 Mo) sont vérifiés au
  téléchargement, APRÈS la réponse : relis la publication et regarde `download_error`. Les
  médias ajoutés se placent après les existants et sont communs à tous les réseaux.
- **Retirer** : `DELETE /api/v1/admin/social-publications/{id}/media/{mediaId}`, corps `{"reason":
  "..."}` (ou en-tête `X-Reason`). **Seuls les médias ajoutés PAR ADRESSE se retirent ici** : ils
  se rajoutent avec la même adresse, renvoyée dans `diff.medias.retire.url`. Un visuel TÉLÉVERSÉ
  dans le portail répond **409 MEDIA_NOT_REMOVABLE** sans rien toucher : c'est à Stéphane de le
  retirer depuis la fiche. Le média n'est retiré que de CETTE publication : un média d'une autre
  publication répond **404 MEDIA_NOT_FOUND**, comme un média inexistant. Le fichier n'est effacé
  du disque que si aucun autre média ne le partage. Réordonner reste permis pour TOUS les médias.
- **Réordonner** : `PUT /api/v1/admin/social-publications/{id}/media/order`, corps `{"media_ids":
  [id3, id1, id2], "reason": "..."}` - EXACTEMENT les médias de la publication, chacun une fois
  (**422 INVALID_MEDIA_ORDER** sinon, le message liste les identifiants attendus). Le même ordre
  qu'aujourd'hui ne change rien.
- **Effet commun** : chaque changement réel fait avancer `version` d'UN cran et suit la MÊME règle
  d'approbation que MODIFIER (approbation existante effacée ; entreprise non exemptée : exigée de
  nouveau et, si elle était `scheduled`/`approved`/`pending_approval`, retour à
  `pending_approval` avec avis au client). Refusé si publiée, en cours d'envoi, une cible déjà
  publiée, ou gelée à 10 minutes de l'envoi (409).
- Réponse 200 : `{id, version, status, requires_client_approval, approbation_effacee, medias,
  diff}`. Le PATCH, lui, refuse toujours `media`/`media_urls` (422 MEDIA_NOT_EDITABLE).

```http
PUT /api/v1/admin/social-publications/512/media/order
If-Match: 4
Idempotency-Key: 9a7c-ordre-512
Content-Type: application/json

{"media_ids": [88, 86, 87], "reason": "La photo de l'équipe en premier."}
```

```json
{"id": 512, "version": 5, "status": "pending_approval", "requires_client_approval": true,
 "approbation_effacee": true, "medias": [{"id": 88, "order": 0}, {"id": 86, "order": 1}, {"id": 87, "order": 2}],
 "diff": {"ordre_medias": {"avant": [86, 87, 88], "apres": [88, 86, 87]},
          "client_approved_at": {"avant": "2026-10-01T13:00:00+00:00", "apres": null},
          "status": {"avant": "approved", "apres": "pending_approval"}}}
```

(Les objets de `medias` portent aussi `type`, `url`, `downloaded_at` et `download_error`,
abrégés ici.)

#### DUPLIQUER - `duplicate_social_publication`

- HTTP : `POST /api/v1/admin/social-publications/{id}/duplicate`, corps `{"reason": "..."}`,
  `If-Match` = version de la SOURCE. La source n'est jamais modifiée.
- La copie part en **brouillon**, sans date, sans approbation ni votes, invisible au client, en
  version 1, avec les mêmes cibles, les mêmes textes (titre suffixé « (copie) », contenu, premier
  commentaire, textes par réseau, texte propre à chaque compte) et les mêmes médias (fichiers
  copiés ; un média déjà purgé du serveur n'est pas recopié). C'est la même copie que le bouton
  « Dupliquer » de l'administration.
- Réponse réelle **201** `{"data": {...}}` : la NOUVELLE publication, au format de
  `get_social_publication`. Programme-la ensuite avec REPLANIFIER. Réponse `dry_run` : 200
  `{dry_run: true, id, version, copie: {status, scheduled_at, requires_client_approval,
  social_account_ids, nombre_medias}}`.
- 422 COMPANY_NOT_ELIGIBLE, 422 INVALID_TARGET (une cible n'appartient plus à l'entreprise).

#### RÉSULTATS PAR CIBLE - `get_social_publication_results` (lecture seule)

- HTTP : `GET /api/v1/admin/social-publications/{id}/results`. Capacité : `admin:read` OU
  `social:manage`. N'interroge aucun réseau.
- Réponse (forme, noms de champs de l'API en code, sans accent) :

  ```json
  {id, status, version, published_at, verification_status, cibles: [{social_account_id, compte, reseau, statut, message_erreur, post_id, lien, publie_le, verifie_le, en_ligne, statistiques}]}
  ```

  `lien` n'est fourni que pour Facebook et LinkedIn (Google Business ne donne
  pas d'adresse publique : `null`). `statistiques` = les dernières valeurs relevées par le portail
  (`impressions`, `reach`, `engagement`, `clicks`, `likes`, `comments`, `shares`, `saves`,
  `releve_le`), ou `null` si rien n'a encore été relevé. **Depuis la 1.52.0, chaque métrique peut
  valoir `null` = « non disponible » (le réseau ne l'a pas fournie), jamais un zéro inventé ; un 0
  est un vrai zéro.** Champs ajoutés : `metriques_disponibles` (liste des métriques réellement
  chiffrées), `disponibilite_connue` (false pour un relevé antérieur au 2026-09-29) et
  `motif_indisponibilite` (en français). Un profil LinkedIn personnel ne fournit AUCUNE
  statistique (réservé aux partenaires de LinkedIn) ; sur Facebook, les vues et la portée peuvent
  manquer. N'annonce jamais un chiffre absent.
- 404 NOT_FOUND si inexistante, ARCHIVÉE ou hors portée.

#### CRÉNEAUX SUGGÉRÉS - `suggest_publication_slots` (lecture seule)

- HTTP : `GET /api/v1/admin/social-publications/suggested-slots?company_id=33&platform=facebook&count=5`.
  Capacité : `admin:read` OU `social:manage`. `company_id` obligatoire, `platform` facultatif,
  `count` de 1 à 20 (5 par défaut).
- Mêmes suggestions que l'écran d'administration, tirées des horaires de publication de
  l'entreprise : `{company_id, slots: [{datetime, datetime_iso, label, day, time}],
  has_schedules, calendrier_actif}`. **Utilise `datetime_iso`** (avec décalage horaire) pour
  REPLANIFIER ; `datetime` est l'heure du Québec SANS fuseau, gardée pour l'écran.
- Liste vide si aucun horaire, ou si le module de calendrier est désactivé
  (`calendrier_actif: false`). 422 COMPANY_OUT_OF_SCOPE pour un jeton borné à une autre entreprise.

### La corbeille de 30 jours (1.50.0, décision de Stéphane du 2026-09-28)

- Une publication archivée reste restaurable **30 jours, visuels compris** (ses fichiers ne sont
  plus effacés à 7 jours comme avant). Au-delà, une tâche quotidienne du
  portail (`social:purge-corbeille`, vers 3 h 40 heure du Québec) la **supprime définitivement**,
  avec ses cibles, ses votes, ses statistiques et ses médias (fichiers compris). Une trace
  d'audit (identifiant, entreprise, titre tronqué, dates, nombre de médias) est écrite avant.
- **Seules les archives faites À PARTIR de la mise en service de la 1.50.0** sont visées (instant
  du déploiement réel, enregistré une seule fois par le portail). Les archives plus anciennes
  (270 en production au 2026-09-28) sont conservées et restent restaurables : elles attendent
  une décision de Stéphane. Si cette date n'existe pas, la règle est fermée : rien n'est
  supprimé et toute restauration reste permise.
- `list_social_publications(archived=1)` (et `get_social_publication`) exposent
  **`purge_prevue_le`** (ISO 8601) : la date de suppression définitive prévue, ou `null` pour une
  publication vivante ou une archive antérieure à la règle. La ressource expose aussi
  `deleted_at`.
- Restaurer après cette date : **410 ARCHIVE_EXPIRED**. Si Stéphane veut garder une publication
  archivée, restaure-la AVANT `purge_prevue_le`.
- Archiver reste donc réversible, mais pas indéfiniment : dis-le à Stéphane quand tu archives
  (« elle est dans la corbeille, restaurable jusqu'au <date> »).

### Courriels de revue aux clients (version 1.57.1)

#### À quoi ça sert

Après avoir créé des publications planifiées pour un client, tu PLANIFIES un courriel « vos
publications sont prêtes » aux approbateurs actifs de son entreprise. Chacun reçoit un lien
personnel, à usage unique, vers EXACTEMENT ces publications dans son portail, pour les voir, les
modifier ou les refuser AVANT leur diffusion sur les réseaux. **Rien ne part sans cet appel
explicite** : créer une publication n'envoie jamais de courriel.

Le courriel part à l'heure prévue (`send_at`), **sans validation manuelle de
l'administrateur**. Stéphane le voit dans l'admin (Réseaux sociaux > Courriels de revue) et peut
l'annuler avant l'envoi. Dis-le-lui donc AVANT de planifier, et montre-lui le résultat du
`dry_run`.

Capacités du jeton (`Modules/Api/routes/api.php`) : lecture avec `admin:read` ou `social:manage`,
écriture avec `admin:write` ou `social:manage`, même portée `company:<id>` que les publications
(entreprise ou courriel hors portée : **404 NOT_FOUND**, jamais 403). Un jeton en lecture seule
ne peut pas planifier. Si le module SocialPublication est désactivé, les routes répondent 404.

#### Les six outils

| Outil | Type | Rôle |
|---|---|---|
| `schedule_review_email` | écriture | Planifier le courriel de revue |
| `list_review_emails` | lecture seule | Lister les courriels de revue |
| `get_review_email` | lecture seule | Le détail d'un courriel de revue |
| `cancel_review_email` | écriture | Annuler un courriel encore planifié |
| `archive_review_email` | écriture | Le mettre à la corbeille (30 jours) |
| `restore_review_email` | écriture | Le sortir de la corbeille |

**Les quatre outils d'écriture** ont tous `dry_run=True` PAR DÉFAUT (simulation : rien n'est écrit
ni envoyé) et un `idempotency_key` facultatif (voir plus bas). Pour agir pour de vrai : montre la
simulation à Stéphane, puis rappelle avec `dry_run=False`.

**`schedule_review_email`**

| Paramètre | Type | Obligatoire | Règle |
|---|---|---|---|
| `company_id` | entier | oui | Entreprise cliente |
| `publication_ids` | liste d'entiers | oui | 1 à 50 identifiants distincts, de CETTE entreprise |
| `send_at` | texte ISO 8601 | oui | AVEC décalage explicite (voir « Fuseau ») |
| `reason` | texte | oui | 3 à 500 caractères, tracé au journal |
| `email_subject` | texte | non | Une seule ligne, sans `{{lien_revue}}`. Défaut : gabarit du portail |
| `email_body` | HTML | non | Doit contenir `{{lien_revue}}` comme adresse d'un lien. Défaut : gabarit du portail |
| `dry_run` | booléen | non | Défaut `True` |
| `idempotency_key` | texte | non | Voir « Idempotence » |

- Le corps est nettoyé (assaini) côté serveur. Balises utiles : `{{prenom}}`, `{{compagnie}}`,
  `{{nb_publications}}`. Le lien s'écrit `<a href="{{lien_revue}}">Revoir mes publications</a>`
  et **nulle part ailleurs** (ni dans une image, ni en texte brut).
- Réponse en `dry_run` (200) : `dry_run: true`, `company_id`, `entreprise`, `publication_ids`,
  `nb_publications`, `send_at` (UTC), `send_at_quebec` (« 2026-10-05 09h00 »), `destinataires`
  (`nom`, `courriel`), `sujet` et `corps` rendus pour le premier destinataire, `apercu_pour`,
  `note`. **Relis `destinataires` et `send_at_quebec` avec Stéphane.**
- Réponse réelle (201) : `{"data": {...}}` avec `id` (l'identifiant du courriel de revue, à
  conserver), `status: "scheduled"`, `origine: "api"`, `send_at`, `send_at_quebec`,
  `nb_publications`, `nb_destinataires`, `publications`, `destinataires`.

**`list_review_emails`** (`company_id` entier, 0 = toutes ; `status` parmi `scheduled`, `sent`,
`cancelled`, `draft` ; `archived`, paramètre DISTINCT de `status` : -1 par défaut = actifs,
1 = corbeille seulement, 0 = actifs ; `status="archived"` n'existe pas et renvoie 422 ; `per_page`
1 à 100, défaut 15). Renvoie `data` (une ligne par courriel : `id`, `company_id`, `entreprise`,
`status`, `origine` admin ou api, `send_at`, `send_at_quebec`, `sent_at`, `annulee_le`,
`motif_annulation`, `email_subject`, `archivee`, `deleted_at`, `purge_prevue_le`,
`nb_publications`, `nb_destinataires`) et `meta` (pagination). Un jeton borné ne voit que ses
entreprises.

**`get_review_email(review_email_id)`** : la même fiche, plus `motif`, `email_body`, `publications`
(`id`, `title`, `status`, `scheduled_at`, `visible_to_client`, `refusee`, `archivee`) et
`destinataires` (`user_id`, `nom`, `courriel`, `notified_at` = courriel parti, `used_at` = lien
ouvert, `exclu_le`, `motif_exclusion`). Le jeton du lien n'est JAMAIS exposé. Inexistant ou hors
portée : 404 dans les deux cas. **Un courriel ARCHIVÉ répond aussi 404** à `get_review_email`, à
`cancel_review_email` et à un second `archive_review_email` : pour le relire, passe par
`list_review_emails(archived=1)`. Attention : `nb_publications` ne compte que les publications
NON archivées, alors que `publications` du détail les liste toutes (avec `archivee: true`) ; les
deux nombres peuvent donc différer après l'archivage d'une publication.

**`cancel_review_email(review_email_id, reason, dry_run, idempotency_key)`**,
**`archive_review_email(...)`**, **`restore_review_email(...)`** : `reason` obligatoire (3 à 500
caractères). Voir « Annuler, archiver, restaurer ».

#### Fuseau et heure d'envoi

- `send_at` **DOIT porter un décalage explicite** : `2026-10-05T09:00:00-04:00` = 09h00 Québec
  (13:00 UTC) en heure avancée ; `2026-11-10T09:00:00-05:00` = 09h00 Québec (14:00 UTC) en heure
  normale ; `...Z` accepté pour de l'UTC. Le décalage du Québec est `-04:00` jusqu'au 1er novembre
  2026 (heure avancée), puis `-05:00`.
- **Sans décalage** (`2026-10-05T09:00:00` ou `2026-10-05`) : **422 INVALID_SEND_AT**, le portail ne
  devine jamais un fuseau. Forme exigée : `AAAA-MM-JJTHH:MM:SS` suivi de `Z` ou de `+HH:MM` /
  `-HH:MM`.
- La réponse renvoie `send_at` en UTC ET `send_at_quebec` : relis-les et compare à ce que
  Stéphane a demandé.
- **Ne confonds pas avec les publications** : pour créer ou replanifier une PUBLICATION, la règle
  de date reste celle déjà documentée plus haut dans ce document (ne la déduis pas de cette
  section). Ici, on ne parle que de l'heure d'envoi du courriel.

#### Délais, fenêtres, limites

- **Plancher** : `send_at` au moins 1 minute dans le futur. **Plafond** : au plus 90 jours.
  Sinon 422 INVALID_SEND_AT.
- **Fenêtre de correction** : le courriel doit partir AU PLUS TARD 24 h (réglage
  `social_review_fenetre_heures`, 24 par défaut) avant la PREMIÈRE parution planifiée À VENIR des
  publications visées, **moins une marge de 15 minutes** (depuis 1.57.1) : l'envoi réel passe
  par une tâche qui tourne toutes les 5 minutes, et un courriel prévu pile à la limite serait
  annulé au moment de partir. Exemple : première parution le 12 octobre à 09h00 Québec, donc
  `send_at` au plus tard le 11 octobre à 08h45 Québec. Sinon **422 REVIEW_TOO_LATE**, dont le
  détail donne `date_limite_quebec` (l'heure limite, marge DÉJÀ incluse : on peut la renvoyer
  telle quelle), `date_limite`, `premiere_parution`, `fenetre_heures`, `marge_minutes`.
  Solution : avancer `send_at`, ou replanifier les publications. **Après tout changement de
  `send_at`, refais la simulation (`dry_run=True`) et montre-la à Stéphane avant l'envoi réel.**
- **Aucune parution à venir** : si aucune publication de la liste n'a de date future au moment
  de planifier, aucune fenêtre ne s'applique et le courriel est accepté. À l'envoi, seules les
  publications dont la date est PASSÉE sont retirées : un brouillon SANS date reste annoncé et le
  courriel part. Il n'est annulé que si plus rien ne reste à revoir. Vérifie donc les dates des
  publications AVANT de planifier.
- **Liste vidée avant l'envoi** : si toutes les publications du courriel ont été archivées,
  refusées ou envoyées entre-temps, le courriel est ANNULÉ à l'envoi (rien ne part) et
  l'équipe est alertée. Le lien d'un client ne montre jamais que les publications de SA liste.
- **Publications** : 1 à 50 par courriel. Elles doivent être de cette entreprise, non archivées,
  en statut `draft`, `pending_approval`, `scheduled` ou `approved`, jamais refusées, et
  **visibles du client** (`visible_to_client`). Le portail ne rend JAMAIS une publication visible
  en silence.
- **Destinataires** : les approbateurs actifs de l'entreprise ayant une adresse courriel. Aucun
  plafond de nombre : tous les approbateurs actifs de l'entreprise reçoivent le courriel.
  Aucun approbateur : **422 NO_RECIPIENT**, rien n'est planifié (désigner un approbateur dans le
  portail d'abord).
- **Vérifié à l'envoi**, pas seulement à la planification : les destinataires qui ne sont plus
  approbateurs actifs sont écartés ; les publications archivées, parties, refusées ou dont la
  parution est passée sont retirées du courriel ; s'il n'en reste aucune ou plus aucun
  destinataire valide, le courriel est ANNULÉ et l'équipe alertée ; si la première parution à
  venir tombe à moins de la fenêtre (24 h) de l'envoi, le courriel est annulé et l'équipe alertée
  (rien ne part). Le lien vit au plus 7 jours et jamais au-delà de la première parution à venir ;
  un plancher de 60 minutes de validité s'applique (en deçà, rien n'est envoyé).
- Limite de débit de l'API : la limite standard du portail (`throttle:api`), commune à toutes les routes de l'API. Un 429 veut dire : attendre et réessayer, jamais boucler.

#### Idempotence et anti-doublon

- L'API exige l'en-tête `Idempotency-Key` (8 à 100 caractères, sinon 422
  IDEMPOTENCY_KEY_REQUIRED). **L'outil MCP le gère pour toi** : sans `idempotency_key`, il génère
  une clé neuve à chaque appel, la renvoie dans `idempotency_key` et réessaie lui-même deux fois
  avec la même clé si le serveur ne répond pas.
- Si un outil échoue SANS réponse, réessaie en repassant la `idempotency_key` reçue : le portail
  ne refait rien (`rejeu: true`). Une même clé avec un corps différent : 422
  IDEMPOTENCY_KEY_REUSED. Une nouvelle clé = une nouvelle opération.
- **Anti-doublon** : une publication déjà annoncée par un courriel de revue PLANIFIÉ et pas encore
  envoyé (par liste ou par lot de l'admin) donne **409 PUBLICATION_ALREADY_IN_CAMPAIGN** avec
  `publication_ids` et `campaign_ids`. Lis d'abord ce courriel (`get_review_email`) : si son
  `origine` est `admin`, c'est Stéphane qui l'a planifié, et tu ne l'annules JAMAIS sans son
  accord explicite. S'il vient de l'API (le tien) et que Stéphane veut le remplacer : annule-le
  (`cancel_review_email`), puis replanifie. Deux planifications simultanées sur une même publication : la seconde reçoit ce 409
  (les publications sont verrouillées dans un ordre stable).

#### Codes d'erreur (`{"error": "<CODE>", "message": "..."}`)

Deux exceptions de forme : le 403 de capacité renvoie `{"message", "required_ability"}` SANS clé
`error`, et les 422 de validation de forme renvoient le format standard `{"message", "errors":
{champ: [...]}}`. Lis donc le statut HTTP d'abord, puis `error` s'il existe, sinon `errors`.

| Code | HTTP | Cause et quoi faire |
|---|---|---|
| INVALID_SEND_AT | 422 | Décalage manquant, illisible, dans moins d'1 minute ou au-delà de 90 jours : corriger `send_at` |
| REVIEW_TOO_LATE | 422 | Trop près de la première parution : lire `date_limite_quebec` et avancer `send_at` |
| PUBLICATION_NOT_IN_COMPANY | 422 | Publication inconnue ou d'une autre entreprise (même réponse, par sécurité) |
| PUBLICATION_ARCHIVED | 422 | Restaurer la publication d'abord (`restore_social_publication`) |
| PUBLICATION_NOT_REVIEWABLE | 422 | Déjà envoyée, refusée, en échec ou annulée : la retirer de la liste |
| PUBLICATION_NOT_VISIBLE | 422 | Invisible du client : Stéphane doit la rendre visible dans le portail, puis replanifier |
| PUBLICATION_ALREADY_IN_CAMPAIGN | 409 | Déjà dans un courriel planifié : voir « Anti-doublon » (jamais annuler un courriel d'origine `admin` sans l'accord de Stéphane) |
| NO_RECIPIENT | 422 | Aucun approbateur actif joignable : le désigner dans le portail |
| REVIEW_LINK_MISSING | 422 | `email_body` sans `<a href="{{lien_revue}}">` correctement placé |
| IDEMPOTENCY_KEY_REQUIRED / IDEMPOTENCY_KEY_REUSED | 422 | Voir « Idempotence » |
| IDEMPOTENCY_IN_PROGRESS | 409 | La même opération est encore en cours : patiente quelques secondes et réessaie avec la MÊME clé |
| CAMPAIGN_NOT_CANCELLABLE | 409 | Déjà envoyé ou annulé : rien à annuler (le statut actuel est dans la réponse) |
| CAMPAIGN_NOT_ARCHIVED | 409 | Restauration d'un courriel qui n'est pas archivé |
| ARCHIVE_EXPIRED | 410 | Plus de 30 jours en corbeille : il sera supprimé définitivement (`purge_prevue_le`) |
| NOT_FOUND | 404 | Entreprise ou courriel inexistant, hors de la portée du jeton, OU courriel archivé (voir `list_review_emails(archived=1)`) |
| (capacité) | 403 | Le jeton n'a pas le droit d'écrire : ne réessaie pas, préviens Stéphane |
| (débit) | 429 | Trop d'appels : attends, puis réessaie une seule fois, jamais en boucle |

Erreurs de forme (422 de validation) : `publication_ids` vide, de plus de 50 éléments ou avec
doublons ; `reason` absente ou hors 3 à 500 caractères ; sujet sur plusieurs lignes, de plus de
255 caractères ou contenant `{{lien_revue}}` ; `email_body` de plus de 20 000 caractères.

#### Annuler, archiver, restaurer

- **Annuler** (`cancel_review_email`) : seulement un courriel encore `scheduled`. Il ne partira
  pas. Rien n'est effacé (statut, date, motif conservés) et les publications ne sont pas touchées.
  Sur un courriel déjà envoyé ou annulé : 409 CAMPAIGN_NOT_CANCELLABLE. **C'est l'outil normal
  pour se raviser.**
- **Archiver** (`archive_review_email`) : mise à la corbeille, l'équivalent de « supprimer ».
  Encore planifié : il est ANNULÉ dans la même opération et ne partira jamais. Déjà envoyé : ses
  liens sont COUPÉS (un clic est refusé). `dry_run` renvoie `would_archive` et `would_cancel`.
- **Restaurer** (`restore_review_email`) : sort de la corbeille. Un courriel qui était planifié
  revient **ANNULÉ, jamais replanifié tout seul** : pour l'envoyer, planifie un NOUVEAU courriel
  avec `schedule_review_email`. Un courriel qui était déjà envoyé reste ENVOYÉ et ses liens
  redeviennent valides jusqu'à leur expiration. Retrouver l'identifiant :
  `list_review_emails(archived=1)`.
- **Corbeille de 30 jours** (même règle que les publications) : restaurable jusqu'à
  `purge_prevue_le` (30 jours après l'archivage), puis **supprimé définitivement** (le courriel,
  ses destinataires et sa liste de publications, JAMAIS les publications elles-mêmes) par la
  purge quotidienne. Après ce délai : 410 ARCHIVE_EXPIRED. Dis à Stéphane la date limite quand tu
  archives. Si `purge_prevue_le` vaut `null` (corbeille automatique pas encore en service pour
  cet élément), il n'y a PAS de suppression automatique ni de 410 : dis-le tel quel, n'invente
  jamais de date.
- Tout est tracé au journal d'activité (motif, clé d'idempotence, jeton).

#### Exemple de séquence complète

Cas : trois publications pour l'entreprise 42, parution le 12 octobre 2026 à 09h00 Québec
(13:00 UTC), courriel de revue 7 jours avant, soit le 5 octobre à 09h00 Québec (13:00 UTC). La
fenêtre de 24 h est respectée (7 jours > 24 h).

1. Crée les trois publications avec `create_social_publication`
   (`scheduled_at: "2026-10-12T09:00:00-04:00"`). Relève leurs `id`, par exemple 901, 902, 903.
   Elles sont **visibles du client** d'office : le portail rend visible toute publication créée
   par l'API qui porte une date (`scheduled_at`) ou qui demande l'approbation du client. Seul un
   brouillon sans date reste invisible ; `get_social_publication` affiche `visible_to_client`.
   Si `dry_run` répond quand même PUBLICATION_NOT_VISIBLE, Stéphane doit la rendre visible dans
   le portail.
2. Simulation :
   `schedule_review_email(company_id=42, publication_ids=[901,902,903],
   send_at="2026-10-05T09:00:00-04:00", reason="Revue client 7 jours avant la parution",
   dry_run=True)`. Montre à Stéphane `destinataires`, `sujet`, `corps` et `send_at_quebec`
   (« 2026-10-05 09h00 »). Erreur REVIEW_TOO_LATE : avancer `send_at`.
3. Après son accord : même appel avec `dry_run=False`. Conserve `data.id` (par exemple 17) et
   `idempotency_key` (à repasser si l'appel a échoué sans réponse).
4. Vérifier : `get_review_email(17)` doit montrer `status: "scheduled"`, les trois publications et
   les destinataires (`notified_at: null` avant l'envoi). Ou
   `list_review_emails(company_id=42, status="scheduled")`.
5. Se raviser : `cancel_review_email(17, reason="Textes à revoir", dry_run=False)`, puis
   `get_review_email(17)` doit montrer `status: "cancelled"`. Replanifier plus tard =
   nouvel appel `schedule_review_email` (une publication annulée du courriel est de nouveau
   libre).
6. Après l'envoi (`status: "sent"`), suivre `notified_at` (courriel parti) et `used_at` (lien
   ouvert) par destinataire avec `get_review_email`.
7. Si Stéphane demande de « supprimer » le courriel : `archive_review_email(17, reason=..., dry_run=False)`,
   puis annoncer la date `purge_prevue_le`.

### Les paramètres qui comptent

- `company_id`, `content` et `social_account_ids` sont obligatoires.
- `scheduled_at` : ISO 8601 **AVEC le décalage horaire**, par exemple `2026-10-12T09:00:00-04:00`
  pour 9h00 heure du Québec. **Jamais d'heure sans fuseau** : le portail stocke en UTC et l'écart
  de quatre ou cinq heures ferait partir la publication au mauvais moment. Le Québec est à moins
  quatre heures de mars à novembre, moins cinq le reste de l'année.
- `social_account_content` : liste de `{social_account_id, content}` pour un texte DIFFÉRENT par
  réseau. Tout identifiant de cette liste doit aussi figurer dans `social_account_ids`, sinon 422.
  Un doublon d'identifiant est rejeté (422). Les comptes non mentionnés retombent sur `content`.
- `first_comment` : **un seul pour toute la publication**. Il n'existe PAS de premier commentaire
  par réseau, ce n'est pas implémenté côté serveur. Il n'est réellement posté que sur Facebook et
  LinkedIn ; **Google Business n'a pas de commentaires**, donc pour une publication qui ne vise
  que ce réseau, mets les coordonnées dans le texte lui-même.
- `media_urls` : adresses publiques et **DIRECTES**. Une adresse qui redirige **échoue en
  silence** et la publication est créée sans image - les redirections ne sont jamais suivies, par
  protection. Les adresses privées, internes ou de bouclage sont refusées (anti-SSRF). Si une
  image semble manquante, vérifie `download_error` avec `get_social_publication`.
- Les comptes doivent appartenir à `company_id` et être actifs : un mélange entre clients est
  rejeté (422).

### Plusieurs images, et le vrai sujet du carrousel

Une version précédente de ce document laissait croire que le portail ne savait pas publier de
carrousel. **C'est faux.** Voici les limites réelles, lues dans le code :

| Réseau | Médias par publication | Format |
|---|---|---|
| Facebook | **10** | album multi-images |
| Instagram | **10** | album |
| LinkedIn | **1** | **carrousel = document PDF** (`publishWithDocument`) |
| Google Business | **1** | image unique |

LinkedIn ne publie pas plusieurs images séparées **parce que LinkedIn lui-même ne le fait pas** :
son carrousel EST un document PDF, et le portail l'implémente. Sur Facebook, envoie simplement
plusieurs `media_urls` et l'album se compose tout seul. En 1.50.0, une
image oubliée s'ajoute après coup avec `add_social_publication_media`, sans recréer la
publication.

Attention si tu fournis une image propre à un réseau : depuis le 2026-09-15, un média marqué pour
un réseau REMPLACE les médias communs pour ce réseau, il ne s'y ajoute pas.

### Les règles de rédaction

- **Facebook** : du récit, 60 à 110 mots, AUCUNE adresse web dans le corps. Les coordonnées vont
  en premier commentaire.
- **Google Business** : une centaine de mots, factuel, ancré localement, appel à l'action simple.
- **LinkedIn** : jamais d'adresse web dans le corps, elle va en premier commentaire.
- **Un texte différent par réseau.** Ces trois formats n'ont rien à voir. Envoyer le même texte
  partout est une mauvaise pratique connue. Utilise `social_account_content`.

> ⚠ **EXCEPTION laveille.ai : PAS de premier commentaire sur LinkedIn.** La règle « LinkedIn : lien
> en premier commentaire » vaut pour les pages CLIENTES. Sur laveille.ai (décision du 2026-09-11),
> le lien court ET le code QR vivent sur la DERNIÈRE diapositive du carrousel, aucun premier
> commentaire sur LinkedIn ; le texte doit DIRE où est le lien (« sur la dernière diapositive »).

### Ce qu'il ne faut JAMAIS faire, et c'est la partie la plus importante

- **Ne jamais rien affirmer sur la disponibilité d'une entreprise** : ni ouverte, ni fermée, ni
  ses horaires, ni qu'elle « revient mardi », ni « on s'occupera de ça après le congé ». Tu ne le
  sais pas. Cette faute a été commise le 2026-09-13 sur trente-cinq textes : tous annonçaient une
  fermeture un jour férié, alors qu'un lave-auto ou une clinique peut très bien être ouvert. Et
  elle s'est reproduite sous une forme déguisée - « laissez faire ça pour aujourd'hui, on
  s'occupera du reste après » dit la même chose sans le mot « fermé ». Publier cela sur la page
  d'un client lui coûte des clients.
- **Ne jamais inventer un fait** : ancienneté, nombre d'employés, prix, rabais, promotion,
  récompense, territoire desservi, certification professionnelle. Si ce n'est pas confirmé sur le
  site du client, ça ne s'écrit pas. En santé, un titre professionnel inventé est un problème
  déontologique, pas une maladresse.
- **Ne rien promettre au nom du client** : aucune disponibilité, aucun délai, aucune offre.
- **Ne jamais énumérer les mots-clés** d'une entreprise. Ils servent à trouver l'angle, pas à
  être recopiés.

### Sur les fêtes

Le Québec est laïque. Pour une fête d'origine religieuse ou importée, prends l'angle du CONGÉ et
du temps qu'on prend, jamais celui de la gratitude à l'américaine. L'emphase va sur les PERSONNES
qui profitent du congé, pas sur ce que fait l'entreprise ce jour-là.

### Ce qui a changé dans le portail, et qui te concerne

- **Une seule politique de planification (1.54.0)** : la même règle vaut désormais pour la
  replanification par l'API, l'écran d'administration, le calendrier et l'import. Ce qui change
  pour toi :
  - `reschedule_social_publication` répond **409 `POST_NOT_SCHEDULABLE`** pour une publication
    annulée, générée ou dans un statut qui ne se planifie pas (avant, la date bougeait en silence) ;
  - **422 `INVALID_TARGET`** (message habituel) si aucun compte social actif n'est ciblé, même pour
    un simple déplacement de date ;
  - une publication « approuvée » replanifiée revient avec `status: scheduled` ;
  - une publication planifiée ou approuvée qui exige l'accord du client sans l'avoir pour sa
    version actuelle repasse en `pending_approval` (le client est avisé) ; une publication déjà
    `pending_approval` reste en attente, seule sa date bouge ;
  - à la création, une date à plus de 365 jours donne une erreur de validation 422 sur
    `scheduled_at` ;
  - les codes existants gardent leur ordre ; les deux nouveaux refus viennent entre
    `POST_FROZEN` et `RESCHEDULE_LIMIT`.
  laveille.ai reste exemptée de l'approbation pour ce que tu crées toi-même.
- **Rapport mensuel (1.53.0)** : le portail produit un rapport mensuel par entreprise, envoyé
  seulement par un administrateur. Rien à faire de ton côté.
- **Corbeille de 30 jours, API complète (1.50.0)** : publier maintenant,
  médias après création, duplication, résultats par cible, créneaux suggérés ; une archive n'est
  plus restaurable au-delà de 30 jours (410 ARCHIVE_EXPIRED). Détail dans « Nouveautés de la
  1.50.0 ».

- **Fenêtre de péremption (1.27.0)** : une publication dont la date planifiée est dépassée de plus
  de **14 jours** ne part plus automatiquement. Si tu programmes loin dans le passé, elle sera
  marquée « périmée » au lieu d'être publiée. Le délai est réglable côté portail.
- **Surveillance horaire (1.27.0)** : le portail alerte les administrateurs quand une publication
  planifiée n'est pas partie, quand elle est périmée, et quand une file d'attente n'est consommée
  par personne. Si une de tes publications ne part pas, quelqu'un le saura.
- **Jetons (1.28.0)** : une publication qui échoue pour une raison d'authentification marque
  désormais le compte social et prévient les administrateurs. Un compte qui s'affiche « actif »
  n'est donc plus une garantie, mais un compte marqué en erreur l'est.

### Toujours

Français du Québec avec tous les accents. Jamais de tiret cadratin : un tiret simple. Une ou deux
émojis au maximum. Et montre le texte exact à Stéphane AVANT qu'il parte : c'est lui qui répond
de ce qui est publié au nom de ses clients.
