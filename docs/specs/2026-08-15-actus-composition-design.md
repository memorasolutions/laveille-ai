# Actus 2.0 - composition manuelle assistée

**Date** : 15 août 2026 (America/Toronto)
**Statut** : PROPOSITION - aucune ligne de code avant approbation explicite
**Protocole** : club des sages, deux rounds complets. Round 1 à 4 oracles sur 5, round 2 à 3 sur 5
(claude.ai indisponible malgré resynchronisation des cookies et redémarrage de session - signalé, pas
masqué). Un fait décisif a été vérifié indépendamment auprès des sources officielles.

---

## 1. Le problème, en une phrase

La publication automatique des actualités est arrêtée et prouvée arrêtée en production ; il faut
maintenant l'outil qui permet à une personne seule de composer à la main deux ou trois fiches par
semaine, plus riches, sans reproduire le défaut mesuré de l'ancienne chaîne.

## 2. Ce qui est déjà fait - à NE PAS refaire

Vérifié par lecture du code le 15 août, jamais supposé :

- **Le composant de sélection d'actualités existe** et est réutilisable : recherche, filtres, tri,
  regroupement, sélection multiple. Déjà utilisé par deux écrans d'administration. Il ne rend PAS la
  colonne des éléments retenus, ni le glisser-déposer, ni le brouillon local : ce sont des greffons
  que la page hôte fournit. **On le réutilise, on n'en écrit pas un second.**
- **La collecte tourne toujours** chaque heure : évaluation, résumé, porte de qualité, fusion,
  déduplication. Seule l'écriture de la publication est court-circuitée.
- **Une bascule manuelle de publication existe déjà** dans la liste d'administration des articles.
- **Le bilan de chaque collecte est journalisé** sur un canal visible en production (livré le
  14 août) : c'est lui qui dira combien de propositions attendent.

## 3. Ce qui existe mais ne convient pas en l'état

- **L'écran du Concentré ne crée aucun article.** Il produit un prompt à copier ailleurs, et son
  téléversement d'image dépose le fichier **brut** : aucun traitement.
- **Trois pipelines d'images coexistent sans être mutualisés.** Le meilleur est celui des auteurs :
  5 largeurs, 3 formats, image sociale JPEG 1200x630, carte 1200x600.
- **La génération d'image par IA n'est PAS programmable.** Pilotage manuel de navigateur sur le
  compte Gemini. Toute promesse d'automatisation dans l'écran serait mensongère.

---

## 4. LE FAIT VÉRIFIÉ QUI CHANGE UNE PRÉMISSE

Soulevé par Gemini, **vérifié indépendamment auprès des documentations officielles 2026** parce
qu'un chiffre faux aurait faussé tout l'arbitrage :

> **Le refus d'entraînement et la rétention pour surveillance des abus sont deux contrôles
> DISTINCTS.** Chez OpenAI comme chez Anthropic, refuser l'entraînement n'efface pas les données :
> elles sont conservées trente jours au titre de la sécurité opérationnelle. Une rétention zéro
> exige un accord entreprise approuvé, ni automatique ni sans conditions.

**Conséquence** : le refus de collecte activé le 7 août ne fait PAS disparaître le texte source de
chez le fournisseur. Il empêche l'entraînement, pas la conservation temporaire. Nous tenions le
contraire pour acquis - c'était faux.

Ce que cela ne change pas : la décision d'arrêter de publier le texte intégral côté public restait
juste, et c'était le risque principal.
Ce que cela change : on ne peut plus écrire que le texte « ne sort pas ». Il sort, et il reste
trente jours. La minimisation avant envoi devient la seule protection réelle.

---

## 5. Décisions arrêtées

### 5.1 Deux sources sont permises, et le liant reste permis

La règle « une fiche, une source » **tombe**, et son auteur l'a lui-même retirée au round 2 :
« elle confond limitation du dommage et exactitude ; le défaut était le liant, non la pluralité des
sources ».

Ses huit contraintes de remplacement tombent aussi, retirées par leur auteur : « un langage de
génération bureaucratique, coûteux à appliquer et encore insuffisant - un identifiant exact peut
soutenir une paraphrase fausse ».

