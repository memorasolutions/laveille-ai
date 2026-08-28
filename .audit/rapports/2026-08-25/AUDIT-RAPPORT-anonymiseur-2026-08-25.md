# Audit complet - Anonymiseur de texte

- **Cible** : https://laveille.ai/outils/anonymiseur (production, **lecture seule** - rien n'a été modifié)
- **Mode** : humain - l'outil a été réellement utilisé dans un navigateur, avec des données personnelles québécoises **fictives**
- **Portée** : complète, 11 dimensions, aucune omise
- **Date** : 2026-08-25, 10h05 Québec (14h05 UTC)
- **Version auditée** : v1.218.0

---

## 1. Matrice de couverture (gate de sortie : zéro « à faire »)

| Dimension | Statut | Preuve |
|---|---|---|
| securite-applicative | complété | Test d'injection HTML exécuté en prod : aucun code exécuté, balises échappées |
| securite-infra | complété | `sec_headers` : grade A, 7/8 ; CSP relevée au curl |
| qualite-code-DRY | complété | 1 897 lignes JS, 0 dépendance externe, 0 appel CDN (grep) |
| performance | complété | Mesures `PerformanceObserver` en prod : FCP 564 ms, load 583 ms, 48 requêtes |
| accessibilite | complété | `wcag_audit_full` (86 critères) + tri manuel des faux positifs dans la page réelle |
| UX-UI | complété | Parcours complet effectué, captures à l'appui |
| SEO-GEO-AEO | complété | Balises, canonical, JSON-LD relevés sur la page servie |
| conformite-Loi25-RGPD | complété | Trafic réseau inspecté requête par requête + stockage local énuméré |
| tests-couverture | complété | 3 tests Pest + 3 fichiers JS exécutés (tous verts) |
| dependances-CVE-licences | complété | `composer audit` : 9 avis sur 3 paquets |
| hygiene-serveur | complété | Aucun cron lié à l'outil, aucun script temporaire déposé |

---

## 2. Scores par dimension

Chaque note porte son taux de couverture et son niveau de confiance : une note nue donnerait une fausse précision.

| Dimension | Note | Couverture | Confiance |
|---|---|---|---|
| securite-applicative | 78/100 | 70 % | élevée |
| securite-infra | 72/100 | 90 % | élevée |
| qualite-code-DRY | 70/100 | 40 % | moyenne |
| performance | 92/100 | 85 % | élevée |
| accessibilite | 84/100 | 75 % | moyenne |
| UX-UI | 74/100 | 80 % | élevée |
| SEO-GEO-AEO | 93/100 | 90 % | élevée |
| conformite-Loi25-RGPD | 88/100 | 85 % | élevée |
| tests-couverture | 55/100 | 95 % | élevée |
| dependances-CVE-licences | 60/100 | 90 % | élevée |
| hygiene-serveur | 95/100 | 80 % | élevée |

**Score global pondéré par la couverture : 78/100.**

Zone la moins explorée, donc la moins fiable : la qualité du code (40 %). Elle n'est pas « saine », elle est **peu connue** - la structure et l'absence de dépendances ont été vérifiées, pas la duplication interne fine des 1 897 lignes.

---

## 3. Ce qui est excellent, et qui a été vérifié plutôt que cru

**La promesse « 100 % local » est tenue, et c'est structurel.** Deux preuves indépendantes, dont la seconde est la plus forte :

*Preuve par le code* : les trois fichiers JS de l'outil ne contiennent **aucun canal de sortie**. Zéro `fetch`, zéro `XMLHttpRequest`, zéro `sendBeacon`, zéro `WebSocket`, zéro `RTCPeerConnection`, zéro `postMessage`, zéro `serviceWorker`, zéro `new Image`. Le texte ne peut pas sortir parce qu'il n'existe aucun moyen de le faire sortir. Cette vérification a été ajoutée après une passe adversariale qui reprochait, à juste titre, de ne s'appuyer que sur l'observation du trafic.

*Preuve par la mesure* : toutes les requêtes réseau émises pendant un cycle complet d'anonymisation ont été inspectées une par une. Aucune ne transporte le texte saisi. Les deux seuls POST sont `/cdn-cgi/rum` (métriques Cloudflare : mémoire, timings, `pageloadId`) et `/api/privacy/consent` (enregistrement du refus de témoins). Le corps du premier a été lu intégralement : que des métriques.

