<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Section 6.7 du plan : la route publique d'image enregistre UNIQUEMENT la date du dernier
 * chargement, au jour près, sans IP ni agent utilisateur, jamais par message/destinataire.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Signature\Models\Signature;
use Modules\Signature\Models\SignatureImage;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
});

test('aucune colonne IP ni agent utilisateur n\'existe sur la table signatures - la structure interdit ce signal par construction', function (): void {
    expect(Schema::hasColumn('signatures', 'ip_address'))->toBeFalse()
        ->and(Schema::hasColumn('signatures', 'user_agent'))->toBeFalse()
        ->and(Schema::hasColumn('signatures', 'last_image_loaded_on'))->toBeTrue();
});

test('le chargement de l\'image met à jour last_image_loaded_on à la date du jour', function (): void {
    $sig = new Signature();
    $sig->template = 'minimal';
    $sig->content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $sig->admin_token_hash = hash('sha256', 'jeton-chargement');
    $sig->status = Signature::STATUS_ACTIVE;
    $sig->save();

    $image = SignatureImage::create([
        'signature_id' => $sig->id,
        'role' => SignatureImage::ROLE_LOGO,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/logo-x.png',
        'width' => 96, 'height' => 40,
    ]);
    Storage::disk('public')->put($image->path, 'contenu');

    expect($sig->last_image_loaded_on)->toBeNull();

    $this->get(route('signature.asset', ['public_id' => $image->public_id, 'ext' => 'png']))->assertOk();

    expect($sig->fresh()->last_image_loaded_on->toDateString())->toBe(now()->toDateString());
});

test('la route d\'image accepte AUCUN paramètre de requête variable (URL nue uniquement) et son cache reste court', function (): void {
    $sig = new Signature();
    $sig->template = 'minimal';
    $sig->content = ['first_name' => 'M', 'last_name' => 'T', 'email' => 'm@t.com'];
    $sig->admin_token_hash = hash('sha256', 'jeton-cache');
    $sig->status = Signature::STATUS_ACTIVE;
    $sig->save();

    $image = SignatureImage::create([
        'signature_id' => $sig->id, 'role' => SignatureImage::ROLE_LOGO,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/logo-y.png',
        'width' => 96, 'height' => 40,
    ]);
    Storage::disk('public')->put($image->path, 'contenu');

    // Un paramètre de requête ajouté "par erreur" ne doit JAMAIS influencer la réponse (section
    // 6.7-b) : la route ignore tout ce qui n'est pas l'identifiant dans le CHEMIN.
    $response = $this->get(route('signature.asset', ['public_id' => $image->public_id, 'ext' => 'png']).'?utm_source=courriel-123');

    $response->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('max-age=86400');
});

test('mineur - le chargement d\'une image par un destinataire ne bouge JAMAIS updated_at (réservé aux actions du propriétaire)', function (): void {
    $sig = new Signature();
    $sig->template = 'minimal';
    $sig->content = ['first_name' => 'M', 'last_name' => 'T', 'email' => 'm@t.com'];
    $sig->admin_token_hash = hash('sha256', 'jeton-updated-at');
    $sig->status = Signature::STATUS_ACTIVE;
    $sig->save();

    $image = SignatureImage::create([
        'signature_id' => $sig->id, 'role' => SignatureImage::ROLE_LOGO,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/logo-z.png',
        'width' => 96, 'height' => 40,
    ]);
    Storage::disk('public')->put($image->path, 'contenu');

    $updatedAtBefore = $sig->fresh()->updated_at;

    $this->travel(1)->hours();
    $this->get(route('signature.asset', ['public_id' => $image->public_id, 'ext' => 'png']))->assertOk();
    $this->travelBack();

    $fresh = $sig->fresh();
    expect($fresh->last_image_loaded_on->toDateString())->toBe(now()->toDateString())
        ->and($fresh->updated_at->equalTo($updatedAtBefore))->toBeTrue();
});
