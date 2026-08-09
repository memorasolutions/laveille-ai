# Plan unique : protection des renseignements dans le constructeur de prompts

Date : 2026-07-31 (America/Toronto)
Statut : PROPOSITION. Rien n'est implémenté.
Sources : inspection factuelle du code réel + Perplexity (pp_search) + Codex.
Manquent : Gemini et claude.ai sur ce plan final.

---

## Le fait qui change tout

**L'aller-retour existe déjà dans /outils/anonymiseur.** Vérifié dans le code, pas supposé :

| Pièce | Où |
|---|---|
| Restauration inverse | `restore(aiText, rules, overrides)`, anonymizer-core.js:556-590 |
| Zone « réponse de l'IA » | `#aiResponse`, étape 2, anonymiseur.blade.php:129-150 |
| Rapport de restauration | `buildRestoreReportHtml`, anonymizer-rich.js:215-239 |
| Valeurs non retrouvées signalées | `{ text, found, notFound }`, core.js:590 |
| Mode réaliste ET mode jetons | `buildRules(..., {mode})`, bascule `btnModeToggle` |
| Cohérence des substituts | `nameMap`, core.js:451-500 |
| Pont vers le constructeur | `#btnToPromptBuilder`, via sessionStorage, ui.js:843-847 |
| Bulle de sélection, popover par occurrence, « Ma valeur », « Seulement ici » | ui.js:364-423, 577-744 |
| 100 % local | 0 fetch/XHR/beacon dans les 3 fichiers JS |

**Conséquence directe : on ne construit rien de neuf. On branche, et on corrige trois défauts réels.**

---

## L'objectif, reformulé avec le propriétaire

Le but n'est pas l'anonymisation absolue. Le but est que **dans le contexte du texte, personne ne puisse être pointé du doigt**, parce que tout ce qui identifie a été remplacé par un équivalent fictif cohérent.

C'est défendable et proportionné pour l'usage courant. Ce n'est pas un état juridique garanti : le règlement québécois demande un risque résiduel très faible en tenant compte de l'individualisation, de la corrélation et de l'inférence. La règle pratique retenue :

> Si quelqu'un qui connaît le milieu reconnaîtrait la situation sans voir les noms,
> le remplacement automatique ne suffit pas.

---

## Les trois défauts réels du moteur (prouvés par le code)

### D1. Faux courriels et téléphones qui peuvent exister pour vrai — CRITIQUE
`domains: ['gmail.com','hotmail.com','yahoo.ca','videotron.ca','bell.net']` (core.js:9).
Un courriel généré peut désigner une boîte réelle. Les téléphones randomisent les chiffres
sans plage réservée (core.js:338) : le faux numéro peut appartenir à quelqu'un.
Correction : domaines réservés (RFC 2606) et plage 555-01XX.

### D2. Aucune notion de genre — BLOQUANT pour la qualité de la réponse
Aucune occurrence de genre dans les 3 fichiers. `generateFake('name')` pioche au hasard dans
les prénoms masculins ET féminins fusionnés (core.js:360). « Marie Tremblay » peut devenir
« Jean Fortin » : l'IA accorde au masculin, la réponse est inutilisable après restauration.

### D3. Employeurs, organisations et villes non détectés — LE PLUS IMPORTANT POUR L'OBJECTIF
La catégorie `organization` existe côté génération (core.js:381-383) mais AUCUNE règle ne la
détecte. Or c'est exactement ce qui identifie une personne dans un contexte : « la directrice
de l'école X à Y ». Sans ça, l'objectif « personne n'est identifiable dans le contexte » n'est
pas atteint, même avec tous les noms remplacés.

---

## Les niveaux de protection, notés

| Niveau | Note | Ce qu'il couvre | Ne couvre pas | Pour qui |
|---|---:|---|---|---|
| 1. Détection et surlignage seulement | 25 | Signale les évidences | Ne transforme rien | Vérification rapide |
| 2. Jetons `[NOM]` | 55 | Identifiants directs | Dégrade la réponse de l'IA | Question générique |
| 3. **Substitution fictive cohérente (existant)** | **72** | Noms, adresses, courriels, téléphones, dates, montants, numéros | Organisations, villes, événements rares, style | Usage courant |
| 4. **Substitution + organisations/villes + genre + plages réservées** | **88** | Le niveau 3 corrigé de D1/D2/D3 | Une histoire connue, une citation retrouvable | **Le défaut visé** |
| 5. Alerte contextuelle avant la copie | 93 | Signale santé, enfants, discipline, citation longue, combinaison très distinctive | Ne garantit rien | Dossiers sensibles |
| 6. Ne pas transmettre le récit | 98 | Résumé abstrait, ou conseil de ne pas utiliser une IA externe | Perte de détail | Santé, droit, enquêtes |

**Retenu : niveau 4 par défaut, niveau 5 déclenché seulement sur signal de risque.**
Présenter six choix à un public non technique tuerait l'outil.

---

## Le plan, par ordre d'exécution

### Étape 1 — Corriger le moteur (3 défauts ci-dessus)
1. Plages et domaines réservés pour courriels et téléphones (D1).
2. Genre du prénom respecté (D2). Prérequis de tout le reste.
3. Détection des organisations et des villes (D3), avec substituts cohérents.
Chaque correctif prouvé par banc d'essai exécuté sur 300 passages, pas une seule exécution
(le tirage est aléatoire : un seul essai ne prouve rien, leçon du round 149).

### Étape 2 — Brancher le constructeur sur l'existant
Le champ du constructeur reste un champ simple. Un bouton ouvre l'éditeur existant, la personne
choisit elle-même quoi remplacer (bulle de sélection), le texte revient masqué. Le pont
`#btnToPromptBuilder` existe déjà dans l'autre sens : il faut l'aller-retour complet.
AUCUN second éditeur n'est construit.

### Étape 3 — Vocabulaire honnête
Bouton : **« Remplacer les renseignements sensibles »**.
Explication : « Remplace les noms, coordonnées et autres détails reconnaissables par des données
fictives cohérentes. Vérifiez le résultat avant de copier : une situation rare ou connue peut
encore permettre de reconnaître quelqu'un. »
Retirer « anonymiser » comme promesse. Le mot reste faux tant que la correspondance existe.

### Étape 4 — Relecture avant copie (le garde-fou qui compte)
Le texte transformé est montré avant la copie, avec ce qui pourrait encore identifier quelqu'un.
Le vrai danger n'est pas un mauvais pseudonyme : c'est un détail non détecté combiné à une
promesse trop rassurante.

---

## Ce qu'il ne faut absolument pas rater

**Ne jamais laisser croire que le bouton garantit que le texte est sûr à transmettre.**
Avec une étape de relecture et un langage honnête, la substitution cohérente devient une mesure
réaliste et proportionnée pour la majorité des usages. Sans elle, l'outil fabrique un faux
sentiment de sécurité.

## Hors périmètre, signalé
- Pas d'export de la table de correspondance hors du navigateur (pas de reprise sur un autre appareil).
- Un seul texte source persistant à la fois : un nouveau texte écrase le contexte précédent.
- La restauration échoue si l'IA reformule au point de ne plus reprendre le faux tel quel.
  Déjà signalé à la personne par le rapport `notFound`, mais non récupéré automatiquement.
