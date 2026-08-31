# Décision : lier le déploiement de laveille.ai à un filet de tests fiable (ticket #2095)

**Date : 2026-08-31 (Québec).** Mandat : mesurer, consulter le club des sages (5 oracles, 3 rounds minimum), trancher, proposer. **Zéro implémentation dans ce cycle** - ce document est la spécification à valider avant tout code.

---

## 1. Le problème, en une phrase

Le déploiement en production (`deploy.yml`) et la CI (`ci.yml`) sont deux workflows GitHub Actions indépendants déclenchés par le même push : **aucun des deux ne consulte l'autre**, et même quand la CI tourne jusqu'au bout, son job de tests ne peut structurellement pas faire échouer le passage - donc rien n'a jamais empêché, et rien n'empêche aujourd'hui, un commit cassé de partir en production.

---

## 2. Les faits mesurés (toutes les commandes sont reproductibles)

### 2.1 Durée réelle de la suite complète

Quatre mesures indépendantes, prises dans les logs bruts de runs GitHub Actions réels et non annulés (`gh run view <id> --log`), plus une cinquième déjà documentée dans `ci.yml` :

| Run | Date | Durée (job complet) | Durée (exécution des tests seule) |
|---|---|---|---|
| 33337672499 | 2026-08-30 21:54 | 2079 s | 2008,05 s |
| (commentaire `ci.yml`) | 2026-08-30 | - | 2204,87 s |
| 33308835659 | 2026-08-30 (cluster d'échecs) | - | non disponible (échec avant les tests) |
| 33399133405 | 2026-08-31 13:49 | 2325 s | 2243,23 s |

**Conclusion : 33 à 39 minutes selon le run, centré autour de 36-37 minutes.** Exécutée avec `php artisan test --parallel` (4 processus), 6600-7500 tests Pest au total (exactement : 1 échoué + 701 ignorés + 6795 réussis = 7497 tests, 19529 assertions sur le run du 2026-08-31).

### 2.2 Cadence de push

`git log --pretty=format:'%ad' --date=format:'%Y-%m-%d' origin/master | sort | uniq -c` sur les 21 derniers jours avec commits : moyenne ~11 push/jour, avec des pics à **30 (23 août), 32 (30 août) et 18 (31 août, journée partielle)**. Sur les 8 jours depuis la résurrection de la CI (24 au 31 août) : ~13,4 push/jour en moyenne. Le détail horodaté montre des rafales de moins de 5 minutes entre deux pushes (ex. 04h51-04h53-05h05, ou une séquence quasi continue de 10h30 à 11h08 le 31 août).

**Une suite de 36 minutes ne peut structurellement pas suivre cette cadence en série.**

### 2.3 Le mécanisme qui masque tout : `continue-on-error: true`

Le job `tests` de `ci.yml` porte `continue-on-error: true` (ajouté le 2026-08-30 en réponse à une dette de tests préexistante, jamais un correctif malveillant). Conséquence vérifiée sur DEUX runs distincts et indépendants, dont un du jour même :

- Run `33337672499` (2026-08-30 21:54) : job `tests` conclusion = **`failure`** (4 tests rouges : `Phase155Test` ×3, `TranslationModuleTest` ×1) ; conclusion du **workflow entier = `success`**.
- Run `33399133405` (2026-08-31 13:49) : job `tests` conclusion = **`failure`** (`BlogAdminTest` : « No hint path defined for [fronttheme] ») ; conclusion du **workflow entier = `success`**.

**Un `needs: ci` naïf posé aujourd'hui sur ce même job ne bloquerait donc RIEN de nouveau** : il verrait toujours « success ».

### 2.4 Les tests qui échouent ne sont pas les mêmes d'un run à l'autre

Comparaison de 3 runs à job `tests` réellement rouge (log brut, motif `FAILED`) : `Phase155Test`/`TranslationModuleTest` un jour, `BlogAdminTest` un autre jour, un troisième run sans trace de `FAILED` (échec avant l'étape tests). **Ce n'est pas une dette statique et stable : c'est un signal de flakiness du mode `--parallel`.** La doctrine interne du projet (« une suite complète lancée dans un dépôt local partagé ne prouve rien ») ne s'applique pas littéralement à GitHub Actions (checkout frais et isolé par run, base SQLite `:memory:` locale au process, un seul run actif par ref grâce à `cancel-in-progress`) - mais cette flakiness `--parallel` est un problème de fiabilité **distinct et propre à la CI elle-même**, à traiter comme tel.

### 2.5 Annulations, échecs et succès réels

`gh run list --workflow=ci.yml --limit 50` (événements `push` seulement) : **29/50 annulés (58 %)**, **7/50 échec réel du workflow (14 %)**, **13/50 succès (26 %)**, 1 en cours. Les 7 échecs réels : 5 concentrés sur une fenêtre de 32 minutes le 2026-08-30 matin (pendant le chantier actif de réparation de la CI, avant l'ajout de `continue-on-error`), 2 anciens de mars-avril 2026 (avant la résurrection de la CI). **Le nombre annoncé dans le mandat (« 6 exécutions sur 10 ») est confirmé, et même légèrement dépassé sur un échantillon plus large.**

### 2.6 `deploy.yml` ne dépend de rien et n'a aucune concurrency

`deploy.yml` se déclenche sur `push: branches: [master]` avec un `paths-ignore` étroit (docs, `*.md`, journal) - **sans `needs`, sans `workflow_run`, sans bloc `concurrency`**. Les 30 derniers runs de déploiement inspectés sont **tous `conclusion: success`**, chacun en 60 à 90 secondes. Rien n'empêche aujourd'hui deux `rsync` de se chevaucher si deux pushes arrivent à moins de 90 secondes d'écart (constaté possible dans les rafales du 2.2) - risque distinct, non couvert par le ticket #2095 mais mesuré au passage et intégré à la recommandation.

### 2.7 `e2e` (Playwright) est mort dans le flux réel

`e2e` ne se déclenche que sur `pull_request`, jamais sur `push`. `gh pr list --state all` ne montre aucune PR de livraison (uniquement Dependabot et une branche d'audit ancienne) : **ce dépôt ne travaille jamais par PR**. Dernière exécution de `ci.yml` sur événement `pull_request` : mars 2026, 100 % en échec. `e2e` ne protège donc rien dans le flux de livraison réel actuel.

### 2.8 Structure de tests déjà disponible

`phpunit.xml` définit déjà 4 testsuites : `Architecture` (2 fichiers), `Unit` (4 fichiers), `Feature` (265 fichiers), `Modules` (531 fichiers répartis sur ~20 modules Laravel). **Les 3 échecs réels observés (2.4) vivent tous dans `Feature`/`Modules` - aucun dans `Architecture`/`Unit`** sur les runs inspectés.

---

## 3. Le club des sages - protocole et disponibilité

**5 oracles convoqués, 4 ont répondu.** `claude.ai` (Playwright) a échoué 3 fois de suite avec `Target page, context or browser has been closed` - une panne du serveur MCP lui-même (pas un cookie expiré : `ia-sync` n'aurait rien changé). Circuit ouvert après 3 échecs, conformément au protocole ; **claude.ai est resté indisponible pour toute la session**, signalé ici plutôt qu'omis.

- **Perplexity** (`pp_search`) : répondu aux 3 rounds, avec 2 échecs intermédiaires récupérés (round 2 : `mode="pro"` a rouvert un fil « Task » pollué par un sujet sans rapport - connu et documenté dans ce projet ; corrigé en repassant en `mode="auto"` et en recentrant la question sur son point fort, la recherche factuelle sourcée).
- **Codex** (`superagent`) : répondu aux 3 rounds, avec 2 échecs intermédiaires (rounds 2 et 3, premières tentatives : sortie démesurée de 200 000+ à 713 000 caractères - un emballement, pas un refus) ; corrigé en raccourcissant drastiquement la consigne et en évitant de lui faire relire un fichier.
- **DeepSeek** (`hermes model_invoke`, `task_type=reasoning`) : répondu aux 3 rounds sans incident.
- **Gemini** (`agy`, modèle Gemini 3.1 Pro High) : répondu aux 3 rounds sans incident.

**3 rounds exécutés** (R1 génération divergente en aveugle, R2 réfutation croisée avec mandat explicite de tuer y compris ses propres idées, R3 attaque des survivantes puis dépassement). **Critère d'arrêt** (fixé avant le lancement) : 2 rounds consécutifs sans idée neuve passant le filtre, ou convergence unanime déclarée. **Atteint après le round 3** : aucune idée véritablement neuve (seulement des raffinements de round 2), et convergence explicite et nommée des 4 oracles sur le mécanisme central (voir section 5).

---

## 4. Les 4 options du mandat, démolies

| Option | Verdict | Motif exact (qui) |
|---|---|---|
| **M1** - déploiement dépendant de la CI complète bloquante | **TUÉE**, 3/3 | Bloque chaque livraison derrière 33-39 min, incompatible avec la cadence mesurée (rafales < 5 min) ; **et même ainsi, ne changerait rien** tant que `continue-on-error` masque le job `tests` (2.3) |
| **M2** - désactiver `cancel-in-progress` sur CI | **TUÉE**, 3/3 | Fait tourner une file de suites sur des commits déjà dépassés ; gaspille le calcul sans corriger l'absence de lien CI→déploiement |
| **M3** - suite courte bloquante + suite complète non bloquante (principe seul) | **Absorbée** - le principe survit mais seulement une fois entièrement spécifié (composition exacte, mécanisme de liaison, sort de la suite longue) : c'est l'objet de la section 5 | Codex et DeepSeek le jugent juste mais insuffisant tel quel ; Gemini le juge trop vague pour survivre en l'état - divergence résolue par la spécification complète ci-dessous |
| **M4** - ne rien changer, documenter que la CI n'est pas un filet | **TUÉE**, 3/3 | Incompatible avec la règle projet « zéro casse » ; documenter un trou connu n'est pas le combler |

---

## 5. La recommandation

### 5.1 Mécanisme complet

**A. Un sas rapide et fiable, bloquant, sur chaque push vers `master`**
- Contenu : testsuites `Architecture` + `Unit` (6 fichiers, confirmés stables sur tous les runs inspectés) **+ un sous-ensemble EXISTANT de tests `Feature`/`Modules` déjà stables, marqué par un tag** (`@smoke` ou équivalent Pest `->group('smoke')`) plutôt que de créer une suite dédiée neuve - éviter d'ajouter une nouvelle surface de maintenance qui finirait par devenir flaky à son tour (auto-critique de Gemini, round 3, retenue).
- Exécution **parallélisée** (pas d'interdiction du mode `--parallel` sur ce sous-ensemble restreint - la piste « `--parallel` cause la flakiness » avancée par Codex au round 1 reste **non confirmée** ; voir section 7, point ouvert).
- Cible : **sous les 10 minutes** (pas 2-5 min comme proposé initialement - corrigé au round 3 par Gemini et Codex eux-mêmes comme trop optimiste pour ce volume).
- **`continue-on-error` retiré de ce sas.** Zéro tolérance : le sas doit pouvoir réellement échouer.

**B. SHA-pinning obligatoire (le point qui a survécu à l'unanimité, sans une seule réserve, à travers les 3 rounds)**
- Le déploiement se déclenche via `workflow_run` (jamais `needs` - impossible entre deux workflows séparés, confirmé par Codex round 3), avec vérification explicite `conclusion == 'success'` **et** déploiement du `head_sha` exact rapporté par cet événement - jamais un `checkout` implicite de la branche courante.
- Sans ce garde-fou, la course documentée par Codex au round 1 reste ouverte : sous `cancel-in-progress` et cadence rapide, l'approbation du sas pour le push A pourrait autoriser le déploiement du push B, plus récent et jamais testé.
- Confirmé comme pattern industriel mature et documenté (GitHub artifact attestations, alignement SLSA Build Level 3) par la recherche sourcée de Perplexity - ce n'est pas une invention du panel.

**C. Concurrency de production : file stricte, jamais d'annulation en cours de transfert**
- `concurrency: group: production-deploy` **sans** `cancel-in-progress` (donc `false`, la valeur par défaut).
- Motif tranché au round 3 par un fait technique concret, pas une opinion : `rsync` n'est pas une opération atomique. Interrompre un déploiement en cours de transfert laisserait le répertoire de production dans un état mi-copié - **casse immédiate**, pire que le problème qu'on cherche à corriger. C'est ce fait précis (pas une préférence) qui tranche la divergence C3/D2 du round 2 (voir section 6).
- Le risque d'empilement (rafale de 10 commits = 10 déploiements en file) se résorbe naturellement une fois le sas amont ramené sous 10 minutes : il n'y a plus de rafale à absorber au niveau du déploiement lui-même.

**D. La suite complète (33-39 min) démotée en filet asynchrone, jamais sur le chemin critique**
- Convergence explicite et littérale de Codex ET Gemini au round 3, indépendamment : exécution **nocturne planifiée** (`schedule` cron), détachée du déploiement.
- Continue de tourner ; ses échecs produisent une alerte visible (pas un silence, et pas un blocage rétroactif).
- Sert aussi de terrain pour diagnostiquer la flakiness elle-même (comparer une exécution `--parallel` et une exécution série de temps en temps) - en diagnostic ponctuel, jamais en politique permanente (l'idée d'une suite longue systématiquement non parallèle a été tuée par ses 3 auteurs successifs comme trop coûteuse).

### 5.2 Ce que ce mécanisme protège vraiment

- Toute régression capturée par `Architecture` + `Unit` (garanties actuellement stables).
- Toute régression dans le périmètre du sous-ensemble `@smoke` choisi (authentification, accès admin, montée de Laravel, une réponse HTTP de base, migrations) - sous réserve que ce sous-ensemble soit effectivement stable et effectivement représentatif (voir section 7).
- La garantie que **ce qui a été testé est exactement ce qui est déployé** (SHA-pinning) - élimine la course silencieuse actuellement possible.
- L'intégrité du répertoire de production pendant un transfert (concurrency stricte) - corrige un second problème mesuré au passage (2.6), non demandé explicitement mais découvert pendant la mesure.

### 5.3 Ce que ce mécanisme NE protège PAS

- Toute régression confinée aux ~99 % de `Feature`/`Modules` qui restent hors du sous-ensemble `@smoke` : elle continuera de partir en production, détectée seulement par la suite nocturne, jusqu'à 24 h plus tard.
- Une migration de base de données qui casse silencieusement des données (le SHA-pinning garantit le bon CODE, pas la bonne MIGRATION) - c'est précisément ce que l'idée en dormance de la section 6 (releases + bascule atomique, migrations rétrocompatibles) viserait à couvrir, mais elle n'est pas dans le périmètre de ce cycle.
- Les pannes d'infrastructure pure (disque, configuration serveur, quotas cPanel) qu'aucune suite de tests ne couvre.
- Le flaky whack-a-mole : le sas doit rester stable PAR CONSTRUCTION (discipline de composition), ce mécanisme ne le rend pas stable par magie.

### 5.4 Coût

- **Latence** : ~8-10 minutes entre un push et la mise en production réelle, contre ~1-2 minutes aujourd'hui (mesuré : les déploiements actuels prennent 60-90 secondes, sans aucune attente). C'est un changement de comportement réel pour les sessions d'agents qui poussent en s'attendant à un effet immédiat.
- **Complexité d'implémentation** (hors périmètre de ce cycle, pour le prochain) : scinder `ci.yml` en un job rapide et un job nocturne planifié, retirer `continue-on-error` du job rapide seulement, réécrire le déclencheur de `deploy.yml` en `workflow_run`, ajouter la vérification `head_sha`, ajouter le bloc `concurrency` de production, choisir et taguer le sous-ensemble `@smoke`.
- **Risque de faux négatif** : le sous-ensemble `@smoke` doit être choisi avec soin (section 7) - trop étroit, il ne protège rien de plus qu'`Unit`+`Architecture` ; trop large ou mal choisi, il réintroduit la flakiness et le dépassement du budget de 10 minutes.

---

## 6. La divergence la plus intéressante du panel

**Round 2 : DeepSeek propose sa propre idée de « backtesting en sandbox ». Round 3 : DeepSeek tue sa PROPRE idée, en la jugeant irréalisable sur l'infrastructure actuelle (« nécessite des conteneurs, un budget de stockage dédié, une migration cloud ») - il tue aussi, par la même logique, l'idée de « déploiement shadow » de Gemini, jugée tout aussi hors de portée.**

**Perplexity, interrogé séparément et factuellement sur la même question, trouve le contraire** : un pattern documenté depuis longtemps (Deployer.org, doc datée du 2024-11-28 citée) permet une bascule atomique par répertoires versionnés + lien symbolique `current`, **sans aucun conteneur**, directement compatible avec un hébergement cPanel mutualisé et un déploiement SSH/rsync - exactement l'infrastructure de ce projet. Gemini, confronté à cette découverte au round 3, confirme et détaille lui-même la version dégradée applicable ici (voir section 8).

**Ce que cette divergence enseigne, au-delà du sujet CI/CD** : un oracle a tué sa propre idée sur la base d'une hypothèse d'infrastructure **jamais vérifiée** (« il faut des conteneurs »). Une recherche factuelle indépendante, demandée à un autre oracle, a directement contredit cette hypothèse. Sans cette vérification croisée, une idée réellement applicable aujourd'hui - à coût modéré - serait tombée dans l'oubli pour une mauvaise raison. C'est très exactement le risque déjà documenté dans la mémoire de ce projet (un oracle qui fabrique un défaut ou une contrainte plutôt que d'admettre qu'il ne l'a pas vérifiée) : **un verdict d'oracle sur la faisabilité technique n'est fiable que si quelqu'un - ici, un autre oracle - va vérifier le fait sous-jacent.**

---

## 7. Ce qui reste ouvert (hors périmètre décisionnel de ce cycle)

1. **La composition exacte du sous-ensemble `@smoke`** - quels tests précis, au-delà d'`Architecture`+`Unit`. C'est le point que le mandat désignait lui-même comme « le vrai travail ». Avant de le figer, un diagnostic ciblé et peu coûteux est recommandé : comparer, sur les tests candidats, une exécution `--parallel` contre une exécution série, plusieurs fois de suite, pour établir si l'hypothèse de Codex (round 1 : « `--parallel` cause probablement la flakiness ») est vraie ou fausse - elle n'a jamais été vérifiée par le panel, seulement supposée deux fois (round 1) et démentie une fois sans preuve non plus (round 3, Gemini : « le parallélisme n'est pas optionnel »). **Cette contradiction non résolue entre deux rounds du même panel est elle-même consignée ici plutôt que tranchée arbitrairement.**
2. **Le sort de `e2e`** : mort dans le flux réel (2.7), mais pas explicitement redemandé par le mandat - une décision distincte (le supprimer, le réactiver sur push, ou le laisser en sommeil) reste à prendre séparément.
3. **La dette Pint/Larastan/audits** (`|| true` partout dans `code-quality` et `security`) : hors périmètre strict de #2095, mais partage exactement le même défaut structurel (signalement sans jamais bloquer) et mériterait le même traitement dans un cycle ultérieur.

---

## 8. Idée en dormance à retenir pour un prochain cycle

**Bascule atomique par répertoires versionnés (`releases/<sha>/` + lien symbolique `current`), sans conteneurs, réalisable sur l'infrastructure cPanel actuelle** (détail et condition de réveil : `innovation/idees-mortes.md`). Élimine le besoin de tout `git revert` en cas d'échec post-déploiement (on re-pointe simplement le lien vers la release précédente, opération quasi instantanée) et rend le rollback réellement sûr - condition : migrations strictement rétrocompatibles (expand/contract), et une vérification préalable du comportement d'OPcache/FastCGI à travers un chemin résolu par symlink (risque identifié par Perplexity, jamais vérifié sur ce serveur précis - ce projet a déjà un historique de réglages OPcache sensibles, cf. mémoire projet). **Ne fait PAS partie de la recommandation de ce cycle** : c'est un chantier d'infrastructure distinct, plus large que la politique de gating demandée par le ticket #2095.

---

## 9. Filtre à 3 axes (appliqué à la recommandation de la section 5)

- **VRAI** (le gain existe-t-il réellement) : oui - mesuré, pas supposé. Le SHA-pinning ferme une course aujourd'hui ouverte et démontrable ; le sas rapide aurait intercepté au moins les 2 pannes réelles observées cette semaine (`Phase155Test`/`TranslationModuleTest`, `BlogAdminTest`) si son sous-ensemble les avait couvertes - ce qui reste à vérifier au moment du choix du sous-ensemble (section 7).
- **PERÇU** (une personne ou un agent ordinaire le remarque-t-il) : oui, dans les deux sens - un déploiement qui prend 10 minutes au lieu d'1-2 est un changement de comportement directement observable pour les sessions d'agents qui poussent ; une régression bloquée avant la mise en ligne plutôt qu'après l'est tout autant.
- **DÉFENDABLE** (résiste-t-il dans 6 mois) : conditionnel - seulement si le sas reste volontairement petit et si sa stabilité est surveillée activement. C'est exactement la dérive qui a rendu la suite complète actuelle inutilisable (`continue-on-error` ajouté après l'accumulation d'une dette non traitée) : le même sort guette le sas rapide sans discipline de maintenance.

---

## 10. Ce que ce document n'est pas

Aucune ligne de `ci.yml` ni de `deploy.yml` n'a été modifiée pour produire cette spécification. Le mandat demandait une décision, pas un correctif - conformément à la consigne reçue.

*MEMORA solutions - info@memora.ca*
