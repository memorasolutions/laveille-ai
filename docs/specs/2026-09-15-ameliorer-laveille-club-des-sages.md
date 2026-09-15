# Améliorer laveille.ai : ce que les mesures disent, et ce que le club des sages a tranché

> Rédigé le 15 septembre 2026. Club des sages complet, 5 oracles, 3 rounds, plus **neuf mesures**
> faites en cours de route qui ont détruit cinq de mes propres prémisses.
> Tickets #2581 (améliorer le site) et #2582 (outil de conversion).
>
> **Ce document n'engage aucune ligne de code. Il dit quoi construire, dans quel ordre, et à quelle
> condition on saura que c'était une erreur.**

---

## 1. La découverte qui domine tout le reste : le site n'a pas d'audience, il a du trafic

C'est le résultat le plus important, et il n'était visible dans AUCUN des chiffres du round 1.
Google Analytics compte des **sessions** ; j'ai raisonné sur des sessions ; les oracles ont raisonné
sur mes sessions. claude.ai a été le seul à demander : *« 114 sessions récurrentes ne font pas 114
personnes »*. Il avait raison.

| Page | Sessions | **Personnes réelles** | Sessions par personne |
|---|---|---|---|
| Liste des actualités | 99 | **6** | 16,5 |
| Constructeur de prompts | 182 | **79** (26 nouvelles) | 2,3 |
| Actualité « vérification » (Galika) | 148 | **114** (110 nouvelles) | 1,3 |
| Générateur de code QR | 63 | 32 | 2,0 |
| Glossaire | 23 | 8 | 2,9 |

Et la ventilation par ville confirme la pollution :

| Ville | Page | Sessions | Personnes |
|---|---|---|---|
| L'Ancienne-Lorette | Liste des actualités | 17 | **1** |
| Québec | Liste des actualités | 65 | **4** |
| Saint-Marc-des-Carrières | Constructeur de prompts | 27 | 17 |
| **Paris** | Actualité Galika | 30 | 24 |

**82 des 99 sessions de la liste d'actualités viennent de cinq personnes de la région de Québec.**
La page que j'avais présentée au round 1 comme « 99 sessions, 5 min 58 » est, à toutes fins utiles,
le trafic de l'exploitant lui-même.

**Conséquence, formulée par ChatGPT et par claude.ai indépendamment** : la seule vraie acquisition
d'audience du site, ce sont les **114 personnes réelles, dont 110 inconnues**, venues sur UNE fiche
de vérification. Le site sait acquérir des inconnus quand il répond exactement à une question posée.
Il ne sait pas encore les retenir.

**Premier geste, avant toute autre chose** : exclure le trafic interne dans Google Analytics.
Tant que ce n'est pas fait, chaque chiffre du tableau de bord est faux dans le sens flatteur.

---

## 2. Le convertisseur : NON, et les quatre oracles finissent unanimes

**Question de Stéphane** : *« est-ce qu'un outil de conversion ultra complet serait bien? »*, puis
*« et le convertisseur facile à utiliser, on remplit un champ, l'autre se calcule, et vice versa »*.

**Réponse : non.** Mais le chemin compte plus que le verdict, parce que Stéphane a marqué un point
réel en cours de route.

### Ce que Stéphane a corrigé, et il avait raison

J'avais décrit le site aux oracles comme « veille sur l'intelligence artificielle ». Les quatre
oracles ont bâti leur refus sur ce mot précis : *« aucun lien avec le sujet du site »*. Stéphane a
objecté que le site couvre aussi la techno. **Vérification en production** : la description servie
dit « intelligence artificielle, **la conformité Loi 25 et la transformation numérique** ». Ma
prémisse était donc trop étroite, et j'ai faussé le round 1.

Réinterrogés avec la bonne prémisse, **trois oracles sur quatre ont admis que leur argument du
round 1 était faux** : DeepSeek (« ma réponse du round 1 était fausse »), ChatGPT (« trop
catégorique et donc faux »), Gemini (« ma définition était trop étroite, mais la conclusion tient »).

### Et pourtant le verdict n'a pas bougé, pour une raison entièrement différente

Au round 2, deux oracles avaient sauvé une version restreinte. **Au round 3, les deux l'ont retirée
eux-mêmes :**

- **ChatGPT retire son propre convertisseur numérique** : *« les fonctions utiles sont des
  commodités immédiatement substituables par Google, les systèmes d'exploitation, les assistants IA
  et les sites spécialisés. Aucun mécanisme ne crée une raison de revenir spécifiquement à
  laveille.ai. »*
- **Gemini change de camp** : *« Claude et DeepSeek ont raison, le convertisseur est mort sous
  toutes ses formes. »*

