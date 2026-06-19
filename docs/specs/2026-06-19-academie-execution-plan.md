# Plan d'exécution — MVP module « Academy » laveille.ai

> Date : 2026-06-19 · Source : design `2026-06-19-academie-formation-design.md` (approuvé) · Plan généré par Hermes/GPT-5, supervisé Opus.
> **Sécurité prod absolue** : module **désactivé** dans `modules_statuses.json` pendant TOUT le dev (zéro impact prod) ; additif seulement ; migrations idempotentes + réversibles ; tests en local avant tout déploiement ; activation + déploiement seulement en **M9 avec confirmation user**. Aucune dépendance composer (JS/CDN OK). Pas de cron. JSON-LD = array PHP.

## M0 — Fondations
1. **Scaffolder le module « Academy » désactivé** — Modules/Academy (ServiceProvider, routes, config) + `modules_statuses.json` Academy:false. Owner: Hermes/Opus. Critère: module existe, /academie = 404 tant que désactivé, composer.json inchangé. Dép: —
2. **Namespace + prefix /academie + guard module-off** — group prefix /academie, middleware 404 si désactivé. Critère: /academie = 404 module off. Dép: 1
3. **Migrations idempotentes (+down)** — courses, chapters, lessons, lesson_items, enrollments, completions, progresses, certificates_issued, course_roles. Critère: migrate + rollback 2× sans erreur. Dép: 1
4. **Modèles Eloquent + relations + SoftDeletes (+factories)** — Course, Chapter, Lesson, LessonItem(video|quiz|doc), Enrollment, Completion, Progress, CertificateIssued, CourseRole. Critère: relations OK en tinker, aucun appel hors module. Dép: 3
5. **Seeder démo minimal** — 1 cours gratuit (2 chapitres) + 1 payant placeholder. Critère: seed OK, rollback OK. Dép: 4
6. **ActivityLog created/updated** — Observers Course/Lesson. Critère: activity_log peuplé en local. Dép: 4,5

## M1 — Rôles/permissions + enrôlement gratuit
7. **Permissions Spatie `academy.*` + gates** — academy.view / academy.enroll / academy.manage (seeder). Critère: admin a manage, view OK pour tous. Dép: 5,6
8. **course_roles (instructor/assistant) + policies par cours** — instructor édite SON cours seulement. Dép: 7
9. **Endpoint enrôlement gratuit** — POST /academie/courses/{course}/enroll, auth requise, Enrollment unique. Critère: pas de doublon, ActivityLog « enrolled ». Dép: 7
10. **Hook QuestProgress sur enrôlement** — event academy.enrolled. Critère: QuestProgress reçoit sans erreur. Dép: 9

## M2 — Pages publiques /academie
11. **Page index /academie** — listing + filtres free/paid/tag + placeholder recherche, charte. Critère: GET /academie affiche seed, a11y >90. Dép: 2,5
12. **Page cours /academie/courses/{slug}** — chapitres/leçons, CTA S'inscrire/Acheter, sections auteur. Critère: free→S'inscrire, paid→Acheter, 200. Dép: 11
13. **Charte (layout commun Academy)** — partial Blade, variables CSS, composants. Critère: conforme specs. Dép: 11,12

## M3 — Lecteur de leçon + gating vidéo + filigrane + CSP
14. **Page leçon** — side-nav, progression, rendu LessonItem video|quiz|doc, next/prev. Critère: 404 si pas d'accès. Dép: 12
15. **Gating auth/enrôlement sur vidéo (overlay)** — non inscrit = overlay login/inscription ; preview si flag. Critère: non inscrit ne lit pas. Dép: 14,9
16. **Embed ScreenPal (non listé + domain-lock) sans proxy** — iframe + doc config CNAME/domain-lock. Owner: Explore(config)+Hermes. Critère: ne joue que sur domaine autorisé. Dép: 15
17. **Filigrane CSS dynamique** — overlay non cliquable (nom/email + timestamp), persiste plein écran, désactivable ARIA (a11y). Dép: 16
18. **CSP routes Academy** — frame-ancestors 'self' *.laveille.ai ; frame-src screenpal+self. Critère: 0 violation console, embed OK. Dép: 14,16

