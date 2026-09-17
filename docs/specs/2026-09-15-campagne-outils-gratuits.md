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
| — | Sudoku, mots croisés, minuteur | 21 / 16 / 28 | 11 / 10 / **4** | **0** | **Hors LinkedIn.** Le club des sages a noté qu'ils brouillent la thématique. Le minuteur fait 28 sessions pour 4 personnes : c'est un usage de classe, pas un produit. |

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

### Texte LinkedIn (à valider avant envoi)

> L'anonymiseur de laveille.ai ne colle pas d'étiquettes [NOM] dans ton texte. Il remplace tes
> vraies données par des fausses, mais cohérentes.
>
> Marie Tremblay devient Nathalie Gagnon. Le numéro reste un numéro de téléphone, l'adresse reste
> une adresse de Québec, le courriel reste un courriel. L'IA continue donc de comprendre ce qu'elle
> lit, et sa réponse reste utilisable, ce qui n'arrive pas quand on remplace tout par des crochets.
>
> Ensuite tu recolles la réponse de l'IA dans l'outil, et il y remet tes vraies informations.
>
> Tout se passe dans ton navigateur. Rien n'est envoyé sur nos serveurs.
>
> Le lien et le code QR sont sur la dernière diapositive.

### Texte Facebook (à valider avant envoi)

> Tu veux faire résumer un dossier par une IA, sans lui donner le nom de ton client.
>
> L'anonymiseur de laveille.ai remplace les vraies données par des fausses cohérentes : Marie
> Tremblay devient Nathalie Gagnon, l'adresse devient une autre adresse de Québec, le numéro reste
> un numéro. L'IA comprend encore le texte, donc sa réponse reste utile.
>
> Ensuite, tu recolles sa réponse et l'outil y remet tes vraies infos.
>
> Tout se passe dans ton navigateur. Le lien est en premier commentaire.

### Le visuel

**PRÊTE** : `docs/campagne-outils-gratuits/01-anonymiseur-comparaison.png` (1440 px, 65 Ko).

Capture faite en production le 2026-09-17, comparaison côte à côte « Votre texte » / « Texte
anonymisé », les valeurs substituées surlignées des deux côtés. Elle montre le mécanisme en une
seconde, sans légende.

**La promesse du texte est re-prouvée le jour même** : pendant l'anonymisation, l'interception
réseau n'a enregistré **aucune requête**. On peut écrire « tout se passe dans ton navigateur » sans
mentir. La page l'affiche d'ailleurs elle-même : « 100 % local - traitement dans votre navigateur ».

**Données de l'exemple entièrement fictives** : le numéro est en 555, réservé à la fiction, et le
numéro de dossier est 000-000-000. Aucune donnée réelle n'a servi à la démonstration.

---

## Le gabarit, pour ne pas repartir de zéro seize fois

Chaque annonce suit la même charpente, qui vaut pour tous les outils :

1. **La situation concrète** où l'outil sert, en une phrase, du point de vue de qui a le problème.
2. **Le mécanisme précis**, avec l'exemple réel de la capture. Jamais « simple et efficace ».
3. **La limite ou la garantie décisive** - ici, le traitement local ; ailleurs, autre chose.
4. **Où trouver le lien** : dernière diapositive sur LinkedIn, premier commentaire sur Facebook.

Et les trois interdits du skill de publication, qui tuent une annonce d'outil plus vite que tout :
aucun préambule interchangeable, aucun superlatif sans preuve, aucune question en conclusion.

---

## Cadence proposée

Un outil par semaine. Seize outils couvrent donc environ quatre mois, sans jamais produire une
seule page de plus, et en faisant découvrir ce qui existe déjà. C'est l'inverse exact du réflexe qui
a valu deux refus publicitaires au site.
