# Changelog

All notable changes to this project will be documented in this file.

## [1.160.0] - 2026-08-10

### Ajouté
- **Annuaire : vignettes bien cadrées + point focal réglable (boucle des 5 IA en 3 rounds, design doc docs/specs/2026-08-10-screenshots-annuaire-design.md)**. Réponse au problème des captures mal positionnées (titre du héro coupé, grands vides). Quatre briques : (1) chaque capture conserve désormais une **image maître** du viewport complet (1200x1400, public/screenshots/masters/) et la vignette 1200x630 en est **dérivée d'un point focal vertical réglable** - dans l'admin, un nouveau bloc « Repositionner la vignette » permet de glisser l'image (souris, clavier et curseur, WCAG AAA) puis d'appliquer le cadrage en un clic, de façon non destructive et corrigeable à volonté ; (2) **capture automatique stabilisée** : animations gelées par CSS injecté, masquage géométrique des bandeaux plein écran (borné à 1,5 s, jamais un header ou un héro légitime contenant un h1/nav), statut de navigation explicite, attente de stabilité bornée - le cadrage par défaut du premier écran est bon du premier coup (vérifié en conditions réelles sur wondering.com) ; (3) **fallback og:image normalisé** : recadré en 1200x630 (cover, ou contain sur fond flouté pour les logos carrés et bannières très larges - zéro contenu coupé) avec garde anti-bombe (10 Mo / 8000 px) au lieu d'être écrit brut ; (4) **mort du garde-fou anti-écrasement par octets** (hérité de l'incident S79) qui rendait une mauvaise vignette lourde irremplaçable : remplacé par le verrou humain screenshot_locked + une validation de contenu (image décodable, dimensions exactes, non quasi uniforme, rejet des pages bloquées) + un backup .bak avant chaque remplacement. Migration additive (screenshot_focal_y), contrat News (ScreenshotUploadService) strictement intact, purge Cloudflare ciblée mutualisée (DRY). Revue adversariale passée (7 angles, 3 correctifs appliqués), 216 tests Directory+News verts, dérivation focale prouvée pixel par pixel.

### Corrigé
- Recapture d'un même outil : la date de mise à jour avance désormais même quand aucun champ ne change, pour que le cache-bust `?v=` serve toujours la nouvelle image (défaut préexistant relevé par la revue adversariale).

## [1.159.1] - 2026-08-09

### Corrigé
- **news:fetch mourait en épuisement mémoire à CHAQUE exécution horaire depuis des jours (bug préexistant, découvert pendant l'activation contrôlée d'Actus 2.0)**. Les logs prod montrent ~20-22 « Allowed memory size of 134217728 bytes exhausted » par jour depuis au moins le 6 août, un à chaque cron de XX:15. Cause racine mesurée : la file « articles sans résumé structuré et non publiés » n'avait aucune borne temporelle - les articles sautés par quota s'y accumulaient indéfiniment (12 436 articles, ~43 Mo de texte brut, rechargés intégralement en mémoire à chaque exécution). Correctif : news:fetch ne (re)traite plus que les articles créés dans la fenêtre `news.fetch_backlog_hours` (48 h par défaut, surchargeable via NEWS_FETCH_BACKLOG_HOURS) - une actualité plus vieille n'a plus vocation à être résumée. Nouveau test de non-régression (un article de 3 jours n'est plus retraité ni ne déclenche d'appel IA). Suite News : 134 tests verts (353 assertions).

## [1.159.0] - 2026-08-09

### Ajouté
- **Actus 2.0 : fusion multi-sources des actualités (derrière un drapeau, désactivé par défaut)**. Chantier issu de la boucle des 5 oracles (3 rounds) et d'un design doc complet (docs/specs/2026-08-09-actus-fusion-design.md) : au lieu de 15 fiches isolées par jour, les articles couvrant le même sujet sont regroupés (clustering déterministe par similarité de titres et d'entités, zéro API externe) et produisent UNE fiche comparative croisant les sources - divergences entre médias, mémoire de nos archives (« ce qui a changé depuis »), angle canadien seulement quand une donnée vérifiable existe, chaque source citée avec son auteur (art. 29.1/29.2 LDA). Quota fixe d'indexation quotidien (défaut 5) : au-delà, les fiches naissent noindex (réutilise le mécanisme d'élagage réversible). Un appel IA par GROUPE au lieu d'un par article = coût réduit. Réutilise l'infrastructure de déduplication d'avril 2026 (is_potential_duplicate_of, news_dedup_log) - une seule colonne ajoutée. Revue adversariale passée : 2 failles corrigées avant livraison (le contournement du DEDUP-SKIP qui aurait laissé publier des republications en doublon ; les effets de bord d'observer sur les membres absorbés) + garde anti-injection de prompt dans les deux prompts IA. Tout le comportement est inerte tant que NEWS_FUSION_ENABLED n'est pas activé : drapeau éteint = pipeline strictement identique (critère testé). 133 tests News verts (348 assertions), rendu vérifié visuellement (fiche comparative + bandeau des pages membres).

## [1.158.1] - 2026-08-09

### Corrigé
- **Constructeur de prompts, étape 4 : lecture verticale des cases à cocher sur desktop**. Le panel de 5 oracles sur l'intuitivité de la compaction (verdict : neutre, l'orientation s'améliore mais pas la compréhension) a livré une condition de validité précise : l'exception « groupes de cases homogènes » à la règle « une colonne » des formulaires (Baymard/NN/g) ne vaut que si la lecture se fait de haut en bas par colonnes. Or la grille CSS plaçait les 6 options en flux Z (1-2 / 3-4 / 5-6). Passage de `grid` à `columns:2` (multicolonnes) : options 1-3 en colonne gauche, 4-6 à droite, aucune case coupée entre colonnes (`break-inside:avoid`), cibles >= 44 px conservées, mobile inchangé (1 colonne).

## [1.158.0] - 2026-08-09

### Modifié
- **Élagage SEO des actualités : recalibré et enfin actif (préparation du réexamen AdSense)**. L'audit pré-réexamen a mesuré que 80 % des URL indexables (5588 fiches d'actualités sur 7017) sont des résumés courts (~350 mots) de nouvelles externes - le profil exact du refus AdSense « contenu à faible valeur » d'avril, alors que le reste du site est riche (articles ~4000 mots, annuaire 1500-4000, glossaire, outils). Le système d'élagage réversible existait (noindex,follow + auto-guérison + exclusion du sitemap) mais était calibré 12 mois/30 vues sur un site de 7 mois : 0 fiche élaguée depuis sa création. Recalibrage mesuré : fenêtre de fraîcheur 2 mois / 300 vues (médiane réelle à 2 mois : 237 vues) - ~3497 fiches périmées sortiront de l'index, les populaires et les récentes restent. Planification passée de mensuelle à quotidienne (02h10 Québec) dans le module News. Perte SEO mesurée négligeable (1 à 12 clics Google par fiche sur 28 jours). Validé par Codex (critère âge + popularité plutôt que nombre de mots ; attendre le recrawl 1-3 semaines avant de redemander l'examen AdSense).

## [1.157.1] - 2026-08-09

### Corrigé
- **Faux courriel « The schedule did not run yet » à chaque déploiement : cause racine structurelle éliminée** (2 alertes le 2026-08-09, une par déploiement, malgré le correctif du 2026-08-01). La vraie cause : dans le planificateur, `health:check` (qui vérifie et notifie) était enregistré AVANT `health:schedule-check-heartbeat` (qui écrit le témoin) - à la première minute suivant l'`optimize:clear` du déploiement, le contrôle lisait un témoin absent et envoyait le courriel avant que la réécriture du pipeline (étape SSH séparée, quelques secondes plus tard) n'arrive. Double correctif : (1) ordre inversé dans routes/console.php - le témoin est reposé dans la même passe du planificateur, avant la vérification ; (2) le heartbeat est aussi exécuté dans la même commande SSH qu'`optimize:clear` dans le pipeline (fenêtre réduite à néant), l'étape dédiée existante restant en filet.

## [1.157.0] - 2026-08-09

### Ajouté
- **Constructeur de prompts : enveloppe visuelle des groupes de l'étape « Options » (« quoi va avec quoi »)** (demande du fondateur, convergente avec les propositions spontanées de Gemini et DeepSeek au panel intuitivité ; option A retenue à 94-95/100 par le club des sages contre barre d'accent, carte par groupe, espacement seul et code couleur). Chaque groupe (« Apparence de la réponse », « Voix et niveau de langage », « Règles à respecter ») reçoit un fond teal très pâle (3,5 %) à coins arrondis qui englobe ses cartes blanches - la frontière entre thèmes devient visible d'un coup d'œil, sans rien cacher ni retirer, cibles tactiles intactes. Garde-fous appliqués : fond extrêmement pâle (pas d'air « désactivé »), un seul signal de délimitation, marge inter-groupes réduite en compensation (+3 % de hauteur seulement). Preuves : captures desktop et mobile, 30 tests Pest, 73/73 tests JS.

## [1.156.1] - 2026-08-09

### Modifié
- **Constructeur de prompts : consigne « Facultatif : coche toutes les options utiles. » au-dessus de la grille des règles** (verdict de la consultation du club des sages sur l'intuitivité de la compaction, appuyé sur NN/g checkboxes-design-guidelines : pour des novices, expliciter la multi-sélection est la condition qui rend une grille de cases à cocher aussi claire qu'une colonne unique). Clé i18n ajoutée dans lang/en.json.

## [1.156.0] - 2026-08-09

### Modifié
- **Constructeur de prompts : l'étape « Options » est plus compacte, sans rien cacher ni retirer** (go du fondateur sur le verdict unanime de la boucle 3 rounds : compaction plutôt qu'accordéons, onglets ou 5e étape, tous rejetés). Sur ordinateur, les 6 cases à cocher des règles passent en 2 colonnes et les champs « Format de sortie » et « Longueur précise » partagent la largeur ; les marges entre blocs sont resserrées (l'espacement entre groupes reste supérieur à l'espacement interne, exigence du panel). Sur mobile, disposition inchangée (1 colonne). Cibles tactiles >= 44 px intactes, aucun texte du prompt touché. Mesure réelle : la carte passe de 2919 à 2579 px, la zone des trois groupes d'options de ~1960 à ~1622 px (-17 %). Preuves : captures desktop et mobile, 366 tests Pest, 73/73 tests JS.

## [1.155.0] - 2026-08-09

### Ajouté
- **Constructeur de prompts : l'étape courante est reflétée dans l'URL** (demande du 2026-08-09 : « quand on est à l'étape x dans l'outil, le mettre dans le slug pour si on rafraîchit »). L'URL porte maintenant `#etape-2` à `#etape-4` selon l'étape active (via `history.replaceState` : zéro pollution de l'historique de navigation, zéro impact serveur ou cache) ; à l'étape 1 le hash est retiré. Au chargement, l'étape du hash est restaurée SEULEMENT si les prérequis des étapes précédentes sont remplis - jamais de saut arbitraire vers un formulaire vide. Limite assumée : un rafraîchissement complet vide aussi les champs (aucun brouillon automatique n'existe), la restauration bénéficie donc surtout aux parcours où l'état persiste (retour arrière, partage d'un lien pendant la session). Preuves : 3 assertions JS dédiées (73/73), 366 Pest Tools, navigateur réel (étape 2 → `#etape-2`, retour étape 1 → hash retiré).

## [1.154.4] - 2026-08-09

### Ajouté
- **Constructeur de prompts : avis à la création d'un espace dont le texte apparaît plusieurs fois** (question du 2026-08-09 : « "Mon nom" sera toujours remplacé partout ? »). Le remplacement global (publipostage) est le comportement voulu et conservé ; l'outil affiche désormais un toast informatif au moment de créer l'espace : « Ce texte apparaît N fois : chaque endroit sera remplacé par ta réponse. » - information, jamais un blocage. Réutilise le comptage borné existant (`_countBoundedOccurrences`). Preuves : capture navigateur du toast (2 occurrences), 70/70 tests JS espaces, 366 Pest Tools, TranslationTest 28/28.

## [1.154.3] - 2026-08-09

### Corrigé
- **Constructeur de prompts : l'aperçu « Voici ce qui sera envoyé à l'IA » ignorait les valeurs remplies des espaces** (signalement avec capture) - la tâche affichait toujours le mot de départ (« Mon nom ») même après avoir rempli l'espace (« Stéphane »), alors que le prompt copié était, lui, correct. L'aperçu résumé passe maintenant par les mêmes règles de remplacement que le prompt final (frontières de mots, priorité aux textes longs) : nouvelle méthode `_fillSpacesInText()` branchée sur les deux branches de `promptSummary`. Preuves : 3 assertions JS dédiées (70/70 vertes), 366 tests Pest du module Tools verts, capture navigateur montrant « Stéphane » dans l'aperçu.

## [1.154.2] - 2026-08-09

### Corrigé
- **Constructeur de prompts : le panneau « Voir le texte exact envoyé à l'IA » affichait des espaces parasites** - un grand vide avant la première phrase et des décalages entre les segments (signalement avec capture). Le prompt réel copié et envoyé à l'IA a toujours été propre (le compteur de caractères était le bon) : le panneau, qui préserve les espaces pour afficher fidèlement le texte, rendait aussi l'indentation de son propre gabarit. Le balisage interne du panneau est maintenant compact : le texte affiché est identique caractère pour caractère au prompt réel (prouvé en navigateur : 1024 = 1024 caractères).

## [1.154.1] - 2026-08-09

### Corrigé
- **Bouton Copier : repli quand le presse-papiers est indisponible** - la fonction partagée `copyToClipboard()` appelait l'API moderne du presse-papiers sans vérifier sa présence ; en contexte non sécurisé (HTTP, environnements restreints), l'appel échouait avant même d'afficher un message : ni copie, ni toast. Une garde vérifie maintenant la disponibilité de l'API et bascule sur la méthode classique (`execCommand`) avec les mêmes messages de confirmation ou d'erreur. Invisible en production (HTTPS), mais tous les boutons Copier du site deviennent robustes dans tout contexte. Prouvé en navigateur : la copie et son toast fonctionnent désormais là où ils échouaient silencieusement.

## [1.154.0] - 2026-08-09

### Ajouté
- **Constructeur de prompts : espaces à remplir rendus robustes et plus intuitifs (boucle de 5 IA en 3 rounds - la question « faut-il un identifiant caché sans accents ? » a été tranchée : non, à l'unanimité ; le vrai risque était la forme invisible des caractères, pas les accents)** :
  - normalisation des comparaisons - un texte collé depuis Word avec une apostrophe courbe, un espace insécable ou un accent encodé différemment est maintenant reconnu comme identique au texte tapé : les pastilles ne deviennent plus « introuvables » pour une différence invisible à l'œil (le texte tapé et le prompt copié restent intacts au caractère près - seule la comparaison est tolérante) ; les valeurs déjà mémorisées migrent sans perte (en cas de doublon entre deux formes du même texte, la forme encore présente dans la demande gagne, sinon la plus récente - rien n'est écrasé) ;
  - garde-fou à la fusion - renommer une pastille vers un texte déjà présent ailleurs dans la demande affiche une confirmation claire (« Ce texte apparaît déjà N fois - toutes les occurrences seront remplies ensemble ») au lieu de fusionner en silence ; le compte respecte les mots entiers (« client » ne compte pas « clientèle ») ;
  - avis au moment de copier - si un espace à remplir n'existe plus dans le texte (parce que la phrase a été retouchée), une ligne discrète le signale près du bouton Copier, sans rien bloquer ;
  - promesse d'usage en une ligne au-dessus du champ principal (« Écris ta demande une fois - réutilise-la en changeant seulement quelques mots. »).
- **Clarté du parcours des espaces (couche complémentaire)** : chaque pastille est présentée comme « un bout de texte de ta demande », note explicite « accents et espaces bienvenus », pastille orpheline signalée en clair (« introuvable dans le texte ») et message persistant après l'insertion d'un espace (lisible aussi par les lecteurs d'écran).

## [1.153.0] - 2026-08-07

### Ajouté
- **Constructeur de prompts : vague 1 de bonifications (boucle de 5 IA en 3 rounds, zéro coût récurrent - tout est texte statique et mémoire locale du navigateur)** :
  - case « Laisser l'IA me proposer des choix avant de répondre » - le prompt demande à l'IA de présenter 3 pistes numérotées et d'attendre un choix avant de rédiger (consigne placée en fin de prompt, position documentée comme la plus fiable) ;
  - case « Répéter pour chaque élément de ma liste » - l'IA traite chaque élément de la liste collée séparément ;
  - bouton « Ouvrir dans mon IA habituelle » - la destination préférée est mémorisée localement, les autres se replient sous « Autres choix » ;
  - pastilles du déjà-dit - sous chaque espace à remplir, les trois dernières valeurs utilisées se remettent en un clic (extension du rappel existant, migration silencieuse) ;
  - relances de secours - trois relances prêtes à copier sous le bouton Copier (« C'est trop long... », « C'est trop vague... », « Le ton ne convient pas... »), pour le moment où la première réponse déçoit.
- La sixième proposition de la boucle (cartes de démarrage à trous prénommés) a été écartée à la vérification : les cartes de démarrage ne sont plus affichées depuis le retour à l'assistant 4 étapes - aucun code mort conservé.

## [1.152.0] - 2026-08-07

### Amélioré
- **Confidentialité : les avatars par défaut sont désormais générés localement** - les utilisateurs sans photo de profil recevaient un avatar Gravatar, ce qui transmettait le hachage MD5 de leur courriel et l'adresse IP des visiteurs à un fournisseur américain (Automattic). L'avatar par défaut est maintenant un SVG local d'initiales (couleur déterministe de la charte, contraste AAA), sans aucune requête externe. Recommandation issue de l'évaluation des transferts hors Québec (Loi 25, art. 17).

## [1.151.0] - 2026-08-07

### Corrigé
- **Politique de confidentialité (v3.6) : l'information sur l'hébergement était matériellement fausse** - la page affirmait « serveur - Canada - décision d'adéquation » alors que le serveur de production est situé aux États-Unis (vérifié par whois le 7 août 2026). Le tableau des transferts indique désormais la destination réelle et l'état honnête (« évaluation en cours conformément à l'article 17 de la Loi 25, encadrement contractuel »). La promesse d'une copie intégrale de l'ÉFVP sur demande est remplacée par un résumé non sensible, et la phrase affirmant que les clauses contractuelles types « garantissent » la protection adéquate est reformulée honnêtement (avec sa traduction anglaise, qui manquait).

### Amélioré
- **Conditions d'utilisation (v4.1) : clauses de responsabilité renforcées et rendues conformes** - la clause de sauvegarde distingue désormais expressément les deux règles de l'article 1474 du Code civil (préjudice matériel par faute intentionnelle ou lourde ; préjudice corporel ou moral quel que soit le degré de faute) et exclut expressément du plafond les réclamations d'un consommateur pour le fait personnel de l'exploitant (article 10 de la Loi sur la protection du consommateur). Nouvelle clause 8.1 pour le répertoire d'outils (contenus tiers indicatifs, responsabilité de l'exploitant préservée pour ses propres représentations).
- **Pages secondaires calibrées** : la méthodologie passe d'une promesse ferme (« traitée sous 7 jours ») à un engagement de moyens ; la politique d'affiliation remplace « jamais influencés » par une formulation d'indépendance défendable ; la politique de retrait ne s'auto-disqualifie plus ; la FAQ affiche un renvoi « à titre informatif, sans valeur contractuelle » vers les Conditions d'utilisation. Le tout validé par une boucle de réfutation multi-IA en 3 rounds (4 réviseurs convergents), avec traductions anglaises alignées.

## [1.150.1] - 2026-08-07

### Corrigé
- **Constructeur de prompts (espaces à remplir) : frontières de mots** - un espace créé sur « son » ne touche plus jamais le « son » caché dans « maison » : le remplissage, le renommage et le statut « non retrouvé » exigent maintenant que le mot soit entier (défaut trouvé par la boucle adversariale multi-IA post-lancement, DeepSeek). Renommer un espace vers le nom d'un espace déjà existant fusionne les deux au lieu de créer une pastille en double (la valeur déjà saisie est conservée).

### Amélioré
- **Constructeur de prompts (espaces à remplir) : petits polis visuels** issus du panel (Gemini) - le nom de l'espace est mis en évidence dans le bloc « Remplis tes espaces » (le concept « texte à trous » se lit mieux), et le bouton « + Ajouter un espace à remplir » est rapproché de sa bande de pastilles.

## [1.150.0] - 2026-08-07

### Ajouté
- **Constructeur de prompts : « Espaces à remplir » sans aucune syntaxe** - conçu en 5 rounds de panel multi-IA (Perplexity, Codex, claude.ai, Gemini, DeepSeek) pour remplacer l'astuce `{{sujet}}` jugée trop technique. Deux gestes en français normal, zéro symbole : sélectionner un mot de sa phrase et cliquer « En faire un espace à remplir », ou insérer « information à préciser » au curseur avec le bouton « + Ajouter un espace à remplir ». Chaque espace apparaît en pastille sous le champ (« Tu pourras changer : »), se renomme en place (le mot est remplacé partout dans le texte), et se remplit dans le bloc « Remplis tes espaces » sous l'aperçu - l'aperçu se met à jour en direct, la copie et « Ouvrir dans [IA] » utilisent la valeur saisie, et un espace laissé vide garde simplement le mot de départ (le prompt reste toujours grammatical). Un mot disparu du texte devient une pastille grise « non retrouvé », jamais une corruption. Les espaces sont conservés dans les prompts sauvegardés et l'historique, et les dernières valeurs saisies sont proposées en un clic à la réutilisation. Les variables `{{...}}` existantes continuent de fonctionner.

### Corrigé
- **Constructeur de prompts : l'infobulle « ce mot n'a pas été retrouvé » ne se rendait pas** - l'apostrophe française cassait l'expression du gabarit (erreur console à chaque visite de la page) ; échappement corrigé.

## [1.149.0] - 2026-08-07

### Amélioré
- **Constructeur de prompts : le prompt généré passe aux gabarits v2**, conçus avec un panel de 5 IA (Perplexity, Codex, claude.ai, Gemini, DeepSeek) contre les meilleures pratiques d'août 2026. Chaque choix de l'utilisateur produit maintenant un fragment plus performant : critères de réussite observables dérivés des réglages (« La réponse est réussie si... »), ancrage final qui rappelle le livrable exact (« Produis maintenant : ... »), contexte balisé comme données (""") avec consigne de signaler les conflits, rôle en une phrase utile au lieu du boilerplate, consigne d'écriture naturelle concrète (sans l'exemple négatif qui amorçait la formule interdite), héritage explicite de la 2e tâche, vérification silencieuse contre les critères. Deux verrous logiques empêchent désormais les combinaisons contradictoires : chaîne de pensée montrée ET cachée (une seule instruction fusionnée), et « pose des questions » ET « réponds maintenant » (clôture conditionnelle).
- **Aides des variables {{sujet}} réécrites avec un exemple concret** (courriel aux parents dont seul le sujet change à chaque réutilisation) - la formule abstraite « espace à remplir plus tard » n'était pas comprise ; traductions anglaises ajoutées (elles manquaient).

## [1.148.3] - 2026-08-07

### Corrigé
- **Constructeur de prompts : l'aide du champ « rôle » (persona) ne surpromet plus** - vérification par un panel de 5 IA (Perplexity, Codex, claude.ai, Gemini, DeepSeek), verdict unanime appuyé sur les recherches 2024-2026 (EMNLP 2024, Wharton 2025) : donner un rôle à l'IA oriente le ton, le style et le vocabulaire, mais n'améliore ni l'expertise ni l'exactitude des faits. L'ancien texte (« donnera des réponses plus stratégiques ») laissait croire le contraire ; le nouveau le dit clairement et conseille de miser sur le contexte et des consignes précises pour la justesse. Français et anglais alignés.

## [1.148.2] - 2026-08-07

### Corrigé
- **Constructeur de prompts : le « ? » des boutons d'aide est enfin optiquement centré** - deux causes mesurées : la taille du texte du composant était écrasée par un style du thème (glyphe rendu trop petit), et le « ? » de la police DM Sans, sans jambage, se perchait dans le haut de sa boîte de ligne. Taille passée en style direct et correction optique proportionnelle ; centrage vérifié au pixel (écart nul sur les deux axes).

## [1.148.1] - 2026-08-07

### Corrigé
- **Constructeur de prompts : erreurs console au chargement** - l'objet Alpine `showHelp` était déclaré vide alors que la vue référence trois clés (persona, contexte additionnel, cadre strict), ce qui levait trois TypeError à chaque visite (deux préexistants, un introduit par le champ contexte) ; les clés sont désormais initialisées.

## [1.148.0] - 2026-08-07

### Ajouté
- **Constructeur de prompts : champ « Contexte additionnel »** - un espace facultatif pour donner à l'IA les informations de fond (ce qui a déjà été essayé, contraintes, contexte du projet), distinct de la tâche, intégré au prompt final, aux sauvegardes, au permalien et au remix.
- **Constructeur de prompts : variables réutilisables** - écrire `{{sujet}}` dans un champ crée automatiquement une zone « Remplis tes variables » sous l'aperçu ; la copie et « Ouvrir dans [IA] » utilisent le texte complété, et les prompts sauvegardés conservent leurs variables pour réutilisation.
- **Constructeur de prompts : historique local pour les visiteurs non connectés** - les 10 derniers prompts générés sont conservés uniquement dans le navigateur (jamais envoyés au serveur), rechargeables et effaçables en un clic.
- **Rétention des prompts supprimés** - les prompts mis à la corbeille par leur propriétaire sont désormais définitivement effacés après 30 jours (réglable dans l'écran admin « Rétention des données », mentionné dans la politique de confidentialité). Auparavant, la suppression laissait la donnée en base indéfiniment.

### Corrigé
- **Files d'attente : workers périmés** - le déploiement redémarre maintenant les workers de queue (`queue:restart`) ; les workers gardaient l'ancien code en mémoire, ce qui provoquait l'erreur « The force option does not exist » toutes les 15 minutes dans le journal de prod.

### Maintenance
- Suivi git de production réaligné sur origin/master (HEAD retardait de 7 semaines, aucun fichier touché).
- Base de données locale de développement : tables de l'annuaire re-seedées depuis les données publiques de production (2334 outils).

## [1.147.7] - 2026-08-06

### Corrigé
- **Annuaire** : le lien « 🗄️ Voir les X outils archivés » restait visible pour tous (la v1.147.6 avait réservé le toggle aux modérateurs mais pas le compteur qui pilote l'affichage du lien) - le compteur est désormais nul pour le public, le lien disparaît.
- **Sitemap** : `/sitemap.xml` répondait HTTP 500 par épuisement de la mémoire PHP (128 Mo) - les neuf requêtes de génération chargeaient les modèles complets (contenus intégraux des actualités, descriptions, définitions), un poids qui grossissait chaque jour et que le cache masquait jusqu'à la purge du soir. Chaque requête ne sélectionne plus que les colonnes utiles (id, slug, date, image) ; trouvé grâce au journal Laravel prod (FatalError 20h15 Québec), GSC lisait encore le sitemap sans erreur à 09h46.

## [1.147.6] - 2026-08-06

### Corrigé
- **Annuaire** : les outils archivés (contenu HN/blog/vidéo crawlé à tort, nettoyage d'avril 2026) ne sont plus visibles au public. Le toggle `?show_archived=1` et le lien « Voir les X outils archivés » sont réservés aux modérateurs ; les fiches archivées sans outil de remplacement répondent désormais 404 au public (25 d'entre elles étaient servies en 200) ; le sitemap ne les référence plus (elles étaient proposées à l'indexation Google). Aucune donnée supprimée - les modérateurs conservent l'accès complet.

## [1.147.5] - 2026-08-06

### Modifié
- **Constructeur de prompts** : bouton d'aide « ? » bonifié suivant l'avis du panel (Codex + DeepSeek) - zone cliquable invisible portée à 40 px (cercle visuel inchangé), états survol et focus clavier visibles. Correctif au passage : un style global du thème écrasait la hauteur du cercle (32x22) - les dimensions passent en inline, prioritaires partout.

## [1.147.4] - 2026-08-06

### Corrigé
- **Constructeur de prompts** : le « ? » des boutons d'aide n'était pas centré dans son cercle (centrage par line-height, fragile). Nouveau composant réutilisable x-tools::help-btn avec centrage flexbox exact - les 3 boutons dupliqués inline passent par ce bloc unique. Mesuré : 0 px d'écart horizontal et vertical.

## [1.147.3] - 2026-08-06

### Corrigé
- **Constructeur de prompts** : cocher « Autre (longueur personnalisée) » ou « Autre (ton personnalisé) » laissait le menu déroulant visible alors que les deux contrôles pilotent la même valeur - on croyait pouvoir en choisir deux. Le menu se masque maintenant quand « Autre » est coché (et la valeur repart à zéro au basculement). Le format de sortie reste volontairement cumulatif (multi-sélection + format personnalisé).

## [1.147.2] - 2026-08-06

### Corrigé
- **Constructeur de prompts** : l'explication « L'IA insérera des repères ### entre les sections » supposait de connaître le Markdown. Elle décrit maintenant l'effet concret : « Chaque partie de la réponse sera précédée d'une ligne de séparation bien visible... ». L'instruction envoyée à l'IA reste inchangée.

## [1.147.1] - 2026-08-06

### Corrigé
- **Constructeur de prompts** : les lignes de résumé des blocs d'options commençaient par « Ajouté : » sans dire qui ajoutait quoi - un utilisateur croyait à une erreur en voyant « Ajouté : verbe « Analyse », longueur modéré (300-500 mots) » (valeurs pré-remplies automatiquement). Reformulé en « Sera inclus dans ton prompt : ... ».

## [1.147.0] - 2026-08-06

### Modifié
- **Constructeur de prompts** : « Format de sortie » et les lecteurs prédéfinis de « Qui va lire ça ? » reviennent au menu déroulant - chaque sélection s'ajoute maintenant comme pastille amovible sous le champ (demande explicite du 2026-08-06, remplace les rangées de boutons). Les garde-fous du format (maximum 3, exclusivité Format JSON/Diagramme Mermaid) restent appliqués via la désactivation des options du menu.

### Corrigé
- **Constructeur de prompts** : la liste d'audiences recalibrée en v1.146.0 restait invisible en production - l'ancienne liste, figée en base de données par le seeder du 2026-07-26, primait sur le nouveau défaut du code. Une migration réversible met à jour la valeur stockée et purge son cache.

## [1.146.0] - 2026-08-06

### Modifié
- **Constructeur de prompts** : audiences prédéfinies de « Qui va lire ça ? » recalibrées sur le public réel du site (consensus panel Codex/DeepSeek/Perplexity, familles du guide MEQ) : Élèves du primaire, Élèves du secondaire, Étudiants, Parents, Collègues de travail, Direction ou gestionnaires, Clients, Grand public. Les prompts déjà sauvegardés avec les anciennes catégories sont automatiquement remappés à la restauration.

### Corrigé
- **Constructeur de prompts** : le libellé des pastilles de sélection (audiences, personas, formats...) est de nouveau centré. La coche « ✓ » réservait 18 px invisibles à gauche du texte même sur les pastilles non sélectionnées ; elle n'occupe désormais l'espace que lorsqu'elle est affichée (pastille sélectionnée), avec la transition existante.

## [1.145.0] - 2026-08-06

### Ajouté

- **Constructeur de prompts : format de sortie multi-sélection avec garde-fous (#1618).** Cartes à
  cocher (même pattern que l'audience), maximum 3 formats, JSON et Mermaid utilisables seuls (raison
  affichée), prompt composé intelligemment (« Structure principale : X. En complément, intègre : Y » ;
  livrables multiples produits en sections numérotées). Migration transparente des prompts déjà
  sauvegardés (ancien format scalaire converti à la lecture, réédition et remix préservés).
- **3 nouvelles méthodes dans « Comment l'IA doit-elle s'y prendre ? » (#1620)** : reformuler la
  demande avant de répondre, vérifier et corriger sa réponse avant de la donner, proposer 2 ou 3
  versions et recommander la meilleure. Chaque option affiche désormais le nom pédagogique de sa
  méthode en second plan discret (zero-shot, chaîne de pensée, few-shot, décomposition guidée...).

### Corrigé

- **Champs « Autre (longueur / ton / format personnalisé) » invisibles (#1619)** : bloc accentué
  (fond teinté, barre latérale de couleur) avec apparition animée - constat utilisateur « je ne
  l'ai pas vu la première fois », option notée 94/100 par le panel.
- **Libellé mensonger « Séparer clairement les données du reste (délimiteurs ###) » (#1621)** :
  l'option sépare en réalité les sections de la réponse - nouveau libellé « Séparer clairement les
  parties de la réponse » + aide technique dessous.
- **Boutons d'aide « ? » invisibles comme boutons (#1622)** : vraie apparence de bouton circulaire
  (bordure couleur charte, 28 px, aria-label et aria-expanded) sur persona et cadre strict.
- **Modale d'aide collée en haut de l'écran (#1623)** : centrage vertical (modal-dialog-centered).

## [1.144.2] - 2026-08-06

### Corrigé

- **Constructeur de prompts : les Vérifications ne reprochent plus des étapes pas encore atteintes
  (#1616).** Le panneau signalait l'audience (étape 3) et le format/contraintes (étape 4) dès
  l'étape 2 - un premier utilisateur croyait avoir mal fait. Chaque suggestion n'apparaît plus
  qu'à partir de l'étape de son champ ; le panneau reste masqué tant qu'il n'a rien d'utile à dire,
  et l'état « tout est beau » est réservé à la dernière étape. Prouvé par captures Playwright aux
  étapes 2 (absent) et 4 (présent) + 52 tests / 217 assertions.
- **Audience personnalisée : l'aide « plusieurs lecteurs » manquait (#1617).** Le champ « Qui va
  lire ça ? » acceptait déjà plusieurs lecteurs mais rien ne le disait ; nouveau placeholder
  (« Ex : mes élèves de 5e année, leurs parents ») + ligne d'aide « Tu peux nommer plusieurs
  lecteurs, séparés par des virgules. »

## [1.144.1] - 2026-08-06

### Corrigé

- **Constructeur de prompts : le panneau « Vérifications » parlait en jargon (#1615).** Signalement
  utilisateur : « Aucun contexte ni audience précisé(e) pour qui recevra la réponse. Compléter »
  était incompréhensible. Les 3 messages de diagnostic, le sous-titre et le bouton sont réécrits en
  langage néophyte, orienté action avec exemples concrets (« Tu n'as pas indiqué à qui s'adresse la
  réponse (par exemple : tes élèves, des parents, des collègues)... ») ; « Compléter » devient
  « Ajouter cette info ». Textes seulement, aucune modification de structure. Prouvé par capture
  Playwright et 52 tests / 217 assertions.

## [1.144.0] - 2026-08-06

### Ajouté

- **Socle légal validé par un panel de 5 IA (Perplexity, Codex, DeepSeek, claude.ai, Gemini) puis corrigé
  selon leurs réfutations (#1600/#1602/#1610).**
  - Identification exacte de l'entité sur les 5 pages légales : « MEMORA solutions, dénomination
    commerciale de 9307-6719 Québec inc. (NEQ 1170260492) », vérifiée contre la fiche du Registraire
    des entreprises ; « La veille de Stef » présentée comme plateforme, jamais comme nom d'affaires.
  - Responsable de la protection des renseignements personnels nommé sur les 5 pages, avec courriel
    du même domaine (confidentialite@laveille.ai, transfert créé et vérifié).
  - Attestation obligatoire « 16 ans ou plus » à l'inscription : formulaire web ET inscription
    sociale Google/GitHub (nouvel écran « Finaliser votre inscription » - le contournement social
    avait été détecté par Codex). Aucune date de naissance stockée.
  - Rappel automatique au DPO des demandes de droits approchant le délai de 30 jours
    (privacy:remind-overdue-requests, quotidien, idempotent).
  - Courriel de confirmation de commande : référence versionnée aux conditions de vente (art. 54.7 LPC).
  - Lien « Exercer mes droits » au pied de page + séparateur des liens du bandeau cookies.

### Corrigé

- **Concordance code↔promesses publiées** : journaux de connexion réellement conservés 12 mois
  (défaut ET valeur en base, migration incluse) ; preuve de consentement conservée 5 ans ; purge des
  statistiques de clics de liens courts à 12 mois (les liens ne sont jamais touchés) ; libellés de
  purge honnêtes (« suppression définitive ») ; rétention des comptes décrite selon le comportement
  réel ; courriel des ventes distinct du courriel du DPO ; « plus de 200 pays » remplacé par « les
  pays proposés au moment de la commande » ; versions et dates des documents légaux incrémentées.

### Notes

- Les amendements de clauses CGV/CGU (14 blocs) sont volontairement NON publiés : réécrits en
  brouillon v2 selon les réfutations du panel et mis en attente de validation juridique.

## [1.143.0] - 2026-08-05

### Ajouté

- **Constructeur de prompts : 6 correctifs du document de rétroaction « Modifications à faire - 001 » (#1594-#1599).**
  Évolution incrémentale du wizard 4 étapes (jamais de refonte structurelle), prouvée par Playwright
  (contraste stepper 8,2:1 AAA, cibles 44 px) et 63 tests / 284 assertions :
  1. Espacement des boutons de navigation d'étapes (`.ct-step-nav`).
  2. Stepper restylé : cercles, connecteurs, `aria-current="step"`.
  3. Aperçu et vérifications repliés par défaut (disclosures `previewOpen`/`checksOpen`).
  4. Panneau d'actions atténué tant que l'étape n'est pas valide (`.ct-actions-panel`).
  5. Coches ✓ de complétion par étape (`stepComplete()`).
  6. « Diagnostic rapide » renommé « Vérifications » + étape 4 regroupée en 3 fieldsets
     (« Apparence de la réponse », « Voix et niveau de langage », « Règles à respecter »).

### Corrigé

- **Export RGPD `/user/export-data` : 4 catégories manquantes (#1603).** L'export du tableau de bord
  utilisateur omettait `saved_prompts`, `bookmarks`, `newsletter` et `consents` (présentes dans
  l'export du module Privacy mais pas dans celui du dashboard). Alignement sur
  `DataExportController`, prouvé par `GdprDataExportTest` (8/8).

## [1.142.2] - 2026-08-05

### Corrigé

- **Constructeur de prompts : 5 correctifs issus d'un audit UX/qualité dédié (#1590/#1591).**
  Vérifiés un à un par Playwright après le fix :
  1. Message d'erreur d'étape 1/2 figé - l'alerte de validation ne se cachait qu'au prochain clic
     sur « Suivant », jamais quand le champ redevenait valide entre-temps (ex. sélection d'un
     rôle après un premier clic sur « Suivant » sans rien remplir). L'alerte disparaît maintenant
     automatiquement dès que le champ redevient valide.
  2. 3 références mortes à « carte d'objectif » (alerte de validité, modale d'aide, featureList
     JSON-LD) - régression de la correction 1.142.1 : ce vocabulaire était juste au moment de son
     ajout (wizard à cartes v1.139.x) mais est redevenu périmé après la restauration du menu
     déroulant pour le persona (#1546→#1549). Reformulées pour décrire le vrai flux (rôle à
     l'étape 1, tâche à l'étape 2).
  3. Faute de français dans tous les prompts générés à persona prédéfinie (« Tu es un(e)
     Rédacteur... ») - la majuscule du libellé n'était jamais abaissée en milieu de phrase dans le
     texte technique réellement envoyé à l'IA, alors que l'aperçu en langage courant le faisait
     déjà correctement.
  4. Prompt généré sans phrase de clôture actionnable - s'arrêtait net après la checklist qualité.
  Ajout de « Réponds maintenant à cette demande. » en fin de texte.
  351 tests Pest `Modules/Tools` verts (aucune régression). Hors périmètre de ce lot (backlog
  #1593) : champ « Contexte additionnel » distinct de la tâche, variables réutilisables
  `{{sujet}}`.

## [1.142.1] - 2026-08-05

### Corrigé

- **Constructeur de prompts : aide périmée.** La modale « Comment créer un bon prompt » et
  l'indice de validité du formulaire référençaient encore une « carte de démarrage » et un bouton
  « Affiner » retirés lors de refontes antérieures (les réglages rôle de l'IA/verbe/format/
  contraintes sont désormais des blocs toujours visibles, pas un panneau replié derrière un
  bouton nommé). 3 chaînes corrigées pour refléter le vocabulaire réel de l'UI (« carte
  d'objectif », « réglages »). 30 tests Pest `ConstructeurPromptsGateTest` verts, dont celui qui
  vérifie le rendu texte réel du Blade.

## [1.142.0] - 2026-08-05

### Ajouté

- **Constructeur de prompts : permalien public + bouton « Remixer » (Phase 1 du plan de croissance/popularité).**
  Nouveau plan approuvé après un club des sages relancé (4/5 oracles - Perplexity, Codex,
  DeepSeek, claude.ai ; Gemini indisponible ce round, quota `agy` épuisé + session navigateur
  déconnectée, signalé explicitement plutôt que de prétendre à l'unanimité). Nouvelle route
  publique `GET /p/{publicId}` (`PublicPromptController::show`), calquée sur le pattern déjà
  éprouvé en production de `PublicCrosswordController::play()` (mots-croisés, `/jeumc/{identifier}`)
  - **zéro nouvelle migration** : `public_id` et `is_public` existaient déjà sur `saved_prompts`,
  simplement jamais exposés publiquement. La bascule public/privé réutilise le `PUT
  /api/prompts/{id}` déjà existant (`SavedPromptController::update`), aucun nouvel endpoint dédié.
- Page `/p/{publicId}` : lecture seule du prompt, avertissement explicite avant partage (« ne
  partage jamais de renseignements personnels »), bouton **Remixer** qui préremplit l'étape Tâche
  du constructeur (`?remix={publicId}` → nouvel endpoint public `GET
  /p/{publicId}/remix-data`), boutons Copier (réutilisant le composant DRY
  `window.copyToClipboard` du layout maître FrontTheme) et widgets de partage LinkedIn/X. `noindex`
  par défaut.
- Panneau « Mes prompts » : bascule public/privé inline, avertissement PII affiché **avant** toute
  activation (jamais après), lien public copiable une fois actif.

### Sécurité

- **Fuite d'information trouvée et corrigée en gate qualité, avant livraison** : le nouvel
  endpoint `remix-data` renvoyait initialement le modèle `SavedPrompt` complet
  (`response()->json($prompt)`), exposant `id`/`user_id`/timestamps à tout visiteur anonyme
  possédant un lien public, alors que le JS ne lit que `name`/`params`. Corrigé : réponse
  restreinte à `public_id`/`name`/`prompt_text`/`params` (les deux premiers champs sont déjà
  publics via la page elle-même, `user_id` et `id` ne le sont pas). Test IDOR explicite ajouté
  (un prompt privé ne peut jamais fuiter via `remix-data`, quel que soit l'appelant).

### Corrigé

- **Message d'erreur invisible sur lien de partage invalide** : trouvé lors de la vérification
  visuelle de cette même phase (pas un régression signalée par l'utilisateur). Visiter `/p/{id}`
  avec un `public_id` inexistant/privé redirigeait bien vers `/outils/constructeur-prompts` avec
  `->with('error', ...)`, mais ce flash de session ne s'affichait **jamais** : cette route passe
  par `cacheResponse:600` (Spatie ResponseCache), qui sert un snapshot HTML entier en cache -
  invisible au flash posé APRÈS la mise en cache. Corrigé par un paramètre de requête
  `?share_error=notfound`, lu côté client par `constructeur-prompts-core.js` (s'exécute à chaque
  chargement, cache ou non) et affiché via le mécanisme toast déjà existant (`_showSaveError`),
  puis nettoyé de l'URL via `history.replaceState`. Le flash de session est conservé en parallèle
  (utile hors cache). Vérifié en direct (Playwright) : toast affiché avec le bon message, URL
  nettoyée après coup. Ajout collatéral, plus général : `Modules/FrontTheme/resources/views/
  layouts/master.blade.php` déclenche désormais un toast générique sur tout `session('error')`/
  `session('success')` (jusqu'ici silencieusement ignorés sur les pages non cachées).

Vérifié : 351 tests Pest `Modules/Tools` verts (dont 8 `PublicPromptControllerTest`) ;
vérification visuelle Playwright réelle (page publique, avertissement PII, flux Remixer
préremplissant bien l'étape Tâche dans le DOM, toast d'erreur sur lien invalide). Phases 2
(galerie éditorialisée par métier québécois) et 3 (rétention locale pour les invités) du plan
approuvé restent à faire, chacune avec
son propre cycle veille→club des sages avant implémentation - pas de gros-bang.

## [1.141.0] - 2026-08-04

### Ajouté

- **Constructeur de prompts : bouton « Inverser l'ordre » pour la séquence à deux tâches.** Suite
  d'un round 2 de consultation du club des sages (5 IA - unanimité) sur des pills réordonnables par
  glisser-déposer : rejetées pour non-conformité WCAG AAA (2.1.1 Clavier + 2.5.7 Mouvements de
  glissement, aucun équivalent clavier/pointeur simple sans reconstruire tout le pattern). La
  séquence étant bornée à 2 tâches (2 permutations possibles), un simple bouton « ⇅ Inverser
  l'ordre » (proposé indépendamment par 2 des 5 oracles) suffit - accessible nativement, zéro
  pattern à construire.
- **Restylisation légère : badges numérotés (①②) + bordure arrondie teal** autour des 2 blocs
  verbe quand la deuxième tâche est active. Contraste vérifié 9,35:1 (AAA).

### Corrigé

- **Bug trouvé en vérification visuelle (pas dans les tests) : le badge « 1 » ne s'affichait pas
  en cercle plein comme le badge « 2 ».** Le span utilisait `x-show` sur lui-même ; Alpine reprend
  le contrôle de la propriété `display` au show/hide et écrasait le `display: inline-flex` du
  style inline, ne laissant qu'un span `display: inline` sans dimensions ni fond. Corrigé avec
  `<template x-if>` : l'élément n'existe simplement pas dans le DOM quand caché, le style inline
  complet s'applique intact dès l'insertion.

Comportement à une seule tâche et les 7 `<select>` natifs + cartes Audience strictement inchangés.
Vérifié : 343 tests Pest Modules/Tools + 10 bancs Node (dont les 2 nouveaux tests `swapTaskOrder`)
passants, 0 échec ; vérification visuelle Playwright desktop+mobile en direct (badges identiques,
inversion fonctionnelle confirmée dans le DOM, retrait de la 2e tâche revient à l'état initial sans
résidu visuel).

## [1.140.0] - 2026-08-04

### Ajouté

- **Constructeur de prompts : option « deuxième tâche » bornée à 2, en séquence explicite.**
  Remplace un multi-select libre écarté après consultation du club des sages (5 IA - Perplexity,
  Codex, DeepSeek, Gemini, claude.ai - unanimité). Sur l'étape Tâche, un lien discret « + Ajouter
  une deuxième tâche (optionnel) » révèle un second menu déroulant verbe. Le prompt généré exprime
  une séquence numérotée (« Ta tâche comporte deux étapes... 1) X : ... 2) Y, à partir du résultat
  de l'étape précédente. ») plutôt qu'une simple juxtaposition ambiguë. Comportement à une seule
  tâche strictement inchangé si l'option n'est pas activée.
- **Défauts intelligents pour format/longueur/ton.** Ces trois champs partaient vides, ce qui
  déclenchait à tort le « Diagnostic rapide » pour quiconque ne visitait jamais l'étape Options -
  désormais pré-remplis (« Paragraphes détaillés » / « Modéré (300-500 mots) » / « Professionnel »),
  toujours modifiables.

### Corrigé

- **Bug latent trouvé en creusant le nettoyage ci-dessous : `openDiagnosticSection()` forçait
  toujours l'étape 2**, peu importe le diagnostic cliqué - reliquat de l'ancienne numérotation
  jamais mis à jour lors de la restauration du wizard 4 étapes (2026-08-03). Le clic « Compléter »
  n'atteignait donc jamais le bon bloc (audience = étape 3, format/contraintes = étape 4). Corrigé
  et vérifié en direct.

### Nettoyé

- État Alpine mort `affinerOpen` (écrit deux fois, jamais lu nulle part) et CSS orphelin
  `.ct-profile-strip` retirés du constructeur de prompts - aucun effet visuel.

**Décision de conception explicite (club des sages 5/5 + historique du projet)** : aucune carte
introduite pour les champs à choix unique, aucune sélection multiple libre sur la tâche - ces deux
options ont déjà été essayées et rejetées deux fois cette année sur cet outil. Les 7 menus
déroulants natifs et les cartes Audience (multi-sélection) restent visuellement identiques.

## [1.139.22] - 2026-08-04

### Retiré

- **Constructeur de prompts : panneau d'anonymisation intégré retiré, sur demande explicite de
  l'utilisateur.** Le bouton « Masquer mes informations personnelles » et l'éditeur riche embarqué
  (`<x-tools::anonymizer-editor>`) sont retirés de `constructeur-prompts.blade.php` : les deux
  outils doivent rester séparés, l'anonymisation ne vivant plus QUE dans l'outil dédié
  `/outils/anonymiseur` (jamais touché par ce retrait, ni ses fichiers propres
  `anonymizer-core/ui/rich.js`, `anon-v2.css`, ni le composant réutilisable
  `anonymizer-editor.blade.php`). Le message de confidentialité du champ « Tâche » pointe
  désormais vers un lien discret vers l'Anonymiseur plutôt que vers le panneau disparu.
  `prompt-anon-panel.js` (785 lignes, 100% dédié au pont) est supprimé entièrement ; dans
  `constructeur-prompts-core.js`, les 8 sites qui déclenchaient un événement `input` synthétique
  pour réveiller le garde-fou anti-PII du fichier supprimé sont retirés (la logique d'assignation
  elle-même reste intacte), ainsi que `purgerCopieLocaleDesCartes()` (câblée uniquement sur cet
  événement, plus aucun appelant). 5 fichiers de tests Feature et 7 bancs Node dédiés exclusivement
  à cette intégration sont supprimés ; 8 fichiers de tests Feature mixtes sont ajustés (assertions
  concernées retirées, reste inchangé). Le garde `profile-anon-guard.js` (page `/user/prompts`,
  intégration distincte et non demandée) n'est pas touché. 343 tests Pest `Modules/Tools` + 29
  bancs Node `tests/js` passants, 0 échec.

## [1.139.21] - 2026-08-04

### Corrigé

- **Constructeur de prompts : le vrai menu déroulant restauré pour le rôle/persona et 6 autres
  champs.** Le 1.139.20 restait un malentendu : le wizard 4 étapes « fidèle à mi-juin » utilisait
  des cartes cliquables pour le rôle, jamais le vrai `<select>` HTML décrit explicitement par
  l'utilisateur (« menu déroulant pour le persona ou personnalisé... on pouvait aussi changer les
  contenus des menus déroulants »). Recherche git précise : le vrai menu déroulant a existé sans
  interruption de juin (v1.65.260) jusqu'au 2026-08-01 (`fb55854e`), remplacé par des cartes
  seulement à la refonte « page blanche » du 2026-08-02 - toutes les tentatives de restauration
  depuis en descendaient, donc aucune n'avait jamais réellement ramené le select. Changement
  chirurgical : structure 4 étapes et backend (préférences admin-éditables, panneau anti-PII,
  « Ouvrir dans » 5 IA) intégralement conservés - seul le widget change de cartes vers `<select>`
  pour les 7 champs à choix unique (rôle, verbe, format, longueur, ton, technique de prompting,
  langue). Audience laissée en cartes (multi-sélection, hors périmètre de la demande). 368 tests
  Pest `Modules/Tools` passants, 0 échec (identique au compte d'avant refonte).

## [1.139.20] - 2026-08-04

### Modifié

- **Constructeur de prompts : 2e retour à l'assistant 4 étapes (Persona/Tâche/Audience/Options),
  sur confirmation explicite via question posée directement à l'utilisateur.** L'assistant 4
  étapes (v1.139.16) avait déjà été essayé puis reverté le 2026-08-03 (v1.139.17, retour au
  formulaire 3 écrans). Avant de relancer ce cycle, l'historique complet a été présenté à
  l'utilisateur (dates, citations exactes de ses choix précédents) ; il a confirmé vouloir
  précisément cette version malgré ce contexte. Revert propre (`git revert` de 17b14ca6, qui
  réapplique le commit ac9b7a26) : aucun conflit sur le Blade ni sur le JS, les correctifs du
  1.139.18 (point final double, accord « Tâche demandée : ») vivent dans une zone du fichier
  jamais touchée par la restructuration en 4 étapes et sont donc automatiquement préservés.

## [1.139.19] - 2026-08-03

### Sécurité

- **Faille RBAC corrigée : le rôle `editor` pouvait supprimer/modifier n'importe quelle fiche de
  l'annuaire.** Trouvée et reproduite en direct durant la vague ADMIN de la simulation E2E : le
  groupe de routes `admin/directory/*` ne vérifiait que la permission `view_admin_panel`
  (`EnsureIsAdmin`), que le rôle éditeur possède aussi pour accéder au panneau admin. Corrigé en
  ajoutant `can:moderate_tools` au middleware du groupe de routes. Effet de bord découvert par le
  test de régression : le rôle `directory_moderator`, seedé sans `view_admin_panel`, ne pouvait
  lui-même jamais atteindre ces routes malgré ses permissions - corrigé dans le seeder. 4 tests
  Pest neufs, 61/61 `Modules/Directory` + 17/17 `Modules/RolesPermissions` passants.

## [1.139.18] - 2026-08-03

### Corrigé

- **6 bugs trouvés durant la vague GUEST de la simulation E2E complète du site.** Décido : copie
  marketing trompeuse « sans compte requis » corrigée (voter est bien sans compte, mais créer un
  sondage exige un compte gratuit). Constructeur de prompts : point final double dans le prompt
  généré corrigé (le verbe est déjà à l'impératif) ; accord fautif type « Elle va rédige »
  corrigé en renommant la clé i18n vers un libellé qui n'exige plus de conjuguer le verbe choisi
  par l'utilisateur. Oscilloscope RLC : la sidebar de partage fixe chevauchait le panneau gauche
  de l'outil en desktop (≥ 992px), corrigé par un padding-left ciblé.
- Complète la tâche laissée en chantier avant une compaction de contexte : extraction du script
  inline de `/user/prompts` vers un asset dédié (même pattern que `constructeur-prompts-core.js`)
  + banc d'essai comportemental Node (17 tests). 396 tests Pest `Modules/Tools` passants.

## [1.139.17] - 2026-08-03

### Modifié

- **Constructeur de prompts : retour au formulaire à 3 écrans, sur nouvelle demande explicite de
  l'utilisateur.** L'assistant 4 étapes fidèle à mi-juin (livré au 1.139.16) n'était finalement
  pas non plus la version recherchée. Revert propre du commit du 1.139.16 - aucun autre commit
  n'avait touché ces fichiers entretemps, donc aucun conflit et aucune perte du travail de
  confidentialité (anti-PII) déjà en place, qui précède ce détour et n'a jamais été affecté par
  lui.

## [1.139.16] - 2026-08-03

### Corrigé

- **Constructeur de prompts : retour à l'assistant 4 étapes (Persona/Tâche/Audience/Options),
  fidèle à la version de mi-juin, sur demande explicite de l'utilisateur.** Le formulaire à 3
  écrans restauré au 1.139.14/15 n'était toujours pas ce qui était attendu - l'utilisateur voulait
  retrouver précisément l'assistant avec le sélecteur de technique de prompting (zero-shot,
  zero-shot + réflexion étape par étape, avec exemples, avec exemples + réflexion étape par étape,
  itératif avec validation) présent à la dernière étape. Découverte clé : les champs de l'ancien
  assistant n'avaient jamais été supprimés par les refontes intermédiaires, seulement déplacés
  dans un panneau « Affiner » repliable - la reconstruction a donc consisté à réorganiser le code
  déjà existant en 4 étapes visibles, pas à réécrire quoi que ce soit. Tout le travail de
  confidentialité déjà livré (masquage anti-PII, panneau d'anonymisation) et le backend (prompts
  sauvegardés, partage, ouverture directe dans 5 IA) restent intacts et inchangés. Vérifié
  visuellement en navigateur invité jusqu'à l'étape 4 : le sélecteur de technique s'affiche et
  fonctionne.

## [1.139.15] - 2026-08-03

### Corrigé

- **Constructeur de prompts : le formulaire restauré (v1.139.14) était invisible pour tout
  visiteur non-superadmin.** Le drapeau « en révision » activé pendant la refonte cassée était
  resté actif en base après le retour à la version stable - un vrai visiteur recevait encore la
  page « fait peau neuve » au lieu du formulaire à 3 écrans. Drapeau levé, cache applicatif vidé,
  rendu réel revérifié en navigateur en tant qu'invité (sans session) : le formulaire s'affiche
  maintenant correctement, zéro erreur console.

## [1.139.14] - 2026-08-03

### Modifié

- **Constructeur de prompts : retour au formulaire à 3 écrans, sur demande explicite de
  l'utilisateur.** La réécriture en cartes visuelles + phrase à trous (livrée hier) s'est révélée
  plus difficile à utiliser en pratique que l'ancien formulaire. L'outil revient à sa version
  précédente : écran 1 (objectif en texte libre), écran 2 (réglages en blocs dépliés), écran 3
  (aperçu + Copier/Ouvrir dans une IA) - sans cartes ni phrase à trous.

## [1.139.13] - 2026-08-03

### Corrigé

- **Constructeur de prompts : triple anneau de focus sur les champs Sujet/Ton/Longueur/Destiné à.**
  Trouvé en simulant réellement un usage humain sur le site : le correctif précédent (v1.139.12)
  avait bien réglé le problème global du site, mais un style propre à cet outil rajoutait encore
  son propre anneau par-dessus - trois contours superposés au lieu d'un. Un seul contour maintenant.
- **Constructeur de prompts : le bouton "Ouvrir dans ChatGPT/Claude/Gemini/Perplexity" ne
  fonctionnait jamais réellement.** À chaque clic, un message trompeur "la fenêtre a été bloquée"
  s'affichait et un onglet vide restait ouvert, alors que rien n'avait vraiment été bloqué - un
  détail technique de l'appel d'ouverture de fenêtre empêchait systématiquement la navigation
  directe vers l'IA choisie. Le bouton ouvre maintenant vraiment l'IA cible dans le nouvel onglet.

## [1.139.12] - 2026-08-02

### Corrigé

- **Constructeur de prompts : la barre de défilement horizontale des 9 cartes n'avait jamais
  vraiment disparu.** Les correctifs précédents (v1.139.8/1.139.9) n'avaient retouché que
  l'apparence de la rangée défilante, sans jamais la retirer - une fois une carte choisie, le
  fieldset des 9 cartes se transforme maintenant en une seule pastille (comme prévu à l'origine),
  la rangée qui débordait et coupait des cartes a disparu.
- **Double bordure sur les champs de formulaire au focus, site-wide.** L'anneau de mise en
  évidence (`box-shadow`) s'ajoutait par-dessus le contour natif du navigateur au lieu de le
  remplacer - un seul anneau visible désormais sur tout champ actif.

## [1.139.11] - 2026-08-02

### Corrigé

- **Fiches outils `/annuaire/{slug}` : 5-7 secondes de temps de réponse.** Mesure directe en
  production (probes auto-suppressibles avec `DB::enableQueryLog`) : 24 ms de SQL cumulé sur 81
  requêtes contre 6,6 secondes de temps total - la lenteur entière était hors base de données,
  dans `@glossarize()` (`GlossaryLinkifier::linkify()`), qui boucle une comparaison par terme
  (glossaire + acronymes + ~465 outils + tous leurs alias/variantes) sur chaque nœud de texte du
  contenu, pour chaque visite. Seule la LISTE des termes était mise en cache (1h), jamais le
  résultat du matching. Le résultat est maintenant caché (limité au premier appel par page, pour
  ne rien changer aux pages qui appellent la fonction plusieurs fois), invalidé automatiquement
  quand le glossaire ou les outils changent. Cette fonction est utilisée sur les fiches d'outils,
  les articles de blog, les actualités et le glossaire lui-même - l'amélioration profite à tout le
  site, pas seulement à l'annuaire.

## [1.139.10] - 2026-08-02

### Corrigé

- **4 derniers quick wins du conseil des sages final (Constructeur de prompts).** Consultation
  finale (Codex, Gemini, claude.ai, DeepSeek) sur le produit fini avec captures réelles : confirmation
  avant de changer de carte si des champs sont déjà remplis (message honnête, les données sont en
  réalité toujours conservées) ; distinction visuelle claire entre survol/focus (bordure grise +
  ombre légère) et carte sélectionnée (fond teal plein), qui se ressemblaient trop ; région
  `aria-live="polite"` annonçant le passage grille → formulaire aux lecteurs d'écran. Le libellé du
  fieldset a été vérifié non affecté par le grid blowout déjà corrigé. 265/265 tests verts,
  vérification navigateur réelle sur les 5 points, zéro régression. Le conseil des sages juge le
  produit prêt pour les étapes 11 (test enseignants) et 12 (mise en public).

## [1.139.9] - 2026-08-02

### Corrigé

- **5 améliorations d'ergonomie (Constructeur de prompts).** Trouvées par un audit réel (marche à
  pied superadmin dans l'outil + club des sages, verdict Codex "réel" sur chacune) :
  vérificateur PII avec statut simple d'abord (teal/orange) et détails en secondaire ; repère
  visuel permanent (bordure + point orange) sur les champs vides ; phrase d'intro expliquant la
  construction automatique du prompt ; formulaire et aperçu côte à côte dès 1024px ; divulgation
  progressive des 9 cartes sur mobile (4 prioritaires + bouton "Voir toutes les options", les 9
  radios natifs restent en permanence dans le DOM). Bug de rendu trouvé et corrigé en cours de
  route : le statut du vérificateur restait gris (règle globale de charte plus spécifique) -
  corrigé par une classe CSS composée. 265/265 tests verts, vérification navigateur réelle sur
  chaque point, zéro régression.

## [1.139.8] - 2026-08-02

### Corrigé

- **Cible tactile AAA sur le bouton de reset de la pastille sélectionnée (Constructeur de
  prompts).** Trouvé par la vérification visuelle Playwright de l'étape 10 du plan de refonte :
  `.cp-selected-pill__reset` mesurait 32×32px, sous le standard AAA 44px déjà appliqué aux autres
  boutons de l'outil. Porté à 44×44px. Aucun autre problème bloquant trouvé par la vérification
  (desktop, mobile 390px, zoom 200%, clavier seul, console, contraste AAA 5/5 éléments testés
  ≥7:1, ordre synchrone iOS Safari confirmé par lecture de code).

## [1.139.7] - 2026-08-02

### Corrigé

- **Courriel Schedule sans marche à suivre + seuil trop sensible.** Incident réel le 2026-08-02 à
  10h41-10h42 UTC : un courriel **URGENT** « The schedule did not run yet » sans une seule ligne
  de marche à suivre (contrairement à OPcache) - et c'était le premier courriel Schedule jamais
  reçu, les notifications n'étant actives que depuis la veille (v1.139.2). Vérifié via
  l'historique des 43 631 passages sur 30 jours : 290 échecs (0,66 %), tous des blips de 1-2
  minutes auto-résolus - même surcharge ponctuelle du pool PHP-FPM partagé que celle déjà
  identifiée pour OPcache, jamais une séquence prolongée. Deux correctifs : le seuil de tolérance
  du battement de cœur passe de 2 à 5 minutes (tolère un blip isolé, détecte toujours un vrai
  arrêt du planificateur rapidement), et une marche à suivre concrète a été ajoutée. Deux tests
  verrouillent la présence/absence de la marche à suivre selon le statut.

## [1.139.6] - 2026-08-02

### Corrigé

- **Marche à suivre erronée sur un timeout de mesure.** Incident réel le 2026-08-01 à 21h11
  Québec : un timeout cURL (5001 ms) contre le point de contrôle interne a produit un courriel
  **URGENT** affichant quand même « augmentez `opcache.max_accelerated_files`, redémarrez
  PHP-FPM » - une consigne fausse puisqu'aucune capacité n'a pu être mesurée. Vérifié via
  l'historique des 304 474 passages en base : incident isolé (1 seul depuis le 2026-08-01
  15h57), cohérent avec une surcharge ponctuelle du pool PHP-FPM **partagé par des dizaines de
  scripts cron d'autres sites** sur ce serveur mutualisé - pas un problème récurrent.
  La marche à suivre se choisit désormais selon la présence de mesures réelles : capacité
  saturée → la procédure d'augmentation existante ; mesure impossible → une nouvelle procédure
  de diagnostic de charge, sans toucher à OPcache. Deux tests verrouillent chaque cas.

## [1.139.5] - 2026-08-01

### Corrigé

- **Plus de courriel quand tout va bien.** Un message intitulé « AVERTISSEMENT » arrivait alors
  que son seul contenu disait « OPcache dispose d'une capacité suffisante. Aucune action
  requise » - clés 34,4 %, mémoire 33,5 %, zéro refus. Cause : Spatie envoie une notification
  pour **tout contrôle dont le message n'est pas vide, quel que soit son statut**
  (`RunHealthChecksCommand` ligne 116) ; le filtrage sur l'échec n'intervient que si
  `only_on_failure` est vrai, et il est volontairement faux ici pour être prévenu dès les
  avertissements. J'avais écrit `ok('OPcache dispose…')` : ce simple message suffisait à
  déclencher l'envoi. Tous les contrôles Spatie natifs retournent `ok()` **sans** message -
  c'est pour cette raison qu'eux ne produisaient rien. Le contrôle est désormais silencieux
  quand tout va bien ; son état reste lisible sur `/health` via le résumé chiffré.
  Deux tests verrouillent les deux sens : silence quand c'est sain, message dès qu'il y a
  vraiment quelque chose à signaler.

## [1.139.4] - 2026-08-01

### Corrigé

- **La marche à suivre OPcache ne s'affiche plus quand OPcache va bien.** Le courriel déclenché
  par l'échec d'un **autre** contrôle annonçait « OPcache dispose d'une capacité suffisante,
  aucune action requise » puis listait quand même « augmentez la directive saturée ». Une
  consigne contradictoire est une consigne qu'on apprend à ignorer. Elle est désormais
  conditionnée au statut réel du contrôle, et un test le verrouille.
- **Fin du faux URGENT à chaque mise en ligne.** Le déploiement lance `optimize:clear`, qui vide
  le cache et donc la marque de passage du planificateur ; le contrôle suivant, une minute plus
  tard, la trouvait absente et envoyait « The schedule did not run yet » en **URGENT**. Constaté
  en production à 16h29 Québec (20:29 UTC), deux minutes après un déploiement. Le workflow repose
  maintenant le battement de cœur juste après avoir vidé les caches. Une alerte qui sonne à
  chaque déploiement finit ignorée le jour où le planificateur s'arrête vraiment.

## [1.139.3] - 2026-08-01

### Corrigé

- **Le courriel d'alerte devient lisible et actionnable.** Corrigé après avoir lu le premier
  courriel réellement reçu (16h16 Québec, 20:16 UTC), pas après l'avoir imaginé. Deux défauts que
  seul le message réel révèle : `json_encode` dumpait les mesures brutes, donc des flottants à
  précision machine (`29.39999999999999857891452847979962825775146484375` pour 29,4) et un pavé
  JSON de 900 caractères dans un courriel censé être clair pour un lecteur non technicien ; et la
  ligne annonçait « marche à suivre » sans en donner aucune. Désormais : mesures traduites en
  libellés français (« Table des clés occupée : 29,4 % ») et un bloc de 5 étapes concrètes propre
  à OPcache - chemin du `.ini`, sauvegarde préalable, directive à augmenter selon ce qui sature,
  commande de redémarrage, et l'avertissement qu'elle touche **tous** les sites PHP du serveur.
  Un test verrouille les deux corrections.

## [1.139.2] - 2026-08-01

### Corrigé

- **Les notifications de santé peuvent enfin partir.** `config/health.php` avait
  `'enabled' => false` figé en dur : le contrôle pouvait tourner et échouer, **aucun courriel ne
  partait jamais**. Toute la chaîne de notification était morte depuis l'installation du paquet.
  Trouvé en **vérifiant la boîte de réception** plutôt qu'en supposant l'envoi — le contrôle avait
  bien produit son avertissement sur `/health`, mais rien n'était arrivé, ni en réception, ni en
  spam. Désormais piloté par `HEALTH_NOTIFICATIONS_ENABLED`, à `false` par défaut.
- **`OptimizedAppCheck` retiré.** Il exige une configuration mise en cache, or `config:cache` est
  **interdit sur ce projet** : des `env()` sont lus au moment de l'exécution et la mise en cache
  ferme `/academie` (décision du 2026-06-30). Ce contrôle était donc **rouge en permanence, par
  conception**. Allumer les notifications sans le retirer aurait envoyé une alerte pour une
  condition volontaire, dès le premier passage. Un contrôle qui ne peut jamais passer n'alerte de
  rien : il apprend seulement à ignorer le tableau de bord. À remettre le jour où la dette des
  `env()` au runtime sera résorbée (tâche #1469).

## [1.139.1] - 2026-08-01

### Corrigé

- **Le signal de refus n'est plus évalué qu'en situation de pression réelle.** Défaut trouvé en
  mesurant la production dix minutes après le déploiement de 1.139.0 : l'écart `misses` moins
  `num_cached_scripts` était passé de 23 à 436 alors que le cache n'était rempli qu'à 28,7 %. La
  cause n'était pas un refus mais le déploiement lui-même — avec `validate_timestamps=1`, chaque
  fichier modifié est invalidé puis recompilé. Le seuil d'avertissement étant à 100, l'alerte
  aurait sonné à **chaque mise en ligne**. Deux tests verrouillent le comportement dans les deux
  sens.

## [1.139.0] - 2026-08-01

### Ajouté

- **Surveillance OPcache branchée sur Spatie Health**, avec un courriel d'alerte lisible plutôt que
  technique. Aucun nouveau cron : `health:check` est déjà planifié à la minute et dispose déjà d'un
  battement de coeur (`health:schedule-check-heartbeat`).
- Point d'entrée HTTP protégé par jeton (`Modules/Health/app/Http/Controllers/OpcacheStatusController.php`).
  Le check DOIT passer par HTTP : le CLI et PHP-FPM sont deux SAPI distincts avec deux caches
  différents, et `opcache_get_status()` en ligne de commande ne voit pas le cache servi au web.
  Le jeton voyage par en-tête `X-Sante-Jeton` et jamais dans l'URL, pour ne pas atterrir dans les
  journaux d'accès du serveur web ni du réseau de diffusion.
- **Quatre signaux indépendants** (`Modules/Health/app/Checks/OpcacheCheck.php`) : occupation des
  clés, de la mémoire, du tampon de chaînes internées, et **progression des refus** (`misses` moins
  `num_cached_scripts`). Ce quatrième signal est le plus important : le 2026-08-01, le cache était
  saturé à 100 % des clés alors que 285 Mo de mémoire restaient libres. Un seuil unique sur la
  mémoire n'aurait jamais sonné.
- Le check échoue explicitement s'il ne parvient pas à mesurer (connexion refusée, JSON incomplet).
  Un contrôle qui ne peut pas mesurer ne renvoie jamais « tout va bien ».
- Notification `CheckFailedNotification` en français lisible, forcée sur le mailer `workspace`.
  Brevo reste réservé à l'infolettre. Elle profite à **tous** les contrôles de santé, pas seulement
  à OPcache.
- Tout est activable et désactivable par configuration, sans valeur en dur.

### Contexte

L'OPcache partagé de ea-php84 a été porté de 1024 Mo à **3584 Mo**, de 20000 à **130987 clés** et de
128 Mo à **640 Mo** de chaînes internées, JIT désactivé. Avant : 758 909 ratés pour 19 120 scripts en
cache, soit **739 789 refus purs**, et `cache_full = OUI` pendant des heures sans aucun redémarrage
automatique. Après : `cache_full = NON` et un écart ratés-scripts de **23**, donc zéro refus.

## [1.138.1] - 2026-08-01

### Correctif - Performance de la page d'accueil (index composite manquant)

La page d'accueil coûtait **1 745 ms** de temps serveur, dont **1 710 ms de SQL**. Une seule
requête en représentait **1 642 ms**, soit **94 % du total** :

```sql
select ... from `news_articles` where `is_published` = ? order by `pub_date` desc limit 8
```

Origine : `Modules/FrontTheme/app/Http/Controllers/HomeController.php` lignes 74 à 79.

**Plan d'exécution mesuré en production avant correction** : `type=ALL`, `key=NULL`,
`Using where; Using filesort`, **19 863 lignes balayées** puis triées par date pour n'en garder
que 8. Table de **293,95 Mo** (30 084 lignes, dont 5 236 publiées, ligne moyenne de 15 517
octets). **Aucun index n'existait sur `is_published` ni sur `pub_date`.**

**Mesure de contrôle** : requête complète **1 660,22 ms** contre colonnes ciblées seulement
**1 655,96 ms**, soit **4,26 ms d'écart**. La sélection de toutes les colonnes n'était donc pas
en cause malgré les colonnes `text` et `longtext` de la table : seul l'index manquait.

**Point de comparaison** : `/blog` rendait en **36,83 ms**, avec un temps hors SQL quasi
identique à celui de l'accueil (29,05 ms contre 35,36 ms). Ni l'amorçage de Laravel, ni les 196
fournisseurs de services, ni la saturation de l'OPcache n'expliquaient donc l'écart, contrairement
à l'hypothèse de départ : la totalité du facteur 16 tenait dans cette requête.

### Ajouté

- Migration `2026_08_01_000000_add_is_published_pub_date_index_to_news_articles` : index composite
  `news_articles_is_published_pub_date_index` sur `(is_published, pub_date)`.
- Migration **réversible et idempotente** : garde sur le pilote MySQL, `Schema::hasTable`, et
  vérification de l'existence de l'index via `information_schema` avant d'agir. Elle ne peut pas
  échouer si elle est rejouée.
- Aucune donnée modifiée : un index est une structure d'accès. Le retour arrière est un simple
  retrait d'index, prouvé en local par un cycle complet migration, rollback, re-migration.

## [1.138.0] - 2026-08-02

### Feature - Constructeur de prompts, ecran 3 (blocs toujours visibles)

Remplace les 5 accordeons imbriques "+ Reglages avances" derriere le bouton "Affiner" par cinq
blocs affiches EN MEME TEMPS, zero mecanisme d'ouverture/fermeture a l'interieur : Pour qui / Le
resultat / Le ton / Les limites / Un modele. Chaque bloc porte une question en langage humain, un
exemple concret, la mention "Facultatif" et une ligne "Ajoute : ..." qui explique ce que le
dernier choix vient de produire.

- Nouveau composant `x-tools::prompt-card` : vrai bouton radio ou case a cocher (jamais un `<div>`
  qui imite un bouton), coche visible en plus de la couleur pour l'etat selectionne (exigence
  explicite du panel), cible tactile >= 44px.
- Nouveau composant `x-tools::prompt-block` : conteneur reutilise 5 fois (DRY strict, le gabarit
  ne grossit pas de cinq blocs copies-colles).
- Audiences, roles, verbes, formats, longueurs, tons, techniques et langues rendus en cartes
  cliquables plutot qu'en menus deroulants.
- Trois profils de regles conditionnels (Texte / Programmation / Traduction), pre-selectionnes
  par correspondance de mots-cles simples (zero IA dans l'outil - jamais "j'ai compris que...",
  toujours "Vous avez choisi X, j'ajoute donc Y."), toujours corrigeables d'un clic. Programmation
  et Traduction coupent les regles de style francais (ecriture anti-IA, typographie) et
  Programmation ajoute une regle de mise en forme du code.
- Bug trouve en verification visuelle (pas dans les tests) : la ligne "Ajoute : ecriture naturelle
  anti-IA" restait affichee meme quand Cadre strict etait desactive ou qu'un profil supprimait
  reellement la regle du prompt final. Corrige par un getter `_stylistRulesApply` qui reproduit
  exactement la condition deja utilisee dans `get promptSegments()`.
- Les sept fonctions existantes (typographie francaise, ecriture naturelle anti-IA, technique
  zero-shot/few-shot/iterative, poser des questions, reflexion etape par etape, exemples,
  delimiteurs) restent toutes atteignables - deplacees, jamais retirees. Markup des 5 champs
  surveilles par le garde-fou anti-donnees-personnelles deplace VERBATIM (memes id, memes
  attributs).
- 2 assertions Round130AdversarialFixesTest re-ancrees avec justification explicite dans le
  fichier : l'obligation ARIA du <select> retire est reportee vers le `role="radiogroup"` qui le
  remplace (attribut valide sur ce role selon WAI-ARIA), aucun affaiblissement.

Verifie : Pest Modules/Tools 393 passed (1654 assertions, compte identique a avant cette passe),
tests JS 36 fichiers 0 echec, fr.json/en.json synchronises (46 cles ajoutees, toutes les cles
fr.json existent dans en.json). Validation visuelle reelle desktop et mobile (375x812) :
profil auto-detecte par mots-cles confirme en direct (carte "Ecrire ou deboguer du code" ->
profil Programmation, regle de code injectee, regles de style francais absentes de l'apercu),
coche non-couleur confirmee par capture, aucun accordeon residuel (grep).

## [1.137.1] - 2026-08-01

### Fixed - En-tete mobile (logo + hamburger)

- Loupe de recherche qui chevauchait le logo en mobile (375px) : la colonne du logo (~136px de
  contenu) etait plus etroite que le style inline `max-width:200px` de l'image, qui debordait de
  ~64px par-dessus le bouton de recherche. Chevauchement mesure a 0px apres correctif (contre
  44px avant), desktop 1440px inchange au pixel pres (200px).
- Bouton hamburger mobile avec un fond bleu-violet (#3756f7, defaut du theme) hors charte,
  remplace par le teal de la charte (var(--c-primary), #064E5A). Contraste des barres blanches
  mesure a 9.35:1 (WCAG AAA).

## [1.137.0] - 2026-08-01

### Confidentialite - anonymiseur (outil public deja en ligne)

Sept classes de fuites fermees, chacune prouvee par execution sur 300 passages avant et apres.
Le tirage etant aleatoire, une seule execution ne prouve rien : tous les chiffres ci-dessous
sont des taux mesures.

| Cas | Avant | Apres |
|---|---|---|
| Nom de famille apres un verbe (« Appelle Marc Tremblay ») | fuite | 0/300 |
| « 1234 rue des Erables » (article de voie) | fuite | 0/300 |
| « Patrick O'Neil » (apostrophe puis majuscule) | non detecte | 0/300 |
| « JEAN TREMBLAY » (tout en majuscules) | non detecte | 0/300 |
| « Tremblay, Marc » (inversion) | non detecte | 0/300 |
| « 300, 12e Avenue » (adresse ordinale quebecoise) | 300/300 | 0/300 |
| « Patrick d'Astous » (elision minuscule) | 300/300 | 0/300 |
| « Sophie MacDonald » (majuscule interne) | 300/300 | 0/300 |
| « rang Saint-Joseph » (collision partielle de voie) | 27/300 | 0/300 |
| Substitut identique a la vraie valeur | 20/300 | 0/300 |
| Prenom feminin remplace par un prenom masculin | 152/300 | 0/300 |

Le genre merite d'etre nomme : « Marie Tremblay » devenait un prenom masculin une fois sur deux.
L'IA accordait alors au masculin pour une femme, et la reponse restait inutilisable meme apres
restauration des vraies donnees. Le genre est desormais lu dans les catalogues deja presents et
le substitut vient de la meme liste.

Les fausses valeurs ne peuvent plus designer quelqu'un de reel : domaines reserves RFC 2606 au
lieu de gmail.com et videotron.ca, echangeur telephonique force a 555, la plage nord-americaine
reservee a la fiction.

### Perte de donnees - constructeur de prompts

Avec deux champs masques, le bouton de retour ne restaurait que le dernier. Le premier restait
masque sans aucun moyen d'y revenir : le texte d'origine survivait en memoire mais devenait
inaccessible. Chaque champ dispose maintenant de son propre controleur de retour, construit par
une fabrique unique - aucun bloc duplique dans le gabarit.

Le champ de saisie ne disparait plus quand on masque : le remplacement se fait en place, avec un
recapitulatif et un retour possible.

### Tests

Un filet de securite manquait : aucun test ne protegeait les classes de fuites fermees. Deux
nouveaux bancs d'essai couvrent desormais les classes historiques en non-regression, les defauts
corriges, l'anti-collision et la coherence des substituts. Chaque correctif a ete casse
volontairement un par un pour verifier que les tests echouent bien.

Tests JS 411/411 sur 36 fichiers. Pest Modules/Tools 382 passed (1633 assertions).

### Connu, signale plutot que masque

Autoriser la majuscule interne pour capter « MacDonald » fait aussi masquer « MacBook Pro » comme
un nom de personne. Arbitrage assume : sur-masquer legerement plutot que laisser fuir, la
sous-detection etant invisible donc plus dangereuse.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.136.0] - 2026-07-31

### Corrigé
- **Le champ de saisie ne disparaît plus : l'anonymisation se fait EN PLACE.** Cliquer « Masquer mes
  infos personnelles d'abord » faisait disparaître le champ « Sur quoi porte votre demande ? » et
  ouvrait un éditeur en mode Split affichant DEUX zones. La personne passait de 1 zone visible à 2 et
  perdait son champ de vue - l'inverse exact de l'intention « une seule surface d'écriture ».
  Le champ reste maintenant TOUJOURS visible, qu'on anonymise ou non. Le bouton n'ouvre plus rien :
  il masque directement dans le champ.

### Ajouté
- **Récapitulatif de ce qui a été masqué** après l'opération, annoncé aux lecteurs d'écran.
- **Bouton « Annuler le masquage »** bien visible, qui restaure le texte d'origine. Le texte original
  ne vit qu'en mémoire, jamais dans un stockage persistant.

### Retiré
- Le mode Split de ce parcours, jugé « complexité d'expert » par la revue croisée : comparer deux
  versions est un réflexe de développeur, pas un besoin de la personne qui veut juste un texte
  sécuritaire. L'anonymiseur complet reste accessible pour les cas complexes.
- Le bouton « ← Modifier ma demande », devenu inutile puisque le champ ne disparaît plus.

## [1.135.0] - 2026-07-31

### Corrigé
- **Anonymiseur : le nom de famille survivait au masquage.** « Appelle Marc Tremblay » devenait
  « Manon Pelletier Tremblay » - le vrai nom de famille restait en clair dans un texte que la
  personne croyait anonymisé. La détection appariait deux mots capitalisés consécutifs et captait
  « Appelle Marc », laissant « Tremblay » orphelin, donc jamais détecté. Une liste de verbes de
  sollicitation fréquents en tête de phrase (appelle, contactez, veuillez, écrivez...) empêche
  désormais ce vol d'appariement, avec une comparaison insensible aux accents.
- **Service worker : les exclusions ne couvraient que les requêtes GET.** Les routes d'exclusion
  (/admin, /livewire/, cross-origin) étaient enregistrées sans méthode explicite ; Workbox range
  les routes par méthode et utilise GET par défaut. Tout POST vers l'administration ou un composant
  Livewire tombait donc dans le rejeu automatique en arrière-plan, ce qui n'a jamais été voulu.

### Ajouté
- **Filet de sécurité contre la sous-détection** (`detecterFuitesResiduelles`). Après masquage, il
  signale tout fragment d'une donnée source qui survivrait littéralement dans la sortie. Il ne
  corrige rien : il avertit avant que la personne copie un texte incomplètement masqué. La
  sous-détection est le risque le plus dangereux de ce type d'outil, parce qu'elle est invisible.

### Tests
- Le test d'insertion anonymisée était **tautologique** : il vérifiait la présence du jeton masqué
  et restait vert alors que la vraie adresse courriel subsistait à côté. Renforcé par une assertion
  de disparition et une égalité stricte, qui échoue sur toute concaténation résiduelle.

## [1.134.0] - 2026-07-31

### Ajouté
- **Constructeur de prompts - une seule surface d'écriture pour l'anonymisation.** Le panneau
  « Masquer mes infos » et le champ « Sur quoi porte votre demande ? » formaient deux zones de
  saisie pour une même intention. Le champ principal s'efface maintenant pendant que le panneau
  travaille, et un bouton « ← Modifier ma demande » offre une sortie explicite.
- **Pré-remplissage des deux portes d'entrée.** Il n'existait que dans le chemin du bandeau
  anti-données-personnelles ; le bouton « Masquer mes infos » ouvrait un panneau vide. Les deux
  portes partagent désormais la même fonction (DRY).

### Corrigé
- **L'insertion remplace au lieu d'ajouter à la suite.** Le texte d'origine ne subsiste plus à côté
  de sa version masquée : la donnée personnelle disparaît réellement du champ.
- **Le comportement ne dépend plus de la provenance de l'événement.** L'ouverture manuelle et
  l'ouverture programmatique se distinguaient par `evt.isTrusted` - un signal implicite qui rendait
  le comportement invérifiable en test automatisé et fragile à tout traitement différé. Elles se
  distinguent maintenant par un paramètre d'intention passé par l'appelant, et `.click()` n'est plus
  utilisé comme API interne (revue croisée Codex 92/100, Gemini 30/100 - Gemini retenu sur le fond).

## [1.133.1] - 2026-07-30

> Livraison ciblée et volontairement étroite : uniquement l'éditeur d'anonymisation partagé
> (`anonymizer-ui.js`), parce que `/outils/anonymiseur` est PUBLIC et déjà en ligne, alors que le
> constructeur de prompts reste gaté en mode « révision ». Le reste du lot attend la fin de la
> boucle adversariale (voir [Unreleased], cible 1.134.0).

### Fixed

- **La bulle « Anonymiser ce passage » ne montrait rien.** Sélectionner un passage puis cliquer la
  bulle créait bien la règle et affichait « Passage anonymisé », mais l'éditeur restait en mode
  édition, où le volet annoté est en `display: none`. Rien ne changeait à l'écran, et il fallait
  cliquer « Détecter et anonymiser » pour voir le résultat, ce qui donnait l'impression que la
  bulle ne servait à rien. Le geste existait en DEUX exemplaires - la bulle et le bouton
  « Anonymiser la sélection » - et un seul basculait la vue ; les deux passent maintenant par un
  point d'entrée unique.
- **La bulle félicitait même quand elle n'avait rien fait** : le message de succès partait de façon
  inconditionnelle. Sans sélection, l'outil avertit désormais au lieu de confirmer.
- **Valeur de remplacement personnalisée (« Ma valeur »)** : mêmes deux défauts sur ce chemin
  jumeau. Vider le champ proposé puis valider ne créait aucune règle mais annonçait quand même
  « Remplacé par votre valeur » ; et depuis le mode édition, le remplacement s'appliquait dans un
  volet masqué.
- **Sous-bulle « Ma valeur » sans sortie au clavier** : elle contient un champ et un bouton, mais
  seule la touche Entrée avait un effet - une personne naviguant sans souris y restait piégée.
  Échap la referme et rend le focus au bouton qui l'a ouverte.
- **La bulle ignorait le volet « Votre texte »** : elle n'était branchée que sur le volet annoté,
  qui reste vide tant qu'aucune détection n'a tourné. Suivre la consigne affichée dès le premier
  écran - « sélectionnez un passage, surlignez, anonymisez » - ne produisait donc rien du tout, en
  silence.
- **Anonymisation sans détection préalable sans effet à l'écran** : la règle était créée et le
  message annonçait « Passage anonymisé », mais la source n'avait jamais été ingérée, donc le rendu
  n'avait aucun texte sur lequel travailler.
- **Bulle tronquée sur mobile** : sa position horizontale est bornée dans la fenêtre (elle sortait
  de l'écran, mesurée à `left: -64px` sur une largeur de 390 px).

Chaque correctif a été vérifié geste par geste dans le navigateur sur l'outil réel, pas seulement
par lecture de code. Régression : 718 tests Pest verts, 30 fichiers de tests JS verts.

## [Unreleased]

> Cible : 1.134.0. Publication conditionnée à deux verdicts adversariaux consécutifs sans manque
> (gate /100). Le constructeur de prompts reste gaté en mode « révision » jusque-là.

### Added

- **Message d'insertion contextuel** : le toast nomme le champ réellement visé au lieu d'annoncer
  « la tâche » quelle que soit la destination.
- **Badge « Mise à jour en cours »** sur la carte /outils d'un outil en révision, distinct du
  « Bientôt » réservé aux outils jamais lancés.
- **Pack de contexte de génération de tests** (`.claude/refs/test-generation-context.md`) : bloc
  unique rappelé dans chaque délégation, avec les chemins réels du dépôt, la règle d'indexation
  des traductions par la chaîne source française, le harnais CommonJS des tests JS et
  l'obligation du contrôle négatif. Protégé par son propre méta-test.

### Fixed

- **Cible d'insertion figée** : après un passage par le bandeau anti-données-personnelles depuis
  un champ autre que la tâche, toutes les insertions suivantes atterrissaient dans ce champ
  périmé, sans aucune indication à l'écran.
- **Cible effacée par l'ouverture du panneau** : le clic synthétique qui déplie le panneau
  exécutait le handler d'ouverture manuelle et réinitialisait la cible avant usage. Le texte
  anonymisé partait dans la tâche pendant que la donnée personnelle restait en place dans le
  champ qui avait déclenché l'alerte.
- **Texte anonymisé perdu en silence** : si le panneau d'édition d'une carte était refermé entre le
  moment où l'on choisissait « Masquer mes infos » et le clic sur « Insérer », le texte partait dans
  un champ qui n'existait plus à l'écran, avec un message de succès par-dessus. L'outil dit
  maintenant clairement que le champ n'est plus affiché et n'insère rien, plutôt que d'écrire
  ailleurs (ce qui recopierait la donnée personnelle au lieu de la masquer).
- **Le parcours guidé « Masquer mes infos » ne masquait rien** (défaut de confidentialité, présent en
  production). Deux causes cumulées : l'ouverture automatique déclenchait « Détecter seulement », qui
  souligne les données repérées sans créer la moindre règle de masquage ; et l'insertion AJOUTAIT le
  résultat à la suite du champ au lieu de le remplacer, alors que ce champ contient précisément la
  donnée personnelle. Le champ finissait avec le vrai courriel ET sa copie, le tout sous un message
  « Texte anonymisé inséré ». Vérifié au navigateur avant et après correction.
- **Prompt importé impossible à supprimer** de l'historique avant un rechargement de page :
  l'identifiant public n'était pas utilisé sur ce seul chemin, contrairement à la sauvegarde
  ordinaire.
- **Interface anglaise** : quatre libellés de l'outil s'affichaient en français, dont le nom du
  champ à l'intérieur de l'alerte de données personnelles.
- **Bouton « Insérer » sans effet visible** : après avoir masqué ses infos depuis un champ autre
  que la tâche, cliquer sur « Insérer » ne produisait plus aucun message et laissait le panneau
  ouvert, alors que le texte avait bel et bien été inséré. Une fonction utilitaire de libellé
  était déclarée dans un bloc inaccessible depuis l'insertion, ce qui interrompait le traitement
  juste après l'écriture du texte et laissait aussi la cible d'insertion bloquée sur ce champ.
- **Bulle tronquée sur mobile** : position horizontale bornée dans la fenêtre (mesurée à
  `left: -64px` en 390 px de large).
- **Texte d'aide trompeur** : le gabarit de carte ne « pré-remplit » plus automatiquement depuis
  le correctif de préservation du texte saisi ; le libellé le dit maintenant.
- **Lien du wizard** vers la bibliothèque dédiée « Mes prompts » au lieu de la page générique.
- **Outil gaté** : `noindex` sur la page placeholder et exclusion du sitemap, alignés sur le
  patron déjà appliqué par les deux autres modules gatés.
- **Focus perdu** après Supprimer et Dupliquer, y compris depuis le menu ⋮ (composant partagé par
  six modules).
- **Champ Exemples** désormais surveillé par le garde-fou de données personnelles.
- **Destination Mistral** : boutons « Ouvrir dans » présents dans les deux rangées.
- **Accessibilité** : `aria-required` sur les champs persona, bannière de validité annoncée et
  reliée aux trois boutons qu'elle explique.

### Changed

- **Suite de tests JS** : `npm run test:js` énumère automatiquement `tests/js/*.test.cjs`. La
  liste était écrite à la main, donc tout nouveau test était silencieusement ignoré.
- **Cache de vues compilées isolé par worker Paratest** : supprime 26 faux échecs intermittents
  causés par une course entre workers sur `storage/framework/views`.
- **Capture de sélection factorisée** dans l'éditeur d'anonymisation : trois copies divergentes
  de la même logique ramenées à une brique unique. C'est leur divergence qui avait produit le
  défaut d'origine.

## [1.133.0] - 2026-07-26

### Added
- **Message "indisponible" à 2 modes pour les outils** (`tools.construction_mode`) — mode "construction" (nouvel outil, ton anticipation) et mode "révision" (outil retiré temporairement pour amélioration, ton transparence + réassurance explicite sur la conservation des données sauvegardées). Palette indigo/ambre pour la révision, zéro rouge.

### Changed
- `/outils/constructeur-prompts` remis en révision (superadmin seulement) le temps d'une refonte plus poussée, suite aux retours de l'utilisateur sur la découvrabilité des options et la réutilisation des prompts sauvegardés.

## [1.132.0] - 2026-07-26

### Added
- **`/outils/constructeur-prompts` : refonte "objectif d'abord" (Phases 1-3 de l'audit, plan validé par Codex/Gemini/claude.ai)** — nouvelle étape 1 par cartes de tâches concrètes (Rédiger, Résumer, Trouver des idées, Analyser, Apprendre, Traduire, Planifier, Coder...) au lieu du concept "Persona" en premier ; wizard simplifié à 2 étapes + panneau unique "Afficher tous les réglages" (divulgation progressive, pas de bascule de mode) ; vocabulaire technique reformulé en langage courant ; aperçu en langage courant avant la vue technique.
- Nouveau test JS (`constructeur-prompts-openin.test.cjs`, 26/26) validant la génération des 4 liens ChatGPT/Claude/Perplexity/Gemini.

### Changed
- Script CDN `@alpinejs/intersect` chargé uniquement sur les 4 pages qui en ont réellement besoin (`/blog`, `/annuaire`, `/glossaire`, `/acronymes-education`), version pinnée + intégrité SRI. Cache-Control immutable étendu aux assets du constructeur de prompts.

## [1.131.0] - 2026-07-26

### Added
- **Maillage interne : article OpenClaw relié au reste du site** — relation glossaire broader/narrower Docker↔Socket ; liens contextuels ajoutés dans l'article OpenClaw vers l'Anonymiseur, le Constructeur de prompts et le Concentré qui couvrait déjà l'outil sous son ancien nom "Moltbot/Clawdbot" ; liens réciproques depuis l'article "C'est quoi le MCP ?" et ce Concentré vers l'article OpenClaw.

### Fixed
- **`/outils/constructeur-prompts` Phase 0 (audit du 2026-07-26)** : bandeau de cookies (site-wide) qui bloquait le formulaire et dont le bouton "Tout accepter" pouvait sortir du viewport mobile — corrigé avec footer d'actions toujours visible, unités `dvh`/`env(safe-area-inset-bottom)`, et attribut `inert` sur la modale fermée. Cibles tactiles des radios agrandies (13px→24px+). Contrastes corrigés vers AAA (lien "vos sauvegardes" 2,22:1→11,65:1 ; message de confidentialité 3,02:1→15,89:1). JS inline (~430 lignes) extrait vers un fichier externe mis en cache navigateur, `Cache-Control` immutable ajouté sur `/build/`. Accents corrigés dans le seeder de configuration. 14 nouveaux tests Feature pour `SavedPromptController` (IDOR, validation, auth, soft-delete).

## [1.130.0] - 2026-07-26

### Added
- **Glossaire : nouveau terme "Socket"** (`/glossaire "socket"`) — point de communication logiciel réseau (IP:port) ou local (Unix domain socket). Recherche `perplexity/sonar-pro` croisée avec Codex (Berkeley sockets = 4.2BSD, UC Berkeley, rapport Leffler/Fabry/Joy du 27 juillet 1983 ; distinction protocole TCP/UDP vs type de socket vs famille d'adresses). 2 sources officielles vérifiées (UC Berkeley, POSIX.1 The Open Group). Image de couverture via `/nanobanana`. Migration réversible.

### Fixed
- **Bug systémique : `match_strategy` invalide sur 25 termes du glossaire empêchait l'auto-lien** — découvert en investiguant un signalement utilisateur (« licence MIT » non soulignée dans l'article OpenClaw malgré le terme existant). 25 termes ajoutés entre le 21 et le 25 juillet 2026 (les 20 licences open source + OpenClaw, sudo, MITRE ATT&CK, TCC, Laravel Herd) avaient `match_strategy = 'exact'`, une valeur **invalide** non reconnue par `GlossaryLinkifier::matchInText()` — le code retombait alors sur une correspondance stricte à la casse exacte du nom du terme. Pour les 20 licences, dont le nom commence par le mot français commun « Licence » (majuscule), la prose naturelle écrit presque toujours « licence » en minuscule en milieu de phrase : ces 20 termes ne se sont **jamais** auto-liés correctement depuis leur création. Corrigé vers `'loose'` (insensible à la casse) pour les 20 licences, `'case_sensitive'` (normalisation sans changement de comportement) pour les 5 autres. Migration réversible.

## [1.129.1] - 2026-07-25

### Fixed
- **Bug racine : featured_image cassé silencieusement sans repli** (`Modules\Blog\Models\Article::getFeaturedImageUrlAttribute()`) — l'accesseur générait toujours une URL à partir de la valeur DB sans jamais vérifier que le fichier existait physiquement. 12 articles "Concentré IA hebdo" avaient un `featured_image` pointant vers un chemin fantôme (`images/blog/concentre-hebdo-...jpg`, jamais réellement téléversé par le script de publication — 3 d'entre eux partageaient même par erreur la même valeur copiée-collée), produisant une balise `<img>` cassée sans jamais lever d'erreur. Ajout d'une vérification `file_exists()`/`Storage::disk('public')->exists()` selon la convention de chemin, avec repli sur l'image par défaut du site (`images/og-image.png`, déjà utilisée comme fallback og:image ailleurs) — défense en profondeur qui empêche toute récurrence de ce type de bug, peu importe sa cause future. 4 nouveaux tests Pest (`Modules/Blog/tests/Unit/ArticleFeaturedImageUrlTest.php`), 11/11 tests du module Blog verts.
- **Données** : les 12 articles concernés ont reçu de vraies images de couverture générées via `/nanobanana` (Gemini Playwright, compte utilisateur), reproduisant fidèlement le style photo déjà établi des Concentrés existants (bureau vitré nocturne, gratte-ciel en fond, panneau holographique bleu/teal, texte "La veille de Stef"), téléversées en production et `featured_image` corrigé pour chacun vers la convention qui fonctionne réellement (`storage/blog/{fichier}.jpg`).

## [1.129.0] - 2026-07-25

### Added
- **Mode Glossaire (article de blog) : auto-liens moins agressants + toggle désactivable** — demande explicite de l'utilisateur, veille `pp_search` croisée Codex/claude.ai/Gemini sur 3 volets (agressivité visuelle, découvrabilité, comportement désactivé). `GlossaryLinkifier` accepte une nouvelle option opt-in `per_section` (1 occurrence par terme **par section H2** au lieu de par article entier, défaut `false` = comportement historique inchangé sur les autres call sites glossaire/acronymes/annuaire). Appliqué uniquement sur `blog/show.blade.php` via `@glossarize($articleContent, ['per_section' => true, 'max_occ' => 1])`. Nouveau bouton **"Glossaire : Actif/Désactivé"** dans la barre d'action de l'article (pattern `.aab-btn` existant), état persisté en `localStorage`. Désactivé = suppression **totale** de l'interaction (`pointer-events:none` + tooltips forcés cachés, pas seulement un changement de style visuel).

### Fixed
- **3 bugs trouvés par vérification visuelle Playwright avant livraison, corrigés le jour même** : (1) le bouton toggle ne s'affichait jamais — l'ordre de rendu Blade appelait la barre d'action AVANT `@glossarize()`, donc `GlossaryLinkifier::getLastMatchedTerms()` était toujours vide au moment du bouton ; déplacé le calcul du contenu linkifié avant l'include de la barre d'action. (2) Le soulignement pointillé de `.glossary-link` était écrasé en soulignement plein par une règle plus spécifique de `charte.css` (`.wpo-blog-single-section .entry-details a:not(.btn)`) — `!important` ajouté sur les propriétés `text-decoration*` concernées. (3) La limite "1 lien par section H2" ne s'appliquait pas réellement (plafond resté à 10/section) faute du paramètre `max_occ => 1` dans l'appel `@glossarize`.

### Verified
- 18 tests Pest (2 nouveaux sur `walkAndReplace()` via Reflection, sans dépendance DB) ; 188 tests Core+FrontTheme+Blog verts (zéro régression). Suite complète : 116 échecs préexistants dans des modules indépendants (service worker, lien magique, campagnes newsletter, bannière vérification courriel) — aucun dans les fichiers touchés aujourd'hui. Vérification visuelle Playwright complète (bouton visible, pointillé confirmé, 1 lien/section H2, toggle ON/OFF avec persistance, aucune régression sur le reste de la page article).

## [1.128.1] - 2026-07-25

### Fixed
- **Accordéons FAQ d'article : caret/chevron manquant** (`.article-faq-item`, `public/css/components.css`) — le `display: flex` appliqué à `<summary>` (nécessaire à l'alignement du texte) empêchait silencieusement le rendu du `::marker` natif (le marker n'existe que sous `display: list-item`), ce qui rendait la règle `summary::marker { color: var(--c-primary) }` totalement inopérante depuis l'introduction du composant. Bug latent jamais visible en prod : l'article OpenClaw (`comment-installer-openclaw-en-toute-securite-sur-macos`, id=67) est le tout premier à publier une FAQ depuis la mise en place de ce composant. Trouvé par l'utilisateur en révision visuelle. Corrigé en ajoutant un chevron `::after` (glyphe `›`, rotation 90° à l'ouverture, `prefers-reduced-motion` respecté) reprenant exactement le pattern déjà établi par `x-core::accordion`. Vérifié : composant partagé par TOUS les articles avec FAQ publiée (pas spécifique à OpenClaw) ; module Books (`bk-faq-item`) utilise une classe CSS distincte sans cette règle, non affecté.

## [1.128.0] - 2026-07-25

### Added
- **Glossaire : nouveau terme "Commandes shell"** (`/glossaire "commandes shell"`) — concept général de l'interpréteur de commandes Unix/Linux/macOS/Windows (pas un shell précis). Recherche `perplexity/sonar-pro` croisée avec Codex (précisions : Thompson shell documenté dans le 1er manuel Unix du 3 novembre 1971 ; Bourne shell développé dès 1976, décrit publiquement en 1978, distribué avec Unix V7 en 1979 ; Bash débuté le 10 janvier 1988 par Brian Fox, bêta 0.99 annoncée le 8 juin 1989). 2 sources officielles vérifiées joignables (curl HTTP 200) : POSIX.1-2024 (The Open Group/IEEE) et le man page GNU Bash via man7.org. Relié au terme existant "sudo" (narrower_slugs). Image de couverture via `/nanobanana`. Migration réversible.

## [1.127.0] - 2026-07-25

### Added
- **Glossaire : 19 nouveaux termes de licences open source** (`/glossaire "de chacune des licences"`) — MIT, BSD 2-Clause, BSD 3-Clause, ISC, zlib, Boost Software License, The Unlicense, CC0 1.0 Universal, Creative Commons (licences de contenu), The PostgreSQL License, SIL Open Font License 1.1, GNU GPL v2, GNU GPL v3, GNU AGPL v3, GNU LGPL, Mozilla Public License 2.0, Eclipse Public License 2.0, CDDL, Artistic License 2.0. Demande explicite de l'utilisateur suite à la liste complète des licences fournie. Le 20e terme prévu, "Apache 2.0", existait déjà en prod depuis un lot antérieur (contenu adéquat, non lié aux licences) — l'anti-doublon a correctement évité toute duplication ; seule son image de couverture a été rafraîchie pour cohérence visuelle avec le reste de la famille. Recherche `perplexity/sonar-pro` par famille de licence, croisée avec Codex (corrections : année exacte ISC 1995, année zlib 1995 confirmée par lecture directe du texte de licence). Sources officielles vérifiées joignables (curl HTTP 200), sauf 3 URLs gnu.org (AGPL/LGPL) non re-vérifiables au moment de la rédaction en raison d'une panne réseau transitoire du domaine (URLs soeurs identiques déjà vérifiées + miroirs OSI équivalents, confiance élevée maintenue). Catégorie "concepts-fondamentaux". 19 nouvelles images de couverture + 1 image rafraîchie via `/nanobanana` (métaphore visuelle dédiée par licence, style isométrique teal/orange cohérent, aucun logo de marque réelle). Migration réversible unique.

## [1.126.0] - 2026-07-25

### Added
- **Glossaire : nouveau terme "Digest SHA-256"** (`/glossaire "digest SHA-256"`) — fonction de hachage cryptographique SHA-2 (NSA/NIST), pertinente pour la vérification d'intégrité de fichiers et de paquets logiciels. Recherche `perplexity/sonar-pro` croisée avec Codex (dates exactes : FIPS 180-2 le 1er août 2002, FIPS 180-4 finalisé le 4 août 2015, collision SHAttered de SHA-1 en février 2017). 2 sources officielles NIST vérifiées joignables (curl HTTP 200 : csrc.nist.gov). Image de couverture via `/nanobanana`. Migration réversible.

## [1.125.0] - 2026-07-25

### Added
- **Glossaire : nouveau terme "Laravel Herd"** (`/glossaire "Laravel Herd"`) — environnement de développement local natif pour PHP/Laravel (Laravel LLC), utilisé sur ce projet même. Recherche `perplexity/sonar-pro` croisée avec Codex (date exacte de lancement 21 juillet 2023, Windows 1.0.0 le 26 mars 2024, correction des fonctionnalités Pro : tunnel Expose et non ngrok, Laravel Valet toujours existant en parallèle). 2 sources vérifiées joignables (curl HTTP 200 : laravel-news.com, herd.laravel.com). Image de couverture via `/nanobanana`. Migration réversible.

## [1.124.0] - 2026-07-25

### Added
- **Glossaire : nouveau terme "TCC"** (`/glossaire "TCC"`) — Transparency, Consent, and Control, sous-système de sécurité/confidentialité de macOS régulant l'accès des applications aux ressources sensibles (caméra, micro, localisation, Full Disk Access, etc.), pertinent pour le contexte de persistance macOS via LaunchAgents évoqué dans l'article OpenClaw. Recherche `perplexity/sonar-pro` croisée avec Codex (correction de la date d'introduction : OS X Mountain Lion 10.8/2012, pas Mavericks 10.9/2013). 2 sources officielles vérifiées joignables (curl HTTP 200 : developer.apple.com, attack.mitre.org T1548.006). Image de couverture via `/nanobanana`. Migration réversible.

## [1.123.0] - 2026-07-25

### Added
- **Glossaire : nouveau terme "MITRE ATT&CK"** (`/glossaire "MITRE ATT&CK"`) — cadre de référence mondial des tactiques, techniques et sous-techniques d'attaquants réels (v19, 15 tactiques, 222 techniques, 475 sous-techniques), cité dans l'article OpenClaw pour la technique de persistance macOS T1569.001. Recherche via `perplexity/sonar-pro` (pp_search indisponible), 2 sources officielles vérifiées joignables (curl HTTP 200, attack.mitre.org). Catégorie "securite-et-ethique" résolue dynamiquement. Image de couverture via `/nanobanana` (compte Gemini de l'utilisateur, matrice/grille isométrique teal/orange, aucun logo de marque réelle). Migration réversible.

## [1.122.0] - 2026-07-25

### Added
- **Glossaire : 4 nouveaux termes** liés à l'article OpenClaw : **"nvm"** (gestionnaire de versions Node.js, sans sudo) ; **"Node.js"** (alias "Node" - une seule fiche plutôt que deux redondantes, en réponse à la question de l'utilisateur "Node et Node.js, est-ce la même chose ?" - réponse : oui, exactement) ; **"OpenClaw"** (CVE-2026-32922 cross-vérifiée via 4 sources indépendantes : NVD, GitHub Security Advisory, Snyk, SentinelOne) ; **"sudo"** (principe du moindre privilège, pourquoi certains outils déconseillent son usage à l'installation). Sources toutes vérifiées joignables (curl HTTP 200) avant écriture. Images de couverture via `/nanobanana` (compte Gemini de l'utilisateur, style isométrique teal/orange, aucun logo de marque réelle). Migrations réversibles.

## [1.121.0] - 2026-07-25

### Fixed
- **Accordéon FAQ des articles hors charte graphique** (signalé par l'utilisateur, comparé aux autres articles) : `Modules/FrontTheme/resources/views/blog/partials/faq-accordion.blade.php` posait un attribut `style` statique complet (fond, couleur, bordure) PUIS un binding Alpine.js `:style` qui ne fusionne pas mais **remplace tout l'attribut style** au premier rendu — effaçait donc fond/couleur/bordure/largeur, laissant le style natif du navigateur (bordure noire, fond gris). Bug de fond, dormant depuis sa création (aucun autre article n'avait de FAQ publiée avant l'article OpenClaw). Remplacé par le pattern natif `<details>/<summary>` déjà éprouvé et sans JS dans `Modules/Books` — couleurs alignées charte (`var(--c-primary)`/`var(--c-dark)`) au lieu du gris/bleu générique précédent.
- **Bouton copier sur les blocs de code jamais livré en production** malgré du code prêt et testé localement (icône seule, réutilise `window.copyToClipboard` + toast global existant) — fichiers modifiés mais jamais commités. Déployé.
- **Espacement compressé des listes `<ul>/<ol>` du corps d'article** (encadré rouge visuellement différent des autres, signalé par l'utilisateur) : même cause — le CSS correctif (`line-height` 1.6→1.8, marges) était écrit localement mais jamais déployé.

### Changed
- **Migration `Modules/Tools` crosswords (Mes grilles)** vers le composant DRY `action-menu` (kebab compact), alignement avec le reste du site.

## [1.120.6] - 2026-07-25

### Added
- **Glossaire : nouveau terme "Docker"** — conteneurisation, reproductibilité des environnements dev/IA/ML, usage comme bac à sable pour agents IA autonomes (risque `docker.sock` = accès équivalent root explicitement couvert). Recherche croisée (Perplexity + Codex, recherche web réelle), 3 sources vérifiées joignables (HTTP 200) avant écriture. Image de couverture générée via `/nanobanana` (compte Gemini de l'utilisateur, style isométrique teal/orange, aucun logo Docker réel). Migration réversible.

## [1.120.5] - 2026-07-25

### Fixed
- **Police incohérente sur les blocs de code des articles** (signalé par l'utilisateur sur l'article OpenClaw) : le sélecteur CSS `.wp-block-code pre` ne matchait jamais (la classe `wp-block-code` est posée directement sur le `<pre>`, pas sur un wrapper) — seul `.wp-block-code code` s'appliquait, laissant le conteneur `<pre>` retomber sur la police monospace par défaut du navigateur (SFMono/Menlo, taille différente) au lieu de JetBrains Mono. Sélecteur corrigé (`.wp-block-code, .wp-block-code code`).
- **Sommaire de l'article vérifié** (même signalement) : la hiérarchie h2/h3 du composant `<x-fronttheme::table-of-contents>` s'est avérée techniquement correcte (imbrication propre, pas de bug) - fausse alerte initiale corrigée après re-vérification par méthode de test appropriée.

### Changed
- **Séparation visuelle des h3 imbriqués sous un h2** (ex. étapes numérotées d'un tutoriel) : bordure supérieure + marge accrue pour rester lisible sur les articles longs à plusieurs sous-étapes consécutives (signalé « pas facile à suivre » sur l'article OpenClaw, 9 étapes). Changement CSS générique site-wide, bénéficie à tout article structuré ainsi.

### Fixed
- **Bug systémique : image mise en avant cassée sur tout article (signalé par l'utilisateur)** : deux conventions de stockage incompatibles coexistaient pour `articles.featured_image` - upload admin (`store('articles', 'public')`, chemin sans préfixe `storage/`) vs import WordPress (chemin déjà préfixé `storage/blog/...`). Toutes les vues appelaient `asset($article->featured_image)` directement, ce qui cassait systématiquement l'image de tout article dont l'image a été téléversée via l'admin (pas seulement l'aperçu de l'article en brouillon signalé initialement - impact identique en production sur les articles publiés). Nouvel accesseur unique `Article::getFeaturedImageUrlAttribute()` qui détecte la convention et génère la bonne URL dans les deux cas ; remplace les ~22 appels `asset($x->featured_image)` dans 12 fichiers (admin + thème public + accueil). Suite Blog 45/45 verte après correction.

## [1.120.3] - 2026-07-24

### Fixed
- **Reproductibilité du fix modèles IA prod (durabilité)** : le correctif appliqué le 2026-07-21 sur les 6 clés `ai.*_model` (alignement sur `openrouter/free`, résolvait un "service IA indisponible") avait été fait par une UPDATE SQL manuelle directe sur la table `settings` de production, jamais capturée en migration. Trouvé par une passe de vérification adversariale indépendante : si la table `settings` prod est un jour restaurée depuis un backup antérieur au 21/07, ou qu'un nouvel environnement est provisionné, le bug réapparaît silencieusement car `SettingsDatabaseSeeder` (`firstOrCreate`) n'écrase jamais une ligne déjà existante et n'est de toute façon jamais rejoué en déploiement. Nouvelle migration `2026_07_24_180000_fix_ai_models_openrouter_free.php` (`updateOrInsert`) qui capture le correctif dans le code versionné et le rend reproductible sur tout environnement.

## [1.120.2] - 2026-07-24

### Fixed
- **Français sans accents (i18n)** : correction de 5 endroits trouvés par audit où du texte français était tapé sans accents. Page de construction du Sudoku (public + phrases traduites) ; description JSON-LD de la page de jeu Sudoku (indexée par Google, impact SEO direct) ; libellés de la « Courbe d'apprentissage » dans le formulaire admin de l'Annuaire (Phase 3) ; texte explicatif dupliqué sur la répartition des abonnés newsletter (`Modules/Newsletter/admin/stats.blade.php` et `Modules/Backoffice` `subscribers-table.blade.php`) ; 2 valeurs de traduction dans `lang/fr.json` (et `lang/fr_CA.json`, symlink) pour l'expérience A/B. Aucun changement de structure, uniquement le texte.

## [1.120.1] - 2026-07-24

### Security
- **Dépendances** : mise à jour `dompdf/dompdf` v3.1.4 → v3.1.6, corrige 6 avis de sécurité publiés le 2026-07-22 (déni de service et fuite de fichiers via SVG intégré dans un PDF) sur une dépendance exposée en surface publique (Modules/Tools, génération PDF des grilles de mots croisés) et Académie (certificats). Trouvé pendant l'audit plateforme du jour. `composer audit` confirme 0 vulnérabilité restante. Suite de tests ciblée (Academy, Decido, Export, Tools) 129/129 verte après mise à jour.

### Fixed
- **Traductions** : retrait d'une entrée résiduelle non intentionnelle (`"Login": "Updated Login"`) dans `lang/en.json`, trouvée pendant l'audit (artefact non commité, pas une vraie traduction).

## [1.120.0] - 2026-07-24

### Added
- **Annuaire** : infrastructure de liens d'affiliation. Badge de divulgation visible "Lien affilié" sur les fiches outil concernées, page `/annuaire/politique-affiliation`, tracking de clic sortant réel (`outbound_clicks_count`, distinct des vues de fiche) via la route `directory.visit`, filtre admin `?affiliate=yes|no`, fichier de référence `config/affiliate_programs.php` documentant 12 programmes confirmés (Canva AI, ElevenLabs, Grammarly, Copy.ai, Notion AI, Runway, Murf AI, Synthesia, Jasper, HeyGen, Writesonic, Descript) croisés avec les outils les plus cliqués du site.

### Fixed
- **Annuaire** : fusion des fiches dupliquées "Jasper AI"/"Jasper" (même produit, deux seeders différents) via le mécanisme de redirection déjà en place, aucune perte de données.

### Changed
- **Pied de page** : le texte de divulgation d'affiliation déjà présent est désormais lié vers la nouvelle page de politique ; contraste corrigé à 12.44:1 (AAA).

## [1.119.0] - 2026-07-23

### Added
- **Annuaire** (`/annuaire`) : regroupement des outils par écosystème/éditeur. Badge discret par carte ("OpenAI · 6 produits"), cliquable pour filtrer la grille ; section "Autres outils de l'éditeur" sur la fiche détail. Détection automatique du domaine racine (Public Suffix List, package `jeremykendall/php-domain-parser`), config `config/ecosystems.php` versionnée (17 écosystèmes amorcés), commande `directory:backfill-ecosystem-tags --dry-run` pour peupler les 433 outils existants sans jamais écraser un tag manuel, auto-suggestion à la soumission d'un nouvel outil.
- **Annuaire** : filtres compactés. Rangée de 5 onglets de tri remplacée par un menu déroulant compact ; sur mobile, tri + catégories + filtre écosystème actif regroupés derrière un bouton unique "Filtres/Tri (N)" ouvrant un tiroir accessible (chips actifs, "Tout effacer", clavier, focus, contraste AAA).

### Changed
- **Annuaire** : comptage par écosystème mis en cache (une seule requête agrégée, zéro N+1), invalidé automatiquement à chaque outil créé/modifié/supprimé.

## [1.118.2] - 2026-07-23

### Added
- **Glossaire Techno** : 4 nouveaux termes (Adobe, Cloudflare, Shutterstock, Hub), angle « rôle dans l'écosystème IA ». Images générées via Gemini, sources vérifiées (HTTP 200, aucune URL devinée).

### Fixed
- **Glossaire** : le terme « Prompt » n'avait aucun alias, donc « requête »/« requêtes » n'était jamais auto-lié dans les articles. Ajout des 2 formes comme alias.

### Changed
- Skill `/glossaire` bonifié : recherche et validation multi-sources désormais obligatoires (section 0), URLs de sources toujours vérifiées réellement joignables.

## [1.118.1] - 2026-07-23

### Fixed
- **Prompteur** : import du script JSON échouait silencieusement quand la réponse de l'IA était collée sans habillage (JSON brut, ni marqueurs ni bloc ```json```) — seule la dernière section était extraite au lieu du document complet. Corrigé (recherche du document racine parmi toutes les accolades ouvrantes, pas seulement la dernière). Signalé par un utilisateur, reproduit, corrigé et vérifié visuellement (8/8 sections importées).

## [1.118.0] - 2026-07-23

### Added
- **Prompteur** (`/outils/prompteur`) : collage HTML→Markdown automatique dans « Objectif de la vidéo ». Colle le contenu d'un article de blog copié depuis le navigateur, les titres/gras/listes/liens deviennent automatiquement du Markdown (`#`, `**`, etc.). Turndown.js vendorisé localement (MIT), aucune dépendance CDN.
- **Inscription** : « Nom complet » remplacé par deux champs « Prénom » / « Nom de famille » séparés, pour permettre une personnalisation future plus fine. Architecture additive (aucune donnée existante affectée, `name` reste calculée automatiquement et continue d'alimenter les 47 vues existantes sans modification).

## [1.117.26] - 2026-07-23

### Fixed
- **Bug fonctionnel** : suppression de message de contact exigeait une permission `delete_contacts` jamais seedée — inaccessible à tout le monde sauf superadmin. Aligné sur `manage_contacts`.

### Security
- Round 10 adversarial `/100` (dernier de la session) : scaffold mort oublié (module Booking, désactivé, corrigé en prévention).

### Note
Bilan de la session `/100` (2026-07-22/23, rounds 1-10) : 2 vraies failles de contrôle d'accès corrigées (Acronyms/Dictionary, Shop), ~60 défauts d'affordance UI corrigés, 15 scaffolds nwidart morts neutralisés, 2 bugs de permission fantôme corrigés, 1 incident de production auto-provoqué et résolu en transparence. Convergence réelle mais non certifiée formellement (2 verdicts vides consécutifs non atteints) — session close sur ce constat.

## [1.117.25] - 2026-07-23

### Security
- Round 9 adversarial `/100` : fichiers `routes/api.php` jumeaux d'Export/Translation/Backup (oubliés au round 8) — même scaffold mort nettoyé.

### Fixed
- **Bug fonctionnel** (pas sécurité) : la création d'expérience A/B exigeait une permission `create_feature_flags` jamais seedée — inaccessible à tout le monde sauf superadmin. Aligné sur `manage_feature_flags` (permission réellement seedée pour cette entité).

## [1.117.24] - 2026-07-23

### Security
- Round 8 adversarial `/100` (angle IDOR/API/Artisan — rien trouvé de ce côté) : même motif de scaffold mort que le round 7, cette fois côté `routes/api.php` sur 10 modules (Ads, Authors, Dictionary, News, Roadmap, Community, Directory, FrontTheme, Tools, Voting). Routes `apiResource` mortes supprimées, routes API réelles préservées intactes. 494/494 tests verts.

## [1.117.23] - 2026-07-23

### Security
- Round 7 adversarial `/100` : scaffolds nwidart morts (Export, Translation, Backup — contrôleurs vides, zéro méthode) exposés via `Route::resource` sans permission, même motif qu'Authors (round 6). Sévérité nulle actuellement (erreur fatale, pas une fuite), mais routes supprimées par cohérence préventive avant qu'un futur développeur implémente les méthodes sans y repenser.
- Balayage complémentaire (middleware global `/admin`, composants Livewire orphelins, routes racine) confirmé sain.

### Fixed
- Hotfix incident v1.117.22 : `config/version.php` contenait un `*/` littéral dans un chemin en glob qui a fermé le docblock prématurément, cassant le chargement de config à l'échelle du site (~8 min de panne). Reformulé pour ne plus jamais juxtaposer ces caractères.

## [1.117.22] - 2026-07-23

### Security
- **CORRECTIF DE SÉCURITÉ #2** — Module `Shop` : le back-office boutique complet (produits, commandes clients, réglages, wizard Gelato) n'était protégé que par `['web','auth']` — n'importe quel client connecté avait un accès admin complet. Réutilisation de permissions déjà présentes dans le seeder (`products`, `ecommerce_orders`) mais jamais câblées à aucune route + nouvelle permission `shop` (réglages). Testé (403 confirmé pour un rôle `user`, admin/superadmin OK).
- **Action manuelle requise en prod** (identique à v1.117.21) : `php artisan app:sync-permissions` doit tourner sur le serveur.
- Bonus : suppression de 2 `confirm()` JS natifs (règle projet violée) dans les vues Shop touchées, remplacés par `data-confirm`.
- Nettoyage : scaffold nwidart mort (`Modules/Authors`, jamais implémenté) supprimé.

## [1.117.21] - 2026-07-22

### Security
- **CORRECTIF DE SÉCURITÉ** — Modules `Acronyms` et `Dictionary` (Glossaire Techno) jamais intégrés au système de permissions : n'importe quel utilisateur connecté (pas seulement admin) pouvait créer/modifier/supprimer des acronymes et termes de glossaire en naviguant directement vers `/admin/acronyms` ou `/admin/dictionary`. Ajout des permissions `view_/create_/update_/delete_acronyms` et `_dictionary_terms` + middleware sur les routes + `@can` sur les vues. Testé (403 confirmé pour un rôle `user`, accès superadmin préservé).
- **Action manuelle requise en prod** : `php artisan app:sync-permissions` doit être exécuté sur le serveur pour créer les nouvelles permissions en base (le pipeline CI ne lance que `migrate --force`, pas de seeder). En attendant, le code déployé bloque tout le monde sauf le superadmin sur ces 2 modules (fail-safe).

## [1.117.20] - 2026-07-22

### Fixed
- RBAC (`/100` round 4 + balayage déterministe final) : 24 fichiers sur 8 modules (Tenancy, FormBuilder, Backoffice, Blog, Newsletter, Pages, ABTest) — même correctif `@can(...)` que les rounds précédents.
- **Plus sérieux** : `Modules/Blog/routes/web.php` — les routes `admin.blog.submissions.*` (index/approve/reject) n'avaient aucun middleware `permission:` (accessible à tout admin peu importe son rôle réel). Ajout de `permission:view_articles`/`permission:update_articles`.
- Balayage déterministe (117 permissions du projet énumérées et groupées, pas un échantillon) : 1 dernier trou trouvé hors radar (ABTest experiments), tous les autres groupes de permissions confirmés à permission unique (pas de scission possible). Clôture du fil RBAC-affordance.

### Verified
- Vérification visuelle Playwright en local (code identique à prod) sur le lot v1.117.19 : aucune régression, bug « Nouveau tag » Blog confirmé résolu.
- 3 exécutions complètes de la suite de tests (114-115 échecs à chaque fois, même bassin pré-existant, écart max = 1 test flaky sans lien).

## [1.117.19] - 2026-07-22

### Fixed
- RBAC (`/100` round 3, portée élargie) : 39 fichiers Blade sur 10 modules (AI, Backoffice x21, Menu, Faq, Testimonials, CustomFields, ShortUrl, Team, Widget, Newsletter, Pages, Blog) — boutons Modifier/Supprimer/Créer/Toggle gardés par `@can(...)` pour correspondre aux permissions réelles distinctes de la page. Bonus : bug réel trouvé en production sur Blog (bouton « Nouveau tag » non gardé du tout).
- Suite complète (5299/5283 passants selon run) confirmée sans régression : les 114-115 échecs sont pré-existants (modules désactivés localement, tests legacy racine non couverts par le garde `Pest.php`), vérifié par isolation `git stash`.

## [1.117.18] - 2026-07-22

### Fixed
- RBAC : dernier pendant trouvé par balayage grep exhaustif, `livewire/users-table.blade.php` (fichier legacy, code mort confirmé) gardé par `@can(...)` par cohérence DRY. Clôt le fil RBAC-boutons ouvert par la passe adversariale `/100`.

## [1.117.17] - 2026-07-22

### Fixed
- RBAC : 2 vues supplémentaires (`users/show.blade.php`, `users-table.blade.php`) gardées par `@can(...)`, mêmes pendants que le fix rôles.
- `ImportWordPress.php` : `excerpt` purifié en plus de `content`.
- `SearchService.php` : garde `Module::isEnabled()` uniformisée sur Blog/Pages/Category (cohérence avec le fix SaaS).

## [1.117.16] - 2026-07-22

### Fixed
- **Contenu dupliqué sur les fiches Annuaire** (régression v1.117.14) : `short_description` affiché deux fois, réduit à l'answer-box seule.
- **RBAC incomplet** (complète v1.117.15) : `@can(...)` ajoutés sur `roles/show.blade.php` et `search/index.blade.php` (3 emplacements restants).
- **Incohérence Purifier admin vs publication** : `Article::safeContent()` aligné sur le profil `article`.
- **XSS défense en profondeur** : `ImportWordPress.php` purifie désormais le contenu importé.
- **[500] Recherche admin globale cassée quand SaaS désactivé** : `SearchService::searchAdmin()` plantait sur toute recherche (`class_exists()` insuffisant, table `plans` jamais migrée). Corrigé avec vérification `Module::isEnabled()`.

### Known issue (signalé, nécessite confirmation utilisateur)
- Les commits v1.117.11 à v1.117.15 contiennent une signature `Co-Authored-By: Claude` en violation de la règle projet — corrigible seulement par réécriture d'historique git (force-push), non effectuée sans accord explicite.

## [1.117.15] - 2026-07-22

### Fixed
- **Boutons admin de gestion des rôles visibles sans permission** (trouvé par la simulation E2E `/sim`) : « Ajouter »/« Modifier »/« Supprimer » sur `/admin/roles` s'affichaient pour ADMIN alors qu'il n'a pas ces permissions. Backend déjà sécurisé (403), correction purement UI (`@can(...)` ajoutés).

## [1.117.14] - 2026-07-22

### Added
- **Composant `<x-core::answer-box>` (AEO/GEO)** ajouté aux fiches Annuaire et Glossaire — réponse directe structurée pour les moteurs de réponse IA, réutilisation DRY du composant déjà utilisé sur le blog.

### Changed
- Fiches Glossaire : bloc `one_sentence_answer` maison remplacé par le composant standardisé (ajoute le balisage sémantique manquant).

### Fixed
- **Performance** : image de fond de la page de connexion compressée (2,4 Mo PNG → 63 Ko WebP, -97%).
- **Tests** : garde-fou de skip des modules désactivés (SaaS/Tenancy) étendu aux fichiers de test à la racine — 110 des 224 échecs pré-existants résolus proprement (transformés en skips), 2 vrais bugs sans lien laissés visibles intentionnellement.

### Known issues (signalés, non corrigés)
- Token Google Search Console expiré (reconnexion requise, hors de portée d'un agent).
- CSS de thème hérité (~470 Ko, carrousels/lightbox) potentiellement inutilisé, à vérifier plus largement avant retrait.
- Double pile JS jQuery+Bootstrap (375 Ko) coexistant avec Alpine/Livewire.
- Registre des incidents de confidentialité (Loi 25) absent — nouvelle fonctionnalité à concevoir.
- Modules `Shop`/`Community`/`Voting`/`Ads` sans aucun test (Shop = priorité, gère les paiements).

## [1.117.13] - 2026-07-22

### Security
- **Mise à jour de dépendances vulnérables** trouvées par `composer audit`/`npm audit`. Composer : `guzzlehttp/guzzle` 7.13.1 → `^7.15.1` (4 avis moyens), `web-auth/webauthn-lib` 5.2.4 → `^5.3.5` (1 avis bas). npm : 27 vulnérabilités (2 critiques, 15 hautes) corrigées via `npm audit fix` (devDependencies uniquement : Vite, axios, concurrently, ws...). 0 vulnérabilité restante confirmée, build vérifié fonctionnel.

## [1.117.12] - 2026-07-22

### Security
- **[CRITIQUE] XSS stockée sur la soumission publique d'articles** (`Modules/Blog/app/Http/Controllers/ArticleSubmissionController.php`, route `/proposer-article`) : tout utilisateur connecté pouvait injecter `<script>`/`onerror=`/`javascript:` dans un article, contourner la revue admin (qui voyait une version purifiée alors que la version brute était publiée), et l'exécuter sur tous les visiteurs. Trouvé par un audit sécurité applicative OWASP Top10 Web+LLM. Corrigé à la frontière de soumission via un nouveau profil Purifier `article` (`config/purifier.php`) qui préserve la structure riche légitime (h2-h6, listes, tableaux) tout en bloquant script/gestionnaires d'événements/URI `javascript:`. 3 tests de non-régression ajoutés.

### Fixed
- Bug de robustesse pré-existant (`Undefined array key "excerpt"` si le champ optionnel absent de la requête).

### Known issues (signalés, non corrigés dans ce patch — portée limitée à la faille bloquante)
- SSRF potentiel (`Modules/AI/.../WebScraperService.php`), prompt injection indirecte (`RagService.php`), excessive agency LLM (`CommentModerationObserver.php`), autorisation trop large (`Directory/CommunityController.php`), mot de passe démo sans garde prod (`AcademyDemoSeeder.php`). Détail complet dans `config/version.php` v1.117.12.

## [1.117.11] - 2026-07-21

### Security
- **Retrait d'un script prod donnant un accès lecture/écriture brut à un article**, protégé par un jeton illusoire (valeur = nom du fichier). Non suivi par git, jamais référencé par le code applicatif. Backup du contenu pris avant suppression.

### Fixed
- **Éditeur de recadrage d'image cassé** (`Modules/Media`) : `vite.config.js` copiait 9 plugins NobleUI vers `public/build` mais oubliait `cropperjs`, causant un 404 sur `cropper.min.js`/`cropper.css`. Entrée ajoutée, build relancé, vérifié (200, contenu authentique).
- **3e mécanisme de toast maison** (`Modules/Menu/resources/views/admin/edit.blade.php`) consolidé vers `Livewire.dispatch('toast', ...)`, cohérent avec le reste du site (DRY).

## [1.117.10] - 2026-07-21

### Fixed
- **Apostrophes manquantes sur la calculatrice de taxes** (`Modules/Tools/resources/views/public/tools/calculatrice-taxes.blade.php:255,261`, page publique) : « l autre champ » → « l'autre champ » (2 occurrences). Défaut préexistant (2026-05-07), trouvé par une vérification adversariale du fix 1.117.8, sans lien avec cette session.

### Added
- Tests de non-régression pour verrouiller les 2 fixes d'apostrophes (1.117.8 et 1.117.10) : `Modules/Directory/tests/Feature/CreateFormToastContentTest.php`, `Modules/Tools/tests/Feature/CalculatriceTaxesContentTest.php`.

### Note
- Suite complète relancée en `--parallel` : 224 échecs pré-existants et sans lien confirmés (via `git stash` + re-run identique), voir `config/version.php` pour le détail.

## [1.117.9] - 2026-07-21

### Changed
- **Désactivation de toutes les automations d'envoi newsletter** — demande explicite et urgente de l'utilisateur (« ne pas envoyer de newsletters avant que je le dise, enlève les automations de la newsletter au cas »). `routes/console.php` : `newsletter:digest --preview`/`--send --force`, `newsletter:remind-pending`, `newsletter:purge-unconfirmed` commentés (réversibles). Confirmé via `artisan schedule:list` : plus aucune tâche `newsletter:*` planifiée. Audit complémentaire : aucun cron cPanel externe ni route HTTP ne peut déclencher un envoi, et la table `scheduled_tasks` (planification dynamique en DB) est vide en prod — 3 voies possibles toutes vérifiées.

## [1.117.8] - 2026-07-21

### Fixed
- **Apostrophes manquantes dans le toast d'avertissement de capture d'écran** (`Modules/Directory/resources/views/admin/create.blade.php:91`) : « Entrez d abord l URL du site. » → « Entrez d'abord l'URL du site. ». Trouvé par un 2e round adversarial (agent E2E frais), défaut hérité du texte d'origine copié tel quel lors de la migration `Livewire.dispatch` en 1.117.5 ; confirmé isolé (grep de contrôle sur les 5 autres fichiers touchés).

### Known issue (signalé, non corrigé)
- `public/build/nobleui/plugins/cropperjs/cropper.min.js`/`.css` absents du build (404 en prod et en local) — l'outil de recadrage d'image de `Media/image-editor.blade.php` est cassé, indépendamment du fix toast.
- `Modules/Menu/admin/edit.blade.php` a toujours son 3e mécanisme de toast maison (`showToast()`), non harmonisé — DRY, non bloquant (déjà signalé en 1.117.7).

## [1.117.7] - 2026-07-21

### Removed
- `public/__deploy_oqlf_s83.php` (seeder OQLF ponctuel S83, déjà exécuté et auto-supprimé du serveur) retiré du dépôt — même défaut structurel que les 12 scripts de 1.117.5, avait échappé à l'audit initial. Trouvé par une passe adversariale fraîche.

## [1.117.6] - 2026-07-21

### Changed
- **Adresse postale RGPD mise à jour** (`Modules/Privacy/config/config.php::company.address`, affichée sur `/privacy-policy` — « Responsable du traitement ») : `CP 64021, L'Ancienne-Lorette RPOST-JAC (QC) G2E 2X0, Canada`. Version du document légal 3.3 → 3.4.

### Known issue (signalé, non modifié)
- `terms-of-use.blade.php` et `sales-conditions.blade.php` contiennent encore l'ancienne adresse civique, liée à « MEMORA solutions (incorporation) » + NEQ — potentiellement une adresse d'incorporation distincte, à confirmer avant modification.

## [1.117.5] - 2026-07-21

### Fixed
- **Extension du correctif de toast (v1.117.2) à 5 pages admin supplémentaires** trouvées par un audit exhaustif de tout le repo : `Backoffice/health`, `Blog/articles/edit`, `Directory/admin/create`, `Media/image-editor`, `Menu/admin/edit`, ainsi que `Newsletter/prompt-builder`. Toutes basculées vers `Livewire.dispatch('toast', ...)`.

### Security
- **Retrait d'un script accessible publiquement sans authentification qui déclenchait un envoi réel de courriel de test newsletter** (`_run_defi_w18_test.php` + variante `_v2`). One-shot déjà servi, sans référence active.

### Removed
- 21 scripts PHP de diagnostic déjà neutralisés (stubs 410/404) supprimés de `public/` en production (non suivis par git, résidus indéfinis sinon).
- 12 scripts jetables suivis par git (`seed-oqlf.php`, 7× `clear-s84-*.php`, `_cleanup_residuals_38.php`, `_cleanup_v2_38.php`, `_run_defi_w18_test(_v2).php`) retirés du dépôt — sans ce retrait, ils étaient ressuscités à chaque déploiement.

### Known issue (signalé, décision laissée à l'utilisateur)
- `_content_upload_receiver_b073bc...045.php` (prod, hors dépôt git) : script fonctionnel qui écrit en brut dans `articles.content`, protégé par un token dont la valeur est le nom de fichier lui-même (protection illusoire). Besoin actif incertain — non supprimé, à trancher.

## [1.117.4] - 2026-07-21

### Fixed
- **Dernière occurrence codée en dur des anciens modèles OpenRouter cassés**, dans `AiService::estimateCost()` (table de tarifs). Sans impact pratique (méthode sans appelant, code mort), corrigée par cohérence. Trouvée par une 3e ronde adversariale indépendante.
- Confirmé indépendamment (via `git log --follow`) que l'échec `Phase161Test::toHaveCount(27)` est préexistant (commit du 2026-03-14), sans rapport avec les correctifs de la session.

Bilan de la journée : 3 rondes adversariales complètes (9 sous-agents indépendants), 12 manques réels trouvés et corrigés sur le bouton « Envoyer vers Objectif vidéo », le fix WCAG et le fix de configuration IA — dont un bug majeur (toast de confirmation totalement non fonctionnel sur les deux pages).

## [1.117.3] - 2026-07-21

### Fixed
- **Régression de `tests/Feature/Phase161Test.php`** introduite par le fix de seeder de 1.117.2 (jamais exécuté avant cette livraison — hors `Modules/News`). Mis à jour pour attendre `openrouter/free`.
- **`Modules/AI/app/Services/AiService.php` : les valeurs de repli PHP codées en dur pointaient encore vers les anciens modèles OpenRouter cassés** — le vrai filet de sécurité exécuté si un réglage est vide n'avait jamais été corrigé (seule la seed l'avait été). Alignées sur `openrouter/free`.
- **Le menu déroulant admin « Modèle IA » (`/admin/settings`) ne proposait même pas `openrouter/free`** — un admin choisissant un des anciens modèles listés aurait réintroduit le bug. Ajout de l'option en tête de liste.

### Known issue (hors périmètre, signalé et non corrigé)
- `Modules/Newsletter/resources/views/admin/prompt-builder/index.blade.php` utilise le même mécanisme de toast cassé (CustomEvent DOM sans listener) corrigé en 1.117.2 dans le module News — 8 occurrences, module non touché par cette session.
- `tests/Feature/Phase161Test.php:114` (`toHaveCount(27)`) échoue avec 32 réels — dérive préexistante sans rapport avec les correctifs de cette session (le seeder déclare toujours 27 clés `ai.*`, avant et après).

## [1.117.2] - 2026-07-21

### Fixed
- **Toast de confirmation totalement non fonctionnel sur `/admin/concentre-builder` et `/admin/objectif-video`.** Trouvé par une passe adversariale /100 indépendante. Le code dispatchait un `CustomEvent` DOM `notification-toast`, mais aucun listener n'existe pour cet événement - le layout admin réellement rendu écoute `Livewire.dispatch('toast', {...})`, pas un event DOM. Bug préexistant à cette session (les toasts « copié ! » étaient déjà cassés). Corrigé aux 5 points d'appel des deux fichiers. Vérifié visuellement : le toast s'affiche maintenant réellement.
- **`pushToVideoGoal()` : désynchronisation possible entre `items` et `selectedIds`** si un id sélectionné n'a plus de correspondance dans `newsItems` au moment du clic. `selectedIds` est maintenant dérivé de `items` après filtrage, garantissant leur cohérence.
- **`sessionStorage.setItem()` sans gestion d'erreur** dans `pushToVideoGoal()` - échec totalement silencieux (aucune redirection, aucun message) en cas de quota dépassé ou stockage désactivé. Ajout d'un try/catch avec toast d'erreur.
- **Import sessionStorage sur Objectif Vidéo : `removeItem()` jamais exécuté si le JSON est corrompu** (placé après `JSON.parse()`), laissant la clé bloquée indéfiniment. Déplacé avant le parse.
- **`SettingsDatabaseSeeder.php` gardait les anciens modèles OpenRouter cassés** comme valeurs par défaut pour 6 réglages `ai.*_model` — alignés sur `openrouter/free`. En corrigeant ce point, découverte que `ai.moderation_model`/`ai.seo_model`/`ai.translation_model` étaient **aussi cassés en production** (même cause que le correctif de 1.117.1, jamais vérifiés à l'époque) — corrigés en direct.
- Cron cPanel de diagnostic ponctuel résiduel (403 superadmin, déjà servi) retiré du crontab.

### Added
- Test Pest `ConcentreBuilderIndexTest.php` — aucun test n'exerçait le rendu HTTP de `/admin/concentre-builder` avant (113/113 verts ne couvrait pas cette page).

## [1.117.1] - 2026-07-21

### Fixed
- **Contraste WCAG AAA insuffisant sur le bouton « Tout cocher » désactivé** (et tout bouton `.cb-btn`/`.cb-btn-secondary` désactivé du sélecteur d'actualités partagé Concentré/Objectif vidéo) - signalé par l'utilisateur via capture d'écran. `#94a3b8` donnait 2.18:1 à 2.56:1 selon le bouton (échec AA 4.5:1 ET AAA 7:1) ; `#6b7280` (Objectif vidéo, couleur différente de l'autre page) donnait 4.83:1 (passait AA, échouait AAA). Corrigé vers `#475569` + texte blanc (7.58:1, AAA) dans les deux pages, avec une règle `.cb-btn-secondary:disabled` explicite ajoutée (absente avant, ce qui laissait la couleur de texte dériver selon la cascade CSS).

## [1.117.0] - 2026-07-21

### Added
- **Bouton « Envoyer vers Objectif vidéo » sur `/admin/concentre-builder`.** Pousse la sélection d'actualités en cours vers `/admin/objectif-video` en un clic (sans re-choisir de plage de dates ni re-sélectionner les mêmes actualités). Mécanisme 100% client-side (`sessionStorage`, clé `lv_vgb_import` consommée une seule fois), aucune route/contrôleur ajouté - cohérent avec la philosophie "aucune intégration serveur" déjà établie entre ces deux outils. Objectif Vidéo affiche un toast de confirmation et pré-remplit sa sélection (couleurs/clusters préservés) à la place de son chargement par date par défaut.

### Fixed
- **`x-init="init()"` s'exécutait deux fois sur `/admin/objectif-video`** (morph Alpine/Livewire du layout backoffice au chargement), écrasant silencieusement tout état pré-rempli par un appel synchrone ultérieur à `fetchNews()`. Découvert en développant la fonctionnalité ci-dessus. Corrigé par un flag d'idempotence en tête de `init()`.

## [1.116.10] - 2026-07-20

### Changed
- **Extraction DRY du "sélecteur d'actualités" (recherche/filtre langue/filtre couleur/3 tris/regroupement par acteur/pastille couleur) du Concentré IA vers un composant partagé, réutilisé par le Générateur d'objectif vidéo.** `/admin/objectif-video` avait une liste basique (checkboxes, pas de recherche/filtre/tri) alors que `/admin/concentre-builder` avait un système riche — au lieu de dupliquer ce système une 2e fois, extraction en 3 fichiers partagés : `public/assets/admin/news-article-picker.js` (mixin Alpine `window.NewsArticlePicker(opts)`, stratégie de fetch paramétrable — GET query-string pour le Concentré, POST JSON body pour Objectif Vidéo), `Modules/News/resources/views/admin/partials/news-article-picker.blade.php` (colonne "actualités disponibles", `@include` dans le scope `x-data` du parent), `public/assets/admin/news-article-picker.css`. Objectif Vidéo passe de sa liste plate à checkbox à la même disposition 2 colonnes que le Concentré (disponibles à gauche / sélection simplifiée à droite, sans glisser-déposer — l'ordre n'a pas d'importance pour la synthèse IA). Piège de réactivité Alpine résolu en cours de route (voir `config/version.php` pour le détail complet) : fusionner le mixin via `Object.defineProperties`/`Object.getOwnPropertyDescriptors` doit se faire AVANT le `return` de la factory `x-data`, pas à l'intérieur de `init()` (l'Alpine embarqué par Livewire dans ce projet ne rend pas visibles au template les propriétés ajoutées après coup sur un objet déjà réactif). Zéro régression du Concentré (recherche, filtres, tris, cluster, couleurs, sélection/glisser-déposer, génération de prompt, historique, brouillon localStorage — vérifié visuellement en local desktop + mobile 390px) ; Objectif Vidéo pleinement fonctionnel avec le nouveau système. Tests `Modules/News/tests/Feature/VideoGoalBuilderTest.php` + `ConcentrePromptBuilderTest.php` + régression complète `Modules/News` : 113/113 verts.

## [1.116.9] - 2026-07-20

### Fixed
- **403 "Accès non autorisé" en PRODUCTION sur `/admin/objectif-video` pour le vrai compte superadmin.** Cause racine : `Modules/Authors/app/Http/Middleware/EnsureSuperAdmin` vérifiait `hasRole('super-admin')` (trait d'union, jamais assigné à personne) ou `hasRole('admin')` (rôle différent), alors que le seed réel (`database/seeders/DatabaseSeeder.php`) assigne `super_admin` (underscore) - la convention utilisée partout ailleurs sur le site (`User::isSuperAdmin()`, `User::homeRoute()`, ~150 fichiers). Le repli local `id===1` masquait le bug en développement (il ne s'applique qu'en environnement `local`/`testing`, jamais en production) - confirmé en tinker local que le compte `stephane@memora.ca` n'a QUE le rôle `super_admin`. Corrigé en supprimant la logique dupliquée du middleware au profit de `User::isSuperAdmin()` (source unique de vérité) - DRY, évite toute divergence future. Ce même middleware protège aussi `/backoffice/authors`, potentiellement affecté par le même bug avant ce correctif. Test `VideoGoalBuilderTest::vgbSuperAdmin()` corrigé pour refléter la vraie combinaison email+rôle. Régression : 239/239 tests verts (`Modules/News`, `Modules/Authors`, `Modules/Backoffice`).

## [1.116.8] - 2026-07-20

### Fixed
- **Lien de menu admin manquant pour le « Générateur d'objectif vidéo » (`/admin/objectif-video`, ajouté en v1.116.7).** La page fonctionnait déjà et était protégée nativement par `EnsureSuperAdmin`, mais n'apparaissait dans aucun menu de navigation admin - un oubli lors de son ajout initial. Ajout de l'entrée « Objectif vidéo (Prompteur) » (libellé volontairement explicite pour ne jamais être confondu avec l'entrée existante « Concentré IA - builder » - outil distinct qui génère, lui, le prompt du billet de blog hebdo ; `title` HTML en survol : « Génère le texte d'objectif à coller dans le Prompteur ») dans `Modules/Backoffice/resources/views/themes/backend/partials/sidebar.blade.php`, section « Contenu », juste après « Concentré IA - builder » (repère analogue le plus proche, même style de lien sans icône dédiée). L'entrée est gatée `@if(Route::has('admin.news.video-goal.index') && auth()->user()?->isSuperAdmin())` - même restriction que la route elle-même (middleware `EnsureSuperAdmin`), donc un simple admin ne voit jamais ce lien et ne peut pas se heurter au 403 en cliquant dessus. Vérifié visuellement (Playwright, superadmin `stephane@memora.ca`) : lien visible au bon endroit dans le menu, clic mène à la bonne page. Tests `Modules/News` (régression) et `Modules/Backoffice` verts.

## [1.116.7] - 2026-07-20

### Added
- **Nouvel outil back-office « Générateur d'objectif vidéo » (`/admin/objectif-video`, superadmin uniquement).** Protégé nativement par `EnsureSuperAdmin` (pas de gate "en construction" nécessaire, l'accès est déjà réservé au rôle). Sélectionne les actualités publiées sur une plage de dates choisie, puis génère (appel IA via le nouveau `NewsVideoGoalAiService`) un texte d'« objectif de la vidéo » prêt à copier-coller dans le champ correspondant du Prompteur public (`/outils/prompteur`) — aucune intégration serveur entre les deux outils, le copier-coller reste manuel et volontaire pour préserver l'approche 100 % BYOA du Prompteur. Nouveau `VideoGoalBuilderController` (`index` : page d'accueil de l'outil ; `newsForRange` : endpoint JSON qui retourne les actualités publiées de la plage sélectionnée ; `generateGoal` : appel au service IA et retour du texte généré) + vue `admin/video-goal-builder.blade.php` (sélection multi-actualités, bouton copier, lien direct vers le Prompteur). 3 nouvelles routes sous `admin/objectif-video` (`index`/`actualites`/`generer`), même chaîne de middleware que le Concentré (`web`, `auth`, `two.factor`, `EnsureSuperAdmin`, `SetBackofficeTheme`). Tests `Modules/News/tests/Feature/VideoGoalBuilderTest.php` : 8 passed, 0 failed (22 assertions — accès superadmin, blocage non-superadmin, redirection invité vers login, validation des IDs d'articles et de la plage de dates, génération réelle). Régression complète du module News : 113/113 tests verts.

## [1.116.6] - 2026-07-20

### Added
- **Nouvel outil public gratuit « Prompteur » (`/outils/prompteur`).** Téléprompteur avec éditeur de script structuré en sections (indication visuelle/action + texte à dire, ou grandes lignes au choix), défilement synchronisé au débit de l'utilisateur, et générateur de méta-prompt à copier-coller dans l'IA de son choix (méthode « apportez votre IA », zéro clé API stockée côté serveur) pour générer le contenu automatiquement. Sans compte, 100 % dans le navigateur. Comprend : éditeur de sections avec import robuste (fichier `.json` de projet), mode téléprompteur plein écran (vitesse, taille de texte, contraste renforcé, mode miroir), panneau de personnalisation (thème clair/sombre/système, vue compacte, réduction des animations). Migration additive et réversible `2026_07_20_120000_seed_prompteur_tool_entry.php` (`updateOrInsert`, pattern calqué sur Minuteur visuel). **Gate `is_under_construction = true`** : seul un superadmin voit l'outil réel (bypass déjà géré par `PublicToolController::show()`), tout autre visiteur reçoit le placeholder « En construction » — la mise en ligne publique reste une décision explicite distincte de l'utilisateur. Tests `PrompteurToolTest` : 5 passed, 0 failed ; régression `Modules/Tools` : 33/33 verts. Testé manuellement avec de vraies IA (Claude.ai, Perplexity, Gemini) via le méta-prompt généré.

### Fixed
- **Audit d'accessibilité WCAG 2.2 AAA du nouvel outil Prompteur (8 constats corrigés) avant tout accès superadmin en conditions réelles.** Couvre notamment : motif `role="tablist"`/`"tab"`/`"tabpanel"` avec navigation clavier complète pour les 3 étapes (BYOA, éditeur, téléprompteur), libellés accessibles (`aria-label`) sur l'ensemble des boutons icône seule (déplacer/dupliquer/supprimer une section, plein écran, réglages), zones `aria-live="polite"`/`"assertive"` pour les statuts dynamiques (import, décompte, progression de lecture), tailles de cible tactile conformes (2.5.5 AAA), et contrastes de texte ≥ 7:1 (1.4.6 AAA) sur les états clair et sombre. Vérifié avant les fixes de QA visuelle ultérieurs (v1.116.1 à v1.116.5, ci-dessous), qui ont corrigé des régressions distinctes (défilement, cases à cocher, légende clavier, thème sombre, alignement) trouvées en usage réel après cet audit.

## [1.116.5] - 2026-07-20

### Fixed
- **Prompteur : désalignement vertical champ/boutons dans l'en-tête de carte de section (éditeur de sections, onglet 2).** Mesuré via Playwright (`getBoundingClientRect`) : en Vue compacte (réglage utilisateur du panneau Réglages), le champ "Titre de la section" se terminait à 9px au-dessus des boutons d'action (↑ ↓ ⧉ 🗑️, alignés en `flex-end`), le faisant paraître plus court que la colonne de boutons. Cause : `.pr-compact .pr-field { margin-bottom: .6rem }` et `.pr-section-card__header .pr-field { margin-bottom: 0 }` ont la même spécificité CSS (0,2,0) - l'ordre de source (la règle `.pr-compact` étant déclarée après) faisait gagner la marge de .6rem, cassant l'alignement `flex-end` voulu pour cette rangée. Corrigé en renforçant la spécificité de la règle de remise à zéro (`#prompteur-app-root .pr-section-card__header .pr-field`) pour qu'elle gagne inconditionnellement, quel que soit l'ordre de déclaration de futures classes utilitaires. Non reproductible en mode par défaut (non-compact) - uniquement quand "Vue compacte" est activée. Audit des autres rangées champ-gauche/boutons-droite de l'outil (formulaire BYOA, barre d'actions projet, import, panneau réglages) : aucune autre ne partage ce motif. Tests `PrompteurToolTest` : 5 passed, 0 failed. Vérifié visuellement avant/après (Playwright), thèmes clair/sombre et mobile 390px non régressés.

## [1.116.4] - 2026-07-20

### Fixed
- **Prompteur : le thème "Sombre"/"Système" du panneau de réglages n'avait aucun effet visuel.** Le JS posait déjà `data-theme="sombre|clair|systeme"` sur `#prompteur-app-root` mais aucune règle CSS ne consommait cet attribut - repéré en QA visuelle Playwright. Ajout d'un jeu complet de règles scopées `#prompteur-app-root[data-theme="sombre"]` (+ variante "systeme" via `@media (prefers-color-scheme: dark)`) couvrant les 3 onglets (BYOA, éditeur de sections 2 colonnes Action/Texte, téléprompteur) : cartes, champs, boutons `.ct-btn-outline`/`.ct-btn-ghost` (invisibles sur fond sombre sans override, bug distinct trouvé en cours de route), colonnes Action (bleu) / Voix (orange), badges, `<kbd>`, panneau de réglages, combo avec "Contraste renforcé". Palette alignée sur `public/css/dark.css` (thème sombre global du site, déjà vérifié AAA) pour rester cohérente avec la charte. Contrastes clés recalculés (luminance relative sRGB), tous ≥ 7:1 AAA : texte `#E6E8EC`/fond `#0F1419` = 15,09:1, muted `#A7AEBA`/`#1A1E25` = 7,57:1, accent `#5EEAD4`/page = 12,51:1, colonne visuelle `#93C5FD`/`#16233A` = 8,71:1, colonne script `#FDBA74`/`#2E2013` = 9,35:1. La zone de lecture du téléprompteur (`.pr-reading-area`) n'est pas touchée (intentionnellement toujours sombre, indépendante du thème global). Vérifié visuellement (Playwright local) : Clair (non régressé), Sombre forcé et Système (émulation `prefers-color-scheme` dark et light) sur les 3 onglets. Tests `PrompteurToolTest` : 5 passed, 0 failed.

## [1.116.1 à 1.116.3] - 2026-07-20

### Fixed
- **Prompteur : défilement automatique du téléprompteur inopérant.** Le bouton Lecture écrivait `.scrollTop` sur `#prompteur-reading-area` (conteneur non scrollable, sert juste au clipping des fondus) au lieu de `#prompteur-reading-content` (le vrai conteneur `overflow-y: auto`) - la barre de progression avançait mais le texte à l'écran ne bougeait jamais.
- **Prompteur : cases à cocher du panneau Réglages invisibles.** `display:none` hérité du thème global (motif `input+label:before` incompatible avec l'ordre DOM label-avant-input de ce panneau) - réaffichées en case native stylée (`accent-color`), scopé à `.pr-settings-row`.
- **Prompteur : légende des raccourcis clavier illisible.** `bootstrap.min.css` fixe `kbd { color:#fff }` site-wide ; `.pr-shortcuts-legend kbd` n'écrasait que le fond (clair), pas la couleur héritée - texte blanc sur fond quasi blanc. Repéré en simulation Playwright mobile 390px.

## [1.116.0] - 2026-07-19

### Added
- **Politique de rétention complète des sondages Décido.** Recherche pp_search (limitation de finalité RGPD/Loi 25, pattern d'avertissement) + validation croisée Codex et Gemini (désaccord réel tranché en faveur du système le plus simple, Gemini ayant recalibré une première proposition de 91 à 60/100 car surdimensionnée pour un outil gratuit). Tout sondage a désormais une date d'expiration dès sa création - sondage de type date : dernière date candidate + 2 mois ; classique ou brouillon : création + 3 mois ; sondage clôturé : clôture + 30 jours (au lieu de 6 mois auparavant). Corrige la vraie faille identifiée : un sondage jamais clôturé n'était jamais purgé automatiquement, contournant silencieusement `decido:purge-expired`. Un seul courriel d'avertissement à J-14 avant suppression (pas de cascade intrusive), avec un bouton "Prolonger de 3 mois" plafonné à 2 utilisations - le verrou est appliqué côté serveur (vérifié résistant à un contournement direct de la route, pas seulement dans l'interface). Mention discrète affichée à la création du sondage + ajout de la durée de rétention à la politique de confidentialité du site.

### Fixed
- **Le layout partagé `auth::layouts.user-frontend` ne rendait jamais les erreurs de validation Laravel (`withErrors()`).** Découvert en vérifiant visuellement le plafond de prolongations Décido : une action refusée par le serveur ne montrait aucun message à l'utilisateur. Ce silence touchait en réalité 4 actions existantes de `PollManageController` (extend, export, shortlink, slug), pas seulement la nouvelle fonctionnalité. Corrigé à la source (layout, un seul endroit) plutôt qu'au cas par cas dans chaque contrôleur.

## [1.115.1] - 2026-07-19

### Fixed
- **Menu d'actions unifié absent sur la page de gestion Décido par jeton propriétaire et 6 autres pages "fiche".** Motif identifié : plusieurs modules avaient migré leur vue liste (`index`) vers le composant `action-menu` mais pas leur vue fiche individuelle (`show`/`edit`), notamment `Modules/Decido/resources/views/manage/partials/results-content.blade.php` signalée directement par l'utilisateur. Le bloc "Partage et export" regroupe maintenant copier le lien public/court, options avancées, télécharger CSV/ICS dans le menu (le bouton mailto, le QR code et le formulaire de lien court restent volontairement hors menu, justifié en commentaire). Migrées aussi : `Modules/AI` tickets/agent (show), `Modules/Newsletter` workflows (show), `Modules/ShortUrl` admin (show), `Modules/Backoffice` rights-requests + contact-messages (show). Corrige au passage 2 violations `confirm()` JS natif (interdites sur ce projet) sur `Modules/ABTest` experiments et `Modules/Backoffice` rights-requests, ainsi qu'une réimplémentation non-DRY de la copie presse-papiers sur `Modules/ShortUrl` admin (remplacée par `window.copyToClipboard`). Régression ciblée : 135 passed, 0 failed.

## [1.115.0] - 2026-07-19

### Added
- **Décido : bouton "Envoyer par courriel" sur le panneau Partage et export.** Ouvre le client courriel de l'organisateur (Gmail/Outlook/Mail.app) avec le titre du sondage et le lien public pré-remplis (`mailto:`) - c'est lui qui envoie depuis sa propre adresse, comme le vrai Framadate. Zéro infrastructure d'envoi, zéro donnée collectée côté plateforme. Nouveau composant réutilisable `x-core::mailto-share-btn`.

## [1.114.1] - 2026-07-19

### Fixed
- **21 autres call sites vulnérables au même bug de repli de locale que le P0 v1.114.0.** Audit proactif (`grep` exhaustif site-wide) après le fix du 500 sur `/admin/directory` : le même pattern (`route('directory.show', $tool->slug)` sans repli quand la traduction `fr_CA` du slug est absente) existait encore à 21 endroits — sitemap, JSON-LD, newsletter hebdomadaire (impact le plus large : envoyée à tous les abonnés), page d'accueil, RSS, recherche globale du site, bannière de fin de vie d'outil (bug distinct et plus grave : passait l'objet modèle entier au lieu du slug), contributions utilisateur, vote communautaire, redirections canoniques, comparateur, collections, tarifs éducation, favoris. Tous remplacés par `Tool::getPublicUrl()` (DRY, réutilise le repli déjà corrigé). Régression ciblée : 401 passed, 0 failed.

## [1.114.0] - 2026-07-19

### Added
- **Menus d'actions kebab (⋮) site-wide.** Le composant `admin-action-menu` (déjà déployé sur 41 pages admin) est renommé `action-menu` et généralisé aux pages utilisateur : remplace les rangées de boutons d'actions inline (ex. "Mes liens courts" : Copier/QR/Stats/Modifier/Prolonger/Supprimer) par un menu compact unique. Positionnement anti-débordement automatique (flip vers le haut + ajustement horizontal si pas de place, `position: fixed` insensible aux ancêtres `overflow:hidden`), fermeture au clavier (Escape) et au défilement de page. Validé Codex (94/100) et Gemini (85/100). 12 pages migrées : ShortUrl "Mes liens courts", Journal "Mes journaux", Tools "Mes grilles de mots croisés", Auth "Mes sauvegardes", Directory "Mes collections" (front-end) ; Directory, Dictionary, FormBuilder, News, AI, Blog, Directory pricing-audit (admin).
- **Section "Clôturer le sondage" (Décido) clarifiée.** Texte explicatif sur l'effet de l'action, décompte de votes "✓ X oui" affiché à côté de chaque créneau, créneau gagnant pré-sélectionné, bouton renommé "Confirmer et clôturer le sondage" (ne duplique plus le titre de section).

### Fixed
- **Déclencheur du menu d'actions peu visible.** Caractère unicode ⋮ sur fond transparent (contraste insuffisant hors contexte "Mon espace") remplacé par une icône lucide sur fond rempli, contraste AAA (~10.7:1), cible tactile 44×44px (WCAG 2.2 AAA 2.5.5).
- **Icônes lucide invisibles sur les pages "Mon espace" nouvellement migrées.** Le layout front-end ne charge pas lucide.js par défaut (contrairement aux layouts admin) ; le composant `action-menu` charge désormais lucide.js lui-même de façon garantie et dédupliquée.
- **500 sur `/admin/directory` pour un outil sans traduction `slug` en `fr_CA`.** `Tool::getPublicUrl()` plantait (`UrlGenerationException`) pour tout outil dont le champ Translatable `slug` n'était renseigné que pour `fr` (locale de saisie réelle) alors que `app.locale = fr_CA`. Repli manuel ajouté (locale courante → `fr` → première traduction disponible). Bug préexistant (même code que l'ancien template), pas causé par la migration des menus d'actions.
- **Badges de vote (✓/?/✕) mal alignés verticalement.** Symbole et texte du badge ("✓ 2 oui") ne partageaient pas la même ligne de base selon la police. Corrigé avec `display: inline-flex; align-items: center`.
- **Menu latéral "Mon espace" absent sur la page de gestion Décido via jeton propriétaire.** Le créateur connecté cliquant "Gérer" depuis "Mes sondages" atterrissait sur un gabarit sans sidebar (partagé avec le lien de délégation anonyme). Le layout bascule désormais vers "Mon espace" uniquement pour le créateur connecté ; le délégué anonyme via jeton conserve le gabarit public inchangé (protections GA4/JSON-LD round 10/12/26 préservées).

## [1.113.0] - 2026-07-18

### Added
- **"Mon espace" - menu latéral regroupé en accordéon.** Les 17 liens (Tableau de bord, Académie, Mes journaux, Mes liens courts, etc.) sont maintenant organisés en 5 catégories (Vue d'ensemble, Académie, Mon contenu, Mes outils, Mon compte), repliées par défaut sauf la catégorie active, dépliables au clic - sur desktop comme sur mobile. Décido "Mes sondages" ajouté au menu (en était absent).
- **Fil d'Ariane contextuel sur `/user/liens/{id}/edit`** ("Mon espace > Mes liens courts > Modifier") au lieu du breadcrumb générique hérité.

### Fixed
- **Bug d'état actif du menu "Mon espace".** Le lien courant ne s'allumait jamais sur les pages create/edit (ex. modification d'un lien court) car la comparaison utilisait le nom exact de la route au lieu d'un préfixe. Corrigé avec des patterns explicites par lien (vérifiés contre chaque module pour éviter toute collision, ex. `collections.my` distinct des pages publiques `collections.*` de l'annuaire) et `aria-current="page"` correctement posé (l'échappement Blade produisait auparavant des guillemets littéraux dans l'attribut).
- **Sidebar absente sur "Mes journaux", "Mes commandes" et "Mes sondages Décido".** Ces 3 pages héritaient directement du layout du thème au lieu du layout "Mon espace", cassant la navigation au clic depuis le menu.
- **Menu mobile qui ne se repliait jamais.** Le bouton "Menu de mon espace" affichait le menu complet en permanence sur mobile (poussant tout le contenu utile sous la ligne de flottaison) à cause d'une règle CSS `!important` qui écrasait le contrôle d'affichage géré par Alpine.js.

### Added
- **Décido - mise en public.** Feu vert utilisateur explicite après 27 rounds de revue adversariale + simulation E2E complète (#1134-1139). `DECIDO_UNDER_CONSTRUCTION=false` + migration `2026_07_18_180000_decido_publish.php` (retire le badge "Bientôt" sur `/outils`, pattern identique à Minuteur visuel).
- **Confirmation de copie presse-papiers site-wide (toast + état bouton).** Nouveau helper global `window.copyToClipboard()` (`master.blade.php`) : bascule visuelle du bouton ("Copié !", `aria-hidden`, `aria-label` stable) + toast `window.toast()`/`toast-show` comme seule source d'annonce `aria-live` (évite la double-annonce lecteur d'écran). Options validées Codex (95/100) et Gemini (2e avis indépendant, aucun désaccord). Appliqué aux 3 boutons de `Decido/results.blade.php` (lien admin, lien public, lien court), à `admin-copy-menu.blade.php`, et à 20 fichiers supplémentaires (outils publics, Backoffice, ShortUrl, Newsletter/News). Corrige au passage 2 dispatches d'événement toast morts (`toast-show` dans un layout qui n'écoute que `notification-toast`).
- **Décido - lien court personnalisable (slug perso pour connectés).** `Poll::claimShortUrl()` accepte un `$customSlug` optionnel, réutilise la validation ShortUrl existante (`alpha_dash`, `unique`, mots réservés). Nouveau lien "Options avancées" (nouvel onglet, ne casse pas le flux Décido) réservé au créateur connecté. Gère la race condition sur slug concurrent (`QueryException`).

### Changed
- **Décido - message "lien de gestion" reformulé.** L'ancien texte "il ne sera plus jamais réaffiché" était trompeur pour le créateur connecté (`authorizeManage()` le laisse toujours repasser via "Mes sondages") ; clarifié que ce lien sert à déléguer l'accès à un co-organisateur non connecté.

## [1.111.0] - 2026-07-18

### Added
- **Décido - image de couverture sur /outils (`featured_image`).** Générée via Gemini (compte `stephane@memora.ca`, skill `/nanobanana`) : illustration 3D isométrique dans la palette teal/orange de la charte (urne de vote, calendrier, horloge, silhouettes), sans texte, cohérente avec le style des autres cartons d'outils. Livrée en paire `decido.jpg` (1200×630, ~48 Ko, référence og:image car les réseaux sociaux refusent WebP/AVIF) + `decido.webp` (~23 Ko, affichage site). Migration `2026_07_16_120000_seed_decido_tool_entry.php` mise à jour (guard `Schema::hasColumn`).

## [1.110.0] - 2026-07-18

### Added
- **Décido - fuseaux horaires IANA complets (créateur).** Le sélecteur de fuseau horaire du formulaire de création (limité à 3 valeurs) est remplacé par un combobox de recherche Alpine.js (~420 fuseaux IANA, recherche par ville/région, détection automatique du fuseau navigateur pré-sélectionnée, accessibilité ARIA combobox/listbox complète, préservation `old()` intacte). Nouveau service `TimezoneListService`. Aucun changement de validation backend requis.
- **Décido - adaptation au fuseau local du votant (page de vote).** La page de vote détecte le fuseau du navigateur du votant et affiche l'heure locale du votant en primaire (avec l'heure du fuseau du sondage en secondaire) si les fuseaux diffèrent, avec bascule et repli manuel si la détection échoue. Conversion 100% côté client, aucun changement à la logique de vote. Veille pp_search (NN/g, Calendly, Doodle, W3C/MDN) + validation croisée Codex (91/100).

### Fixed
- **Décido - heure locale du votant incorrecte de plusieurs heures.** Bug trouvé par la vérification visuelle Playwright (non détecté par les tests Pest, qui ne vérifiaient que la présence des attributs, pas leur valeur) : `data-starts-at-utc` calculait directement `toIso8601String()` sur la valeur castée par Eloquent, laquelle réinterprète à tort une valeur UTC comme si elle était déjà en `America/Toronto` - même cause racine que les fix `PollExportService::exportIcs()` (v1.107.1) et `results.blade.php` (v1.107.0), réintroduite ici. Corrigé en reparsant explicitement la valeur comme UTC avant conversion. 92/92 tests Pest Décido verts (396 assertions).

## [1.109.11] - 2026-07-18

### Fixed
- **Décido - fuseau horaire "America/Montreal" invalide.** Cet identifiant a été retiré de la base IANA tzdata en 2014 (fusionné dans `America/Toronto`, mêmes règles HNE/HAE) et n'est donc plus reconnu par `timezone_identifiers_list()` sur PHP moderne. La règle de validation Laravel `timezone` rejetait systématiquement toute soumission où "Montréal (HNE/HAE)" était sélectionné dans le formulaire de création (choix le plus naturel sur un site québécois) - rendant la création de sondage strictement impossible avec ce choix. Corrigé par normalisation `America/Montreal` -> `America/Toronto` dans `PollManageController::store()` avant validation, sans toucher au template (préserve la préservation `old()` du round 27). Bug découvert par la simulation E2E complète `/sim` (tâches #1134/#1139), non détecté par 27 rounds de revue adversariale par lecture de code. 86/86 tests Pest Décido verts (378 assertions).

## [1.109.10] - 2026-07-17

### Changed
- **Décido - icône corbeille rouge sans contour** pour retirer une plage horaire personnalisée (`create-date.blade.php`). Le bouton "×" bordé rouge jugé peu esthétique par l'utilisateur est remplacé par une icône SVG corbeille inline, style `.ct-btn-ghost` (transparent, aucune bordure) coloré en `var(--c-danger)`. Vérifié visuellement (Herd, Playwright).

## [1.109.9] - 2026-07-17

### Fixed
- **Décido - round 27 (revue adversariale fraîche) - 3 correctifs de présentation.** Sévérité HAUTE : `create-date.blade.php` et `create-classic.blade.php` initialisaient leur `x-data` Alpine sans jamais relire `old()` pour les champs-tableaux dynamiques (`candidateDates`/`candidateDateRanges`/`options`), contrairement à tous les autres champs du même formulaire - un échec de validation (ex. options en double, chevauchement de plages horaires) effaçait toute la saisie de l'utilisateur au réaffichage au lieu de lui permettre de corriger l'élément fautif en place. Corrigé en injectant les valeurs `old()` normalisées via `json_encode()` interpolé en `{{ }}` (échappement Blade, jamais `{!! !!}`, pour rester sécuritaire dans l'attribut HTML `x-data="..."`). Sévérité MOYENNE : la section « Meilleurs créneaux » de `results.blade.php` pouvait s'afficher vide sans aucun message quand des votants avaient répondu mais qu'aucun créneau n'avait de réponse « Oui » (scénario réaliste d'un groupe indécis) - un message explicite a été ajouté. Sévérité MOYENNE : `vote.blade.php` n'affichait nulle part l'erreur de validation sur la clé racine `votes` (règles `required`/`min:1`) - un votant qui soumettait sans rien cocher voyait la page se recharger sans le moindre feedback (violation WCAG 3.3.1) ; un bloc `@error('votes')` a été ajouté. 86/86 tests Pest verts (4 nouveaux tests ciblés). Vérifié visuellement (Herd local, Playwright) pour les 3 correctifs.

## [1.109.8] - 2026-07-17

### Fixed
- **Sudoku - 3 correctifs UX/bugs.** (1) Le bouton « Indice » ne fonctionnait pas à la 2e demande consécutive : course réseau confirmée par reproduction directe (`useHint()` est asynchrone et scannait la grille de façon synchrone avant d'attendre la réponse serveur - sans verrou, un 2e appel lancé avant la fin du 1er retrouvait la même case vide, doublant le compteur d'indices et la pénalité de temps sans révéler de nouvelle case). Un verrou de réentrance `hintPending` (avec `try/finally`) empêche désormais tout appel concurrent. (2) Aucun état de fin de grille clair n'existait, ni en cas de succès ni en cas d'erreur : deux bandeaux accessibles ajoutés (`role="status"`/`role="alert"`, texte ET icône - pas seulement une couleur - conforme WCAG), le texte du bandeau d'erreur a été recalculé pour respecter le contraste AAA 7:1. (3) Le bouton « Vérifier la grille » était visuellement identique au bouton secondaire « Indice », sans hiérarchie claire : migré vers les classes `.ct-btn-primary.ct-btn-lg` du design system avec une ombre dédiée, le bouton « Indice » passe en style secondaire (`.ct-btn-outline`). 5/5 tests Pest verts. Vérifié visuellement (Herd local, Playwright, exécution directe des méthodes du composant Alpine) : la course réseau a été reproduite puis confirmée corrigée, les deux bandeaux de fin de grille s'affichent correctement, la nouvelle hiérarchie des boutons est visible à l'écran.

## [1.109.7] - 2026-07-17

### Fixed
- **Décido — icône engrenage cassée/minuscule sur « Personnaliser l'horaire pour cette date ».** Signalé par l'utilisateur : « on dirait qu'elle est cassée ». Le caractère unicode brut `⚙` n'avait aucune dimension explicite et n'existe pas dans les polices de charte (DM Sans/Plus Jakarta Sans) - le navigateur repliait sur une police système, produisant un glyphe minuscule et incohérent avec le texte du bouton (même famille de défaut que l'audit #592 sur les icônes/SVG sans dimension explicite). Remplacé par une icône SVG inline 14×14px, `stroke="currentColor"` (hérite la couleur du bouton, y compris au survol), `aria-hidden="true"`. 82/82 tests Pest verts. Vérifié visuellement (Herd local, Playwright) : icône nette, taille cohérente, correctement alignée avec le texte.

## [1.109.6] - 2026-07-17

### Added
- **Décido — refonte du champ « intervalle entre les créneaux » en 2 choix nommés par intention + popup d'aide complète.** Question de l'utilisateur : « j'ai l'impression que l'option est importante, mais comment rendre ça simple ? ». Veille pp_search juillet 2026 (Doodle recommande un pas égal à la moitié de la durée pour doubler la flexibilité sans complexité ; Nielsen Norman Group sur la progressive disclosure ; GOV.UK Design System sur les valeurs suggérées dynamiquement plutôt que préselectionnées) + validation croisée indépendante par Codex/GPT-5 (86-95/100) et Gemini 2.5 Pro (via OpenRouter, `agy`/SuperAgent Gemini étant à quota épuisé et les 3 comptes 1min.ai également épuisés sur ce modèle - cascade niveau 4). Le select brut « toutes les 15/30/60 minutes » est remplacé par 2 boutons nommés par intention - « Flexible (recommandé) » et « Sans chevauchement » - dont la valeur réelle en minutes est calculée dynamiquement depuis la durée de la rencontre choisie (moitié de la durée, arrondie ; ou durée exacte) et se recalcule tant que l'utilisateur ne l'a pas personnalisée manuellement. Un lien « Valeur personnalisée... » en secours révèle le champ numérique brut, selon le même mécanisme *reveal-on-demand* déjà livré pour la durée personnalisée (v1.109.4). Un bouton d'aide « ? » circulaire ouvre une popup Bootstrap complète (patron identique aux autres outils du site, ex. `code-qr.blade.php`) avec des exemples concrets de créneaux générés pour chaque mode. Backend : la validation de `step_minutes` passe de `in:15,30,60` à `min:5,max:480`, alignée sur les bornes de `duration_minutes` (le service de génération de créneaux ne contraint réellement que `step > 0`). 82/82 tests Pest verts. Vérifié visuellement (Herd local, Playwright) : les 3 états du contrôle (Flexible, Sans chevauchement, Personnalisé) et le rendu complet de la popup d'aide.

## [1.109.5] - 2026-07-17

### Fixed
- **Décido — unité « minutes » affichée dans le select et le champ personnalisé de la durée de la rencontre.** Le sélecteur ne montrait que des nombres bruts (« 15 », « 30 »...) et le champ personnalisé n'avait « minutes » qu'en placeholder (texte qui disparaît dès que l'utilisateur saisit une valeur). Options du sélecteur passées à « 15 minutes » / « 30 minutes » / etc. ; champ personnalisé enveloppé dans un `input-group` avec un suffixe `input-group-text` « minutes » toujours visible (pattern déjà établi sur ce site pour les suffixes d'unité, ex. `simulateur-fiscal.blade.php` avec « $ »). Champ élargi de 130px à 200px pour que le nombre et le mot « minutes » ne soient pas à l'étroit. 82/82 tests Pest verts. Vérifié visuellement (Herd local, Playwright).

## [1.109.4] - 2026-07-17

### Added
- **Décido — durée de la rencontre personnalisable (formulaire de sondage de dates).** Le champ « Durée de la rencontre (minutes) » n'offrait que 6 valeurs présélectionnées (15/30/45/60/90/120), sans possibilité de saisir une valeur libre - déjà supporté côté backend (`PollManageController` valide n'importe quel entier de 5 à 480 minutes), seule l'interface manquait l'option. Ajout d'une option « Personnalisée... » dans le sélecteur qui révèle un champ numérique **inline à droite du select** (et non empilé dessous) ; le select lui-même a aussi été rétréci (`max-width: 180px`, il occupait toute la largeur du formulaire pour n'afficher qu'un nombre à 2-3 chiffres). Un champ caché porte la valeur effective (preset ou personnalisée) vers le serveur, contrat de validation inchangé. 82/82 tests Pest verts. Vérifié visuellement (Herd local, Playwright) sur les 3 états : affichage par défaut compact, sélection « Personnalisée... » (champ révélé inline, valeur saisie propagée correctement au champ soumis), retour à une valeur présélectionnée (champ masqué à nouveau).

## [1.109.3] - 2026-07-17

### Fixed
- **Décido — migration complète vers le système de boutons `.ct-btn` de la charte (respect de la charte graphique et des autres outils de la plateforme).** En réponse à la question de l'utilisateur « est-ce que l'outil respecte la charte du site ? des autres outils de la plateforme ? », audit comparatif contre les outils établis (`code-qr`, `liens-google`, `generateur-equipes`, `tirage-presentations`) : Décido utilisait des classes Bootstrap brutes non tokenisées (`btn btn-outline-secondary`, `btn-outline-primary`, `btn-link`) + une classe ad hoc `.decido-touch-target`, au lieu du système `.ct-btn` déjà standard sur la plateforme - c'est la cause racine des deux bugs visuels corrigés en v1.109.1 et v1.109.2 sur ce même bouton « × » (un composant hors-charte accumule les défauts, ce n'était pas un hasard isolé). 13 boutons migrés sur 4 vues : `create-date.blade.php` (6), `create-classic.blade.php` (2), `results.blade.php` (6, dont 1 déjà `<x-core::button>` non touché) vers les combinaisons établies site-wide - `ct-btn ct-btn-outline-danger ct-btn-sm` pour le retrait/suppression (y compris le bouton « × » de plage horaire), `ct-btn ct-btn-ghost ct-btn-sm` pour les actions secondaires de type lien, `ct-btn ct-btn-outline ct-btn-sm` pour les actions neutres (ajouter, copier, télécharger). `index.blade.php` était déjà 100% conforme (`<x-core::button>` exclusivement), aucun changement nécessaire. Classe `.ct-range-remove` (ajoutée en v1.109.2, redondante avec `.ct-btn-icon`/`.ct-btn-outline-danger` déjà existants sur la plateforme) retirée de `charte.css`. `.decido-touch-target` conservée dans `charte.css` (encore utilisée par `public/vote.blade.php`, hors périmètre de cette migration). 82/82 tests Pest verts. Vérifié visuellement (Herd local, Playwright) sur les 4 vues avant/après - bouton « × » désormais avec bordure rouge visible, boutons « Retirer »/liens ghost/boutons neutres tous cohérents avec le reste du site.

## [1.109.2] - 2026-07-17

### Fixed
- **Décido/charte — polish visuel du bouton "×" de retrait de plage horaire.** Repéré par l'utilisateur après le fix v1.109.1 (« icon trop petit, et il me semble que la mise en page n'est pas très belle ») : même corrigé (bordure/fond restaurés), le glyphe « × » restait minuscule dans sa cible 44x44 et le bouton carré aux angles vifs contrastait avec l'esthétique du reste de la charte. Nouvelle classe réutilisable `.ct-range-remove` dans `public/css/charte.css` : glyphe `1.375rem`/gras (au lieu de la taille Bootstrap par défaut, quasi illisible), coins arrondis `8px` (au lieu des angles vifs de `.btn-outline-secondary`), état hover/focus rouge (`--c-danger`) qui communique clairement l'action de suppression - pattern « chip 2026 » déjà validé sur ce projet pour le minuteur visuel. C'est la 5e fois que ce même défaut (bouton × trop discret/mal intégré) est corrigé sur ce projet ; cette fois la solution est un composant réutilisable dans `charte.css` plutôt qu'un patch local de plus, pour que le prochain bouton « × » du site n'ait pas à réinventer la roue. Vérifié visuellement (capture zoomée avant/après). 82/82 tests Pest verts.

## [1.109.1] - 2026-07-17

### Fixed
- **Décido — bouton "×" de retrait de plage horaire (formulaire de dates dédié) flottait sans bordure ni fond, visuellement déconnecté des champs Début/Fin.** Repéré par l'utilisateur via capture d'écran après une vérification Playwright de ma part jugée insuffisamment critique (le défaut était déjà présent dans ma propre capture, mais qualifié à tort de « visuellement propre »). Cause racine confirmée par `getComputedStyle` (pas par supposition) : une règle CSS globale de `charte.css` - `[aria-label*="vote" i], [aria-label*="Soutenir"], [aria-label*="Retirer"] { border: none !important; background: none !important; }` (écrite pour les boutons de vote/avis BS3) - matchait accidentellement le nouveau bouton `aria-label="Retirer cette plage"` par simple inclusion de sous-chaîne, lui retirant bordure et fond avec `!important`. 17 fichiers du site utilisent un `aria-label` contenant « Retirer » et sont potentiellement affectés par cette règle trop large (non audités ici, hors périmètre de cette correction). Fix ciblé et à risque minimal : renommage de l'`aria-label` en « Supprimer cette plage horaire » (aucune sous-chaîne en commun avec la règle CSS globale) plutôt que de toucher la règle partagée elle-même. Second correctif du même repérage : la ligne Début/Fin/× utilisait des colonnes Bootstrap `col-5/col-5/col-2`, laissant une colonne vide disproportionnée pour le bouton - remplacé par un flex `d-flex gap-2` (Début/Fin en `flex-grow-1`, × en `flex-shrink-0`) pour que le bouton reste toujours collé au champ Fin quelle que soit la largeur du conteneur. Vérifié par `getComputedStyle` avant/après (bordure de `0px none` à `1px solid rgb(108,117,125)`, écart au champ Fin de ~200px à 7.5px) + capture d'écran zoomée. 82/82 tests Pest verts.

## [1.109.0] - 2026-07-17

### Added
- **Décido — Option E (formulaire de création par étape) + plages horaires multiples par date candidate + libellé clarifié.** (1) `/decido/creer` devient un simple sélecteur de type (2 cartes « Sondage de dates » / « Sondage classique ») ; chaque type route désormais vers un formulaire DÉDIÉ et allégé (`decido.create.date` / `decido.create.classic`) au lieu d'une seule page dense partageant un rendu conditionnel `x-show`. Champs essentiels visibles d'emblée (titre, durée, plage par défaut, dates ou options/mode de vote) ; champs secondaires (description, fuseau horaire, pas entre créneaux) regroupés sous `<details>` « Plus d'options », replié par défaut - natif donc accessible sans JS ni ARIA supplémentaire. Le `POST` final reste inchangé vers `decido.store`. Décidé via recherche pp_search juillet 2026 + validation croisée Codex/Gemini (95/100, Option E retenue face à un assistant multi-étapes classique jugé sur-ingénierie pour ce cas d'usage). (2) Une date candidate peut désormais proposer PLUSIEURS plages horaires (ex. 9h-12h ET 14h-17h pour sauter le dîner), et non plus une seule surcharge - `candidate_date_ranges[]` (tableau imbriqué par date puis par plage) remplace `candidate_date_start_times[]`/`candidate_date_end_times[]`. `PollManageController::store()` regroupe les dates candidates par plage horaire EFFECTIVE (une date peut apparaître dans plusieurs groupes) puis appelle `SlotGenerationService::generateSlots()` une fois par groupe - la méthode elle-même (durcie par 20+ rounds d'audit /100 : DST round 8, RFC5545 round 9, plafonds round 9) reste totalement inchangée. Deux plages qui se chevauchent pour la même date sont rejetées avec un message clair. Validé Codex+Gemini (92-95/100). (3) Le libellé « Pas entre les créneaux (minutes) », jugé ambigu par l'utilisateur (confondu avec la durée de la rencontre), est remplacé par la mini-phrase auto-explicative « Proposer une nouvelle heure de début toutes les [X] minutes » - le `<select>` devient grammaticalement partie intégrante du libellé (validé 96/100, aligné sur le pattern « Show available times every: [X] » identifié par la recherche). 82/82 tests Pest verts (nouveaux tests : multi-plages avec preuve du « saut du dîner » par assertions explicites de non-présence des créneaux 12:00/13:00, rejet du chevauchement, rejet d'une plage partielle début-sans-fin, chooser + 2 formulaires dédiés + héritage du gate `decido.*` + `page_noindex`). Vérifié visuellement (Herd local, Playwright) : chooser, formulaire dates (ajout/retrait de plage, retour à l'horaire par défaut, nouveau libellé), formulaire classique.

## [1.108.0] - 2026-07-17

### Added
- **Décido — plages horaires personnalisables par date candidate.** Jusqu'ici `range_start_time`/`range_end_time` étaient GLOBAUX à tout le sondage : toutes les dates candidates partageaient obligatoirement la même plage horaire, une limitation réelle par rapport à Framadate (impossible de proposer « lundi seulement l'après-midi, mercredi seulement le matin »). `candidate_date_start_times[]`/`candidate_date_end_times[]` (parallèles à `candidate_dates[]`) permettent désormais de surcharger la plage pour une date précise ; une entrée vide hérite de la plage par défaut. `PollManageController::store()` regroupe les dates candidates par plage horaire effective puis appelle `SlotGenerationService::generateSlots()` une fois par groupe - la méthode elle-même (durcie par 20+ rounds d'audit /100 : DST round 8, RFC5545 round 9, plafonds round 9) reste totalement inchangée, réutilisée telle quelle plutôt que réécrite pour gérer des plages hétérogènes en interne ; le tri final par `starts_at` restaure l'ordre chronologique. Une surcharge partielle (début renseigné sans fin, ou l'inverse) est rejetée avec un message clair plutôt que de mélanger silencieusement avec la plage par défaut. Formulaire (`create.blade.php`) : case à cocher « Horaire différent pour cette date » par date candidate, révèle 2 champs Début/Fin préremplis avec la plage par défaut au premier cochage. 2 nouveaux tests Pest (plage mixte défaut+surcharge, rejet surcharge partielle). 77/77 tests Pest verts. Vérifié visuellement (Herd local, Playwright).

## [1.107.22] - 2026-07-17

### Fixed
- **Décido — og:url, canonical et hreflang du layout global (`master.blade.php`) fuitaient le jeton admin de la page de gestion, via un vecteur distinct de la barre de partage corrigée au round 25.** Round 26 (skill /100), consigne : le round 25 n'avait corrigé qu'UN SEUL vecteur (barre de partage Facebook/X/LinkedIn) parmi potentiellement plusieurs mécanismes du layout global embarquant l'URL courante complète - grep exhaustif de `request()->url()`/`fullUrl()`/`url()->current()` sur tout `Modules/FrontTheme/resources/views/` exigé. Trouvé : `Modules/FrontTheme/resources/views/layouts/master.blade.php` lignes 82 (`<meta property="og:url">`) et 98-100 (`<link rel="canonical">` + 2x `<link rel="alternate" hreflang>`) utilisaient toutes `url()->current()` **sans aucune exclusion**, contrairement à `$shareUrl` (round 25) qui avait bien reçu l'exclusion `decido/*/gerer*`. Vecteur distinct et plus insidieux que le round 25 : pas un clic explicite sur un bouton de partage, mais un « unfurl » **automatique** - la quasi-totalité des messageries (Slack, Discord, Teams, Messenger, WhatsApp, clients courriel) récupèrent `og:url` dès qu'un lien est collé dans une conversation pour générer un aperçu, et mettent ce contenu en cache sur **leurs propres serveurs**. Le simple fait, pour l'organisateur, de coller son propre lien d'administration dans une messagerie pour se l'envoyer ou le partager avec un co-organisateur suffisait donc à exfiltrer le jeton vers un tiers - sans aucun clic de partage. Le `noindex` posé au round 10 (`<meta name="robots">`) ne bloque pas ce mécanisme : les robots d'aperçu Open Graph l'ignorent largement. Corrigé en encadrant ces 4 balises d'un `@unless(request()->is('decido/*/gerer*'))` : sur cette route spécifique, elles sont purement omises (une page déjà `noindex` et porteuse d'un secret n'a aucune raison fonctionnelle de s'auto-canonicaliser ni de s'annoncer à des crawlers social/SEO), plutôt que de leur substituer une valeur de repli qui aurait ajouté de la complexité pour un gain nul. Preuve réelle par requête HTTP : le HTML rendu de `/decido/{poll}/gerer/{jeton}` contenait littéralement `<meta property="og:url" content=".../gerer/{jeton}">` et `<link rel="canonical" href=".../gerer/{jeton}">` avant correctif (test qui échoue contre l'ancien code, rejoué après un `git stash` temporaire du fichier corrigé pour le confirmer) ; après correctif, ces 4 balises sont absentes du `<head>` uniquement sur cette route - contrôle négatif : la page publique de vote (URL sans secret) continue d'afficher `og:url`/`canonical` normalement. Audit complémentaire du JSON-LD `BreadcrumbList` (`Modules/FrontTheme/resources/views/partials/breadcrumb.blade.php` lignes 77/84, également non protégé) : vérifié réel vs inerte par requête HTTP - le partial n'est inclus sur la page de gestion qu'avec `breadcrumbTitle` (pas `breadcrumbItems`), donc la condition qui encadre les `ListItem` à `url()->current()` y est toujours fausse actuellement ; vecteur présent dans le code mais non exploitable aujourd'hui, donc **aucun correctif appliqué** sur ce point (le round 26 n'invente pas de fuite fictive) - verrouillé par un test de non-régression qui échouera si une future modification passe `breadcrumbItems` à ce partial sur une route à jeton. 75/75 tests Pest verts (73 existants + 2 nouveaux). **Ce round contient un vrai bug corrigé, donc ne compte pas comme un round clean - le compteur des deux verdicts CLEAN consécutifs requis pour clore le gate /100 repart à zéro** (il faudra reprendre au round 27).

## [1.107.21] - 2026-07-17

### Fixed
- **Décido — la barre de partage flottante (Facebook/X/LinkedIn) fuitait le jeton admin de la page de gestion dans le lien de partage lui-même.** Round 25 (skill /100), angle initial audité : le Referrer-Policy HTTP natif du navigateur (`Modules/Core/app/Http/Middleware/SecurityHeaders.php` déclare déjà `strict-origin-when-cross-origin`, qui borne correctement tout Referer cross-origin à la seule origine sans le chemin) - CLEAN, un clic sortant ordinaire ne fuit pas le jeton par ce mécanisme. Mais cet audit a révélé un vecteur bien plus direct : `Modules/FrontTheme/resources/views/layouts/master.blade.php` (barre de partage flottante, présente sur presque toutes les pages) construit `$shareUrl = urlencode(request()->url())` - l'URL courante complète - et l'injecte explicitement dans le paramètre `u=`/`url=` des sharers Facebook/X/LinkedIn (ex. `facebook.com/sharer/sharer.php?u=.../decido/{poll}/gerer/{adminToken}`). Sur `/decido/{poll}/gerer/{adminToken}`, cette URL porte en clair le jeton admin (contrôle total du sondage : clôture, export des pseudonymes des votants, création de lien court). Ce n'est pas une fuite Referer (déjà bornée par la politique globale) mais une fuite par paramètre de requête explicite, invisible à toute politique Referrer-Policy - le jeton aurait été transmis à Facebook/X/LinkedIn (et exploré par leurs robots de prévisualisation OG) au moindre clic accidentel de l'organisateur sur « Partager » depuis sa propre page de gestion. La liste d'exclusion existante de la barre couvrait déjà `admin*` pour la même raison mais pas `decido/*/gerer*`. Corrigé en ajoutant ce pattern à la liste d'exclusion (solution proportionnée, aucune réécriture de la politique Referrer-Policy globale, déjà correcte). Preuve réelle par requête HTTP : le HTML rendu de la page de gestion contenait littéralement le lien `facebook.com/sharer/sharer.php?u=...%2Fgerer%2F{jeton}` avant correctif ; après correctif, la barre de partage entière (Facebook/X/LinkedIn) est absente de cette page uniquement - contrôle négatif : la page publique de vote (URL sans secret) continue d'afficher la barre normalement. 73/73 tests Pest verts (72 existants + 1 nouveau ; le nouveau test échoue contre l'ancien code, vérifié avant correctif). Second des deux verdicts CLEAN consécutifs requis pour clore le gate /100 : ce round contient un vrai bug corrigé, donc ne compte pas comme un round clean - le compteur repart à zéro.

## [1.107.20] - 2026-07-16

### Fixed
- **Décido — le champ `description` du sondage n'avait AUCUNE limite de longueur, contrairement à `title` (`max:255`).** `PollManageController::store()` validait `description` avec `['nullable', 'string']` seulement. Preuve réelle isolée hors framework (INSERT PDO direct sur la DB MySQL/MariaDB locale, `'strict' => true` comme en prod — `config/database.php`) : une description de 5 Mo ne produit PAS de troncature silencieuse mais lève une `PDOException` SQLSTATE 22001 `Data too long for column 'description'` (limite réelle de la colonne `text` : 65 535 octets). Cette exception (`Illuminate\Database\QueryException`, jamais une `InvalidArgumentException`) n'était interceptée NULLE PART dans `store()` — elle aurait remonté telle quelle jusqu'au gestionnaire d'exceptions global, produisant un crash 500 brut pour une simple entrée trop longue, même défaut de robustesse que le fuseau horaire invalide corrigé au round 18. Corrigé en ajoutant `'max:5000'` à la règle (`mb_strlen`, marge large pour un texte libre légitime tout en restant très en-deçà de la limite d'octets même dans le pire cas UTF-8 multi-octets).

- **Décido — `decido:purge-expired` laissait un `ShortUrl` orphelin (lien mort) après suppression d'un sondage expiré ayant un lien court associé.** `decido_polls.short_url_id` n'a AUCUNE contrainte de clé étrangère (migration `add_short_url_id_to_decido_polls` : `unsignedBigInteger` nullable, ni `constrained()` ni `cascadeOnDelete()`). Le `ShortUrl` créé par `Poll::claimShortUrl()` survivait donc indéfiniment en base après la purge du sondage, continuant de rediriger (301, `is_active` toujours actif) vers l'URL désormais supprimée du sondage — un lien mort, potentiellement partagé publiquement (c'est tout l'objet d'un lien court), aboutissant à un 404 brut sans jamais être nettoyé. Corrigé en identifiant les `short_url_id` des sondages sur le point d'être purgés puis en soft-supprimant (`SoftDeletes` du modèle `ShortUrl`) les `ShortUrl` correspondants avant le `DELETE` en masse : le scope global Eloquent les exclut alors de `ShortUrlService::resolve()`, et `ShortUrlRedirectController` affiche désormais la page `/lien-expire` dédiée au lieu d'un 404 brut.

  Troisième angle audité (`close()` avec `final_option_id` NULL — organisateur clôturant sans choisir de créneau final — puis export ICS) : déjà géré proprement par le code existant. `PollExportService::exportIcs()` lève une `RuntimeException` claire dès que `final_option_id === null`, interceptée et affichée par `PollManageController::exportIcs()` (redirection + message d'erreur, jamais de fichier ICS cassé ou de crash). Seul le parcours HTTP complet de ce cas précis (clôture réelle sans créneau final, puis appel à la route d'export ICS) n'était pas encore prouvé par un test — test ajouté sans correctif, angle CLEAN.

Trouvé par une passe adversariale indépendante (skill `/100`, round 23). Ce round n'est PAS clean (deux vrais bugs corrigés) — le compteur de rounds clean consécutifs reste à zéro, il faut désormais deux rounds clean consécutifs pour clore le gate. 70/70 tests Pest verts (65 existants + 5 nouveaux ; les 2 tests prouvant les bugs échouent contre l'ancien code, vérifié par stash avant correctif).

## [1.107.19] - 2026-07-16

### Fixed
- **Décido — `PollExportService::exportCsv()` corrompait le CSV exporté pour un `voter_pseudonym` contenant un backslash suivi d'un guillemet interne (intégrité RFC4180, au-delà de l'injection de formule déjà neutralisée au round 5).** `fputcsv($handle, [...], ';', '"', '\\')` — le 5e argument `'\\'` active le mécanisme d'ÉCHAPPEMENT PROPRIÉTAIRE de PHP (non-RFC4180), qui échappe le caractère SUIVANT le backslash au lieu de doubler les guillemets internes comme le veut la norme. Bug réel trouvé par isolation directe de `fputcsv`/`fgetcsv` puis reproduit par requête HTTP réelle sur l'export : un pseudonyme texte libre (contrôlé par un votant anonyme) tel que `Jean\"Boss"` corrompt le champ de deux façons distinctes selon le lecteur —
  - relu avec le même `escape='\\'` (round-trip PHP) : le guillemet fermant est lui-même échappé, le parseur avale la ligne SUIVANTE entière dans le même champ (fusion de lignes, colonnes décalées, un votant disparaît du fichier) ;
  - relu avec un lecteur RFC4180 strict (`escape=''`, comportement réel d'Excel/Google Sheets/Numbers, qui ignorent la convention backslash propriétaire de PHP) : le nombre de colonnes reste correct mais la VALEUR récupérée est silencieusement corrompue (`Jean\Boss"""` au lieu de `Jean\"Boss"`) — corruption de donnée invisible, sans erreur, pire qu'un plantage visible.

  Corrigé en passant une chaîne vide comme 5e argument à `fputcsv()` (désactive le mécanisme propriétaire, revient au pur doublage RFC4180 des guillemets internes) — vérifié sans régression sur tous les cas déjà couverts (virgule+guillemets round 5, saut de ligne interne, point-virgule = délimiteur réel du fichier).

  Second angle audité (sémantique accessible des barres de résultats pour lecteur d'écran) : aucune barre de progression visuelle (`width%`) n'existe dans `results.blade.php` ni ailleurs dans le module — vérifié par grep exhaustif de la vue. Les résultats sont déjà affichés en TEXTE pur (badges `✓ 3 oui`) et le tableau croisé porte déjà des `aria-label` explicites par cellule + `caption` + `scope` — aucun correctif ARIA nécessaire, pas de correctif cosmétique forcé sur un composant qui n'existe pas.

Trouvé par une passe adversariale indépendante (skill `/100`, round 22, intégrité structurelle RFC4180 du CSV exporté). Ce round n'est PAS clean (un vrai bug corrigé) — le compteur de rounds clean consécutifs reste à zéro, il faut désormais un round clean de plus pour clore le gate. 65/65 tests Pest verts (64 existants + 1 nouveau).

## [1.107.18] - 2026-07-16

### Fixed
- **Décido — `DistinctNormalized` (round 20) était contournable par NORMALISATION UNICODE, angle explicitement laissé en suspens par le round 20 lui-même dans son propre test de contrôle négatif.** `DistinctNormalized::validate()` ne faisait que `trim()` + collapse des espaces + `mb_strtolower()` — aucune normalisation de FORME Unicode. Un même caractère accentué peut être encodé en octets strictement différents tout en étant rendu de façon identique par tout navigateur : `"é"` précomposé (NFC, U+00E9, 1 code point) vs `"é"` décomposé (NFD, U+0065 + U+0301, 2 code points). Preuve réelle par requête HTTP réelle rejouée pendant cet audit : `POST options=["café" NFC, "café" NFD]` (bytes `636166c3a9` vs `63616665cc81`, strictement différents même après `mb_strtolower()`) passait `DistinctNormalized` intact et créait bien 2 `PollOption` distinctes rendues à l'identique par le navigateur — recréant le bug de scission de votes des rounds 11/20 via un vecteur invisible à l'œil nu (aucune différence de casse ni d'espacement visible). Corrigé en ajoutant `Normalizer::normalize($item, Normalizer::FORM_C)` AVANT le collapse d'espaces/minuscules (extension `intl` confirmée chargée sur ce projet ; garde défensive si `normalize()` échoue sur une entrée malformée).

Second angle audité en profondeur : les homoglyphes multi-scripts (ex. cyrillique `"а"` U+0430 vs latin `"a"` U+0061) contournent bien la validation — confirmé réel par requête HTTP — mais jugé **hors périmètre raisonnable** : aucune relation de canonicité Unicode n'existe entre scripts différents, une détection complète nécessiterait une table de correspondance substantielle (type Unicode TR39/UTS#39 « skeleton ») avec un risque réel de faux positifs sur des libellés légitimes non-latins. Documenté comme limite connue et assumée dans le docblock de `DistinctNormalized`, verrouillé par un test de régression qui prouve ce comportement documenté plutôt que de le laisser implicite.

Trouvé par une passe adversariale indépendante (skill `/100`, round 21, contournement Unicode de `DistinctNormalized`). Ce round n'est PAS clean (un vrai bug corrigé) — le compteur de rounds clean consécutifs reste à zéro. 64/64 tests Pest verts (61 existants + 3 nouveaux).

## [1.107.17] - 2026-07-16

### Fixed
- **Décido — la règle Laravel `distinct` (round 11) était contournable par variation de FORMAT plutôt que par duplication exacte.** `distinct` compare des chaînes exactes, pas des valeurs métier équivalentes — elle ne détecte pas deux valeurs différentes en octets mais identiques en pratique. Deux angles réels, prouvés par de vraies requêtes HTTP :
  - **Dates candidates.** `candidate_dates.*` portait la règle générique `date` (accepte tout ce que `strtotime()` reconnaît) au lieu de `date_format:Y-m-d`. `POST candidate_dates=['2027-03-14', '2027-3-14']` (même jour calendaire, deux formats différents) passait `distinct` intact puis `SlotGenerationService::generateSlots()` → `Carbon::createFromFormat('Y-m-d H:i', ...)` parsait les deux chaînes vers le même instant UTC — 4 `PollOption` créées (2 créneaux × 2 dates « différentes ») avec `starts_at`/`ends_at` strictement identiques deux à deux, recréant le bug de scission de votes du round 11. Corrigé en durcissant la règle à `date_format:Y-m-d` (le `<input type="date">` HTML5 du formulaire soumet toujours ce format canonique — aucun usage légitime restreint).
  - **Options texte (type classique).** Deux libellés qui ne diffèrent que par la casse (`"Pizza"`/`"pizza"`) ou des espaces internes multiples (`"Pizza 4 fromages"`/`"Pizza  4 fromages"`, collapsés identiquement à l'affichage HTML) passaient `distinct` et créaient réellement 2 `PollOption` distinctes, visuellement indiscernables pour un votant. Corrigé par une nouvelle règle de validation `Modules\Decido\Rules\DistinctNormalized` (normalise casse + espaces avant comparaison), ajoutée au champ `options` en complément de `distinct` sur `options.*`.

Angle Unicode NFC/NFD non nécessaire — deux bugs réels déjà trouvés et corrigés sur les angles prioritaires demandés.

Trouvé par une passe adversariale indépendante (skill `/100`, round 20, contournement de `distinct` par variation de format). Ce round n'est PAS clean (deux vrais bugs corrigés) — le compteur de rounds clean consécutifs reste à zéro. 61/61 tests Pest verts (57 existants + 4 nouveaux).

## [1.107.16] - 2026-07-16

### Fixed
- **Décido — un sondage de dates pouvait être publié sans AUCUN créneau (heure d'été).** `PollManageController::store()` vérifiait `count($slots) > 500` (plafond volumétrique, round 9) mais jamais `count($slots) === 0`. `SlotGenerationService::validateInputs()` ne compare la plage horaire à la durée que sur une date de référence neutre (`2000-01-01`, sans DST) — une plage/durée nominalement valides (ex. `01:30`-`03:00` = 90 min, durée 60 min) passaient donc la validation. Mais `generateSlots()` calcule l'écart réel en UTC pour chaque date candidate (round 8) : le jour du passage à l'heure d'été (America/Toronto), l'écart réel entre `01:30` et `03:00` heure locale n'est que de 30 minutes (l'heure `02:00`-`02:59` n'existe pas ce jour-là) — inférieur à la durée de 60 min. Prouvé en réel : `generateSlots(['2027-03-14'], '01:30', '03:00', 60, 15, 'America/Toronto')` retourne un tableau vide. Si toutes les dates candidates soumises tombaient dans ce cas, le `Poll` était quand même sauvegardé avec `status='open'` et zéro `PollOption` — un sondage publié, partageable, sur lequel personne ne peut voter, sans aucun message d'erreur pour le créateur. Garde-fou `count($slots) === 0` ajouté, avec rollback complet via la transaction du round 16 (aucun sondage fantôme en base).

Second angle audité en profondeur sans trouver de bug : `final_option_id` sur `close()` (IDOR potentiel — un ID d'option appartenant à un autre sondage). Déjà correctement scopé via `$pollModel->options()->where('id', $finalOptionId)->exists()` (`options()` = `HasMany` scopé par `poll_id`) — vérifié par une vraie requête HTTP avec jeton admin valide et option étrangère, verrouillé par un nouveau test de régression.

Trouvé par une passe adversariale indépendante (skill `/100`, round 19, angle sondage vide). Le round 18 avait été CLEAN ; ce round non-clean remet le compteur à zéro. 57/57 tests Pest verts (55 existants + 2 nouveaux).

## [1.107.15] - 2026-07-16

### Fixed
- **Décido — validation du fuseau horaire manquante (crash 500 sur entrée invalide).** `PollManageController::store()` ne validait `timezone` que via `string|max:60`, sans vérification contre la liste IANA réelle. Une valeur arbitraire (`"Not/AZone"`, chaîne à rallonge, caractères spéciaux) passait la validation puis atteignait directement `Carbon::createFromFormat()` dans `SlotGenerationService`, dont le `DateTimeZone` interne lève une `\Exception` brute (jamais `InvalidArgumentException`) — ni la validation ni le `catch` de `store()` n'interceptaient l'erreur, produisant un crash 500 pour une simple erreur de saisie au lieu d'un message de validation convivial. Règle Laravel `'timezone'` ajoutée (vérifie `timezone_identifiers_list(DateTimeZone::ALL)`).
- **Décido — clôture d'un sondage non idempotente.** `PollManageController::close()` ne vérifiait jamais si le sondage était déjà clôturé avant de réappliquer `status='closed'`, `final_option_id` et `expires_at`. Un second appel (double-clic avant que l'UI ne masque le formulaire, ou rejeu de la requête POST) écrasait silencieusement le créneau final déjà choisi — potentiellement vers une autre option ou vers `null` — et repoussait indéfiniment la date d'expiration à chaque rejeu, contournant la politique de purge automatique (`decido:purge-expired`, round 5). Un garde en début de méthode redirige désormais sans rien muter si le sondage est déjà `closed`.

Trouvé par une passe adversariale indépendante (skill `/100`, round 18, angles fuseau horaire + idempotence clôture). Angle SQL brut audité en profondeur (grep exhaustif sur tout `Modules/Decido`) : aucune injection trouvée. Le round 17 avait été CLEAN ; ce round non-clean remet le compteur à zéro. 55/55 tests Pest verts (52 existants + 3 nouveaux).

## [1.107.14] - 2026-07-16

### Fixed
- **Décido — création d'un sondage non atomique pouvait laisser un sondage fantôme en base.** `PollManageController::store()` insérait le `Poll` puis bouclait sur la création de ses `PollOption` (jusqu'à 500 créneaux pour le type date) sans transaction — seul le garde-fou 500 créneaux était rattrapé pour nettoyer manuellement. Toute autre exception en cours de boucle (contrainte DB, perte de connexion, timeout) laissait un sondage `status='draft'` avec des options partielles, jamais promu à `open`, visible dans « Mes sondages » mais inutilisable. Toute la création (Poll + options + passage à `open`) est désormais enveloppée dans un seul `DB::transaction()`.

Trouvé par une passe adversariale indépendante (skill `/100`, round 16, angle atomicité). 50/50 tests Pest verts.

## [1.107.13] - 2026-07-16

### Fixed
- **Décido — race condition sur la création de lien court pouvait orpheliner un `ShortUrl`.** `PollManageController::createShortLink()` lisait `short_url_id` sur l'instance Eloquent déjà chargée en début de méthode, créait le `ShortUrl`, puis écrivait — sans transaction ni verrou, contrairement au pattern déjà en place pour le vote/la clôture. Deux requêtes quasi simultanées (double-clic, retry réseau) pouvaient chacune lire `short_url_id=NULL` avant qu'aucune n'ait écrit, créer chacune un `ShortUrl` distinct, la seconde écriture écrasant silencieusement la première — orphelinant un `ShortUrl` jamais référencé. Nouvelle méthode `Poll::claimShortUrl()` qui relit l'état dans une transaction verrouillée (`lockForUpdate`) au lieu de faire confiance à l'instance potentiellement périmée.

Trouvé par une passe adversariale indépendante (skill `/100`, round 15, angle concurrence réelle). Le round 14 avait été le premier verdict CLEAN depuis le round 3 ; ce round non-clean remet le compteur à zéro. 48/48 tests Pest verts.

## [1.107.12] - 2026-07-16

### Fixed
- **Décido — fuite du jeton admin vers Sentry (télémétrie d'erreurs serveur).** `Sentry\Integration\RequestIntegration` capture inconditionnellement l'URL complète de la requête (`event.request.url`) sur chaque exception rapportée, même avec `send_default_pii=false` (ce flag ne protège que cookies/headers/IP, jamais l'URL). Le jeton admin Décido transite dans le *chemin* de l'URL (`/decido/{poll}/gerer/{adminToken}`, invisible à un audit "pas de token en paramètre") : toute exception levée pendant une requête de gestion l'aurait envoyé en clair vers Sentry (tiers hors UE). Vecteur distinct du round 12 (GA4/`page_location`, uniquement navigateur) — télémétrie d'erreurs serveur, non couverte par ce fix. Nouveau service générique et réutilisable `Modules\Core\Services\SentryUrlScrubber` (motif regex extensible pour tout futur module exposant un jeton en chemin d'URL), branché via `config/sentry.php` (clé `before_send` uniquement, fusionnée par `mergeConfigFrom` - aucune autre option Sentry affectée).

Trouvé par une passe adversariale indépendante (skill `/100`, round 13, angle brute-force/fuite tierce serveur). 44/44 tests Pest verts.

## [1.107.11] - 2026-07-16

### Fixed
- **Décido — fuite du jeton admin vers Google Analytics.** Le jeton admin (contrôle total du sondage - clôture, export des pseudonymes des votants, création de lien court) transite en clair dans le chemin de l'URL de gestion (`/decido/{poll}/gerer/{adminToken}`). Le layout global charge GA4 (`send_page_view: true`) sur toute page ne déclarant pas `no_analytics` : le hit GA4 capture automatiquement `page_location = window.location.href`, transmettant donc le jeton en clair à un tiers (Google), stocké indéfiniment dans la propriété GA4 à chaque chargement de la page. Le round 10 avait déjà bloqué l'indexation (`page_noindex`) pour la même raison de fond, mais avait laissé passer ce second vecteur, entièrement distinct. `@section('no_analytics', '1')` et `@section('no_ads', '1')` ajoutés (même posture que l'anonymiseur, outil traitant des PII).

Trouvé par une passe adversariale indépendante (skill `/100`, round 12, angle fuite de données vers un tiers). 42/42 tests Pest verts.

## [1.107.10] - 2026-07-16

### Fixed
- **Décido — dates candidates ou options en double faussaient silencieusement le décompte des votes.** Aucune règle de validation n'empêchait de soumettre deux fois la même date candidate, ou deux options classiques au libellé strictement identique — `PollManageController::store()` créait alors deux `PollOption` distinctes en tout point identiques. Les votants qui cliquaient l'une ou l'autre carte voyaient leur vote silencieusement scindé entre les deux lignes en base, faussant le résultat final sans jamais faire remonter d'erreur (ex. 5 votes réels pour « Pizza » affichés 3/2 sur deux lignes séparées au lieu de révéler la vraie majorité). Règle `distinct` ajoutée sur `candidate_dates.*` et `options.*`.

Trouvé par une passe adversariale indépendante (skill `/100`, round 11, angle intégrité des données de vote). 41/41 tests Pest verts.

## [1.107.9] - 2026-07-16

### Added
- **Décido listé sur `/outils`, marqué « En construction ».** Migration réversible `2026_07_16_120000_seed_decido_tool_entry.php` ajoute l'entrée `decido` à la table `tools` (`is_under_construction=true`, pattern `updateOrInsert` identique à Minuteur visuel/Anonymiseur). Le carton apparaît pour tous les visiteurs sur `/outils` avec le badge « Bientôt », mais son lien pointe directement vers `/decido` (module dédié avec ses propres routes/contrôleurs — aucune colonne `external_url` n'existe dans le schéma `tools`, contrairement aux outils à vue générique). L'accès réel reste entièrement gouverné par le middleware `DecidoUnderConstruction` déjà en place (superadmin uniquement, testé) : un invité qui clique est redirigé vers la connexion, un utilisateur connecté non-superadmin reçoit 503.

### Fixed
- **Décido — pages privées indexables une fois le module public.** Aucune vue Décido (`vote.blade.php`, `results.blade.php`, `create.blade.php`, `manage/index.blade.php`) ne déclarait `@section('page_noindex', true)` — une fois `DECIDO_UNDER_CONSTRUCTION=false`, les pages contenant pseudonymes et choix de vote seraient devenues indexables par défaut. Ajouté aux 4 vues, avec preuve HTTP réelle (présence de la balise `<meta name="robots" content="noindex">`).

Trouvés par une passe adversariale indépendante (skill `/100`, round 10, angle SEO/confidentialité). 39/39 tests Pest verts.

## [1.107.8] - 2026-07-16

### Fixed
- **Décido — export ICS sans pliage de ligne conforme RFC 5545.** Un titre de sondage long ou contenant des caractères unicode produisait une ligne `SUMMARY:` de plusieurs centaines d'octets, dépassant largement la limite RFC 5545 §3.1 (75 octets/ligne) — risque de troncature par des lecteurs de calendrier stricts (Outlook/Exchange). `PollExportService::foldIcsLine()` ajouté, plie chaque ligne de contenu ICS sans jamais couper au milieu d'une séquence UTF-8 multi-octets.
- **Décido — aucune borne sur le nombre de dates candidates ni sur le volume total de créneaux générés.** Contrairement au type de sondage classique (déjà plafonné à 20 options), le type "date" n'avait aucune limite — 3800 options créées en test réel avec 40 dates candidates × une large plage horaire × un pas de 15 minutes. Ajout d'un plafond de 60 dates candidates et d'un plafond de 500 créneaux au total.

Trouvés par une passe adversariale indépendante (skill `/100`, round 9, angle réversibilité des migrations + cas limites de données — cycle complet rollback/remigrate testé réellement sans erreur, titre 255 caractères, unicode/emoji et XSS stocké via `voter_pseudonym` tous vérifiés propres). 38/38 tests Pest verts.

## [1.107.7] - 2026-07-16

### Fixed
- **Décido — boutons "+ Ajouter" / "Retirer" sous la cible tactile AAA sur `/decido/creer`.** Le fix touch-target des rounds 6-7 n'avait jamais été porté sur cette vue. La classe `.decido-touch-target` a été déplacée de `results.blade.php` vers `public/css/charte.css` (utilitaire global réutilisable, DRY) et appliquée aux 4 boutons concernés.
- **Décido — grille de vote peu utilisable au pouce sur mobile.** Les radios/checkboxes natifs (~14×14px, libellé cliquable ~22×21px) étaient bien sous 44×44px — jusqu'à 144 cibles trop petites pour un sondage de dates multi-jours. `public/vote.blade.php` utilise désormais des libellés pleine taille en pilules/blocs (44px minimum, `:has(input:checked)`/`:has(input:focus-visible)` en CSS pur, sans JavaScript) pour les 3 modes de vote.
- **Décido — créneaux incohérents lors des changements d'heure (DST).** L'arithmétique de `SlotGenerationService` opérait en heure locale (`America/Toronto`), traversant silencieusement les changements d'heure : un créneau de 30 minutes à cheval sur le passage à l'heure d'été durait en réalité 90 minutes une fois relu. Déplacée entièrement en UTC (sans DST par nature) — la durée d'un créneau est désormais toujours exacte, quel que soit le jour candidat.
- **Décido — libellés de créneaux ambigus au retour à l'heure normale.** Deux créneaux UTC distincts pouvaient produire un libellé local strictement identique (l'heure locale se produit deux fois ce jour-là), rendant impossible pour un votant de savoir lequel choisir. Le service ajoute désormais automatiquement le décalage UTC en désambiguïsation, uniquement sur les libellés en collision.
- **Décido — `class_exists()` ne détecte pas un module ShortUrl réellement désactivé.** `class_exists()` reste vrai même quand un module est désactivé via `modules_statuses.json` (nwidart garde les classes en autoload, seul le boot du `ServiceProvider` est coupé) — un lien court "fantôme" (pointant vers des routes jamais enregistrées, 404 réel) pouvait être créé et affiché à l'organisateur sans le moindre avertissement. Remplacé par `Modules\Core\Services\ModuleChecker::isAvailable()` (utilitaire DRY déjà existant dans le projet, vérifie `Module::has()`+`isEnabled()`) dans `Poll::shortUrl()`/`getShortUrlString()` et `PollManageController::createShortLink()`.

Trouvés par une passe adversariale indépendante (skill `/100`, round 8, angle responsive mobile réel + cas limites DST + frontières d'intégration entre modules — vérifiés en conditions réelles via Playwright et script PHP autonome). 35/35 tests Pest verts.

## [1.107.6] - 2026-07-16

### Fixed
- **Décido — requêtes redondantes (N+1) sur `Poll::getShortUrlString()`.** Un `ShortUrl::find()` brut, jamais mis en cache, était exécuté à chaque appel — la page de résultats appelant cette méthode 3 fois par chargement (6 requêtes `short_urls`/`short_url_domains` redondantes observées via query log réel). Remplacé par `$this->shortUrl` (relation Eloquent, mise en cache après le premier accès), nouveau test de non-régression comptant les requêtes réelles.
- **Décido — `decido:purge-expired` chargeait tous les sondages expirés en mémoire avant de les supprimer un par un.** Défaut de conception qui empire linéairement avec le volume (aucun problème aujourd'hui, confirmé par exécution réelle). Remplacé par un `DELETE` en masse — comportement strictement identique (aucun hook Eloquent `deleting`/`deleted` enregistré sur `Poll`, cascades options/votes déjà au niveau contrainte FK de la base de données).

Trouvés par une passe adversariale indépendante (skill `/100`, round 7, angle performance/N+1 + vérification end-to-end réelle : création/vote/clôture de sondages réels, contenu des exports CSV/ICS lu et validé, `decido:purge-expired` exécuté réellement). 32/32 tests Pest verts.

## [1.107.5] - 2026-07-16

### Fixed
- **Décido — suppression du compte créateur orpheline désormais le sondage au lieu de le cascader.** Décision explicite de l'utilisateur suite au finding round 5 : `cascadeOnDelete()` sur `creator_id` détruisait intégralement un sondage (créneaux + tous les votes de tiers) dès que le créateur supprimait son compte, sans préavis possible pour les votants anonymes (aucun compte requis pour voter). Nouvelle migration `2026_07_16_160000_orphan_instead_of_cascade_decido_polls_creator.php` : `creator_id` devient nullable + `nullOnDelete()` (réversible). Le sondage et tous les votes des participants survivent désormais, seule la gestion via compte devient indisponible (accès toujours possible via le lien admin à jeton).

## [1.107.4] - 2026-07-16

### Fixed
- **Décido — sélecteur "Type de sondage" inaccessible au clavier.** Les radios de `/decido/creer` utilisaient `class="d-none"` (display:none), les retirant de l'ordre de tabulation — violation WCAG 2.1.1 (niveau A) sur le tout premier champ du formulaire de création. Remplacé par `visually-hidden` (masqué visuellement, reste focalisable/actionnable au clavier) avec un anneau de focus visible sur la carte via `:has(input:focus-visible)`.
- **Décido — bug de données : votants homonymes silencieusement fusionnés.** Deux votants distincts partageant le même pseudonyme voyaient un de leurs deux votes disparaître du résumé et du tableau croisé de la page de résultats — `totalVoters`/`voterNames`/`matrix` étaient clés par `voter_pseudonym` (texte libre) au lieu de `voter_token` (identifiant réellement unique par votant). Reclé par `voter_token`, nouveau test de non-régression.
- **Décido — race condition (TOCTOU) entre vote en cours et clôture du sondage.** Le statut du sondage n'était vérifié qu'une seule fois en tout début de traitement d'un vote, sans verrou — un vote soumis dans la fenêtre entre cette vérification et l'écriture pouvait être accepté silencieusement même si l'organisateur venait de clôturer le sondage entre-temps. `PublicPollController::vote()` enveloppé dans `DB::transaction()` avec `lockForUpdate()` et re-vérification du statut à l'intérieur de la transaction.
- **Décido — contraste WCAG AAA du badge "Fermé" + cibles tactiles + accessibilité du drill-down.** Badge `#6c757d` (4.69:1, sous le seuil AAA 7:1) remplacé par `var(--c-dark)`. Six boutons secondaires (Copier ×3, Créer un lien court, Voir qui a répondu, Télécharger le QR code) sous la cible tactile AAA de 44×44px — le layout public du module n'hérite pas de la règle `.user-space` qui l'impose ailleurs sur le site — corrigés via une classe utilitaire `.decido-touch-target`. Bouton "Voir qui a répondu" doté de `aria-expanded`/`aria-controls`.
- **Décido — cartes "Type de sondage" sans état sélectionné visible.** Signalé directement par l'utilisateur (capture d'écran) : les 2 cartes de `/decido/creer` n'affichaient aucune différence visuelle entre l'état sélectionné et non sélectionné. Ajout d'une classe `.decido-poll-type-selected` (bordure + fond `var(--c-primary-light)`) plus un badge "✓ Sélectionné" (icône+texte, jamais la couleur seule).

Les 4 correctifs ci-dessus ont été trouvés par une passe adversariale indépendante (skill `/100`, round 6, angle WCAG 2.2 AAA + qualité du français + concurrence/données réelles). Un point supplémentaire a été signalé à l'utilisateur pour décision plutôt que corrigé unilatéralement : la suppression du compte créateur cascade la suppression intégrale d'un sondage, y compris les votes de tiers.

## [1.107.3] - 2026-07-16

### Fixed
- **Décido — injection de formule CSV (OWASP CSV Injection).** `voter_pseudonym`, texte libre contrôlé par un votant anonyme non authentifié, était écrit verbatim dans les cellules du CSV exporté par l'organisateur. Une valeur commençant par `=`, `+`, `-`, `@`, une tabulation ou un retour chariot est interprétée comme une formule active par Excel/Google Sheets à l'ouverture (ex. `=HYPERLINK(...)` pouvant exfiltrer des données). Nouvelle méthode `PollExportService::sanitizeCsvCell()` qui préfixe d'une apostrophe toute valeur à risque, appliquée à `voter_pseudonym` et `option->label`. Trouvé par une passe adversariale indépendante (skill `/100`, round 5).
- **Décido — aucun anti-abus sur la création de sondage ni le vote anonyme.** `decido.store` et `decido.vote.store` n'avaient aucune limite de fréquence, permettant en théorie un bourrage d'urnes (cookies `decido_voter_*` illimités) ou un spam de création de sondages. Ajout de `throttle:10,1` (création) et `throttle:20,1` (vote).
- **Décido — politique de rétention `expires_at` jamais appliquée.** Le champ était écrit à la clôture d'un sondage (`PollManageController::close()`) mais jamais relu nulle part ailleurs dans le module — aucune purge réelle ne se produisait malgré le commentaire de config l'annonçant. Nouvelle commande `decido:purge-expired` (pattern calqué sur `shorturl:cleanup-expired`), planifiée quotidiennement à 06h15 (`routes/console.php`).

## [1.107.2] - 2026-07-16

### Fixed
- **Décido — paramètres de génération de créneaux jamais persistés sur le sondage.** `duration_minutes`, `range_start_time`, `range_end_time` et `step_minutes` étaient validés et déjà présents dans `Poll::$fillable`, mais `PollManageController::store()` ne les assignait jamais à l'objet `$poll` avant sauvegarde — toujours `NULL` en base pour tout sondage de type date, bien que les créneaux eux-mêmes soient générés correctement (le service recevait les valeurs directement, pas via le modèle). Bloquait silencieusement toute fonctionnalité future de modification/régénération de créneaux. Trouvé par une passe adversariale indépendante (skill `/100`, round 4). Nouveau test Pest vérifie ces 4 colonnes après un vrai `fresh()` depuis la DB.
- **Décido — impasse UX : aucun lien vers la gestion d'un sondage depuis « Mes sondages ».** Un créateur de sondage connecté qui perdait le lien admin à jeton reçu à la création n'avait plus aucun moyen d'accéder à la gestion de son propre sondage, malgré un bypass propriétaire déjà présent dans `PollManageController::authorizeManage()` (`Auth::id() === $poll->creator_id`) — ce bypass n'était simplement jamais exploité par aucune vue. Ajout d'un bouton **« Gérer »** sur chaque ligne de la liste `/decido`, exploitant ce bypass existant. Vérifié visuellement (navigation réelle jusqu'à la page de résultats, 200, aucune erreur 403/404) et par un nouveau test Pest.

## [1.107.1] - 2026-07-16

### Fixed
- **Décido — fuseau horaire manquant dans `PollExportService::exportIcs()`.** Le même bug corrigé dans `results.blade.php` (v1.107.0) était aussi présent dans l'export ICS : `DTSTART`/`DTEND` utilisaient `->utc()` directement sur une valeur `Carbon` déjà mal étiquetée par le cast Eloquent (`config('app.timezone')` = `America/Toronto` réinterprète à tort la valeur UTC stockée comme étant déjà en heure de Québec), causant un décalage de 4h dans le fichier `.ics` téléchargé. Trouvé par une passe adversariale indépendante (skill `/100`), reproduit empiriquement (`20260801T180000Z` au lieu de `20260801T140000Z`), corrigé par reparse explicite de la valeur brute comme UTC. Nouveau test Pest asserte la valeur `DTSTART`/`DTEND` exacte après un vrai `fresh()` depuis la DB — condition nécessaire pour déclencher le bug, que l'ancien test ne couvrait pas.

## [1.107.0] - 2026-07-16

### Changed
- **Décido — refonte UX de la page de résultats (superadmin).** L'ancien design (une carte pleine largeur par créneau candidat, jusqu'à 16+ cartes empilées pour un sondage de dates = page extrêmement longue) est remplacé par une architecture en divulgation progressive : un résumé **« Meilleurs créneaux »** toujours visible en haut de page (tous les ex-æquo au meilleur score, avec le compte réel oui/peut-être/non/sans réponse — jamais un simple pourcentage isolé) avec un **drill-down interactif** (Alpine.js) qui affiche qui a répondu quoi sans avoir à ouvrir la grille complète, puis une section **« Comparer toutes les réponses »** repliée par défaut (élément HTML natif `<details>`, accessible clavier sans JS custom) contenant le tableau croisé complet (vrai `<table>` sémantique avec `<caption>` et `<th scope>`, colonnes groupées par jour pour un sondage de dates, en-têtes et première colonne figées, icônes + texte pour coder l'état — jamais la couleur seule, conforme WCAG 2.2 AAA). Design établi par recherche `pp_search` (bonnes pratiques listes longues et pattern Framadate, juillet 2026) puis validé indépendamment par Codex (93-96/100) et Gemini via `agy` (92/100), les deux convergeant sur la même architecture sans concertation.

### Fixed
- **Décido — en-têtes du tableau croisé affichaient l'heure en UTC brute au lieu de l'heure du fuseau du sondage** (ex. « 13h00 » au lieu de « 9h00 »), découvert lors de la vérification visuelle de la refonte ci-dessus. Cause racine : `config('app.timezone')` de l'application est `America/Toronto` ; `starts_at` est stocké en UTC par `SlotGenerationService`, donc le cast Eloquent `datetime` réinterprète à tort la valeur brute comme étant déjà en heure de Québec à la lecture (pas de conversion automatique) — un simple `->timezone()` appliqué sur cette instance déjà mal étiquetée ne changeait donc rien. Fix : reparser explicitement la valeur brute comme UTC (`Carbon::parse($valeur->format('Y-m-d H:i:s'), 'UTC')`) avant de convertir vers le fuseau du sondage.

## [1.106.0] - 2026-07-16

### Added
- **Nouvel outil Décido** (`Modules/Decido`, nwidart) : générateur de sondages type Framadate repensé au complet (aucun code Framadate réutilisé). Deux types de sondages : **sondage de dates** (l'organisateur choisit d'abord la durée de la rencontre, la plage horaire et le pas entre créneaux ; `SlotGenerationService` génère automatiquement tous les créneaux candidats à partir des dates proposées) et **sondage classique** (options libres, mode `single_choice` ou `approval`). Vote anonyme sans compte requis (identité par cookie signé `decido_voter_{public_id}`, UUID, `updateOrCreate` idempotent pour la revote). Gestion sans compte pour l'organisateur non plus : lien admin à jeton (`admin_token_hash` SHA-256, `hash_equals`), généré une seule fois et affiché une seule fois. Export CSV et ICS (RFC 5545 minimal, sans dépendance Composer) disponibles depuis la page de gestion, ICS uniquement après clôture avec créneau final choisi. Réservé aux utilisateurs connectés pour la création ; **en construction (503 + `noindex`, superadmin-only)** jusqu'à mise en ligne publique. 20 tests Pest (création, vote, revote, clôture, exports, permissions admin-token, gate under-construction).

### Fixed
- **Décido — `TypeError` sur la création d'un sondage de dates** : `SlotGenerationService::generateSlots()` déclare `int $durationMinutes`/`int $stepMinutes` (typage strict) mais `PollManageController::store()` transmettait directement les valeurs de `$request->validate(['duration_minutes' => 'integer', ...])`, qui restent des **strings** après validation (la règle Laravel `integer` valide le format, elle ne caste pas la valeur). Les tests Pest passaient des entiers PHP natifs directement au service et ne l'ont donc jamais détecté ; découvert seulement à la vérification visuelle Playwright (soumission d'un vrai formulaire HTML → POST `application/x-www-form-urlencoded` → toutes les valeurs sont des strings). Fix : cast `(int)` explicite au point d'appel dans le contrôleur.
- **Décido — validation du vote `yes_no_maybe` bloquait tout vote partiel.** Chaque créneau généré (potentiellement 16+ pour une seule journée) portait la règle `required`, forçant un votant à répondre Oui/Peut-être/Non à **tous** les créneaux avant de pouvoir soumettre — contraire au principe même de l'outil (répondre seulement aux créneaux pertinents), découvert en testant un vote réel via Playwright. Fix : règle par créneau passée à `sometimes`, avec `min:1` sur le tableau `votes` global pour continuer à refuser une soumission totalement vide.

## [1.105.1] - 2026-07-12

### Fixed
- **Bandeau `.wpo-breadcumb-area` (titre de page + fil d'Ariane, en haut de presque toutes les pages du site) prenait trop de place verticale.** `min-height: 400px` → `250px` (aligné sur la valeur déjà utilisée en mobile via media query `<767px`, désormais redondante et retirée). Vérifié visuellement via Playwright sur 2 gabarits (`/glossaire`, `/academie`) × 3 résolutions (desktop 1440px, tablette 768px, mobile 390px) : titre et fil d'Ariane restent bien centrés, aucun chevauchement avec le contenu qui suit.

## [1.105.0] - 2026-07-12

### Added
- **Consolidation des 3 widgets admin flottants en un seul menu déroulant.** Les pages publiques accumulaient jusqu'à 3 badges superposés pour un superadmin (badge+menu "⋮" `admin-bar`, toggle "Lecture/Édition" `mode-toggle`, pastille "Modifié il y a X" `admin-activity-mini`) — collision déjà documentée dans un commentaire de `table-of-contents.blade.php`. Le composant `Modules/Core/resources/views/components/admin-bar.blade.php` accepte désormais deux props optionnelles, `model` (ajoute une ligne d'information "Modifié il y a X · causer" dans le menu, si Activitylog est disponible pour le modèle) et `editUrl` (ajoute une bascule Lecture/Édition dans le menu, préservant exactement le mécanisme existant : `localStorage` clé `laveille.edit_mode`, classe `body.edit-mode`, script de délégation de clic sur `[data-editable]`). `admin-action-menu.blade.php` gagne deux nouveaux types d'item (`info`, `alpineClick`) pour supporter ces entrées sans dupliquer sa logique existante (wireClick/method+url/url restent inchangés).
- Appliqué sur les **11 pages publiques** qui affichaient au moins un des 3 anciens widgets ou auraient dû en afficher un : Glossaire, Actualités, Annuaire, Acronymes, Blog (widgets fusionnés), Journal, Livres, Académie (cours), Collections Annuaire, Outils (vue générique), mini-site Auteurs (widget ajouté). Sur Journal spécifiquement, **aucune bascule Lecture/Édition n'a été ajoutée** (choix délibéré) : un superadmin peut modérer/supprimer un journal mais plus l'éditer silencieusement (cf. correctif sécurité v1.104.0) — proposer un raccourci d'édition aurait contredit cette décision.
- Gate de la pastille "Modifié" resserrée de `@auth` (n'importe quel utilisateur connecté) à `@can('view_admin_panel')`, cohérent avec le reste du menu.
- Nouveau helper global `reading_time_minutes(?string $text): int` (`Modules/Core/app/Helpers/helpers.php`), centralise la formule `max(1, ceil(str_word_count(strip_tags($text)) / 200))` dupliquée à 3 endroits (`Modules/News/resources/views/public/show.blade.php`, `partials/article-card.blade.php`, `Modules/Authors/app/Livewire/AuthorEditor.php::computeReadingTime()`).

### Fixed
- **Le menu déroulant du profil (avatar, header) pouvait s'afficher tronqué/masqué derrière d'autres éléments flottants** (widget admin consolidé, onglets sticky de l'Académie, clone `.sticky-header` du script de scroll) — signalé par capture d'écran en cours de session. Cause racine : `.wpo-site-header .header-right { position: relative; z-index: 991 }` crée son propre contexte d'empilement CSS, ce qui plafonne tous ses enfants — dont le dropdown profil (`z-index: 9999` inline, `header.blade.php`) — à 991 face à n'importe quel élément `position: fixed` **hors** du header, indépendamment du z-index inline déclaré sur le dropdown lui-même. `z-index: 991` → `10000` (`public/themes/bloggar/sass/style.css`), confirmé par diagnostic Playwright réel (inspection des contextes d'empilement) puis par vérification visuelle avant/après scroll. Corrigé pour de bon un bug latent qui existait déjà avant l'ajout de l'admin-bar consolidé (les onglets Académie, présents depuis plus longtemps, provoquaient déjà la même collision).
- **`style.css` (thème Bloggar) n'avait aucun cache-bust** contrairement à `charte.css`/`components.css`/`fonts.css` — un visiteur ayant déjà ce fichier en cache n'aurait jamais reçu le correctif de z-index ci-dessus. Aligné sur le pattern `?v={{ filemtime(...) }}` déjà en place (`master.blade.php`).
- Régression introduite puis corrigée pendant la même session : une balise `@endauth` orpheline dans `Modules/News/resources/views/public/show.blade.php` (mon édition initiale avait retiré le `@auth` d'ouverture sans retirer le `@endauth` correspondant, situé après un bloc `@can` intercalé pour la capture d'écran assistée) cassait la compilation Blade de la page — détectée par la suite Pest (`NewsComicViewerTest`), corrigée, suite complète revérifiée à 0 échec (2280 passed, 209 skipped).

## [1.104.1] - 2026-07-12

### Fixed
- **Incident P0 production (2026-07-11) : 500 pour tout utilisateur connecté sur Actualités/Glossaire/Annuaire, cause racine complète et durcissement du pipeline CI.** Un fichier de migration (`Modules/News/database/migrations/2026_07_10_160000_backfill_auto_tool_detection.php`) avait été supprimé du dépôt git (commit `9502674a`) mais était resté physiquement présent en production, car le workflow `.github/workflows/deploy.yml` déploie via `rsync` sans le flag `--delete` : les fichiers retirés de git n'étaient jamais retirés du serveur. Ce fichier zombie contenait un `chunkById()` non borné qui faisait systématiquement timeout l'étape `php artisan migrate --force` à chaque déploiement — mais le `|| true` de cette étape (ajouté 2026-05-03, fix L15) avalait cet échec silencieusement depuis l'origine, empêchant TOUTES les migrations postérieures de s'exécuter, dont les 3 migrations du nouveau module Journal (2026-07-11) et la migration `add_review_tracking_to_reports_table` : le composant "+ Ajouter à mon journal", intégré sur ces trois familles de pages publiques, requêtait alors une table `journals` inexistante.
- Correctifs déjà appliqués directement en production (hors dépôt) avant ce commit : fichier de migration zombie neutralisé en no-op via cPanel, puis `php artisan migrate --force` rejoué manuellement avec succès (3 migrations Journal + 1 migration reports confirmées `DONE`).
- Durcissement `.github/workflows/deploy.yml` : retrait du `|| true` sur `php artisan migrate --force` (tout échec de migration fait désormais échouer le job CI visiblement, au lieu d'être masqué) + ajout d'un `timeout 300` (5 min) pour continuer à borner le risque qu'un futur backfill non borné bloque indéfiniment le pipeline, sans pour autant masquer l'échec. `--delete`/`--delete-after` sur rsync délibérément **NON activé** après audit : `public/fonts/` (police self-hébergée Caveat, v1.104.0) est présent en production, gitignoré localement et absent de la liste `--exclude` du rsync — l'activer aurait supprimé les polices en prod au prochain déploiement. Risque documenté en commentaire dans `deploy.yml` avec la marche à suivre pour l'activer un jour en sécurité (exclusions complémentaires + `--dry-run` obligatoire).

## [1.104.0] - 2026-07-12

### Added
- **Refonte visuelle "accents papier discrets" de la page publique du Journal** (`show.blade.php`) : police manuscrite self-hébergée `Caveat` (poids 600, latin+latin-ext, `public/fonts/caveat/`) appliquée uniquement à la date et aux citations, jamais au corps de texte ; papier ligné très subtil en fond des blocs (`repeating-linear-gradient`, opacité ~0.045) ; coin corné discret sur les photos du gabarit Carnet photo. Génération du CSS déléguée à `mcp__hermes__model_invoke` (Qwen3-max), validée et corrigée par revue avant intégration.
- **Migration complète des boutons du module Journal vers le composant DRY `<x-core::button>`** (4 vues) — remplace 25 boutons Bootstrap bruts par le composant tokenisé de la charte (focus AAA, variants primary/secondary/danger déjà éprouvés site-wide).

### Fixed
- **Sécurité — le superadmin pouvait éditer silencieusement le journal privé de n'importe quel utilisateur.** Le bypass global `Gate::before()` (`Modules/RolesPermissions`) accordait un accès total à toutes les policies, y compris `JournalPolicy::update()` qui n'avait volontairement aucune exception admin. Corrigé par une exclusion ciblée (ability `update` sur `Journal` uniquement) — le pouvoir de modération/suppression admin reste intact. Confirmé juridiquement pertinent par veille (Loi 25/PIPEDA/RGPD : l'édition non consentie de contenu personnel excède la finalité de modération légitime).
- **Assignation de rôle non-atomique et `email_verified_at` jamais posé sur connexion OTP** (`Modules/Auth/MagicLinkController`), trouvés par simulation E2E : un échec partiel de `assignRole()` laissait un compte orphelin sans rôle de façon permanente ; un utilisateur connecté uniquement par code OTP était bloqué par les routes gatées `verified` alors que le code prouve déjà la possession du courriel. Les deux corrigés (transaction DB + `email_verified_at` posé sur vérification OTP réussie), 31/31 tests Auth verts.
- **Bug de compilation Blade** : la directive `@js()` ne se compile pas correctement à l'intérieur d'un attribut de balise composant (`<x-core::button @click="...@js(...)...">`), cassait le bouton "Supprimer" de `/journaux`. Corrigé en pré-calculant via `{{ Illuminate\Support\Js::from(...) }}` (echo standard) au lieu d'imbriquer la directive.
- **Cache-bust manquant sur `fonts.css`** (`master.blade.php`) : les visiteurs ayant déjà ce fichier en cache ne recevraient jamais une police nouvellement ajoutée (repli silencieux sur `cursive` générique). Aligné sur le pattern `?v={{ filemtime(...) }}` déjà utilisé pour `charte.css`/`components.css`.
- 2 bugs de spécificité CSS trouvés par vérification visuelle (citation manuscrite écrasée par une règle globale du thème sur le `<p>` enfant généré par Tiptap ; couleur de la date écrasée par `.wpo-blog-single-section p`), corrigés par sélecteurs qualifiés/ciblage explicite.

### Verified
- Simulation E2E complète du module Journal (skill `/simulation`) : 4 rôles (guest, owner, other_user, admin) testés avec régression complète relancée après chaque correctif, jusqu'à un passage 100% propre sans aucune correction nécessaire. Anti-IDOR vérifié rigoureusement (URL directe, DELETE forgé, appel Livewire direct sur ressource étrangère) — tous bloqués correctement.

## [1.103.0] - 2026-07-11

### Added
- **Journal personnel** (nouveau module `Modules/Journal`) : chaque utilisateur connecté peut créer des journaux privés ou publiés (`/journaux`, `/journal/creer`, `/journaux/{slug}/editer`, `/journaux/{slug}`), composés de blocs de contenu réordonnables (texte riche, image, vidéo YouTube, source liée) via un constructeur Livewire (`JournalBuilder`) avec 4 gabarits de mise en page. Intégration « + Ajouter à mon journal » sur les pages Actualités, Glossaire et Annuaire (dropdown des journaux de l'utilisateur, ajout instantané par requête `fetch`, gate d'autorisation serveur anti-IDOR à chaque action). Page publique de lecture avec JSON-LD Article, réutilisation du système Signaler + extension du régime avis-et-avis (`/annuaire/retrait`) au contenu Journal. 33 tests Pest (modèle/policy, service de blocs, cycle de vie Livewire, HTTP/modération) — zéro régression sur 256 tests Journal+Directory+Authors.

### Fixed
- **Éditeur de texte riche (Tiptap) non fonctionnel dans le constructeur Journal.** Le panneau « + Texte » affichait une barre d'outils aux icônes vides puis, une fois corrigé, un éditeur complètement inerte (`ReferenceError: tiptapEditor is not defined`) : cause racine réelle = condition de course entre le chargement asynchrone du script de l'éditeur (`resources/js/tiptap-frontend.js`, module Vite) et le morph Livewire qui insère et évalue immédiatement le `x-data` Alpine correspondant, déclenché par le clic sur « + Texte » (contenu absent du rendu initial de la page, contrairement aux autres usages déjà en production de ce composant partagé sur Annuaire/Auteurs). Corrigé en chargeant le script au niveau racine du composant Livewire `JournalBuilder`, dès le rendu initial de la page d'édition — même mécanisme déjà éprouvé pour le plugin de réordonnancement par glisser-déposer dans ce même fichier.
- **Erreur 500 sur `/admin` en environnement local** (colonne `newsletter_subscribers.deleted_at` manquante) et **~150 migrations en retard sur la base de données de développement locale**, dont une table `dictionary_categories` jamais peuplée par une migration versionnée (seedée manuellement en production à l'origine) — reliquat d'une restauration incomplète après un incident `migrate:fresh` accidentel du 2026-07-04. Nouvelle migration idempotente et réversible qui comble définitivement cette lacune pour tout environnement futur (local neuf, CI). Aucun impact production (déjà correctement peuplée, migration sans effet si déjà appliquée).

## [1.102.0] - 2026-07-10

### Added
- **Auto-détection des outils annuaire à la publication d'une actualité.** Le bouton manuel « Suggérer les outils détectés » nécessitait une action admin ; les outils mentionnés dans une actualité sont désormais liés automatiquement dès la publication (`is_published` false→true, couvrant la publication auto par le cron `news:fetch` et la bascule manuelle admin), via `AutoDetectNewsToolsJob` (queue `news-tools`, calqué sur `PurgeCloudflareCacheJob`), déclenché depuis `NewsArticleObserver`. Les liaisons automatiques sont marquées `source=auto` en base et n'écrasent jamais une sélection manuelle existante (`NewsToolSyncAction::attachAuto()`, ajout pur) ; le bouton manuel reste disponible pour compléter/ajuster (`source=manual`, comportement inchangé). Worker de queue planifié (hébergement mutualisé sans démon, même convention que la queue `newsletters`) + commande manuelle bornée `news:backfill-auto-tools --limit=200` pour les actualités déjà publiées sans outil lié. 6 nouveaux tests Pest (24/24 verts sur le module News, aucune régression).

### Fixed
- **Incident de déploiement évité de justesse.** Une première version du backfill (migration non bornée) a bloqué le pipeline CI plus de 10 minutes sur le backlog réel de production. Run annulé avant toute réplication en base (transaction Laravel jamais validée) ; migration retirée au profit de la commande manuelle bornée ci-dessus, rejouable sans risque.

## [1.101.1] - 2026-07-10

### Added
- **Planche assemblée de la BD « Itération »** déployée sur `/glossaire/iteration` (`public/bd/iteration/`, formats avif/webp/jpg en 1600px, 1024px et miniature 600px, `manifest.json`). Standard `ComicLibrary`/`comic-viewer` déjà éprouvé (rançongiciel, deepfake, etc.) — contenu statique uniquement, aucun code touché.

## [1.101.0] - 2026-07-10

### Added
- **6 nouveaux termes de glossaire** : MTIA (puce IA custom de Meta), Broadcom, TSMC, AMD, PyTorch et DMA (Digital Markets Act, règlement européen — orthographe officielle vérifiée « Markets » au pluriel, ajoutée en `acronym_full` et en alias pour l'auto-lien site-wide). Standard 10 champs du skill `/glossaire` respecté (définition, analogie, exemple, anecdote, réponse en une phrase, FAQ, sources datées et signées, alias, icône, type/difficulté). Contenu rédigé via `mcp__hermes__model_invoke` à partir de faits vérifiés (recherche `pp_search`/fallback `sonar-pro`), images générées via `/nanobanana` (compte Gemini Workspace), migrations réversibles (`Modules/Dictionary/database/migrations/2026_07_10_*`).
- **Bande dessinée pédagogique « Itération »** (personnage Octopus) pour vulgariser `/glossaire/iteration` : 5 illustrations de case livrées (flux narratif définir → répéter → nommer l'époque → résumer) accompagnées du fichier `iteration-structure.md` (textes de bulles/encadrés fact-checkés). Conforme au périmètre resserré du skill `/bd` (2026-07-07) : images de contenu seules, sans contour ni bulle rendue, assemblage laissé à l'utilisateur.

### Fixed
- Investigation approfondie (round 2) du signalement « Service Worker was updated because 'Update on reload' » répété : confirmé qu'il ne s'agit pas d'une boucle serveur (5 minutes d'observation continue sans croissance des messages, aucun minuteur caché dans le code). La cause la plus probable est un comportement natif de Chrome DevTools (message émis à chaque reload réel tant que la case « Update on reload » est cochée), amplifié par « Preserve log ». Aucun correctif de code nécessaire.

## [1.100.0] - 2026-07-09

### Added
- **État de chargement du lecteur flip-reader avec LQIP, squelette et optimisation des priorités.** Sur signalement utilisateur de « pages blanches constantes » à l'ouverture des extraits, un état de chargement complet a été implémenté dans le composant flip-reader. La solution repose sur une veille des meilleures pratiques 2026 (squelette + blur-up + priorité de chargement) plutôt qu'un simple spinner générique, jugé moins performant pour un contenu à mise en page connue. Détails techniques : génération d'images LQIP (~40 px de large, ~4 Ko chacune via ImageMagick) pour les 97 pages d'extraits existantes (5 livres), affichées instantanément et floutées en attendant l'image nette avec un fondu CSS de 220 ms ; `Book::excerptPages()` (`Modules/Books/app/Models/Book.php`) retourne désormais une clé `lqip` par page (chemin ou null si absent), avec correction d'un bug réel au passage : le glob `page-*.jpg` comptait aussi les nouveaux fichiers `-lqip.jpg` comme des pages, désormais filtrés explicitement ; squelette shimmer ajouté au composant générique `Modules/FrontTheme/resources/views/components/flip-reader.blade.php`, désactivé automatiquement sous `prefers-reduced-motion` ; retrait de `loading="lazy"` sur l'image de la page actuelle (`fetchpriority="high"` à la place), ce lazy-load étant inapproprié pour du contenu déjà à l'écran ; `aria-busy` sur la case en cours de chargement et annonce `aria-live` sobre (« Chargement de la page… », reprend le compteur de pages une fois chargée), sans duplication du mécanisme d'annonce existant. Vérifié visuellement avec un réseau ralenti simulé (CDP) confirmant le bon affichage du squelette et du flou LQIP pendant le chargement sur deux livres différents ; navigation clavier/souris et absence de rognage (`object-fit:contain`) reconfirmées sans régression. 12/12 tests Pest verts (3 nouveaux).

## [1.99.1] - 2026-07-09

### Fixed
- **Régression visuelle sur le lecteur flip-reader (page rognée sur grands écrans).** Le correctif précédent (v1.99.0) avait résolu le clic souris bloqué sur le bouton "Page suivante" mais avait introduit une régression non détectée : sur fenêtres hautes (ex. 1717x1151), le titre de la page 1 apparaissait rogné en haut. Cause : `.fpr-book` combinait `width:100%` explicite avec `aspect-ratio` et `max-height:100%`, or l'algorithme CSS "transferred size" ne réduit la largeur que si `width` est `auto`. La hauteur était plafonnée mais la largeur restait à 900px, créant une boîte 900x1063 au lieu de 708x1063, et `object-fit:cover` rognait le haut/bas. Tentative de `width:auto` (boîte effondrée à 0x0, aucune dimension pour amorcer aspect-ratio). Correctif final (`Modules/FrontTheme/resources/views/components/flip-reader.blade.php`) : `.fpr-book` utilise `width:100%; height:100%; max-width:900px` sans `aspect-ratio` ; l'image passe en `object-fit:contain` (plus de rognage) ; StPageFlip en mode `stretch` préserve son ratio en interne. Vérifié par mesures DOM et captures sur 2 tailles de fenêtre (1717x1151, 1280x800) et 2 livres à ratios de page différents : plus aucun rognage, clic souris toujours fonctionnel. 9/9 tests Pest verts.

## [1.99.0] - 2026-07-09

### Fixed
- **Lecteur flip-reader : bouton "Page suivante" inaccessible.** Le lecteur "feuilleter" livré en 1.98.0 présentait un bug bloquant au clic souris : le bouton "Page suivante" (›) devenait injoignable (timeout Playwright confirmé, utilisateur signalant "impossible de lire les pages de prévisualisation"). La cause racine, identifiée par mesure DOM directe (`document.elementFromPoint` aux coordonnées du bouton retournait la balise IMG, pas le bouton), venait de l'absence de `max-height` sur `.fpr-book`. Un simple `aspect-ratio` dérivait la hauteur de la largeur : pour des pages portrait dans la modale (scène de hauteur fixe), le livre calculait une hauteur supérieure à l'espace disponible et débordait symétriquement (centré par le flex parent) par-dessus la barre de navigation `.fpr-bar`. Correction (`Modules/FrontTheme/resources/views/components/flip-reader.blade.php`, CSS uniquement) : ajout de `max-height: 100%` sur `.fpr-book` (force le navigateur à contraindre aussi la largeur via l'algorithme de "transferred size" de `aspect-ratio`, comme un `object-fit: contain`), plus des `z-index` explicites (`.fpr-bar` à 2, `.fpr-stage` à 1) en filet de sécurité pour garantir la cliquabilité de la barre au-dessus de tout contenu injecté par StPageFlip. Revérifié par clics souris réels (pas seulement au clavier) sur 2 livres à ratios de page différents : navigation avant et arrière fonctionnelle sur plusieurs essais consécutifs. 9/9 tests Pest toujours verts.

### Changed
- **Titre de section catalogue : "Essais" remplacé par "Guides pratiques".** Sur demande de l'utilisateur, le titre de section du catalogue `/livres` passe de "Essais" à "Guides pratiques" (`Modules/Books/resources/views/public/index.blade.php`), un intitulé jugé plus accessible que le terme littéraire "essais" pour désigner les 2 livres pratiques (conformité IA pour PME, parentalité numérique). La section "Fiction" (trilogie Nexus Neural) n'est pas touchée.

## [1.98.1] - 2026-07-09

### Fixed
- **La librairie StPageFlip vendorisée (flip-reader) ne se déployait jamais en prod (404).** Le pipeline `.github/workflows/deploy.yml` exclut `vendor/` du rsync pour ne jamais copier le vrai dossier `vendor/` composer, mais le motif n'était pas ancré à la racine (`vendor/` au lieu de `/vendor/`) - il excluait donc aussi `public/vendor/page-flip/`, livré en 1.98.0. Détecté par vérification directe en production (`curl` sur `page-flip.browser.js` -> 404) après le déploiement de 1.98.0. Corrigé en ancrant le motif (`--exclude='/vendor/'`), aucun impact sur l'exclusion du vrai `vendor/` composer.

## [1.98.0] - 2026-07-09

### Added
- **Nouveau lecteur "feuilleter" (flip-reader) intégré dans l'onglet Extrait des fiches livre.** Composant Blade générique et réutilisable `Modules/FrontTheme/resources/views/components/flip-reader.blade.php` avec partial partagé `partials/flip-reader-body.blade.php` (modal/inline, zéro duplication), basé sur la librairie StPageFlip vendorisée localement à `public/vendor/page-flip/page-flip.browser.js` (npm pack, aucun CDN externe pour respecter la Content-Security-Policy). Nouveau helper `Book::excerptPages()` qui scanne `public/images/livres-extraits/{slug}/page-*.jpg` (tri naturel, dimensions lues via getimagesize) et affiche 15 à 26 pages par livre (couverture, table des matières, extraits de chapitres réels) générées depuis les dernières versions vérifiées des manuscrits sources (deux corrections de fraîcheur appliquées : Livre 1 utilisait un PDF du 7 mai remplacé par la version du 1er juillet avec différences de contenu réelles ; Tome 1 utilisait un PDF du 26 décembre remplacé par la version du 5-6 janvier avec conversion typographique dialogue tiret vers guillemets). Accessibilité complète : navigation clavier (flèches, Home/End, Échap avec restauration du focus), mode simplifié automatique si `prefers-reduced-motion` ou échec de chargement de la librairie, annonce `aria-live="polite"` sobre (uniquement au changement de page), cibles tactiles 44x44px, contrastes WCAG AAA (8,81:1 à 18,65:1). Composant volontairement générique (props: pages/triggerLabel/title/mode/downloadable) sans concept de "livre" en dur, prévu pour une réutilisation future (lecteur d'actualités/glossaire).

### Changed
- **CTA "version papier" passé en primaire pour les 5 livres.** Auparavant Kindle était primaire pour la trilogie Nexus Neural, changé sur demande explicite (le papier est le format préféré des lecteurs).
- **Fil d'ariane : l'entrée "Livres" est désormais cliquable partout.** Ajout dans la table `$breadcrumbRoutes` de `Modules/FrontTheme/resources/views/partials/breadcrumb.blade.php`, corrige automatiquement tous les usages.
- **9/9 tests Pest verts** (`BooksLibraryTest.php`, 3 nouveaux tests ajoutés pour le compte de pages d'extrait et la présence du bouton du lecteur).

## [1.97.2] - 2026-07-09

### Fixed
- **« Pourquoi lire ce livre » déplacé du système d'onglets vers le hero.** Sur demande de l'utilisateur, ce bloc doit être visible immédiatement, sans interaction - déplacé dans la colonne droite du hero (entre le paragraphe auteur et le CTA), avec un nouveau titre `h2` au contraste ~18:1 (AAA). Les onglets passent de 5 à 4 (Extrait, Structure, Auteur, FAQ), avec Extrait comme onglet actif par défaut. L'ancien override CSS mobile qui inversait l'ordre corps/couverture a été retiré - l'ordre DOM naturel (couverture → titre/sous-titre/auteur → Pourquoi lire → CTA) suffit désormais. 6/6 tests Pest verts, aucune adaptation nécessaire.

## [1.97.1] - 2026-07-09

### Fixed
- **Deux problèmes signalés sur la fiche livre suite à la refonte 1.97.0.** (1) Le bandeau « Trilogie Nexus Neural » s'était retrouvé entre le hero et la section « Pourquoi lire », créant un grand espace vide - déplacé après le premier bloc CTA, « Pourquoi lire » suit désormais directement le hero sans rien entre les deux. (2) Remplacement du sommaire flottant par ancres par de **vrais onglets ARIA** (`role="tablist"/"tab"/"tabpanel"`, `aria-selected`, navigation clavier flèches gauche/droite) pour les sections Pourquoi lire (actif par défaut), Extrait, Structure, Auteur et FAQ - un seul panneau visible à la fois, mais les 5 panneaux restent présents dans le HTML brut (masquage CSS uniquement, pas de chargement AJAX) pour préserver le SEO/AEO. Correction additionnelle : couleur du texte des onglets inactifs ajustée de `#6B7280` (contraste 4,83:1, AA) à `#4B5563` (7,55:1, AAA). 6 tests Pest verts, vérifié desktop et mobile.

## [1.97.0] - 2026-07-09

### Added
- **Refonte de l'ordre de la page fiche livre (`/livres/{slug}`).** Suite à une veille `pp_search` (best practices pages de vente de livres, juillet 2026) : pour un livre conceptuel d'un auteur moins connu, le CTA doit rester tôt, mais la section « Pourquoi lire ce livre » doit arriver immédiatement après le hero - les onglets classiques qui cachent du contenu sont déconseillés pour une fiche livre (nuisent à la découvrabilité et à l'indexation AEO/GEO), un sommaire flottant par ancres est recommandé. Nouvel ordre : hero compact (couverture/titre/sous-titre/auteur, sans gros bloc CTA) → « Pourquoi lire ce livre » → 1er bloc CTA principal → sommaire flottant par ancres (réutilisation du composant DRY `x-fronttheme::table-of-contents`, déjà utilisé sur le blog et l'Académie) → reste de la page inchangé (preuve, extrait, structure, auteur, FAQ, CTA final) → nouveau bandeau CTA sticky sur mobile (contraste AAA 9,35:1, cible tactile 44px). Bug découvert et corrigé en cours de route : le widget « Gérer les témoins » chevauchait le bandeau sticky mobile, corrigé par une règle CSS scopée à cette page. 6/6 tests Pest verts, navigation inter-tomes toujours fonctionnelle.

### Fixed
- **Catalogue `/livres` - cartes Essais en pleine largeur avec espace vide.** Les 2 cartes de la section « Essais » s'empilaient à 100 % de largeur, laissant un espace disproportionné sur grand écran. Corrigé par une grille CSS (`display:grid`, `repeat(auto-fit, minmax(360px,1fr))`) donnant 2 cartes côte à côte sur desktop et un repli naturel à 1 colonne sur mobile - la section « Trilogie Nexus Neural » n'était pas touchée (déjà en grille).
- **Couvertures Nexus Neural avec filigrane Gemini visible.** Les 3 couvertures de la trilogie portaient un filigrane Gemini (aucune version propre trouvée dans les dossiers sources locaux après recherche exhaustive). Remplacées par les couvertures officielles récupérées depuis les fiches produit Amazon en direct (1600×2560, éditions françaises), confirmées sans filigrane, régénérées en 4 variantes pour les 3 tomes.

## [1.96.3] - 2026-07-09

### Fixed
- **Recherche `/annuaire` donnait l'impression de recharger la page.** Diagnostic Playwright : ce n'était pas une vraie navigation (aucune requête réseau de navigation, aucun `beforeunload`), mais un jank causé par le champ Alpine.js `x-model="search"` sans debounce, qui recalculait le filtrage/tri/rendu d'environ 391 outils à chaque frappe. Corrigé par l'ajout de `.debounce.200ms` sur le `x-model` (`Modules/Directory/resources/views/public/index.blade.php`) - la saisie reste instantanée, seul le filtrage est différé de 200 ms. Vérifié par test Playwright (focus/valeur intacts, aucune requête répétée) et 26/26 tests Pest du module Directory, aucune régression. Deux problèmes secondaires signalés dans les logs (bruit console CSP/AdSense, 404 favicons Google pour 2 outils) ont été investigués et confirmés sans lien avec ce bug - non corrigés dans cette passe, documentés pour plus tard.

## [1.96.2] - 2026-07-09

### Fixed
- **Fuite mineure de défense en profondeur (règle CSS `.nw-shared-dot`).** Vérification post-déploiement de 1.96.1 : la règle CSS `.nw-shared-dot` (composant `admin-shared-dot.blade.php`) était poussée via `@once @push('styles')` **avant** la vérification `isSuperAdmin()`, la rendant visible dans la balise `<style>` du HTML pour tout visiteur anonyme - aucune donnée sensible n'était exposée (ni `shared_at`, ni article), mais cela ne respectait pas l'exigence "zéro trace dans le HTML pour un non-admin". Corrigé en déplaçant le bloc `@once`/`@push` à l'intérieur du `@if(isSuperAdmin())`. Vérifié : compilation Blade OK, 10/10 tests Pest `NewsArticleShareTrackingTest` toujours verts, `curl` en production confirme l'absence totale de `nw-shared-dot` dans le HTML anonyme.

## [1.96.1] - 2026-07-09

### Fixed
- **Point rouge admin-only "déjà publié" manquant sur la liste publique des actualités.** Le point rouge livré en 1.96.0 sur la fiche individuelle et la liste admin manquait sur la grille de cartes publique `/actualites`, créant une incohérence pour les admins qui parcourent la liste. Ajouté dans le partial `article-card.blade.php` et refactorisé en composant Blade partagé `x-news::admin-shared-dot` pour éliminer la duplication du markup Alpine/aria - réutilisé maintenant sur la fiche individuelle et la liste publique (la liste admin garde son propre markup statique préexistant). Vérifié par 10/10 tests verts (2 nouveaux : présence pour superadmin après marquage, absence totale du HTML pour un visiteur anonyme même avec des données en base) et 99/99 sur toute la suite News (230 assertions), zéro régression.

## [1.96.0] - 2026-07-09

### Added
- **Glossaire — nouveau terme "PinPoint Test".** Test sanguin de dépistage/triage du cancer basé sur l'IA (machine learning), utilisé dans le NHS (Royaume-Uni). Analyse ~30-33 biomarqueurs sanguins routiniers combinés à des données démographiques (âge, sexe) dans un modèle entraîné sur plus de 370 000 patients (jeu rétrospectif), avec un suivi prospectif de 17 000 patients sur 5 ans. Logiciel de diagnostic in vitro (Software IVD) réglementé CE/UKCA, utilisé comme outil de triage pour 9 groupes de cancers (sein, gynécologique, hématologique, tête et cou, gastro-intestinal haut et bas, poumon, peau, urologique) - un outil d'aide à la décision, pas un substitut au diagnostic clinique. 3 sources vérifiées (BMJ Open 2022, Pinpoint Data Science 2026, AI News 2026). Image générée via `/nanobanana`.
- **Actualités — point rouge admin-only "déjà publié" sur LinkedIn/Facebook.** Quand un admin clique "Post LinkedIn" ou "Post Facebook" (menu de copie presse-papier existant, aucun appel API externe), un point rouge apparaît désormais avant le titre de l'actualité (page publique et liste admin), indiquant que le texte de partage a déjà été copié pour ce réseau. Nouvelles colonnes `linkedin_shared_at`/`facebook_shared_at` sur `news_articles`. Le tracking a été ajouté de façon générique et rétrocompatible dans le composant partagé `Modules/Core/admin-copy-menu.blade.php` (clé optionnelle `track_url` par item, zéro impact sur les 3 autres usages du composant - Acronyme/Terme/Outil/Article). Une route POST admin-only (`isSuperAdmin` strict, liste blanche de plateformes, idempotente) marque le timestamp ; le point se met à jour instantanément sans recharger la page. Vérifié : le point et les données de tracking sont totalement absents du HTML pour un visiteur non-admin, même si les champs sont remplis en base - et l'indicateur porte un `aria-label`/`title` explicite (pas de couleur seule). 72 tests Pest verts (8 nouveaux + 64 régression).

## [1.95.0] - 2026-07-09

### Added
- **Bibliothèque de livres `/livres` (nouveau module `Modules/Books`).** Catalogue + fiche riche par livre, calqué sur le module Dictionary (modèle `Book` avec `HasPublishedState`/`Searchable`, `BookSchemaService` générant un JSON-LD `@graph` `Book`+`Offer[]`+`BreadcrumbList`+`FAQPage`+`Person`). 5 livres publiés : "L'IA sans se faire poursuivre" et "L'IA pour les parents" (essais), trilogie "Nexus Neural" (3 tomes de science-fiction). Chaque fiche est optimisée SEO/AEO/GEO : hero avec 2 CTA (papier/Kindle vers Amazon), bénéfices, extrait, structure/table des matières, biographie de l'auteur, FAQ de 5 à 10 questions - toutes les données (prix, ASIN, ISBN, disponibilité) ont été vérifiées en direct sur Amazon via Playwright avant la rédaction, aucune donnée inventée. Navigation cliquable ajoutée entre les 3 tomes de la trilogie (badge "Tome N/3", tome courant non cliquable avec `aria-current`). Correctif inclus : les boutons d'achat étaient repoussés sous la ligne de flottaison mobile (390px) par l'ordre du flex du hero - corrigé par un `order` CSS scopé au module. La section est techniquement en ligne mais invisible au public : middleware `BooksUnderConstruction` (503 pour tout visiteur non-superadmin, piloté par `BOOKS_UNDER_CONSTRUCTION`) + `@section('page_noindex')` en défense en profondeur. Aucun lien de menu ajouté - la section reste invisible tant qu'elle n'est pas activée explicitement. 6 tests Pest verts (gate 503/200, contenu, JSON-LD, 404 propre sur slug inexistant).

### Fixed
- **Icône "réinitialiser le zoom" du visionneur BD minuscule/difforme.** Le bouton utilisait le caractère Unicode `⟳` (U+27F3), mal supporté par les polices système, ce qui le rendait visuellement cassé comparé aux autres icônes du même bandeau (`-`, `+`, `‹`, `›`, `⬇`, `✕`). Remplacé par une icône SVG inline 18×18px (`stroke="currentColor"`, style refresh/rotate cohérent avec Feather/Lucide). Vérifié visuellement (icône désormais cohérente en taille et en poids avec les autres) et 9 tests Pest du module Dictionary toujours verts.

## [1.94.4] - 2026-07-08

### Fixed
- **Visionneur BD ne naviguait pas entre les planches multi-pages.** Le composant `comic-viewer.blade.php` utilisait `$planche = $comic['planches'][0] ?? null` pour l'ensemble du rendu de la lightbox, limitant l'affichage à la première planche du manifest.json. En production, la BD deepfake (2 pages) ne permettait pas d'accéder à la seconde planche, malgré l'annonce du README. Correctif : le composant charge désormais le tableau complet des planches en JSON dans l'état Alpine.js, avec un index de page courant, des boutons précédent/suivant, un compteur "X / Y" (affiché seulement si plus d'une planche), une navigation clavier (PageUp/PageDown, virgule/point) et un lien de téléchargement pointant vers la planche affichée. Le zoom/pan/fit existant reste intact. 9 tests Pest verts (module Dictionary), dont un nouveau test vérifiant le rendu de la navigation multi-planches sur la BD deepfake réelle.

## [1.94.3] - 2026-07-08

### Added
- **Glossaire — BD pédagogique "Octopus face au deepfake"** (2 planches). Ajout d'une nouvelle bande dessinée pédagogique de deux pages sur le glossaire `/glossaire/deepfake`. La page 1 explique le deepfake (définition, réalisme, mécanisme d'IA, menaces et arnaques) ; la page 2 présente des mesures de protection (mot de passe familial, règle des 10 minutes, vérification de la source), sourcées via la veille pp_search de juillet 2026. Les personnages sont Octopus (héros), Hibou (mentor), Enfant (novice) et Pirate (menace). La BD a été produite le 2026-07-07 via le nouveau workflow `/bd` : Claude Code a généré les 8 images de case (skill nanobanana/Gemini), l'utilisateur a assemblé bulles, encadrés, branding et QR code dans son propre outil. Déployée dans `public/bd/deepfake/` (manifest.json décrivant les 2 planches, fichiers avif/webp/jpg + variante 1024 + miniature par page). La détection automatique par `ComicLibrary` ajoute un bouton "Lire la BD" sur la fiche glossaire. Un défaut de forme de bulle de pensée interdite sur une case a été corrigé par régénération ciblée de cette seule case.

## [1.94.2] - 2026-07-08

### Fixed
- **Service Worker interceptait /admin/* et tous les POST Livewire — lenteur sur /admin/users.** Le scope site-wide `/` du Service Worker captait aussi le backoffice et enveloppait CHAQUE requête POST (dont `/livewire/update`, utilisé par tout composant interactif) dans un `BackgroundSyncPlugin` (file de retry 24h, prévu pour de vrais formulaires hors-ligne, pas pour l'AJAX temps réel Livewire) — d'où l'attente perçue entre chaque sélection sur `/admin/users`. Des requêtes cross-origin (ex. AdSense) tombaient aussi dans le handler par défaut du SW, provoquant des erreurs réseau en console. Corrigé par 3 routes `NetworkOnly` passthrough prioritaires dans `sw-source.js` (avant les routes de cache) : `/admin/*`, `/livewire/*`, et tout cross-origin — zéro interception, zéro cache, zéro background sync sur ces requêtes.

## [1.94.1] - 2026-07-08

### Fixed
- **Conflit de scope Service Worker — rechargements infinis, surtout /actualites.** `/sw-authors.js` (mini-site auteur `/@slug`) était enregistré sans scope explicite, héritant du scope racine `/` identique au Service Worker vite-pwa principal (déjà widened via `Service-Worker-Allowed`). Résultat : ping-pong install/activate à chaque navigation entre pages publiques et mini-sites, visible côté DevTools comme "Service Worker was updated because 'Update on reload' was checked" s'incrémentant indéfiniment. Corrigé par un scope explicite `{scope: '/@'}` (`Modules/Authors/resources/views/mini-site/show.blade.php`) + un nettoyage rétroactif dans `resources/js/pwa.js` qui désenregistre toute ancienne registration `sw-authors.js` au scope racine, pour les visiteurs déjà affectés.

## [1.94.0] - 2026-07-07

### Added
- **Glossaire — BD pédagogique "Octopus et le rançongiciel".** Nouvelle bande dessinée sur `/glossaire/rancongiciel` (personnage Octopus, 6 cases : chiffrement des fichiers, WannaCry 2017, hameçonnage, rançon en cryptomonnaie, sauvegardes hors ligne, ne jamais payer). Déployée via `public/bd/rancongiciel/` (manifest.json + avif/webp/jpg/thumb), détectée automatiquement par `ComicLibrary` (bouton "Lire la BD" sur la fiche). Premier livrable du nouveau workflow `/bd` (2026-07-07) : Claude Code génère les images de case, l'utilisateur assemble bulles/encadrés/contours/branding.

## [1.93.0] - 2026-07-07

### Added
- **Glossaire — nouveau terme AlphaFold.** Systèmes d'IA de Google DeepMind qui prédisent la structure 3D des protéines (CASP13 2018, percée AlphaFold2 CASP14 2020, AlphaFold3 2024 pour les complexes biomoléculaires) - prix Nobel de chimie 2024 attribué à Demis Hassabis et John Jumper, partagé avec David Baker. Lien bidirectionnel avec le terme existant "transformer" (architecture Evoformer). Image générée via `/nanobanana`.

## [1.92.0] - 2026-07-07

### Added
- **Glossaire — 3 nouveaux termes.** JadePuffer (premier rançongiciel entièrement autonome piloté par un agent LLM, Sysdig Threat Research Team, juillet 2026), Cybermenaces (terme umbrella liant 15 termes de menaces déjà présents, taxonomie ENISA/ANSSI/CISA), Bitcoin (réseau monétaire décentralisé, Satoshi Nakamoto 2008-2009). Images générées via `/nanobanana`. Graphe de connaissances bidirectionnel construit (broader/narrower_slugs).

## [1.91.0] - 2026-07-06

### Fixed
- **Glossaire — 13 images manquantes.** Comparaison du sitemap public (446 URLs) contre le listing réel des fichiers en production a révélé 13 termes publiés sans aucune image (applescript, blindspot-pass, fable-5, fate-h-fate-x, interface-pam, javascript, lean-4, leanstral, licence-apache-2-0, minif2f, putnambench, thariq-shihipar, unknown-unknowns). Images générées via `/nanobanana` (Gemini), compressées 1200x669 (jpg+webp), `hero_image` mis à jour via migration réversible.

## [1.90.0] - 2026-07-06

### Added
- **PWA Académie — raccourci manifest.** Ajout de "Académie" aux raccourcis PWA (parité avec Actualités/Répertoire/Glossaire/Outils).

### Fixed
- **Scope du service worker PWA limité à `/build/` (site-wide).** Le service worker (vite-plugin-pwa) n'était en réalité enregistré et actif que sur les fichiers sous `/build/` - aucune page du site n'était contrôlée ni mise en cache hors-ligne, malgré la stratégie NetworkFirst configurée dans le code source du SW. Corrigé via `scope:'/'` (vite.config.js) + en-tête `Service-Worker-Allowed: /` (public/.htaccess) - les deux mécanismes sont nécessaires ensemble pour élargir le scope au-delà du répertoire du fichier SW.

## [1.89.0] - 2026-07-06

### Added
- **Minuteur visuel — mise en ligne publique.** L'outil `/outils/minuteur-visuel`, développé et affiné en gate superadmin-only depuis son introduction, est maintenant public. Levé après régression complète du module Tools (33 tests verts) et vérification de l'accès invité (plus de gate "En construction", présence confirmée dans `/outils`).

## [1.88.0] - 2026-07-06

### Added
- **Minuteur visuel — durée personnalisée en secondes.** Le champ "Durée personnalisée" accepte désormais un champ Secondes (0-59) en plus des Minutes, permettant des durées comme "1 min 30 s" ou "45 s" seules. Les durées épinglées et le partage d'URL (`?minutes=X&seconds=Y`) suivent le même format ; les anciens liens `?minutes=X` restent identiques (rétrocompatibilité vérifiée).

## [1.87.5] - 2026-07-06

### Fixed
- **Collision CSS site-wide `.ct-btn` (composant `x-core::button`).** Un composant Blade du module Core injectait un style global redéfinissant `.ct-btn` (bordure 1px, rayon 0.75rem), collisionnant silencieusement avec `.ct-btn-outline`/`.ct-btn-primary` de la charte graphique (bordure 2px, rayon 0.5rem) dès que les deux coexistaient sur une même page - signalé via le chip "durée épinglée" du minuteur visuel (ligne intérieure visible + contour disproportionné). Corrigé en renommant toutes les classes du composant Core en `core-btn`/`core-btn--xxx` (zéro collision possible). En complément, le chip du minuteur a été redesigné en bordure unique portée par le conteneur (pattern 2026 confirmé), immunisé contre toute collision future similaire.

## [1.87.4] - 2026-07-06

### Fixed
- **Minuteur visuel — texte "X minutes restantes" redondant sous le cadran.** Ce texte était en fait une annonce ARIA (`aria-live="polite"`) pensée pour les lecteurs d'écran, mais affichée visuellement alors qu'elle dupliquait le chiffre mm:ss déjà visible en continu au centre du cadran. Masqué visuellement (pattern sr-only standard), l'annonce reste fonctionnelle pour les lecteurs d'écran.

## [1.87.3] - 2026-07-06

### Fixed
- **Minuteur visuel — les fonctions personnalisées prenaient encore trop de place.** Le disclosure « Favoris, couleur par défaut, récentes » (v1.87.2) a été fusionné directement dans le panneau « Réglages », renommé « Réglages et personnalisations », organisé en 4 sous-sections groupées (🎨 Personnalisation des couleurs, ♿ Accessibilité, 🍅 Minuteur Pomodoro, 🚦 Feu de circulation), visibles selon le style actif. Décision confirmée par veille pp_search 2026 : un seul accordéon avec sous-sections légères plutôt que plusieurs tiroirs empilés ou des onglets imbriqués. Aucune fonctionnalité perdue.

## [1.87.2] - 2026-07-05

### Fixed
- **Minuteur visuel — bloc couleur beaucoup trop haut avant le cadran.** L'ajout incrémental des favoris (étoile), de la couleur par défaut du compte et de l'historique récent empilait chacun sa propre rangée toujours visible, portant le bloc à 4-5 rangées (~200px) avant même d'atteindre le cadran. Consolidé dans un disclosure natif replié par défaut (« ★ Favoris, couleur par défaut, récentes »), calqué sur le pattern « Réglages » déjà présent sur la page : 28px replié contre ~200px avant, aucune fonctionnalité perdue.

## [1.87.1] - 2026-07-05

### Fixed
- **Minuteur visuel — bouton × des chips épinglés (durées et couleurs favorites) redevenu un rond flottant.** Une règle CSS globale du site ciblant tout élément dont l'attribut `aria-label` contient « Retirer » (pensée pour un bouton vote/soutenir ailleurs sur le site, en `!important`) entrait accidentellement en collision avec nos boutons ×, qui utilisent le même mot pour l'accessibilité. Corrigé en renforçant la spécificité de nos sélecteurs CSS sans toucher à la règle globale.

## [1.87.0] - 2026-07-05

### Added
- **Minuteur visuel — couleur par défaut du compte (connectés)** : bouton « Définir comme couleur par défaut » près du sélecteur de couleur, sauvegarde la teinte active (curatée ou personnalisée) comme défaut multi-appareils. S'applique automatiquement sur tout nouvel appareil ou navigateur connecté n'ayant encore fait aucun choix de couleur local, sans jamais écraser une personnalisation déjà faite sur un appareil existant.

## [1.86.1] - 2026-07-05

### Fixed
- **Minuteur visuel — seuils du feu de circulation : confirmation visible manquante.** Les 3 boutons de profils fonctionnaient réellement (préférence bien appliquée et persistée), mais le feu de circulation reste vert tant que le décompte n'a pas commencé, donc cliquer un profil ne changeait visiblement rien avant le démarrage du minuteur. Ajout d'une confirmation textuelle immédiate à côté des boutons, indépendante de l'état du feu.

## [1.86.0] - 2026-07-05

### Added
- **Minuteur visuel — couleurs favorites épinglables (connectés)** : jusqu'à 2 couleurs favorites via une étoile ☆/★, bascule explicite (même comportement que les durées épinglées), distinctes de l'historique roulant automatique des couleurs personnalisées récentes.
- **Minuteur visuel — seuils du feu de circulation configurables (connectés)** : 3 profils préréglés en un clic (Standard 50 %/20 %, Alerte précoce 70 %/40 %, Sprint final 30 %/10 %) + repli « Personnalisé » (2 champs en pourcentage). Option retenue après veille : hybride préréglés + champs numériques, plus simple et plus fiable qu'un double curseur de plage.

### Changed
- **Minuteur visuel — retrait de la pulsation du style Chiffres** : l'effet de zoom (scale) déclenché à chaque seconde de décompte, jugé fatiguant par un utilisateur, a été retiré (anti-pattern UX confirmé par veille : le changement du chiffre suffit déjà comme signal, sans animation supplémentaire).

## [1.85.0] - 2026-07-05

### Changed
- **Minuteur visuel — palette de couleurs élargie à 6 teintes** : retrait de « Orange » (une rouille perçue comme un second rouge redondant avec le rouge classique TimeTimer), ajout de « Rose poudré » (#E8A9AE) et « Sable pâle » (#DCC3A0), deux teintes pâles tendance 2026 confirmées par veille. Le contraste du texte affiché reste calculé automatiquement (WCAG AAA) sur les 3 styles supportant la palette (disque, anneau, chiffres).
- **Minuteur visuel — bouton de retrait d'une durée personnalisée épinglée redessiné** : l'ancien petit rond flottant (18x18px, hors du cadre du bouton, sous le seuil de cible tactile WCAG) est remplacé par un segment intégré à même la pastille (28x28px), pattern chip « dismissible tag » (Material 3/shadcn) plus lisible et tendance 2026.

## [1.76.9] - 2026-07-04

### Changed
- **Renommage « Glossaire IA » → « Glossaire Techno »** (décision produit) : changement de libellé site-wide (menu, fil d'Ariane, pied de page, pages piliers SEO, module Dictionary, `llms.txt`, infolettre, admin). Aucun changement de schéma DB ni d'URL (`/glossaire` inchangé).

## [1.71.0] - 2026-07-01

### Added
- **Académie — Tuteur IA : fenêtre d'accès + quota + rappel** (recommandation de veille juillet 2026). Le formateur peut limiter (optionnel) la durée pendant laquelle un apprenant peut utiliser le tuteur IA d'un cours (aucune fenêtre, X jours après l'inscription, X jours après le lancement du cours, ou date fixe) et/ou un quota mensuel de questions, réglables à tout moment dans l'éditeur de cours. Le contenu du cours reste **toujours** accessible, même après la fin de l'accès au tuteur. Un rappel calme est envoyé par courriel avant l'échéance (une semaine avant, puis la veille). Modifier ces réglages n'affecte jamais un apprenant déjà inscrit : seules les nouvelles inscriptions suivent la nouvelle configuration. Activable via `ACADEMY_AI_TUTOR_ACCESS_CONTROL_ENABLED` (désactivé par défaut — le tuteur IA se comporte comme avant).

## [1.70.0] - 2026-07-01

### Added
- **Académie — traduction IA d'un champ de cours (formateur, brouillon)** : panneau « 🌐 Traduction IA » dans l'éditeur de cours — le formateur colle un texte, l'IA propose une traduction, il relit et modifie l'aperçu, puis VALIDE. Aucune écriture automatique dans le cours (les cours ne stockent pas encore de contenu multilingue) : le résultat reste un brouillon à copier soi-même. Activable via `ACADEMY_AI_TRANSLATION_ENABLED` (désactivé par défaut).
- **Académie — narration audio d'une leçon (accessibilité)** : bouton « 🔊 Écouter cette leçon » sur la page de leçon, basé exclusivement sur la synthèse vocale native du navigateur (aucun service tiers, aucun coût). Contrôles lecture/pause/reprise/arrêt, voix française privilégiée si disponible. Activable via `ACADEMY_TTS_ENABLED` (désactivé par défaut).

## [1.66.0] - 2026-07-01

### Added
- **Académie — répétition espacée (SRS) native** : après une leçon complétée, l'apprenant peut réviser de courtes cartes (concepts et mini-quiz) reprogrammées au meilleur moment par l'algorithme SM-2. Un bouton « Réviser » apparaît dans l'espace personnel, une session plein écran présente chaque carte avec auto-évaluation (Facile / Correct / Difficile / À revoir), et une relance quotidienne par courriel invite à réviser (au plus une fois par jour). Fonctionnalité entièrement activable et désactivable (drapeau `ACADEMY_SRS_ENABLED`, désactivée par défaut) : lorsqu'elle est désactivée, aucune carte n'est créée et rien ne s'affiche.

## [1.65.264] - 2026-06-18

### Fixed
- **Annuaire — étiquettes de langue des tutoriels fiabilisées** : la détection privilégie désormais les indices clairement français du titre (la langue audio déclarée par les créateurs étant souvent erronée), et les tutoriels existants ont été reclassés. Les vidéos anglaises ne sont plus marquées « FR ».

## [1.65.263] - 2026-06-18

### Fixed
- **Annuaire — détection de langue des tutoriels** : correction de la cause des tutoriels marqués « FR » mais en anglais. La langue provient maintenant de la vraie langue audio de la vidéo (et non plus du titre, que YouTube traduit parfois), et l'enrichissement « Sonar » ne force plus « FR ». Les nouveaux tutoriels seront correctement étiquetés ; les anciens sont reclassés par un traitement de correction.

## [1.65.262] - 2026-06-18

### Added
- **Constructeur de prompts — bouton « Ouvrir dans Gemini »** (copie le prompt et ouvre Gemini ; Gemini ne permet pas le pré-remplissage par lien, le prompt est donc copié à coller).
- **Constructeur de prompts — bouton « Recommencer »** pour réinitialiser l'outil à zéro (confirmation en deux temps).
- **Encadré « ✦ En bref » — fermé par défaut + mémoire d'état** : l'encadré est replié par défaut et se souvient ensuite de votre choix (ouvert/fermé) au rafraîchissement.

## [1.65.261] - 2026-06-18

### Fixed
- **Constructeur de prompts — menu « Définir la persona » réparé** : correction d'une régression (le menu des personas s'affichait vide) en rendant la lecture des listes robuste, quel que soit leur format de stockage. Les personas (dont les nouveaux) réapparaissent.

## [1.65.260] - 2026-06-18

### Added
- **Constructeur de prompts — plus de choix utiles** : nouveaux formats de sortie (questionnaire/QCM avec corrigé, grille d'évaluation, fiche pratique, gabarit réutilisable, FAQ), tons (neutre et factuel, empathique, motivant) et personas (concepteur pédagogique, gestionnaire de médias sociaux, rédacteur publicitaire, formateur, adjoint administratif), particulièrement utiles pour les enseignants et les PME.

## [1.65.259] - 2026-06-18

### Fixed
- **Constructeur de prompts — « Ouvrir dans » réparé** : les boutons « Ouvrir dans ChatGPT/Claude/Perplexity » transmettent maintenant le prompt (le seuil de longueur était trop bas et le bloquait dans la plupart des cas) ; un message confirme que le prompt est copié.
- **Constructeur de prompts — formulation** : correction du double article (« Tu es un(e) un… ») quand la persona personnalisée commence par un article.
- **Constructeur de prompts — confirmation de copie** : un message « Prompt copié ! » s'affiche clairement au clic.

### Added
- **Encadré « ✦ En bref » repliable** : l'encadré résumé en haut des pages d'outils peut maintenant être replié/déplié (accordéon accessible), tout en restant lisible par les IA.

## [1.65.258] - 2026-06-18

### Added
- **Collection « Top outils IA pour le secteur public »** : une sélection curée de 7 outils (ChatGPT, Claude, Perplexity, NotebookLM, Copilot, Gemini, DeepL), accessible à `/collections/top-outils-ia-secteur-public` et reliée au dossier secteur public.

## [1.65.257] - 2026-06-18

### Added
- **Dossier secteur public — 2 nouveaux guides** : « Rédiger avec l'IA dans le secteur public : bonnes pratiques » et « IA et Loi 25 : protéger les renseignements personnels », reliés à la page pilier et à l'anonymiseur. Le dossier « IA pour le secteur public » devient une véritable grappe de contenu.

## [1.65.256] - 2026-06-18

### Added
- **Dossier « IA pour le secteur public québécois »** : nouvelle page pilier (`/ia-secteur-public-quebec`) qui explique comment les organismes publics et parapublics peuvent utiliser l'IA de façon encadrée (principes du ministère de la Cybersécurité et du Numérique, Loi 25), avec un encadré réponse-rapide, une FAQ et des liens vers l'anonymiseur, l'annuaire et le glossaire. Premier dossier d'une série par métier pour élargir l'audience au-delà des enseignants.

## [1.65.255] - 2026-06-17

### Added
- **llms.txt** : ajout d'un fichier `/llms.txt` qui présente le site et ses pages clés aux IA (ChatGPT, Perplexity, Google AI), pour favoriser des citations exactes vers nos outils et ressources.

## [1.65.254] - 2026-06-17

### Fixed
- **Formulaire de contact — répondre facilement** : le courriel reçu affiche maintenant clairement le nom, l'adresse et le sujet de la personne, avec un rappel que « Répondre » écrit directement au visiteur. L'expéditeur reste l'adresse du site (pour la livraison), mais on voit enfin d'un coup d'œil qui a écrit et on peut lui répondre.

## [1.65.253] - 2026-06-17

### Fixed
- **Formulaire de contact — anti-pourriel** : ajout d'une protection invisible (piège à robots) et d'un filtre qui bloque silencieusement les messages bourrés de liens. Cela met fin aux courriels indésirables reçus via le formulaire de contact, qui semblaient « venir de votre propre adresse » alors qu'il s'agissait du formulaire du site (pas d'un piratage).

## [1.65.252] - 2026-06-17

### Added
- **Outils mieux compris par les IA (GEO/AEO)** : chaque outil interactif publie désormais des données structurées (Schema.org WebApplication) et peut afficher un encadré « réponse rapide » au-dessus du contenu, pour être mieux cité par ChatGPT, Perplexity et les aperçus IA de Google.
- **Constructeur de prompts — ouvrir dans une IA** : nouveaux boutons « Ouvrir dans ChatGPT / Claude / Perplexity » qui copient le prompt et l'ouvrent directement dans l'assistant choisi.
- **Articles — éditeur « réponse rapide »** : le tableau de bord permet maintenant de rédiger un résumé direct et des points clés pour chaque article, pour une meilleure visibilité dans les réponses des IA.
- **Blogue — liens utiles en haut d'article** : un encadré « Pour aller plus loin » oriente vers le constructeur de prompts et des articles reliés, dès le haut de la page (réduit le rebond).

## [1.65.169] - 2026-06-12

### Added
- **Annuaire — alerte qualité des tutoriels** : une vérification automatique quotidienne contrôle que les tutoriels importés sont en français/anglais et pertinents, désapprouve automatiquement ceux qui ne le sont pas, et envoie un courriel récapitulatif d'alerte. Surveillance continue sans intervention.

## [1.65.167] - 2026-06-12

### Fixed
- **Annuaire — enrichissement de tutoriels débloqué** : correction d'un blocage qui faisait re-scanner sans fin les mêmes outils populaires sans tutoriel, empêchant les autres outils d'être traités. De plus, l'enrichissement écarte désormais le contenu sans rapport (jeux, films, clips musicaux) pour éviter les faux tutoriels par homonymie de nom.

## [1.65.165] - 2026-06-12

### Fixed
- **Annuaire — doublons archivés redirigent vers l'outil canonique** : la fiche d'un outil marqué comme doublon (archivé avec remplaçant) redirige désormais en 301 vers l'outil conservé, au lieu d'afficher une page en double. Les autres outils archivés restent consultables comme avant.

## [1.65.164] - 2026-06-12

### Added
- **Annuaire — tutoriels en français/anglais seulement** : l'enrichissement automatique de tutoriels YouTube écarte désormais les vidéos clairement dans une autre langue (titres en arabe, chinois, espagnol, etc.), pour ne garder que des tutoriels pertinents pour l'audience québécoise (FR/EN).

## [1.65.163] - 2026-06-11

### Fixed
- **Raccourcisseur — boutons de copie des adresses jumelles** : au clic, le bouton affiche maintenant « ✅ Copié ! » (en plus du changement de couleur), comme le bouton de copie standard.

## [1.65.162] - 2026-06-11

### Added
- **Raccourcisseur — adresses jumelles copiables** : quand l'entrée « 1lien.ca / unlien.ca » est choisie dans le sélecteur, un message rappelle que les deux adresses mènent au même endroit. Une fois le lien créé, chaque adresse (1lien.ca et unlien.ca) a son propre bouton de copie, pour partager celle qu'on préfère. Comportement inchangé pour les autres domaines.

## [1.65.161] - 2026-06-11

### Changed
- **Raccourcisseur — 1lien.ca et unlien.ca regroupés** : dans le sélecteur de domaine, les deux adresses jumelles « un lien » apparaissent comme une seule entrée « 1lien.ca / unlien.ca » ; les autres adresses (veille.la, go3.ca, lurl.ca) restent distinctes. Le lien créé via cette entrée utilise 1lien.ca (joignable partout), tandis qu'unlien.ca continue de rediriger normalement. Mise en place propre via deux champs en base (libellé d'affichage et masquage du menu), sans toucher à la résolution des liens.

## [1.65.160] - 2026-06-11

### Changed
- **Raccourcisseur — sélecteur de domaine plus distinct** : le bloc de choix d'adresse (membre) est désormais présenté dans un panneau au fond foncé (couleur du thème) avec le contenu en blanc, pour bien le démarquer du reste du formulaire. Champs (domaine + slug) en blanc, badge du nombre d'adresses et note « toutes ces adresses mènent au même lien » adaptés au fond foncé. Aucun changement de logique.

## [1.65.159] - 2026-06-11

### Added
- **Raccourcisseur — note « adresses jumelles » dynamique** : dans le créateur de liens, dès qu'un domaine est choisi dans le sélecteur, un message data-driven nomme les autres adresses actives et rappelle qu'elles mènent toutes au même lien court (la résolution se fait par slug global, donc un lien créé sur une adresse fonctionne sur toutes). Aucun nom de domaine codé en dur : la liste vient des domaines actifs ; toute nouvelle adresse (ex. unlien.ca) y apparaîtra automatiquement. Remplace l'ancienne note fixe (plus clair, se met à jour selon le domaine sélectionné).

## [1.65.158] - 2026-06-11

### Changed
- **Conditions d'utilisation — raccourcisseur** : renforcement de la clause de non-responsabilité (section 7). Trois ajouts conformes au droit québécois : statut d'intermédiaire technique (LCCJTI art. 22), responsabilité exclusive de l'utilisateur qui crée le lien quant au contenu de destination, et garantie/indemnisation de laveille.ai et MEMORA solutions par l'utilisateur. À faire valider par un juriste.

## [1.65.157] - 2026-06-11

### Added
- **Raccourcisseur — choix du domaine plus évident** : quand plusieurs adresses sont disponibles, le créateur de liens affiche clairement un sélecteur (« Choisis ton adresse » + nombre d'adresses disponibles) et une note rassurante « Adresse différente, même destination : toutes ces adresses mènent au même lien court ».

## [1.65.156] - 2026-06-11

### Fixed
- **Liens en milieu de phrase** : quand une URL est introduite par un mot de liaison (« Accessible via https://…, il repose »), le retrait du lien ne laisse plus de mot orphelin — la phrase devient « Accessible, il repose ». Les tournures sans lien (« via une API », « sur le marché ») restent intactes.

## [1.65.155] - 2026-06-11

### Added
- **Post social de l'annuaire — nombre de tutoriels** : le post d'un outil affiche désormais une ligne de preuve sociale dynamique « 🎓 {N} tutoriels pour bien démarrer t'attendent déjà sur la veille » (accord singulier/pluriel), uniquement si l'outil a au moins un tutoriel, sans lien. Le compte suit exactement celui de la fiche /annuaire.

## [1.65.154] - 2026-06-11

### Fixed
- **Post social des actualités — moins de redondance** : le « 👉 » (point clé) ne répète plus le « En clair » (résumé). Le post choisit automatiquement un point clé, une citation ou un « pourquoi c'est important » réellement distinct du résumé (sinon il est omis).

## [1.65.153] - 2026-06-11

### Fixed
- **Typographie française dans les contenus de partage** : l'espace avant `: ; ! ?` est préservée (seuls les espaces parasites avant `. , …` sont retirés).

## [1.65.152] - 2026-06-11

### Fixed
- **Liens entre parenthèses** : le retrait d'une URL ne laisse plus de parenthèse ouvrante orpheline (« Nom ( est… »).

## [1.65.151] - 2026-06-11

### Fixed
- **Nettoyage des liens dans les contenus de partage** : après le retrait d'une URL entre parenthèses, on supprime la parenthèse vide laissée (« Nom ( est… » → « Nom est… »), on réduit les espaces multiples et on recolle la ponctuation isolée. S'applique à tous les posts sociaux et résumés NotebookLM.

## [1.65.150] - 2026-06-11

### Changed
- **Post réseaux sociaux du bouton Admin — format « 2026 » partout** : le glossaire, l'annuaire, le blog et les actualités utilisent désormais le même format engageant que les acronymes (accroche curiosity-gap + « En clair : » + « 👉 » + appel à commenter + hashtags), **sans lien ni signature promotionnelle**, avec une accroche adaptée à chaque type. Réutilise `buildEngagingSocialPost()` + `smartTrim()` (zéro duplication). L'ancienne signature « Plus de contenu IA… sur LaVeille AI » est retirée de ces posts.

## [1.65.149] - 2026-06-11

### Fixed
- **Post social — troncature propre** : les blocs « En clair : » et « 👉 » sont coupés à la fin d'une phrase complète (sinon au dernier mot + « … ») au lieu d'être tronqués en plein milieu d'un mot.

## [1.65.148] - 2026-06-11

### Changed
- **Post réseaux sociaux du bouton Admin (acronymes) — refonte « 2026 »** : le post copié est désormais plus riche et attirant, selon les meilleures pratiques de juin 2026 (recherche Perplexity). Format : accroche qui ouvre une boucle de curiosité + « En clair : » (définition sans jargon) + « 👉 » (fait à retenir) + un appel à commenter (CTA conversationnel) + hashtags. **Aucun lien, aucune signature promotionnelle.** Nouvelle méthode réutilisable `buildEngagingSocialPost()` (les autres sections gardent leur format actuel pour l'instant).

## [1.65.147] - 2026-06-11

### Changed
- **Acronymes — liste cohérente avec la fiche** : les cartes de la liste `/acronymes-education` affichent l'icône emoji de catégorie dans leur vignette (au lieu du favicon), pour un rendu net et cohérent avec la fiche.

## [1.65.146] - 2026-06-11

### Fixed
- **Acronymes — fin des logos déformés sur la fiche** : les fichiers de logos sont des canevas carrés 64×64 où les logos rectangulaires (wordmarks) avaient été écrasés (déformation dans le fichier, incorrigeable en CSS) et tous pixelisés à l'affichage. Le re-téléchargement depuis les sites officiels s'est révélé non fiable (og:image = photos/bannières, favicons 32×32 ou 404). La fiche affiche désormais l'**icône emoji de catégorie** (vectorielle, nette, cohérente, zéro déformation). `logo_url` est conservé en base (réversible).

## [1.65.145] - 2026-06-11

### Fixed
- **Acronymes — hauteur du logo portée à 90 px** : le logo de la fiche ne se rendait qu'à ~76 px (le padding interne rognait la hauteur). L'image porte maintenant `height: 90 px` avec `object-fit: contain`, ce qui garde la hauteur de mise en forme et garantit l'absence de déformation, y compris pour un logo très large.

## [1.65.144] - 2026-06-11

### Changed
- **Acronymes — bouton « Admin » (NotebookLM) remonté en haut de la fiche** : les 3 copies superadmin (Résumé NotebookLM, NotebookLM Infographie, Post réseaux sociaux) sont désormais dans la barre d'action en haut, juste après l'en-tête — comme sur le glossaire et les actualités (auparavant en bas de page, donc peu visible). Zéro duplication, le partage social reste en bas.

### Fixed
- **Acronymes — logos non déformés** : la boîte de logo de la fiche n'est plus un carré figé 90×90 (qui écrasait les logos rectangulaires). Le logo respecte maintenant son ratio natif (largeur auto) avec une hauteur fixe de 90 px et une largeur max de 240 px, conservant la mise en forme. La vignette circulaire de la liste/index (44×44, `object-fit:contain`) est inchangée.

## [1.65.143] - 2026-06-10

### Added
- **Acronymes — icônes emoji par catégorie** : chaque acronyme publié (312) reçoit l'emoji de sa catégorie (🏛️ ministères et organismes gouvernementaux, 🤝 associations et organismes professionnels, 🔧 formation professionnelle et technique, 🎓 formation générale et diplômes, 💻 technologies éducatives et numérique, 🧩 services aux élèves et adaptation, 🏫 centres de services scolaires, 📋 gestion et administration scolaire). Affiché dans l'en-tête de la fiche et sur les chips. Donnée seulement (la vue v1.65.142 lisait déjà `icon`).
- **Acronymes — maillage broader/narrower (graphe de connaissances)** : ~82 relations hiérarchiques parent→enfant générées par IA (OpenRouter qwen3-max), **intra-catégorie**, avec garde-fou anti-hallucination (validation serveur des slugs contre la liste réelle + symétrisation broader↔narrower + `temperature` 0.1). 105 acronymes maillés (77 « Catégorie parente », 34 « Sous-acronymes »). Les associations professionnelles (catégorie sans hiérarchie) restent volontairement sans maillage. Affiché en chips « Acronymes liés » (la vue v1.65.142 lisait déjà `broader_slugs`/`narrower_slugs`).

### Notes
- Aucun code applicatif modifié (enrichissement de **données** uniquement) ; aucun cron ; backups conservés (`storage/app/backup-acronyms-icons`, `storage/app/backup-acronyms-mesh`). Rollback : remettre `icon`/`broader_slugs`/`narrower_slugs` à `NULL` (la migration #304 peut aussi `down()` ces colonnes).

## [1.65.136] - 2026-06-10

### Added
- **Menu de partage admin étendu au glossaire, à l'annuaire et au blog** (superadmin only), avec **contenu adapté par type** pour maximiser les vues réseaux sociaux (veille juin 2026) : glossaire = explainer éducatif, annuaire = revue par cas d'usage, blog = teaser insight. Chaque type expose les 3 copies (Résumé NotebookLM, NotebookLM Infographie, Post réseaux sociaux).
- **Trait partagé `Modules\Core\Concerns\HasAdminShareContents`** (zéro-duplication) : `infographiePrompt()`, `buildSocialPost()`, `stripLinks()`, `normalizeShareHashtag()`. Utilisé par `Term`, `Tool`, `Article` et **`NewsArticle` (refactorisé)**. Branché via `$adminShareItems` dans les 3 vues `show` (le composant `<x-core::admin-copy-menu>` est réutilisé tel quel).

## [1.65.133] - 2026-06-09

### Added
- **News — bouton « Admin » superadmin sur la page actualité** (barre de partage), ouvrant un menu de 3 actions de copie : (1) **Résumé pour NotebookLM** (`structured_summary` → Markdown avec titres de section, sans liens), (2) **Prompt NotebookLM** (consignes infographie fixes), (3) **Post réseaux sociaux** natif optimisé 2026 (hook + 3 points + CTA-question + hashtags ciblés, ton québécois, sans lien externe). Visible uniquement si `auth()->user()?->isSuperAdmin()`.
- **Composant générique réutilisable `<x-core::admin-copy-menu>`** (`Modules/Core/.../components/admin-copy-menu.blade.php`) : bouton + menu Alpine + copie presse-papier multi-lignes (textarea ref + fallback `execCommand`), CSS `@once`. Zéro logique métier → réemployable sur d'autres sections. La génération du contenu vit dans `NewsArticle::adminShareContents()` (séparation UI / contenu, zéro duplication).

## [1.65.132] - 2026-06-09

### Added
- **SEO/AEO — `llms.txt` + `llms-full.txt` générés dynamiquement** (audit utilisateur : fichiers statiques périmés, chiffres contradictoires, `llms-full` faux « full » sans accents, contradiction training). Nouveau `App\Http\Controllers\LlmsController` (routes racine `/llms.txt` + `/llms-full.txt`, `Cache::remember` 1h) avec **compteurs en temps réel** (outils/termes/articles/acronymes/actualités publiés). `/llms.txt` = index AEO (pitch chiffré, sections, expertise, politique IA, ressources machines, date Québec). `/llms-full.txt` = **vrai dump** (glossaire complet + outils + articles + acronymes + 100 actualités récentes, Markdown, accents fr-CA). Politique tranchée : **entraînement ET citation autorisés** (aligné `robots.txt`). Modules désactivables gérés (`class_exists` + try/catch).

### Removed
- Fichiers statiques `public/llms.txt` et `public/llms-full.txt` (périmés, remplacés par la génération dynamique). Backup : `.rapports/llms-backup-2026-06-09/` + historique git.

## [1.65.131] - 2026-06-09

### Fixed
- **News — logo œil pixelisé dans le visuel auto** (signalé par l'utilisateur). Le logo `logo-eye-white.svg` (viewBox 52×52) était lu par Imagick à sa taille native (~52 px) puis agrandi à 200 px (`resizeImage`, ×3,8 upscale) → bords pixelisés. Correction : `$logo->setResolution(1200, 1200)` **avant** `readImage()` → le SVG est rasterisé à ~870 px puis réduit à 200 px (Lanczos) = rendu net.

## [1.65.130] - 2026-06-09

### Fixed
- **News — centrage du texte dans le badge « pill » de catégorie** (signalé par l'utilisateur : le texte débordait par le haut du badge, surtout avec les accents majuscules É/Ô). Cause : la formule de baseline avait le signe inversé (`500 - (asc+desc)/2`) → texte ~17 px trop haut. Correction : `$baseline = $pillCenterY + ($asc - $desc)/2` (valeurs absolues des métriques, robuste quel que soit le signe renvoyé par Imagick) → le centre du glyphe tombe exactement sur le centre du pill. La hauteur du pill passe à `(asc+desc)+26` (marge verticale pour les accents montants) et le rayon des coins à 16.

## [1.65.129] - 2026-06-09

### Changed
- **News — palettes du visuel auto alignées sur les VRAIES catégories** : relevé des 18 tags réels en base (« IA générative » 3333, « Autre » 2956, « Cybersécurité » 888, « Infrastructure » 824, « Robotique », « Startup », « Cloud », « Données », « Éducation tech »…). Les anciennes clés de palette (`ia`, `securite`…) ne correspondaient à quasi aucun tag réel → la couleur tombait presque toujours sur le repli déterministe `id % 10`. Désormais la table `$palettes` est ré-indexée sur les tags normalisés (IA générative = teal signature, Cybersécurité = rouge, Données = vert, Cloud = bleu ciel, Éducation tech = indigo, Énergie renouvelable = vert nature…), et la normalisation `$catKey` translittère correctement les accents (`mb_strtolower` + `strtr` : « Cybersécurité » → `cybersecurite`). Le pill affiche le tag réel accentué en majuscules. La couleur du visuel est maintenant **sémantiquement liée** à la catégorie de l'article.

## [1.65.128] - 2026-06-09

### Changed
- **News — affinage du visuel « réseau de neurones » suite validation visuelle** (agent Playwright sur 6 témoins → 6,5/10, 3 défauts corrigés) : (1) **bloquant** — un nœud chevauchait « laveille.ai » → les nœuds sont désormais cantonnés aux **marges latérales** (index pair = gauche x[20,380], impair = droite x[820,1180]) avec y borné à [20,470] (épargne la bande du titre ET le footer) ; (2) **asymétrie** (motif massé dans un coin) → l'alternance gauche/droite garantit l'équilibre (2 grappes propres, arêtes < 300 px) ; (3) **gros nœuds** bornés à un rayon 9–11 (n'éclipsent plus le logo). Le label de catégorie devient un **badge « pill »** (roundRectangle couleur d'accent à 85 % + texte en majuscules blanc centré via `queryFontMetrics`) au lieu du texte gris brut. Imagick pur, déterministe.

## [1.65.127] - 2026-06-09

### Added
- **News — visuel auto « réseau de neurones » génératif (design choisi par l'utilisateur, veille pp_search juin 2026, 91/100)** : `NewsImageService::generateFallbackImage` superpose désormais `drawNeuralPattern()` sur le dégradé de marque — un motif déterministe **nœuds + arêtes unique par titre** (PRNG LCG seedé sur `crc32($title)` → même titre = même motif). Arêtes blanches 10 % entre nœuds proches (< 320 px), nœuds à 22 % d'opacité (3 « gros » à 16 % avec anneau-halo), 1 nœud sur 4 en couleur d'accent de la palette de catégorie. La bande centrale du titre (y 250–560) est préservée (nœuds repoussés vers le haut). Thématiquement IA, subtil, lisible, Imagick pur (≤ ~30 primitives, ~0,2 s), **zéro dépendance externe, zéro droit d'auteur**. Sert au robot (nouveaux articles) ET au rattrapage de masse des anciennes images. Code délégué à Hermes (qwen3-max), intégré + affiné (contour des disques neutralisé, halo des gros nœuds à rayon+6).

## [1.65.125] - 2026-06-09

### Fixed
- Actualités / **droits d'auteur** — le robot d'agrégation **ne télécharge/ré-héberge plus aucune image de source** (photos de presse). À la place, il génère une **image de marque libre de droits** (fond La veille + titre de l'article). Stoppe la récidive des réclamations type PicRights/Reuters. Couvre tous les chemins (fetch, rescrape, reprocess). Réversible (le code de téléchargement est conservé mais neutralisé). L'article litigieux a par ailleurs été corrigé (photo remplacée par une image libre + crédit retiré).

## [1.65.124] - 2026-06-09

### Added
- Newsletter — **override HTML par édition** (`content.custom_html`). Une édition peut désormais figer un **HTML validé** envoyé tel quel aux abonnés (et au test), sans régénération par le gabarit. Le lien de désabonnement reste personnalisé par abonné. Sans `custom_html`, le comportement est strictement inchangé. Permet d'envoyer exactement l'aperçu approuvé.

## [1.65.123] - 2026-06-09

### Fixed
- Anonymiseur (moteur) — **qualité d'anonymisation** : trois défauts repérés par la simulation E2E sont corrigés. (1) **Anti-collision** : un faux nom ne peut plus réutiliser un vrai nom présent ailleurs dans le texte (qui créait une ambiguïté). (2) **Aucune fuite du vrai nom dans le faux courriel** : la partie locale d'un faux courriel ne laisse plus passer un vrai nom de famille, même abrégé ou accentué (ex. « Côté-Pelletier » → « cote »), et même en mode jetons. (3) **Prénom isolé** : un prénom employé seul (« Geneviève » après « Geneviève Côté-Pelletier ») est maintenant masqué dans les deux modes. (4) **Cohérence** : le faux courriel correspond toujours au faux nom complet affiché. Validé par banc d'essai (17/17 + 6/6 non-régression, restauration 100 % préservée). Réversible.

## [1.65.122] - 2026-06-09

### Changed
- Anonymiseur — **accordéon de confidentialité « Je comprends »**. Le bloc « 🛡️ 100 % local » (rappel Loi 25 / RGPD, texte inchangé) s'affiche maintenant **ouvert au premier affichage**. Un bouton **« ✓ Je comprends »** à l'intérieur le **ferme et mémorise le choix** dans le navigateur (`localStorage`) : il **reste fermé** lors des visites suivantes, mais l'utilisateur peut le **rouvrir/refermer à volonté** via son en-tête. Un script inline (anti-flash) applique l'état mémorisé avant l'affichage, sans clignotement. Le composant générique `<x-core::accordion>` n'est pas modifié ; seule la page de l'anonymiseur l'est. Accessible (aria-expanded, clavier, focus visible). Réversible.

## [1.65.121] - 2026-06-08

### Added
- Glossaire — **nouveau terme « Bluetooth »**, catégorie « Concepts fondamentaux ». Fiche complète au gabarit standard (définition d'environ 270 mots, analogie, exemple, « le saviez-vous » [le nom vient du roi viking Harald Blåtand et le logo combine ses initiales runiques], réponse en une phrase, FAQ FAQPage, 2 sources Wikipédia vérifiées). Dérivés en `aliases` pour l'auto-liaison : Bluetooth Low Energy, BLE, Bluetooth LE. Image hero générée sur le compte Gemini de l'utilisateur (3D isométrique teal/orange, sans texte), fournie en `bluetooth.jpg` (og:image — réseaux sociaux refusent WebP/AVIF) + `bluetooth.webp`, 1200×669 compressées, nom de fichier = slug. Migration réversible.
- Glossaire — **nouveau terme « PowerShell »**, catégorie « Outils ». Fiche complète au gabarit standard (définition d'environ 285 mots, analogie, exemple de pipeline `Get-Process | …`, « le saviez-vous » sur le pipeline d'objets .NET, réponse en une phrase, FAQ FAQPage, 2 sources vérifiées : Wikipédia + Microsoft Learn). Dérivés en `aliases` : pwsh, PowerShell Core, PowerShell 7, Windows PowerShell. Image hero générée sur le compte Gemini de l'utilisateur (console isométrique teal/orange, sans texte lisible), fournie en `powershell.jpg` (og:image) + `powershell.webp`, 1200×669 compressées, nom de fichier = slug. Migration réversible.

## [1.65.120] - 2026-06-08

### Added
- Glossaire — **nouveau terme « Firmware » (micrologiciel)**, catégorie « Concepts fondamentaux ». Fiche complète au même gabarit que les autres termes (définition d'environ 290 mots, analogie, exemple concret, « le saviez-vous » [le mot a été forgé par Ascher Opler en 1967 dans Datamation], réponse en une phrase, FAQ avec balisage FAQPage, 2 sources Wikipédia vérifiées). Les dérivés et synonymes français (micrologiciel, microprogramme, firmwares) sont gérés en `aliases` pour l'auto-liaison automatique dans les articles. Image hero générée sur le compte Gemini de l'utilisateur (illustration 3D isométrique teal/orange, sans texte) et fournie en deux formats : `firmware.jpg` (og:image — les réseaux sociaux refusent WebP/AVIF) et `firmware.webp` (affichage), en 1200×669 compressées, nom de fichier = slug pour le référencement. Insertion via migration réversible.

## [1.65.119] - 2026-06-08

### Fixed
- Sudoku — **message « non classé » honnête** (défaut trouvé en testant une partie Diabolique complète). La modale de victoire affichait **toujours** « non classé : temps trop court » dès qu'un score n'était pas publié, alors que la publication au classement exige **deux** conditions : temps ≥ minimum **ET** utilisateur **connecté**. Un joueur **anonyme** avec un bon temps voyait donc un message **faux** (« temps trop court » alors que son temps était suffisant). Correctif : l'API renvoie désormais `publish_reason` (`published` / `anonymous` / `too_fast`) et `min_time` ; la modale affiche le bon message — connecté mais trop rapide → « Non classé : temps trop court (minimum X s) » ; anonyme → « Connectez-vous pour apparaître au classement » ; publié → « Rang du jour : N ». (Le reste du test Diabolique complet est PASS : 24 indices de départ, saisie clavier, notes, erreur+correction, indice, pause, auto-détection de victoire, soumission.)

## [1.65.118] - 2026-06-08

### Added
- Sudoku — **avertissement de persistance locale + indicateur de grille terminée** (demande utilisateur : « le dernier sudoku reste dans le navigateur… ajouter un avertissement »). (1) Note permanente dans le panneau latéral : « Votre partie est enregistrée sur cet appareil et restaurée si vous rechargez la page (rien n'est envoyé au serveur tant que vous ne soumettez pas un score) ; elle disparaît si vous changez d'appareil/navigateur ou videz les données du site. » (2) Bandeau (visible quand la grille est terminée, y compris après rechargement d'une grille finie) : « ✅ Grille terminée. Cliquez « Nouvelle grille » pour rejouer. » — clarifie pourquoi la grille est verrouillée.

## [1.65.117] - 2026-06-08

### Fixed
- Sudoku — **vraie cause du titre « Bravo ! » illisible** : le titre s'affichait en **foncé** (`#1A1D23`) sur le fond teal foncé, et non en blanc. Cause = le passage du titre de `<h5>` à `<h2>` (v1.65.112) : la règle globale `h2 { color: #1A1D23 }` l'emportait sur la couleur `#fff` héritée de l'en-tête. Correctif : `color:#fff` explicite sur le `<h2>` du titre (l'inline bat la règle globale). Désormais blanc sur `#064E5A` = **9.35:1** (AAA). Complète le dégradé AAA de la v1.65.116.

## [1.65.116] - 2026-06-08

### Fixed
- Sudoku — **modale de victoire** (retours utilisateur). **(1) Contraste WCAG 2.2 AAA du titre « Bravo ! »** : l'en-tête utilisait un dégradé `#0B7285 → #053d4a` ; le blanc sur `#0B7285` (extrémité claire) ne donnait que **5.58:1** (AA, mais pas AAA). Nouveau dégradé `#064E5A → #053d4a` → blanc = **9.35:1** et **11.85:1** (≥ 7:1, AAA, vérifié). **(2) Pseudo prérempli avec le nom du compte si connecté** : le composant reçoit le nom de l'utilisateur authentifié (`auth()->user()->name`) ; à l'ouverture, le champ « Pseudo (pour le classement) » est prérempli avec ce nom. Hors connexion, comportement inchangé (dernier pseudo en localStorage).

## [1.65.115] - 2026-06-08

### Fixed
- Sudoku — **auto-détection de fin de grille** (retour utilisateur : « quand j'ai terminé, pas de félicitation ? pas d'envoi au classement ? »). `verifyComplete()` n'était déclenché **que** par le bouton « Vérifier la grille » : un joueur qui remplissait sa grille sans cliquer ce bouton ne voyait jamais la modale de félicitations ni le classement. Nouvelle méthode `checkCompletion()` (si la grille est pleine → `verifyComplete` = félicitations + soumission au classement) appelée **après chaque saisie** (`inputValue`) **et chaque indice** (`useHint`). Grille pleine et valide → modale « Bravo ! » automatique ; pleine mais avec une erreur → message d'erreur ciblé (comportement inchangé). Le bouton « Vérifier la grille » reste disponible.

## [1.65.114] - 2026-06-08

### Fixed
- Sudoku — **2 bugs du mode notes** (retours utilisateur). **(1) Le crayon rouge cachait le chiffre** : l'icône ✎ (pseudo-élément `::after` au coin haut-droit de la case sélectionnée en mode notes) recouvrait la note affichée à cette position — la note « 3 » s'affiche justement en haut-droite de la mini-grille 3×3. C'est aussi ce qui donnait l'impression que « la note n'apparaît pas, mais est là après avoir changé de case » (le crayon suit la case sélectionnée). Vérifié : la note **s'affiche bien immédiatement** (la réactivité fonctionne — ce n'était pas un bug de rendu). Correctif : l'icône ✎ est **retirée** ; le mode notes reste clairement signalé par le contour + le fond rouges de la case, le pavé numérique rouge et le bouton « Notes » enfoncé. **(2) Le bouton « Notes » volait le focus** : après avoir cliqué « Notes », il fallait recliquer la case pour que le clavier fonctionne, car le clic plaçait le focus sur le bouton (hors de la grille) → la frappe n'atteignait plus la grille. Correctif : `toggleNotesMode()` bascule le mode notes **puis redonne le focus** à la case sélectionnée (helper `focusCell` partagé avec `selectCell`).

## [1.65.113] - 2026-06-08

### Fixed
- Sudoku — **saisie au clavier fiable dans les cases** (demande utilisateur : « pourquoi je ne peux pas utiliser mon clavier en plus des numéros en bas ? »). Le clavier ne fonctionnait que si la cellule **exacte** avait le focus DOM (le gestionnaire `handleKey` était attaché `@keydown` sur chaque cellule), or sélectionner une case ne déplaçait pas le focus → dès qu'on cliquait une case-indice, le pavé, ou ailleurs, la frappe ne faisait rien. Refonte selon la meilleure pratique de juin 2026 (widget composite, source de vérité unique `selectedCell`, périmètre = la grille, **pas** de gestionnaire global `window`) : (1) un **seul** gestionnaire `@keydown` au niveau du **conteneur de la grille** (rendu focusable, `tabindex=0`) qui route les touches vers la cellule sélectionnée ; (2) `selectCell` **synchronise désormais le focus DOM** sur la cellule sélectionnée (au clic **et** aux flèches) ; (3) retrait du `@keydown` par cellule (anti double-traitement). Chiffres 1-9 = saisie, Backspace/Suppr/0 = effacer, flèches = déplacer. **Notes** : via le bouton « Notes » existant (la saisie respecte le mode notes) + raccourci Maj+chiffre conservé. Pavé numérique du bas inchangé.

## [1.65.112] - 2026-06-08

### Fixed
- Sudoku — **accessibilité WCAG 1.3.1 (ordre des titres)** : les titres de **dialogue** créaient des sauts de niveau (overlay « Partie en pause » `<h3>` après le `<h1>` ; modale de victoire `<h5>` après le `<h3>`). Tous les titres de dialogue (pause, victoire, changement de niveau, nouvelle grille) sont passés à `<h2>`, avec la **taille visuelle préservée** via les classes utilitaires Bootstrap `.h3`/`.h5`. La hiérarchie de la page est désormais `<h1>` « Sudoku quotidien » puis uniquement des `<h2>` → plus aucun saut. L'`id="winModalLabel"` est conservé (`aria-labelledby` intact).

## [1.65.111] - 2026-06-08

### Fixed
- Sudoku — **accessibilité WCAG 4.1.2** (suite v1.65.110). Le retrait de `role="gridcell"` avait laissé un `aria-label` sur des `<div>` sans rôle valide (invalide : « aria-label cannot be used on a div with no valid role »). Correctif : seules les **cases éditables** reçoivent `role="button"` (rôle valide pour `aria-label`, aucun parent ARIA requis, et elles sont réellement activables) + `tabindex=0` + `aria-label` ; les **cases-indices** (données fixes) deviennent du texte simple (sans rôle/aria-label/focus). Audit WCAG : `1.3.1` (grid/tablist) **et** `4.1.2` résolus ; layout 3×3 et ordre vertical intacts ; ne restent que les faux positifs documentés (blanc/blanc dû à l'en-tête foncé mal lu par le scanner, skip-link 1×1 site-wide, modale infolettre masquée).

## [1.65.110] - 2026-06-08

### Fixed
- Sudoku — **accessibilité WCAG 1.3.1 (structure ARIA)**, reco P2 issue du bilan de simulation. (1) **Grille** : `role="grid"` → `role="group"` et `role="gridcell"` retiré des cellules. Un `role="grid"` impose un maillage strict `grid > row > gridcell` ; sans conteneur `role="row"` intermédiaire, l'audit signalait « grid must contain row » + « gridcell must be contained by row ». La solution `display:contents` sur un `role="row"` n'étant **pas fiable cross-navigateur en 2026** (recherche), on retire la promesse ARIA invalide ; l'information de position reste portée par l'`aria-label` de chaque cellule (« Ligne X, colonne Y, vide/valeur N ») et la navigation aux flèches déjà fonctionnelle. **Zéro changement de CSS/layout** (blocs 3×3 et ordre vertical intacts). (2) **Navigation du haut** : `role="tablist"` + `role="presentation"` retirés (ce sont des **liens** entre pages — Jouer/Classements/Mes parties — pas un widget d'onglets) + `aria-current="page"` sur le lien actif. (3) **Pills de difficulté** : `role="tablist"` → `role="group"` (boutons bascule `aria-pressed` à tabulation indépendante, pas des onglets). Amélioration future possible : grille en `<table>` natif + roving tabindex.

## [1.65.109] - 2026-06-08

### Changed
- Sudoku — endpoint indice : limite de débit **60 → 120 requêtes/min**. En vérifiant le correctif v1.65.108 dans le navigateur (remplir toute la grille uniquement avec « Indice »), le throttle de 60/min introduit en v108 pouvait s'épuiser sur une partie résolue surtout par indices (Diabolique ≈ 57 cases vides). 120/min reste anti-abus (la solution n'est jamais exposée, une seule case par appel, pénalité de temps par indice) sans jamais bloquer un joueur légitime. Vérification du correctif v108 : Facile = 41 indices sur 41 trous → grille **complète, 0 conflit, 0 erreur** (chaque indice pose la bonne valeur).

## [1.65.108] - 2026-06-08

### Fixed
- Sudoku — **bouton « Indice » pouvait remplir une mauvaise valeur** (bug trouvé pendant la simulation E2E complète des 5 niveaux). `useHint()` devinait côté client la première valeur **sans conflit** au lieu d'utiliser la vraie solution (jamais envoyée au navigateur pour empêcher la triche) → sur certaines cases à plusieurs candidats, l'indice posait un chiffre faux, puis générait des erreurs. Correctif : nouvel endpoint serveur `POST /api/sudoku/hint/{puzzle_id}` (corps `{row, col}`, throttle 60/min) qui révèle **une seule** case « trou » depuis `SudokuPuzzle::solution` (refuse une case-indice ou une valeur invalide → 422) ; `useHint()` devient asynchrone et appelle cet endpoint (jeton CSRF, message de repli si indisponible). **Anti-triche préservé** : la solution complète ne quitte jamais le serveur, une seule case par appel, compteur d'indices et pénalité de temps inchangés. Reproduit sur Facile/Difficile avant le correctif, indice correct après.

## [1.65.107] - 2026-06-08

### Fixed
- Sudoku — **VRAI « problème de cases » corrigé : les blocs 3×3 affichaient des bandes 4/3/2 au lieu de 3/3/3**. Diagnostic Playwright : la grille `display:grid` était rendue **verticalement inversée** (data-row 0 en bas, data-row 8 en haut) ; les bordures de blocs (correctement sur data-row 2 et 5) tombaient alors après les 4e et 7e rangées visuelles → grandes cases de 4, 3 puis 2 petites cases. Correctif robuste indépendant de la cause : **placement explicite** de chaque cellule via `grid-row`/`grid-column` (data-row 0 → rangée 1 = haut). Vérifié : data-row 0 en haut, 8 en bas, blocs parfaitement découpés en 3×3 (3/3/3). (Les diagnostics précédents — densité de givens v1.65.105, sauvegarde locale v1.65.106 — étaient des améliorations valides mais à côté du vrai défaut structurel.)

## [1.65.106] - 2026-06-08

### Fixed
- Sudoku — **la sauvegarde locale obsolète masquait un puzzle régénéré** (« rien n'a changé » côté joueur). La grille de jeu est sauvée en localStorage sous `sudoku_state_<puzzle_id>` ; quand un puzzle est régénéré côté serveur en gardant le même id, l'ancienne grille était restaurée, écrasant la nouvelle. Correctif : `saveLocalState()` enregistre désormais une **signature des givens** (`init`), et `restoreLocalState()` **invalide la sauvegarde** si la grille initiale serveur diffère (helper `givensMatch()`, avec repli de validation cellule par cellule pour les anciennes sauvegardes). Un puzzle régénéré force ainsi un repartir propre depuis le serveur. (Le service worker `sw.js` est déjà en mode cleanup ; non impliqué.)

## [1.65.105] - 2026-06-08

### Fixed
- Sudoku — **les niveaux déterminent désormais un nombre de chiffres donnés (givens) DISTINCT et croissant** (« problème de cases » signalé). Avant : le retrait glouton en une seule passe se bloquait vers ~24 indices, donc Difficile/Expert/Diabolique étaient quasi identiques (24-25 indices) et `clues_count` stockait la cible et non le réel. Maintenant : nouveau `digHoles()` en **retrait multi-passes** (avec garantie d'unicité conservée) atteignant des cibles distinctes — **Facile 40 · Moyen 34 · Difficile 30 · Expert 26 · Diabolique ~22-24** — et stockage du **compte réel** d'indices. Garde-fou temps (budget 12 s) contre les pics de génération sur grilles très creuses. Cibles fondées sur les best practices juin 2026 (fourchettes NYT/Conceptis/Sudoku Coach). Aucune donnée touchée (les puzzles existants conservent scores/parties ; le nouveau barème s'applique aux puzzles à venir). Amélioration recommandée ensuite : classement par technique de résolution (gold standard).

## [1.65.104] - 2026-06-08

### Fixed
- Glossaire — **arbitrage des 4 paires limites** (décision éditoriale finale). Après lecture du contenu réel : 3 paires sont des **concepts hiérarchiques distincts** (pas des synonymes) et sont **conservées séparées** — embeddings/vectorisation, ia-multimodale/modele-multimodal, llm/modele-de-langage (ex. : un LLM est un *type* de modèle de langage). Seule l'entrée **« spoiler »** — mal nommée (le vrai « Spoiler » est une faille CPU) et dont le contenu décrivait en réalité l'empoisonnement de données — est **fusionnée** vers `data-poisoning` (dépubliée + redirigée 301). `data-poisoning` reçoit la catégorie « Sécurité et éthique » et l'alias « empoisonnement de données ». Correction d'un lien taxonomique inversé : `embeddings` est désormais correctement rattaché comme sous-type de `vectorisation`. Migration réversible, aucun DELETE.

## [1.65.103] - 2026-06-08

### Fixed
- Glossaire — **8 doublons sémantiques consolidés** (audit prod-wide, fusion dans « Aussi appelé ») : `tokens`→`token`, `moe`→`mixture-of-experts`, `context-window`→`fenetre-de-contexte`, `shadow-ai`→`ia-fantome`, `infiltration-de-requete`→`prompt-injection`, `knowledge-distillation`→`distillation-de-modele`, `affinage`→`fine-tuning`, `edge-ai`→`ia-embarquee`. Pour chaque paire (même concept sous 2 fiches, le doublon étant l'entrée admin sans catégorie) : nom + alias uniques fusionnés dans « Aussi appelé » de la fiche canonique, doublon **dépublié** (réversible, aucun DELETE), liens broader/narrower nettoyés (self-refs retirés, `byoai.broader` shadow-ai→ia-fantome), ancien slug **redirigé 301**. Les paires limites (embeddings/vectorisation, ia-multimodale/modele-multimodal, llm/modele-de-langage) et l'entrée douteuse « spoiler » sont volontairement laissées pour décision éditoriale (concepts potentiellement distincts).

## [1.65.102] - 2026-06-08

### Fixed
- Glossaire — **liens internes cassés corrigés** (audit prod-wide) : 8 références `broader_slugs`/`narrower_slugs` invalides. Les renvois vers des doublons dépubliés sont remappés vers la fiche canonique (`differential-privacy` → `confidentialite-differentielle` sur anonymisation et k-anonymity) ; les renvois vers des slugs inexistants sont retirés (`protection-vie-privee` ×4, `hash-sha-256`, `hallucination-ia`). Migration réversible, aucun terme supprimé. Audit confirme aussi : 0 fiche sans image hero (les alertes initiales étaient des faux positifs dus au suffixe `?v=` dans le champ hero_image).

## [1.65.101] - 2026-06-07

### Fixed
- Glossaire — **2 doublons supplémentaires consolidés** (révélés par un audit prod-wide après le cas MCP) : `differential-privacy` → canonique `confidentialite-differentielle`, et `hallucination-ia` → canonique `hallucination`. Même traitement réversible : alias uniques fusionnés dans la fiche canonique (« differential privacy », « hallucination IA », « Hallucination LLM »…), doublon **dépublié** (aucun DELETE), ancien slug **redirigé en 301**. Les fiches canoniques (originaux du seeder, contenu propre) sont conservées ; les doublons venaient d'ajouts manuels via l'admin (le doublon `hallucination-ia` avait des artefacts markdown bruts).

## [1.65.100] - 2026-06-07

### Fixed
- Glossaire — **consolidation du doublon « MCP »** : deux fiches existaient pour le même concept (`mcp`, acronyme issu du seeder d'origine, contenu propre ; et `mcp-model-context-protocol`, ajouté via l'admin sur prod, avec des artefacts markdown bruts). La fiche canonique `/glossaire/mcp` (slug court, contenu propre) est conservée et enrichie des alias uniques du doublon (« serveur MCP », « MCP server », « protocole MCP ») ; le doublon est **dépublié** (migration réversible, aucun DELETE) et son ancien slug **redirige en 301** vers `/glossaire/mcp` (préserve le SEO, évite le contenu dupliqué et tout 404). Cause : ajout manuel via l'admin sans voir l'acronyme existant.

## [1.65.99] - 2026-06-07

### Added
- Glossaire : terme **Latence** (latency, cat Concepts fondamentaux) — délai entre une demande et le début de la réponse ; distinction latence de bout en bout / TTFT (temps jusqu'au premier token), facteurs réseau et calcul, différence avec le débit (throughput). Fiche complète (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées : Wikipédia, NVIDIA), image hero générée via le compte Gemini de l'utilisateur (jpg + webp 1200×670, sans texte). Migration réversible, anti-doublon par slug.

## [1.65.98] - 2026-06-07

### Changed
- Glossaire (/glossaire) — refonte de la zone recherche+filtres en **toolbar sticky compacte** (best practice UX 2026 : Baymard, NN/g, eBay, Material). La barre slim (recherche + bouton « Filtres » avec compteur d'actifs + compteur de résultats) suit désormais le scroll de façon non envahissante (~65px) ; les filtres (catégorie, type, A-Z) sont déplacés dans un **panneau dropdown** ouvert à la demande ; les filtres actifs s'affichent en **chips supprimables**. Synchronisation avec le header sticky du site (offset 90px desktop / 60px mobile, jamais de chevauchement) via MutationObserver sur `.sticky-on`. WCAG 2.2 : `scroll-padding-top` (focus non masqué), cibles ≥44px, focus visible, `position:static` en très faible hauteur (reflow). Correctif `position:sticky` (override `overflow` du `.page-wrapper`) **scopé à la seule page glossaire** (`!important`), zéro impact site-wide (vérifié sur /blog). Filtrage Alpine 100% client inchangé.

## [1.65.97] - 2026-06-07

### Added
- Glossaire : terme **Tokenpocalypse** (apocalypse des tokens, cat Intelligence artificielle) — néologisme 2026 décrivant l'explosion des coûts de tokens (agents IA, jusqu'à 1000×), le durcissement des limites de contexte/quotas et la fin des forfaits illimités. Fiche complète (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées : Stanford Digital Economy Lab, Yahoo Finance), image hero générée via le compte Gemini de l'utilisateur (jpg + webp 1200×670, sans texte). Migration réversible, anti-doublon par slug.

## [1.65.96] - 2026-06-07

### Added
- Glossaire (batch #13, dernier lot du backlog audit) : 3 termes « boucle d'entraînement » — **Époque** (epoch), **Batch** (lot d'entraînement), **Itération** (cat Concepts fondamentaux). Fiches complètes (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées chacun), images hero générées via le compte Gemini de l'utilisateur (jpg + webp 1200×670). Migration réversible, anti-doublon par slug. **Backlog audit glossaire clos : 405 termes au total.**

## [1.65.95] - 2026-06-07

### Added
- Glossaire (batch #12) : 3 termes « calcul & métriques » — **CUDA** (Compute Unified Device Architecture, cat Acronymes et sigles), **F1-score** (score F1, cat Données et traitement), **Perplexité** (perplexity, cat Données et traitement). Fiches complètes (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées chacun), images hero générées via le compte Gemini de l'utilisateur (jpg + webp 1200×670). Migration réversible, anti-doublon par slug.

## [1.65.94] - 2026-06-07

### Added
- Glossaire (batch #11) : 3 termes « média génératif » — **Inpainting** (retouche par masque, cat Outils et techniques), **Upscaling** (super-résolution, cat Outils et techniques), **Text-to-video** (texte vers vidéo, cat IA). Fiches complètes (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées chacun), images hero générées via le compte Gemini de l'utilisateur (jpg + webp 1200×670). Migration réversible, anti-doublon par slug.

## [1.65.93] - 2026-06-07

### Added
- Glossaire (batch #10) : 3 termes « alignement / capacités IA » — **Sycophancy** (flagornerie de l'IA, cat Sécurité et éthique), **Reward hacking** (piratage de la récompense, cat Sécurité et éthique), **Frontière dentelée** (jagged frontier, cat IA). Fiches complètes (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées chacun), images hero générées via le compte Gemini de l'utilisateur (jpg + webp 1200×670). Migration réversible, anti-doublon par slug.

## [1.65.92] - 2026-06-07

### Added
- Blog — image éditoriale du **concentré IA hebdomadaire (semaine du 1 au 7 juin 2026)** générée via le compte Gemini de l'utilisateur (isométrique, charte Memora navy/orange, sans texte) ; jpg 1200×670 (89 Ko) + webp (60 Ko) dans `public/images/blog/`. L'article (20 actualités, catégorie LE CONCENTRÉ) est publié en base.

## [1.65.91] - 2026-06-07

### Added
- Glossaire (batch #9) : 3 termes « capacités IA 2026 » — **Computer use** (usage de l'ordinateur, cat IA), **Deep research** (recherche approfondie, cat IA), **Instruction tuning** (ajustement par instructions, cat Concepts fondamentaux). Fiches complètes (définition, analogie, exemple, le saviez-vous, réponse en une phrase, 2 FAQ, 2 sources vérifiées chacun), images hero générées via le compte Gemini de l'utilisateur (jpg + webp 1200×669). Migration réversible, anti-doublon par slug.

## [1.65.90] - 2026-06-07

### Added
- **Glossaire — 3 termes « fiabilité LLM/RAG »** (batch #8) : **Reranking (reclassement)**, **Grounding
  (ancrage)**, **Sortie structurée**. Fiches complètes au standard (sources vérifiées 200 : Pinecone, Jina,
  Google Vertex, IBM, OpenAI, JSON Schema). Images via le compte Gemini de l'utilisateur. Migration
  réversible. Glossaire à 390 termes.

## [1.65.89] - 2026-06-07

### Added
- **Glossaire — 3 termes « architecture Transformer »** (batch #7, catégorie « Concepts fondamentaux ») :
  **Espace latent**, **Encodeur-décodeur**, **Encodage positionnel**. Fiches complètes au standard
  (définition, analogie, exemple, le saviez-vous, AEO, FAQPage, sources vérifiées 200 : DataFranca, IBM,
  Vaswani 2017, d2l). Images via le compte Gemini de l'utilisateur en Playwright. Migration réversible.
  Glossaire à 387 termes.

## [1.65.88] - 2026-06-07

### Added
- **Glossaire — 3 termes « agents & sûreté 2026 »** (batch #6, catégorie « IA ») : **Garde-fous (guardrails)**,
  **A2A (Agent-to-Agent)**, **Effondrement de modèle (model collapse)**. Fiches complètes au standard
  (définition, analogie, exemple, le saviez-vous, AEO, FAQPage, sources vérifiées 200 : IBM, Microsoft,
  GitHub A2A, Nature 2024). Images générées via le compte Gemini de l'utilisateur en Playwright (full-res).
  Migration réversible. Glossaire à 384 termes.

## [1.65.87] - 2026-06-07

### Added
- **Glossaire — 3 termes « tendances 2025-2026 »** (batch #5, catégorie « IA ») : **SLM (petit modèle de
  langage)**, **Modèle frontière**, **Poids ouverts**. Fiches complètes au standard (définition, analogie,
  exemple, le saviez-vous, AEO, FAQPage, sources vérifiées 200, image hero `.webp` + og:image `.jpg`).
  **Images générées via le compte Gemini de l'utilisateur en Playwright** (méthode imposée, full-res via
  « Télécharger en taille réelle »). Migration réversible. Glossaire à 381 termes.

## [1.65.86] - 2026-06-07

### Improved
- **Élagage SEO actualités — R4 : whitelist de rubriques protégées** (best-practice 2026 « hard-exclusions ») :
  nouvelle clé `config/news/seo_prune.php` → `protect_categories` (liste de `category_tag` jamais élagués,
  quelles que soient l'ancienneté/les vues). Défaut **vide** (aucun effet → 100 % additif et sûr). Les
  `category_tag` NULL restent élageables. Validé MySQL (rubrique protégée → index, autre → noindex).
  Rend la décision **multi-signal** (âge + vues + rubrique). R2 (signal GSC) et R6 restent différés.

## [1.65.85] - 2026-06-07

### Improved
- **Élagage SEO des actualités — remédiations post-audit** (audit v1.65.84, note 78/100) :
  - **R1** — la commande `news:prune-seo` **journalise** désormais chaque exécution (`Log::info`) et **notifie
    IndexNow** (`IndexNowService::submitBatch`) des URLs passées en noindex → déindexation plus rapide + traçabilité
    (corrige le bypass des observers par le mass-update + le cron muet).
  - **R3** — **auto-healing** : une actualité noindex redevenue performante (`views_count >= max_views`) repasse
    automatiquement en `index` (symétrie, évite de pénaliser un regain de trafic).
  - **R5** — **test automatisé** (`PruneSeoCommandTest`, Pest) + validation fonctionnelle MySQL (noindex /
    auto-healing / reset / dry-run / disabled = 5/5).
  - `--dry-run` affiche maintenant aussi les candidats « ré-index ». Toujours 100 % réversible.
  Différé (décisions structurelles) : R2 multi-signal GSC, R4 whitelist/maillage, R6 batchs+monitoring.

## [1.65.84] - 2026-06-07

### Added
- **Élagage SEO automatique et réversible des anciennes actualités** (anti-index-bloat, best practice 2026) :
  nouvelle colonne `news_articles.seo_status` (index|noindex|gone) + commande `news:prune-seo`
  (`--dry-run`, `--reset`) planifiée **mensuellement** (scheduler Laravel existant — aucun cron ajouté).
  Politique pilotée par `config/news/seo_prune.php` (zéro hardcode) : les actualités publiées depuis
  > 12 mois ET vues < 30 fois passent en **`noindex, follow`** (sorties de l'index + du sitemap, mais
  accessibles et l'autorité circule) ; les performantes restent indexées. Tier **410 Gone** disponible
  mais **désactivé** par défaut. 100 % réversible (flag DB, aucune suppression ; `--reset` annule).
  Évite la pénalité « index bloat » / Helpful Content tout en préservant le trafic longue traîne (données GSC).
  Master layout : robots `noindex,follow` par page via `@section('page_noindex')`. Réversible (`down()` + tag git).

## [1.65.83] - 2026-06-07

### Added
- **Glossaire — 3 termes « évaluation des modèles »** (batch P0 #4) : **Précision et rappel**,
  **Matrice de confusion** (catégorie « Données et traitement »), **LLM-as-a-judge** (catégorie « IA »).
  Fiches complètes au standard (définition, analogie, exemple chiffré, le saviez-vous, AEO, FAQPage,
  sources vérifiées 200, image hero `.webp` + og:image `.jpg`). Migration réversible. Glossaire à 378 termes.

## [1.65.82] - 2026-06-07

### Added
- **Glossaire — 3 termes fondamentaux ML/réseaux** (batch P0 #3) : **Sous-apprentissage** (complète la paire
  avec Surapprentissage), **Généralisation**, **Fonction d'activation** (catégorie « Concepts fondamentaux »).
  Fiches complètes au standard (définition, analogie, exemple, le saviez-vous, AEO, FAQPage, sources vérifiées,
  image hero `.webp` + og:image `.jpg`). Migration réversible. Glossaire à 375 termes.

## [1.65.81] - 2026-06-07

### Added
- **Glossaire — 3 termes « mécanique du RAG »** (batch P0 #2) : **Chunking**, **Recherche sémantique**,
  **Similarité cosinus** (catégorie « Données et traitement »). Fiches complètes au standard (définition,
  analogie, exemple chiffré, le saviez-vous, AEO, FAQPage 2 Q/R, sources GEO vérifiées 200, image hero
  `.webp` + og:image `.jpg` 1200×669). Migration réversible (anti-doublon par slug, `down()`). Glossaire à 372 termes.

## [1.65.80] - 2026-06-06

### Added
- **Glossaire — 3 termes fondamentaux d'entraînement ML** (batch P0 #1, audit des manques) :
  **Rétropropagation**, **Descente de gradient**, **Fonction de perte** (catégorie « Concepts fondamentaux »).
  Fiches complètes conformes au standard : définition, analogie, exemple chiffré, « le saviez-vous »,
  réponse AEO (one_sentence_answer), FAQPage (2 Q/R), sources GEO vérifiées ({label,url} 200), image hero
  `{slug}.webp` + og:image `{slug}.jpg` (1200×669, compressées). Migration réversible (anti-doublon par slug,
  `down()` supprime par slug). Contenu via délégation MCP (gpt-4o-mini) + faits sourcés (sonar-pro),
  images via multi-ai-mcp (gemini-2.5-flash-image, session Playwright indisponible), affiné par le superviseur.

## [1.65.79] - 2026-06-06

### Fixed
- **Glossaire — dédoublonnage des catégories (données prod)** : la table `dictionary_categories`
  contenait des lignes dupliquées (catégories ré-insérées), d'où un `<select>` de filtre avec chaque
  catégorie en triple. Migration **réversible** `2026_06_06_030000_dedup_dictionary_categories` :
  sauvegarde complète (`dict_categories_dedup_bak` + mapping `dict_terms_catmap_dedup_bak`),
  groupe par `name` brut (ne fusionne QUE les doublons identiques), **réassigne** les termes des
  doublons vers la catégorie canonique (icône non-nulle puis plus petit id) AVANT suppression
  (FK `nullOnDelete`), puis supprime les doublons. **Zéro perte de termes**. `down()` restaure tout.
  Garde-fou additionnel : `->unique('name')` sur le filtre du glossaire (anti-doublons d'affichage futurs).
  Testé en local (up + down sans erreur). Réversible (tag `backup-pre-glossaire-dedup-v1.65.78`).

## [1.65.78] - 2026-06-06

### Fixed
- **Glossaire — « Duplicate key on x-for » (17×)** : le tableau `$categoriesForFilter` (filtre du
  dictionnaire) ne contenait pas de champ `id`, alors que le `<template x-for="cat in categories"
  :key="cat.id">` l'utilisait comme clé → clés `undefined` dupliquées. Ajout de `'id' => $c->id`.
  Le filtrage par catégorie se fait par `slug` (inchangé) → zéro impact comportemental, 366 termes
  rendus normalement. Découvert pendant la vérif Alpine (v1.65.77).

## [1.65.77] - 2026-06-06

### Fixed
- **Warning « Detected multiple instances of Alpine running » (site-wide)** : le thème chargeait Alpine 3
  via CDN EN PLUS de Livewire 4 (qui embarque déjà Alpine + ses plugins). Le **core Alpine CDN est retiré**
  du master ; seul le plugin `@alpinejs/intersect` reste (il s'attache à l'Alpine de Livewire via `alpine:init`).
  Tous les `Alpine.data()` du site sont déjà enregistrés sous `alpine:init` → compatibles. Une seule instance
  Alpine désormais. Sourcé pp_search (doc Livewire 4, juin 2026). Réversible (`backup-pre-p2-alpine-panel-v1.65.76`).
- **Panneau d'anonymisation du constructeur trop serré (~39 ch/volet)** : l'éditeur imbriqué dans la card
  étroite (col-lg-8) affichait 2 colonnes de ~309 px. Il est désormais **empilé** (`#cpAnonPanel .anon-grid`
  en 1 colonne) → volets pleine largeur (~83 ch), bien plus lisibles. Scoppé au constructeur ; l'anonymiseur
  autonome conserve son affichage 2 colonnes.

## [1.65.76] - 2026-06-06

### Fixed
- **Bouton « Copier » ne recouvre plus le texte (toutes largeurs, audit 1440 px)** : le bouton flottant
  `position:absolute` en haut-droite de la boîte de sortie masquait la 1re ligne du texte anonymisé à
  **toutes** les largeurs (mobile → desktop 1440). Il est désormais placé dans une **ligne d'en-tête**
  (`.anon-pane-head`, au-dessus de la boîte, à droite du label) → zéro chevauchement. Les deux volets
  reçoivent une `.anon-pane-head` de même hauteur → l'alignement des boîtes en mode 2 colonnes est
  préservé. Compact (~2.2em) sur desktop, ≥44 px en tactile (≤860 px). Composant + CSS, appliqué aux
  2 outils (anonymiseur + constructeur). Réversible (tag `backup-pre-copybtn-header-v1.65.75`).

## [1.65.75] - 2026-06-06

### Fixed
- **UX tablette éditeur d'anonymisation** (audit Playwright 768×1024 + 1024×768) : les correctifs tactiles
  (bouton « Copier » en flux normal hors du texte + bascule de vue ≥44 px) passent du breakpoint mobile
  (≤480 px) à **≤860 px** → couvre la tablette portrait, où le bouton « Copier » flottant chevauchait la
  première ligne du texte anonymisé (overlap 8 px mesuré). À ≤860 px la grille est déjà empilée, donc
  aucun impact sur l'alignement des volets en mode 2 colonnes (≥1024 px inchangé). Police 16 px reste
  scoppée ≤480 px (anti-zoom iOS iPhone). CSS uniquement, desktop inchangé.

### Notes
- « Split » à 768 px portrait = les 2 volets empilés et visibles (comportement tablette voulu, lisible) —
  pas un défaut. Forcer 2 colonnes à 768 px cramperait l'éditeur riche.

## [1.65.74] - 2026-06-06

### Changed
- **Pop-up infolettre retirée des pages outils** (`outils/*`) : le scroll-trigger (bottom-sheet ~234 px sur mobile,
  ~29 % de l'écran) n'apparaît plus pendant l'usage d'un outil (éditeur/formulaire = tâche focalisée), où il
  masquait la barre d'outils et risquait la pénalité Google « interstitiels mobiles intrusifs ». Conservée sur le
  contenu (blog, articles, index) où le déclenchement au scroll reste pertinent. Décision sourcée (pp_search NN/g,
  juin 2026). Réversible (retrait du `@unless`). Aucune autre page affectée, aucune donnée supprimée.

### Notes
- Modale cookies : déjà conforme (`max-height: min(90vh,640px); overflow-y:auto`) — le « bouton hors viewport »
  de l'audit était un artefact Playwright (clic avant scroll), aucun correctif nécessaire.

## [1.65.73] - 2026-06-06

### Fixed
- **UX mobile éditeur d'anonymisation** (anonymiseur + panneau du constructeur, audit Playwright 375 px) :
  - police des champs éditables portée à 16 px sous 480 px → supprime le zoom automatique de Safari iOS au focus ;
  - bascule de vue (Éditeur/Split/Aperçu) : cibles tactiles ≥44 px, pleine largeur sur mobile ;
  - bouton « Copier » flottant remis en flux normal sous 480 px → ne recouvre plus le texte de sortie, cible ≥44 px.
  - CSS uniquement, scoppé `@media (max-width:480px)` ; desktop inchangé.

## [1.65.72] - 2026-06-06

### Fixed
- **Constructeur de prompts** : icône du bouton « Insérer dans la tâche » illisible (emoji ➕ sombre
  sur fond teal foncé) remplacée par une icône SVG `+` en `currentColor` (blanche, contraste correct).

## [1.65.71] - 2026-06-06

### Changed
- **DRY — éditeur d'anonymisation réutilisable** : extraction de l'éditeur de `/outils/anonymiseur`
  (barre d'outils, bulle de sélection, surlignage/annotation, modes réaliste/jetons, popover d'occurrence)
  dans un composant Blade unique `<x-tools::anonymizer-editor>` + un partial scripts partagé
  `tools::partials.anonymizer-scripts`. Slot `previewActions` pour adapter les boutons à chaque page.
- **Constructeur de prompts** : le panneau « 🛡️ Anonymiser un texte » réutilise désormais l'éditeur
  COMPLET (même UX que l'anonymiseur : sélection, surligner, anonymiser) au lieu d'un mini-formulaire.
  Le bouton « Insérer dans la tâche » lit le texte anonymisé partagé (`window.lvAnonUI.anonPlain`).
- `anonymizer-ui.js` expose `window.lvAnonUI` (init défensif uniquement si l'éditeur est présent).
- Aucune duplication de markup ni de logique entre les deux outils ; zéro régression sur l'anonymiseur.

## [1.65.70] - 2026-06-06

### Added

- **Pied de page — crédit « Conçu et hébergé par MEMORA solutions · Entreprise canadienne 🍁 »** : ligne discrète sous le copyright (site-wide), d'après les best practices juin 2026 (sous le copyright, typo réduite, couleur atténuée WCAG, ancre = nom de marque). Lien `rel="nofollow noopener noreferrer" target="_blank"` vers https://memora.solutions (évite un profil de liens artificiel sur un lien site-wide).

## [1.65.69] - 2026-06-06

### Changed / Fixed

- **Anonymiseur — la colonne « anonymisé » suit la colonne de gauche en TEMPS RÉEL** : dès qu'on colle/écrit dans l'éditeur, le panneau de droite se met à jour (anti-rebond ~120 ms), sans devoir cliquer « Détecter et anonymiser ». Avant masquage : la droite reflète le texte ; après : anonymisé en direct.
- **Anonymiseur — le popover d'occurrence se ferme au clic à l'extérieur** (+ Échap) : il restait ouvert quand on cliquait dans le texte (le handler excluait la zone annotée). Cliquer ailleurs le ferme désormais ; cliquer une autre entité le rouvre.
- **Anonymiseur — « Réinitialiser » et « Oublier mes données » sont maintenant distincts** : « ↺ Réinitialiser le masquage » efface l'anonymisation mais **conserve votre texte** (pour re-masquer autrement) ; « 🗑️ Oublier mes données » efface **tout** (texte + correspondances). Lèvent la confusion (les deux faisaient la même chose).

## [1.65.68] - 2026-06-06

### Changed

- **Anonymiseur — un seul bouton « 🕵️ Détecter et anonymiser »** (demande utilisateur) : remplace les deux boutons séparés de la barre d'outils ; détecte puis anonymise tout en un clic. Les actions séparées « 🔍 Détecter seulement » et « 🕵️ Tout anonymiser » sont déplacées dans le menu ⋯ Actions (toujours disponibles). Nouvelle méthode `detectAndAnonymizeAll()` ; `detect(silent)` pour éviter le double toast. **Vérifié Playwright** : un clic → 3 entités détectées + anonymisées (0 candidat restant, données réelles absentes), 0 erreur console.

## [1.65.67] - 2026-06-06

### Changed

- **Anonymiseur — « Tout anonymiser » remonte dans la barre d'outils** (demande utilisateur) : à droite de « 🔍 Détecter », le bouton est désormais « 🕵️ Tout anonymiser » (action la plus courante après détection). « ✍️ Anonymiser la sélection » est déplacé dans le menu ⋯ Actions (fonction inchangée). Aucun changement de logique (ids conservés).

## [1.65.66] - 2026-06-06

### Added

- **Anonymiseur — le courriel reprend le MÊME faux nom que la personne (cohérence)** : quand le nom d'une personne apparaît dans la partie locale d'un courriel (« martin.rousseau@hexasoft.io »), le faux courriel utilise désormais le même faux nom que la personne (« Martin Rousseau » → « André Gauthier » ⇒ « andre.gauthier@… ») au lieu d'un nom aléatoire incohérent. Nouvelle fonction `relinkEmails()` (moteur) appelée après chaque anonymisation et changement de mode ; remplace les jetons du nom dans la partie locale, conserve les séparateurs (`.`/`_`/`-`) et le faux domaine, garantit l'unicité (réversibilité préservée). Les courriels sans nom lié restent aléatoires. **Vérifié** : test Node (round-trip 100 % sur cas variés) + Playwright UI (cohérence prénom.nom + restauration exacte).

## [1.65.65] - 2026-06-06

### Added

- **Anonymiseur — le texte de l'éditeur est conservé dans le navigateur (restauré à votre retour)** : demande utilisateur. Le contenu est sauvegardé en `localStorage` (clé `lv_anon_source_v3`, **stable et non purgée aux mises à jour** → survit aux déploiements ; **jamais envoyé à un serveur**) à chaque saisie, et restauré au chargement avec sa mise en forme. Effacé uniquement par « Réinitialiser » ou « 🗑️ Oublier mes données ». « Réinitialiser » efface désormais **tout le contenu** (texte + correspondances + sauvegarde). Note de confidentialité mise à jour (transparence + rappel d'effacer sur un poste partagé). **Vérifié Playwright** : saisie → rechargement → texte + format restaurés ; reset → vidé et persistant.

## [1.65.64] - 2026-06-06

### Fixed

- **Accessibilité/SEO — `h1` manquant ajouté sur 2 outils** (oscilloscope-rlc, roue-tirage) : ces pages n'avaient aucun `<h1>` (uniquement des `h2`). Ajout d'un `h1` accessible (sr-only, technique clip — lu par Google et les lecteurs d'écran, zéro impact visuel sur ces outils canvas/app dont le titre s'affiche déjà via l'UI et le fil d'Ariane). Chaque page outil a désormais exactement un `h1`.

## [1.65.63] - 2026-06-06

### Changed

- **Anonymiseur — surlignage optimisé (fast-path) sur les longs documents** (audit P2, plan validé) : `highlightEntitiesInElement` ne lance plus chaque regex sur chaque nœud texte (O(N×M)). Pré-calcul du 1er mot normalisé de chaque entité ; pour un nœud, on saute une entité si son 1er mot n'y figure pas (`indexOf`) — skip **sûr** (le 1er mot doit être présent pour tout match, même avec espaces flexibles). **Vérifié Playwright** : surlignage identique (cas piège « Jean  Dubé » double espace OK), 200 paragraphes / 800 surlignages en **10 ms**, 0 régression.
- **Anonymiseur — `execCommand('insertHTML')` conservé volontairement** (recherche juin 2026) : c'est le seul levier qui préserve l'annuler/refaire natif ; un remplacement par `Range.insertNode` casserait l'undo. Décision documentée en commentaire (pas de refactor à régression).

## [1.65.62] - 2026-06-06

### Changed

- **Publicités AdSense retirées des pages d'outils traitant des données personnelles** (décision suite à l'audit ; posture de confiance Loi 25) : le chargeur AdSense du layout (`master.blade.php`) ne se déclenche plus sur les pages déclarant `@section('no_ads')`. Activé sur l'**anonymiseur** et le **constructeur de prompts** (qui manipulent du texte potentiellement personnel). Mécanisme scopé via section Blade : **aucun impact sur les autres pages** (les pubs restent actives partout ailleurs — revenu préservé). Liste extensible à tout futur outil sensible.

## [1.65.61] - 2026-06-06

### Added / Fixed

- **Anonymiseur — bouton « 🗑️ Oublier mes données » (vie privée, audit P0)** : nouvel item du menu ⋯ Actions qui efface TOUT de ce navigateur (texte, sortie, réponse IA, **table de correspondance** `lv_anon_rules_v3`/`overrides_v3` en localStorage). Note explicite ajoutée dans l'accordéon « 100 % local » (effacer sur un poste partagé). Répond au constat d'audit : les correspondances vraie↔fictive persistaient en localStorage.
- **Anonymiseur — défense en profondeur XSS (audit P1)** : `renderAnnotated` et `updateOutput` re-sanitizent désormais le HTML de l'éditeur (`sanitizePastedHtml`) avant toute injection `innerHTML`, au lieu de se fier uniquement à la sanitisation au collage. Vérifié Playwright : le formatage (gras, listes) reste préservé.
- **Constructeur de prompts — méta-description enrichie (SEO, audit P2)** : `tools.description` passe de 53 à ~165 car. (migration `2026_06_06_020000`, réversible) — décrit persona/tâche/audience/format/techniques + modèles cibles (ChatGPT, Claude, Gemini, Mistral).

## [1.65.60] - 2026-06-06

### Fixed

- **Anonymiseur — comble 3 fuites de détection identifiées par l'audit (NAS, montants format québécois, noms abrégés)** : l'audit exhaustif des outils (rapport `.outils/audit-anonymiseur-constructeur-2026-06-06.md`) a mesuré une détection automatique de 80 % avec des faux négatifs sensibles. Ajout à `detectEntities` : (1) **NAS** (numéro d'assurance sociale) — contextuel (étiquette « NAS »/« assurance sociale ») + isolé validé par **algorithme de Luhn** (évite les faux positifs sur tout numéro à 9 chiffres) ; (2) **montants format québécois** où le « $ » suit le nombre (« 1 250,00 $ », « 2 750$ ») ; (3) **noms abrégés** initiale + nom après titre (« Mme L. Gagnon », « Dr. A. Roy »). **Vérifié (test Node, corpus 12 cas PII québécois)** : détection 80 % → **100 % (40/40)**, réversibilité round-trip **100 %**, **zéro régression** (cas noms/médicaux), **zéro faux positif** (numéros non-Luhn et téléphones non confondus).

## [1.65.59] - 2026-06-05

### Fixed

- **Accessibilité — icônes SVG du bouton plein écran marquées décoratives** (audit WCAG de l'anonymiseur fraîchement publié). Le bouton porte déjà `aria-label="Plein écran"` ; ses 2 SVG reçoivent `aria-hidden="true" focusable="false"` (cohérent avec le bouton « partager »), ce qui lève le signalement WCAG 1.1.1 (« SVG missing accessible name ») sans double annonce pour les lecteurs d'écran. Passe qualité de mise en ligne : indexabilité OK (`robots: index,follow`, présent au sitemap, canonical), contraste du nouveau panneau d'anonymisation du constructeur conforme AA (6,77:1 et 7,34:1). Les autres signalements de l'audit headless sont des faux positifs connus (blanc/blanc = fond foncé du header / modale cachée non vus par le scanner ; « Tab » = éléments dans des panneaux volontairement masqués).

## [1.65.58] - 2026-06-05

### Fixed

- **Bouton plein écran des outils — icône « brisée » corrigée** (signalé sur le constructeur de prompts, partial partagé `tools::partials.fullscreen-btn`). Cause : la règle responsive globale `svg { max-width:100%; height:auto }` (charte.css) s'appliquait à la SVG inline 16×16 du bouton ; comme ce bouton est `ct-btn-ghost ct-btn-xs` (largeur **auto**, contrairement au bouton « partager » en `ct-btn-icon` 44×44 fixe), le dimensionnement devenait circulaire et l'icône se réduisait/déformait. Correctif ciblé **zéro risque** : taille forcée en style inline (`width:16px;height:16px;flex-shrink:0`) sur les 2 SVG du partial (bat la règle globale). Corrige l'icône sur **tous** les outils, sans toucher aux autres médias.

## [1.65.57] - 2026-06-05

### Added

- **Anonymiseur ↔ Constructeur de prompts — liaison des deux outils (utilisables séparément OU ensemble, 100 % local)** : d'après la recherche best practices juin 2026 (Perplexity ; privacy-by-design, pas de PII en URL), approche hybride notée 93/100 (module partagé in-page) + 88/100 (handoff sessionStorage), évitant le deep-link URL (35/100, fuite PII).
  - **Module partagé in-page** (pattern 2) : le constructeur de prompts charge le moteur `window.AnonymizerCore` et expose un panneau repliable « 🛡️ Anonymiser un texte (optionnel) » (progressive disclosure) — anonymise un texte localement puis l'insère dans le champ « Objet de la tâche » (`prompt-anon-panel.js`, vanilla, 100 % local).
  - **Handoff sessionStorage** (pattern 1) : bouton « ✨ Créer un prompt → » dans l'anonymiseur qui transmet **uniquement le texte anonymisé** via `sessionStorage` (volatile, same-origin — **jamais dans l'URL**) ; le constructeur l'importe automatiquement, affiche un toast et **efface la clé** (one-time). Lien « ↗ Anonymiseur complet » côté constructeur.
  - Les deux outils restent **100 % autonomes**. Aucune donnée personnelle ne quitte le navigateur.

## [1.65.56] - 2026-06-05

### Changed

- **Anonymiseur de texte — PUBLIÉ publiquement** (GO user « publie l'outil ») après la refonte UX/UI complète (v1.65.43→55) et la certification E2E intégrée PASS. Migration `2026_06_05_210000_publish_anonymiseur_go_user` : `tools.is_under_construction = false` pour `slug='anonymiseur'` (le déploiement exécute `php artisan migrate --force` puis vide les caches). Seeder par défaut aligné sur `false`. L'outil n'est plus en placeholder « en construction » : il est accessible à tous sur `/outils/anonymiseur` et listé sans badge « Bientôt ». Réversible via le `down()` de la migration.

## [1.65.55] - 2026-06-05

### Added

- **Anonymiseur — les données restaurées sont surlignées + leur ancienne valeur anonyme se révèle au survol/focus** : dans « Résultat avec vos vraies données », chaque vraie donnée remise en place est **surlignée en vert** (= restaurée). Au **survol OU au focus clavier**, un tooltip accessible affiche « Anonymisé : *faux* » (fermable avec Échap, survolable/persistant — conforme **WCAG 2.2 §1.4.13**, pas le `title` natif). Bouton **« 👁️ Voir les valeurs anonymes »** : bascule globale qui révèle tous les faux en ligne « vrai (faux) » pour relecture/audit (mobile/clavier-friendly). Approche notée 92/100 (recherche pp_search juin 2026 : tooltip accessible custom + bascule globale > badge inline > `title` natif). `#restoredOutput` passe de `textarea` à div riche ; la copie du résultat reste le texte exact (`restoredPlain`). **Vérifié Playwright** : 3 données surlignées avec `data-fake`+`aria-label`, tooltip hover **et** focus, fermeture Échap, bascule `aria-pressed`, 0 erreur console.

## [1.65.54] - 2026-06-05

### Fixed

- **Anonymiseur — restauration plus robuste quand la réponse IA est collée sans séparateurs + bornes de mots sensibles aux accents** : trouvé lors d'une certification E2E intégrée. (1) `restore()` utilise désormais `buildAccentInsensitiveUnboundedRegex` (sans `\b`) car les pseudonymes sont uniques par construction — une valeur dont la fin touche le mot suivant (ex. `…01RAMQ…` dans un texte collé) est désormais restaurée. (2) `buildAccentInsensitiveBoundedRegex` (détection/anonymisation) : les bornes `\b` (ASCII seulement) deviennent des bornes explicites incluant les lettres accentuées `À-ÿ` → meilleures limites de mots pour « Gagné », « Émilie », etc. **Vérifié (test Node)** : détection inchangée, **round-trip 100 % (3/3)**, restauration d'adjacence corrigée. **Certification E2E intégrée PASS** : 7 entités, format préservé des 2 côtés, restauration complète, rapport structuré, 0 erreur console.

## [1.65.53] - 2026-06-05

### Added

- **Anonymiseur — la colonne de droite (texte anonymisé) surligne aussi les valeurs, pour comparer facilement** : le panneau résultat passe de `textarea` à une vue riche miroir de la colonne gauche. Les valeurs remplacées y sont **surlignées** (fond teal) et les candidats non encore masqués **soulignés**, exactement aux mêmes endroits qu'à gauche → comparaison original ↔ anonymisé immédiate. La mise en forme (gras, listes) est conservée des deux côtés. La fonction `highlightEntitiesInElement` accepte un remplacement par marque + un mode non interactif (pas de boutons/`tabindex` inertes à droite). **La copie vers l'IA reste le texte simple exact** (`anonPlain` via l'anonymisation à plat, avec les overrides), indépendant de l'affichage riche. **Vérifié Playwright** : surlignage à droite (faux affichés, vraies valeurs absentes), surlignage imbriqué dans `<strong>`, listes préservées, alignement gauche/droite conservé, 0 bouton inerte, 0 erreur console.

## [1.65.52] - 2026-06-05

### Fixed

- **Anonymiseur — meilleure détection des noms dans les lettres (médicales/admin)** : « Patient Louise Gagnon » détectait « Patient Louise » (le mot « Patient » en début de phrase pris pour un prénom) et ratait le vrai nom. Ajout des mots courants qui précèdent un nom aux mots ignorés (`patient`, `patiente`, `usager`, `bénéficiaire`, `médecin`, `concernant`, `référence`, `sujet`, `destinataire`, `dossier`, `date`) + **rembobinage du scan** : quand le 1er mot d'une paire est un mot courant, on ne consomme pas le 2e mot et on rescanne pour capter le vrai nom complet derrière. **Vérifié (test Node)** : « Patient Louise Gagnon » → « Louise Gagnon », « Concernant Julie Morin » → « Julie Morin », « Le bénéficiaire Marc Tremblay » → « Marc Tremblay », sans régression (« Dr Jean Dubé » → « Jean Dubé », « Dr Lavoie » → « Lavoie »).

## [1.65.51] - 2026-06-05

### Changed

- **Anonymiseur — bouton « Copier » accessible en haut du panneau résultat (plus seulement en bas)** : d'après les meilleures pratiques juin 2026 (Perplexity ; éviter « Copier » uniquement en bas sur un long contenu), ajout d'un bouton « 📋 Copier » flottant en haut-droite du panneau « Texte anonymisé » (pattern bloc de code, overlay → ne casse pas l'alignement gauche/droite). Le bouton « Copier pour l'IA » du bas est conservé (2e accès pour les longs contenus) et « J'ai la réponse de l'IA → » reste en bas comme action de progression séparée. Feedback « ✓ Copié » sur les boutons. **Vérifié Playwright** : bouton flottant en `position:absolute` haut-droite, colonnes split toujours alignées (262.5px=262.5px), 0 erreur console.

## [1.65.50] - 2026-06-05

### Changed

- **Anonymiseur — rapport de restauration restructuré (UX lisible)** : la longue phrase « X valeur(s) restaurée(s) sur N. Non retrouvées : « … », « … », … » est remplacée par un rapport structuré : en-tête avec icône + compte (✅ si ≥1 restaurée, ⚠️ si 0), une note explicative (« absentes de la réponse collée — normal si l'IA ne les a pas reprises »), puis les valeurs non retrouvées en **puces** lisibles. **Déduplication du bruit** : un nom de famille ou prénom seul (« Louise », « Gagnon ») n'apparaît plus si le nom complet (« Louise Gagnon ») est déjà listé. Nouveau `buildRestoreReportHtml()` dans `anonymizer-rich.js`. **Vérifié Playwright** : 3 puces correctes (Roy / Louise Gagnon / Julie Morin), sous-parties dédupliquées, 0 erreur console.

## [1.65.49] - 2026-06-05

### Fixed

- **Anonymiseur — débordement horizontal sur mobile (375px) corrigé** : trouvé lors d'une passe QA proactive (Playwright). La `.anon-textarea` avait `width:100%` sans `box-sizing:border-box` → padding + bordure provoquaient un débordement de 18px à 375px. Ajout de `box-sizing:border-box`. **Passe QA complète PASS 13/13** : 3 vues (Éditeur/Split/Aperçu), pipeline collage riche → détection → anonymisation (•/1. + faux, nom seul vs complet) → restauration exacte, clavier (Entrée sur entité), responsive 375/768/1280 sans débordement, 0 erreur console.

## [1.65.48] - 2026-06-05

### Fixed

- **Anonymiseur — les deux champs (original / anonymisé) démarrent maintenant au même niveau** : le label de gauche « Votre texte (cliquez les passages soulignés pour les anonymiser) » occupait 3 lignes (texte sur 2 lignes + le « ? » qui retombait dessous), poussant la boîte de gauche bien plus bas que celle de droite. Corrigé : label raccourci à « Votre texte » (le détail reste dans l'aide « ? » et la légende), et `.anon-pane-label` passe en hauteur fixe égale avec `flex-wrap:nowrap` (le « ? » reste à côté du texte). **Vérifié Playwright** : labels 32px = 32px, les deux champs démarrent au même Y (262.5px = 262.5px).

## [1.65.47] - 2026-06-05

### Fixed

- **Anonymiseur — espacement identique entre le volet original (gauche) et anonymisé (droite)** : le volet gauche était plus aéré (line-height 1.7 + marges de paragraphes/listes) que le textarea de sortie (line-height 1.5, sans marges), ce qui nuisait à la comparaison côte à côte. Uniformisé en CSS : line-height 1.5 partout, marges de bloc (p/ul/ol/li/titres) à 0 dans l'éditeur riche pour épouser le rythme du textarea, hauteur min des labels égalisée (`min-height` → les 2 boîtes démarrent au même Y), hauteur min des 2 boîtes alignée. **Vérifié Playwright** : line-height (24px=24px), padding-left (16px=16px), hauteur des labels (38px=38px) et position top des 2 boîtes (268.4px=268.4px) strictement identiques.

## [1.65.46] - 2026-06-05

### Changed

- **Anonymiseur — la sortie texte conserve la vraie puce « • » des listes à puces (au lieu d'un tiret « - »)** : suite à une remarque utilisateur (les puces de l'éditeur devenaient des tirets dans le texte anonymisé). `richToText()` sérialise désormais les `<ul>` avec « • » (identique à l'affichage de l'éditeur) ; les `<ol>` gardent « 1. / 2. ». La sortie envoyée à l'IA ressemble ainsi exactement à l'éditeur. **Vérifié Playwright** : `• Tension`/`• LDL`, `1. Analyse`/`2. Suivi`, puce conservée après anonymisation, 0 erreur console.

## [1.65.45] - 2026-06-05

### Fixed

- **Anonymiseur — un nom seul (prénom OU nom de famille) n'est plus remplacé par un nom complet inventé** : « Bonjour Dr Lavoie » devenait « Bonjour Dr Nathalie Morin » (prénom + nom fabriqués). Désormais un seul mot → un seul faux. Trois corrections : (1) `detectEntities` — un seul mot après un titre de civilité (Dr/M/Mme…) est classé `lastName` au lieu de `name` ; (2) `buildRules` — un `'name'` à un seul mot (ex. sélection manuelle) utilise un faux unique au lieu d'un prénom + nom ; (3) `guessCategory` (ui) — un mot capitalisé seul → `lastName`, deux mots ou plus → `name`. Les noms complets (« Dr Jean Dubé » → « Dr Isabelle Morin ») restent complets ; cohérence préservée entre un nom de famille seul et le même nom dans un nom complet. **Vérifié (test unitaire Node)** : « Dr Lavoie »→« Dr Fortin », « Mme Gagnon »→« Mme Lavoie », « Dr Jean Dubé »→« Dr Isabelle Morin », phrase mixte OK.

## [1.65.44] - 2026-06-05

### Added

- **Anonymiseur — la sortie vers l'IA conserve aussi les marqueurs de liste (`1.`, `2.`, `-`)** : complément du v1.65.43. Le texte simple dérivé de l'éditeur riche passe d'`innerText` (qui perdait les puces/numéros générés par CSS) à un nouveau `richToText()` (dans `anonymizer-rich.js`) qui sérialise `<ol>`/`<ul>` en marqueurs texte (`1. `, `- `, indentation des listes imbriquées). Les listes survivent donc de bout en bout : éditeur → texte anonymisé copié à l'IA → restauration. Détection, anonymisation et restauration intactes (les marqueurs ne font pas partie des valeurs d'entités). **Vérifié banc d'essai Playwright** : `richToText` 1./2./- corrects sans indentation parasite niveau 1, sortie anonymisée conserve les listes, anonymisation nom+courriel OK, restauration 3/3, 0 erreur console.

## [1.65.43] - 2026-06-05

### Added

- **Anonymiseur — l'éditeur conserve la mise en forme (gras, italique, listes à puces et numérotées, titres) au collage** : le champ de saisie passe de `textarea` (texte brut, qui supprimait tout format) à un éditeur riche `contenteditable`. Approche retenue après recherche best practices juin 2026 (Perplexity, doc ProseMirror/Tiptap paste-handler) : **éditeur riche + anonymisation sur les nœuds texte** (note 90/100), supérieure au Markdown round-trip (68) et au textarea brut (38), sans réintroduire de dépendance Tiptap (les bugs passés y étaient liés).
  - Nouveau fichier additif `anonymizer-rich.js` : `sanitizePastedHtml()` (liste blanche stricte `p/br/b/strong/i/em/u/ul/ol/li/h1-3/blockquote/a[href]`, nettoyage du HTML Word/Google Docs : styles, classes, `<span>`, scripts, balises `mso`/`o:p`) + `highlightEntitiesInElement()` (surlignage injecté **dans les nœuds texte** d'un clone du HTML riche → la mise en forme reste intacte ET les entités restent cliquables).
  - **Zéro régression sur le moteur réversible** : détection, anonymisation et restauration continuent sur le texte (`innerText`), la sortie pour l'IA reste en texte simple (c'est ce que l'IA reçoit). Bulle de sélection, popover par occurrence, modes réaliste/jetons, valeur personnalisée, bascule de vue : tous conservés.
  - **Vérifié en banc d'essai local (Playwright)** : sanitize Word 9/9, `<strong>/<ul>/<ol>` préservés à travers détection → annotation → sortie, 5 entités détectées+anonymisées (les vraies données disparaissent de la sortie), restauration 3/3 exacte, **0 erreur console**.

## [1.65.42] - 2026-06-05

### Fixed

- **Anonymiseur — boutons d'aide alignés sur la charte réelle du site** : mes boutons utilisaient `.ct-help-btn` avec le glyphe « ⓘ » (un caractère cercle-i → effet cercle-dans-cercle, présent seulement sur calculatrice). La charte dominante (constructeur-prompts, simulateur-fiscal, code-qr, roue-tirage) utilise un **« ? » rond** `ct-btn ct-btn-ghost ct-btn-xs` (22px, border-radius 50%). Basculé sur ce style **byte-identique** au bouton de référence (même classes + même style inline), en conservant `data-help-key` pour ouvrir la popup complète. Conforme à la capture utilisateur (bouton « ? » de la section persona du constructeur de prompts).

## [1.65.41] - 2026-06-05

### Fixed

- **Anonymiseur — boutons d'aide alignés sur la charte du site** : uniformisation des ⓘ (un seul glyphe « ⓘ » partout — un « ? » résiduel retiré ; un seul ⓘ par section ; l'explication « Seulement ici »/« Ma valeur » fusionnée dans l'aide « masquer »). **Vérifié visuellement** (Playwright) : identique à la référence `.ct-help-btn` du site (22×22px, cercle teal #064E5A).
- **Anonymiseur — rester en haut de l'éditeur après collage d'un long texte** : le champ auto-extensible faisait « tomber » la page en bas ; un handler de collage ramène la vue en haut du champ (offset toolbar). **Vérifié visuellement** : après 60 lignes collées, le haut de l'éditeur reste visible.

## [1.65.40] - 2026-06-05

### Added

- **Anonymiseur — boutons d'aide ⓘ (popups du thème) + valeur personnalisée partout (en construction/admin)**.
  - **Aides contextuelles** : boutons ⓘ sur les sections clés (affichage des volets, « comment ça marche », masquer une donnée, **éléments déjà masqués / « Différent ici »**, restauration), via le **composant officiel `<x-core::help-modal>`** (déjà global) + `window.HELP_CONTENT` → **100 % uniforme avec la charte**. Explications grand public.
  - **Valeur personnalisée (anti-régression)** rendue intuitive et disponible partout : la bulle de sélection et le popover d'une donnée masquée offrent **« ✎ Ma valeur »** (je choisis le remplacement, partout) ; le popover ajoute **« 🔀 Seulement ici »** (valeur distincte pour cette occurrence) et **« ↩︎ Annuler »**. `setCustomReplacement` (global) + `addOverride` (par occurrence). Validé **E2E Playwright** : 5/5 popups d'aide + 4/4 valeur personnalisée (sélection, globale, par occurrence) avec **restauration exacte**.

## [1.65.39] - 2026-06-05

### Added

- **Anonymiseur — bascule de vue « ✍️ Éditeur · ⬓ Split · 👁️ Aperçu »** (en construction/admin) : un *segmented control* au-dessus de l'éditeur permet d'**agrandir un volet à pleine largeur** (Éditeur seul, ou Aperçu seul, en masquant l'autre) ou de revenir au **Split** côte à côte. Choix recommandé par la recherche juin 2026 (Apple HIG/UX Planet/W3C, option 95/100 : très découvrable, état visible, accessible clavier, excellent mobile). État **persisté** (localStorage `lv_anon_view`). Validé **E2E Playwright 5/5** (Éditeur 1000px/Aperçu masqué et inverse, retour split, persistance au rechargement, 0 erreur console).

## [1.65.38] - 2026-06-05

### Added

- **Anonymiseur — anonymisation par occurrence (« rendre cette occurrence différente »)**. Réponse à la demande : par défaut un même contenu reçoit toujours le même faux (cohérence) ; en cliquant sur une occurrence déjà anonymisée, un popover offre **« ✎ Différent ici »** pour donner à **cette occurrence précise** une valeur de remplacement distincte (les autres restent identiques), ou **« ↩︎ Annuler »**. Construit sur le moteur durci (passe par intervalles + overrides) : `renderAnnotated` numérote les occurrences (`data-occ`), overrides persistés (`lv_anon_overrides_v3`, versionnés). Validé **E2E Playwright 9/9** : cohérence par défaut (3× même faux), override sur la 2ᵉ occurrence seulement, et **restauration exacte des 3 occurrences** (réversibilité préservée). Option A retenue (refactor strangler-fig + golden/round-trip 100 %), sans régression.

## [1.65.37] - 2026-06-05

### Fixed

- **Anonymiseur — durcissement du moteur : réversibilité garantie (~73 % → 100 %)**. En auditant une demande d'évolution (anonymisation par occurrence), découverte d'un défaut latent : ~1 aller-retour sur 4 échouait à cause de **collisions de valeurs factices** (remplacements en cascade + deux personnes recevant le même faux). Refonte best-practice (recherche juin 2026 : single-pass interval tokenizer) : `anonymize` **et** `restore` réécrits en **passe unique par intervalles** (plus de re-remplacement en cascade) ; `buildRules` génère des faux **globalement uniques** (aucun faux n'égale un original ni un autre faux) avec garantie finale d'unicité. Résultat : **aller-retour 100 % sur 30 000 cas** (y compris adversariaux : 6 personnes même nom, répétitions). Préliminaire au support de l'anonymisation par occurrence (overrides). Détection et UI inchangées.

## [1.65.36] - 2026-06-05

### Fixed

- **Anonymiseur — garde-fou anti-fuite : le faux n'égale jamais l'original** : par collision aléatoire, une valeur factice pouvait égaler la vraie (ex. faux prénom « Jean » = vrai prénom « Jean »), laissant fuiter une donnée. Ajout de `safeFake()` (régénère jusqu'à 8× si le faux normalisé == l'original) ; `buildRules` compose les noms à partir de **parties prénom/nom garanties différentes** des vraies (et cohérentes entre occurrences). **Confirmation** : les répétitions d'un même contenu reçoivent **toujours le même** faux (cohérence pour l'IA) et la restauration reste parfaite. Testé Node : 18 000 règles, **0 collision**.

## [1.65.35] - 2026-06-05

### Fixed

- **Anonymiseur — règles « fantômes » persistantes** : des règles créées par d'anciennes versions (avant les correctifs de détection) restaient dans `localStorage` et re-surlignaient à tort des termes (« Vieux-Québec », « Téléphone »…) même si la détection actuelle ne les crée plus. Fix : les règles sont **estampillées avec la version de l'outil** (`window.LV_ANON_VERSION`) ; au chargement, si la version a changé, on **repart d'un état propre** (purge automatique). Plus de règles périmées après un déploiement. (La détection actuelle sur le texte médical de référence est propre : 15 entités, toutes correctes.)

## [1.65.34] - 2026-06-05

### Fixed

- **Anonymiseur — faux nom détecté à cheval sur un saut de ligne** : la regex de noms utilisait `\s+` (qui traverse les retours à la ligne) → deux mots capitalisés en fin/début de lignes voisines (ex. « CLSC de **Rosemont** » + « **Référence** en cardiologie ») étaient fusionnés en un faux nom, avec l'espace surligné. Fix : entre les deux mots (regex `name` et `titled`), n'autoriser que l'espace **sur la même ligne** (`[^\S\r\n]+`). Vérifié Node : plus de fusion cross-ligne **et zéro régression** sur les vrais noms (Jean Dubé, Jean-François Tremblay, Dr Lavoie, Louise Gagnon, Marie Roy, espaces insécables).

## [1.65.33] - 2026-06-05

### Fixed

- **Anonymiseur — Cmd/Ctrl+A sélectionnait toute la page** : la vue annotée est un `div` (non éditable nativement) → le raccourci sélectionnait tout le document. Désormais intercepté pour **confiner la sélection au seul contenu du champ annoté** (`Range.selectNodeContents`). Validé E2E Playwright (sélection limitée à `#anonAnnotated`, rien hors champ).

## [1.65.32] - 2026-06-05

### Fixed

- **Anonymiseur — faux respectant le format (en construction/admin)** : (1) un **code postal** « H2K 1E5 » devenait une rue → produit désormais un **faux code postal** valide (« H8H 8N9 »), tandis qu'une adresse de rue reste une rue. (2) les **dates** gardent le **format de l'entrée** : « 12 mars 1982 »→« 24 mai 1958 » (J mois AAAA), « 2023-05-15 »→« AAAA-MM-JJ », « 15/05/2023 »→« JJ/MM/AAAA ».
- **Anonymiseur — passage à l'étape 2 remonte en haut de l'outil** : « J'ai la réponse de l'IA → » faisait rester dans le footer → `scrollIntoView` de la nav d'étapes au changement d'étape.

### Added

- **Anonymiseur — valeur de remplacement personnalisée** : la bulle de sélection offre, à côté de « 🕵️ Anonymiser » (auto), un bouton **✎** qui ouvre un champ pour **saisir sa propre valeur** de remplacement (préremplie d'une suggestion) → règle sur mesure. Validé **E2E Playwright 4/4** (code postal, dates FR/ISO format-préservé, valeur perso « 120/80 », scroll remonté).

## [1.65.31] - 2026-06-05

### Fixed

- **Anonymiseur — pseudonyme incohérent en anonymisation MANUELLE (bug critique, en construction/admin)** : sélectionner « Jean-François Tremblay » ou « 12 mars 1982 » donnait un nom **d'entreprise** (« Groupe Solva »…). Cause : `guessCategory()` échouait sur les noms à trait d'union et les dates → catégorie `other` → faux d'entreprise ; et la catégorie `id` (RAMQ/permis) tombait aussi sur « entreprise ». Fix : `guessCategory` **réutilise le moteur de détection** sur le passage sélectionné (nom→name, date→date, RAMQ→id, courriel→email, tél→phone, adresse→address) ; `generateFake('id')` masque chiffres **et** lettres en gardant le format (RAMQ « TREM 8203 12 01 »→« ODWL 6764 33 54 », permis « 123456 »→« 864904 »). Vérifié : nom→faux nom, date→fausse date, RAMQ→numéro masqué — plus aucune entreprise parasite.

### Added

- **Anonymiseur — bulle contextuelle « 🕵️ Anonymiser » à la sélection** (anonymisation manuelle enfin intuitive). Recherche juin 2026 (W3C/Notion) : **hybride** (option 96/100) = bouton fixe conservé **+** bulle flottante qui apparaît juste au-dessus du passage sélectionné à la souris (pattern Medium/Notion), même action, avec l'extrait sélectionné dans le libellé. Consigne d'amorçage clarifiée. Validé **E2E Playwright** (vraie sélection souris → bulle positionnée → clic → bonne catégorie, 10/10).

## [1.65.30] - 2026-06-05

### Added

- **Anonymiseur — champs auto-extensibles + plein écran (en construction/admin)** : sur un long texte, les champs (texte source, aperçu anonymisé, réponse IA, résultat) **s'allongent automatiquement** avec le contenu (auto-resize sur saisie + après détection/anonymisation/restauration), **sans scrollbar interne** — la page défile, la barre d'actions reste collante/accessible. Recalcul au redimensionnement de la fenêtre. Le bouton **plein écran** existant (API Fullscreen native) est conservé pour donner toute la largeur/hauteur. Validé **E2E Playwright** : #anonSource 216px→2936px sur 40 lignes, output étendu, zéro scroll interne, recalcul responsive OK.

## [1.65.29] - 2026-06-05

### Fixed

- **Anonymiseur — 3 bugs corrigés + simplification UI (audit UX/UI complet, en construction/admin)**. Audit fonctionnel Playwright (texte médical réel) + recherche pp_search (heuristiques Nielsen, WCAG 2.2, tendances juin 2026, options notées /100).
  - **BUG détection (moteur)** : la regex captait « Bonjour Dr » (salutation+titre) et ratait « Dr Lavoie ». Réécriture de `detectEntities` : gestion des **titres de civilité** (Dr/M./Mme/Me/Pr → capture le nom : « Dr Lavoie »→« Lavoie », « Dr Louise Gagnon »→« Louise Gagnon »), **stopwords de salutation** (Bonjour/Merci/Est/Ouest…), **prénoms composés** (« Jean-François Tremblay »), + nouvelles entités **RAMQ**, **code postal**, **n° de permis/matricule**. Zéro faux positif sur le texte médical.
  - **BUG sélection (UI)** : « Anonymiser la sélection » ne marchait pas car le clic du bouton **effaçait la sélection** avant lecture. Fix : **capture continue** de la sélection (mouseup/keyup/select) → on peut enchaîner plusieurs sélections manuelles.
  - **BUG réinitialisation** : « Réinitialiser » laissait des règles fantômes. Fix : purge `localStorage` + retour en mode édition → **état vierge garanti** et réutilisable immédiatement.
- **Anonymiseur — surcharge de boutons remplacée par un menu « ⋯ Actions »** (tendance 2026, option 96/100) : toolbar réduite à **Détecter** + **Anonymiser la sélection** + menu accessible (WAI-ARIA `role=menu`, Échap, clic-extérieur) regroupant Tout anonymiser · Modifier le texte · Mode · Réinitialiser. Légende clarifiée (souligné=à anonymiser / surligné=anonymisé, cliquer pour basculer). Validé **E2E Playwright** (3 bugs corrigés + menu + toggle, 0 erreur JS).

## [1.65.28] - 2026-06-05

### Removed

- **Anonymiseur — élimination de la dette technique de l'ancienne version** : suppression des 13 assets devenus **morts** après la refonte (plus référencés par la vue) : `app.js`, `enhancements*.js` (×7), `sw.js`, `manifest.webmanifest` (local à l'outil), `styles.css`, `detect-panel.css`, `compromise.min.js` (351 Ko). Le dossier ne garde que les 3 fichiers actifs (`anonymizer-core.js`, `anonymizer-ui.js`, `anon-v2.css`). Assets partagés **non touchés** (`tiptap-frontend.js`, `/manifest.webmanifest` global). Rollback git garanti.

### Fixed

- **Anonymiseur — désinscription de l'ancien Service Worker** : snippet ajouté à la vue qui désinscrit toute registration de SW scope `/outils/anonymiseur` (l'ancien `sw.js` network-first, retiré) et purge ses caches → garantit que les utilisateurs (admin) voient la version actuelle, pas une version périmée servie par le SW.
- **Test `AnonymiseurToolTest` aligné sur la refonte** : les assertions vérifiaient les anciens marqueurs/assets (`#sourceText`, `app.js`, `styles.css`, `enhancements.js`) cassés par la refonte → mises à jour vers les nouveaux (`#anonSource`, `#anonAnnotated`, `#anonOutput`, `#btnRestore`, `anonymizer-core.js`, `anonymizer-ui.js`, `anon-v2.css`). CI (MySQL migré) repasse au vert.

## [1.65.27] - 2026-06-05

### Added

- **Anonymiseur — mode optionnel « jetons stables » (défaut OFF, en construction/admin)** : nouveau bouton de bascule dans la toolbar (🎭 Réaliste ↔ 🏷️ Jetons). En mode jetons, les données deviennent des balises stables `[PERSONNE_1]`, `[DOSSIER_1]`, `[ADRESSE_1]`, etc. (même donnée → même jeton, numérotation continue, aucune sous-règle) — **restauration la plus fiable** même quand l'IA reformule beaucoup (recommandation recherche juin 2026). Consigne affichée : « demandez à l'IA de garder les jetons intacts ». Le **mode réaliste reste le défaut** (comportement inchangé) ; basculer régénère les règles existantes dans le nouveau mode. Persisté (localStorage `lv_anon_mode`). Moteur : `buildRules(selections, {mode, existing})` + `tokenLabel()`. Validé Node (2 modes + numérotation stable + non-régression pseudo) + **E2E Playwright 10/10** (activation, jetons, restauration 3/3, aller-retour réaliste↔jetons↔réaliste).

## [1.65.26] - 2026-06-05

### Changed

- **Anonymiseur — refonte UX en éditeur annoté inline (en construction/admin)** : l'empilement vertical (textarea + boutons + détections) était difficile à travailler. Nouveau paradigme validé par la recherche juin 2026 (Microsoft Presidio inline highlights + WAI-ARIA toolbar, options notées /100, choix 97/100) : **le texte source est la surface de travail**. Les données repérées sont **soulignées** (« sera anonymisé »), un **clic** les **surligne** (« anonymisé ») et inversement ; barre d'outils **collante** (Détecter · Anonymiser la sélection · Tout anonymiser · Modifier le texte · Réinitialiser), **aperçu anonymisé en direct côte-à-côte** (empilé sur mobile). La **sélection d'un passage** + bouton anonymise directement (remplace définitivement l'ancienne popup Tiptap). Navigation simplifiée à **2 étapes** (Anonymiser → Restaurer). Accessibilité : entités focusables (role=button, Entrée/Espace), toolbar ARIA. Zéro Tiptap, zéro popup native. Moteur `anonymizer-core.js` inchangé. Validé **E2E Playwright 15/15** (détection, clic souligné↔surligné, aperçu live, tout anonymiser, sélection, aller-retour, basculement inverse).

## [1.65.25] - 2026-06-05

### Added

- **Anonymiseur — « Anonymiser la sélection » (sélection native, en construction/admin)** : retour du geste « sélectionner un passage du texte puis l'anonymiser » qui causait beaucoup de bugs dans l'ancien outil (popup Tiptap en conflit avec la détection auto). Réimplémenté proprement sur le **textarea natif** (`selectionStart/End`) : sélectionner du texte → bouton « ✍️ Anonymiser la sélection » préremplit la règle manuelle (texte + choix du type) → coexiste sans conflit avec la détection automatique (règles dédoublonnées, tri longueur décroissante anti-chevauchement). **Zéro Tiptap, zéro popup native.** Moteur : la catégorie « Autre »/organisation génère désormais un **faux réaliste** (entreprise fictive) au lieu de `***`, donc réversible. Validé : moteur Node + **E2E Playwright combiné (auto + sélection + restauration) 8/8**.

## [1.65.24] - 2026-06-05

### Changed

- **Anonymiseur — refonte complète du moteur (réversibilité fiable, en construction/admin)** : l'aller-retour échouait car la restauration cherchait les valeurs factices par **correspondance exacte** dans la réponse IA reformulée. Reconstruction « simple d'abord » inspirée de l'ancien outil éprouvé : nouveau moteur pur `anonymizer-core.js` (détection regex FR/QC : nom, n° de dossier, adresse, courriel, téléphone, montant, date ; pseudonymes réalistes québécois ; **sous-règles nom complet + prénom seul + nom seul**) + restauration **durcie** (regex bornée **insensible à la casse ET aux accents**, espaces flexibles, tri longueur décroissante) → survit à la reformulation IA et aux variantes (« Dubé » seul, minuscules). Nouveau contrôleur `anonymizer-ui.js` (vanilla, toasts du thème, zéro popup native) + vue Blade **simplifiée** (3 étapes, textareas) qui **retire la couche fragile** (Tiptap, PWA/Service Worker, 7 scripts d'enhancement). Validé : moteur testé en Node + **E2E Playwright navigateur 100 %** sur l'exemple de référence (dossier #86734 / Jean Dubé / 15 rue de la gare → anonymisé → réponse IA reformulée → désanonymisé exact). Reste `is_under_construction=true` (visible admin seulement).

## [1.65.23] - 2026-06-05

### Added

- **Nouveau terme au glossaire IA : « CTAP (Client to Authenticator Protocol) »** (catégorie Sécurité et éthique, type technique) — protocole de la **FIDO Alliance** définissant le dialogue **plateforme↔authentificateur** (navigateur/OS ↔ clé de sécurité, téléphone) sur USB/NFC/BLE. C'est la **2e brique de FIDO2**, complémentaire de **WebAuthn** (qui gère le côté navigateur↔site web). Fait vérifié : **CTAP1 = ancien FIDO U2F (2FA) ; CTAP2 = version FIDO2 sans mot de passe (CBOR, clés résidentes)**. Relié au **knowledge graph bidirectionnel** (CTAP `broader`=fido2 ↔ FIDO2 `narrower`=ctap) et renvoie à WebAuthn et aux YubiKey/clés de sécurité. Image Gemini 1200×669 (`ctap.jpg` og:image + `ctap.webp`), sources vérifiées (FIDO Alliance, Wikipedia). **Cluster FIDO2 désormais complet : ses 4 enfants (passkey, WebAuthn, YubiKey, CTAP) sont maillés.**

## [1.65.22] - 2026-06-05

### Added

- **Nouveau terme au glossaire IA : « YubiKey »** (catégorie Sécurité et éthique, type outil) — **clé de sécurité matérielle** de Yubico, authentificateur physique **multi-protocole** (FIDO2/WebAuthn, FIDO U2F, OTP, PIV, OpenPGP) pour l'authentification forte (2FA/MFA) et la connexion sans mot de passe ; formats USB-A/USB-C/NFC/Lightning, activation par **contact tactile** (présence humaine, anti-hameçonnage). Fait vérifié : **Yubico fondée en 2007, première YubiKey en 2008**. Reliée au **knowledge graph bidirectionnel** (YubiKey `broader`=fido2 ↔ FIDO2 `narrower`=yubikey) et renvoie à WebAuthn et aux passkeys (qu'une YubiKey peut stocker). Image Gemini 1200×669 (`yubikey.jpg` og:image + `yubikey.webp`), sources vérifiées (Yubico, Wikipédia).

## [1.65.21] - 2026-06-04

### Added

- **Nouveau terme au glossaire IA : « WebAuthn (Web Authentication API) »** (catégorie Sécurité et éthique) — **API standardisée par le W3C** (avec la FIDO Alliance) permettant aux navigateurs d’authentifier **sans mot de passe** par cryptographie à clé publique, exposée via `navigator.credentials`. C’est la **brique web** de FIDO2 (côté navigateur/serveur), complémentaire de CTAP (côté authentificateur). Fait vérifié inclus : **recommandation officielle du W3C depuis mars 2019**. Relié au **knowledge graph bidirectionnel** (WebAuthn `broader`=fido2 ↔ FIDO2 `narrower`=webauthn) et renvoie aux passkeys. Pour éviter le conflit, « WebAuthn » a été **retiré des aliases de FIDO2** (il a désormais sa propre fiche). Image Gemini 1200×669 (`webauthn.jpg` og:image + `webauthn.webp`), sources vérifiées (W3C, MDN).

## [1.65.20] - 2026-06-04

### Added

- **Nouveau terme au glossaire IA : « passkey (clé d'accès) »** (catégorie Sécurité et éthique) — identifiant d'authentification **sans mot de passe** basé sur FIDO2, déverrouillé par biométrie/NIP, synchronisable entre appareils (iCloud, Google). Relié à FIDO2 via le **knowledge graph bidirectionnel** (passkey `broader`=fido2 ↔ FIDO2 `narrower`=passkey). Pour éviter le conflit, « passkey » et « clé d'accès » ont été **retirés des aliases de FIDO2** (ils appartiennent désormais au terme passkey). Contenu cross-référençant FIDO2 et le mot de passe. Image Gemini 1200×669 (`passkey.jpg` og:image + `passkey.webp`), sources vérifiées (FIDO Alliance, Wikipédia).

## [1.65.19] - 2026-06-03

### Added

- **Nouveau terme au glossaire IA : « FIDO2 »** (catégorie Sécurité et éthique) — standard d'authentification **sans mot de passe** (WebAuthn + CTAP, cryptographie à clé publique, **résistant au hameçonnage** car les clés sont liées au domaine du site). Synonymes/notions proches en **aliases** (WebAuthn, passkey, clé d'accès, clé de sécurité FIDO2). Contenu cross-référençant mot de passe / OTP / MFA sans les redéfinir. Définition, analogie, exemple, « le saviez-vous », FAQ (Schema.org), sources vérifiées (IBM, Wikipedia), JSON-LD. Image Gemini 1200×669 (`fido2.jpg` og:image + `fido2.webp`).

## [1.65.18] - 2026-06-03

### Added

- **Nouveau terme au glossaire IA : « MFA (authentification multifacteur) »** — traité comme **entité distincte** du 2FA (anti-duplication, approche entity-based 2026) : les vrais synonymes (« authentification multifacteur », « multi-factor authentication ») sont des **aliases** (pas de pages dupliquées), et MFA est relié au 2FA via le **knowledge graph Schema.org bidirectionnel** (MFA `narrower` = 2fa, 2FA `broader` = mfa) avec un lien visible vers /glossaire/2fa. Le contenu renvoie au 2FA (cas particulier à 2 facteurs) sans le redéfinir. Image Gemini 1200×669 (`mfa.jpg` og:image + `mfa.webp`), 3 catégories de facteurs (savoir/posséder/être), sources vérifiées (Wikipédia, Pensez cybersécurité Canada).

## [1.65.17] - 2026-06-03

### Added

- **Nouveau terme au glossaire IA : « SSO (authentification unique) »** (catégorie Sécurité et éthique) — mise en page identique aux autres termes : définition, analogie, exemple concret, « le saviez-vous », FAQ (Schema.org), sources vérifiées (Wikipédia, Okta), réponse AEO en une phrase, JSON-LD. **Image** générée via Gemini (`gemini-2.5-flash-image`), recadrée au standard **1200×669**, déclinée en **`sso.jpg`** (og:image — compatible réseaux sociaux) + **`sso.webp`** (affichage), compressées (~40 Ko / ~16 Ko).

## [1.65.16] - 2026-06-03

### Added

- **Badge « 🚧 Bientôt » sur les outils en construction (liste `/outils`)** : la carte d'un outil dont `is_under_construction = true` affiche désormais un badge « Bientôt » (accent marque, blanc AAA), au lieu de rester sans indication (le champ `under_construction` du composant carte était figé à `false`). L'outil **reste listé** ; sa page affiche « En construction » pour le public tandis que le super-admin garde l'accès complet (amélioration/corrections). Premier cas : l'anonymiseur.

## [1.65.15] - 2026-06-03

### Added

- **Lien LinkedIn dans les liens sociaux** : ajout du profil LinkedIn (Stéphane Lapointe) à côté de Facebook et Messenger, dans la barre du haut (header) et le footer « Communauté ». URL servie par `lv_social('linkedin')` (setting `social.linkedin_url` mis à `https://www.linkedin.com/in/lapointestephane/` + défaut du helper corrigé).

## [1.65.14] - 2026-06-03

### Changed

- **Boutique en maintenance — retrait des liens résiduels** : pendant `SHOP_MAINTENANCE=true`, les liens « Boutique » du menu et du footer s'affichaient encore pour les super-admins (bypass de test). Le bypass est retiré côté menu → liens cachés pour tous. De plus, l'entrée « Mes commandes » (lien `/boutique/...` qui menait à un 503) est filtrée du menu utilisateur pendant la maintenance. Cohérent avec l'icône panier déjà masquée (1.65.13). Entièrement réversible : tout réapparaît quand `SHOP_MAINTENANCE=false`. Le super-admin garde l'accès direct via `/admin/shop` et l'URL `/boutique` (le middleware le laisse passer).

## [1.65.13] - 2026-06-03

### Fixed

- **Icône panier visible alors que la boutique est désactivée** : le mini-cart du header était inclus sans tenir compte du kill switch `SHOP_MAINTENANCE`. Inclusion désormais gatée par `@unless(config('shop.maintenance'))` → l'icône panier disparaît du menu tant que la boutique est en maintenance (réversible : réapparaît quand `SHOP_MAINTENANCE=false`). Cohérent avec les liens « Boutique » déjà masqués.

## [1.65.12] - 2026-06-03

### Fixed

- **Page publique « Collections de la communauté » (`/collections`) — cartes trop larges / débordement** : même cause que `/user/collections`, la grille Bootstrap `col-md-4` débordait le `.container` (4ᵉ carte coupée au bord). Remplacée par une **grille CSS responsive** (`repeat(auto-fill, minmax(280px, 1fr))`) contenue dans le conteneur → plus de débordement, cartes bien alignées.

## [1.65.11] - 2026-06-03

### Fixed

- **Page « Mes collections » (`/user/collections`) — mise en page incohérente / cartes trop larges** : la vue utilisait le layout générique `fronttheme::layouts.master` (pleine largeur, sans la sidebar « Mon espace ») avec une grille Bootstrap `col-md-4` qui débordait, contrairement aux autres pages de l'espace utilisateur. Migrée vers `auth::layouts.user-frontend` (sidebar + colonne centrée) avec une **grille CSS responsive** (`repeat(auto-fill, minmax(230px, 1fr))`) → plus de débordement, rendu aligné sur les autres pages (favoris, contributions, sauvegardes).

## [1.65.10] - 2026-06-03

### Changed

- **Menu — compteur dynamique d'acronymes** : dans la variante de méga-menu « Référence », l'entrée « Acronymes éducation » affichait le texte fixe « Sigles du Québec » au lieu d'un compteur, contrairement aux autres références (Glossaire, Répertoire). Ajout de `$acronymsCount` (cache 3600s, même pattern que `$dictionaryCount`/`$directoryCount`) → affiche désormais « N acronymes du Québec ».

## [1.65.9] - 2026-06-03

### Fixed

- **Erreur 500 sur `/mes-favoris`** : le modèle `Bookmark` (`$timestamps = false`, sans `$casts`) renvoyait `created_at` comme **chaîne**, donc `$bookmark->created_at?->format('d/m/Y')` dans la vue déclenchait *« Call to a member function format() on string »* (le `?->` ne protège que `null`, pas une string). Ajout de `protected $casts = ['created_at' => 'datetime']` → `created_at` redevient un `Carbon` en lecture. Vérifié par rendu complet de la vue (date affichée, aucune exception).

## [1.65.8] - 2026-06-03

### Changed

- **Taille des « ? » d'aide inline (outils)** : les boutons d'aide circulaires inline (à côté des libellés de champs, `.ct-btn-xs`) passent de 44px à **24×24** (cercle, conforme WCAG 2.2 AA — exception « cible inline »), pour un rendu plus léger. Les boutons icône de barre d'outils (`.ct-btn-icon`) restent à **44px AAA**. Suite du correctif ovales→cercles (1.65.7).

## [1.65.7] - 2026-06-03

### Fixed

- **Boutons icône ovales → cercles (tous les outils)** : les boutons icône circulaires (`border-radius:50%`) des outils — notamment les « ? » d'aide — apparaissaient **ovales** car le composant `x-core::button` impose `.ct-btn { min-height: 44px }` (cible tactile WCAG 2.2 AAA), ce qui étirait la hauteur de boutons à largeur fixe (32/22px). Correctif dans `charte.css` : `.ct-btn-icon` et tout `.ct-btn[style*="border-radius:50%"]` forcés à `width = height = 44px` → **cercle parfait, conforme AAA**. Couvre les 6 outils concernés (constructeur-prompts, code-qr, liens-google, roue-tirage, simulateur-fiscal, anonymiseur). Vérifié visuellement (44×44, ratio 1:1).

## [1.65.6] - 2026-06-02

### Fixed

- **Contraste WCAG 2.2 AAA — newsletter digest-weekly** : les boutons CTA cyan (`#3dc9d8`) situés dans les blocs à fond foncé (ex. « Construire mon prompt → », « Raccourcir un lien → ») héritaient de la règle générique « liens sur fond foncé » qui force le texte en cyan clair `#5eead4` → bouton cyan-sur-cyan illisible. Ajout d'une règle CSS plus spécifique (sélecteur sur l'attribut `background-color`) qui restaure le texte foncé `#0c1427` sur ces boutons (**9.21:1 = AAA**), sans toucher les liens texte (qui restent `#5eead4`).

## [1.65.5] - 2026-06-02

### Added

- **Générateur de prompt newsletter — menus déroulants cherchables + facettes** : les 6 sections « contenu du site » (Actualité vedette, Top actualités, Outil de la semaine, Terme IA, Article de blogue, Outil interactif) passent du texte libre à un **combobox cherchable** (recherche AJAX en base, ARIA combobox/listbox, navigation clavier) avec **chips** de sélection (simple ou multiple jusqu'à 5). Les sections Actualités ajoutent des **facettes** : dates (Du/Au) + filtres rapides par **compagnie** (OpenAI, Anthropic, Google, Meta, Mistral, Microsoft, Apple, xAI, DeepSeek — liste en config). Le prompt généré émet directement les **IDs sélectionnés** (`content['tool_id'] = 93`, `content['top_news_ids'] = [2]`) — aucune recherche manuelle requise côté Claude Code.
- Nouveau service `PromptBuilderSearchService` (recherche DB sécurisée : `class_exists()` pour modules désactivables, requêtes paramétrées, contenus publiés uniquement) + endpoint `GET admin/newsletter/prompt-builder/search` (gardé par `permission:view_newsletter` + `throttle:60,1`). Vérifié E2E en local (combobox → suggestions → chip → prompt).

## [1.65.4] - 2026-06-02

### Fixed

- **Anonymiseur — application des règles** : après avoir enregistré une règle, le résultat anonymisé apparaît (bascule auto à l'étape 2) et le mot est surligné dans l'éditeur (décorations Tiptap), au lieu de rien. Le bouton « Effacer » vide maintenant vraiment l'éditeur (visait un élément invisible). Vérifié E2E (vrai drag souris).

## [1.65.3] - 2026-06-02

### Fixed

- **Déploiement des assets compilés (CRITIQUE)** : le rsync de `deploy.yml` excluait `public/build/` → aucun asset Vite recompilé n'arrivait en prod (build figé). Le fix anonymiseur (1.65.2) ne s'appliquait donc pas. Exclude retiré (dossier 100% versionné) ; les assets buildés se déploient désormais.

## [1.65.2] - 2026-06-02

### Fixed

- **Anonymiseur — sélection souris pour anonymiser** : le listener était attaché à un élément `#sourceText` devenu invisible (ghost hors-écran) depuis l'éditeur Tiptap ; désormais câblé sur l'éditeur visible (`.ProseMirror`). Sélectionner du texte ouvre à nouveau la modale de règle. Vérifié E2E.

## [1.65.1] - 2026-06-02

### Changed

- **Prompt newsletter plus précis** : pour chaque section personnalisée, le prompt généré indique maintenant la **forme exacte** attendue dans `NewsletterIssue.content` (éditorial = HTML, défi = structure `wellness_challenge`/`weekly_prompt`, sections par ID = lookup DB). Claude Code CLI remplit chaque section sans deviner.

## [1.65.0] - 2026-06-02

### Changed

- **Générateur de prompt newsletter repensé en « override de sections »** : au lieu d'un prompt libre, il liste les 8 sections du gabarit `digest-weekly` (Éditorial, Défi, Actu vedette, Top actus, Outil, Terme IA, Article blog, Outil interactif), chacune en **Auto** ou **Personnaliser**. Le contenant reste identique ; on ne remplace que les sections choisies, le reste garde le contenu automatique. Le prompt généré cible le `NewsletterIssue` de la semaine (clés réelles de `content`) + l'envoi test. Email test externalisé (`NEWSLETTER_TEST_EMAIL`).

## [1.64.4] - 2026-06-02

### Changed

- **Menu admin Newsletter regroupé** : sous-en-tête de section « NEWSLETTER » + entrées indentées (Vue d'ensemble, Campagnes, Workflows, Templates, Abonnés, Générateur de prompt) pour qu'on voie clairement qu'elles forment un groupe.

### Fixed

- **Suppression de preset (prompt-builder)** : ajoute une modale de confirmation (`confirm-action` du layout admin) — la suppression ne s'exécute plus sans confirmation.

## [1.64.3] - 2026-06-02

### Fixed

- **Scroll infini sur toutes les pages admin** : `infinite-scroll.js` (script du front public) était chargé dans le layout admin et détournait la pagination des listes (annuaire…) → page qui grossit sans fin + icônes d'action vides sur les lignes chargées. Script retiré du layout admin.
- **Bouton « Générer le prompt » (prompt-builder)** : n'apparaissait qu'à l'étape 5 → ajout d'un bouton « Générer » persistant dans l'aperçu, accessible depuis toutes les étapes.

## [1.64.2] - 2026-06-02

### Changed

- **Retrait du dark mode du back-office** (non utilisé ; signalé comme faisant planter Chrome) : mode clair forcé (`data-bs-theme="light"` en dur + nettoyage `localStorage.theme`), JS de bascule `color-modes.js` débranché, toggle supprimé, CSS dark mort retiré. Vérifié sans crash sur toutes les pages admin.

## [1.64.1] - 2026-06-02

### Fixed

- **Dark mode back-office WCAG 2.2 AA** : le branding inline (`--bs-body-bg`/`--bs-app-bg`) en `:root` écrasait le thème sombre → fond blanc et texte illisible (corps 1.46:1, tableaux 1:1). Surcharges branding scopées `:root:not([data-bs-theme="dark"])` + overrides tokens dark conformes AA (corps 12.57:1, bouton primaire 5.28:1, badges 10.14:1). Mode clair inchangé, pas de rebuild d'assets.

## [1.64.0] - 2026-06-02

### Added

- **Générateur de prompt newsletter (back-office)** : page admin `/admin/newsletter/prompt-builder` — assistant multi-étapes (stepper éditable : onglets cliquables + Suivant/Précédent, ARIA tablist, navigation clavier) pour composer un prompt prêt à coller dans Claude Code CLI. 5 étapes (éditorial, défi de la semaine, actualités, sections custom, options + courriel test), aperçu live, copie en 1 clic (toast), presets réutilisables (note pour la prochaine newsletter). Toute section laissée vide → le prompt instruit l'IA d'appliquer le comportement automatique par défaut. Permissions granulaires, throttle, validation liste blanche, structure newsletter best-practice intégrée.

## [1.63.28] - 2026-06-02

### Fixed

- **Courriels « No hint path for [mail] »** : `WelcomeMail` rend désormais `emails.welcome` via `markdown:` (la vue utilise des composants `mail::`) au lieu de `view:`, ce qui initialise le renderer Markdown. Bouton du courriel pointé vers `/dashboard` au lieu de `/admin`.
- **Redirection post-connexion d'un non-admin vers `/admin` (403)** : nouvelle méthode role-aware `User::homeRoute()` (source unique DRY) remplace 3 redirections codées en dur vers `admin.dashboard` dans `TwoFactorChallenge`, `SocialAuthController` et `MagicLinkController::verify`.

## [1.1.0] - 2026-03-02

### Added

**Multi-tenant avancé (module Tenancy)**
- Trait `BelongsToTenant` pour scope automatique des modèles par tenant
- 3 middlewares : identification tenant, scope global, isolation données
- Domaines custom par tenant avec vérification DNS
- Admin centralisé : gestion tenants, domaines, plans, statistiques
- Migration `add_tenant_id_to_tables` pour les tables existantes

**Marketing automation (module Newsletter)**
- Workflows email automatisés (drip campaigns, séquences)
- Modèles `EmailWorkflow`, `WorkflowStep`, `WorkflowEnrollment`, `WorkflowStepLog`
- Templates marketing avec éditeur visuel
- Enrollments automatiques basés sur événements (inscription, achat, etc.)
- Commande `newsletter:process-workflows` pour traitement planifié
- Admin : gestion workflows, templates, statistiques d'envoi

**API GraphQL v2 (Lighthouse)**
- Endpoint `/graphql` avec schema-first approach
- Queries : articles, categories, pages, FAQ, subscribers
- Mutations : CRUD articles, gestion newsletter, contact
- Authentification Sanctum via directive `@guard`
- Pagination relay cursor-based
- Sécurité : query depth limiting, introspection désactivée en production

**Module Team**
- Organisations multi-utilisateurs avec invitations
- Rôles par équipe (owner, admin, member)
- Gestion des membres et permissions

**Commandes**
- `app:audit` : audit complet du projet (sécurité, performances, qualité)
- `make:crud {module} {model}` : générateur CRUD avec options `--fields=`, `--with-api`, `--force`

**Polish CMS (P1-P8)**
- Content versioning : trait `HasRevisions`, `ContentRevision` model, diff et restauration (max 50 par contenu)
- Scheduled publishing : trait `HasScheduledPublishing`, champs `published_at`/`expired_at` sur Article, StaticPage, FAQ
- URL redirections : modèle `UrlRedirect` dans SEO, exact + wildcard, compteur de hits, admin CRUD
- Announcements/changelog : modèle `Announcement` dans Core, admin CRUD, page publique `/changelog`
- Breadcrumbs dynamiques : `@yield('breadcrumbs')` dans admin layout, 14 vues enrichies
- Media manager : métadonnées SEO (titre, alt_text, légende, description), dossiers, compression WebP (6 conversions), composant `<x-media::picture>`
- Preview avant publication : aperçu articles et pages sans publier, bannière admin, bouton dans les formulaires d'édition

### Changed
- Tests : 2463 → 2734+ tests (0 échec)
- Modules : 33 → 34 (ajout Team)
- Permissions : 39 → 43
- Feature flags enrichis dans `core:new-project` avec catégories de modules

## [1.0.0] - 2026-03-01

### Added

**Modules (34 total)**
- RBAC: 39 permissions, 4 roles (super_admin, admin, editor, user), Gate::before super_admin, per-route middleware
- Stripe billing: plans, checkout, trial, webhooks, cancellation flow (Laravel Cashier)
- Blog: articles, categories, tags, comments, media picker, TipTap rich editor
- CMS / Pages: static pages with template support, configurable homepage (landing or static page)
- Newsletter: subscriber management, campaigns, unsubscribe flow
- FAQ: CRUD admin, public page, JSON-LD Schema.org structured data
- Menu: drag-and-drop builder (SortableJS), cache, Blade component for frontend
- Widgets: configurable dashboard widgets per role
- Form builder: dynamic forms with field types, submissions storage
- Custom fields: attach arbitrary fields to any entity
- Import / Export: CSV/XLSX import-export with queue support
- A/B testing: variant management and conversion tracking
- AI module: OpenRouter integration (chat, article generation, moderation, SEO, translation)
- PWA: manifest, service worker, install prompt
- Push notifications: Web Push (VAPID), Reverb WebSocket channel
- Two-factor authentication: TOTP (Google Authenticator compatible)
- Social login: OAuth2 via Laravel Socialite (Google, GitHub)
- GDPR compliance: personal data export and anonymization commands
- Session management: active session list, remote session revocation
- Password policy: HIBP breach check, complexity rules, expiry
- Email notifications: trial ending, payment succeeded/failed, subscription cancelled
- Contact messages: storage, admin UI (read/unread, filters, detail view)
- Search: Laravel Scout integration (Meilisearch / database driver)
- Media: Spatie Media Library, admin media picker, upload API
- Editor: TipTap with image upload, link, code block extensions
- Backups: automated backups with Spatie Backup, admin restore UI
- Health: system health checks dashboard
- Logging: structured log viewer with level filter and tail mode
- Tenancy: multi-tenant scaffolding (single database)
- Storage: S3-compatible driver support, presigned URLs
- Translation: UI string management, locale switcher
- SEO: meta tags, Open Graph, JSON-LD service, sitemap
- SaaS: plan comparison page, usage metering, upgrade/downgrade flow
- Webhooks: outgoing webhook delivery with retry and log

**Security**
- Content Security Policy (CSP) headers
- HTTP Strict Transport Security (HSTS)
- XSS filtering via mews/purifier on all rich-text inputs
- Honeypot on public forms
- Rate limiting on login, registration, API endpoints
- IP blocking (admin-managed blocklist)
- Audit logging for sensitive admin actions

**Developer experience**
- PHPStan level 6, 0 errors
- 2655+ tests (Pest 3, parallel execution)
- Playwright E2E test suite
- Docker Compose setup for local development
- CI/CD pipeline (GitHub Actions): Pint, PHPStan, tests
- Makefile shortcuts: `make test`, `make check`, `make check-quick`
- Artisan commands: `app:install`, `app:demo`, `app:status`, `app:check`, `app:make-module`, `app:logs`, `app:setup-hooks`
- NobleUI Bootstrap 5.3.8 admin theme with Lucide icons
- Authero guest theme (Tailwind, Tabler icons)
- GoSass frontend theme

**Architecture**
- `BaseRouteServiceProvider` shared by all modules (DRY route registration)
- `SettingsReaderInterface` in Core module, implemented by Settings module (Core/Settings decoupled)
- Plugin manifest (`plugin.json`) per module for metadata and dependency declaration
- Theme resolution in module ServiceProviders (theme-aware view loading)

[Unreleased]: https://github.com/memora-solutions/laravel-saas/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/memora-solutions/laravel-saas/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/memora-solutions/laravel-saas/releases/tag/v1.0.0
