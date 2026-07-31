# Devis Fonctionnel — Outil de Création de Mots Croisés
### Application Web — Plateforme Laravel

***

## 1. Présentation générale du projet

L'objectif est de développer un module intégré à une application Laravel existante permettant à un utilisateur de créer des grilles de mots croisés personnalisées. L'utilisateur saisit une liste de paires question/réponse (indices/mots), puis le système génère automatiquement une grille valide de mots croisés. La grille peut ensuite être sauvegardée, prévisualisée et partagée.

Ce devis décrit l'ensemble des comportements attendus, des règles métier, des contraintes d'interface et des flux utilisateur. Il ne contient aucune indication de code ou d'implémentation technique — il constitue le référentiel fonctionnel à respecter lors du développement.

***

## 2. Périmètre fonctionnel

Le module couvre les fonctionnalités suivantes :

- Saisie d'une liste de paires indice/réponse via un formulaire dynamique
- Validation des données saisies selon des règles précises
- Génération automatique d'une grille de mots croisés à partir des réponses
- Prévisualisation interactive de la grille générée
- Sauvegarde, gestion et suppression des grilles
- Export de la grille (impression ou PDF)
- (Optionnel phase 2) Mode joueur : remplir une grille à blanc

***

## 3. Acteurs et rôles

| Rôle | Description |
|------|-------------|
| **Créateur** | Utilisateur authentifié qui crée et gère ses grilles |
| **Joueur** | Utilisateur (authentifié ou non, selon configuration) qui résout une grille partagée |
| **Administrateur** | Supervise les contenus, peut modérer ou supprimer |

Pour la phase 1 du présent devis, seul le rôle **Créateur** est ciblé.

***

## 4. Flux principal — Création d'une grille

### 4.1 Accès à la fonctionnalité

L'utilisateur accède à la section de création via le menu principal de l'application. Une page dédiée s'affiche, composée de deux zones principales :

1. **Zone de saisie des paires** (partie supérieure ou panneau gauche)
2. **Zone de prévisualisation de la grille** (partie inférieure ou panneau droit)

### 4.2 Formulaire de saisie des paires

Le formulaire de saisie est le cœur de l'interface de création. Il doit respecter les règles suivantes :

**Structure d'une paire :**
- Chaque paire est composée de deux champs :
  - **Indice** : la question ou l'indice qui sera affiché au joueur (ex. : « Capitale de la France »)
  - **Réponse** : le mot ou groupe de mots qui sera placé dans la grille (ex. : « PARIS »)
- Les deux champs sont obligatoires pour chaque paire
- Un numéro de paire est affiché automatiquement devant chaque ligne (1, 2, 3, etc.)

**Comportement du formulaire :**
- À l'ouverture de la page, **une première paire vide** est déjà affichée, prête à être remplie
- Un bouton **« Ajouter une paire »** permet d'ajouter une nouvelle ligne de saisie à la suite des précédentes
- Chaque paire dispose d'un bouton **« Supprimer »** (icône ou texte) permettant de retirer cette paire de la liste
- On ne peut pas supprimer la dernière paire restante : si l'utilisateur tente de le faire, un message lui indique qu'au minimum une paire est requise
- L'ordre des paires peut être réorganisé par glisser-déposer (drag & drop), sans rechargement de page
- Il n'y a pas de limite maximale stricte au nombre de paires, mais un avertissement non-bloquant est affiché au-delà de 50 paires pour informer l'utilisateur que la génération peut être plus longue

**Champ « Indice » :**
- Accepte tout texte libre (lettres, chiffres, ponctuation, caractères accentués)
- Longueur maximale : 250 caractères
- Aucune transformation automatique appliquée à ce champ
- Peut contenir des espaces, apostrophes, tirets, etc.

**Champ « Réponse » :**
- Longueur minimale : 2 caractères
- Longueur maximale : 30 caractères
- Uniquement des lettres (A–Z, a–z, caractères accentués courants : é, è, à, ê, etc.)
- Les espaces, chiffres et caractères spéciaux ne sont pas autorisés dans la réponse
- La saisie est automatiquement convertie en **majuscules** dès que le champ perd le focus (ou en temps réel, selon préférence du programmeur)
- Les tirets dans les mots composés sont acceptés uniquement si le choix est validé lors de la conception (à définir en accord avec le client) — par défaut, ils sont **refusés**

