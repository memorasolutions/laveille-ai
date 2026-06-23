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
- [ ] F2 · Question **Cloze / texte à trous** (sous-questions intégrées)
- [ ] F3 · Question **Numérique** (réponse + tolérance/unités)
- [ ] F4 · Question **Glisser-déposer sur texte**
- [ ] F5 · Question **Essai** (correction MANUELLE, reliée au carnet)
- [ ] F6 · Mode quiz **Adaptatif** (réessai avec pénalité ; reporté de V1-f)

### VAGUE 4 — Activités
- [ ] F7 · Activité **Feedback / Sondage** (questionnaire non noté)
- [ ] F8 · Activité **Choice** (vote/sondage simple)
- [ ] F9 · Activité **Forum** (discussions, anti-spam)

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
| 2026-06-22 | **F1 Ordonnancement** | v335-337 | 584 tests (+13) | PASS A→F (ordre exact 8/8, partiel 4/8, non-régression, QuizAttempt OK) | 75/100 puis corrigé | Bug pré-existant B1 (minutes vides → TypeError ?int) corrigé v336. Audit : 0 critique ; H1 TOCTOU attempts + M1 bornes + M3 depth + M4 fieldset a11y + B1 Schema::hasColumn + M2 select perf → corrigés v337 (589 tests). Dette notée : B2 (agrég PHP), B3 (checkbox required), B4 (détail révision mode immédiat). |
| 2026-06-22 | (init du chantier) | v334 base | - | - | - | Journal + plan créés. Démarrage F1. |
