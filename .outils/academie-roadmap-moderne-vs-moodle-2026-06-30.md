# Académie — Rattraper Moodle, en mieux (roadmap moderne 2026)

> 2026-06-30. Croisement : veille LMS mondiale (NA/Asie/Inde/Europe/Afrique) + dette D01-D19 + état réel de l'Académie. Notes /100 = valeur×modernité pour un LMS pro québécois (IA/techno), pondérées effort.

## Principe directeur
Ne PAS recopier Moodle tel quel : égaler ses fonctions UTILES, jeter ses défauts, ajouter le meilleur de 2026. On vise des **différenciateurs**, pas la parité servile.

## Anti-patterns Moodle à NE PAS copier (notre position)
1. Longues pages de cours → **DÉJÀ RÉGLÉ** (DeckPlayer, leçon en cartes).
2. UX à 3 clics de trop / admin pléthorique → garder le « tout en front-end », RBAC simple.
3. Mobile = desktop rétréci → viser mobile-first réel.
4. Personnalisation = enfer de plugins → modules nwidart activables, pas de dépendances tierces lourdes.
5. Analytics « qui a terminé ? » → viser prédictif + ROI métier.
6. Thèmes fragiles, notifications en lot, prérequis statiques, authoring rudimentaire → versions modernes ci-dessous.

## État actuel (déjà à parité ou mieux)
✅ Cours/chapitres/leçons · items multi-types (doc, vidéo, quiz, sondage, forum, wiki, base de données, atelier, H5P) · inscriptions · cohortes · banque de questions · carnet pondéré · badges · **certificats PDF** · analytics de base + par leçon · prérequis · RBAC complet · **microlearning DeckPlayer** · interrupteur notifications admin · PWA (service worker) · paiements Stripe (Cashier, partiel).

## Liste complète des manques (notée /100)

Légende statut : ❌ absent · ◑ partiel · 🆕 différenciateur au-delà de Moodle.

### Pédagogie & IA
| Cap | /100 | Statut | Note |
|---|---|---|---|
| Tuteur IA conversationnel ancré au cours (RAG) | 97 | ❌ 🆕 | Différenciateur #1 2026 (Coursera Coach, Khanmigo) |
| Adaptatif par graphe de connaissances (mastery nano) | 96 | ❌ 🆕 | Squirrel AI (Chine) : mastery +33%/mois. Gros effort |
| Feedback IA sur réponses ouvertes | 90 | ❌ | Libère le formateur (Cognii, Turnitin) |
| Répétition espacée (SRS) native | 88 | ❌ 🆕 | Phase 2 du DeckPlayer. Rétention #1 prouvée |
| Diagnostic/placement adaptatif en entrée | 85 | ❌ | Évite de relire le déjà-su |

### Contenu & Authoring
| Cap | /100 | Statut | Note |
|---|---|---|---|
| Authoring IA générative (plan/quiz depuis prompt) | 92 | ❌ | 360Learning, Docebo Shape |
| Microlearning natif (deck de cartes) | 89 | ✅ | **FAIT** (DeckPlayer) |
| Co-création par les pairs (UGC validé) | 83 | ◑ | Éditeur front-end existe ; workflow de validation à étoffer |
| Traduction/localisation IA + TTS multilingue | 80 | ◑ | FR/EN (Translatable) ; IA + voix = à ajouter |

### Évaluation & Certification
| Cap | /100 | Statut | Note |
|---|---|---|---|
| Certificats vérifiables OpenBadges 3.0 + wallet | 88 | ◑ | PDF+QR faits ; OpenBadge/Credly = D09 |
| Évaluations adaptatives (CAT) | 87 | ❌ | Gold standard certifications |
| Proctoring IA | 72 | ❌ | Niche ; Loi 25 = consentement biométrique |

### Social & Engagement
| Cap | /100 | Statut | Note |
|---|---|---|---|
| Notifications intelligentes / nudges comportementaux | 86 | ◑ | Interrupteur fait ; nudges inactivité/échéance = D10 + au-delà |
| Gamification moderne (points/niveaux/ligues cohorte) | 84 | ◑ | Badges faits ; points/classements à ajouter |
| Mentorat & cohortes structurées (check-in) | 82 | ◑ | Cohortes faites ; mentor assigné/suivi à ajouter |
| Discussion sociale structurée (Q horodatées par vidéo) | 81 | ◑ | Forum fait ; fils par vidéo à ajouter |

