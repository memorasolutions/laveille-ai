# Design - Bibliothèque de pré-prompts (constructeur de prompts)

Date : 2026-08-20 (America/Toronto). Décision issue d'un club des sages 5 oracles (Perplexity, Codex, Gemini via agy, DeepSeek, claude.ai), round 1, convergence unanime. Statut : APPROUVÉ par délégation (« tu décides du mieux ») - HARD-GATE `/reflexion` levé.

## Problème / objectif
Réduire le syndrome de la page blanche du constructeur : offrir des gabarits largement pré-rédigés par cas d'usage (« courriel professionnel pour un client », « résumé de réunion »...) où l'utilisateur ne remplit que quelques champs.

## État réel vérifié (ne pas réinventer)
- Moteur « espaces à remplir » : FINI et robuste (ancrage par chaîne, mémoire des valeurs, zéro syntaxe visible).
- `SavedPrompt` : sauvegarde l'état COMPLET du wizard (persona, format, ton, ET les `spaces`), avec `public_id`, permalien public `/p/{id}` et « remix » (`?remix={id}`) - branché et testé.
- `customCards` : gabarits personnels (10 max, UN champ texte `query_template`, SANS espaces) - embryon limité.
- Galerie publique par cas d'usage : ABSENTE.
- Archi « gabarit = phrase-à-trous PAR MÉTIER » : essayée (27 cas testés) puis ABANDONNÉE 5 jours après (2026-08-07). Règle projet : ne pas refondre le wizard sans demander, incrémental privilégié.

## Décision (convergence 5 oracles, notes /100)
- **(A) Véhicule des gabarits = `SavedPrompt`** (il porte DÉJÀ les espaces). REFUS d'un gabarit qui déclare ses propres champs (= la refonte abandonnée déguisée). La vraie lacune de `customCards` n'est pas les trous, c'est le plafond de 10 + champ unique. [A : 68-85]
- **(B) Galerie CURÉE par l'équipe (~15-25 gabarits), ZÉRO UGC public.** Sans modération, l'UGC = incident Loi 25 déclarable à la CAI (PII collée dans des prompts publics). [curé 86-91 / UGC 28]
- **(C) NE PAS rouvrir l'archi par métier.** Le métier est un PARAMÈTRE (ton/vocabulaire), pas une architecture. Exposer le métier comme préréglage dans le wizard actuel suffit. [réouverture 24 / générique 88-95]

## Frontière à INSCRIRE dans le CLAUDE.md du projet (anti-dérive)
Un gabarit = un état pré-rempli du wizard existant (un `SavedPrompt`). JAMAIS un gabarit qui déclare ses propres champs / son propre moteur de templating. La version « faible » (état pré-rempli) glisse vers la version « forte » (champs propres = archi métier abandonnée) en quelques sprints si la frontière n'est pas écrite. Toute demande d'aller vers la version forte = décision explicite de l'utilisateur, jamais une dérive.

## Briques (par valeur/effort)

### Brique 0 - garde-fous (à faire AVANT toute distribution)
1. **Sécurité des permaliens `/p/{id}` publics** : vérifier qu'ils sont `noindex`, que l'UI dit sans ambiguïté « consultable par quiconque a le lien », et que la suppression PURGE vraiment. Une galerie transforme un lien obscur en distribution active = responsabilité éditoriale accrue (Loi 25).
2. **Tags dans « Mes prompts »** : la liste plate devient ingérable au-delà de ~30 entrées. Prérequis d'hygiène. (À vérifier : les tags existent peut-être déjà - la découverte a mentionné `tags` sur SavedPrompt et l'UI /user/prompts.)
3. **Écrire la frontière ci-dessus dans le CLAUDE.md du projet.**

### Brique 1 - gabarits curés en état vide (LE COEUR, ~90)
- 15-25 gabarits curés par l'équipe = des `SavedPrompt` d'un compte/flag « équipe » (`is_system_template` ou compte dédié), état complet du wizard pré-rempli AVEC les espaces déjà posés. Zéro nouveau moteur.
- Surfaçage : boutons de gabarits curés DANS l'état vide au chargement (« ✉️ Rédiger un courriel », « 📝 Résumer une réunion ») - trivial (charge un `SavedPrompt` au clic), visibilité 100 %. + optionnellement une page catalogue légère par catégorie.
- Catégories par TÂCHE/objectif (pas par métier) : Rédiger et communiquer / Marketing et ventes / Résumer et analyser / Coder / RH et opérations / Éducation.
- Cartes structurées (Perplexity, standard 2026) : chaque carte répond en quelques secondes à - quel résultat ? pour qui ? quoi remplir ? quelle sortie ?
- CURÉ, zéro UGC public.

### Brique 2 - « Partir de mon brouillon » (décomposition inverse) - IDÉE NEUVE LA PLUS FORTE (96, trouvée indépendamment par Codex ET claude.ai)
- L'utilisateur colle un courriel / des notes / un prompt existant → un appel modèle le transforme en état de wizard avec espaces à remplir détectés. Le gabarit n'est plus rédigé, il est CAPTURÉ.
- Règle d'un coup : remplissage + amorçage + conformité (le collage est le bon moment pour détecter noms/courriels/numéros et proposer un MASQUAGE - brique réutilisable pour sécuriser ensuite les permaliens publics).
- Coût : un appel modèle + un écran de confirmation. Délégué à un MCP (Hermes/Qwen), jamais Opus.
- Mis en scène pour un PASSAGE DÉDIÉ (sensibilité PII + qualité de l'appel modèle) - conçu ici, pas construit dans la foulée.

## Explicitement écarté (motivé)
- UGC public (Loi 25, pas de modération) - note 28. Compromis futur possible : soumission par lien, publication par l'équipe (une table, un statut, aucune promesse de délai).
- Rouvrir l'archi phrase-à-trous par métier (24).
- 2e logique d'espaces bolt-on sur `customCards` (Codex 38 / DeepSeek 35).

## Stratégie de tests
- Brique 1 : un gabarit curé chargé pré-remplit bien le wizard + ses espaces ; l'état vide affiche les boutons curés ; un gabarit système n'apparaît pas dans « Mes prompts » d'un utilisateur ordinaire.
- Garde-fou 1 : `/p/{id}` rend `noindex` ; suppression purge (aucun accès après).
- Non-régression : le wizard existant, « Mes prompts », le remix `?remix=` inchangés.

## Critères d'acceptation
Un nouvel utilisateur voit, à l'écran vide, 3-6 gabarits curés cliquables qui pré-remplissent le wizard avec des espaces à remplir déjà posés ; aucune exposition UGC ; permaliens publics noindex + consentement clair + purge réelle ; frontière inscrite dans CLAUDE.md.
