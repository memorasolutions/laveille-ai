# Outil : Anonymiseur de texte (formulaire_anonyme_v2)

## Vue d'ensemble

Outil web 100 % côté client permettant d'**anonymiser un texte avant de l'envoyer à une IA** (ChatGPT, Claude, Gemini, etc.), puis de **restaurer les données originales** dans la réponse retournée. Conçu pour respecter la **Loi 25 (Québec)** et le **RGPD (Europe)** : aucune donnée personnelle ne quitte l'appareil de l'utilisateur.

- **Emplacement** : `public/outils/formulaire_anonyme_v2/`
- **URL publique** : `https://guide.memora.solutions/outils/formulaire_anonyme_v2/?_token=...`
- **Type** : page statique HTML + JS vanilla + CSS, protégée par token via `_protection.php`
- **ID outil (Filament Tool)** : `01kbr36a8atk5k3238xc5exrtk`
- **Stockage** : `localStorage` (clé `anonymizer_rules_v2`), aucun backend
- **Aucune dépendance externe** (pas de CDN, pas de framework)

## Fichiers

| Fichier | Lignes | Rôle |
|---|---|---|
| `index.php` | 300 | Markup HTML + appel à `memora_protect()` pour la vérification du token d'accès |
| `app.js` | 1302 | Toute la logique (détection PII, génération de fausses données, rendu, import/export, restauration) |
| `styles.css` | 1245 | Thème complet de l'app |
| `../_protection.php` | partagé | Garde l'accès via token signé issu du portail Filament |

## Workflow utilisateur (3 étapes)

1. **Coller le texte original** dans la zone d'édition (contenteditable).
2. **Anonymiser** : créer des règles manuellement, OU cliquer sur *Détecter* pour repérer automatiquement les PII (Personally Identifiable Information).
3. **Utiliser & décrypter** : copier le texte anonymisé vers l'IA, coller la réponse de l'IA, puis cliquer *Restaurer les données originales* pour réinjecter les vraies valeurs.

## Catégories supportées

| Clé | Icône | Type de donnée |
|---|---|---|
| `identity` | 👤 | Prénom + nom (avec variantes auto : nom seul, prénom seul) |
| `contact` | 📞 | Email, téléphone CA/FR |
| `location` | 🏠 | Adresse, code postal CA (`H2X 1Y4`) ou FR (5 chiffres) |
| `id` | 🔢 | NAS, numéro de dossier, carte de crédit |
| `date` | 📅 | Dates ISO, EU (`15/03/1985`), française (`12 mai 1950`) — format préservé |
| `money` | 💰 | Montants $ / euros / CAD / EUR / dollars |
| `other` | 📝 | Entreprises, projets, lieux, etc. |

## Détection automatique (regex)

L'objet `DetectionPatterns` (`app.js:79`) scanne le texte avec ces patterns :

- `email` : `[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}`
- `phoneCA` : formats `(514) 555-1234`, `+1 514.555.1234`, etc.
- `postalCA` : `H2X 1Y4`
- `postalFR` : 5 chiffres
- `nas` : `123-456-789` ou `123 456 789`
- `creditCard` : 4×4 chiffres séparés
- `date` : français + ISO + EU
- `money` : avec milliers (espace, NBSP, virgule), décimales, devise avant/après
- `properName` : 2 mots commençant par majuscule (avec accents et tirets)

## Données fictives (générateur)

Objet `FakeData` (`app.js:17`) – pool québécois :
- Prénoms masculins (20) / féminins (20) / noms (20)
- Villes québécoises (10), rues (10)
- Domaines email fictifs (5)
- Noms d'entreprises (10), projets (10)

Génération de noms : choix aléatoire OU forcé par le sélecteur de genre (`👨 / 👩 / 🎲`).
Génération de dates : préserve le format d'origine, décale de ±30 jours pour rester réaliste.

## Système de règles

