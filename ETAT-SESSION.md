# État de session - la-veille-de-stef-v2

> Mis à jour le **2026-09-25 à 09h43 Québec (13:43 UTC)**. Ce fichier est TOUJOURS le même : on le réécrit, on n'empile pas. Version précédente : `.backups/ETAT-SESSION-2026-09-25-0814.md`.

---

## ✅ Où on en est (terminé et prouvé)

- **v1.298.0 en production** : les articles planifiés ne sont plus lisibles avant leur date. Les parties 2 et 3 de « IA et emplois 2030 » répondent 200 avec `noindex`, affichent « À paraître le mardi 29 septembre 2026 à 9 h » et « le mardi 6 octobre 2026 à 9 h », ne sont pas en cache et sont absentes du plan de site. Le robot conversationnel et les mini-sites d'auteurs ne les exposent plus. La liste admin affiche « Planifié » et la date prévue (tests 3/3, à constater par Stéphane, la capture exige sa connexion 2FA).
- **Registre des publications** : `docs/publications/registre-publications.csv` (294, 296, 345), et le hook refuse désormais une publication laveille.ai sans UTM.
- **Caricature** : règle « la personne doit se lire comme une caricature » écrite dans `/article`, `/publier` et `/dalle`.
- **13 fiches /actu2 dont le texte est appliqué en production** (non publiées), 5 autres rédigées.

## 🔄 En cours (ce qui reste à prouver)

- **Images des 18 fiches** : sous-agent de génération au navigateur (ChatGPT, limites de débit fréquentes), contrôle en aveugle par deux oracles. Ensuite : rouvrir la passerelle, `news:apply --image --credit`, `--publish`, vérifier ce qui est servi, refermer la passerelle à la main.
- **Système de mesure publications -> trafic** (#2799) : Perplexity et DeepSeek consultés (`storage/app/travaux-session-2026-09-25/club-mesure/`), ChatGPT, Gemini et claude.ai au navigateur restent à consulter, puis rounds 2 et 3.
- **Audit des prompts** livré, non appliqué : `storage/app/travaux-session-2026-09-25/audit-prompts/`.

## ⏸️ Ce qui attend une réponse du fondateur

#2585 (approuver la 300, refuser la 298) · #2735 (garder 341 et 345) · #2788 (nom « Anonymiseur ») · #2368 (clé Turnstile) · #2276 et #2638 (prompts à transmettre) · #2597 (Cloudflare) · #2722 (heure du portail) · 7 commits signés d'une IA : réécriture = force-push, sur ta demande seulement.

## ➡️ Prochaine action proposée

Recevoir les images contrôlées, publier les 18 fiches, refermer la passerelle, puis lancer le round 1 du club des sages au navigateur sur le système de mesure.