**Le critère qui tranche, et il est réutilisable** (claude.ai, round 3) : la question n'est pas
« est-ce dans ma thématique ? » mais **« Google répond-il lui-même, au-dessus des liens ? »**. Pour
le stockage, les débits, les devises et les fuseaux, la réponse est oui. Il ne resterait que les
pixels par pouce et les ratios d'image, deux niches déjà occupées.

**La donnée qui a fait basculer Gemini** (Perplexity, source Ahrefs nommée) : les calculateurs
gratuits qui construisent une audience sont ceux **cohérents avec le domaine du site** - calculateur
de placements de Groww, 3,5 millions de visites ; hypothèque de Bankrate, 1,4 million ; tous des
sites de finance. Le contre-exemple est décisif : **PercentageCalculator.net capte 1,6 million de
visites organiques estimées sur un site de trois pages, et n'a produit aucune audience éditoriale.**

**La bidirectionnalité ne change rien** : c'est une qualité d'interface, pas un avantage. Google
l'offre déjà dans ses propres résultats.

**Ce qui reste vrai de l'intuition de Stéphane** : un outil utilitaire n'est PAS un cul-de-sac.
La mesure le prouve sur ce site même - l'actualité virale fait 1,09 page par session, le code QR en
fait 2,28. Un oracle avait parié « presque zéro » et a perdu son pari. Mais « faire circuler » n'est
pas « bâtir une audience », et aucune mesure ne montre le passage de l'outil vers la veille.

---

## 3. Ce qui a été tué, et par quel mécanisme

Dix-sept idées ont circulé sur trois rounds. **Aucune n'a survécu telle quelle au round 1.**

| Idée | Sort | Le mécanisme précis qui la tue |
|---|---|---|
| Publier plus de fiches d'actualité | **Tuée, 3 voix sur 4 au round 1** | Reproduit la cause exacte des deux refus AdSense. Et la mesure (g) l'enterre : ce travail quotidien est lu par six personnes. |
| Convertisseur universel, puis bidirectionnel | **Tuée 4/4 au round 3** | Google répond au-dessus des liens sur l'essentiel des conversions. |
| Convertisseur PDF vers texte pour LLM avec purge | Tuée | Un seul numéro d'assurance sociale manqué rend le bénéfice toxique pour un site qui parle de Loi 25. |
| Mur d'inscription pour copier le résultat | Tuée | Punit exactement les 53 personnes fidèles qui reviennent, alors que ChatGPT écrit un prompt sans demander de courriel. |
| Mini-quiz sur les 537 termes | Tuée | Surface énorme à produire et à maintenir, sans aucun signal de demande. |
| Décodeur de jargon relié au glossaire | Tuée | **Le moteur d'auto-liens avec infobulles existe déjà** sur tout le site. |
| Calculateur du coût d'un outil IA | Tuée par son auteur | Quinze grilles tarifaires tenues à jour par une personne seule deviennent fausses, donc du contenu à faible valeur. |
| Évaluateur de prompts, Diagnostic IA, Labo IA | Tuées | Exigent un appel d'API payant à chaque usage pour offrir ce que l'assistant fait déjà gratuitement. |
| Vérificateur Loi 25 | Tuée par son auteur au round 3 | Sans API, l'outil ne repère que des mots-clés. Or la Loi 25 juge des **pratiques**, pas la qualité d'un texte : une politique bien écrite par ChatGPT obtiendrait « conforme » alors que l'entreprise ne fait rien. |
| Calculateur de rentabilité d'une automatisation | Tuée par son auteur au round 3 | Précision fictive : le résultat est dominé par les variables que l'utilisateur connaît le moins bien. |
| Trousse pour enseignants | Tuée par son auteur | Le canal de diffusion n'existe pas : les conseillers RÉCIT ne relaient pas un site privé qui vise AdSense. |
| Liens de partage du constructeur | Sans objet | **Existe déjà en production** : route `/p/{identifiant}`, reprise des réglages, bouton « Partager ». |
| Détecteur de données sensibles avant de coller dans une IA | Sans objet | **L'anonymiseur existe déjà**, et sa description dit mot pour mot « Anonymise tes textes avant de les coller dans une IA. Tout se passe dans ton navigateur. Conforme à la Loi 25 ». |

---

## 4. La survivante, et son test d'échec écrit AVANT

**Générateur de politique interne d'utilisation de l'IA pour PME québécoises.** Deux oracles sur
quatre l'élisent (claude.ai, ChatGPT), et les deux autres ne l'ont pas attaquée au round 3.

Pourquoi elle tient quand tout le reste tombe :

