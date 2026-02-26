# ROADMAP - Laravel CORE Template

## État actuel (2026-02-24)
- **2156 tests, 4192 assertions, 100% pass**
- **Larastan** : 0 erreurs (niveau 6)
- **Pint** : 100% pass
- **25 modules**, phases 0-189 + theme switcher terminées
- **3 thèmes admin** : wowdash (Bootstrap 5), tabler (Tabler CSS), backend (Jobick/Tailwind)
- **Thème actif** : backend (géré en BDD, plus de variable .env)

---

## Phases suggérées à venir

### Priorité haute
- [ ] **Phase 190** - Correctifs theme switcher : erreur JS flatpickr backend + audit hardcode wowdash dans Newsletter/Pages/Editor
- [ ] **Phase 191** - Synchronisation README.md : réécriture ou mise à jour des chiffres (2156 tests, 25 modules, 456 routes, 78 migrations)
- [ ] **Phase 192** - Nettoyage AUDIT_REPORT Phase 1 : supprimer 21 vues dupliquées, CrudService mort, 4 stubs vides
- [ ] **Phase 193** - Découplage Core Phase 2 : EnsureIsAdmin vers Core, 4 dépendances circulaires

### Priorité moyenne
- [ ] **Phase 194** - Extraction code partagé (Phase 3-4) : traits ParsesTags/VerifiesPassword, AdminOnlyPolicy, FormRequests dupliqués
- [ ] **Phase 195** - Harmonisation boutons d'action admin (kebab menu vs liens texte)
- [ ] **Phase 196** - Email digest/résumé (notifications groupées)
- [ ] **Phase 197** - Documentation technique auto-générée

### Priorité basse
- [ ] **Phase 198** - Teams/multi-tenant avancé
- [ ] **Phase 199** - Marketing automation avancé
- [ ] **Phase 200** - A/B testing intégré
- [ ] **Phase 201** - Migration Modules/ vers plugins/ (risque élevé, décision utilisateur requise)
- [ ] **Phase 202** - API v2 GraphQL
- [ ] **Phase 203** - Suite de tests E2E Playwright automatisés
- [ ] **Phase 204** - Optimisation performance

---

## Historique des phases complétées

| Phase | Description | Tests ajoutés |
|-------|-------------|---------------|
| 0 | Laravel 12 setup initial : modules, Livewire, Pest, Alpine.js | - |
| 1-11 | Modules Core, Auth, RolesPermissions, Backoffice, Settings, Media, FrontTheme, Logging, Notifications, SEO, Api | - |
| 12-22 | Storage, SaaS, Tenancy, Webhooks, Health, tests, CI, sécurité | - |
| 23-37 | Backoffice admin : dashboard WowDash, CRUD utilisateurs/rôles/settings, sidebar | - |
| 38-45 | Auth avancé : 2FA TOTP, OAuth social, magic links, brute-force, vérification email | - |
| 64-68 | Backoffice admin suite : médias, logs, backups, recherche globale | - |
| 69-80 | Analytics et statistiques ApexCharts | - |
| 87-92 | SaaS/Stripe : plans, checkout, webhooks Stripe | - |
| 95-99 | Notifications : push web, Reverb temps réel | - |
| 100-108 | Blog complet : articles, catégories, commentaires, tags | - |
| 109-112 | Newsletter et email | - |
| 120-128 | Frontend GoSaaS : landing, pricing, contact, FAQ, cookie consent | - |
| 129-136 | Sécurité et qualité : SecurityHeaders, pages erreur custom | - |
| 137-140 | SaaS suite : revenue dashboard, billing history, MRR/ARR/churn | - |
| 141 | Éditeur TipTap (Module Editor) | - |
| 142-155 | API REST v1 complète Sanctum | - |
| 145 | GDPR cookie préférences granulaires | 17 |
| 146 | Onboarding wizard nouveaux utilisateurs | 13 |
| 147 | Révisions articles : tests, diff visuel LCS, settings, dropdown | 20 |
| 149 | API endpoints : tests blog/articles/plans/profile/notifications + FormRequests | 38 |
| 150 | Notifications push : toggle profil, JS admin, 8 dropdowns convertis | 17 |
| 151 | Full-text search : Scout, 5 modèles searchable, SearchController API | 18 |
| 152 | Webhooks retry/signature : enum, model, job, service dispatch/retry | 21 |
| 153 | Analytics avancé : AnalyticsService, AnalyticsController, 4 endpoints | 18 |
| 154-160 | Phases contenu/SaaS/notifications diverses | - |
| 161-169 | Module AI : AiService OpenRouter, chatbot, générateur articles, modération, SEO IA | - |
| 170-172 | SaaS avancé : billing history détaillée | - |
| 173 | Email templates personnalisables | - |
| 174-189 | UX/UI : WCAG 2.2 AA, responsive, feature flags conditions, traductions Livewire, export/import CSV, sidebar accordéons | - |
| Theme switcher (2026-02-24) | Theme switcher dynamique BDD : middleware SetBackofficeTheme, 3 layouts Auth, Blog refactorisé, vues backend, BACKOFFICE_THEME retiré du .env, 8 tests mis à jour | 0 |

---

## Dernière mise à jour
- **Date** : 2026-02-24
- **Total tests** : 2156
- **Total assertions** : 4192
