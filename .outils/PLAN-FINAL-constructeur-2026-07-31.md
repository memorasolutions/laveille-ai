# Plan final : refonte du constructeur de prompts

2026-07-31 (America/Toronto). Version corrigée après refus du propriétaire : aucune fonction
existante ne disparaît, vocabulaire exact (pas d'IA dans l'outil), document lisible par un
néophyte total. Fondé sur la lecture du code réel (`constructeur-prompts.blade.php` et
`constructeur-prompts-core.js`), pas sur des suppositions.

Ceci est une proposition. Rien n'est déployé tant que le banc d'essai (section 9) n'a pas
prouvé que les prompts produits sont au moins aussi bons qu'avant.

---

## 1. À quoi sert l'outil, en 5 lignes

Le constructeur de prompts aide une personne à écrire une bonne demande à donner à une IA
(ChatGPT, Claude, Gemini, etc.). Un « prompt », c'est simplement le texte qu'on tape dans la
boîte de conversation d'une IA pour lui demander quelque chose. L'outil ne contient aucune
intelligence artificielle : il assemble un texte à partir de gabarits (des blocs de phrases
tout faits) selon les choix de la personne, puis elle copie ce texte et le colle où elle veut.
Résultat : un prompt plus précis, écrit en quelques clics au lieu d'être rédigé de zéro.

---

## 2. Le problème actuel

Le formulaire actuel compte **19 champs**, organisés en accordéons (des sections qu'il faut
ouvrir et fermer une à une, cachant leur contenu par défaut). Trois défauts concrets :

| Défaut | Effet pour la personne |
|---|---|
| Accordéons fermés par défaut | On ne voit jamais tout le formulaire d'un coup ; certaines options passent inaperçues |
| Étiquettes en jargon interne | « Persona », « verbe d'action », « chaîne de pensée » ne veulent rien dire pour un débutant |
| 19 champs, dont 5 qui ne servent à rien | Le formulaire paraît plus compliqué qu'il ne devrait l'être |

De plus, le prompt final est identique peu importe l'IA choisie (ChatGPT, Claude, Perplexity,
Gemini, Mistral) : seule l'adresse (URL) d'ouverture change. Gemini et Mistral ne reçoivent
même pas le texte automatiquement : la personne doit le coller elle-même dans la fenêtre de
conversation.

---

## 3. Ce qui ne change pas : toutes les fonctions existantes restent

Aucune fonction actuelle n'est retirée. Une fonction peut changer de PLACE dans l'écran, de
LIBELLÉ (le mot affiché) ou de VALEUR PAR DÉFAUT (cochée ou non au départ), mais elle reste
toujours disponible et toujours capable d'influencer le prompt final.

### 3.1 Les sept fonctions de réglage du prompt

| Fonction (nom actuel dans le code) | Bénéfice concret pour la personne | Défaut aujourd'hui | Défaut proposé |
|---|---|---|---|
| **Règles typographiques** (`constraintTypo`) | Le texte que l'IA produira respectera les règles d'écriture en français (majuscule seulement en début de phrase, pas de tiret cadratin, accents). Utile pour un texte destiné à être publié tel quel. | Décochée | Décochée (inchangé) |
| **Écriture naturelle, anti-IA** (`constraintAntiAI`) | Empêche l'IA de produire un texte qui « sonne robot » (phrases toutes pareilles, expressions toutes faites comme « dans un monde en constante évolution »). Le résultat ressemble davantage à un texte écrit par une personne. | Cochée | Cochée (inchangé) |
| **Comment l'IA doit réfléchir** (`technique`) | Choisit la méthode que l'IA emploie pour construire sa réponse. Change la qualité et la longueur du raisonnement visible. Voir le détail des 5 choix ci-dessous. | « Réponse directe » (zero-shot) | Inchangé, mais mieux expliqué |
| **Poser des questions** (`constraintAskIfUnclear`) | Si la demande est incomplète, l'IA peut soit poser une question avant de répondre, soit répondre quand même en indiquant clairement ce qu'elle a supposé. Case cochable dans les deux cas ; voir section 3.3 pour le changement de comportement par défaut. | Décochée | Décochée (comportement par défaut modifié, pas la case elle-même) |
| **Réflexion étape par étape** (`constraintChainOfThought`) | Demande à l'IA de montrer son raisonnement complet avant de donner la réponse finale, plutôt que de livrer seulement le résultat. Utile pour un calcul, un problème logique ou une décision à justifier. | Décochée | Décochée (inchangé) |
| **Exemples** (`examples`, few-shot) | On donne 2 ou 3 exemples de ce qu'on attend comme résultat ; l'IA imite le style ou le format de ces exemples. Apparaît seulement quand la méthode de réflexion choisie inclut des exemples. | Champ vide, masqué | Inchangé |
| **Séparer clairement les données** (`useDelimiters`) | Ajoute des marqueurs (###) qui indiquent clairement où commencent et où finissent les données à traiter (un texte à résumer, par exemple), pour que l'IA ne les confonde pas avec les instructions. | Décochée | Décochée (inchangé) |

**Explication des 5 choix de « Comment l'IA doit réfléchir »** (termes techniques traduits en
langage simple) :

