---
titre: Académie / formation laveille.ai — Design
date: 2026-06-19
statut: EN ATTENTE D'APPROBATION (HARD-GATE — aucune implémentation avant « approuvé »)
auteur: Stéphane Lapointe (Memora) — design assisté (Opus superviseur + Hermes/GPT-5 + recherche pp_search)
décisions verrouillées:
  - architecture: Module natif Laravel nwidart « Academy » (88/100) — pas de Moodle (départ de zéro)
  - déploiement: inclus dans laveille au chemin /academie (90/100), architecturé pour bascule sous-domaine academie.laveille.ai
  - monétisation: freemium (gratuit accès-membre + payant via Stripe Cashier)
  - H5P: phase 2 (player JS h5p-standalone, CDN, zéro dépendance composer)
  - vidéo ScreenPal: combo zéro-surcharge (non répertorié + verrou domaine/CNAME + CSP + embed gaté par auth/inscription + filigrane CSS)
contrainte CI: pas de composer install au déploiement → aucune nouvelle dépendance composer lourde ; JS/CDN OK ; JSON-LD = array PHP (jamais spatie/schema-org)
---

# Design — Académie laveille.ai

1. Contexte & problème
- Laveille.ai veut offrir une formation structurée (cours, leçons, quiz, certificats) sans alourdir la stack ni réinventer l’existant.
- Constat clé : laveille possède déjà ~70 % des briques d’un LMS (Auth passkeys/2FA/social, rôles Spatie, moteur de quiz QtService avec 4 types de questions, gamification QuestProgress, Stripe Cashier, Media, Search, automation Newsletter, API, ActivityLog). Aucune instance Moodle n’est branchée.
- Contraintes clés:
  - Pas de LMS externe (ex.: pas de Moodle).
  - Aucune nouvelle dépendance Composer lourde (CI n’exécute pas composer install).
  - Monétisation Freemium à même Stripe Cashier déjà en place.
  - Réutiliser au maximum les briques internes (Auth, rôles/permissions, QtService pour quiz, gamification, MediaLibrary, Search, Module API, ActivityLog, etc.).
  - Héberger les parcours vidéo ScreenPal de façon sécuritaire sans proxy et avec friction minimale.
- Besoin: un module natif « Academy » dans Laravel nwidart, intégré à /academie, évolutif vers un sous-domaine dédié, avec UX simple pour l’apprenant et des outils d’édition/gestion pour instructeurs/admins.

2. Objectifs / Non-objectifs (scope MVP clair)
Objectifs MVP
- Découverte et recherche de cours publics à /academie.
- Pages de cours (syllabus, chapitres, leçons).
- Types de contenu en leçon (MVP):
  - Video (ScreenPal, iframe) avec contrôles d’accès.
  - Quiz (QtService): QCM, vrai/faux, réponse courte, appariement, via banques PHP existantes.
  - Doc (lecture/ressource) basé sur contenu riche (Dictionary/Media).
- Enrôlement:
  - Gratuit avec compte (freemium) pour cours gratuits.
  - Payant (Stripe Cashier) pour cours premium (one-off, prix fixe; abonnement si déjà implémenté côté Cashier).
- Suivi de progression (par item, progression agrégée).
- Certificat de complétion (PDF/HTML avec vérification simple).
- Protection vidéo (gating, CSP, filigrane CSS, verrou ScreenPal par domaine/CNAME).
- Observabilité: événements, ActivityLog, analytics basiques + export CSV.
- Recherche Meilisearch des cours et contenus de cours.
- Rôles/permissions Academy pour instructeurs/admins.

Non-objectifs (MVP)
- H5P (reporté Phase 2).
- SCORM/xAPI (Phase 3 éventuelle).
- Éditeur avancé de quiz en BD (Phase 2; MVP = banques PHP existantes).
- App mobile dédiée.
- Traduction complète UI (MVP FR; i18n prévu plus tard si nécessaire).

3. Décisions d’architecture (résumé + notes /100)

| Axe | Décision | Note /100 | Alternatives (notes) |
|---|---|---:|---|
| Architecture | Module nwidart Laravel « Academy » réutilisant les briques internes | 88 | Hybride Moodle 74 · Headless Moodle 68 · Reskin Moodle 60 |
| Déploiement | Inclus à /academie, prêt pour bascule sous-domaine (Route::domain + SESSION_DOMAIN) | 90 | Sous-domaine d’emblée 82 · App séparée 65 |
| Vidéo ScreenPal | Non listé/privé + verrou domaine/CNAME + CSP frame-ancestors=laveille + iframe seulement si membre connecté & inscrit + filigrane CSS, sans proxy | ~90 effectif | Proxy signed-URL 55 (rejeté: surcharge) |