### 4.3 Bouton « Générer la grille »

- Un bouton principal **« Générer la grille »** est placé en bas du formulaire de saisie ou dans la barre d'actions
- Ce bouton déclenche la validation de toutes les paires, puis le processus de génération de la grille
- Il est désactivé tant qu'aucune paire valide n'est présente
- Pendant la génération, le bouton est désactivé et un indicateur visuel de chargement est affiché
- Si la génération réussit, la grille s'affiche dans la zone de prévisualisation
- Si la génération échoue, un message d'erreur explicatif est affiché (voir section 7)

***

## 5. Règles de validation des données

### 5.1 Validation globale

Avant de lancer la génération, le système vérifie :

- Qu'au moins **2 paires valides** sont présentes (une seule paire ne permet pas de créer une vraie grille de mots croisés avec intersections)
- Qu'aucun champ obligatoire n'est vide
- Que les champs « Réponse » respectent toutes les contraintes de format (voir 4.2)
- Qu'il n'existe pas de doublons exacts dans les réponses (deux mots identiques) — en cas de doublon, un avertissement est affiché, mais la génération peut quand même être tentée si l'utilisateur le confirme

### 5.2 Validation à la saisie (en temps réel)

- Chaque champ est validé individuellement au moment où il perd le focus
- Les erreurs de champ sont affichées directement sous le champ concerné, de façon non intrusive
- La couleur du bord du champ passe au rouge en cas d'erreur, au vert en cas de validation réussie
- Le compteur de caractères restants est affiché pour les deux champs

### 5.3 Règles sur les réponses pour la génération

Les mots utilisés comme réponses doivent permettre la construction d'une grille. Les règles suivantes s'appliquent lors de la phase de génération :

- Chaque mot doit partager au moins une lettre en commun avec au moins un autre mot de la liste pour pouvoir être placé dans la grille
- Si un mot ne peut pas être placé dans la grille (aucune intersection possible avec les autres), il est signalé comme « non placé » dans le résultat, et la grille est tout de même générée avec les mots restants
- Les mots trop courts (1 caractère) sont ignorés automatiquement avec un message d'avertissement

***

## 6. Algorithme de génération — comportement attendu (sans détail technique)

Le générateur doit produire une grille rectangulaire dans laquelle les mots sont placés horizontalement (sens gauche-droite) ou verticalement (sens haut-bas). Les règles de placement sont les suivantes :

### 6.1 Règles de placement des mots

- Les mots se croisent uniquement aux **lettres communes** : une lettre d'un mot horizontal peut coïncider avec une lettre d'un mot vertical, à condition que la lettre soit identique
- Deux mots parallèles (deux horizontaux ou deux verticaux) ne peuvent pas être placés côte à côte sans au moins une case vide entre eux
- Deux mots ne peuvent pas se toucher à leur extrémité sans se croiser (une case libre doit les séparer)
- Chaque mot doit être placé **entièrement à l'intérieur** des limites de la grille
- La grille doit être **connexe** : tous les mots placés doivent être reliés entre eux (directement ou indirectement via des intersections)

### 6.2 Orientation et numérotation

- Chaque mot se voit attribuer un numéro (1, 2, 3…) dans l'ordre de lecture standard : de gauche à droite, de haut en bas
- Les mots horizontaux sont désignés par leur numéro suivi de **« Horizontal »** ou **« →»**
- Les mots verticaux sont désignés par leur numéro suivi de **« Vertical »** ou **« ↓ »**
- Un même numéro peut désigner à la fois un mot horizontal et un mot vertical si leur case de départ est identique (ex. : « 3 → » et « 3 ↓ »)

### 6.3 Optimisation de la grille

- Le générateur doit tenter de placer le maximum de mots possible
- La grille générée doit être **aussi compacte que possible** (éviter les espaces inutiles)
- Si plusieurs dispositions sont possibles, privilégier celle qui maximise le nombre d'intersections
- La grille ne doit pas être excessivement rectangulaire ; une forme proche du carré est préférable pour l'esthétique

### 6.4 Cas d'échec partiel

