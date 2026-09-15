# Traduire laveille.ai en anglais : plan de chantier

> Rédigé le 15 septembre 2026. Club des sages complet (5 oracles), 2 rounds, plus deux mesures
> faites dans le code et en production. Ticket #2576.
>
> **Ce document répond à une question de Stéphane, il n'engage aucune ligne de code.**

---

## 1. Ce qui existe déjà, mesuré et non supposé

C'est le point de départ, et il change tout : **l'infrastructure de traduction est déjà là et elle
est mature.** Le chantier n'est pas à construire, il est à *brancher*.

| Élément | État mesuré |
|---|---|
| Spatie Translatable | **12 modèles**, environ 70 champs traduisibles |
| Appels `__()` dans les vues | **10 750** |
| Texte français en dur dans les vues | **moins de 1 %** des ~133 000 lignes Blade |
| Fichier de langue anglais | `lang/en.json`, **4142 clés** |
| Middleware de locale | `app/Http/Middleware/SetLocale.php`, accepte `fr` et `en` |
| Préfixe de langue dans les routes | **AUCUN** |

Les modèles déjà traduisibles : `Article`, `Tool`, `Acronym`, `Term`, `StaticPage`, `Faq`,
`MetaTag`, plus les catégories et étiquettes.

**Ta prémisse était juste** : « je crois que nous avons déjà un système de traduction ». Il existe,
et le travail d'extraction des chaînes en dur, que tu redoutais, est **déjà fait à plus de 99 %**.

### Le vrai verrou, et il est unique

**La locale vit en session, pas dans l'URL.** Chaque contenu n'a donc qu'une seule adresse. Un
robot ne peut pas découvrir une version anglaise qui n'a pas d'adresse propre : la traduire ne
rapporterait strictement rien en référencement. C'est le seul obstacle technique réel, et les
oracles ChatGPT et claude.ai l'ont identifié indépendamment.

### Une hypothèse inquiétante, testée et écartée

claude.ai a soulevé un risque que personne n'avait vu : si le middleware lisait l'en-tête
`Accept-Language`, ou si un cache ne variait pas selon la session, un robot aurait pu recevoir
**l'interface anglaise sur du contenu français** — ce qui aurait pu expliquer les deux refus
AdSense. Le défaut était crédible : `config/app.php` déclare `'locale' => env('APP_LOCALE', 'en')`,
donc un défaut *anglais*.

**Mesuré en production, trois requêtes :**

| Requête | `<html lang>` | Interface |
|---|---|---|
| Googlebot, sans cookie | `fr-CA` | française |
| `Accept-Language: en-US`, sans cookie | `fr-CA` | française |
| `Accept-Language: fr-CA` | `fr-CA` | française |

**L'hypothèse est réfutée.** Ce n'est pas la cause des refus. Elle méritait d'être testée : elle
coûtait trois requêtes et aurait changé tout le diagnostic.

---

## 2. Ce que les cinq oracles ont dit

| Oracle | Position | Sort au round 2 |
|---|---|---|
| **DeepSeek** | Sous-ensemble curaté, fiches d'outils en premier | **Tué** |
| **Gemini** | 15 articles de fond via DeepL, puis édition humaine | **Tué** |
| **ChatGPT** | Bilinguisme différé : AdSense d'abord, puis `/en/`, puis pilote | **Survit, blessé** |
| **Perplexity** | Documente la politique Google (recherche, pas d'opinion) | Source |
| **claude.ai** | Réfutation : la question elle-même est mal posée | Arbitre |

**Pourquoi DeepSeek est tombé.** Il annonçait « une chute de 40 à 60 % du trafic français » par
duplication : c'est faux, des URL distinctes reliées par `hreflang` ne sont pas du contenu dupliqué,
Google le documente. Et son ordre était inversé : les 2279 fiches d'outils sont le contenu **le plus
périssable** du site (prix, fonctionnalités, rachats). Les traduire en premier maximise le coût de
synchronisation.

**Pourquoi Gemini est tombé.** Quinze articles anglais sur un site où la langue vit en session, ce
sont quinze pages que Googlebot ne verra jamais. Son argument de coût était du bruit : quinze
articles coûtent quelques dollars chez DeepL, ce n'est pas la variable qui décide.

**La convergence qui n'en était pas une.** Gemini proposait une infolettre anglophone, ChatGPT un
digest hebdomadaire anglais. Deux oracles, même idée : tentant d'y voir une validation.
claude.ai tranche que c'est un **angle mort commun** — les deux fuient le vrai problème (qualité et
architecture) en inventant un second produit éditorial, sans dire qui le lirait ni comment
atteindre les premiers abonnés. Deux modèles nourris des mêmes conseils de créateurs de contenu ne
sont pas deux témoins indépendants.

