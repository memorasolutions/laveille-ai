# Constructeur de prompts - remise à zéro

Date : 2026-08-01 (America/Toronto)
Statut : **proposition, aucune ligne de code écrite avant accord**

---

## 1. Le diagnostic, en chiffres mesurés

| Mesure | Avant refonte (v1.131.0) | Aujourd'hui | Écart |
|---|---|---|---|
| Lignes de code (Blade + JS) | 965 | 4 304 | **+245 %** |
| Options cliquables | - | **87** | - |
| Concepts imposés à l'utilisateur | 27 champs | **21 étiquettes** | - |
| Boutons | - | 50 | - |

Trois faits qui tranchent le débat :

1. **L'outil était noté 88/100** lors de l'audit du 2026-06-18, à 890 lignes, qualifié de
   « mature et complet ». La refonte n'a pas réparé un outil cassé : elle a cassé un outil qui
   fonctionnait.
2. **Revenir en arrière ne réglerait rien.** La version 88/100 avait déjà 27 champs
   (`technique`, `useDelimiters`, `constraintAntiAI`, `canvasFormat`, `personaPreset`…).
   Elle était moins volumineuse, pas plus simple. Un retour arrière reproduirait le même
   problème en plus petit.
3. **Les fonctions ajoutées ne servent à personne** (production, mesuré) :
   - bibliothèque de prompts : 13 prompts, 7 utilisateurs, **rien de nouveau depuis le 2026-05-25** ;
   - cartes de démarrage personnalisables : **1 utilisateur sur 69**.

Symptôme de la dérive : la spécification écrite pour cet outil fait **60 Ko**. Pour un outil qui
doit produire un paragraphe de texte.

## 2. Ce que le panel a dit

| Oracle | Verdict | Note « page blanche » vs « élaguer » |
|---|---|---|
| Perplexity (août 2026) | 3 champs par défaut, 4 maximum. Le champ à ne jamais perdre : le format de sortie. | - |
| Codex | « L'architecture actuelle encode déjà la mauvaise idée. » Métrique unique : % d'utilisateurs qui copient un prompt en moins de 60 secondes. | **95** / 30 |
| DeepSeek | Seuil d'abandon entre 3 et 5 décisions. Folklore périmé : zero-shot/few-shot/CoT, rôle, verbe d'action, délimiteurs, anti-IA, Canvas. | **85** / 40 |
| Gemini | Casse le consensus : le formulaire vide laisse l'utilisateur devant l'angoisse de la page blanche. Propose la **phrase à trous**. | **98** pour sa propre approche |

| claude.ai (Opus 5) | Tranche pour la phrase à trous : « un champ vide intitulé Contexte demande exactement la compétence que l'utilisateur n'a pas ». | - |

Convergence totale sur un point : **supprimer**, pas replier. Et sur un second :
en 2026, les assistants déduisent seuls le ton, le format et le rôle - structurer le prompt
n'apporte plus rien. Ce qui reste utile, c'est d'aider quelqu'un à **préciser sa pensée**.

### Ce que la synthèse ajoute (les oracles ne s'opposent pas)

Les trois premiers décrivent **quoi** collecter (objectif, contexte, format). Les deux derniers
décrivent **comment** le demander (une phrase à compléter, pas un champ vide). C'est compatible :
la phrase à trous est simplement la façon de recueillir ces trois informations sans jargon.

Trois avertissements retenus, chacun d'un oracle différent :

- **claude.ai** : le prompt assemblé doit rester **modifiable** avant la copie, sinon l'utilisateur
  se sent piloté. Plus un champ libre optionnel à la fin.
- **claude.ai** : le vrai danger de la phrase à trous est l'**explosion combinatoire** - chaque cas
  d'usage réclame sa phrase. C'est exactement le mécanisme qui a produit 87 options. D'où le
  plafond dur : **8 cas d'usage, jamais plus**, et on assume que l'outil soit incomplet.
- **Codex** : une métrique unique qui arbitre tout ajout futur - **copier un prompt en moins de
  60 secondes**.

Repositionnement proposé (claude.ai) : l'outil n'est plus un « générateur de meilleurs prompts »
(argument mort en 2026) mais un **exercice** - on repart en comprenant pourquoi sa demande était
floue.

## 3. Ce qui est proposé

Un écran. Une phrase à compléter. Un bouton. Le résultat visible immédiatement.

Rien d'autre au premier regard : ni onglet, ni accordéon, ni réglage, ni étape 2.

**Ce qui disparaît de l'écran** (le code part, les données restent) :
technique zero-shot/few-shot/chaîne de pensée, délimiteurs, cadre strict, rôle de l'IA,
verbe d'action, ton, longueur précise, écriture anti-IA, règles typographiques,
destination Canvas/Artefact, cartes personnalisables, profil, deuxième écran.

**Ce qui est conservé** :
- les 13 prompts déjà sauvegardés (aucune donnée utilisateur supprimée, jamais) ;
- l'accès à ces prompts par une page à part, hors du chemin principal ;
- le bouton « Copier », le seul geste qui compte ;
- l'avertissement permanent de ne pas inscrire de renseignement personnel.

## 4. Le garde-fou pour ne pas recommencer

Une seule règle, vérifiable : **une personne qui n'a jamais vu l'outil doit copier un prompt
en moins de 60 secondes, sans aide.** Toute fonction qui n'améliore pas cette mesure est
refusée - y compris si elle est intéressante.

## 5. Rollback

Le code actuel reste dans l'historique git (branche + tag avant remplacement). L'outil est
déjà en mode révision, invisible du public : la refonte se fait sans aucun risque pour les
visiteurs.
