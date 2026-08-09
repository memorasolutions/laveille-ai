# Club des sages - Constructeur de prompts, round 1 et 2

Date : 2026-08-02 (America/Toronto)
Oracles consultés : Perplexity (2 recherches), Codex, Gemini (agy, Gemini 3.1 Pro High), claude.ai (Opus 5 Élevé, navigateur), DeepSeek (deepseek-v4-flash via Hermes, tier mid - deepseek-reasoner non disponible au moment de l'appel)

---

## Question 1 - Fonctionnalités manquantes pour un outil "ultra performant"

### Synthèse convergente (notée par au moins 2 oracles indépendants)

| # | Fonctionnalité | Note synthèse | Oracles | Justification |
|---|---|---|---|---|
| 1 | **Aperçu du prompt vivant et éditable** (se construit sous les yeux, modifiable avant copie) | **93/100** | Codex 93, Gemini 95, claude.ai 90 | Démystifie la "boîte noire", enseigne implicitement sans ajouter de clic. Coût nul (front seulement). |
| 2 | **Passerelle vers l'IA** (copier + ouvrir ChatGPT/Claude/Gemini) | **88/100** | Codex 83, Gemini 90, claude.ai 92, DeepSeek (implicite) | Le vrai point de rupture du néophyte n'est pas d'écrire le prompt, c'est de savoir quoi en faire ensuite. **Piège critique (claude.ai, vérifié par recherche)** : le préremplissage par URL (`?q=`) est fragile - fonctionne partiellement sur chatgpt.com (limites de longueur/encodage), le paramètre web de Claude aurait été retiré vers octobre 2025. Conception robuste : **copie presse-papiers d'abord, ouverture d'onglet ensuite**, jamais l'inverse. |
| 3 | **Vérificateur déterministe** (champs manquants : public, format ; alerte si donnée personnelle détectée) | **85/100** | Codex 91 (détection manques) + 81 (confidentialité), claude.ai 84, DeepSeek 85 (ambiguïtés) | Substitut gratuit à un "améliorer par IA" payant. Règles locales (regex/mots-clés), pas de modèle. Argument Loi 25 crédible pour la détection de données personnelles. |
| 4 | **Historique et favoris locaux** (localStorage, sans compte) | **84/100** | Codex 84, Gemini 85, claude.ai 78, DeepSeek 90 | Convergence sur 4 oracles. Zéro coût serveur, respecte la vie privée. Prévoir un bouton "Effacer mon historique" et assumer que tout disparaît si le cache est vidé. |
| 5 | **Exemple avant/après ou exemple prérempli par situation** | **80/100** | Codex 87, claude.ai 76, DeepSeek 70 | Réduit la peur de la page blanche mieux qu'une explication longue. Coût de code faible, coût de rédaction réel (mieux vaut 8 bons exemples que 40 médiocres). |
| 6 | **Lien de partage** (état du prompt encodé dans l'URL) | **82/100** | claude.ai 86 | Un enseignant qui partage sa recette à des collègues devient un canal d'acquisition. Zéro base de données (fragment d'URL). |
| 7 | **Choix du format de sortie** (court, détaillé, liste, tableau, étapes) | **89/100** | Codex 89 | Souvent oublié par les débutants alors qu'il détermine fortement l'utilité du résultat. Boutons limités selon la situation choisie, pas affichés partout. |

### Pistes à note plus faible ou proposées par un seul oracle (optionnelles, non prioritaires)

- PWA hors-ligne (Gemini, 85) - solide mais hors du problème central (complexité perçue), à ne considérer qu'après le socle.
- Jauge de qualité gamifiée locale (Gemini, 70) - risque de "fausse note scientifique" si mal présentée.
- Variantes de ton en un clic (DeepSeek, 95 isolé) - séduisant mais risque de recréer des options ; à traiter comme sous-fonction du vérificateur, pas un nouveau bloc visible.
- Suggestions de mots-clés contextuels par catégorie (DeepSeek, 75) - utile mais mineur.
- Entrée inverse : coller une demande floue, classement automatique vers une situation (claude.ai, non noté séparément) - **utile comme filet de sécurité pour la 9e carte "Autre chose"**, voir Question 2.

### Ce qu'il ne faut PAS construire (consensus explicite, notamment claude.ai)

- Sélecteur de modèle cible avec variantes du prompt par IA (40/100) - la différence entre modèles ne justifie plus la complexité en 2026.
- Bibliothèque de prompts façon catalogue (45/100) - **recrée exactement le problème déjà mesuré** (13 prompts sauvegardés, 7 utilisateurs, rien depuis le 2026-05-25 selon le round précédent).
- Comptes utilisateurs obligatoires (30/100) - coût d'opération, obligations Loi 25, friction.
- Tout "optimiseur IA" ou "vérificateur" appuyé sur une API tierce, même gratuite : **rejeté par tous les oracles qui l'ont abordé** (Codex, claude.ai). Un palier gratuit d'API (Gemini, Groq) est un quota PARTAGÉ entre tous les visiteurs du site - le trafic de laveille.ai l'épuiserait en quelques heures. Le BYOK (apporter sa propre clé) reporte le problème sur un néophyte qui ne sait pas ce qu'est une clé API.

---

## Question 2 - Cartes visuelles vs phrase à trous

### Verdict : convergence totale des 5 oracles - COMBINER, jamais remplacer

**Note synthèse : 92/100**

| Oracle | Note | Formulation |
|---|---|---|
| Perplexity (recherche) | - | Pattern standard 2026 : "grille de tuiles → formulaire/phrase à compléter", documenté dans les systèmes de design gouvernementaux (Québec, France), l'e-learning, les générateurs de gabarits. |
| Codex | 96/100 | Cartes = "quel est mon besoin ?", phrase à trous = "quelles précisions dois-je fournir ?" - pas concurrentes. |
| Gemini | 95/100 | "Entonnoir parfait" : triage visuel (étape 1) puis affinage guidé (étape 2). Remplacer entièrement par des cartes serait une régression (aucun contexte capturé) ; remplacer entièrement par la phrase avec menu déroulant reproduirait l'aspect formulaire intimidant. |
| DeepSeek | 92/100 | Cartes remplacent la 1re étape de sélection, la phrase reste l'étape 2. Seul risque : confusion si les 2 étapes ne sont pas visuellement distinctes. |
| claude.ai | 88/100 | Nuance la plus fine (voir ci-dessous) : la carte n'est pas une étape séparée, c'est le premier trou de la phrase rendu visuellement. |

### La nuance de claude.ai qui reconcilie tout (à retenir pour le design)

Le découpage "étape 1 cartes / étape 2 phrase" que proposent Codex/Gemini/DeepSeek risquerait de recréer un assistant à 2 écrans - **exactement ce que le round précédent (2026-08-01) a exclu** ("un écran, une phrase à compléter, un bouton"). claude.ai résout la tension : la carte choisie **se réduit en pastille cliquable en tête de la phrase**, et le reste de la phrase à trous continue sur le MÊME écran, sans bouton "suivant" ni fenêtre modale. On reste donc fidèle au mandat "un seul écran" tout en gagnant l'entrée visuelle par cartes.

### Précédent concret trouvé (Perplexity + claude.ai, recherche croisée)

**Galerie de prompts Microsoft 365 Copilot** : cartes avec titre + description, filtrables par tâche/métier ; la sélection insère le prompt dans un champ éditable où les parties modifiables sont signalées par des crochets (`[sujet]`, `[fichier]`) - hybride carte + phrase à trous déployé à grande échelle auprès d'un public non technique. La même interface propose aussi "copier" et "copier un lien partageable", ce qui valide indépendamment les pistes #2 (passerelle) et #6 (lien de partage) de la Question 1.

Contre-exemples instructifs : les Gems Gemini (galerie puis édition en texte libre SANS trous guidés - le néophyte décroche à cette étape) et les catalogues purs type PromptBase/Gumroad (s'arrêtent au copier-coller, échouent exactement où cet outil doit réussir).

### Risques identifiés à couvrir dans le design final (claude.ai, à ne pas ignorer)

1. **Mobile** : 8 cartes carrées avec icône = 3-4 écrans de défilement avant tout contenu utile. Vérifier la part de trafic mobile réelle de l'outil avant de choisir entre tuiles et rangées compactes (icône à gauche + libellé bat souvent les cartes carrées sur téléphone).
2. **Taxonomie fermée** : 8 cartes disent "voici tout ce que l'outil sait faire" - prévoir une 9e carte "Autre chose" avec classement par mots-clés (rejoint la piste "entrée inverse" de Q1). Son taux de clic devient une donnée de conception pour la 9e catégorie future.
3. **Libellés** : "Préparer une activité pédagogique" n'est pas le vocabulaire d'un enseignant pressé ("préparer un cours"). Reformuler en verbes courts, longueurs homogènes (sinon cartes visuellement inégales/tronquées).
4. **Contamination visuelle** : ne jamais transformer le ton, le public ou le format en cartes plus tard - règle explicite à écrire dans le plan : **les cartes servent une seule fois, pour la situation, jamais pour un sous-choix**. C'est le garde-fou direct contre une régression vers 87 options.
5. **Mesure** : si trafic suffisant, A/B avec une seule métrique qui arbitre (% de visiteurs qui atteignent la copie du prompt) et une métrique de contrôle (temps jusqu'à la copie) - cohérent avec et renforce la métrique unique déjà retenue par Codex au round précédent (copier en moins de 60 secondes).

---

## Recommandation tranchée (synthèse Claude, confrontation des 5 avis)

**Q1** : construire dans l'ordre de priorité les 7 fonctionnalités convergentes (aperçu vivant, passerelle IA robuste, vérificateur déterministe, historique local, exemples, lien de partage, choix du format) - toutes gratuites, toutes côté client ou règles locales, aucune ne réintroduit un champ visible en permanence. Rejeter explicitement sélecteur multi-modèle, bibliothèque façon catalogue, comptes obligatoires, et tout mécanisme dépendant d'une API IA tierce (gratuite ou non) pour la vérification/amélioration.

**Q2** : les cartes ET la phrase à trous, sur un seul écran, sans étape 2 distincte - la carte choisie devient une pastille en tête de phrase. Précédent validé à grande échelle (Microsoft 365 Copilot). Les 5 garde-fous de claude.ai (mobile, taxonomie fermée, libellés, contamination visuelle, mesure) sont non négociables dans le design final, pas des "nice to have".
