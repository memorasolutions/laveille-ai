<!-- github-maison:start -->
## GitHub maison MEMORA - où pousser ce projet (laveille = EXCEPTION double remote)

Ce projet est versionné dans le **github maison** (Forgejo local sur le Pi) ET sur **GitHub** (qui porte la CI/CD de déploiement). NE PAS traiter laveille comme un projet forge-only.

- Organisation / client forge : `laveille`
- Dépôt forge : `la-veille-de-stef-v2`
- **`origin` = GitHub** (`https://github.com/memorasolutions/laveille-ai.git`) - **c'est lui qui DÉCLENCHE la CI GitHub Actions et le déploiement en prod**. Le déploiement PASSE par `git push origin master`.
- **`forge` = Forgejo Pi** (`http://100.66.177.50:3000/laveille/la-veille-de-stef-v2.git`) - miroir/backup local, aucune CI.

**Règle de push pour CE projet** : pousser vers les DEUX à chaque livraison : `git push origin master` (CI + déploiement) PUIS `git push forge master` (miroir). Ne jamais mettre Forgejo en `origin` ici (casserait la CI). Le MCP `github-maison` (gm_push --remote=forge) sert pour le miroir ; `gm_whereami` en cas de doute.
<!-- github-maison:end -->

<!-- constructeur-prompts:frontiere-gabarits -->
## Constructeur de prompts - frontière des gabarits (anti-dérive, club des sages 2026-08-20)
Un « gabarit » / pré-prompt = un ÉTAT PRÉ-REMPLI du wizard existant (un `SavedPrompt`, qui porte déjà les `spaces`). JAMAIS un gabarit qui déclare ses PROPRES champs / son propre moteur de templating : ce serait la refonte « phrase-à-trous par métier » déjà essayée puis abandonnée le 2026-08-07 (règle projet : ne pas refondre le wizard sans demander). La version « faible » (état pré-rempli) glisse vers la version « forte » (champs propres) en quelques sprints si la frontière n'est pas tenue. Toute demande d'aller vers la version forte = décision EXPLICITE de Stéphane, jamais une dérive silencieuse. La galerie de gabarits reste CURÉE par l'équipe (flag `is_official`), ZÉRO UGC public (Loi 25, pas de modération). Design : docs/specs/2026-08-20-bibliotheque-pre-prompts-design.md.
<!-- /constructeur-prompts:frontiere-gabarits -->

