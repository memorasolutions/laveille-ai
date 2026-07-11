# Journal personnel — design (Option D « Sélection rapide + blocs éditables »)

Statut : design approuvé (brainstorming du 2026-07-11), prêt pour plan d'exécution.

## Contexte et problème

L'utilisateur veut permettre aux membres connectés (incluant admins) de créer des « journaux »
personnalisés à partir du contenu du site (actualités, termes de glossaire, outils de
l'annuaire, texte perso, images, vidéos YouTube), via un constructeur visuel simplifié
(pas Elementor complet), avec gabarits pré-faits. Objectif business : plus de trafic +
indexation SEO pour laveille.ai, tout en réduisant le besoin de publier fréquemment sur les
réseaux sociaux (un journal partagé = plus de substance qu'un post social).

Précédent à ne pas répéter : `Modules/Blog/ArticleSubmissionController` (« proposer un
article ») était juste une grosse `<textarea>` + validation admin obligatoire avant
publication — zéro mise en page, zéro liberté créative, friction systématique. Rejeté par
l'utilisateur.

## Objectifs

- Créer plusieurs journaux par utilisateur, choix titre/date/gabarit.
- Curation rapide : bouton **« + Ajouter à mon journal »** sur les fiches actualités,
  glossaire et outils du site → remplit un bloc automatiquement (friction quasi nulle).
- Enrichissement libre : texte perso (Tiptap), image (case droits d'auteur obligatoire,
  compression auto), vidéo YouTube — blocs empilés réordonnables (pas de positionnement
  libre XY type Elementor).
- 4-5 gabarits de structure de page pour démarrer sans page blanche.
- Visibilité par journal : privé → partagé (lien) → public indexé.
- Page personnelle « Mes journaux » (façon favoris) + prévisualisation feuilletable
  (réutilise `flip-reader`).

## Non-objectifs (V1)

- Journaux d'équipe / invitation par courriel (V2 — le module `Team` existe déjà tout fait
  techniquement, mais les permissions multi-utilisateurs sur un journal partagé ajoutent une
  surface de conception qui diluerait l'effort avant validation du concept solo).
- Export PDF (V2, publication web uniquement en V1 — l'indexation SEO est l'objectif
  principal, le PDF n'y contribue pas).
- Constructeur drag-n-drop libre (délibérément hors scope — c'est la complexité qui a nui à
  l'adoption d'Elementor pour des non-techniciens).

## Options évaluées (brainstorming, notées /100)

| Option | Note | Retenue |
|---|---|---|
| A — Carnet Tiptap + slash-commands | 82/100 | Non |
| B — Constructeur par blocs empilés (sans point d'entrée rapide) | 88/100 | Non (seule) |
| C — Digest agrégateur façon Inde (sélection uniquement) | 80/100 | Non (seule) |
| **D — Hybride : sélection rapide (« +Ajouter ») + blocs éditables** | **95/100** | **Oui** |

D fusionne B et C après contre-interrogatoire adversarial (qwen3-max) : un constructeur de
blocs vide seul recrée une partie de la friction de l'ancien blog ; le point d'entrée rapide
depuis les pages existantes du site est ce qui rend ça vraiment plus facile qu'Elementor.

## Modération et conformité légale

Décision : **pas de pré-approbation admin** avant publication publique (ça recréerait la
friction de l'ancien blog, et n'est pas exigé légalement — voir raisonnement ci-dessous).

- **Préventif, zéro friction, automatique** : case à cocher droits d'auteur obligatoire à
  l'upload d'image ; filtre automatique léger (mots interdits, seuil anti-duplication) avant
  mise en ligne — aucune intervention humaine.
- **Publication publique immédiate**, sans validation admin préalable.
- **Réactif — bouton Signaler** sur chaque journal public : motif catégorisé (droit d'auteur
  / contenu illégal / harcèlement / vie privée / autre) + texte libre optionnel → file
  d'attente admin avec engagement de traitement rapide documenté (48h) + journalisation
  complète (date réception, analyse, action) = preuve de diligence (défense « diffusion
  innocente », Code civil du Québec / jurisprudence canadienne).
- **Droit d'auteur spécifiquement** : réutilise le système avis-et-avis déjà construit pour
  l'annuaire (`TakedownController`, `/annuaire/retrait/{slug}`, table
  `directory_takedown_requests`), étendu au Journal — transmission + conservation, jamais de
  retrait automatique (conforme Loi sur le droit d'auteur du Canada, art. 41.25-41.27).

Fondement légal complet (recherche 2026-07-11) : voir mémoire projet
`rgpd-representant-ue-analyse-2026-07-11.md` pour le raisonnement RGPD associé (hors scope
direct du Journal, touche tout le site).

## Réutilisation de code existant (DRY — rien à réinventer)

| Besoin | Brique existante | Fichier de référence |
|---|---|---|
| Brouillon privé / publié | `HasPublishedState` trait | `Modules/Core/app/Traits/HasPublishedState.php` |
| « Mes journaux » / favoris | `Bookmark` modèle polymorphe | `Modules/Core/app/Models/Bookmark.php` |
| Éditeur riche (bloc texte) | Composant Tiptap + autosave | `Modules/Editor/resources/views/components/tiptap.blade.php`, `Modules/Editor/app/Traits/HasAutosave.php` |
| Prévisualisation feuilletable | `flip-reader` (StPageFlip, générique) | `Modules/FrontTheme/resources/views/components/flip-reader.blade.php` |
| Édition front-end sans backoffice | Pattern `ArticleToolsEditor` (Livewire, gaté `@can`) | `Modules/News/app/Livewire/ArticleToolsEditor.php` |
| Retrait légal droit d'auteur | `TakedownController` (avis-et-avis) | `Modules/Blog/...` (annuaire), à étendre au Journal |
| Invitation équipe (V2 futur) | `Modules/Team` complet (tokens, expiration) | `Modules/Team/app/Models/TeamInvitation.php` |
| Autorisation propriétaire/modération | Pattern `ArticlePolicy` | `Modules/Blog/app/Policies/ArticlePolicy.php` (modèle pour `JournalPolicy`) |

## Prochaine étape

Décomposition en plan d'exécution phasé (scaffold module → modèle/migrations → blocs de
contenu → constructeur front-end → points d'intégration « +Ajouter » → page Mes journaux →
Signaler/modération → tests → déploiement), via le skill `/mcp-task`.
