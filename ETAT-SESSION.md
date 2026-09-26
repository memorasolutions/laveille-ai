# État de session - la-veille-de-stef-v2

> Mis à jour le **2026-09-26 (Québec)**. Ce fichier est TOUJOURS le même : on le réécrit.

---

## ✅ Où on en est (terminé et prouvé en prod)

Outil de signatures de courriel, chantier majeur livré en plusieurs trains, tous vérifiés en prod :

- **v1.300.0** : création de l'outil (en construction).
- **v1.302.0** : outil ENRICHI - 8 gabarits via registre déclaratif + composants, bannière cliquable, pronoms, forme de photo, taille de police, sécurité mode sombre, copie/export HTML.
- **v1.303.0** : GALERIE de mises en page (bouton, modale à onglets par catégorie) + 6 nouvelles dispositions distinctes (14 gabarits au total); l'éditeur devient un ASSISTANT À ÉTAPES (5 étapes, stepper accessible) avec APERÇU COLLANT - sticky à droite sur ordinateur dans un cadre courriel, bande d'aperçu agrandissable sur mobile qui se réduit au focus. Décision mobile arbitrée entre 2 oracles (hybride).
- **v1.303.1** : correction du refus « dégueulasse » du fondateur - le sélecteur de gabarit (étape 1) ET la galerie affichent désormais des SCHÉMAS DE DISPOSITION (wireframes façon HubSpot) dérivés du registre, au lieu de boutons texte et d'aperçus iframe. Nouveau service `SignatureWireframeRenderer` (entrée = registre seul, donc testable), sérialisé en `window.SIGNATURE_TEMPLATE_WIREFRAMES`, lu par un accesseur `templateWireframe()` partagé entre le sélecteur et la galerie.
- **v1.303.2 (dernier, déployé et vérifié EN PROD sur la page réelle)** : le fil d'étapes s'affichait empilé à la verticale (numéros mal centrés). CAUSE RACINE : dans `signature.css`, un commentaire contenait la suite `*/` (dans `--sys-status-*/--c-primary`), qui refermait le commentaire trop tôt et faisait avaler par le navigateur la règle `.ct-stepper { display:flex }` juste en dessous. Le train v1.303.1 avait bien ajouté la règle, mais elle n'était jamais appliquée. Corrigé le commentaire.
  - **Preuves** : commentaires CSS équilibrés (14 ouvertures / 14 fermetures) ; CI verte (run 36250033365), déploiement réussi (run 36250212518), prod = 1.303.2. Vérifié sur la PAGE RÉELLE en production (débloquée temporairement en mode aperçu avec l'accord du fondateur, puis remise en construction) : `.ct-stepper` calcule `display:flex/row`, les 5 étapes alignées (mêmes `top`, `left` croissants), numéros centrés dans les cercles ; captures avant/après à l'appui.
- **v1.303.3** : la fenêtre modale « Toutes les mises en page » (et les 2 autres modales) n'était pas centrée - le `display:flex` de centrage, mis en style INLINE, était effacé par Alpine `x-show` (qui gère la propriété `display`). Centrage déplacé dans une classe CSS `.sig-modal-overlay`. Prouvé centré sur la PAGE RÉELLE en prod (aperçu par jeton), CI verte (run 36251548587), déploiement réussi (run 36251743333).
- **v1.303.4** : le curseur « Taille d'affichage » (logo/portrait/bannière) ne changeait rien à l'aperçu en direct - le moteur dimensionnait l'image par ses dimensions intrinsèques, pas par `display_width`. Corrigé côté JS ET PHP. Audit live : TOUTES les options (taille d'image, police, couleur, forme, texte) se reflètent désormais immédiatement. 103 tests PHP verts, prod=1.303.4.
- **v1.303.5 (dernier, déployé et vérifié EN PROD sur la page réelle)** : les boutons de l'éditeur ne respectaient pas la charte (style Bootstrap bleu générique). Conversion complète vers le système de charte `ct-btn` (19 occurrences : `ct-btn-primary` teal plein, `ct-btn-outline` contour, `ct-btn-outline-danger`, `ct-btn-sm`) sur « Suivant/Précédent », les ajouts de lien social et de ligne, la bascule d'aperçu Bureau/Mobile, les retraits; `btn-close` (4) et `btn-group` (2) conservés. Aucun résidu `btn btn-*`.
  - **Preuves** : CI verte (run 36255213735), déploiement réussi (run 36255388276), prod=1.303.5 (témoin statique + pied de page). Mesuré sur la PAGE RÉELLE en production (mode aperçu par jeton, puis remise en construction) : « Suivant » = blanc sur teal `rgb(6,78,90)` = `#064E5A` (`ct-btn-primary`), « Précédent » + « Ajouter un lien social » + « Ajouter une ligne » = contour teal transparent (`ct-btn-outline`), police « Plus Jakarta Sans »; bascule Bureau (teal plein) / Mobile (contour) visible à la capture.
