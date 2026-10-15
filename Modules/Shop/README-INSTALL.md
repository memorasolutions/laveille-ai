# Module Shop

Auteur : MEMORA solutions (info@memora.ca) - https://memora.solutions

Boutique d'impression à la demande : catalogue, panier, paiement Stripe, taxes canadiennes,
garde-fou de marge et exécution des commandes par Gelato. **ZONE ARGENT** : ne jamais
modifier les calculs de prix, de taxe ou de marge sans tests.

## Installation

1. Copier le dossier dans `Modules/Shop/`.
2. Ajouter `"Shop": false` dans `modules_statuses.json`, puis `composer dump-autoload`.
3. Publier la configuration (facultatif, la configuration du module se fusionne seule) :
   `php artisan vendor:publish --tag=shop-config`
4. Renseigner le `.env` (voir plus bas), puis lancer `php artisan migrate`
   (les migrations se chargent depuis le module; `--tag=shop-migrations` ne sert qu'à les copier
   dans `database/migrations` si le projet l'exige, jamais les deux à la fois).
5. Pour activer : `"Shop": true` dans `modules_statuses.json`.
6. Webhooks à déclarer chez les fournisseurs : `POST /webhooks/stripe-shop` (Stripe) et
   `POST /webhooks/gelato` (Gelato).

## Variables d'environnement

| Variable | Rôle |
|----------|------|
| `SHOP_ENABLED` | Présent dans la configuration mais lu par aucun code : seul `modules_statuses.json` active ou désactive le module |
| `SHOP_MAINTENANCE` | Mode maintenance (administrateurs seulement) |
| `SHOP_FOUNDER_ONLY` | Boutique visible du seul super-administrateur (404 pour les autres) |
| `GELATO_API_KEY`, `GELATO_STORE_ID`, `GELATO_API_URL` | Accès Gelato |
| `GELATO_WEBHOOK_SECRET` | Signature des webhooks Gelato |
| `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY` | Clés Stripe |
| `STRIPE_SHOP_WEBHOOK_SECRET` | Signature du webhook Stripe de la boutique |
| `SHOP_GELATO_ZERO_ERREUR` | Mode strict (fichier d'impression approuvé, idempotence, signatures en fail-closed). Exige les deux secrets de webhook |
| `SHOP_GELATO_EDITOR` | Éditeur client (exige aussi `SHOP_GELATO_ZERO_ERREUR`) |
| `SHOP_GELATO_ENV_MARKER` | Marqueur d'environnement, valeur DIFFÉRENTE par environnement qui partage un compte Gelato |
| `SHOP_CURRENCY`, `SHOP_TAX_TPS`, `SHOP_TAX_TVQ`, `SHOP_TAX_COLLECT_PST` | Devise et taxes (TVP non perçue par défaut) |
| `SHOP_SHIPPING_COUNTRIES` | Pays de livraison, `CA` par défaut, `*` pour tous |
| `SHOP_FLOOR_MARGIN_CAD`, `SHOP_USD_CAD_RATE`, `SHOP_USD_CAD_RATE_FLOOR` | Garde-fou de marge et taux de change |
| `SHOP_ROUTES_PREFIX`, `SHOP_ROUTES_ADMIN_PREFIX` | Préfixes d'URL (`boutique`, `admin/shop`) |
| `GELATO_PREP_URL`, `GELATO_PREP_SECRET`, `GELATO_PREP_CLI` | Moteur d'impression "print-prep" (facultatif) |

Les secrets vont dans un gestionnaire de secrets, jamais dans le dépôt.

## Dépendances réelles

- Laravel avec `nwidart/laravel-modules`, base de données relationnelle, file d'attente et planificateur
  (`php artisan schedule:run`) : synchro des prix (04h00), nettoyage des commandes en attente (03h00),
  relances de paniers abandonnés (chaque heure).
- Stripe et Gelato sont appelés par HTTP (client `Http` de Laravel), aucun SDK tiers requis.
- Modèle `App\Models\User` et middleware `permission:*` (Spatie ou équivalent) pour l'administration.
- Gabarits : `backoffice::layouts.admin`, `auth::layouts.user-frontend` et le helper `fronttheme_layout()`
  doivent exister dans le projet hôte (voir la liste de couplage ci-dessous).
- Facultatifs, protégés par `class_exists()` : `Modules\Settings`, `Modules\Newsletter`.

## Désactivation

Mettre `"Shop": false` dans `modules_statuses.json` : le module n'enregistre ni routes, ni commandes,
ni tâches planifiées, et le site continue de fonctionner.
Les tables `shop_*` ne sont jamais supprimées automatiquement (données clients et commandes).
Pour un arrêt temporaire sans désactiver : `SHOP_MAINTENANCE=true`.

## Avant d'exporter vers un autre projet

Le module reste couplé à laveille sur quelques points (gabarits, sujet des courriels, gabarit de
conditions de vente, données d'amorçage). Les adapter dans le projet hôte avant la mise en ligne.
