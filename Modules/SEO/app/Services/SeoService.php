<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Services;

use Modules\SEO\Models\MetaTag;

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

    public function loadFromUrl(string $url): static
    {
        $metaTag = MetaTag::findForUrl($url);

        if ($metaTag) {
            $this->title = $metaTag->title ?? '';
            $this->description = $metaTag->description ?? '';
            $this->keywords = $metaTag->keywords ?? '';
            $this->ogImage = $metaTag->og_image ?? '';
            $this->canonicalUrl = $metaTag->canonical_url ?? '';
            $this->robots = $metaTag->robots ?? 'index, follow';
        }

        return $this;
    }

    /**
     * Sert le robots.txt du site.
     *
     * Cette méthode est une VOIE DE SECOURS, pas la source de vérité : le serveur web sert
     * public/robots.txt directement, sans jamais atteindre l'application. Elle ne s'exécute donc
     * que si ce fichier vient a disparaître.
     *
     * Elle LIT ce fichier plutôt que de réécrire des règles en parallèle. Une version antérieure
     * en tenait sa propre copie, bien plus permissive : elle n'interdisait ni /decido/, ni /user,
     * ni /dashboard, et ne connaissait aucun robot d'IA. Le jour ou elle se serait réveillée, la
     * protection des sondages serait tombée sans qu'aucune alerte ne se déclenche.
     *
     * A défaut de fichier, le repli FERME plutôt qu'il n'ouvre : mieux vaut un site temporairement
     * invisible qu'un espace prive temporairement récoltable.
     */
    public function generateRobotsTxt(): string
    {
        $fichier = public_path('robots.txt');

        if (is_readable($fichier)) {
            return (string) file_get_contents($fichier);
        }

        return implode("\n", [
            '# Repli : public/robots.txt est introuvable. On ferme, on n\'ouvre pas.',
            'User-agent: *',
            'Disallow: /',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ]);
    }
}
