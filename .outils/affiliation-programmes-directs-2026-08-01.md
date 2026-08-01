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
