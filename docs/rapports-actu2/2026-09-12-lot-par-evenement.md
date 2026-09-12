# Lot /actu2 du 2026-09-12 - carte par ÉVÉNEMENT

Reconstitué le 2026-09-12 vers 09h50 Québec (13:50 UTC) après la purge de 02h40 qui a
emporté les 13 fiches en file (ticket #2487).

## Comment le lot a été retrouvé - la méthode, plus durable que la carte

Les 18 identifiants relevés la veille étaient TOUS purgés (18/18 mesurés un par un, pas
supposés). Les URL ont été récupérées dans le fichier que `news:prune-drafts` écrit AVANT
de supprimer : `storage/app/news-prune-drafts-backup-AAAAMMJJ-024019.json` (754 entrées pour
la nuit du 12). 18 URL sur 18 retrouvées, puis `news:create-draft` a recréé chaque fiche.

**Ce backup est la porte de récupération officielle d'un lot purgé.** Il n'était documenté
nulle part dans le skill /actu2 : la doctrine « relever l'URL en même temps que l'id » reste
juste, mais elle n'est plus la SEULE issue quand on ne l'a pas fait.

Sept fiches sont revenues avec `created:false` : le flux RSS les avait déjà re-récupérées
sous un nouvel identifiant. C'est la confirmation de plus que **l'URL est l'identité stable,
jamais l'id**.

## La carte (identifiants du 2026-09-12, eux aussi périssables)

| Événement | Ticket | Fiches | Statut |
|---|---|---|---|
| OpenAI GPT-Live-1, parle et écoute en même temps | #2480 | **49141** (The Decoder, composée) + 49059 (The Register, 2e relais à consolider) | payload appliqué |
| Anthropic, rapport sur les détournements de Claude | #2481 | 49353 (Frandroid) + 49003 (BBC, volet biologique) | à composer |
| Risque existentiel de l'IA | #2482 | 49007 (BBC) + 49128 (Wired, podcast) ; 48721 déjà publiée | à composer |
| Meta Muse | #2479 | 49114 (TechCrunch) + 49199 (Siècle Digital) ; 47928 déjà publiée | à composer |
| Produits (4 fiches indépendantes) | #2484 | 49354 DeepSeek V4.1-Flash, 49184 Gemini sur Windows, 49183 Astra 200 $, 49065 OpenAI lève le pied | à composer |
| Travail et coûts | #2485 | 49355 (Presse-citron, « salaires »), 49356 (The Decoder, coût par employé), 49357 (The Register, retour en arrière coûteux) | à composer |
| Recherche et cas d'usage | #2486 | 49044 (animaux et carburant), 49092 (avocat sanctionné), 49134 (Vinyals) | à composer |

## Correspondance ancien identifiant -> URL -> nouvel identifiant

48284 -> theregister .../openai-arms-devs-with-ai-conversation-tool... -> 49059
48082 -> frandroid .../3243847_anthropic-publie-161-pages... -> 49353
48553 -> bbc .../cx2zrrpkx20o -> 49003
48239 -> bbc .../ckgwy1k42w4o -> 49007
48362 -> wired .../uncanny-valley-podcast-is-ai-actually-going-to-kill-us-all -> 49128
48342 -> techcrunch .../metas-ai-agent-muse-is-now-the-no-2-app-in-the-us -> 49114
48431 -> siecledigital .../meta-veut-confier-votre-quotidien-a-muse... -> 49199
48371 -> the-decoder .../openais-gpt-live-1-api... -> 49141
48376 -> the-decoder .../new-deepseek-model-v4-1-flash... -> 49354
48705 -> siecledigital .../google-lance-une-application-gemini-native-sur-windows -> 49184
48704 -> siecledigital .../openai-victime-du-succes-dastra... -> 49183
48745 -> 01net .../course-a-lia-openai-se-dit-pret-a-lever-le-pied... -> 49065
48457 -> presse-citron .../ia-menace-salaire-anthropic-extreme-2030 -> 49355
48380 -> the-decoder .../top-ai-spenders-cut-per-employee-costs... -> 49356
48290 -> theregister .../ai-job-cuts-could-come-with-a-costly-undo-button -> 49357
48810 -> theregister .../ai-more-likely-to-kill-animals-if-it-saves-fuel-or-money -> 49044
48787 -> theverge .../chatgpt-new-mexico-lawyer-fined-murder-appeal -> 49092
48780 -> the-decoder .../ex-deepmind-vp-vinyals-says-ai-self-improvement... -> 49134

## Contrainte de la journée

Toute fiche restée en BROUILLON ce soir sera emportée par la purge de 02h40 (06:40 UTC).
La protection réelle et immédiate est la PUBLICATION le jour même ; le mécanisme de rétention
(#2487) est en cours d'écriture et ne sera pas en production à temps pour cette nuit.

## Avancement du lot (2026-09-12, milieu de journée)

| Fiche | Événement | État |
|---|---|---|
| **49141** | GPT-Live-1 | **EN LIGNE ET VÉRIFIÉE** sur la page servie (photo, 2 auto-liens justes, 0 cadratin dans le texte lu) |
| 49059 | GPT-Live-1, 2e relais | ABSORBÉ dans 49141, aucune fiche séparée |
| **49007** | Risque existentiel | **EN LIGNE ET VÉRIFIÉE** (photo, 3 auto-liens justes, 0 cadratin dans le texte) |
| 49128 | Risque existentiel, balado Wired | ABSORBÉ dans 49007 : aucun fait daté nouveau, mais sa critique de méthode devient la citation de la fiche |
| **49114** | Meta Muse, adoption | **EN LIGNE ET VÉRIFIÉE** (photo, 3 auto-liens justes, 0 cadratin dans le texte) |
| 49199 | Meta Muse, Siècle Digital | 8 détails opérationnels nouveaux (Muse Spark, Stripe Link, opt-out d'entraînement, modèle d'affaires) - à verser par enrichissement dans la fiche 47928 déjà publiée, PAS une fiche neuve |
| **49003** | Anthropic, détournements | **EN LIGNE ET VÉRIFIÉE** (photo, 10 auto-liens justes, 0 cadratin dans le texte) |
| 49353 | Anthropic, Frandroid | matière de distillation conservée en rédaction, PAS en paire de preuve : le texte n'a pas été recoupé à la source primaire |

### Trois décisions éditoriales du lot, et leur raison

**1. Une fausse attribution évitée (49141).** The Decoder attribue la citation Yelp au « CTO Alex Levy », The Register à « Akhil Kuduvalli Ramesh, chief product officer ». Recherche indépendante : les deux personnes existent, les deux titres sont exacts, et c'est le CPO que cite le communiqué officiel. Plutôt que d'arbitrer entre deux relais, j'ai retiré l'attribution nominative et adossé le fait au communiqué Businesswire.

**2. Le nombre de pages du rapport Anthropic n'est PAS publié.** Frandroid annonce 161 pages ; une mesure antérieure de cette session avait relevé 154 à la source. Je n'ai pas revérifié aujourd'hui, donc je ne publie ni l'un ni l'autre : ce chiffre n'apporte rien au lecteur et propager un nombre douteux coûte plus qu'il ne rapporte.

**3. Les faits Frandroid ne deviennent pas des paires de preuve.** Les chiffres de distillation (Alibaba 151 M d'échanges, etc.) sont spectaculaires, mais ils viennent d'un relais dont je n'ai pas recoupé la lecture du rapport. Ils restent en rédaction, jamais en preuve : une paire `primary_fact` adossée à un relais aurait menti sur son propre nom.

## Les quatre fiches en ligne ce midi

| Fiche | URL publiée |
|---|---|
| 49141 | /actualites/openai-ouvre-aux-developpeurs-la-voix-qui-ecoute-et-parle-en-meme-temps-005-la-minute-mais-seulement-pour-la-voix |
| 49007 | /actualites/plus-de-10-de-chances-que-lia-tue-tous-les-humains-dou-sort-ce-chiffre-et-ce-que-son-auteur-ne-publie-pas |
| 49114 | /actualites/muse-grimpe-au-2e-rang-de-lapp-store-americain-avec-83-000-telechargements-moins-que-le-lancement-ia-precedent-de-meta |
| 49003 | /actualites/anthropic-publie-ce-quil-a-bloque-cinq-cas-lies-aux-armes-biologiques-et-la-nuance-que-son-propre-responsable-ajoute |

Chacune contrôlée sur la page RÉELLEMENT SERVIE, pas sur le payload envoyé : code 200, titre, og:image, puis chaque auto-lien ouvert dans son contexte rédactionnel et chaque cadratin lu avec ses 70 caractères de contexte.

### Le contrôle des cadratins, et pourquoi il se lit toujours en contexte

Les quatre pages portent EXACTEMENT six cadratins dans leur HTML, et les six sont les mêmes sur les quatre : ils vivent dans des commentaires CSS et JavaScript du gabarit (correctif de voile latérale, notes WCAG 2.2, note « Phase0 », note « CWV »). **Zéro dans le corps rédactionnel.** Un comptage brut sur le HTML aurait signalé quatre fiches fautives ; c'est le contexte qui tranche, jamais le compte.

### Reste du lot, toujours en BROUILLON ce soir

49354, 49184, 49183, 49065 (produits) - 49355, 49356, 49357 (travail et coûts) - 49044, 49092, 49134 (recherche et usages) - 49199 (Muse, à verser dans 47928 par enrichissement).

Ces douze-là tombent sous la purge de 02h40 si elles ne sont ni publiées ni retenues d'ici là. Le mécanisme de rétention (ticket #2487) est écrit et testé en local ; tant qu'il n'est pas EN PRODUCTION, la seule protection réelle reste la publication le jour même.
