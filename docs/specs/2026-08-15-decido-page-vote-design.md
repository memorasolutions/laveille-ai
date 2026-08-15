# Decido - page de vote : ce qu'on change et pourquoi

**Date** : 15 août 2026 (America/Toronto)
**Statut** : ARRÊTÉ par le panel, prêt à implémenter
**Panel** : deux rounds. Round 1 à trois oracles (Gemini, DeepSeek, Codex), round 2 à deux
(DeepSeek n'a pas participé, claude.ai indisponible - session Playwright figée au démarrage du
serveur, voir la mémoire dédiée). **Convergence à deux oracles sur cinq, pas cinq** - signalé plutôt
que masqué.

---

## 1. Le problème, mesuré sur un sondage réel en production

Relevé le 15 août sur `/decido/LDitANr2dPmJ`, jamais supposé :

- **14 créneaux**, répartis sur **6 journées très inégalement** : mardi à vendredi n'ont **qu'un seul
  créneau chacun** ; samedi et dimanche en ont **cinq chacun**.
- **Sur téléphone : ZÉRO créneau visible au premier écran.** L'espace est occupé par un bandeau
  promotionnel, le menu, une grande bannière de titre, un fil d'Ariane, le titre répété une seconde
  fois, et **un popup d'infolettre qui recouvre le formulaire**.
- **Trois gestes de défilement** du premier créneau au bouton d'envoi.
- La date est réécrite en toutes lettres à chaque ligne : « samedi 22 août, 14 h - 15 h » puis
  « samedi 22 août, 15 h - 16 h », cinq fois de suite.
- **Aucun séparateur visuel entre les journées.**
- **Aucun tableau des réponses déjà reçues.**

Le retour visuel de sélection, lui, **existe déjà** et fonctionne (bordure, fond, gras). Ce n'était
pas le problème - hypothèse fausse corrigée avant de concevoir.

---

## 2. Ce que le panel a TUÉ, chaque oracle ayant éliminé sa propre idée

- **Le pinceau** (glisser le doigt pour appliquer un état) : « une usine à gaz absurde pour 14
  malheureuses cases » (Gemini, sur sa propre idée). Et l'appui maintenu de 300 ms nécessaire pour
  ne pas entrer en conflit avec le défilement est « peu découvrable » (Codex).
- **Le champ en langage naturel** (« tous les soirs sauf mardi ») : « obliger l'utilisateur à ouvrir
  son clavier virtuel pour cibler 14 créneaux est infiniment plus lent qu'une saisie manuelle »
  (Gemini, sur sa propre idée).
- **Les boutons d'action par journée** : « du chrome inutile sur 4 jours sur 6, puisqu'ils
  reproduisent l'action d'un seul créneau » (Codex, sur sa propre idée).

**Ce que ces trois morts enseignent** : les trois avaient été conçues pour « 20 à 60 créneaux ». La
réalité en compte 14. Une mécanique de saisie de masse n'a pas d'objet ici ; **le problème est
l'encombrement, pas la vitesse de saisie**.

---

## 3. Ce qu'on fait, par ordre d'impact

### 3.1 Dégager le premier écran (priorité absolue)

« Zéro créneau visible et un popup qui recouvre le formulaire constituent un échec d'accès,
**antérieur à tout problème de sélection** » (Codex). Gemini : « la mécanique de sélection en dessous
n'a aucune importance » si le formulaire est invisible.

- **Retirer le popup d'infolettre sur cette page** (uniquement sur les pages de vote Decido - c'est
  une page où quelqu'un accomplit une tâche pour rendre service, pas une page de découverte).
- **Réduire la bannière de titre** et **supprimer le titre répété** une seconde fois.
- Objectif mesurable : **au moins un créneau complet visible au premier écran en 375 pixels de
  large**, sans défiler.

### 3.2 Regrouper visuellement par journée

Un **en-tête de date fort** (« Samedi 22 août »), puis en dessous **les heures seules** :
« 14 h - 15 h », « 15 h - 16 h ». La date disparaît de chaque ligne.

Gain annoncé par Gemini : **hauteur de page divisée par trois**.

**Nuance de Codex, retenue** : ce regroupement sert **à LIRE, pas à accélérer**. C'est pourquoi le
regroupement survit alors que ses boutons par journée tombent - avec quatre journées à créneau
unique, un bouton de groupe ne grouperait rien.

### 3.3 Afficher les totaux par créneau

Le manque le plus grave, et le plus invisible. Sans lui :

> « Un participant hésitant votera Non par précaution, paralysant toute la prise de décision. »
> (Gemini)

> « Le produit ne coordonne rien ; il collecte seulement des disponibilités isolées. » (Codex)

Ce n'est donc pas un simple confort : l'absence de cette information **biaise les réponses vers le
refus** et empêche le consensus de se former - ce que l'outil est censé produire.

**Option de confidentialité retenue** (Codex) : on peut masquer les NOMS, mais **les totaux par
créneau doivent rester visibles**.

### 3.4 Ne rien inventer sur la sélection

**Les boutons radio natifs restent.** Aucun geste nouveau, aucune grille gestuelle. C'est ce qui
garantit que le clavier, le focus, les lecteurs d'écran et la soumission continuent de fonctionner
sans avoir rien à coder pour ça.

Au plus, **une seule action globale** « répondre Oui / Peut-être / Non à tous les créneaux », avec
correction individuelle ensuite. Elle survit chez Codex ; elle n'est pas prioritaire.

**Si elle est implémentée, piège à respecter** (Codex) : les boutons d'action globale ne doivent
**jamais être des champs nommés**. Le contrôleur valide les identifiants connus mais n'interdit pas
les clés supplémentaires, et tenterait de convertir une clé artificielle en identifiant de créneau.
Ce sont des boutons **sans nom** qui cochent les vrais boutons radio existants, et qui déclenchent
les événements attendus après modification. Aucune modification serveur nécessaire.

---

## 4. Défaut existant relevé au passage, hors périmètre

Le contrôleur **met à jour les votes reçus mais ne supprime pas les votes omis**. Donc « effacer ma
réponse » ne fonctionne pas aujourd'hui. Corriger cela demande une modification serveur explicite -
à traiter séparément, ce n'est pas une régression introduite par ce chantier.

---

## 5. Ce qui reste incertain, et par quoi ça se tranche

- **Par une décision de Stéphane** : l'absence des totaux est-elle volontaire, pour éviter
  l'influence sociale entre participants ? Les deux oracles la jugent grave, mais aucun ne sait si
  c'était un choix.
- **Par une mesure** : quelle part de l'abandon vient du popup plutôt que de l'en-tête ? On ne le
  saura qu'après avoir retiré l'un puis l'autre.
- **Par le code** : le sélecteur de fuseau horaire est absent de cette page alors qu'il existe dans
  le module. Oubli ou affichage conditionnel ?

---

## 6. Critères d'acceptation, tous vérifiables

1. En 375 pixels de large, **au moins un créneau complet est visible sans défiler**.
2. Aucun popup ne recouvre le formulaire sur cette page.
3. La date n'apparaît **qu'une fois par journée**, jamais répétée ligne à ligne.
4. Un séparateur visuel distingue chaque journée.
5. Chaque créneau affiche le nombre de réponses reçues par état.
6. La navigation au clavier et le passage d'un lecteur d'écran restent équivalents à aujourd'hui -
   à vérifier réellement, pas à supposer.
7. La suite de tests du module reste verte.
