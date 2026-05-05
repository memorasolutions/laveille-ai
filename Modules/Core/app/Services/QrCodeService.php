<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * 2026-05-05 #97 Phase 2 : service QR partagé (lib bacon/bacon-qr-code 3.0.3).
 * API : generate(url, options) -> binary PNG. Logo via GD post-render. Validation contraste WCAG 4.5:1.
 * Réutilisable par : Modules/Tools (mots-croisés), Modules/ShortUrl (raccourcir), futurs modules.
 */

namespace Modules\Core\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Module\DotsModule;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\Module\SquareModule;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use InvalidArgumentException;

class QrCodeService
{
    /**
     * Génère un QR code PNG binaire avec options de personnalisation.
     *
     * @throws InvalidArgumentException Si contraste fg/bg < 4.5:1 (WCAG AA min).
     */
    public function generate(string $content, array $options = []): string
    {
        $opts = array_merge($this->defaultOptions(), $options);

        // Clamp valeurs numériques (sécurité utilisateur).
        $size = max(120, min(1000, (int) $opts['size']));
        $margin = max(0, min(10, (int) $opts['margin']));

        // Validation contraste WCAG 4.5:1 minimum (sinon QR illisible scanners).
        $ratio = $this->wcagContrastRatio($opts['foreground'], $opts['background']);
        if ($ratio < 4.5) {
            throw new InvalidArgumentException(
                sprintf('QR contrast %.2f:1 below WCAG 4.5:1 threshold (foreground=%s, background=%s)',
                    $ratio, $opts['foreground'], $opts['background'])
            );
        }

        // Force ECC Q (25%) si logo activé pour compenser la perte de modules sous le logo.
        $ecc = $opts['logo_path'] ? 'Q' : (string) $opts['ecc'];
        $ecc = in_array($ecc, ['L', 'M', 'Q', 'H'], true) ? $ecc : 'M';

        // Module style : Imagick + Roundness compatibles via bacon 3.x.
        $module = match ($opts['dot_style']) {
            'rounded' => new RoundnessModule(RoundnessModule::MEDIUM),
            'dots' => new DotsModule(DotsModule::MEDIUM),
            default => SquareModule::instance(),
        };

        $fgRgb = $this->hexToRgb($opts['foreground']);
        $bgRgb = $this->hexToRgb($opts['background']);

        $rendererStyle = new RendererStyle(
            $size,
            $margin,
            $module,
            null, // eyeModule (default = same as module)
            Fill::uniformColor(
                new Rgb($bgRgb[0], $bgRgb[1], $bgRgb[2]),
                new Rgb($fgRgb[0], $fgRgb[1], $fgRgb[2])
            )
        );

        $renderer = new ImageRenderer($rendererStyle, new ImagickImageBackEnd('png'));
        $writer = new Writer($renderer);

        // bacon 3.x : writeString(content, encoding, eccLevel)
        $eccConst = match ($ecc) {
            'L' => \BaconQrCode\Common\ErrorCorrectionLevel::valueOf('L'),
            'M' => \BaconQrCode\Common\ErrorCorrectionLevel::valueOf('M'),
            'Q' => \BaconQrCode\Common\ErrorCorrectionLevel::valueOf('Q'),
            'H' => \BaconQrCode\Common\ErrorCorrectionLevel::valueOf('H'),
        };
        $png = $writer->writeString($content, 'utf-8', $eccConst);

        // Embed logo via GD post-render (bacon ne supporte pas natif).
        if ($opts['logo_path'] && is_file($opts['logo_path'])) {
            $png = $this->embedLogo($png, $opts['logo_path'], $size);
        }

        return $png;
    }

    /**
     * Options par défaut (cohérent charte Memora teal).
     */
    public function defaultOptions(): array
    {
        return [
            'size' => 400,
            'margin' => 4,
            'foreground' => '#0B7285',
            'background' => '#FFFFFF',
            'ecc' => 'M',
            'logo_path' => null,
            'dot_style' => 'square', // square|rounded|dots
        ];
    }

    /**
     * 4 presets visuels charte Memora pour UI.
     */
    public function presets(): array
    {
        return [
            'teal' => ['foreground' => '#0B7285', 'background' => '#FFFFFF', 'label' => 'Teal'],
            'dark' => ['foreground' => '#1A1D23', 'background' => '#FFFFFF', 'label' => 'Noir'],
            'accent' => ['foreground' => '#C2410C', 'background' => '#FFFFFF', 'label' => 'Orange'],
            'inverse' => ['foreground' => '#FFFFFF', 'background' => '#064E5C', 'label' => 'Inversé'],
        ];
    }

    /**
     * Calcule ratio de contraste WCAG 2.1 entre 2 couleurs hex.
     */
    public function wcagContrastRatio(string $hex1, string $hex2): float
    {
        $l1 = $this->relativeLuminance($this->hexToRgb($hex1));
        $l2 = $this->relativeLuminance($this->hexToRgb($hex2));
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Parse hex #RRGGBB ou #RGB → [r, g, b] entiers 0-255.
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            throw new InvalidArgumentException("Invalid hex color: #{$hex}");
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /**
     * Luminance relative WCAG 2.1 (sRGB linearization).
     */
    private function relativeLuminance(array $rgb): float
    {
        $linearize = function (int $c): float {
            $s = $c / 255.0;
            return $s <= 0.03928 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $linearize($rgb[0]) + 0.7152 * $linearize($rgb[1]) + 0.0722 * $linearize($rgb[2]);
    }

    /**
     * Embed logo au centre du QR (18% taille, marge blanche 4px pour scannabilité).
     * Utilise GD natif (Imagick aurait été plus propre mais GD plus universel).
     */
    private function embedLogo(string $qrPng, string $logoPath, int $qrSize): string
    {
        $qr = @imagecreatefromstring($qrPng);
        if (! $qr) {
            return $qrPng;
        }

        // Charger logo (GD auto-détecte format PNG/JPG/GIF/WebP).
        $logo = @imagecreatefromstring((string) @file_get_contents($logoPath));
        if (! $logo) {
            imagedestroy($qr);
            return $qrPng;
        }

        $logoSize = (int) ($qrSize * 0.18);
        $padding = 6; // marge blanche autour du logo (4-8 px recommandé scanners).

        // Resize logo avec alpha preservation.
        $resized = imagecreatetruecolor($logoSize, $logoSize);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $logoSize, $logoSize, $transparent);
        imagecopyresampled($resized, $logo, 0, 0, 0, 0, $logoSize, $logoSize, imagesx($logo), imagesy($logo));
        imagedestroy($logo);

        // Marge blanche carrée (lisibilité scanner).
        $whiteSize = $logoSize + 2 * $padding;
        $cx = (int) ((imagesx($qr) - $whiteSize) / 2);
        $cy = (int) ((imagesy($qr) - $whiteSize) / 2);
        $white = imagecolorallocate($qr, 255, 255, 255);
        imagefilledrectangle($qr, $cx, $cy, $cx + $whiteSize, $cy + $whiteSize, $white);

        // Coller logo centré.
        imagealphablending($qr, true);
        imagecopy($qr, $resized, $cx + $padding, $cy + $padding, 0, 0, $logoSize, $logoSize);
        imagedestroy($resized);

        // Output PNG binaire.
        ob_start();
        imagepng($qr);
        $out = (string) ob_get_clean();
        imagedestroy($qr);

        return $out !== '' ? $out : $qrPng;
    }
}
