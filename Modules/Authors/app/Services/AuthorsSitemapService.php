<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Illuminate\Support\Facades\File;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use XMLWriter;

final class AuthorsSitemapService
{
    public function generate(): string
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $xmlWriter->startDocument('1.0', 'UTF-8');
        $xmlWriter->startElement('urlset');
        $xmlWriter->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xmlWriter->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');

        $this->writeAuthorUrls($xmlWriter);
        $this->writeAuthorPostUrls($xmlWriter);

        $xmlWriter->endElement();
        $xmlWriter->endDocument();

        return $xmlWriter->outputMemory();
    }

    public function generateAndStore(): string
    {
        $xml = $this->generate();
        $path = public_path('sitemap-authors.xml');
        File::put($path, $xml);

        return $path;
    }

    private function writeAuthorUrls(XMLWriter $xmlWriter): void
    {
        $authors = AuthorProfile::whereNull('archived_at')->get();

        foreach ($authors as $author) {
            $xmlWriter->startElement('url');
            $xmlWriter->writeElement('loc', url("/@{$author->slug}"));
            $xmlWriter->writeElement('lastmod', $author->updated_at->toIso8601String());
            $xmlWriter->writeElement('changefreq', 'weekly');
            $xmlWriter->writeElement('priority', '0.8');
            $xmlWriter->endElement();
        }
    }

    private function writeAuthorPostUrls(XMLWriter $xmlWriter): void
    {
        $posts = AuthorPost::published()
            // ACTION : le plan de site porte les articles publics ET « abonnes » (dont
            // l'extrait est visible de tous), jamais les « premium ».
            // MCP: SELF (<5 lignes)
            // RAISON : ticket #2445. Indexer une page dont le visiteur ne verra RIEN
            // reviendrait a montrer aux moteurs ce qu'on refuse au lecteur.
            ->whereIn('visibility', [AuthorPost::VISIBILITY_PUBLIC, AuthorPost::VISIBILITY_SUBSCRIBERS])
            ->whereHas('authorProfile', fn ($q) => $q->whereNull('archived_at'))
            ->with('authorProfile')
            ->get();

        foreach ($posts as $post) {
            if (! $post->authorProfile) {
                continue;
            }
            $xmlWriter->startElement('url');
            $xmlWriter->writeElement('loc', url("/@{$post->authorProfile->slug}/{$post->slug}"));
            // ACTION : updated_at est réécrit par une simple consultation (Modules\Core\Services\
            // ViewCounterService::record()) - editorialModifiedAt() (Modules\Core\Traits\
            // TracksEditorialModification) ne bouge que si le CONTENU a réellement changé.
            // MCP: SELF (<5 lignes)
            // RAISON: docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md, MESURE B.
            $xmlWriter->writeElement('lastmod', $post->editorialModifiedAt()->toIso8601String());
            $xmlWriter->writeElement('changefreq', 'monthly');
            $xmlWriter->writeElement('priority', '0.6');
            $xmlWriter->endElement();
        }
    }
}