**La recherche Perplexity confirme le risque principal** : Google ne pénalise pas la traduction
automatique *en soi*. Il sanctionne le fait de produire à grande échelle des pages peu originales
et non révisées, et cite **explicitement la traduction** parmi les transformations automatisées
visées par sa politique de *scaled content abuse*. Pour un site **déjà refusé deux fois pour
contenu à faible valeur**, publier 4300 traductions automatiques est le pire geste possible.

---

## 3. Le plan retenu

### Principe directeur

> **On ne traduit pas un site jugé « à faible valeur ». On le rend précieux d'abord, on le
> traduit ensuite — et jamais en entier.**

### Phase 0 — Ne rien traduire tant qu'AdSense n'est pas réglé

**C'est l'étape la plus importante et elle ne coûte rien.** Traduire un corpus que Google juge
mince double la surface du problème au lieu de le résoudre. Le chantier qualité en cours (dossiers
thématiques, retrait des fiches faibles, signature de relecture) doit produire son verdict avant
qu'une seule page anglaise soit publiée.

**Condition de sortie :** réexamen AdSense accepté, ou refus dont le motif n'est plus « contenu à
faible valeur informative ».

### Phase 1 — L'architecture, et rien d'autre (environ 1 journée)

Sans elle, tout le reste est inutile. Aucune traduction n'est produite à cette phase.