4. Personas & user stories
- Apprenant (pro QC)
  - Comme pro en veille/innovation, je veux découvrir des cours pertinents, voir le syllabus, et m’inscrire rapidement (gratuit ou payant) pour me former à mon rythme.
  - Je veux reprendre là où je l’ai laissé, suivre ma progression, et obtenir un certificat partageable.
  - Je veux que le contenu soit accessible sur desktop/tablette et rechercher dans les cours.
- Instructeur
  - Je veux créer/éditer un cours (chapitres, leçons, items), publier/dépublier, fixer le prix, et voir les enrôlements et la complétion.
  - Je veux intégrer des quiz existants (QtService) et des ressources du Dictionary/Media.
- Administrateur
  - Je veux gérer la monétisation (Stripe produits/prix), les permissions, les catégories, et consulter des rapports.
  - Je veux assurer la conformité (CSP, activité, logs), exporter les données, et configurer la bascule sous-domaine.

5. Parcours UX
- Découverte
  - Page /academie: liste de cours (carte: titre, courte description, durée, niveau, prix), filtres (gratuit/payant, niveau, thèmes), recherche Meilisearch.
  - Page de cours: description, objectifs, prérequis, syllabus (chapitres/leçons), FAQ (Dictionary), instructeur(s), avis (Phase ultérieure), CTA: S’inscrire (gratuit) ou Acheter.
- Inscription
  - Gratuit: bouton « S’inscrire » → Auth obligatoire (social/passkeys/2FA géré par Auth existant) → enrôlement créé.
  - Payant: bouton « Acheter » → Checkout Stripe Cashier → webhook confirme → enrôlement activé → accès.
- Leçons
  - Sidebar progression (chapitres/leçons/items), barre d’avancement %.
  - Item vidéo: iframe uniquement si enrôlé. Filigrane CSS (nom+date), info durée.
  - Item quiz: intégré via QtService; feedback sommaire; enregistrement d’une tentative QtAttempt.
  - Item doc: ressource texte/FAQ/sources; marquage complété manuel ou auto (au scroll/time).
- Progression
  - Auto: vidéo « vu » quand X% du temps joué OU arrivée à la fin (MVP: action manuelle « Marquer comme complété » + événement Lecture démarrée); quiz « complété » à la soumission; doc « complété » au clic « Terminer ».
  - Agrégation par cours, dernière activité, badges/streak via QuestProgress.
- Certificat
  - Délivré à 100% complété (ou seuil: ex. quiz ≥ 70% si configuré).
  - Page certificat (URL vérifiable), PDF (simple), métadonnées (heures, date, nom, hash).
  - JSON-LD Course/Certificate sous forme d’array PHP pour SEO.

6. Modèle de données (tables principales)
Module: Modules/Academy (nwidart)

- courses
  - id, slug, title, subtitle, summary, description, language (fr-CA), level (intro|inter|avancé), duration_minutes, image_media_id, visibility (public|unlisted|private), access_type (free|paid_one_time|paid_subscription), price_cents, currency, stripe_price_id, status (draft|published|archived), published_at, seo_jsonld (array PHP sérialisé), faq_dictionary_ids (json), tools_collection_id (nullable), created_by, updated_by.
  - Relations: hasMany chapters, hasMany enrollments, hasMany course_roles, belongsToMany terms (tags), morphMany media.
- chapters
  - id, course_id (FK), title, position, summary.
  - Relations: belongsTo course, hasMany lessons.
- lessons
  - id, chapter_id (FK), title, slug, position, summary, estimated_minutes.
  - Relations: belongsTo chapter, hasMany lesson_items.
- lesson_items
  - id, lesson_id (FK), type (video|quiz|h5p|doc), title, position, payload (json: selon type), estimated_minutes, is_required (bool), external_ref (ex.: screenpal_id ou quiz_key), poster_media_id (nullable).
  - Video payload: player_url, duration_seconds, transcript_media_id (nullable), captions (json), domain_lock (bool).
  - Quiz payload: qt_bank_key (fichier PHP), passing_score (int), attempts_allowed (int|null).
  - H5P payload (Phase 2): content_url (Media/CDN), settings (json minimal), standalone_player=true.
  - Doc payload: dictionary_entry_id ou rich_text (stored), attachments (media ids).
- course_roles
  - id, course_id, user_id, role (instructor|editor|owner).
