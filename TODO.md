# TODO - Laravel SaaS Boilerplate

**Derniere mise a jour** : 2026-02-26 (session RBAC)
**Voir aussi** : PROGRESS_REPORT.md (rapport complet)

---

## Completes (sessions recentes)

- [x] Commit massif 4e0500c (4835 fichiers, 584K insertions)
- [x] Audit hardcode wowdash, docs obsoletes, vues orphelines (-3577 lignes)
- [x] Decouplage Core, code partage, PHPDoc API Scramble
- [x] Fix XSS critique (mews/purifier), index manquant, Queue::failing
- [x] Clone readiness (.env.example, seeder decouple, CoreSetupCommand)
- [x] DX : app:install, app:demo, app:status, app:check, app:make-module, app:logs, app:setup-hooks
- [x] CI/CD GitHub Actions (concurrency, npm audit, coverage-text)
- [x] VS Code config (extensions.json + settings.json)
- [x] Google Fonts local RGPD (GoogleFontService, 3 themes, 23 fichiers bunny.net nettoyes, Playwright 10/10)
- [x] PROGRESS_REPORT.md (croisement docs + code reel + tests)
- [x] RBAC fonctionnel (29 decoratives → 36 permissions actives, Gate::before, middleware route, AdminOnlyPolicy, 18+ fichiers modifies, 2185 tests 100%)
- [x] Fix tests paralleles (race condition MakeModuleCommandTest → groupe sequential)

## Restant - Remplacement WordPress (par priorite)

### Critique (sans ca, pas de remplacement WP)
- [ ] Menu dynamique (table menus, admin UI drag-and-drop, rendu header/footer)
- [ ] FAQ en base de donnees (module CRUD admin, page publique, schema.org FAQ)
- [ ] Stockage messages contact en DB (table contact_messages, liste admin, accuse reception)
- [ ] Homepage configurable (choisir une page statique comme accueil depuis admin)
- [ ] Templates de pages (landing, sidebar, full-width, selectable dans admin)

### Important (ameliore le SEO et le contenu)
- [ ] Schema.org / JSON-LD (articles, pages, FAQ, organisation)
- [ ] Tags blog dedies (modele Tag, CRUD admin, page archive /blog/tag/{slug})
- [ ] Temoignages (module CRUD admin + affichage frontend carousel/grid)
- [ ] Media picker dans TipTap (browser images dans editeur)

### Secondaire (nice-to-have)
- [ ] Widgets/blocs configurables (zones sidebar, footer, via admin)
- [ ] Form builder dynamique (formulaires configurables depuis admin)

## Restant - Technique

- [ ] Sidebar @can directives (masquer liens sans permission)
- [ ] Tests RBAC dedies (editor ne voit pas backups, etc.)
- [ ] Validation visuelle Playwright du RBAC
- [ ] Supprimer CrudService mort (0 import, dead code)
- [ ] Commit des changements RBAC en attente (52 fichiers)

## Restant - Nouvelles fonctionnalites (priorite basse)

- [ ] Phase 154 : email digest
- [ ] Phase 155 : documentation technique auto-generee
- [ ] Phase 156 : multi-tenant avance
- [ ] Phase 157 : marketing automation
- [ ] Phase 158 : tests A/B
- [ ] Migration Modules/ vers plugins/ (decision utilisateur requise)
- [ ] API v2 GraphQL
- [ ] Tests E2E Playwright automatises (suite complete)

## Decisions en attente (utilisateur)

| # | Question | Impact |
|---|----------|--------|
| 1 | Migration Modules/ vers plugins/ souhaitee ? | Risque eleve, valeur incertaine |
| 2 | Priorite Phases 154-158 ? | Planification prochaines sessions |
| 3 | Supprimer CrudService mort ? | Nettoyage, -1 fichier inutile (0 import) |
| 4 | Corriger 4 erreurs PHPStan env() ? | PHPStan 0 erreurs mais perd flexibilite clone |
