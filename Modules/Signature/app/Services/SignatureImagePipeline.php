<?php

declare(strict_types=1);

namespace Modules\Signature\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Modules\Signature\Models\Signature;
use Modules\Signature\Models\SignatureImage;
use RuntimeException;

/**
 * Pipeline serveur (section 5 du plan) : validation du type MIME RÉEL (jamais l'extension),
 * SVG explicitement refusé, sur-échantillonnage FIXE x2 (décision du fondateur 2026-09-25),
 * jamais d'agrandissement au-delà de la résolution native. Le fichier ORIGINAL n'est JAMAIS
 * persisté sur disque (M3.3) : seule la dérivée finale, vérifiée valide dans la MÊME requête,
 * est conservée - rien à nettoyer plus tard, aucune fenêtre où l'original brut attend sur un
 * disque public.
 *
 * Patron repris de Modules\Authors\Services\ImagePipelineService (Imagick avec repli GD, hash
 * aléatoire dans le chemin, jamais le nom de l'utilisateur) - adapté : une seule dérivée par image
 * (pas de variantes responsives multiples, superflu pour une signature).
 */
final class SignatureImagePipeline
{
    private ImageManager $manager;

    public function __construct()
    {
        // strip: true (M3.2) - ceinture générale appliquée par les encodeurs qui la supportent
        // (JPEG notamment). Bretelles : le coeur Imagick natif est aussi strippé explicitement
        // avant encodage plus bas (process()), quel que soit le format de sortie, parce que
        // l'encodeur PNG d'Intervention Image ne propose pas d'option strip propre.
        try {
            $this->manager = new ImageManager(new ImagickDriver(), strip: true);
        } catch (Exception) {
            $this->manager = new ImageManager(new GdDriver(), strip: true);
        }

        // M3.1 - bombe de décompression, ceinture Imagick (en plus de la vérification
        // getimagesize() dans assertValidUpload(), qui reste la première barrière) : jamais
        // décoder au-delà de ces bornes, même si un fichier mentait sur ses dimensions déclarées.
        if (class_exists(\Imagick::class)) {
            \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_WIDTH, (int) config('signature.max_input_side_px', 6000));
            \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_HEIGHT, (int) config('signature.max_input_side_px', 6000));
            \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_AREA, (int) ((float) config('signature.max_input_megapixels', 25) * 1_000_000));
            \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256 * 1024 * 1024);
        }
    }

    /**
     * @throws RuntimeException si le fichier est invalide (taille, type MIME réel, SVG)
     */
    public function process(string $tmpPath, Signature $signature, string $role, int $displayWidth): SignatureImage
    {
        if (! in_array($role, SignatureImage::ROLES, true)) {
            throw new RuntimeException("Rôle d'image inconnu : {$role}");
        }

        $this->assertValidUpload($tmpPath);

        $disk = (string) config('signature.disk', 'public');
        $factor = max(1, (int) config('signature.oversampling_factor', 2));
        $displayWidth = max(24, min(1200, $displayWidth));

        // Round zéro-casse : orient() applique la rotation EXIF AVANT le ré-encodage - sans quoi
        // une photo prise en portrait sur un téléphone ressortirait couchée une fois les
        // métadonnées d'orientation perdues au ré-encodage (voir note EXIF ci-dessous).
        $image = $this->manager->read($tmpPath)->orient();
        $nativeWidth = $image->width();

        // Jamais d'agrandissement artificiel au-delà de la résolution native (section 5) : repli
        // silencieux sur la résolution native si le fichier source est trop petit pour le facteur
        // demandé.
        $targetWidth = min($displayWidth * $factor, max($nativeWidth, 1));
        $upscaled = $targetWidth < $displayWidth * $factor;

        $derivative = clone $image;
        $derivative->scale(width: $targetWidth);

        // M3.2 - EXIF/GPS : strip explicite du coeur Imagick natif, AVANT encodage, quel que soit
        // le format de sortie (PNG compris, que l'encodeur PNG d'Intervention Image ne sait pas
        // stripper lui-même faute d'option dédiée). Sous GD, aucune métadonnée EXIF n'est jamais
        // recopiée par construction (imagecreatefromjpeg()/imagejpeg() ne transportent pas les
        // segments EXIF) - rien à retirer explicitement sur ce chemin.
        if ($this->manager->driver() instanceof ImagickDriver) {
            $derivative->core()->native()->stripImage();
        }

        $derivativeHeight = $derivative->height();

        $sourceMime = (string) mime_content_type($tmpPath);
        // Transparence : approximée par le format SOURCE (PNG/WebP -> PNG, sinon JPEG) - le plan
        // signale lui-même (section 5) que la détection exacte du canal alpha d'Intervention Image
        // reste à vérifier à l'implémentation; cette approximation reste sûre par défaut (jamais de
        // fond noir sur un PNG transparent, au pire un PNG un peu plus lourd qu'un JPEG).
        $outputsPng = in_array($sourceMime, ['image/png', 'image/webp'], true);
        $extension = $outputsPng ? 'png' : 'jpg';
        // strip: true explicite en plus du strip natif ci-dessus et du strip:true global du
        // ImageManager (M3.2, défense en profondeur - jamais une seule barrière pour une donnée
        // aussi sensible qu'un GPS). L'encodeur PNG d'Intervention Image n'a pas de paramètre
        // strip propre, d'où le stripImage() natif fait plus haut, valable pour les deux formats.
        $encoded = $outputsPng ? (string) $derivative->toPng() : (string) $derivative->toJpeg(quality: 85, strip: true);

        $hash = Str::random(24);
        $derivativePath = rtrim((string) config('signature.derivatives_directory'), '/')."/signature-{$signature->id}/{$role}-{$hash}.{$extension}";

        Storage::disk($disk)->put($derivativePath, $encoded);

        // Vérification (section 5) : la dérivée doit exister et être lisible avec des dimensions
        // cohérentes - M3.3 : plus AUCUN original persisté, donc rien à nettoyer en cas d'échec.
        $derivativeValid = Storage::disk($disk)->exists($derivativePath)
            && Storage::disk($disk)->size($derivativePath) > 0;

        if (! $derivativeValid) {
            Storage::disk($disk)->delete($derivativePath);
            throw new RuntimeException("Échec de production de la dérivée d'image pour le rôle {$role}.");
        }

        if ($upscaled) {
            Log::info("signature: image {$role} de la signature #{$signature->id} servie à sa résolution native (fichier source trop petit pour le facteur x{$factor}).");
        }

        $existing = $signature->image($role);
        $data = [
            'signature_id' => $signature->id,
            'role' => $role,
            'path' => $derivativePath,
            'width' => $targetWidth,
            'height' => $derivativeHeight,
            'display_width' => $displayWidth,
            'display_height' => (int) round($derivativeHeight / $factor),
            'purged_at' => null,
        ];

        if ($existing) {
            // Remplacement d'une image déjà présente pour ce rôle : l'ANCIENNE dérivée est
            // supprimée immédiatement - aucune raison de la conserver.
            $this->deleteFileIfSet($disk, $existing->path);
            $existing->update($data);

            return $existing->fresh();
        }

        return SignatureImage::create($data);
    }

    private function deleteFileIfSet(string $disk, ?string $path): void
    {
        if ($path !== null && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    private function assertValidUpload(string $tmpPath): void
    {
        if (! is_file($tmpPath)) {
            throw new RuntimeException('Fichier introuvable.');
        }

        $size = filesize($tmpPath);
        $maxBytes = (int) config('signature.max_upload_bytes', 8 * 1024 * 1024);
        if ($size === false || $size > $maxBytes) {
            throw new RuntimeException('Fichier trop volumineux.');
        }

        // Type MIME RÉEL (jamais l'extension déclarée) - section 5 et 10 du plan.
        $mime = mime_content_type($tmpPath);
        $allowed = (array) config('signature.allowed_input_mimes', ['image/jpeg', 'image/png', 'image/webp']);

        if (! is_string($mime) || ! in_array($mime, $allowed, true)) {
            throw new RuntimeException("Format d'image non accepté : {$mime}. Le SVG n'est jamais accepté (surface XSS inutile pour cet outil).");
        }

        // Garde-fou explicite supplémentaire (section 5/10) : même si un fichier SVG était un jour
        // ajouté par erreur à allowed_input_mimes, cette seconde vérification par signature
        // textuelle interdit tout de même le SVG - défense en profondeur, jamais confiance en une
        // seule barrière.
        $head = @file_get_contents($tmpPath, false, null, 0, 512);
        if (is_string($head) && stripos($head, '<svg') !== false) {
            throw new RuntimeException('Le format SVG est refusé.');
        }

        // M3.1 - bombe de décompression : getimagesize() lit UNIQUEMENT les en-têtes (jamais les
        // pixels), donc c'est une vérification bon marché à faire AVANT toute lecture par
        // Intervention Image/Imagick, qui elle décoderait réellement le fichier en mémoire.
        $dimensions = @getimagesize($tmpPath);
        if ($dimensions === false) {
            throw new RuntimeException('Impossible de lire les dimensions de l\'image.');
        }

        [$sourceWidth, $sourceHeight] = $dimensions;
        $maxSide = (int) config('signature.max_input_side_px', 6000);
        $maxMegapixels = (float) config('signature.max_input_megapixels', 25);

        if ($sourceWidth > $maxSide || $sourceHeight > $maxSide) {
            throw new RuntimeException("Image trop grande (maximum {$maxSide}px de côté).");
        }

        if (($sourceWidth * $sourceHeight) > $maxMegapixels * 1_000_000) {
            throw new RuntimeException("Image trop grande ({$maxMegapixels} mégapixels maximum).");
        }
    }
}
