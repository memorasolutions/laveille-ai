# Audit ciblé — mécanisme Service Worker ("Update on reload") — 2026-07-10

Score global : **92/100**

## Matrice de couverture (complétée)

| Dimension | Statut | Preuve / justification |
|---|---|---|
| securite-applicative | non applicable | Aucune surface d'attaque liée au mécanisme SW - hors périmètre demandé |
| securite-infra | **complété** | ETag/content-length stables sur 2 requêtes espacées de 5s (`907e-65642bfd185e6`, 36990 octets) ; `service-worker-allowed: /` présent en prod (Apache) |
| qualite-code-DRY | **complété** | Revue de `resources/js/pwa.js` (69 lignes) : registration unique, garde `isAdminRoute`, cleanup ciblé sw-authors.js (scope exact, pas de faux positif) |
| performance | **complété** | Un seul timer dans tout le système (`registration.update()` /1h, dans `onRegisteredSW`) ; aucun minuteur caché ni boucle |
| accessibilite | non applicable | Aucun impact UI/a11y - hors périmètre demandé |
| UX-UI | **complété** | Message n'apparaît que dans la console DevTools d'un visiteur avec "Update on reload" coché - jamais visible d'un visiteur normal |
| SEO-GEO-AEO | non applicable | Hors périmètre demandé |
| conformite-Loi25-RGPD | non applicable | Hors périmètre demandé |
| tests-couverture | **complété** | Aucun test n'existait sur le mécanisme SW - reproduction manuelle Playwright effectuée (voir Phase 6), recommandation ci-dessous |
| dependances-CVE-licences | non applicable | Hors périmètre demandé |
| hygiene-serveur | **complété** | `curl -I` prod × 2 : fichier stable, cache Cloudflare `HIT`, aucune anomalie serveur |

## Verdict (Phase 6 - preuves de reproduction, 4 méthodes indépendantes)

**Conclusion : il n'existe AUCUN bug côté code ou serveur causant une répétition réelle de l'enregistrement du Service Worker.**

1. **Comptage de messages (round 2)** : 5 minutes d'observation continue sans navigation forcée sur 4 pages différentes → nombre de messages stable, zéro croissance.
2. **Audit de code (round 2)** : un seul `setInterval` dans tout le système PWA (`registration.update()` toutes les heures, dans `onRegisteredSW`), qui ne se déclenche même pas en local (l'enregistrement échoue faute de `Service-Worker-Allowed`). Aucun autre minuteur, aucun ré-abonnement d'écouteur en boucle.
3. **Stabilité HTTP (round 3)** : `ETag`/`Content-Length` du fichier `sw-source.js` identiques sur 2 requêtes espacées de 5s en prod. Le fichier ne change JAMAIS entre deux chargements - la condition nécessaire à un vrai cycle de mise à jour n'existe pas.
4. **Reproduction Playwright réelle (round 4, ce jour)** : 3 navigations complètes et réelles (`page.goto`, équivalent à un rechargement dur) sur `https://laveille.ai/glossaire/iteration`, chacune suivie d'un relevé complet de la console. **Résultat : exactement 1 message anodin identique à chaque fois** (`beforeinstallprompt` preventDefault, sans lien avec le Service Worker), **zéro message lié à une mise à jour du SW sur les 3 essais**.

**Piste explorée et écartée (nouvelle cette ronde)** : hypothèse Livewire `wire:navigate` (navigation SPA sans reload complet, qui aurait pu ré-exécuter le script d'enregistrement à chaque clic interne). Vérifiée par grep exhaustif : `wire:navigate` n'est utilisé QUE sur 5 pages précises (messagerie Académie, connexion/inscription), **jamais** sur le parcours public glossaire/actualités où le symptôme est rapporté. La balise `<body>` du layout public ne porte aucun attribut de navigation SPA globale. Hypothèse formellement écartée pour la page concernée.

**Explication la plus probable, confirmée par 4 méthodes convergentes** : le message exact cité par l'utilisateur est une chaîne **native de Chrome DevTools**, imprimée uniquement quand la case « Update on reload » est cochée dans l'onglet Application, à chaque F5/reload manuel effectué par l'utilisateur pendant qu'il inspecte la page - combiné probablement à « Preserve log » qui accumule les messages de plusieurs sessions dans la même vue console, créant une impression de boucle continue.

## Recommandation actionnable (fichier:ligne, correction, outil)

| # | Finding | Sévérité | Fichier:ligne | Correction proposée | Outil recommandé |
|---|---|---|---|---|---|
| 1 | Aucun test de non-régression ne garantit qu'un futur changement de `pwa.js` n'introduira pas un vrai double-enregistrement | Basse | `Modules/FrontTheme/tests/` (nouveau fichier à créer) | Ajouter un test Playwright/E2E (hors Pest, JS) qui navigue 3× sur une page publique et assert `navigator.serviceWorker.getRegistrations().length === 2` (SW principal + sw-authors si déjà enregistré) stable entre les 3 passes | Sonnet (test à écrire, pattern Playwright déjà démontré dans cette session) - non prioritaire, à faire lors d'un prochain chantier PWA |
| 2 | Aucune action requise sur `resources/js/pwa.js` - code déjà sain | — | — | — | — |

## Needs-review (validation humaine)

**Action demandée à l'utilisateur** : la prochaine fois que le message apparaît, vérifier concrètement dans DevTools → **Application → Service Workers** si la case **« Update on reload »** est cochée, et décocher **« Preserve log »** dans l'onglet Console. Si le message persiste avec les DEUX cases décochées et un SEUL reload (F5), c'est un signal réellement nouveau qui justifierait une 5e investigation avec capture d'écran à l'appui - aucune preuve technique disponible aujourd'hui ne permet d'aller plus loin sans cette confirmation visuelle du côté navigateur de l'utilisateur.

## Preuve de nettoyage (Phase 7, point 7 du skill)

- Aucun cron temporaire créé pendant cet audit.
- `browser_close` exécuté après la session Playwright de reproduction.
- Fichier de matrice conservé dans `.audit/AUDIT-MATRICE-sw-update-on-reload-2026-07-10.md` (traçabilité).

## Incident opérationnel découvert pendant ce chantier (hors périmètre SW, documenté séparément)

Le déploiement de la fonctionnalité News liée (auto-détection d'outils, v1.102.0) a révélé un défaut de conception distinct (backfill de migration non borné bloquant le pipeline CI >10 min sur le backlog prod réel). Corrigé (migration retirée, remplacée par une commande manuelle bornée `news:backfill-auto-tools --limit=200`) - voir tâches #971/#974 et CHANGELOG v1.102.0.
