<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
    <title>{{ $feedTitle }}</title>
    <link>{{ $siteUrl }}</link>
    <description>{{ $feedDescription }}</description>
    <language>fr-CA</language>
    <copyright>© {{ date('Y') }} La veille — Memora solutions</copyright>
    <managingEditor>info@memora.ca (Stéphane Lapointe)</managingEditor>
    <generator>Laravel + La veille (laveille.ai)</generator>
    <lastBuildDate>{{ $lastBuildDate }}</lastBuildDate>
    <atom:link href="{{ $feedUrl }}" rel="self" type="application/rss+xml" />
    <image>
        <url>{{ url('images/logo.png') }}</url>
        <title>{{ $feedTitle }}</title>
        <link>{{ $siteUrl }}</link>
    </image>

    @foreach($tools as $tool)
        @php
            $url = $tool->getPublicUrl();
            $name = $tool->name;
            $shortRaw = $tool->getTranslation('short_description', 'fr_CA') ?: $tool->getTranslation('short_description', 'fr') ?: '';
            $longRaw = $tool->getTranslation('description', 'fr_CA') ?: $tool->getTranslation('description', 'fr') ?: '';
            $teaser = $shortRaw ?: \Illuminate\Support\Str::words(strip_tags($longRaw), 50, '...');
            $teaser = trim(strip_tags($teaser));

            $pricing = $tool->pricing ?: 'inconnu';
            $catNames = $tool->categories->pluck('name')->take(3)->implode(', ');
            $eduInfo = $tool->has_education_pricing
                ? ' [Programme éducation : ' . ($tool->education_pricing_type ?? 'oui') . ']'
                : '';

            $pubDate = $tool->created_at?->toRfc2822String() ?? now()->toRfc2822String();
            $guid = $url;
        @endphp
        <item>
            <title>{{ $name }}</title>
            <link>{{ $url }}</link>
            <guid isPermaLink="true">{{ $guid }}</guid>
            <pubDate>{{ $pubDate }}</pubDate>
            <description><![CDATA[{{ $teaser }}{{ $eduInfo }}

Tarification : {{ $pricing }}.@if($catNames) Catégorie : {{ $catNames }}.@endif

→ Voir la fiche complète sur La veille : {{ $url }}]]></description>
            @foreach($tool->categories->take(3) as $cat)
            <category>{{ $cat->name }}</category>
            @endforeach
        </item>
    @endforeach
</channel>
</rss>
