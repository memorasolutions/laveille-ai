# Audit — domaines en dur (laveille.ai / laveilledestef.test) — 2026-06-19

Déclencheur : lien « Se connecter » du pied de page affichant `laveilledestef.test` (local). 
**Verdict : NON-BUG.** Le footer (`Modules/FrontTheme/resources/views/partials/footer.blade.php:125`) utilise `route('login')` = env-aware (local→laveilledestef.test, prod→laveille.ai). **Aucun code de prod ne fige `laveilledestef.test`** (seules occurrences = commandes legacy d'import WordPress `laveilledestef.com`, inoffensives). Donc zéro risque de fuite du domaine local en prod.

## Vrais points à corriger (P0/P1)
- **P0** `config/statut.php:102` — fallback `'https://memora.solutions'` → devrait être `env('APP_URL', …)`.
- **P0/config** `.env:128` — `STATUT_BRAND_URL=https://laveille.ai` figé → `${APP_URL}` (page /statut afficherait le bon domaine selon l'env).
- **P1** `Modules/Authors/app/Services/AuthorsSitemapService.php:48,69` — `https://laveille.ai` en dur dans le sitemap → `url()`. (⚠️ module Authors DÉSACTIVÉ → inactif, mais à corriger si réactivé.)

## ~48 occurrences `https://laveille.ai` en dur (à centraliser — P1/P2)
Correctes en prod (vrai domaine) mais non env-aware. Principaux fichiers :
- JSON-LD / Schema : `Modules/Dictionary/app/Services/TermSchemaService.php` (26,29,147,166) ; `app/Helpers/jsonld.php` (fallback 33,81,121,146 — sûr si APP_URL défini).
- Courriels : `Modules/Authors/resources/views/mail/*` (5), `Modules/Shop/.../order-confirmed.blade.php:60`, `Modules/Newsletter/.../digest-weekly.blade.php:619-641` (5).
- Vues/branding : `Modules/ShortUrl/resources/views/public/*` (31,44,73), `Modules/Core/.../smart-share.blade.php` (53,84), `Modules/Authors/.../laveille-ad-banner.blade.php`, `mini-site/show.blade.php:428`.
- Outils : `Modules/Tools/.../code-qr.blade.php` (690,849 — defaults JS), `mots-croises.blade.php:252` (texte d'affichage).
- Contexte prompt IA (interne, OK) : Tool.php, Article.php, NewsArticle.php, Term.php, Acronym.php.
- Légitimes (ne pas toucher) : URLs réseaux sociaux (helpers), `laveilledestef.com` (migration WP legacy), embed Figma externe.

## Recommandation
Helper unique :
```php
if (!function_exists('app_domain')) {
    function app_domain(string $path = ''): string {
        return rtrim(config('app.url', 'https://laveille.ai'), '/').$path;
    }
}
```
Remplacer les littéraux `https://laveille.ai...` par `app_domain('...')` (sitemaps/JSON-LD/canonical/og:url en priorité). Centralise + env-aware + testable. `.env.example` : documenter `STATUT_BRAND_URL="${APP_URL}"`.

## Décision en attente (user) : A=centraliser tout (helper+48 remplacements, sur branche), B=3 points P0 seulement, C=laisser (prod correcte).
