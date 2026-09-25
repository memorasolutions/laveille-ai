<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * M3.1 (bombe de décompression) et M3.2 (EXIF/GPS survit dans la dérivée publique). Fixtures
 * réelles dans tests/Feature/fixtures/ - un fichier JPEG déclarant 7000x7000px (M3.1, généré par
 * ImageMagick avec une seule couleur pleine, ~190 Ko malgré ses dimensions déclarées) et un JPEG
 * portant de vraies coordonnées GPS lisibles par exif_read_data() (M3.2).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Signature\Models\Signature;
use Modules\Signature\Models\SignatureImage;
use Modules\Signature\Services\SignatureImagePipeline;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
});

function sigPipelineSecuritySignature(): Signature
{
    $sig = new Signature();
    $sig->template = 'minimal';
    $sig->content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $sig->admin_token_hash = hash('sha256', 'jeton-pipeline-'.uniqid());
    $sig->status = Signature::STATUS_ACTIVE;
    $sig->save();

    return $sig;
}

test('M3.1 - une image dépassant les bornes de dimensions (côté ou mégapixels) est refusée AVANT tout décodage', function (): void {
    $sig = sigPipelineSecuritySignature();
    $fixture = __DIR__.'/fixtures/oversized-sample.jpg';
    expect(is_file($fixture))->toBeTrue();

    // 7000x7000 = 49 Mpx, largement au-dessus des bornes par défaut (6000px de côté, 25 Mpx).
    $dimensions = getimagesize($fixture);
    expect($dimensions[0])->toBeGreaterThan((int) config('signature.max_input_side_px', 6000));

    expect(fn () => (new SignatureImagePipeline())->process($fixture, $sig, SignatureImage::ROLE_LOGO, 96))
        ->toThrow(RuntimeException::class);

    expect($sig->images()->count())->toBe(0);
});

test('M3.1 - une image dans les bornes est traitée normalement (le garde-fou ne bloque pas un usage légitime)', function (): void {
    $sig = sigPipelineSecuritySignature();
    $file = UploadedFile::fake()->image('logo-normal.png', 400, 200);

    $image = (new SignatureImagePipeline())->process($file->getRealPath(), $sig, SignatureImage::ROLE_LOGO, 96);

    expect($image->path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($image->path))->toBeTrue();
});

test('M3.2 - les métadonnées EXIF/GPS d\'un JPEG source ne survivent PAS dans la dérivée publique', function (): void {
    $sig = sigPipelineSecuritySignature();
    $fixture = __DIR__.'/fixtures/exif-gps-sample.jpg';
    expect(is_file($fixture))->toBeTrue();

    // Le fixture porte réellement des coordonnées GPS lisibles - preuve que le test n'est pas
    // vide (une fixture sans EXIF ferait "passer" le test sans rien prouver).
    $sourceExif = @exif_read_data($fixture);
    expect($sourceExif)->not->toBeFalse()
        ->and($sourceExif)->toHaveKey('GPSLatitude');

    $image = (new SignatureImagePipeline())->process($fixture, $sig, SignatureImage::ROLE_PORTRAIT, 80);

    $derivativeContent = Storage::disk('public')->get($image->path);
    $tmpDerivative = tempnam(sys_get_temp_dir(), 'sig-derivative-').'.jpg';
    file_put_contents($tmpDerivative, $derivativeContent);

    $derivativeExif = @exif_read_data($tmpDerivative);
    @unlink($tmpDerivative);

    // exif_read_data() renvoie soit `false` (aucun segment EXIF du tout), soit un tableau SANS la
    // clé GPS - les deux prouvent l'absence de métadonnées de localisation. Ce qui échouerait le
    // test : la présence de GPSLatitude dans le résultat.
    $stillHasGps = is_array($derivativeExif) && array_key_exists('GPSLatitude', $derivativeExif);
    expect($stillHasGps)->toBeFalse();
});

test('M3.3 - aucun fichier original n\'est jamais persisté, seule la dérivée existe sur le disque', function (): void {
    $sig = sigPipelineSecuritySignature();
    $file = UploadedFile::fake()->image('logo.png', 400, 200);

    (new SignatureImagePipeline())->process($file->getRealPath(), $sig, SignatureImage::ROLE_LOGO, 96);

    $allFiles = Storage::disk('public')->allFiles();
    expect($allFiles)->toHaveCount(1)
        ->and($allFiles[0])->toContain('signature-derivatives')
        ->and($allFiles[0])->not->toContain('original');
});
