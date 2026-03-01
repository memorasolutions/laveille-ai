# TODO - Laravel SaaS Boilerplate

**Dernière mise à jour** : 2026-03-01
**Voir aussi** : PROGRESS_REPORT.md (rapport complet croisé docs/code)

---

## Complétés (sessions récentes)

- [x] Commit massif 4e0500c (4835 fichiers, 584K insertions)
- [x] Audit hardcode wowdash, docs obsolètes, vues orphelines (-3577 lignes)
- [x] Découplage Core, code partagé, PHPDoc API Scramble
- [x] Fix XSS critique (mews/purifier), index manquant, Queue::failing
- [x] Clone readiness (.env.example, seeder découplé, CoreSetupCommand)
- [x] DX : app:install, app:demo, app:status, app:check, app:make-module, app:logs, app:setup-hooks
- [x] CI/CD GitHub Actions (concurrency, npm audit, coverage-text)
- [x] VS Code config (extensions.json + settings.json)
- [x] Google Fonts local RGPD (GoogleFontService, 3 thèmes, 23 fichiers bunny.net nettoyés)
- [x] RBAC fonctionnel (39 permissions actives, Gate::before, middleware route, policies, 4 rôles)
- [x] Fix tests parallèles (race condition MakeModuleCommandTest)

## Complétés - Remplacement WordPress

### Critique
- [x] Menu dynamique (drag-and-drop SortableJS, cache, Blade component) - 928a915
- [x] FAQ en base de données (CRUD admin, page publique, JSON-LD Schema.org) - dc1fcd6
- [x] Stockage messages contact en DB (table, liste admin, filtres lu/non lu) - 0aa8cae
- [x] Homepage configurable (landing ou page statique via admin Settings) - bea7e03
- [x] Templates de pages (default, full-width, sidebar, landing - 4 templates)

### Important
- [x] Schema.org / JSON-LD (articles, pages, FAQ, organisation, breadcrumbs, WebSite)
- [x] Tags blog dédiés (modèle Tag, CRUD admin, page archive /blog/tag/{slug})
- [x] Témoignages (module CRUD admin + affichage frontend)
- [x] Media picker dans TipTap (browser images dans éditeur, Alpine Proxy fix)

### Technique
- [x] Sidebar @can directives (masquer liens sans permission) - thème backend
- [x] Tests RBAC dédiés (11 tests, 57 assertions) - sidebar + route-level 403
- [x] Nettoyage thèmes (wowdash/tabler supprimés, ~133 Mo libérés, 0 référence restante)
- [x] Dashboard actions rapides protégées @can (manage_users, manage_backups, manage_settings)
- [x] Layout auth guest réécrit Authero (Tailwind CSS + Preline UI + Tabler icons)
- [x] Layout auth user corrigé Jobick (dashboard utilisateur fonctionnel)
- [x] Vues Livewire auth converties Bootstrap→Tailwind (login, register, forgot-password, reset-password)
- [x] Fix 16 tests cassés post-nettoyage thèmes (assertions wowdash→backend)
- [x] Fix @push('js')→@push('scripts') vue revenue (ApexCharts rendu)
- [x] Fix Phase57 (bg-success→bg-green-500, Tailwind colors → inline colors)
- [x] jQuery supprimé des vues auth (vanilla JS)
- [x] Tests WCAG Phase188 (h1, nav landmark, aria-labels layout admin)
- [x] Harmonisation 9 vues auth design Authero (auth-* CSS, inline SVGs, 0 Bootstrap)
- [x] Guest layout split 50/50 (formulaire + hero image, responsive)
- [x] Pages légales dynamiques via Settings (mentions légales, confidentialité)
- [x] Onglet "Légal" dans SettingsManager admin
- [x] NobleUI SCSS compilation via Vite (54 fichiers source, 381 KB CSS)
- [x] Audit 140+ vues admin (0 Tailwind/WowDash/FontAwesome restant)
- [x] Settings dark mode fix (tabs, labels, TipTap toolbar)
- [x] Migration user dashboard Jobick → NobleUI (app.blade.php + 16 vues)
- [x] Lien "Mon espace" dans header admin (profil dropdown)
- [x] Tests Phase162 + Phase86 corrigés (ai-chatbot, $unreadCount)

---

## Restant - Immédiat (trivial)

- [x] Commit des changements en attente (d42db7e, d382d99, d47a082, b03cd5b)
- [x] Corriger 13 erreurs PHPStan → 0 erreurs (niveau 6, 439 fichiers)
- [x] Supprimer `public/assets/` Jobick (déjà supprimé)

## Restant - Technique (moyen terme)

- [x] Validation visuelle Playwright du RBAC (4 rôles x pages admin, 0 bug sécurité)
- [x] Tests E2E Playwright automatisés (15 tests : 5 auth, 5 RBAC, 5 pages publiques)

## Restant - Nice-to-have WordPress

- [ ] Widgets/blocs configurables (zones sidebar, footer, via admin)
- [ ] Form builder dynamique (formulaires configurables depuis admin)

## Restant - Nouvelles fonctionnalités (priorité basse)

- [ ] Phase 154 : email digest
- [ ] Phase 155 : documentation technique auto-générée
- [ ] Phase 156 : multi-tenant avancé
- [ ] Phase 157 : marketing automation
- [ ] Phase 158 : tests A/B
- [ ] Migration Modules/ vers plugins/ (décision utilisateur requise)
- [ ] API v2 GraphQL

---

## Décisions en attente (utilisateur)

| # | Question | Impact |
|---|----------|--------|
| 1 | Migration Modules/ vers plugins/ souhaitée ? | Risque élevé, 28 modules à adapter |
| 2 | Priorité Phases 154-158 ? | Planification prochaines sessions |
| 3 | ~~Corriger 13 erreurs PHPStan~~ | ✅ Résolu (0 erreurs) |
