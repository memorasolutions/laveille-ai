# Module « vérification » - marquer les fiches qui démontent une affirmation

**Version** : v1.202.0 - **Date** : 2026-08-21 (America/Toronto) - **Demande** : fondateur
(« quand on brise une fakenews, il devrait y avoir une indication… pour que le site soit ultra
crédible, et pourquoi pas un tueur de fausses nouvelles »).

## 1. Le problème constaté

Trois fiches publiées les 20 et 21 août examinaient une affirmation virale et concluaient qu'elle
était fausse (citation prêtée à Sam Altman, propos recomposés de Jensen Huang, docufiction Galika
présentée comme une expérience réelle d'IA). Rien ne le signalait : il fallait lire la fiche en
entier pour comprendre qu'il s'agissait d'une vérification, alors que c'est précisément
l'information que le lecteur pressé - et le moteur de réponse - devrait obtenir en premier.

## 2. Ce qui est livré

| Élément | Emplacement | Note |
|---|---|---|
| Vocabulaire des verdicts | `NewsArticle::FACT_CHECK_VERDICTS` | Source UNIQUE : libellé, teinte, phrase explicative, note. Ajouter un verdict = une entrée, rien d'autre. |
| Colonnes | `fact_check_verdict`, `fact_check_claim`, `fact_check_source` | Nullables, migration réversible. |
| Accès modèle | `hasFactCheck()`, `factCheckVerdict()`, `scopeFactChecked()` | Ni la vue ni le JSON-LD ne lisent la constante directement. |
| Badge | `<x-news::fact-check-badge>` | Un composant, deux formats (`compact` false/true). Rien du tout sans verdict. |
| Fiche publique | `news::public.show` | Badge AVANT la signature éditoriale : de quoi il s'agit, puis qui en répond. |
| Cartes de liste | `news::public.partials.article-card` | Pastille compacte, au-dessus du titre. |
| Page publique | `/verifications` → `PublicNewsController::verifications()` | Active le filtre et délègue à `index()`. En-tête, titre et fil d'Ariane conditionnels : jamais une seconde vue. |
| Balisage machine | `JsonLdService::claimReview()` | Un seul par page. |
| Porte d'écriture | clé `fact_check` de `news:apply --payload` | Liste blanche stricte, `--enrich` pour une fiche déjà publiée, `null` pour retirer. |
| Page Méthodologie | `fronttheme::methodologie` | Explique le mécanisme, invite à contredire, renvoie vers `/verifications`. |
| Tests | `Modules/News/tests/Feature/FactCheckModuleTest.php` | 19 tests. |

## 3. Les cinq verdicts

`contenu_synthetique` (contenu généré par une IA, présenté comme authentique) ·
`citation_inexacte` · `attribution_erronee` · `presentation_trompeuse` · `contexte_manquant`.

Cinq et pas douze, pour qu'un lecteur pressé comprenne du premier coup d'oeil. Le premier a été
ajouté par la passe adversariale, qui a nommé l'angle mort : sur un site consacré à l'IA, le cas le
plus probable n'est pas la citation mal recopiée, c'est l'image ou la vidéo fabriquée puis
présentée comme un document authentique.

## 4. Décisions et arbitrages

**ClaimReview est posé alors que Google l'a déprécié - volontairement.** Vérifié le 2026-08-21 :
le retrait du résultat enrichi a été annoncé le 12 juin 2025 et la page Search Central porte
l'avertissement. Le balisage reste posé parce que Fact Check Explorer et l'API Fact Check Tools le
consomment toujours, et parce que c'est la seule forme structurée qui dit « cette page examine
telle affirmation et conclut ceci ». Il n'est PAS posé en espérant un badge dans les résultats de
recherche : ce serait poursuivre une fonctionnalité morte. Le docblock de la méthode le dit, pour
que personne ne réintroduise la croyance inverse.

**Le verdict qualifie l'affirmation, jamais la personne.** Règle éditoriale gravée dans le skill
`/actu2` (section « Le verdict de vérification »), autant par justesse - se tromper de bonne foi
n'est pas mentir - que par prudence juridique en droit québécois. Le `claim` décrit ce qui est
affirmé, jamais qui l'affirme.

**Le droit de nous contredire est affiché.** La page Méthodologie invite explicitement à signaler
une vérification erronée, et s'engage à corriger EN LE DISANT sur la fiche plutôt qu'à effacer en
silence. Un site qui juge les affirmations des autres doit accepter d'être jugé.

**Refusé, avec motif** : fusionner `presentation_trompeuse` et `contexte_manquant` (la distinction
est réelle : contenu authentique présenté autrement ≠ information exacte mais incomplète) ;
remplacer « citation inexacte » par « citation fausse » (un libellé plus fort augmente le risque
juridique, il ne le réduit pas) ; construire un mécanisme de rétractation outillé (l'effacement par
`fact_check: null` suffit tant que le volume est faible - à revoir si le nombre de vérifications
croît).

## 5. Piège rencontré, à ne pas réintroduire

Première écriture, les trois colonnes voyageaient dans `$updates` de `NewsApplyCommand`. Or tout
payload dont `$updates` est non vide efface `structured_summary`, le résumé composé de la fiche
(règle voulue depuis le 2026-08-17). Poser un verdict sur une fiche déjà rédigée aurait donc
détruit son résumé en silence - exactement le défaut déjà rencontré avec `related_tool_slugs`. Les
colonnes ont désormais leur propre panier `$factCheckUpdates`, appliqué à part, et deux tests
tiennent la règle dans les deux sens.

**Réflexe à garder** : toute nouvelle clé de payload se pose la question « contenu rédactionnel ou
méta-donnée posée après coup ? ». Une méta-donnée ne passe jamais par le panier du contenu.

## 6. Contraste

Les teintes ont été mesurées, pas choisies à l'oeil : `#A32222` plafonnait à 6,94:1 et `#8A5A00` à
5,53:1 sur le fond réel du badge, tous deux sous le seuil AAA de la charte. Valeurs retenues :
**`#9B1F1F`** (7,47:1) et **`#6E4700`** (7,64:1). Toute retouche se remesure avant d'être posée.