Chaque règle est un objet :
```js
{
  id: 'rule_<timestamp>_<random>',
  original: 'Jean Dupont',
  replacement: 'Pierre Tremblay',
  category: 'identity',
  exceptions: 'Royale, Royal',     // mots à NE PAS remplacer (CSV)
  isMainRule: true,                 // identité complète
  isSubRule: false,                 // variante (prénom ou nom seul)
  parentId: null
}
```

### Variantes automatiques pour `identity`
Quand on enregistre `Jean Dupont` → `Pierre Tremblay`, 3 règles sont créées :
1. `Jean Dupont` → `Pierre Tremblay` (règle principale)
2. `Jean` → `Pierre` (sous-règle)
3. `Dupont` → `Tremblay` (sous-règle)

Code : `addIdentityRules()` (`app.js:872`).

### Exceptions
Champ CSV. Exemple : règle `Royal` avec exceptions `Royale, Leroy` → ne remplace pas `Banque Royale` ni `Mme Leroy`. Logique dans `isException()` (`app.js:613`) qui reconstitue le mot complet entourant le match.

### Tri & remplacement
Les règles sont triées par longueur décroissante de `original` avant remplacement, pour éviter qu'une sous-règle (`Jean`) écrase la règle principale (`Jean Dupont`). Voir `updateAnonymizedText()` (`app.js:632`).

### Bornes intelligentes (`createBoundedRegex`)
Construit une regex avec `\b` ou `(?<!\w)` selon que le pattern commence/finit par un caractère « word » → évite les faux positifs (`Pierre` ne matche pas dans `Pierrette`).

## Import / Export

- **Export** : génère un JSON `{ version: '2.0', exportDate, rules: [...] }` téléchargé sous `anonymisation_YYYY-MM-DD.json`.
- **Import** : ajoute les règles importées aux règles existantes (pas de remplacement).

## UI / UX

- **3 étapes** navigables en haut (`.steps`)
- **Sidebar droite** : liste des règles + menu actions (export / import / tout effacer) + stats (nb règles, nb remplacements)
- **Surlignage in-line** dans la zone source : chaque match est entouré d'un `<span class="highlight-inline <category>">` avec couleur par catégorie
- **Badges de détection** cliquables sous la zone source → ouvrent la modale pré-remplie
- **Modale règle** : adapte ses champs selon la catégorie (identité = 4 champs prénom/nom, autres = original + remplacement + suggestions)
- **Bouton 🎲 « Autre suggestion »** : régénère 4 variantes fictives via `generateVariants()`
- **Modale de confirmation** custom (jamais `confirm()` natif) — respecte la règle Memora globale
- **Toasts** custom en bas à droite (succès / erreur / warning / info)
- **Mode pleine page** : bouton `⛶` qui agrandit l'app
- **Bannière info** dismissable rappelant que tout reste local

## Restauration (étape 3)

