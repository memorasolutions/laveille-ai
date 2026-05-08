<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Helpers Dictionary (DRY) — résout le hero_image avec fallback intelligent
 * pour éviter les images cassées quand un format (.webp, .jpg) n'a pas été généré.
 *
 * Pattern : prefer webp pour <source type="image/webp"> ; original pour <img src>.
 */

if (! function_exists('dictionary_hero_image_url')) {
    /**
     * Retourne l'URL absolue du hero_image avec fallback chain :
     *   - $preferWebp=true  : .webp si existe sur disque, sinon original.
     *   - $preferWebp=false : original si existe, sinon .webp si existe.
     *   - null si ni l'un ni l'autre n'existe ou hero_image vide.
     */
    function dictionary_hero_image_url(?string $heroImage, bool $preferWebp = false): ?string
    {
        if (! $heroImage) {
            return null;
        }

        $webpPath = preg_replace('/\.[^.]*$/', '.webp', $heroImage);

        if ($preferWebp && is_string($webpPath) && file_exists(public_path($webpPath))) {
            return asset($webpPath);
        }

        if (file_exists(public_path($heroImage))) {
            return asset($heroImage);
        }

        if (is_string($webpPath) && file_exists(public_path($webpPath))) {
            return asset($webpPath);
        }

        return null;
    }
}

if (! function_exists('dictionary_hero_image_webp_url')) {
    /**
     * Retourne l'URL du .webp seulement s'il existe sur disque, sinon null.
     * Pratique pour conditionner un <source type="image/webp"> sans risque de mismatch.
     */
    function dictionary_hero_image_webp_url(?string $heroImage): ?string
    {
        if (! $heroImage) {
            return null;
        }

        $webpPath = preg_replace('/\.[^.]*$/', '.webp', $heroImage);

        if (is_string($webpPath) && file_exists(public_path($webpPath))) {
            return asset($webpPath);
        }

        return null;
    }
}
