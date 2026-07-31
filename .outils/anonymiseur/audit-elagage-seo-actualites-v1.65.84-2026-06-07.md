# Audit complet — Système d'élagage SEO des actualités (v1.65.84)

Date : 2026-06-07 · Méthode : revue de code indépendante (superagent gemini, codex KO) + analyse correctness (superviseur) + confrontation aux best practices SEO mai 2026 (pp_search) + vérification des effets de bord (traits/observers).

## Note globale : **78/100** — socle solide, sûr et 100 % réversible ; perfectible sur le signal de décision (mono-source), la propagation IndexNow, l'observabilité et les tests.

## Notes par dimension

| Dimension | Note | Verdict |
|---|---|---|
| Correctness & robustesse | **90** | `chunkById()` SÛR (pas de saut malgré le WHERE muté — avance par id, lignes traitées déjà < lastId). Idempotent. `gone`-après-`noindex` = 2 UPDATE (mineur). NULL `views_count`/`pub_date` : risque faible (colonnes non-nulles, défaut 0). |
| Sécurité | **95** | Pas d'injection (requêtes paramétrées), mass-update borné, tier 410 désactivé par défaut, réversible. |
| Réversibilité / rollback | **98** | Flag DB (aucune suppression), `--reset`, `down()`, tag git `backup-pre-news-seo-prune-v1.65.83`. Excellent. |
| Architecture / CORE | **95** | Module nwidart, config `seo_prune.php` (zéro hardcode), désactivable, planifié via le scheduler EXISTANT (0 cron ajouté). |
| Performance / scale (17 763) | **92** | Index sur `seo_status`, `chunkById(1000)`, `DB::table()->update()` (évite 1000 Eloquent), count queries OK. |
| Rendu robots | **95** | Ordre `@extends`→`@section('page_noindex')` garantit `View::hasSection()` au rendu du `<head>`. `noindex, follow` (bonne directive). Validé end-to-end en local + prod. |
| **Conformité best-practice SEO 2026** | **60** | Signal LIMITÉ : vues internes (`views_count`) + âge. La grille mai 2026 exige du **multi-signal** (GSC clics/impressions + fenêtres 30/90/365 j + analytics + éditorial), une **whitelist** de rubriques, une protection du **maillage interne**, et des **batchs contrôlés + monitoring GSC**. Atténué : conservateur (12 mois), réversible, 0 candidat actuel. |
| **Propagation / déindexation (IndexNow)** | **50** | Le `DB::table()->update()` **bypasse le trait `NotifiesIndexNow`** → les moteurs ne sont PAS notifiés du `noindex` → déindexation passive (plus lente), alors que c'est l'objectif même du système. |
| **Observabilité / traçabilité** | **40** | Cron → `/dev/null` ET bypass de `LogsActivityStandard` → **aucune trace** de ce qui a été élagué chaque mois. |
| **Tests automatisés** | **20** | ABSENTS pour une mutation de masse impactant le SEO. |

## Points forts confirmés
- `chunkById` correct (pas de saut de lignes) — confirmé par la revue indépendante.
- 100 % réversible, aucune suppression physique, rollback triple (reset / down / tag).
- Piloté par config (zéro hardcode), module désactivable (CORE).
- `noindex, follow` (l'autorité circule), exclusion du sitemap, tier 410 désactivé par défaut (prudent).
- Aucun cron ajouté (réutilise le scheduler `schedule:run` existant).

## Findings priorisés
- **P1 — Bypass IndexNow** : le pruning ne notifie pas les moteurs → déindexation plus lente. (effet de bord du DB::table mass-update qui contourne le trait `NotifiesIndexNow`).
- **P1 — Aucune observabilité** : impossible de savoir ce qui a été élagué (cron muet + log d'activité contourné).
- **P1 — Signal mono-source** : décision sur les vues internes seules ; risque de noindexer une page à 0 vue interne mais qui gagne des impressions Google (la donnée GSC a montré des news qui rankent). Best-practice = multi-signal.
- **P2 — Pas d'auto-healing** : une news redevenue performante reste `noindex` jusqu'à un `--reset` manuel.
- **P2 — Aucun test automatisé**.
- **P3 — NULL** (défensif, faible impact car colonnes non-nulles).

## Recommandations d'amélioration — NOTÉES /100 (priorité = valeur × alignement best-practice × faible effort)

| # | Recommandation | Note | Justification |
|---|---|---|---|
| **R1** | **Logging + notification IndexNow groupée** après chaque run (Log::info des comptes + `IndexNow::notify()` sur les URLs passées en noindex) | **90** | Corrige 2 P1 d'un coup (traçabilité + déindexation rapide = l'objectif SEO). Faible effort, forte valeur, aligné mai 2026 (monitoring). |
| **R2** | **Signal multi-source : intégrer GSC** (impressions/clics, fenêtres 30/90/365 j) en plus des vues internes | **85** | Le plus aligné best-practice 2026 (« jamais sur un seul signal »). Évite de désindexer une page qui gagne des impressions Google. Effort + (câbler GSC dans un job). |
| **R3** | **Auto-healing** : la commande remet `seo_status=index` si une news redevient performante (vues ≥ seuil / impressions GSC) | **78** | Symétrie + évite de pénaliser un regain de trafic. Effort faible (une requête inverse). |
| **R4** | **Whitelist par rubrique + protection du maillage interne** (ne jamais élaguer certaines catégories ni les pages très liées en interne) | **72** | Garde-fou « faux négatifs » de la grille 2026. Effort moyen (compter les inlinks). |
| **R5** | **Test automatisé** (feature test : 13 mois/10 vues → noindex ; performante → index ; `--reset`) | **70** | Filet de sécurité pour une mutation de masse SEO. Effort faible. |
| **R6** | **Batchs contrôlés + monitoring GSC** (vagues au lieu d'un big-bang mensuel, surveiller l'index coverage) | **65** | Best-practice anti-« mass pruning ». Pertinent quand le volume de candidats deviendra grand (pas urgent : 0 candidat aujourd'hui). |
| **R7** | **Robustesse NULL** (`whereNotNull('pub_date')`, NULL views = 0) | **40** | Défensif ; faible impact réel (colonnes non-nulles, défaut 0). |

## Verdict
Le système est un **MVP sûr, réversible et bien architecturé (78/100)**, déployé sans risque (0 candidat actuel, news < 12 mois). Pour atteindre le niveau « best-practice média 2026 », les 3 priorités sont **R1 (logging + IndexNow)**, **R2 (multi-signal GSC)** et **R3 (auto-healing)**. R1 + R5 sont des quick wins à faible effort et fort impact.

Sources best practices : screamingfrog.co (data-driven content pruning), adimeo, searchengineland, moov-up, netoffensive (pp_search, mai 2026).