- **v1.304.0 (dernier, déployé et vérifié EN PROD)** : DEUX demandes du fondateur. (1) FONCTIONNALITÉ - option « mention laveille.ai » : un commentaire HTML `<!-- Signature créée gratuitement avec laveille.ai, ... -->` en tête du code, invisible dans la signature rendue, présent dans la source; case cochée par défaut à l'étape Finaliser, retirable. Moteur unique (renderSignature JS + SignatureRenderer PHP), champ `content.show_attribution` normalisé des deux côtés (défaut true), validé (nullable boolean), 4 tests neufs (107 verts). (2) CORRECTIF - la fenêtre « Code HTML » passe de `white-space:pre` à `pre-wrap` + césure, le code revient à la ligne. Bump MINEUR (fonctionnalité nouvelle).
  - **Preuves** : CI verte (run 36256606533), déploiement réussi (run 36256773736), prod=1.304.0 (témoin statique + pied de page). Mesuré sur la PAGE RÉELLE en prod (aperçu par jeton, puis remise en construction) : case cochée par défaut; code coché commence par le commentaire, décoché commence par `<table` (commentaire retiré); textarea du code `white-space:pre-wrap` + `overflow-wrap:anywhere`. Commit d2937cd86 poussé origin+forge.
- **v1.305.0 (poussé le 2026-09-26, CI/déploiement et QC prod par cycle aperçu à confirmer dans la foulée)** : TROIS demandes du fondateur, groupées en UN seul train (règle anti-fenêtres-de-maintenance). (1) #2839 CORRECTIF de l'attribution : le commentaire HTML est désormais TOUJOURS présent (non désactivable), et la case pilote une MENTION VISIBLE au bas de la signature (défaut affichée, retirable) - corrige le 1.304.0 où la case retirait le commentaire et où aucune mention visible n'existait. (2) #2840 FONCTIONNALITÉ : sauvegarde auto de la signature en cours dans le navigateur (localStorage `lv_signature_draft_v1`, jamais pour une signature déjà chargée du serveur), restaurée au rafraîchissement, + bouton « Remise à zéro » (confirmation par modale du thème, jamais native). (3) #2841 UX : barre de navigation « Précédent/Suivant » COLLANTE en bas de la colonne du formulaire avec indicateur « Étape X sur 5 » - verdict de 2 oracles (barre collante plutôt que duplication haut+bas), cibles 44 px, `scroll-padding-bottom`, zone sûre mobile. Bump MINEUR (fonctionnalités nouvelles).
  - **Preuves LOCALES** : 188 tests PHP verts (1018 assertions), `php -l`/`node --check`/compilation Blade OK. Vérifié sur la PAGE RÉELLE en local (outil débloqué en base locale puis remis en construction) : brouillon sauvé puis RESTAURÉ après rechargement (Amélie/amelie@example.com); `resetSignature()` remet le contenu à vide et à l'étape 1; barre `position:sticky; bottom:0`, « Étape 1 sur 5 », bouton Suivant `min-height:44px`, `scroll-padding-bottom:96px`; rendu JS : commentaire présent dans LES DEUX états, mention visible seulement si cochée. Captures de la modale de reset et de la barre collante prises. **Reste : confirmer CI verte + déploiement + QC sur la prod réelle par cycle aperçu.**

Aussi plus tôt : bug toast du tirage unifié (v1.302.0), fiche 59018, 2 fiches de glossaire, tirage-présentations 4 flexibilités.

## 🔄 En cours

Rien en agent. Tout le travail non bloqué est déployé et vérifié en prod.

## ⏸️ Ce qui BLOQUE sur le fondateur

1. **QC interactive de l'éditeur de signatures** (ta connexion admin, /outils/signature-courriel, outil EN CONSTRUCTION donc invisible au public) : parcourir les étapes, la galerie de wireframes, l'aperçu qui suit. Structure et comportement déjà prouvés par tests + captures + asset prod identique.
2. **Deux points de jugement du wizard à confirmer** : le slogan (tagline) est placé à l'étape « Vos informations »; le champ bannière reste toujours visible (comportement d'origine préservé).
3. **QR code des signatures** : reporté, dépendance de génération à valider avec toi.
4. **Refonte veille.la (#2822) + partie 1 (#2817)** : tu dois d'abord SUPPRIMER dans le portail les publications en attente (10 actus 350-359, 4 série 346-349), aucune route DELETE dans l'API.
5. Décisions/actions : #2585, #2735, #2788, #2368 (Turnstile), #2276/#2638, #2597, #2722, #2720, #2723, #2686, #2798/#2799.

## ➡️ Déféré, assumé

- #2812 compactage MEMORY.md; #2824 ménage cosmétique des résidus prod (terminal Shell HS).

## ➡️ Prochaine action proposée

En l'absence d'une action débloquée, présenter au fondateur la correction v1.303.1 (galerie/sélecteur en wireframes) pour sa QC interactive, ou attendre son déblocage sur les points ci-dessus. Le plan de croissance du constructeur de prompts (`~/.claude/plans/elegant-popping-storm.md`, Phase 1 permalien + remixer) reste disponible si tu veux ouvrir ce chantier.
