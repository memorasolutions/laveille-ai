# Générateur de politique d'utilisation de l'IA pour PME québécoises : conception

> Ticket #2584. Rédigé le 15 septembre 2026.
> **Seule survivante des trois rounds du club des sages** (#2581), élue par claude.ai et ChatGPT,
> non attaquée par Gemini ni DeepSeek au round 3.

---

## 1. Pourquoi celle-là, et pas les seize autres idées

Elle est la seule à réunir les quatre conditions que les trois rounds ont fait émerger :

| Condition | Pourquoi elle la remplit |
|---|---|
| **Dans le périmètre réel** | Elle touche les trois volets servis par le site : IA, conformité Loi 25, transformation numérique. |
| **Sans coût récurrent** | Gabarits et assemblage, **aucun appel d'API**. Toutes les idées qui en exigeaient un ont été tuées : sans budget, offrir ce que l'assistant fait déjà gratuitement est intenable. |
| **Répond à une recherche réelle** | « modèle de politique IA en entreprise » est une requête de tâche, pas de curiosité. Or le site vient de prouver qu'il sait capter des inconnus quand il répond à une question précise : **110 nouvelles personnes** sur une seule fiche de vérification. |
| **Ne prétend rien établir** | Contrairement au « vérificateur Loi 25 », tué au round 3 par son propre auteur : sans API, un tel outil ne repère que des mots-clés, alors que la Loi 25 juge des **pratiques**. Une politique bien écrite obtiendrait « conforme » d'une entreprise qui ne fait rien. |

**Sa faiblesse, nommée par claude.ai et assumée** : c'est un usage **unique**. Une PME rédige sa
politique une fois et ne revient pas. Le même oracle retourne l'argument au round 3, et c'est
l'arbitrage retenu : *sans audience à fidéliser, un usage unique qui capte une recherche vaut mieux
qu'une visite récurrente d'un public qui n'existe pas.*

---

## 2. Architecture : le motif de l'anonymiseur, qui est déjà prouvé ici

**Tout se passe dans le navigateur.** Aucune réponse du formulaire n'est envoyée au serveur, aucune
n'est stockée.

Ce n'est pas un choix technique, c'est le coeur de l'offre. Une PME qui remplit un formulaire
nommant ses outils, ses fournisseurs et son responsable des renseignements personnels décrit sa
surface d'attaque. Lui demander d'envoyer ça sur un serveur pour obtenir un document sur la
protection des données serait une contradiction que n'importe quel lecteur attentif relèverait.

**Et le motif est déjà en service sur le site** : l'anonymiseur fonctionne exactement ainsi, et la
vérification à l'inspecteur réseau du 2026-09-15 confirme **zéro requête sortante** pendant le
traitement. On réutilise une mécanique éprouvée plutôt que d'en inventer une.

### Où ça vit

`Modules/Tools`, comme les seize autres outils publics. Route `/outils/politique-ia`. Aucun
nouveau module : la frontière du projet veut qu'un outil public vive avec ses pairs.

### Ce qui est produit

1. Le document assemblé, affiché dans la page, avec un bouton pour le copier.
2. Un téléchargement en texte et en Markdown, générés côté navigateur.
3. **Aucune donnée persistée**, donc aucune obligation de rétention, aucun registre à tenir, aucune
   demande d'accès à honorer. Le coût de conformité du générateur lui-même est nul, ce qui compte
   pour une personne seule.

---

## 3. Le formulaire : court, sinon personne ne le finit

Dix questions au maximum, chacune avec une valeur par défaut raisonnable, pour qu'un document
utilisable sorte même si la personne ne répond qu'à la première.

1. Nom de l'entreprise.
2. Secteur (liste courte : services professionnels, commerce, santé, construction, éducation,
   organisme sans but lucratif, autre).
3. Nombre de personnes (moins de 5, 5 à 20, 21 à 100, plus de 100).
4. Qui approuve un nouvel outil (une personne nommée, un comité, la direction).
5. Qui répond des renseignements personnels.
6. Outils déjà utilisés (cases à cocher, liste ouverte).
7. Les agents et l'automatisation sont-ils permis.
8. Niveau de tolérance pour les comptes personnels au travail (interdit, toléré sans données
   d'affaires, permis avec approbation).
9. Fréquence de révision de la politique.
10. Courriel de signalement d'un incident.

---

## 4. La page, et pas seulement l'outil

C'est la leçon commune de ChatGPT et claude.ai au round 1, et elle vaut pour tous les outils du
site : **un outil nu ne se référence pas**. La page porte donc, autour du générateur :

- ce qu'est une politique d'utilisation de l'IA et pourquoi une PME en a besoin ;
- ce que la Loi 25 exige réellement, et surtout **ce qu'elle n'exige pas** - elle ne crée aucune loi
  générale sur l'IA, ses obligations s'appliquent dès qu'un outil traite des renseignements
  personnels ;
- les erreurs fréquentes, formulées comme des cas concrets ;
- une foire aux questions pensée pour être citée, sans promesse de conformité.

---

## 5. Les trois interdits, qui sont la raison d'être de ce document

1. **Ne jamais écrire, ni laisser entendre, que l'entreprise devient « conforme ».** Le document est
   un point de départ, jamais une attestation.
2. **Ne jamais inventer un numéro d'article de loi.** Une référence fabriquée dans un document
   juridique est pire que pas de référence du tout. Si le numéro exact n'est pas vérifié, la règle
   s'écrit sans le citer.
3. **Ne jamais réclamer de renseignements personnels** dans le formulaire. Le nom d'un responsable
   est une donnée d'entreprise et il ne quitte pas le navigateur, mais rien de plus n'est demandé.

---

## 6. Le test d'échec, écrit AVANT la construction

Retenu des deux seuils proposés au round 3, le plus strict des deux :

> **Au 90e jour après la mise en ligne, c'est une erreur si la page cumule moins de 300 impressions
> dans Search Console, OU si moins de 25 politiques ont été générées par des visiteurs venus de la
> recherche organique.**
>
> Dans ce cas, on n'y ajoute plus rien. Budget de construction plafonné à **20 heures**.

**Comment compter les générations sans stocker de données** : un événement d'analyse déclenché au
clic sur « Générer », sans aucun contenu du formulaire. On compte des gestes, jamais des réponses.

**Prérequis de mesure, non négociable** : le filtre de trafic interne doit être ACTIF (#2583),
sinon le seuil de 25 est ininterprétable - on ne saura pas distinguer 25 PME de 25 rafraîchissements
de l'exploitant.

---

## 7. Ce qui reste à faire, dans l'ordre

1. Le gabarit de politique lui-même, dix sections rédigées, relu par Stéphane avant publication.
2. La page et le formulaire, sur le motif de l'anonymiseur.
3. L'assemblage côté navigateur et les deux formats de sortie.
4. Le contenu éditorial autour de l'outil.
5. Les tests, dont un qui vérifie qu'**aucune requête réseau** ne part pendant l'assemblage - la
   promesse centrale doit être tenue par un test, pas par une intention.
