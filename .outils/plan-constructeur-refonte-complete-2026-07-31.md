# Plan complet : constructeur de prompts, simple devant, technique derrière

Date : 2026-07-31 (America/Toronto). PROPOSITION, rien n'est implémenté.
Sources : pp_search juillet 2026 (3 requêtes) + Codex (2 critiques sévères) + inspection du code réel.
Non consultés sur ce volet : Gemini, claude.ai.

---

## LE PRINCIPE DIRECTEUR

**La personne répond à des questions humaines. Le moteur écrit un prompt technique.
Elle ne voit jamais la technique, mais elle en profite entièrement.**

Aujourd'hui, l'outil demande à un néophyte de choisir un « rôle », un « verbe d'action », un
« format ». Ce sont des concepts d'ingénierie de prompt. C'est ce qui rend l'outil incompréhensible,
et c'est exactement ce qu'il faut cesser de demander sans cesser de le PRODUIRE.

| La personne dit | Le moteur écrit (invisible) |
|---|---|
| « Pour des élèves de 3e année » | niveau de langage, longueur de phrase, vocabulaire, exemples concrets |
| « Un courriel » | structure, formule d'appel, longueur cible, ton, appel à l'action |
| « Je veux que ça soit court » | contrainte de mots, interdiction de remplissage |
| (rien, toujours injecté) | règles d'écriture humaine, format de sortie, cadrage du rôle |

---

## LES RÈGLES TOUJOURS INJECTÉES, JAMAIS DEMANDÉES

Ce bloc part dans CHAQUE prompt, sans que personne ait à le configurer. C'est là que vit la
performance de l'outil.

**Écriture humaine**
- pas de majuscules de titre à l'américaine ;
- pas de tiret cadratin ;
- pas de jargon d'IA (« plongeons dans », « il est important de noter », « en conclusion ») ;
- phrases de longueur variée, pas de rythme mécanique ;
- français du Québec, tous les accents ;
- pas d'emoji sauf demande explicite ;
- pas de listes à puces quand une phrase suffit.

**Cadrage technique**
- rôle déduit du type de tâche, jamais demandé ;
- destinataire et niveau déduits de la réponse « pour qui » ;
- format de sortie déduit du livrable choisi ;
- consigne de demander une précision plutôt que d'inventer, si l'information manque.

**Adaptation à la destination** (choisie au moment de copier, pas au début)
- Claude : suggérer un artefact quand le livrable est un document, du code ou une page ;
- ChatGPT : suggérer le canevas pour un texte long à retravailler ;
- Gemini : cadrage de sortie structuré ;
- Autre ou inconnue : version neutre, sans instruction propriétaire.

---

## L'ARCHITECTURE RETENUE

### Écran 1 : la demande

Un seul champ, en grand : **« Que voulez-vous demander à l'IA ? »**
Sous le champ, les cartes de démarrage existantes (déjà livrées) comme raccourcis.
Un seul bouton : **« Créer mon prompt »**.

Aucune ambiguïté, aucun choix technique, aucune numérotation d'étapes.

### Écran 2 : le prompt est prêt

