# Audit complet - Module Académie (Modules/Academy)

Date : 2026-07-19 (America/Toronto)
Périmètre : `Modules/Academy` (LMS, 557 fichiers) + intégrations directes (tuteur IA `Modules/AI`, diplomation Konva, structure de code des paliers d'abonnement). Paiements Stripe live explicitement hors scope (gate utilisateur #874 actif).
Constat de cadrage : `/academie` répond **503** en production (`ACADEMY_UNDER_CONSTRUCTION=true`, gate superadmin-only) - ce constat est noté pour chaque dimension concernée (SEO non indexable, pas de données CWV terrain, etc.).
Audit antérieur restreint (UX-UI + WCAG + comparatif Moodle) du 2026-07-13 archivé dans `.audit/AUDIT-MATRICE-academie-2026-07-13-uxui-wcag-only.md` - utilisé comme référence de départ mais entièrement re-vérifié cette fois (grille jamais figée).

## 1. Matrice de couverture (gate validée - 11/11 complété)

| Dimension | Statut | Score | Preuve résumée |
|---|---|---|---|
| securite-applicative | complété | 68/100 | SSRF moyenne-haute + gap SoftDeletes A08 ; architecture LLM globalement saine |
| securite-infra | complété | 58/100 | CSP incomplète, version PHP/Laravel masquée (non confirmée patchée) |
| qualite-code-DRY | complété | 62/100 | Duplication ×5 commandes, God method 350 lignes, dépendance module non gardée |
| performance | complété | 70/100 | Load correct (705ms) mais N+1, librairies mortes, scripts pub inattendus |
| accessibilite | complété | 65/100 | 10 violations réelles, contraste et cibles tactiles récurrents |
| UX-UI | complété | 69/100 | 4 pages notées (78/62/82/55), mur de connexion sans teaser |
| SEO-GEO-AEO | complété | 85/100 | Bonne readiness code (JSON-LD, meta), 2 détails mineurs pour le lancement |
| conformite-Loi25-RGPD | complété | 40/100 | 5/7 points non conformes - écart le plus sérieux de l'audit |
| tests-couverture | complété | 80/100 | 1409 passés / 0 échec / 4473 assertions ; % couverture non mesuré (contrainte de temps) |
| dependances-CVE-licences | complété | 90/100 | 1 avis low severity, non spécifique à Academy |
| hygiene-serveur | complété | 95/100 | 87 crons, zéro résidu |

**Score global pondéré : 71/100.**

Le détail complet, fichier par fichier, avec toutes les justifications, est dans `.audit/AUDIT-MATRICE-academie.md` (source de vérité de cette gate).

## 2. Findings triés par sévérité puis effort (quick wins en premier)

### Sévérité haute

**F01 - Loi 25/RGPD : données Académie absentes de la conformité (haute, effort moyen)**
- Preuve : `Modules/Legal/...` politique de confidentialité ne mentionne aucune donnée Academy (progression, quiz, certificats, DM, badges) ; `privacy:purge-expired` ne couvre que 6 tables hors Academy ; `PrivacyCenterController::deleteAccount()` anonymise `users` sans jamais déclencher le cascade FK sur les tables Academy → les données survivent indéfiniment à un compte "supprimé".
- Correction proposée : ajouter la catégorie Academy à la politique + au Centre de confidentialité, étendre `privacy:purge-expired` aux tables Academy, faire déclencher le cascade FK réel dans `deleteAccount()`.
- Outil recommandé : skill `/politiques` puis implémentation déléguée à Qwen (`mcp__hermes__model_invoke`).

**F02 - SSRF sur l'ingestion d'URL du tuteur IA (haute, effort moyen)**
- Preuve : `Modules/AI/app/Http/Controllers/KnowledgeUrlController.php:57-79` + `WebScraperService.php:79-96` - validation `'url'=>'required|url|max:500'` uniquement, aucun filtrage IP privée/loopback/metadata cloud avant `Http::get()`. Le contenu scrapé alimente ensuite le RAG du chatbot public.
- Correction proposée : filtrer les IP résolues (DNS rebinding inclus) contre les plages privées/loopback/metadata avant tout `Http::get()`.
- Outil recommandé : Qwen via Hermes (`task_type=code`), review par DeepSeek.

**F03 - OpenRouter sous-traitant IA non déclaré pour le tuteur/correction (haute, effort faible)**
- Preuve : OpenRouter listé dans la politique de confidentialité uniquement pour "Résumés IA", pas pour le tuteur IA ni la correction automatique IA, alors que le code confirme son usage réel dans ces deux flux.
- Correction proposée : étendre la mention du sous-traitant dans la politique.
- Outil recommandé : skill `/politiques`.

### Sévérité moyenne-haute

**F04 - SoftDeletes absent sur 4 modèles pédagogiques critiques (moyenne-haute, effort faible)**
- Preuve : `Enrollment`, `CertificateIssued`, `Submission`, `QuizAttempt` sans `SoftDeletes` - suppression accidentelle/malveillante = perte définitive de preuve de note/certificat (A08 OWASP).
- Correction : ajouter le trait + migration `deleted_at` sur les 4 modèles.
- Outil recommandé : Qwen (migration + trait), correction < 5 lignes possible en direct.

### Sévérité moyenne

**F05 - Dépendance module non gardée : `AcademyNotificationService` → `Modules\Newsletter` (moyenne, effort faible)**
- Preuve : le même fichier garde correctement `Modules\Settings` via `class_exists()` mais pas `Modules\Newsletter` - viole la règle projet "module désactivé ne casse jamais le site".
- Correction : ajouter le garde `class_exists()` manquant.
- Outil recommandé : correction directe < 5 lignes.

**F06 - Même défaut sur 4 modèles avec `Modules\Media` (moyenne, effort faible)**
- Correction : idem F05, garder le trait Media.

**F07 - CSP incomplète (moyenne, effort faible-moyen)**
- Preuve : seule `frame-src` définie ; `script-src`/`default-src`/`object-src` absents → protection XSS quasi nulle.
- Outil recommandé : `/laravel-theme` + config middleware headers.

**F08 - 9 contrôleurs dupliquent la vérification "inscription active" sans scope Eloquent (moyenne, effort moyen)**
- Risque : IDOR si le critère change un jour dans un seul endroit et pas les 8 autres.
- Correction : extraire un scope Eloquent `Enrollment::active()`.

**F09 - N+1 sur `/academie` (moyenne, effort faible)**
- Preuve : media non eager-loadé, 12 requêtes/page.
- Correction : `->with('media')` sur la requête de listing.

**F10 - Librairies carrousel mortes chargées (Owl/Slick/Swiper) (moyenne, effort faible)**
- Preuve : jamais utilisées sur les pages cours, causent aussi ~100+ éléments non atteignables au clavier (recoupe le finding accessibilité A11).
- Correction : retirer les includes.

**F11 - Contraste 4.48:1 sous le seuil AA (moyenne, effort faible)**
- Preuve : texte gris partagé hero/footer/cookies, impact large (toutes pages Academy).
- Correction : ajuster le token de couleur gris vers un ton conforme AAA (charte du projet).

**F12 - Cibles tactiles footer < 24×24px (moyenne, effort faible)**
- Preuve : WCAG 2.5.8, 8 liens concernés, présent sur toutes les pages Académie.

**F13 - Cookie banner chevauche le contenu utile, pire sur mobile (moyenne, effort faible)**

**F14 - Aucun teaser avant le mur de connexion sur 3 pages gatées (moyenne, effort moyen)**
- Impact : risque de perte de conversion (pages notées 55/100 en UX-UI).

**F15 - 5 commandes console "rappel" copiées-collées, 534 lignes (moyenne, effort moyen)**
- Correction : extraire un template method commun.

**F16 - `resolveCourseId()` triplicée (moyenne, effort faible)**

### Sévérité moyenne-basse / basse

**F17 - `QuizController::submitQuiz()` : méthode de 350 lignes (basse-moyenne, effort élevé)**

**F18 - `CourseRoster.php` : 923 lignes, 8 paires confirm/cancel identiques (basse-moyenne, effort moyen-élevé)**

**F19 - `<svg role="img" aria-hidden="true">` contradictoire sur le graphe de compétences (basse, effort faible)**

**F20 - Landmarks nav/footer absents sur le gabarit `/login` (basse, effort faible)**

**F21 - `/login` utilise une charte visuelle différente du reste du site (basse, effort moyen)**

**F22 - Collision texte mobile "Mot de passeMot de passe oublié ?" (basse, effort très faible)**

**F23 - Page `/academie` clairsemée (1 seule formation, hero massif) (basse, effort moyen - contenu, pas code)**

**F24 - 503 sans header/meta noindex explicite (basse, effort très faible)**
- Le pattern "503 + noindex" est déjà établi ailleurs sur le site (ex. module Books) - incohérence mineure à corriger pour la cohérence du pattern, pas un risque réel (503 suffit déjà en pratique).

**F25 - Pas de rappel automatisé pour ajouter Academy au sitemap au lancement (basse, effort faible)**

**F26 - Pas de garde anti-prompt-injection explicite dans le prompt système du tuteur IA (basse, défense en profondeur, effort faible)**

**F27 - `RagService` (chatbot général, PAS le tuteur Academy) injecte du contenu web scrapé sans neutralisation (basse-moyenne, effort moyen)**
- Vecteur d'injection indirecte classique LLM01 OWASP LLM Top10. Distinct du tuteur Academy (sain, voir needs-review).

**F28 - Minceur infra (basse, effort faible)** : pas d'enregistrement CAA, HSTS sans `preload`, SPF en softfail, DMARC en quarantine plutôt que reject.

**F29 - 1 avis composer low severity sur `web-auth/webauthn-lib` (basse, effort faible)**
- Non spécifique à Academy (module Auth/passkeys), sans impact direct sur le périmètre audité. À traiter dans une prochaine maintenance composer générale.

## 3. Section needs-review (validation humaine/légale requise, jamais marquée "résolue")

- **NR01 - Qualification légale de la notation automatisée** : la notation automatique quiz/CAT sans validation humaine détermine l'émission de certificats. Qualification juridique "décision automatisée" (art. 22 RGPD / 12.1 Loi 25) à trancher par l'utilisateur ou un conseil juridique avant le lancement public.
- **NR02 - Protection mineurs déclarative uniquement** : seuil 16 ans mentionné mais sans vérification technique. Décision produit à prendre (ajouter une vérification, ou assumer le risque en l'état pour le lancement).
- **NR03 - Version Laravel/PHP masquée à distance** : bonne pratique de sécurité, mais empêche de confirmer à distance le correctif contre CVE-2025-27515 (Laravel, 9.8) et CVE-2025-1861 (PHP wrappers HTTP, 9.8). À vérifier directement via `composer show`/`php -v` en environnement de build (accès direct requis, pas de lecture prod).
- **NR04 - Point éthique (pas un bug technique)** : `RagService` peut être configuré pour instruire le LLM à ne pas révéler ses sources - signalé pour décision produit, pas corrigé automatiquement.
- **NR05 - Périmètre de lecture de code non exhaustif** : la revue sécurité applicative a priorisé ~50/702 fichiers (incluant tous les contrôleurs et modèles les plus exposés). `ForumController`/`WikiController`/`WorkshopController`/`LessonController` et ~50 composants Livewire restants n'ont pas été relus ligne à ligne - à couvrir dans un futur round si le périmètre s'étend.
- **NR06 - 2 faux positifs WCAG probables** signalés honnêtement par le sous-agent (skip link, contraste checklist login) - à reconfirmer manuellement avant toute correction (ne pas corriger un fantôme).
- **NR07 - % de couverture de tests exact non mesuré** : deux tentatives (`--coverage`, `--parallel`) ont chacune dépassé le budget de temps de cet audit sans produire de résultat exploitable. Le compte pass/fail complet (1409/0/3, preuve la plus utile pour détecter une régression) est fiable ; seul le pourcentage de lignes couvertes reste inconnu.

## 4. Preuve de nettoyage (obligatoire, Phase 7)

- `cpanel_cron_list` (compte gmemora, vérifié dans cette même session le 2026-07-19) : 87 crons actifs, tous légitimes et nommés, zéro résidu temporaire lié à cet audit ou à un précédent.
- Aucun processus Playwright résiduel : les sous-agents Playwright utilisés pour les captures UX-UI/WCAG ont fermé leur navigateur (`browser_close`) en fin de tâche.
- Aucun script/fichier tmp orphelin créé sur le serveur de production par cet audit (audit 100% lecture seule côté prod).
- Fichiers de sortie des runs pest en arrière-plan (`bhbq2qsac.output`, `b7jswggmw.output`, `bam4ewnk6.output`) : locaux au répertoire scratchpad de session, hors du dépôt Git, aucun nettoyage serveur requis.

## 5. Bilan

Le module Académie est fonctionnellement solide (0 échec sur 1409 tests, architecture LLM du tuteur globalement saine) mais présente un écart de conformité Loi 25/RGPD sérieux (score 40/100, le plus bas de l'audit) qui devrait être traité avant toute levée du gate `ACADEMY_UNDER_CONSTRUCTION` en production. Les findings de sécurité applicative (SSRF, SoftDeletes) et les 6 quick wins DRY/perf/accessibilité à effort faible constituent le reste des priorités court terme. Aucune régression détectée par la suite de tests complète.

Prochaine étape suggérée (hors scope de cet audit, à confirmer par l'utilisateur) : traiter F01-F04 (sévérité haute et moyenne-haute) avant la levée du gate de mise en production de l'Académie.