- Elle touche les **trois** volets du périmètre réel : IA, Loi 25, transformation numérique.
- Elle se construit **avec des gabarits, sans appel d'API**, donc sans coût récurrent.
- Elle répond à une recherche réelle (« modèle de politique IA en entreprise »), et le site vient de
  prouver qu'il **sait capter des inconnus quand il répond à une question précise**.
- Elle ne prétend pas établir une conformité juridique, contrairement au vérificateur Loi 25 qui
  s'est effondré sur ce point.

**Sa faiblesse, nommée par claude.ai** : c'est un usage **unique**. Une PME rédige sa politique une
fois et ne revient pas. Mais le même oracle retourne l'argument au round 3 : *sans audience à
fidéliser, un usage unique qui capte une recherche vaut mieux qu'une visite récurrente d'un public
qui n'existe pas.*

### Le test chiffré, écrit avant de commencer

Les deux oracles qui l'ont élue ont proposé des seuils voisins. Retenu, le plus strict des deux :

> **Au 90e jour après la mise en ligne, c'est une erreur si la page cumule moins de 300 impressions
> dans Search Console, OU si moins de 25 politiques ont été générées par des visiteurs venus de la
> recherche organique** (événement Analytics, trafic interne exclu).
>
> Dans ce cas : on n'y ajoute plus rien. Budget de construction plafonné à 20 heures.

---

## 5. Les divergences, conservées telles quelles

**Assainir l'index (noindex ou fusion des 4300 fiches).** claude.ai et Gemini pour ; DeepSeek
contre, au motif que la fiche virale tire 118 sessions de la recherche organique, donc que
l'actualité ciblée capte bien l'algorithme. **Arbitrage** : claude.ai l'amende lui-même et donne le
bon critère - *un noindex décidé sur 28 jours de sessions supprimerait des pages qui gagnent des
positions (de 47 à 18 en un mois). Le critère juste est le nombre d'impressions Search Console sur
16 mois, pas les sessions.* Chantier conservé, mais **pas avec la mesure que j'avais proposée**.

**L'outil utilitaire comme porte d'entrée.** DeepSeek s'est dédit ; Gemini a maintenu (« illusion de
propriétaire », « statistiques de vanité ») puis a changé de camp au round 3 ; ChatGPT a maintenu la
nuance la plus juste : *la mesure prouve la circulation, pas le passage vers la veille.* Position
retenue : **non tranché par la donnée disponible**, et 18 sessions ne tranchent rien. Le bon juge
serait l'inscription à l'infolettre ou le retour dans les 28 jours.

---

## 6. Une idée neuve écartée, et pourquoi il faut le dire

claude.ai a proposé d'offrir le générateur en primeur aux **« environ 148 PME québécoises abonnées »**
de MEMORA. **Ce chiffre n'existe pas dans mes prompts** : je lui avais donné « 148 sessions » pour
la fiche Galika. Il a transformé une mesure d'audience en clientèle d'entreprise.

Troublant : un nombre voisin décrit bien le parc de sites hébergés par MEMORA. Mais la règle du
projet est explicite - ce chiffre décrit un **parc d'hébergement**, jamais la clientèle d'un produit,
et il ne doit jamais servir à raisonner sur l'adoption d'autre chose. S'y ajoute que **solliciter ces
organisations sans consentement tomberait sous la Loi 25 et la loi anti-pourriel**.

L'idée sous-jacente - solliciter des clients existants plutôt que d'attendre la recherche organique -
reste valable, mais elle appartient au développement des affaires de Stéphane, pas à un plan
technique, et elle exige un consentement préalable.

---

## 7. Honnêteté sur la méthode

- **5 oracles sur 5 consultés** : DeepSeek (Hermes), Gemini (Antigravity), ChatGPT (navigateur),
  claude.ai (navigateur), Perplexity (recherche). Aucun n'a manqué.
- **3 rounds complets**, critère d'arrêt fixé avant de commencer (deux rounds consécutifs sans idée
  neuve passant le filtre). Arrêt au round 3 sur convergence unanime.
- **Chaque oracle a tué au moins une de ses propres idées.** ChatGPT en a tué deux, claude.ai deux,
  Gemini une, DeepSeek une.
- **Neuf mesures ont été faites en cours de route, et cinq ont détruit mes propres prémisses** :
  le 9 min 31 (durée de session, pas d'engagement), les 114 « récurrents » (53 personnes), les 99
  sessions de la liste d'actualités (6 personnes), le pari sur le code QR (perdu par son auteur), et
  mon interprétation trop généreuse des 2,28 pages par session.
- **Deux idées proposées existaient déjà dans le code** (partage du constructeur, anonymiseur). Sur
  l'ensemble du chantier de cette semaine, c'est la neuvième fois.
- **Un oracle a fabriqué un chiffre** (« 148 PME abonnées »), consigné en section 6 plutôt que repris.