**« Votre prompt est prêt. »** Le prompt complet est affiché.
Deux actions de même poids visuel :
- **« Copier »** (avec le choix de l'IA au moment de copier, pas avant) ;
- **« L'améliorer »**.

C'est ici que se règle le défaut fatal de ma version précédente : plus de conflit entre
« continuer » et « terminer », plus d'« étape 1 sur 3 » mensongère.

### Écran 3 : l'amélioration (seulement si demandée)

Une page structurée, **sans aucun accordéon**, avec des questions humaines :
- **Pour qui ?** (cartes : des élèves, des collègues, des clients, le grand public, moi-même, autre)
- **Quel genre de résultat ?** (cartes : un courriel, un texte, une explication, une liste, un plan, un tableau, autre)
- **Des limites ?** (court, formel, avec exemples, sans jargon, autre)
- **Un exemple à imiter ?** (champ libre facultatif)

Chaque groupe a une option « Autre » qui ouvre un champ libre. Les cartes ne servent que les cas
fréquents, elles ne prétendent pas couvrir toute une taxonomie.

Le prompt se met à jour, et un court message dit ce que le choix vient d'ajouter :
« Ajouté : niveau de langage adapté à des élèves. »
C'est la version utile de l'aperçu en direct : elle ENSEIGNE au lieu de se réécrire en boucle.

---

## LES OPTIONS COMPARÉES

| Option | Note | Justification |
|---|---:|---|
| **Deux temps (créer, puis améliorer) + moteur technique invisible** | **94** | Sépare réussite minimale et optimisation. Aucune étape mensongère. Un seul bouton par écran. La performance vit dans le moteur, pas dans le formulaire |
| Modèles par tâche (écrire, résumer, expliquer, planifier) puis canevas dédié | 90 | Vocabulaire compréhensible, contraintes adaptées au type. Risque de taxonomie rigide et de cas à cheval |
| Éditeur unique guidé, suggestions contextuelles sous le champ | 91 | Modèle mental naturel, rien à apprendre. Détection contextuelle limitée sans IA |
| Canevas par blocs visibles, une page, zéro accordéon | 88 | Étendue visible, correction facile. Page longue, toute la charge apparaît d'un coup |
| Assistant adaptatif 2 à 4 écrans | 86 | Questions pertinentes. Complexité de branches élevée, tests coûteux |
| Mon assistant 3 étapes (version précédente) | 68 | Champ action/verbe perdu, « 1 sur 3 » mensongère, conflit de bouton |
| Statu quo (accordéons) | 28 | Ouvrir, lire, remplir, refermer. Douze gestes qui n'écrivent rien |

---

## CE QUI DOIT ÊTRE CONSERVÉ DE LA VERSION ACTUELLE

Rien ne disparaît, tout est déplacé :
- **le champ action/verbe** : absorbé dans la demande principale, plus jamais demandé séparément ;
- **le rôle/persona** : déduit du type de tâche, injecté automatiquement, modifiable dans l'amélioration ;
- **la destination IA** : déplacée au moment de copier ;
- **les cartes de démarrage personnalisables** : deviennent le point d'entrée principal de l'écran 1 ;
- **la bibliothèque « Mes prompts »** : inchangée ;
- **le bouton « Améliorer » à coût zéro** : devient l'écran 3 ;
- **la protection des renseignements** : reste attachée au champ de l'écran 1.

---

## LE RISQUE NUMÉRO UN

**Transformer un outil immédiat en tunnel, et confondre une interface plus propre avec une
expérience plus simple.** L'actuel est irritant mais on voit les champs et on bricole.

**Prévention** : prototyper trois versions (page unique sans accordéons, deux temps, trois étapes)
et mesurer sur de vrais néophytes le **temps jusqu'au premier prompt copié**. Le critère n'est pas
« est-ce plus moderne » mais « une personne qui ne connaît rien à l'IA obtient-elle plus vite un
prompt qu'elle comprend et juge utile ».

---

## À ÉTABLIR AVANT D'ÉCRIRE DU CODE

1. Inventaire exact : ce que chaque champ actuel produit dans le prompt final. Un champ sans effet
   visible doit disparaître, pas changer de place.
2. Le prompt varie-t-il réellement selon la destination IA ? Si non, la destination n'est qu'un lien.
3. Contrat de compatibilité : paramètres d'URL partageables, stockage local, prompts déjà sauvegardés
   dans l'ancien format, identifiants utilisés par les tests.
4. Architecture mobile propre : le prompt affiché ne doit pas être poussé hors écran par le clavier.
5. Accessibilité des cartes : vraies cases à cocher ou boutons radio, jamais des div cliquables ;
   état sélectionné pas uniquement par la couleur ; aperçu non annoncé à chaque caractère.