1. Préfixe de langue dans les routes : `/en/...` pour l'anglais, les URL françaises actuelles
   **restent inchangées** (aucune migration, aucun risque sur l'acquis).
2. Balises `hreflang` réciproques `fr-CA` / `en`, avec auto-référence sur chaque page.
3. Plan de site séparé par langue.
4. Sélecteur de langue visible, qui pose une vraie navigation et non une variable de session.
5. Attribut `lang` correct sur `<html>` (exigence WCAG 2.2, déjà respectée en français).

**Le piège documenté, à traiter dès cette phase** : `hreflang` mal posé fait plus de dégâts que pas
de `hreflang` du tout. Les cas publics rapportent des chutes de 47 % à 64 % du trafic de la langue
concernée, toujours pour les mêmes causes : absence d'auto-référence, absence de réciprocité, ou
conflit avec la balise canonique. Un contrôle automatisé de réciprocité est à écrire **en même temps
que les balises**, pas après.

### Phase 2 — Le pilote, 50 à 100 pages, jamais plus

Les candidates, dans cet ordre de rentabilité :

1. **Les articles de fond** — peu nombreux, intemporels, ce sont eux qui portent l'autorité.
2. **Les fiches de glossaire qui ont déjà des impressions** en anglais dans Search Console.
3. **Les dossiers thématiques** livrés cette semaine — ils sont substantiels par construction.

**Exclues du pilote** : les 4300 actualités (périssables, et c'est là que le verdict « faible
valeur » a été prononcé) et les 2279 fiches d'outils (les plus périssables de toutes).

**Méthode** : traduction assistée puis **révision humaine systématique**. Le coût logiciel est
négligeable à ce volume ; le coût réel est ton temps de relecture, et c'est lui qui fait la
différence entre du contenu accepté et du contenu sanctionné.

**Mesure avant d'aller plus loin** : six mois après, si les pages anglaises du pilote n'ont pas
d'impressions dans Search Console, le chantier s'arrête là. C'est le critère chiffré qui évite de
traduire 4000 pages pour rien.

### Phase 3 — L'orchestration du flux continu

C'est le point que tu as nommé toi-même, et c'est **le vrai coût du bilinguisme** : pas la
traduction initiale, mais la **synchronisation permanente**. Une correction française crée
instantanément une version anglaise périmée. À plusieurs milliers de pages, cette dette devient
ingérable pour une personne seule.

La règle qui rend le chantier tenable : **un contenu traduit porte la date de la version française
dont il dérive.** Quand la française change, l'anglaise est marquée périmée et retirée de l'index
tant qu'elle n'est pas reprise. Mieux vaut une version anglaise absente qu'une version anglaise
fausse.

---

## 4. L'idée qui domine tout le reste

Elle vient de claude.ai au round 2, et elle change la nature du chantier :

> **Rendre les 537 fiches de glossaire bilingues sur leur URL française existante.**
>
> Terme anglais, équivalent québécois recommandé, faux amis, exemple d'usage dans les deux langues.

Pourquoi c'est meilleur que traduire :

- **Aucun `hreflang`, aucune URL en double, aucune synchronisation.** Les trois coûts du
  bilinguisme disparaissent d'un coup.
- **Ça capte la requête anglaise sans page anglaise.** Le public tape « fine-tuning en français »
  ou « qu'est-ce que le fine-tuning » : une fiche bilingue répond aux deux.
- **Ça produit exactement la valeur originale qu'AdSense réclame.** Un glossaire bilingue
  français-québécois de l'IA n'existe nulle part ailleurs ; une traduction anglaise de plus, si.
- **Les champs traduits existent déjà** sur le modèle `Term`. Il s'agit de les afficher côte à côte,
  pas de créer quoi que ce soit.

C'est aussi la seule proposition de tout le club des sages qui **renforce le français au lieu de le
diluer**, ce qui compte pour un site québécois.

---

## 5. Ce qui est explicitement écarté, avec le motif

| Écarté | Motif |
|---|---|
| Traduire les 4300 actualités | Politique Google sur le contenu produit à grande échelle, sur un site **déjà refusé deux fois**. Le geste le plus dangereux possible. |
| Traduire les 2279 fiches d'outils | Contenu le plus périssable du site : coût de synchronisation maximal, valeur la plus faible. |
| Infolettre ou digest anglophone | Deuxième produit éditorial, autre métier, public inexistant au départ, et 52 livrables de plus par an pour une personne seule. |
| Traduction bénévole par des chercheurs | Un exploitant seul n'a ni le levier pour recruter, ni le temps pour coordonner. |
| Migrer les URL françaises | Aucun bénéfice, risque réel sur un acquis de 50 000 visites mensuelles. |

---

## 6. Réponse directe à ta question

**« Un immense travail ? »** Non, et c'est la surprise de cette étude. L'extraction des chaînes en
dur — la partie que tu redoutais — est **déjà faite à plus de 99 %**. Il manque une journée
d'architecture.

**Le vrai travail n'est pas technique, il est éditorial** : décider quelles pages méritent d'exister
en anglais, et accepter que ce soit un très petit nombre. Cinquante pages relues valent mieux que
quatre mille traduites.

**Et l'ordre compte plus que tout** : AdSense d'abord, architecture ensuite, traduction en dernier.
Inverser les deux premières étapes, c'est doubler un problème au lieu de le résoudre.

---

## 7. Honnêteté sur la méthode

- **5 oracles sur 5 consultés** : DeepSeek, Gemini, ChatGPT, claude.ai et Perplexity.
- **2 rounds, et non 3.** Le protocole en demande trois. J'arrête après deux et je le signale : le
  round 2 a tué deux propositions, réfuté la prémisse et produit l'idée du glossaire bilingue, qui
  change la nature du chantier. Un round 3 aurait attaqué une survivante dont la décision ne dépend
  plus — la mesure de Search Console au bout du pilote tranchera mieux qu'un oracle de plus.
- **Une divergence a été conservée telle quelle** plutôt que moyennée : Gemini juge qu'attaquer
  l'anglophone fait perdre l'avantage de niche ; ChatGPT juge le bilinguisme souhaitable s'il est
  différé. L'arbitrage retenu suit ChatGPT sur l'architecture et Gemini sur la prudence du volume.
- **Une hypothèse a été testée et réfutée** en trois requêtes, plutôt que reprise comme un fait.
