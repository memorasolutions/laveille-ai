<?php

declare(strict_types=1);

namespace Modules\SEO\Services;

class SeoService
{
    protected string $title = '';

    protected string $description = '';

    protected string $keywords = '';

    protected string $ogImage = '';

    protected string $canonicalUrl = '';

    protected string $robots = 'index, follow';

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function setKeywords(string $keywords): static
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function setOgImage(string $ogImage): static
    {
        $this->ogImage = $ogImage;

        return $this;
    }

    public function setCanonicalUrl(string $canonicalUrl): static
    {
        $this->canonicalUrl = $canonicalUrl;

        return $this;
    }

    public function setRobots(string $robots): static
    {
        $this->robots = $robots;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMetaTags(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'og:title' => $this->title,
            'og:description' => $this->description,
            'og:image' => $this->ogImage,
            'robots' => $this->robots,
            'canonical' => $this->canonicalUrl,
        ]);
    }

    public function renderMetaTags(): string
    {
        $html = '';

        if ($this->title) {
            $html .= '<title>'.e($this->title).'</title>'."\n";
        }

        if ($this->description) {
            $html .= '<meta name="description" content="'.e($this->description).'">'."\n";
        }

        if ($this->keywords) {
            $html .= '<meta name="keywords" content="'.e($this->keywords).'">'."\n";
        }

        if ($this->robots) {
            $html .= '<meta name="robots" content="'.e($this->robots).'">'."\n";
        }

        if ($this->title) {
            $html .= '<meta property="og:title" content="'.e($this->title).'">'."\n";
        }

        if ($this->description) {
            $html .= '<meta property="og:description" content="'.e($this->description).'">'."\n";
        }

        if ($this->ogImage) {
            $html .= '<meta property="og:image" content="'.e($this->ogImage).'">'."\n";
        }

        if ($this->canonicalUrl) {
            $html .= '<link rel="canonical" href="'.e($this->canonicalUrl).'">'."\n";
        }

        return $html;
    }

    public function generateRobotsTxt(bool $allowAll = true): string
    {
        $content = "User-agent: *\n";

        if ($allowAll) {
            $content .= "Allow: /\n";
        } else {
            $content .= "Disallow: /\n";
        }

        $content .= "\nSitemap: ".url('/sitemap.xml')."\n";

        return $content;
    }
}
