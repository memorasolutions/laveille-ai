# Contraintes du projet - à lire AVANT toute action

Ce fichier existe pour une raison précise : le propriétaire ne doit pas avoir à répéter les mêmes
consignes à chaque tâche. Tout ce qui suit est acquis, permanent, et non négociable.

**Tout brief de sous-agent doit renvoyer ici plutôt que de recopier ces règles.**

---

## 1. INTERDICTIONS ABSOLUES - aucune exception, jamais

- **Ne JAMAIS supprimer de données utilisateur.** Seule limite réellement infranchissable.
- **Ne JAMAIS modifier un mot de passe, un jeton ou un compte**, même en local, même temporairement,
  même en comptant le restaurer. Si tu ne peux pas te connecter : ARRÊTE-TOI et signale-le.
  (Un sous-agent l'a fait le 2026-08-15 ; la restauration n'est pas vérifiable après coup.)
- **Ne JAMAIS exécuter `config:cache`** - ferme des sections du site en production.
- **Ne JAMAIS exécuter `migrate:fresh`, `DROP`, un `DELETE` sans `WHERE`, `git reset --hard`, ni un
  `rm -rf` sans sauvegarde.** (Une base locale a déjà été vidée ainsi le 2026-07-04.)
- **Ne JAMAIS committer ni pousser** sauf mandat explicite du superviseur.
- **Ne JAMAIS utiliser une fenêtre native du navigateur** : `alert()`, `confirm()`, `prompt()` sont
  interdits. Utiliser la modale du thème ou un toast.
- **Ne JAMAIS synchroniser `public/screenshots/` du local vers la production** - écrase les captures.
- **Ne JAMAIS utiliser WebSearch ni WebFetch.** La recherche web passe uniquement par
  `mcp__perplexity-pro-playwright__pp_search`.

## 2. TESTS - la règle qui a coûté le plus cher

- **UNE seule suite de tests à la fois sur cette machine.** Deux suites concurrentes partagent la
  base et les caches : elles produisent de FAUX échecs et multiplient la durée par sept.
  Mesuré le 2026-08-16 : huit processus concurrents, chaque test passant de 0,7 à 4,7 secondes.
- **Si le brief te dit de ne pas lancer de tests, ne les lance pas.** Le superviseur les exécutera
  lui-même, une fois, après toutes les écritures.
- **Ne termine JAMAIS ton tour en attendant un résultat** : la notification arrive au superviseur,
  pas à toi. Tu t'endormirais. Boucle activement sur la vérification, ou signale que tu attends.

## 3. VÉRIFICATION - ce qui vaut preuve

- **`php -l` obligatoire** après édition de tout fichier PHP.
- **`config/version.php` fait environ 927 Ko.** Ne le lis JAMAIS en entier ; utilise un décalage
  ciblé. `php -l` après édition est obligatoire - un incident de production a déjà été causé par une
  édition de ce fichier.
- **La validation visuelle est obligatoire** avant de déclarer un travail terminé, en navigateur
  VISIBLE, jamais invisible. Une suite verte ne prouve pas qu'une page est utilisable.
- **Ne déclare jamais « terminé » sans preuve externe collée** : sortie de test, diff, capture,
  requête de vérification. Une auto-évaluation ne vaut rien.
- **Vérifie contre les fichiers et données RÉELS**, jamais contre ta mémoire du modèle. Signale
  explicitement toute hypothèse non confirmée.
- **Un chiffre qui servira à une décision doit être reproductible par une commande.** S'il ne l'est
  pas, il ne peut pas fonder une action. (Des comptages cités de mémoire se sont révélés
  introuvables le 2026-08-15.)

## 4. QUALITÉ DE CODE - non négociable

- **DRY strict.** Grep AVANT toute écriture qui pourrait dupliquer. Chercher le composant existant
  avant d'en créer un. Signaler dans le rapport ce qui a été RÉUTILISÉ plutôt que réécrit.
- **Tout en modules activables/désactivables** : `class_exists()`, `module_enabled()`. Un module
  retiré ne doit jamais casser le site.
- **Zéro code en dur** : noms d'entreprise, adresses, domaines vont dans `.env` ou la table des
  réglages.
- **Charte du projet** : `public/css/charte.css`, famille de classes `.ct-*`, jeton principal
  `#064E5A`. Pages de contenu : `.wpo-blog-single-section`. **Jamais de Tailwind générique.**
- **Accessibilité WCAG 2.2 niveau AAA** : contraste 7:1, zones tactiles de 44 pixels.
- **L'ordre HTML doit suivre l'ordre visuel.** Aucune propriété `order`, aucun placement de grille
  qui inverserait deux éléments - cela casse la lecture au clavier et au lecteur d'écran.
