# État de session - la-veille-de-stef-v2

> Mis à jour le **2026-09-25 à 08h14 Québec (12:14 UTC)**, en plein lot /actu2. Ce fichier est TOUJOURS le même : on le réécrit, on n'empile pas. Version précédente : `.backups/ETAT-SESSION-2026-09-24-2053.md`.

---

## ✅ Où on en est (terminé et prouvé)

- **Publication 345** (anonymiseur, Facebook) : partie à **08h01 Québec (12:01 UTC)**, `published_at` relu par `get_social_publication`.
- **v1.297.0** en production (anonymiseur : villes, numéros de dossier, prénom caché dans le courriel), poussée sur GitHub et la forge.
- **Élagage nocturne des brouillons** : la règle `news:hold` est écrite dans `/actu2` (section 0 bis); les 18 fiches du lot ont une retenue de 14 jours.
- **Contrôleur `verif_payload.py`** renforcé ce matin, avec deux contrôles neufs testés sur témoin : forme `{label, url}` des sources primaires, et espace ordinaire collée à une insécable.
- **13 fiches /actu2 dont le texte est APPLIQUÉ en production** (non publiées) : 58676, 58471, 58466, 57860, 57645, 58468, 57540, 58624, 58511, 58492, 58179, 57713, et 57647 (en file, lot 5). Sorties : `storage/app/a2_out_lot1..5_20260925.txt` sur le serveur.

## 🔄 En cours (ce qui reste à prouver)

- **Images des 13 fiches** : un sous-agent les génère par ChatGPT au navigateur, puis deux oracles les contrôlent en aveugle. Prompts : `storage/app/travaux-session-2026-09-25/images-lot/prompts.txt`.
- Ensuite : `news:apply --image --credit`, puis `news:apply --publish`, puis vérification servie (`?cb=`, `detecter_cartes.py`, capture).
- **Sous-agents /actu2 encore actifs** : 58491 (Paper2Agent), 58740 (pertes OpenAI), 58484 (Opus 5.5), 58692 (règlement Siri), 58668 (appels Gemini).
- **Passerelle prod OUVERTE** : cron `2780396691` actif, `a2_runner.sh` actif avec auto-expiration (`a2_expire`). À fermer À LA MAIN après le dernier lot : `cron_remove`, neutraliser le runner, `a2_expire` à 0, puis relire `cron_list`. L'auto-expiration n'est pas un retrait.

## ⏸️ Ce qui attend une réponse du fondateur

#2585 (approuver la 300, refuser la 298) · #2735 (garder 341 et 345, supprimer les anciennes) · #2759 (parties 2 et 3 de la série) · #2788 (nom « Anonymiseur ») · #2368 (clé Turnstile dans 1Password) · #2276 et #2638 (prompts à coller dans d'autres sessions) · #2597 (tableau de bord Cloudflare) · #2722 (heure du portail) · 7 commits déjà poussés signés d'une IA : les réécrire exige un force-push, donc ta demande explicite.

## ➡️ Prochaine action proposée

Recevoir les images contrôlées, les appliquer et publier les 13 fiches, vérifier ce qui est servi, fermer la passerelle, puis traiter les 5 fiches restantes de la même façon.
