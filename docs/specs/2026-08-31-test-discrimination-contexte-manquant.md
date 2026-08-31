# Test de discrimination : contexte manquant, grave ou accessoire

Document produit le 31 août 2026 (America/Toronto).
Auteur : MEMORA solutions (info@memora.ca, https://memora.solutions).

## 1. La faille constatée

Le verdict `contexte_manquant` (`NewsArticle::FACT_CHECK_VERDICTS`) se définit ainsi : « L'information
est exacte, mais il lui manque un élément sans lequel on la comprend mal. » Cette définition est
circulaire : elle exige de savoir quel élément est « indispensable » à la compréhension, sans dire
comment le reconnaître. Deux rédacteurs différents, devant la même fiche, peuvent légitimement en
tirer deux réponses opposées, puisque « indispensable » n'est ancré à rien de vérifiable - c'est un
jugement de goût déguisé en critère. Un verdict public qui repose sur un jugement de goût n'est pas
défendable si on nous le conteste.

Aucune règle existante ne distingue un contexte manquant qui INVERSE le sens compris d'un détail
simplement omis par compression éditoriale normale - chose que fait toute rédaction, à chaque
fiche, sans que cela mérite un verdict.

## 2. Le test retenu : la bascule de croyance

**Question unique, à réponse exclusive (oui/non), qui route directement vers le verdict :**

> Sans l'élément manquant, un lecteur qui comprend l'affirmation selon son SENS ORDINAIRE (pas une
> lecture pointilleuse mot à mot) en retire-t-il une croyance qui devient FAUSSE une fois cet
> élément connu ?
>
> - **OUI** -> `contexte_manquant` s'applique : le contexte manquant est grave, il fait dire à
>   l'affirmation quelque chose de faux tant qu'il reste absent.
> - **NON** -> `contexte_manquant` ne s'applique pas pour ce motif : le détail est accessoire,
>   c'est de la compression éditoriale normale. Un autre verdict peut s'appliquer, pour une autre
>   raison, mais pas celui-ci.

Ce test imite dans son esprit le test à deux branches déjà validé ailleurs dans l'écosystème
Memora pour le même besoin (une question unique, à réponse exclusive, qui tranche sans laisser de
zone grise) - notamment le test à branches du skill `/politiques` pour qualifier un régime
applicable, et le test binaire déjà en production dans ce module même pour la preuve `fact` de
`news:apply` (une citation est-elle, oui ou non, une sous-chaîne exacte du texte source ?). Le
principe commun : remplacer un jugement de magnitude (« est-ce important ? ») par un jugement de
VALEUR DE VÉRITÉ (« est-ce que ça devient faux ? »), qui admet une réponse vérifiable plutôt qu'une
opinion.

### Procédure en cinq pas, pour que le test soit reproductible entre deux personnes

Le risque d'un test à une seule question posée dans l'abstrait est qu'il retombe, dans la pratique,
dans le même flou que « indispensable ». La procédure suivante force à écrire un artefact concret à
chaque pas, ce qui rend le résultat comparable entre deux rédacteurs :

1. Écrire l'affirmation TELLE QU'ELLE CIRCULE, dans les mots qu'un lecteur pressé en retiendrait -
   pas une reformulation savante.
2. Écrire, en une phrase, LA croyance concrète et vérifiable qu'un lecteur ordinaire forme à la
   lecture de cette affirmation - une chose qu'il pourrait répéter telle quelle à quelqu'un d'autre.
3. Ajouter l'élément manquant candidat à l'affirmation de l'étape 1.
4. Réécrire la croyance du lecteur, en une phrase, avec cet élément ajouté.
5. Comparer les phrases des étapes 2 et 4 : est-ce la MÊME croyance, seulement plus précise, ou
   deux croyances CONTRADICTOIRES (l'une vraie, l'autre fausse) ?
   - Contradictoires -> réponse OUI au test -> `contexte_manquant`.
   - Même croyance, enrichie d'un détail -> réponse NON -> pas de verdict pour ce motif.

Le test ne s'applique que si l'affirmation de départ est elle-même établie comme ayant réellement
circulé sous cette forme. S'il manque déjà une base factuelle à l'étape 1 (la citation elle-même
n'est pas retrouvée, par exemple), la prémisse du test est absente et un autre verdict est en
cause (`citation_inexacte`), jamais `contexte_manquant`.

## 3. Éprouvé sur quatre cas réels

Deux cas sont des vérifications intégralement publiées sur laveille.ai, relues en entier pour ce
document (fiches 32 et 35 de la base). Un cas est une variante construite à partir de la même fiche
réelle, avec un second élément réellement omis mais accessoire, pour éprouver la SPÉCIFICITÉ du
test (est-ce qu'il sur-déclenche sur toute omission ?), pas seulement sa sensibilité. Le quatrième
cas s'appuie sur le dossier documenté du déploiement du module (`memory/
module-verification-factcheck-2026-08-21.md`), pour éprouver que le test n'empiète pas sur un
verdict voisin.

### Cas 1 - positif, réel, publié (fiche 32, verdict réel : `contexte_manquant`)

- **Affirmation circulante** : « La nouvelle superintelligence artificielle d'Ilya Sutskever
  serait sur le point de changer l'IA pour toujours, avec un modèle attendu en août 2026. »
- **Étape 2, croyance sans l'élément** : « SSI, l'entreprise de Sutskever, s'apprête à sortir en
  août 2026 un modèle qui va bouleverser l'IA - une annonce sérieuse, qui vient de l'entreprise. »
- **Élément manquant candidat** : l'échéance d'août vient d'une remarque de l'investisseur Gavin
  Baker dans un balado du 4 août 2026 ; SSI elle-même n'a publié ni modèle, ni date, ni produit.
- **Étape 4, croyance avec l'élément** : « Un investisseur EXTERNE a spéculé, dans un balado, que
  SSI pourrait sortir un modèle en août ; l'entreprise elle-même n'a rien annoncé ni confirmé. »
- **Étape 5** : les deux croyances sont contradictoires - la première fait croire à une annonce
  officielle et imminente, la seconde révèle une spéculation tierce non confirmée. Un lecteur qui
  répéterait la première phrase dirait quelque chose de faux une fois l'élément connu.
- **Verdict du test : OUI -> `contexte_manquant`.** Concorde avec le verdict réellement publié.

### Cas 2 - négatif, limite, construit sur la MÊME fiche réelle (spécificité du test)

- **Élément manquant candidat, différent, également vrai et également absent de l'affirmation
  virale** : Ilya Sutskever a cofondé OpenAI en 2015 et l'a quittée en 2024 avant de fonder Safe
  Superintelligence.
- **Étape 2** : identique au cas 1 - « SSI s'apprête à sortir un modèle qui va bouleverser l'IA
  en août 2026. »
- **Étape 4, avec cet élément** : « SSI, l'entreprise du cofondateur d'OpenAI Ilya Sutskever,
  s'apprête à sortir un modèle qui va bouleverser l'IA en août 2026. »
- **Étape 5** : même croyance, seulement enrichie d'un détail biographique. Rien ne devient faux :
  un lecteur qui ignorait le passé de Sutskever chez OpenAI ne croyait rien de FAUX à ce sujet, il
  ignorait un fait annexe.
- **Verdict du test : NON -> pas de verdict pour ce motif.** Comportement attendu : un détail vrai
  mais omis reste un choix éditorial normal. Ce cas est celui qui manquait le plus à la doctrine
  d'origine : la preuve que le test ne sur-déclenche pas sur la première omission venue.

### Cas 3 - négatif, réel, publié, verdict voisin (fiche 35, verdict réel : `citation_inexacte`)

- **Affirmation circulante** : une citation attribuée à un « ingénieur SpaceXAI », reprenant des
  noms d'agents précis (« Grok Bot », « agent chef de cabinet »).
- Le problème n'est pas un élément manquant à une affirmation par ailleurs exacte : la citation
  elle-même, sous cette forme, ne se retrouve nulle part dans les quelque quarante-cinq minutes de
  propos transcrits et passés en revue pour la fiche. La prémisse du test (une affirmation établie,
  à laquelle il manquerait un élément) n'est pas remplie : il n'y a rien à comparer aux étapes 2 et
  4, puisque l'affirmation de départ elle-même n'est pas établie sous cette forme.
- **Verdict du test : prémisse absente -> `contexte_manquant` exclu par construction.** Le cas
  relève d'un autre mécanisme. Le test ne confond pas les deux.

### Cas 4 - négatif, réel, documenté (dossier de déploiement, fiche 34655, verdict réel :
`citation_inexacte`)

- Citation prêtée à Sam Altman, retenue au déploiement du module comme cas fondateur de
  `citation_inexacte` (voir `docs/specs/2026-08-21-module-verification.md`, section 1, et
  `memory/module-verification-factcheck-2026-08-21.md`).
- Même raisonnement structurel que le cas 3 : une citation non retrouvée sous la forme attribuée
  n'est pas une affirmation exacte à laquelle il manquerait un élément - la prémisse du test est
  absente dès l'étape 1.
- **Verdict du test : prémisse absente -> `contexte_manquant` exclu.** Confirme, sur un second cas
  indépendant, que le test respecte la frontière avec `citation_inexacte`.

## 4. Ce que ce test ne fait PAS

Il ne redéfinit pas la frontière entre `contexte_manquant` et `presentation_trompeuse` (une
présentation qui déforme activement du contenu authentique reste un mécanisme distinct, non
rouvert ici). Il ne remplace pas le jugement du rédacteur sur CE QUI constitue un élément manquant
candidat à tester - il ne fait que trancher, une fois un candidat identifié, s'il est grave ou
accessoire. Et il n'élimine pas le besoin d'une preuve dans la fiche elle-même (règle 2 du skill
`/actu2`, section « Le verdict de vérification ») : le test ne dispense jamais de démontrer, dans
le texte publié, la croyance fausse et l'élément qui la corrige.

## 5. Application

Le test s'applique au moment de renseigner `fact_check.verdict = "contexte_manquant"` dans le
payload `news:apply` (voir skill `/actu2`, section « Le verdict de vérification ») et, une fois le
module étendu au blogue, au moment de renseigner le verdict d'une entrée de
`blog_article_verifications`. Il n'est pas codifié en validation automatique (la comparaison de
croyances à l'étape 5 requiert un jugement humain sur le sens, pas une règle mécanique) : c'est un
protocole de rédaction, documenté ici comme référence unique, et rappelé - jamais recopié - par les
skills `/actu2` et `/article`.