**Ce qui les remplace, une seule règle dure** (Codex, round 2) :

> **Aucune causalité, comparaison ou généralisation produite par le rédacteur ne peut être présentée
> comme provenant des sources.**

Le raisonnement qui la fonde, et qui corrige une erreur de tout le panel précédent : *« le liant est
précisément le lieu du travail éditorial. Il doit être assumé comme analyse, non maquillé en fait
sourcé. »* On ne cherche plus à supprimer le liant - c'est ce que le lecteur vient chercher. On
interdit de le faire porter par les sources.

En pratique : « à mon sens, ces deux annonces vont dans le même sens » est permis. « Les deux sources
confirment que » ne l'est pas.

Chaque bloc conserve **son propre bouton vers sa source**.

### 5.2 Conservation du texte source

**Point de départ, décision du propriétaire** : garder le texte en base, jamais exposé côté public,
suppressible à tout moment.

**Correction apportée par Codex, retenue** : *« Supprimable n'est pas une politique de
conservation. »* Sans échéance, journal de suppression et effacement en cascade, le texte survivra
dans les sauvegardes, les journaux d'activité et les exports.

**Ce qu'on conserve durablement** : l'adresse de la source, la date de capture, une empreinte du
contenu, **et les extraits effectivement cités**. Ces extraits sont courts et couverts par le droit
de citation.

**Ce qui devient supprimable sans perte** : le texte intégral. Puisque les extraits invoqués sont
conservés, la preuve subsiste après suppression.

**Écarté** : l'empreinte SEULE, à la place du texte. Gemini l'a qualifiée d'illusion au round 1
(« un hash ne prouve rien sans le texte pour le recalculer ») puis proposée comme idée neuve au
round 2 - contradiction consignée. Elle ne survit qu'en complément des extraits, jamais seule.

### 5.3 L'écran est un assistant de composition, jamais un générateur

- **Aucun bouton « générer l'image ».** Libellé exact : **« copier le prompt et ouvrir Gemini »**.
- **Aucun indicateur de progression fictif** : l'application ne sait rien de l'autre onglet.
- **Le flux ne bloque jamais sur l'image.** La fiche s'enregistre sans illustration.
- **Validation automatique du fichier rapporté** : dimensions, poids, format, orientation, présence
  du JPEG social - vérifiés par la machine avant publication.
- **Le brouillon conserve le texte source** (décision 5.2), ce qui règle le piège trouvé par Codex :
  un brouillon ne pouvait pas reprendre une composition dont la matière n'existait qu'en mémoire.

### 5.4 Le standard d'images

**Fait vérifié (Perplexity)** : Facebook accepte WebP pour l'image de partage, X aussi, **LinkedIn
pas de façon fiable** ; AVIF n'est fiable sur aucun des trois. Le JPEG social est le seul
dénominateur commun sûr.

- **Image sociale : JPEG 1200x630, obligatoire, toujours.** Antécédent : 107 images du glossaire
  rattrapées faute d'équivalent JPEG.
- **Budget mesuré** : 200 à 600 Ko d'images au chargement initial, moins d'un mégaoctet au total
  (Web Almanac 2025).
- **Le texte alternatif décrit l'image ; il ne contient pas de mots-clés.** Correction de Codex :
  « référencement maximal » pousse au bourrage, ce qui dégrade l'accessibilité sans bénéfice.
- **À tester avant de figer** : les outils d'inspection de partage des trois réseaux. Perplexity
  déclare non vérifiées les dates exactes et la limite de poids de LinkedIn.

**Divergence consignée sur la mutualisation.** DeepSeek et Codex la réclamaient au round 1 ; Codex
l'a retirée au round 2 : « avec trois pipelines et une automatisation impossible, centraliser
maintenant concentre les pannes sans supprimer le travail manuel ». **Arbitrage retenu : reportée.**
On applique le standard à l'écran des actualités sans toucher aux trois pipelines existants. La
mutualisation redeviendra pertinente si la génération devient un jour programmable.

---