## M4 — Quiz QtService + Completion + Progress
19. **QtService sur LessonItem type=quiz** — composant Blade, 4 types, résultat/feedback. Critère: soumission OK, 0 dépendance. Dép: 14
20. **Completion (manuelle vidéo/doc + seuil quiz)** — bouton « Marquer terminé », auto si score≥seuil. Critère: idempotent, ActivityLog « completed ». Dép: 19
21. **Progress par cours + reprise** — service %, barre UI, bouton « Reprendre ». Critère: % cohérent, reprend la 1re leçon non finie. Dép: 20,9
22. **Hook QuestProgress completion/progress** — events. Critère: réagit sans erreur. Dép: 21

## M5 — Paiement Stripe Cashier
23. **Tarification cours (free/paid + prix + devise)** — champs + UI admin module. Dép: 3,12
24. **Cashier checkout + gating enrollment** — « Acheter »→checkout→Enrollment ; pas de composer modifié. Critère: sandbox OK, double achat bloqué. Dép: 23,9
25. **Webhook paiement réussi (sans nouveau package)** — listener Cashier invoice.payment_succeeded→enrôle. Critère: sandbox webhook OK. Dép: 24

## M6 — Certificat + JSON-LD
26. **Certificat (HTML imprimable PDF) + record** — page certificat, données user/cours, CertificateIssued une seule fois. Dép: 21
27. **JSON-LD (array PHP) Course + Certificate** — pas de spatie/schema-org. Critère: rich results valide. Dép: 12,26

## M7 — Recherche Meilisearch + observabilité/export
28. **Indexer Course/Chapter/Lesson (Meilisearch)** — observers + reindex command. Dép: 6,11,12
29. **Recherche /academie (autocomplete + filtres)** — Meilisearch JS (CDN) + fallback serveur. Critère: <200ms local, fallback sans JS. Dép: 28,11
30. **Export CSV admin (enrollments/completions/progress)** — fputcsv UTF-8 + filtres. Dép: 21,24
31. **Newsletter automation (enrollment/completion/certificate)** — events existants, pas de doublon. Dép: 9,20,26

## M8 — Tests + a11y
32. **Tests Pest** (migrations, routes, policies, enrollment, progress, certificat). Critère: `artisan test` vert local. Dép: 21,24,26
33. **E2E Playwright (Sonnet)** — parcours free, gating vidéo, achat, quiz, certificat + axe a11y. Critère: 0 violation critique. Dép: 15,16,24,26
34. **Revue sécurité/perf (Explore)** — CSP, cookies, perf Lighthouse, 0 fuite vidéo hors domaine. Dép: 18,33

## M9 — Activation + déploiement (CONFIRMATION USER)
35. **Préflight + confirmation activation** — checklist (tests OK, rollback, impacts nuls hors module) ; modules_statuses.json reste false avant go. Owner: Opus. Critère: validation écrite user. Dép: 32,33,34
36. **Déploiement (cpanel) + QA + activation** — code + migrations + caches ; toggle Academy→true après QA ; doc rollback. Owner: cpanel-deploy. Critère: prod OK, rollback testé. Dép: 35

## Phases futures (titres)
- **Phase 2** : H5P (player JS h5p-standalone) ; CRUD quiz en DB (migration des banques PHP) ; analytics vidéo ; profils instructeurs.
- **Phase 3** : sous-domaine academie.laveille.ai (Route::domain + SESSION_DOMAIN) ; gradebook avancé ; SCORM/xAPI ; learning paths ; avis cours.

## À confirmer avant exécution
- Capacité du **plan ScreenPal** pour le verrou de domaine/CNAME (sinon les autres couches suffisent).