**Aucune injection possible.** Une charge utile classique (`<img src=x onerror=…>` + `<script>`) a été saisie puis passée dans le pipeline : aucun code exécuté, aucune balise injectée dans le DOM, contenu échappé et traité comme du texte. Sur un outil qui manipule du contenu utilisateur, c'est le risque numéro un, et il est fermé dans le code.

**Zéro dépendance externe.** Aucun `require`, aucun appel CDN dans les trois fichiers JS. Pour un outil de confidentialité, c'est la bonne décision : rien à faire confiance, aucune chaîne d'approvisionnement à surveiller.

**Auto-purge après 7 jours d'inactivité**, avec le compromis expliqué en commentaire (`anonymizer-ui.js:105`). L'utilisateur est averti à l'écran que texte et correspondances restent dans son navigateur, et un bouton « Oublier mes données » existe. C'est un choix assumé, documenté et réversible - pas un défaut.

**Performance et SEO** : FCP 564 ms, page interactive en 583 ms, 48 requêtes. Title, description, canonical, `og:image` en JPG (correct pour le partage social), `lang="fr-CA"`, un seul h1, JSON-LD `SoftwareApplication` + `WebApplication` + `BreadcrumbList`.

---

## 4. Findings, par sévérité puis effort

### F1 - Le permis de conduire québécois n'est pas détecté (sévérité : haute, `niveau_preuve : reproduit`)

`public/assets/tools/anonymiseur/anonymizer-core.js:133`

```js
const permit = /(?:permis|oiiq|matricule|n[°o]\s*de\s*permis)[^\d]{0,15}(\d{5,})/gi;
```

La regex ne capture que des suites de **chiffres**. Or le permis de la SAAQ est **une lettre suivie de 12 chiffres** (`T1234-567890-12`). Test isolé, reproductible :

| Cas | Résultat |
|---|---|
| `Mon permis est T1234-567890-12.` | **NON DÉTECTÉ** |
| `Mon permis est 123456789.` | détecté |

Confirmé de bout en bout : dans un parcours réel, le numéro est resté **en clair** dans le texte anonymisé.

**Correction proposée** : accepter une lettre initiale et les tirets, par exemple `([A-Za-z]?[\d-]{7,})` en remplacement de `(\d{5,})`, en gardant le garde-fou de contexte. Rayon **local** - auto-fixable, avec test.

### F2 - L'adresse IP est reconnue par la regex mais reste en clair dans la sortie (sévérité : haute, `niveau_preuve : reproduit`)

`anonymizer-core.js:189-192`

Le détecteur IPv4 existe et fonctionne : testé isolément, il reconnaît `24.201.88.143` comme `192.168.14.207`. Pourtant, dans le parcours réel, l'IP publique est **restée intacte** dans le texte anonymisé.

Le défaut n'est donc pas la détection mais un maillon en aval (seuil de confiance à 0.85, déduplication, ou chevauchement avec un autre détecteur). C'est plus préoccupant qu'une regex trop étroite : la donnée est vue puis perdue.

Une IP publique est une donnée personnelle au sens du RGPD (CJUE, arrêt *Breyer*, 2016) et relève de la Loi 25.

**À investiguer avant correctif** - la cause exacte n'est pas établie, donc aucun correctif n'est proposé à l'aveugle. Rayon **local**.

### F3 - Les comptes bancaires canadiens ne sont pas couverts (sévérité : moyenne, `niveau_preuve : reproduit`)

`anonymizer-core.js:186-188` - seul l'IBAN est détecté, format **européen** (`FR76…`). Le format canadien (transit - institution - compte, ex. `815-30456-7`) n'a aucun détecteur. Vérifié : `Compte 815-30456-7` → non détecté, et resté en clair dans la sortie réelle.

**Correction proposée** : ajouter un détecteur contextuel (« compte », « transit », « folio ») plutôt qu'un motif numérique nu, pour éviter les faux positifs. Rayon **local**.

### F4 - Faux positifs de détection qui dégradent le texte (sévérité : moyenne, `niveau_preuve : reproduit`)

Dans le parcours réel, le texte de sortie contenait :

- `Permis T1234-567890-12` → **`Sophie T1234-567890-12`** : le mot « Permis » a été pris pour un prénom et remplacé, pendant que le numéro, lui, restait en clair. Le pire des deux mondes.
- `Compte Desjardins 815-30456-7` → **`Jean Bouchard 815-30456-7`** : l'institution a été prise pour une personne.

Le remplacement altère le sens du texte sans rien protéger. « Desjardins » est un cas légitimement ambigu (patronyme courant au Québec), « Permis » ne l'est pas.

