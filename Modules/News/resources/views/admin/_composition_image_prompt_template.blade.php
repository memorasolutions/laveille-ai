{{-- Consigne de recherche photo pour l'écran de composition manuelle d'une actualité (design doc
     "Actus - composition manuelle assistée" 2026-08-15, section 5.3 ; RÉVISION 2026-08-17.3 -
     décision du propriétaire, panel de 5 IA : l'illustration générée par IA est abandonnée au
     profit d'une PHOTO cherchée dans une banque libre de droits, APRÈS que le texte de la fiche
     soit révisé et figé (étape 5 du gabarit d'orchestration). Ce gabarit ne produit donc plus un
     prompt de génération d'image 3D isométrique, mais la CONSIGNE DE RECHERCHE que Claude Code CLI
     (ou l'admin, via le bouton "copier") suit pour choisir la photo à l'étape 6. Le bouton de
     l'écran reste fonctionnel à l'identique : il copie ce texte tel quel.
     Variables : $title, $angle. --}}
CONSIGNE DE RECHERCHE PHOTO pour une fiche d'actualité du site laveille.ai.

SUJET : {{ $title !== '' ? $title : "(aucun titre de travail fourni - cherche une photo sur le thème de l'intelligence artificielle en général)" }}
@if($angle !== '')
ANGLE ÉDITORIAL : {{ $angle }}
@endif

MOTS-CLÉS DE RECHERCHE : tire 3 à 5 mots-clés concrets du sujet ci-dessus (objets, lieux, métiers,
scènes réelles qu'il évoque - jamais des mots abstraits comme « intelligence » ou « innovation »
qui ne renvoient aucun résultat pertinent dans une banque de photos). Cherche-les en français ET en
anglais : la majorité des banques libres de droits sont indexées en anglais, une recherche
uniquement française rate souvent la meilleure photo.

CRITÈRES D'ACCROCHE :
- Une photo qui attire l'oeil en vignette : sujet net, cadrage clair, lisible même en petit format.
- Cohérente avec le sujet réel de la fiche - pas une scène générique interchangeable (poignée de
  main devant un écran flou, personne souriante devant un ordinateur sans rapport avec le texte).
- Si une photo dans la palette teal/bleu-acier de la charte du site existe et convient, elle est un
  plus, mais ce n'est jamais un critère éliminatoire face à une photo plus juste et plus accrocheuse
  dans d'autres teintes.

INTERDITS DE LICENCE, STRICTS (le projet a déjà reçu une réclamation PicRights sur une photo de
presse) :
- AUCUNE photo de presse, éditoriale ou d'agence (Getty Images, AP, Reuters, agences de presse et
  équivalents) - même trouvée via une recherche d'image générale. Seules les licences explicitement
  libres de droits pour usage commercial (Unsplash, Pexels, ou équivalent) sont admises.
- Aucun logo visible, aucune marque reconnaissable.
- Éviter les personnes réellement identifiables (visage reconnaissable d'une personnalité publique
  liée au sujet) - préférer une scène, un objet, ou des personnes anonymes de photo de stock.
- Vérifier la licence AVANT de retenir la photo, pas après.

FORMAT : cadrage paysage, pensé pour un recadrage 1200×630 (image de partage réseaux sociaux) -
sujet principal centré, avec de la marge exploitable en haut et en bas de l'image.

CRÉDIT : note le nom du photographe et de la banque exigés par la licence - il sera appliqué à la
fiche via le champ image_credit (étape 6 du gabarit d'orchestration).