## 6. Le standard est surdimensionné - ce qu'on garde et ce qu'on jette

Les trois oracles du round 2 convergent : des contraintes conçues pour brider une machine coûtent
plus qu'elles ne rapportent quand un humain écrit trois fiches par semaine. Mais - et c'est ce qui
justifie de garder quelque chose - *« la baisse de volume réduit la probabilité totale, pas la
responsabilité par fiche »*.

**GARDÉ** : publication manuelle ; provenance visible pour chaque affirmation contestable ; relecture
de la phrase contre sa source ; distinction explicite entre fait et analyse ; aperçu avant
publication ; JPEG social testé ; budget d'images ; texte alternatif descriptif.

**JETÉ** : la source unique ; l'interdiction générale du liant ; la duplication des affirmations
multisources ; le modèle de validation bloquant ; la file d'images complexe tant que le volume ne la
justifie pas ; le service d'images central avant automatisation réelle.

---

## 7. L'idée retenue du round 2

**La fiche de preuve éditoriale interne** (Codex). Chaque passage risqué affiche simultanément la
phrase publiée, l'extrait source, l'adresse, et une décision binaire **fait / analyse**. La
validation humaine n'est obligatoire que pour ces passages.

*« Elle bat les contraintes globales en concentrant l'effort exactement où vivait l'erreur. »*

C'est la seule mécanique du panel qui applique la règle 5.1 sans imposer de carcan à tout le texte.

---

## 8. Phases proposées

Aucune phase ne démarre avant approbation. Chacune se termine par une preuve, jamais par une
affirmation.

**Phase A - l'écran de composition.** Réutilisation du composant de sélection existant, colonne des
articles retenus, champ de texte source par article, construction du prompt, dépôt manuel de l'image,
validation automatique du fichier, prévisualisation, publication. Preuve : parcours complet en
navigateur visible, de la sélection à la fiche publiée.

**Phase B - la fiche de preuve éditoriale.** Marquage des passages risqués, affichage côte à côte
phrase / extrait / adresse, décision fait ou analyse. Preuve : jeu de tests avec des cas qui doivent
échouer - une généralisation présentée comme sourcée, une causalité absente de la source.

**Phase C - la conservation et sa politique.** Extraits invoqués, empreinte, date de capture,
suppression du texte intégral avec journal et effacement en cascade. Preuve : suppression réelle
suivie d'une vérification que rien ne subsiste dans les journaux ni les exports.

**Phase D - le standard d'images appliqué à cet écran**, sans toucher aux trois pipelines existants.
Preuve : test réel des aperçus de partage sur les trois réseaux.

**L'ordre A puis B est imposé** ; C peut avancer en parallèle ; D vient en dernier.

---

## 9. Ce qui reste à trancher, par qui

**Par une mesure, jamais par un oracle** : les dimensions et formats réellement acceptés par les
trois réseaux ; le taux de faux positifs de la détection de généralisations en français.

**Par le propriétaire seul** : combien de fiches par semaine ; le sort de la fonctionnalité de fiches
comparatives déjà en production ; le sort des 274 fiches indexées à risque, toujours en attente ; et
surtout - au vu du fait vérifié en section 4 - **quel fournisseur de modèle utiliser pour les
résumés, sachant que le texte y reste trente jours quoi qu'on fasse**.

**Par un juriste** : la valeur probante d'une empreinte et d'extraits si la source disparaît ou
change.

---

## 10. Explicitement écarté

- **La règle « une fiche, une source »** : retirée par son propre auteur.
- **Les huit contraintes de fragmentation** : retirées par leur propre auteur.
- **L'interdiction du liant** : le liant est le travail éditorial, pas le défaut.
- **L'empreinte seule** à la place du texte : illusion de rigueur.
- **Un modèle de validation bloquant** : « il déplace l'erreur vers un classificateur opaque et crée
  une fausse assurance ».
- **Un bouton de génération d'image** : techniquement impossible, donc mensonger.
- **La mutualisation immédiate des pipelines d'images** : reportée, pas abandonnée.
- **Le bourrage de mots-clés dans le texte alternatif.**
