<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Modules\Shop\Gelato\PrintFile;
use Modules\Shop\Gelato\PrintFileService;
use Modules\Shop\Gelato\PrintFileStatus;
use Modules\Shop\Models\Product;

uses(Tests\TestCase::class, RefreshDatabase::class);

const GE_UID = 'apparel_product_gca_t-shirt_gsc_crewneck_gcu_unisex_gqa_classic_gsi_m_gco_white_gpr_4-0';

function geBase(): Product
{
    return Product::create([
        'name' => 'T-shirt', 'slug' => 'ts-'.uniqid(), 'price' => 30, 'status' => 'published',
        'variants' => [['label' => 'M blanc', 'gelato_uid' => GE_UID]],
    ]);
}

function geEnableEditor(): void
{
    config([
        'shop.maintenance' => false, 'shop.gelato_zero_erreur' => true, 'shop.gelato_editor' => true,
        'shop.print_prep.url' => 'http://prep.test', 'shop.print_prep.secret' => 'sek',
    ]);
    // Les routes se déclarent au boot : on les (re)charge après avoir levé le drapeau.
    require module_path('Shop', 'routes/editor.php');
    app('router')->getRoutes()->refreshNameLookups();
}

function geSpec(array $over = []): array
{
    return ['elements' => [array_merge([
        'type' => 'text', 'content' => 'Bonjour', 'fontFamily' => 'Roboto', 'fontSizePt' => 48,
        'colorHex' => '#111111', 'xMm' => 40, 'yMm' => 60, 'maxWidthMm' => 120,
    ], $over)]];
}

function geRendered(string $hash = 'h1'): array
{
    return [
        'status' => 'rendered', 'contentHash' => $hash, 'publicUrl' => "https://cdn.test/pf/{$hash}.png",
        'specHash' => 's', 'validation' => ['ok' => true, 'effectiveDpi' => 300],
        'mockupPreviewPath' => "/srv/pf/{$hash}.preview.png", 'elements' => [],
    ];
}

test('drapeau OFF : aucune route de l\'éditeur', function () {
    config(['shop.gelato_zero_erreur' => true, 'shop.gelato_editor' => false]);
    $base = geBase();

    expect(Route::has('shop.editor.render'))->toBeFalse();
    $this->postJson("/boutique/editeur/{$base->slug}/rendu", ['spec' => geSpec()])->assertNotFound();
    $this->postJson('/boutique/editeur/televerser')->assertNotFound();
});

test('upload image : le moteur renvoie l assetHash, secret en en-tête, SVG refusé', function () {
    geEnableEditor();
    Http::fake(['prep.test/v1/assets' => Http::response(['assetHash' => 'abcdef1234567890', 'format' => 'png', 'width' => 10, 'height' => 10], 200)]);

    $this->postJson('/boutique/editeur/televerser', ['image' => UploadedFile::fake()->image('a.png', 10, 10)])
        ->assertOk()->assertJson(['assetHash' => 'abcdef1234567890']);
    Http::assertSent(fn ($r) => $r->url() === 'http://prep.test/v1/assets' && $r->hasHeader('X-Gelato-Prep-Secret', 'sek'));

    $this->postJson('/boutique/editeur/televerser', ['image' => UploadedFile::fake()->create('x.svg', 1, 'image/svg+xml')])->assertStatus(422);
});

