<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Shop\Models\Product;
use Modules\Shop\Services\GelatoSyncService;

uses(Tests\TestCase::class, RefreshDatabase::class);

const GCS_STORE = 'c6cdf0b6-b032-4204-ad54-3db963fbac1a';

function gcsUid(string $color, string $size): string
{
    return "apparel_product_gca_t-shirt_gsc_crewneck_gcu_unisex_gqa_classic_gsi_".strtolower($size)."_gco_{$color}_gpr_4-0";
}

/** Produit store Gelato : 2 couleurs x 3 tailles = 6 variantes. */
function gcsDetail(string $id = 'sp-1', string $status = 'active', bool $ready = true): array
{
    $variants = [];
    foreach (['white' => 'Blanc', 'black' => 'Noir'] as $slug => $label) {
        foreach (['S', 'M', 'XL'] as $size) {
            $variants[] = [
                'id' => "var-{$slug}-{$size}",
                'title' => "{$label} - {$size} - DTG",
                'productUid' => gcsUid($slug, $size),
            ];
        }
    }

    return [
        'id' => $id, 'title' => 'T-shirt La veille.ai', 'description' => '<p>Un t-shirt</p>',
        'status' => $status, 'isReadyToPublish' => $ready, 'previewUrl' => 'https://img.test/p.png',
        'variants' => $variants, 'productVariantAttributes' => [], 'productImages' => [],
    ];
}

function gcsFake(array $details, array $costBySize = ['S' => 10.0, 'M' => 10.0, 'XL' => 12.0]): void
{
    Http::swap(new \Illuminate\Http\Client\Factory()); // repart d'un client neuf : les fakes successifs ne s'écrasent pas
    $list = array_map(fn ($d) => ['id' => $d['id']], $details);
    $fake = [
        'ecommerce.gelatoapis.com/v1/stores/*/products?*' => Http::response(['products' => $list]),
        'product.gelatoapis.com/v3/products/*/prices*' => function ($request) use ($costBySize) {
            preg_match('/_gsi_([a-z0-9]+)_/', $request->url(), $m);

            return Http::response([['price' => $costBySize[strtoupper($m[1])] ?? 10.0]]);
        },
    ];
    foreach ($details as $d) {
        $fake['ecommerce.gelatoapis.com/v1/stores/*/products/'.$d['id']] = Http::response($d);
    }
    // Images de mockup Gelato (previewUrl / productImages) : octets factices avec un type image,
    // pour que l'auto-hébergement (ProductImageStore) réussisse sans appel réseau réel.
    $fake['img.test/*'] = Http::response("\xFF\xD8\xFFimage-factice", 200, ['Content-Type' => 'image/jpeg']);
    // Les URL spécifiques d'abord, la liste (motif plus large) ensuite.
    $ordered = array_merge(array_slice($fake, 2), array_slice($fake, 0, 2));
    Http::fake($ordered);
}

beforeEach(function () {
    config([
        'shop.gelato.api_key' => 'test-key', 'shop.gelato.store_id' => GCS_STORE,
        'shop.gelato_sync_autopublish' => true,
    ]);
    \Illuminate\Support\Facades\Cache::put('exchange_rate_usd_cad', 1.40, 3600);
    // La synchro AUTO-HÉBERGE désormais les images Gelato (ProductImageStore) : disque public
    // simulé pour que les écritures restent en mémoire, jamais sur le vrai storage.
    \Illuminate\Support\Facades\Storage::fake('public');
});

