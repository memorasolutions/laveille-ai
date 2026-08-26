# Lot `/actu2` du 26 août 2026 - contrôle de doublon fait, cycles à reprendre

**Pourquoi ce document** : les dix cycles `/actu2` demandés n'ont pas pu être exécutés, deux
serveurs MCP indispensables étant tombés en cours de session (voir « Blocage » plus bas). Le
contrôle de doublon, lui, est **obligatoire avant toute rédaction** et il a été fait : le
consigner évite de le refaire, et il change déjà la décision pour trois sujets sur dix.

Relevé contre les **1 097 slugs publiés** du sitemap, le 26 août 2026.

---

## Le blocage, et pourquoi rien n'a été contourné

| Serveur MCP | État | Ce qu'il porte dans `/actu2` |
|---|---|---|
| `perplexity-pro-playwright` | **mort** (aucun processus) | Étape 1, découverte de l'original. Règle 3 : `pp_search` UNIQUEMENT, jamais `WebSearch`, `WebFetch` ni `curl`. |
| `playwright` (navigateur) | outils désenregistrés | Étape 7, photo via le compte Gemini du propriétaire. Règle 14 : aucun repli, 1min.ai interdit. |

Les deux étapes sont obligatoires. Composer sans la première produirait des fiches `relais` par
défaut d'outillage plutôt que par constat ; publier sans la seconde signifierait soit aucune image,
soit l'og:image du relais, c'est-à-dire une photo de presse non licenciée (une réclamation
PicRights a déjà touché ce projet).

**Correctif** : redémarrer Claude Code. Les outils `browser_*` ne réapparaissent qu'au
redémarrage ; `ia-sync` ne remonte que des cookies, pas un serveur MCP.

---

## Ce que le contrôle de doublon a trouvé

### Trois sujets dont la décision change

**`37451` - OX Alpha / z-ai (TechCrunch).** Le site a déjà publié
`opencode-offre-ox-alpha-gratuitement-les-chiffres-sont-exacts-loperateur-reste-inconnu`, dont le
titre affirme que **l'opérateur reste inconnu**. L'article annonce précisément que cet opérateur
est z-ai. Ce n'est donc pas un doublon : c'est la **suite directe de notre propre fiche**, cas rare
et de forte valeur. Deux conséquences : la nouvelle fiche doit nommer le lien et citer l'ancienne,
et l'ancienne mérite un `--enrich` car son titre est désormais périmé.

**`37459` - Keynote Apple (Numerama).** Deux fiches voisines existent déjà :
`iphone-18-pro-batterie-5200-mah-et-autonomie-record-ca-vaut-lupgrade` et
`iphone-pliable-apple-glass-apple-prepare-t-il-sa-plus-grosse-vague-de-nouveautes`. L'événement
distinct est l'annonce d'une **date**. C'est le sujet le plus mince du lot, et une date de keynote
n'a pas de contenu propre au-delà de ce que les deux fiches disent déjà. À composer en dernier, ou
à traiter par `--enrich` de la fiche « plus grosse vague de nouveautés » plutôt qu'en fiche neuve.

**Les deux billets X (Google « 85 % des ingénieurs », Anthropic « ne plus écrire de prompts »).**
Aucun voisin publié, mais les deux relèvent du **même gabarit** : compte tiers, durée mise en avant
(« 25 minutes », « en 45 minutos »), affirmation spectaculaire attribuée à un ingénieur non nommé.
C'est exactement le motif du 23 août (`@0xMovez` / `@0xCodez`) qui aurait produit deux fiches
jumelles le même jour. Les traiter **ensemble ou pas du tout**, et confirmer chaque citation à la
source primaire avant toute rédaction : un compte tiers qui cite quelqu'un est un relais, jamais
une preuve. Le « 85 % » en particulier ne doit pas être repris sans une source nommée.

### Les autres

| Fiche | Sujet | Voisins publiés | Décision |
|---|---|---|---|
| `37498` | DRAM/NAND, capex des hyperscalers | aucun (les 6 résultats étaient des faux positifs : « drama », « mémoire de Gemini ») | rien de proche |
| Le Monde | Retard français sur NIS 2 | `souverainete-numerique-le-vrai-test-commence-le-jour-2` | voisin thématique, événement distinct : nommer la différence et lier |
| `37478` | Incident GitHub | 7 résultats, tous sur la facturation Copilot | rien de proche |
| `37481` | WebMCP | aucun | rien de proche |
| `37483` | GLM-5.3 Flash | **quatre** fiches sur GLM-5.2 | version distincte, mais ce serait la 5e fiche GLM ; la page source est un tableau de résultats, pas une annonce |
| `37455` | Qwen3.8 Flash Next | aucun sur cette version | rien de proche |

---

## Point de vigilance sur Le Monde

Le texte a été fourni collé, et il porte en tête la mention « Article réservé aux abonnés » ainsi
qu'une interdiction explicite de reproduction. La fiche ne peut donc **pas** être récoltée par
`news:source` (mur payant, jamais contourné). Elle se compose en **droit de citation** : extraits
courts, attribution dans chaque phrase, lien vers la tribune. Les paires seront des
`primary_fact` avec `source_url`, jamais des `fact` - ceux-ci sont vérifiés contre un texte
persisté qui n'existera pas. Aucun `fiche:<id>` n'a été fourni : `news:create-draft` d'abord.

---

## Runner

Le runner de production du cycle précédent (`_r29b00cf620.php`) a été **neutralisé** en fin de
session : il répond 410 avec et sans jeton. Un nouveau runner, avec un nom et un jeton neufs, est
à déposer avant toute écriture.
