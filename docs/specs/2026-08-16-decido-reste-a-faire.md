# Decido - ce qui reste, en quatre lots

**Date** : 16 août 2026 (America/Toronto)
**Contexte** : les outils de todo du harness sont hors service ; ce fichier tient lieu de liste de
référence, versionné dans le dépôt.

**Fait qui relativise tout** : la production compte 2 sondages et **zéro vote**. La plupart de ces
manques répondent à des usages qui n'ont pas commencé. On les livre parce qu'ils doivent exister au
premier usage réel, pas parce qu'ils font mal aujourd'hui.

---

## Déjà livré et vérifié en production (16 août)

- v1.174.1 - chevron du champ fuseau horaire, 5 boutons d'aide migrés vers le composant partagé
- v1.176.0 - popup d'infolettre retiré de cette route, titre affiché une seule fois, créneaux groupés
  par journée, totaux de réponses par créneau
- v1.177.0 - bannière rendue à sa hauteur standard, contenu enveloppé dans une carte, classe
  `.ct-badge-status` créée et migrée aux deux endroits
- v1.177.1 - microcopie en trois états, alignement du titre, empilement propre des pastilles

---

## LOT 1 - refermer le cycle (priorité absolue)

Diagnostic du panel : *« le manque n'est pas une fonctionnalité, c'est une boucle procédurale
incomplète »*. Le cycle organiser - voter - décider - informer s'arrête après « voter ».

1. **Afficher le résultat final sur la page publique.** Une fois le créneau choisi, la page reste
   « essentiellement un formulaire fermé » (Codex). Le participant qui revient ne trouve pas la
   décision.
2. **Une échéance de réponse**, facultative, affichée avant et après le vote. Sans elle,
   l'organisateur attend indéfiniment et le votant ne sait pas quand répondre.
   Défaut connu de cette idée (DeepSeek) : une échéance trop courte exclut les retardataires et crée
   de la gestion d'exceptions à la main. Donc facultative, et jamais bloquante par défaut.
3. **Une option « aucune date ne me convient ».** Aujourd'hui, l'organisateur ne peut pas distinguer
   un refus d'un silence. Il relance quelqu'un qui a déjà tranché.

## LOT 2 - défauts fonctionnels

4. **« Effacer ma réponse » ne fonctionne pas.** Le contrôleur met à jour les votes reçus mais ne
   supprime pas ceux qui sont omis. Correction serveur explicite nécessaire.
5. **Aucun commentaire possible.** La table des votes n'a aucun champ pour ça. Doodle le permet
   (« je peux seulement après 18 h », « je participe à distance »).

## LOT 3 - côté organisateur

6. **Page « mes sondages ».** L'organisateur a DÉJÀ un compte obligatoire (contrainte non nulle en
   base) - il ne s'agit que d'afficher une donnée existante.
7. **Suivi des non-répondants SANS carnet d'adresses** (Codex) : l'organisateur déclare un nombre
   attendu, l'outil affiche « 7 sur 10 » et fournit un message de rappel prêt à copier. Aucune
   collecte, aucun envoi automatique, aucune obligation légale nouvelle.

## LOT 4 - dette technique

8. Sortir les ~194 lignes de CSS de la vue vers une feuille dédiée (contient des requêtes de
   conteneur et des sélecteurs `:has()` - à déplacer avec prudence).
9. Le script du popup d'infolettre reste chargé sur cette page alors que son marqueur est absent ; il
   se neutralise seul, mais c'est du poids inutile.
10. À 320 pixels, les boutons de vote passent sur deux lignes. Préexistant, hors des lots précédents.

---

## Décisions arrêtées, à ne pas rouvrir

- **Pas de courriel demandé aux votants.** Motif principal : le risque de dégradation de la
  réputation d'envoi du domaine, qui sert aussi l'infolettre. Motif secondaire : sept obligations
  légales déclenchées dès la première adresse, sans aucun seuil de taille (vérifié auprès des
  sources officielles).
- **Pas de comptes pour les votants.** Tue la friction zéro qui fait la valeur de l'outil.
- **Les totaux restent visibles** aux participants - livré.
- **Pas de geste de sélection inventé** (pinceau, glissement, langage naturel) : les trois ont été
  tués par leurs propres auteurs, et les concurrents observés utilisent tous une simple liste.

---

## LOT 5 - notification à l'organisateur (ajouté le 2026-08-16)

**Constat vérifié dans le code** : Decido n'envoie qu'UN seul type de courriel, un avertissement au
créateur quand son sondage approche de son expiration. **Quand quelqu'un vote, personne n'est
prévenu.** L'organisateur doit retourner consulter sa page de résultats pour découvrir s'il y a du
nouveau.

**Ce cas est complètement différent du débat sur le courriel des votants** : l'organisateur a DÉJÀ un
compte avec une adresse vérifiée. Aucune nouvelle collecte, aucune obligation légale nouvelle, aucun
risque sur la réputation d'envoi - c'est un message transactionnel vers son propre compte.

C'est aussi une meilleure réponse au problème « je ne sais pas qui manque » que la progression
passive « 7 sur 10 » livrée au lot 3 : au lieu d'aller vérifier, on est prévenu.

**LE PIÈGE À TRAITER** : dix participants qui votent une fois donnent dix courriels ; quelqu'un qui
modifie sa réponse trois fois en donne treize. Il faut REGROUPER - un résumé quotidien, ou une
notification au premier vote puis silence, ou un seuil déclaré par l'organisateur. Sans ce
regroupement, une bonne idée devient une nuisance et finit désactivée.

Le mécanisme d'envoi existe déjà (`PollExpiringSoonMail`, `WarnExpiringPollsCommand`) : le RÉUTILISER,
ne pas en écrire un second.

---

## LOT 6 - faire de Decido une référence (panel en cours)

Demande du propriétaire le 2026-08-16 : consulter le panel en boucle pour que Decido devienne une
référence, pas seulement un outil fonctionnel.

**Le fait qui doit cadrer cette réflexion** : la production compte 2 sondages et ZÉRO vote. La
question n'est donc pas « quelles fonctions ajouter » - il en a déjà l'essentiel et il vient d'être
poli par cinq déploiements. La question est : **pourquoi quelqu'un choisirait Decido plutôt que
Doodle, Framadate, Rallly ou When2meet ?**

Résultats du panel à consigner ici une fois les rounds terminés.
