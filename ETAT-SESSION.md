# État de session - La veille de Stef v2

Dernière mise à jour : 15 octobre 2026, 19h02 Québec (23:02 UTC). Production en **v1.355.0** (déployée et PROUVÉE).

## Où on en est (livré et prouvé)

- **v1.355.0 DÉPLOYÉE ET SERVIE EN PROD.** CI run 38813706953 succès + "Deploy to cPanel via rsync/SSH" run 38814139806 succès (22:01 UTC). Preuve servie : pied de page et actifs cache-bustés portent `v1.355.0`, /cours répond 200.
- **#3130 (FUITE LOI 25 : cours privé 32 exposé sur /cours) → COLMATÉ ET PROUVÉ EN LIVE.** Mitigation immédiate par exclusion des catégories privées (`CATALOG_EXCLUDED_CATEGORY_IDNUMBERS` dans le `.env` prod, config non cachée donc effet immédiat), backup `.env.bak-leak-cat-20261015-173928`.
- **#3133 (durcissement : /cours en OPT-IN fail-closed sur la case `vitrine`) → CODÉ, TESTÉ, DÉPLOYÉ (v1.355.0).** Un cours n'apparaît QUE si `vitrine` (valueraw=1) est coché; `valueraw` fait seul autorité dès que la clé existe. 53 tests Pest + 31 tests arch verts. Revue adversariale Codex : aucune fuite pour un export Moodle cohérent. Effet VISIBLE sur /cours = dépend de la pose de `vitrine` côté Moodle par le pair + purge de ses caches.
- **Hygiène crons prod : PROPRE.** Les 92 crons sont tous des tâches de production légitimes (laveille, lucidnest, planifize, monate, clinique, writeafrica, lfc, tvhr9, sauvegardes Softaculous, Moodle, SSL). AUCUN cron temporaire de mes sessions. L'entrée `Command:?` est l'artefact d'API cPanel connu, à NE PAS supprimer.

## Ce qui est en cours

- **#3004 EFVP Loi 25 (SSO + vente de cours) → v0.2 RÉDIGÉE, passe adversariale en cours.** Enrichie des sections manquantes : avis au point de collecte (art. 8), durée de conservation (art. 23), mesures de sécurité (art. 10), procédure d'incident + registre (art. 3.5 à 3.8), correction de la clé de rattachement SSO (claim `sub`, 4.8). Typographie OQLF propre. Passe Codex = 27 constats + 1 risque. Triage légal délégué à un sous-agent frais (vrai / sur-interprétation / décision fondateur) → produira la v0.3. Fichier : `docs/legal/EFVP-sso-moodle-vente-cours.md`.
- **#2989 (module boutique Shop exportable) → packaging en cours** (sous-agent, additif : README d'installation, publication config/migrations, rapport de couplage). Zone argent, mandat zéro casse.

## Ce qui bloque et attend une action du fondateur

- **Décision d'adoption de l'EFVP (#3004 → débloque #3002 SSO, #3086 vente, #3135)** : l'adoption et la décision du RPRP relèvent du fondateur (section 8.1 de l'EFVP). Claude prépare et recommande; il n'auto-adopte pas et n'allume aucun flux live.
- **Grappe navigateur (#2799, #3126, #3129, #3131, images, AdSense)** : BLOQUÉE car le Chrome du fondateur est OUVERT pendant `ia-sync` (Perplexity `logged_in:false`, Google non connecté). Déblocage : fermer ton Chrome, je relance `ia-sync`.
- **#3124 boutique aperçu couleur** : décision prise, bloqué sur connexion Gelato au navigateur.

## Prochaine action proposée

À la réponse du triage légal : appliquer l'EFVP v0.3, commiter (EFVP + README Shop) origin + forge, puis présenter au fondateur les décisions irréductibles de la section 8.1. HEAD = a2cada8f6 (v1.355.0), déployé origin + forge. Deux sous-agents actifs (triage légal, packaging Shop).
