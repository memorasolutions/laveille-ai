<?php

declare(strict_types=1);

namespace Modules\Editor\Services;

use Modules\Editor\Models\Shortcode;

class ShortcodeService
{
    private ?array $shortcodes = null;

    public function loadShortcodes(): void
    {
        $this->shortcodes = Shortcode::active()->get()->keyBy('tag')->toArray();
    }

    public function render(string $content): string
    {
        if (! preg_match('/\[(\w+)/', $content)) {
            return $content;
        }

        if ($this->shortcodes === null) {
            $this->loadShortcodes();
        }

        return preg_replace_callback(
            '/\[(\w+)((?:\s+\w+="[^"]*")*)\](?:([\s\S]*?)\[\/\1\])?/',
            function ($matches) {
                $tag = $matches[1];
                $attributeString = $matches[2] ?? '';
                $innerContent = $matches[3] ?? '';

                if (! isset($this->shortcodes[$tag])) {
                    return $matches[0];
                }

                $shortcode = $this->shortcodes[$tag];
                $attributes = $this->parseAttributes($attributeString);
                $htmlTemplate = $shortcode['html_template'] ?? '';

                $replacements = [];
                foreach ($attributes as $key => $value) {
                    $replacements['{{ $'.$key.' }}'] = $value;
                }
                $replacements['{{ $content }}'] = $innerContent;

                return str_replace(array_keys($replacements), array_values($replacements), $htmlTemplate);
            },
            $content
        );
    }

    public function parseAttributes(string $attributeString): array
    {
        $attributes = [];
        if (preg_match_all('/(\w+)="([^"]*)"/', $attributeString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[2];
            }
        }

        return $attributes;
    }
}