- enrollments
  - id, user_id, course_id, status (pending|active|cancelled|refunded|expired), source (free|purchase|coupon|admin), cashier_id (nullable), amount_cents, currency, enrolled_at, expires_at (nullable), cancelled_at (nullable).
- completions
  - id, user_id, course_id, lesson_item_id, status (started|completed), score (nullable), qt_attempt_id (nullable), started_at, completed_at.
- progresses
  - id, user_id, course_id, percent (0-100), last_lesson_item_id (nullable), last_activity_at, required_total (int), required_completed (int).
- certificates_issued
  - id, user_id, course_id, serial, verification_hash, issued_at, hours_earned, final_score (nullable), public_url_slug.
- term_course (pivot si Terms déjà existent)
  - course_id, term_id.
- activity/logging via ActivityLog existant (pas de table nouvelle ici).

Références hors module:
- users (Auth existant).
- tools/tool_collections (Bookmarks/ToolCollection).
- terms/taxonomy si déjà en place.
- QtAttempt (QtService) lié via completions.qt_attempt_id.
- Media et médias via Spatie MediaLibrary (morph).

7. Architecture & composants (brancher les briques existantes)
- Auth: requis pour enrôlement et accès aux leçons. Social/passkeys/2FA inchangés. Middleware auth sur routes internes.
- Rôles Spatie: mapping global + course_roles:
  - Permissions globales (voir section 8).
  - Droit d’édition contextuel par course via course_roles.
- QtService (quiz):
  - Items de type quiz référencent des banques PHP existantes via qt_bank_key.
  - Soumission crée QtAttempt; scoring ramené pour Completions.score; gating « complété » selon passing_score si configuré.
- Gamification QuestProgress:
  - Incrémenter streak/badges sur complétions (events).
- Dictionary:
  - Doc items: lier des entrées (FAQ, sources, contenus riches).
  - Page de cours: bloc FAQ alimenté par Dictionary si faq_dictionary_ids.
- Bookmarks/ToolCollection:
  - Associer des outils à un cours (outils recommandés).
- Newsletter Automation (EmailWorkflow/WorkflowEnrollment):
  - Triggers sur EnrollmentCreated, CertificateIssued pour séquences (ex.: onboarding, upsell).
- Stripe Cashier:
  - Paiements one-time (via Price) ou abonnement (si requis). Webhooks pour activer enrollment.
- Media (Spatie MediaLibrary):
  - Posters, assets doc, transcripts, fichiers .h5p (Phase 2), image de cours.
- Search (Meilisearch):
  - Indexer courses (titre, résumé, tags, niveau), leçons (titres) pour auto-complétion/filtre.
- Module API:
  - Exposer en lecture (liste de cours publics, progression de l’utilisateur authentifié) pour front/SPA minimal si nécessaire.
- ActivityLog:
  - Traçage d’événements (enrollment, playback affiché, quiz soumis, certificat émis).

8. Sécurité / rôles / permissions
Nouvelles permissions (Spatie):
- academy.view (voir l’Académie).
- academy.courses.create / .update / .delete.
- academy.courses.publish.
- academy.lessons.manage (créer/éditer chapitres, leçons, items).
- academy.instructors.assign (gérer course_roles).
- academy.enrollments.manage (ajouter/retirer enrôlements).
- academy.certificates.issue (forcer émission).
- academy.reports.view (accès aux rapports/exports).
- academy.quizzes.manage (Phase 2, édition DB).
- academy.h5p.manage (Phase 2).

Rôle par défaut:
- member: academy.view, accès aux cours gratuits (après login), progression personnelle.
- editor: peut gérer contenu de ses cours si course_roles.role in [editor,instructor].
- admin/super_admin: toutes permissions.

Gating vidéo et contenu:
- Règle serveur: un item n’est rendu que si:
  - Cours publié ET (course.visibility = public OU utilisateur a permission spécifique).
  - Utilisateur enrôlé actif OU cours gratuit avec compte.
- Iframe ScreenPal jamais rendu si non autorisé. À la place: CTA (s’inscrire / acheter).
- Instructeurs ont accès preview à leurs cours (course_roles).

9. Protection vidéo (combo détaillé)
- Hébergement:
  - Vidéos ScreenPal en mode non répertorié/privé.
  - Verrouillage par domaine via ScreenPal et/ou CNAME dédié (ex.: video.laveille.ai mappé chez ScreenPal).