test('la synchro crée un produit avec store_variant_map COMPLET, marqueur, prix et publication', function () {
    gcsFake([gcsDetail()]);

    $r = app(GelatoSyncService::class)->syncStore();

    expect($r['ok'])->toBeTrue()->and($r['created'])->toBe(1)->and($r['errors'])->toBe(0);

    $p = Product::firstOrFail();
    expect($p->status)->toBe('published')
        ->and($p->metadata['gelato_store_product_id'])->toBe('sp-1')
        ->and($p->gelato_store_product_id)->toBe('sp-1');

    $map = $p->metadata['store_variant_map'];
    expect($map)->toHaveCount(6)
        ->and($map[gcsUid('white', 'S')])->toBe('var-white-S')
        ->and($map[gcsUid('black', 'XL')])->toBe('var-black-XL');

    expect($p->variants)->toHaveCount(2);
    $v = collect($p->variants)->firstWhere('color_slug', 'white');
    expect($v['variant_ids'])->toHaveCount(3)
        ->and($v['product_uids']['M'])->toBe(gcsUid('white', 'M'))
        ->and($v['variant_ids']['M'])->toBe('var-white-M')
        ->and($v['size_prices']['S'])->toBe(Product::smartPrice(10.0, 't-shirts'))
        ->and($v['size_prices']['XL'])->toBe(Product::smartPrice(12.0, 't-shirts'))
        ->and((float) $p->price)->toBe(Product::smartPrice(10.0, 't-shirts'))
        ->and((float) $p->metadata['cost_by_uid'][gcsUid('white', 'XL')])->toBe(12.0)
        ->and((float) $p->metadata['cost_by_uid'][gcsUid('white', 'S')])->toBe(10.0);
});

test('une re-synchro met à jour sans dupliquer (idempotent)', function () {
    gcsFake([gcsDetail()]);
    $svc = app(GelatoSyncService::class);
    $svc->syncStore();

    $r2 = $svc->syncStore();
    expect($r2['created'])->toBe(0)->and($r2['unchanged'])->toBe(1)->and(Product::count())->toBe(1);

    // Un titre modifié côté Gelato = mise à jour de la même ligne.
    $d = gcsDetail();
    $d['title'] = 'T-shirt La veille.ai v2';
    gcsFake([$d]);
    $r3 = $svc->syncStore();
    expect($r3['updated'])->toBe(1)->and($r3['created'])->toBe(0)->and(Product::count())->toBe(1);
});

test('un produit disparu de Gelato est dépublié, jamais supprimé', function () {
    gcsFake([gcsDetail('sp-1'), gcsDetail('sp-2')]);
    $svc = app(GelatoSyncService::class);
    $svc->syncStore();
    expect(Product::count())->toBe(2);

    gcsFake([gcsDetail('sp-1')]);
    $r = $svc->syncStore();

    expect($r['unpublished'])->toBe(1)->and(Product::withTrashed()->count())->toBe(2);
    $gone = Product::where('gelato_store_product_id', 'sp-2')->firstOrFail();
    expect($gone->status)->toBe('draft')->and($gone->trashed())->toBeFalse();
    expect(Product::where('gelato_store_product_id', 'sp-1')->first()->status)->toBe('published');

    // Réapparition côté Gelato : republié.
    gcsFake([gcsDetail('sp-1'), gcsDetail('sp-2')]);
    $svc->syncStore();
    expect($gone->fresh()->status)->toBe('published');
});

test('autopublish=false : le produit reste en brouillon', function () {
    config(['shop.gelato_sync_autopublish' => false]);
    gcsFake([gcsDetail()]);

    app(GelatoSyncService::class)->syncStore();

    expect(Product::firstOrFail()->status)->toBe('draft');
});

test('un produit Gelato non prêt à publier reste en brouillon', function () {
    gcsFake([gcsDetail('sp-1', 'active', false)]);

    app(GelatoSyncService::class)->syncStore();

    expect(Product::firstOrFail()->status)->toBe('draft');
});

test('liste Gelato illisible : aucun retrait, aucun changement', function () {
    gcsFake([gcsDetail()]);
    $svc = app(GelatoSyncService::class);
    $svc->syncStore();

    Http::swap(new \Illuminate\Http\Client\Factory());
    Http::fake(['ecommerce.gelatoapis.com/*' => Http::response([], 500)]);
    $r = $svc->syncStore();

    expect($r['ok'])->toBeFalse()->and(Product::firstOrFail()->status)->toBe('published');
});

