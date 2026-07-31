# Devis Fonctionnel V2 — Outil Mots Croisés laveille.ai

**Version** : 2.0 — Adaptation architecture laveille.ai + bonifications mai 2026
**Date** : 2026-05-01
**Slug cible** : `https://laveille.ai/outils/mots-croises`
**Module** : `Modules/Tools` (intégration nwidart existante)

---

## 0. Contexte d'intégration laveille.ai (NOUVEAU)

Cet outil est intégré au pattern **outils gratuits laveille.ai** (`/outils`), aux côtés de 10 outils existants : calculatrice-taxes, code-qr, constructeur-prompts, generateur-equipes, generateur-mots-passe, liens-google, oscilloscope-rlc, roue-tirage, simulateur-fiscal, tirage-presentations.

### Architecture imposée

- **Module** : intégration `Modules/Tools` (pas de nouveau module nwidart)
- **Routes publiques** : `/outils/mots-croises` (création), `/jeu/{public_id}` (mode joueur)
- **Routes API** : `/api/tools/crossword-presets` (CRUD sauvegarde, AUTH-required)
- **Routes admin** : héritées du pattern `Tool` model existant
- **Vues Blade** : `Modules/Tools/resources/views/public/tools/mots-croises.blade.php`
- **Composants UI** : Alpine.js + composants Memora (`<x-core::alert-toast>`, `<x-core::modal>`, `<x-core::smart-share>`)

### Pattern Saved\*Preset (5 outils existants : prompts, qr, wheel, draw, team)

Le storage suit le pattern :

```
Modules/Tools/app/Models/SavedCrosswordPreset.php
- has_uuid public_id
- user_id FK
- name string max 255
- config_text TEXT (JSON pairs + grid layout)
- params JSON (metadata: difficulty, language, visibility)
- is_public boolean default false
- timestamps + soft deletes
```

Controller suit pattern `SavedQrPresetController` :

```
Modules/Tools/app/Http/Controllers/SavedCrosswordPresetController.php
- index() → JsonResponse paginate(20) forUser(auth()->id())
- store() → validate (name, config_text, params, is_public) + create
- update($publicId) → scope user + validate + update
- destroy($publicId) → scope user + delete
```

---

## 1. Présentation générale (révisée)

L'outil "Mots croisés" permet à tout visiteur (authentifié ou non) de :

1. **Créer** une grille de mots croisés à partir de paires indice/réponse
2. **Générer** automatiquement la grille via algorithme backtracking
3. **Prévisualiser** + télécharger PDFs (vierge + corrigé) avec footer sobre
4. **Sauvegarder** dans son compte (UNIQUEMENT si connecté)
5. **Partager** via lien public + embed iframe
6. **Jouer** une grille reçue en mode interactif en ligne

### Différenciateurs uniques mai 2026 (bonifications validées)

- **Bonification #1** : IA générateur d'indices automatiques (sonar-pro/qwen3-max via OpenRouter)
- **Bonification #2** : Génération thématique zero-effort (champ "Thème" → 10-20 paires auto)
- **Bonification #3** : Mode joueur en ligne avec timer + hints + completion celebration
- **Bonification #4** : Embed iframe pour Moodle/Notion/Google Classroom
- **Bonification #5** : PDF footer sobre avec logo + URL + QR code partage

---

## 2. Périmètre fonctionnel adapté

### 2.1 Accessible SANS connexion (anonyme)

- Saisie paires indice/réponse via formulaire dynamique
- Validation données saisies (règles section 5)
- Génération automatique grille (backtracking + intersections)
- **NEW** Bonification #1 : IA suggestion indices via bouton "✨ Indices IA"
- **NEW** Bonification #2 : Champ "Thème" → IA pre-fill 10-20 paires
- Prévisualisation interactive grille
- Export PDF "À compléter" + "Corrigé" avec footer sobre
- Impression directe via `window.print()`
- Brouillon localStorage (anonyme, conservation 7 jours)

