<?php

declare(strict_types=1);

namespace Modules\Signature\Http\Controllers;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Modules\Signature\Models\SignatureImage;

/**
 * Route publique de service d'image (section 11.3, 6.4, 6.7). AUCUN paramètre de requête variable
 * n'est jamais lu ici (section 6.7-b - sinon l'image devient elle-même un pixel de suivi) : la
 * seule entrée est l'identifiant PUBLIC (public_id, correctif B3 - jamais l'id auto-incrémenté
 * interne) dans le CHEMIN.
 */
class SignatureAssetController extends Controller
{
    public function show(string $publicId, string $ext): Response
    {
        $image = SignatureImage::with('signature')->where('public_id', $publicId)->first();

        if (! $image) {
            abort(404);
        }

        $cacheSeconds = (int) config('signature.asset_cache_seconds', 86400);

        // Après purge (path=null) : rectangle TRANSPARENT aux dimensions EXACTES de l'original
        // (jamais un visuel "expiré", jamais un pixel 1x1 générique) - section 6.4. Largeur/hauteur
        // restent en base après la purge du contenu (voir PurgeExpiredSignaturesCommand).
        if ($image->path === null || $image->isPurged()) {
            return $this->transparentPlaceholder($image->width ?? 1, $image->height ?? 1, $cacheSeconds);
        }

        $disk = (string) config('signature.disk', 'public');

        if (! Storage::disk($disk)->exists($image->path)) {
            return $this->transparentPlaceholder($image->width ?? 1, $image->height ?? 1, $cacheSeconds);
        }

        $this->recordLoad($image);

        $binary = Storage::disk($disk)->get($image->path);
        $mime = match (pathinfo($image->path, PATHINFO_EXTENSION)) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return response($binary, 200)
            ->header('Content-Type', $mime)
            ->header('Cache-Control', "public, max-age={$cacheSeconds}")
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * Écriture au plus UNE FOIS PAR JOUR (si la date du jour est déjà enregistrée, on ne réécrit
     * rien) - AUCUNE IP, AUCUN agent utilisateur, AUCUN compteur par destinataire (section 6.7-c).
     * Ce signal ne sert JAMAIS à des statistiques de lecture, seulement à retarder une purge
     * (Signature::isEligibleForPurge()).
     */
    private function recordLoad(SignatureImage $image): void
    {
        $signature = $image->signature;
        if (! $signature) {
            return;
        }

        $today = now()->toDateString();
        if ($signature->last_image_loaded_on?->toDateString() === $today) {
            return;
        }

        // Mineur : saveQuietly() (pas d'événements Eloquent inutiles sur une route à fort volume)
        // ET timestamps désactivés le temps de cette écriture - un chargement d'image par un
        // DESTINATAIRE ne doit jamais faire bouger updated_at, qui sert d'indicateur "Modifiée le"
        // dans « Mes signatures » et doit rester réservé aux actions du PROPRIÉTAIRE.
        $signature->timestamps = false;
        $signature->forceFill(['last_image_loaded_on' => $today])->saveQuietly();
        $signature->timestamps = true;
    }

    private function transparentPlaceholder(int $width, int $height, int $cacheSeconds): Response
    {
        $width = max(1, min(4096, $width));
        $height = max(1, min(4096, $height));

        try {
            $manager = new ImageManager(new ImagickDriver());
        } catch (Exception) {
            $manager = new ImageManager(new GdDriver());
        }

        $png = (string) $manager->create($width, $height)->toPng();

        return response($png, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', "public, max-age={$cacheSeconds}")
            ->header('Referrer-Policy', 'no-referrer');
    }
}
