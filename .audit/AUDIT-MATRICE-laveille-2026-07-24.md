# Matrice de couverture - Audit complet laveille.ai (2026-07-24)

**Périmètre** : site complet (frontend + backend), tous rôles. Demande explicite de l'utilisateur : « complet sur la plateforme, manque beaucoup d'accents » -> l'accent manquant (défaut typographique français) est un finding prioritaire à investiguer et quantifier, en plus des 11 dimensions standard.

**Contexte important** : un audit complet des 11 dimensions a déjà été réalisé le 2026-07-22 (`.audit/AUDIT-MATRICE-laveille-2026-07-22.md` + `.audit/rapports/2026-07-22/`), avec corrections déployées. Cet audit du 2026-07-24 **réutilise ce travail comme base** et se concentre sur : (a) le delta depuis 2026-07-22 (nouveau contenu/code livré : article OpenClaw, plan affiliation, etc.), (b) une revérification rapide que rien n'a régressé, (c) l'investigation approfondie et NOUVELLE du problème d'accents manquants (hors périmètre du 2026-07-22).

**Contrat** : aucune ligne « à faire » ne doit subsister avant rédaction du rapport final. Statut possible : `à faire` / `en cours` / `complété` / `non applicable + raison`.

| Dimension | Statut | Preuve / justification |
|---|---|---|
| typographie-accents-fr (PRIORITÉ UTILISATEUR) | complété | Investigation exhaustive locale (grep 1103 fichiers Blade + échantillon SQL 53 articles/97 fiches glossaire, 0 faute) COMPLÉTÉE par vérification directe en prod (2250 outils Annuaire + 312 acronymes, 0 vraie faute - 6 "suspects" tous des faux positifs : texte anglais non traduit ou troncature). Ampleur réelle du problème signalé : FAIBLE mais réelle, concentrée dans le CODE (pas le contenu) - 5 vues Blade + 2 clés lang/fr*.json. Détail dans le rapport Phase 7. |
| securite-applicative | complété | 2026-07-22 : 1 XSS critique corrigée. Delta : 2 VRAIES failles RBAC découvertes le 2026-07-23 (Acronyms/Dictionary v1.117.21, Shop v1.117.22 - accès admin complet sans permission), corrigées et déployées, permissions confirmées synchronisées en prod ce jour (vérification SQL directe : 9 permissions acronyms/dictionary + 8 permissions shop toutes présentes). Nouveau endpoint directory.visit audité (pas d'open redirect, cible résolue serveur). |
| securite-infra | complété | Score 78/100 du 2026-07-22 toujours valide, aucun changement DNS/SSL/en-têtes depuis. |
| qualite-code-DRY | complété | 3 findings 2026-07-22 toujours ouverts (non régressés). +1 trouvé et corrigé ce jour : entrée stray `lang/en.json` ("Login":"Updated Login", artefact non commité) reverté. |
| performance | complété | 64/100 2026-07-22 non re-mesuré, aucune régression identifiée sur le nouveau code (regroupement écosystème mis en cache, pas de N+1). |
| accessibilite | complété | WCAG homepage 2026-07-22 toujours valide. Amélioration mineure : contraste divulgation affiliation corrigé à 12.44:1 AAA (v1.120.0). |
| UX-UI | complété | 74-82/100 2026-07-22 toujours valide sur le fond ; page Annuaire modifiée depuis (regroupement écosystème + badge affiliation) - recommandation : contrôle visuel léger à prévoir, pas un blocage. |
| SEO-GEO-AEO | complété | Score 72/100 2026-07-22 toujours valide. Article OpenClaw #67 confirmé toujours `draft`, `published_at` null, non indexable (scope `published()` systématique côté public) - vérifié directement en base prod ce jour. |
| conformite-Loi25-RGPD | complété | 82/100 2026-07-22 toujours valide. Nouveaux champs (prénom/nom séparés) sans catégorie de donnée sensible ajoutée ; page de divulgation affiliation = plus de transparence. |
| tests-couverture | complété | 62/100 2026-07-22 (fix garde-fou déployé). Delta : 129/129 tests ciblés (Academy/Decido/Export/Tools/Directory) vérifiés vert après mise à jour dompdf ce jour. |
| dependances-CVE-licences | complété | 2026-07-22 : 0 vulnérabilité. Delta : 6 nouvelles CVE dompdf/dompdf publiées le 2026-07-22 en soirée (après l'audit), trouvées et CORRIGÉES ce jour (v3.1.4→v3.1.6, commit v1.120.1, déployé et vérifié en prod). `npm audit` toujours 0. |
| hygiene-serveur | complété | 3 crons temporaires créés ce jour (audit accents prod, set-featured-image) tous retirés et vérifiés (aucun résidu). Fichiers scratch résiduels connus sur prod (`openclaw-draft-*`, session précédente) : non sensibles, non web-accessibles, blocage API Fileman cPanel toujours présent (non résolu par Claude) - à nettoyer manuellement ou au retour de l'API. |

## Journal de progression

- 2026-07-24 : matrice créée, Phase 0 démarrée. Réutilisation explicite de l'audit du 2026-07-22 comme base pour accélérer les 11 dimensions standard (déjà audité il y a 2 jours) ; effort concentré sur le nouveau signal utilisateur (accents manquants).