`restoreOriginalData()` (`app.js:759`) :
1. Récupère la réponse IA collée dans le textarea
2. Trie les règles par longueur **de `replacement`** décroissante (inverse de l'anonymisation)
3. Remplace chaque `replacement` par son `original` avec la même regex bornée
4. Bascule automatiquement sur l'onglet « Texte restauré »

## Protection d'accès (`_protection.php`)

- Lit `APP_KEY`, `APP_URL`, `APP_NAME` depuis le `.env` Laravel (2 niveaux au-dessus)
- Vérifie le `_token` GET signé contenant le `tool_id`
- Si invalide → page d'erreur avec compte à rebours de 5 s puis redirection vers `/login`
- L'outil est donc accessible **uniquement** depuis un lien généré par le portail Filament (resource `Tool`)

## Points d'attention pour modifications futures

- **Pas de framework JS** : tout est en vanilla, manipulation directe du DOM. Toute refonte impliquerait soit de garder ce style, soit d'introduire Alpine/Vue (à éviter — règle « keep it simple »).
- **Pas de tests** : aucune couverture Playwright/Jest sur cet outil.
- **Risque sur `properName`** : la regex de détection des noms est très large (2 mots majuscules) → produit beaucoup de faux positifs (titres de sections, début de phrase). À améliorer avec une liste de mots exclus ou NLP léger.
- **localStorage non chiffré** : les règles contiennent les données originales en clair côté navigateur. Acceptable car 100 % local, mais à documenter pour l'utilisateur.
- **Pas de versioning des règles importées** : `version: '2.0'` n'est pas vérifié à l'import → si on change le schéma, prévoir une migration.
- **Bouton « Tout effacer »** : passe par `showConfirmModal()` custom (jamais `confirm()` natif, conformément à la règle Memora).
- **Aucune popup native nulle part** (`alert`, `confirm`, `prompt`) — vérifier avant chaque commit avec `grep -E 'confirm\(|alert\(|prompt\(' app.js`.

## Cas d'usage typique

> Un utilisateur veut résumer le dossier médical de « Marie Tremblay, NAS 123-456-789, demeurant au 45 rue Sainte-Catherine, Montréal » avec ChatGPT.
>
> 1. Il colle le texte → clique **Détecter** → 3 badges apparaissent (nom, NAS, adresse).
> 2. Il clique sur chaque badge → règles créées automatiquement avec données fictives québécoises.
> 3. Il copie le texte anonymisé (« Pierre Gagnon, NAS 987-654-321, demeurant au 234 boulevard René-Lévesque, Québec »).
> 4. Il l'envoie à ChatGPT → reçoit un résumé.
> 5. Il colle le résumé dans l'étape 3 → clique **Restaurer** → le résumé est restauré avec les vraies infos.

---

## Améliorations recommandées (veille mai 2026)

Synthèse issue d'une veille Perplexity sur les tendances et meilleures pratiques 2026 (Microsoft Presidio, OpaquePrompts, NIST FF1/FF3-1, Langfuse, CAI Québec, Grid Dynamics, K2View, Fortanix).

### Vocabulaire à corriger en priorité

L'outil s'appelle « Anonymiseur » mais réalise en réalité de la **pseudonymisation** (réversible via table de correspondance). Sous Loi 25 et RGPD, l'anonymisation est **irréversible**. Implication directe : ajouter un mode redaction strict (`[REDACTED]`) qui produit de vraies données anonymes, et clarifier le vocabulaire dans l'UI pour éviter une fausse promesse juridique.

### Tendances clés 2026 à intégrer

- **Pipelines hybrides** : NER + regex + checksums + règles contextuelles (modèle Microsoft Presidio)
- **Client-side AI** : modèles NER légers embarqués via WebGPU/WASM (DistilBERT, spaCy ONNX)
- **Format-Preserving Encryption** (FF1/FF3-1 NIST) : pseudonymisation déterministe préservant format
- **Privacy by design** : audit logs, masking fonctionnel, vocabulaire juridique strict
- **Tokenization vaultless** : génération déterministe sans table de correspondance externe
- **Observabilité LLM** : Langfuse-like pour tracer ce qui est masqué (sans ré-exposer la PII)

### 15 améliorations notées /100

| # | Amélioration | Note | Justification |
|---|---|---|---|
| 1 | **Règles contextuelles** : booster le score si mots-clés (« NAS », « patient », « n° dossier ») entourent un match | **95** | Effort minimal (~50 lignes), gain énorme en précision, pattern Presidio éprouvé. Réduit drastiquement les faux positifs sans dépendance NER. |
| 2 | **WCAG 2.2 AA** : audit contraste, focus visible, ARIA labels, navigation clavier de la modale et des badges | **92** | Règle Memora absolue + obligation légale 2026. Modale custom, `contenteditable`, badges cliquables = points sensibles. |
| 3 | **NER léger côté client** (DistilBERT-NER compilé ONNX-WASM, lazy-loaded) | **90** | Vraie tendance 2026 (Presidio + Grid Dynamics WebGPU). Élimine ~80 % des faux positifs `properName`. Coût : ~30 Mo en lazy-load. |
| 4 | **Validation Luhn** sur cartes CB + **checksum NAS canadien** | **90** | 10 lignes de code, supprime ~90 % des faux positifs sur identifiants numériques. Standard industrie sécurité. |
| 5 | **Score de confiance** par détection (0.0–1.0) + slider UI threshold | **88** | Pattern Presidio standard. Chaque match scoré selon source (regex strict, contexte, NER). UX considérablement améliorée sur `properName`. |
| 6 | **Round-trip consistency check** avant copie vers IA (`anonymize → restore == original`) | **88** | Détecte les règles non-bidirectionnelles avant que l'utilisateur ne fuite des données. Garantie de sécurité simple à implémenter. |
| 7 | **Tests Playwright + unitaires** : 3 étapes E2E + regex + corpus faux positifs/négatifs | **88** | Outil de **sécurité** = tests obligatoires. Couverture actuelle nulle = risque inacceptable. Règle `/dev-workflow` Memora. |
| 8 | **Audit log local** (type + timestamp des masquages, **jamais la PII**) | **87** | Exigence Loi 25 « auditabilité ». Conforme aux meilleures pratiques reconnues. Preuve en cas d'incident. ~30 lignes. |
| 9 | **Modes de masquage par catégorie** : pseudonymisation / hash SHA-256 / redaction `[REDACTED]` / FPE | **85** | Aligne avec « data masking dynamique » 2026 (K2View, Fortanix). Permet vraie anonymisation Loi 25 (irréversible) ET pseudonymisation au choix utilisateur. |
| 10 | **Mode redaction irréversible** distinct + vocabulaire juridique correct dans l'UI | **85** | Critique pour la conformité : seule la redaction est « anonymisation » au sens Loi 25/RGPD. Évite la fausse promesse juridique actuelle. |
| 11 | **Chiffrement localStorage** via Web Crypto API (AES-GCM + passphrase utilisateur, toggle optionnel) | **82** | Les règles contiennent les vraies PII en clair sur disque. Critique Loi 25 si appareil volé/partagé. Friction UX → optionnel. |
| 12 | **Export/import chiffré** (AES-GCM passphrase) + signature HMAC | **80** | Le JSON exporté actuel = leak total des PII. Critique pour partage entre collègues sans risque. |
| 13 | **PWA offline-first** (Service Worker + manifest + install prompt) | **78** | Renforce la promesse « 100 % local ». Usage hors-ligne complet. Effort modeste, gros impact perception sécurité. |
| 14 | **Détection dates relatives** (« il y a 3 ans », « le mois dernier », « né en automne 1985 ») | **75** | Comble une lacune réelle d'identification indirecte. Mais transformation cohérente complexe (offset partagé entre dates). |
| 15 | **Patterns régionalisés multi-pays** (IBAN, SIRET, SSN US, NHS UK) en plugins | **72** | Étend l'audience hors QC/CA. Moins prioritaire pour portail québécois. Architecture en plugins recommandée pour découplage. |

### Quick wins recommandés (cycle 1)

Les améliorations **#1, #4, #6, #7, #8** offrent le meilleur ratio impact/effort et n'imposent aucune dépendance externe. Cycle 1 réaliste en 1–2 jours de dev :
1. Règles contextuelles (#1) → précision détection +30 %
2. Luhn + checksum NAS (#4) → faux positifs identifiants -90 %
3. Round-trip check (#6) → sécurité utilisateur garantie
4. Tests Playwright (#7) → non-régression
5. Audit log (#8) → conformité Loi 25

### Cycle 2 (mois suivant)

Améliorations **#2, #5, #9, #10, #11, #12** pour conformité juridique et UX. Effort modéré, impact fort sur la crédibilité de l'outil.

### Cycle 3 (R&D)

Améliorations **#3, #13, #14, #15** pour différencier l'outil sur le marché 2026. Effort important, impact stratégique. À évaluer en fonction du roadmap produit Memora.
