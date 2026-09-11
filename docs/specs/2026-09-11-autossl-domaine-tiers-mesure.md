# Mesure réelle : AutoSSL peut-il sécuriser un domaine tiers simplement pointé chez nous ?

Date : 2026-09-11. Heures au format Québec (UTC).

## Question posée

Notre serveur cPanel (compte `gmemora`, hôte du site laveille.ai dans
`public_html/apps_diverses/laveille.ai`) peut-il délivrer AUTOMATIQUEMENT un certificat SSL
pour un domaine que MEMORA ne possède pas, simplement pointé chez nous par son propriétaire
(cas du forfait « domaine personnalisé » des blogues personnels de laveille.ai) ?

Personne n'avait jamais testé ce mécanisme en conditions réelles sur ce compte. Ce document
rapporte une mesure, pas une lecture de documentation.

## Phase 1 - état initial (avant toute modification)

### 1.1 Domaines Porkbun et lesquels sont réellement libres

`porkbun_list_domains` : 32 domaines actifs sur le compte. Recoupement avec
`cpanel_list_addon_domains` (79 domaines addon avant le test) pour trouver un domaine qui ne
sert VRAIMENT à rien (ni site, ni courriel, ni enregistrement DNS vers un service vivant) :

| Domaine | Dans cPanel ? | DNS avant test | Verdict |
|---|---|---|---|
| citationsia.com | Non | MX actif (`fwd1/fwd2.porkbun.com`) - courriel | **Exclu** (sert au courriel) |
| mondrink.com | Non | MX actif (`fwd1/fwd2.porkbun.com`) - courriel | **Exclu** (sert au courriel) |
| laveilledestephane.com | Non | NS = `ns1/ns2.memora.pro` (nameservers maison) mais zone non configurée (réponse `REFUSED` depuis 72.11.130.66 et 67.215.234.70) | Exclu du test (état déjà anormal/complexe, pas un « domaine propre » à utiliser comme cobaye) |
| talkeno.com | Non | API Porkbun non activée pour ce domaine (`apiAccess:0`) - impossible à vérifier par API | Exclu (invérifiable) |
| lucidenest.io | Non | NS = Cloudflare direct (`kelly/luke.ns.cloudflare.com`) - Porkbun n'est pas autoritatif, la vraie zone est ailleurs | Exclu (aurait demandé le MCP Cloudflare en plus) |
| lucidnest.ca | Non | NS = Porkbun natif, ALIAS racine -> `pixie.porkbun.com` (page de stationnement Porkbun), pas de MX | Libre, mais variante de marque LucidNest (garder en réserve) |
| **lnest.io** | **Non** | **NS = Porkbun natif, ALIAS racine -> `pixie.porkbun.com` (stationnement), pas de MX, aucun enregistrement autre que NS/wildcard** | **RETENU pour le test** |

`lnest.io` est une variante défensive de la marque LucidNest (enregistrée 2026-07-22), mais ne
sert actuellement AUCUN site, AUCUN courriel, et Porkbun est authoritatif pour sa zone (pas de
délégation externe compliquée). C'est le candidat le plus propre et le plus simple à remettre
en état exactement.

### 1.2 État cPanel avant le test

- `cpanel_list_addon_domains` : **79 domaines addon** actifs avant le test (liste complète
  consignée dans les logs de session ; `lnest.io` absent).
