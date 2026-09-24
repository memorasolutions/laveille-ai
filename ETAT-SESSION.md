# État de session - La veille de Stef v2

> Fichier UNIQUE, réécrit à chaque fin de lot. Ne jamais l'empiler ni le dupliquer.
> Il porte le POINT DE REPRISE, pas le détail (todolist) ni l'historique (QUESTIONS-CLAUDE.html).

**Dernière mise à jour : 2026-09-24, 12h25 Québec (16:25 UTC)**

## Où on en est

- **Carrousel « le test en 3 gestes », v7, EN LIGNE ET PROUVÉ.**
  `https://laveille.ai/carrousels/anonymiseur-test-3-gestes-v7.pdf` - 200, zéro redirection,
  8 pages, empreinte servie identique à la locale (`b5af502cddd98a70`).
  **Aucune diapositive ne présente notre outil comme défaillant** - règle du fondateur du
  2026-09-24, vérifiée par balayage des 8 pages.
- **Ce qui a motivé la v7, et il faut le retenir** : la v6 attribuait à NOTRE outil une limite qui
  appartient à toute la catégorie. Mesuré en aveugle avec la question commerciale ajoutée :
  clarté 8/10, service rendu à l'entreprise **3/10**. Le contrôle de clarté seul, qui donnait
  10/10, ne mesurait pas ce qui comptait.
- **Deux publications programmées pour le 25 septembre**, `requires_client_approval: false`, donc
  elles partiront seules. Leur titre commence par « GARDER » :
  - **322** - Facebook, 08h00 Québec (12:00 UTC). Média téléchargé, aucune erreur.
  - **323** - LinkedIn, 11h00 Québec (15:00 UTC), carrousel v7. Type `document`,
    `downloaded_at` 16:23:14 UTC, `download_error` nul.
- **Tout ce qui précède (314 à 321) a disparu du portail**, balayé pendant que je travaillais.
  L'API n'en montre plus aucune. Rien à faire de ce côté.
- **Skill `/publier` : 6 règles neuves aujourd'hui**, toutes écrites AVANT la livraison. Les deux
  dernières sont l'interdit absolu d'autodénigrement et les 4 questions commerciales à poser à
  l'oracle, avec leur critère de rejet.
- **6 commits locaux, poussés sur le forge seulement.** Tous de la documentation : pas poussés vers
  `origin` pour ne pas déclencher une fenêtre de 503 inutile. À grouper avec la prochaine livraison
  de code réelle.

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
