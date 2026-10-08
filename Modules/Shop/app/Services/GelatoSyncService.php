<?php

declare(strict_types=1);

namespace Modules\Shop\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Shop\Gelato\SyncLockLostException;
use Modules\Shop\Models\Product;

/**
 * Sync produit Gelato store → DB locale shop_products.
 *
 * Lit l'endpoint `/v3/stores/{storeId}/products/{productId}` (ecommerce API)
 * et transforme la réponse en JSON variants stocké dans shop_products.variants.
 *
 * Source de vérité = Gelato store. La DB locale est un cache requêtable.
 * Re-exécution idempotente : upsert sur slug.
 */
class GelatoSyncService
{
    /** Map nom couleur Gelato → hex (Gildan 5000 standards 2026). */
    private const COLOR_HEX_MAP = [
        'white' => '#FFFFFF',
        'natural' => '#E8DCC4',
        'kiwi' => '#A4C639',
        'carolina-blue' => '#7BAFD4',
        'royal' => '#1F3A93',
        'dark-heather' => '#4D4F53',
        'cardinal-red' => '#8B1A1A',
        'forest-green' => '#1B3A28',
        'black' => '#000000',
    ];

    /**
     * Surcharge prix par taille (Gelato facture plus cher au-dessus de XL).
     * Pourcentage du prix de base.
     */
    private const SIZE_SURCHARGE = [
        'S' => 0.00, 'M' => 0.00, 'L' => 0.00, 'XL' => 0.00,
        '2XL' => 3.00, '3XL' => 5.00, '4XL' => 7.00, '5XL' => 9.00,
    ];

    /** Verrou de synchro tenu par CE processus pendant une écriture (null hors synchro verrouillée). */
    private ?\Illuminate\Contracts\Cache\Lock $lock = null;

    /** @var array<int, array> ambiguïtés relevées par le dernier transformToLocalVariants (variantes retirées de la vente) */
    public array $lastAmbiguities = [];

    public function isConfigured(): bool
    {
        return ! empty(config('shop.gelato.api_key')) && ! empty(config('shop.gelato.store_id'));
    }

