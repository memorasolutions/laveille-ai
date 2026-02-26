# TODO - Laravel SaaS Boilerplate

**Dernière mise à jour** : 2026-02-26
**Voir aussi** : PROGRESS_REPORT.md (rapport complet)

---

## Priorité haute

- [ ] **Synchroniser README.md** - Indique 1216 tests au lieu de 2156, 21 modules au lieu de 25
- [ ] **Audit hardcode wowdash** - Newsletter, Pages, Editor possiblement affectés par le theme switcher
- [ ] **Nettoyer docs obsolètes** - DOCUMENTATION_SUMMARY.md (1655 tests), SESSION_STATE.md (placeholder)
- [ ] **Nettoyage vues dupliquées** - 21 anciennes vues Backoffice identifiées (Phase 1 AUDIT_REPORT)
- [ ] **Commiter les changements** - 105+ fichiers modifiés depuis merge feature/plugin-architecture

## Priorité moyenne

- [ ] **Découplage Core** - Déplacer EnsureIsAdmin, résoudre 4 dépendances circulaires (Phase 2 AUDIT_REPORT)
- [ ] **Extraction code partagé** - Traits ParsesTags, VerifiesPassword, FormRequests dupliqués (Phase 3-4 AUDIT_REPORT)
- [ ] **Harmoniser boutons d'action admin** - kebab vs liens texte inconsistants entre les tables
- [ ] **Nettoyage screenshots racine** - 200+ fichiers .png d'audits/phases à déplacer ou supprimer

## Priorité basse

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
| 3 | Nettoyage 200+ screenshots .png ? | Taille du repo |
| 4 | Commit massif maintenant ? | 105+ fichiers modifies |

---

## Recemment complete (2026-02-26)

- [x] Toast notifications Bootstrap 5 (20 composants Livewire)
- [x] i18n validation FR (148 regles) + passwords (5) + pagination (2)
- [x] Footer "Tous droits reserves." corrige dans fr.json
- [x] Hook wire:loading global (spinner + disable boutons Livewire)
- [x] 8 textes anglais corriges (ON/OFF, Active, placeholders FR)
- [x] ~85 aria-labels WCAG 2.2 AA sur 17 composants Livewire
- [x] PROGRESS_REPORT.md complet et a jour
- [x] Bug branding x-cloak corrige (texte alt invisible)
- [x] Favicon upload/drag-n-drop verifie fonctionnel (branding page)
