# Doctrine de vérification en profondeur

Document produit le 27 août 2026, 13h19 Québec (17:19 UTC).
Auteur : MEMORA solutions (info@memora.ca, https://memora.solutions).

## 0. Portée et méthode

Ce document est le socle commun de vérification de laveille.ai. Il est appelé par les deux skills de rédaction du site, /actu2 (fiches d'actualité courtes) et /article (articles de fond), qui doivent y renvoyer plutôt que le recopier (section 7).

Il répond au mandat du fondateur : rechercher en profondeur pour toujours faire remonter la vérité, s'appuyer sur des recherches fiables, universitaires et autres, afin que ce qui est publié sur laveille.ai devienne la référence.

**Méthode.** Club des sages à trois rounds, sur le sujet précis de cette doctrine : génération divergente en aveugle (round 1), réfutation croisée avec mandat explicite de démolir ses propres idées (round 2), attaque des idées survivantes plus notation chiffrée (round 3). Panel à trois voix sur cinq : Codex (superagent codex), DeepSeek (deepseek-r1, via Hermes en tâche de raisonnement) et Gemini ont répondu aux trois rounds. Perplexity et claude.ai étaient injoignables dans cette session (serveurs absents). Ceci n'est pas une unanimité à cinq déguisée en trois : les trois oracles convergent explicitement sur l'architecture d'ensemble au round 3, et cette convergence est consignée telle quelle plus bas, mais l'absence de Perplexity prive la doctrine d'une vérification de terrain (comment des sites de vérification comparables gèrent le même arbitrage), et l'absence de claude.ai d'un regard éditorial supplémentaire sur la formulation.

**Contrainte de réalité**, posée avant toute recherche et pesant sur chaque arbitrage : laveille.ai est tenu par une seule personne, sans équipe de vérification, publiant plusieurs fois par semaine. Une méthode magnifique mais intenable est contournée dès la première semaine chargée, et une règle contournée est une règle morte. Ce critère pèse autant que la rigueur; c'est le second des trois axes de notation utilisés au round 3 pour juger chaque idée retenue : réel (le gain existe-t-il vraiment), tenable (une personne seule le tient-elle dans la durée, sans charge qui s'accumule), perçu (un lecteur ordinaire le remarque-t-il).

## 1. Principe directeur

Les trois rounds convergent sur un même verdict : la doctrine doit devenir un protocole de triage par préjudice, pas une accumulation de contrôles appliqués uniformément à chaque phrase. Une bonne partie des propositions du round 1, prises isolément, semblaient rigoureuses; soumises à la question « une personne seule un mardi soir chargé peut-elle vraiment faire cela pour chaque fiche? », la plupart ont été jugées trop lourdes et ramenées à une forme réduite, réservée aux affirmations qui comptent vraiment. Les règles A à F qui suivent portent cette logique : un socle léger, obligatoire partout, et des exigences supplémentaires réservées aux affirmations à fort enjeu (règle F).

## 2. Les six règles, version finale après trois rounds

### A. Lire une source académique, pas seulement en exiger une

**R1. Statut de la source.** Avant de citer un article, noter explicitement son statut : préimpression (arXiv, OSF, bioRxiv, SSRN...) ou article relu par les pairs. Le mot « étude » seul, sans ce qualificatif, ne doit jamais laisser croire à une validation par les pairs.
*Vérification* : relire le texte publié; toute occurrence du mot « étude » ou « recherche » sans statut explicite à proximité est une non-conformité.

**R2. Lecture personnelle minimale, jamais déléguée à une IA.** Lire soi-même le résumé et la section des limites de l'article (jamais un résumé produit par une IA, qui peut inventer ou déformer un chiffre pour paraître utile). L'exigence de citation est la suivante : pouvoir écrire en une phrase « les auteurs ont mesuré X, sur Y, dans les conditions Z ».
Cette exigence vaut aussi pour toute référence, citation ou résumé produit par un modèle d'intelligence artificielle, qu'il s'agisse d'un outil de rédaction, d'un oracle du club des sages ou d'un moteur de recherche : aucun n'est cité dans un texte publié sans vérification directe à la source primaire. Ce risque s'est déjà matérialisé deux fois sur ce projet même (ajout du round 4, 27 août 2026, voir section 9).
*Vérification* : cette phrase doit exister, telle quelle, dans les notes de recherche ou dans le texte publié; son absence interdit de citer l'étude à l'appui d'une affirmation.

**R3. Alerte de rétractation, réservée aux affirmations structurantes.** Pour toute étude qui soutient une affirmation centrale, controversée ou classée au niveau Standard ou Complet (règle F), vérifier l'absence de rétractation ou de correction (recherche du titre ou du DOI avec le mot « retraction », consultation de la page de l'éditeur). Cette vérification n'est pas due pour une citation secondaire ou d'appoint : l'imposer à chaque citation a été jugée irréaliste par les trois oracles au round 2 et retirée sous cette forme.
*Vérification* : présence d'une note « rétractation vérifiée le [date] » pour toute étude appuyant une affirmation de niveau Standard ou Complet.

