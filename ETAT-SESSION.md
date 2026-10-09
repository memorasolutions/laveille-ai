# État de session - La veille de Stef v2

> Fichier UNIQUE de reprise (réécrit, jamais empilé). Dernière mise à jour : 2026-10-09, ~13h30 Québec (17:30 UTC).

> ▶️ SESSION ACTIVE le 2026-10-09. Le fondateur a répondu à ses 12 questions en attente (Q1-Q12). Direction : « tout mettre prêt pour mettre des formations en ligne » + ouvrir la boutique Gelato (tests fondateur d'abord). Deux chantiers autonomes lancés ce tour : (1) synchro avec la session Moodle (faite, alignée), (2) refonte prix+taxes zéro-perte de la boutique (en cours, agent d'implémentation lancé).

## 1. Où on en est (terminé et PROUVÉ)

- **Synchro académie/SSO avec le peer Moodle : FAITE et alignée.** Journal durable `_sso-academie/JOURNAL-DEMANDES.md` entrée D-20261009-10. Décisions fondateur relayées ; partage du travail CONFIRMÉ des deux côtés (moi = socle IdP Passport réversible OFF + listener Cashier + checkout taxes/redirection ; peer = récepteur `local_laveille` grant/revoke DÉJÀ prouvé D-09 + thème + config auth_oauth2 à l'allumage). Aucun double emploi, pas de casse.
- **Boutique Gelato - recherche et conception prix/taxes : FAITES.** Cartographie du chemin prix→taxe→Stripe (sous-agent) ; analyse quantitative « jamais de perte » (Codex, 0 jeton) ; faits fiscaux 2026 (sonar-pro, Perplexity navigateur indisponible). Tout consolidé dans `docs/boutique-gelato-prix-taxes-2026.md` (spec + décisions + logique d'argent).

## 2. Décisions du fondateur du 2026-10-09 (Q1-Q12) - intégrées

- **Q1-Q5 (académie/SSO)** : canonique = formations.laveille.ai ; l'académie maison de laveille REDIRIGE vers Moodle ; vente = option (ii) (paiement+taxes sur laveille, provisionnement Moodle) ; SSO = go de principe, allumage au 1er cours réel + EFVP ; se synchroniser avec le peer (FAIT).
- **Q6** : boutique accès fondateur SEUL pour tests (statu quo, founder_only ON).
- **Q7** : viser une bonne marge (20 %?) ; ne JAMAIS perdre, frais Stripe inclus ; +1 $ sur livraison ; prix CAD ; cadrer où vendre/livrer. → Décision retenue (doc) : GARDER 30 % markup (20 % trop mince après Stripe), + garde-fou de marge plancher + gross-up Stripe + arrondi vers le haut. Codex le confirme.
- **Q8** : taxes toutes provinces, 0 hors Canada. → Table GST/TVH par destination + TVQ (QC) ; PAS de TVP C.-B./SK/MB (pas inscrit — défaut sûr, drapeau pour activer plus tard) ; livraison+manutention taxées.
- **Q9** : créer un compte test @memora.ca (atterrit dans sa boîte).
- **Q10 (AdSense auto)** : « Ouvre-le, je te donne accès » → EN ATTENTE de son accès.
- **Q11 (Gemini/Perplexity)** : il a fait ia-sync ; je l'ai refait aussi. Perplexity reste déconnecté (ia-sync ne l'a pas resynchronisé) → repli sonar-pro utilisé.
- **Q12 (crons)** : ne toucher qu'aux crons du projet laveille (noté).

## 3. Ce qui est EN COURS

- **#3005 + #3006 (prix+taxes zéro-perte)** : agent d'implémentation lancé (code + tests Pest, SANS déploiement). À réviser à son retour + revue adversariale Codex avant tout déploiement. Spec : `docs/boutique-gelato-prix-taxes-2026.md`.
- **#3002 (socle SSO IdP)** : à coder APRÈS le feu du branchement (synchro faite) ; reste réversible OFF, allumage au go fondateur + EFVP.
- **#3003 (listener Cashier + taxes checkout)** : suit #3005/#3006 et le socle.
- **#3007 (`/academie` → redirection)** : à faire, zéro casse (inspecter d'abord ce que sert le module `Academy` maison, préserver toute donnée).
- **#3008 (compte test @memora.ca)** : après que les prix soient corrects.

## 4. Ce qui BLOQUE en attendant le fondateur

- **Q10 AdSense auto** : attend son accès à la session Google AdSense (« je te donne accès »).
- **Décisions non bloquantes portées au fondateur (doc §6, défaut sûr déjà appliqué)** : TVP C.-B./SK/MB (probable : pas inscrit → pas facturée) ; ouvrir la vente aux États-Unis (défaut : Canada seul) ; politique de remboursement (frais Stripe ~1 $ non récupérables).
- **#2924** (FTC, 14 oct), **#2931** (Reddit, 15 oct), **#2977** (veille actu2, 20 oct) : en attente de DATE.

## 5. Sauvegarde / versioning

- Aucune mutation de prod ce tour. Prochain déploiement : après revue de #3005/#3006 (bump SemVer MINOR, backup, CI verte, validation visuelle). Boutique en founder_only : aucun risque public pendant la construction.