test('spec valide : MOCKUP_READY + mockup renvoyé, puis l approbation rend le produit commandable', function () {
    geEnableEditor();
    Http::fake(['prep.test/v1/render-spec' => Http::response(geRendered(), 200)]);
    $base = geBase();

    $res = $this->postJson("/boutique/editeur/{$base->slug}/rendu", ['variant_uid' => GE_UID, 'spec' => geSpec()])->assertOk();
    $res->assertJson(['status' => 'MOCKUP_READY', 'content_hash' => 'h1', 'mockup_url' => 'https://cdn.test/pf/h1.preview.png']);

    $designId = $res->json('design_id');
    $file = PrintFile::where('product_id', $designId)->firstOrFail();
    expect($file->status)->toBe(PrintFileStatus::MockupReady)->and($file->product_uid)->toBe(GE_UID);
    // Pas commandable avant approbation
    expect(fn () => app(PrintFileService::class)->assertOrderable($designId, GE_UID, GE_UID))
        ->toThrow(\Modules\Shop\Gelato\PrintFileNotApprovedException::class);

    // La spec envoyée au moteur est reconstruite côté serveur (productUid vérifié).
    Http::assertSent(fn ($r) => str_contains($r->url(), '/render-spec') && $r['productUid'] === GE_UID && $r['elements'][0]['colorHex'] === '#111111');

    $this->postJson('/boutique/editeur/approuver', ['design_id' => $designId, 'content_hash' => 'h1'])
        ->assertOk()->assertJson(['status' => 'APPROVED']);
    expect(app(PrintFileService::class)->assertOrderable($designId, GE_UID, GE_UID)->public_url)->toBe('https://cdn.test/pf/h1.png');
    expect(Product::find($designId)->status)->toBe('draft');
});

test('spec hors zone : refus 422 du moteur propagé, aucun PrintFile ni produit de conception', function () {
    geEnableEditor();
    Http::fake(['prep.test/v1/render-spec' => Http::response(['code' => 'HORS_ZONE_SECURITE', 'message' => 'Élément #0 hors zone de sécurité.'], 422)]);
    $base = geBase();
    $before = Product::count();

    $this->postJson("/boutique/editeur/{$base->slug}/rendu", ['variant_uid' => GE_UID, 'spec' => geSpec(['xMm' => 1])])
        ->assertStatus(422)->assertJsonPath('error.code', 'HORS_ZONE_SECURITE');

    expect(PrintFile::count())->toBe(0)->and(Product::count())->toBe($before);
});

test('entrées invalides : police inconnue, variante étrangère, design d un autre visiteur', function () {
    geEnableEditor();
    Http::fake(['prep.test/v1/render-spec' => Http::response(geRendered(), 200)]);
    $base = geBase();

    $this->postJson("/boutique/editeur/{$base->slug}/rendu", ['variant_uid' => GE_UID, 'spec' => geSpec(['fontFamily' => 'Comic Sans'])])->assertStatus(422);
    $this->postJson("/boutique/editeur/{$base->slug}/rendu", ['variant_uid' => 'autre_uid', 'spec' => geSpec()])->assertStatus(422);
    Http::assertNothingSent();

    $other = Product::create(['name' => 'x', 'slug' => 'perso-x', 'price' => 1, 'status' => 'draft']);
    $this->postJson('/boutique/editeur/approuver', ['design_id' => $other->id, 'content_hash' => 'h1'])->assertForbidden();
});

test('moteur en panne : 502 générique, pas de fuite de détail', function () {
    geEnableEditor();
    Http::fake(['prep.test/v1/render-spec' => fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error secret-host')]);
    $base = geBase();

    $res = $this->postJson("/boutique/editeur/{$base->slug}/rendu", ['variant_uid' => GE_UID, 'spec' => geSpec()])->assertStatus(502);
    expect($res->getContent())->not->toContain('secret-host');
});

test('page éditeur : Fabric depuis cdnjs, polices TTF du serveur, zone de sécurité', function () {
    geEnableEditor();
    $base = geBase();

    $this->get("/boutique/editeur/{$base->slug}")->assertOk()
        ->assertSee('cdnjs.cloudflare.com/ajax/libs/fabric.js', false)
        ->assertSee('/boutique/editeur/polices/Roboto.ttf', false)
        ->assertSee('ged-safe', false);
    $this->get('/boutique/editeur/polices/Roboto.ttf')->assertOk();
    $this->get('/boutique/editeur/polices/passwd')->assertNotFound();
});
