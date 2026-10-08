<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato\Editor;

/**
 * DOCUMENTÉ, NON CORRIGÉ (revue fable 2026-10-08) : M1 / M2 - le prix de l'éditeur personnalisé n'est pas finalisé
 * (dérivation du prix d'une variante personnalisée, cohérence panier/devis). Le risque est confiné : il faut DEUX drapeaux
 * OFF (shop.gelato_editor ET shop.gelato_zero_erreur). Ne pas activer l'éditeur en production avant décision et re-gate.
 */
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