### 2.2 Réservé AUTH (connecté)

- Sauvegarde grille dans compte (Saved\*Preset pattern)
- Liste personnelle grilles sauvegardées (`/admin/mes-grilles` ou `/account/grilles`)
- Modification + duplication + suppression grilles
- Génération lien public partagé `public_id` UUID + page mode joueur
- Stats vues grilles publiques (anonymes)

### 2.3 Mode joueur (NEW Bonification #3)

- URL publique `/jeu/{public_id}` accessible sans connexion
- Grille interactive remplir cases au clavier/tactile
- Timer démarré au premier clic
- Hint progressifs : 1ʳᵉ lettre / mot complet (3 hints max)
- Completion celebration animation + temps + nombre hints
- **Optionnel** : sauvegarde score local (localStorage) ou compte (auth)

---

## 3. Acteurs et rôles (révisés)

| Rôle | Description | Connexion requise |
|------|-------------|-------------------|
| **Visiteur anonyme** | Crée + génère + télécharge PDF + joue | NON |
| **Créateur connecté** | + sauvegarde + lien public + gère ses grilles | OUI |
| **Joueur en ligne** | Résout grille via lien public | NON |
| **Administrateur** | Modère contenus signalés | OUI + admin role |

---

## 4. Flux principal — Création d'une grille (adapté)

### 4.1 Accès à la fonctionnalité

URL : `https://laveille.ai/outils/mots-croises`

