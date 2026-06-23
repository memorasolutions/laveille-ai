# Chantier « parité Moodle complète » — Académie laveille.ai

> Journal d'historique du gros chantier (démarré 2026-06-22, America/Toronto). Source de vérité pour ne pas perdre le fil. Une feature à la fois.

## Objectif
Implanter de manière EXHAUSTIVE les fonctionnalités Moodle manquantes (cf. gap analysis 2026-06-22), **une à la fois**, version 2026 sur Laravel. Posture : le mieux pour la plateforme, jamais le plus facile. Zéro casse, zéro perte de données client.

## Cycle imposé par feature (chaque feature = 1 boucle complète)
1. **Implanter** (déléguer la génération de code ; superviser ; tests Pest verts, additif, rétrocompat).
2. **Déployer** (bump version + changelog + commit + push ; purge cache prod ; pas d'attente CI).
3. **/sim complet** (E2E Playwright visuel sur les parcours de la feature + non-régression ; comptes/courriels `@memora.ca` ; nettoyage des courriels au fur et à mesure).
4. **/audit complet** de la section (sécurité/perf/SEO/a11y/code/conformité, /100 ; prod lecture seule).
5. **Journaliser** ici (statut, version, résultats sim+audit, dette éventuelle).
6. Feature suivante.

## Hygiène
- Comptes/courriels de test = `@memora.ca` UNIQUEMENT ; **supprimés à la fin du chantier** (et au fur et à mesure pour les courriels). Ne JAMAIS toucher aux vrais courriels/comptes.
- Backups avant écriture ; scripts jetables auto-supprimés ; aucun cron laissé ; navigateur Playwright fermé après chaque sim.

## Backlog ordonné (je décide l'ordre — valeur d'abord, auto-contenu d'abord)

### VAGUE 3 — Profondeur quiz (types de questions) — extension directe de la banque QB1-3
- [x] F1 · Question **Ordonnancement** (mettre en ordre) — auto-scoré ✅ (v335-337, /sim PASS, /audit 75→corrigé)
- [x] F2 · Question **Cloze / texte à trous** (sous-questions intégrées) ✅ (v338-339, /sim 5/5, /audit ciblé 79→corrigé)
- [x] F3 · Question **Numérique** (réponse + tolérance/unités) ✅ (v340-341, /sim 18/18, /audit 83→corrigé)
- [x] F4 · Question **Glisser-déposer sur texte** ✅ (v342-343, /sim a trouvé bug scoring systémique, corrigé)
- [x] F5 · Question **Essai** (correction MANUELLE, reliée au carnet) ✅ (v344-345, /sim 6/7→bloquant corrigé, /audit 87→corrigé)
- [x] F6 · Mode quiz **Adaptatif** (réessai avec pénalité) ✅ (v346-347, /sim PASS, /audit 85→corrigé) — **VAGUE 3 COMPLÈTE** : 9 types + 3 comportements = parité Moodle questions.

### VAGUE 4 — Activités
- [x] F7 · Activité **Feedback / Sondage** (questionnaire non noté) ✅ V4-b (v350-351, /sim 8/8, /audit 84→corrigé : participants anti-respam + SoftDeletes)
- [x] F8 · Activité **Choice** (vote/sondage simple) ✅ V4-a (v348-349, /sim 6/6, /audit 83→corrigé)
- [x] F9 · Activité **Forum** (discussions, anti-spam) ✅ V4-c (v352-353, /sim A→G, /audit 91→corrigé) — **VAGUE 4 COMPLÈTE** (Choice+Feedback+Forum).

### VAGUE 5 — Communication & progression
- [ ] F10 · **Calendrier** + événements + **rappels d'échéance**
- [ ] F11 · **Notifications courriel d'activité** (Brevo, déjà en place)
- [ ] F12 · **Achèvement de cours configurable** (critères)
- [ ] F13 · **Restrictions d'accès** étendues (date/note/groupe/profil)

### VAGUE 6 — Profondeur notes & contenu
- [ ] F14 · **Échelles personnalisées (scales)** + méthodes d'agrégation du carnet
- [ ] F15 · **Sauvegarde / restauration / import de cours**
- [ ] F16 · **H5P** (contenu interactif, player h5p-standalone)
- [ ] F17 · **Banque** : versions de question + tags + statistiques

### VAGUE 7 — Social & avancé (selon appétit)
- [ ] F18 · **Notes/ratings** + commentaires
- [ ] F19 · Activité **Wiki** · F20 · **Database** collaborative · F21 · **Atelier (Workshop)** peer-assessment
- [ ] F22 · **Compétences / résultats (outcomes)** + plans d'apprentissage
- [ ] F23 · **Rapports & logs** (journaux, participation)

> Hors périmètre (plomberie entreprise peu utile à laveille.ai, sauf demande) : LDAP, SAML SSO, cohort sync site, web services REST, formats de cours multiples, app mobile native.

## Journal d'exécution (le plus récent en haut)
| Date | Feature | Version | Implant | /sim | /audit | Notes |
|------|---------|---------|---------|------|--------|-------|
| 2026-06-23 | **V4-c Forum → VAGUE 4 COMPLÈTE** | v352-353 | 789 tests (+25) | A→G PASS (sujet+réponse, modération pin/lock/delete soft, honeypot, XSS strippé, locked/allow_student_topics, non-régression) | 91/100 (meilleur) puis corrigé | Type d'item `forum` (tables topics+posts SoftDeletes, critère achèvement `post`, anti-spam honeypot+throttle, trait authz). Corrigé v353 (793 tests) : a11y sr-only épinglé/verrouillé, posts bornés 50 + repère troncature, 2 `—`. **JALON Vague 4 = activités Choice + Feedback + Forum.** Dette : images markdown posts étudiants (politique). |
| 2026-06-23 | **V4-b Activité Feedback/Sondage** | v350-351 | 755 tests (+19) | 8/8 PASS (multi-questions, anonyme user_id null, résultats formateur-seul, required, sécurité, non-régression) | 84/100 puis corrigé, XSS sûr | Type d'item `feedback` (table academy_feedback_responses, critère achèvement `submit`). Corrigé v351 (764 tests) : anti-respam anonyme robuste (table academy_feedback_participants, anonymat préservé), requête sortie de la vue (LessonController preload), SoftDeletes + withTrashed. Dette : results() agrégation PHP (OK volume). |
| 2026-06-23 | **V4-a Activité Choice** | v348-349 | 727 tests (+20) | 6/6 PASS (vote unique, re-vote, visibilité never/after_vote/anonyme, achèvement, sécurité, non-régression) | 83/100 puis corrigé | 1re activité Vague 4. Nouveau type d'item `choice` (table academy_choice_responses, critère achèvement `vote`). Corrigé v349 (736 tests) : trait DRY `AuthorizesAcademyAccess` (partagé quiz+choice), **throttle:20,1 sur tous les POST Academy** (anti-DoS), perf tally lazy + preload N+1, a11y/PII. |
| 2026-06-23 | **F6 Adaptatif → VAGUE 3 COMPLÈTE** | v346-347 | 700 tests (+11) | PASS A→E (1pt/0,67pt/0pt, anti-spam serveur, deferred inchangé) | 85/100 puis corrigé | Réessai avec pénalité max(0,1-n×p)×justesse, bornage serveur, idempotent. Corrigé v347 (707 tests) : review_options respecté en vue verrouillée immédiat/adaptatif (tous types), focus visible WCAG, masquage champs adaptatifs. **JALON : Vague 3 = 9 types de questions + 3 comportements = parité Moodle sur les questions.** Dette : score/max_score DB bruts vs percent pénalisé ; immédiat non testé en /sim live (pas d'item en base, vérifié par code). |
| 2026-06-23 | **F5 Essai (correction manuelle)** | v344-345 | 680 tests (+11) | 6/7 → bloquant corrigé | 87/100 puis corrigé | Type le + complexe : workflow soumission→en attente→correction formateur→note finale→complétion. Migration additive (needs_grading/manual_scores/feedback/graded_at/by), service EssayGradingService + composant EssayGrading gatés. **/sim a trouvé bloquant** : étudiant ne voyait pas son essai corrigé (note+feedback en flash seul). Corrigé v345 (689 tests) : RÉSULTAT PERSISTANT (charge dernière QuizAttempt user+item, affiche note+feedback+révision, anti-IDOR, review_options) → bénéficie à TOUS les quiz. + completion score, N+1, bornes longueur. |
| 2026-06-22 | **F4 Glisser-déposer (ddwtos)** | v342-343 | 657 tests (+18) | release-blocker trouvé puis corrigé | 84/100 ciblé puis corrigé | select a11y-first + pool partagé. **BUG CRITIQUE SYSTÉMIQUE attrapé par le /sim** (pas par les tests !) : crédit partiel `(int)round(fraction*points)` → question 1 pt à 50 % = 100 % (passing_score faussé) sur les 4 types partiels. **LEÇON : le /sim après chaque feature est indispensable** (les tests utilisaient des points pairs où l'arrondi tombait juste). Corrigé v343 (669 tests) : accumulation FLOAT + percent exact ; + dédup pool, 0 tiret cadratin (règle 10), MAX_DDWTOS_TEXT, aria-required. Bénéficie rétroactivement à F1/F2/V1-e. |
| 2026-06-22 | **F3 Numérique** | v340-341 | 627 tests (+18) | 18/18 PASS (42 plein, 42,4 virgule plein, 43 hors=0, vide/abc=0, sécurité, non-régression) | 83/100 ciblé puis corrigé | parseNumber DRY (virgule/point). Corrigés v341 (639 tests) : C1 BLOQUANT overflow INF (is_finite racine + défense profonde), C2 bornes unité (max:40 serveur), C3 DRY partial numerical-input + formatNumber. **DETTE (à traiter plus tard, non bloquant)** : BUG-1 sélecteur catégorie de banque absent du form « Ajouter un élément » (faut Éditer) ; OBS-2 minuteur n'inhibe pas les boutons « Vérifier » en mode immédiat à 0:00 (garde serveur timed_out OK) ; OBS-3 3 erreurs console pré-existantes sur /academie (non liées). |
| 2026-06-22 | **F2 Cloze** | v338-339 | 602 tests (+13) | 5/5 PASS (inline input+select, plein 4/4, partiel 2/4, réponses non exposées, QuizAttempt OK) | 79/100 ciblé puis corrigé | Méthode : audit CIBLÉ par type (code-reviewer-fr) + remédiation, pas de re-audit 100pts redondant (section déjà auditée à F1). Corrigés v339 (609 tests) : C1 marqueur dupliqué [[1]][[1]] (biais notation), C2 fieldset immédiat a11y, C3 DRY 2 partials cloze, C4 bornes validation. |
| 2026-06-22 | **F1 Ordonnancement** | v335-337 | 584 tests (+13) | PASS A→F (ordre exact 8/8, partiel 4/8, non-régression, QuizAttempt OK) | 75/100 puis corrigé | Bug pré-existant B1 (minutes vides → TypeError ?int) corrigé v336. Audit : 0 critique ; H1 TOCTOU attempts + M1 bornes + M3 depth + M4 fieldset a11y + B1 Schema::hasColumn + M2 select perf → corrigés v337 (589 tests). Dette notée : B2 (agrég PHP), B3 (checkbox required), B4 (détail révision mode immédiat). |
| 2026-06-22 | (init du chantier) | v334 base | - | - | - | Journal + plan créés. Démarrage F1. |
