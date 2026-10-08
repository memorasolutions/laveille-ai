# État de session - La veille de Stef v2

> Fichier UNIQUE de reprise (réécrit, jamais empilé). Dernière mise à jour : 2026-10-08, ~09h20 Québec (13:20 UTC).

## 1. Où on en est (terminé et PROUVÉ)

- **Boutique Gelato : flux catalogue RÉPARÉ et DÉPLOYÉ en production (v1.323.0).** La cause des commandes qui envoyaient toujours le même chandail avec un logo du serveur est corrigée : routage par `storeProductVariantId` du vrai produit Gelato, repli du logo brut SUPPRIMÉ, échec bruyant si le mapping manque. CI GitHub verte, déploiement cPanel réussi, migrations additives passées. Version servie en prod vérifiée : `v=1.323.0` dans les assets.
- **Durcissement zone argent tenu par 4 rondes adversariales** (Codex x2, puis fable) + correctifs A1 (`CanadianProvince` : province dérivée du code postal, pas du champ déclaré) et A2 (`settlePaidSession` : marque payé UNIQUEMENT si `payment_status=paid` ET montant encaissé = total au cent près). Verdict fable : "SÛR DERRIÈRE DRAPEAU OFF".
- **Tests tous verts** : 200 tests Shop (1182 assertions) + testsuite Architecture/Unit 95 (le seul trou du déploiement était le preset sécurité Pest, hors du filtre Shop : 2 usages non-crypto `sha1` exemptés avec justification dans `tests/Architecture/ArchTest.php`, même convention que les entrées existantes).
- **Accès restreint au fondateur (#2987)** : boutique en maintenance (503 pour le public) + middleware FounderOnly, drapeaux zone-argent OFF (`shop.gelato_zero_erreur`, `shop.gelato_editor`), réversibles par `.env`.
- **Cause racine expliquée** : `docs/boutique-gelato-pourquoi-ca-echouait.html` (3 causes : repli `print_file_url` brut, absence de synchro catalogue, libellé de variante doublé).
- **Vrai produit Gelato confirmé À LA SOURCE** (MCP gelato, lecture) : magasin "La veille.ai", T-shirt Gildan 5000, 72 variantes (9 couleurs x 8 tailles) toutes `connected`, mockup Gelato réel, `isReadyToPublish`. C'est LUI que la vitrine affichera dès l'ouverture.
- **P1 d'énumération de commande (`/confirmation/{order}`)** : re-vérifié DORMANT en prod. `ShopMaintenanceMode` tourne APRÈS `SubstituteBindings` (un id de commande réel renvoie 503 pour un non-admin), et la branche ajoute les contrôles de propriété. Pas de fuite ouverte.
- **AdSense en-tête (#2991)** : garde-fou `lvPurgeHeaderAds` déployé (v1.322.1), il retire les auto-ads injectées dans l'en-tête. DOM prod vérifié : 0 auto-ad dans l'en-tête.

## 2. Ce qui BLOQUE en attendant une décision du fondateur

- **Ouverture commerciale de la boutique (#2984-D, #2988)** : consignée dans `QUESTIONS-CLAUDE.html` entrée 328. Trois décisions zone-argent/légales qui lui reviennent :
  1. **Ouvrir la boutique** (lever la maintenance) OU se connecter en super-admin pour que je lance la synchro + prenne la capture. Je NE peux PAS me connecter à sa place (OTP courriel + frontière 1Password).
  2. **Prix et devise** : Gelato en EUR, boutique en CAD. Fixer la marge cible (%) avant d'exposer un prix.
  3. **Taxe M3 (Loi 25)** : avant toute vente PUBLIQUE, décider quelles provinces taxer (recommandation : lancer Québec seul, taxe exacte, puis brancher la grille TVH). Sans réponse : rien ne bouge, aucun prix/taxe faux exposé, aucune commande ne part.
- **AdSense mode auto (#2983 + #2991 racine permanente)** : le correctif définitif (couper/borner le mode auto) est au tableau de bord Google AdSense, pas dans le code. BLOQUÉ : session Google à reconnecter par le fondateur.
- **Garde-crontab gmemora (#2990)** : acquittement à faire par le fondateur (non bloquant pour laveille).

## 3. Prochaine action (sans dépendre du fondateur)

- **#2989 - Packaging exportable du module Gelato boutique (phase 2)** : contrats, config, README d'installation pour réutiliser le module dans d'autres projets Laravel. Seul todo Gelato restant qui ne dépend pas d'une décision du fondateur.
- Todos en attente de date : #2924 (FTC, 14 oct), #2931 (Reddit, 15 oct), #2977 (veille actu2, 20 oct), #2981 (badge CVBooster).

## 4. Sauvegarde / versioning

- `feature/gelato-zero-erreur` fusionnée proprement dans master (worktree de déploiement retiré). Poussé sur `origin` (GitHub, CI + déploiement) ET `forge` (Pi). v1.323.0 en prod.
- Migrations additives nullable (réversibles à l'ajout). Aucune donnée utilisateur touchée. Aucune mutation de prod non autorisée. Backup du journal avant écriture (`QUESTIONS-CLAUDE.html.bak-gelato-*`).
