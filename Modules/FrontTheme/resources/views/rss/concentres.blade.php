<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
    <title>{{ $feedTitle }}</title>
    <link>{{ $siteUrl }}</link>
    <description>{{ $feedDescription }}</description>
    <language>fr-CA</language>
    <copyright>© {{ date('Y') }} La veille — Memora solutions</copyright>
    <managingEditor>info@memora.ca (Stéphane Lapointe)</managingEditor>
    <webMaster>info@memora.ca (Memora solutions)</webMaster>
    <generator>Laravel + La veille (laveille.ai)</generator>
    <lastBuildDate>{{ $lastBuildDate }}</lastBuildDate>
    <atom:link href="{{ $feedUrl }}" rel="self" type="application/rss+xml" />
    <image>
        <url>{{ url('images/logo.png') }}</url>
        <title>{{ $feedTitle }}</title>
        <link>{{ $siteUrl }}</link>
    </image>

    @foreach($articles as $article)
        @php
            $url = $article->getPublicUrl();
            $excerptRaw = $article->getTranslation('excerpt', 'fr_CA') ?: $article->getTranslation('excerpt', 'fr') ?: '';
            $contentRaw = $article->getTranslation('content', 'fr_CA') ?: $article->getTranslation('content', 'fr') ?: '';
            // Mode teaser : 250 mots max, pas de full content
            $teaser = $excerptRaw ?: \Illuminate\Support\Str::words(strip_tags($contentRaw), 50, '...');
            $teaser = trim(strip_tags($teaser));
            $title = $article->getTranslation('title', 'fr_CA') ?: $article->getTranslation('title', 'fr') ?: '';
            $authorName = $article->getAuthorName() ?? 'Stéphane Lapointe';
            $pubDate = $article->published_at?->toRfc2822String() ?? now()->toRfc2822String();
            $guid = $url;
        @endphp
        <item>
            <title>{{ $title }}</title>
            <link>{{ $url }}</link>
            <guid isPermaLink="true">{{ $guid }}</guid>
            <pubDate>{{ $pubDate }}</pubDate>
            <dc:creator>{{ $authorName }}</dc:creator>
            <description><![CDATA[{{ $teaser }}

→ Lire l'article complet : {{ $url }}]]></description>
            @if($article->blogCategory && $article->blogCategory->name)
            <category>{{ $article->blogCategory->name }}</category>
            @endif
        </item>
    @endforeach
</channel>
</rss>
