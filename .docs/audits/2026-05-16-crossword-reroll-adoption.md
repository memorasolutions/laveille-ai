# Audit adoption — Crossword Re-roll (S79 + 2 semaines)

| Champ | Valeur |
|---|---|
| **Date d'audit** | 2026-05-16 |
| **Période analysée** | 2026-05-02 → 2026-05-16 (14 jours) |
| **Commits S79** | `a8aa7416` · `0bd3057d` (master, déployés 2026-05-02) |
| **URL cible** | https://laveille.ai/outils/mots-croises |
| **Event GA4** | `crossword_reroll` |
| **Agent** | scheduled S79+2sem (2026-05-16) |

---

## 1. Requêtes à exécuter dans GA4

> Coller dans **GA4 Explorer → Exploration libre** ou via `mcp__ga4__ga4_run_report` (accès MCP local requis).

### Q1 — Nombre total d'événements `crossword_reroll`

```json
{
  "dateRanges": [{ "startDate": "2026-05-02", "endDate": "2026-05-16" }],
  "metrics": [{ "name": "eventCount" }],
  "dimensionFilter": {
    "filter": {
      "fieldName": "eventName",
      "stringFilter": { "matchType": "EXACT", "value": "crossword_reroll" }
    }
  }
}
```

**Réponse attendue :** `eventCount` → entier N.

---

### Q2 — Ratio reroll / page_view sur `/outils/mots-croises`

Étape A — page_views sur la page :

```json
{
  "dateRanges": [{ "startDate": "2026-05-02", "endDate": "2026-05-16" }],
  "metrics": [{ "name": "screenPageViews" }],
  "dimensionFilter": {
    "filter": {
      "fieldName": "pagePath",
      "stringFilter": { "matchType": "BEGINS_WITH", "value": "/outils/mots-croises" }
    }
  }
}
```

Étape B — calculer manuellement :

```
ratio = Q1_eventCount / Q2_pageViews × 100   (en %)
```

---

### Q3 — Distribution `words_count` (médiane + p90)

```json
{
  "dateRanges": [{ "startDate": "2026-05-02", "endDate": "2026-05-16" }],
  "metrics": [{ "name": "eventCount" }],
  "dimensions": [{ "name": "customEvent:words_count" }],
  "dimensionFilter": {
    "filter": {
      "fieldName": "eventName",
      "stringFilter": { "matchType": "EXACT", "value": "crossword_reroll" }
    }
  },
  "orderBys": [{ "dimension": { "dimensionName": "customEvent:words_count" } }]
}
```

Calculer médiane + p90 à partir de la distribution retournée.

---

### Q4 — Sessions uniques avec ≥ 1 reroll

```json
{
  "dateRanges": [{ "startDate": "2026-05-02", "endDate": "2026-05-16" }],
  "metrics": [{ "name": "sessions" }],
  "dimensionFilter": {
    "andGroup": {
      "expressions": [
        {
          "filter": {
            "fieldName": "eventName",
            "stringFilter": { "matchType": "EXACT", "value": "crossword_reroll" }
          }
        }
      ]
    }
  }
}
```

> Note : GA4 ne filtre pas nativement "sessions avec ≥1 event". Ce chiffre est une approximation (sessions dans lesquelles l'event a été déclenché au moins une fois).

---

## 2. Résultats

> **À remplir manuellement après exécution des requêtes GA4.**

| Métrique | Valeur | Seuil |
|---|---|---|
| Total `crossword_reroll` (14j) | **TBD** | ≥ 30 → Phase 3 |
| Page views `/outils/mots-croises` (14j) | **TBD** | — |
| Ratio reroll / page_view | **TBD %** | ≥ 5 % → Phase 3 |
| Médiane `words_count` | **TBD** | — |
| p90 `words_count` | **TBD** | — |
| Sessions uniques avec ≥ 1 reroll | **TBD** | — |

---

## 3. Décision

```
SI (total_events >= 30) OU (ratio >= 5 %):
    → RECOMMANDER Phase 3 — Simulated Annealing (~6 h dev)
SINON:
    → MARQUER 'validée, pas urgente' — reprendre à S79+2 mois si besoin
```

**Décision actuelle :** `TBD` (en attente données GA4)

---

## 4. Si Phase 3 recommandée — tâches & effort estimé

| # | Tâche | Fichier / composant | Effort |
|---|---|---|---|
| 4.1 | Ajouter méthode `generateWithSimulatedAnnealing()` dans `CrosswordGeneratorService.php` | `Modules/Tools/app/Services/CrosswordGeneratorService.php` | 3 h |
| 4.2 | Calibrer paramètres température initiale, cooling rate, iterations max (tests empiriques sur corpus 5–20 mots) | idem | 1 h |
| 4.3 | Basculer l'appel dans la méthode `regenerate` (Alpine) vers le nouvel endpoint ou enrichir le contrôleur existant | `Modules/Tools/resources/views/public/tools/mots-croises.blade.php` | 30 min |
| 4.4 | Écrire tests Pest : score placement ≥ score baseline greedy, pas de régression temps réponse (<3 s p95) | `tests/Feature/Tools/CrosswordSimulatedAnnealingTest.php` | 1 h |
| 4.5 | Deploy + smoke test prod, vérifier event `crossword_reroll` toujours déclenché | — | 30 min |
| **Total** | | | **~6 h** |

### Notes algorithme Simulated Annealing

- **Score analysé (S79, note 71/100)** : améliore la densité de placement de ~18 % vs greedy sur mots > 10, au prix d'un temps de génération ×2–3.
- **Paramètres de départ suggérés** : `T₀ = 100`, `cooling = 0.995`, `max_iter = 5000` — à valider sur benchmarks locaux.
- **Fallback** : si SA dépasse timeout (>3 s), rétrograder vers algorithme greedy actuel.

---

## 5. Références

- Commits S79 : [`a8aa7416`](https://github.com/memorasolutions/laveille-ai/commit/a8aa7416) · [`0bd3057d`](https://github.com/memorasolutions/laveille-ai/commit/0bd3057d)
- Event câblé dans : `Modules/Tools/resources/views/public/tools/mots-croises.blade.php` (méthode Alpine `regenerate`)
- Paramètres GA4 event : `event_category=tools`, `event_label=mots-croises`, `words_count=N`
