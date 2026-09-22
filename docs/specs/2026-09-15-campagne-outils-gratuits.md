# Campagne sociale : annoncer les outils gratuits, un par un

> Ticket #2585. Rédigé le 15 septembre 2026.
>
> **RIEN N'EST PUBLIÉ DEPUIS CE DOCUMENT.** Il prépare les textes et l'ordre de passage. Chaque
> annonce est montrée à Stéphane, texte exact et destination, avant le moindre envoi.

---

## Pourquoi cette campagne remplace les annonces d'actualités

Les mesures du club des sages (#2581) convergent, et elles sont sans appel :

- **3 oracles sur 4** ont désigné « publier plus de fiches d'actualité » comme la **pire** piste.
- Les outils gratuits sont le **premier poste** du site, 400+ sessions sur 28 jours.
- La liste des actualités est lue par **six personnes**. Le travail éditorial quotidien s'adresse,
  pour l'essentiel, à l'exploitant lui-même.
- Le site sait acquérir de vrais inconnus quand il répond à une question précise : **110 nouvelles
  personnes** sur une seule fiche de vérification.

Annoncer un outil, c'est répondre à une question précise. Annoncer une actualité de plus, c'est
ajouter au stock que personne ne lit.

---

## L'ordre de passage, établi sur les chiffres et non au hasard

Mesure Google Analytics sur **90 jours**, colonne décisive : les **nouvelles personnes** attirées.

| Rang | Outil | Sessions | Personnes | Nouvelles | Pourquoi ce rang |
|---|---|---|---|---|---|
| 1 | **Anonymiseur** | absent du top 45 | - | - | Parfaitement aligné sur « conformité Loi 25 », et **invisible**. Le plus grand écart entre valeur et notoriété. |
| 2 | Constructeur de prompts | 468 | 161 | 69 | Le vaisseau amiral. Déjà connu, mais c'est lui qui retient. |
| 3 | Prompteur | absent du top 45 | - | - | Même famille que le constructeur, jamais annoncé. |
| 4 | Générateur de mots de passe | absent du top 45 | - | - | Sécurité : dans le périmètre, jamais montré. |
| 5 | Code QR | 86 | 42 | 12 | Fonctionne déjà seul, une annonce l'amplifie. |
| 6 | Décido | 11 par sondage | 10 | **8** | Le meilleur taux de nouveauté du site. Débloqué par la page publique livrée en v1.287.0. |
| 7 | Raccourcisseur | 44 | 16 | 2 | Utilitaire, faible nouveauté. |
| 8 | Oscilloscope RLC | 15 | 9 | 4 | Niche technique, public précis. |
| 9 | Simulateur fiscal, calculatrice | 28 / 16 | 20 / 7 | **0** / 1 | Zéro nouvelle personne en 90 jours : ils ne servent qu'aux mêmes. |
| – | Sudoku, mots croisés, minuteur | 21 / 16 / 28 | 11 / 10 / **4** | **0** | **Hors LinkedIn.** Le club des sages a noté qu'ils brouillent la thématique. Le minuteur fait 28 sessions pour 4 personnes : c'est un usage de classe, pas un produit. |

**Cinq outils ont ZÉRO nouvelle personne en 90 jours.** Ce n'est pas qu'ils sont mauvais : c'est que
personne ne sait qu'ils existent. C'est exactement ce que la campagne corrige.

---

## Annonce nº 1 : l'anonymiseur

### Ce que l'outil fait vraiment, vérifié en production le 2026-09-15

Il ne pose **pas** d'étiquettes `[NOM]` dans le texte. Il **substitue des données fictives
cohérentes** :

| Dans le texte d'origine | Après anonymisation |
|---|---|
| Marie Tremblay | Nathalie Gagnon |
| 418 555-0142 | 775 555-5807 |
| marie.tremblay@exemple.ca | nathalie.gagnon@example.net |
| 250 rue des Érables | 882 boulevard Saint-Joseph |
| 000-000-000 | 247-086-807 |

> Sortie RÉELLE relevée en production le 2026-09-17. Les valeurs changent à chaque passage : ce
> sont des substitutions tirées au sort, pas une table fixe. La capture de l'annonce montre
> exactement ces valeurs.

C'est nettement plus malin qu'un marqueur : **l'IA continue de comprendre ce qu'elle lit**, donc sa
réponse reste utilisable. Puis l'étape 2 de l'outil remet les vraies valeurs dans la réponse.

**Affirmation forte, et elle est PROUVÉE** : pendant toute l'anonymisation, l'inspecteur réseau n'a
enregistré **aucune requête** sortante liée au texte. Les seuls appels observés sont la mesure de
performance de l'hébergeur et l'enregistrement du choix de témoins. On peut donc écrire « tout se
passe dans ton navigateur » sans mentir.

### Les textes, version 2 - réécrits le 2026-09-21 après DEUX rounds du club des sages

> **La version 1 de ces deux textes est morte, et il faut savoir pourquoi.** Les cinq oracles
> (Codex, Perplexity, ChatGPT, Gemini, claude.ai), consultés en aveugle, ont convergé sur le même
> verdict : c'était une **fiche produit**, pas quelque chose qu'on relaie. Elle ouvrait sur le nom
> du produit au lieu du problème du lecteur, et quatre oracles sur cinq ont pointé la même phrase
> comme signature d'écriture machine : *« Le numéro reste un numéro de téléphone, l'adresse
> reste une adresse de Québec, le courriel reste un courriel. »* - un tricolon parfaitement
> symétrique. Elle a été coupée.

**Texte LinkedIn** (599 caractères, première ligne 74) :

> J'ai fait résumer un dossier par ChatGPT sans lui donner un seul vrai nom.
>
> Le dossier est fictif, l'essai est réel. Un outil a remplacé les coordonnées par d'autres tout aussi crédibles : Marie Tremblay est devenue Nathalie Gagnon. L'IA a travaillé là-dessus, puis l'outil a remis les vrais noms dans sa réponse.
>
> Ce qu'une page de vente ne dirait pas : c'est réversible, exprès. La Loi 25 garde « anonymiser » pour ce qui est irréversible. Ici, l'IA ne voit pas tes vraies données. Ce n'est pas une conformité.
>
> Gratuit, sans compte. Le lien et le code QR sont sur la dernière diapositive.
>
> #Loi25

**Texte Facebook** (475 caractères, sous la coupure « voir plus » de 477) :

> J'ai fait résumer un dossier par ChatGPT sans lui donner un seul vrai nom.
>
> Le dossier est fictif, l'essai est réel. Un outil remplace les coordonnées par d'autres tout aussi crédibles, l'IA travaille là-dessus, puis on recolle sa réponse dans l'outil, qui remet les vraies valeurs.
>
> Un conseil que je me donne aussi : relis la restauration. Si l'IA mélange deux personnes, les vrais noms reviennent sur la mauvaise.
>
> Gratuit, sans compte. Le lien est en premier commentaire.

### Ce que le round 2 a détruit, et qui n'aurait pas dû partir

Le deuxième round, lancé contre la RÉÉCRITURE et non contre la version 1, a intercepté une
**preuve qui ne prouvait rien** :

> « Coupe ton Wi-Fi et refais l'essai, ça fonctionne encore. »

La formule venait d'un oracle du round 1, elle était séduisante, et elle est **fausse comme
preuve** : une page déjà chargée continue de fonctionner hors ligne, que le code envoie des
données ou non. Elle aurait pu prouver le contraire de ce qu'on voulait, et le lecteur technique
l'aurait relevé en commentaire. Remplacée par la mesure réelle, qui figure maintenant sur la
diapositive 3 du carrousel : l'inspecteur réseau n'enregistre aucune requête, mesuré les 15 et
17 septembre 2026.

**Leçon, au-delà de ce texte** : une bonne idée d'oracle reste une idée non vérifiée. Le round de
réfutation doit porter sur la RÉÉCRITURE, sinon il valide ce qu'on vient d'y introduire.

### La contrainte juridique, trouvée par claude.ai et VÉRIFIÉE contre la loi

Le mot « anonymiser » a un sens précis au Québec, et ce n'est pas le nôtre. Vérifié le
2026-09-21 par recherche indépendante (sources : cai.gouv.qc.ca, legisquebec.gouv.qc.ca) :

| Terme | Ce que dit la loi | Statut du renseignement |
|---|---|---|
| **Anonymisé** (art. 23, P-39.1) | il n'est plus raisonnable de prévoir qu'on puisse identifier la personne, de façon **irréversible** | cesse d'être un renseignement personnel |
| **Dépersonnalisé** (art. 12) | ne permet plus d'identifier **directement** | **reste** un renseignement personnel |

Notre outil est **réversible par conception** - c'est sa fonctionnalité, l'étape 2 remet les vraies
valeurs. Le texte ne peut donc promettre aucune conformité, et il ne le fait plus : il dit
exactement ce que le lecteur gagne, et ce qu'il ne gagne pas.

Cette nuance n'est pas un frein à la viralité, c'est le contraire : **une page de vente ne se
limite jamais elle-même.** C'est ce qui fait que le texte ne se lit pas comme une machine.

> ⚠️ **Défaut distinct, EN PRODUCTION, tracé au ticket #2721** : la page de l'outil affiche
> aujourd'hui deux badges « Conforme Loi 25 » et « Conforme RGPD », et sa méta
> description dit « Conforme à la Loi 25 ». Rien n'a été modifié : c'est un choix de
> positionnement produit, il revient à Stéphane.

### Le visuel : un carrousel PDF de 5 diapositives, fabriqué le 2026-09-21

**`docs/campagne-outils-gratuits/01-anonymiseur-carrousel.pdf`** - 5 pages carrées, 112 Ko,
très loin du plafond de 20 Mo du portail. Source HTML conservée à côté
(`01-anonymiseur-carrousel.source.html`) pour refaire ou décliner.

| Diapo | Ce qu'elle porte |
|---|---|
| 1 | L'accroche, seule, sur fond plein |
| 2 | **Le parcours d'une phrase réelle** : ce que tu écris → ce que l'IA reçoit, en gros texte |
| 3 | Ce qui part sur le réseau : « Rien », avec la mesure datée |
| 4 | La mise en garde : relis la restauration, et la nuance Loi 25 |
| 5 | Le code QR et l'adresse |

**La diapositive 2 est l'idée la plus forte du cycle**, et trois oracles sur cinq l'ont proposée
indépendamment : montrer le PARCOURS plutôt que décrire le mécanisme. Un premier essai plaçait la
capture d'écran de 1440 x 280 px dans la page : elle était **illisible sur un téléphone**, ce que
seule l'inspection visuelle image par image a montré. Refaite en texte typographié.

**Contrôles passés, chacun mesuré et non supposé** :
- Code QR **décodé** (OpenCV) : il rend bien `https://laveille.ai/outils/anonymiseur`, et cette
  page répond 200. Un QR non décodé est un lien mort qui ne se voit pas.
- Contrastes WCAG : les 7 paires calculées passent AAA. L'orange des surtitres a dû être assombri
  de `#c2691f` à `#a45614` - il était à 3,61:1 pour un seuil de 4,5:1.
- Typographie québécoise : zéro faute, contrôle éprouvé par un TÉMOIN volontairement fautif.
- Apostrophes typographiques dans tout le corps, aucune apostrophe droite.

**Données de l'exemple entièrement fictives** : numéros en 555, réservés à la fiction.

## Le gabarit, pour ne pas repartir de zéro seize fois

Chaque annonce suit la même charpente, qui vaut pour tous les outils :

1. **La situation concrète** où l'outil sert, en une phrase, du point de vue de qui a le problème.
2. **Le mécanisme précis**, avec l'exemple réel de la capture. Jamais « simple et efficace ».
3. **La limite ou la garantie décisive** - ici, le traitement local; ailleurs, autre chose.
4. **Où trouver le lien** : dernière diapositive sur LinkedIn, premier commentaire sur Facebook.

Et les trois interdits du skill de publication, qui tuent une annonce d'outil plus vite que tout :
aucun préambule interchangeable, aucun superlatif sans preuve, aucune question en conclusion.

---

## Cadence proposée

Un outil par semaine. Seize outils couvrent donc environ quatre mois, sans jamais produire une
seule page de plus, et en faisant découvrir ce qui existe déjà. C'est l'inverse exact du réflexe qui
a valu deux refus publicitaires au site.