| Choix | Ce que ça veut dire |
|---|---|
| Réponse directe (zero-shot) | On demande directement, sans donner d'exemple ni demander de raisonnement visible. |
| Réponse directe + réflexion étape par étape (zero-shot-cot) | L'IA réfléchit avant de répondre, mais sans exemple fourni. « Chain of thought », ou chaîne de pensée : l'IA explique les étapes de son raisonnement avant la réponse finale. |
| Avec des exemples (few-shot) | On donne 2 ou 3 exemples de ce qu'on attend, pour montrer le style ou le format voulu. |
| Avec des exemples + réflexion étape par étape (few-shot-cot) | Les deux à la fois : des exemples fournis, et un raisonnement détaillé affiché. |
| Par étapes, avec validation (iterative) | L'IA avance un morceau à la fois et attend l'accord de la personne avant de continuer, plutôt que de tout produire d'un coup. |

### 3.2 Les autres fonctions conservées intégralement

| Fonction | Bénéfice concret |
|---|---|
| Export en fichier .txt | Permet de garder une copie du prompt hors du site, dans un fichier texte. |
| Diagnostic avec bouton « Compléter » | Signale les champs manquants et propose de les remplir en un clic. |
| Import des prompts sauvegardés localement | Récupère d'anciens prompts stockés seulement dans le navigateur, sans compte. |
| Duplication d'un prompt | Copie un prompt existant pour en faire une variante sans repartir de zéro. |
| Étiquettes (tags) | Classe les prompts sauvegardés par catégorie pour les retrouver plus vite. |
| Favoris | Marque les prompts les plus utilisés pour y revenir rapidement. |
| Recherche | Retrouve un prompt sauvegardé par mot-clé. |
| Plein écran | Agrandit la zone de rédaction pour travailler plus confortablement. |
| Historique invité | Garde les prompts créés même sans compte utilisateur, dans le navigateur. |
| Transfert depuis l'anonymiseur | Reprend un texte déjà nettoyé de renseignements personnels dans l'anonymiseur pour construire un prompt avec. |
| Cartes personnalisables avec les icônes | Permet de créer ses propres modèles de départ, avec environ 200 icônes au choix pour les repérer visuellement. |
| Bouton « Améliorer » | Produit un second texte, tout fait, qui dit à une IA : « améliore le prompt suivant sans changer l'intention ». L'outil ne reformule RIEN lui-même : il prépare cette consigne, la personne la copie vers son IA, et c'est l'IA qui fait le travail. |
| Masquage des renseignements personnels | Retire automatiquement les informations sensibles (noms, courriels, etc.) avant que le prompt soit copié. |
| Bibliothèque « Mes prompts » | Espace personnel où sont rangés tous les prompts créés ou sauvegardés. |