- **Code attribué à MEMORA solutions.** AUCUNE référence à Claude, Anthropic ou à une IA, ni dans le
  code, ni dans les commentaires, ni dans les commits. Jamais de `Co-Authored-By`.
- **Français impeccable** : tous les accents, jamais de tiret cadratin (utiliser - ou ;), pas de
  majuscules à l'américaine dans les titres.

## 5. PRODUCTION

- Chemin : `public_html/apps_diverses/laveille.ai` (compte gmemora). Domaine : **laveille.ai**
  (`laveilledestef.com` est déprécié).
- **Sauvegarde AVANT toute écriture en production.** Rollback toujours disponible.
- Le terminal cPanel et le gestionnaire de fichiers sont **hors service** sur ce compte. La commande
  `tinker` est muette via SSH. Contournement établi : script PHP autonome qui amorce Laravel, écrit
  son résultat dans un fichier, puis se supprime lui-même.
- **Tout cron temporaire créé doit être retiré immédiatement après usage**, et son absence vérifiée.
- Le pipeline de déploiement purge déjà Cloudflare et vide les caches - ne pas le refaire à la main.
- **Versionnement** : `config/version.php`. Une fonctionnalité nouvelle est un MINOR, une correction
  un PATCH. Le CHANGELOG suit le format des entrées existantes.

## 6. PIÈGES CONNUS DE CE PROJET

- **En production, le niveau de journalisation jette les messages d'information avant écriture.** Un
  `Log::info` sur le canal par défaut n'existe nulle part. Utiliser un canal dédié à niveau fixe -
  et **jamais** transformer un message d'information en erreur pour le rendre visible.
- **Un commentaire de code n'est pas une preuve.** Un événement a été annoncé comme journalisé par un
  commentaire alors qu'aucune ligne ne le faisait.
- **`ia-sync` doit précéder le redémarrage, jamais l'inverse.** Playwright lit les cookies une seule
  fois au démarrage du serveur MCP.
- **Un `<template x-if>` d'Alpine ne rend qu'un seul enfant racine** : un frère ajouté devient du
  code mort, sans erreur ni test rouge.
- **Vider `responsecache` avant de juger un correctif Blade en local**, sinon on teste l'ancienne
  page.
- **Les formats WebP et AVIF ne sont pas fiables pour les aperçus sur les réseaux sociaux.** Un JPEG
  1200x630 reste obligatoire pour l'image de partage. (107 images du glossaire ont dû être
  rattrapées.)
- **`container-type: inline-size` sur un élément de grille dont la piste est `auto` effondre sa
  largeur.** La mise en confinement fait que l'élément contribue une taille d'environ zéro au calcul
  de la piste : le parent s'effondre et la mise en page casse à GRANDE largeur, là où il y avait
  pourtant toute la place. Trouvé le 2026-08-16 en ajoutant une requête de conteneur aux boutons de
  vote - le palier au-delà de 760 pixels repassait en deux plus un. Parade : donner une largeur
  minimale explicite, scopée au seul palier concerné.
- **La forme courte `@php(...)` casse la compilation Blade** dans ce projet (erreur de syntaxe,
  page 500). Utiliser systématiquement la forme bloc `@php ... @endphp`.
