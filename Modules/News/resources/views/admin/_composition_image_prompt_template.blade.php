{{-- Prompt d'image pour l'écran de composition manuelle d'une actualité (design doc "Actus -
     composition manuelle assistée" 2026-08-15, section 5.3). Copié manuellement par l'admin dans
     Gemini (pilotage navigateur, compte propriétaire) : la génération d'image n'est PAS
     programmable sur ce projet, aucun bouton « générer » n'existe.
     Style calqué sur le standard établi (mémoire projet "Miniatures via Gemini Playwright" -
     scène 3D isométrique multi-niveaux, jamais un objet unique) et cohérent avec la palette
     teal/bleu-acier signature déjà produite par NewsImageService::generateFallbackImage()
     (dégradé #064E5A par défaut, accents ambre/orange discrets) - ce prompt reproduit ce standard
     en scène illustrée plutôt qu'en dégradé généré par code.
     Variables : $title, $angle. --}}
Crée une image d'illustration pour une fiche d'actualité du site laveille.ai.

SUJET : {{ $title !== '' ? $title : "(aucun titre de travail fourni - illustre le thème de l'intelligence artificielle en général)" }}
@if($angle !== '')
ANGLE ÉDITORIAL : {{ $angle }}
@endif

STYLE OBLIGATOIRE (identité visuelle du site, ne pas dévier) :
- Scène 3D ISOMÉTRIQUE multi-niveaux : plusieurs plateformes/socles flottants à des hauteurs
  différentes, chacun illustrant un aspect du sujet - jamais un objet unique centré sur fond plat.
- Palette dominante : bleu acier et teal/cyan pâle, avec des touches DISCRÈTES d'ambre/orange en
  accent (jamais l'orange en couleur dominante).
- Personnages et objets : silhouettes blanches ou crème, écrans cyan, petits modules/robots/plantes
  qui peuplent la scène - univers "monde miniature" riche en détails, jamais minimaliste.
- Connexions/lignes fines cyan ou bleues entre les plateformes.
- Fond clair (bleu-gris ou bleu ciel très pâle), jamais un fond sombre uniforme.

INTERDICTIONS STRICTES :
- AUCUN texte, AUCUNE lettre, AUCUN chiffre visible dans l'image, nulle part.
- Aucun logo, aucune marque, aucun drapeau, aucune carte du monde.
- Pas de style "icône plate 2D" ni de fond sombre uni.

FORMAT : cadrage paysage, pensé pour un recadrage 1200×630 (image de partage réseaux sociaux) - garde
le sujet principal centré, avec de la marge exploitable en haut et en bas de l'image.
