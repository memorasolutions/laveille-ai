# État de session - La veille de Stef v2

Dernière mise à jour : 13 octobre 2026, ~10h15 Québec (14:15 UTC). Production en **v1.346.0** (CI + Deploy verts, servie et vérifiée en prod).

## Où on en est (livré et prouvé en prod)

- **Fiche actu « Muse Wonder » (74322) PUBLIÉE ET PROUVÉE** (13 oct ~10h35 Québec, 14:35 UTC) : https://laveille.ai/actualites/meta-annonce-muse-wonder-et-dit-avoir-rattrape-les-meilleurs-en-ia-le-premier-test-independant-nuance (HTTP 200, 46 occurrences « Muse Wonder », présente dans le fil /actualites). Image = caricature Gemini (deux coureurs, sarcelle/orange). Gate image = mon contrôle structuré + 1 oracle frais Claude, propres sur les 6 critères (ni texte, ni logo, ni enfant, caricature évidente); 2e oracle de famille navigateur ARRÊTÉ (justifié : zéro facteur de risque, navigateur partagé gardé libre, Perplexity HS). og:image = .jpg 1200×630 servi, webp de page servi, crédit « Illustration : caricature générée par IA (Google Gemini) ».
- **Fiche actu « Torvalds + Claude Code » (74352) PUBLIÉE ET PROUVÉE** (13 oct ~11h45 Québec, 15:45 UTC) : https://laveille.ai/actualites/linus-torvalds-qui-reduisait-lia-a-90-de-marketing-publie-un-patch-du-noyau-linux-ecrit-avec-claude-code (HTTP 200, og:image .jpg 1200x630, webp+jpg servis, dans le fil). Source primaire = message de commit de Torvalds cité par Phoronix (git.kernel.org bloqué par Anubis, signalé dans niveau_preuve=mixte). Gate exigeant : 3 passes de procureur (2 blocages réels corrigés : dates au jour non prouvées, cadrage « renie l'IA » exagéré, puis la revue faussement attribuée à l'humain alors que Claude Code l'a faite sous direction), 7 paires de preuve à extraits exacts. Image = caricature générique (développeur rasé, non identifiable, qui supervise un robot ; écrans et papiers vierges), 2 regards image propres après durcissement du prompt. Titre : « Torvalds, critique du battage sur l'IA, teste Claude Code dans le noyau ».
- **`/cours?theme=<slug>` (#3090, v1.346.0).** Lien profond par thème pour le méga-menu de l'académie : ?theme=ia/cyber/prod/mkt pré-sélectionne le filtre de la grille; slug inconnu = grille complète (repli propre, zéro casse). Slug dérivé de l'idnumber Moodle thm-. PROUVÉ par curl en prod (theme=ia→filter:'ia', etc.; version servie 1.346.0) + 32 tests Catalog verts (2 neufs). Pair prévenu, il câble ses 4 liens.
- **Plan « 2 plateformes = 1 » FIGÉ avec le pair Moodle** : provisionnement (enrôlement manuel après paiement, clé idnumber, idempotent), rien de live avant l'EFVP, certificats = customcert natif + lien WS. Doc pair : docs/ARCHITECTURE-2-PLATEFORMES.md.
- Antérieur (toujours en prod) : vitrine /cours complète (v1.343→345.3), Perplexity reconnecté.

## Ce qui est en cours

- **/cours : cartes de cours plus compactes (#3091, demande fondateur du 13 oct)** : édition Blade/CSS faite (image 150px au lieu de 192, padding réduit, titre + résumé clampés à 2 lignes, gap 18px). RESTE à déployer puis vérifier visuellement desktop + mobile.
- **File /actu2 restante — Perplexity FONCTIONNE de nouveau** (testé le 13 oct, le défi Cloudflare est levé) : #3068 (Les Affaires Bitcoin), #3073 (Mistral visual grounding), #3076 (TVA prédiction), #3077 (Unlimited OCR, source HF). #2924 (FTC) à re-vérifier dès le 14 oct.
- **EFVP Loi 25 SSO + vente de cours (#3004)** : brouillon 0.1 rédigé (docs/legal/EFVP-sso-moodle-vente-cours.md), insécables OQLF posés. En attente de RÉVISION du fondateur/RPRP. Constat central : SSO et Moodle co-hébergés (gmemora) = aucun destinataire hors Québec nouveau; paiement confiné à Stripe (déjà inventorié).
- File /actu2 restante : #3073 (Mistral visual grounding, recherche source en cours), #3076 (TVA prédiction), #3077 (Unlimited OCR, vérifier source HF), #3068 (Les Affaires Bitcoin).

## Ce qui bloque et attend une réponse du fondateur

- RÉVISION du brouillon EFVP (#3004) → débloque #3002 (SSO), #3003 (vente Stripe), #3086 (page détail /cours/{slug} + Acheter).
- Décisions : #3070/#3071 (steer éditorial Les Affaires), #2988 (ouverture boutique Gelato), #2990 (acquitter garde-crontab gmemora).
- Daté : #2924 FTC (dès le 14 oct = demain), #2931 (15 oct), #2977 (20 oct), #3001 (~avril 2027).

## Prochaine action proposée

Déployer #3091 (cartes /cours compactes, v1.346.1) : push origin + forge, attendre CI + Deploy verts, puis vérifier /cours au navigateur (bureau + mobile), purger les caches si l'ancien CSS est servi. Ensuite, enchaîner la file /actu2 (#3068, #3073, #3076, #3077). HEAD avant push = **457e7bace** (v1.346.0). Aucun cron temporaire. Runner prod _a2run.php + payloads dans storage/app à NETTOYER en fin de session.
