# Matrice de couverture - audit ultra exhaustif de laveille.ai

Lancé le 2026-08-25 (America/Toronto). Portée : **complet** (aucune dimension retirée).
Cible : laveille.ai (production, lecture seule) + dépôt local @ ec5195e3 (v1.219.0).
Volume : 4 913 fichiers PHP, 54 modules nwidart, 942 routes, 1 178 vues Blade, 542 migrations.

| Dimension | Statut | Preuve / justification |
|---|---|---|
| securite-applicative | complété | 10 classes OWASP 2025 + 3 classes LLM toutes conclues ; XSS stocké REPRODUIT (ligne 1114, `<img onerror>` intact) ; RCE signalée ÉCARTÉE par test (published_at jamais renseigné) ; endpoint `public/_lvgit.php` audité et testé (403 sans jeton et avec jeton invalide, `proc_open` en tableau donc aucune injection, seeder filtré par liste blanche) |
| securite-infra | complété | `sec_full_audit` : en-têtes grade A (7/8), HSTS, CSP, X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy tous présents ; SPF + DMARC (p=quarantine) + DKIM configurés ; .env, .git/config, logs, vendor, storage tous en 404 vérifiés par requête réelle |
| qualite-code-DRY | complété | Trait `NotifiesIndexNow` importé SANS garde dans 4 modèles (vérifié) alors que 11 modules sont réellement désactivés ; 15 `onclick="return confirm("` dans 12 fichiers alors que 53 `data-confirm` conformes existent ; 4 méthodes mortes ; clé `app.frontend_theme` inexistante |
| performance | complété | Mesure réelle accueil : TTFB 372 ms, DOM interactif 604 ms, chargé 885 ms, 77 requêtes, 2 397 Ko (images 998 Ko, styles 839 Ko, scripts 535 Ko) |
| accessibilite | complété | WCAG 2.2 AA accueil + fiche annuaire ; faux positifs tranchés au navigateur (skip link conforme : 233x51 px au focus ; titres blancs posés sur image sombre, lisibles) ; défauts réels retenus : cible 1x1 du honeypot, focus order partiel |
| UX-UI | complété | Capture réelle : bandeau de témoins ET modale d'infolettre simultanés ; 2 bandeaux empilés au-dessus du titre |
| SEO-GEO-AEO | complété | Effondrement daté au 19 juillet 2026 (481 impressions le 18, 46 le 19) ; technique saine (index+follow, canonical, sitemap 3 599 URL, `Submitted and indexed`) ; volatilité SERP externe confirmée les 18-19 juillet |
| conformite-Loi25-RGPD | complété | AdSense chargé SANS consentement : REPRODUIT (10 requêtes publicitaires émises, bandeau encore affiché, consentement absent) ; registre d'incidents Loi 25 absent ; double opt-in infolettre et purges automatiques conformes |
| tests-couverture | complété | Suite complète : **6 485 tests, 0 échec** ; 3 modules sans aucun test (Ads, Community, Voting) ; 41/42 fichiers de tests JS au vert (1 orphelin préexistant : sa source a été supprimée par le revert fad32772) |
| dependances-CVE-licences | complété | `composer audit` : 9 avis (5 hautes) sur guzzle, league/commonmark, sodium_compat ; `npm audit` : 10 avis (9 hauts) ; licences toutes permissives, aucun GPL/AGPL |
| hygiene-serveur | complété (partiel externe) | Local : `opcache_reset.php` et `audit-console-errors.log` résiduels. Prod par HTTP : aucun fichier sensible exposé (10 chemins testés). **Blocage externe** : MCP cPanel et memora-multi renvoient un interstitiel de chargement / "Could not fetch WHM accounts" - la liste des crons prod n'a pas pu être lue. Pour débloquer : ouvrir une session cPanel valide puis relancer `cpanel_cron_list`. |
