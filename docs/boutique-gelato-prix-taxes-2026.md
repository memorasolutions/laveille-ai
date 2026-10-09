# Boutique Gelato - modèle de prix et de taxes « zéro perte » (2026-10-09)

> Document de référence pour les tâches #3005 (prix zéro-perte) et #3006 (taxes provinciales).
> Décisions du fondateur du 2026-10-09 (Q6 à Q9) + analyse quantitative Codex + recherche
> factuelle (sonar-pro, Perplexity navigateur non disponible ce jour). Boutique en accès
> fondateur seul (`SHOP_FOUNDER_ONLY`), donc aucun risque public pendant la construction.

## 1. Contrainte dure du fondateur

« Je ne dois JAMAIS perdre d'argent, incluant les frais Stripe. » Prix en CAD. Viser une bonne
marge. Ne pas oublier les frais de livraison, avec +1 $ de tampon. Taxes pour toutes les provinces,
rien hors Canada.

## 2. État mesuré du code (cartographie 2026-10-09)

- Prix affiché = `arrondi.99( coûtGelatoRéel_USD × tauxUSD_CAD × (1 + marge) )` (`Modules/Shop/app/Models/Product.php:61-72`). La « marge » est un MARKUP sur coût (30 % défaut, mugs 35 %, posters 25 %, `config.php:101-109`).
- Coût d'impression = réel (API Gelato, par couleur/taille). Livraison = vrai devis Gelato en CAD au checkout (`GelatoService.php:332-351`), + `handling_fee` 1 $ par devis (`:359,385`). `estimated_shipping_usd` / `shipping_by_category` sont des clés MORTES (lues nulle part).
- **Frais Stripe : pris en compte NULLE PART.** Stripe prélève 2,9 % + 0,30 $ CAD sur le TOTAL encaissé ; non remboursés au remboursement.
- Taxe maison (`CheckoutController.php:118-122`) : QC = TPS+TVQ ; autre province CA = TPS 5 % SEULE ; hors CA = 0. Province déduite du code postal. Taxe sur le SOUS-TOTAL seulement (ni livraison ni manutention). Le code avoue le trou « M3 » (TVH ON/Atlantique facturée 5 %).
- Aucun garde-fou de marge plancher (ni commande ni webhook). Prix figé à la synchro ; coût Gelato et taux FX bougent après. Arrondi .99 vers le bas (rogne jusqu'à ~0,95 $).
- BUG : repli FX lit `shop.usd_cad_rate` au lieu de `shop.pricing.usd_cad_rate` (`ExchangeRateService.php:31`) → `SHOP_USD_CAD_RATE` ignoré, repli toujours 1,40.
- `shipping_countries` lu NULLE PART ; le checkout accepte tout code pays 2 lettres (`CheckoutController.php:40`).

## 3. Analyse quantitative (Codex, 2026-10-09)

- **20 % de markup ne garantit pas le zéro perte.** Exemple coût 12 $, livraison 8 $, manutention 1 $, taxe 15 % : bénéfice +1,96 $ ; mais si le coût courant monte de 20 % (14,40 $), la MÊME vente perd 0,44 $.
- Frais Stripe : le fixe 0,30 $ égale les 2,9 % à 10,34 $ encaissés ; part totale 5,9 % à 10 $, 3,9 % à 30 $, 3,4 % à 60 $. **Les 2,9 % ne disparaissent jamais.**
- **Le +1 $ de manutention apporte 0,971 $ net** ; il couvre Stripe seulement si le total encaissé ≤ ~24,14 $. À 50 $ encaissés, déficit de couverture 0,75 $.
- Formule de résultat : `Résultat = 0,971 × (P + T + L + H) − 0,30 − T − C` (P sous-total produit, T taxe à remettre, L livraison, H manutention, C coût réel courant production+livraison).
- **Prix plancher** (zéro perte), taxe t proportionnelle au produit : `P_min = [C + 0,30 − 0,971 × (L + H)] / [1 − 0,029 × (1 + t)]`. Choisir le plus petit prix en .99 ≥ P_min ; ne jamais arrondir sous le plancher.
- Le contrôle doit se faire AVANT l'encaissement (le webhook est trop tard : les frais Stripe sont déjà engagés).
- **Risque irréductible : un remboursement intégral laisse toujours ~1 $ de frais Stripe perdus.** « Ne jamais perdre sur AUCUNE commande » est littéralement incompatible avec certains remboursements, quel que soit le markup.

## 4. Faits fiscaux (sonar-pro, recoupés) - 2026

| Destination (livraison) | Taux | Note |
|---|---|---|
| Alberta, Yukon, T.N.-O., Nunavut | 5 % (GST) | GST seule |
| Colombie-Britannique | 5 % GST (+7 % PST si inscrit) | voir §6 PST |
| Saskatchewan | 5 % GST (+6 % PST si inscrit) | voir §6 PST |
| Manitoba | 5 % GST (+7 % RST si inscrit) | voir §6 PST |
| Ontario | 13 % TVH | |
| Québec | 5 % TPS + 9,975 % TVQ = 14,975 % | |
| Nouveau-Brunswick | 15 % TVH | |
| Nouvelle-Écosse | **14 % TVH** | baissée de 15 % le 2025-04-01 |
| Île-du-Prince-Édouard | 15 % TVH | |
| Terre-Neuve-et-Labrador | 15 % TVH | |
| Hors Canada | 0 | export, pas de taxe canadienne |

- **Livraison ET manutention taxables** quand liées à un bien taxable (ARC + Revenu Québec).
- **Manutention +1 $ légale** si divulguée avant confirmation (interdiction du « drip pricing », Loi sur la concurrence + LPC Québec). Donc : l'afficher dans le récapitulatif avant paiement (déjà le cas, ajoutée au devis).

## 5. Décisions retenues (au mieux pour la plateforme)

1. **Markup : GARDER 30 % par défaut** (pas 20 %). Après Stripe + arrondi + dérive FX, 20 % laisse une marge nette trop mince et bascule en perte au moindre mouvement de coût. Le +1 $ et le plancher protègent la solvabilité ; 30 % protège le PROFIT. On pourra rouvrir 20 % une fois le plancher prouvé en service.
2. **Garde-fou de marge plancher AVANT encaissement** (dans la construction du PaymentIntent / au checkout, pas au webhook) : recalculer le coût réel courant (produit + devis Gelato) et exiger `0,971 × total − 0,30 − taxeÀRemettre − coûtRéel ≥ marge plancher`. Sinon, bloquer la commande proprement (message clair). C'est LA garantie déterministe du zéro perte.
3. **Absorber Stripe dans le prix produit** : markup effectif = `(1 + marge) / (1 − 0,029)` à la synchro, pour que le 2,9 % soit déjà dans le prix affiché. Le 0,30 $ fixe reste couvert par la manutention + le plancher.
4. **Arrondi .99 vers le HAUT au-dessus du plancher**, jamais en dessous.
5. **Taxes par destination** : TVH (ON/NB/NS/PE/NL aux taux ci-dessus), QC = TPS+TVQ, AB/BC/SK/MB/territoires = 5 % GST, hors CA = 0. **Taxer produit + livraison + manutention.** Table de taux dans la config (pas en dur), recoupée §4.
6. **Fixer le bug FX** (`shop.pricing.usd_cad_rate`) + ajouter une marge de sécurité FX (plancher de taux) pour qu'une baisse du CAD n'érode pas la marge entre deux synchros.
7. **Borne pays** : appliquer `shipping_countries` au checkout (rejet propre si hors liste). Défaut `['CA']`.

## 6. Décisions qui reviennent au fondateur (non bloquantes - défaut sûr appliqué)

- **TVP C.-B. / Sask. / Man.** : les facturer exige une inscription provinciale distincte. DÉFAUT SÛR appliqué = NE PAS facturer de TVP (seulement GST 5 %), parce que percevoir une taxe sans y être inscrit est pire que ne pas la percevoir. À confirmer : est-on inscrit à la TVP de ces provinces ? (probable : non). Numéros TPS/TVQ : dans 1Password.
- **Vendre hors Québec/Canada** : défaut = Canada seulement (`['CA']`). Possible d'ouvrir les États-Unis (Gelato y livre ; Stripe charge en CAD, le client paie le change ; taxe canadienne = 0 sur l'export). À décider.
- **Politique de remboursement** : pour l'impression à la demande, une commande en production chez Gelato n'est plus annulable, et un remboursement laisse ~1 $ de frais Stripe perdus. À encadrer (remboursement exceptionnel, conditions claires).

## 7. Points de branchement (fichier:ligne)

- Markup + gross-up Stripe + arrondi plancher : `Product.php:61-88` (+ `config.php:101-109`).
- Garde-fou plancher : `CheckoutController.php` (construction des lignes Stripe) avant `StripeService.php:40-59`.
- Taxe par destination + livraison/manutention taxables : `CheckoutController.php:106-122`, `CartController.php:179-180`, `CartService.php:162-170`, affichage `Order.php:34-35,204`.
- Bug FX : `ExchangeRateService.php:31`.
- Borne pays : `CheckoutController.php:40,100`.

## 8. Consultation (transparence)

- Codex (quantitatif, 0 jeton Anthropic) : analyse §3, reco « garder 30 % jusqu'à ce que le plancher existe ».
- sonar-pro (repli, Perplexity navigateur non disponible après ia-sync qui n'a pas resynchronisé Perplexity) : faits fiscaux §4.
- Non encore passé au club complet (ChatGPT/claude.ai/Gemini navigateur) : le coeur de la solvabilité est déterministe ; le résidu de jugement (20 % vs 30 %, PST, ouverture US) est porté au fondateur §6. À élargir si le fondateur veut un second avis sur la politique de marge.