test('le bouton admin déclenche la synchro et affiche le résultat', function () {
    gcsFake([gcsDetail()]);
    $this->post('/admin/shop/products-sync-gelato')->assertRedirect(); // invité : refusé
    expect(Product::count())->toBe(0);

    config(['app.superadmin_email' => 'fondateur@example.com', 'shop.maintenance' => false]);
    $user = \App\Models\User::factory()->create(['email' => 'fondateur@example.com']);
    $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web'));

    $this->actingAs($user)->post('/admin/shop/products-sync-gelato')
        ->assertRedirect(route('admin.shop.products.index'))
        ->assertSessionHas('success');
    expect(Product::count())->toBe(1);

    $this->actingAs($user)->get('/admin/shop/products')->assertOk()->assertSee('Synchroniser maintenant');
});

test("l'image du produit est AUTO-HÉBERGÉE (/storage/ local), jamais l'URL Gelato signée qui expire", function () {
    gcsFake([gcsDetail()]); // previewUrl = https://img.test/p.png simule une URL S3 pré-signée éphémère

    app(GelatoSyncService::class)->syncStore();

    $p = Product::firstOrFail();
    expect($p->images)->toHaveCount(1)
        ->and($p->images[0])->toStartWith('/storage/shop/products/')
        ->and($p->images[0])->not->toContain('img.test'); // l'URL distante n'a PAS survécu

    // Le fichier a réellement été déposé sur le disque public (simulé).
    $rel = ltrim(str_replace('/storage/', '', $p->images[0]), '/');
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($rel);
});

test("aucune URL d'image DISTANTE ne survit à la synchro (produit ET variantes)", function () {
    $detail = gcsDetail();
    // Gelato fournit un mockup de variante (URL éphémère) pour la 1re variante noire.
    $detail['productImages'] = [
        ['fileUrl' => 'https://img.test/black-mockup.png', 'productVariantIds' => ['var-black-S'], 'isPrimary' => false],
    ];
    gcsFake([$detail]);

    app(GelatoSyncService::class)->syncStore();

    $p = Product::firstOrFail();
    $toutes = collect($p->images);
    foreach ($p->variants as $v) {
        $toutes = $toutes->merge($v['images'] ?? []);
    }
    expect($toutes)->not->toBeEmpty();
    foreach ($toutes as $img) {
        expect($img)->toStartWith('/storage/')
            ->and(str_starts_with($img, 'http'))->toBeFalse();
    }
});

test("une image distante héritée (URL Gelato expirée en base) est remplacée par une copie locale au --force", function () {
    // Produit préexistant portant encore l'ANCIENNE URL Gelato signée, désormais expirée (cas prod réel).
    $old = Product::create([
        'name' => 'T-shirt La veille.ai', 'slug' => 'tshirt-heritage',
        'price' => 20.0, 'category' => 't-shirts', 'status' => 'published',
        'images' => ['https://old.gelato/expired-signed.png'],
        'variants' => [],
        'gelato_store_product_id' => 'sp-1',
        'metadata' => ['gelato_store_product_id' => 'sp-1'],
    ]);

    gcsFake([gcsDetail('sp-1')]);
    app(GelatoSyncService::class)->syncStore(['force' => true]);

    $old->refresh();
    expect($old->images[0])->toStartWith('/storage/shop/products/')
        ->and($old->images[0])->not->toContain('old.gelato');
});

test("une image admin DISTANTE encore valide est PRÉSERVÉE (auto-hébergée), pas écrasée par le preview Gelato", function () {
    $admin = Product::create([
        'name' => 'T-shirt La veille.ai', 'slug' => 'tshirt-admin-img',
        'price' => 20.0, 'category' => 't-shirts', 'status' => 'published',
        'images' => ['https://img.test/custom-admin.png'], // image posée dans l'admin, encore valide
        'variants' => [],
        'gelato_store_product_id' => 'sp-1',
        'metadata' => ['gelato_store_product_id' => 'sp-1'],
    ]);

    gcsFake([gcsDetail('sp-1')]); // previewUrl Gelato = https://img.test/p.png
    app(GelatoSyncService::class)->syncStore(['force' => true]);

    $admin->refresh();
    // L'image admin a été localisée (dérivée de SON chemin), PAS remplacée par le preview Gelato (/p.png).
    expect($admin->images[0])->toBe('/storage/shop/products/'.sha1('/custom-admin.png').'.jpg');
});
