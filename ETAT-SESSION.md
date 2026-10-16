# État de session - La veille de Stef v2

Dernière mise à jour : 15 octobre 2026, 19h28 Québec (23:28 UTC). Production en **v1.355.1** (déployée et PROUVÉE).

## Où on en est (livré et prouvé)

- **v1.355.1 DÉPLOYÉE ET SERVIE EN PROD.** CI run 38823079980 + Deploy run 38823414424 verts; pied de page et actifs cache-bustés servent `v1.355.1`, /cours répond 200. Commit 62679b0bb, poussé origin + forge.
- **#2989 module boutique Shop EXPORTABLE → LIVRÉ.** README-INSTALL.md (installation, variables d'env, dépendances, désactivation, 18 points de couplage à adapter), ShopServiceProvider additif (tags shop-config + shop-migrations), 208 tests Pest verts. Zone argent intacte. Découplage fin = options documentées pour l'export réel (phase 3).
- **v1.355.0 (hier soir) : fuite Loi 25 #3130 colmatée + /cours opt-in fail-closed #3133** restent en place et prouvées.
- **Hygiène crons prod : PROPRE** (92 crons tous légitimes, aucun temporaire de mes sessions; `Command:?` = artefact cPanel, intact).

## Ce qui est en cours

- **#3004 ÉFVP Loi 25 (SSO + vente) → v0.3 livrée, committée, déployée** (dans le dépôt, AUCUN flux live). Révision adversariale double : Codex (27 constats) puis triage légal (modèle fort) ancré sur le corpus de loi (P-39.1, Règlement incidents). 24/27 retenus, 3 rejetés sur le fond, + 3 trous trouvés dans le CODE réel : 7 revendications SSO émises au lieu de 3 (`name`/`picture` à retirer avant allumage), code OTP routé hors Québec par Postmark (non inventorié), art. 9.1 absent. ADOPTÉE par le fondateur/RPRP le 16 octobre 2026, sous réserve des conditions de suivi 6.3 à 6.16 (AUCUN flux live avant qu'elles soient satisfaites et prouvées + 1er cours réel + coordination pair). Décision Postmark (16 oct) : CONSERVÉ pour le seul OTP, reste la documentation dans l'ÉFVP de base (#3136). Fichier : `docs/legal/EFVP-sso-moodle-vente-cours.md`.

## Ce qui bloque et attend une action du fondateur

- **Adoption de l'ÉFVP (#3004 → débloque #3002 SSO, #3086 vente, #3135)** : l'adoption et la décision du RPRP te reviennent (section 8.1 de l'ÉFVP). Claude prépare et recommande; n'auto-adopte pas, n'allume aucun flux. Avis juridique externe recommandé sur deux points (Postmark art. 17, qualification art. 12).
- **#3136 Postmark dans l'ÉFVP de base** : préparation mienne possible; la décision d'adéquation art. 17 (ou router l'OTP au Canada) te revient.
- **Grappe navigateur (#2799, #3126, #3129, #3131, images, AdSense)** : BLOQUÉE tant que ton Chrome est ouvert pendant `ia-sync` (Perplexity et Google non connectés). Déblocage : fermer ton Chrome, je relance `ia-sync`.
- **#3124 boutique aperçu couleur** : décision prise, bloqué sur connexion Gelato au navigateur.

## Prochaine action proposée

En autonomie, le gros de ce qui restait sans te dépendre est livré (EFVP v0.3, module Shop exportable, hygiène, déploiement prouvé). Ce qui reste est soit ta décision (adoption ÉFVP, Postmark, navigateur), soit gaté par le pair Moodle (3043, 3044, 3065, 3115, 3116, 3122, 3134, 3135). HEAD = 62679b0bb (v1.355.1), origin + forge. Deux sous-agents terminés, aucun cron temporaire, aucun résidu.