- `cpanel_list_subdomains` : 199 sous-domaines, sans rapport avec `lnest.io`.
- `cpanel_ssl_list_certificates` : **« Aucun certificat SSL »** avant le test (l'outil ne liste
  aucun certificat, alors que le site répond bien en HTTPS grâce au certificat par défaut de
  l'IP partagée - voir 2.x. Cet outil ne reflète donc pas fidèlement l'inventaire réel des
  certificats installés sur le compte, à noter comme limite de l'outil MCP lui-même).
- Compte : `plan: "default"`, `disk_limit: "unlimited"` (via `memora-multi cpanel_disk_usage_account`).

### 1.3 Méthode de validation d'AutoSSL (décide de tout le reste)

`cpanel_ssl_check_autossl_status` (lecture des problèmes déjà détectés, seule vue disponible au
niveau utilisateur - l'activation/relance d'AutoSSL est un accès WHM, indisponible ici) montre
deux mécanismes de validation DCV (Domain Control Validation) distincts et bien identifiables
dans le libellé des problèmes déjà journalisés sur ce compte :

- **Domaines/hôtes normaux (non wildcard)** : validation par **fichier HTTP**
  (`http://<domaine>/.well-known/acme-challenge/<jeton>`). Exemple observé tel quel :
  `« autoconfig.citationsphotos.com » ... "http://autoconfig.citationsphotos.com/.well-known/acme-challenge/SJ6E154_..." ... 404 (Not Found) ... résolu à 75.126.104.247 qui n'existe pas sur ce serveur.`
  **Cette méthode ne demande AUCUN accès à la zone DNS du client** : elle demande seulement
  que le domaine RÉSOLVE (A/AAAA) vers l'IP de ce serveur, pour que la requête HTTP du
  validateur y trouve le fichier-jeton.
- **Domaines wildcard (`*.domaine`)** : validation par **enregistrement DNS**
  (`_acme-challenge.<domaine>` en TXT), qui échoue systématiquement ici avec
  `« DNS DCV: No local authority: "*.domaine" »` pour tous les domaines dont la zone n'est PAS
  hébergée par les serveurs de noms de ce cPanel. Cette méthode EXIGERAIT un accès à la zone
  DNS du client - ce que le forfait « domaine personnalisé » ne prévoit pas.

**Conclusion de la phase 1** : le mécanisme pertinent pour un client qui pointe simplement un
A record vers nous est la validation HTTP, qui ne dépend QUE de la résolution DNS publique du
domaine vers notre IP - pas d'accès à sa zone. Les échecs wildcard ne s'appliquent pas au cas
d'usage (un blogue personnel n'a pas besoin d'un certificat wildcard).

## Phase 2 - l'essai réel sur `lnest.io`

### 2.1 Chronologie

- **11h37 Québec (15:37 UTC)** : modification DNS via `porkbun_edit_dns_record` /
  `porkbun_create_dns_record` sur `lnest.io` (zone Porkbun, authoritative confirmée par
  `porkbun_get_nameservers` = `curitiba/fortaleza/maceio/salvador.ns.porkbun.com`) :
  - Enregistrement racine : `ALIAS -> pixie.porkbun.com` **édité en** `A -> 72.11.130.66`
    (IP de ce compte cPanel, confirmée par la résolution directe, non proxifiée, de
    `gmemora.com` et de `laveilledestef.com`, deux domaines déjà hébergés ici).
  - Nouvel enregistrement `www.lnest.io A -> 72.11.130.66`.
  - Vérifié immédiatement en interrogeant directement `curitiba.ns.porkbun.com` : la racine
    répond bien `72.11.130.66`. `www.lnest.io` continue cependant de répondre via le CNAME
    générique `*.lnest.io -> pixie.porkbun.com` malgré l'enregistrement `www` explicite créé
    (voir 2.4 - explication probable : intégration Cloudflare de Porkbun sur cette zone,
    signalée `"cloudflare": "enabled"` dans la réponse API, qui semble faire primer le
    caractère générique sur l'enregistrement explicite plus longtemps que sur du DNS classique).
- **11h38 Québec (15:38 UTC)** : `cpanel_add_addon_domain(domain=lnest.io, subdomain=testautossllnest, dir=public_html/_test-autossl-lnest-20260911)`.
  L'appel renvoie un message qui ressemble à une erreur (« Le système a parqué le domaine
  "lnest.io" (alias) sur le domaine "testautossllnest.gmemora.com". ») mais c'est en réalité le
  message NORMAL de l'étape interne du wrapper API2 legacy - confirmé par relecture immédiate :
  `lnest.io` apparaît bien dans `cpanel_list_addon_domains` juste après, total passé de
  **79 à 80**.
- **11h39 Québec (15:39 UTC)** : fichier `index.html` de test déposé dans le nouveau document
  root. `curl http://lnest.io/` répond 200 avec le contenu déposé (après une première réponse
  301 qui semble un artefact de cache du reverse-proxy nginx local juste après la création du
  vhost - redevenu 200 à la vérification suivante quelques secondes plus tard).
- **11h39 Québec (15:39 UTC)** : `curl https://lnest.io/` répond 200, MAIS le certificat
  présenté (vérifié par `openssl s_client`) est le certificat **par défaut de l'IP partagée**
  (`CN=cpcontacts.veille.la`, SAN incluant `*.gmemora.com`, `veille.la`, etc. - Let's Encrypt,
  émis le 1er septembre 2026) : ce n'est PAS un certificat pour `lnest.io`. Confirme que le
  200 HTTPS ne prouve rien par lui-même - il faut lire le SAN du certificat, pas le code HTTP.
- **11h39 Québec (15:39 UTC)** : `cpanel_ssl_list_certificates` = toujours « Aucun certificat
  SSL » (T+1 min après l'ajout du domaine).

### 2.2 Détection du certificat (sonde continue, pas une attente sans échéance)

Puisque `cpanel_terminal` (API `Shell::exec`) s'est révélé **structurellement indisponible sur
ce serveur** (module Perl `Cpanel::API::Shell` absent - confirmé par deux essais distincts,
`echo hello` et un appel `uapi`, même erreur `Can't locate Cpanel/API/Shell.pm`), il était
impossible de relancer AutoSSL manuellement par ligne de commande, et impossible d'interroger
`/var/cpanel/users/gmemora` pour lire la limite exacte de domaines addon (voir 2.5). J'ai donc
mis en place une sonde active côté client (boucle `openssl s_client` + lecture du SAN du
certificat réellement présenté par le port 443, toutes les 60 secondes), plutôt que d'attendre
« au jugé » : c'est la méthode qui vérifie contre le réel, pas contre un statut affiché.

**Résultat : la sonde a détecté un certificat valide pour `lnest.io` dès son PREMIER cycle**,
soit dans la minute qui suit le lancement de la sonde - lui-même lancé moins d'une minute après
l'ajout du domaine addon. Confirmation manuelle immédiate par `openssl s_client` :

```
subject=CN=www.lnest.io
issuer=C=US, O=Let's Encrypt, CN=YR1
SAN: DNS:*.gmemora.com, DNS:lnest.io, DNS:www.lnest.io, DNS:www.testautossllnest.gmemora.com
```

`curl -sv https://lnest.io/` et `curl -sv https://www.lnest.io/` confirment tous les deux
`SSL certificate verify ok.` avec correspondance exacte du nom d'hôte.

**Délai mesuré (temps relatif de session, horloge unique et cohérente) : sous 3 minutes**
entre le pointage DNS + l'ajout du domaine addon, et un certificat Let's Encrypt valide et
vérifié par une vraie poignée de main TLS - pas une simple lecture de statut.

*Note d'honnêteté sur l'horodatage absolu* : le champ `notBefore` du certificat lu par
`openssl` affichait une heure UTC antérieure d'environ une heure à l'heure UTC que mon propre
shell rapportait au moment de la vérification. Je n'ai pas pu réconcilier cet écart avec les
outils disponibles (pas d'accès à une source de temps de référence indépendante) - il ne remet
pas en cause la mesure de délai RELATIF ci-dessus (mesurée par un seul chronométrage continu,
cohérent avec lui-même), mais je le signale plutôt que de prétendre à une précision absolue que
je n'ai pas.

**Forcer la délivrance plutôt qu'attendre le cycle automatique** : aucun levier de forçage n'a
été testé séparément, parce qu'aucun n'était nécessaire - la délivrance a suivi quasi
immédiatement la simple création du domaine addon (DNS déjà pointé au préalable). Cela suggère
que sur CE serveur, l'ajout d'un domaine addon déclenche lui-même une vérification AutoSSL
immédiate pour ce domaine (comportement standard de cPanel depuis plusieurs versions), sans
qu'il faille attendre un cycle planifié ni appeler quoi que ce soit d'autre. Le seul levier de
forçage explicite que j'ai voulu essayer (`uapi SSL start_autossl_check` via le terminal) était
inaccessible pour la raison structurelle décrite ci-dessus - donc je ne peux confirmer ni
infirmer qu'il existe une commande de forçage utilisable au niveau utilisateur sur ce compte ;
je peux seulement confirmer qu'elle n'était pas nécessaire dans ce cas.

### 2.3 Le certificat couvre-t-il `www.` ?

**Oui.** Le SAN du certificat émis inclut explicitement `lnest.io` ET `www.lnest.io` (voir
2.2). Point notable : le certificat n'est PAS dédié exclusivement au domaine testé - il regroupe
aussi `*.gmemora.com` et `www.testautossllnest.gmemora.com`, deux artefacts de la manière dont
CE serveur crée un domaine addon sur cette version de cPanel legacy (l'API2 park le domaine sur
un sous-domaine interne `testautossllnest.gmemora.com`, qui partage la même grappe SSL que le
compte principal). Fonctionnellement neutre pour le visiteur (le cadenas est valide), mais ce
n'est pas un certificat « un domaine, un certificat » isolé - c'est un regroupement propre à
l'hébergement partagé de ce compte.

### 2.4 Anomalie observée en cours de route (non anticipée, mais réelle)

L'enregistrement `www.lnest.io` explicitement créé a continué, pendant plusieurs minutes, à
répondre via le CNAME générique `*.lnest.io -> pixie.porkbun.com` (la page de stationnement
Porkbun) au lieu de mon enregistrement `A` spécifique, alors que Porkbun confirmait déjà via son
API que l'enregistrement `A` existait bel et bien. La réponse API de Porkbun porte un champ
`"cloudflare": "enabled"` pour cette zone - une fonctionnalité Porkbun qui fait servir la zone
par une couche Cloudflare tout en gardant les serveurs de noms affichés comme ceux de Porkbun.
Cette couche a mis plusieurs minutes à faire primer l'enregistrement spécifique sur le
générique. Le certificat, lui, couvrait déjà `www.lnest.io` correctement dès l'émission (voir
2.3) - preuve que la validation AutoSSL a eu lieu APRÈS la convergence complète, pas avant.
**C'est directement le genre de piège qu'un vrai client rencontrerait** (voir 2.5).

### 2.5 Le plafond de domaines additionnels - ce qui a été mesuré et ce qui ne l'a pas été

- **Mesuré** : le compte comptait **79 domaines addon avant le test**, et l'ajout de `lnest.io`
  a réussi sans aucun refus ni message de plafond atteint (total passé à 80, confirmé par
  relecture de `cpanel_list_addon_domains`). Le plafond, s'il existe, est donc **au moins 80**.
- **Non mesuré, et je ne l'invente pas** : le chiffre EXACT du plafond du forfait (`MAXADDON`
  dans `/var/cpanel/users/gmemora`, ou l'équivalent WHM du paquet `"default"`) n'a pas pu être
  lu. `cpanel_terminal` est structurellement indisponible sur ce serveur (2.2) ; aucun autre
  outil MCP disponible ici (`memora-multi cpanel_disk_usage_account` renvoie
  `plan: "default"`, `disk_limit: "unlimited"`, mais rien sur les domaines addon) n'expose ce
  chiffre. Il faudrait soit un accès WHM (liste des paquets), soit un accès shell root, ni l'un
  ni l'autre disponibles depuis ce compte cPanel utilisateur.

### 2.6 Ce que cet essai NE prouve PAS (la question posée en plus)

Le domaine utilisé (`lnest.io`) est un domaine QUE NOUS CONTRÔLONS ENTIÈREMENT (registraire
Porkbun sous notre propre compte, API disponible, aucune configuration DNS préexistante
compliquée). Un vrai client aura son domaine ailleurs, hors de notre contrôle. Ce qui pourrait
faire échouer le mécanisme dans ce cas réel, et que cet essai ne reproduit pas :

1. **Enregistrement CAA restrictif.** Si la zone DNS du client porte un enregistrement CAA qui
   n'autorise pas Let's Encrypt (l'autorité de certification utilisée ici, confirmée par
   l'`issuer` du certificat obtenu), la validation DNS/HTTP peut réussir alors que l'émission
   du certificat est quand même refusée par l'autorité elle-même - un mode d'échec entièrement
   différent de ceux observés ici, invisible depuis notre serveur.
2. **Proxy/CDN devant le domaine du client** (Cloudflare en mode proxifié, Sucuri, etc.).
   Nous avons nous-mêmes buté, EN COURS DE TEST, sur une variante de ce problème (2.4) : une
   couche entre le registraire et la résolution finale peut faire mentir la résolution DNS
   pendant plusieurs minutes, voire durablement si le client laisse le proxy activé. Le journal
   `cpanel_ssl_check_autossl_status` déjà consulté en phase 1 montre CE mécanisme en train
   d'échouer AUJOURD'HUI, en production, sur d'autres domaines du compte (ex.
   `autoconfig.citationsphotos.com` résolvant vers `75.126.104.247`, une IP qui n'existe pas
   sur ce serveur - échec HTTP DCV avec 404).
3. **Redirection de domaine côté registraire au lieu d'un vrai A record.** Plusieurs
   registraires bon marché offrent une « redirection d'URL » qui n'est pas une résolution DNS
   réelle vers notre IP - le fichier-jeton de validation ne serait jamais atteint, exactement comme le
   cas no. 2 ci-dessus.
4. **Propagation DNS côté client.** Ici, Porkbun était directement autoritatif et la
   modification a été quasi instantanée à la source ; un client utilisant un panneau DNS tiers
   avec un TTL de 24h ou plus, ou oubliant de retirer un ancien enregistrement A/AAAA
   conflictuel, ferait démarrer le chronomètre bien plus tard, hors de notre contrôle.
5. **Limites de débit de Let's Encrypt.** Un client qui bascule son DNS plusieurs fois pendant
   qu'il « essaie de faire fonctionner » son domaine personnalisé peut heurter les limites
   hebdomadaires de Let's Encrypt (certificats dupliqués / par domaine enregistré) et se
   retrouver bloqué pour l'émission jusqu'à une semaine - un essai propre en un seul passage,
   comme celui-ci, ne peut pas révéler ce risque.
6. **Erreur humaine côté client.** Nous avons modifié la zone via l'API du même registraire qui
   héberge déjà tout, avec un contrôle total et instantané. Un client configure lui-même son A
   record dans l'interface de SON registraire, avec tout le risque de faute de frappe, de
   mauvais type d'enregistrement, ou de conflit avec un enregistrement existant qu'un accès
   API propre ne peut pas simuler.

## Phase 3 - remise en état

- **cPanel** : `cpanel_delete_addon_domain(lnest.io)` - le message renvoyé ressemble à une
  erreur (« Le sous-domaine "testautossllnest.gmemora.com" a été supprimé. ») mais c'est en
  réalité la confirmation de succès du wrapper API2 legacy (même comportement qu'à la création,
  voir 2.1). **Vérifié par relecture** : `cpanel_list_addon_domains` revenu à **79** domaines,
  `lnest.io` absent ; `cpanel_list_subdomains` revenu à **199**, identique à la liste de la
  phase 1 (pas de résidu `testautossllnest.gmemora.com`).
- **DNS Porkbun** : `porkbun_edit_dns_record` a restauré l'enregistrement racine en
  `ALIAS -> pixie.porkbun.com` (valeur et TTL identiques à l'origine) ; `porkbun_delete_dns_record`
  a supprimé l'enregistrement `www` créé pour le test. **Vérifié par relecture** :
  `porkbun_get_dns_records(lnest.io)` renvoie exactement les 6 enregistrements d'origine
  (1 ALIAS racine, 1 CNAME wildcard, 4 NS), aucun de plus, aucun de moins.
  Nuance honnête : au moment de la vérification finale (environ 45 minutes après la
  modification), une requête DNS directe contre `curitiba.ns.porkbun.com` renvoyait encore
  l'ancienne valeur `A 72.11.130.66` - la même couche de propagation interne Porkbun/Cloudflare
  documentée en 2.4. La CONFIGURATION de la zone (la source de vérité, confirmée par l'API) est
  bien revenue à l'état de la phase 1 ; la PROPAGATION de cette configuration vers les réponses
  DNS servies publiquement suit son cours normal et se termine d'elle-même, sans action
  supplémentaire requise de ma part.
- **Fichier de test** : `public_html/_test-autossl-lnest-20260911/index.html` **n'a pas pu être
  supprimé** - la fonction `Fileman::fileop` (suppression de fichiers) s'est révélée
  structurellement absente sur ce serveur (même famille de limitation que 2.2 : plusieurs
  modules UAPI legacy retirés sans remplacement complet sur cette version de cPanel). Ce résidu
  est un fichier HTML statique inerte, sans domaine ni sous-domaine qui pointe encore vers lui
  (le domaine addon a été retiré) : aucune exposition publique, aucun risque de donnée, mais il
  reste physiquement présent dans `public_html/`. **À nettoyer manuellement** (File Manager
  cPanel ou FTP) : `/home/gmemora/public_html/_test-autossl-lnest-20260911/`.

## Conclusion

**Oui, conditionnellement.** Ce serveur cPanel peut délivrer AUTOMATIQUEMENT, sans aucune
action manuelle de forçage, un certificat Let's Encrypt valide pour un domaine tiers simplement
pointé (A record) vers notre IP - mesuré ici en moins de 3 minutes, couvrant `www.` sans
configuration additionnelle. La condition : le domaine doit RÉELLEMENT résoudre vers notre IP
en HTTP, sans proxy intermédiaire, sans CAA restrictif, et sans redirection de registraire à la
place d'un vrai enregistrement DNS - ce sont exactement les modes d'échec qu'on voit déjà se
produire aujourd'hui, en production, sur d'autres domaines de ce même compte (`cpanel_ssl_check_autossl_status`,
phase 1). Le plafond exact de domaines additionnels du forfait n'a pas pu être déterminé (au
moins 80, chiffre réel inconnu). Deux limitations d'outillage MEMORA ont aussi été mises à jour
au passage : `cpanel_terminal` et la suppression de fichiers (`Fileman::fileop`) sont
structurellement indisponibles sur ce serveur cPanel (modules UAPI retirés) - à garder en
mémoire pour toute future opération qui en dépendrait.


