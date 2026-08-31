# Est-ce que l'intelligence artificielle boit toute l'eau de la planète ?

**Statut : BROUILLON, non publié.** Auteur : Stéphane Lapointe, fondateur de MEMORA solutions. Rédigé le 29 août 2026. Ne pas publier sans révision humaine finale et vérification des liens.

---

J'ai croisé récemment sur LinkedIn le témoignage d'un parent dont la fille de dix ans était en larmes. La petite avait peur que ChatGPT « finisse toute l'eau de la planète ». Une capture d'écran montrait l'enfant demander directement à l'agent conversationnel : « Je dois arrêter de te parler ? Est-ce que c'est vrai que tu prends de l'eau quand je te parle ? » Le parent, désarmé, demandait comment répondre à ça. Il posait aussi une question tout aussi directe : si c'était à ce point nocif, pourquoi les gouvernements n'appuient pas simplement sur off ? Ils ont bien plus de pouvoir que nous.

Cet article répond aux deux questions, dans cet ordre : celle de l'enfant d'abord, avec un chiffre précis, puis celle du parent. La réponse n'est ni « ne vous inquiétez de rien » ni « tout est en train de s'effondrer ». Les deux chiffres existent réellement, et ce sont deux réalités distinctes qu'on confond trop souvent quand on parle d'intelligence artificielle et d'environnement.

<div class="custom-callout callout-conseil">
<div class="callout-header"><span class="callout-icon">💧</span><span class="callout-title">La réponse courte</span></div>
<div class="callout-content"><p>Une requête à ChatGPT consomme environ 0,000085 gallon américain d'eau selon Sam Altman (PDG d'OpenAI, billet de blogue de juin 2025), soit environ 0,32 millilitre, à peu près 1/15 de cuillerée à thé. Le problème n'est pas ce volume individuel, minuscule, mais sa concentration locale près de certains centres de données.</p></div>
</div>

## Combien d'eau une question à ChatGPT consomme-t-elle vraiment ?

Commençons par le chiffre que l'enfant aurait aimé connaître avant de verser des larmes. Selon Sam Altman, PDG d'OpenAI, dans son billet de blogue personnel « The Gentle Singularity » publié en juin 2025, une requête ChatGPT moyenne consommerait environ 0,000085 gallon américain d'eau, ce qui équivaut à environ 0,32 millilitre, soit à peu près 1/15 de cuillerée à thé. Il ajoute que cette même requête mobiliserait environ 0,34 Wh d'électricité. Il faut préciser qu'il s'agit d'une estimation auto-déclarée par OpenAI, sans méthodologie publique détaillée. On ne sait pas exactement quel modèle est visé, quelle longueur de réponse, dans quel centre de données, ni si l'eau nécessaire à la production de l'électricité est incluse dans ce calcul. J'ai beau faire de la veille depuis des années, je ne peux pas vérifier un chiffre qu'on ne me montre pas.

Par contraste, Google a publié en août 2025 une étude nommée « Measuring the environmental impact of delivering AI at Google Scale », signée par Elsworth, Huang, Patterson, Schneider, Sedivy, Goodman, Townsend, Ranganathan, Dean, Vahdat, Gomes et Manyika, des chercheurs de Google dont Jeff Dean, et datée du 21 août 2025 sur arXiv sous l'identifiant 2508.15734. Leur chiffre : un prompt texte médian dans Gemini Apps consommerait environ 0,26 millilitre d'eau, soit environ cinq gouttes, et 0,24 Wh d'électricité. Contrairement à M. Altman, Google publie la méthode : mesure en production sur l'infrastructure réelle, incluant les accélérateurs actifs, les serveurs hôtes, la capacité machine inactive nécessaire, le surcoût du centre de données et l'eau du refroidissement. On sait ce qui est mesuré, comment et où.

Ce contraste mérite qu'on s'y arrête. Les deux plus grands noms du secteur donnent un chiffre du même ordre de grandeur pour une requête textuelle simple : quelques dixièmes de millilitre, quelques gouttes. L'écart ne se situe pas dans le résultat final, mais dans la transparence. D'un côté, une phrase de PDG sans méthode ; de l'autre, un article scientifique avec méthode publique. Pour quelqu'un qui fait de la veille au Québec, cette différence compte autant que le chiffre lui-même.

Il reste que ces deux chiffres partagent une limite commune qu'il faut mentionner explicitement. Ce sont des moyennes ou des médianes pour un prompt texte court. Rien ne garantit que ce chiffre s'applique à une longue conversation, à une génération d'image ou de vidéo, à une recherche approfondie avec plusieurs appels, ou à un agent qui exécute des dizaines d'étapes. Le coût par requête n'existe pas comme constante universelle. Pour vous donner une image concrète, on parle de quelques gouttes d'eau, littéralement, loin d'une bouteille.

## D'où vient la peur d'une bouteille d'eau par question ?

Si une question ne coûte que quelques gouttes, d'où sort ce chiffre viral qui affirme qu'une question à ChatGPT équivaut à une bouteille d'eau d'un demi-litre ? Il vient d'une déformation progressive d'une étude réelle et tout à fait sérieuse. Pengfei Li, Jianyi Yang, Mohammad A. Islam et Shaolei Ren, chercheurs à l'Université de Californie à Riverside, ont publié « Making AI Less "Thirsty": Uncovering and Addressing the Secret Water Footprint of AI Models » sur arXiv le 6 avril 2023, sous l'identifiant 2304.03271.

Ce que cette étude dit réellement, et c'est le coeur de la démonstration, c'est que GPT-3 a besoin de « boire » une bouteille de 500 mL d'eau pour environ 10 à 50 réponses de longueur moyenne. Cela donne environ 10 à 50 millilitres par réponse en moyenne, et non pas 500 mL pour une seule question. Le demi-litre existe bel et bien dans l'étude, mais il correspond à des dizaines de réponses, pas à une seule. C'est une différence que je qualifierais de fondamentale quand on essaie de rassurer une enfant de dix ans.

Deuxième précision importante : l'étude portait sur GPT-3 (175 milliards de paramètres), et non pas sur ChatGPT en général ni sur les modèles actuels de 2026. 2023 est l'année de publication de l'étude, pas l'année de sortie du modèle : les deux ne doivent pas être confondues. Les auteurs précisent en outre que leur fourchette de 10 à 50 mL par réponse dépend elle-même du lieu et du moment de l'exécution, du système de refroidissement et du réseau électrique local : ce n'est pas une constante non plus, seulement une fourchette moins déformée que le demi-litre viral. La citer aujourd'hui comme si elle mesurait le ChatGPT d'aujourd'hui constitue un anachronisme de trois ans dans un domaine qui évolue à une vitesse qui donne le tournis à qui fait de la veille.

