# Plan : refonte du formulaire du constructeur de prompts

Date : 2026-07-31 (America/Toronto). PROPOSITION, rien n'est implémenté.
Source : pp_search juillet 2026. Panel Codex/Gemini/claude.ai non consulté sur CE volet.

## Diagnostic confirmé par la recherche

L'outil compte environ 8 champs (objectif, rôle, audience, action, contraintes, exemples,
format, destination) répartis en accordéons. La recherche de juillet 2026 conclut :

- au-delà de 6 champs, le multi-étapes surperforme la page unique (environ +14 % de complétion) ;
- **les accordéons sont rarement idéaux comme structure PRINCIPALE d'un formulaire long pour
  un public non technique** : ils cachent l'étendue de la tâche et compliquent le repérage des erreurs ;
- la zone optimale est 3 à 5 étapes ; au-delà, la fatigue revient ;
- « une question par écran » convient aux questions à forte charge cognitive ou aux vraies
  dépendances, sinon 2 à 4 champs par écran avec un bon regroupement ;
- l'indicateur de progression doit être une carte 1:1 honnête du parcours, avec un intitulé
  clair (« Étape 2 sur 3 : Pour qui ») plutôt qu'une barre abstraite.

Ce qui prédit l'abandon n'est pas le nombre d'étapes mais la **charge perçue** : ambiguïté des
questions, risque d'erreur, dépendances, effort mobile.

## Les options, notées

| Option | Note | Justification |
|---|---:|---|
| **A. Assistant 3 étapes + aperçu du prompt en direct** | **94** | Pile dans la zone optimale 3-5. L'aperçu répond au vrai problème du néophyte : il ne sait pas à quoi sert ce qu'il remplit. Voir le prompt se construire transforme un formulaire abstrait en cause à effet immédiate |
| B. Assistant 5 étapes, une question par écran | 76 | Charge minimale par écran, excellent sur mobile. Mais lent sur ordinateur, et 5 transitions pour un outil qu'on utilise plusieurs fois par semaine agacent vite |
| C. Page unique avec regroupement visuel fort (sections ouvertes, pas d'accordéon) | 71 | Tout visible, aucune transition. Mais 8 champs d'un coup intimident le néophyte, et la page devient très longue sur mobile |
| D. Formulaire conversationnel (style discussion) | 62 | Réduit l'anxiété, ton humain. Mais perd la possibilité de survoler, complique l'accessibilité, et ne compense pas une mauvaise structure |
| E. Statu quo (accordéons) | 28 | Cache l'étendue de la tâche, complique le repérage des erreurs, empile les décisions avant la première ligne écrite. Contre-indiqué par la recherche pour ce public |

## Recommandation : option A

### Les 3 étapes

**Étape 1 sur 3 : Ce que vous voulez**
Le champ de demande (celui qui existe) + le choix de l'IA de destination.
C'est la seule étape strictement obligatoire. Une personne pressée s'arrête ici et copie déjà
un prompt correct.

**Étape 2 sur 3 : Pour qui, et comment**
Audience et format, présentés en cartes visuelles cliquables plutôt qu'en listes déroulantes
(« Pour des élèves », « Pour des collègues », « Pour moi »). Deux à quatre choix par écran,
conformément à la recherche.

**Étape 3 sur 3 : Précisions (facultatif)**
Rôle, contraintes, exemples. Étape clairement marquée « facultatif » : le néophyte la saute,
l'utilisateur avancé y trouve tout.

### Ce qui rend l'option A supérieure

**L'aperçu en direct.** Le prompt se construit visiblement à mesure qu'on répond. C'est ce qui
manque le plus aujourd'hui : la personne remplit des champs sans comprendre ce qu'ils produisent.
Cause et effet immédiats, pas d'abstraction.

**Le raccourci assumé.** L'étape 1 suffit à produire un prompt utilisable. Les deux suivantes
bonifient. On ne force personne à traverser huit champs pour obtenir un résultat.

**Des cartes plutôt que des listes.** « Visuel si possible » : une carte cliquable avec une icône
et deux mots se comprend sans lire, une liste déroulante demande d'ouvrir puis de choisir.

**Progression honnête.** « Étape 2 sur 3 : Pour qui » avec les étapes précédentes cliquables.
Jamais d'étape surprise ajoutée en cours de route : la recherche est formelle, ça casse la confiance.

## Points de vigilance

- Ne pas perdre les cartes de démarrage personnalisables déjà livrées : elles préremplissent
  l'étape 1 et deviennent le raccourci le plus rapide.
- Le bouton de protection des renseignements reste attaché au champ de demande, à l'étape 1.
- L'état du formulaire doit survivre au passage d'une étape à l'autre ET au rechargement.
- Accessibilité : chaque étape est un point d'ancrage, le focus se place sur le titre de l'étape
  à chaque transition, et l'aperçu est annoncé sans être verbeux pour un lecteur d'écran.
- Mobile : l'aperçu passe sous le formulaire, jamais en colonne écrasée.

## À vérifier avant d'écrire du code

1. Inventaire exact des champs actuels et de leurs dépendances réelles.
2. Ce que produit chaque champ dans le prompt final (certains sont peut-être inutiles).
3. Si un champ n'influence pas visiblement le prompt, il doit disparaître, pas changer de place.
