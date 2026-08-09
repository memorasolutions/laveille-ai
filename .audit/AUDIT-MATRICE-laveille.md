# Matrice de couverture - audit laveille.ai

Date : 2026-08-01 (America/Toronto)
Demande : « quand je suis connecté, le site est ultra lent, normal ? On doit améliorer. »

Périmètre RESTREINT à la performance en session authentifiée. Conformément au skill, un
argument ne fait que retirer des dimensions, jamais en ajouter : les dix autres dimensions sont
donc déclarées non applicables au présent audit, avec leur raison. « Non applicable » est ici une
conclusion de cadrage, pas une omission - chacune reste auditable par un `/audit` sans argument.

| Dimension | Statut | Preuve / justification |
|---|---|---|
| performance | à faire | SEULE dimension demandée. Symptôme rapporté : lenteur ressentie une fois connecté. |
| securite-applicative | non applicable | Hors périmètre demandé. Couverte le 2026-07-22 et le 2026-07-24 (2 failles RBAC trouvées et corrigées, v1.117.21/22). |
| securite-infra | non applicable | Hors périmètre demandé. Aucun changement d'infra depuis le dernier audit. |
| qualite-code-DRY | non applicable | Hors périmètre demandé, SAUF si la cause racine de la lenteur est une duplication : dans ce cas elle remonte au titre de la performance. |
| accessibilite | non applicable | Hors périmètre demandé. |
| UX-UI | non applicable | Hors périmètre demandé. La lenteur perçue est traitée comme un fait mesurable, pas comme une impression d'interface. |
| SEO-GEO-AEO | non applicable | Hors périmètre demandé. Les pages authentifiées ne sont pas indexées. |
| conformite-Loi25-RGPD | non applicable | Hors périmètre demandé. Bannière de consentement revérifiée le jour même (fausse alerte close). |
| tests-couverture | non applicable | Hors périmètre demandé. Suite Modules/Tools verte ce matin : 393 tests, 1654 assertions. |
| dependances-CVE-licences | non applicable | Hors périmètre demandé, SAUF si une dépendance est la cause de la lenteur. |
| hygiene-serveur | non applicable | Hors périmètre demandé. Crons vérifiés le jour même : zéro résidu temporaire. |

## Gate de sortie

Le rapport final ne peut pas être rédigé tant que la ligne « performance » porte encore « à faire ».
Aucune formule du type « cet audit ne couvre pas tout » n'est autorisée : le périmètre est
explicitement restreint et documenté ci-dessus.

## Règle de méthode pour cet audit

Aucune cause n'est retenue sans mesure. Le symptôme est rapporté par le propriétaire, ce qui le
rend crédible mais pas encore chiffré. La première étape est donc de reproduire l'écart et de le
quantifier, avant toute proposition de correctif. Une hypothèse plausible et fausse coûte plus
cher qu'une absence d'hypothèse.