- CSP:
  - frame-ancestors: restreint à https://laveille.ai et https://academie.laveille.ai (anticipation sous-domaine).
  - script-src/style-src incluent les CDN requis (ScreenPal player) minimalement.
- Gating applicatif:
  - Iframe seulement si membre connecté et enrôlé au cours (vérification serveur).
  - Masquage complet de l’URL d’embed pour visiteurs non autorisés (pas de rendu, pas de DOM).
- Filigrane CSS:
  - Overlay fixe semi-transparent (nom complet de l’utilisateur, date/heure locale) sur le conteneur vidéo (au-dessus de l’iframe, positionné en multiples coins pour limiter recadrage).
  - C’est un dissuasif, pas une protection totale (capture d’écran reste possible).
- Pas de proxy vidéo:
  - Aucune retransmission côté serveur; on s’appuie sur verrou domaine et gating.
- Limitations connues:
  - Aucune protection contre capture d’écran; risque accepté avec mitigations (watermark, domaine, gating).

10. Observabilité (events, analytics, export)
Événements (dispatch + ActivityLog):
- CoursePublished
- EnrollmentCreated, EnrollmentActivated, EnrollmentCancelled
- LessonItemViewed, VideoIframeRendered
- LessonItemCompleted (avec type, score)
- QuizAttemptSubmitted (avec réussite/échec)
- ProgressUpdated
- CertificateIssued
- PaymentSucceeded/Failed (Cashier)

Analytics minimales:
- Taux d’activation post-achat.
- Progression moyenne par cours, taux de complétion, temps estimé consommé.
- Heatmap d’abandon par leçon (via events).
- Tableau de bord admin (sommaire par cours, période).

Exports CSV:
- Enrôlements (user, cours, statut, source, date).
- Progressions (percent, last_activity_at).
- Résultats quiz (score, tentative).
- Certificats (serial, date, user).

11. Stratégie de tests
- Unitaires/Intégration (Pest)
  - Modèles et relations (Course->Chapters->Lessons->Items).
  - Règles d’éligibilité: visibilité/publish, gating d’accès aux items.
  - Enrôlement: gratuit/payant (mock Cashier events).
  - Progression: calcul percent, required_total vs required_completed.
  - Completions: quiz (QtService) — mapping score et passing.
  - Permissions: course_roles vs Spatie permissions.
  - Émission certificat à 100% complété.
- E2E (Playwright)
  - Découverte cours → inscription gratuite → consultation vidéo (watermark visible) → quiz → progression → certificat.
  - Parcours payant: checkout Stripe en mode test → accès activé via webhook simulé → visionnement → complétion.
  - Non-auth: pas d’iframe vidéo ni d’accès aux contenus gated.
  - Recherche Meilisearch: résultats pertinents, filtres.

12. Plan de déploiement par phases
- Phase MVP
  - Module Academy (nwidart) sous /academie.
  - Types: video, quiz (QtService banques PHP), doc.
  - Enrôlement gratuit/payant (Cashier).
  - Progression/Completions, Certificat simple.
  - Gating vidéo + combo sécurité (CSP, CNAME, watermark).
  - Search Meilisearch, Observabilité, Exports.
- Phase 2
  - H5P via h5p-standalone (CDN), aucun Composer; stockage fichier .h5p via MediaLibrary/CDN; rendu player.
  - CRUD quiz en BD (éditeur interne) avec migration progressive des banques PHP.
  - Rapports améliorés (taux réussite par question, analyse itemisée).
  - UX auteur: duplication de cours, brouillons d’items, prévisualisation.
- Phase 3
  - Bascule sous-domaine academie.laveille.ai (Route::domain, SESSION_DOMAIN=.laveille.ai).
  - Gradebook avancé (par cohorte, export multi-cours).
  - Intégrations SCORM/xAPI si justifié (lecteur JS, pas de Composer).
  - Avis/évaluations cours, partage de certificat (LinkedIn) si opportun.

13. Critères d’acceptation TESTABLES (checklist)
- [ ] Un visiteur voit /academie, peut rechercher/filtrer des cours publics.
- [ ] Un membre peut s’inscrire à un cours gratuit; l’enrôlement passe à active.
- [ ] Un membre peut acheter un cours payant; à réception webhook, l’enrôlement passe à active.
- [ ] Un membre non enrôlé ne voit jamais l’iframe vidéo ni l’URL d’embed dans le DOM.
- [ ] L’iframe vidéo affiche un filigrane visible avec nom+date.
- [ ] Un quiz QtService est jouable et enregistre QtAttempt; le score remonte dans Completion.
- [ ] La progression (%) s’actualise quand un item requis est complété; 100% quand tous les items requis sont complétés.
- [ ] Un certificat est généré à 100% complété; l’URL publique est vérifiable via hash.
- [ ] Les événements clés (EnrollmentCreated, LessonItemCompleted, CertificateIssued) sont loggés dans ActivityLog.
- [ ] Un admin peut exporter CSV des enrôlements, progressions, certificats.
- [ ] La recherche Meilisearch retourne des cours pertinents par titre/termes.
- [ ] Les permissions bloquent l’édition aux non-autorisés; un instructeur ne peut éditer que ses cours.