    /**
     * Récupère un product depuis Gelato store API (ecommerce, pas catalog).
     */
    public function fetchStoreProduct(string $storeId, string $productId): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => config('shop.gelato.api_key'),
            ])->baseUrl('https://ecommerce.gelatoapis.com')
              ->get("/v1/stores/{$storeId}/products/{$productId}");

            if (! $response->successful()) {
                Log::error('GelatoSyncService.fetchStoreProduct failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('GelatoSyncService.fetchStoreProduct exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Transforme la réponse Gelato en JSON variants utilisable par la vue Product.
     *
     * Format de sortie compatible show.blade.php (Alpine x-data) :
     *   [
     *     {
     *       "color": "#FFFFFF",                        // HEX (dot couleur dans la vue)
     *       "color_slug": "white",
     *       "label": "Blanc",                          // texte affiché
     *       "size_prices": { "S": 20.99, ..., "5XL": 29.99 },
     *       "variant_ids": { "S": "uuid-gelato", ... }, // gelato store variant id par taille
     *       "product_uids": { "S": "apparel_...", ... },// gelato catalog product uid par taille
     *       "images": ["mockup_url"],                  // array (compat vue galerie)
     *       "gelato_uid": "<product_uid taille M>",     // fallback compat vue
     *       "sort_order": 0
     *     }
     *   ]
     */
    public function transformToLocalVariants(array $gelatoProduct, float $basePrice): array
    {
        $variants = $gelatoProduct['variants'] ?? [];
        $attributes = $gelatoProduct['productVariantAttributes'] ?? [];
        $images = $gelatoProduct['productImages'] ?? [];

        $colorOrder = [];
        foreach ($attributes as $attr) {
            if ($attr['name'] === 'Couleur') {
                foreach ($attr['values'] as $i => $v) {
                    $slug = $v['keys'][0]['value'] ?? strtolower($v['value']);
                    $colorOrder[$slug] = ['label' => $v['value'], 'order' => $i];
                }
            }
        }

        // Map image fileUrl par variant_id (un mockup peut couvrir N variants)
        $imageByVariantId = [];
        foreach ($images as $img) {
            foreach (($img['productVariantIds'] ?? []) as $vid) {
                if (! isset($imageByVariantId[$vid])) {
                    $imageByVariantId[$vid] = $img['fileUrl'] ?? null;
                }
            }
        }
        $primaryImage = collect($images)->firstWhere('isPrimary', true)['fileUrl']
            ?? ($images[0]['fileUrl'] ?? null);

        // Variantes dont le productUid est partagé par plusieurs variantes Gelato (designs différents) : indécidables.
        $ambiguousUids = $this->findAmbiguousUids($gelatoProduct);
        $this->lastAmbiguities = array_map(fn ($uid) => ['productUid' => $uid, 'raison' => 'même productUid pour plusieurs variantes Gelato'], $ambiguousUids);

        $grouped = [];
        $conflicts = []; // "groupe|taille" -> productUid/identifiants en conflit : la case est RETIRÉE, jamais écrasée en silence
        foreach ($variants as $v) {
            $title = $v['title'] ?? '';
            [$colorLabel, $size] = $this->parseTitle($title);

            $productUid = (string) ($v['productUid'] ?? '');
            if (in_array($productUid, $ambiguousUids, true)) {
                continue; // jamais commandable (storeProductVariantId indécidable) : ni mappée ni offerte
            }

            $colorSlug = $this->colorSlugOf($productUid, $colorLabel);
            if ($colorSlug === '') {
                $conflicts['?|'.($v['id'] ?? '')] = true;
                $this->lastAmbiguities[] = ['variantId' => $v['id'] ?? null, 'raison' => 'couleur indéterminable'];

                continue;
            }

            $price = round($basePrice + (self::SIZE_SURCHARGE[$size] ?? 0), 2);

            if (! isset($grouped[$colorSlug])) {
                $mockup = $imageByVariantId[$v['id']] ?? $primaryImage;
                $grouped[$colorSlug] = [
                    // `color` = HEX : la vue show.blade.php l'utilise directement comme background-color du dot
                    'color' => self::COLOR_HEX_MAP[$colorSlug] ?? '#888888',
                    'color_slug' => $colorSlug,
                    'label' => $colorOrder[$colorSlug]['label'] ?? $colorLabel,
                    'size_prices' => [],
                    'variant_ids' => [],
                    'product_uids' => [],
                    // `images` = array : compat galerie vue (currentVariant.images)
                    'images' => $mockup ? [$mockup] : [],
                    'gelato_uid' => '', // rempli plus bas avec le product_uid taille M (fallback)
                    'sort_order' => $colorOrder[$colorSlug]['order'] ?? 99,
                ];
            }

            $slot = $colorSlug.'|'.$size;
            if (isset($grouped[$colorSlug]['product_uids'][$size]) && $grouped[$colorSlug]['product_uids'][$size] !== $productUid) {
                $conflicts[$slot] = true; // deux productUid pour la même couleur + taille
                $this->lastAmbiguities[] = ['couleur' => $colorSlug, 'taille' => $size, 'raison' => 'deux productUid pour la même couleur et taille'];
            }
            $grouped[$colorSlug]['size_prices'][$size] = $price;
            $grouped[$colorSlug]['variant_ids'][$size] = $v['id'];
            $grouped[$colorSlug]['product_uids'][$size] = $productUid;
        }

        foreach (array_keys($conflicts) as $slot) {
            [$slug, $size] = array_pad(explode('|', $slot, 2), 2, '');
            if (isset($grouped[$slug])) {
                unset($grouped[$slug]['size_prices'][$size], $grouped[$slug]['variant_ids'][$size], $grouped[$slug]['product_uids'][$size]);
            }
        }
        foreach ($grouped as $slug => &$g) {
            if ($g['product_uids'] === []) {
                unset($grouped[$slug]);
                continue;
            }
            // gelato_uid fallback = product_uid de la taille M (ou première taille restante)
            $g['gelato_uid'] = $g['product_uids']['M'] ?? reset($g['product_uids']);
        }
        unset($g);

        // Tri ordre Gelato puis tailles standard
        $sizeOrder = self::SIZE_ORDER;
        usort($grouped, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);
        foreach ($grouped as &$g) {
            $sortedPrices = [];
            $sortedIds = [];
            $sortedUids = [];
            foreach ($sizeOrder as $s) {
                if (isset($g['size_prices'][$s])) {
                    $sortedPrices[$s] = $g['size_prices'][$s];
                    $sortedIds[$s] = $g['variant_ids'][$s];
                    $sortedUids[$s] = $g['product_uids'][$s];
                }
            }
            $g['size_prices'] = $sortedPrices;
            $g['variant_ids'] = $sortedIds;
            $g['product_uids'] = $sortedUids;
        }

        return array_values($grouped);
    }


    /**
     * « Couleur - Taille - Technique » -> [couleur, taille]. Découpe sur « espace-tiret-espace » : une couleur composée
     * (« Bleu-gris - XL ») garde son tiret interne. Repli sur le tiret simple pour un titre sans espaces (« Blanc-XL »).
     *
     * @return array{0: string, 1: string}
     */
    private function parseTitle(string $title): array
    {
        $parts = array_map('trim', str_contains($title, ' - ') ? explode(' - ', $title) : explode('-', $title));

        return [$parts[0] ?? 'Unknown', ($parts[1] ?? '') !== '' ? $parts[1] : 'M'];
    }

    /**
     * Clé de regroupement : slug de couleur du productUid (...gco_<slug>_gpr...), sinon slug du LIBELLÉ de couleur.
     * Jamais un seau commun « unknown » où une variante en écraserait une autre. Source UNIQUE (structure ET coûts).
     */
    private function colorSlugOf(string $productUid, string $colorLabel): string
    {
        return preg_match('/_gco_(.+?)_gpr/', $productUid, $m) ? $m[1] : Str::slug($colorLabel);
    }

    /** @throws SyncLockLostException si un verrou est tenu par ce processus mais n'est plus à nous (expiré / repris) */
    private function assertLockStillOwned(): void
    {
        if ($this->lock !== null && ! $this->lock->isOwnedByCurrentProcess()) {
            throw new SyncLockLostException('Verrou de synchronisation perdu (expiré) : écriture refusée.');
        }
    }

    /**
     * Sync ciblé : fetch Gelato + mise à jour d'UN produit local, mapping COHÉRENT (colonne ET métadonnées ET store_variant_map).
     *
     * Refus nets (aucune écriture) :
     *  - l'identifiant Gelato appartient déjà à un AUTRE produit local (sinon deux produits partageraient un mapping);
     *  - le produit est déjà rattaché à un AUTRE produit Gelato, sauf $reassign explicite;
     *  - prix de base local nul (aucun prix arbitraire).
     */
    public function syncProductBySlug(string $slug, string $gelatoStoreProductId, bool $reassign = false): array
    {
        // Même verrou que la synchro complète : deux écritures concurrentes sur le catalogue sont exclues.
        $lock = Cache::lock(self::LOCK_KEY, 900);
        if (! $lock->get()) {
            return ['ok' => false, 'error' => 'Une synchronisation Gelato est déjà en cours.'];
        }
        $this->lock = $lock;

        try {
            $product = Product::where('slug', $slug)->first();
            if (! $product) {
                return ['ok' => false, 'error' => "Product slug={$slug} not found"];
            }

            $owner = $this->findLocalProduct($gelatoStoreProductId);
            if ($owner && $owner->id !== $product->id) {
                return ['ok' => false, 'error' => "Le produit Gelato {$gelatoStoreProductId} est déjà associé au produit local #{$owner->id} ({$owner->slug}) : aucune réaffectation"];
            }

            $current = $this->markerOf($product);
            if ($current !== null && $current !== $gelatoStoreProductId && ! $reassign) {
                return ['ok' => false, 'error' => "Le produit {$slug} est déjà rattaché au produit Gelato {$current} : réaffectation explicite requise"];
            }

            if ((float) $product->price <= 0) {
                return ['ok' => false, 'error' => "Le produit {$slug} n'a aucun prix de base : synchro refusée (jamais de prix arbitraire)"];
            }

            $gelato = $this->fetchStoreProduct((string) config('shop.gelato.store_id'), $gelatoStoreProductId);
            if (! $gelato) {
                return ['ok' => false, 'error' => 'Gelato fetch failed'];
            }

            // Backup ancien gelato_product_id avant écraser
            if ($product->gelato_product_id && ! $product->legacy_gelato_uid) {
                $product->legacy_gelato_uid = $product->gelato_product_id;
            }
            // Si title vide en DB, prendre celui Gelato
            if (empty($product->name)) {
                $product->name = strip_tags($gelato['title'] ?? '');
            }

            // MÊME chemin que la synchro complète (vendabilité, prix = coût Gelato par couleur et taille + marge, retrait,
            // empreinte) : plus de seconde logique de prix qui divergerait. Écriture forcée : c'est une demande explicite.
            $this->costCache = [];
            $this->syncStoreProduct($gelato, ['force' => true], null, $product);

            $variants = $product->fresh()->variants ?? [];

            return [
                'ok' => true,
                'product_id' => $product->id,
                'colors' => count($variants),
                'total_variants' => array_sum(array_map(fn ($v) => count($v['size_prices'] ?? []), $variants)),
                'gelato_title' => $gelato['title'] ?? null,
            ];
        } catch (SyncLockLostException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        } finally {
            $this->lock = null;
            $lock->release();
        }
    }

    // ------------------------------------------------------------------
    // Synchro COMPLÈTE du catalogue du store (commande shop:sync-gelato et bouton admin)
    // ------------------------------------------------------------------

    /** Clé du verrou empêchant deux synchros simultanées (cron + bouton admin). */
    private const LOCK_KEY = 'shop:gelato-sync';

    /** Ordre d'affichage des tailles. */
    private const SIZE_ORDER = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'];

    /** @var array<string, float|null> cache des coûts par productUid, le temps d'une synchro */
    private array $costCache = [];

    /**
     * Synchronise tout le catalogue du store Gelato vers shop_products.
     *
     * Le store Gelato est la source de vérité : un produit créé côté Gelato apparaît dans la boutique,
     * un produit retiré côté Gelato est DÉPUBLIÉ (status=draft), jamais supprimé.
     *
     * @param  array{dry_run?: bool, force?: bool}  $options
     * @param  callable|null  $report  fn(string $label, string $detail): void, pour la sortie console
     * @return array{ok: bool, error: ?string, created: int, updated: int, unchanged: int, unpublished: int, errors: int, lines: array<int, string>}
     */
    public function syncStore(array $options = [], ?callable $report = null): array
    {
        $result = [
            'ok' => false, 'error' => null,
            'created' => 0, 'updated' => 0, 'unchanged' => 0, 'unpublished' => 0, 'errors' => 0,
            'lines' => [],
        ];
        $say = function (string $label, string $detail = '') use (&$result, $report) {
            $result['lines'][] = trim($label.' '.$detail);
            if ($report) {
                $report($label, $detail);
            }
        };

        if (! $this->isConfigured()) {
            $result['error'] = 'GELATO_STORE_ID ou GELATO_API_KEY manquant dans .env';

            return $result;
        }

        $lock = Cache::lock(self::LOCK_KEY, 900);
        if (! $lock->get()) {
            $result['error'] = 'Une synchronisation Gelato est déjà en cours.';

            return $result;
        }

        $this->lock = $lock;

        try {
            $storeId = (string) config('shop.gelato.store_id');
            $list = $this->listStoreProducts($storeId);
            if ($list === null) {
                // Liste illisible : on ne touche à RIEN (surtout pas aux retraits).
                $result['error'] = 'Erreur API Gelato : liste des produits illisible.';

                return $result;
            }

            $this->costCache = [];
            $seen = [];
            foreach ($list as $gp) {
                $gelatoId = (string) ($gp['id'] ?? '');
                if ($gelatoId === '') {
                    continue;
                }
                $seen[$gelatoId] = true;

                // Le verrou expire (900 s) sans renouvellement : avant CHAQUE écriture on vérifie qu'il est toujours à nous,
                // sinon une 2e synchro a pu démarrer et deux processus écriraient en parallèle. On s'arrête net.
                if (! $lock->isOwnedByCurrentProcess()) {
                    $result['error'] = 'Verrou de synchronisation perdu (expiré) : synchro interrompue, aucun retrait effectué.';
                    $say('[INTERROMPU]', 'verrou de synchronisation perdu avant '.$gelatoId);

                    return $result;
                }

                $detail = $this->fetchStoreProduct($storeId, $gelatoId);
                if (! $detail) {
                    $result['errors']++;
                    $say('[ERREUR]', "{$gelatoId} : détail illisible, produit laissé tel quel");

                    continue;
                }

                try {
                    $outcome = $this->syncStoreProduct($detail, $options, $say);
                } catch (SyncLockLostException $e) {
                    $result['error'] = 'Verrou de synchronisation perdu (expiré) : synchro interrompue, aucun retrait effectué.';
                    $say('[INTERROMPU]', "verrou de synchronisation perdu pendant {$gelatoId}");

                    return $result;
                } catch (\Throwable $e) {
                    Log::error('GelatoSyncService.syncStoreProduct exception', ['id' => $gelatoId, 'error' => $e->getMessage()]);
                    $result['errors']++;
                    $say('[ERREUR]', "{$gelatoId} : {$e->getMessage()}");

                    continue;
                }
                $result[$outcome]++;
            }

            if (! $lock->isOwnedByCurrentProcess()) {
                $result['error'] = 'Verrou de synchronisation perdu (expiré) : synchro interrompue, aucun retrait effectué.';
                $say('[INTERROMPU]', 'verrou de synchronisation perdu avant les retraits');

                return $result;
            }
            try {
                $result['unpublished'] = $this->unpublishRemoved(array_keys($seen), ! empty($options['dry_run']), $say);
            } catch (SyncLockLostException $e) {
                $result['error'] = 'Verrou de synchronisation perdu (expiré) : retraits interrompus.';
                $say('[INTERROMPU]', 'verrou de synchronisation perdu pendant les retraits');

                return $result;
            }
            $result['ok'] = true;
        } finally {
            $this->lock = null;
            $lock->release();
        }

        return $result;
    }

    /**
     * Liste paginée des produits du store. null si l'API échoue (jamais une liste vide trompeuse).
     *
     * @return array<int, array>|null
     */
    public function listStoreProducts(string $storeId): ?array
    {
        $limit = 100;
        $all = [];
        $known = [];

        for ($page = 0; $page < 20; $page++) {
            try {
                $response = Http::withHeaders(['X-API-KEY' => config('shop.gelato.api_key')])
                    ->get("https://ecommerce.gelatoapis.com/v1/stores/{$storeId}/products", [
                        'limit' => $limit, 'offset' => $page * $limit,
                    ]);
            } catch (\Throwable $e) {
                Log::error('GelatoSyncService.listStoreProducts exception', ['error' => $e->getMessage()]);

                return null;
            }

            if (! $response->successful()) {
                Log::error('GelatoSyncService.listStoreProducts failed', ['status' => $response->status()]);

                return null;
            }

            // Réponse sans tableau `products` = réponse DOUTEUSE (jamais « fin de liste ») : une liste partielle
            // ferait dépublier à tort tous les produits absents.
            $batch = $response->json('products');
            if (! is_array($batch)) {
                Log::error('GelatoSyncService.listStoreProducts : champ products absent', ['page' => $page]);

                return null;
            }
            $fresh = 0;
            foreach ($batch as $gp) {
                $id = $gp['id'] ?? null;
                if ($id !== null && ! isset($known[$id])) {
                    $known[$id] = true;
                    $all[] = $gp;
                    $fresh++;
                }
            }
            // Une page NON VIDE qui n'apporte aucun produit nouveau (page pleine répétée par l'API) n'est pas une fin de liste :
            // des produits des pages jamais reçues seraient dépubliés à tort. Réponse douteuse => null (aucun retrait).
            if ($batch !== [] && $fresh === 0) {
                Log::error('GelatoSyncService.listStoreProducts : page répétée sans produit nouveau, liste douteuse', ['page' => $page]);

                return null;
            }
            // Dernière page : moins que la limite.
            if (count($batch) < $limit) {
                return $all;
            }
        }

        // Plafond de pages atteint alors que la dernière page était encore pleine : la liste est potentiellement TRONQUÉE.
        Log::error('GelatoSyncService.listStoreProducts : liste potentiellement tronquée (plafond de pages atteint)');

        return null;
    }

    /**
     * Correspondance COMPLÈTE productUid -> storeProductVariantId : une entrée par variante du store product.
     * Le chemin de commande lit metadata['store_variant_map'][productUid].
     *
     * @return array<string, string>
     */
    public function buildStoreVariantMap(array $gelatoProduct): array
    {
        $ambiguous = $this->findAmbiguousUids($gelatoProduct);
        $map = [];
        foreach ($gelatoProduct['variants'] ?? [] as $v) {
            $uid = (string) ($v['productUid'] ?? '');
            $id = (string) ($v['id'] ?? '');
            // Un productUid partagé par des variantes à designs différents n'est PAS commandable : une seule ne doit pas « survivre ».
            if ($uid !== '' && $id !== '' && ! in_array($uid, $ambiguous, true)) {
                $map[$uid] = $id;
            }
        }

        return $map;
    }

    /**
     * productUid portés par PLUSIEURS variantes Gelato (identifiants distincts) : le catalogue Gelato les distingue
     * (design différent), pas notre routage par productUid. Retirés du mapping ET de la vente, jamais départagés au hasard.
     *
     * @return array<int, string>
     */
    public function findAmbiguousUids(array $gelatoProduct): array
    {
        $idsByUid = [];
        foreach ($gelatoProduct['variants'] ?? [] as $v) {
            $uid = (string) ($v['productUid'] ?? '');
            $id = (string) ($v['id'] ?? '');
            if ($uid !== '' && $id !== '') {
                $idsByUid[$uid][$id] = true;
            }
        }

        return array_keys(array_filter($idsByUid, fn ($ids) => count($ids) > 1));
    }

    /** Un produit Gelato est vendable s'il est actif et prêt à publier (champ absent = on ne bloque pas). */
    public function isSellable(array $gelatoProduct): bool
    {
        $status = $gelatoProduct['status'] ?? null;
        if ($status !== null && strtolower((string) $status) !== 'active') {
            return false;
        }

        return ($gelatoProduct['isReadyToPublish'] ?? true) !== false;
    }

    /**
     * Crée ou met à jour (idempotent par gelato_store_product_id) un produit local.
     *
     * @return 'created'|'updated'|'unchanged'
     */
    public function syncStoreProduct(array $gelato, array $options = [], ?callable $say = null, ?Product $target = null): string
    {
        $say ??= static function (string $label, string $detail = ''): void {};
        $dry = ! empty($options['dry_run']);
        $force = ! empty($options['force']);

        $gelatoId = (string) $gelato['id'];
        $title = strip_tags((string) ($gelato['title'] ?? 'Produit Gelato')) ?: 'Produit Gelato';
        $category = $this->detectCategory($title);
        $map = $this->buildStoreVariantMap($gelato);
        $sellable = $this->isSellable($gelato);
        $autopublish = (bool) config('shop.gelato_sync_autopublish', true);

        $product = $target ?? $this->findLocalProduct($gelatoId);
        if ($product && $product->trashed()) {
            // Supprimé volontairement côté admin : on respecte, on ne recrée pas.
            $say('[IGNORÉ]', "{$title} (supprimé dans la boutique)");

            return 'unchanged';
        }

        // Coûts Gelato par taille (une requête par taille distincte), puis prix = coût + marge.
        // Coût par (couleur, taille) : une couleur plus chère n'est jamais vendue au coût d'une autre.
        $costs = $this->fetchCostsByColorAndSize($gelato);
        $firstBySize = [];
        foreach ($costs as $bySize) {
            foreach ($bySize as $size => $cost) {
                $firstBySize[$size] ??= $cost;
            }
        }
        $baseCost = $firstBySize['M'] ?? ($firstBySize === [] ? null : reset($firstBySize));
        $basePrice = $baseCost !== null
            ? Product::smartPrice($baseCost, $category)
            : (float) ($product?->price ?: 0);

        // transformToLocalVariants fournit la STRUCTURE (couleurs, tailles, identifiants); les prix, eux, ne viennent que
        // d'un coût Gelato réel ou du prix déjà publié pour cette couleur + taille. JAMAIS un prix de remplacement :
        // une taille sans prix connu est RETIRÉE de la vente (et le produit nouveau reste en brouillon).
        $variants = $this->transformToLocalVariants($gelato, max($basePrice, 0.0));
        $ambiguities = $this->lastAmbiguities;
        $existingVariants = collect($product?->variants ?? []);
        $unpriced = 0;
        foreach ($variants as &$variant) {
            $old = $existingVariants->first(fn ($e) => ($e['color_slug'] ?? null) === $variant['color_slug']);
            foreach (array_keys($variant['size_prices']) as $size) {
                $cost = $costs[$variant['color_slug']][$size] ?? null;
                $price = $cost !== null ? Product::smartPrice($cost, $category) : (isset($old['size_prices'][$size]) ? (float) $old['size_prices'][$size] : null);
                if ($price !== null && $price > 0) {
                    $variant['size_prices'][$size] = $price;
                } else {
                    unset($variant['size_prices'][$size], $variant['variant_ids'][$size], $variant['product_uids'][$size]);
                    $unpriced++;
                }
            }
            $variant['_old_images'] = $old['images'] ?? [];
        }
        unset($variant);
        // Une couleur sans aucune taille tarifée n'est plus offerte.
        $variants = array_values(array_filter($variants, fn ($v) => $v['size_prices'] !== []));
        foreach ($variants as &$variant) {
            $variant['gelato_uid'] = $variant['product_uids']['M'] ?? (string) reset($variant['product_uids']);
        }
        unset($variant);
        $fullyPriced = $variants !== [] && $unpriced === 0;

        $sizes = [];
        foreach ($variants as $variant) {
            $sizes = array_merge($sizes, array_keys($variant['size_prices']));
        }
        $sizes = array_values(array_unique($sizes));
        usort($sizes, fn ($a, $b) => $this->sizeRank($a) <=> $this->sizeRank($b));

        // Empreinte stable du contenu synchronisé (calculée AVANT toute fusion locale d'images).
        $hashed = array_map(function ($v) {
            unset($v['_old_images']);

            return $v;
        }, $variants);
        $hash = sha1(json_encode([$title, $gelato['description'] ?? '', $hashed, $map, $sizes, $sellable, $unpriced, $ambiguities]));

        // Images propres au produit local conservées si elles existent (gérées dans l'admin).
        foreach ($variants as &$variant) {
            if (! empty($variant['_old_images'])) {
                $variant['images'] = $variant['_old_images'];
            }
            unset($variant['_old_images']);
        }
        unset($variant);

        if ($unpriced > 0) {
            $say('[PRIX MANQUANTS]', "{$title} : {$unpriced} taille(s) sans coût Gelato ni prix publié, retirée(s) de la vente");
        }
        if ($ambiguities !== []) {
            $say('[AMBIGU]', "{$title} : ".count($ambiguities).' variante(s) indécidable(s) retirée(s) de la vente');
        }

        $previewUrl = $gelato['previewUrl'] ?? ($variants[0]['images'][0] ?? null);

        if (! $product) {
            // Publié seulement si vendable, autopublication ET chaque taille offerte a un prix RÉEL (jamais 0 ni arbitraire).
            $status = ($sellable && $autopublish && $fullyPriced) ? 'published' : 'draft';
            if ($dry) {
                $say('[DRY RUN CRÉÉ]', "{$title} : ".count($variants).' couleurs, '.count($map)." variantes, {$basePrice} CAD ({$status})");

                return 'created';
            }

            $product = new Product([
                'name' => $title,
                'slug' => $this->uniqueSlug($title),
                'description' => strip_tags((string) ($gelato['description'] ?? '')),
                'price' => $basePrice,
                'category' => $category,
                'images' => $previewUrl ? [$previewUrl] : [],
                'variants' => $variants,
                'status' => $status,
                'metadata' => [
                    'gelato_store_product_id' => $gelatoId,
                    'store_variant_map' => $map,
                    'sizes' => $sizes,
                    'cost_base' => $baseCost,
                    'cost_currency' => 'USD',
                    'gelato_sync_hash' => $hash,
                ] + ($unpriced > 0 ? ['gelato_sync_pricing_incomplete' => $unpriced] : [])
                  + ($ambiguities !== [] ? ['gelato_sync_ambiguous' => $ambiguities] : []),
            ]);
            $product->gelato_store_product_id = $gelatoId;
            $product->gelato_synced_at = now();
            $this->assertLockStillOwned(); // les coûts ont pu prendre plus que la durée du verrou
            $product->save();

            $say('[CRÉÉ]', "{$title} : ".count($variants).' couleurs, '.count($map)." variantes, {$basePrice} CAD ({$status})");

            return 'created';
        }

        $metadata = $product->metadata ?? [];
        // Comparaison AVANT écrasement de l'empreinte précédente.
        $previousHash = $metadata['gelato_sync_hash'] ?? null;
        $wasRetired = ! empty($metadata['gelato_sync_unpublished']);
        $republish = $sellable && $autopublish && $fullyPriced && $product->status === 'draft' && ($wasRetired || $previousHash === null);
        // Plus vendable chez Gelato (inactif / non prêt à publier) ou plus aucune taille tarifée : retiré de la vente
        // (brouillon réversible, jamais supprimé). Le checkout ne vend que du « published ».
        $retire = $product->status === 'published' && (! $sellable || $variants === []);
        $markerMissing = ($metadata['gelato_store_product_id'] ?? null) !== $gelatoId
            || $product->gelato_store_product_id !== $gelatoId;

        if (! $force && ! $markerMissing && ! $republish && ! $retire && $previousHash === $hash) {
            $say('[INCHANGÉ]', "{$title} : ".count($variants).' couleurs');

            return 'unchanged';
        }

        if ($dry) {
            $say('[DRY RUN MIS À JOUR]', "{$title} : ".count($variants).' couleurs, '.count($map).' variantes'.($republish ? ', republié' : ''));

            return 'updated';
        }

        $metadata['gelato_store_product_id'] = $gelatoId;
        $metadata['store_variant_map'] = $map;
        $metadata['sizes'] = $sizes;
        $metadata['gelato_sync_hash'] = $hash;
        unset($metadata['gelato_sync_pricing_incomplete'], $metadata['gelato_sync_ambiguous']);
        if ($unpriced > 0) {
            $metadata['gelato_sync_pricing_incomplete'] = $unpriced;
        }
        if ($ambiguities !== []) {
            $metadata['gelato_sync_ambiguous'] = $ambiguities;
        }
        if ($baseCost !== null) {
            $metadata['cost_base'] = $baseCost;
            $metadata['cost_currency'] = 'USD';
        }

        $update = ['variants' => $variants, 'metadata' => $metadata];
        if ($basePrice > 0 && $firstBySize !== []) {
            $update['price'] = $basePrice;
        }
        if (empty($product->images) && $previewUrl) {
            $update['images'] = [$previewUrl];
        }
        if ($republish) {
            $update['status'] = 'published';
            unset($metadata['gelato_sync_unpublished'], $metadata['gelato_unpublished_at']);
            $update['metadata'] = $metadata;
        } elseif ($retire) {
            $update['status'] = 'draft';
            $metadata['gelato_sync_unpublished'] = true;
            $metadata['gelato_unpublished_at'] = now()->toIso8601String();
            $update['metadata'] = $metadata;
        }

        $product->fill($update);
        $product->gelato_store_product_id = $gelatoId;
        $product->gelato_synced_at = now();
        $this->assertLockStillOwned();
        $product->save();

        $say('[MIS À JOUR]', "{$title} : ".count($variants).' couleurs, '.count($map).' variantes'.($republish ? ', republié' : '').($retire ? ', retiré de la vente' : ''));

        return 'updated';
    }

    /**
     * Dépublie (status=draft) tout produit catalogue absent de la liste Gelato. JAMAIS de suppression.
     *
     * @param  array<int, string>  $seenIds
     */
    private function unpublishRemoved(array $seenIds, bool $dry, callable $say): int
    {
        $catalog = Product::all()->filter(fn ($p) => $this->markerOf($p) !== null);
        if (empty($seenIds) && $catalog->isNotEmpty()) {
            // Liste vide mais catalogue local non vide : plus probablement un incident API qu'un store vidé.
            $say('[RETRAIT SUSPENDU]', 'liste Gelato vide, aucun produit dépublié par précaution');

            return 0;
        }

        $count = 0;
        foreach ($catalog as $p) {
            if (in_array($this->markerOf($p), $seenIds, true) || $p->status === 'draft') {
                continue;
            }
            $count++;
            if ($dry) {
                $say('[DRY RUN DÉPUBLIÉ]', $p->name);

                continue;
            }
            $this->assertLockStillOwned();
            $metadata = $p->metadata ?? [];
            $metadata['gelato_sync_unpublished'] = true;
            $metadata['gelato_unpublished_at'] = now()->toIso8601String();
            $p->status = 'draft';
            $p->metadata = $metadata;
            $p->save();
            $say('[DÉPUBLIÉ]', "{$p->name} (retiré de Gelato, conservé en brouillon)");
        }

        return $count;
    }

    private function markerOf(Product $p): ?string
    {
        $id = $p->gelato_store_product_id ?: ($p->metadata['gelato_store_product_id'] ?? null);

        return $id ? (string) $id : null;
    }

    private function findLocalProduct(string $gelatoId): ?Product
    {
        return Product::withTrashed()->where('gelato_store_product_id', $gelatoId)->first()
            ?? Product::withTrashed()->get()->first(fn ($p) => ($p->metadata['gelato_store_product_id'] ?? null) === $gelatoId);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'produit-gelato';
        $slug = $base;
        for ($i = 2; Product::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * Coût Gelato (USD) par couleur ET par taille, via /v3/products/{uid}/prices du productUid de CHAQUE combinaison.
     *
     * @return array<string, array<string, float>> [color_slug][taille] => coût
     */
    private function fetchCostsByColorAndSize(array $gelato): array
    {
        $ambiguous = $this->findAmbiguousUids($gelato);
        $uidBySlot = [];
        foreach ($gelato['variants'] ?? [] as $v) {
            $uid = (string) ($v['productUid'] ?? '');
            if ($uid === '' || in_array($uid, $ambiguous, true)) {
                continue;
            }
            [$colorLabel, $size] = $this->parseTitle((string) ($v['title'] ?? ''));
            $slug = $this->colorSlugOf($uid, $colorLabel);
            if ($slug !== '' && ! isset($uidBySlot[$slug][$size])) {
                $uidBySlot[$slug][$size] = $uid;
            }
        }

        $costs = [];
        foreach ($uidBySlot as $slug => $bySize) {
            foreach ($bySize as $size => $uid) {
                $cost = $this->fetchCostBase($uid);
                if ($cost !== null) {
                    $costs[$slug][$size] = $cost;
                }
            }
        }

        return $costs;
    }

    public function fetchCostBase(string $productUid): ?float
    {
        if (array_key_exists($productUid, $this->costCache)) {
            return $this->costCache[$productUid];
        }

        $cost = null;
        try {
            $response = Http::withHeaders(['X-API-KEY' => config('shop.gelato.api_key')])
                ->get("https://product.gelatoapis.com/v3/products/{$productUid}/prices", ['country' => 'CA']);
            if ($response->successful()) {
                $data = $response->json();
                $cost = isset($data[0]['price']) ? round((float) $data[0]['price'], 2) : null;
            }
        } catch (\Throwable $e) {
            Log::warning('GelatoSyncService.fetchCostBase exception', ['uid' => $productUid, 'error' => $e->getMessage()]);
        }

        return $this->costCache[$productUid] = $cost;
    }

    public function detectCategory(string $title): string
    {
        $title = strtolower($title);
        $map = [
            'hoodie' => 'hoodies', 't-shirt' => 't-shirts', 'tshirt' => 't-shirts',
            'mug' => 'mugs', 'tasse' => 'mugs', 'water bottle' => 'water-bottles',
            'bouteille' => 'water-bottles', 'tote' => 'tote-bags', 'sac' => 'tote-bags',
            'poster' => 'posters',
        ];
        foreach ($map as $keyword => $category) {
            if (str_contains($title, $keyword)) {
                return $category;
            }
        }

        return 'default';
    }

    private function sizeRank(string $size): int
    {
        $i = array_search(strtoupper($size), self::SIZE_ORDER, true);

        return $i === false ? 99 : $i;
    }
}