- **Un fork hérite du contexte COMPLET de la conversation.** Chargé d'une question étroite (« quel
  est le chemin du compte ? »), un fork économique peut exécuter le MANDAT ENTIER qu'il voit dans le
  contexte hérité - y compris des opérations de production (constaté le 2026-08-17 : dépôt de script,
  cron, lecture de production, au lieu d'une simple recherche locale). Parade : pour une question
  étroite, préférer un agent NEUF avec un brief minimal plutôt qu'un fork ; et tout brief de
  sous-sous-agent répète explicitement « UNIQUEMENT cette question, rien du mandat parent ».
- **Une nouvelle clé de `news:apply --payload` est soit du CONTENU, soit une méta-donnée - jamais
  les deux.** Tout payload dont le panier de contenu est non vide efface `structured_summary` (le
  résumé composé de la fiche), volontairement, depuis le 2026-08-17. Une clé posée APRÈS coup sur
  une fiche déjà rédigée (outils liés, entités, verdict de vérification) doit donc voyager dans son
  PROPRE panier, appliqué à part - sinon elle détruit le résumé en silence. Le piège s'est produit
  deux fois, la seconde le 2026-08-21.
- **Le texte alternatif décrit l'image**, il ne contient pas de mots-clés - le bourrage dégrade
  l'accessibilité sans bénéfice.

**Le cache de RÉPONSE fausse un rouge/vert sur du rendu.** Mesuré le 2026-08-30 : vider le cache
des vues ne suffit pas, `responsecache` sert la page telle qu'elle était AVANT le correctif. Un
agent qui mesure « avant » puis « après » sans le purger obtient deux fois la même page et conclut
que son correctif est sans effet - ou pire, qu'il fonctionne alors que rien n'a bougé. Purger les
DEUX caches entre les deux mesures, et se méfier de tout rouge/vert identique au pixel près.

**Une cible tactile de 44 px ARRONDIE n'est pas une cible de 44 px.** Même date : un bouton portait
bien une zone de clic de 44 px, mais avec un arrondi complet - donc un cercle de 44 px de diamètre
(1521 px²) et non un carré de 44 sur 44 (1936 px²). Les coins retombaient sur l'élément voisin. Une
lecture de la feuille de style dit « 44 px » et paraît conforme ; seul un CLIC RÉEL au coin de la
zone révèle le défaut. Vérifier les cibles tactiles par le geste, jamais par la déclaration.

**LA CORRESPONDANCE EN SOUS-CHAÎNE, QUATRE FOIS LE MÊME DÉGÂT.** Chercher un mot sans borner ses
frontières le trouve à l'intérieur d'autres mots. Mesuré quatre fois sur ce projet, dans deux
mécanismes sans aucun rapport :
  - « accept » trouvé dans « Acceptable Use Policy » : la logique qui écarte les bandeaux de cookies
    a cliqué un lien de bas de page et capturé la mauvaise page ;
  - « ok » trouvé dans « Book a demo » (b-OO-k) : même mécanisme, même dégât ;
  - « clés d'accès » lié vers l'authentification sans mot de passe alors qu'il s'agissait de clés d'API ;
  - « dos » lié vers le déni de service dans « sac à dos » et « vue de dos ».

Le remède est le même partout : **borner par des frontières de mot**. Et le contrôle qui compte
n'est pas que le bug disparaisse, c'est que les cas LÉGITIMES passent encore - un resserrement du
même genre a cassé Node.js, Z.ai et jan.ai le 2026-08-27 en réglant trois faux liens.

Avant d'écrire une recherche de motif dans du texte, se demander : ce motif peut-il vivre à
l'intérieur d'un mot plus long ? Si oui, il faut le borner avant, pas après l'incident.

## 6 bis. TU N'ES JAMAIS RÉVEILLÉ PAR UN SIGNAL - tu lis un fichier

Mesuré TROIS fois dans la même soirée, le 2026-08-29, sur trois agents indépendants qui ont tous
gelé de la même façon : « j'attends la notification de la tâche de fond », « j'attends que le
Monitor me réveille », « je reprendrai quand le résultat arrivera ».

**Ce réveil n'arrive jamais.** Une notification de fin de tâche de fond remonte à la boucle
principale, pas à un agent délégué. Un Monitor armé depuis un sous-agent ne le sort pas non plus de
son attente. L'agent reste suspendu jusqu'à ce qu'un humain ou le superviseur le relance - du temps
perdu, à chaque fois.

**La règle : une tâche de fond écrit son résultat dans un FICHIER. On lit le fichier.**

    tail -40 <chemin/du/fichier.output>

Le chemin t'est donné au moment où tu lances la commande. Interroge-le, à intervalles si besoin.
Ne reste jamais suspendu à un signal.

### La commande d'attente

Mesuré à nouveau le 2026-08-30, cinquième agent figé le même soir malgré cette section : annoncer
« j'attends la notification » puis ne plus rien faire n'est PAS une attente, c'est un arrêt. Une
notification de tâche de fond ne remonte qu'à la boucle principale, jamais à un agent délégué -
même quand la tâche de fond a été lancée par toi. Le geste concret, celui qui bloque réellement ton
tour en avant-plan et qui rend la main dès que c'est fini, sans dépendre d'aucune notification :

```
for i in $(seq 1 60); do
  if grep -q "exited with code" <chemin>/<id-tache>.output 2>/dev/null; then
    echo "=== TERMINE ==="; tail -40 <chemin>/<id-tache>.output; break
  fi
  echo "en cours ($i)"; sleep 30
done
```

Le `<chemin>/<id-tache>.output` est celui que la commande t'a donné au moment où tu l'as lancée en
arrière-plan. Cette boucle plafonne à trente minutes - largement de quoi couvrir une suite de
tests de ce projet - et se relance simplement (nouvel appel, même motif) si elle expire sans que
la tâche soit terminée : ne jamais la confondre avec un signal que la tâche elle-même a échoué. Une
simple relecture du fichier à chaque tour (`tail -40`) fonctionne tout aussi bien si la boucle
complète n'est pas disponible : tant que la dernière ligne ne dit pas « exited with code », c'est
encore en cours, on réinterroge. Dans les deux cas, ne jamais terminer un tour en te déclarant en
attente d'une notification à venir - le tour suivant ne viendra pas tout seul.

**Un Monitor « armé » ne te réveillera pas non plus.** Mesuré six fois le 2026-08-29 et le
2026-08-30, sur six agents distincts. « Armé » veut dire qu'il surveille, jamais qu'il te parlera :
sa notification remonte à la boucle principale, pas à un agent délégué. Deux agents ont même
ABANDONNÉ l'interrogation manuelle qui fonctionnait pour revenir à cette attente qui ne fonctionne
pas, la jugeant plus propre. **L'interrogation est la méthode normale, pas un pis-aller.**

**Ce qui reste bon, et qu'il ne faut PAS relâcher** : refuser de déployer avant d'avoir la preuve,
et ne lancer qu'une suite de tests à la fois. Ces exigences sont justes. Seule la façon d'attendre
était fausse.

**Trois lectures possibles du fichier, trois suites :**
1. *Terminé et vert* → tu enchaînes, sans demander la permission.
2. *Terminé et rouge* → tu colles la sortie d'échec et tu établis si elle vient de TON changement
   ou d'une dette préexistante. Tu ne contournes pas, tu ne devines pas la cause.
3. *Toujours en cours* → tu réinterroges. Et si ça dépasse largement le temps attendu, **soupçonne
   ton propre délai d'attente avant d'accuser la commande** : le 2026-08-28, des lots de tests
   réputés « morts » avaient simplement été coupés par le timeout de l'appelant.

**Piège voisin, mesuré le même soir** : deux suites de tests lancées en même temps se font
mutuellement échouer, parce que `modules_statuses.json` est un fichier RÉEL partagé (hors base
`:memory:`) que plusieurs modules de test écrivent. Symptôme : `FileActivator::readJson(): Return
value must be of type array, null returned`. Avant de conclure à une régression, vérifie qu'aucune
autre suite ne tourne, et relance le test SEUL.


## 6 quater. UNE SUITE COMPLÈTE LANCÉE DANS UN CHECKOUT PARTAGÉ NE PROUVE RIEN

Mesuré plusieurs fois les 2026-08-29 et 30. Ce dépôt est travaillé par plusieurs sessions en
parallèle. Une suite complète dure une quarantaine de minutes ; pendant ce temps, une autre session
commite, change de branche ou pousse. Les fichiers changent SOUS la suite en cours.

Trois issues observées, toutes trompeuses : la suite est tuée sans rendre de verdict ; elle échoue
sur un fichier qu'un autre venait de modifier ; ou elle passe, mais sur un mélange de deux états du
code qui n'a jamais existé nulle part.

**La preuve fiable est une suite CIBLÉE sur le module touché, lancée dans un clone indépendant.**
Elle prend quelques minutes, personne n'écrit dedans, et son verdict porte sur le code qu'on a
réellement modifié. Une suite complète reste utile comme filet périodique, jamais comme preuve d'un
correctif précis.

Corollaire, déjà appliqué avec justesse par plusieurs agents : quand la suite complète meurt ou
traîne, **ne pas la présenter comme un résultat, et ne pas la remplacer par un silence** - dire
qu'elle n'a pas rendu de verdict, et fournir la preuve ciblée qui, elle, vaut.

## 6 ter. UN WORKTREE AVEC `vendor/` SYMBOLIQUE TESTE LE MAUVAIS CODE

Mesuré le 2026-08-30. Un agent isole son travail dans un worktree pour ne pas gêner les autres
sessions - bon réflexe, encouragé plus haut. Mais si `vendor/` y est un lien symbolique vers celui
du dépôt principal, **le chargeur de classes résout le VRAI chemin du lien et charge donc le code du
dépôt principal, pas les modifications du worktree.**

Conséquence, et elle est vicieuse : le test « rouge avant » et le test « vert après » sont FAUX tous
les deux, puisqu'ils s'exécutent sur du code que l'agent n'a pas modifié. La preuve de non-vacuité,
qui est précisément ce qui doit protéger d'un correctif illusoire, devient elle-même illusoire.

**Avant de tirer la moindre conclusion d'un test lancé depuis un worktree**, vérifier quel fichier
est réellement chargé - par réflexion sur la classe visée, ou en cassant volontairement le fichier
du worktree pour voir si le test s'en aperçoit. S'il ne s'en aperçoit pas, le test regarde ailleurs.

Remèdes, du plus simple au plus sûr : un clone indépendant plutôt qu'un worktree quand des
dépendances entrent en jeu, ou à défaut un `composer dump-autoload` local au worktree avec un
`vendor/` qui lui appartient.

## 7. CE QU'ON ATTEND D'UN RAPPORT

- Court, factuel, sans embellissement.
- Ce qui a été RÉUTILISÉ plutôt que réécrit, avec les chemins.
- La sortie RÉELLE des vérifications, pas leur résumé.
- **Toute anomalie, même mineure, même gênante.** Un rapport honnête d'échec partiel vaut
  infiniment mieux qu'une affirmation fausse.
- Si quelque chose t'a empêché de finir, DIS-LE. Ne prétends jamais avoir réussi.
