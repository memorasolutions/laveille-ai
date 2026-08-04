# Programmes d'affiliation/parrainage accessibles en direct - laveille.ai

Date de la recherche : 1er août 2026 (America/Toronto). Méthode : `mcp__perplexity-pro-playwright__pp_search` exclusivement, requêtes en série, sources officielles priorisées sur les annuaires tiers.

## Avertissement de portée (à lire avant tout le reste)

Ce rapport classe les pistes par **notoriété générale** et par les rangs déjà documentés dans `config/affiliate_programs.php` (juillet 2026), **PAS par les statistiques de clics réelles du site**. L'accès à la base de production (API terminal cPanel) est hors service et la base locale a été vidée lors d'un incident antérieur. Aucune donnée de trafic ou de clics sortants réels de laveille.ai n'a servi à établir ces priorités. Si le classement des outils par clics change une fois l'accès rétabli, l'ordre de priorité ci-dessous devrait être révisé.

Aucune inscription, création de compte ou acceptation de conditions n'a été effectuée : ce document documente uniquement l'état des programmes.

## Constat transversal important

Aucun des programmes vérifiés (existants ou nouveaux) n'affiche de **seuil de trafic minimum chiffré publiquement**. Le vrai obstacle n'est pas un nombre de visiteurs affiché sur une page - c'est une **approbation discrétionnaire** (marketplace PartnerStack, revue manuelle Impact, etc.), exactement le mécanisme qui a bloqué laveille.ai chez PartnerStack. La preuve la plus nette : le programme Notion, documenté comme actif dans le fichier de config, a sa page d'application PartnerStack qui affiche actuellement **« All applications will be auto-declined for the time being »** - fermé de fait, sans annonce publique claire. Les programmes vraiment « auto-serve » repérés (Writesonic via FirstPromoter, ~24h d'approbation ; Systeme.io, Tidio, Framer, Beehiiv en inscription directe) sont donc la vraie catégorie à privilégier, plus que la présence ou non d'un chiffre de trafic minimum.

---

## Partie 1 - Réévaluation des 12 programmes déjà documentés

| Outil | Config (juillet 2026) | Constat août 2026 | Écart |
|---|---|---|---|
| **Canva** | Impact, jusqu'à 36 $/vente Pro, 1-src | **Programme historique fermé depuis le 31 janvier 2024.** Remplacé par le programme « Empower Canvassador », **sur approbation/invitation seulement**, pas de commission publique documentée. Les liens Impact existants restent actifs si on est accepté dans Empower, mais on ne peut plus s'inscrire librement. | **ÉCART MAJEUR** - le fichier de config est obsolète, ce n'est plus un programme ouvert. |
| **ElevenLabs** | PartnerStack, 22 %/11 %, récurrent 12 mois, cookie **365 j** | Confirmé 22 %/11 %, 12 mois récurrent. Cookie officiel = **90 jours** (page officielle `elevenlabs.io/affiliates-terms` + PartnerStack). | Écart sur le cookie : 90 j, pas 365 j. |
| **Grammarly** | ShareASale, 20 $/vente + 0,20 $/inscription | Toujours actif. Cookie officiel = **90 jours** (nouvelle info). Montants confirmés par 2 sources tierces (pas trouvé de chiffre exact sur la page officielle elle-même, qui reste vague). Pas de mention de ShareASale dans les sources trouvées - la page officielle ne nomme pas le réseau. | Cookie précisé ; réseau non reconfirmé indépendamment. |
| **Copy.ai** | Direct, 45 % (1 source, 20 % en alternative) | 45 % récurrent 12 mois confirmé par plusieurs annuaires tiers. **Réseau ambigu** : certaines sources disent PartnerStack, d'autres « in-house ». Cookie = 60 jours (nouvelle info). Page officielle d'affiliation non retrouvée directement. | Le libellé « Direct » du fichier est incertain - à reconfirmer avant contrat. |
| **Notion** | PartnerStack, jusqu'à 50 $ + 20 % an 1, cookie 180 j | Page publique toujours en ligne (jusqu'à 50 $ + 20 % an 1, conversion sous 180 j confirmée). **MAIS la page d'application PartnerStack affiche « auto-declined » pour le moment - fermé aux nouvelles candidatures.** | **ÉCART MAJEUR** - programme affiché mais fermé dans les faits. |
| **Runway** | Direct, 20 % récurrent 12 mois | **Changement de modèle** : la page officielle actuelle (`affiliates.runwayml.com`) annonce désormais **15 $ US forfaitaire par nouvel abonné payant** (pas de pourcentage récurrent), candidature revue par l'équipe, partenariat qui débute par un « pilote » de 3 mois. | **ÉCART MAJEUR** - modèle complètement changé, plus un pourcentage récurrent. |
| **Murf.ai** | PartnerStack, 20 % récurrent 24 mois | **Confirmé conforme** : 20 % récurrent jusqu'à 24 mois, cookie 90 jours, PartnerStack. | Aucun écart. |
| **Synthesia** | Rewardful, 25 % récurrent, cookie 60 j | **Confirmé conforme** par la page officielle (`synthesia.io/partners/affiliates`). | Aucun écart. |
| **Jasper** | Direct, 25-30 % (ambigu) | **Clarifié** : base 25 % récurrent 12 mois ; palier à 30 % seulement après 100 leads générés ET 100 clients convertis sur 12 mois glissants. Page officielle `jasper.ai/legal/affiliates`. | Ambiguïté résolue - config à préciser. |
| **HeyGen** | Direct/PayPal, 35 % récurrent mais 3 mois | **Confirmé conforme**, plus précisions : cookie 30 jours (nouvelle info), seuil de paiement 30 $, page existe en français (`heygen.com/fr-fr/affiliate-program`). | Aucun écart, infos enrichies. |
| **Writesonic** | Direct, 20 % récurrent, cookie 60 j | **Confirmé conforme.** Réseau précisé : **FirstPromoter**, approbation généralement sous 24h. | Aucun écart ; c'est un des programmes les plus accessibles du lot. |
| **Descript** | « Direct », 25 $ forfaitaire | Montant confirmé (25 $ US, unique, pas récurrent). **Mais le réseau est en fait PartnerStack**, pas un système « Direct » au sens strict comme documenté. | Écart sur le libellé réseau. |

