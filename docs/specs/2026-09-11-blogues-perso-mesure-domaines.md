# Blogues perso : ce qui est MESURÉ sur les domaines personnalisés

Date : 2026-09-11. Ticket #2441. Sources nommées, aucune attribution anonyme.
Méthode : API Porkbun interrogée directement (prix), puis 6 recherches Perplexity
(`pp_search_many`) pour tout ce que l'API ne dit pas.

## 1. Oui, Porkbun permet d'ACHETER un domaine par programme

Source : documentation officielle Porkbun API v3, relevée par Perplexity.

- Point d'entrée : `POST /domain/create/{domain}` sur `https://api.porkbun.com/api/json/v3`
- `cost` en CENTS américains, et il doit correspondre EXACTEMENT au prix courant
- `agreeToTerms` doit valoir `yes`

**Les cinq conditions qui changent la conception**, et qu'aucune intuition n'aurait données :

1. L'achat débite un **crédit prépayé** du compte Porkbun. Il faut donc provisionner du
   solde d'avance : on ne facture pas « à la demande » sans réserve.
2. Courriel ET téléphone du compte doivent être vérifiés.
3. **Le compte doit avoir DÉJÀ enregistré au moins un domaine** : le tout premier achat
   peut devoir passer par l'interface web. Un mécanisme automatisé ne démarre donc pas à froid.
4. Les domaines **premium sont exclus** de ce point d'entrée. Il faut prévoir ce que
   l'interface répond quand le nom choisi est premium.
5. La création se fait à la **durée minimale du registre**, en général un an. Ce n'est pas
   un point d'entrée fait pour vendre trois ans d'un coup.

**Ce n'est PAS une API de revendeur ICANN** : les domaines restent enregistrés chez Porkbun
comme registraire officiel.

## 2. Les prix, et la correction de ma propre lecture

J'avais relevé les prix directement par `porkbun_get_pricing` (907 extensions au catalogue).
La recherche a corrigé DEUX choses que l'API seule ne disait pas :

| Extension | Inscription | Renouvellement | Transfert | Ce que l'API ne disait pas |
|---|---|---|---|---|
| `.com` | 11,08 | 11,08 | 11,08 | **Hausse annoncée vers ~11,81 au 1er novembre 2026** (+6,6 %) |
| `.ca` | 8,91 | 9,18 | 8,98 | **8,91 est un prix PROMOTIONNEL** ; le tarif régulier affiché est 11,12 / 11,39 / 11,19 |
| `.ai` | 82,70 | 82,70 | 165,09 | Le transfert reflète un minimum de DEUX ans, pas un an |
| `.blog` | 2,57 | 21,11 | 21,11 | Piège d'appel : le renouvellement coûte 8 fois la première année |
| `.io` | 28,12 | 51,80 | 51,80 | Même piège, facteur 1,8 |

Montants en dollars américains. **La réponse de l'API ne déclare AUCUNE devise** : c'est la
page produit de Porkbun qui l'établit, pas l'appel.

**Conséquence directe sur la marge** : le coût récurrent qui compte n'est jamais le prix
d'appel. Pour `.ca` c'est 9,18 et non 8,91 ; pour `.com` ce sera bientôt 11,81 et non 11,08.
Une grille de forfait bâtie sur les prix d'inscription se retrouverait à perte à la
deuxième année.

**Il n'existe AUCUN programme de gros chez Porkbun** : pas de grille de volume publique,
pas de sous-comptes clients isolés. On paie les prix de notre compte, et on construit
soi-même la facturation, le support et la marge.

## 3. Le vrai goulot : servir le domaine, pas l'acheter

Source : documentation cPanel officielle.

- Le terme moderne est **Domains** : « addon domain » devient un domaine avec sa propre
  racine de documents ; « parked domain » devient **alias**.
- Pour un blogue client autonome, c'est un **domaine additionnel** avec racine distincte
  (décocher « partager la racine »), pas un alias.
- **AutoSSL peut délivrer le certificat si la validation de contrôle du domaine réussit**,
  ce qui dépend de la résolution effective du nom vers notre serveur.

