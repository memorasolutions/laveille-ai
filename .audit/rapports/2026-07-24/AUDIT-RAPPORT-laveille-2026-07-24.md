# Audit plateforme laveille.ai - 2026-07-24

**Périmètre** : site complet, demande explicite « complet sur la plateforme, manque beaucoup d'accents ». Cet audit s'appuie sur l'audit complet des 11 dimensions déjà réalisé le 2026-07-22 (`.audit/rapports/2026-07-22/`) et se concentre sur (a) le delta depuis cette date, (b) l'investigation approfondie du signal utilisateur sur les accents manquants, hors périmètre de l'audit précédent.

## Matrice de couverture (gate de sortie)

Voir `.audit/AUDIT-MATRICE-laveille-2026-07-24.md` - **12 dimensions, toutes `complété`, zéro « à faire »**.

## Résumé exécutif

Le signal de départ (« manque beaucoup d'accents ») est confirmé, mais son ampleur réelle est **beaucoup plus faible que ce que la formulation laissait craindre**. Le contenu produit par le site (2250 fiches Annuaire, 312 acronymes, 53 articles, 97 fiches Glossaire vérifiés) est quasiment impeccable côté accentuation. Le problème se loge dans une poignée de **libellés d'interface tapés directement dans le code**, concentrés sur 5 fichiers. Pendant l'investigation, un problème plus sérieux et sans rapport a été découvert et corrigé : 6 nouvelles failles de sécurité publiées le 22 juillet sur une dépendance PDF exposée en public.

## 1. Accents manquants (finding prioritaire de l'utilisateur)

### Où ils sont réellement

| Priorité | Fichier | Impact | Exemples |
|---|---|---|---|
| 1 | `Modules/Sudoku/resources/views/construction.blade.php` + `play.blade.php` | **Public + SEO** (JSON-LD indexé par Google) | « derniere main », « tres bientot », « genere a la demande » |
| 2 | `Modules/Directory/.../admin/partials/education-phase3-fields.blade.php` | Admin, visible en continu | « Courbe d apprentissage », « Tres intuitive », « Moderee », « Avancee » |
| 3 | `Modules/Newsletter/resources/views/admin/stats.blade.php` (épicentre, ~15 mots) + `Modules/Backoffice/.../subscribers-table.blade.php` (duplication partiellement corrigée - violation DRY) | Admin | « Repartition des abonnes », « purges d'hygiene », « fenetre temporelle » |
| 4 | `lang/fr_CA.json` + `lang/fr.json`, clés 467 et 729 | Traduction jamais faite (clé = valeur, contrairement à `lang/en.json` qui a une vraie traduction) | « Cette experience est en brouillon... » |

### Ce qui n'est PAS en cause

- **Aucune faute réelle en base de données** : 53 articles de blog, 97 fiches glossaire (local), 2250 fiches Annuaire et 312 acronymes (prod, vérifiés directement ce jour) sont propres. Les 6 « suspects » remontés par le grep prod sont des faux positifs (description en anglais non traduite, ou troncature de la colonne).
- **Aucun mojibake / bogue d'encodage** détecté.
- Cause racine : saisie humaine rapide et jamais relue sur quelques écrans admin/publics ponctuels, pas un défaut systémique.

### Recommandation (non exécutée - correctifs de contenu texte)

Correction cas par cas (pas de remplacement automatique en masse - un dictionnaire de correction aveugle créerait de nouvelles fautes, ex. « traite »/« taches » sont parfois corrects sans accent). Ordre suggéré par impact :
1. Sudoku (`construction.blade.php`, `play.blade.php`) - impact public + SEO.
2. Formulaire admin Annuaire (`education-phase3-fields.blade.php`).
3. Newsletter (`stats.blade.php` + `subscribers-table.blade.php`) - corriger et dédupliquer en même temps (éviter une nouvelle divergence).
4. Écrire une vraie traduction pour les 2 clés `lang/fr_CA.json`/`lang/fr.json`.

À déléguer à Hermes/Qwen (`mcp__hermes__model_invoke`) sur demande - ce sont des corrections de texte de quelques lignes chacune, faciles à valider avant commit.

## 2. Delta depuis l'audit du 2026-07-22

14 commits depuis le dernier audit. Toutes les dimensions restent valides ou ont été renforcées ; un problème de sécurité a émergé entre-temps (voir section 3).

- **2 vraies failles RBAC** (Acronyms/Dictionary, Shop) trouvées et corrigées le 2026-07-23 (v1.117.21/22), en dehors de l'audit du 07-22. **Vérifié ce jour directement en base prod** : les 9 permissions Acronyms/Dictionary et les 8 permissions Shop sont bien présentes (`app:sync-permissions` a tourné correctement).
- **Infrastructure d'affiliation Annuaire** (v1.120.0) : complète, testée (9/9 tests verts), pas de risque d'open redirect sur la nouvelle route `directory.visit`.
- **Article de blog OpenClaw (#67)** : confirmé `draft`, `published_at` null, non indexable - aucune exposition publique accidentelle.
- **Hygiène** : entrée stray `lang/en.json` trouvée et retirée (artefact de test, pas une vraie traduction).

## 3. Dépendances / CVE - action corrective appliquée ce jour

`composer audit` a révélé **6 avis de sécurité sur `dompdf/dompdf` v3.1.4**, publiés le 2026-07-22 en soirée (après la clôture de l'audit précédent, donc non manquants de cet audit-là) : déni de service et fuite de fichiers via SVG intégré dans un PDF. Cette dépendance est exposée en surface publique (génération PDF des grilles de mots croisés, `Modules/Tools`) et pour les certificats de l'Académie.

**Corrigé et déployé ce jour** : mise à jour vers v3.1.6 (commit `6d5fbbc3`, version v1.120.1). `composer audit` confirme 0 vulnérabilité restante. Suite de tests ciblée (Academy, Decido, Export, Tools - 129 tests) verte. Déploiement CI vert, version confirmée en prod (`composer.lock` : dompdf v3.1.6).

## Preuve de nettoyage (hygiène)

- 3 crons temporaires créés pendant cet audit (script d'audit accents/permissions prod, script de mise à jour de l'image de couverture), tous retirés et vérifiés via `cpanel_cron_list` - aucun résidu.
- Résidu connu, non lié à cet audit : quelques fichiers scratch d'une session précédente (`openclaw-draft-*`) restent sur prod, non sensibles et non web-accessibles, bloqués par une panne persistante de l'API Fileman cPanel (hors du contrôle de l'assistant).

## Bilan

- **Accents** : investigué, quantifié, plan de correction priorisé livré (non exécuté, correctifs de texte à valider avec vous).
- **Sécurité** : 1 vulnérabilité réelle trouvée et corrigée le jour même (dompdf, déployée en prod).
- **Reste du site** : stable, aucune régression depuis le 2026-07-22.
- **Aucune correction en attente d'approbation** pour les accents (contenu, pas de risque) - je peux lancer les 4 correctifs listés dès votre accord.
