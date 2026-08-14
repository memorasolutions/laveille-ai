# Actus - zéro copie du texte source

Document de conception. 13 août 2026 (America/Toronto). MEMORA solutions.

Tous les faits de code cités ont été vérifiés le jour même dans le dépôt et en production.
Aucun élément ne vient de mémoire.

---

## 1. Problème

La colonne `news_articles.description` contient le texte intégral de l'article source, jusqu'à
5000 caractères, en anglais. Ce texte est republié tel quel dans le JSON-LD de chaque page
publique : une fiche qui affiche 255 mots en français publie 959 mots de texte d'éditeur dans ses
données structurées.

Décision du propriétaire, non négociable : le texte source peut servir en mémoire à produire notre
résumé, mais ne doit jamais être conservé sur le serveur, ni pour les nouveaux articles ni pour les
anciens. Seul notre résumé est conservé et publié. Les boutons « Voir l'article original » et
« Lire en français » restent.

## 2. Objectifs et non-objectifs

Objectifs : supprimer toute persistance du texte source ; le faire transiter uniquement en mémoire ;
garantir qu'aucune fiche publiée n'ait un corps vide ; publier notre résumé dans le JSON-LD ;
purger l'existant sans recréer le problème dans la table de journal.

Non-objectifs, traités ailleurs : l'enrichissement éditorial des fiches et le changement de sources
(design distinct, en attente de décision) ; la correction de la fuite vers les fournisseurs de
modèles (#1819) ; l'attribution de l'auteur (#1818).

## 3. Faits de code établis

| Point | Emplacement | Fait |
|---|---|---|
| Écriture fautive principale | `RssFetcherService.php:129-130` | Écrase `description` par `Str::limit($texte, 5000)` |
| Écriture initiale | `RssFetcherService.php:74` | `description` reçoit le blurb court du flux |
| Même écrasement, voie admin | `ReprocessArticlesCommand.php:87-88` | Idem |
| Consommation par le résumé | `AiSummaryService.php:45-66` | Reçoit le texte, prompt « base-toi UNIQUEMENT sur le texte fourni » |
| Points d'appel | `FetchNewsCommand.php:95,196,314,396` | Passent `$article->description` au service |
| Publication du texte source | `JsonLdService.php:189,250` | `articleBody` et `wordCount` dérivés de `description` |
| Journalisation | `NewsArticle.php:28` + `LogsActivityStandard.php` | `description` figure dans `$activitylogFields` |
| Schéma | migration `2026_03_29_000000:30` | `text()` NOT NULL sans défaut : écrire `''`, jamais `NULL` |

Mesures du 13 août 2026 : 6015 fiches publiées, 3641 en noindex, 2374 indexables.
**51 fiches (0,85 %) n'ont pas de résumé structuré exploitable** - c'est le volume exact du cas
« corps vide » à traiter, et il est marginal.

## 4. Architecture cible

### 4.1 Le texte ne transite plus par une colonne

C'est la décision centrale. Sans elle, vider la colonne ferait générer chaque fiche à partir du
titre seul, **sans lever d'erreur et sans faire échouer un test** : le mode d'échec du système est
silencieux.

- `RssFetcherService` **retourne** le texte extrait dans sa structure de résultat au lieu de
  l'écrire. `description` reçoit `''` à la création ; la logique d'écrasement des lignes 129-130
  disparaît.
- `FetchNewsCommand` tient le texte dans une variable locale et le passe **en argument** au service.
  Aucune propriété du modèle ne sert de véhicule.
- `AiSummaryService::scoreAndSummarize()` prend le texte source en paramètre explicite. Le prompt
  garde sa consigne de fidélité mais lit ce paramètre.
- `ReprocessArticlesCommand` : même règle ; s'il traite une fiche existante, il re-télécharge la
  source et la garde en mémoire.

**Conséquence à retenir : la fenêtre en mémoire est le seul moment où une relance sur un autre
modèle est possible.** Une fois l'exécution terminée, toute reprise exige de re-télécharger.

### 4.2 Porte de qualité avant persistance

Une fiche n'a qu'une seule chance d'être bonne. Contrôles, dans l'ordre :

1. **Structure** : JSON valide, toutes les clés attendues présentes.
2. **Vacuité** : aucun champ obligatoire vide ou réduit à des espaces.
3. **Langue** : le contenu est en français.
4. **Longueurs** : bornes du contrat respectées par champ.
5. **Non-copie** : aucun segment du résumé ne reproduit littéralement une longue suite du texte
   source au-delà de la citation prévue.

En cas d'échec : relance sur le modèle suivant de la cascade, avec le même texte encore en mémoire.
Si la cascade est épuisée : **la fiche n'est pas publiée**. Ce refus est une issue normale et
journalisée, pas une exception. « Ne rien publier » doit être un comportement attendu du pipeline.

### 4.3 Le JSON-LD publie notre résumé

`JsonLdService` construit `articleBody` à partir du rendu textuel de notre résumé structuré, et
recalcule `wordCount` sur cette base. Règle de contrôle : **le JSON-LD d'une fiche ne doit contenir
aucune phrase absente de la page visible.**

### 4.4 Aucune page publique au corps vide

Les 51 fiches sans résumé structuré affichent aujourd'hui le texte source comme corps. Une fois la
colonne vidée, elles deviendraient des pages vides, en production, sans erreur.

Traitement, dans cet ordre :
1. **Régénérer** ces 51 fiches **avant** la purge - c'est la fenêtre qui se referme.
2. Toute fiche dont la régénération échoue est **dépubliée** (retirée du fil, `noindex`), jamais
   supprimée de la base.
3. **Garde-fou permanent** : une fiche sans résumé exploitable ne peut pas être servie publiquement
   avec un corps vide, quelle qu'en soit la cause. Vérifié par un test.

### 4.5 Cascades d'affichage à recâbler

Chacune de ces cascades finit aujourd'hui sur `description`. Aboutir à une chaîne vide sur une page
publique est un défaut, pas un cas limite acceptable.

| Emplacement | Nouvel ordre |
|---|---|
| `show.blade.php:17` (meta description) | `meta_description`, sinon `summary`, sinon premier paragraphe du résumé, sinon phrase de repli configurée |
| `show.blade.php:113` (résumé pour agents) | `summary`, sinon rendu du résumé structuré, sinon catégorie plus date |
| `show.blade.php:256` (temps de lecture) | Calcul sur le résumé publié, jamais sur le texte source |
| `show.blade.php:442-444` (corps) | Résumé structuré uniquement ; le repli disparaît |
| `article-card.blade.php:116,175` | `summary`, sinon accroche du résumé, sinon catégorie plus date |
| `NewsArticle.php:179` (recherche) | Retirer `description` des champs cherchés |
| `NewsArticle.php:209` (extrait) | `summary`, sinon extrait du résumé, sinon mention non vide |
| `NewsArticle.php:273` (partage admin) | Résumé structuré |
| `JournalBlockService.php:61` | Résumé structuré ; les blocs déjà créés gardent leur instantané |

## 5. Procédure de purge

L'ordre est contraignant. Inverser deux étapes recrée le problème ailleurs.

0. **Sauvegarde complète de la base**, vérifiée restaurable.
1. **Export hors site** des textes sources et de leurs métadonnées, sur disque, avant toute
   destruction. C'est aussi ce qui rend possible la mesure du taux d'erreur (#1817).
2. **Régénérer** les 51 fiches sans résumé (section 4.4).
3. **Déployer le recâblage** (sections 4.1 à 4.5) et vérifier par les tests que le pipeline produit
   toujours des fiches complètes.
4. **Retirer `description`** de `$activitylogFields` (`NewsArticle.php:28`). Cette étape précède
   obligatoirement les suivantes, sinon l'opération se journalise elle-même.
5. **Expurger la table `activity_log`** : ne jamais supprimer de ligne - c'est une trace d'audit.
   Remplacer uniquement la valeur textuelle du champ dans la charge utile JSON par un marqueur
   daté. Opération idempotente. Contrôle : aucune entrée ne contient plus de texte d'éditeur.
6. **Purger la colonne** par requête directe (`DB::table(...)->update(['description' => ''])`),
   jamais par `save()` ni `update()` sur modèle, qui déclencheraient les événements Eloquent et
   écriraient 6015 copies du texte dans le journal.
7. **Vérifier** : `SELECT COUNT(*) FROM news_articles WHERE description != ''` retourne 0.

**Point de non-retour : l'étape 6.** Après elle, seule la sauvegarde de l'étape 0 et l'export de
l'étape 1 permettent de revenir.

## 6. Tests

- Le texte extrait est retourné par le service et `description` reste `''`.
- `scoreAndSummarize` reçoit le texte par son paramètre et ne lit jamais la colonne.
- La porte de qualité refuse un résumé incomplet, en anglais, ou trop court.
- Échec du premier modèle, succès du second : la fiche est publiée.
- Cascade épuisée : aucune fiche créée, un journal écrit.
- Le JSON-LD ne contient aucune phrase absente de la page visible.
- Une fiche sans résumé exploitable n'est jamais servie avec un corps vide.
- Chaque cascade d'affichage retourne une valeur non vide quand `summary` et `description` sont
  vides.
- L'expurgation du journal est idempotente et ne change pas le nombre de lignes.
- Après purge simulée, aucune entrée de journal ne contient de texte d'éditeur.

## 7. Risques

| Risque | Parade |
|---|---|
| Baisse du volume publié si la porte rejette souvent | Assumé : ne rien publier vaut mieux qu'une fiche vide. Mesurer le taux de rejet dès la première semaine. |
| `articleBody` plus court qu'avant | Conforme à l'intention ; c'était du texte d'éditeur, sa disparition est le but. |
| Un point d'écriture oublié réintroduit le texte | Revue de code : aucune affectation à `description` hors chaîne vide. Test dédié. |
| Purge lancée avant recâblage | Interdit par l'ordre de la section 5 ; l'étape 3 est bloquante. |

## 8. Critères d'acceptation

1. `SELECT COUNT(*) FROM news_articles WHERE description != ''` retourne 0.
2. Une exécution du pipeline crée une fiche avec `description = ''` et un résumé non vide.
3. Le JSON-LD d'une fiche publiée ne contient aucune phrase absente de la page visible.
4. `activity_log` conserve le même nombre de lignes, sans texte d'éditeur, et l'expurgation a bien
   eu lieu après le retrait du champ journalisé.
5. Aucune page publique ne s'affiche avec un corps vide ; le garde-fou est testé.
6. Les 51 fiches sans résumé ont été régénérées ou dépubliées, aucune supprimée.
7. La sauvegarde et l'export hors site existent et sont vérifiés avant l'étape 6.
