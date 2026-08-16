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
- **Le texte alternatif décrit l'image**, il ne contient pas de mots-clés - le bourrage dégrade
  l'accessibilité sans bénéfice.

## 7. CE QU'ON ATTEND D'UN RAPPORT

- Court, factuel, sans embellissement.
- Ce qui a été RÉUTILISÉ plutôt que réécrit, avec les chemins.
- La sortie RÉELLE des vérifications, pas leur résumé.
- **Toute anomalie, même mineure, même gênante.** Un rapport honnête d'échec partiel vaut
  infiniment mieux qu'une affirmation fausse.
- Si quelque chose t'a empêché de finir, DIS-LE. Ne prétends jamais avoir réussi.