**Correction au passage (pas une suppression)** : le bouton « Partager » partage actuellement
l'adresse de la page de l'outil, pas le contenu du prompt en cours. Ce sera corrigé pour
partager le prompt réellement affiché.

### 3.3 Le seul changement de comportement (pas de suppression)

La case « Poser des questions » reste une case à cocher, disponible dans les deux réglages.
Ce qui change, c'est uniquement ce qui se passe **quand elle est décochée** (le défaut) :

- **Aujourd'hui** : rien n'est demandé à l'IA à ce sujet ; elle devine et répond, sans le dire.
- **Proposé** : par défaut, l'IA livre quand même un résultat, mais elle déclare les hypothèses
  qu'elle a prises (par exemple : « J'ai supposé que c'était pour des élèves du primaire ;
  dites-le-moi si ce n'est pas le cas »). La personne peut alors corriger en une phrase au lieu
  de rester bloquée devant une question sans savoir y répondre.

Raison du changement : une personne qui débute ne sait pas toujours répondre à une question de
clarification de l'IA et abandonne. Une personne expérimentée, elle, préfère souvent qu'on lui
pose la question. C'est pour ça que la case reste : cochée, elle redonne l'ancien comportement
(l'IA pose une vraie question avant de répondre) ; décochée, elle donne le nouveau comportement
par défaut (résultat livré avec hypothèses déclarées).

---

## 4. Ce qui est supprimé : 5 éléments qui ne produisent RIEN

Seuls ces cinq éléments disparaissent, parce qu'ils ne changent jamais une seule ligne du
prompt final ou ne sont jamais atteignables. Preuve pour chacun :

| Élément supprimé | Preuve qu'il ne produit rien |
|---|---|
| Langue quand elle vaut « français » | C'est déjà la valeur par défaut ; aucune ligne n'est ajoutée au prompt dans ce cas (le code ne génère une instruction de langue que si la valeur est « anglais » ou « espagnol »). Le champ reste utile seulement pour ces deux cas et sera déplacé dans les options avancées. |
| La carte d'objectif choisie au départ | Cette carte sert seulement à préremplir d'autres champs (rôle, verbe, etc.) ; sa valeur elle-même n'apparaît jamais dans le texte du prompt généré. |
| `audiencePreset` (au singulier) | Une variable de l'ancien système à choix unique pour le public visé ; elle n'est plus reliée à aucun champ de l'interface actuelle (remplacée par `audiencePresets`, au pluriel, qui permet plusieurs choix). |
| La branche « aucune audience » (`audienceType = 'none'`) | Ce cas de code existe mais aucun bouton de l'interface ne peut y mener : impossible à atteindre en utilisant le formulaire normalement. |
| Le texte d'aide de la Destination | Une phrase d'explication écrite dans le code pour ce champ, mais jamais affichée nulle part à l'écran. |

Aucun de ces cinq éléments n'est une fonction que la personne utilise ou remarque : leur retrait
ne change rien à ce qu'elle peut faire avec l'outil.

---

## 5. La nouvelle organisation : 3 écrans, zéro accordéon

Le principe : tout ce qui est visible reste visible en tout temps sur chaque écran. Plus de
sections qu'il faut cliquer pour ouvrir avant de voir leur contenu (un « accordéon », dans le
jargon d'interface, est justement une section repliée par défaut qu'il faut dérouler).

### Écran 1 : la demande

Un grand champ de texte : « Que voulez-vous demander à l'IA ? »
En dessous : des cartes de départ (des blocs cliquables avec une icône et un titre, comme
« Rédiger un texte » ou « Corriger du code ») pour les demandes fréquentes, plus les cartes que
la personne a créées elle-même. Un bouton : « Créer mon prompt ».

### Écran 2 : le prompt est prêt

Le texte généré s'affiche en couleur : ce que la personne a écrit dans une teinte, ce que
l'outil a ajouté automatiquement dans une autre, avec une légende d'une ligne pour expliquer le
code de couleur. Le but : montrer clairement le travail que l'outil vient de faire, qui serait
autrement invisible.