14. Risques & mitigations
- Fuite de vidéo (partage d’URL d’embed)
  - Mitigation: verrou domaine/CNAME ScreenPal, gating serveur, CSP frame-ancestors, watermark. Risque résiduel: capture d’écran.
- Limitations CI (pas de nouvelles deps Composer)
  - Mitigation: tout en natif Laravel + libs JS via CDN (H5P Phase 2). Audit régulier des assets CDN.
- Paiements/Taxes
  - Mitigation: s’appuyer sur Cashier et configurations Stripe (taxes, reçus). Tests webhook robustes.
- Performance recherche/indexation
  - Mitigation: index Meilisearch lean; jobs asynchrones; monitoring.
- Évolution quiz (banques PHP → BD)
  - Mitigation: plan de migration Phase 2 avec rétrocompatibilité; mapping stable qt_bank_key.
- Sécurité H5P (Phase 2)
  - Mitigation: h5p-standalone sandboxé, CSP stricte, validation des fichiers .h5p, pas d’upload public.
- Fragmentation domaine (bascule sous-domaine)
  - Mitigation: SESSION_DOMAIN=.laveille.ai, CORS/CSP prévus, tests cookies/auth.

15. Alternatives rejetées
- Intégration hybride Moodle (74/100): lourdeur, double auth/UX, coût d’intégration/maintenance, surdimensionné pour le scope, et aucune instance Moodle existante à préserver.
- Reskin d’une plateforme externe / thème Moodle (60/100): contrôle limité, branding superficiel, ne règle pas le rejet de l’UX Moodle, verrou de données.
- Headless Moodle (68/100): complexité API (quiz/H5P/completion mal exposés), temps d’intégration, peu de valeur vs besoins MVP.
- App séparée: surcharge DevOps, duplications d’auth/rôles/paiements, time-to-market plus long.
- Proxy vidéo (55/100): coût infra/bande passante, complexité, bénéfice sécurité marginal vs verrou domaine+gating+watermark — décision arrêtée de NE PAS proxifier.

Annexes — Détails fonctionnels (sans code)
- Routes et structure
  - Publiques: /academie (listing, cours publié).
  - Auth requise: /academie/cours/{slug}/learn, items.
  - Admin/éditeur: /academie/admin (CRUD cours, chapitres, leçons, items, prix).
- Indexation Meilisearch
  - Course: id, title, summary, level, tags (terms), visibility=public, status=published.
  - Boost: popularité (enrôlements actifs), récence (published_at).
- JSON-LD (array PHP)
  - Course, LearningResource, EducationalOccupationalCredential (certificat) générés côté serveur; pas de spatie/schema-org.
- UX accessibilité
  - Navigation clavier, transcripts/captions via MediaLibrary pour vidéos, contrastes conformes AA.

---

## Auto-revue de la spec (étape 7 du skill, par le superviseur)
- Placeholders « TBD » non justifiés : aucun.
- Contradictions internes : aucune détectée (freemium cohérent avec access_type ; H5P bien isolé en Phase 2).
- Ambiguïté à clarifier à l'implémentation : règle exacte de complétion vidéo (MVP = bouton « Marquer comme complété » + event « lecture démarrée », car le temps de visionnage ScreenPal en iframe n'est pas fiable côté hôte — assumé).
- Scope creep : maîtrisé (gradebook/SCORM/xAPI explicitement hors MVP).
- Critères d'acceptation : testables (checklist §13).
- Sécurité/permissions : couvertes (§8, §9) ; risque résiduel capture d'écran assumé.
- Rétro-compatibilité : aucune rupture (module additif, désactivable via modules_statuses.json ; aucune brique existante modifiée).
- Contrainte CI composer : respectée (natif + JS/CDN ; JSON-LD array PHP).
- Dépendance implicite à vérifier avant exécution : capacité exacte du PLAN ScreenPal pour le verrou de domaine/CNAME (selon abonnement) — à confirmer côté compte ScreenPal.
