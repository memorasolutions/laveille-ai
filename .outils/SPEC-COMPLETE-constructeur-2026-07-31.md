# Spécification complète - Constructeur de prompts

Document généré à partir de la lecture directe du code source du projet, le 2026-07-31.
Chaque affirmation porte sa preuve sous la forme `fichier:ligne`. Les chemins sont relatifs
à la racine du projet `la-veille-de-stef-v2`. Quand une information n'a pas pu être
localisée dans le code, la mention « NON TROUVÉ » est utilisée explicitement.

---

## 1. Vue d'ensemble

Le « Constructeur de prompts » est un assistant en 2 étapes qui transforme une intention en
langage naturel (« je veux rédiger un texte », « je veux résumer un contenu »...) en un
prompt structuré (rôle, tâche, audience, format, contraintes, technique de raisonnement)
prêt à être copié-collé dans ChatGPT, Claude, Gemini, Mistral ou tout autre LLM. L'outil ne
génère jamais lui-même de texte par IA : il assemble un gabarit de texte à partir des choix
de l'utilisateur, entièrement côté navigateur (`get prompt()`,
`public/assets/tools/constructeur-prompts/constructeur-prompts-core.js:581-667`).

Public visé : toute personne utilisant un LLM grand public (rédaction, résumé, brainstorming,
analyse, apprentissage, traduction, planification, code), sans connaissance préalable du
« prompt engineering » - l'étape 1 est volontairement formulée par intention concrète plutôt
que par jargon technique (`Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php:943-966`,
commentaire de conception : « Phase 1 (audit 2026-07-26) : taxonomie de tâches concrètes »).

Modèle économique : coût serveur nul. Aucun appel à un modèle de langage n'est effectué par
le backend MEMORA. La fonctionnalité « Améliorer avec mon IA » (§6.5) construit un
méta-prompt côté client et le pousse vers l'IA déjà connectée de l'utilisateur (BYOA - Bring
Your Own AI). Commentaire explicite dans le code : « AUCUN appel réseau backend, zéro coût
serveur (posture BYOA stricte) » (`constructeur-prompts-core.js:119-122`).

Statut actuel (2026-07-31) : l'outil est **gaté en révision**. Vérifié en base de données :
`is_under_construction = true`, `construction_mode = 'revision'`, `is_active = true`, table
`tools`, ligne `slug = 'constructeur-prompts'`. Seul un superadministrateur
(`$user->isSuperAdmin()`) peut l'utiliser ; tout autre visiteur (invité ou connecté non-admin)
reçoit la page « fait peau neuve » (`Modules/Tools/resources/views/public/under-construction.blade.php:18-45`)
avec le message : « Vos prompts déjà sauvegardés sont intacts et vous seront accessibles dès
le retour de l'outil. » Le gate est appliqué par `Tool::isAccessibleTo()`
(`Modules/Tools/app/Models/Tool.php:144-166`) et étendu à toutes les routes satellites
(bibliothèque `/user/prompts`, API prompts, API tool-preferences) via le middleware
`EnsureToolNotUnderConstruction` (`Modules/Tools/app/Http/Middleware/EnsureToolNotUnderConstruction.php`).
Version applicative courante du site au moment de la rédaction : `1.136.0`
(`config/version.php`, clé `semver`).

---

## 2. Toutes les routes

### 2.1 Routes web

| Méthode | URI | Nom de route | Contrôleur:méthode | Middlewares | Rôle |
|---|---|---|---|---|---|
| GET | `/outils/constructeur-prompts` | `tools.show` (slug dynamique) | `PublicToolController::show` | `web`, `cacheResponse:600` | Affiche la page de l'outil (vue `tools::public.tools.constructeur-prompts`). Gate `is_under_construction` appliqué **en interne** à la méthode (pas un middleware) : `Tool::isAccessibleTo($slug, ...)` (`Modules/Tools/app/Http/Controllers/PublicToolController.php:42-52`). Si bloqué, retourne la vue `under-construction` en 200 (pas de redirection). Si accessible, incrémente `views_count` et journalise l'usage quotidien (`trackUsage`). |
| GET | `/user/prompts` | `user.prompts.index` | `UserPromptController::index` | `web`, `auth`, `EnsureToolNotUnderConstruction:constructeur-prompts` | Bibliothèque « Mes prompts » : liste paginée (20/page), recherche, filtre par tag, filtre favoris, panneau « Mon profil ». (`Modules/Tools/routes/web.php:171-173`) |

Route générique associée (non spécifique à l'outil mais empruntée pour y accéder depuis
l'index) : `GET /outils` → `PublicToolController::index` (`Modules/Tools/routes/web.php:22`).

### 2.2 Routes API (préfixe `/api`, groupe de middleware `api` + `web` + `auth` + `throttle:60,1,tools-api`)

Le préfixe `/api` et le nom `api.` sont appliqués globalement par le `RouteServiceProvider`
du module Tools (`Modules/Tools/app/Providers/RouteServiceProvider.php:50` :
`Route::middleware('api')->prefix('api')->name('api.')->group(...)`). Le groupe applique en
plus `web`, `auth` et un throttle **dédié** `tools-api` (60 requêtes/minute par utilisateur,
bucket isolé des autres modules du site - voir commentaire `Modules/Tools/routes/api.php:20-25`).
L'authentification est donc **session Laravel classique** (cookie + jeton CSRF envoyé en
en-tête `X-CSRF-TOKEN`), pas un jeton API séparé - confirmé par `_headers()` côté JS
(`constructeur-prompts-core.js:718-720`).