- Si certains mots ne peuvent pas être placés, ils sont listés séparément dans un message récapitulatif après la génération
- L'utilisateur peut alors modifier ces mots (indice ou réponse) et relancer la génération
- La génération d'une grille avec **0 mot placé** est considérée comme un échec total et bloque l'enregistrement

***

## 7. Gestion des erreurs et messages

Tous les messages d'erreur, d'avertissement et de succès doivent respecter les principes suivants :

- **Clairs et non techniques** : rédigés en langage naturel, compréhensibles par tout utilisateur
- **Contextualisés** : ils indiquent quel champ ou quelle paire est concerné
- **Non intrusifs** : les avertissements n'interrompent pas le flux de travail (pas de boîtes de dialogue bloquantes sauf pour les confirmations destructives)

### Liste des messages à prévoir

| Situation | Type | Message proposé |
|-----------|------|-----------------|
| Champ « Réponse » vide | Erreur | « La réponse est obligatoire. » |
| Réponse contenant des chiffres ou caractères spéciaux | Erreur | « La réponse ne peut contenir que des lettres. » |
| Réponse inférieure à 2 caractères | Erreur | « La réponse doit contenir au moins 2 lettres. » |
| Réponse dépassant 30 caractères | Erreur | « La réponse ne peut pas dépasser 30 caractères. » |
| Indice vide | Erreur | « L'indice est obligatoire. » |
| Doublon de réponse | Avertissement | « Ce mot est déjà présent dans la liste. La grille pourrait être incorrecte. » |
| Moins de 2 paires valides | Erreur | « Veuillez saisir au moins 2 paires valides pour générer une grille. » |
| Tentative de supprimer la dernière paire | Avertissement | « Vous devez conserver au moins une paire. » |
| Mot non placé dans la grille | Information | « Le mot "[RÉPONSE]" n'a pas pu être placé dans la grille (aucune intersection possible). » |
| Génération réussie | Succès | « Votre grille a été générée avec succès ! [X] mots sur [Y] ont été placés. » |
| Génération échouée totalement | Erreur | « Impossible de générer une grille avec les mots fournis. Vérifiez que vos mots partagent des lettres communes. » |
| Sauvegarde réussie | Succès | « Votre grille a été sauvegardée avec succès. » |
| Suppression confirmée | Succès | « La grille a été supprimée. » |

***

## 8. Interface de prévisualisation de la grille

### 8.1 Rendu de la grille

- La grille est affichée sous forme d'un tableau à cases carrées
- Les cases **actives** (contenant une lettre) sont affichées en blanc avec une bordure visible
- Les cases **inactives** (vides, hors-mots) sont affichées en noir ou gris foncé
- Chaque case active peut contenir un numéro de départ (en haut à gauche de la case) lorsqu'un mot commence à cet emplacement
- Les lettres ne sont **pas affichées** dans la prévisualisation par défaut (mode joueur), sauf si le créateur active un mode « Afficher les solutions »

### 8.2 Interaction avec la grille (mode créateur)

- En mode « Afficher les solutions », chaque lettre de la grille est visible
- Le créateur peut basculer entre le mode « Grille vide » et le mode « Grille résolue » via un bouton bascule
- Un clic sur une case active met en surbrillance le mot complet auquel elle appartient (horizontal ou vertical), et affiche l'indice correspondant dans un panneau latéral ou une infobulle

### 8.3 Liste des indices

- À côté de la grille (ou en dessous sur mobile), la liste des indices est affichée, organisée en deux colonnes : **Horizontaux** et **Verticaux**
- Chaque indice est précédé de son numéro
- Un clic sur un indice met en surbrillance le mot correspondant dans la grille

***

## 9. Titre et métadonnées de la grille

L'utilisateur doit pouvoir saisir les informations suivantes pour sa grille :

| Champ | Obligatoire | Règles |
|-------|-------------|--------|
| **Titre de la grille** | Oui | 3 à 100 caractères, texte libre |
| **Description / consigne** | Non | Texte libre, max 500 caractères |
| **Niveau de difficulté** | Non | Sélecteur : Facile / Moyen / Difficile |
| **Visibilité** | Oui | Privé (par défaut) / Public / Partagé via lien |
| **Langue** | Oui | Sélecteur de langue (Français par défaut) |

Ces champs sont accessibles dans un formulaire distinct situé au-dessus ou à côté du formulaire de saisie des paires.

***