**Correction proposée** : exclure du dictionnaire de prénoms/noms les mots qui servent d'amorce contextuelle aux autres détecteurs (permis, matricule, compte, dossier, folio, transit). Rayon **local**.

### F5 - La CSP en production ne protège pas contre le XSS (sévérité : moyenne, `niveau_preuve : reproduit`, **needs-review**)

En-tête réel mesuré au curl :

```
content-security-policy: frame-src 'self' https://screenpal.com … https://www.google.com
```

**Une seule directive.** Ni `default-src`, ni `script-src`, ni `object-src`, ni `base-uri`, ni `form-action`. Les scripts ne sont donc pas restreints en production.

Deux middlewares PHP posent pourtant un `script-src` strict avec nonce (`Modules/Core/app/Http/Middleware/ContentSecurityPolicy.php:28`), mais leur en-tête est écrasé par une source de plus haute priorité, vraisemblablement une règle Cloudflare.

**Ce constat est déjà documenté dans la mémoire du projet depuis le 21 juin 2026** (`csp-prod-effective-2026-06-21`). Il est donc corroboré par deux mesures indépendantes à deux mois d'écart, et toujours ouvert.

Ce n'est pas une faille exploitable en soi - l'échappement du code tient, F0 le prouve - mais c'est une défense en profondeur absente sur un outil qui traite des données sensibles.

**Rayon élevé** (règle Cloudflare, action externe) : **aucun auto-fix**, approbation explicite requise.

### F6 - Couverture de tests faible sur le coeur de l'outil (sévérité : moyenne, `niveau_preuve : corroboré`)

3 tests Pest et 3 fichiers de tests JS (tous verts) pour 1 897 lignes de JavaScript. Les tests JS couvrent la détection, les classes de noms et la bulle de sélection - mais aucun ne vérifie le **résultat de bout en bout** : « telle donnée entre, telle donnée ne doit plus sortir ».

C'est précisément le type de test qui aurait attrapé F1, F2 et F3 avant moi.

**Correction proposée** : un test de table (données en entrée → doit être masqué oui/non) couvrant les treize types de données personnelles québécoises. Chaque finding ci-dessus devient alors un actif de non-régression permanent.

### F7 - Télémétrie Cloudflare envoyée malgré un refus de tous les témoins (sévérité : basse, `niveau_preuve : reproduit`)

Après avoir cliqué « Tout refuser », un POST part vers `/cdn-cgi/rum` avec un `pageloadId` et un `siteToken`. Le corps ne contient que des métriques de performance - aucune donnée personnelle, vérifié - et l'identifiant paraît lié au chargement de page, non persistant.

La mesure d'audience strictement nécessaire peut être exemptée de consentement, mais le point mérite une décision explicite plutôt qu'un statu quo par défaut. **Rayon élevé** (Cloudflare) : needs-review.

### F8 - Contraste insuffisant sur le bouton de menu mobile (sévérité : basse, `niveau_preuve : corroboré`)

`rgba(0,0,0,0.55)` sur `rgb(6,78,90)` = **2,25:1**, sous le seuil de 3:1 applicable à un composant d'interface (WCAG 1.4.11). Concerne le thème global, pas l'anonymiseur en propre.

### F9 - Courriels générés avec des accents (sévérité : basse, `niveau_preuve : reproduit`)

La sortie réelle contenait `pierre.bélanger@example.com`. Un accent dans la partie locale d'une adresse n'est pas valide en pratique. Cosmétique, mais visible dans le texte remis à l'IA.

### F10 - La bannière de témoins bloque l'accès à l'outil (sévérité : basse, UX)

Au premier chargement, la modale de consentement intercepte les clics : impossible d'utiliser l'outil sans la traiter d'abord. Comportement légitime et conforme, signalé pour mémoire du parcours réel.

### F11 - Aucune option « ne rien conserver » (sévérité : moyenne, `niveau_preuve : reproduit`)

`anonymizer-ui.js:56` et `:65` - la sauvegarde du texte source et de la table de correspondance dans le navigateur est **inconditionnelle** : aucune condition ne l'entoure, et aucune option de l'interface ne permet de la désactiver.

L'utilisateur peut effacer **après coup** (bouton « Oublier mes données », auto-purge à 7 jours), mais il ne peut pas choisir de **ne rien laisser** derrière lui. Sur un poste partagé, une bibliothèque ou un ordinateur d'entreprise, la fenêtre d'exposition va de la saisie jusqu'à l'effacement manuel.