### Mobile & Accès
| Cap | /100 | Statut | Note |
|---|---|---|---|
| UX mobile-first (gestes, 1 action/écran) | 93 | ◑ | DeckPlayer aide ; navigation mobile-first globale à finir |
| PWA offline-first (capsules/quiz hors-ligne) | 91 | ◑ | PWA existe ; offline-first leçons = leapfrog Afrique/Inde |
| Multi-appareil / kiosque | 65 | ❌ | Niche inclusive |

### Analytics & Mesure
| Cap | /100 | Statut | Note |
|---|---|---|---|
| Analytics prédictifs (risque d'abandon, ML) | 91 | ❌ | ROI direct (D2L Insights) |
| Tableaux de bord par rôle (apprenant/formateur/manager/admin) | 88 | ◑ | Analytics de base ; vues par rôle à différencier |
| Corrélation formation ↔ performance métier (HRIS/CRM) | 85 | ❌ | Argument DRH #1 |

### Intégrations & Administration
| Cap | /100 | Statut | Note |
|---|---|---|---|
| SSO SAML 2.0 / OIDC + provisioning SCIM | 90 | ❌ | Bloquant vente 50+ employés |
| Visio live native (Zoom/Teams/Meet) + présence | 87 | ◑ | Vidéo ScreenPal ; live planifié à ajouter |
| Paiements/abonnements/coupons complets | 82 | ◑ | Stripe Cashier partiel ; abos/coupons à finir |
| Multi-tenant (clients/partenaires, branding cloisonné) | 80 | ❌ | Différenciateur commercial |
| Marketplace catalogue externe (Go1/LinkedIn Learning) | 78 | ❌ | Complément de contenu |

### Standards & Interopérabilité
| Cap | /100 | Statut | Note |
|---|---|---|---|
| xAPI / LRS (traces, hors-LMS) | 86 | ❌ | D01 (H5P « page vue ») ; conformité UE |
| SCORM 2004 / AICC (contenus legacy) | 70 | ❌ | D14 ; niche, effort lourd |

### Parité Moodle « niche » (basse priorité, dette résiduelle)
Import `.mbz` (D16, 60) · messagerie directe (D15, 65) · calendrier global (D12, 62) · catégories de catalogue (D04, 70) · banque partagée formateurs (D06, 68) · fenêtres d'inscription (D07, 72) · note manuelle au carnet (D19, 74) · seuils % prérequis (D17, 66) · restrictions quiz IP/mdp (D18, 64) · CRUD admin badges (D08, 60).

### À nettoyer (anti-pattern interne)
Doublon admin legacy `/admin/academy` ∥ gestion front-end (D03) — fusionner.

## Roadmap recommandée (best for platform)

**Vague 1 — Différenciateurs à fort ROI (nous rendre MEILLEURS que Moodle).**
Tuteur IA ancré (97) · Feedback IA réponses ouvertes (90) · Notifications intelligentes/nudges (86, prolonge D02) · SRS (88, phase 2 DeckPlayer) · Analytics prédictifs + tableaux par rôle (91/88).

**Vague 2 — Parité pro + déblocage vente B2B.**
SSO/SAML/OIDC + SCIM (90) · OpenBadges 3.0 (88, D09) · Visio live (87) · mobile-first + offline-first PWA (93/91) · paiements/abos complets (82) · ménage D03.

**Vague 3 — Adaptatif avancé + interop.**
Graphe de connaissances/adaptatif (96) · Authoring IA (92) · CAT (87) · xAPI/LRS (86, D01) · corrélation ROI métier (85) · multi-tenant (80).

**Vague 4 — Parité Moodle niche (à faire seulement si demandé).**
SCORM (70) · import `.mbz` (60) · DM (65) · kiosque (65) · calendrier global, catégories, banque partagée, etc.

## Garde-fous Québec/pro
Loi 25 + RGPD (xAPI pseudonyme + LRS local ; proctoring = consentement) · FR québécois d'abord (tuteur/authoring/notifs) · WCAG 2.2 AA (fonds publics) · mobile-first non négociable.
