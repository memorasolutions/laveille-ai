<?php

declare(strict_types=1);

namespace Modules\Tools\Models\Concerns;

trait Shareable
{
    protected function extractKeywords(): string
    {
        $stopwords = ['de', 'la', 'le', 'les', 'du', 'des', 'et', 'ou', 'pour', 'avec', 'sans', 'un', 'une', 'l', 'd'];
        $words = array_filter(
            explode(' ', strtolower($this->name)),
            fn ($word) => !in_array($word, $stopwords, true) && strlen($word) > 2
        );
        $keywords = array_slice($words, 0, 3);
        return empty($keywords) ? 'productivite, efficacite, innovation' : implode(', ', $keywords);
    }

    public function getShareData(): array
    {
        $title = $this->name . ' | Outil gratuit IA';
        $description = $this->description
            ? \Illuminate\Support\Str::limit(strip_tags($this->description), 200)
            : 'Outil IA gratuit laveille.ai';
        $url = url('/outils/' . $this->slug . '?utm_source=share&utm_medium=clipboard');
        $hashtags = ['#LaVeilleDeStef', '#IAQuebec', '#OutilsIA'];
        $keywords = $this->extractKeywords();
        $clipboardText = "\xF0\x9F\x9A\x80 " . $title . "\n\n" . $description . "\n\n\xE2\x9C\xA8 Pratique pour " . $keywords . "\n\xF0\x9F\x94\x97 " . $url . "\n\n" . implode(' ', $hashtags);
        $ogImage = $this->featured_image && file_exists(public_path($this->featured_image))
            ? url($this->featured_image) . '?v=' . filemtime(public_path($this->featured_image))
            : url('images/og-image.png');

        return [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'hashtags' => $hashtags,
            'keywords' => $keywords,
            'clipboard_text' => $clipboardText,
            'og_image' => $ogImage,
            'og_type' => 'article',
            'meta_description' => $description,
            'share_text' => $clipboardText,
        ];
    }
}