**Réserve majeure, à ne pas franchir sans mesure** : « la documentation dit que ça peut »
n'est pas « ça marche sur NOTRE serveur ». Cette vérification est sortie en ticket distinct.

## 4. Comment les autres plateformes font, réellement

Source : documentations officielles Ghost, Substack, Bear Blog, Micro.blog.

Aucune n'installe un site par client. Toutes font du **routage par nom d'hôte** derrière une
infrastructure mutualisée : le proxy reçoit `Host: blogue-du-client.com`, choisit le bon
locataire, sert son contenu, et un contrôleur de certificats valide le domaine.

**La difficulté centrale, identique partout** : un `CNAME` convient à un sous-domaine
(`blog.client.com`), mais le domaine racine (`client.com`) ne peut pas recevoir un CNAME
standard. Il faut soit un `ALIAS`/`ANAME`/aplatissement CNAME chez le fournisseur DNS, soit
un enregistrement `A` vers une adresse IP.

- Ghost(Pro) : CNAME vers `publication.ghost.io`
- Bear Blog : CNAME/ALIAS vers `domain-proxy.bearblog.dev`, repli par deux enregistrements A
- Micro.blog : CNAME vers `username.micro.blog`, ou A pour la racine

Délai réel : quelques minutes à 48 h selon la propagation DNS, puis l'émission TLS.

## 5. Le volet juridique, et il a une conclusion nette

Source : ICANN (accord d'accréditation des registraires, RDAP).

- **Aucune accréditation ICANN n'est nécessaire** pour facturer et gérer des domaines
  pour des clients. On agit comme revendeur via un registraire accrédité.
- **Mais le client, ou sa société, devrait être le TITULAIRE officiel**, et conserver un
  moyen autonome de récupérer son domaine. Acheter « pour le client » dans notre propre
  compte est le modèle à éviter, sauf portefeuille interne encadré.
- **WHOIS est en voie de remplacement par RDAP** : depuis le 28 janvier 2025, les
  registraires de domaines génériques doivent fournir RDAP. La Registration Data Policy de
  l'ICANN s'applique depuis le 21 août 2025.

## 6. Ce que Ghost et WordPress.com font payer, et à quel palier

Source : page de tarification officielle Ghost, relevée le 2026-09-11.

| Palier Ghost(Pro) | Prix | Domaine perso | Thèmes | Taille de fichier | Code et intégrations |
|---|---|---|---|---|---|
| Starter | 18 $US/mois (annuel) | **OUI** | Réglages simples seulement | **5 Mo** | Aucune API, aucun webhook |
| Publisher | 29 $US/mois (annuel) | oui | **Thèmes personnalisés** | **100 Mo** | API admin, webhooks, 8000+ intégrations |
| Business | 199 $US/mois (annuel) | oui | idem | **250 Mo** | idem, plus de capacité |

**Trois faits qui contredisent l'intuition, et qui doivent nourrir le plan :**

1. **Ghost donne le domaine personnalisé dès son palier le MOINS cher.** Le domaine n'est
   pas ce qui distingue les paliers chez eux.
2. **Ce qui distingue vraiment, c'est le THÈME personnalisé** : impossible avant 29 $US/mois.
   C'est là que Ghost place sa frontière, pas sur le domaine.
3. **Le quota n'est pas un volume total d'images : c'est une taille MAXIMALE PAR FICHIER**
   (5 / 100 / 250 Mo). Mécanique plus simple à expliquer, et impossible à contourner en
   téléversant beaucoup de petits fichiers - donc à évaluer sérieusement contre l'idée
   d'un quota cumulé.

WordPress.com : le CSS, les thèmes premium et les extensions sont accessibles bien plus tôt
dans la grille, mais l'accès `SFTP`, `SSH`, `WP-CLI` et `Git` exige le palier Business.

## Ce qui reste NON mesuré, et qui ne doit pas être supposé

1. AutoSSL sur un domaine tiers pointé vers NOTRE serveur : jamais testé ici.
2. Le compte Porkbun de MEMORA a-t-il déjà enregistré un domaine (condition 3 ci-dessus) ?
3. Le solde prépayé disponible sur ce compte.
4. Le taux de change à appliquer pour fixer un prix en dollars canadiens.