| Méthode | URI complète | Nom de route | Contrôleur:méthode | Middlewares additionnels | Rôle | Retour |
|---|---|---|---|---|---|---|
| GET | `/api/prompts` | `api.prompts.index` | `SavedPromptController::index` | `EnsureToolNotUnderConstruction:constructeur-prompts` | Liste paginée (20/page) des prompts de l'utilisateur connecté, avec recherche (`search`), filtre tag (`tag`), filtre favoris (`favorite`) | JSON paginé Laravel + clé additionnelle `available_tags` (tags distincts de l'utilisateur, indépendants des filtres actifs) |
| GET | `/api/prompts/{id}` | `api.prompts.show` | `SavedPromptController::show` | idem | Récupère UN prompt par `public_id` (nécessaire pour `?edit=ID`, hors pagination de `index`) | JSON du prompt (404 si absent ou d'un autre utilisateur) |
| POST | `/api/prompts` | `api.prompts.store` | `SavedPromptController::store` | idem | Crée un nouveau prompt sauvegardé | 201 + JSON du prompt créé |
| PUT | `/api/prompts/{id}` | `api.prompts.update` | `SavedPromptController::update` | idem | Met à jour un prompt existant (par `public_id`) | 200 + JSON du prompt mis à jour |
| DELETE | `/api/prompts/{id}` | `api.prompts.destroy` | `SavedPromptController::destroy` | idem | Suppression douce (soft delete) d'un prompt (par `public_id`) | 204 sans contenu |
| POST | `/api/prompts/{id}/duplicate` | `api.prompts.duplicate` | `SavedPromptController::duplicate` | idem | Duplique un prompt (préfixe « Copie de », jamais public/favori par défaut) | 201 + JSON de la copie |
| GET | `/api/tool-preferences/{tool}` | `api.tool-preferences.show` | `ToolPreferenceController::show` | `EnsureToolNotUnderConstruction` (générique, lit `{tool}` dynamiquement) | Lit `users.tool_preferences[{tool}]` (clé `constructeur-prompts` pour cet outil) | JSON `{preferences: {...}}` |
| POST | `/api/tool-preferences/{tool}` | `api.tool-preferences.update` | `ToolPreferenceController::update` | idem | Écrit UNE clé (`key`/`value`) dans `users.tool_preferences[{tool}]`, fusion atomique (verrou de ligne + transaction) | JSON `{preferences: {...}}` (état complet après fusion) |

Toutes les routes `/api/prompts*` sont protégées contre l'IDOR : chaque requête est scopée par
`SavedPrompt::where('user_id', auth()->id())->where('public_id', $publicId)` - jamais l'id
interne auto-incrémenté (`Modules/Tools/app/Http/Controllers/SavedPromptController.php:87-89,
117-119, 126-128, 142-144`). Sans utilisateur connecté, toutes les routes API renvoient 401
(`Route::middleware([..., 'auth', ...])`).

Clés `tool_preferences` réellement utilisées par cet outil via `ToolPreferenceController`
(§3.2) : `custom_cards` et `prompt_profile`.

---

## 3. Modèle de données

### 3.1 Table `saved_prompts`

Créée par `Modules/Tools/database/migrations/2026_03_31_200000_create_saved_prompts_table.php`,
enrichie par 2 migrations additives ultérieures.

| Colonne | Type | Contraintes / index | Ajoutée par |
|---|---|---|---|
| `id` | bigint auto-incrémenté | clé primaire | migration initiale |
| `user_id` | `foreignId` → `users.id` | `onDelete('cascade')`, index | migration initiale |
| `public_id` | `string(12)` | unique, nullable, générée aléatoirement (`Str::random(12)`, vérifiée unique en boucle) | `2026_03_31_210000_add_public_id_to_saved_prompts.php` |
| `name` | `string(255)` | - | migration initiale |
| `prompt_text` | `text` | - | migration initiale |
| `params` | `json` | nullable | migration initiale |
| `is_public` | `boolean` | défaut `false`, index | migration initiale |
| `tags` | `json` | nullable | `2026_07_26_100000_add_tags_favorite_to_saved_prompts.php` |
| `is_favorite` | `boolean` | défaut `false`, index | `2026_07_26_100000_add_tags_favorite_to_saved_prompts.php` |
| `created_at` / `updated_at` | timestamps | - | migration initiale |
| `deleted_at` | soft delete | présent (`$table->softDeletes()`) | migration initiale |

Soft delete actif : `SavedPrompt` utilise le trait `SoftDeletes`
(`Modules/Tools/app/Models/SavedPrompt.php:14`) - une suppression via `DELETE
/api/prompts/{id}` n'efface pas la ligne, elle pose `deleted_at`.

Génération de `public_id` : hook `static::creating()` dans le modèle
(`Modules/Tools/app/Models/SavedPrompt.php:28-38`), boucle `do…while` garantissant l'unicité
avant insertion.

Casts Eloquent (`Modules/Tools/app/Models/SavedPrompt.php:40-45`) :
`params` → `array`, `tags` → `array`, `is_public` → `boolean`, `is_favorite` → `boolean`.

Relation : `SavedPrompt::user()` → `belongsTo(App\Models\User::class)`
(`Modules/Tools/app/Models/SavedPrompt.php:47-50`).

Scopes Eloquent : `forUser(int $userId)`, `public()` (public / non utilisé par l'UI actuelle
de cet outil), `favorite()`, `search(string $term)` (recherche `LIKE` échappée sur `name` ET
`prompt_text`, protégée contre l'injection de jokers `%`/`_`), `withTag(string $tag)`
(`whereJsonContains('tags', $tag)`) - `Modules/Tools/app/Models/SavedPrompt.php:52-86`.

### 3.2 Colonne `users.tool_preferences`

Ajoutée par `Modules/Tools/database/migrations/2026_07_05_160000_add_tool_preferences_to_users_table.php` :
`json('tool_preferences')->nullable()->after('social_links')`. Colonne **partagée entre tous
les outils du module Tools** (structure : `{ [slug_outil]: { [clé]: valeur } }`), pas
spécifique au constructeur de prompts.

Contenu exact pour `constructeur-prompts` (clé `tool_preferences['constructeur-prompts']`) :

| Clé | Type | Description | Validation serveur |
|---|---|---|---|
| `custom_cards` | `array` (max 10 éléments) | Cartes de démarrage personnalisées de l'étape 1. Chaque élément : `{id, title, icon, query_template, hidden}` | `ToolPreferenceController::sanitizeCustomCards()` (`Modules/Tools/app/Http/Controllers/ToolPreferenceController.php:182-234`) - titre requis (sinon carte ignorée), id filtré par regex `^[a-z0-9_\-]{1,40}$` (régénéré + dédupliqué sinon), icône tronquée à 8 octets, `query_template` tronqué à 500 caractères, `title` tronqué à 60 caractères, `hidden` casté en booléen, tableau final tronqué à 10 |
| `prompt_profile` | `array` (0 à 3 clés) | Profil réutilisé pour pré-remplir automatiquement un NOUVEAU prompt (jamais sur `?edit=ID`) : `profile_role`, `profile_style`, `profile_constraints` | `ToolPreferenceController::sanitizePromptProfile()` (`ToolPreferenceController.php:138-173`) - chaînes uniquement, tronquées (pas rejetées) à 80/80/500 caractères respectivement, clé absente si champ vide |

Écriture atomique : `ToolPreferenceController::update()` verrouille la ligne utilisateur
(`lockForUpdate()`) dans une transaction avant de fusionner `array_merge($prefs[$tool] ?? [],
[$key => $value])`, pour éviter qu'une écriture concurrente sur une AUTRE clé n'écrase la
précédente (`ToolPreferenceController.php:58-78`).

---

## 4. Inventaire complet des champs

Convention de la colonne « Injection dans le prompt » : texte exact produit par
`get prompt()` (`constructeur-prompts-core.js:581-667`), reformaté ici pour lisibilité mais
identique au code.

### 4.1 Étape 1 - Objectif

| Champ | Type de contrôle | Valeurs | Défaut | Obligatoire | Injection dans le prompt |
|---|---|---|---|---|---|
| Carte d'objectif (`selectedTask`) | 9 cartes cliquables système + jusqu'à 10 cartes personnalisées | voir tableau 4.1.1 | aucune sélection | **oui** (bloque le passage à l'étape 2) | Aucune injection directe : pré-remplit `personaPreset`/`verb` (cartes système) ou `taskObject` (cartes personnalisées, si le champ est vide ou contient déjà exactement ce gabarit) |

**Tableau 4.1.1 - Les 9 cartes système** (`constructeur-prompts.blade.php:956-966`,
`window.promptBuilderConfig.taskCards`) :

| id | icône | Libellé | Description | Persona pré-assignée | Verbe pré-assigné |
|---|---|---|---|---|---|
| `redaction` | ✍️ | Rédiger un texte | Un article, un courriel, une publication... | `redacteur_web` | Rédige |
| `resume` | 📝 | Résumer un contenu | Condenser un texte, un rapport, une réunion... | `analyste` | Résume |
| `idees` | 💡 | Trouver des idées | Brainstormer des angles, des options, des titres... | `consultant` | Génère |
| `analyse` | 🔍 | Analyser ou comparer | Étudier des données, comparer des options... | `analyste` | Analyse |
| `apprendre` | 🎓 | Apprendre ou comprendre | Faire expliquer un sujet clairement, étape par étape... | `enseignant` | Explique |
| `traduire` | 🌐 | Traduire un texte | Passer d'une langue à une autre... | `redacteur_web` | Traduis |
| `planifier` | 🗂️ | Planifier ou organiser | Un projet, une stratégie, un horaire... | `gestionnaire` | Planifie |
| `coder` | 💻 | Écrire ou déboguer du code | Créer, corriger ou expliquer du code... | `developpeur` | Développe |
| `autre` | ✨ | Autre chose | Je préfère tout choisir moi-même | (vide) | (vide) |

Cartes personnalisées (jusqu'à 10, §6.6) : mêmes contrôles visuels que les cartes système,
mais réordonnables (glisser-déposer ou boutons ↑/↓), éditables (titre, icône via sélecteur,
gabarit de requête), masquables, supprimables.

### 4.2 Étape 2 - Votre demande

| Champ | id DOM | Libellé exact | Type | Valeurs / options | Défaut | Obligatoire | Section | Injection dans le prompt |
|---|---|---|---|---|---|---|---|---|
| Description de la demande | `cpTaskObject` | « Sur quoi porte votre demande ? » | textarea | libre | vide | **oui** | Toujours visible | `"Ta tâche : " + verbe + " " + taskObject + "."` (ou sans le verbe si aucun verbe choisi) |
| Mode audience | (radio `audienceType`) | « Prédéfinie » / « Personnalisée » | radio | `preset`, `custom` | `preset` | - | Bloc audience | pilote quel champ ci-dessous alimente `audienceText` |
| Audience prédéfinie | (pills, `audiencePresets[]`) | « Pour qui ? » | cases à cocher multiples | voir tableau 4.2.1 (7 valeurs) | aucune | non (mais recommandé - signalé par le diagnostic) | Bloc audience | `"Audience cible : " + audienceText + ". Adapte ton vocabulaire, tes exemples et ton niveau de détail en conséquence. Assure-toi que le contenu soit pertinent et accessible pour ce public."` |
| Audience personnalisée | `cpAudienceCustom` | « Pour qui ? » | texte libre | libre | vide | non | Bloc audience | idem, avec le texte libre comme `audienceText` |
| Ton général souhaité | (select, `tone`) | « Ton général souhaité » | liste déroulante | voir tableau 4.2.2 (11 tons) | vide (« -- Aucun -- ») | non | Bloc audience | ligne `"Ton et style : " + tone` dans le bloc « Format de la réponse » |

**Tableau 4.2.1 - Les 7 audiences prédéfinies** (`constructeur-prompts.blade.php:905`,
`$defaultAudiences`) :

| value | label |
|---|---|
| `pro` | Professionnels du secteur |
| `debutants` | Débutants |
| `entrepreneurs` | Entrepreneurs et dirigeants |
| `etudiants` | Étudiants universitaires |
| `grand_public` | Grand public |
| `techniques` | Collègues techniques |
| `direction` | Direction générale |

Si plusieurs audiences sont cochées, `audienceText` les joint en français correct : une
seule = telle quelle ; deux = jointes par « et » ; trois ou plus = virgules puis « et » avant
la dernière (`constructeur-prompts-core.js:517-530`).

**Tableau 4.2.2 - Les 11 tons** (`constructeur-prompts.blade.php:424-435`) :

Professionnel · Accessible et pédagogique · Technique et précis · Chaleureux et engageant ·
Académique · Créatif et dynamique · Conversationnel · Persuasif · Neutre et factuel ·
Empathique et bienveillant · Motivant et inspirant.

### 4.3 Section repliable « Rôle de l'IA » (requise)

| Champ | id DOM | Type | Défaut | Injection |
|---|---|---|---|---|
| Mode rôle | (radio `personaType`) | `preset` / `custom` | `preset` | pilote la source de `personaText` |
| Rôle prédéfini | (select, `personaPreset`) | liste déroulante, 17 valeurs (tableau 4.3.1) | vide | `"Tu es " + (article "un(e) " sauf si le libellé commence déjà par un déterminant) + personaText + " avec une expertise approfondie dans ton domaine. Tu communiques de manière claire et efficace, en adaptant ton niveau de langage à ton audience."` |
| Rôle personnalisé | `cpPersonaCustom` | texte libre | vide | idem, avec le texte libre comme `personaText` |

**Tableau 4.3.1 - Les 17 personas** (`constructeur-prompts.blade.php:903`, `$defaultPersonas`) :

| value | Libellé |
|---|---|
| `expert_marketing` | Expert en marketing digital |
| `redacteur_web` | Rédacteur web professionnel |
| `enseignant` | Enseignant pédagogue |
| `developpeur` | Développeur senior |
| `consultant` | Consultant en stratégie |
| `graphiste` | Graphiste créatif |
| `analyste` | Analyste de données |
| `gestionnaire` | Gestionnaire de projet |
| `coach` | Coach professionnel |
| `journaliste` | Journaliste d'investigation |
| `chercheur` | Chercheur scientifique |
| `rh` | Spécialiste en ressources humaines |
| `concepteur_pedagogique` | Concepteur pédagogique |
| `community_manager` | Gestionnaire de médias sociaux |
| `copywriter` | Rédacteur publicitaire (copywriter) |
| `formateur` | Formateur en entreprise |
| `adjoint_admin` | Adjoint administratif |

Ces 17 valeurs, les 14 verbes (§4.4) et les 7 audiences (§4.2.1) sont configurables sans
déploiement via `Modules\Settings\Facades\Settings` (clés
`tools.prompt_builder.personas`/`verbs`/`audiences`), avec repli automatique sur les valeurs
par défaut ci-dessus si absentes ou invalides (`constructeur-prompts.blade.php:906-916`).

### 4.4 Section repliable « Verbe d'action » (requise)

| Champ | id DOM | Type | Défaut | Injection |
|---|---|---|---|---|
| Mode verbe | (radio `verbType`) | `preset` / `custom` | `preset` | pilote la source du verbe |
| Verbe prédéfini | (select, `verb`) | liste déroulante, 14 valeurs (tableau 4.4.1) | vide | combiné à `taskObject`, voir §4.2 |
| Verbe personnalisé | `cpVerbCustom` | texte libre | vide | idem |

**Tableau 4.4.1 - Les 14 verbes** (`constructeur-prompts.blade.php:904`, `$defaultVerbs`) :

Rédige · Analyse · Crée · Génère · Explique · Compare · Résume · Traduis · Optimise · Évalue ·
Développe · Conçois · Planifie · Diagnostique.

### 4.5 Section repliable « Format, longueur et langue » (facultative)

| Champ | Type | Valeurs | Défaut | Injection |
|---|---|---|---|---|
| Format de sortie (`format`) | select | voir tableau 4.5.1 (12 formats) | vide (« -- Aucun -- ») | ligne `"Structure : " + format` dans le bloc « Format de la réponse » |
| Longueur précise (`length`) | select | voir tableau 4.5.2 (6 longueurs) | vide (« -- Aucune -- ») | ligne `"Longueur visée : " + length` |
| Langue de réponse (`language`) | select | `fr` (Français), `en` (English), `es` (Español) | `fr` | ajoute `"Langue de rédaction : anglais"` si `en`, `"Langue de rédaction : espagnol"` si `es` ; rien pour `fr` |

**Tableau 4.5.1 - Les 12 formats** (`constructeur-prompts.blade.php:511-522`) :

Liste à puces · Paragraphes détaillés · Tableau structuré · Plan hiérarchisé · Étapes
numérotées · Format JSON · Diagramme Mermaid · Questionnaire / QCM avec corrigé · Grille
d'évaluation (rubrique) · Fiche pratique (1 page) · Modèle réutilisable (gabarit) · FAQ
structurée.

**Tableau 4.5.2 - Les 6 longueurs** (`constructeur-prompts.blade.php:529-534`) :

Concis (100-200 mots) · Modéré (300-500 mots) · Détaillé (500-800 mots) · Exhaustif (800+
mots) · 3 à 5 points clés · 5 à 10 points clés.

### 4.6 Section repliable « Comment l'IA doit réfléchir » (facultative)

| Champ | Type | Valeurs | Défaut | Injection |
|---|---|---|---|---|
| Technique de réflexion (`technique`) | select | voir tableau 4.6.1 (5 valeurs) | `zero-shot` | voir §5 (section TECHNIQUE) |
| Séparer les données (`useDelimiters`) | case à cocher | booléen | `false` | `"Utilise des délimiteurs ### pour séparer clairement chaque section de ta réponse."` |
| Exemples (`examples`, id `cpExamples`) | textarea | libre | vide | visible seulement si technique = `few-shot`/`few-shot-cot` ; injecté tel quel sous `"Voici des exemples pour guider ta réponse :"` |

**Tableau 4.6.1 - Les 5 techniques** (`constructeur-prompts.blade.php:564-568`) :

| value | Libellé affiché | Micro-explication (`techniqueHints`) |
|---|---|---|
| `zero-shot` | Réponse directe (par défaut) | L'IA répond directement, sans exemple ni étape intermédiaire. |
| `zero-shot-cot` | Réponse directe + réflexion étape par étape | L'IA réfléchit en interne avant de répondre, sans montrer ce raisonnement. |
| `few-shot` | Avec des exemples | Vous donnez 2-3 exemples du résultat attendu pour guider l'IA. |
| `few-shot-cot` | Avec des exemples + réflexion étape par étape | Exemples fournis, puis raisonnement détaillé appliqué au même modèle. |
| `iterative` | Par étapes, avec votre validation à chaque fois | L'IA avance étape par étape et attend votre accord avant de continuer. |

### 4.7 Section repliable « Contraintes et destination » (facultative)

| Champ | id / x-model | Type | Défaut | Injection |
|---|---|---|---|---|
| Écriture naturelle (anti-IA) | `constraintAntiAI` | case à cocher | **`true`** (seule contrainte activée par défaut) | « Écriture naturelle et humaine : varie la longueur des phrases, utilise des expressions authentiques et des transitions fluides. Évite les formulations génériques (« dans un monde en constante évolution »), les listes à puces systématiques et les répétitions de structure. » |
| Règles typographiques | `constraintTypo` | case à cocher | `false` | « Typographie française stricte : majuscules en début de phrase et noms propres uniquement, pas de tiret cadratin (utilise le tiret court), ponctuation correcte, accents toujours présents. » |
| Destination (`destination`, getter/setter piloté par `constraintCanvas` + `canvasAI`) | select | vide, `chatgpt`, `claude`, `gemini`, `mistral` | vide (« Conversation standard ») | « Destination : crée un nouveau [Canvas de ChatGPT / artefact de Claude / Canvas de Gemini / espace de travail de Mistral] pour ta réponse (pas dans le fil de conversation). » + éventuellement « Format attendu dans cet espace : [format]. » |
| Mode format attendu | `formatMode` (radio) | `preset` / `custom` | `preset` | pilote la source de `fmt` ci-dessous, visible seulement si une destination est choisie |
| Format attendu prédéfini | `canvasFormat` (select) | dépend de l'IA choisie (tableau 4.7.1) | vide | ajouté à la ligne « Destination » ci-dessus |
| Format attendu personnalisé | `canvasCustomFormat` (texte libre) | libre | vide | idem, disponible pour les 4 IA |
| Réflexion étape par étape | `constraintChainOfThought` | case à cocher | `false` | « Montre ton raisonnement complet étape par étape avant de formuler ta réponse finale. » |
| Poser des questions | `constraintAskIfUnclear` | case à cocher | `false` | « Si un élément de ma demande est ambigu ou manque de contexte, pose-moi des questions de clarification avant de commencer. Ne devine pas, demande. » |
| Contraintes spécifiques | `cpConstraintCustom` (textarea) | libre | vide | injecté tel quel dans la liste des contraintes |

**Tableau 4.7.1 - Formats attendus par IA de destination**
(`constructeur-prompts-core.js:56-61`, `canvasFormatMap`) :

| IA | Formats disponibles (nombre) |
|---|---|
| ChatGPT | Markdown, PDF, DOCX (Word), Code (Python/JS/SQL), Tableau interactif, Python exécutable (6) |
| Claude | Markdown, HTML, SVG, React (.jsx), Mermaid, Code, DOCX (Word), PDF, XLSX (tableur), PPTX (slides) (10) |
| Gemini | Google Docs, Google Slides, PDF, Code (Colab), App embarquée, Quiz/Infographie, Markdown (7) |
| Mistral | Markdown, HTML, Code, Diagramme (4) |

### 4.8 Champ transverse produit automatiquement - Critères de qualité

Non saisi par l'utilisateur : généré par assemblage conditionnel selon ce qui a été rempli
(ton, audience, longueur, écriture anti-IA) sous l'en-tête « Avant de finaliser, vérifie
que : » (§5). N'apparaît que si au moins un des 4 critères est actif.

---

## 5. La fonction d'assemblage du prompt

Source exacte : getter `prompt` (`public/assets/tools/constructeur-prompts/constructeur-prompts-core.js:581-667`).
Le prompt final est la concaténation, séparée par une ligne vide (`\n\n`), des sections
suivantes - **produites toujours dans cet ordre**, chaque section n'apparaissant que si les
données qui la composent sont présentes :

1. **RÔLE** - `"Tu es " + [article] + personaText + " avec une expertise approfondie dans ton domaine. Tu communiques de manière claire et efficace, en adaptant ton niveau de langage à ton audience."`
   (l'article « un(e) » n'est ajouté que si `personaText` ne commence pas déjà par un
   déterminant - regex `/^\s*(un |une |des |le |la |l'|d'|du |de )/i`)
2. **TÂCHE** - `"Ta tâche : " + verbe + " " + taskObject + "."` (ou sans verbe si absent)
3. **AUDIENCE** - `"Audience cible : " + audienceText + ". Adapte ton vocabulaire, tes exemples et ton niveau de détail en conséquence. Assure-toi que le contenu soit pertinent et accessible pour ce public."`
4. **FORMAT DE LA RÉPONSE** - liste à puces construite à partir de : Structure (`format`),
   Longueur visée (`length`), Ton et style (`tone`), Langue de rédaction (si `en`/`es`)
5. **CONTRAINTES À RESPECTER** - liste à puces construite à partir de : écriture naturelle,
   typographie française, destination + format attendu, réflexion étape par étape, poser des
   questions, contraintes personnalisées (dans cet ordre fixe)
6. **CRITÈRES DE QUALITÉ** - « Avant de finaliser, vérifie que : » suivi d'une liste
   conditionnelle (ton respecté, contenu adapté à l'audience, longueur correcte, texte non
   détectable comme généré par IA)
7. **DÉLIMITEURS** - si `useDelimiters` coché : « Utilise des délimiteurs ### pour séparer
   clairement chaque section de ta réponse. »
8. **TECHNIQUE** - selon `technique` :
   - `zero-shot-cot` : « Avant de répondre, réfléchis étape par étape à ta stratégie (ne
     montre pas ce raisonnement dans ta réponse finale). »
   - `few-shot`/`few-shot-cot` (si `examples` rempli) : « Voici des exemples pour guider ta
     réponse : » + le contenu de `examples` ; `few-shot-cot` ajoute en plus « Applique le même
     type de raisonnement détaillé que dans les exemples ci-dessus. »
   - `iterative` : « Procède étape par étape. Après chaque étape majeure, présente ton travail
     et demande ma validation avant de continuer. »

### Exemple complet - entrée utilisateur → prompt final

**Entrée** : carte « Rédiger un texte » sélectionnée (persona `redacteur_web`, verbe
`Rédige`) ; demande = « un article de blogue sur les bienfaits du télétravail pour les PME
québécoises » ; audience prédéfinie = « Professionnels du secteur » + « Direction générale » ;
ton = « Professionnel » ; format = « Paragraphes détaillés » ; longueur = « Modéré (300-500
mots) » ; langue = français ; contrainte « Écriture naturelle (anti-IA) » cochée (par défaut) ;
aucune autre option modifiée.

**Sortie produite par `get prompt()`** :

```
Tu es un(e) Rédacteur web professionnel avec une expertise approfondie dans ton domaine. Tu communiques de manière claire et efficace, en adaptant ton niveau de langage à ton audience.

Ta tâche : Rédige un article de blogue sur les bienfaits du télétravail pour les PME québécoises.

Audience cible : Professionnels du secteur et Direction générale. Adapte ton vocabulaire, tes exemples et ton niveau de détail en conséquence. Assure-toi que le contenu soit pertinent et accessible pour ce public.

Format de la réponse :
- Structure : Paragraphes détaillés
- Longueur visée : Modéré (300-500 mots)
- Ton et style : Professionnel

Contraintes à respecter :
- Écriture naturelle et humaine : varie la longueur des phrases, utilise des expressions authentiques et des transitions fluides. Évite les formulations génériques (« dans un monde en constante évolution »), les listes à puces systématiques et les répétitions de structure.

Avant de finaliser, vérifie que :
- le ton demandé est respecté du début à la fin
- le contenu est adapté à l'audience cible
- la longueur correspond à ce qui est demandé
- le texte ne ressemble pas à du contenu généré par IA
```

---

## 6. Toutes les fonctionnalités

### 6.1 Copier le prompt

Déclencheur : bouton « Copier le prompt » (`copy()`, `constructeur-prompts-core.js:1033-1047`),
actif seulement si `isValid`. Comportement : appelle `window.copyToClipboard()` (helper
global du thème), attend la Promise réelle de l'API presse-papiers avant d'afficher
« Copié ! » - le toast de succès ne s'affiche que si l'écriture a réellement réussi. Envoie
un événement de suivi `gtag('event', 'prompt_copy', ...)` si `gtag` est chargé. Persistance :
aucune. Limite : aucune (le prompt copié n'a pas de longueur maximale imposée côté client).

### 6.2 Exporter en .txt

Déclencheur : bouton « Exporter .txt » (`exportPrompt()`, `constructeur-prompts-core.js:1725-1738`),
actif si `isValid`, **atteignable par un invité** (pas de gate `isAuthenticated`). Génère un
`Blob` texte téléchargé sous le nom fixe `prompt.txt` via un lien `<a>` temporaire créé puis
supprimé du DOM. Sur échec (ex. API `Blob`/`URL.createObjectURL` indisponible), affiche un
message d'erreur traduit (`i18n.exportError`). Persistance : aucune (fichier local à
l'utilisateur, rien envoyé au serveur).

### 6.3 Sauvegarder / bibliothèque « Mes prompts »

- **Sauvegarde connectée** (`addToHistory()`, `constructeur-prompts-core.js:1126-1189`) :
  `POST /api/prompts` (nouveau) ou `PUT /api/prompts/{id}` (mode édition, `_editingId` non
  nul) avec `{name, prompt_text, params: wizardParams}`. `wizardParams` sérialise **tous**
  les champs du wizard (25 clés) pour permettre une restauration fidèle via `?edit=ID`.
  Bloqué tant que `historyLoaded` n'a pas résolu (évite qu'un GET tardif n'écrase la
  sauvegarde fraîche). Titre par défaut si champ vide : `personaText` ou « Prompt ».
- **Sauvegarde invité** : `addToHistory()` redirige vers `$dispatch('open-auth-modal')` - un
  invité **ne peut pas** sauvegarder côté compte ; seul l'historique `localStorage` (§7) lui
  est disponible.
- **Recherche** (`SavedPrompt::scopeSearch`) : `LIKE` échappé sur `name` ET `prompt_text`
  simultanément (`Modules/Tools/app/Models/SavedPrompt.php:67-81`).
- **Tags** : jusqu'à 5 par prompt, 30 caractères max chacun, dédupliqués insensible à la
  casse côté serveur (`SavedPromptController::sanitizeTags()`) ; édition inline sur
  `/user/prompts` sans `window.prompt()` natif (`user/prompts/index.blade.php:238-252`) ;
  filtrage par tag via chips cliquables générées depuis `available_tags` (union des tags de
  l'utilisateur, indépendante de la page courante).
- **Favoris** (`is_favorite`) : bascule instantanée (`toggleFavorite()`,
  `user/prompts/index.blade.php:366-402`) via `PUT /api/prompts/{id}` ; disparition
  immédiate de la carte si le filtre « Favoris seulement » est actif et que le favori est
  retiré.
- **Duplication** (`duplicatePrompt()` + `SavedPromptController::duplicate()`) : copie
  `prompt_text`/`params`/`tags`, préfixe le nom de « Copie de : », jamais publique ni favorite
  par défaut quel que soit l'original.
- **Suppression** : `DELETE /api/prompts/{id}` (soft delete), confirmation via la modale
  globale du thème (`open-confirm-global`), jamais `confirm()` natif.
- **Import localStorage → compte** (`importLocalStorage()`,
  `constructeur-prompts-core.js:1226-1288`) : geste explicite (bouton « Importer »), jamais
  automatique. Traite chaque entrée indépendamment (`Promise.allSettled`-like via `.catch`
  individuel) : les items qui échouent restent en `localStorage`, les autres sont retirés.

### 6.4 Cartes de démarrage système et personnalisées

- Système (9 cartes, tableau 4.1.1) : fixes, non éditables, non réordonnables, non
  supprimables.
- Personnalisées (0 à 10, §3.2) : ajout (`addCustomCard()`), édition du titre inline (clic →
  input, `Entrée`/`blur` valide, `Échap` annule), édition de l'icône (sélecteur, §6.7),
  édition du « gabarit de requête » (`query_template`, 500 caractères max, pré-remplit
  `taskObject` à la sélection **seulement si le champ est vide ou contient déjà exactement ce
  gabarit** - ne détruit jamais un texte déjà saisi par l'utilisateur), masquage (`hidden`,
  la carte reste dans la grille mais visuellement atténuée et non sélectionnable),
  suppression (confirmation modale), réordonnancement par glisser-déposer natif HTML5 **ou**
  boutons ↑/↓ (alternative clavier obligatoire WCAG 2.2).
- Persistance : `localStorage` (invité, clé `cp_custom_cards`, §7) ou
  `tool_preferences.custom_cards` (connecté) ; file d'attente `_cardsPersistQueue` sérialisant
  les écritures serveur pour éviter les courses (`constructeur-prompts-core.js:1384-1421`).
- Import invité → compte (`importLocalCustomCards()`) : geste explicite, fusionne
  (n'écrase pas) avec les cartes déjà en base, plafonné à 10 au total, les cartes en surplus
  restent disponibles pour un import ultérieur après suppression d'une carte existante.
- Limite : 10 cartes personnalisées maximum par utilisateur/navigateur.

### 6.5 Sélecteur d'icônes des cartes personnalisées

Catalogue de ~199 emojis classés en 12 catégories nommées en français (Écriture, Analyse et
données, Apprentissage, Communication, Travail et organisation, Technique et code, Création
et design, Santé, Commerce, Lieux et voyage, Temps et planification, Symboles et statuts -
`constructeur-prompts-core.js:197-421`). Recherche par mot-clé français, insensible aux
accents et à la casse (normalisation NFD, `_normalizeIconText`). Regroupé par catégorie sans
recherche active, à plat pendant une recherche. Navigation clavier réelle (flèches,
Début/Fin, 5 colonnes bureau / 4 mobile) - `handleIconGridKeydown()`. Chaque emoji est
garanti tenir sous 8 octets UTF-8 (contrainte de troncature serveur `Str::limit(icon, 8,
'')`).

### 6.6 Diagnostic rapide

`get diagnostic()` (`constructeur-prompts-core.js:673-693`) : détection **par règles
simples, zéro IA, zéro appel réseau**, de 3 manques fréquents, chacun avec un lien « Compléter »
qui ouvre la section « Réglages avancés » correspondante et y fait défiler la page
(`openDiagnosticSection()`) :
1. Aucun format ni longueur précisés
2. Aucune audience précisée
3. Aucune contrainte cochée dans « Contraintes et destination »

N'est affiché que si `isValid` (le panneau ne peut donc jamais signaler « verbe manquant »,
ce cas étant couvert par l'alerte générique `!isValid`).

### 6.7 Méta-prompt « Améliorer avec mon IA »

`toggleMetaPrompt()` + `get metaPrompt()` (`constructeur-prompts-core.js:698-704,1740-1745`).
Génère 100 % côté client un méta-prompt fixe : « Tu es un expert en ingénierie de prompt.
Améliore le prompt suivant pour le rendre plus clair, plus précis et plus efficace pour un
LLM, SANS changer l'intention de l'utilisateur. Retourne UNIQUEMENT le prompt amélioré, sans
commentaire ni explication. » suivi du prompt courant entre guillemets. Réutilise
intégralement le mécanisme « Ouvrir dans » / « Copier » existant (aucune duplication de code).
Disponible aux invités comme aux connectés. Aucun appel réseau MEMORA.

### 6.8 Masquage des renseignements personnels (« anonymisation en place »)

Bouton `#cpAnonToggle` (`maskFieldInPlace()`, `prompt-anon-panel.js:368-444`) : détecte les
entités personnelles (`window.AnonymizerCore.detectEntities()`) dans le champ **Tâche**
directement, sans jamais ouvrir de panneau séparé (refonte round 148, 2026-07-31, décision
validée par un panel de 3 IA : Perplexity/Gemini 95/100, Codex 82/100). Remplace le contenu
en place (préservant l'historique Ctrl+Z natif via `document.execCommand('insertText', ...)`),
affiche un récapitulatif humain généré (« 2 noms et 1 numéro de téléphone ont été masqués. »,
avec accords grammaticaux corrects genre/nombre), propose « Revenir à mon texte de départ »
(annulation en mémoire JS uniquement, avec confirmation modale si le champ a été modifié
depuis le masquage - jamais de perte silencieuse). Étendu à 6 champs surveillés au total :
Tâche, Rôle personnalisé, Audience personnalisée, Verbe personnalisé, Contraintes
personnalisées, Exemples. Les gabarits de carte personnalisée (`cpCardTemplate-*`) restent
sur l'ancien mécanisme à 2 zones (panneau `#cpAnonPanel` avec `<x-tools::anonymizer-editor>`),
car ce sont des champs montés/démontés dynamiquement par Alpine.

Garde-fou proactif (§9) : un bandeau ambre s'affiche automatiquement (débounce 600 ms +
`blur`) sur les 6 champs surveillés dès qu'une entité personnelle est détectée, avec bouton
« Masquer mes infos → » et bouton de fermeture par contenu (anti-harcèlement : ne réapparaît
pas pour le même texte déjà ignoré).

### 6.9 Handoff depuis l'anonymiseur complet

Lien « ↗ Anonymiseur complet » vers `/outils/anonymiseur`
(`constructeur-prompts.blade.php:365`). Au retour, `prompt-anon-panel.js:52-60` lit
`sessionStorage.getItem('lv_handoff_prompt_text')` (clé volatile, one-time), l'injecte dans
`#cpTaskObject`, déclenche l'événement `input` (met à jour Alpine), puis **supprime**
immédiatement la clé de `sessionStorage`.

### 6.10 Ouvrir dans une IA

`openIn(target, text?)` (`constructeur-prompts-core.js:1060-1114`). DRY : même mécanisme pour
le prompt normal et pour le méta-prompt. 5 cibles :

| Cible | URL de base | Comportement |
|---|---|---|
| ChatGPT | `https://chatgpt.com/?q=` | pré-remplit via paramètre `q` si < 4000 caractères encodés |
| Claude | `https://claude.ai/new?q=` | idem |
| Perplexity | `https://www.perplexity.ai/search?q=` | idem |
| Gemini | `https://gemini.google.com/app` | pas de pré-remplissage URL - ouvre l'app, prompt copié, message « colle-le » |
| Mistral | `https://chat.mistral.ai/chat` | idem Gemini |

Au-delà de 4000 caractères encodés, aucune IA (sauf Gemini/Mistral) n'est pré-remplie : le
prompt est copié et un message explicite invite à coller manuellement. `window.open()`
s'exécute toujours de façon synchrone dans la pile du clic (jamais après une Promise) pour
éviter le blocage de popup par le navigateur.

### 6.11 Plein écran

Composant réutilisable `tools::partials.fullscreen-btn`
(`Modules/Tools/resources/views/partials/fullscreen-btn.blade.php`), utilise l'API
Fullscreen native du navigateur sur l'élément portant `.tool-fullscreen-target` (le
`<div class="card">` racine de l'outil).

### 6.12 Partage

Composant réutilisable `tools::partials.share-btn`, s'appuie sur `Tool::getShareData()`
(trait `Modules/Tools/app/Models/Concerns/Shareable.php`). Utilise l'API Web Share native
(`navigator.share`) si disponible, sinon repli sur la copie du texte de partage dans le
presse-papiers.

### 6.13 Historique invité

Visible uniquement pour les non-connectés (`!isAuthenticated && history.length > 0`,
`constructeur-prompts.blade.php:783-803`) : liste des prompts stockés en `localStorage`
(§7), copie rapide, suppression individuelle ou globale (« Effacer »). Les connectés voient à
la place le lien contextuel vers `/user/prompts` (§6.3).

### 6.14 Import des données locales vers le compte

Deux imports distincts et indépendants au moment de la connexion : historique de prompts
(`importLocalStorage()`, `localStorage['pb_history']` → `POST /api/prompts` un par un) et
cartes personnalisées (`importLocalCustomCards()`, `localStorage['cp_custom_cards']` → fusion
avec `tool_preferences.custom_cards`). Les deux sont des **gestes explicites** (bouton
cliqué), jamais une fusion automatique silencieuse à la connexion (décision utilisateur
2026-07-26, documentée en commentaire).

---

## 7. Persistance et cycle de vie des données

| Support | Clé / colonne | Contenu | Portée | Survit à... |
|---|---|---|---|---|
| Base de données | `saved_prompts` | Prompts sauvegardés d'un compte | Compte utilisateur | rechargement, fermeture d'onglet, changement d'appareil (accessible partout où le compte est connecté) |
| Base de données | `users.tool_preferences['constructeur-prompts']['custom_cards']` | Cartes personnalisées d'un compte | Compte utilisateur | idem |
| Base de données | `users.tool_preferences['constructeur-prompts']['prompt_profile']` | Profil de pré-remplissage (`/user/prompts`) | Compte utilisateur | idem |
| `localStorage` | `pb_history` | Historique de prompts d'un **invité** (tableau `{id, prompt, name}`) | Navigateur, non versionné explicitement | rechargement, fermeture d'onglet - **pas** un changement d'appareil/navigateur |
| `localStorage` | `cp_custom_cards` | Cartes personnalisées d'un **invité** | Navigateur | Enveloppe versionnée `{version: 1, cards: [...]}` (`_readLocalCustomCards`/`_saveLocalCustomCards`, `constructeur-prompts-core.js:1302-1312`) ; une version future non `=== 1` serait ignorée (repli sur tableau vide) |
| `sessionStorage` | `lv_handoff_prompt_text` | Texte anonymisé transmis depuis `/outils/anonymiseur` | Onglet courant, one-time | supprimée immédiatement après lecture - ne survit ni au rechargement ni à un nouvel onglet |
| Mémoire JS uniquement | `maskState` (Map, `prompt-anon-panel.js:257`) | Texte d'origine + texte masqué de chaque champ, pour l'« Annuler » du masquage en place | En mémoire seulement | perdu au rechargement de page (jamais écrit sur disque ni envoyé au serveur) |
| Mémoire JS uniquement | État du wizard (Alpine, tous les champs de §4) | État courant du formulaire | En mémoire seulement | perdu au rechargement, sauf sauvegarde explicite ou `?edit=ID` |

Aucun autre mécanisme de persistance identifié (pas de cookie applicatif dédié à cet outil,
pas d'IndexedDB). Le seul « schéma versionné » explicite trouvé dans le code est celui de
`cp_custom_cards` (`version: 1`) ; `pb_history` n'a pas de champ de version - NON TROUVÉ de
logique de migration pour cette clé.

---

## 8. États et parcours

1. **Chargement initial** (`init()`, `constructeur-prompts-core.js:736-950`) :
   - Connecté : `GET /api/prompts` (historique), et si `?edit=ID` présent, `GET
     /api/prompts/{id}` pour restaurer l'état complet du wizard (saute directement à l'étape 2,
     déplie toutes les sections avancées) ; sinon `GET /api/tool-preferences/constructeur-prompts`
     pour lire `prompt_profile` et pré-remplir un nouveau prompt (seulement si les champs
     ciblés sont encore vides).
   - Invité : lecture de `localStorage['pb_history']`.
   - Dans tous les cas : `_loadCustomCards()` (GET serveur si connecté, sinon lecture
     `localStorage`).
2. **Étape 1 (objectif)** : sélection obligatoire d'une carte. `nextStep()` refuse d'avancer
   si `selectedTask` est vide et affiche l'alerte « Veuillez choisir une carte avant de
   continuer. » (`showValidation`).
3. **Étape 2 (demande)** : remplissage du champ Tâche (obligatoire) et, dans les sections
   repliées par défaut, du rôle et du verbe (tous deux obligatoires pour que `isValid` soit
   vrai - `get isValid()`, `constructeur-prompts-core.js:502-505`).
4. **Validation bloquante** : `isValid` exige `personaText` non vide **ET** `taskObject` non
   vide **ET** un verbe non vide (preset ou custom selon `verbType`). Tant que faux : alerte
   « Choisissez un objectif (étape 1) et décrivez votre demande (étape 2) pour générer votre
   prompt. » et les 3 boutons d'action (Copier, Améliorer, Exporter) restent désactivés
   (`aria-describedby="cpValidityHint"`).
5. **État vide** : aucun état vide serveur particulier - un invité sans historique ne voit
   simplement pas le bloc historique (`history.length > 0`) ; un connecté sans prompt voit
   sur `/user/prompts` le message « Aucun prompt sauvegardé » avec bouton « Créer un prompt »
   (`user/prompts/index.blade.php:167-171`), ou « Aucun prompt ne correspond à ces filtres. »
   si des filtres actifs retournent un ensemble vide.
6. **Réinitialisation** : bouton « 🔄 Recommencer » exige une double confirmation en place
   (`armReset()`, le libellé devient « ⚠️ Confirmer la réinitialisation » pendant 4 secondes),
   puis recharge la page sans paramètres (`resetAll()` → `window.location.href =
   window.location.pathname`).

---

## 9. Sécurité et confidentialité

### 9.1 Ce qui part au serveur

- Sur sauvegarde/édition explicite (`POST`/`PUT /api/prompts`) : `name`, `prompt_text`
  (jusqu'à 20 000 caractères), `params` (état complet du wizard, ≤ 10 000 octets JSON
  sérialisés - validé par une règle custom `paramsSizeRule()`), `tags` (≤ 5 × 30 caractères),
  `is_public`, `is_favorite`.
- Sur mutation de préférences (`POST /api/tool-preferences/constructeur-prompts`) :
  `custom_cards` (≤ 10 cartes) ou `prompt_profile` (3 champs texte, ≤ 80/80/500 caractères).
- Le texte du champ Tâche N'EST envoyé au serveur QUE lors d'une sauvegarde explicite (bouton
  « Sauvegarder ») - jamais en frappe continue (pas d'autosave serveur détecté dans le code
  lu).

### 9.2 Ce qui ne quitte jamais le navigateur

- Le masquage des renseignements personnels (`window.AnonymizerCore`) : détection et
  remplacement **100 % local**, message explicite affiché à l'utilisateur : « 100 % local :
  aucune donnée ne quitte votre navigateur. » (`constructeur-prompts.blade.php:377`).
- Le méta-prompt « Améliorer avec mon IA » : construit et affiché côté client uniquement,
  aucun appel réseau MEMORA (§6.7).
- L'historique et les cartes d'un **invité** : `localStorage`/`sessionStorage` uniquement,
  jamais transmis au serveur tant qu'aucun import explicite n'est déclenché après connexion.
- Le prompt copié/exporté/ouvert dans une IA tierce : transite par le presse-papiers du
  système d'exploitation ou par l'URL de l'IA cible, jamais par un serveur MEMORA.

### 9.3 Throttles

- Toutes les routes `/api/prompts*` et `/api/tool-preferences/*` : `throttle:60,1,tools-api`
  (60 requêtes/minute par utilisateur connecté, bucket **dédié**, isolé des autres modules du
  site - corrige un partage de compteur découvert en passe adversariale round 47,
  `Modules/Tools/routes/api.php:20-25`).
- `GET /outils/constructeur-prompts` : `cacheResponse:600` (cache de réponse 600 secondes,
  pas un throttle à proprement parler mais réduit la charge serveur par requête répétée).

### 9.4 Validations serveur

Voir détail exhaustif §3.2 et §6.3 : `SavedPromptController` valide `name` (requis, 255 max),
`prompt_text` (requis, 20 000 max), `params` (tableau, ≤ 10 000 octets JSON), `tags` (≤ 5,
30 caractères chacun, dédupliqués insensibles à la casse), `is_favorite`/`is_public`
(booléens). `ToolPreferenceController` valide `key` (regex `^[a-z_]{1,40}$`) et applique un
« sanitizer » dédié par clé connue (`custom_cards`, `prompt_profile`), avec un plafond
générique de 2000 octets JSON pour toute clé inconnue.

Anti-IDOR systématique : toute lecture/écriture/suppression d'un `SavedPrompt` est scopée par
`user_id = auth()->id() AND public_id = $publicId` - jamais par l'id interne (confirmé par 8
tests dédiés dans `SavedPromptControllerTest.php`, §11).

### 9.5 Gate de révision

Documenté en détail au §1 et §2. Le gate `Tool::isAccessibleTo()` s'appuie sur la colonne
`tools.is_under_construction` (avec repli permissif si la colonne n'existe pas encore - cas
d'un déploiement en cours de migration) et sur `$user->isSuperAdmin()`. Étendu à toutes les
routes satellites (bibliothèque, API, export RGPD, page `/user/saved`) - trouvaille et
correctif documentés dans `ConstructeurPromptsGateTest.php` (§11). En mode `revision`, la page
de remplacement affiche explicitement que les prompts déjà sauvegardés sont intacts et
resteront accessibles au retour de l'outil.

### 9.6 Masquage local - détail

Voir §6.8/§6.9. Le moteur `AnonymizerCore` (fichier séparé, `anonymizer-core.js`, hors
périmètre strict de cet outil mais réutilisé tel quel) détecte 16 catégories d'entités
personnelles (`$anonPluralLabels`, `constructeur-prompts.blade.php:981-998`) : nom complet,
nom de famille, prénom, RAMQ, numéro de permis, adresse, code postal, courriel, carte
bancaire, IBAN, adresse IP, téléphone, numéro de dossier, montant, date, NAS.

---

## 10. Internationalisation

- Locales de **l'interface du site** : `fr` (défaut) et `en` uniquement, pilotées par
  `session('locale')` via le middleware `App\Http\Middleware\SetLocale`
  (`app/Http/Middleware/SetLocale.php:22-26` - toute valeur hors `['fr', 'en']` est ignorée).
- Toutes les chaînes d'interface Blade passent par `__()` ; les clés vivent dans
  `lang/fr.json` et `lang/en.json` (fichiers de traduction « plates », clé = texte source
  français). Un test dédié (`ConstructeurPromptsGateTest.php`, §11) vérifie le rendu HTTP réel
  en français ET en anglais pour 184 chaînes Blade + 12 clés `i18n` JS critiques.
- Les données injectées en JS (`window.promptBuilderConfig`) sont **elles-mêmes** déjà
  traduites côté serveur via `__()` avant d'être sérialisées en JSON
  (`constructeur-prompts.blade.php:1005-1106`) - le JS ne fait aucune traduction lui-même, il
  consomme des chaînes déjà localisées avec repli français en dur si la clé est absente.
- Distinction essentielle : le champ **Langue de réponse** (`language`, fr/en/es, §4.5) n'est
  **pas** une locale d'interface - c'est une instruction ajoutée au **texte du prompt généré**
  pour demander à l'IA cible de répondre dans cette langue. Le gabarit du prompt lui-même
  (« Tu es... », « Ta tâche... ») reste **toujours rédigé en français**, quelle que soit la
  locale du site ou la langue de réponse demandée - décision documentée explicitement en
  commentaire (`constructeur-prompts.blade.php:949-955` : « Traduire personas/verbes/audiences
  casserait donc le prompt généré (grammaire mixte FR/EN) »).
- Les valeurs internes (`value` des personas/verbes/audiences/cartes) ne sont **jamais**
  traduites - seuls leurs libellés d'affichage (`label`) le sont - car ces valeurs sont
  injectées brutes dans le texte du prompt final.

---

## 11. Tests existants

5 fichiers de tests Feature (style Pest), 1694 lignes au total, couvrant les invariants
suivants.

### `Modules/Tools/tests/Feature/SavedPromptControllerTest.php` (473 lignes, ~29 cas)

Protège : blocage des invités sur les 4 verbes CRUD + duplication ; anti-IDOR complet
(update, show, destroy, duplicate, index - 5 tests dédiés « user B cannot ... user A's ») ;
`show()` fonctionne au-delà de la 1ʳᵉ page paginée (fix round 5) ; duplication toujours non
publique/non favorite quel que soit l'original ; validations de `name` (requis, non tableau,
max longueur), `prompt_text` (requis, non tableau, max 20 000) ; `params` volumineux rejeté
(store ET update, round 34) mais un payload réaliste accepté ; `PUT` sur l'id interne (pas
`public_id`) retourne 404 plutôt qu'un no-op silencieux ; `user_id` du payload est ignoré à la
création ; dédoublonnage des tags insensible à la casse ; soft delete effectif (disparaît de
l'index) ; rendu HTTP réel du compteur `trans_choice()` pour 0/1/2+ prompts (round 27).

### `Modules/Tools/tests/Feature/SavedPromptFiltersAndProfileTest.php` (425 lignes, 16 cas)

Protège : filtrage par recherche (nom OU texte), par tag, par favori ; `available_tags`
inclut tous les tags de l'utilisateur indépendamment du filtre actif, n'inclut jamais les
tags d'un autre utilisateur, et ne fait jamais disparaître un tag littéral « 0 » (round 44) ;
validation de la taille/contenu du tableau `tags` au `store` ; mise à jour de `is_favorite`
sans toucher aux autres champs, bloquée en IDOR ; duplication (attributs corrects, IDOR,
invité bloqué) ; sauvegarde/lecture du profil de préremplissage, troncature (pas rejet) d'un
rôle trop long, rejet des champs non-chaîne, isolation stricte entre utilisateurs.

### `Modules/Tools/tests/Feature/ToolPreferenceControllerTest.php` (331 lignes, 21 cas)

Protège (dont plusieurs clés partagées avec d'autres outils, non spécifiques à
constructeur-prompts) : blocage des invités ; retour vide si aucune préférence ;
`custom_colors`/`custom_durations`/`favorite_colors`/`traffic_thresholds`/`default_color` -
hors périmètre direct de cet outil. **Spécifique constructeur-prompts** : sauvegarde/lecture
de `custom_cards`, plafond à 10 entrées avec préservation de l'ordre, filtrage des cartes sans
titre (sans rejeter le reste du lot), génération d'id/icône sûrs par défaut si absents,
troncature (pas rejet) d'un titre/gabarit trop long, rejet d'une valeur non-tableau, tableau
vide accepté (suppression de la dernière carte) ; non-perte d'une écriture concurrente sur une
autre clé pendant la même session (round 40, verrou de ligne).

### `Modules/Tools/tests/Feature/ConstructeurPromptsGateTest.php` (376 lignes, 20 cas)

Protège le gate de révision et son extension aux routes satellites : `/user/prompts` bloqué
pour un non-admin (JSON 403 pour l'API, page HTML pour le reste) ; API prompts et
tool-preferences bloquées pour un non-admin ; **superadmin non affecté** sur toutes les routes
satellites ; **aucun effet croisé** sur un autre outil (`minuteur-visuel`) non gaté ; rendu
localisé réel (FR/EN) des 12 clés `i18n` JS critiques ET de 184 chaînes Blade du wizard ;
masquage/affichage correct du lien « Mes prompts » dans le menu utilisateur selon le rôle ;
masquage/affichage des prompts sur `/user/saved` (aperçu ET nom) selon le rôle, sans affecter
les autres types d'éléments sauvegardés ; exclusion/inclusion des prompts dans l'export RGPD
(`/user/data-export`) selon le rôle ; message « indisponible » et libellé d'outil localisés en
anglais ; état vide de `/user/saved` localisé ; non-régression de performance
(`Tool::isAccessibleTo()` ne relance pas de requête `information_schema` à chaque appel,
round 33) ; gestionnaire de suppression de `/user/saved` vérifie `r.ok` avant de retirer la
ligne du DOM (round 39).

### `Modules/Tools/tests/Feature/PromptsLibraryScriptIntegrityTest.php` (89 lignes, 2 cas)

Protège l'intégrité syntaxique du script inline `promptsLibrary()` de `/user/prompts` (round
132) : validité syntaxique JS (parsée réellement, pas juste un `grep`), et présence effective
de toutes les méthodes attendues dans l'objet retourné (pas de méthode orpheline hors de
l'objet suite à une erreur d'accolade).

---

## 12. Code mort et incohérences trouvées

- **`audienceType === 'none'`** : le getter `audienceText`
  (`constructeur-prompts-core.js:517-530`) teste une branche `if (this.audienceType ===
  'none') return '';`, mais l'UI n'expose que deux options radio pour `audienceType`
  (`preset`/`custom`, `constructeur-prompts.blade.php:396-403`) - aucun contrôle ne peut
  jamais positionner `'none'`. Branche inatteignable en pratique.
- **`audiencePreset` (singulier) vs `audiencePresets` (pluriel)** : l'état interne conserve
  les deux (`personaPreset`/`audiencePreset: ''` ligne 41 ET `audiencePresets: []` ligne 42 de
  `constructeur-prompts-core.js`), et `wizardParams` sérialise les deux. Seul `audiencePresets`
  (pluriel, tableau) est lu par `audienceText` et par l'UI (pills à cocher). Le champ singulier
  `audiencePreset` n'est plus jamais écrit par une action utilisateur actuelle - il ne sert
  qu'à la rétrocompatibilité de lecture d'anciens prompts sauvegardés avant l'introduction du
  multi-sélection (`init()`, ligne ~817-818 : « if (Array.isArray(p.audiencePresets)) ...
  else if (p.audiencePreset) self.audiencePresets = [p.audiencePreset]; »). Conservé
  intentionnellement pour la migration de lecture, mais constitue un champ mort à l'écriture.
- **Migration `canvasAI === 'custom'`** : `init()` (`constructeur-prompts-core.js:872-874`)
  contient une migration silencieuse d'anciens prompts où `canvasAI` valait littéralement
  `'custom'` (ancien schéma antérieur au 2026-05-05, #104) vers `canvasAI: 'chatgpt'` +
  `formatMode: 'custom'`. Aucune valeur `'custom'` ne peut plus être écrite par l'UI actuelle
  (le `<select>` `destination` n'expose que `chatgpt`/`claude`/`gemini`/`mistral`) - code de
  migration à sens unique, à conserver tant que d'anciens `SavedPrompt.params` peuvent
  contenir cette valeur historique.
- **`canGoToStep(s)`** (`constructeur-prompts-core.js:1022-1026`) : fonction toujours définie
  et utilisée par `goToStep()`, mais avec seulement 2 étapes possibles (`step: 1|2`), sa
  logique `if (s <= 1) return true;` est redondante avec le seul autre cas testé (`s >= 2`).
  Fonctionnellement correcte, simplement sur-généralisée pour un wizard à 2 étapes alors
  qu'elle semble taillée pour un nombre d'étapes variable (vestige d'une version antérieure à
  plus de 2 étapes, cf. commentaires « Phase 1/Phase 2 » de la refonte « objectif d'abord »).
- **Rendu HTML du bouton « Suivant » avec retour à la ligne brut** dans le gabarit Blade
  (`constructeur-prompts.blade.php:688-689` : le texte `{{ __('Suivant') }}` est sur sa propre
  ligne, précédé d'un saut de ligne dans le code source à l'intérieur du `<button>`) - sans
  effet visuel (HTML collapse les espaces), mais incohérent avec le formatage à plat du reste
  du fichier.
- **Route `tools.motdle`** (`Modules/Tools/routes/web.php:127`) : redirection permanente vers
  `/outils`, jeu retiré en 2026-06-13 - sans lien avec le constructeur de prompts, mais
  présente dans le même fichier de routes et pourrait prêter à confusion lors d'une recherche
  par mot-clé « prompt » (aucune relation réelle : Motdle est un jeu de vocabulaire, pas cet
  outil).
- **Tool distinct « Prompteur »** (`Modules/Tools/resources/views/public/tools/prompteur.blade.php`,
  route générée dynamiquement par `PublicToolController::show` pour le slug `prompteur`) : nom
  proche et domaine voisin (génération de script vidéo par IA, BYOA également) mais **outil
  entièrement séparé**, hors périmètre de cette spécification - à ne pas confondre lors d'une
  recherche de code.
- **`scopePublic()`** (`Modules/Tools/app/Models/SavedPrompt.php:57-60`) : scope Eloquent
  défini (`is_public = true`) mais aucun endpoint ni vue de cet outil ne l'utilise dans le
  code lu - la colonne `is_public` existe en base et est acceptée en validation
  (`SavedPromptController::store/update`, règle `'is_public' => 'boolean'`) mais aucune
  fonctionnalité de partage public de prompt (page publique, lien partageable) n'a été
  localisée dans les fichiers lus. Fonctionnalité probablement préparée mais non branchée côté
  UI actuelle du wizard.