- Aucune connexion requise pour la création
- Page composée de :
  1. **Header outil** : titre + breadcrumb + lien "Toutes les grilles" si auth
  2. **Zone IA optionnelle (Bonification #1+#2)** : champ "Thème" + bouton "✨ Générer paires avec IA"
  3. **Zone saisie paires** : formulaire dynamique drag&drop
  4. **Zone prévisualisation grille** : panneau droit (desktop) ou bas (mobile)
  5. **Barre actions** : Générer / Sauvegarder (auth) / Exporter / Partager

### 4.2 Bouton IA "✨ Générer indices"

**NEW Bonification #1** : Pour chaque paire avec réponse remplie mais indice vide (ou via bouton global "Régénérer tous les indices") :

- Backend : appel OpenRouter `qwen/qwen3-max` ou `perplexity/sonar-pro`
- Prompt : "Génère un indice court (max 80 chars) en français québécois professionnel pour le mot '[REPONSE]' dans le contexte d'une grille de mots croisés thème '[THEME]'"
- Réponse JSON : 3 propositions d'indices différents
- UI : modal `<x-core::modal>` avec 3 indices proposés + bouton "Choisir" sur chacun
- Coût : ~$0.005/grille (10-20 paires)

### 4.3 Champ "Thème" (Bonification #2)

**NEW** : Au-dessus du formulaire de paires :

- Input "Thème de votre grille" (placeholder : "Marketing B2B 2026, Histoire du Québec, Programmation Python...")
- Bouton "✨ Pré-remplir 10-20 paires"
- Backend : appel sonar-pro avec prompt structuré
- Output JSON : array de paires `[{indice, reponse}]`
- Validation auto : len 2-30, lettres seulement, 10-20 paires
- UI : insertion auto dans formulaire + toast confirmation

### 4.4 Formulaire saisie paires (inchangé sauf drag&drop)

(Reprend section 4.2 du devis V1, mêmes règles validation)

- Drag&drop fonctionne avec **SortableJS** (pas une dépendance lourde nouvelle)
- Compteur caractères restants pour les 2 champs
- Validation temps réel via Alpine.js `x-effect`

### 4.5 Bouton "Générer la grille"

(Inchangé sauf algo)

- Backend service `CrosswordGeneratorService` (PHP backtracking)
- Async via fetch + JSON response
- Indicateur chargement spinner
- Délai cible < 3s pour 30 mots, < 8s pour 50 mots

---

## 5. Règles validation (inchangées)

(Reprend §5 du devis V1 sans modification — règles métier identiques)

---

## 6. Algorithme génération (adapté §6 V1)

(Spécifications fonctionnelles inchangées + ajout)

### 6.5 Implémentation technique imposée

- Service Laravel `Modules\Tools\Services\CrosswordGeneratorService::generate(array $pairs): array`
- Algorithme backtracking récursif avec contraintes (intersection, séparation, connexité)
- Tests Pest unitaires obligatoires : 5 cas (cas heureux, doublons, mots non plaçables, grille vide, performance 50 mots)
- Pas de dépendance externe non validée (pas de package Composer crossword)

---

## 7. Gestion erreurs (inchangée)

(Reprend §7 V1)

---

## 8. Interface prévisualisation grille (inchangée + ajout)

(Reprend §8 V1)

### 8.4 Composant Alpine.js (NEW)

- Composant `<div x-data="crosswordPreview()">` autonome
- Communication parent/enfant via `$dispatch` events
- État reactif via `Alpine.store('crossword')`
- Mode sombre auto via `class="dark:..."` (Tailwind ou custom)

---

## 9. Métadonnées grille (adapté)

| Champ | Obligatoire | Règles | Mapping DB |
|-------|-------------|--------|------------|
| **Titre** | Oui (sauvegarde) | 3-100 chars | `name` |
| **Description/consigne** | Non | max 500 chars | `params.description` |
| **Difficulté** | Non | Facile/Moyen/Difficile (auto-calculé suggestion #13) | `params.difficulty` |
| **Visibilité** | Oui | `is_public` boolean (Privé/Public+lien) | `is_public` |
| **Langue** | Oui | fr/en (default fr) | `params.language` |
| **Thème** (NEW) | Non | Texte libre 100 chars (utilisé Bonif #2) | `params.theme` |

---

## 10. Sauvegarde (révisé selon pattern Saved\*Preset)

### 10.1 Sauvegarde manuelle (auth-only)

- Bouton "Sauvegarder dans mon compte" visible UNIQUEMENT si `@auth`
- Si visiteur anonyme : remplacer par bouton "Se connecter pour sauvegarder" → modal login + retour grille post-login
- Validation backend : `name | config_text JSON | params array | is_public bool`
- API REST : `POST /api/tools/crossword-presets`
- Confirmation toast `<x-core::alert-toast>` "Grille sauvegardée ✓"

### 10.2 Brouillon localStorage (anonyme + auth)

**RETIRÉ du devis V1** : auto-save 60s + brouillon temporaire serveur 7 jours
**REMPLACÉ par** : localStorage browser uniquement

- Au moindre changement formulaire : `localStorage.setItem('crossword_draft', JSON.stringify(state))`
- Au reload page : si `crossword_draft` existe → toast "Brouillon trouvé" + bouton "Reprendre"
- Pas de sync server, pas de cron, pas de table draft
- Cohérent avec pattern existant constructeur-prompts/wheel laveille.ai

### 10.3 Reprise grille sauvegardée (auth)

- Page `/account/mes-grilles` (à créer) : liste paginée 20/page
- Action "Reprendre" → redirige `/outils/mots-croises?preset={public_id}`
- Pré-fill formulaire + grille via API GET
- Action "Dupliquer" → POST nouvelle preset avec name "Copie de [X]"
- Action "Supprimer" → confirmation modal `<x-core::modal>` + DELETE
- Action "Lien public" → toggle `is_public` + copy URL `/jeu/{public_id}`

---

## 11. Export PDF avec footer sobre (NEW Bonification #5)

### 11.1 Footer obligatoire (toutes pages PDF)

**Élément graphique imposé** :

```
┌─────────────────────────────────────────────────────┐
│ [LOGO laveille.ai 60x16px]      laveille.ai/outils  │
│                                                     │
│ Lien grille : https://laveille.ai/jeu/{public_id}   │
│                                          [QR 30x30] │
└─────────────────────────────────────────────────────┘
```

- Logo : `public/assets/logos/laveille-mark-60.png` (existant ou à créer 60×16px noir/gris #6E7687)
- Texte : "laveille.ai/outils" (police Inter 8pt grey #6E7687)
- Lien grille : visible sous le logo ligne 2 ("Lien grille : https://laveille.ai/jeu/abc123")
- QR code : généré via `endroid/qr-code` (déjà présent pour outil code-qr)
- Hauteur footer : 24mm (sobre, pas envahissant)

### 11.2 Deux PDFs (inchangé V1)

- "Grille vierge" + "Corrigé" (specs §11 V1 inchangées)
- A4 portrait par défaut, A4 paysage si > 20 colonnes
- Génération côté serveur via **DomPDF** (laravel/domppdf déjà présent ou à installer)
- Alternative : **Browsershot** (Spatie) si HTML+CSS complexe (Chrome headless)

### 11.3 Décision tech : DomPDF

- Compatible cPanel (pas de Chrome headless)
- Templates Blade dédiés : `views/public/tools/crossword/pdf-blank.blade.php` + `pdf-solution.blade.php`
- CSS print-only via `<style>` inline

---

## 12. Comportement mobile/responsive (étendu)

(Reprend §12 V1 + ajouts WCAG AAA)

- Touch optimization : drag&drop SortableJS avec touch events
- Target size 44×44px minimum (WCAG AAA target size 2.2)
- Pinch-zoom autorisé (pas de `user-scalable=no`)
- Test obligatoire : iPhone 12 + Samsung Galaxy S22 + iPad Mini

---

## 13. Accessibilité WCAG 2.2 **AAA** (élevé depuis AA)

- Contraste **7:1** minimum (charte laveille.ai)
- Validation via `mcp__wcag-mcp__wcag_check_contrast` avant commit
- Navigation clavier complète : flèches grille, Tab/Shift+Tab champs, Enter activate
- Labels ARIA explicites : `aria-label="Mot horizontal numéro 3"`
- Live regions : `aria-live="polite"` pour annonces succès/erreur
- Support NVDA + VoiceOver + TalkBack testés
- Annonce vocale lettres saisies : "P" → "P comme Paris"
- Skip-link "Aller à la grille" en haut de page

---

## 14. Performance (révisé)

- Génération grille < 3s pour 30 mots (cible)
- Cache Redis 24h sur génération identique (clé = hash MD5 paires)
- Lazy load grille via Intersection Observer si scroll
- Lighthouse score > 90 mobile/desktop (Performance, Accessibility, Best Practices, SEO)
- Image optimisation : pas d'image hors logo+QR
- CSS critique inliné : tailwind purge

---

## 15. Sécurité et contrôle d'accès (révisé)

### 15.1 Création + génération + jeu (publics, sans auth)

- Rate limiting : 30 générations / IP / heure (middleware throttle)
- Validation stricte côté serveur (Laravel FormRequest)
- Sanitization XSS : `{{ }}` Blade auto + `e()` explicit
- CSRF protection : tokens auto Laravel (sauf `/jeu/{public_id}` lecture seule)

### 15.2 Sauvegarde + gestion grilles (auth-only)

- Middleware `auth` sur routes API
- Scope `where('user_id', auth()->id())` obligatoire (pattern Saved\*Preset)
- `public_id` UUID v4 non-devinable pour lien public
- Pas d'exposition email créateur dans page joueur

### 15.3 Conformité Loi 25 Québec / RGPD

- Pas de cookies tracking dans page anonyme (banner consent existant respecté)
- Données utilisateur supprimables sur demande (`/account/delete`)
- Export grilles JSON sur demande (RGPD article 20)

---

## 16. Règles interface globales (inchangé V1)

(Reprend §16 V1)

---

## 17. Cas limites (inchangé V1)

(Reprend §17 V1)

---

## 18. Livrables programmeur (mis à jour)

À la fin du développement, livrables attendus :

### Phase 1 MVP (8-12h estimé)

1. **Migration** `2026_05_01_create_saved_crossword_presets_table.php`
2. **Model** `Modules/Tools/app/Models/SavedCrosswordPreset.php` (HasUuid, fillable, casts)
3. **Controller API** `Modules/Tools/app/Http/Controllers/SavedCrosswordPresetController.php` (pattern QR existant)
4. **Controller Public** `Modules/Tools/app/Http/Controllers/PublicCrosswordController.php` (méthodes : index page outil, generate API, play `/jeu/{public_id}`)
5. **Service** `Modules/Tools/app/Services/CrosswordGeneratorService.php` (algo backtracking + tests Pest)
6. **Service IA** `Modules/Tools/app/Services/CrosswordAiSuggestionService.php` (OpenRouter integration)
7. **Vue Blade** `Modules/Tools/resources/views/public/tools/mots-croises.blade.php`
8. **Vues PDF** `views/public/tools/crossword/pdf-blank.blade.php` + `pdf-solution.blade.php`
9. **Vue mode joueur** `views/public/tools/crossword/jeu.blade.php`
10. **Routes** `Modules/Tools/routes/web.php` + `api.php`
11. **Tests Pest** : 5 cas génération + 3 cas sauvegarde + 2 cas mode joueur
12. **Translations** `lang/fr_CA/tools.php` + `lang/en/tools.php` (clés crossword.\*)

### Phase 2 (post-validation)

- Templates pré-faits éducation/marketing/IT (Bonif #6)
- Export PNG (Bonif #7)
- Stats anonymes (Bonif #8)
- PWA (Bonif #9)
- Smart-share (Bonif #10)

---

## 19. Critères acceptation Phase 1 (révisés)

Une fonctionnalité est terminée uniquement si :

- ✅ Toutes les règles §1-17 respectées
- ✅ Tests Pest 100% verts (10 tests minimum)
- ✅ Validation Playwright sur Chrome + Firefox + Safari + iOS + Android
- ✅ 0 erreur console navigateur (post-fix S78 jQuery CDN)
- ✅ Lighthouse > 90 sur 4 axes
- ✅ WCAG 2.2 AAA validé via `wcag_check_contrast` + `wcag_audit_keyboard`
- ✅ Smoke test prod : 4/4 routes 200 OK (`/outils`, `/outils/mots-croises`, `/jeu/{test_id}`, `/api/tools/crossword-presets`)
- ✅ Bonifications #1-5 implémentées (IA indices + thème + mode joueur + embed + footer PDF)

---

## 20. Bonifications mai 2026 retenues Phase 1 (NEW)

Score moyen Phase 1 : **89/100**

| # | Bonification | Score | État |
|---|---|---|---|
| 1 | IA générateur d'indices (sonar-pro/qwen3-max) | 94 | ✅ Phase 1 |
| 2 | Thème pré-saisie auto-générée | 92 | ✅ Phase 1 |
| 3 | Mode joueur en ligne timer + hints | 88 | ✅ Phase 1 |
| 4 | Embed widget iframe (Moodle/Notion) | 85 | ✅ Phase 1 |
| 5 | Footer PDF sobre (logo + URL + QR) | 84 | ✅ Phase 1 |

Phase 2 reportée (#6-10 score moyen 78) : templates pré-faits, PNG export, stats publiques, PWA, smart-share.
Phase 3 backlog (#11-15 score moyen 62) : collaboratif temps réel, leaderboards, difficulté auto, duel, TTS indices.

---

## 21. Architecture technique récap (NEW)

```
laveille.ai/
├── Modules/Tools/
│   ├── app/
│   │   ├── Models/
│   │   │   ├── SavedCrosswordPreset.php          (HasUuid, fillable, soft deletes)
│   │   │   └── Tool.php (existant — ajouter slug 'mots-croises' DB)
│   │   ├── Http/Controllers/
│   │   │   ├── SavedCrosswordPresetController.php  (CRUD AUTH)
│   │   │   └── PublicCrosswordController.php       (page outil + generate + jeu)
│   │   └── Services/
│   │       ├── CrosswordGeneratorService.php      (algo backtracking)
│   │       ├── CrosswordAiSuggestionService.php   (OpenRouter)
│   │       └── CrosswordPdfService.php            (DomPDF + footer sobre)
│   ├── database/migrations/
│   │   └── 2026_05_01_*_create_saved_crossword_presets_table.php
│   ├── resources/views/public/tools/
│   │   ├── mots-croises.blade.php                 (page principale création)
│   │   └── crossword/
│   │       ├── pdf-blank.blade.php                (PDF vierge avec footer)
│   │       ├── pdf-solution.blade.php             (PDF corrigé avec footer)
│   │       └── jeu.blade.php                      (mode joueur public)
│   ├── routes/
│   │   ├── web.php                                (+ /jeu/{public_id})
│   │   └── api.php                                (+ /tools/crossword-presets)
│   └── tests/Feature/
│       ├── CrosswordGeneratorTest.php             (5 tests algo)
│       ├── SavedCrosswordPresetTest.php           (3 tests CRUD)
│       └── CrosswordPlayerTest.php                (2 tests mode joueur)
├── lang/
│   ├── fr_CA/tools.php                            (+ crossword.* keys)
│   └── en/tools.php
└── public/assets/logos/
    └── laveille-mark-60.png                       (60×16px logo footer PDF)
```

---

## 22. Estimation effort détaillée

| Tâche | Effort | Délégation |
|---|---|---|
| Migration + Model | 30min | qwen3-max |
| Controllers (Public + Saved) | 1h | qwen3-max |
| Service génération algo | 2h | qwen3-max + tests Pest |
| Service IA suggestions | 30min | SELF (~5 lignes config OpenRouter) |
| Service PDF + footer sobre | 1h | qwen3-max |
| Vue Blade principale + Alpine.js | 2-3h | qwen3-max + Codex review |
| Vues PDF (2) | 30min | qwen3-max |
| Vue mode joueur | 1h | qwen3-max |
| Routes + tests Pest | 1h | qwen3-max |
| Translations | 15min | qwen3-max |
| Validation Playwright multi-browser | 1h | Playwright MCP |
| Validation WCAG AAA | 30min | wcag-mcp |
| Deploy prod + smoke | 30min | cpanel + curl |
| **Total Phase 1** | **~12h** | délégation 95%, SELF supervision 5% |

---

## 23. Notes finales pour le programmeur

- **Cohérence visuelle** : utiliser tokens CSS Memora existants (`--c-orange-medium`, `--c-cream-warm`, `--c-dark`, `--c-text-muted`)
- **Composants réutilisables** : `<x-core::alert-toast>`, `<x-core::modal>`, `<x-core::smart-share>`, `<x-core::skip-link>`
- **Anti-régression** : lire pattern complet `SavedQrPresetController.php` + view `code-qr.blade.php` AVANT d'écrire première ligne
- **Méthodologie Plan→Valider→Exécuter** appliquée à chaque sous-tâche
- **Délégation MCP obligatoire** : multi-ai-mcp 1min.ai prioritaire, openrouter qwen3-max fallback
- **Tests obligatoires** : Pest unitaires + Playwright visuels avant commit
- **Validation WCAG AAA** : `wcag_check_contrast` 7:1 + `wcag_audit_keyboard` avant chaque commit
- **Smoke prod** après chaque déploiement (4 routes minimum)
- **Cron quotidien à respecter** : pas d'overlap avec autres schedules `Modules/Tools` ou `Modules/Directory`

---

*Document V2 adapté pour laveille.ai par Claude Opus session S78 — basé sur recherche openrouter sonar-pro mai 2026 + audit pattern Saved\*Preset existant.*