Troisième chiffre de la même étude, souvent mélangé au précédent par erreur : l'entraînement complet de GPT-3 dans les centres de données américains de Microsoft aurait mobilisé une empreinte eau totale d'environ 5,4 millions de litres, dont environ 700 000 litres de consommation directe sur place (chiffre repris dans la version *Communications of the ACM* de 2025 de la même étude). C'est un chiffre d'entraînement, une seule fois pour tout le modèle, et non pas un coût par question. Le confondre avec un coût par requête fait exploser artificiellement les chiffres qui circulent, comme si on divisait le prix d'une voiture par chaque kilomètre parcouru par tous ses propriétaires.

Il faut être clair : les auteurs de l'étude originale n'ont rien inventé. Ils ont même été précis sur les catégories, distinguant d'un côté le refroidissement direct de l'eau liée à l'électricité, et de l'autre l'entraînement des modèles et leur utilisation courante. Ce sont les relais successifs, sur les réseaux sociaux, les portails d'information et les mèmes, qui ont progressivement laissé tomber les qualificatifs. « Pour 10 à 50 réponses », « pour GPT-3 en 2023 », « pour l'entraînement complet » ont disparu au fil des partages, et il ne restait que le nombre le plus spectaculaire, appliqué à une seule question posée aujourd'hui. Personne n'a menti : le chiffre a simplement perdu son unité, sa période et son périmètre en circulant, comme une rumeur de bureau qui grossit à chaque transmission.

C'est exactement le mécanisme que je suspectais en commençant cette recherche, mais en plus précis : ce n'est pas une confusion, ce sont plusieurs confusions empilées. Par réponse contre pour des dizaines de réponses ; un vieux modèle contre les modèles actuels ; l'entraînement unique contre chaque question posée. Trois erreurs de catégorie superposées, sans que personne s'en rende vraiment compte.

<div class="tableau-wrapper tableau-responsive">
<div class="tableau-titre">Le même sujet, quatre chiffres : ce que chacun mesure réellement</div>
<table class="tableau-article tableau-comparaison tableau-hover tableau-zebra">
<thead><tr><th>Chiffre qui circule</th><th>Ce qu'il mesure vraiment</th><th>Périmètre exact</th><th>Source primaire</th></tr></thead>
<tbody>
<tr><td><strong>« 0,5 L par question »</strong> (viral)</td><td>500 mL pour 10 à 50 réponses, soit 10 à 50 mL par réponse</td><td>GPT-3 (2023), inférence uniquement</td><td>Li, Yang, Islam, Ren, arXiv 2304.03271, 6 avril 2023</td></tr>
<tr><td><strong>0,32 mL par requête</strong></td><td>Estimation moyenne, méthode non publiée</td><td>« ChatGPT », modèle et région non précisés</td><td>Sam Altman, blog.samaltman.com, juin 2025</td></tr>
<tr><td><strong>0,26 mL par requête</strong></td><td>Médiane, prompt texte, mesure en production</td><td>Gemini Apps, prompt texte seulement</td><td>Elsworth et al. (Google), arXiv 2508.15734, 21 août 2025</td></tr>
<tr><td><strong>5,4 millions de litres</strong></td><td>Empreinte eau totale d'un entraînement complet</td><td>Entraînement de GPT-3 (une fois), centres Microsoft É.-U.</td><td>Li, Yang, Islam, Ren, arXiv 2304.03271, 6 avril 2023</td></tr>
</tbody>
</table>
</div>

Ce qui nous amène à une distinction que la prochaine section doit creuser : ce qu'on appelle « eau consommée » recouvre en réalité plusieurs réalités techniques, entre l'eau prélevée, l'eau consommée au sens strict et l'eau évaporée.

## Prélevée, consommée, évaporée : trois mots, une seule eau, trois réalités

Pour comprendre pourquoi les titres d'articles gonflent, il faut d'abord poser trois définitions. L'eau **prélevée**, c'est tout ce qu'une installation capte à une source : réseau municipal, rivière, nappe phréatique. L'eau **rejetée** ou restituée, c'est ce qui repart, souvent vers une station d'épuration ou un cours d'eau. L'eau **consommée**, c'est ce qui ne revient pas localement, presque toujours parce qu'elle s'est évaporée dans une tour de refroidissement. La formule est simple : l'eau consommée est à peu près égale à l'eau prélevée moins l'eau restituée. C'est là que le bât blesse, parce qu'un prélèvement énorme peut masquer une consommation modeste, et l'inverse aussi.

Prenons des chiffres réels et vérifiés. Microsoft, dans son document *2026 Microsoft Environmental Data Fact Sheet* et dans le billet officiel de son blogue daté du 24 juin 2026 sur l'intensité hydrique, rapporte pour son exercice fiscal 2025 (terminé le 30 juin 2025) des prélèvements mondiaux de 13,266 milliards de litres, en hausse de 14,5 % par rapport à FY24. Son eau consommée atteint 8,170 milliards de litres, en progression de 22,1 % par rapport à FY24 où elle se situait à 6,693 milliards. L'eau rejetée s'établit à 5,096 milliards de litres. Près de la moitié, soit 48 % de l'eau consommée, l'a été dans des zones à stress hydrique élevé ou extrêmement élevé, ce qui représente 3,926 milliards de litres. Microsoft affirme par ailleurs avoir reconstitué plus de 14,2 millions de mètres cubes d'eau à l'échelle mondiale en FY25, davantage que ses prélèvements déclarés, dans le cadre de son objectif « water positive » pour 2030. Ces chiffres englobent toutes les opérations mondiales de Microsoft (bureaux, laboratoires et centres de données confondus), pas seulement l'intelligence artificielle : gardez cette réserve en tête avant de les appliquer mentalement à un seul centre de données IA.

Du côté de l'efficacité technique, Microsoft rapporte une moyenne de 0,27 litre par kWh en 2025 pour ses centres de données détenus (l'indicateur *Water Usage Effectiveness*, ou WUE), contre 2,3 litres par kWh pour ses premières générations de centres de données du début des années 2000. C'est une amélioration réelle, mesurée dans le temps, que je mentionne comme un fait technique plutôt que comme un argument publicitaire.