**R4. Conflits d'intérêts déclarés, sans verdict automatique.** Relever le financement et les affiliations déclarés (recherche des mots « funding » ou « competing interests » dans le document). Un conflit n'annule pas le résultat; il interdit de présenter l'étude comme indépendante.
*Vérification* : toute étude financée par une partie intéressée au résultat qu'elle démontre est attribuée explicitement dans le texte publié (« étude financée par [partie] »), jamais présentée sans cette précision quand le financement est connu.

### B. Quand l'université n'a rien à dire

**R5. Hiérarchie des preuves.** En l'absence de littérature pertinente (produit ou modèle sorti récemment) : source primaire (annonce, documentation technique, carte du modèle, dépôt de code) au-dessus de l'observation directe datée et reproductible par laveille.ai, elle-même au-dessus de la confirmation externe indépendante, elle-même au-dessus des réseaux sociaux (pistes seulement, jamais preuve suffisante d'un fait important). Plusieurs documents provenant du même fournisseur comptent comme une seule origine, pas comme plusieurs confirmations.
*Vérification* : chaque affirmation de capacité ou de performance non vérifiée directement par laveille.ai porte le vocabulaire de distance de la règle R6.

**R6. Vocabulaire de distance obligatoire.** Utiliser « [Entreprise] revendique », « selon la documentation », « lors de notre test du [date], nous avons observé... » ou « nous n'avons pas reproduit... », jamais un verbe d'affirmation directe (« le modèle fait X ») pour une capacité que laveille.ai n'a pas vérifiée elle-même.
Un score de performance ou de comparaison (un « benchmark ») est une affirmation comme une autre : il porte le même vocabulaire de distance, et sa reprise au niveau Standard (règle F) exige de lire directement la méthode de mesure (version du modèle, date, protocole, tâches retenues), pas seulement le chiffre annoncé (ajout du round 4, 27 août 2026, voir section 9).
*Vérification* : relecture ciblée de chaque phrase attribuant une capacité à un produit; l'absence d'un marqueur de source dans la phrase ou le paragraphe est une non-conformité.

**R7. L'absence de résultat n'est jamais un démenti.** Une recherche infructueuse s'écrit « nous n'avons pas trouvé [X] dans les sources consultées le [date] », jamais « [X] n'existe pas » ou « c'est un mensonge, un montage ». Nier l'existence d'un contenu exige une source ayant autorité sur ce contenu précis, ou une preuve positive de fabrication, jamais le seul échec d'une recherche. Ce piège a été mesuré sur ce projet le 23 août 2026 : interrogés avec une fenêtre de dates étroite, plusieurs modèles ont déclaré inexistantes deux vidéos parfaitement réelles, et le rédacteur lui-même a crié au contenu fabriqué à tort, la même journée, à quatre reprises.
*Vérification* : toute négation d'existence dans un texte publié est accompagnée d'une source nommée ayant autorité sur le contenu visé; à défaut, correction immédiate selon la règle E.

### C. Le critère d'arrêt, écrit avant de chercher

**R8. Fiche préalable à trois champs**, écrite avant d'ouvrir le premier onglet de recherche : l'affirmation centrale que le texte veut établir; la preuve qui suffirait à l'établir; le budget de temps alloué.
*Vérification* : cette fiche existe comme note de travail, même informelle, pour toute fiche de niveau Standard ou Complet.

**R9. Budgets par défaut, pas des règles absolues.** Quinze minutes pour /actu2, quarante-cinq minutes pour /article. Le niveau d'enjeu (règle F) peut les raccourcir ou les allonger, jamais les rendre indéfinis.
*Vérification* : au niveau Complet, la règle F prévaut explicitement sur l'arrêt anticipé de la présente règle (voir R21); à tout autre niveau, le budget de temps ne s'étire pas sans une question nouvelle et écrite (R10).

**R10. Fin de recherche.** On arrête quand la source primaire est identifiée et que les faits centraux sont confirmés ou clairement attribués, ou quand deux formulations de recherche différentes ne produisent plus d'élément nouveau déterminant. Ce dernier cas n'est pas une preuve d'inexistence : c'est le signal de publier avec un statut « non confirmé » (règle D) ou de renoncer à publier, jamais celui d'élargir silencieusement l'enquête pour forcer la conclusion souhaitée au départ.
*Vérification* : toute prolongation de recherche au-delà du budget de la règle R9 répond à une question nouvelle, écrite dans les notes.

### D. Publier une incertitude comme une conclusion

**R11. Vocabulaire fermé, commun aux deux formats.** Confirmé, attribué, observé, contesté, non confirmé, indéterminable. Aucun autre mot, aucun pourcentage de confiance décoratif.
*Vérification* : tout état de vérification affiché sur le site utilise l'un de ces six mots, textuellement.

**R12. Pour /actu2** : un état de vérification affiché près du début du texte, sous forme courte (exemple : « État de vérification, attribué : l'annonce a été publiée; non confirmé : les performances revendiquées; dernière vérification, le [date] »).
*Vérification* : présence du bloc, daté, sur toute fiche de niveau Standard ou Complet. Le niveau Allégé n'y est pas tenu, pour ne pas bureaucratiser une annonce mineure (voir la mise en garde de la section 4).

**R13. Pour /article** : un encadré à trois rubriques, « ce que nous savons », « ce que nous ignorons », « ce qui pourrait trancher », repris dans la conclusion pour toute incertitude structurante. Chaque incertitude précise sa cause (donnée absente, sources contradictoires, résultat trop récent, accès impossible), sa conséquence (ce qu'elle empêche de conclure) et sa condition de résolution.
*Vérification* : présence de l'encadré et reprise effective dans la conclusion pour toute fiche de niveau Standard ou Complet.

**R14. Les pictogrammes et couleurs aident au repérage visuel; ils n'accompagnent jamais un état sans le mot qui le porte, et ne le remplacent jamais.**
*Vérification* : chaque emoji ou couleur d'état est immédiatement accompagné du mot correspondant de la règle R11.

### E. Se corriger en public, avec la date

**R15. Bloc de correction daté**, en tête de page, avant l'introduction, pour toute erreur substantielle : ce qui était affirmé, pourquoi c'était faux, ce qui remplace, et si la conclusion générale du texte change.
*Vérification* : présence du bloc et de sa date (jour, heure HAE) sur toute fiche corrigée après publication pour un fait, jamais pour une coquille.

**R16. L'ancienne formulation vit dans un journal de correction, jamais barrée dans le corps du texte** : une formulation barrée reste indexable et copiable hors contexte par un moteur de recherche ou un tiers.
*Vérification* : absence de balise de biffure dans le corps principal du texte (une éventuelle biffure appartient seulement au journal de correction, s'il en existe un séparé).

**R17. Une correction diffusée ailleurs (infolettre, réseau social) est reprise au même endroit si l'erreur peut encore causer un tort.**
*Vérification* : pour toute correction de niveau Standard ou Complet, une trace de reprise sur le canal d'origine existe, ou l'absence de canal externe concerné est explicitement notée.

**R18. Les corrections mineures ou purement typographiques restent silencieuses**; seule une erreur qui change un fait, une attribution ou une conclusion déclenche le bloc de la règle R15.
*Vérification* : si le classement mineur ou substantiel n'est pas évident, une phrase de justification est consignée dans le journal de correction.

### F. Gradation par enjeu, jamais par longueur

**R19. Trois niveaux**, déterminés par le préjudice possible et l'irréversibilité, jamais par le nombre de mots, le format ou la popularité du sujet :

- **Allégé** : annonces, sorties de produits, changements d'interface, faits réversibles. Exigence : source primaire, date ou version, attribution explicite.
- **Standard** : comparaisons de modèles, performances, emploi, éducation, droit d'auteur, décisions susceptibles d'influencer un choix. Exigence : Allégé, plus lecture directe des sources déterminantes, plus corroboration indépendante ou signalement explicite de son absence, plus état d'incertitude visible (règle D).
- **Complet** : santé, sécurité physique, fraude, réputation d'une personne, élections, justice, finance, accusations, contenus prétendument faux. Exigence : Standard, plus preuves conservées, plus au moins deux sources primaires indépendantes (seuil chiffré retenu pour lever l'ambiguïté du mot « multiples », relevée comme faille au round 3 par DeepSeek), plus chronologie vérifiée, plus publication reportée ou de portée réduite si l'affirmation centrale reste insuffisamment étayée.

**R20. Le thème n'est pas le préjudice.** Classer d'office un sujet dans une catégorie (par exemple « emploi » ou « droit d'auteur ») ne fixe pas mécaniquement son niveau; c'est le préjudice concret et l'irréversibilité de l'affirmation précise qui comptent. Faille nommée au round 3 par Codex : classer d'office toute affirmation touchant l'emploi ou le droit d'auteur au niveau Complet produirait des vérifications disproportionnées pour des annonces mineures, et une doctrine disproportionnée est une doctrine contournée.
*Vérification* : le niveau retenu se justifie en une phrase par le préjudice concret, pas par le seul thème.

**R21. La règle F prévaut sur le critère d'arrêt général de la règle C au niveau Complet** : une affirmation centrale insuffisamment étayée n'est jamais publiée comme établie sous prétexte que le budget de temps de la règle C est écoulé. Cette précision résout une contradiction nommée par Codex au round 3 entre les deux règles telles que fusionnées au round 2.

**R22. Le doute porte sur un fait précis, jamais sur un état permanent.** Un doute raisonnable sur le niveau applicable, ou une négation de l'existence d'un contenu réel ou présumé réel, fait monter d'un niveau l'affirmation concernée, pas l'ensemble du texte, et pas indéfiniment. Mise en garde retenue du round 3 (Gemini) : si le doute est traité comme un état permanent plutôt que comme le doute sur une affirmation précise, la règle pousse soit au gel indéfini de la publication, soit, à l'inverse, à son contournement par lassitude, les deux issues étant mortelles pour une personne seule. La soupape est la règle D : quand la preuve manque au niveau requis, on ne gèle pas la publication, on publie l'état « non confirmé » ou « indéterminable » pour cette affirmation précise, le reste du texte restant publiable normalement.

**R23. Un antécédent documenté et daté d'un émetteur**, pertinent pour la nouvelle affirmation qu'il formule aujourd'hui, fait monter cette affirmation précise d'un niveau. Sa forme exacte, volontairement plus prudente que ce que deux des trois oracles ont proposé, est détaillée à la règle G (section 3).

**R24. Les opinions clairement identifiées comme telles n'exigent pas de niveau de preuve**; les faits sur lesquels elles s'appuient restent, eux, soumis à la règle qui correspond à leur propre enjeu.

## 3. Deux règles supplémentaires nées de la démolition du round 3

### G. Antécédent de l'émetteur : une mention factuelle, jamais un badge automatique

**La divergence la plus significative de tout l'exercice.** L'idée la plus forte du round 2, que Gemini a nommée « score de casier judiciaire de l'émetteur », propose qu'un fournisseur pris en défaut de démonstration truquée ou de benchmark gonflé voie ses annonces suivantes traitées avec une prudence accrue, plutôt que de repartir de zéro à chaque sortie. Les trois oracles la retiennent au round 3 comme la meilleure idée neuve du round 2, avec les notes les plus hautes des trois grilles (section 6), mais ils divergent sur sa forme.

**Position Gemini (round 3)** : un badge visuel automatique, appliqué à toutes les annonces d'un émetteur pendant une fenêtre de douze mois après un manquement constaté, déplaçant le fardeau de la preuve sur l'émetteur récidiviste. Note attribuée par Gemini elle-même sur ses trois axes : 5, 5, 5 (125 sur 125).

**Position DeepSeek (round 3)** : conserve l'idée d'un badge automatisable (« prudence, antécédents de démonstrations trompeuses »), sans fixer de durée, jugé tenable « si automatisable ». Note : 5, 4, 5 (100 sur 125).

**Position Codex (round 3), retenue ici comme règle finale** : rejette explicitement le format « score » ou « badge automatique sur toutes les annonces futures » et la fenêtre fixe de douze mois, jugés arbitraires et potentiellement diffamatoires pour un site tenu par une seule personne sans service juridique. Codex propose à la place un antécédent documenté, daté, relié à la nouvelle affirmation précise qu'il concerne, déclenchant une vérification renforcée pour cette affirmation, sans étiquette permanente apposée à l'ensemble des publications futures de l'émetteur. Note : 4, 3, 4 (48 sur 125, la plus basse des trois, précisément parce que Codex retire lui-même de la portée de l'idée tout ce qui la rendrait automatique).

**Arbitrage retenu : la position Codex, la plus prudente des trois.** Motif : un site tenu par une seule personne, sans révision juridique, ne peut pas soutenir un mécanisme qui appose une étiquette de mauvaise foi à toutes les publications futures d'une entreprise nommée, sur une fenêtre fixe et sans réexamen au cas par cas. Le risque de préjudice pour laveille.ai elle-même (contestation, plainte, perception de vendetta éditoriale) dépasse le gain de temps que procurerait l'automatisation complète. Cet arbitrage n'est pas un moyennage des trois notes : c'est un choix explicite en faveur de la position la plus défendable juridiquement pour l'opérateur du site, les deux autres positions restant consignées ci-dessus plutôt qu'effacées.

**R25.** Lorsqu'un émetteur a un antécédent documenté et daté de présentation trompeuse (démonstration montée, benchmark gonflé, capacité annoncée puis démentie), pertinent pour la nouvelle affirmation qu'il formule aujourd'hui, cette affirmation précise monte d'un niveau (règle F, R23), et le texte publié mentionne factuellement l'antécédent, sa date et sa source, jamais un badge, un score ou une mention globale s'appliquant à toute mention future de cet émetteur.
*Vérification* : toute mention d'antécédent dans un texte publié cite une date et une source précises; l'absence de ces deux éléments interdit la mention.

**R26.** Aucune base de données de réputation par émetteur n'est tenue à jour en continu. Élimination retenue au round 3 : la « cartographie des biais sources » (idée neuve de DeepSeek au round 2) a reçu la note la plus basse des trois idées neuves sur l'axe tenable dans les trois grilles, jugée à charge croissante sans fin par les trois oracles.
*Vérification* : absence, dans l'architecture du site, d'un registre central de scores de fiabilité par source ou par émetteur alimenté en continu.

### H. Traçabilité réduite des affirmations réutilisées

L'idée neuve de Codex au round 2, une fiche de preuve réutilisable pour chaque affirmation structurante reliant sa formulation, sa source, son état et toutes les pages qui la reprennent, est jugée par les trois oracles au round 3 réelle mais trop lourde sous sa forme complète pour 4 573 fiches vivantes (notes intermédiaires, ni les plus basses ni les plus hautes des trois grilles).

**R27.** La traçabilité d'une affirmation (identifiant, état, source, pages qui la reprennent) n'est tenue que pour les affirmations effectivement réutilisées sur plusieurs pages, ou classées au niveau Complet.
*Vérification* : avant de créer une entrée de traçabilité, au moins une des deux conditions ci-dessus est remplie; à défaut, l'affirmation reste documentée localement dans son propre texte, sans registre séparé.

**R28.** Lorsqu'une affirmation tracée au sens de R27 est corrigée, toutes les pages qui la reprennent sont retrouvées et corrigées dans le même geste.
*Vérification* : la liste des pages associées à l'entrée de traçabilité est consultée et cochée au moment de toute correction visée par la règle E.

## 4. Mise en garde du 27 août 2026 : ne pas alourdir pour la machine ce qui doit rester lisible pour l'humain

Deux faits mesurés le 27 août 2026 pèsent directement sur l'application des règles D et F ci-dessus. Aucun des trois oracles n'en disposait au moment de répondre; ils sont ajoutés ici comme correctif final, pas comme une septième règle indépendante.

**Premier fait.** Sur les 68 articles du site, 71 % de ceux qui apparaissent dans les résultats de recherche Google ne reçoivent aucun clic; le pire cas cumule 1 708 impressions pour zéro clic. Le problème mesuré n'est donc pas d'être trouvé, c'est d'être ouvert.

**Second fait.** Les 19 articles au balisage le plus structuré pour la machine cumulent 6 397 impressions pour 3 clics, un taux seize fois pire que la moyenne du site.

**Conséquence normative :**

**R29.** Les blocs de vérification prescrits par la règle D (état de vérification, encadré « ce que nous savons ») s'écrivent comme un texte que lit un humain pressé, jamais comme un balisage machine supplémentaire. Ils ne s'ajoutent pas à un schéma structuré pour l'occasion; s'ils recoupent un balisage déjà existant (le module de vérification et son balisage ClaimReview), ils ne le dupliquent pas.
*Vérification* : le bloc reste lisible et utile si tout balisage machine est retiré de la page.

**R30.** Le titre et l'amorce d'une fiche ou d'un article restent rédigés pour donner envie de l'ouvrir, jamais sacrifiés à la densité de mots-clés ou à la conformité d'un schéma. En cas de tension entre une formulation optimisée pour un moteur et une formulation qui donne envie de lire, la seconde l'emporte.
*Vérification* : lecture à voix haute du titre et de la première phrase; un titre qui ne se lit pas comme une phrase qu'on dirait à un collègue est reformulé.

**R31.** Cette doctrine ne prescrit aucune nouvelle exigence de balisage machine, de longueur ou de densité structurelle. L'ajout de structure pour la machine reste une décision distincte, justifiée par une preuve d'ouverture (un clic), jamais par une preuve d'indexation (une impression) seule.
*Vérification* : toute proposition future d'alourdir un balisage répond d'abord à la question suivante, par écrit : est-ce que cela a fait cliquer, pas seulement apparaître?

## 5. Ce qui a été écarté, et pourquoi

| Proposition écartée | Origine | Motif retenu |
|---|---|---|
| Mini-fiche académique en neuf points pour chaque étude citée | Codex, round 1 | Trop lourde pour chaque citation; réduite en R1 à R4, la vérification de rétractation réservée aux affirmations structurantes (R3) |
| Croisement obligatoire de trois sources primaires avant publication | DeepSeek, round 1, point B | Rarement possible le jour de sortie d'un produit; remplacé par la hiérarchie de R5 (une seule origine si même émetteur) |
| Classement des revues par quartile SJR (Scimago Journal Rank) comme filtre automatique | DeepSeek, round 1, point A | Le prestige d'une revue n'établit pas la solidité d'un résultat précis |
| Extension de navigateur PubPeer comme outil obligatoire pour chaque source | Gemini, round 1, point A | Dépendance technique non universelle pour une personne seule; recherche manuelle ponctuelle réservée aux affirmations structurantes (R3) |
| Consultation systématique de Retraction Watch pour toute étude citée | DeepSeek, round 1, point A | Accès non garanti, incompatible avec une vérification de moins d'une minute par citation; réservé aux affirmations structurantes (R3) |
| Conserver l'erreur barrée dans le corps du texte corrigé | DeepSeek, round 1, point E | Nuit à la lecture, reste indexable et copiable hors contexte; remplacé par le journal de correction (R16) |
| Hashtag annuel de correction dans les métadonnées | DeepSeek, round 1, point E | Utile surtout à l'auteur, peu perceptible par le lecteur, redondant avec le journal de correction |
| Seuil « plus d'un million d'utilisateurs ou d'euros » pour déclencher le niveau Complet | DeepSeek, round 1, point F | Arbitraire, sans lien démontré avec le préjudice réel; remplacé par la logique de préjudice et d'irréversibilité (R19, R20) |
| Ne pas vérifier un fait repris par plus de cinq médias généralistes | DeepSeek, round 1, point F | Cinq médias peuvent reproduire la même dépêche non vérifiée de manière indépendante |
| Délai systématique de vingt-quatre à quarante-huit heures avant toute publication de niveau Complet | Gemini, round 1, point F | Délai arbitraire; un consensus de plateforme sociale n'est ni garanti ni nécessairement compétent; remplacé par le report ciblé de R19 et R21 quand la preuve manque, pas un délai fixe |
| Date de péremption ou « time to live » uniforme affichée sur chaque fiche | Codex et Gemini, idées neuves du round 1 | Jugée cosmétique par deux oracles sur trois au round 2 : avertir qu'une fiche est vieille ne la met pas à jour. L'esprit du principe (les affirmations les plus volatiles méritent une revérification prioritaire) survit sous forme éditoriale dans R23 et R25, pas sous forme de bannière automatique |
| Journal des preuves négatives publié chaque semaine | DeepSeek, idée neuve du round 1 | Seconde publication à entretenir indéfiniment, fondée sur des recherches infructueuses difficiles à interpréter, risque de transformer une absence de preuve en insinuation |
| Cartographie des biais des sources avec score de fiabilité par domaine | DeepSeek, idée neuve du round 2 | Base à mettre à jour sans fin; note la plus basse des trois idées neuves sur l'axe tenable dans les trois grilles du round 3 (R26) |
| Score de casier judiciaire sous forme de badge automatique appliqué à toute annonce future pendant douze mois | Gemini, idée neuve du round 2, version forte | Retenue en partie mais reformulée de façon plus prudente; voir R25 et l'arbitrage de la section G |
| Carte de preuve réutilisable sous sa forme intégrale, pour l'ensemble des affirmations du site | Codex, idée neuve du round 2 | Réelle mais trop lourde pour 4 573 fiches vivantes; réduite en R27 et R28 aux seules affirmations effectivement réutilisées ou de niveau Complet |
| Nombre de sources en cases à cocher (« deux primaires plus une contradictoire, ou trois concordantes ») comme critère d'arrêt | DeepSeek, round 1, point C | Compter des sources ne mesure ni leur indépendance ni leur qualité; remplacé par le critère qualitatif de C (R8 à R10) et le seuil quantitatif ciblé de F, réservé au niveau Complet (R19) |

## 6. Notation des trois idées neuves survivantes au round 2 (round 3, tableau consolidé)

Échelle par axe : 1 (faible) à 5 (fort). Produit des trois axes, maximum 125.

| Idée neuve | Réel × Tenable × Perçu, Codex | Réel × Tenable × Perçu, DeepSeek | Réel × Tenable × Perçu, Gemini | Moyenne des trois produits |
|---|---|---|---|---|
| Carte de preuve réutilisable (Codex) | 5 × 2 × 2 = 20 | 4 × 2 × 3 = 24 | 4 × 1 × 2 = 8 | 17,3 |
| Cartographie des biais des sources (DeepSeek) | 3 × 1 × 2 = 6 | 3 × 1 × 2 = 6 | 3 × 2 × 3 = 18 | 10,0 |
| Antécédent de l'émetteur, « casier judiciaire » (Gemini) | 4 × 3 × 4 = 48 | 5 × 4 × 5 = 100 | 5 × 5 × 5 = 125 | 91,0 |

Les trois oracles convergent sur le classement (antécédent de l'émetteur en tête, cartographie des biais en dernier); ils divergent fortement sur l'intensité, en particulier pour l'antécédent de l'émetteur (48 chez Codex contre 125 chez Gemini). Cette divergence n'a pas été moyennée pour trancher la forme de la règle finale : elle est résolue à la section 3, règle G, en faveur de la position la plus prudente.

## 7. Où cette doctrine doit être appelée, jamais recopiée

Une même règle écrite à deux endroits finit toujours par diverger; c'est déjà arrivé sur ce poste. Les deux skills doivent donc pointer vers ce document plutôt que d'en reproduire le contenu.

**Dans le skill /actu2** (`~/.claude/skills/actu2/SKILL.md`) :
- La section « Étape 3, rédaction », sous-section « le verdict de vérification » (module vérification), doit renvoyer aux règles D, F et G pour la formulation de l'état de vérification, le niveau applicable et la mention éventuelle d'un antécédent de l'émetteur.
- La section « Étape 4, preuve », y compris sa sous-section « montrer la preuve, jamais un cadre tiers », doit renvoyer aux règles A, B et C pour la lecture d'une source académique, la hiérarchie des preuves en l'absence de littérature, et le critère d'arrêt.
- Le placement et la cadence d'affichage des verdicts sur le site (filtre, badge, pied de page) restent régis par la décision de panel distincte du 27 août 2026, `docs/specs/2026-08-27-exposition-verifications-panel.md`; ce document-ci ne tranche pas cette question et ne doit pas être cité pour le faire.

**Dans le skill /article** (`~/.claude/skills/article/SKILL.md`) :
- La section « Étape 2, recherche et fact-check par section », sous-section « fact-check documenté par section », doit renvoyer aux règles A, B et C.
- La section « Étape 5, double boucle de vérification des sources », y compris sa sous-section « réconciliation finale affirmation et source », doit renvoyer aux règles A, F, H et E.
- La section « Étape 0, socle de preuve SEO, AEO et GEO » doit renvoyer à la section 4 du présent document (R29 à R31) avant tout ajout de balisage ou de structure supplémentaire, pour éviter qu'une exigence de forme y contredise la mise en garde du 27 août 2026.

Aucune modification n'a été apportée aux fichiers des deux skills dans le cadre de la présente rédaction : les renvois ci-dessus sont une recommandation à appliquer par un commit distinct, pas un état déjà en vigueur.

## 8. Composition du panel, honnêtement

Ce document est une synthèse de panel à trois voix sur cinq, pas un audit exhaustif ni une mesure de terrain sur d'autres sites de vérification. Perplexity et claude.ai étaient absents de cette session (serveurs indisponibles) et n'ont donc pas pu apporter leur point de vue, en particulier sur la fréquentation réelle d'autres démarches de vérification comparables. Les trois oracles interrogés (Codex, DeepSeek, Gemini) convergent explicitement au round 3 sur l'architecture d'ensemble des règles A à F; ils divergent sur la forme exacte de la règle G, divergence consignée et arbitrée à la section 3, plutôt que moyennée en silence. Cette doctrine devrait être revue si Perplexity ou claude.ai redeviennent joignables et apportent un élément contraire non anticipé ici.

Mise à jour du 27 août 2026, 14h41 Québec (18:41 UTC) : cette relecture a eu lieu (round 4, section 9); elle n'a apporté que Perplexity, partiellement, claude.ai restant injoignable ce jour-là pour une raison de quota plutôt que de serveur absent.

## 9. Round 4 du 27 août 2026, 14h41 Québec (18:41 UTC) : tentative de jonction de Perplexity et de claude.ai

Suite à la mise en garde de la section 4, une tentative distincte a cherché à joindre les deux oracles absents des rounds 1 à 3, sur le document déjà fini, avec mandat explicite de l'attaquer sans indulgence. Le résultat est honnête plutôt que complet : un des deux a répondu partiellement, l'autre n'a pas pu répondre du tout, pour une raison technique vérifiée et non contournable dans la session.

**Perplexity.** Quatre tentatives ont été faites, avec des formulations différentes (texte intégral, condensé, liste brute des règles, mode recherche approfondie). À chaque fois, l'outil a soit demandé le texte des règles pourtant déjà transmis en entier, soit répondu à une seule phrase isolée du message plutôt qu'à l'ensemble. Ce comportement, reproductible sur quatre essais distincts, pointe vers une limite technique de l'outil de recherche face à un document long soumis pour analyse plutôt que pour une recherche web, et non vers un refus de fond. Une des quatre tentatives a néanmoins produit une critique substantielle, non chiffrée et non liée à des numéros de règle précis, portant sur : l'absence d'une taxonomie des types d'affirmation (fait observé, promesse de fournisseur, résultat expérimental, interprétation, prévision); le risque de reprendre un score de performance sans vérifier le protocole de mesure (« benchmark laundering »); le conflit d'intérêts structurel d'un site tenu par une seule personne, qui est à la fois collecteur, sélectionneur, vérificateur et arbitre de ses propres corrections; et le caractère absolu, donc difficile à démentir en cas d'erreur normale, de la promesse « devenir la source de la vraie information ». Perplexity n'a nommé aucune règle précise à supprimer, faute d'avoir pu lire la liste numérotée.

**claude.ai.** La session du navigateur était connectée sur le compte du propriétaire, confirmé par la présence de son historique de conversations. Le message a été saisi en entier puis soumis à trois reprises (une touche Entrée, deux clics sur le bouton d'envoi). Les trois tentatives ont échoué avec la même erreur serveur, visible dans la console du navigateur : HTTP 429, type « exceeded_limit », fenêtre de sept jours à 100 % d'utilisation, remise à zéro prévue le 28 août 2026, 08h00 Québec (12:00 UTC). Ce n'est pas un refus de contenu ni un problème de connexion : c'est un compte à quota hebdomadaire épuisé, un fait vérifiable et non contournable avant l'heure de remise à zéro. claude.ai n'a donc rien pu répondre dans cette session.

**Conséquence sur la composition du panel.** Le panel n'atteint pas cinq voix pleines. Il reste : trois voix complètes ayant fabriqué la doctrine (Codex, DeepSeek, Gemini), une contribution partielle et générique de Perplexity (utile mais non structurée par numéro de règle), et une absence documentée de claude.ai pour une raison technique datée et vérifiée plutôt que pour un désintérêt ou un refus. La section 8 reste donc, pour l'essentiel, exacte : ce document est une synthèse à trois voix sur cinq enrichie d'un apport partiel d'une quatrième.

**Traitement des objections de Perplexity, une par une, selon la règle du projet (accepté ou rejeté, motif écrit) :**

1. **La promesse « devenir la source de la vraie information » est trop absolue.** Rejeté tel quel. Motif : cette formule est le mandat du fondateur lui-même, pas une clause de la doctrine; ce document n'a pas autorité pour l'affaiblir. L'esprit de l'objection (rendre la promesse falsifiable) est déjà satisfait par le vocabulaire fermé à six mots de la règle D (R11 à R14), qui interdit précisément toute affirmation catégorique invérifiable sur le site.

2. **Absence d'une taxonomie des types d'affirmation (fait, promesse, résultat expérimental, interprétation, prévision).** Rejeté. Motif : le principe directeur (section 1) et la mise en garde de la section 4 interdisent d'ajouter un contrôle non requis par un préjudice démontré; une septième catégorie à codifier en plus du vocabulaire déjà fermé (R11) alourdirait la doctrine sans preuve qu'elle change une décision de publication réelle pour un opérateur seul. Le noyau utile, distinguer une promesse d'un fait vérifié, est déjà couvert par R6.

3. **Reprise d'un score de performance sans vérifier le protocole de mesure (benchmark détourné).** Accepté. Motif : c'est un angle mort réel et spécifique à un site de veille en intelligence artificielle, où les scores comparatifs sont la matière première des annonces couvertes; ni R6 ni R19 ne le rendaient explicite. Plutôt que d'ajouter une trente-deuxième règle, ce qui contredirait le principe de légèreté du document et fausserait le compte « 31 règles » répété ailleurs dans le texte, l'objection est pliée dans R6 (section 2, règle B, phrase ajoutée le 27 août 2026).

4. **Conflit d'intérêts structurel d'un site tenu par une seule personne (collecteur, sélectionneur, vérificateur et arbitre de ses propres corrections).** Rejeté comme mesure concrète, reconnu comme limite. Motif : les remèdes usuels (délai de refroidissement, sollicitation contradictoire par un tiers) supposent une deuxième personne qui n'existe pas sur ce projet et contrediraient directement la contrainte de réalité posée en section 0. Le problème est réel mais déjà partiellement absorbé par R8 (fiche préalable écrite avant recherche) et R15 à R18 (transparence des corrections); il est consigné ici comme une limite structurelle assumée du site, jamais prétendue résolue par une procédure.

5. **Risque qu'une référence produite par une intelligence artificielle (résumé, citation, numéro d'étude) soit fausse.** Accepté. Motif : ce risque n'est pas hypothétique sur ce projet : un oracle du panel a déjà fabriqué des résultats d'étude de toutes pièces, un autre a attribué à trois études des conclusions qui n'y figurent pas. R2 interdisait déjà de déléguer la lecture d'un article à une IA; l'objection élargit ce principe à toute référence produite par un modèle, quel qu'il soit. Plié dans R2 (section 2, règle A, phrase ajoutée le 27 août 2026).

Aucune des cinq objections n'a entraîné l'ajout d'une règle numérotée supplémentaire. Deux ont été pliées dans des règles existantes (R2, R6), une a été reconnue comme limite assumée sans remède procédural, deux ont été rejetées avec un motif écrit. Aucun chiffre nouveau n'a été introduit par ces objections : Perplexity a respecté la consigne de ne rien inventer et n'a cité aucun résultat d'étude à l'appui de sa critique, ce qui rend la vérification à la source sans objet pour ce round.
