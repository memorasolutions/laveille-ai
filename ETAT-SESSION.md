# État de session - La veille de Stef v2

> Fichier UNIQUE, réécrit à chaque fin de lot. Ne jamais l'empiler ni le dupliquer.
> Il porte le POINT DE REPRISE, pas le détail (todolist) ni l'historique (QUESTIONS-CLAUDE.html).

**Dernière mise à jour : 2026-09-24, 11h35 Québec (15:35 UTC)**

## Où on en est

- **Carrousel LinkedIn « le test en 3 gestes », v6, EN LIGNE ET PROUVÉ.**
  `https://laveille.ai/carrousels/anonymiseur-test-3-gestes-v6.pdf` - 200, zéro redirection,
  8 pages, empreinte servie identique à la locale (`7e11d522f4a29cec`).
  Révisé par deux oracles en aveugle, de familles différentes. Quatre défauts corrigés, dont une
  contradiction que la correction du premier oracle avait créée.
- **Deux publications programmées pour le 25 septembre**, toutes deux `requires_client_approval:
  false`, donc elles partiront seules :
  - **316** - Facebook, 08h00 Québec (12:00 UTC), image + lien en premier commentaire.
  - **319** - LinkedIn, 11h00 Québec (15:00 UTC), carrousel v6 en PDF, aucun premier commentaire.
    Vérifié : `type: document`, `downloaded_at` rempli, `download_error` nul.
- **Skill `/publier` enrichi de 4 règles neuves** (5872 octets), écrites AVANT la livraison.
- **4 commits locaux, poussés sur le forge seulement** (`fc6f9fc8e` et les 3 précédents). Tous des
  changements de documentation : ils n'ont pas été poussés vers `origin` pour ne pas déclencher une
  fenêtre de 503 inutile (21 fenêtres ont coûté deux mois de visibilité Google le 19 juillet).
  À grouper avec la prochaine livraison de code réelle.

## En cours

- **#2686 - Module historique** : le club des sages a tranché l'architecture 80/20 en 2 rounds.
  Reste UNE décision, celle de construire ou non. Rien n'est écrit dans le code.
- **#1847 - Actus 2.0, sortir du flux** : chantier permanent, phase technique faite (flux -68 %).
  Ce qui reste se mesure en verdicts publiés, pas en code.

## Ce qui BLOQUE en attendant une réponse ou une action de Stéphane

- **#2735 - Supprimer les publications 314, 315, 317 ET 318.** Le portail n'a AUCUNE route de
  modification ni de suppression : corriger oblige à recréer, et le retrait des anciennes te revient.
- **#2585** - Approuver la 300, refuser la 298. L'heure prévue est passée, rien n'est parti.
- **#2368** - Clé secrète Turnstile : le code est correct et testé, seule la clé manque (règle 1Password).
- **#2276 et #2638** - Deux prompts livrés, à coller dans d'autres sessions (Namaste santé, et la
  session du connecteur MCP Facebook). Rien à faire ici.
- **#2597** - Trace d'un robot d'IA sur /decido/ : 7 voies mesurées et fermées, seul le tableau de
  bord Cloudflare peut répondre.
- **#2722** - Le portail stocke une heure 4 h trop tôt. Contourné en envoyant l'heure UTC voulue,
  mais le correctif appartient à la session du portail.

## Prochaine action proposée

**#2733** - intégrer dans `/publier` les règles de rédaction sociale que tu as transmises le
2026-09-23 (source ChatGPT). C'est la seule tâche ouverte qui ne dépend de personne d'autre, et elle
prolonge directement le travail de doctrine fait aujourd'hui.

Ensuite **#2732** - le défaut de l'anonymiseur lui-même : le nom de famille survit dans l'adresse
courriel, et ni la ville ni le numéro de dossier ne sont détectés. Le carrousel publié l'assume
explicitement ; l'outil, lui, n'est pas corrigé.