Actions disponibles : Ouvrir dans [nom de l'IA choisie] · Copier · Améliorer · Cadre strict
activé/désactivé (voir section 7).

### Écran 3 : améliorer, blocs tous visibles

Tous les réglages sont regroupés en blocs affichés en même temps, sans repli. Voir le tableau
de correspondance à la section 6 pour savoir où va chaque fonction actuelle.

---

## 6. Où va chaque fonction conservée

| Fonction actuelle | Nouvel emplacement | Nouveau libellé en français simple |
|---|---|---|
| Rôle / persona | Bloc « Le ton » | « Sur quel ton l'IA doit-elle répondre ? » |
| Verbe d'action | Bloc « Le résultat » | « Vous voulez quoi au juste ? » |
| Public visé (audience) | Bloc « Pour qui » | « Qui va lire ça ? » |
| Format attendu | Bloc « Le résultat » | fusionné avec le champ précédent |
| Longueur | Bloc « Le résultat » | fusionné avec le champ précédent |
| Ton | Bloc « Le ton » | fusionné avec le rôle |
| Contraintes personnalisées | Bloc « Les limites » | « Quelque chose à respecter ? » |
| Règles typographiques | Bloc « Les limites » | case « Respecter la typographie française » |
| Écriture naturelle (anti-IA) | Bloc « Les limites » | case « Écriture naturelle (anti-IA) », cochée par défaut |
| Comment l'IA doit réfléchir (technique) | Bloc « Un modèle » | « Comment l'IA doit-elle s'y prendre ? » |
| Exemples (few-shot) | Bloc « Un modèle » | apparaît seulement si « Avec des exemples » est choisi |
| Poser des questions | Bloc « Options » | case « Poser une question si ma demande n'est pas claire », décochée par défaut (voir 3.3) |
| Réflexion étape par étape | Bloc « Options » | case « Demander à l'IA de montrer son raisonnement » |
| Délimiteurs (###) | Bloc « Options » | case « Séparer clairement mes données du reste » |
| Langue (si différente du français) | Bloc « Options » | menu « Langue de la réponse », visible seulement si on veut changer de langue |
| Destination / Canvas | Bloc « Options » | « Créer un document modifiable » (voir explication ci-dessous) |

**Sur la Destination** : puisque le texte du prompt ne change pas selon l'IA de destination
(ChatGPT, Claude, etc.), demander cette information au début du parcours n'apporte rien avant
l'écran 2. Le choix de l'IA devient le bouton d'action final de l'écran 2 (« Ouvrir dans
ChatGPT », « Ouvrir dans Claude »...). Le réglage « Canvas / artefact » (qui, lui, modifie
vraiment le texte du prompt en ajoutant une instruction de mise en page) reste une fonction à
part entière, renommée « Créer un document modifiable », dans le bloc Options de l'écran 3.

---

## 7. Les règles conditionnelles par profil : des règles par mots-clés, pas de la détection

L'outil ne « comprend » rien et ne « détecte » aucune intention : il n'y a aucune IA branchée
dessus. Ce que fait l'outil, c'est repérer certains mots-clés dans la demande tapée (par
exemple « code », « fonction », « traduire ») et proposer, à partir de ça, un profil de départ.
Ces règles se trompent parfois, donc elles doivent toujours être visibles à l'écran et
modifiables en un clic. L'interface n'affichera jamais une phrase comme « J'ai compris que... »
(ce serait faux, puisque rien n'a été compris) mais plutôt : « Vous avez choisi Programmation,
j'ajoute donc les règles de mise en forme du code. »

Trois profils, choisis par mots-clés et toujours corrigeables :

| Profil | Ce qui s'applique automatiquement | Corrigeable ? |
|---|---|---|
| **Texte** (profil de départ) | Écriture humaine (anti-IA), typographie française, ton, critères de qualité | Oui, en changeant le profil affiché à l'écran |
| **Programmation** | Aucune règle de style d'écriture en français ; ajout des règles de mise en forme du code | Oui |
| **Traduction / autre langue** | Aucune règle de français du Québec appliquée au résultat | Oui |

Un interrupteur visible « Cadre strict : activé » permet de désactiver d'un coup toutes les
règles automatiques du profil, pour une personne qui préfère tout régler elle-même.

---

## 8. Le design

Objectif : que chaque détail paraisse voulu, pas plus d'effets visuels, seulement plus de
clarté.

- Espace blanc assumé : la page doit paraître calme, jamais vide.
- Une seule hiérarchie évidente par écran (on sait toujours où regarder en premier).
- Micro-interactions utiles seulement : confirmer un clic, montrer ce qui vient de changer.
- Deux tailles de titre, un seul style de corps de texte, rien de plus.
- La colorisation du prompt à l'écran 2 (section 5) est le moment où la valeur ajoutée de
  l'outil devient visible d'un coup d'oeil.

---

## 9. La condition de déploiement : le banc d'essai

**Obligatoire avant toute mise en ligne** : un banc d'essai comparatif sur 25 à 30 demandes
réelles, fixées à l'avance (les mêmes demandes utilisées pour les deux versions, pour comparer
équitablement), avec l'ancienne version du formulaire contre la nouvelle, testées sur les trois
IA principales (ChatGPT, Claude, Gemini), avec une grille de notation fixe.

Le risque le plus important n'est pas visuel : c'est de livrer un outil plus agréable à
utiliser mais qui produit des prompts de moins bonne qualité. Ce risque-là se révèle seulement
dans la réponse de l'IA, une fois le prompt copié ailleurs, donc personne ne le verrait sans ce
test.

**Condition ferme : aucun déploiement si les prompts produits par la nouvelle version sont
jugés moins bons que ceux de l'ancienne.**

---

## 10. Pièges techniques connus

À vérifier explicitement pendant le développement, chacun ayant déjà causé un bogue réel dans
des outils similaires :

| Piège | Explication simple |
|---|---|
| Presse-papiers cassé sur iPhone (Safari) | Si le code attend (`await`) une autre opération juste avant de copier le texte, la copie échoue silencieusement sur Safari iOS. Il faut copier immédiatement, sans attente entre-deux. |
| `localStorage` effacé par Safari | Safari efface les données stockées localement dans le navigateur après 7 jours d'inactivité, et aussi en navigation privée. Les prompts sauvegardés seulement dans le navigateur peuvent donc disparaître. |
| Bouton Retour du navigateur | Le passage entre les 3 écrans doit rester compatible avec le bouton Retour habituel du navigateur, sans perdre le travail en cours. |
| Schéma de données versionné | La structure des prompts déjà sauvegardés (avant la refonte) doit rester lisible après la mise à jour : prévoir un numéro de version dans les données enregistrées. |
| Cartes = vrais boutons radio | Les cartes cliquables de sélection doivent être de vrais boutons radio (un composant standard accessible au clavier et aux lecteurs d'écran), jamais de simples blocs (`div`) qui imitent visuellement un bouton sans en avoir le comportement. |

---

## 11. Ordre d'exécution

1. Supprimer les 5 éléments morts (section 4). Test de non-régression pour s'assurer que rien
   d'autre n'est touché.
2. Construire le moteur de profils par mots-clés (section 7) et le nouveau comportement par
   défaut de « Poser des questions » (section 3.3), sans retirer la case existante.
3. Construire l'écran 2 : colorisation du prompt et boutons d'action.
4. Construire l'écran 3 : tous les blocs de la section 6, toujours visibles, sans accordéon.
5. Construire l'écran 1 et les cartes de démarrage.
6. Lancer le banc d'essai comparatif (section 9). Étape de contrôle : pas de suite tant que le
   résultat n'est pas au moins équivalent à l'ancienne version.
7. Tests automatisés, vérification visuelle réelle de l'interface, mise à jour du numéro de
   version, puis déploiement.