## 10. Sauvegarde, reprise et gestion des grilles

### 10.1 Sauvegarde manuelle

- La sauvegarde est déclenchée par un bouton **« Sauvegarder »** distinct du bouton de génération
- Une grille peut être sauvegardée dans deux états :
  - **Brouillon** : les paires ont été saisies mais la grille n'a pas encore été générée (ou la génération a échoué). Le titre est obligatoire pour sauvegarder un brouillon.
  - **Grille publiée** : la grille a été générée avec succès et au moins un mot a été placé
- Une grille sauvegardée contient : le titre, la description, les métadonnées, la liste complète des paires originales (indices et réponses), la disposition finale de la grille (positions et orientations de chaque mot placé)

### 10.2 Sauvegarde automatique (auto-save)

- Le système sauvegarde automatiquement les données saisies toutes les **60 secondes** dès qu'une modification a été détectée depuis la dernière sauvegarde
- L'auto-sauvegarde s'applique uniquement si la grille a déjà été sauvegardée au moins une fois manuellement (elle ne crée pas de nouvelle entrée automatiquement sans action initiale de l'utilisateur)
- Un indicateur discret affiche l'état de la sauvegarde automatique dans l'interface :
  - « Sauvegarde automatique en cours... »
  - « Sauvegardé automatiquement à [heure] »
  - « Modifications non sauvegardées » (si l'auto-save est en attente)
- L'auto-sauvegarde ne remplace pas la sauvegarde manuelle ; elle préserve uniquement les données de saisie, pas la disposition de la grille générée

### 10.3 Reprise d'une sauvegarde

- L'utilisateur peut reprendre une grille sauvegardée à tout moment depuis sa liste de grilles
- À l'ouverture d'une grille sauvegardée :
  - Les paires saisies sont restaurées dans le formulaire, dans leur ordre d'origine
  - Si la grille avait été générée, la dernière disposition validée est restaurée et affichée dans la zone de prévisualisation
  - Si la grille était en mode brouillon, seul le formulaire est restauré, la zone de prévisualisation reste vide
- L'utilisateur peut continuer à modifier les paires et relancer la génération comme lors d'une création initiale
- Lors de la réouverture, si des modifications auto-sauvegardées plus récentes existent (en cas de fermeture accidentelle), un message propose à l'utilisateur de **restaurer la version la plus récente** ou de **conserver la dernière version sauvegardée manuellement**

### 10.4 Reprise d'une grille en cours (session active)

- Si l'utilisateur ferme son navigateur ou navigue ailleurs sans sauvegarder, les données saisies sont conservées dans un **brouillon temporaire** lié à sa session
- À la prochaine visite de la page de création, un message s'affiche : **« Vous avez un brouillon non sauvegardé. Souhaitez-vous le reprendre ? »** avec les options « Reprendre » et « Commencer une nouvelle grille »
- Ce brouillon temporaire est conservé pendant **7 jours** maximum, puis supprimé automatiquement
- Un seul brouillon temporaire peut exister à la fois par utilisateur

### 10.5 Liste des grilles sauvegardées

- L'utilisateur dispose d'une page dédiée listant toutes ses grilles
- Chaque entrée affiche : titre, date de création, date de dernière modification, nombre de mots placés, statut (Brouillon / Générée), visibilité
- Les actions disponibles par grille : **Reprendre / Modifier**, **Dupliquer**, **Supprimer**, **Partager**
- La suppression d'une grille nécessite une confirmation explicite (fenêtre de confirmation avec bouton « Confirmer la suppression »)
- La pagination ou le défilement infini s'applique si le nombre de grilles dépasse 20
- Un filtre permet de n'afficher que les brouillons ou que les grilles générées

### 10.6 Modification d'une grille existante

- L'utilisateur peut rouvrir une grille sauvegardée pour modifier ses paires
- Après modification, il doit relancer la génération pour obtenir une nouvelle disposition
- L'ancienne disposition est conservée et affichée jusqu'à ce qu'une nouvelle génération soit validée et sauvegardée
- L'historique des versions n'est **pas** conservé (seule la dernière version sauvegardée est accessible)

***

## 11. Export PDF et impression

### 11.1 Deux types d'export PDF distincts

L'utilisateur dispose de **deux boutons d'export PDF séparés**, clairement identifiés :

#### PDF « À compléter » (grille vierge)

Ce document est destiné à être imprimé et rempli à la main par le joueur. Il contient :

- Le titre de la grille et, si présente, la consigne/description
- La grille complète avec toutes les cases actives **vides** (aucune lettre visible)
- Les cases de départ de chaque mot affichent uniquement leur **numéro** (en haut à gauche de la case)
- Les cases inactives sont affichées en noir plein
- La liste complète des indices, organisée en deux sections : **Horizontaux** et **Verticaux**, chacun précédé de son numéro
- Un espace de signature ou de nom en haut de page (champ « Nom : ___________»)
- La date de génération du document en pied de page
- Le PDF vierge **ne contient aucune réponse**, ni en filigrane, ni en page cachée

#### PDF « Corrigé » (grille avec réponses)

Ce document est destiné au créateur ou à la correction. Il contient :

- Le titre de la grille suivi de la mention **« — Corrigé »**
- La grille complète avec **toutes les lettres visibles** dans chaque case active
- Les numéros de départ sont conservés dans les cases concernées
- La liste complète des indices avec, en regard de chaque numéro, **la réponse en majuscules entre parenthèses**
  - Exemple : « 3 → Capitale de la France *(PARIS)* »
- La date de génération et la mention « Document réservé au correcteur » en pied de page

### 11.2 Format et qualité des PDF

- Les deux PDF sont générés au format **A4 portrait**
- La grille est centrée horizontalement sur la page
- Si la grille est très large (plus de 20 colonnes), le format **A4 paysage** est utilisé automatiquement
- La taille des cases s'adapte à la taille de la grille pour occuper au maximum la largeur disponible tout en restant lisible
- La taille minimale d'une case dans le PDF est de 8 mm × 8 mm
- La police des lettres dans le corrigé est en **gras**, centrée dans la case
- La police des indices est en taille lisible (minimum 10pt)
- Si la liste des indices dépasse une page, elle est automatiquement reportée sur une deuxième page

### 11.3 Boutons d'export dans l'interface

- Les deux boutons sont regroupés dans une même zone d'actions, clairement séparés visuellement
- Libellés proposés : **« Télécharger — Grille vierge (PDF) »** et **« Télécharger — Corrigé (PDF) »**
- Un indicateur de chargement s'affiche pendant la génération du PDF (la génération est côté serveur)
- Les boutons d'export ne sont actifs que lorsqu'une grille a été générée avec succès
- Les exports ne nécessitent pas que la grille soit préalablement sauvegardée

### 11.4 Impression directe

- Un bouton **« Imprimer »** ouvre la boîte de dialogue d'impression du navigateur
- L'utilisateur choisit dans l'interface s'il souhaite imprimer la **version vierge** ou le **corrigé** avant d'ouvrir la boîte d'impression
- Les éléments d'interface (boutons, menus, navigation) ne doivent pas apparaître à l'impression

***

## 12. Comportement sur mobile et responsive

- L'interface doit être utilisable sur tablette et mobile
- Sur mobile, les deux zones (formulaire de saisie et prévisualisation) s'affichent en mode vertical : d'abord le formulaire, puis la grille en dessous
- Les cases de la grille doivent s'adapter à la largeur de l'écran sans déborder (la grille est scrollable horizontalement si elle dépasse la largeur disponible)
- Les boutons doivent avoir une taille minimale de 44 x 44 pixels pour être facilement cliquables sur écran tactile
- Le glisser-déposer pour réorganiser les paires doit fonctionner sur écran tactile

***

## 13. Accessibilité

- Tous les champs de formulaire disposent d'un label visible et d'un attribut accessible
- Les messages d'erreur sont associés au champ concerné de façon accessible
- La navigation au clavier doit être possible sur l'ensemble du formulaire
- Le contraste des couleurs respecte les recommandations WCAG AA (rapport minimum 4.5:1 pour le texte)
- Les icônes fonctionnelles (supprimer, déplacer) disposent d'un texte alternatif ou d'un label accessible

***

## 14. Performance et contraintes techniques générales

- La génération de la grille doit s'effectuer côté serveur (pas exposée côté client)
- Le résultat de la génération est retourné via une requête asynchrone (sans rechargement de page)
- Le délai de génération attendu est inférieur à 3 secondes pour une liste de 30 mots ou moins
- Au-delà de 50 mots, le délai peut être plus long ; un message d'attente doit informer l'utilisateur
- Les données de saisie ne doivent pas être perdues en cas de rechargement accidentel (avertissement avant quitter la page si des modifications non sauvegardées sont présentes)

***

## 15. Sécurité et contrôle d'accès

- L'accès au module de création est réservé aux utilisateurs **authentifiés**
- Un utilisateur ne peut voir, modifier ou supprimer que **ses propres grilles**
- Les données saisies sont nettoyées (assainissement) côté serveur avant traitement
- Les tentatives de saisie de contenu malveillant (scripts, balises HTML) dans les champs sont ignorées côté serveur sans générer d'erreur visible pour l'utilisateur (les données sont nettoyées silencieusement)
- Le partage d'une grille via lien public génère un jeton unique non-devinable ; ce lien n'expose pas l'identité du créateur

***

## 16. Règles d'interface globales

Ces règles s'appliquent à l'ensemble du module :

- **Cohérence visuelle** : tous les éléments du module utilisent le même système de design que le reste de l'application
- **Aucune action destructive sans confirmation** : suppression de paire (si >1 paire), suppression de grille, réinitialisation du formulaire
- **États des boutons** : chaque bouton principal doit avoir un état normal, survol (hover), actif, désactivé et chargement, visuellement distincts
- **Indicateurs de chargement** : toute action asynchrone (génération, sauvegarde) doit afficher un indicateur de progression
- **Retours utilisateur** : chaque action réussie doit être confirmée par un message de succès temporaire (notification qui disparaît après 3 à 5 secondes)
- **Internationalisation** : toutes les chaînes de texte de l'interface doivent être externalisées dans des fichiers de traduction pour faciliter la localisation future

***

## 17. Cas limites et comportements spéciaux à gérer

| Cas limite | Comportement attendu |
|------------|----------------------|
| Tous les mots sont identiques | Erreur : doublons détectés, génération impossible |
| Un seul mot de 2 lettres avec tous les autres | Le système tente de placer le mot court en priorité à une intersection |
| Mots très longs (>20 lettres) | Placés en priorité (ils ont plus d'intersections potentielles) |
| Grille avec 1 seul mot placé | Considérée comme un échec ; enregistrement bloqué |
| Réponse avec accents (ex. : ÉTOILE) | Acceptée ; les accents sont conservés dans la grille |
| Utilisateur quitte la page sans sauvegarder | Fenêtre de confirmation : « Vous avez des modifications non sauvegardées. Quitter quand même ? » |
| Connexion perdue pendant la génération | Message d'erreur réseau ; les données saisies sont préservées dans le formulaire |
| Régénération après modification | L'ancienne grille est masquée, un spinner s'affiche, puis la nouvelle grille remplace l'ancienne |

***

## 18. Livrables attendus du programmeur

À la fin du développement, le programmeur doit fournir :

1. Le module fonctionnel intégré à la plateforme Laravel existante
2. Les migrations de base de données nécessaires au stockage des grilles et des paires
3. Les routes, contrôleurs et vues correspondants, respectant la structure Laravel du projet
4. Les fichiers de traduction en français (et en anglais si requis)
5. Un document de recette listant les cas de test à valider avant la mise en production
6. Aucune dépendance externe ne doit être ajoutée sans validation préalable du responsable technique

***

## 19. Critères d'acceptation (définition du « done »)

Une fonctionnalité est considérée comme terminée uniquement lorsque :

- Elle respecte **toutes** les règles décrites dans ce devis
- Elle a été testée sur les navigateurs suivants : Chrome (dernière version), Firefox (dernière version), Safari (dernière version), Edge (dernière version)
- Elle a été testée sur mobile (iOS Safari et Android Chrome)
- Elle ne génère aucune erreur visible dans la console du navigateur
- Les messages d'erreur et de succès s'affichent correctement
- Les données sont correctement persistées en base de données et restituées fidèlement
- La génération de la grille produit un résultat cohérent avec les règles de mots croisés standard

***

*Document rédigé à titre de devis fonctionnel destiné aux programmeurs. Toute ambiguïté ou cas non couvert doit être soumis au responsable de projet avant le début du développement.*