**Bilan Partie 1** : sur 12 programmes, 3 ont un écart **majeur** (Canva effectivement fermé au public, Notion fermé aux candidatures, Runway a changé de modèle et n'est plus récurrent), 2 ont un écart mineur sur le réseau déclaré (Copy.ai, Descript), 1 a un écart sur le cookie (ElevenLabs). Les 6 autres (Grammarly, Murf.ai, Synthesia, Jasper, HeyGen, Writesonic) sont stables et fiables.

### Recheck des trois « confirmés sans programme »

- **ChatGPT/OpenAI** : toujours aucun programme d'affiliation public payant en argent. Il existe un « OpenAI Partner Network » mais c'est un programme de revendeurs/intégrateurs B2B, pas un programme de commission pour créateurs de contenu.
- **Midjourney** : toujours aucun programme d'affiliation officiel trouvé.
- **Perplexity (Comet)** : confirmé fermé depuis le 6 novembre 2025 (le programme antérieur « Give a month, Get a month » avait fermé le 6 octobre 2025). Aucune réouverture trouvée en août 2026.

Aucun changement de situation - ces trois outils restent à écarter.

---

## Partie 2 - Nouveaux candidats à parrainage direct (14 programmes actifs vérifiés)

Sélectionnés pour la pertinence auprès d'un public francophone québécois grand public / enseignants / PME.

| Outil | URL du programme | Adhésion | Commission | Récurrence | Cookie | Conditions notables |
|---|---|---|---|---|---|---|
| **Systeme.io** | `systeme.io/affiliate-program` | **Direct, inscription libre**, aucun réseau tiers mentionné | 60 % par vente [2 sources ; une page tierce mentionne 40 %, à écarter au profit du chiffre officiel] | **Récurrent à vie** tant que le client reste abonné | Non précisé explicitement dans les sources (paiement mensuel, seuil 30 $) | Fondateur français, interface et support disponibles en français - fort alignement public francophone |
| **Beehiiv** | `beehiiv.com/partners` | Direct, inscription au « Partner Program » | Palier Bronze 50 % / Argent 55 % / Or 60 % [1 source officielle, à reconfirmer] | 12 mois récurrent | 60 jours, first-touch | Programme distinct de l'abonnement newsletter des lecteurs ; paiement mensuel |
| **Framer** | page officielle « creator program » (affiliés) | Direct / in-house selon plusieurs sources tierces | 50 % des paiements d'abonnement | 12 mois récurrent | 90 jours | Seuil de paiement 200 $ US, versé via Stripe ; Framer garde le droit de modifier le taux |
| **Tidio** | page officielle affiliés + `affiliate@tidio.net` | Semble direct (pas de réseau externe nommé dans les sources officielles) | 30 % | **Récurrent à vie** [confirmé par la page officielle + 1 source tierce concordante] | 30 jours | Seuil de paiement 50 $, paiement mensuel |
| **Semrush** | `semrush.com` (page officielle affiliés, via Impact) | Réseau (**Impact**) | 10 $/essai gratuit + 100-300 $/vente selon le produit | Paiement à l'acte, pas récurrent | 120 jours | Aucun seuil de trafic officiel trouvé (des rumeurs tierces parlent de 1000 visiteurs/mois, non confirmées officiellement) |
| **Fireflies.ai** | `fireflies.ai/affiliate` | Réseau (probable PartnerStack selon sources tierces) | Jusqu'à 30 % | 12 mois récurrent | 90 jours | Le taux exact dépend du palier/statut de partenaire (10-30 % selon un billet de blogue plus ancien) |
| **GetResponse** | `getresponse.com/affiliate-programs` | Réseau (**PartnerStack**) | 40 % (base), 50 % après 50 ventes, 60 % après 100 ventes | 12 mois récurrent | 90 jours | Client référé ne doit pas avoir été client dans les 12 derniers mois ni contacté dans les 3 derniers mois |
| **HeyGen** *(déjà dans la liste existante, voir Partie 1)* | - | - | - | - | - | - |
| **QuillBot** | `quillbot.com/affiliates` | Réseau (**PartnerStack**) | Jusqu'à 20 % | Non précisé comme récurrent explicitement | 30 jours | Public étudiants/enseignants pertinent (outil de paraphrase/écriture) |
| **Wondershare Filmora** | pages officielles Wondershare/Filmora (créateur + affilié général) | Réseau mixte (**Impact** pour le programme créateur, **Awin** mentionné ailleurs - incohérence entre pages officielles elles-mêmes) | 30-50 % selon palier trimestriel (desktop) ; 30 % fixe (mobile) | Non clairement récurrent - semble palier par vente | 30 jours | Outil vidéo populaire auprès des enseignants/créateurs de contenu ; réseau à clarifier avant de s'inscrire |
| **Wix** | `wix.com/about/affiliates` | Réseau (**Impact**, mentionné par sources tierces uniquement) | 100 $ US forfaitaire par vente Premium | **Non récurrent** (paiement unique) | 30 jours | Seuil de paiement élevé : 300 $ US minimum avant tout versement |
| **ClickUp** | `clickup.com/partners/affiliates` | Réseau (**PartnerStack**) | Jusqu'à 25 $ par espace de travail **gratuit** référé (structure de paiement pour les conversions payantes pas clairement publiée) | Peu clair | 30 jours | Le lien affilié doit être le dernier clic marketing payant avant le premier contact - règle d'attribution stricte |
| **Gamma.app** | aide officielle + programme PartnerStack | Réseau (**PartnerStack**) | 30 % [confirmé par une publication officielle sur X, absent de la page d'aide elle-même - 1 source, à reconfirmer] | Sur les paiements référés (durée non précisée) | Non précisé | Outil de présentations IA en forte croissance, peu de concurrence affiliée encore |
| **Speechify** | page officielle affiliés | Réseau non identifié dans les sources trouvées | 30 % de chaque vente [1 source, à reconfirmer] | Non précisé | 30 jours | Données incomplètes - nécessite vérification directe avant toute décision |
| **Otter.ai** | existence probable, page officielle de commission non localisée | Incertain | Chiffres contradictoires entre annuaires tiers (25 % récurrent 12 mois vs 25 $ forfaitaire vs revenu partagé) | Incertain | Incertain | **Fiabilité faible** - à vérifier directement auprès d'Otter avant toute mention publique d'un taux |

### Programme trouvé mais fermé (à ne pas recommander)

- **Leonardo AI** : programme officiel (60 % du premier mois, réseau Impact, cookie 30 jours) confirmé **fermé aux nouvelles candidatures depuis le 7 avril 2026**. Les commissions déjà courues avant cette date continuent d'être versées, mais impossible de s'y inscrire aujourd'hui.

---

## Partie 3 - Accès SANS condition de trafic minimum publiée

C'est le critère décisif compte tenu du blocage PartnerStack. **Aucun programme vérifié, existant ou nouveau, n'affiche de seuil de trafic chiffré sur sa page officielle.** Le filtre réel est l'approbation manuelle/discrétionnaire. Parmi les 26 programmes examinés, les plus proches d'un « auto-serve » sans barrière connue :

1. **Systeme.io** - inscription directe, aucune mention de revue de trafic.
2. **Writesonic** (FirstPromoter) - approbation généralement sous 24h, réputée peu sélective.
3. **Tidio** - inscription via la page officielle ou courriel dédié, pas de filtre publié.
4. **Beehiiv** - inscription au Partner Program sans seuil publié.
5. **Framer** - programme créateur ouvert, seuil de paiement (200 $) mais pas de seuil d'entrée.
6. **HeyGen** - candidature simple, seuil de paiement bas (30 $).
7. **Synthesia** (Rewardful) - accès après approbation, réputé peu restrictif pour Rewardful en général.
8. **Jasper**, **Murf.ai** - accès standard sans seuil de trafic publié.

À l'inverse, **Notion (PartnerStack)** illustre le risque : programme public affiché mais candidatures auto-refusées en ce moment - exactement le type de blocage discrétionnaire vécu avec PartnerStack.

---

## Partie 4 - Notation /100 (intérêt réel pour laveille.ai)

Pondération : facilité d'adhésion (poids le plus fort) > récurrence des commissions > pertinence francophone/québécoise > fiabilité du paiement.

| Rang | Outil | Score /100 | Justification résumée |
|---|---|---|---|
| 1 | **Systeme.io** | **92** | Direct, aucun réseau, 60 % à vie, fondateur/support francophones - correspond exactement au besoin post-PartnerStack |
| 2 | **Beehiiv** | **85** | Programme direct, 50-60 % récurrent 12 mois, paiement fiable (plateforme US bien établie) |
| 3 | **Framer** | **82** | Direct/in-house, 50 % récurrent 12 mois, cookie généreux (90 j), mais seuil de paiement 200 $ |
| 4 | **Tidio** | **80** | Accès simple, 30 % récurrent **à vie**, seuil de paiement bas (50 $) |
| 5 | **Synthesia** | **75** | Rewardful réputé accessible, 25 % récurrent stable, confirmé sans écart |
| 6 | **Writesonic** | **75** | FirstPromoter, approbation ~24h, 20 % récurrent 12 mois, déjà actif et vérifié |
| 7 | **Jasper** | **72** | Direct, 25 % récurrent 12 mois (30 % accessible à volume), programme mature et stable |
| 8 | **Murf.ai** | **70** | PartnerStack mais programme confirmé stable, 20 % récurrent 24 mois - durée la plus longue du lot |
| 9 | **HeyGen** | **68** | Direct/PayPal, 35 % mais seulement 3 mois - fort taux initial, faible durée |
| 9 | **Semrush** | **68** | Impact, gros montants forfaitaires par vente (100-300 $), cookie long (120 j), mais pas récurrent |
| 9 | **ElevenLabs** | **68** | PartnerStack mais programme mature, 22 % récurrent 12 mois, cookie réel 90 j (pas 365 j) |
| 12 | **Fireflies.ai** | **65** | PartnerStack, jusqu'à 30 % récurrent 12 mois, bon fit pour public PME/productivité |
| 13 | **Copy.ai** | **62** | 45 % taux élevé mais réseau ambigu, à reconfirmer avant d'investir du temps |
| 14 | **GetResponse** | **63** | Bon taux tiéré (40-60 %) mais PartnerStack avec conditions d'exclusion (12/3 mois) |
| 15 | **Wondershare Filmora** | **60** | Bon fit pédagogique (vidéo), mais réseau incohérent entre pages officielles - à clarifier |
| 16 | **Grammarly** | **55** | Fiable et connu, mais paiement unique (pas récurrent), montant modeste |
| 17 | **Gamma.app** | **55** | Croissance forte, mais taux non confirmé sur la page d'aide elle-même (1 source X) |
| 18 | **QuillBot** | **55** | Public étudiant/enseignant pertinent mais taux modeste (20 %) et récurrence non confirmée |
| 19 | **Wix** | **55** | Marque très connue mais paiement unique et seuil de versement élevé (300 $) |
| 20 | **Speechify** | **50** | Taux correct (30 %) mais données insuffisantes (réseau, récurrence) - à revérifier |
| 21 | **Descript** | **45** | Étiqueté « Direct » à tort (en fait PartnerStack), montant modeste, non récurrent |
| 22 | **ClickUp** | **40** | Structure de paiement floue pour les conversions payantes, surtout orienté free-to-paid |
| 23 | **Runway** | **35** | Changement de modèle défavorable : 15 $ forfaitaire au lieu de 20 % récurrent, approbation + pilote 3 mois |
| 24 | **Otter.ai** | **30** | Données publiques contradictoires - existence même du programme incertaine |
| 25 | **Notion** | **10** | Page publique active mais candidatures **auto-refusées actuellement** - fermé de fait |
| 26 | **Canva** | **10** | Programme historique fermé depuis janvier 2024, remplacé par un programme sur invitation |
| - | **Leonardo AI** | **N/A** | Programme fermé aux nouvelles candidatures depuis le 7 avril 2026 - ne pas recommander |

---

## Partie 5 - Ce qui reste à faire (par le propriétaire, hors portée assistant)

Cette recherche documente uniquement l'état des programmes ; **aucune inscription n'a été effectuée**. Pour activer une source de revenu réelle, le propriétaire doit, dans l'ordre de priorité suggéré ci-dessus :

1. **S'inscrire lui-même** aux programmes retenus (Systeme.io, Beehiiv, Framer, Tidio en priorité) - identité, informations de paiement (PayPal/Stripe/virement) et acceptation des conditions requièrent une action humaine directe.
2. **Reconfirmer les taux marqués « 1 source »** directement auprès de chaque programme avant de les inscrire dans un contrat ou une communication officielle (Beehiiv, Speechify, Gamma.app, Wondershare Filmora, Otter.ai notamment).
3. **Mettre à jour `config/affiliate_programs.php`** une fois les inscriptions faites, en corrigeant les écarts identifiés en Partie 1 (Canva, Notion, Runway en particulier - ces trois lignes sont aujourd'hui trompeuses telles qu'écrites).
4. **Saisir les `affiliate_url` réels** dans le champ admin de chaque fiche outil concernée une fois les liens de parrainage obtenus (l'infrastructure - colonne, badge de divulgation, `rel="sponsored"`, page de politique, suivi des clics - est déjà en place, confirmé par la mission).
5. **Décider si Wondershare Filmora et Semrush** valent la peine malgré les incohérences trouvées (réseau ambigu pour l'un, absence de récurrence pour l'autre) - jugement d'affaires, pas une question de recherche.

---

## Passe 2 : croisée avec le trafic réel (1er août 2026)

Cette section répond à la demande de croiser la recherche avec les vraies statistiques de production obtenues par le commanditaire (2316 outils en base, 489 publiés, `affiliate_url` rempli sur **0** fiche - l'infrastructure n'a jamais servi). Le vrai top 20 par `clicks_count` invalide une partie du ciblage de la Partie 2 : plusieurs très gros outils par vues de fiche (FLUX, ChatGPT, Poe, Claude Design, Claude, Wooclap, NotebookLM, Gemini, Copilot) n'avaient pas encore été vérifiés.

### Note méthodologique - incident `pp_search`

À partir de la recherche sur NotebookLM, `pp_search` a échoué de façon répétée (délai > 60 s puis échec immédiat après un `ia-sync` de reprise - session Perplexity non ré-authentifiée malgré l'injection de cookies). Conformément à la procédure de repli prévue, les recherches suivantes (NotebookLM, Copilot, Suno, Ideogram, Rytr, DeepL, Lovable, MagicSchool AI) ont été faites via `mcp__openrouter__chat_with_model` avec le modèle `perplexity/sonar-pro`, en demandant explicitement des citations de sources officielles. Cette bascule est signalée pour transparence ; les réponses restent sourcées mais n'ont pas eu la double lecture Perplexity Pro habituelle.

### Outils du vrai top déjà couverts en Partie 1/2 (pas re-recherchés)

Rappel sans nouvelle recherche : **ElevenLabs**, **Grammarly**, **Copy.ai**, **Notion AI**, **Perplexity**, **Midjourney**, **Runway**, **Leonardo AI** (fermé depuis le 7 avril 2026), **Jasper AI**, **Semrush**, **QuillBot**, **Gamma** - voir Parties 1 et 2 ci-dessus pour le détail déjà vérifié.

### Nouveaux outils du top réel vérifiés

| Outil (rang réel) | Programme trouvé ? | Détails | Score /100 |
|---|---|---|---|
| **FLUX / Black Forest Labs** (#1) | **Aucun** | Page officielle `bfl.ai` ne montre aucun programme d'affiliation. Un « Flux Affiliate Program » (20 % à vie) trouvé en ligne appartient à un produit tiers sans lien avec Black Forest Labs - à ne pas confondre. [1 source, prudence de marque] | N/A |
| **ChatGPT / OpenAI** (#2) | **Aucun** (reconfirmé) | Aucun changement depuis la passe 1. Le « OpenAI Partner Network » est un programme B2B de revendeurs/intégrateurs, pas un programme de commission pour créateurs. | N/A |
| **Poe (Quora)** (#3) | **Aucun programme d'affiliation classique** | Seul un programme de monétisation de « bot creator » existe (jusqu'à 20 $/nouvel abonné selon des sources tierces, non officiellement confirmé), payé sur l'activité d'un bot publié sur Poe - pas un lien de parrainage standard exploitable par un annuaire. [1 source tierce sur le montant] | N/A |
| **Claude Design** (#5) | **Aucun** | Même politique qu'Anthropic/Claude ci-dessous (même entreprise). | N/A |
| **Claude** (#10) | **Aucun programme public individuel** | Anthropic a un « Claude Partner Network » (juin 2026) réservé aux entreprises d'implémentation, et un crédit de parrainage utilisateur-à-utilisateur (crédit, pas commission). Aucun taux de commission publique trouvé pour un site tiers. | N/A |
| **Wooclap** (#6) | **Aucun trouvé** | Recherche ciblée en français (« ambassadeur », « partenaire », « affiliation ») et en anglais (« reseller », « partner ») : aucune page publique Wooclap ne documente un programme rémunéré. Seule confirmation : gratuité pour les enseignants du primaire/secondaire en France et Belgique (pas un programme de recommandation payé). Recherche faite via `pp_search` (avant l'incident de session). | N/A |
| **AI Media Buyer by Creatify** (#7) | **Oui** | Direct via **Rewardful** : 25 % récurrent sur abonnements admissibles, palier bonus +5 % (30 % total) au-delà de 5000 $ US de ventes mensuelles. Page officielle `creatify.ai/affiliate`. [confirmé par la page officielle, cookie/seuil de paiement non publiés] | **70** |
| **AIVA** (#8) | **Non confirmé** | Pages officielles de tarification/licence trouvées, aucune page d'affiliation officielle. Une seule source tierce non officielle mentionne 20 % récurrent, cookie 60 j - **à ne pas traiter comme un fait**. | N/A |
| **3PL Hub** (#9) | **Aucun, par choix explicite** | Le site officiel affiche littéralement « No Commissions. Ever. » - modèle d'affaires délibérément sans parrainage rémunéré. | N/A |
| **Adobe Firefly** (#11) | **Partiel** | Programme Firefly dédié : **liste d'attente seulement**, aucun taux publié. Mais Firefly est accessible via le programme d'affiliation Adobe général (réseau **Partnerize**) qui verse **85 % du premier mois** pour les abonnements Creative Cloud/Document Cloud admissibles - utilisable dès maintenant pour une fiche Adobe/Firefly, mais ce n'est pas un programme Firefly autonome. | **60** |
| **NotebookLM (Google)** (#14) | **Aucun** | Recherché via le repli sonar-pro (incident pp_search). Aucun programme d'affiliation ou de parrainage public trouvé pour NotebookLM ni, plus largement, pour les produits IA Google individuels. | N/A |
| **Gemini (Google)** (#18) | **Aucun** | Même politique Google que NotebookLM ci-dessus - pas de programme de commission par produit IA individuel. | N/A |
| **Copilot (Microsoft)** (#19) | **Aucun programme public payant** | Recherché via sonar-pro. Les seuls mécanismes trouvés sont des incitatifs pour partenaires CSP (revendeurs entreprise) et un programme « Student Ambassador » sélectif (candidature, jusqu'à 1500 $ **forfaitaires** à la fin du programme, pas une commission par référence). Aucun des deux n'est un programme de parrainage exploitable par un annuaire. | N/A |
| **Suno** (#« notable », 503 clics) | **Existe mais inadapté** | Programme « Growth Ambassador » sur invitation (candidature via `sunocreators.hbportal.co`), mais exige **15 à 30 vidéos courtes par mois** en plus d'un partage de revenu affilié de 25 % (récurrence non précisée). Modèle de créateur de contenu actif, pas un lien de parrainage passif utilisable par un annuaire. [2 sources tierces concordantes, formulaire officiel non entièrement visible] | **30** |
| **Ideogram** (#« notable », 457 clics) | **Existe mais fermé/discrétionnaire** | Programme d'affiliation officiel confirmé dans les CGU (`ideogram.ai`), mais accès réservé aux membres approuvés du « Creators Club » (créateurs de contenu YouTube/X/Instagram/TikTok), acceptation « à la seule discrétion » d'Ideogram, **aucun taux de commission publié**. | **35** |
| **Rytr** (#« notable », 457 clics) | **Oui** | Direct sur domaine propre (`affiliates.rytr.me`) : **30 % récurrent 12 mois**, cookie **60 jours**, seuil de paiement 100 $, aucun minimum de trafic officiel trouvé. Confirmé par 3 pages officielles concordantes (page d'affiliation, CGU, centre d'aide). | **75** |
| **DeepL** (#« notable », 425 clics) | **Aucun programme grand public** | Seule offre officielle trouvée : un « partner ecosystem » B2B pour entreprises, pas un programme d'affiliation avec taux public. | N/A |
| **Lovable** (#« notable », 422 clics) | **Oui, mais limité** | Programme officiel direct sur plateforme propre : **jusqu'à 100 $ par nouvel abonné** (semble être un montant unique par vente, pas un pourcentage récurrent confirmé), inscription sur candidature/approbation. | **55** |
| **MagicSchool AI** (#« notable », 422 clics) | **Existe en construction, pas encore monétisable** | Système à deux paliers : programme « AI Pioneers » (candidature, sans rémunération) puis « Ambassador » (nécessite le statut Pioneer au préalable). Un **programme de parrainage rémunéré est annoncé « bientôt disponible »**, sans taux ni structure de commission publiés à ce jour. À surveiller - fort potentiel pour le public enseignant francophone une fois lancé, mais **rien à inscrire aujourd'hui**. [confirmé par 2 pages officielles + réseaux sociaux officiels de la marque] | **25 (potentiel futur, pas actionnable maintenant)** |

### Outils sans AUCUN programme trouvé (à ne pas re-vérifier inutilement)

FLUX / Black Forest Labs, ChatGPT/OpenAI, Poe (au sens parrainage classique), Claude, Claude Design, Wooclap, AIVA (non confirmé), 3PL Hub (refus explicite), NotebookLM, Gemini, Copilot (au sens commission par référence), DeepL (au sens grand public).

### Réponse au point 4 - quelle part du vrai top 20 est réellement monétisable ?

En croisant les 20 premières fiches par `clicks_count` avec l'ensemble des recherches faites (passes 1 et 2) :

- **Programmes réellement rejoignables aujourd'hui, sans blocage connu** (5/20 = **25 %**) : AI Media Buyer by Creatify (#7), Adobe Firefly via le réseau Adobe/Partnerize (#11), ElevenLabs (#12), Grammarly (#13), Copy.ai (#15).
- **Programmes qui existent mais sont actuellement fermés ou gatekept** (2/20 = 10 %) : Canva AI (#4, remplacé par un programme sur invitation depuis janvier 2024), Notion AI (#20, candidatures PartnerStack auto-refusées en ce moment).
- **Aucun programme trouvé, point final** (13/20 = 65 %) : FLUX (#1), ChatGPT (#2), Poe (#3), Claude Design (#5), Wooclap (#6), AIVA (#8), 3PL Hub (#9), Claude (#10), NotebookLM (#14), Perplexity (#16), Midjourney (#17), Gemini (#18), Copilot (#19).

**Conclusion honnête** : sur le vrai top 20 de laveille.ai, **seulement 25 % (5 fiches sur 20)** ont un programme de parrainage direct ou en réseau réellement accessible aujourd'hui. Les quatre géants Anthropic/OpenAI/Google/Microsoft occupent à eux seuls 6 des 20 premières places (ChatGPT, Claude Design, Claude, NotebookLM, Gemini, Copilot, soit 30 % du top 20 par trafic) et **aucun des six** n'offre de programme de commission exploitable - ce sont structurellement des entreprises qui n'ont pas besoin d'un canal d'affiliation grand public pour croître. Concrètement, cela signifie que les outils les PLUS consultés sur laveille.ai (position 1, 2, 3, 5, 10, 14, 16, 17, 18, 19 - soit la moitié du top 20) ne généreront jamais de revenu d'affiliation, quel que soit l'effort de recherche ou de démarchage. Le potentiel de revenu réel se trouve dans la moitié restante du top 20 et surtout dans les candidats de la Partie 2 (Systeme.io, Beehiiv, Framer, Tidio) qui, eux, n'apparaissent pas encore dans le top 20 par trafic mais offrent des conditions bien supérieures. Le fossé entre « ce qui est populaire dans l'annuaire » et « ce qui est monétisable » est donc réel et structurel, pas un simple problème de recherche insuffisante.