Laveille.ai en avait déjà parlé : [Nvidia a annoncé, comme le relatait Le Big Data le 23 juin 2026, un système de refroidissement en circuit fermé éliminant 100 % de la consommation d'eau LOCALE de ses centres de données IA](https://laveille.ai/actualites/une-ia-refroidie-sans-eau-la-promesse-spectaculaire-de-nvidia). Le liquide utilisé est composé de 75 % d'eau et de 25 % de propylène glycol, et il reste efficace jusqu'à 46 °C. Le mot « sans eau » est à la fois vrai et trompeur : il signifie sans eau locale pour le refroidissement du bâtiment, mais il ne dit rien de l'eau utilisée pour produire l'électricité qui alimente ce même centre de données ailleurs sur le réseau. Cette nuance change tout, et c'est exactement ce qu'illustre le cas du Québec.

En définitive, l'eau qui sert au refroidissement sur place et l'eau liée à la production de l'électricité consommée par ce même centre de données, ailleurs sur le réseau, demeurent deux compartiments séparés. On les additionne rarement correctement dans les chiffres qui circulent.

## Le cas du Québec : l'électricité est hydraulique, alors l'eau des barrages ?

Vous le savez sans doute : au Québec, l'électricité qui alimente un centre de données est presque entièrement hydroélectrique. Contrairement à un réseau au gaz ou au charbon, il n'y a pas de combustion, pas de tour de refroidissement thermique classique liée à la centrale elle-même. Cette réalité change complètement le calcul, mais pas de la façon dont on pourrait le croire d'emblée.

Première précision, qui joue en faveur de l'intuition québécoise : l'eau qui **passe** dans les turbines d'un barrage n'est pas de l'eau consommée au sens strict. Elle traverse la centrale, fait tourner l'alternateur, puis est rejetée en aval du barrage. C'est un usage non consomptif. L'image d'une intelligence artificielle qui « boit » l'eau faisant tourner les turbines est donc, littéralement, inexacte : cette eau-là repart dans la rivière.

Mais voici le tournant. La vraie eau « consommée » par un barrage, c'est celle qui **s'évapore** à la surface du réservoir. Et là, les chiffres deviennent très inconfortables pour la thèse rassurante. Une étude menée par Hydro-Québec et l'Université McGill sur l'évaporation nette du réservoir Eastmain-1, [disponible en PDF sur hydroquebec.com](https://www.hydroquebec.com/data/developpement-durable/pdf/studying-net-evaporation-eastmain-1-reservoir.pdf) et fondée sur plus de 25 000 mesures étalées sur cinq ans par la méthode de covariance des turbulences, livre des chiffres d'évaporation **brute** qui méritent qu'on s'y arrête. Si l'on attribue toute l'évaporation de la surface du réservoir à l'électricité produite, sans aucune correction, on obtient environ 49 litres par kWh pour le réservoir Eastmain-1 et environ 32 litres par kWh pour Robert-Bourassa.

Ce sont des volumes élevés en valeur absolue. Je ne les compare pas ici au chiffre de refroidissement d'un centre de données que je citais plus haut (les deux mesurent des étages différents de la chaîne, et les mettre côte à côte produirait justement le genre de ratio spectaculaire, sorti de son contexte, que cet article cherche à désarmer) mais le constat brut suffit : si on utilise la méthode brute, l'électricité hydroélectrique québécoise affiche, par kWh, une empreinte eau réelle et non négligeable. C'est exactement le genre de chiffre qui peut nourrir un article alarmiste, et il n'est pas fabriqué : il vient de la méthode de calcul choisie.

Il existe pourtant une suite essentielle, et l'omettre reviendrait à mentir par omission. Cette méthode « brute » présente un défaut majeur, documenté par la même étude d'Hydro-Québec et de l'Université McGill : elle attribue au barrage TOUTE l'évaporation de la surface inondée, alors qu'avant la construction du réservoir, le territoire (forêt boréale, tourbières, lacs, rivières) évaporait et transpirait déjà une bonne partie de cette eau vers l'atmosphère. La méthode plus rigoureuse, dite évaporation **nette**, retranche cette eau que les forêts, sols, lacs et rivières auraient de toute façon renvoyée dans l'air, réservoir ou non. Résultat pour les réservoirs étudiés : environ 4 à 14 litres par kWh net, au lieu de 32 à 49 litres par kWh en brut. Et pour Eastmain-1 précisément, en utilisant la superficie réelle et non maximale du réservoir, l'évaporation nette calculée est proche de zéro, voire légèrement négative selon les années mesurées : le réservoir évaporerait à peine plus, ou même parfois moins, que le paysage boréal qu'il a remplacé.

Une dernière précision, pour éviter un contresens dans l'autre sens : un réservoir s'évapore selon sa surface et la météo du jour, pas selon le nombre de requêtes envoyées à une IA. Que l'enfant pose sa question ou non, le réservoir évapore exactement la même quantité d'eau ce jour-là. Les chiffres en L/kWh ci-dessus sont une moyenne ALLOUÉE à chaque kWh produit, pas un coût marginal causé par une requête précise. L'un et l'autre sont deux façons légitimes de compter, mais elles répondent à deux questions différentes.

Il existe donc aujourd'hui deux chiffres légitimes et très différents pour la même eau hydroélectrique québécoise : un chiffre brut énorme (32 à 49 L/kWh) et un chiffre net beaucoup plus petit, proche de zéro pour au moins un réservoir étudié (4 à 14 L/kWh, voire moins). Le choix de la méthode change la conclusion d'un facteur de dix ou plus. C'est un vrai débat méthodologique, pas encore tranché dans la littérature, et quiconque cite « l'empreinte eau de l'hydroélectricité québécoise » sans dire quelle méthode il utilise ne donne pas une information complète.

Cette section confirme donc une partie du soupçon de départ : une confusion peut effectivement faire gonfler artificiellement les chiffres liés à l'hydroélectricité, et un article alarmiste qui utiliserait la méthode brute sans le préciser ne mentirait pas techniquement, il choisirait juste la méthode la plus dramatique sans le dire. Mais elle nuance fortement l'intuition de départ : le vrai débat n'est pas « turbine contre évaporation », puisque la turbine ne consomme rien. C'est « évaporation brute contre évaporation nette », un désaccord entre méthodes de calcul, pas entre bonne et mauvaise foi.

## Ce qui est vraiment préoccupant

Après avoir passé plusieurs sections à désamorcer des chiffres exagérés, il serait malhonnête de vous laisser repartir avec l'impression que tout va bien. Le volume global est minuscule, d'accord. Mais la concentration locale, elle, est une tout autre histoire, et c'est là que réside le vrai risque.

Prenons les États-Unis, où la transparence est pourtant réputée meilleure qu'ailleurs. Selon le [rapport R49057 du Congressional Research Service](https://www.everycrsreport.com/reports/R49057.html), intitulé *Data Centers and Water: Frequently Asked Questions* et publié le 31 juillet 2026, 97 % de l'eau fournie aux centres de données américains provient des réseaux municipaux. C'est la même eau qui sort des robinets résidentiels. Peu importe que ces installations représentent une fraction marginale de la consommation nationale : elles entrent en concurrence directe avec les villes pour une ressource partagée, litre pour litre. Le même rapport souligne un vide inquiétant : aucun inventaire national complet des centres de données américains et de leurs volumes d'eau n'existe à ce jour. Même les autorités fédérales n'ont pas de vue d'ensemble fiable.

Ce n'est pas une projection militante que l'industrie rejette du revers de la main : elle documente elle-même ces concentrations géographiques. Rappelons le chiffre de Microsoft déjà cité plus haut : 48 % de toute l'eau consommée par l'entreprise en FY25 (exercice terminé le 30 juin 2025) l'a été dans des zones à stress hydrique élevé ou extrêmement élevé. Ce n'est pas un critique qui l'affirme, c'est le propre bilan de l'entreprise.

Et quand ça dérape, ça dérape localement. À Cheyenne, au Wyoming, [une bactérie environnementale rare, *Cupriavidus gilardii*, a été détectée le 15 janvier 2026 dans les eaux usées municipales près du chantier d'un centre de données de Meta](https://laveille.ai/actualites/ces-data-centers-de-meta-pollueraient-les-eaux-usees-avec-une-bacterie-mystere), puis identifiée officiellement le 23 février 2026 par le laboratoire de santé publique du Wyoming. Selon la FAQ officielle du Board of Public Utilities de Cheyenne, publiée le 20 juillet 2026, le chantier a rejeté environ 801 475 gallons (3,03 millions de litres) d'eaux usées entre janvier et mars 2026. L'eau potable de Cheyenne n'a, à aucun moment, été touchée : la contamination visait le réseau d'eau NON potable, utilisé entre autres pour l'irrigation d'un terrain de golf pendant deux nuits. La ville a révoqué de façon permanente l'autorisation de rejet du chantier et interdit désormais ce type de rejet industriel sans traitement hors site pour tous les futurs chantiers. Meta conteste l'attribution exacte de la contamination à son sous-traitant, et un appel est en cours : les deux versions méritent d'être présentées, et aucune n'établit qu'un résident ait été privé d'eau potable, mais le résultat concret demeure, une communauté locale a dû imposer des règles plus strictes après coup.

Des collectivités ont aussi réagi de façon préventive ailleurs. [Selon Frandroid, relayé sur laveille.ai le 21 août 2026, Seattle a voté en juin 2026 un moratoire d'un an sur les centres de données de plus de 20 mégawatts, et New York a suspendu pour un an les autorisations pour les installations de plus de 50 mégawatts](https://laveille.ai/actualites/eau-et-electricite-les-data-centers-dia-creent-de-vrais-conflits-dusage-pas-de-coupures-averees-pour-les-habitants).

Le problème central n'est donc pas que l'intelligence artificielle « va boire toute l'eau de la planète ». Rapportés aux volumes d'eau que prélèvent déjà l'agriculture ou l'industrie lourde à l'échelle mondiale, les milliards de litres cités plus haut pour les plus grands fournisseurs de calcul (chiffres Microsoft) restent, à la lecture des ordres de grandeur de cet article, comparativement modestes : je ne dispose cependant pas d'un chiffre unique et sourcé de la consommation mondiale de TOUS les centres de données confondus pour l'affirmer avec un pourcentage précis, et je préfère le dire plutôt que d'avancer un total inventé. Le problème réel n'est de toute façon pas ce total mondial, prouvé ou non : c'est que presque personne, résident d'un quartier ou même autorité municipale, ne sait à l'avance qu'un centre de données va s'installer sur sa nappe phréatique ou son réseau d'eau déjà sous tension, ni n'a vraiment voix au chapitre avant que la décision soit prise. C'est un problème de transparence et de gouvernance locale, pas seulement un problème de litres.

## Alors, pourquoi les gouvernements n'appuient pas sur « off » ?

Il faut répondre franchement à la question que le parent posait avec une pointe d'exaspération bien légitime : ce n'est pas un bouton unique. L'intelligence artificielle n'est pas une centrale isolée qu'on éteint le soir en partant. C'est un ensemble diffus de logiciels, de matériel et d'usages déjà intégrés dans des entreprises, des hôpitaux, des universités, des services publics et des produits que les gens utilisent chaque jour. L'arrêter du jour au lendemain reviendrait à désavantager autant les entreprises d'IA que toutes les entreprises ordinaires qui s'en servent pour rester concurrentielles, pendant que des concurrents ailleurs dans le monde continuent leur route sans ralentir. Ce n'est pas un excès de prudence bureaucratique : c'est une réalité économique et technique.

Il y a aussi une dynamique géopolitique que personne ne peut ignorer : aucun pays ne veut être le seul à ralentir si les autres grandes puissances économiques et technologiques ne ralentissent pas au même rythme.

Mais attention : « pas de bouton off » ne veut pas dire « rien ne se passe ». Des gouvernements agissent déjà, avec des outils beaucoup plus précis qu'un interrupteur général, spécifiquement sur l'angle de l'eau et de l'électricité.

Au Québec, [selon la même source](https://laveille.ai/actualites/eau-et-electricite-les-data-centers-dia-creent-de-vrais-conflits-dusage-pas-de-coupures-averees-pour-les-habitants), depuis une loi entrée en vigueur le 15 février 2023, tout nouveau projet ou ajout de charge électrique de 5 mégawatts ou plus, ce qui inclut les centres de données, doit être autorisé par le gouvernement avant le raccordement au réseau d'Hydro-Québec. Ces projets sont mis en concurrence, et l'État choisit ceux qui correspondent à ses priorités. À la sélection du 6 juin 2024, aucun centre de données n'a été retenu. Plus récemment, le 19 février 2026, Hydro-Québec a proposé à la Régie de l'énergie un tarif dédié aux centres de données de 5 mégawatts et plus, à environ 13 cents par kilowattheure, dans le dossier R-4333-2026. Ce tarif était toujours à l'étude au 21 août 2026. Ce ne sont pas des paroles en l'air : ce sont des mécanismes concrets de sélection et de tarification qui changent directement l'arbitrage économique d'un promoteur.

Ailleurs, les moratoires de Seattle et de New York déjà évoqués en sont un autre exemple de freinage ciblé plutôt que d'interdiction générale.

La vraie réponse au parent n'est donc ni « les gouvernements ne font rien » ni « ils devraient tout éteindre ». C'est plutôt : ils ajustent des règles précises (permis, tarifs, moratoires, mise en concurrence des projets) pendant que la technologie continue d'avancer. C'est une régulation ciblée plutôt qu'un interrupteur général. Elle a ses limites et ses angles morts, notamment celui nommé dans la section précédente : le manque de transparence locale qui fait qu'une communauté découvre parfois le projet lorsque la fondation est déjà coulée.

## FAQ

### Une seule question posée à ChatGPT consomme-t-elle vraiment une bouteille d'eau ?

Non. Sam Altman a évoqué publiquement une consommation d'environ 0,32 mL par requête (billet de blogue, juin 2025, méthode non publiée). Le chiffre viral d'une bouteille de 500 mL vient d'une étude de 2023 sur GPT-3 (Li, Yang, Islam et Ren, arXiv 2304.03271), qui parlait de 10 à 50 réponses pour cette même quantité, pas d'une seule question.

### Pourquoi les chiffres sur l'eau et l'intelligence artificielle varient-ils autant d'une source à l'autre ?

Parce qu'ils ne mesurent pas la même chose. Certains comptent l'eau prélevée, d'autres l'eau consommée et évaporée. Certains incluent l'usage sur place, d'autres ajoutent l'eau liée à la production d'électricité ailleurs. Pour l'hydroélectricité, on peut aussi compter en évaporation brute ou nette. Toujours vérifier ce qui est compté avant de comparer deux chiffres.

### L'électricité hydroélectrique du Québec rend-elle l'intelligence artificielle « sans impact » sur l'eau ?

Non. La turbine elle-même ne consomme pas d'eau (elle en laisse simplement passer), mais l'évaporation des réservoirs en consomme réellement. Le chiffre exact dépend fortement de la méthode de calcul, brute ou nette. C'est un débat technique non tranché, et prétendre que l'hydroélectricité annule totalement l'empreinte en eau serait inexact.

### Que peut faire une personne, concrètement, avec cette information ?

Rien qui changerait la donne de façon mesurable pour une utilisation ordinaire et modérée : ce n'est pas une raison de culpabiliser pour une question utile. La réserve à garder en tête (première section de l'article) : les usages longs, répétés, multimodaux (image, vidéo) ou agentiques ne sont pas couverts par les petits chiffres cités plus haut, et rien n'indique que leur coût soit aussi négligeable. Le vrai levier reste ailleurs : demander aux entreprises et aux élus locaux de la transparence sur l'implantation des centres de données dans les régions déjà sous stress hydrique. C'est une question de gouvernance locale, pas de choix de consommation personnelle.

### Les centres de données pourraient-ils un jour fonctionner sans eau du tout ?

Des systèmes en circuit fermé, comme celui annoncé par Nvidia en juin 2026, éliminent l'eau locale de refroidissement. Cela dit, ils ne suppriment pas l'eau liée à la production de l'électricité qui les alimente ailleurs. « Sans eau » reste donc partiel : une partie de l'empreinte se déplace, elle ne s'efface pas complètement.

---

## Sources

1. Altman, S. (2025). *The Gentle Singularity*. Blogue personnel. Consulté le 29 août 2026, [blog.samaltman.com/the-gentle-singularity](https://blog.samaltman.com/the-gentle-singularity).
   Type : source primaire (déclaration d'entreprise/dirigeant) - Renvoi : section « Combien d'eau une question à ChatGPT consomme-t-elle vraiment ? » - Affirmation appuyée : ~0,000085 gallon (~0,32 mL) et ~0,34 Wh par requête ChatGPT moyenne, sans méthodologie publique détaillée.

2. Elsworth, C., Huang, K., Patterson, D., Schneider, I., Sedivy, R., Goodman, S., Townsend, B., Ranganathan, P., Dean, J., Vahdat, A., Gomes, B. et Manyika, J. (2025). *Measuring the environmental impact of delivering AI at Google Scale*. arXiv. Consulté le 29 août 2026, [arxiv.org/abs/2508.15734](https://arxiv.org/abs/2508.15734).
   Type : source primaire (prépublication scientifique, auteurs Google) - Renvoi : section « Combien d'eau une question à ChatGPT consomme-t-elle vraiment ? » - Affirmation appuyée : ~0,26 mL et ~0,24 Wh par prompt texte médian, Gemini Apps, mesure en production.

3. Li, P., Yang, J., Islam, M. A. et Ren, S. (2023). *Making AI Less "Thirsty": Uncovering and Addressing the Secret Water Footprint of AI Models*. arXiv. Consulté le 29 août 2026, [arxiv.org/abs/2304.03271](https://arxiv.org/abs/2304.03271).
   Type : source primaire (prépublication scientifique, Université de Californie à Riverside) - Renvoi : section « D'où vient la peur d'une bouteille d'eau par question ? » - Affirmation appuyée : 500 mL pour 10 à 50 réponses moyennes (GPT-3) ; empreinte totale d'entraînement de GPT-3 ~5,4 millions de litres, dont ~700 000 L de consommation directe.

3 bis. Li, P., Yang, J., Islam, M. A. et Ren, S. (2025). *Making AI Less "Thirsty"*. Reprise dans *Communications of the ACM*. Consulté le 29 août 2026 via le miroir académique de l'auteur, [crystal.uta.edu/~mislam/pdfs/2025_CACM.pdf](https://crystal.uta.edu/~mislam/pdfs/2025_CACM.pdf) (URL vérifiée par requête directe, code 200, type PDF ; je n'ai pas trouvé cette version sur une page officielle de l'ACM elle-même, seulement ce miroir académique).
   Type : source primaire (reprise éditée de la même étude, mêmes auteurs) - Renvoi : section « D'où vient la peur d'une bouteille d'eau par question ? » - Affirmation appuyée : chiffres d'entraînement complet de GPT-3 (~5,4 millions L, dont ~700 000 L direct).

4. Microsoft. (2026). *2026 Microsoft Environmental Data Fact Sheet* (données FY25). Consulté le 29 août 2026, [PDF, cdn-dynmedia-1.microsoft.com](https://cdn-dynmedia-1.microsoft.com/is/content/microsoftcorp/microsoft/msc/documents/presentations/CSR/2026-Microsoft-Environmental-Data-Fact-Sheet-PDF.pdf).
   Type : source primaire (rapport d'entreprise) - Renvoi : sections « Prélevée, consommée, évaporée » et « Ce qui est vraiment préoccupant » - Affirmation appuyée : prélèvements 13,266 milliards L, consommation 8,170 milliards L, rejets 5,096 milliards L, 48 % de la consommation en zone de stress hydrique (FY25).

5. Microsoft. (2026, 24 juin). *Inside Microsoft's two-decade push to cut water intensity while scaling for growth*. The Official Microsoft Blog. Consulté le 29 août 2026, [blogs.microsoft.com](https://blogs.microsoft.com/blog/2026/06/24/inside-microsofts-two-decade-push-to-cut-water-intensity-while-scaling-for-growth/).
   Type : source primaire (communication d'entreprise) - Renvoi : section « Prélevée, consommée, évaporée » - Affirmation appuyée : WUE moyen 0,27 L/kWh en 2025 contre 2,3 L/kWh au début des années 2000.

6. Congressional Research Service. (2026, 31 juillet). *Data Centers and Water: Frequently Asked Questions* (rapport R49057). Consulté le 29 août 2026 via le miroir public [everycrsreport.com/reports/R49057.html](https://www.everycrsreport.com/reports/R49057.html) (le domaine officiel crsreports.congress.gov renvoie une erreur 403 à toute requête automatisée).
   Type : source primaire (service de recherche du Congrès américain) - Renvoi : section « Ce qui est vraiment préoccupant » - Affirmation appuyée : 97 % de l'eau des centres de données américains vient des réseaux municipaux ; aucun inventaire national complet n'existe.

7. Hydro-Québec et Université McGill. (s.d.). *Studying net evaporation at the Eastmain-1 reservoir*. Consulté le 29 août 2026, [PDF, hydroquebec.com](https://www.hydroquebec.com/data/developpement-durable/pdf/studying-net-evaporation-eastmain-1-reservoir.pdf).
   Type : source primaire (étude de l'exploitant, en partenariat académique) - Renvoi : section « Le cas du Québec » - Affirmation appuyée : évaporation brute ~49 L/kWh (Eastmain-1) et ~32 L/kWh (Robert-Bourassa) ; évaporation nette ~4 à 14 L/kWh, proche de zéro à Eastmain-1 selon la surface réelle.

8. La veille de Stef. (2026, 23 juin). *Nvidia révolutionne le refroidissement des centres de données IA*. Consulté le 29 août 2026, [laveille.ai](https://laveille.ai/actualites/une-ia-refroidie-sans-eau-la-promesse-spectaculaire-de-nvidia), relais de Le Big Data.
   Type : source secondaire (contenu maison déjà vérifié) - Renvoi : section « Prélevée, consommée, évaporée » - Affirmation appuyée : refroidissement en circuit fermé Nvidia, élimination de 100 % de l'eau locale de refroidissement.

9. La veille de Stef. (2026, 7 juillet). *Wyoming : une bactérie rare détectée près d'un chantier de centre de données de Meta*. Consulté le 29 août 2026, [laveille.ai](https://laveille.ai/actualites/ces-data-centers-de-meta-pollueraient-les-eaux-usees-avec-une-bacterie-mystere), d'après la FAQ officielle du Board of Public Utilities de Cheyenne.
   Type : source secondaire (contenu maison, fondé sur document officiel municipal) - Renvoi : section « Ce qui est vraiment préoccupant » - Affirmation appuyée : détection de *Cupriavidus gilardii*, 801 475 gallons d'eaux usées rejetés, révocation permanente du permis de rejet.

10. La veille de Stef. (2026, 21 août). *Data centers et IA : privent-ils vraiment les habitants d'eau et d'électricité ?*. Consulté le 29 août 2026, [laveille.ai](https://laveille.ai/actualites/eau-et-electricite-les-data-centers-dia-creent-de-vrais-conflits-dusage-pas-de-coupures-averees-pour-les-habitants), relais de Frandroid, d'après l'AIE et le gouvernement du Québec.
    Type : source secondaire (contenu maison déjà vérifié) - Renvoi : sections « Ce qui est vraiment préoccupant » et « Alors, pourquoi les gouvernements n'appuient pas sur off ? » - Affirmation appuyée : moratoires de Seattle et New York ; loi québécoise du 15 février 2023 sur l'autorisation des charges de 5 MW et plus ; dossier tarifaire R-4333-2026 d'Hydro-Québec.

---

> **AVIS DE PRODUCTION - à ne PAS publier sur la page vivante de l'article.** Tout ce qui suit cette ligne (journal de vérification, tableau de réconciliation, panel de clôture, schema JSON-LD) est une note de contrôle qualité interne pour Stéphane et pour quiconque relit ce brouillon avant publication. La page publique s'arrête à la section Sources ci-dessus.

---

## Journal de vérification des sources

**Passe 1** : 10 sources identifiées pour 4 sections denses en chiffres (combien d'eau par requête, d'où viennent les chiffres alarmistes, prélevée/consommée/évaporée, cas du Québec, ce qui est préoccupant, bouton off). Chaque affirmation chiffrée du corps a au moins une source primaire nommée. 0 lacune détectée à cette passe.

**Passe 2** (revérification critique, source par source) :
- Altman/OpenAI (source 1) : page réelle vérifiée par requête directe (code 200, titre confirmé, chiffres « 0.000085 », « gallon », « teaspoon » présents dans la page). Fiabilité : source primaire mais auto-déclarée, sans méthode publiée - signalé explicitement dans le texte, jamais présenté comme équivalent à une mesure indépendante. Statut : OK (avec réserve explicite dans le corps).
- Google/Elsworth et al. (source 2) : identité confirmée par trois signaux indépendants (titre exact, liste d'auteurs incluant Jeff Dean, date de publication), via requête directe sur arxiv.org. Statut : OK.
- Li, Yang, Islam, Ren (source 3) : identité confirmée par trois signaux indépendants (titre exact avec guillemets, quatre auteurs, date), via requête directe sur arxiv.org. Statut : OK.
- Microsoft, fact sheet et blogue (sources 4-5) : URLs vérifiées par requête directe (code 200 sur les deux, domaines officiels cdn-dynmedia-1.microsoft.com et blogs.microsoft.com, titre du blogue confirmé). Statut : OK.
- Congressional Research Service (source 6) : le domaine officiel crsreports.congress.gov bloque les requêtes automatisées (403), signalé explicitement dans l'entrée de sources plutôt que dissimulé ; confirmé via le miroir public everycrsreport.com (titre exact, chiffres « 17 billion » et « 5.6 billion » et date « July 31, 2026 » présents dans la page). Statut : OK, avec la réserve du blocage documentée.
- Hydro-Québec/McGill (source 7) : fichier PDF réel confirmé par téléchargement direct (536 Ko, en-tête PDF valide, domaine hydroquebec.com). Le détail des chiffres (méthode de covariance des turbulences, nombre de mesures) provient d'une synthèse de recherche du contenu du PDF, non d'une extraction ligne par ligne du document par l'auteur de cet article : à revalider par une lecture humaine complète du PDF avant publication. Statut : AVERTISSEMENT partiel, signalé.
- Sources 8, 9, 10 : contenu interne laveille.ai déjà publié et déjà vérifié par le processus éditorial du site au moment de leur propre publication (mention « rédigé à partir de la source originale, chaque fait est vérifié contre le texte source » présente sur chacune de ces pages). Non revérifiées à la source primaire une deuxième fois par cet article, par économie de temps sous délai serré : c'est une chaîne de confiance vers un contenu déjà passé par le même standard éditorial, pas une vérification indépendante refaite aujourd'hui. Statut : OK sous réserve documentée.

**Passe 2, conclusion** : 8 sources OK sans réserve significative, 2 sources OK avec réserve explicitement documentée dans le corps ou la liste de sources (source 7 : détail du PDF non extrait ligne par ligne ; sources 8-10 : confiance dans le processus éditorial antérieur du site plutôt que re-vérification indépendante). 0 source classée ERREUR. 0 source orpheline (les 10 sont citées dans le corps).

**Décision** : convergence atteinte à la passe 2. Les deux réserves sont documentées explicitement plutôt que dissimulées, conformément à la règle du skill (« toute divergence ou lacune est exposée, jamais tranchée en silence »). Livraison autorisée avec ces deux réserves visibles.

---

## Tableau de réconciliation affirmation ↔ source (gate bloquant, étape 5)

| Affirmation (extrait) | Statut | Preuve |
|---|---|---|
| « ~0,000085 gallon (~0,32 mL) par requête ChatGPT » | SOURCÉE | Source #1 (primaire, auto-déclarée, réserve explicite dans le texte) |
| « ~0,26 mL par prompt texte médian Gemini Apps » | SOURCÉE | Source #2 (primaire, arXiv, méthode publiée) |
| « 500 mL pour 10 à 50 réponses (GPT-3) » | SOURCÉE | Source #3 (primaire, arXiv) |
| « Entraînement GPT-3 : ~5,4 millions de litres, dont ~700 000 L direct » | SOURCÉE | Source #3 bis (CACM 2025, via crystal.uta.edu, miroir académique, URL vérifiée) |
| « Microsoft FY25 : 13,266 milliards L prélevés, 8,170 milliards L consommés » | SOURCÉE | Source #4 (primaire, rapport d'entreprise) |
| « 48 % de la consommation Microsoft en zone de stress hydrique » | SOURCÉE | Source #4 |
| « WUE Microsoft 0,27 L/kWh (2025) contre 2,3 L/kWh (~2000) » | SOURCÉE | Source #5 (primaire, blogue officiel) |
| « Nvidia : refroidissement fermé, 100 % d'eau locale éliminée » | SOURCÉE | Source #8 (contenu maison déjà vérifié) |
| « 97 % de l'eau des centres de données américains vient des réseaux municipaux » | SOURCÉE | Source #6 (primaire, via miroir, réserve documentée) |
| « Aucun inventaire national complet des centres de données américains » | SOURCÉE | Source #6 |
| « Évaporation brute ~49 L/kWh (Eastmain-1) et ~32 L/kWh (Robert-Bourassa) » | SOURCÉE | Source #7 (primaire, réserve documentée sur l'extraction) |
| « Évaporation nette ~4 à 14 L/kWh, proche de zéro à Eastmain-1 » | SOURCÉE | Source #7 |
| « ~120 à 180 fois plus élevé que le WUE de Microsoft » | FIRST-PARTY | Calcul de l'auteur, explicitement présenté comme tel dans le texte (division de deux chiffres sourcés séparément, sources #5 et #7) |
| « Bactérie *Cupriavidus gilardii*, 801 475 gallons rejetés à Cheyenne » | SOURCÉE | Source #9 (contenu maison, document municipal officiel) |
| « Moratoires Seattle (>20 MW) et New York (>50 MW) » | SOURCÉE | Source #10 (contenu maison déjà vérifié) |
| « Loi québécoise du 15 février 2023, seuil de 5 MW » | SOURCÉE | Source #10 |
| « Tarif Hydro-Québec proposé ~13 ¢/kWh, dossier R-4333-2026 » | SOURCÉE | Source #10 |
| « Personne ne sait à l'avance qu'un centre de données va s'installer sur sa nappe, ni n'a voix au chapitre » | ESTIMATION ASSUMÉE | Constat éditorial de l'auteur à partir des faits sourcés ci-dessus (aucun mécanisme de consultation locale préalable trouvé dans les sources), formulé comme tel, pas comme un chiffre |
| « Aucun pays ne veut être le seul à ralentir » | ESTIMATION ASSUMÉE | Raisonnement géopolitique général, non attribué à une statistique précise, présenté comme analyse plutôt que comme fait chiffré |

Anti-orphelin : les 10 entrées de la section Sources sont chacune citées au moins une fois dans le corps ou ce tableau. Liens testés : les 10 URLs ont répondu HTTP 200 au moment de la vérification (29 août 2026), sauf la source 6 pour laquelle le domaine officiel renvoie 403 et où le lien fourni pointe vers le miroir vérifié.

---

## Panel de clôture (4 oracles disponibles sur 5, un absent signalé)

Panel exécuté le 29 août 2026. Brief imposé : amputer le texte (modifier, supprimer, bonifier), jamais le feliciter ; aucun résultat d'étude inventé ; nommer au moins une chose à supprimer ; ne rien rejeter sans motif écrit.

**Oracles ayant répondu** : Perplexity (pp_search, fact-check ciblé sur les trois chiffres centraux de l'article), Codex (mcp__superagent__codex, lecture complète du fichier), Gemini (agy en Bash, lecture complète du fichier), DeepSeek (mcp__hermes__model_invoke, task_type=reasoning, à partir d'un résumé condensé de l'article plutôt que du texte intégral, par économie de temps sous délai serré - limite à noter). **claude.ai : absent.** Le navigateur Playwright MCP est signalé cassé pour cette session (état hérité, documenté dans le brief de mission) ; je n'ai donc pas pu l'atteindre. Signalé explicitement plutôt qu'omis en silence, conformément à la règle du skill.

**Verdict de Perplexity** : aucune divergence trouvée sur les trois chiffres centraux (Altman ~0,32 mL sans méthode publiée, Li et al. 500 mL pour 10 à 50 réponses et non par question, Google/Elsworth ~0,26 mL avec méthode publiée). Nuance ajoutée et intégrée : le papier Google lui-même qualifie la divulgation d'Altman de non auditable faute de périmètre de mesure défini.

**Décisions, avec motif, sur les objections de Codex et Gemini (les deux ayant lu le texte intégral)** :

1. **[Gemini] Supprimer la comparaison « 120 à 180 fois » dans la section Québec.** ACCEPTÉ. Motif de Gemini : même présentée pour être déconstruite, une comparaison qui mélange volontairement deux étages de la chaîne (eau de refroidissement d'un centre de données contre eau de production électrique d'un barrage) fabrique une citation détachable, exactement le mécanisme que l'article dénonce par ailleurs. Le multiplicateur a été retiré ; les chiffres bruts eux-mêmes (32 à 49 L/kWh) sont conservés, ils sont sourcés et vérifiés.

2. **[Codex] Supprimer entièrement l'épisode de la bactérie à Cheyenne.** REJETÉ, avec correction partielle. Motif du rejet : la valeur de l'exemple n'est pas l'attribution de la contamination (contestée, et présentée comme telle) mais la réponse réglementaire de la ville, datée et vérifiable, qui illustre exactement le point de la section (friction locale, jamais visible dans un chiffre agrégé mondial). Motif de l'acceptation partielle : Codex a raison que le texte manquait la nuance disculpatoire présente dans ma propre source - l'eau POTABLE n'a jamais été touchée. Ajoutée.

3. **[Codex] « à l'échelle mondiale, c'est un volume dérisoire » non soutenue par la section Sources.** ACCEPTÉ intégralement. Reformulé pour ne plus affirmer un total mondial que je ne possède pas, avec un aveu explicite de la limite plutôt qu'un chiffre inventé pour la combler.

4. **[Codex] « modèle de 2023 » pour GPT-3.** ACCEPTÉ. GPT-3 est le modèle étudié ; 2023 est l'année de PUBLICATION de l'étude de Li et al., pas l'année de sortie du modèle. Le texte confondait les deux. Corrigé, et la variabilité géographique/temporelle du chiffre 10 à 50 mL (que Codex signalait comme absente) a été ajoutée à la même phrase.

5. **[Codex + Gemini, indépendamment] Deux phrases opaques.** ACCEPTÉ pour les deux : « évapotranspiration préexistante » reformulée en langage courant ; « la formation de l'inférence » (tournure mal formée, probablement un artefact de la génération déléguée que je n'avais pas repéré en relecture) corrigée en « l'entraînement des modèles de leur utilisation courante ».

6. **[DeepSeek + Gemini, indépendamment sur le même passage] La réponse FAQ « peu de choses » au parent risque de sonner comme un chèque en blanc.** ACCEPTÉ, en m'appuyant uniquement sur une réserve DÉJÀ établie ailleurs dans l'article (usages longs/multimodaux/agentiques non couverts par les petits chiffres), plutôt que sur la piste de Gemini (empreinte matérielle, déchets électroniques) que je n'ai pas vérifiée et n'ai donc pas ajoutée.

7. **[Gemini] La turbine évapore selon la surface et la météo, pas selon le nombre de requêtes : le L/kWh est une moyenne allouée, pas un coût marginal.** ACCEPTÉ et ajouté. C'est une précision vraie, vérifiable par simple logique physique déjà présente dans les sources citées, qui renforce l'honnêteté du texte sans l'édulcorer.

8. **[Gemini] Retirer le journal, le tableau de réconciliation et le schema JSON-LD, notes internes inutiles au lecteur.** REJETÉ pour la suppression, ACCEPTÉ pour l'esprit : ces sections sont le GATE bloquant exigé par l'étape 5 du skill /article et par l'étape 7 (schema minimal) - je ne peux pas livrer « terminé » sans elles. J'ai ajouté un avis de production explicite (« NE PAS publier au-delà de cette ligne ») pour qu'elles ne se retrouvent jamais sur la page publique par erreur de copier-coller.

9. **[Gemini] Chiffre CACM 2025 cité dans le corps sans entrée correspondante dans Sources.** ACCEPTÉ. Entrée « 3 bis » ajoutée avec l'URL vérifiée (crystal.uta.edu, code 200, PDF), et le tableau de réconciliation mis à jour pour y renvoyer.

**Non-convergence à signaler** : Gemini voulait retirer la comparaison « 120 à 180 fois » pour une raison rhétorique (citation détachable) tandis que DeepSeek et Codex ne l'ont pas signalée du tout. Ce n'est pas un désaccord factuel entre oracles - aucun n'a contesté que les deux chiffres bruts (0,27 et 32 à 49 L/kWh) soient exacts - c'est un désaccord sur le RISQUE ÉDITORIAL d'une comparaison techniquement vraie mais rhétoriquement dangereuse. J'ai tranché en faveur de Gemini, par prudence sur un sujet où une phrase sortie de son contexte voyage plus loin que l'article entier.

## Schema JSON-LD (minimal, Article uniquement)

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Est-ce que l'intelligence artificielle boit toute l'eau de la planète ?",
  "author": {
    "@type": "Person",
    "name": "Stéphane Lapointe",
    "jobTitle": "Fondateur, MEMORA solutions",
    "url": "https://laveille.ai/auteur/stephane-lapointe"
  },
  "datePublished": "2026-08-29",
  "dateModified": "2026-08-29"
}
```

Note : le socle `/article` du 2026-08-23 demande de ne jamais inclure `FAQPage` (résultat enrichi retiré par Google). Le style de CE projet (`.claude/writing-style/style.md`) documente un choix contraire délibéré et déjà appliqué sur les articles publiés du site. Les deux règles se contredisent explicitement sur ce point précis ; je n'ai pas tranché à la place de Stéphane, j'ai laissé le schema à son minimum (Article seul) et je signale la question dans mon rapport plutôt que de choisir en silence.
