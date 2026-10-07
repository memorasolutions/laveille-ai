<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato\Editor;

/** Point unique de lecture du drapeau shop.gelato_editor (DRY). Exige AUSSI shop.gelato_zero_erreur. */
final class GelatoEditor
{
    /** Polices supportées par le moteur (graisse 400 seulement) => fichier TTF du module. */
    public const FONTS = [
        'Open Sans' => 'OpenSans.ttf',
        'Montserrat' => 'Montserrat.ttf',
        'Roboto' => 'Roboto.ttf',
        'Lora' => 'Lora.ttf',
        'Oswald' => 'Oswald.ttf',
    ];

    public static function enabled(): bool
    {
        return (bool) config('shop.gelato_editor', false) && \Modules\Shop\Gelato\ZeroErreur::enabled();
    }

    public static function fontPath(string $file): ?string
    {
        return in_array($file, self::FONTS, true) ? module_path('Shop', 'resources/fonts/'.$file) : null;
    }
}
