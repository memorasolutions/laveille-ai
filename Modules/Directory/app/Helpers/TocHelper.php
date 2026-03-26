<?php

declare(strict_types=1);

namespace Modules\Directory\Helpers;

use Illuminate\Support\Str;

class TocHelper
{
    /**
     * Parse HTML, add IDs to H2 tags, and extract table of contents.
     *
     * @return array{html: string, toc: array<int, array{id: string, title: string}>}
     */
    public static function generate(string $html): array
    {
        $toc = [];
        $usedSlugs = [];

        $modifiedHtml = preg_replace_callback('/<h2([^>]*)>(.*?)<\/h2>/is', function ($matches) use (&$toc, &$usedSlugs) {
            $attrs = $matches[1];
            $title = trim(strip_tags($matches[2]));
            $slug = Str::slug($title);

            // Avoid duplicates
            $original = $slug;
            $i = 1;
            while (in_array($slug, $usedSlugs, true)) {
                $slug = $original . '-' . $i++;
            }
            $usedSlugs[] = $slug;

            $toc[] = ['id' => $slug, 'title' => $title];

            // Preserve existing attributes, add id
            if (str_contains($attrs, 'id=')) {
                return $matches[0]; // Already has an id
            }

            return '<h2 id="' . $slug . '"' . $attrs . '>' . $matches[2] . '</h2>';
        }, $html);

        return ['html' => $modifiedHtml ?? $html, 'toc' => $toc];
    }
}