Le RGPD (article 25) et l'esprit de la Loi 25 demandent la protection **par défaut**, pas seulement la protection **possible**. Ce constat vient d'une passe adversariale, et il est fondé.

**Correction proposée** : une case « ne rien conserver dans ce navigateur » (session éphémère), ou un choix au premier usage. Rayon **local**.

*Note* : ce finding nuance le jugement porté plus haut sur le stockage local, il ne l'annule pas. Le mécanisme reste averti, borné et réversible ; ce qui manque, c'est le choix en amont.

---

## 4 bis. Deux risques testés, écartés par la mesure

Une passe adversariale indépendante a reproché à cet audit trois angles morts. Deux ont été testés ensuite, et écartés :

**ReDoS (déni de service par expression régulière).** Les **82 expressions régulières** du fichier de détection ont été extraites et lancées contre 7 entrées pathologiques (3 000 espaces, 5 000 chiffres, 4 000 tirets, 3 000 points, etc.). Pire cas mesuré : **40 ms**. Aucun blocage possible du navigateur par une saisie malveillante.

**Canaux d'exfiltration hors HTTP** (WebSocket, WebRTC, `sendBeacon`, service worker, `postMessage`) : aucun n'existe dans le code, voir plus haut.

Le troisième reproche - l'absence d'option de non-conservation - s'est révélé fondé et devient F11.

---

## 5. Faux positifs écartés (et pourquoi)

Un audit qui recopie la sortie d'un scanner n'est pas un audit. Sur les 6 critères WCAG signalés « non conformes », l'essentiel est du bruit, vérifié dans la page réelle :

| Alerte du scanner | Vérification | Verdict |
|---|---|---|
| `h2` blanc sur blanc (1:1) | fond réel = dégradé sombre `rgb(26,29,35)` | **faux positif** (~16:1) |
| Fil d'Ariane blanc sur blanc | couleur réelle `rgb(35,47,75)` sur blanc | **faux positif** (~12:1) |
| Lien d'évitement 1×1 px | passe à **233×51** au focus, blanc sur `#1A1A1A` | **faux positif** (technique standard) |
| « Aucun lien d'évitement en premier tab » | il **est** le premier élément tabulable | **faux positif** (le scanner se contredit) |
| Champ `hp_url` : taille, nom, `required` manquants | piège à robots volontaire (`aria-hidden`, `tabindex="-1"`) | **faux positif** - le rendre accessible casserait la protection |
| ~60 « éléments non atteignables au clavier » | 206 des 300 éléments interactifs sont masqués (menus et modales fermés) | **faux positifs** |

---

## 6. Needs-review (validation humaine obligatoire)

- **F5** - CSP de production : action Cloudflare, rayon élevé. Ne rien changer sans décision.
- **F7** - Télémétrie après refus : arbitrage de conformité, pas une correction technique.
- **9 CVE sur 3 paquets** (`guzzlehttp/guzzle` ×2, `league/commonmark` ×6, `paragonie/sodium_compat` ×1). **Aucune ne concerne l'anonymiseur**, qui n'a aucune dépendance : elles touchent d'autres parties du site. Mise à jour de dépendances = rayon élevé, jamais d'auto-fix.

---

## 7. Preuve de nettoyage

- Aucun cron temporaire créé (aucun cron lié à l'outil : `schedule:list` → 0).
- Aucun script déposé sur le serveur : l'audit est resté en **lecture seule** sur la production.
- `browser_close` exécuté en fin de session Playwright.
- Aucun fichier temporaire laissé dans le dépôt.

---

## 8. Ce que je n'ai pas pu établir

- **La cause exacte de F2** (IP détectée mais non masquée). Il faudrait instrumenter le pipeline entre détection et remplacement. J'ai préféré le dire plutôt que de proposer un correctif à l'aveugle.
- **La duplication interne du code** (couverture 40 %). La structure et l'absence de dépendances sont vérifiées ; une revue ligne à ligne des 1 897 lignes ne l'est pas.
- **L'accessibilité de l'état APRÈS anonymisation** : le scanner et le tri manuel ont porté sur la page au chargement. Les annonces aux lecteurs d'écran au moment où le texte change (nombre d'entités détectées, résultat du masquage) n'ont pas été vérifiées.
- **Les charges utiles d'injection exotiques** (SVG, attributs `data-*`, gabarits) n'ont pas été testées une à une. Le pipeline échappant le HTML dans son ensemble et le traitant comme du texte, la classe entière paraît fermée, mais la preuve porte sur deux charges utiles, pas sur toutes.
