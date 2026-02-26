# Observations MCP - mise à jour 2026-02-20

## Routage par tâche - observations terrain

| Modèle | Forces | Faiblesses | Taches ideales |
|--------|--------|------------|----------------|
| nvidia/nemotron-3-nano-30b-a3b:free | Fiable pour Pest v3, bonnes reponses structurees | Reponses vides si prompt trop long (>500 tokens), API spatie incorrecte | Tests Pest, code boilerplate simple |
| qwen/qwen3-coder:free | Excellent code, grand contexte 262K | 429 FREQUENT (quota quotidien epuise vite) | Generation code quand disponible |
| moonshotai/mimo-v2-flash:free | 73.4% SWE-Bench | Erreur 400 avec prompts > 300 tokens | Debugging court, fix < 300 tokens |
| meta-llama/llama-3.3-70b-instruct:free | Polyvalent | 429 frequent en heures de pointe | Code general quand disponible |
| stepfun/step-3.5-flash:free | - | Erreur 405 (modele souvent indisponible) | Eviter |
| openrouter/free | Auto-selection | Style PHPUnit au lieu de Pest, imprevisible | Fallback Q&A uniquement |

## Observations critiques - Phase 128

### Probleme : API specifiques de librairies
Les modeles gratuits OpenRouter ne connaissent PAS correctement les API de librairies specifiques comme spatie/laravel-model-states v2. 2 tentatives (nemotron + qwen) ont produit du code utilisant une API inexistante (registerStates, allowTransitions en tant que methodes statiques au lieu de config() -> StateConfig).

**Regle** : pour du code dependant d'une API specifique de librairie, lire d'abord le vendor source, puis ecrire le code directement si < 20 lignes boilerplate. OpenRouter ne peut pas etre fiable pour ces cas.

### Saturation des modeles gratuits
En session intensive (4+ appels rapides), TOUS les modeles gratuits peuvent etre satures simultanement (429 sur qwen, llama, 400 sur mimo, 405 sur stepfun). Dans ce cas, ecrire directement si < 20 lignes.

### Fiabilite par heure
- Matin (EST) : meilleure disponibilite des modeles gratuits
- Apres-midi/soir : saturation frequente, prevoir fallback

## Observations - Phase 147 (2026-02-22)

| Modèle | Résultat | Notes |
|--------|----------|-------|
| nvidia/nemotron-3-nano-30b-a3b:free | Réponse VIDE (tests), OK (DiffService court) | Fiable seulement pour prompts courts (<200 tokens) |
| qwen/qwen3-coder:free | 429 | Quota épuisé rapidement |
| moonshotai/mimo-v2-flash:free | 400 | Prompt trop long (>300 tokens) |
| deepseek/deepseek-r1-0528:free | Code généré mais namespaces/signatures INCORRECTS | Connaît mal les architectures modulaires nwidart |
| deepseek/deepseek-v3-0324:free | 400 | Prompt trop long |
| **deepseek/deepseek-v3.2-20251201** | **EXCELLENT** (tests + factory) | **0.25$/M, MEILLEUR rapport qualité/prix, comprend Pest v3 et modules** |

**Conclusion Phase 147** : deepseek-v3.2 (low-cost) est le MEILLEUR modèle pour tests Pest v3 modulaires. Nemotron fiable uniquement pour du code court. Deepseek-r1 gratuit produit du code structurellement correct mais avec mauvais namespaces.

## Strategie optimale

1. **Code dependant d'API specifique** (spatie, filament, etc.) : lire vendor + ecrire directement
2. **Code generique** (CRUD, tests, controllers) : OpenRouter gratuit (nemotron > qwen > mimo)
3. **Si tous satures** : ecrire directement avec justification
4. **Tests Pest v3** : nemotron ou qwen (pas openrouter/free qui fait du PHPUnit)
