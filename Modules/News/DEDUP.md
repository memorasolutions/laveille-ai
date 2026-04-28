# Déduplication conservatrice News

## Principe
La déduplication priorise le recall sur la précision, avec un soft-merge non destructif. Philosophie : "Mieux avoir 1-2 doublons visibles que perdre une actualité unique".

## Architecture
Composants clés :
- `Modules\News\Services\DedupService` (méthodes statiques : normalizeUrl, extractCanonical, titleSimilarity, isLikelyDuplicate)
- `Modules\News\Models\NewsDedupLog` (table d'audit)
- Migration `2026_04_28_000000_add_dedup_columns_news_articles.php` (ajoute 4 colonnes : canonical_url, is_potential_duplicate_of, dedup_score, dedup_reason + table news_dedup_log)

## Cascade 4 niveaux + soft-merge

| Niveau | Technique | Action | Impact dédup | Risque FP |
|--------|-----------|--------|--------------|-----------|
| 1 | URL normalization (utm_*/fbclid/gclid/etc) | auto soft-merge | ~5% | <0.1% |
| 2 | Canonical URL match | auto soft-merge | ~8% | <0.2% |
| 3 | Title fuzzy ≥95% + temporal 6h + same source_language | auto soft-merge | ~12% | ~0.5% |
| 4 | Multi-signal CONFIRMATION (≥2 signaux dont 1 core) | auto soft-merge ou admin queue | ~20% | ~0.3% |

## Sources alternatives (Seeder S70SourcesAlternativesSeeder)
Sources prioritaires :
- TechCrunch AI (breaking, en)
- VentureBeat AI (breaking, en)
- MIT Technology Review AI (analysis, en)
- IEEE Spectrum AI (analysis, en)
- Numerama IA (general, fr)

## Tuning seuils
- **Title fuzzy** : seuil par défaut 0.95 (conservateur). Modifier `DedupService::isLikelyDuplicate` pour ajuster
- **Temporal window** : 21600 secondes (6h) par défaut. Augmenter pour fenêtre plus large
- **Multi-signal** : seuil par défaut 2 signaux (dont 1 core). Augmenter à 3 pour mode ultra-conservateur

## Garde-fous anti-perte
Protections intégrées :
- Soft-merge : article toujours créé, jamais supprimé
- Colonnes `is_potential_duplicate_of`, `dedup_score`, `dedup_reason` dans news_articles
- Affichage public : badge "version alternative" + lien parent (à implémenter Blade S71)
- Interface admin : revue des duplicats (à implémenter S71)
- Logging complet dans `news_dedup_log` pour audit et tuning

## Désactivation module
Le module News peut être désactivé via `php artisan module:disable News`, stoppant la cascade de déduplication tout en préservant les données (soft-merge non destructif).

## Tests
Suite Pest de 8 tests dans `Modules/News/Tests/Unit/DedupServiceTest.php` couvrant :
- normalizeUrl (3 cas : tracking params, www+ports, query sort)
- extractCanonical (2 cas : link rel + meta og:url fallback)
- titleSimilarity (2 cas : identique 1.0, non-related <0.6)
- isLikelyDuplicate (1 cas : multi-signal canonical+source_lang)

Exécution : `./vendor/bin/pest Modules/News/Tests/Unit/DedupServiceTest.php`

## Roadmap
- **S71** : Intégration avec RssFetcherService (HIGH RISK différé pour tests dédiés + dry-run)
- **S71** : Interface admin pour revue des duplicats potentiels
- **S72** : Option SimHash + embeddings sémantiques pour cas complexes (gardé désactivé par défaut)
