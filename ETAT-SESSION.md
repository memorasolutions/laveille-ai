# État de session - La veille de Stef v2

> Fichier UNIQUE de reprise (réécrit, jamais empilé). Dernière mise à jour : 2026-10-09, ~16h30 Québec (20:30 UTC).

> ▶️ SESSION ACTIVE le 2026-10-09, MODE AUTONOME (go fondateur : poursuivre jusqu'à 100 % sans s'arrêter à chaque todo). Déploiement boutique v1.325.0 VÉRIFIÉ en prod. En cours : désactivation ancienne académie (#3007).

## 1. Où on en est (terminé et PROUVÉ)

- **Boutique prix zéro-perte + taxes provinciales : DÉPLOYÉE ET VÉRIFIÉE (v1.325.0).** #3005 + #3006 clos.
  - CI run `37978710306` = success ; « Deploy to cPanel » run `37979102178` = success. Prod sert `?v=1.325.0`. `/boutique` anonyme = HTTP 503 (gate founder-only intacte). Accueil = 200 (zéro casse). Git `origin` = `forge` = `81e2365fc`.
  - Gate adversarial pré-déploiement : 4 findings Codex corrigés + 2e passe Sonnet nette + suite Shop verte.
  - RESTE (bloqué) : validation VISUELLE de la vitrine (prix/taxes affichés) = #2988, exige une session super-admin connectée (identifiants fondateur).
- **Centralisation vente de cours : ARCHITECTURE FIGÉE avec le peer Moodle.** Journal `_sso-academie/JOURNAL-DEMANDES.md` (HORS dépôt), D-10 à D-12. Convergence 2 sessions + 3 oracles. laveille = HUB (catalogue cours clé idnumber, 1 compte, registre de droits, caisse, taxes catégorie formation DISTINCTE, reçus, identité Passport) ; Moodle = livraison pure. Garde-fous détaillés dans #3003.
- **Règle fondateur 2026-10-09** : « toujours communiquer avec le Claude du Moodle » (mémoire projet) ; « continuer en autonome jusqu'à 100 % » (réaffirmé, mémoire `continuer-autonome-sans-checkpoint`).

## 2. Ce qui est EN COURS (ordre logique décidé par Claude)

- **#3007 (`/academie` → redirection + désactivation module Academy)** : IN PROGRESS. Sous-agent de cartographie lancé (couplage CORE, données à préserver, autoload des modules désactivés). ⚠️ Retrait COMPLET du code BLOQUÉ : `app/Models/User.php` importe un trait du module → le retirer casserait l'auth. Donc désactivation publique + redirection seulement, trait conservé ; chiffrer le découplage pour un retrait futur. Zéro casse, préserver inscriptions/progression/attestations.
- **#3002 (socle SSO IdP Passport, réversible OFF)** : à construire ENSUITE (boutique déployée = feu vert que j'avais donné au peer). Reste `IDP_ENABLED=false`, aucun changement de comportement prod, gate adversarial avant déploiement. Au livrable : signal au journal → le peer branche `auth_oauth2`.
- **#3003 (vente de cours)** : archi figée ; implémentation gatée (socle + formations prêtes + go + EFVP + recette). AVANT implémentation : club des sages NAVIGATEUR + recherche fiscale Perplexity (était en timeout, à refaire).
- **#3008 (compte test @memora.ca)** : ⚠️ zone argent — tester un vrai checkout = vraie charge Stripe sauf mode test. À séquencer prudemment (mode test Stripe requis), possiblement un vrai blocage à remonter.

## 3. Ce qui BLOQUE en attendant le fondateur

- **#2988** validation visuelle vitrine : session super-admin connectée requise.
- **Q10 AdSense auto** : accès à sa session Google AdSense.
- **Décisions non bloquantes (défaut sûr appliqué)** : TVP C.-B./SK/MB ; vente aux États-Unis ; politique de remboursement.
- **#2924** (FTC, 14 oct), **#2931** (Reddit, 15 oct), **#2977** (actu2, 20 oct) : en attente de DATE.

## 4. Sauvegarde / versioning / hygiène

- v1.325.0 sur `origin` + `forge` (SHA `81e2365fc`), rollback = `git revert`. Boutique `SHOP_FOUNDER_ONLY` : aucun risque public.
- À faire au prochain passage serveur : vérifier qu'aucun cron TEMPORAIRE de mes sessions ne traîne (rappel fondateur) ; ne toucher qu'aux crons du projet laveille (Q12).
