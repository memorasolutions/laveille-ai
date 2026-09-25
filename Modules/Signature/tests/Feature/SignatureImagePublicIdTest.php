<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * B3 - images énumérables par id séquentiel : l'URL publique doit porter un identifiant NON
 * séquentiel (public_id, 32 caractères hex CSPRNG), jamais l'id auto-incrémenté interne. Une
 * vieille URL numérique doit cesser de répondre (404), pas être silencieusement acceptée.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Signature\Models\Signature;
use Modules\Signature\Models\SignatureImage;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
});

function sigForPublicIdTest(): Signature
{
    $sig = new Signature();
    $sig->template = 'minimal';
    $sig->content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $sig->admin_token_hash = hash('sha256', 'jeton-public-id-'.uniqid());
    $sig->status = Signature::STATUS_ACTIVE;
    $sig->save();

    return $sig;
}

test('deux images ont des public_id distincts, non séquentiels et non devinables à partir de leur id interne', function (): void {
    $sig = sigForPublicIdTest();

    $image1 = SignatureImage::create([
        'signature_id' => $sig->id, 'role' => SignatureImage::ROLE_LOGO,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/logo-1.png', 'width' => 96, 'height' => 40,
    ]);
    $image2 = SignatureImage::create([
        'signature_id' => $sig->id, 'role' => SignatureImage::ROLE_PORTRAIT,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/portrait-1.png', 'width' => 80, 'height' => 80,
    ]);

    expect($image1->public_id)->not->toBeNull()
        ->and($image2->public_id)->not->toBeNull()
        ->and($image1->public_id)->not->toBe($image2->public_id)
        // 32 caractères hexadécimaux (bin2hex(random_bytes(16))) - jamais l'id interne en clair,
        // ni la même valeur zéro-paddée qui serait triviale à deviner à partir de l'id.
        ->and($image1->public_id)->toMatch('/^[0-9a-f]{32}$/')
        ->and($image2->public_id)->toMatch('/^[0-9a-f]{32}$/')
        ->and($image1->public_id)->not->toBe((string) $image1->id)
        ->and($image1->public_id)->not->toBe(str_pad((string) $image1->id, 32, '0', STR_PAD_LEFT));
});

test('l\'URL publique porte le public_id, jamais l\'id auto-incrémenté interne', function (): void {
    $sig = sigForPublicIdTest();
    $image = SignatureImage::create([
        'signature_id' => $sig->id, 'role' => SignatureImage::ROLE_LOGO,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/logo-x.png', 'width' => 96, 'height' => 40,
    ]);

    $url = $image->toAssetPayload()['url'];

    expect($url)->toContain($image->public_id)
        ->and($url)->not->toContain('/signature-assets/'.$image->id.'.');
});

test('l\'ancienne forme numérique de l\'URL (/signature-assets/{id}.png) répond 404, jamais l\'image', function (): void {
    $sig = sigForPublicIdTest();
    $image = SignatureImage::create([
        'signature_id' => $sig->id, 'role' => SignatureImage::ROLE_LOGO,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/logo-y.png', 'width' => 96, 'height' => 40,
    ]);
    Storage::disk('public')->put($image->path, 'contenu');

    // L'ancienne route acceptait un id numérique brut - avec le motif public_id ([0-9a-f]{32}),
    // ce chemin ne matche plus DU TOUT la route (id trop court, jamais 32 caractères hex), donc
    // Laravel répond 404 avant même d'atteindre le contrôleur.
    $this->get('/signature-assets/'.$image->id.'.png')->assertNotFound();
});

test('un public_id inconnu répond 404 (rectangle transparent réservé aux images purgées existantes)', function (): void {
    $this->get('/signature-assets/'.str_repeat('0', 32).'.png')->assertNotFound();
});
